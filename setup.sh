#!/bin/bash
# CS3332 AllStars - TTPM Development Environment Setup
# Cross-platform setup script (Linux/macOS/WSL)

echo \"============================================\"
echo \"CS3332 AllStars TTPM Setup\"
echo \"Team Task and Project Management System\"
echo \"============================================\"
echo

# Check if MySQL is available
echo \"[1/6] Checking MySQL installation...\"
if ! command -v mysql &> /dev/null; then
    echo
    echo \"ERROR: MYSQL NOT FOUND\"
    echo \"============================================\"
    echo \"REQUIRED ACTION: Install MySQL\"
    echo \"============================================\"
    echo \"1. For Ubuntu/Debian: sudo apt install mysql-server\"
    echo \"2. For macOS: brew install mysql\"
    echo \"3. For RHEL/CentOS: sudo dnf install mysql-server\"
    echo \"4. Or install MAMP for macOS: https://www.mamp.info/\"
    echo \"5. Start MySQL service and run this script again\"
    echo
    echo \"Press any key when MySQL is installed and running...\"
    read -n 1 -s
    
    # Check again after user action
    if ! command -v mysql &> /dev/null; then
        echo \"ERROR: MySQL still not found\"
        echo \"Please ensure MySQL is installed correctly\"
        exit 1
    fi
fi
echo \"[OK] MySQL found\"

# Check if MySQL service is running
echo \"[2/6] Checking MySQL service...\"
if ! pgrep -x \"mysqld\" > /dev/null && ! pgrep -x \"mysql\" > /dev/null; then
    echo
    echo \"WARNING: MYSQL NOT RUNNING\"
    echo \"============================================\"
    echo \"REQUIRED ACTION: Start MySQL Service\"
    echo \"============================================\"
    echo \"Ubuntu/Debian: sudo systemctl start mysql\"
    echo \"macOS (Homebrew): brew services start mysql\"
    echo \"macOS (MAMP): Open MAMP and start MySQL\"
    echo \"RHEL/CentOS: sudo systemctl start mysqld\"
    echo
    echo \"Press any key when MySQL is running...\"
    read -n 1 -s
fi
echo \"[OK] MySQL service checked\"

# Check if PHP is available
echo \"[3/6] Checking PHP installation...\"
if ! command -v php &> /dev/null; then
    echo
    echo \"ERROR: PHP NOT FOUND\"
    echo \"============================================\"
    echo \"REQUIRED ACTION: Install PHP\"
    echo \"============================================\"
    echo \"1. Ubuntu/Debian: sudo apt install php php-mysql php-cli\"
    echo \"2. macOS: brew install php\"
    echo \"3. RHEL/CentOS: sudo dnf install php php-mysqlnd\"
    echo \"4. Or use MAMP which includes PHP\"
    echo \"5. Ensure PHP 7.4+ is installed\"
    echo
    echo \"Press any key when PHP is installed...\"
    read -n 1 -s
    
    # Check again after user action
    if ! command -v php &> /dev/null; then
        echo \"ERROR: PHP still not found\"
        echo \"Please ensure PHP is installed correctly\"
        exit 1
    fi
fi

