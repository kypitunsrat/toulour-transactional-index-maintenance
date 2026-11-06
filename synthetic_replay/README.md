# Stage 4: safe synthetic W/M/T transactional index replay

**What this package proves:** on a fully invented fixture, two implemented posting policies (selective row refresh vs differential posting updates) produce the same W/M/T projection and match a fresh full rebuild from the mutated source under 18 deterministic batch/mutation scenarios. Injected failures roll back **both** dictionary and index changes in SQLite. The integrity oracle is tested against an intentionally missing W posting.

**What it does NOT prove:** this is **not** an original Stage22C/22D PHP/MariaDB run, exact lexical-normalizer equivalence on all Unicode words, production concurrency or InnoDB locking behavior, identical `FOR UPDATE` semantics, or any latency improvement on the real Toulour corpus. This package does **not** independently re-create the observed 75–77% write reduction on the 4,587 approved Toulour records; its results are explicitly synthetic and must not be pooled with the 180 historical pairs.

## Setup and execution

- Python 3.9+ stdlib only; on the user's Windows 10 the tested `py -3` launcher is available.
- No XAMPP, MySQL/MariaDB, internet access, external packages, root credentials, or original database required.
- Run `JALANKAN_REPLAY_SINTETIS.bat` in the repository root via Explorer or CMD.
- Cross-platform: `python synthetic_replay/run_replay.py`.
- The script opens **SQLite `:memory:` databases exclusively**, recreates each pristine baseline in RAM, and writes just two reports (`summary.json`, `synthetic_pairs.csv`) to a **new uniquely named system TEMP directory** printed at the end. It never creates a database on disk, reads project secrets, or contacts a host.
- An optional `--output PATH` may select a **new, nonexistent** report directory; existing paths will not be overwritten.

## Model and protocol

1. Invented fixture: 280 source dictionary rows, of which 160 start approved and 120 start pending. Terms such as `lemma0001` and generic synthetic glosses are **not authentic Toulour entries**.
2. W: normalized word; M: normalized full meaning; T: distinct non-stopword meaning tokens, hashed SHA-256. Content SHA-256 combines category, normalized word and normalized meaning. Index table names and keys mirror `IndexStage2Materializer.php`'s Stage 2 W/M/T projection schema, with **SQLite type substitutions**.
3. Run same deterministic mutations for both arms from independently reset source and index: 3 batch sizes × 6 mutation types = **18 pairs**. Each pair is checked against a from-source full-projection oracle and against the other arm.
4. Both arms perform the same metadata/entry/posting pre-reads in an atomic source+index transaction. **Refresh** rewrites all postings of each changed indexed entry. **Differential** updates only changed posting keys. Deletion/unpublication uses FK cascade and counts the affected posting rows logically. No-op mutations do not trigger rewriting.
5. Additional tests inject a transaction failure after a 10-row mutation for each arm; compare complete source and index digests before and after rollback. A separate corruption test deletes one W posting inside a rollback-only transaction, requires the oracle to catch it, then restores baseline.
6. Logical posting operations are the number of `DELETE`/`INSERT` rows including SQL FK cascades, not physical storage writes. There is deliberately **no latency field** in this demonstration.

## Files

- `run_replay.py`: runnable Python/sqlite3 test harness.
- `schema_sqlite.sql`: runnable SQLite schema for the isolated in-memory fixture.
- `schema_mariadb_REFERENCE_ONLY.sql`: documentation of Stage-2 W/M/T table schema from historical source; **never executed by launcher**.
- `example_results/`: sample deterministic output generated with this test harness; not Stage22 data.
- [`SOURCE_MAPPING.md`](SOURCE_MAPPING.md): precise relations and differences to historical code.

## Constraints and licensing

This demonstrator includes **no copyrighted dictionary corpus**. VOICE source attribution and the relevant CC BY-NC 4.0 notices remain in the existing repository documentation; those terms are not asserted as a blanket license for the software in this folder. Do not interpret this illustrative fixture as an independently collected Toulour dataset. Code ownership and any separate permission to reuse code remain distinct from source-derived lexical materials.

The public repository's saved-results verifier and SHA inventory remain separate. Run `JALANKAN_AUDIT_REPO.bat` to check those results and the full inventory; run `JALANKAN_REPLAY_SINTETIS.bat` to execute the new synthetic replay.
