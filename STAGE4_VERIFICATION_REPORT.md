# Stage 4 verification — 2026-11-06

This report describes a successful **synthetic SQLite-only** test in the authoring environment. It is **not** an independent external reproduction or a certification of the original PHP/MariaDB code.

- 18 method pairs: 3 batch sizes (1, 10, 100) x 6 mutation types (word, meaning, category, unpublish, publish, no-op).
- 36 full-projection oracle evaluations after mutations, plus fresh-baseline checks.
- Within each pair, selectively refreshed and differentially maintained indexes have matching SHA-256 content digests and source digests; metadata counts and all W/M/T rows agree.
- Two injected-error rollback tests (one per policy) return the dictionary and index to their exact pre-transaction logical digests.
- A deliberately removed W posting is detected by the from-source rebuild oracle; test transaction is rolled back.
- No meaningful MariaDB latency measurement: this test **deliberately reports logical postings written**, not milliseconds.
- Original 180 Stage22C/D saved-result checks and six baseline hashes remain separately verifiable.

Sample saved output: `synthetic_replay/example_results/summary.json` and `synthetic_replay/example_results/synthetic_pairs.csv`. Running locally writes results to a new folder in the system temporary directory.

**Caveats:** SQLite `BEGIN IMMEDIATE` is not InnoDB `SELECT ... FOR UPDATE`; normalizer is limited to ASCII examples; full-projection oracle shares the implementation's projection generator and cannot uncover all common-mode semantic errors. Cross-database performance comparisons are inappropriate. Real corpus baseline remains in the frozen Stage22 reports and VOICE-linked research evidence, not in this package.
