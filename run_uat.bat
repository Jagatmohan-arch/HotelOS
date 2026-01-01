@echo off
echo ==========================================
echo      HotelOS - UAT Simulation Runner
echo ==========================================
echo.
echo Attempting to run simulation...
@echo off
echo ==========================================
echo      HotelOS - UAT Simulation Runner
echo ==========================================
echo.
echo Attempting to auto-detect PHP...

REM Initialize PHP path variable
set PHP_PATH=

REM Check System PATH first
where php >nul 2>nul
if %errorlevel% equ 0 (
    set PHP_PATH=php
    echo [FOUND] PHP detected in System PATH.
    goto :RUN_SIMULATION
)

REM Check XAMPP Default Path
if exist "C:\xampp\php\php.exe" (
    set PHP_PATH="C:\xampp\php\php.exe"
    echo [FOUND] PHP detected in XAMPP.
    goto :RUN_SIMULATION
)

REM Check C:\php Default Path
if exist "C:\php\php.exe" (
    set PHP_PATH="C:\php\php.exe"
    echo [FOUND] PHP detected in C:\php.
    goto :RUN_SIMULATION
)

REM If not found
echo.
echo [ERROR] PHP Could not be found automatically.
echo.
echo Please provide the full path to your php.exe file.
echo (Usually inside C:\xampp\php\ or C:\wamp\bin\php\)
echo.
set /p USER_PHP_PATH="Paste Path to php.exe: "

if exist "%USER_PHP_PATH%" (
    set PHP_PATH="%USER_PHP_PATH%"
    goto :RUN_SIMULATION
) else (
    echo.
    echo [ERROR] Invalid path provided. Exiting.
    pause
    exit /b 1
)

:RUN_SIMULATION
echo.
echo Using PHP: %PHP_PATH%
echo Logging output to uat_debug.log...
echo.

echo Using PHP: %PHP_PATH% > uat_debug.log
%PHP_PATH% -v >> uat_debug.log 2>&1

echo Starting script... >> uat_debug.log
%PHP_PATH% tests/uat_simulation.php >> uat_debug.log 2>&1


if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Execution failed! Check uat_debug.log for details.
    echo.
    type uat_debug.log
    pause
    exit /b 1
)

echo.
echo [SUCCESS] Simulation finished.
type uat_debug.log
pause
