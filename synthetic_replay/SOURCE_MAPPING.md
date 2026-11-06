# Historical source to Stage-4 synthetic demonstrator mapping

**Inspected code:** `kamus_tolour_index_v1/services/IndexStage2Materializer.php`, `kamus_tolour_index_v1/services/IndexStage3IncrementalMaintainer.php`, and the Stage22D B2/C2 historical runner (`tests/index_stage22d_fairness.php`). The originals are **not redistributed here** as application files.

| Historical behavior | Stage-4 implementation | Scope/limitation |
|---|---|---|
| `idx_stage2_entries`, `idx_stage2_terms`, `idx_stage2_meta`, W/M/T posting keys | `schema_sqlite.sql`; `projection()` | Same conceptual columns/unique keys, adapted types/collations for SQLite |
| `normalizeText()` and `tokenizeMeaning()` | `norm()` and `tokenize()` | Tests use **ASCII-only invented terms**. Does not establish full Unicode or PHP mbstring/intl parity |
| `makeProjection()` content hash and W/M/T set | `projection()` | Same formula for ASCII synthetic fixture; does not infer NLP relevance |
| Caller-owned BEGIN/COMMIT/ROLLBACK | `apply_batch()` with `BEGIN IMMEDIATE` | Atomic dictionary and projection updates in SQLite; different lock and isolation semantics from InnoDB |
| Stage22D B2/C2 same pre-reads and metadata aggregation | `apply_batch()` for both arms | Common source+index transaction boundaries, same reads. Not byte-equivalent original PHP implementation |
| Selective refresh B2 | method `refresh` | All postings of changed entry reinserted; untouched source rows skipped |
| Differential C2 | method `differential` | Difference of `(kind,sha256)` posting sets; unchanged postings preserved |
| Correctness assertions | `independent_oracle()` and cross-arm digest test | Full from-source rebuild check **shares** projection/tokenization logic, so it cannot detect every common-mode algorithm error |
| Stage22C/D real-corpus benchmark, 180 pairs | **No Stage22C/D re-run in this package** | Synthetic 18 pairs are method demonstration only, never a claim of original empirical latency replication |

**Safety:** `run_replay.py` does not accept DSN, host, credentials or a MariaDB database name, uses only `sqlite3.connect(':memory:')`, and generates reports outside the checkout in system temporary storage by default.
