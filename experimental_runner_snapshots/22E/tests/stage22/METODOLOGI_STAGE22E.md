# Stage 22E — Bottleneck Profiling (versi aman, berbasis evidence)

## Pertanyaan
Mengapa penurunan sekitar 75% operasi posting pada differential maintenance tidak selalu diikuti penurunan *maintenance latency*?

## Protokol
1. **Forensic replay offline:** membaca JSON FINAL Stage22C (B/C asli) dan Stage22D (B2/C2 terkendali), masing-masing batch 1/10/100 × 30 pasangan. Tidak mengulang eksperimen dan tidak mencampur data latensi lintas protokol.
2. **Pairing kontrol:** memastikan `mutation_id_sha256`, digest indeks pascamutasi, serta jumlah query metadata/locking B2/C2 sama dalam setiap pasangan. Membandingkan `phase_ms` dan `postings_total_writes`.
3. **SQL read-only:** memeriksa skema/indeks InnoDB, jalur akses lewat `EXPLAIN SELECT`, dan 15 repetisi probe `SELECT` sederhana tanpa `FOR UPDATE`. Memverifikasi ulang hitungan, hash core dan digest indeks pada reference.
4. **Interpretasi:** memisahkan penjelasan yang *didukung data* (write-count reductions dan fase dengan wall-clock tertinggi) dari hipotesis (lock waits, I/O stalls, checkpoint, buffer pool, disk contention).

## Kontrol keamanan
- Hash runner historis dan empat file source harus cocok dengan baseline.
- Hanya database sumber V2 dan Stage21 S1, keduanya diakses READ-ONLY.
- Tidak ada database kerja yang dibuat, dihapus, di-COMMIT, atau diubah.
- Jika database kerja ABC/22D ada, probe SQL berhenti sebelum query profil.
- Hasil selalu dibuat dengan nama unik; tidak menimpa JSON historis.

## Definisi timing
- `maintenance_ms` adalah window maintenance yang sudah diukur pada Stage22C/22D.
- `phase_ms` Stage22D adalah durasi operasi terinstrumentasi dalam window tersebut. Nilai tinggi tidak membuktikan lock wait tertentu karena tidak ada wait event per-query.
- `restore_ms_excluded`, `drop_work_db_ms_excluded`, verifikasi, dan snapshot tidak masuk `maintenance_ms`.
- Query diagnostik `SELECT` tidak memasukkan data ke hasil latency Stage22D; ukurannya bukan perbandingan B2/C2 dan bukan throughput aplikasi.

## Kriteria kelulusan
**PASS offline**: enam JSON benar-benar PASS, 30 pasangan lengkap masing-masing, SHA runner historis sama, fixture/digest lintas protokol sama, kontrol metadata/lock identik dan penghitungan statistik berhasil.
**PASS SQL**: baseline sumber/reference sama sebelum dan sesudah, reference digest cocok, EXPLAIN dan probe SELECT berhasil, database kerja lain tidak aktif.

## Batasan
- Satu mesin dan satu database asli 1×; tidak diuji pada workload organik beragam ataupun konkurensi.
- Kesetaraan jumlah lock SQL dan metadata UPDATE tidak sama dengan kesetaraan physical cost.
- Penelusuran sebab-akibat (misalnya pembuktian contention) membutuhkan instrumentation DB yang lebih rinci serta desain eksperimen khusus, jika data Stage22E menunjukkan kebutuhan itu.
