# Supplementary Evidence S3 — Toulour Index Maintenance (source-free benchmark audit)

This local supplementary bundle is prepared for journal review. It contains **six final 30-pair Stage22C/22D raw JSON reports**, one offline Stage22E audit, a read-only SQL diagnostic, a phase-level CSV, a pilot rollback report, a 180-row derived paired CSV, and a standalone Python 3 arithmetic verifier.

**To recalculate:** `python TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py` from the root of the extracted supplement. The script uses only the standard library and does not connect to a database. It recomputes paired medians, win counts, logical posting-write reductions, same-index digests and cross-protocol fixture matches.

**What this bundle establishes:** traceability and reproducibility of numerical claims from preserved raw benchmark reports, using explicit checksum-locked inputs. The original experiment used separate disposable 1x PHP/MariaDB databases and immutable project source hashes shown in the JSON.

**What it does not establish:** independent source-to-index correctness for all 90 fixtures, concurrent serializability, zero lock waiting, end-user HTTP latency, an optimized competitor baseline, or independent replay on a new corpus. The code needed for replay and the licensed lexical source are **not included** here. This is **result-level reproducibility**, not full experimental replication.

**Rights and ethics:** The original lexical data derive from *Kamus Tondano–Indonesia* (1985), ministry record https://repositori.kemendikdasmen.go.id/23919/. Repository-wide notices mention CC BY-NC 4.0, while the item is labeled `Copyright All Rights Reserved` and the volume has a reproduction notice. Until relevant rights and reuse conditions are clarified, this audit bundle intentionally contains no full lexical dictionary export, no participant judgments, and no authentication or contribution history. This bundle does not confer rights to republish the source lexicon. Any article's open-access license must not be construed as relicensing external lexical material.

**Provenance:** These JSON/CSV evidence files are retained original outputs; no latency observations were fabricated, re-estimated or altered. All values come from Stage22C/D/E saved runs. Do not pool Stage22C and Stage22D latencies as though they were identical implementations.
