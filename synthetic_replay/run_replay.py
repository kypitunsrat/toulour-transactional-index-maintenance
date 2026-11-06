#!/usr/bin/env python3
"""Safe synthetic *logical* index maintenance replay. Python 3 stdlib only.

Based on Stage 2 IndexStage2Materializer (W/M/T) and Stage 3 differential
maintenance behavior; B2/C2 posting policies use matched pre-reads.
Each method receives an independent pristine, SQLite :memory: DB and the
same deterministic synthetic mutations. No MariaDB, real corpus, network,
DROP, CREATE DATABASE, production tables, or real timing claims.

Not a byte-exact port of PHP UTF-8 normalization/locking, or an independent
reproduction of Stage22C/22D MariaDB latency.
"""
from __future__ import annotations
import argparse
import csv
import hashlib
import json
import re
import sqlite3
import sys
import tempfile
import unicodedata
from pathlib import Path

SCHEMA_VERSION = "index_materialization_stage2_v1"
STOPWORDS = frozenset("yang di ke dari pada dalam untuk dengan dan atau ini itu adalah sebuah suatu tentang seperti sangat paling yg".split())


def sha(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def json_bytes(data) -> bytes:
    return json.dumps(data, ensure_ascii=False, separators=(",", ":")).encode("utf-8")


def norm(s: str) -> str:
    """ASCII fixture subset of original PHP normalizer; Unicode features not certified."""
    s = unicodedata.normalize("NFC", s.strip())
    for ch in ("\u2018", "\u2019", "\u02bc", "`"):
        s = s.replace(ch, "'")
    s = s.lower()
    s = re.sub(r"[^a-z0-9\s'\-]", " ", s)  # Intentionally ASCII-only fixture.
    return " ".join(s.split())


def tokenize(s: str) -> list[str]:
    out = []
    for token in norm(s).split():
        token = token.strip("-' ")
        if token and not token.isnumeric() and token not in STOPWORDS and token not in out:
            out.append(token)
    return out


def projection(row: dict) -> tuple[tuple, dict]:
    word, meaning, cat = norm(row["tolour_word"]), norm(row["indonesian_meaning"]), int(row["category_id"])
    content_hash = hashlib.sha256(json_bytes([cat, word, meaning])).hexdigest()
    ent = (int(row["id"]), cat, word, meaning, content_hash)
    post = {}
    for kind, term in [("W", word), ("M", meaning)] + [("T", t) for t in tokenize(meaning)]:
        if term:
            key = (kind, sha(term))
            if key in post:
                assert post[key] == term
            post[key] = term
    return ent, post


def fixtures() -> list[dict]:
    # INVENTED entries, not digitized vocabulary; 160 approved and 120 pending.
    rows = []
    tones = ["merah", "biru", "hijau", "kuning", "hitam"]
    for i in range(1, 281):
        rows.append({"id": i, "category_id": 1 + i % 5,
                     "status": "disetujui" if i <= 160 else "pending",
                     "tolour_word": f"lemma{i:04d}",
                     "indonesian_meaning": f"contoh buatan benda {tones[i % 5]} untuk latihan indeks"})
    return rows


def db_new() -> sqlite3.Connection:
    con = sqlite3.connect(":memory:")
    con.execute("PRAGMA foreign_keys=ON")
    con.executescript((Path(__file__).resolve().parent / "schema_sqlite.sql").read_text(encoding="utf-8"))
    return con


def fetch_row(con: sqlite3.Connection, rid: int) -> dict:
    row = con.execute("SELECT id, category_id, status, tolour_word, indonesian_meaning FROM dictionary WHERE id=?", (rid,)).fetchone()
    if row is None:
        raise ValueError(f"Unknown fixture ID {rid}")
    return dict(zip(("id", "category_id", "status", "tolour_word", "indonesian_meaning"), row))


def source_digest(con: sqlite3.Connection) -> str:
    rows = con.execute("SELECT id,category_id,status,tolour_word,indonesian_meaning FROM dictionary ORDER BY id").fetchall()
    return hashlib.sha256(json_bytes(rows)).hexdigest()


def initialize(con: sqlite3.Connection) -> None:
    data = fixtures()
    con.executemany("INSERT INTO dictionary VALUES (:id,:category_id,:status,:tolour_word,:indonesian_meaning)", data)
    en, terms = 0, 0
    for row in data:
        if row["status"] != "disetujui":
            continue
        entry, postings = projection(row)
        con.execute("INSERT INTO idx_stage2_entries VALUES (?,?,?,?,?)", entry)
        con.executemany("INSERT INTO idx_stage2_terms VALUES (?,?,?,?)", [(row["id"], kind, keyhash, term) for (kind, keyhash), term in postings.items()])
        en += 1
        terms += len(postings)
    con.execute("INSERT INTO idx_stage2_meta VALUES (1,?,?,?,?)", (SCHEMA_VERSION, source_digest(con), en, terms))
    con.commit()


def state(con: sqlite3.Connection):
    entries = con.execute("SELECT dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256 FROM idx_stage2_entries ORDER BY dictionary_id").fetchall()
    postings = con.execute("SELECT dictionary_id,kind,term_sha256,normalized_term FROM idx_stage2_terms ORDER BY dictionary_id,kind,term_sha256").fetchall()
    meta = con.execute("SELECT schema_version,materialized_entries,materialized_terms FROM idx_stage2_meta WHERE id=1").fetchone()
    return entries, postings, meta


def state_digest(con: sqlite3.Connection) -> str:
    return hashlib.sha256(json_bytes(state(con))).hexdigest()


def independent_oracle(con: sqlite3.Connection) -> None:
    """Rebuild from the mutated source using separate SELECTs, not incremental diffs.
    Projection function is still shared, so this is not an independent NLP oracle.
    """
    source = con.execute("SELECT id,category_id,status,tolour_word,indonesian_meaning FROM dictionary ORDER BY id").fetchall()
    expected_entries, expected_terms = [], []
    for rid, cat, status, word, meaning in source:
        if status != "disetujui":
            continue
        entry, postings = projection({"id": rid, "category_id": cat, "tolour_word": word, "indonesian_meaning": meaning})
        expected_entries.append(entry)
        expected_terms.extend((rid, kind, tokenhash, token) for (kind, tokenhash), token in postings.items())
    expected_terms.sort(key=lambda x: (x[0], x[1], x[2]))
    entries, terms, meta = state(con)
    assert entries == expected_entries, "INDEX_ENTRY_ORACLE_MISMATCH"
    assert terms == expected_terms, "INDEX_POSTING_ORACLE_MISMATCH"
    assert meta == (SCHEMA_VERSION, len(expected_entries), len(expected_terms)), "INDEX_META_ORACLE_MISMATCH"
    assert con.execute("PRAGMA foreign_key_check").fetchall() == [], "FK_VIOLATION"


def plan(n: int, kind: str) -> list[tuple[int, str]]:
    assert n in (1, 10, 100)
    if kind == "publish":
        return [(161 + i, kind) for i in range(n)]
    return [(1 + i, kind) for i in range(n)]


def mutate_source(con: sqlite3.Connection, rid: int, kind: str) -> dict:
    row = fetch_row(con, rid)
    if kind == "word":
        row["tolour_word"] += "baru"
    elif kind == "meaning":
        row["indonesian_meaning"] = row["indonesian_meaning"].replace("untuk latihan", "untuk simulasi")
    elif kind == "category":
        row["category_id"] += 10
    elif kind == "unpublish":
        assert row["status"] == "disetujui"
        row["status"] = "pending"
    elif kind == "publish":
        assert row["status"] == "pending"
        row["status"] = "disetujui"
    elif kind == "noop":
        pass
    else:
        raise AssertionError("Unknown mutation")
    con.execute("UPDATE dictionary SET category_id=?,status=?,tolour_word=?,indonesian_meaning=? WHERE id=?",
                (row["category_id"], row["status"], row["tolour_word"], row["indonesian_meaning"], rid))
    return row


def apply_batch(con: sqlite3.Connection, changes: list[tuple[int, str]], method: str, force_rollback: bool = False) -> dict:
    """Caller-owned atomic source+index transaction. The methods differ ONLY in posting DML policy.
    Both use the same pre-reads. SQLite BEGIN IMMEDIATE is NOT MySQL FOR UPDATE.
    """
    if method not in ("refresh", "differential"):
        raise ValueError("Invalid method")
    stat = {"entry_writes": 0, "postings_deleted": 0, "postings_inserted": 0, "changed_rows": 0}
    if len({r for r, _ in changes}) != len(changes):
        raise ValueError("Duplicate IDs in batch")
    con.execute("BEGIN IMMEDIATE")
    try:
        meta = con.execute("SELECT schema_version,materialized_entries,materialized_terms FROM idx_stage2_meta WHERE id=1").fetchone()
        assert meta and meta[0] == SCHEMA_VERSION
        delta_entries, delta_postings = 0, 0
        for rid, kind in changes:
            row = mutate_source(con, rid, kind)
            old_entry = con.execute("SELECT dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256 FROM idx_stage2_entries WHERE dictionary_id=?", (rid,)).fetchone()
            old_rows = con.execute("SELECT kind,term_sha256,normalized_term FROM idx_stage2_terms WHERE dictionary_id=? ORDER BY kind,term_sha256", (rid,)).fetchall()
            old_post = {(k, h): term for k, h, term in old_rows}
            assert old_entry is not None or not old_post, "ORPHAN_POSTING"
            new_entry, new_post = (projection(row) if row["status"] == "disetujui" else (None, {}))
            for k in old_post.keys() & new_post.keys():
                assert old_post[k] == new_post[k], "HASH_COLLISION_OR_CORRUPT_POSTING"
            # A change to source that does not change any projection is a no-op for both arms.
            if old_entry == new_entry and old_post == new_post:
                continue
            stat["changed_rows"] += 1
            delta_entries += int(new_entry is not None) - int(old_entry is not None)
            delta_postings += len(new_post) - len(old_post)
            if new_entry is None:
                if old_entry is not None:
                    con.execute("DELETE FROM idx_stage2_entries WHERE dictionary_id=?", (rid,))
                    stat["entry_writes"] += 1
                    stat["postings_deleted"] += len(old_post)  # SQLite FK cascade, counted logically.
                continue
            if old_entry is None:
                con.execute("INSERT INTO idx_stage2_entries VALUES (?,?,?,?,?)", new_entry)
                stat["entry_writes"] += 1
            elif old_entry != new_entry:
                con.execute("UPDATE idx_stage2_entries SET category_id=?,normalized_word=?,normalized_meaning=?,content_sha256=? WHERE dictionary_id=?",
                            (*new_entry[1:], rid))
                stat["entry_writes"] += 1
            if method == "refresh":
                con.execute("DELETE FROM idx_stage2_terms WHERE dictionary_id=?", (rid,))
                stat["postings_deleted"] += len(old_post)
                con.executemany("INSERT INTO idx_stage2_terms VALUES (?,?,?,?)",
                                [(rid, kind, tokenhash, term) for (kind, tokenhash), term in sorted(new_post.items())])
                stat["postings_inserted"] += len(new_post)
            else:
                for k in sorted(old_post.keys() - new_post.keys()):
                    deleted = con.execute("DELETE FROM idx_stage2_terms WHERE dictionary_id=? AND kind=? AND term_sha256=?", (rid, *k))
                    assert deleted.rowcount == 1
                    stat["postings_deleted"] += 1
                for k in sorted(new_post.keys() - old_post.keys()):
                    con.execute("INSERT INTO idx_stage2_terms VALUES (?,?,?,?)", (rid, *k, new_post[k]))
                    stat["postings_inserted"] += 1
        con.execute("UPDATE idx_stage2_meta SET materialized_entries=materialized_entries+?,materialized_terms=materialized_terms+? WHERE id=1",
                    (delta_entries, delta_postings))
        if force_rollback:
            raise RuntimeError("INJECTED_FAILURE_FOR_ROLLBACK_TEST")
        con.commit()
        return stat
    except Exception:
        con.rollback()
        raise


def test_rollback() -> None:
    for method in ("refresh", "differential"):
        c = db_new()
        try:
            initialize(c)
            before = (source_digest(c), state_digest(c))
            try:
                apply_batch(c, plan(10, "meaning"), method, force_rollback=True)
            except RuntimeError as exc:
                assert str(exc) == "INJECTED_FAILURE_FOR_ROLLBACK_TEST"
            else:
                raise AssertionError("Failure injection did not raise")
            assert (source_digest(c), state_digest(c)) == before, f"ROLLBACK_FAILED {method}"
            independent_oracle(c)
        finally:
            c.close()


def test_oracle_rejects_corruption() -> None:
    c = db_new()
    try:
        initialize(c)
        c.execute("BEGIN IMMEDIATE")
        c.execute("DELETE FROM idx_stage2_terms WHERE dictionary_id=1 AND kind='W'")
        try:
            independent_oracle(c)
        except AssertionError as exc:
            assert "INDEX_POSTING_ORACLE_MISMATCH" in str(exc)
        else:
            raise AssertionError("Oracle failed to detect intentionally corrupted posting")
        finally:
            c.rollback()
        independent_oracle(c)
    finally:
        c.close()


def run(output: Path) -> dict:
    test_rollback()
    test_oracle_rejects_corruption()
    rows = []
    for n in (1, 10, 100):
        for kind in ("word", "meaning", "category", "unpublish", "publish", "noop"):
            pair = {}
            for method in ("refresh", "differential"):
                con = db_new()
                try:
                    initialize(con)
                    independent_oracle(con)
                    start_hash = source_digest(con)
                    start_index = state_digest(con)
                    stats = apply_batch(con, plan(n, kind), method)
                    independent_oracle(con)
                    pair[method] = {"stats": stats, "source_after": source_digest(con),
                                    "index_after": state_digest(con), "source_before": start_hash,
                                    "index_before": start_index,
                                    "state_after": state(con)}
                finally:
                    con.close()
            b, d = pair["refresh"], pair["differential"]
            assert b["source_before"] == d["source_before"] and b["index_before"] == d["index_before"]
            assert b["source_after"] == d["source_after"], f"SOURCE_DIVERGENCE {n} {kind}"
            assert b["index_after"] == d["index_after"], f"INDEX_DIVERGENCE {n} {kind}"
            assert b["state_after"] == d["state_after"], f"STATE_DIVERGENCE {n} {kind}"
            assert d["stats"]["postings_deleted"] + d["stats"]["postings_inserted"] <= b["stats"]["postings_deleted"] + b["stats"]["postings_inserted"]
            rows.append({"batch_size": n, "mutation_type": kind, "pairs": 1, "digest_equal": "PASS",
                         "rebuild_oracle": "PASS", "baseline_source_sha256": b["source_before"],
                         "baseline_index_sha256": b["index_before"], "final_source_sha256": b["source_after"],
                         "final_index_sha256": b["index_after"],
                         "refresh_entry_writes": b["stats"]["entry_writes"],
                         "refresh_posting_writes": b["stats"]["postings_deleted"] + b["stats"]["postings_inserted"],
                         "differential_entry_writes": d["stats"]["entry_writes"],
                         "differential_posting_writes": d["stats"]["postings_deleted"] + d["stats"]["postings_inserted"]})
            print(f"PASS  batch={n:3d} mutation={kind:<9s} "
                  f"index parity + rebuild oracle; posting DML "
                  f"refresh={rows[-1]['refresh_posting_writes']} differential={rows[-1]['differential_posting_writes']}")
    assert len(rows) == 18
    output.mkdir(parents=True, exist_ok=False)
    fields = list(rows[0])
    with (output / "synthetic_pairs.csv").open("w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=fields)
        writer.writeheader()
        writer.writerows(rows)
    summary = {"protocol": "STAGE4_SYNTHETIC_SQLITE_LOGICAL_DEMONSTRATOR_V1",
               "historical_stage22_reproduction": False,
               "mysql_mariadb_latency_reproduction": False,
               "synthetic_fixture_entries": 280, "synthetic_initial_approved": 160,
               "comparisons": len(rows), "oracle_checked": len(rows) * 2,
               "digest_parity_checked": len(rows), "injected_rollback_checks": 2,
               "corruption_detection_check": "PASS", "status": "PASS",
               "rows": rows}
    (output / "summary.json").write_text(json.dumps(summary, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    return summary


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--output", type=Path, help="Fresh output directory. Default: new system temporary directory")
    args = p.parse_args()
    out = args.output or Path(tempfile.mkdtemp(prefix="toulour_stage4_"))
    # mkdtemp already created directory. Explicit user --output must not exist.
    if args.output:
        if out.exists():
            p.error("--output must be a non-existing directory (prevents overwrite)")
    else:
        out.rmdir()
    try:
        result = run(out)
    except Exception as exc:
        print(f"FAIL: {type(exc).__name__}: {exc}", file=sys.stderr)
        return 1
    print(f"PASS: {result['comparisons']} independent synthetic method pairs, 36 rebuild-oracle checks, "
          "2 rollback tests, 1 corruption-detection test")
    print(f"OUTPUT: {out}")
    print("NOTE: NO real Toulour data; NO historical MariaDB latency measurement reproduced.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
