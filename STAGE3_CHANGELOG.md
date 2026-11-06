# Stage 3 journal-neutral documentation update — 6 November 2026

## Changes

1. Updated indexing README title to **Transactional maintenance of bidirectional lexical indexes: consistency and write–latency trade-offs in a Toulour–Indonesian dictionary** (sentence case; manuscript title, not claim of publication).
2. Added `CROSS_STUDY_DATASET_PROVENANCE.md` and a text-free `STAGE22_BASELINE_LOCK_MANIFEST.json` documenting matching of the VOICE approved lexical subset and the archived Stage22 source/W/M/T baseline digests.
3. Added `DATA_AVAILABILITY.md` that links to the separate VOICE lexical fixture and states explicitly that neither a complete 5,687-row DB nor standalone PHP/MariaDB replay is supplied.
4. Updated `PROVENANCE_AND_RIGHTS.md` following VOICE's documented attribution basis: original 1985 book copyright preserved; published ministry repository CC BY-NC 4.0 statement cited for source-derived noncommercial material, item All Rights Reserved and printed restrictions retained, no invented permission and no blanket code license.
5. Updated `REPLAY_REQUIREMENTS.md`, `PUBLICATION_NOTES.md`, and archive checksum inventory. Added a read-only consistency checker and `JALANKAN_AUDIT_REPO.bat` for one-command CMD verification.

## Immutable historical evidence

- `raw_reports/**`: **unchanged bytes**
- `experimental_runner_snapshots/**`: **unchanged bytes**
- `historical_comparator_26pairs/**`: **unchanged bytes**
- `TOULOUR_INDEX_S3_PAIRED_180.csv`, `TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py`, and its saved evidence hash manifest: **unchanged bytes**
- VOICE repository: **unchanged**

This patch is a release-candidate for the repository owner to review and commit; it does **not** assert changes have already been pushed to GitHub or that the repository's previously cited Git commit now contains these new files. Cite the new actual commit only after upload. No new benchmark was executed.
