STAGE 22B - Correctness Pilot (Tahap awal)

PASANG:
  1. Simpan ZIP di C:\xampp\htdocs\kamus_tolour_index_v1
  2. Ekstrak isi ZIP langsung ke folder project (tanpa folder pembungkus).
  3. Pastikan Apache tidak perlu, tetapi MySQL XAMPP harus aktif.
  4. Klik dua kali JALANKAN_STAGE22B.bat, ketik UJI.

APA YANG DIUJI:
  - Gate memverifikasi hash empat file source beku dan runner A/B/C asli.
  - Menggunakan runner B/C historis yang sama, bukan algoritma baru.
  - Empat pasangan B1 menguji WORD, MEANING, CATEGORY, UNPUBLISH.
  - Per pasangan, B dan C memakai snapshot awal identik, COMMIT hanya di database kerja disposable,
    assertProjection dan digest indeks identik diperiksa.
  - Referensi sumber dan sandbox seharusnya tidak berubah.

KEAMANAN:
  - Script akan menolak source yang hash-nya berbeda atau jika work database ABC telah ada.
  - Runner historis membuat dan menghapus HANYA kamus_tolour_index_abc_s1_work_sandbox.
  - Bila terjadi kegagalan, database disposable mungkin ditinggalkan untuk diagnosis.
    JANGAN hapus manual sebelum hasil diperiksa.
  - Snapshot sementara dibuat menggunakan mysqldump dan importer XAMPP; jangan hentikan MySQL saat uji.
  - User MySQL root lokal harus sesuai konfigurasi historis runner (127.0.0.1, user root, password kosong).

OUTPUT:
  tests\stage22\reports\STAGE22B_ABC_*.log
  tests\stage22\reports\STAGE22B_RAW_B1_4PAIRS_*.json
  tests\stage22\reports\STAGE22B_SUMMARY_*.json
  Runner asli juga menyimpan JSON awal di tests\reports\INDEX_ABC_...json (jejak asli).

BATASAN:
  Tahap ini menguji empat jenis perubahan pada entri approved dan COMMIT pada disposable DB.
  Publish-pending, ROLLBACK eksplisit dan concurrency belum diuji ulang oleh paket ini.
  Jangan gunakan hasil pilot ini sebagai benchmark performa atau klaim menyeluruh.

Jika gagal, arsipkan STAGE22B_ABC_*.log beserta kode kesalahannya. Jika PASS,
  simpan STAGE22B_SUMMARY_*.json dan STAGE22B_RAW_B1_4PAIRS_*.json sebagai bukti.
