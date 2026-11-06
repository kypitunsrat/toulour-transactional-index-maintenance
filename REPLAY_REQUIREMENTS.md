# Reproduction boundaries and replay prerequisites

## Tier A — Saved-result reproducibility (public and runnable now)

Requirements: Python 3, no packages, no SQL server, no project checkout.

```cmd
cd <this repository folder>
python TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py
python verify_public_package.py
```

A PASS recomputes *saved* numerical claims but is not a new independent ground-truth proof.

## Cross-study data inputs and baseline evidence

The approved-only lexical subset is available in the separate VOICE research archive, subject to the source and rights notices described in `DATA_AVAILABILITY.md`. **Those approved 4,587 records do not reconstruct the complete 5,687-record original database and W/M/T state.** For exact historical baseline lineage see `CROSS_STUDY_DATASET_PROVENANCE.md` and `STAGE22_BASELINE_LOCK_MANIFEST.json`.

Public read-only metadata verification (checks six Stage22C/D raw report headers, counts and digests; does not need source data):

```cmd
python tools/verify_stage22_baseline_reports.py
```

The public check compares reports to a previously verified logical manifest; it is not a new private-SQL hash calculation and cannot verify absent transient mysqldumps.

## Tier B — Inspection of the historical experiment source

Three snapshotted runners and BAT menus are located in `experimental_runner_snapshots/22C`, `/22D`, and `/22E`. The PHP computation files preserve historical bytes. The published BAT/README operator guidance was normalized without changing its command logic; see PUBLICATION_NOTES.md. The code retains local XAMPP paths, root user assumptions, and destructive disposable work DB restoration/drop logic. **Do not run them from this repository or on production systems**.

Those scripts require the exact originally frozen source version, historical ABC runner, and the baseline project directory `kamus_tolour_index_v1`. Hash guards refuse altered inputs. This archive intentionally excludes those dependencies and source-derived database records. Consequently, **Tier B is code inspectability, not a runnable clean-room reproduction**.

## Tier C — Synthetic local logical replay (provided in Stage 4)

The new `synthetic_replay/` folder is **independently runnable** from a Python 3 standard-library installation. It starts from 280 invented rows (160 approved, 120 pending) in SQLite `:memory:`, runs deterministic batch mutations for both policies, compares 18 resulting index pairs to from-source materialization and each other, and checks transaction rollback. The Windows `JALANKAN_REPLAY_SINTETIS.bat` uses `py -3` where necessary. No project code/database is modified. This is a **limited model**, not a validated MariaDB/PHP reproduction; the word/meaning normalization on arbitrary Unicode, `FOR UPDATE`, concurrent conflicts and real-corpus timing remain untested.

## Tier D — Exact historical PHP/MariaDB reproduction (NOT provided)

An exact historical replay would require the following, which are intentionally absent from the public release:

1. Explicitly licensed sanitized project source (`IndexStage3IncrementalMaintainer`, materializer, dictionary model and historical comparator), with no secrets or sensitive routes.
2. Releasable schema + **independently designed non-copyrighted synthetic fixture data** (or permission-cleared real corpus); a synthetic corpus can demonstrate correct function but **does not independently reproduce the real-corpus timing numbers**.
3. A parameterized non-root restricted DB account and isolated disposable test database with destructive permissions limited to a known prefix.
4. A clear independent full-projection rebuild/oracle, data/fixture generator, DB version and config, reproducible software versions, and independent replication run records.

## Checks required before any new public release

- Confirm software license rights with all code owners.
- Review all data for lexical words/glosses, secrets, personal identities, IP/hostnames, user-specific paths, and assessment records.
- Run an independent verifier on publicly shareable synthetic fixtures.
- Publish a Git tag or release, freeze SHA-256 hashes, then cite the **real** canonical URL and commit SHA in the paper. Do not prefill an imagined commit hash/DOI.
