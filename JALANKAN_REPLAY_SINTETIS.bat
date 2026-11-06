@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Toulour Indexing - Replay Sintetis Aman
 echo =====================================================
 echo  TAHAP 4 - REPLAY SINTETIS W/M/T (READ-ONLY XAMPP)
 echo  Tidak mengakses database MariaDB / korpus asli
 echo  SQLite hanya di memori; laporan ke folder TEMP
 echo =====================================================
 echo.
py -3 -c "import sqlite3,sys; assert sys.version_info.major==3" >nul 2>&1
if not errorlevel 1 (
  py -3 synthetic_replay\run_replay.py
  goto :done
)
python -c "import sqlite3,sys; assert sys.version_info.major==3" >nul 2>&1
if not errorlevel 1 (
  python synthetic_replay\run_replay.py
  goto :done
)
echo ERROR: Python 3 belum ditemukan. Coba: py -3 --version
set "RESULT=2"
goto :end
:done
set "RESULT=%errorlevel%"
:end
echo.
if "%RESULT%"=="0" (
  echo PASS - Replay sintetis selesai. Lihat folder OUTPUT di atas.
) else (
  echo FAIL - Exit code %RESULT%. Kirim screenshot CMD.
)
echo Tidak ada database XAMPP yang diubah.
pause
exit /b %RESULT%
