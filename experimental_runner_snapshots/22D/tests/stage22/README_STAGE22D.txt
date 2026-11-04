RESTI - STAGE 22D | COMPARATOR FAIRNESS B2/C2
=============================================
UNTUK: kamus_tolour_index_v1 (Windows XAMPP, PHP 8.2, MariaDB 10.4)

PENTING: paket ZIP ini hanya menambahkan file baru. Tidak menimpa script lama.

INSTALASI:
1. Pastikan MySQL di XAMPP aktif.
2. Salin ZIP ke C:\xampp\htdocs\kamus_tolour_index_v1\.
3. Ekstrak ISI ZIP langsung di folder itu (jangan membuat subfolder ZIP baru).
4. Klik dua kali JALANKAN_STAGE22D.bat.

URUTAN DISARANKAN:
- Menu 0: preflight (harus PASS).
- Menu 1: pilot 2 pasangan batch 1 (harus PASS).
- Menu 3: pilot 2 pasangan batch 100 (harus PASS; menangkap potensi isu batch besar).
- Baru jika lulus, menu 4, 5, 6 untuk masing-masing 30 pasangan.
- Kirim JSON hasilnya dari tests\stage22\reports.

AKUN DATABASE: root tanpa password, localhost (sesuai runner historis Stage21).
Apabila konfigurasi berbeda, STOP; jangan ubah source lama atau menebak kredensial.

HAK AKSES:
- Koneksi kamus_tolour_index_v2 / kamus_tolour_index_stage21_s1_sandbox: query baca saja.
- DDL/DML hanya database sementara kamus_tolour_index_stage22d_work_sandbox.
- Menolak run bila database sementara tersebut sudah ada.
- Tidak menghapus database sementara jika terjadi kegagalan di tengah jalan.
- Tidak ada pembaruan SOURCE, V1 services, atau script ABC historis.

METODE B2/C2:
- B2 dan C2 sama-sama: lock metadata 1x/batch, read+FOR UPDATE entry dan semua posting lama,
  proyeksi yang sama, update metadata agregat max 1x/batch.
- B2: hapus+masukkan semua posting untuk entry yang tetap approved.
- C2: hapus+masukkan HANYA delta posting.
- Unpublish sama-sama menghapus entry beserta posting via FK ON DELETE CASCADE.
- C2 adalah implementasi EKSPERIMENTAL kontrol, BUKAN produksi/index_stage3 asli.
- B2 juga dibebani full posting-read/lock agar setara, berbeda dari baseline B asli.

TIMER:
- Maintenance-only, mutation, commit, dan verification dicatat terpisah.
- Diagnostic phase timer berjalan di dalam maintenance pada kedua arms secara simetris.
- Snapshot/restore/cleanup/checkpoint/progress/ETA tidak masuk latency.
- Latency ini BUKAN latency aplikasi atau endpoint pencarian.

LAPORAN:
- STAGE22D_B1_2PAIR_PILOT_*.json, dll untuk pilot;
- STAGE22D_B1_30PAIRS_*.json, B10, B100 untuk pengujian utama;
- STAGE22D_CHECKPOINT_B*_*.json untuk progres parsial jika terhenti.
Checkpoint bukan mekanisme resume otomatis. Bila terputus, simpan JSON dan kirim untuk diagnosis.

BELUM DIJALANKAN DI WINDOWS XAMPP ANDA saat paket ini dibuat. Jalankan pilot dulu.
