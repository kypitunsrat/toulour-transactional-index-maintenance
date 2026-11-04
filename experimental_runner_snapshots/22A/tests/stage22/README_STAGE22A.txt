STAGE 22A - CMD SAJA (TIDAK PERLU POWERSHELL)

TUJUAN: pemeriksaan kesiapan source dan database secara READ-ONLY.
INI BUKAN benchmark baru; tidak membuat / menghapus / mengubah database.

CARA PASANG (PENTING):
1. Buka folder project asli, contoh: C:\xampp\htdocs\kamus_tolour_index_v1\
2. Salin ZIP RESTI_STAGE22A_CMD_UNTUK_INDEX_V1.zip ke folder itu.
3. Klik kanan ZIP > Extract Here / Ekstrak di sini.
   PENTING: jangan ekstrak menjadi folder baru bernama RESTI_STAGE22A_CMD_UNTUK_INDEX_V1.
   File JALANKAN_STAGE22A.bat harus ada LANGSUNG di folder kamus_tolour_index_v1.
4. Start XAMPP -> MySQL jika ingin cek database.
5. Klik dua kali JALANKAN_STAGE22A.bat (CMD), pilih 3 lalu ENTER.
6. Hasil akan ada di tests\stage22\reports\*.json.
7. Kirim file JSON PASS/FAIL, bukan database mentah.

STRUKTUR SETELAH EKSTRAK:
kamus_tolour_index_v1\
  JALANKAN_STAGE22A.bat
  tests\
    index_comparator_abc.php  [script lama milik project, tidak berubah]
    stage22\
      index_stage22a_preflight.php
      MANIFEST_SUMBER_V16_SHA256.txt
      AUDIT_STATIC_TAHAP_1.md
      README_STAGE22A.txt
      reports\
         STAGE22A_offline_...json     [dibuat saat dijalankan]
         STAGE22A_db-check_...json    [dibuat saat dijalankan]

PEMERIKSAAN: 
Mode 1: validasi SHA-256 empat file frozen V16 + tiga JSON ABC historis.
Mode 2: cek SELECT only pada database kamus_tolour_index_v2 dan
        kamus_tolour_index_stage21_s1_sandbox, serta metadata/digest dasar.
Mode 3: mode 1 lalu mode 2.

ASUMSI: XAMPP PHP terpasang, PHP 8.2+, database tersedia di MySQL lokal.
Default koneksi database: 127.0.0.1:3306, root, password kosong (dari
runner historis). Jika root punya password, jalankan CMD dengan variabel
lingkungan RESTI_DB_USER dan RESTI_DB_PASS; jangan tulis password di ZIP.
Tidak perlu mengubah script/source sejarah Stage 17-21 atau ABC.

Jika hasil FAIL: jangan menghapus database atau mengimpor SQL otomatis.
Tunjukkan file JSON untuk ditelusuri lebih dulu.

Catatan: preflight ini diverifikasi secara OFFLINE terhadap salinan project
V1 dari ZIP yang diserahkan; pemeriksaan koneksi DB Windows tidak dapat
dijalankan di sini dan harus dijalankan secara lokal.
