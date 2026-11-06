# Publication distribution notes

This release is a journal-independent public-facing copy of the indexed-dictionary benchmark evidence archive.
The **raw measurement reports, paired CSV data, numerical verification scripts, and all PHP computation files are byte-for-byte identical** to the selected working archive.
Only operator-facing BAT messages, a small number of local README instructions, stage-specific file-hash lists, and repository navigation text were adjusted for general publication. Four non-essential editorial handoff/upload documents were omitted. These editorial changes were **not used to generate benchmark numbers**.
The historical BAT/README SHA-256 hashes below distinguish the study-working files from the earlier public release. Subsequent journal-neutral packaging renamed four top-level supplementary files and updated public-facing documentation; the primary raw reports and historical runner snapshots remain unchanged. For the **current** release use `SHA256_PUBLIC_FILES.json`, not the prior-release hash values in the table below.

## Prior working-to-public changes (historical checksum comparison)

| File | Working SHA-256 | Earlier public SHA-256 (not current) |
|---|---|---|
| `EXPERIMENT_CODE_NOTICE.md` | `485ed3aab14cb75131c6f61b0e29d1cfc60cd7bc7a007667ffd38c02c5c034e9` | `2bf9a053ef600a9b1b9833b105d04a8f053a039f2bc606fe0db18e8bf78299e7` |
| `README.md` | `a99dbc55b81663641424316d0e0557a8cdc9f9e0638ba7a2f1eba9454a49ee3d` | `e5c4768857e8550f0035a54cf9bcc7f6e9bd822eebe4e30179bcc9197e2689fa` |
| `REPLAY_REQUIREMENTS.md` | `9e25e04ec808089c656f61954a8f92814b0dec844894e717d2ba339c84e71917` | `66e041c42556e78d2e769e959c4ae0d8dd92f580cce26394bbedf3f4ee45789b` |
| `experimental_runner_snapshots/22B/JALANKAN_STAGE22B.bat` | `28848f601366a86bcbb54bf76fd59625aa0c9c4318054f45940079425e4509e3` | `7758c3f529fde5e11aa9c6be7639eeb4f5c1dd9a82ef4fca1392b306ea390e54` |
| `experimental_runner_snapshots/22B/tests/stage22/README_STAGE22B.txt` | `9d5c90f4e6196584aed1c298168e71a2e7aad1299ae1ba64d66ab1bd3dc29115` | `659e0404e6eba195737426f24507150a9a92a9be6668291d90120f6fa4c556f9` |
| `experimental_runner_snapshots/22C/JALANKAN_STAGE22C.bat` | `49e76d0eed7c5e94fbf6fd4ef49e7e3e00f48e62c2e6e221d7e0b51f507b8590` | `b82ec188c516ddb4f05c65a2063af6f05339bc8a8e9d642d0c806441e258cff5` |
| `experimental_runner_snapshots/22D/JALANKAN_STAGE22D.bat` | `66fc12ddeffe79f8b5a2827e52aaa91072fdf7ff81e7313aff89c54f04f73495` | `55f78064deb3629d4b9e0d443e059c0ca933198f3a0f75aab32fe267aec48acd` |
| `experimental_runner_snapshots/22D/tests/stage22/SHA256_MANIFEST_STAGE22D.txt` | `0423c8154ae002b16fafeae83b97751b8b6bc81e36ebc38bb6145e82738fa6b5` | `75f6ba3231416ee0ffadf06139fdd704b4ed6706ae0ab2a27a6cb57b62b02923` |
| `experimental_runner_snapshots/22E/JALANKAN_STAGE22E.bat` | `83d98e39d2bfb86d9b29068a64786e8e49fb642756b88ba177b67db1d0d41dbc` | `259739041d190c2e2014a56432998cd7f1786801d2235e0aa256613ef958e9bd` |
| `experimental_runner_snapshots/22E/tests/stage22/README_STAGE22E.txt` | `b3f91ce54e73dee75f59ba56926317867e4a028e9a4ab0339a96b34fa29d8634` | `52d36da3991b974b74d553d6a22342c03d42b7469356889e8dcf06101f662fbe` |
| `experimental_runner_snapshots/22E/tests/stage22/SHA256_MANIFEST_STAGE22E.txt` | `e5f09006e782bc2b219ae29f67f1493226756b540af43e94c90325359aa091cc` | `0b25575649b0a88a0a3efdf187ecab364a1d52e13dabab683bc185182bd1d7f0` |

## Files omitted (documentation only)

- `GITHUB_UPLOAD_GUIDE_WINDOWS.md`
- `STAGE22F_EVIDENCE_HANDOFF.md`
- `historical_comparator_26pairs/V16_AUDIT_REPORT.md`
- `historical_comparator_26pairs/V17_EVIDENCE_HANDOFF.md`

## Interpretation

A successful Python report verification checks saved numerical results, **not** a standalone independent PHP/MariaDB replay.
The snapshotted experiment runners require the original controlled project and approved source database, which are not in this archive. The original study must not be represented as independently replayed from this package.
The code files may contain destructive operations restricted to disposable databases. Do not execute on production systems.

## 6 November 2026: cross-study Stage 3 documentation addendum

A new documentation release aligns the indexing source attribution and data availability language with the already-distributed VOICE archive **without changing VOICE R01 itself**. It adds `DATA_AVAILABILITY.md`, `CROSS_STUDY_DATASET_PROVENANCE.md`, `STAGE22_BASELINE_LOCK_MANIFEST.json`, `tools/verify_stage22_baseline_reports.py`, and `STAGE3_CHANGELOG.md`; it updates public README, rights, replay instructions, and SHA-256 inventory. The journal-neutral display title is updated to the authors' preferred sentence-case form. **No raw JSON/CSV evidence, historical 26-pair reports, or experiment runner snapshot bytes are modified.** Rebuilding the inventory is an archive-integrity bookkeeping change, not a new benchmark.
