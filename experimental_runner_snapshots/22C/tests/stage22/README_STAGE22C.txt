STAGE 22C - B/C ROBUSTNESS: PAKET CMD
======================================

CARA PAKAI (Windows/XAMPP)
1. Salin ZIP ke C:\xampp\htdocs\kamus_tolour_index_v1 lalu EXTRACT HERE.
2. Buka XAMPP, aktifkan MySQL.
3. Klik dua kali JALANKAN_STAGE22C.bat.
4. Pilih [0] untuk read-only preflight; [1] untuk pilot 2 pasangan.
5. Setelah keduanya PASS, jalankan [2], [3], [4] satu per satu.
6. Setiap selesai, kirim JSON STAGE22C_B{batch}_30PAIRS_*.json.

Struktur (langsung di folder V1, TIDAK membuat nested folder project):
  kamus_tolour_index_v1/JALANKAN_STAGE22C.bat
  kamus_tolour_index_v1/tests/index_stage22c_robustness.php
  kamus_tolour_index_v1/tests/stage22/README_STAGE22C.txt
  kamus_tolour_index_v1/tests/stage22/reports/*.json

METODE
- 1x real corpus 4.587 indexed entries / 29.546 postings.
- Targeted B (selective-refresh) vs C (differential-maintenance).
- Per batch 1,10,100: 30 paired repetitions, counterbalanced order, deterministic distinct fixtures.
- 30 pasangan berarti 60 kali pembuatan/pemulihan database kerja per batch.
- Source dan reference DB dibaca saja. COMMIT dilakukan HANYA ke disposable
  kamus_tolour_index_abc_s1_work_sandbox, dihapus setelah run normal.
- Jika sebuah run gagal, jangan menghapus work database atau memaksakan lanjut.
- Source PHP historis tidak diubah; copy runner Stage22C terpisah.
- Stage22C bergantung pada SHA source dan historical ABC runner yang dibekukan.

STATUS DAN ETA
- Progress, elapsed time, dan ETA dicetak setelah tiap pasangan B/C selesai,
  di luar pengukuran maintenance, mutation atau COMMIT.
- ETA menggunakan waktu wall-clock rata-rata dan mencakup restore/cleanup.
  ETA awal (1-3 pasangan) bisa kurang akurat.
- Setelah tiap pasangan, checkpoint ditulis ke tests/stage22/reports.
- Checkpoint bukan fitur auto-resume: jika gagal, kirim checkpoint dan error
  terlebih dahulu. Re-run langsung tanpa diagnosis dapat membias hasil.
- Pilot 2 pasangan terpisah, JANGAN gabungkan dengan run utama 30 pasangan.

BATAS INTERPRETASI
- Data benar-benar pasangan B/C; 30 pasangan mencakup campuran mutasi
  word/meaning/category/unpublish yang sudah ada pada runner ABC.
- Tidak menguji publish-pending di Stage22C; Stage22B2 sudah melakukannya.
- B vs C masih memiliki kebijakan metadata dan lock berbeda, jadi ini
  ROBUSTNESS implementasi aktual, bukan controlled FAIRNESS kausal.
- Stage22D baru akan mengontrol kebijakan metadata dan lock.
- Ini bukan pengukuran latency end-to-end atau bukti superioritas search.
- Windows/XAMPP php 8.2, MariaDB 10.4, dump/mysql exe default C:/xampp/mysql/bin.

KEAMANAN
- Hanya menjalankan snapshot dari sandbox Stage21 S1 dan dataset V2 sebagai read-only.
- Source/corpus canonical hash dan metadata harus cocok; mismatch -> STOP.
- Nama database kerja tetap dan allowlisted, tidak boleh sudah ada saat start.
- Stop jika ada SQL snapshot dengan CREATE/DROP DATABASE atau USE.
- Tidak menjalankan perintah PowerShell.
