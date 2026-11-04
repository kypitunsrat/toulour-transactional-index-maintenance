@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "ROOT=%~dp0"
set "RUNNER=%ROOT%tests\index_comparator_abc.php"
set "GATE=%ROOT%tests\stage22\index_stage22b_gate.php"
set "COLLECT=%ROOT%tests\stage22\index_stage22b_collect.php"
set "OUTDIR=%ROOT%tests\stage22\reports"

cls
echo ==============================================================
echo STAGE 22B - CORRECTNESS PILOT (B vs C, 4 MUTATION TYPES)
echo XAMPP CMD - HANYA DATABASE KERJA SEMENTARA
 echo ==============================================================
echo.
if not exist "%RUNNER%" goto :missing
if not exist "%GATE%" goto :missing
if not exist "%COLLECT%" goto :missing
if not exist "%OUTDIR%" mkdir "%OUTDIR%"
if errorlevel 1 goto :missing

set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
    set "PHP_EXE="
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)
if not defined PHP_EXE (
    echo [GAGAL] php.exe tidak ditemukan. Pastikan XAMPP terpasang.
    goto :fail
)

"%PHP_EXE%" "%GATE%"
if errorlevel 1 goto :fail

echo.
echo Ini akan menjalankan 4 pasangan B/C dengan perubahan data hanya
 echo dalam database kerja ABC sementara. Runner asli akan membuat
 echo dan menghapus database tersebut. Jangan matikan MySQL selama tes.
echo.
set "CONFIRM="
set /p "CONFIRM=Ketik UJI lalu Enter untuk menjalankan (lainnya = batal): "
if /I not "%CONFIRM%"=="UJI" goto :cancel

set "LOG=%OUTDIR%\STAGE22B_ABC_%RANDOM%_%RANDOM%.log"
echo.
echo [RUN] Benchmark correctness 4 repetisi, batch 1, B dan C...
"%PHP_EXE%" "%RUNNER%" --abc-benchmark --batch=1 --reps=4 --arms=targeted > "%LOG%" 2>&1
set "RUN_CODE=%ERRORLEVEL%"
type "%LOG%"
if not "%RUN_CODE%"=="0" (
    echo [GAGAL] Runner historis mengembalikan exit code %RUN_CODE%.
    echo Periksa log: %LOG%
    echo Jika ada work database tersisa, JANGAN hapus secara manual.
    goto :fail
)

echo.
echo [CHECK] Audit isi JSON baru dan salin ke folder Stage 22...
"%PHP_EXE%" "%COLLECT%" "--log=%LOG%"
if errorlevel 1 goto :fail

echo.
echo [PASS] Stage 22B pilot selesai. Laporan STAGE22B_SUMMARY_*.json
 echo tersedia di tests\stage22\reports untuk audit.
goto :end

:missing
echo [GAGAL] File Stage22B tidak lengkap. Ekstrak ZIP tepat di
 echo folder kamus_tolour_index_v1 (jangan di dalam folder baru).
goto :fail
:cancel
echo Dibatalkan; benchmark tidak dijalankan.
goto :end
:fail
echo.
echo BELUM LULUS. Simpan log/JSON untuk diagnosis.
:end
echo.
pause
endlocal
