@echo off
setlocal enabledelayedexpansion

:: Set working directory to this script's directory
cd /d "%~dp0"

title Hope Academy - School Management System
color 0B
cls

echo ===================================================================
echo           HOPE ACADEMY - SCHOOL MANAGEMENT SYSTEM
echo               One-Click Plug ^& Play Launcher
echo ===================================================================
echo.

:: 1. Detect PHP binary (prioritize XAMPP PHP with MySQL extension support)
set "PHP_BIN="

:: Candidates list - XAMPP paths checked first to avoid unconfigured global PHP
set PHP_PATHS="%~dp0..\..\php\php.exe" "C:\xampp\php\php.exe" "D:\xampp\php\php.exe" "E:\xampp\php\php.exe" "php" "C:\php\php.exe" "C:\Program Files\PHP\php.exe"

for %%p in (%PHP_PATHS%) do (
    if "!PHP_BIN!"=="" (
        if "%%~p"=="php" (
            where php >nul 2>&1
            if !errorlevel! equ 0 (
                php -r "exit((extension_loaded('pdo_mysql') || extension_loaded('mysqli')) ? 0 : 1);" >nul 2>&1
                if !errorlevel! equ 0 (
                    set "PHP_BIN=php"
                ) else (
                    php -d extension=pdo_mysql -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" >nul 2>&1
                    if !errorlevel! equ 0 (
                        set "PHP_BIN=php -d extension=pdo_mysql"
                    )
                )
            )
        ) else if exist "%%~p" (
            "%%~p" -r "exit((extension_loaded('pdo_mysql') || extension_loaded('mysqli')) ? 0 : 1);" >nul 2>&1
            if !errorlevel! equ 0 (
                set "PHP_BIN=%%~p"
            ) else (
                "%%~p" -d extension=pdo_mysql -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" >nul 2>&1
                if !errorlevel! equ 0 (
                    set "PHP_BIN=%%~p -d extension=pdo_mysql"
                )
            )
        )
    )
)

:: If no candidate with verified driver was found, fallback to any detected PHP
if "!PHP_BIN!"=="" (
    if exist "%~dp0..\..\php\php.exe" (
        set "PHP_BIN=%~dp0..\..\php\php.exe"
    ) else if exist "C:\xampp\php\php.exe" (
        set "PHP_BIN=C:\xampp\php\php.exe"
    ) else if exist "D:\xampp\php\php.exe" (
        set "PHP_BIN=D:\xampp\php\php.exe"
    ) else (
        where php >nul 2>&1
        if !errorlevel! equ 0 set "PHP_BIN=php"
    )
)

if "!PHP_BIN!"=="" (
    echo [ERROR] PHP executable was not found on your system!
    echo Please install XAMPP or add PHP to your Windows PATH environment variable.
    echo.
    pause
    exit /b 1
)
echo [OK] PHP detected: "!PHP_BIN!"

:: 2. Check and start MySQL server if needed
echo [INFO] Checking MySQL connection on port 3306...
netstat -ano | findstr ":3306" | findstr "LISTENING" >nul 2>&1
if !errorlevel! neq 0 (
    echo [INFO] MySQL is not running on port 3306. Attempting to start MySQL server...
    set "STARTED_MYSQL=0"

    if exist "%~dp0..\..\mysql_start.bat" (
        start "" /min cmd /c "call "%~dp0..\..\mysql_start.bat""
        set "STARTED_MYSQL=1"
    ) else if exist "C:\xampp\mysql_start.bat" (
        start "" /min cmd /c "call C:\xampp\mysql_start.bat"
        set "STARTED_MYSQL=1"
    ) else if exist "D:\xampp\mysql_start.bat" (
        start "" /min cmd /c "call D:\xampp\mysql_start.bat"
        set "STARTED_MYSQL=1"
    ) else if exist "%~dp0..\..\mysql\bin\mysqld.exe" (
        start "" /min /D "%~dp0..\..\mysql" "%~dp0..\..\mysql\bin\mysqld.exe" --defaults-file="%~dp0..\..\mysql\bin\my.ini" --standalone
        set "STARTED_MYSQL=1"
    ) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" /min /D "C:\xampp\mysql" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        set "STARTED_MYSQL=1"
    ) else if exist "D:\xampp\mysql\bin\mysqld.exe" (
        start "" /min /D "D:\xampp\mysql" "D:\xampp\mysql\bin\mysqld.exe" --defaults-file="D:\xampp\mysql\bin\my.ini" --standalone
        set "STARTED_MYSQL=1"
    )

    if "!STARTED_MYSQL!"=="1" (
        echo [INFO] Waiting for MySQL server to initialize...
        set "MYSQL_READY=0"
        for /L %%i in (1,1,12) do (
            if !MYSQL_READY!==0 (
                netstat -ano | findstr ":3306" | findstr "LISTENING" >nul 2>&1
                if !errorlevel! equ 0 (
                    set "MYSQL_READY=1"
                ) else (
                    ping -n 2 127.0.0.1 >nul
                )
            )
        )

        if "!MYSQL_READY!"=="1" (
            echo [OK] MySQL server started and ready on port 3306.
        ) else (
            echo [WARNING] MySQL did not respond on port 3306 within 12 seconds.
            echo Please make sure MySQL is started in your XAMPP Control Panel.
        )
    ) else (
        echo [WARNING] Could not locate XAMPP MySQL automatically.
        echo Please make sure MySQL is started in your XAMPP Control Panel.
    )
) else (
    echo [OK] MySQL is active on port 3306.
)

