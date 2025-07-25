@echo off
REM CS3332 AllStars - TTPM Development Environment Setup
REM Windows/XAMPP Setup Script

echo ============================================
echo CS3332 AllStars TTPM Setup
echo Team Task and Project Management System
echo ============================================
echo.

REM Check if XAMPP is installed and running
echo [1/6] Checking XAMPP installation...
if not exist "C:\xampp\xampp-control.exe" (
    echo.
    echo ERROR: XAMPP NOT FOUND
    echo ============================================
    echo REQUIRED ACTION: Install XAMPP
    echo ============================================
    echo 1. Download XAMPP from: https://www.apachefriends.org/
    echo 2. Install XAMPP to C:\xampp\ (default location)
    echo 3. After installation, open XAMPP Control Panel
    echo 4. Start both Apache and MySQL services
    echo 5. Run this setup script again
    echo.
    echo Press any key when XAMPP is installed and services are running...
    pause
    
    REM Check again after user action
    if not exist "C:\xampp\xampp-control.exe" (
        echo ERROR: XAMPP still not found at C:\xampp\
        echo Please ensure XAMPP is installed correctly
        pause
        exit /b 1
    )
)
echo [OK] XAMPP found

REM Check if Apache and MySQL are running
echo [2/6] Checking services...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo.
    echo WARNING: APACHE NOT RUNNING
    echo ============================================
    echo REQUIRED ACTION: Start Apache in XAMPP
    echo ============================================
    echo 1. Open XAMPP Control Panel
    echo 2. Click "Start" next to Apache
    echo 3. Wait for Apache status to show "Running"
    echo.
    echo Press any key when Apache is running...
    pause
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo.
    echo WARNING: MYSQL NOT RUNNING
    echo ============================================
    echo REQUIRED ACTION: Start MySQL in XAMPP
    echo ============================================
    echo 1. Open XAMPP Control Panel
    echo 2. Click "Start" next to MySQL
    echo 3. Wait for MySQL status to show "Running"
    echo.
    echo Press any key when MySQL is running...
    pause
)
echo [OK] Apache and MySQL services checked

REM Check PHP version
echo [3/6] Checking PHP version...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ⚠️ PHP NOT ACCESSIBLE
    echo ============================================
    echo AUTOMATIC FIX: Adding XAMPP to PATH
    echo ============================================
    echo Adding XAMPP to PATH for this session...
    
    REM Add XAMPP paths to current session PATH
    set "PATH=%PATH%;C:\xampp\php;C:\xampp\mysql\bin;C:\xampp\apache\bin"
    
    REM Test PHP again
    php --version >nul 2>&1
    if %errorlevel% neq 0 (
        echo.
        echo ❌ AUTOMATIC FIX FAILED
        echo ============================================
        echo MANUAL ACTION REQUIRED: Add XAMPP to PATH
        echo ============================================
        echo 1. Right-click "This PC" -^> Properties
        echo 2. Click "Advanced system settings"
        echo 3. Click "Environment Variables"
        echo 4. Select "Path" in System variables, click "Edit"
        echo 5. Add: C:\xampp\php
        echo 6. Add: C:\xampp\mysql\bin
        echo 7. Click OK and restart command prompt
        echo 8. Run this setup script again
        echo.
        echo Press any key to exit...
        pause
        exit /b 1
    ) else (
        echo ✓ XAMPP added to PATH for this session
    )
) else (
    echo ✓ PHP already accessible
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

REM Set up database using direct XAMPP MySQL path
echo [6/6] Setting up database...
echo Creating database and tables...

REM Use direct XAMPP MySQL path instead of relying on PATH
set MYSQL_CMD="C:\xampp\mysql\bin\mysql.exe"
if not exist %MYSQL_CMD% (
    echo.
    echo ❌ MYSQL EXECUTABLE NOT FOUND
    echo ============================================
    echo XAMPP MySQL not found at expected location
    echo ============================================
    echo Please ensure XAMPP is installed correctly
    echo Expected location: C:\xampp\mysql\bin\mysql.exe
    echo.
    echo Press any key to exit...
    pause
    exit /b 1
)

echo Using MySQL from: %MYSQL_CMD%

REM Try database setup with direct path
%MYSQL_CMD% -u root -h localhost < database\schema.sql 2>db_error.log
if %errorlevel% neq 0 (
    echo.
    echo ❌ DATABASE SETUP FAILED
    echo ============================================
    echo TROUBLESHOOTING REQUIRED
    echo ============================================
    echo Error details:
    if exist db_error.log (
        type db_error.log
        del db_error.log
    )
    echo.
    echo Most common solutions:
    echo 1. Reset MySQL root password in XAMPP:
    echo    - Open XAMPP Control Panel
    echo    - Click "Admin" next to MySQL
    echo    - Go to User Accounts -^> Edit privileges for root
    echo    - Set password to empty or update .env file
    echo 2. Check if another MySQL service is running:
    echo    - Open Task Manager
    echo    - Look for other MySQL processes
    echo    - Stop conflicting services
    echo 3. Restart XAMPP MySQL:
    echo    - Stop MySQL in XAMPP Control Panel
    echo    - Wait 5 seconds
    echo    - Start MySQL again
    echo.
    echo Press any key to exit and try these solutions...
    pause
    exit /b 1
) else (
    echo ✓ Database schema created successfully
    if exist db_error.log del db_error.log
)

echo Loading sample data...
%MYSQL_CMD% -u root -h localhost < database\sample_data.sql 2>data_error.log
if %errorlevel% neq 0 (
    echo.
    echo ⚠️ SAMPLE DATA FAILED
    echo Database schema created successfully, but sample data failed to load.
    echo.
    echo Error details:
    if exist data_error.log (
        type data_error.log
        del data_error.log
    )
    echo.
    echo You can continue development and create test users manually.
    echo Press any key to continue...
    pause
) else (
    echo ✓ Sample data loaded successfully
    if exist data_error.log del data_error.log
)

echo.
REM Create symbolic link to XAMPP htdocs for direct development
echo [7/7] Setting up development server access...
set PROJECT_NAME=Team-Task-And-Project-Management-Tool
set CURRENT_DIR=%CD%
set HTDOCS_PATH=C:\xampp\htdocs\%PROJECT_NAME%

REM Check if link already exists
if exist "%HTDOCS_PATH%" (
    echo ✓ Symbolic link already exists at %HTDOCS_PATH%
) else (
    echo Creating symbolic link to XAMPP htdocs...
    REM Create symbolic link (requires admin privileges on some systems)
    mklink /D "%HTDOCS_PATH%" "%CURRENT_DIR%" >nul 2>&1
    if %errorlevel% neq 0 (
        echo WARNING: Could not create symbolic link automatically
        echo This may require administrator privileges
        echo.
        echo Manual option 1 - Run as Administrator:
        echo   Right-click setup.bat and "Run as administrator"
        echo.
        echo Manual option 2 - Create link manually:
        echo   mklink /D "C:\xampp\htdocs\%PROJECT_NAME%" "%CURRENT_DIR%"
        echo.
        echo Manual option 3 - Copy files:
        echo   Copy this entire folder to C:\xampp\htdocs\
        echo.
    ) else (
        echo ✓ Symbolic link created successfully
    )
)

REM Run database migrations
echo Running database migrations...
php migrate.php >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Migration runner not available yet - this is normal for initial setup
) else (
    echo [OK] Database migrations completed
)

