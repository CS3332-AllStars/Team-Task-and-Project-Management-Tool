@echo off
REM CS3332 AllStars - TTPM Development Environment Setup with PHPUnit
REM Windows/XAMPP Setup Script v2.0

echo ============================================
echo CS3332 AllStars TTPM Setup v2.0
echo Team Task and Project Management System
echo with PHPUnit Testing Framework
echo ============================================
echo.

REM Check if XAMPP is installed
echo [1/7] Checking XAMPP installation...
if not exist "C:\xampp\xampp-control.exe" (
    echo ERROR: XAMPP not found. Please install XAMPP first.
    echo Download from: https://www.apachefriends.org/
    pause
    exit /b 1
)
echo [OK] XAMPP found

REM Check if services are running
echo [2/7] Checking XAMPP services...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="1" echo WARNING: Apache not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL  
if "%ERRORLEVEL%"=="1" echo WARNING: MySQL not running
echo [OK] Service check complete

REM Create .env file
echo [3/7] Setting up environment configuration...
if not exist ".env" (
    echo Creating .env file...
    copy ".env.example" ".env" >nul
    echo [OK] .env file created
) else (
    echo [OK] .env file already exists
)

REM Create database config
echo [4/7] Setting up database configuration...
if not exist "src\config" mkdir "src\config"
if not exist "src\config\database.php" (
    echo Creating database configuration...
    (
        echo ^<?php
        echo // Auto-generated database configuration
        echo try {
        echo     $db_host = 'localhost';
        echo     $db_name = 'ttpm_system';
        echo     $db_user = 'root';
        echo     $db_pass = '';
        echo     $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass^);
        echo     $pdo-^>setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION^);
        echo     $pdo-^>setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC^);
        echo     unset($db_user, $db_pass^);
        echo } catch(PDOException $e^) {
        echo     error_log("Database connection failed: " . $e-^>getMessage(^)^);
        echo     die("Database connection failed."^);
        echo }
        echo ?^>
    ) > "src\config\database.php"
    echo [OK] Database configuration created
) else (
    echo [OK] Database configuration already exists
)

REM Setup database
echo [5/7] Setting up database...
echo Creating database and loading schema...
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS ttpm_system; CREATE DATABASE ttpm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if %errorlevel%==0 (
    echo [OK] Database created
    C:\xampp\mysql\bin\mysql.exe -u root ttpm_system < "database\schema.sql" 2>nul
    if %errorlevel%==0 (
        echo [OK] Schema loaded
        C:\xampp\mysql\bin\mysql.exe -u root ttpm_system < "database\sample_data.sql" 2>nul
        if %errorlevel%==0 (
            echo [OK] Sample data loaded
        ) else (
            echo WARNING: Sample data failed to load
        )
    ) else (
        echo ERROR: Schema failed to load
    )
) else (
    echo ERROR: Database creation failed - ensure MySQL is running
)

REM Setup PHPUnit
echo [6/7] Setting up PHPUnit testing framework...
if not exist "tests" mkdir "tests"

if exist "tests\phpunit-9.phar" (
    echo [OK] PHPUnit already installed
) else (
    echo Downloading PHPUnit...
    
    REM Try curl first (Windows 10+)
    curl --version >nul 2>&1
    if %errorlevel%==0 (
        curl -o "tests\phpunit-9.phar" -L "https://phar.phpunit.de/phpunit-9.phar" 2>nul
    ) else (
        REM Fallback to PowerShell
        powershell -Command "try { Invoke-WebRequest -Uri 'https://phar.phpunit.de/phpunit-9.phar' -OutFile 'tests\phpunit-9.phar' } catch { exit 1 }" 2>nul
    )
    
    if exist "tests\phpunit-9.phar" (
        echo [OK] PHPUnit downloaded successfully
    ) else (
        echo MANUAL DOWNLOAD REQUIRED:
        echo 1. Download: https://phar.phpunit.de/phpunit-9.phar  
        echo 2. Save as: tests\phpunit-9.phar
        echo 3. Test with: php tests\phpunit-9.phar --version
        echo.
        set /p CONTINUE="Continue without PHPUnit? (y/n): "
        if /i "!CONTINUE!"=="n" exit /b 1
    )
)

REM Test PHPUnit
if exist "tests\phpunit-9.phar" (
    echo Testing PHPUnit installation...
    php tests\phpunit-9.phar --version >nul 2>&1
    if %errorlevel%==0 (
        for /f "tokens=*" %%i in ('php tests\phpunit-9.phar --version') do echo [OK] %%i
        
        REM Run quick test if test files exist
        if exist "tests\Unit\UserTest.php" (
            echo Running quick test verification...
            php tests\phpunit-9.phar --configuration phpunit.xml --filter testValidatePasswordStrength_ValidPassword >nul 2>&1
            if %errorlevel%==0 (
                echo [OK] Test suite verified working
            ) else (
                echo [INFO] Tests available but may need implementation
            )
        )
    ) else (
        echo WARNING: PHPUnit installed but not working correctly
    )
)

REM Create web access
echo [7/7] Setting up web access...
echo Attempting to create symbolic link...
mklink /D "C:\xampp\htdocs\ttpm" "%cd%" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Symbolic link created: http://localhost/ttpm/
) else (
    echo INFO: Symbolic link failed (no admin rights)
    echo.
    echo ALTERNATIVE OPTIONS:
    echo 1. Copy project to C:\xampp\htdocs\
    echo 2. Use built-in server: php -S localhost:8000
    echo 3. Run as administrator for symbolic link
)

echo.
echo ============================================ 
echo SETUP COMPLETE!
echo ============================================
echo.
echo NEXT STEPS:
echo 1. Open XAMPP Control Panel
echo 2. Start Apache and MySQL services
echo 3. Visit: http://localhost/ttmp/ or http://localhost:8000
echo 4. Login with: james_ward / password123
echo.
echo TESTING:
echo - Run all tests: php tests\phpunit-9.phar --configuration phpunit.xml
echo - Run specific: php tests\phpunit-9.phar --testsuite unit
echo - View results: Tests should pass (33 tests total)
echo.
echo DEVELOPMENT:
echo - Edit code in this directory
echo - Database: http://localhost/phpmyadmin
echo - Reset DB: rerun this setup script
echo.
pause
