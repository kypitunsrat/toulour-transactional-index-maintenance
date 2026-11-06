# Stage 4 addendum — November 6, 2026

This is a **journal-neutral, source-inspection-friendly** synthetic demonstration. It does not revise VOICE Round 1 files, original RESTI article data, frozen Stage22 outputs, or application database.

New files:
- `synthetic_replay/run_replay.py`, `schema_sqlite.sql`, `schema_mariadb_REFERENCE_ONLY.sql`
- `synthetic_replay/README.md`, `SOURCE_MAPPING.md`, and deterministic `example_results/` (JSON and CSV)
- `JALANKAN_REPLAY_SINTETIS.bat` for Windows CMD (uses `py -3` first)
- `.github/workflows/synthetic-replay.yml` for Ubuntu/Windows CI checks

Updated: `README.md`, `REPLAY_REQUIREMENTS.md`, checksum inventory `SHA256_PUBLIC_FILES.json` (excluding the inventory itself to prevent recursion).

Integrity guards: historical raw JSON, historical runner source, saved result scripts, and the existing public snapshot-lock manifest must remain byte-identical. Existing Stage22C/D 180-pair checks and the standalone Stage4 18-pair checks have distinct identifiers and are not merged.

After committing to GitHub, cite the **actual new commit SHA**, not the previous frozen commit. Do not edit the already reviewed VOICE code or results solely to match documentation changes here.
