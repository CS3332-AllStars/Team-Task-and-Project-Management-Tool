@echo off
REM CS3332 AllStars - TTPM Development Environment Setup
REM Windows/XAMPP Setup Script

echo ============================================
echo CS3332 AllStars TTPM Setup
echo Team Task & Project Management System
echo ============================================
echo.

REM Check if XAMPP is installed and running
echo [1/6] Checking XAMPP installation...
if not exist "C:\xampp\xampp-control.exe" (
    echo ERROR: XAMPP not found at C:\xampp\
    echo Please install XAMPP from https://www.apachefriends.org/
    echo After installation, start Apache and MySQL services
    pause
    exit /b 1
)
echo ✓ XAMPP found

REM Check if Apache and MySQL are running
echo [2/6] Checking services...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo WARNING: Apache may not be running
    echo Please start Apache in XAMPP Control Panel
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo WARNING: MySQL may not be running
    echo Please start MySQL in XAMPP Control Panel
    echo.
    echo Press any key when MySQL is running...
    pause >nul
)

REM Check PHP version
echo [3/6] Checking PHP version...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP not found in PATH
    echo Add C:\xampp\php to your system PATH
    echo Or run this script from XAMPP PHP directory
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('php --version ^| findstr /i "PHP"') do (
    set PHP_VERSION=%%i
    goto :php_found
)
:php_found
echo ✓ PHP %PHP_VERSION% found

REM Create .env file if it doesn't exist
echo [4/6] Creating environment configuration...
if not exist ".env" (
    echo Creating .env file...
    (
        echo # CS3332 AllStars TTPM Configuration
        echo DB_HOST=localhost
        echo DB_NAME=ttpm_system
        echo DB_USER=root
        echo DB_PASS=
        echo DB_PORT=3306
        echo.
        echo # Application Settings
        echo APP_ENV=development
        echo APP_DEBUG=true
    ) > .env
    echo ✓ .env file created
) else (
    echo ✓ .env file already exists
)

REM Create database.php config if it doesn't exist
echo [5/6] Setting up database configuration...
if not exist "src\config\database.php" (
    echo Creating database.php...
    (
        echo ^<?php
        echo // CS3332 AllStars TTPM Database Configuration
        echo.
        echo // Load environment variables
        echo $env = [];
        echo if (file_exists(__DIR__ . '/../../.env'^)^) {
        echo     $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES ^| FILE_SKIP_EMPTY_LINES^);
        echo     foreach ($lines as $line^) {
        echo         if (strpos(trim($line^), '#'^) === 0^) continue;
        echo         list($name, $value^) = explode('=', $line, 2^);
        echo         $env[trim($name^)] = trim($value^);
        echo     }
        echo }
        echo.
        echo // Database configuration
        echo $config = [
        echo     'host' =^> $env['DB_HOST'] ?? 'localhost',
        echo     'dbname' =^> $env['DB_NAME'] ?? 'ttpm_system',
        echo     'username' =^> $env['DB_USER'] ?? 'root',
        echo     'password' =^> $env['DB_PASS'] ?? '',
        echo     'port' =^> $env['DB_PORT'] ?? 3306,
        echo     'charset' =^> 'utf8mb4'
        echo ];
        echo.
        echo try {
        echo     $pdo = new PDO(
        echo         "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}",
        echo         $config['username'],
        echo         $config['password']
        echo     ^);
        echo     $pdo-^>setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION^);
        echo } catch (PDOException $e^) {
        echo     die('Database connection failed: ' . $e-^>getMessage(^)^);
        echo }
        echo.
        echo return $pdo;
        echo ?^>
    ) > src\config\database.php
    echo ✓ database.php created
) else (
    echo ✓ database.php already exists
)

REM Set up database
echo [6/6] Setting up database...
echo Creating database and tables...
mysql -u root -h localhost < database\schema.sql
if %errorlevel% neq 0 (
    echo ERROR: Failed to create database schema
    echo Make sure MySQL is running and accessible
    pause
    exit /b 1
)

echo Loading sample data...
mysql -u root -h localhost < database\sample_data.sql
if %errorlevel% neq 0 (
    echo ERROR: Failed to load sample data
    echo Database schema created but sample data failed
    pause
    exit /b 1
)

echo.
echo ============================================
echo ✓ Setup Complete!
echo ============================================
echo.
echo Your development environment is ready:
echo • Database: ttpm_system created with sample data
echo • Test users: james_ward, summer_hill, juan_ledet, alaric_higgins
echo • Password for all test users: password123
echo • Local URL: http://localhost/Team-Task-And-Project-Management-Tool/
echo.
echo Next steps:
echo 1. Copy this project to C:\xampp\htdocs\
echo 2. Open http://localhost/Team-Task-And-Project-Management-Tool/
echo 3. Start coding!
echo.
echo Team members can now clone and run setup.bat
pause
