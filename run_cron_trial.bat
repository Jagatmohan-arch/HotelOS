@echo off
echo ==========================================
echo      HotelOS - Trial Expiry Cron Job
echo ==========================================
echo.
echo Running daily trial expiry check...
echo Output will be saved to cron_debug.log
echo.

REM Initialize PHP path variable
set PHP_PATH=

REM Check System PATH first
where php >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_PATH=php
    goto :RUN_CRON
)

REM Check XAMPP Default Path
if exist "C:\xampp\php\php.exe" (
    set PHP_PATH="C:\xampp\php\php.exe"
    goto :RUN_CRON
)

REM Check C:\php Default Path
if exist "C:\php\php.exe" (
    set PHP_PATH="C:\php\php.exe"
    goto :RUN_CRON
)

:RUN_CRON
if "%PHP_PATH%"=="" (
    echo [ERROR] PHP not found. Please run run_uat.bat first to setup.
    pause
    exit /b 1
)

%PHP_PATH% scripts/cron_trial_expiry.php > cron_debug.log 2>&1

echo Done. Check cron_debug.log for details.
pause
