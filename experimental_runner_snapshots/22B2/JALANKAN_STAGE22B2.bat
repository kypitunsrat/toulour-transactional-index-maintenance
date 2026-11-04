@echo off
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
set "PHP_EXE=C:\xampp\php\php.exe"
set "RUNNER=%~dp0tests\stage22\index_stage22b2_complete.php"
cls
echo =========================================================
echo STAGE 22B2 - PUBLISH-PENDING AND ROLLBACK PARITY
echo 4 operations on disposable DB, baseline V16 unchanged
echo =========================================================
echo.
if not exist "%RUNNER%" (echo [ERROR] Script missing. Extract ZIP inside index_v1 root.& goto :end)
if not exist "%PHP_EXE%" (echo [ERROR] C:\xampp\php\php.exe not found.& goto :end)
echo Preflight: verify XAMPP MySQL is running and no other Stage 21/22 benchmark is active.
echo IMPORTANT: if a failure leaves work database behind, DO NOT delete it manually.
echo.
set "CONFIRM="
set /p "CONFIRM=Ketik UJI lalu Enter (atau apa pun untuk batal): "
if /I not "%CONFIRM%"=="UJI" (echo BATAL.& goto :end)
"%PHP_EXE%" "%RUNNER%"
if errorlevel 1 (echo [GAGAL] Simpan file JSON & laporkan sebelum mencoba lagi.) else (echo [PASS] Periksa JSON di tests\stage22\reports.)
:end
echo.
pause
endlocal
