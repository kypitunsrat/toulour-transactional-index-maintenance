Stage 22B2 – Correctness completion (not benchmark performance)

1. Copy ZIP to C:\xampp\htdocs\kamus_tolour_index_v1\.
2. Extract Here; it must merge tests\stage22, not create nested project folder.
3. Start MySQL in XAMPP. Do not run other Stage 21 / ABC benchmarking at the same time.
4. Double click JALANKAN_STAGE22B2.bat, type UJI.
5. Upload STAGE22B2_PUBLISH_ROLLBACK_*.json from tests\stage22\reports.

Tests: publish-pending from status 'menunggu' to 'disetujui' with COMMIT,
using frozen B (Selective Row Refresh copied from original runner) and C (original maintained algorithm),
then ROLLBACK on both methods. Both run in a disposable dedicated database restored from frozen Stage21 S1 snapshot.

Never import backup .sql or manually drop a failed work DB. On failure the script will
leave work DB intact for diagnosis rather than deleting evidence. Contact the reviewer
with JSON and any errors before rerun.

This is an unexecuted proposed extension. PHP syntax checked; SQL behavior needs user XAMPP run.
It does not prove concurrent schedule safety, production coverage, or all mutation families.
Root password empty assumed consistent with the previous Stage22A and Stage22B setup.
