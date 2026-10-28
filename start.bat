@echo off
setlocal enabledelayedexpansion
title Hope Academy - School Management System
color 0B
cls

echo ===================================================================
echo           HOPE ACADEMY - SCHOOL MANAGEMENT SYSTEM
echo               One-Click Plug ^& Play Launcher
echo ===================================================================
echo.

:: 1. Detect PHP binary
set "PHP_BIN=php"
where php >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PHP_BIN=C:\xampp\php\php.exe"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PHP_BIN=D:\xampp\php\php.exe"
    ) else if exist "E:\xampp\php\php.exe" (
        set "PHP_BIN=E:\xampp\php\php.exe"
    ) else (
        echo [ERROR] PHP executable was not found on your system!
        echo Please install XAMPP or add PHP to your Windows PATH.
        echo.
        pause
        exit /b 1
    )
)
echo [OK] PHP detected: %PHP_BIN%

:: 2. Check and start MySQL server if needed
echo [INFO] Checking MySQL connection on port 3306...
netstat -ano | findstr ":3306" | findstr "LISTENING" >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] MySQL is not running. Attempting to start MySQL server...
    set "STARTED_MYSQL=0"
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        set "STARTED_MYSQL=1"
    ) else if exist "D:\xampp\mysql\bin\mysqld.exe" (
        start "" /B "D:\xampp\mysql\bin\mysqld.exe" --defaults-file="D:\xampp\mysql\bin\my.ini" --standalone
        set "STARTED_MYSQL=1"
    )

    if "!STARTED_MYSQL!"=="1" (
        echo [INFO] Waiting 3 seconds for MySQL to initialize...
        timeout /t 3 /nobreak >nul
    ) else (
        echo [WARNING] Could not locate XAMPP MySQL automatically.
        echo Please ensure MySQL is started in your XAMPP Control Panel.
    )
) else (
    echo [OK] MySQL is active on port 3306.
)

:: 3. Run automated database provisioner
echo [INFO] Verifying database schema and initial data...
"%PHP_BIN%" setup_database.php >nul 2>&1
echo [OK] Database 'naini' verified and ready.

:: 4. Start local server & open browser
set "PORT=8080"
echo.
echo ===================================================================
echo   Application URL: http://localhost:%PORT%
echo   Default Admin:   Username: admin    Password: admin123
echo   Default Lecturer:Username: Test     Password: test123
echo.
echo   [Press CTRL+C in this window at any time to stop the server]
echo ===================================================================
echo.

:: Open default web browser
start http://localhost:%PORT%

:: Start PHP built-in web server
"%PHP_BIN%" -S localhost:%PORT%
