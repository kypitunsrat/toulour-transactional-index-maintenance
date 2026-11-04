# Evidence coverage by research stage

This repository is a **selected evidence release**, NOT a claim that all historical stages are fully reproducible. The research stage labels are internal experiment management labels. Never pool latency from distinct protocols without a justified model.

| Stage | Research role | Public evidence supplied here | Reproducibility level |
|---|---|---|---|
| 15–16 | Index build/import/baseline engineering | Results mentioned in the manuscript, but complete raw runs not available in this release | Context only |
| 17A–17D | Mutation-family functional and consistency pilots | No complete raw original log archive available for safe release; historical handoff summarizes some findings | Context only |
| 18 | Evidence consolidation | Historical manuscript narrative only, not independent raw replay | Context only |
| 19–20 | Further read/write and scale studies | No complete raw original log archive available for safe release | Context only |
| 21 | Safe-recovery and 1×/2×/5× sensitivity; groundwork for comparator | Historical editorial audit and 26-pair follow-on comparator in `historical_comparator_26pairs/` | Partial raw comparator only; do not label 26 pairs as raw Stage 21 experiments |
| 22A | Read-only baseline source, index and hash preflight | Two final JSON reports + frozen runner snapshot | Inspectable raw checks |
| 22B/B2 | Four B/C mutation pilots; publish-pending and rollback | Raw, summary and rollback JSON + runner snapshots | Inspectable raw checks |
| 22C | Robustness, B/C: 30 paired runs × batches 1/10/100 | Three final JSONs + script snapshot | Independently recomputable saved-result statistics; no complete PHP/MySQL replay |
| 22D | Fairness-controlled B2/C2: 30 pairs × batches 1/10/100 | Three final JSONs + runner snapshot | Independently recomputable saved-result statistics; no complete PHP/MySQL replay |
| 22E | Offline phase analysis + read-only MariaDB plans | Offline JSON, read-only SQL JSON, derived CSV, interpretation, runner snapshot | Offline re-analysis and query-plan inspection |
| 22F | Manuscript evidence consolidation | Source crosswalk and release documentation; no article draft published without author approval | Documentation only; not an experimental run |

**Why are earlier raw logs incomplete?** The locally retained Stage 17–21 archives available during packaging were largely SQL database backups, application code trees and summary documents, not a curated set of releasable original result JSONs. Do not upload SQL dumps or whole web-app archives unreviewed: they may contain derivative lexical content, user accounts, secrets, historical records or third-party assets. Releasing only the supported later evidence is preferable to inventing missing results.

**Twenty-six-pair distinction.** The three pre-22C baseline comparator JSON reports (10/8/8 paired runs at batch sizes 1/10/100) originate in the historical V16 package dated Nov 1–2, 2026. They are a *separate*, smaller protocol and must not be appended to Stage 22C's 90 pairs or Stage 22D's 90 pairs when computing primary benchmark summaries. The V17 editorial handoff explicitly states that it did **not** include raw JSONs for all earlier Stage17–21 experiments.

**Copyright and privacy.** Original dictionary SQL and codebase are intentionally not supplied. See `PROVENANCE_AND_RIGHTS.md` and `REPLAY_REQUIREMENTS.md`.
