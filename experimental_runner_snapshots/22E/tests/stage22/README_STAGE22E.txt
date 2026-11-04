STAGE 22E - BOTTLENECK PROFILING (CMD + PHP, READ-ONLY)
======================================================

INSTALASI
1. Salin ZIP ke C:\xampp\htdocs\kamus_tolour_index_v1\ .
2. Extract Here / Ekstrak di sini (tanpa subfolder ekstra).
3. Klik dua kali JALANKAN_STAGE22E.bat .

MENU
[1] AUDIT OFFLINE: periksa semua enam laporan Stage22C/Stage22D final 30 pasangan.
    Tidak memerlukan MySQL. Memeriksa fixture, digest, jumlah operasi,
    fairness count dan fase timing; menulis JSON + CSV + Markdown.
[2] SQL READ-ONLY: memeriksa baseline MySQL, indeks tabel, EXPLAIN SELECT,
    dan 15 ulang SELECT kecil. MySQL XAMPP harus aktif.
[3] Gabungan menu 1+2 (lebih mudah jika semua file tersedia dan MySQL aktif).

PRASYARAT AUDIT OFFLINE
Enam file FINAL di tests\stage22\reports\, misalnya:
 STAGE22C_B1_30PAIRS_*.json
 STAGE22C_B10_30PAIRS_*.json
 STAGE22C_B100_30PAIRS_*.json
 STAGE22D_B1_30PAIRS_*.json
 STAGE22D_B10_30PAIRS_*.json
 STAGE22D_B100_30PAIRS_*.json
Harus berstatus PASS. Jika ada beberapa file yang cocok, script memilih
laporan PASS dengan timestamp tertinggi dan mencatat kandidat lainnya.
Jangan hapus/mengganti laporan historis.

OUTPUT (di tests\stage22\reports\)
 STAGE22E_OFFLINE_YYYYMMDD_HHMMSS_*.json
 STAGE22E_SQL_READONLY_YYYYMMDD_HHMMSS_*.json
 STAGE22E_GABUNGAN_YYYYMMDD_HHMMSS_*.json (jika pilih menu 3)
 STAGE22E_PAIRED_PHASES_YYYYMMDD_HHMMSS_*.csv
 STAGE22E_INTERPRETASI_YYYYMMDD_HHMMSS_*.md

PENGAMAN
- Tidak membuat, menghapus, memperbarui tabel/database, tidak menjalankan
  COMMIT, transaksi mutasi, SELECT FOR UPDATE, atau benchmark beban tulis.
- SQL hanya menyentuh kamus_tolour_index_v2 dan Stage21 S1 reference melalui
  SELECT/EXPLAIN/SHOW dan information_schema, menggunakan root localhost standar.
- Menolak database jika baseline 5.687/4.587/29.546 atau hash tidak sesuai.
- Berhenti jika database sementara ABC/22D masih ada: jangan dihapus manual.
- Script historis, indeks, dan file report lama tetap utuh.

BENTUK BUKTI
Ini adalah analisis deskriptif fase operasi dan diagnostik query access path,
BUKAN pembuktian root cause lock wait, EXPLAIN ANALYZE, throughput produksi,
atau klaim bahwa algoritma differential selalu lebih cepat.

Setelah PASS, simpan file STAGE22E_OFFLINE_*.json dan
STAGE22E_SQL_READONLY_*.json (atau STAGE22E_GABUNGAN_*.json) sebagai bukti audit.
