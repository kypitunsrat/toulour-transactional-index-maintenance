@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "ROOT=%~dp0"
set "RUNNER=%ROOT%tests\index_stage22d_fairness.php"
set "ORIGINAL=%ROOT%tests\index_comparator_abc.php"
set "OUTDIR=%ROOT%tests\stage22\reports"
cls
echo =====================================================================
echo STAGE 22D - COMPARATOR FAIRNESS B2 vs C2 - TOULOUR INDEX V1
echo CMD only. Semua file dan laporan di dalam project kamus_tolour_index_v1
echo =====================================================================
echo.
if not exist "%RUNNER%" goto :missing
if not exist "%ORIGINAL%" goto :missing
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
echo  [0] Preflight source + database READ-ONLY
echo  [1] PILOT batch 1   - 2 pasangan B2/C2
echo  [2] PILOT batch 10  - 2 pasangan B2/C2
echo  [3] PILOT batch 100 - 2 pasangan B2/C2
echo  [4] UTAMA batch 1   - 30 pasangan B2/C2
echo  [5] UTAMA batch 10  - 30 pasangan B2/C2
echo  [6] UTAMA batch 100 - 30 pasangan B2/C2
echo  [9] Keluar
echo.
set "PILIH="
set /p "PILIH=Pilih nomor: "
if "%PILIH%"=="9" goto :end
if "%PILIH%"=="0" goto :check
if "%PILIH%"=="1" (set "BATCH=1" & set "REPS=2" & goto :run)
if "%PILIH%"=="2" (set "BATCH=10" & set "REPS=2" & goto :run)
if "%PILIH%"=="3" (set "BATCH=100" & set "REPS=2" & goto :run)
if "%PILIH%"=="4" (set "BATCH=1" & set "REPS=30" & goto :run)
if "%PILIH%"=="5" (set "BATCH=10" & set "REPS=30" & goto :run)
if "%PILIH%"=="6" (set "BATCH=100" & set "REPS=30" & goto :run)
echo [INFO] Nomor tidak dikenal.
goto :menu
:check
echo.
echo [CHECK] Tidak ada CREATE, DROP, UPDATE, atau COMMIT database.
"%PHP_EXE%" "%RUNNER%" --fair-check --batch=1 --reps=1 --arms=targeted
if errorlevel 1 goto :fail
echo.
echo [PASS] Preflight 22D berhasil. Lanjut PILOT menu [1].
goto :end
:run
echo.
echo [RUN] Batch=%BATCH%  Pair=%REPS%  Arms=B2 vs C2
echo [AMAN] Hanya database sementara kamus_tolour_index_stage22d_work_sandbox.
echo [PERHATIAN] Full run dapat memerlukan puluhan menit atau lebih.
echo [ETA] Progres diperbarui setelah setiap pasangan, bukan selama latency diukur.
echo [STOP] Jangan jalankan benchmark lain bersamaan.
echo.
set "CONFIRM="
set /p "CONFIRM=Ketik UJI lalu Enter untuk mulai (Enter kosong = batal): "
if /I not "%CONFIRM%"=="UJI" goto :cancel
echo.
"%PHP_EXE%" "%RUNNER%" --fair-run --batch=%BATCH% --reps=%REPS% --arms=targeted
if errorlevel 1 goto :fail
echo.
echo [PASS] Stage 22D selesai. File STAGE22D_B*.json tersimpan di:
echo %OUTDIR%
echo Gunakan laporan FINAL untuk verifikasi (bukan checkpoint).
goto :end
:missing
echo [GAGAL] File BAT atau PHP tidak berada langsung di folder project V1.
echo Ekstrak isi ZIP ke kamus_tolour_index_v1, bukan ke subfolder baru.
goto :fail
:nophp
echo [GAGAL] php.exe tidak ditemukan. Pastikan XAMPP terpasang.
goto :fail
:cancel
echo [BATAL] Tidak ada benchmark dijalankan.
goto :end
:fail
echo.
echo [BELUM PASS] Simpan JSON FAIL dan checkpoint untuk diagnosis.
echo Jika database stage22d_work_sandbox tertinggal, JANGAN dihapus manual.
:end
echo.
pause
endlocal