echo.
echo ============================================
echo SETUP COMPLETE!
echo ============================================
echo.
echo Your development environment is ready:
echo - Database: ttpm_system created with sample data
echo - Test users: james_ward, summer_hill, juan_ledet, alaric_higgins
echo - Password for all test users: password123
if exist "%HTDOCS_PATH%" (
    echo - Local URL: http://localhost/%PROJECT_NAME%/
    echo - Live development: Changes in this folder appear instantly
) else (
    echo - Manual setup required: See instructions above
)
echo.
echo NEXT STEPS FOR DEVELOPMENT:
echo ============================================
echo 1. Open your web browser
echo 2. Go to: http://localhost/%PROJECT_NAME%/
echo 3. Start coding in this directory
echo 4. Changes will appear instantly in the browser
echo 5. Use test accounts to login and test features
echo.
echo HELPFUL COMMANDS:
echo - Start XAMPP services: Open XAMPP Control Panel
echo - Access database: http://localhost/phpmyadmin
echo - Run PHP scripts: php filename.php
echo - MySQL console: mysql -u root
echo.
echo OPTIONAL: Add XAMPP to permanent PATH
echo This allows you to use 'php' and 'mysql' commands from any directory
echo.
set /p ADD_TO_PATH="Add XAMPP to system PATH permanently? (y/n): "
if /i "%ADD_TO_PATH%"=="y" (
    echo Adding XAMPP to system PATH...
    
    REM Add to system PATH using setx (requires admin on some systems)
    for /f "tokens=2*" %%i in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v PATH 2^>nul') do set SYSTEM_PATH=%%j
    
    REM Check if XAMPP paths are already in system PATH
    echo %SYSTEM_PATH% | findstr "xampp\php" >nul
    if %errorlevel% neq 0 (
        echo Attempting to add XAMPP to system PATH...
        setx PATH "%SYSTEM_PATH%;C:\xampp\php;C:\xampp\mysql\bin;C:\xampp\apache\bin" /M >nul 2>&1
        if %errorlevel%==0 (
            echo ✓ XAMPP added to system PATH successfully
            echo Restart your command prompt to use the new PATH
        ) else (
            echo WARNING: Could not add to system PATH (may require administrator privileges)
            echo Manual instructions:
            echo 1. Right-click "This PC" -^> Properties -^> Advanced system settings
            echo 2. Click "Environment Variables"
            echo 3. Select "Path" in System variables, click "Edit"
            echo 4. Add these paths:
            echo    C:\xampp\php
            echo    C:\xampp\mysql\bin
            echo    C:\xampp\apache\bin
        )
    ) else (
        echo ✓ XAMPP already in system PATH
    )
) else (
    echo Skipping PATH modification
)
echo.
echo Ready to start coding!
echo Team members can now clone and run setup.bat
pause
