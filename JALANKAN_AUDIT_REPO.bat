@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Audit Repository Toulour Indexing

echo ========================================================
echo   AUDIT REPOSITORI TOULOUR INDEXING - READ ONLY
echo ========================================================
echo Lokasi: %CD%
echo.
if not exist "TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py" goto missing_repo
if not exist "tools\verify_stage22_baseline_reports.py" goto missing_repo
if not exist "raw_reports" goto missing_repo

set "PY="
where py >nul 2>nul
if not errorlevel 1 (
    py -3 --version >nul 2>nul
    if not errorlevel 1 set "PY=py -3"
)
if not defined PY (
    where python >nul 2>nul
    if not errorlevel 1 (
        python --version >nul 2>nul
        if not errorlevel 1 set "PY=python"
    )
)
if not defined PY goto missing_python

echo [1/3] Memeriksa 180 pasangan dari laporan tersimpan...
%PY% "TOULOUR_INDEX_S3_VERIFY_SAVED_RESULTS.py"
if errorlevel 1 goto failed

echo.
echo [2/3] Memeriksa enam laporan baseline Stage 22...
%PY% "tools\verify_stage22_baseline_reports.py"
if errorlevel 1 goto failed

echo.
echo [3/3] Memeriksa inventaris SHA-256 berkas publik...
%PY% "verify_public_package.py"
if errorlevel 1 goto failed

echo.
echo ========================================================
echo PASS: Ketiga audit selesai. Tidak ada database yang diubah.
echo ========================================================
goto end

:missing_repo
echo.
echo ERROR: Folder ini hanya berisi PATCH atau repositori belum lengkap.
echo Ekstrak seluruh isi patch KE DALAM folder repositori asli.
echo Folder yang benar harus memiliki raw_reports dan skrip Stage 22.
goto end

:missing_python
echo.
echo ERROR: Python 3 tidak ditemukan. Pasang Python 3 dan aktifkan PATH.
goto end

:failed
echo.
echo FAIL: Ada pemeriksaan yang gagal. Lihat pesan di atas.
echo Jangan commit/push sebelum penyebabnya diperbaiki.
goto end

:end
echo.
pause
endlocal
