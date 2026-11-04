# Stage 22D — Matched-Overhead Comparator Fairness

## Tujuan dan pertanyaan

Stage 22C menunjukkan C berulang kali mengurangi *posting writes* (~75%) tetapi tidak konsisten lebih cepat pada batch 10/100. Stage 22D adalah eksperimen kontrol untuk memisahkan **kebijakan penulisan posting** dari **frekuensi pembaruan metadata dan jenis pembacaan/locking** yang berbeda pada B vs C historis.

## Definisi perlakuan yang adil

| Faktor | B2: Matched Selective Refresh | C2: Matched Differential |
|---|---|---|
| Baseline dan fixture | 1x real corpus, seed/fixture sama dengan 22C | Sama |
| Urutan eksekusi | Counterbalanced berpasangan | Sama |
| Snapshot dan transaction owner | Restore snapshot ke work DB independen | Sama |
| Metadata lock | `SELECT * ... FOR UPDATE` sekali pada awal batch | Sama |
| Index entry lock | `SELECT * ... FOR UPDATE`, tiap entri | Sama |
| Posting lock/read | Ambil seluruh posting lama dengan `FOR UPDATE`, tiap entri | Sama |
| Projection computation | `makeProjection`, sama | Sama |
| Delta set computation | Dihitung (untuk kontrol) | Dihitung |
| Entry update | Hanya jika berubah | Sama |
| Posting write policy | DELETE posting lama seluruhnya lalu INSERT semua posting baru | DELETE old-only lalu INSERT new-only |
| Unpublish | Delete entry + FK cascade | Sama |
| Metadata counter UPDATE | Maksimum satu agregat per batch jika delta != 0 | Sama |
| COMMIT/verification | Sama, dihitung terpisah | Sama |

**Konsekuensi penting:** B2 sengaja melakukan posting-read/lock dan komputasi delta yang tidak diperlukan B historis, sedangkan C2 tidak lagi memanggil maintainer lama per entri. Karena itu B2/C2 adalah *controlled ablation*; jangan digabung sebagai observasi identik dengan B/C pada Stage 22C. Hasil bukan bukti bahwa original B atau original C berubah menjadi lebih cepat.

## Pengukuran

- Maintenance-only (utama), mutation-only, COMMIT, segmented mutation + maintenance + COMMIT.
- Fase diagnostik: metadata lock, posting/entry read+lock, projection+diff, entry writes, posting writes, metadata update.
- Posting writes = total DELETE + INSERT posting, **bukan** total SQL roundtrip atau semua storage writes.
- Pencocokan: digest indeks lengkap (index entry, posting, metadata) identik B2/C2 tiap pasangan; projection entri terdampak, metadata count = table count, database sumber/reference utuh sebelum/sesudah.
- `n=30` pasangan per batch (1, 10, 100), sekaligus tiga pilot (`n=2`) bila diperlukan. Pilot tidak digabung dengan benchmark utama.
- Reporting: per-method median/mean/P95/SD, kemenangan berpasangan, operasi posting, fase diagnostik; interval kepercayaan dan uji statistik dianalisis dari JSON setelah eksperimen, bukan diasumsikan PASS.

## Pengaman dan batasan

- Fixed allowlist DB; DDL/DML hanya `kamus_tolour_index_stage22d_work_sandbox`.
- Menolak run ketika work DB sudah ada; tidak menghapusnya otomatis setelah kegagalan.
- Memeriksa SHA-256 empat file source dan SHA-256 historical ABC runner; tidak mengubah file tersebut.
- Snapshot berasal dari `kamus_tolour_index_stage21_s1_sandbox` dan dicek bebas instruksi perpindahan database.
- Tidak mengukur concurrency, production search latency, scalability lintas korpus beragam, atau algorithmic novelty.
- Satu lock metadata per batch **tidak** membuktikan keselamatan keseluruhan aplikasi dalam beban konkuren.
- Performa dipengaruhi I/O, thermal throttling, cache, dan variansi Windows; hasil tidak boleh diseleksi berdasarkan apakah B2/C2 menang.
- Saat STOP/FAIL, simpan semua bukti termasuk checkpoint dan database work yang tertinggal untuk diagnosis.

## Interpretasi yang diharapkan (tidak diasumsikan)

Apabila C2 lebih kompetitif setelah matching overhead, ada dukungan bahwa overhead bookkeeping/lock pada C historis menjelaskan sebagian hasil Stage22C. Jika C2 masih kalah, hasil mendukung bahwa penghematan posting saja tidak cukup untuk menutup biaya diff/SQL/transaction. Kedua hasil valid dan harus dilaporkan.
