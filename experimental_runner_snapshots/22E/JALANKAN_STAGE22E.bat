@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "ROOT=%~dp0"
set "RUNNER=%ROOT%tests\index_stage22e_bottleneck.php"
set "OUTDIR=%ROOT%tests\stage22\reports"
cls
echo ====================================================================
echo STAGE 22E - BOTTLENECK PROFILING - INDEX V1 - CMD
 echo READ-ONLY: TIDAK membuat atau mengubah database; tidak benchmark ulang.
echo ====================================================================
echo.
if not exist "%RUNNER%" goto :missing
if not exist "%ROOT%tests\index_comparator_abc.php" goto :missing
if not exist "%OUTDIR%" mkdir "%OUTDIR%"
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
 set "PHP_EXE="
 for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE goto :nophp
:menu
echo.
echo [1] AUDIT OFFLINE - cek 6 JSON 22C+22D, statistik fase (tanpa MySQL)
echo [2] DIAGNOSTIK SQL READ-ONLY - EXPLAIN, indeks, SELECT kecil
echo [3] JALANKAN KEDUANYA (offline + SQL read-only)
echo [9] Keluar
echo.
set "PILIH="
set /p "PILIH=Pilih nomor [disarankan 1 lalu 2]: "
if "%PILIH%"=="9" goto :end
if "%PILIH%"=="1" (
 set "MODE=--offline"
 goto :run
)
if "%PILIH%"=="2" (
 set "MODE=--sql"
 goto :run
)
if "%PILIH%"=="3" (
 set "MODE=--all"
 goto :run
)
echo Nomor tidak dikenal.
goto :menu
:run
echo.
echo [SAFE] MODE=%MODE% -- read-only, tidak menyentuh data benchmark asli.
echo [INFO] Hasil akan disimpan ke %OUTDIR%
echo [INFO] Jangan menjalankan bersamaan dengan benchmark 22C/22D lain.
if "%MODE%"=="--offline" (
 echo [INFO] Pastikan 6 laporan FINAL 30PAIRS Stage22C dan Stage22D ada di folder reports.
) else (
 echo [INFO] Pastikan MySQL XAMPP aktif dan tidak ada database kerja benchmark yang tertinggal.
)
echo.
"%PHP_EXE%" "%RUNNER%" %MODE%
if errorlevel 1 goto :fail
echo.
echo [PASS] Stage 22E %MODE% sukses. Laporan JSON FINAL siap diaudit.
goto :end
:missing
echo [GAGAL] Runner 22E / runner historis tidak ditemukan.
echo Ekstrak ZIP langsung di folder kamus_tolour_index_v1, bukan ke subfolder.
goto :end
:nophp
echo [GAGAL] php.exe belum ditemukan; periksa instalasi XAMPP.
goto :end
:fail
echo.
echo [BELUM PASS] Baca error; simpan file JSON FAIL untuk diagnosis.
echo Jangan menghapus database, file benchmark, maupun checkpoint lama secara manual.
:end
echo.
pause
endlocal
