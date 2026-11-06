# VOICE ↔ transactional index maintenance: canonical dataset provenance (6 November 2026)

## Why these are two separate papers

VOICE studies lexical retrieval effectiveness, including the V2.2 Fast retrieval approach, human relevance assessments and query-level metrics. This indexing study examines maintenance of derived bidirectional W/M/T postings, consistency under mutation, logical writes, and batch-dependent maintenance latency. Shared application/data provenance is disclosed; claims of improved retrieval accuracy or new human annotation are **not transferred** to this paper.

## Audited shared 1× baseline (recorded evidence)

| Property | Verified result |
|---|---|
| Source dictionary records | 5,687 |
| Approved lexical records | 4,587 |
| Nonapproved database records (not public fixture) | 1,100 |
| Materialized entries | 4,587 |
| W/M/T term postings | 29,546 |
| VOICE approved vs archived indexing approved data | 4,587/4,587 exact six-field match |
| Six-field canonical approved SHA-256 | `09fb2a7b9b3b504675654f953380206f576dbb8bb3e6df01c024830162d71f49` |
| Indexing source-row SHA-256 (10-field original s21CoreHash) | `8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4` |
| Indexing initial W/M/T SHA-256 (original s21IndexDigest) | `dbbd4272c1c135f263746a706b412b1b55a06f1f9db2aaf35761027aa13425f0` |

The six-field canonical subset equality was checked against **two archived 1× private SQL baselines** (an earlier index backup and a Stage21 sandbox) and the approved-only VOICE fixture. The Stage22C and Stage22D report source/index hashes match the hashes independently recomputed from the archived SQL baseline. The source-only and index-digest hashes above use **different serialization and field scopes**; comparing them directly would be meaningless. The canonical six-field digest is cross-study-specific, not a file SHA-256 of the JSON export.

## Stage22C / Stage22D locks

Six archived reports, batches 1, 10 and 100 for each protocol, report the same initial source hash and W/M/T index digest, with 30 paired comparisons per report, 180 paired comparisons total. Stage22C (B/C robustness) and Stage22D (B2/C2 matched-control) are **separate protocols**. Stage22E is a distinct diagnostic analysis. The per-pair digest equalities are observed for tested fixtures; they are not a proof of universal index correctness, serializability, or independent PHP/MySQL replay.

The **six temporary per-run SQL dumps were deleted by the controlled runner** after use. Their raw file-byte SHA-256s and sizes appear in original reports but the file contents are unavailable; the archived backup equality and logical digest checks do **not** make those absent dump files recoverable or byte-for-byte authenticated.

The independent logical snapshot audit was performed on 6 November 2026 and retained as a separate private/internal verification package. Publicly, this repository provides a condensed text-free manifest and a script which independently rechecks agreement among the six public reports, **not** a copy of the private SQL.

## Cross-study dataset access and reuse

- VOICE dataset: https://github.com/kypitunsrat/toulour-selective-lexical-retrieval — `data/lexical/approved_corpus.json` and `SOURCE_ATTRIBUTION.md`.
- Indexing dataset: same **approved** lexical subset; full 5,687-row database baseline and W/M/T index tables are **not** exposed in the VOICE fixture and are not redistributed here.
- Source basis and notices: copyrighted 1985 *Kamus Tondano–Indonesia*; published repository CC BY-NC 4.0 information is the stated basis for source-derived noncommercial academic material, while the item-level All Rights Reserved notice and original-book restriction remain disclosed. The original book is not reproduced and no new blanket rights are claimed.
- Separate software terms: no open-source license is imposed on the indexing implementation; permission for software reuse must be addressed independently.

## Audit boundaries

This provenance document records the **verified archived evidence** and does not claim a fresh PHP/MariaDB run, external independent replication, or a legal determination of all third-party rights. See `DATA_AVAILABILITY.md`, `PROVENANCE_AND_RIGHTS.md`, and `REPLAY_REQUIREMENTS.md`.