:: 3. Locate MySQL CLI if available and create database 'naini' immediately
set "MYSQL_CLI="
if exist "%~dp0..\..\mysql\bin\mysql.exe" (
    set "MYSQL_CLI=%~dp0..\..\mysql\bin\mysql.exe"
) else if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_CLI=C:\xampp\mysql\bin\mysql.exe"
) else if exist "D:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_CLI=D:\xampp\mysql\bin\mysql.exe"
) else if exist "E:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_CLI=E:\xampp\mysql\bin\mysql.exe"
) else (
    where mysql >nul 2>&1
    if !errorlevel! equ 0 set "MYSQL_CLI=mysql"
)

if not "%MYSQL_CLI%"=="" (
    echo [INFO] Ensuring database 'naini' exists via MySQL client...
    "%MYSQL_CLI%" -u root -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS naini CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" >nul 2>&1
    if exist "%~dp0setup_db.sql" (
        "%MYSQL_CLI%" -u root -h 127.0.0.1 naini < "%~dp0setup_db.sql" >nul 2>&1
    )
)

:: 4. Run automated database provisioner & initial tables/seeds
echo.
echo [INFO] Running database provisioner (setup_database.php)...
echo ---------------------------------------------------------------
!PHP_BIN! setup_database.php
if !errorlevel! neq 0 (
    echo.
    echo [ERROR] setup_database.php encountered an error connecting to MySQL.
    echo Please make sure MySQL is running in XAMPP and try again.
    echo.
    pause
    exit /b 1
)
echo ---------------------------------------------------------------
echo [OK] Database 'naini' verified and synchronized.

if exist "fix_orphan_students.php" (
    !PHP_BIN! fix_orphan_students.php >nul 2>&1
)

:: 5. Smart port selection (use 8000 or fallback)
set "PORT=8000"
netstat -ano | findstr ":!PORT!" | findstr "LISTENING" >nul 2>&1
if !errorlevel! equ 0 (
    echo [INFO] Port 8000 is in use. Trying port 8080...
    set "PORT=8080"
    netstat -ano | findstr ":!PORT!" | findstr "LISTENING" >nul 2>&1
    if !errorlevel! equ 0 (
        echo [INFO] Port 8080 is in use. Trying port 8088...
        set "PORT=8088"
    )
)

echo.
echo ===================================================================
echo   HOPE ACADEMY - SERVER IS READY
echo ===================================================================
echo   Application URL:   http://localhost:%PORT%
echo.
echo   Default Credentials:
echo   ---------------------------------------------------------------
echo   [Admin]    Username: admin           Password: admin123
echo   [Lecturer] Username: Test            Password: test123
echo   [Student]  Username: BIT/2026/0001   Password: student123
echo.
echo   Register a new student account:
echo   http://localhost:%PORT%/modules/auth/register.php
echo.
echo   [Press CTRL+C in this window at any time to stop the server]
echo ===================================================================
echo.

if "%~1"=="--test" (
    echo [TEST MODE] All startup steps validated successfully.
    exit /b 0
)

:: Open default web browser
start "" "http://localhost:%PORT%"

:: Start PHP built-in web server
!PHP_BIN! -S localhost:%PORT%
