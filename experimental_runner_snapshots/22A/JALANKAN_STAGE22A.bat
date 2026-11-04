@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "PROJECT_ROOT=%~dp0"
set "CHECKER=%PROJECT_ROOT%tests\stage22\index_stage22a_preflight.php"
set "REPORTS=%PROJECT_ROOT%tests\stage22\reports"

cls
echo ==========================================================
echo STAGE 22A - PREFLIGHT PROJECT INDEXING TOULOUR
 echo CMD / XAMPP - HANYA BACA - TIDAK MENGUBAH DATABASE
 echo ==========================================================
echo.

if not exist "%PROJECT_ROOT%tests\index_comparator_abc.php" (
    echo [GAGAL] File benchmark lama tidak ditemukan di tests\index_comparator_abc.php.
    echo Pastikan ISI ZIP diekstrak LANGSUNG ke folder kamus_tolour_index_v1.
    goto :fatal
)
if not exist "%CHECKER%" (
    echo [GAGAL] Script Stage 22A tidak ditemukan.
    goto :fatal
)

set "PHP_EXE="
if exist "%PROJECT_ROOT%..\..\php\php.exe" set "PHP_EXE=%PROJECT_ROOT%..\..\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE (
    echo [GAGAL] php.exe tidak ditemukan. Pastikan XAMPP terpasang.
    echo Dicari: folder PHP XAMPP dan perintah php dari PATH.
    goto :fatal
)

if not exist "%REPORTS%" mkdir "%REPORTS%"
if errorlevel 1 (
    echo [GAGAL] Tidak dapat membuat folder laporan.
    goto :fatal
)

echo Project : %PROJECT_ROOT%
echo PHP     : %PHP_EXE%
echo Laporan : %REPORTS%
echo.
echo [1] Cek SOURCE dan laporan benchmark lama (tanpa database)
echo [2] Cek DATABASE V2 dan Stage 21 S1 (read-only)
echo [3] Jalankan kedua cek, urut 1 lalu 2 [disarankan]
echo [0] Batal
echo.
set "PILIH=3"
set /p "PILIH=Pilihan [default 3]: "
if "%PILIH%"=="" set "PILIH=3"
if "%PILIH%"=="0" goto :end
if "%PILIH%"=="1" goto :offline
if "%PILIH%"=="2" goto :db
if "%PILIH%"=="3" goto :both
echo [GAGAL] Pilihan tidak dikenal.
goto :fatal

:offline
call :runcheck offline
if errorlevel 1 goto :fatal
goto :success

:db
call :runcheck db-check
if errorlevel 1 goto :fatal
goto :success

:both
call :runcheck offline
if errorlevel 1 goto :fatal
echo.
echo [INFO] Pemeriksaan source PASS. Melanjutkan database...
call :runcheck db-check
if errorlevel 1 goto :fatal
goto :success

:runcheck
set "MODE=%~1"
set "DEST=%REPORTS%\STAGE22A_%MODE%_%RANDOM%_%RANDOM%.json"
echo.
echo Menjalankan: %MODE% [READ-ONLY]
"%PHP_EXE%" "%CHECKER%" "--project=%PROJECT_ROOT%." "--%MODE%" > "%DEST%" 2> "%DEST%.error.txt"
set "CODE=%ERRORLEVEL%"
if exist "%DEST%" type "%DEST%"
if not "%CODE%"=="0" (
   echo.
   echo [GAGAL] Pemeriksaan %MODE% belum PASS.
   if exist "%DEST%.error.txt" type "%DEST%.error.txt"
   echo Laporan: %DEST%
   exit /b 1
)
if exist "%DEST%.error.txt" del /q "%DEST%.error.txt" >nul 2>&1
echo.
echo [PASS] %MODE%
echo Laporan: %DEST%
exit /b 0

:success
echo.
echo ==========================================================
echo STAGE 22A SELESAI - SEMUA PEMERIKSAAN DIPILIH PASS
 echo Simpan dan kirim file JSON dari tests\stage22\reports
 echo ==========================================================
goto :end

:fatal
echo.
echo Pemeriksaan belum selesai. JANGAN jalankan benchmark berikutnya.
echo Kirim isi error atau JSON dari folder tests\stage22\reports.
:end
echo.
pause
endlocal