PHP_VERSION=$(php -r \"echo PHP_VERSION;\")
echo \"[OK] PHP $PHP_VERSION found\"

# Check required PHP extensions
echo \"[4/6] Checking PHP extensions...\"
REQUIRED_EXTENSIONS=(\"pdo\" \"pdo_mysql\" \"mysqli\")
MISSING_EXTENSIONS=()

for ext in \"${REQUIRED_EXTENSIONS[@]}\"; do
    if ! php -m | grep -q \"^$ext$\"; then
        MISSING_EXTENSIONS+=(\"$ext\")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo
    echo \"ERROR: MISSING PHP EXTENSIONS\"
    echo \"============================================\"
    echo \"REQUIRED ACTION: Install PHP Extensions\"
    echo \"============================================\"
    echo \"Missing extensions: ${MISSING_EXTENSIONS[*]}\"
    echo
    echo \"Ubuntu/Debian: sudo apt install php-mysql\"
    echo \"macOS (Homebrew): These should be included with php\"
    echo \"RHEL/CentOS: sudo dnf install php-mysqlnd\"
    echo
    echo \"Press any key when extensions are installed...\"
    read -n 1 -s
    
    # Check again
    for ext in \"${MISSING_EXTENSIONS[@]}\"; do
        if ! php -m | grep -q \"^$ext$\"; then
            echo \"ERROR: PHP extension '$ext' still not found\"
            echo \"Please install required PHP extensions\"
            exit 1
        fi
    done
fi
echo \"[OK] Required PHP extensions found\"

# Create .env file if it doesn't exist
echo \"[5/6] Creating environment configuration...\"
if [ ! -f \".env\" ]; then
    echo \"Creating .env file...\"
    cat > .env << 'EOF'
# CS3332 AllStars TTPM Configuration
DB_HOST=localhost
DB_NAME=ttpm_system
DB_USER=root
DB_PASS=
DB_PORT=3306

# Application Settings
APP_ENV=development
APP_DEBUG=true

# Security (generate your own in production)
APP_KEY=your-secret-key-here

# Development URLs
APP_URL=http://localhost
DEV_PORT=8000
EOF
    echo \"[OK] .env file created\"
else
    echo \"[OK] .env file already exists\"
fi

# Create database.php config if it doesn't exist
echo \"[6/6] Setting up database configuration...\"
if [ ! -f \"src/config/database.php\" ]; then
    echo \"Creating database.php...\"
    cat > src/config/database.php << 'EOF'
<?php
// CS3332 AllStars TTPM Database Configuration

// Load environment variables
$env = [];
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
}

// Database configuration
$config = [
    'host' => $env['DB_HOST'] ?? 'localhost',
    'dbname' => $env['DB_NAME'] ?? 'ttpm_system',
    'username' => $env['DB_USER'] ?? 'root',
    'password' => $env['DB_PASS'] ?? '',
    'port' => $env['DB_PORT'] ?? 3306,
    'charset' => 'utf8mb4'
];

try {
    $pdo = new PDO(
        \"mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}\",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

return $pdo;
?>
EOF
    echo \"[OK] database.php created\"
else
    echo \"[OK] database.php already exists\"
fi

# Set up database
echo \"[7/7] Setting up database...\"
echo \"Creating database and tables...\"

# Read MySQL credentials from .env
DB_USER=$(grep DB_USER .env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASS .env | cut -d '=' -f2)

# Test MySQL connection
echo \"Testing MySQL connection...\"
if [ -z \"$DB_PASS\" ]; then
    mysql -u \"$DB_USER\" -e \"SELECT 1;\" 2>/dev/null
    CONNECTION_TEST=$?
else
    mysql -u \"$DB_USER\" -p\"$DB_PASS\" -e \"SELECT 1;\" 2>/dev/null
    CONNECTION_TEST=$?
fi

if [ $CONNECTION_TEST -ne 0 ]; then
    echo
    echo \"ERROR: DATABASE CONNECTION FAILED\"
    echo \"============================================\"
    echo \"TROUBLESHOOTING REQUIRED\"
    echo \"============================================\"
    echo \"Most common solutions:\"
    echo \"1. MySQL service not running:\"
    echo \"   - Ubuntu/Debian: sudo systemctl start mysql\"
    echo \"   - macOS: brew services start mysql\"
    echo \"   - Check: sudo systemctl status mysql\"
    echo \"2. MySQL root password required:\"
    echo \"   - Edit .env file and set DB_PASS=your_password\"
    echo \"   - Or reset MySQL root password\"
    echo \"3. Permission issues:\"
    echo \"   - Check MySQL user permissions\"
    echo \"   - Ensure root user can connect from localhost\"
    echo
    echo \"Press any key to exit and try these solutions...\"
    read -n 1 -s
    exit 1
fi

# Create database schema
echo \"Creating database and tables...\"
if [ -z \"$DB_PASS\" ]; then
    mysql -u \"$DB_USER\" < database/schema.sql 2>db_error.log
    SCHEMA_RESULT=$?
else
    mysql -u \"$DB_USER\" -p\"$DB_PASS\" < database/schema.sql 2>db_error.log
    SCHEMA_RESULT=$?
fi

if [ $SCHEMA_RESULT -ne 0 ]; then
    echo
    echo \"ERROR: DATABASE SETUP FAILED\"
    echo \"============================================\"
    echo \"Error details:\"
    if [ -f db_error.log ]; then
        cat db_error.log
        rm db_error.log
    fi
    echo
    echo \"Common solutions:\"
    echo \"1. Check MySQL credentials in .env file\"
    echo \"2. Ensure MySQL service is running\"
    echo \"3. Verify database permissions\"
    echo
    exit 1
else
    echo \"[OK] Database schema created successfully\"
    [ -f db_error.log ] && rm db_error.log
fi

# Load sample data
echo \"Loading sample data...\"
if [ -z \"$DB_PASS\" ]; then
    mysql -u \"$DB_USER\" < database/sample_data.sql 2>data_error.log
    DATA_RESULT=$?
else
    mysql -u \"$DB_USER\" -p\"$DB_PASS\" < database/sample_data.sql 2>data_error.log
    DATA_RESULT=$?
fi

if [ $DATA_RESULT -ne 0 ]; then
    echo
    echo \"WARNING: SAMPLE DATA FAILED\"
    echo \"Database schema created successfully, but sample data failed to load.\"
    echo
    echo \"Error details:\"
    if [ -f data_error.log ]; then
        cat data_error.log
        rm data_error.log
    fi
    echo
    echo \"You can continue development and create test users manually.\"
    echo \"Press any key to continue...\"
    read -n 1 -s
else
    echo \"[OK] Sample data loaded successfully\"
    [ -f data_error.log ] && rm data_error.log
fi

echo
echo \"============================================\"
echo \"SETUP COMPLETE!\"
echo \"============================================\"
echo
echo \"Your development environment is ready:\"
echo \"- Database: ttpm_system created with sample data\"
echo \"- Test users: james_ward, summer_hill, juan_ledet, alaric_higgins\"
echo \"- Password for all test users: password123\"
echo \"- Configuration: .env and database.php created\"
echo
echo \"NEXT STEPS FOR DEVELOPMENT:\"
echo \"============================================\"
echo \"1. Start the PHP development server:\"
echo \"     php -S localhost:8000\"
echo \"2. Open your web browser to:\"
echo \"     http://localhost:8000\"
echo \"3. Start coding in this directory\"
echo \"4. Changes will appear instantly in the browser\"
echo \"5. Use test accounts to login and test features\"
echo
echo \"HELPFUL COMMANDS:\"
echo \"- Start MySQL: sudo systemctl start mysql (Linux)\"
echo \"- Start MySQL: brew services start mysql (macOS)\"
echo \"- Access database: mysql -u root ttpm_system\"
echo \"- Run PHP scripts: php filename.php\"
echo \"- Check PHP version: php --version\"
echo
echo \"Ready to start coding!\"
echo \"Team members can now: clone → chmod +x setup.sh → ./setup.sh → code\"
echo
