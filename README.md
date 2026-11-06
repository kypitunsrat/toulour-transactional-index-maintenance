# Transactional maintenance of Toulour–Indonesian lexical indexes

**Journal-independent reproducibility and audit package**  
*Transactional maintenance of bidirectional lexical indexes: consistency and write–latency trade-offs in a Toulour–Indonesian dictionary*  
Kenneth Yosua Palilingan, Salaki Reynaldo Joshua, Kihyeon Kwon (2026).

## Scope and main result

This repository is **separate from the [VOICE lexical retrieval project](https://github.com/kypitunsrat/toulour-selective-lexical-retrieval)**. It covers update-side consistency and index maintenance of word (W), full-meaning (M), and meaning-token (T) postings, **not** the V2.2 Fast retrieval algorithm or linguistic relevance claims.

The frozen experimental corpus has 4,587 approved records and 29,546 index postings. A cross-study audit confirms that the six-field approved lexical subset matches the archived VOICE dataset for all 4,587 entries; see [`CROSS_STUDY_DATASET_PROVENANCE.md`](CROSS_STUDY_DATASET_PROVENANCE.md). The Stage22 source and W/M/T baseline digests were independently reconstructed from a private Stage21 backup and agree with all six Stage22C/D reports. The six ephemeral per-run SQL dumps were not retained; this is a logical-baseline verification, not a byte-for-byte dump replay. Two *separate* protocols compared selective refresh vs. differential maintenance: Stage22C (the existing B/C implementations) and Stage22D (the matched-control B2/C2 ablation). Each protocol has 30 paired comparisons for batch sizes 1, 10, and 100: **90 pairs per protocol, 180 pairs overall**. All saved within-protocol paired index digests match; the reported differential policy reduces logical posting writes by approximately 75–77% but does **not** consistently reduce maintenance latency.

**Evidence tiers — do not conflate:**

1. **Recomputable now (fully provided):** check raw JSON reports, recompute medians, win counts, index-digest equality, fixture consistency and logical posting-write reductions using Python 3 (no database). 
2. **Code-inspectable (provided as snapshots):** Stage22C/D/E runner source and original CMD launchers for the recorded environment. These **are not standalone**, because the frozen application source, schema, and source-derived database are intentionally not included.
3. **Independent experimental replay:** **not available from this repository alone**. Do not claim another researcher has replicated the PHP/MariaDB transactions without the licensed dataset and application dependencies.

## Research stage coverage

The initial release emphasized Stage22B–E because these reports were supplied and audited after an earlier manuscript draft. The current release also includes **Stage22A baseline records, Stage22B summary, historical 26-pair B/C comparator evidence and the available runner snapshots**. It does **not** claim complete Stage15–21 replay; those original logs are missing from this selected public release. See [`STAGE_EVIDENCE_COVERAGE.md`](STAGE_EVIDENCE_COVERAGE.md) for a stage-by-stage evidence matrix and rights constraints.

## Fast verification

From the repository root (Python 3, standard library only):

```shell
python TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py
python verify_public_package.py
python tools/verify_stage22_baseline_reports.py
```

The first script checks all 180 saved paired comparisons from six final reports, including metadata parity of digests. The second checks the updated file inventory. The third verifies that all six stored reports agree with the separately verified Stage22 baseline manifest. These are read-only checks; none needs a database or independently recreates original SQL snapshots. On Windows, `JALANKAN_AUDIT_REPO.bat` runs all three checks from CMD.

## Contents

- [`raw_reports/`](raw_reports/): Stage22A baseline/preflight; Stage22B/B2 pilot, summary and rollback; six immutable Stage22C/D 30-pair JSONs; Stage22E offline/SQL read-only analyses and diagnostic CSV.
- [`TOULOUR_INDEX_S3_PAIRED_180.csv`](TOULOUR_INDEX_S3_PAIRED_180.csv): 180 derived paired observations for independent secondary analysis.
- [`TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py`](TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py): self-contained saved-result verifier.
- [`TOULOUR_INDEX_S3_SHA256_MANIFEST.json`](TOULOUR_INDEX_S3_SHA256_MANIFEST.json): original Stage22F/V22 saved-evidence checksums.
- [`historical_comparator_26pairs/`](historical_comparator_26pairs/): three original earlier B/C benchmark JSONs (10+8+8 pairs) and the paired 26-case CSV; a separate baseline protocol, **not** part of the 180 Stage22C/D pairs.
- [`experimental_runner_snapshots/`](experimental_runner_snapshots/): available Stage22A/B/B2/C/D/E PHP/CMD snapshots that were used locally, **not** a public setup guide or a complete runnable program.
- [`STAGE_EVIDENCE_COVERAGE.md`](STAGE_EVIDENCE_COVERAGE.md): stage-by-stage availability matrix, provenance and missing historical raw logs.
- [`ARCHIVE_SCOPE.md`](ARCHIVE_SCOPE.md): journal-independent naming and historical artifact preservation.
- [`CROSS_STUDY_DATASET_PROVENANCE.md`](CROSS_STUDY_DATASET_PROVENANCE.md): verified VOICE–indexing baseline bridge and the exact scope of the Stage22 snapshot lock.
- [`STAGE22_BASELINE_LOCK_MANIFEST.json`](STAGE22_BASELINE_LOCK_MANIFEST.json): text-free hashes, counts, and recorded report filenames.
- [`DATA_AVAILABILITY.md`](DATA_AVAILABILITY.md): dataset access, rights basis aligned with VOICE, reproducibility levels and exclusions.
- [`PROVENANCE_AND_RIGHTS.md`](PROVENANCE_AND_RIGHTS.md): dictionary copyright, CC BY-NC 4.0 source-derived-data basis, privacy and separate software terms.
- [`tools/verify_stage22_baseline_reports.py`](tools/verify_stage22_baseline_reports.py): read-only public six-report hash/count consistency check.
- [`REPLAY_REQUIREMENTS.md`](REPLAY_REQUIREMENTS.md): what a third-party replay would require, with a safe boundary between public analysis and private SQL data.
- [`PUBLICATION_NOTES.md`](PUBLICATION_NOTES.md): transparent record of documentation-only edits relative to the historical working package.
- [`SHA256_PUBLIC_FILES.json`](SHA256_PUBLIC_FILES.json): hashes of distributed files; check before copying into other packages.

## Measurement and interpretation limits

- Stage22C and Stage22D are distinct protocols; never pool their latency observations as an identical implementation. Their digests can be compared at matched fixture hashes, but timings cannot be interpreted as direct speedups of the same code.
- A matched digest is **agreement of index output between methods on tested fixtures**, not an independent oracle proving universal correctness, and not serializability.
- Phase wall-clock timings can include scheduling, I/O or DB stalls. Read-only EXPLAIN/SELECT is not causal measurement of physical lock waits or INSERT/DELETE costs.
- Experiments used one Windows/XAMPP host, MariaDB 10.4.32, one 1× lexical corpus, and deterministic selected mutations. No claim of end-user retrieval speedup is made.
- We do **not** duplicate the 4,587-entry derived lexicon in this release; access to the approved-only corpus is documented in the separate VOICE archive, subject to its source notices and conditions. We also exclude original database backups, individual rater labels, authentication or contribution histories, and private system configuration.

## Citation and status

See [`CITATION.cff`](CITATION.cff). This journal-independent evidence package corresponds to a **manuscript draft**; it has no registered DOI and is not asserted to be the version of record. Repository URLs, permanent release tags and any post-submission journal citation must be added **only after they actually exist**.

## Licensing

As with the VOICE materials, the published ministry-repository CC BY-NC 4.0 notice is the stated basis for noncommercial reuse of **source-derived lexical materials**, subject to attribution, change notices and the original dictionary copyright warnings. The indexing release itself contains no lexicon dump. Code ownership/licensing and human research data remain separate; public availability does not grant blanket software rights. See [`PROVENANCE_AND_RIGHTS.md`](PROVENANCE_AND_RIGHTS.md) and [`DATA_AVAILABILITY.md`](DATA_AVAILABILITY.md).
