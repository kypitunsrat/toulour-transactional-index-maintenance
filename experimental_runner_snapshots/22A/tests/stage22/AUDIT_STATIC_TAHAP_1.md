# Audit Tahap 1 — Benchmark indexing Toulour untuk RESTI

**Basis:** `kamus_tolour_index(1).zip`, `TOULOUR_INDEX_BACKUP.zip`, paket evidence V16, serta konteks artikel RESTI V20. **Tanggal audit:** 3 November 2026 (konteks percakapan). Tidak ada database pengguna yang diubah.

## Ringkasan pemeriksaan

| Pemeriksaan | Hasil | Batas interpretasi |
|---|---|---|
| Arsip project | 841 file non-folder; berisi V0, V1, dan salinan aplikasi untuk eksperimen | Beberapa file aplikasi dan konfigurasi disimpan sebagai bagian dari ZIP, tetapi tidak disertakan ke paket output ini |
| Arsip database | 18 dump SQL | Dump adalah snapshot; bukan bukti bahwa DB aktif saat ini identik |
| Source freeze | Empat dari empat hash SHA-256 cocok dengan konstanta di `tests/index_comparator_abc.php` | Tidak berarti benchmark sudah dijalankan ulang |
| PHP syntax | `index_comparator_abc.php`, `IndexStage2Materializer.php`, `IndexStage3IncrementalMaintainer.php` lolos `php -l` di lingkungan audit | Eksekusi PHP/MariaDB/XAMPP Windows belum dilakukan |
| Evidence A/B/C | B1 = 10 pasangan; B10 = 8; B100 = 8; total 26. Semua JSON berstatus PASS dan setiap run menandai parity PASS | Validasi struktur evidence historis, bukan reproduksi langsung |
| Metadata database | V2 dan Stage21 S1: 4.587 entries/29.546 terms; S2: 9.174/59.092; S5: 22.935/147.730 | Diambil dari `idx_stage2_meta` pada dump, belum di-query langsung di server Windows |

## Implementasi yang diaudit

- **Full rebuild (A):** memproyeksikan seluruh data approved dan menulis ulang posting.
- **Selective row refresh (B):** melakukan penghapusan+penulisan ulang posting per ID yang dimutasi, dengan satu pembaruan metadata agregat ketika delta hitungan tidak nol.
- **Differential maintenance (C):** `IndexStage3IncrementalMaintainer::apply()` membandingkan posting lama/baru, menulis posting yang berubah, memakai penguncian metadata dan membaca posting tiap ID, serta memperbarui metadata bila delta terjadi.

Perbedaan yang masih perlu dikontrol: jumlah query, penguncian `FOR UPDATE`, pekerjaan per-ID, dan strategi pembaruan metadata tidak identik. Eksperimen B/C lama mengukur **gabungan kebijakan dan implementasi**, bukan isolasi matematika set differencing murni. Pada kategorisasi tanpa perubahan jumlah posting, C masih harus memproses entry dan lock; B menghapus dan menulis ulang posting yang nilainya sama.

## Pemeriksaan keselamatan

1. Script A/B/C asli memiliki guard nama folder project, daftar DB yang diizinkan, hash source, fingerprint sumber, jumlah dataset, dan penolakan jika nama DB kerja sudah ada. Script membuat serta menghapus database kerja **khusus** dengan nama tetap `kamus_tolour_index_abc_s1_work_sandbox`. Artinya script historis bukan **read-only**; jangan dijalankan untuk percobaan baru tanpa preflight dan backup.
2. Script historis memakai user MySQL `root` tanpa password serta utilitas `C:\xampp\mysql\bin\mysqldump.exe` dan `mysql.exe`. Kondisi autentikasi dan path ini harus cocok pada komputer pengguna sebelum menjalankan benchmark selanjutnya.
3. SQL ZIP memiliki `CREATE DATABASE` dan `USE` karena opsi `mysqldump --databases`. Tidak aman menggunakan dump itu secara langsung sebagai snapshot import menuju DB kerja berbeda.
4. SQL dump mengandung tabel aplikasi seperti `users`, sehingga perlu perlindungan data pribadi sebelum dibagikan atau diterbitkan.
5. Script historis membatasi `--reps` maksimal 20; rencana 30 repetisi membutuhkan runner baru/penyesuaian yang tidak mengubah skrip baseline.
6. Pengujian B/C memisahkan waktu mutasi, maintenance, COMMIT, dan verifikasi. `mutation_maintenance_commit_ms` adalah **jumlah segmen** tanpa verifikasi; `wall_mutation_to_commit_including_verification_ms` memiliki batas waktu berbeda. Perlu konsisten saat pelaporan.
7. Rencana pengujian historis tidak mencakup `publish-pending` pada B/C karena pemilihan tipe mutasi di runner hanya `word`, `meaning`, `category`, `unpublish`; pengujian publishing telah ada pada stage lama tetapi tidak pada ketiga JSON B/C baru.

## Keputusan desain Tahap 2–5

- Pertahankan **V0/V1 dan bukti historis** tanpa modifikasi.
- Jalankan `resti_stage1_preflight.php` dalam dua mode: offline, lalu query SELECT DB lokal.
- Pakai **salinan sandbox yang dibekukan** sebagai acuan, bukan DB produksi. Jangan menggunakan seluruh 18 dump dalam satu percobaan.
- Stage 2: uji kesamaan sumber→indeks dan rollback/commit untuk mutasi yang tercakup, pada database disposable khusus runner baru.
- Stage 3: perluas B/C dengan repetisi yang diseimbangkan, dan sampel mutasi terstratifikasi.
- Stage 4: varian pembanding yang mengontrol metadata; validasi ulang digest untuk setiap arm, tanpa menyamakan perubahan semantik transaksi secara sembarangan.
- Stage 5: profiling fase SQL/lock/metadata; pisahkan klaim latency maintenance, total transaksi, dan retrieval.

## Kriteria lulus Tahap 1

- [x] Struktur ZIP dan database dipetakan.
- [x] Hash empat sumber utama cocok.
- [x] Tiga laporan B/C JSON historis 26 pasangan terbaca dan berstatus PASS.
- [x] Script preflight **read-only** dibuat dan diuji mode offline pada ekstrak source.
- [ ] Mode `--db-check` PASS di XAMPP pengguna (belum tersedia akses MySQL Windows di lingkungan audit).

**Status akhir:** **TAHAP 1 — AUDIT STATIS SELESAI; VERIFIKASI DATABASE LOKAL MENUNGGU HASIL PREFLIGHT.** Jangan menyatakan Tahap 2 benchmark telah dilakukan sampai pemeriksaan lokal dikonfirmasi.
