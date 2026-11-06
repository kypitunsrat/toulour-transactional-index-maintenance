# Data availability and rights — indexing study (6 November 2026)

This repository provides **saved evidence and inspectable runner snapshots** for the transactional maintenance of bidirectional W/M/T lexical indexes. It is **not a dataset repository or a complete PHP/MariaDB reproduction environment**. It is independent of journal destination (RESTI or another venue).

## Shared corpus and access

The 1× indexing baseline contains **5,687 dictionary records**, including **4,587 approved records**; the materialized W/M/T baseline has **4,587 index entries and 29,546 postings**. The approved subset is the same six-field canonical dataset previously released for the distinct VOICE lexical-retrieval study: [Toulour selective lexical retrieval](https://github.com/kypitunsrat/toulour-selective-lexical-retrieval), `data/lexical/approved_corpus.json`. Six fields checked: `id`, `category_id`, `tolour_word`, `indonesian_meaning`, `popularity`, `search_count`.

**Scope of verification:** 4,587/4,587 approved records matched the examined archived 1× SQL baselines; the original Stage22 source-row and W/M/T initial index hash routines were independently reconstructed from a private Stage21 SQL backup and matched all six public Stage22C/D reports. See [`CROSS_STUDY_DATASET_PROVENANCE.md`](CROSS_STUDY_DATASET_PROVENANCE.md) and [`STAGE22_BASELINE_LOCK_MANIFEST.json`](STAGE22_BASELINE_LOCK_MANIFEST.json). This does **not** prove byte identity of per-run SQL dumps (they were temporary and were not retained), and does **not** establish independent execution on another machine. The 1,100 nonapproved records are not part of the VOICE public approved-only fixture and are not distributed here; their exact original database state is not reconstructible from that public fixture alone.

### Source rights — same basis as VOICE, adapted to indexing

The original lexical source is the copyrighted *Kamus Tondano–Indonesia* (1985), repository item https://repositori.kemendikdasmen.go.id/23919/ . As in the VOICE package's `SOURCE_ATTRIBUTION.md` and `DATA_AVAILABILITY.md`, the stated basis for the research-derived lexical release is the ministry repository's published CC BY-NC 4.0 information: https://repositori.kemendikdasmen.go.id/information.html and https://creativecommons.org/licenses/by-nc/4.0/ . This is a **source-derived-materials license basis**, not a declaration that the book has lost copyright, is in the public domain, or may be used commercially. Preserve attribution, license link, indication of digitization/curation changes, and noncommercial-use terms. Academic purpose by itself is **not** a substitute for license conditions.

The repository item also bears an *All Rights Reserved* notice, and the printed book includes a reproduction restriction. These notices are preserved here. No separately obtained written reuse permission or formal authorization is asserted. If a downstream use is not covered or the contradictory notices create uncertainty, obtain permission/clarification from the rightsholder. This release does not create stricter restrictions on uses already authorized by the applicable license.

The derivative approved-only lexical fixture in VOICE is **not re-uploaded** here. No blanket software license is created by this statement: code ownership and reuse terms must be decided separately by code rightsholders. No rater judgments, user contributions or other human data are covered by the dictionary source license.

## What is reproducible from this release

- **Public saved-result recomputation:** `python TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py`, `python verify_public_package.py`, and `python tools/verify_stage22_baseline_reports.py` validate archived numerical/evidence consistency without a database.
- **Inspectability:** saved PHP/CMD runner snapshots are provided as historical sources, but depend on private project components and include destructive disposable-database routines; do not execute on a production host.
- **Independent experimental replay:** not yet possible from this repository or from the VOICE fixture alone. It would require a separately licensed and sanitized implementation, W/M/T schema, executable data fixture/complete baseline state, safe DB credentials, and controlled machine configuration. Synthetic-data replay would test method behavior but would not recreate the original real-corpus timings.

## Excluded material

Original dictionary PDF or scans; full private SQL dumps and nonapproved records; authentication/config secrets; individual contributor history, validator/rater responses, audit trails, and personal data. The 1985 book PDF is referenced but not redistributed.

## Reuse by future journal versions

The dataset citation and baseline manifest are **journal-neutral**. New submissions may reference these resources without changing VOICE R01's frozen evidence or the indexing Stage22 measurement files. If the public VOICE repository receives a later update, cite a specific real Git commit or archived release after confirming it; do not invent or silently assume a new snapshot identity.
