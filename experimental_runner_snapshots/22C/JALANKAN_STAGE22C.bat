@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "ROOT=%~dp0"
set "RUNNER=%ROOT%tests\index_stage22c_robustness.php"
set "HISTORICAL=%ROOT%tests\index_comparator_abc.php"
set "OUTDIR=%ROOT%tests\stage22\reports"
cls
echo ==============================================================
echo STAGE 22C - ROBUSTNESS B vs C  ^|^|  TOULOUR INDEX
 echo CMD only - project V1 - reports inside tests\stage22\reports
 echo ==============================================================
echo.
if not exist "%RUNNER%" goto :missing
if not exist "%HISTORICAL%" goto :missing
if not exist "%OUTDIR%" mkdir "%OUTDIR%"
if errorlevel 1 goto :fail
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
    set "PHP_EXE="
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE goto :nophp
:menu
echo.
echo [0] Preflight read-only (cek source dan database)
echo [1] Pilot batch 1 - 2 pasangan saja
 echo [2] Benchmark batch 1 - 30 pasangan B/C
 echo [3] Benchmark batch 10 - 30 pasangan B/C
 echo [4] Benchmark batch 100 - 30 pasangan B/C
 echo [9] Keluar
 echo.
set "PILIH="
set /p "PILIH=Masukkan pilihan: "
if "%PILIH%"=="9" goto :end
if "%PILIH%"=="0" goto :check
if "%PILIH%"=="1" (set "BATCH=1" & set "REPS=2" & goto :run)
if "%PILIH%"=="2" (set "BATCH=1" & set "REPS=30" & goto :run)
if "%PILIH%"=="3" (set "BATCH=10" & set "REPS=30" & goto :run)
if "%PILIH%"=="4" (set "BATCH=100" & set "REPS=30" & goto :run)
echo Pilihan tidak dikenali.
goto :menu
:check
echo.
echo [CHECK] Memeriksa tanpa membuat / memodifikasi database...
"%PHP_EXE%" "%RUNNER%" --abc-check --batch=1 --reps=1 --arms=targeted
if errorlevel 1 goto :fail
 echo [PASS] Preflight berhasil.
goto :end
:run
echo.
echo Batch=%BATCH% ; paired repetitions=%REPS% ; methods=B/C.
 echo Setiap pasangan memerlukan pemulihan snapshot untuk kedua metode.
 echo PERINGATAN: 30 pasangan dapat memerlukan puluhan menit atau lebih.
 echo Jangan jalankan paralel. Jika gagal, simpan laporan dan database kerja.
 echo.
set "CONFIRM="
set /p "CONFIRM=Ketik UJI untuk memulai, atau Enter untuk batal: "
if /I not "%CONFIRM%"=="UJI" goto :cancel
echo.
echo [RUN] Progress dan ETA akan muncul setelah setiap pasangan selesai.
 echo Semua JSON tersimpan di tests\stage22\reports.
echo.
"%PHP_EXE%" "%RUNNER%" --abc-benchmark --batch=%BATCH% --reps=%REPS% --arms=targeted
if errorlevel 1 goto :fail
echo.
echo [PASS] Pengujian selesai, ambil JSON STAGE22C_B% BATCH % (lihat nama aktual).
 echo Simpan file JSON STAGE22C_B*_30PAIRS_*.json untuk verifikasi (atau PILOT).
goto :end
:missing
echo [GAGAL] Isi ZIP tidak berada langsung di folder kamus_tolour_index_v1.
goto :fail
:nophp
echo [GAGAL] php.exe tidak ditemukan. Pastikan XAMPP terpasang.
goto :fail
:cancel
echo Dibatalkan. Tidak ada benchmark dijalankan.
goto :end
:fail
echo.
echo [BELUM PASS] Simpan laporan JSON, checkpoint, dan informasi error.
 echo Jika work database tersisa, JANGAN hapus sebelum diagnosis.
:end
echo.
pause
endlocal
