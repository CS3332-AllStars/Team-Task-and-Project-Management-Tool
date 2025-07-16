#!/bin/bash
# CS3332 AllStars - TTPM Development Environment Setup with PHPUnit
# Unix/Linux/Mac Setup Script v2.0

echo "============================================"
echo "CS3332 AllStars TTPM Setup v2.0"
echo "Team Task and Project Management System"
echo "with PHPUnit Testing Framework"
echo "============================================"
echo

# Check for required tools
echo "[1/7] Checking system requirements..."
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found. Please install PHP 7.4+ first."
    exit 1
fi
echo "[OK] PHP found: $(php -v | head -n1)"

if ! command -v mysql &> /dev/null; then
    echo "WARNING: MySQL client not found in PATH"
    echo "Ensure MySQL server is running and accessible"
fi

# Create .env file
echo "[2/7] Setting up environment configuration..."
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp ".env.example" ".env"
    echo "[OK] .env file created"
else
    echo "[OK] .env file already exists"
fi

# Create database config
echo "[3/7] Setting up database configuration..."
mkdir -p "src/config"
if [ ! -f "src/config/database.php" ]; then
    echo "Creating database configuration..."
    cat > "src/config/database.php" << 'EOF'
<?php
// Auto-generated database configuration
try {
    $db_host = 'localhost';
    $db_name = 'ttpm_system';
    $db_user = 'root';
    $db_pass = '';
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    unset($db_user, $db_pass);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed.");
}
?>
EOF
    echo "[OK] Database configuration created"
else
    echo "[OK] Database configuration already exists"
fi

# Setup database
echo "[4/7] Setting up database..."
echo "Creating database and loading schema..."
mysql -u root -e "DROP DATABASE IF EXISTS ttpm_system; CREATE DATABASE ttpm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "[OK] Database created"
    mysql -u root ttpm_system < "database/schema.sql" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "[OK] Schema loaded"
        mysql -u root ttpm_system < "database/sample_data.sql" 2>/dev/null
        if [ $? -eq 0 ]; then
            echo "[OK] Sample data loaded"
        else
            echo "WARNING: Sample data failed to load"
        fi
    else
        echo "ERROR: Schema failed to load"
    fi
else
    echo "ERROR: Database creation failed - ensure MySQL is running"
fi

# Setup PHPUnit
echo "[5/7] Setting up PHPUnit testing framework..."
mkdir -p "tests"

if [ -f "tests/phpunit-9.phar" ]; then
    echo "[OK] PHPUnit already installed"
else
    echo "Downloading PHPUnit..."
    if command -v curl &> /dev/null; then
        curl -o "tests/phpunit-9.phar" -L "https://phar.phpunit.de/phpunit-9.phar" 2>/dev/null
    elif command -v wget &> /dev/null; then
        wget -O "tests/phpunit-9.phar" "https://phar.phpunit.de/phpunit-9.phar" 2>/dev/null
    else
        echo "ERROR: Neither curl nor wget found. Please install one or download PHPUnit manually:"
        echo "1. Download: https://phar.phpunit.de/phpunit-9.phar"
        echo "2. Save as: tests/phpunit-9.phar"
        echo "3. Make executable: chmod +x tests/phpunit-9.phar"
        read -p "Continue without PHPUnit? (y/n): " CONTINUE
        if [ "$CONTINUE" != "y" ]; then
            exit 1
        fi
    fi
    
    if [ -f "tests/phpunit-9.phar" ]; then
        chmod +x "tests/phpunit-9.phar"
        echo "[OK] PHPUnit downloaded and made executable"
    fi
fi

# Test PHPUnit
if [ -f "tests/phpunit-9.phar" ]; then
    echo "Testing PHPUnit installation..."
    if php tests/phpunit-9.phar --version &>/dev/null; then
        echo "[OK] $(php tests/phpunit-9.phar --version)"
        
        # Run quick test if test files exist
        if [ -f "tests/Unit/UserTest.php" ]; then
            echo "Running quick test verification..."
            if php tests/phpunit-9.phar --configuration phpunit.xml --filter testValidatePasswordStrength_ValidPassword &>/dev/null; then
                echo "[OK] Test suite verified working"
            else
                echo "[INFO] Tests available but may need implementation"
            fi
        fi
    else
        echo "WARNING: PHPUnit installed but not working correctly"
    fi
fi

# Setup web access
echo "[6/7] Setting up web access..."
echo "Web server options:"
echo "1. Built-in PHP server: php -S localhost:8000"
echo "2. Copy to web root: cp -r . /var/www/html/ttpm"
echo "3. Use MAMP/XAMPP htdocs directory"

# Final setup
echo "[7/7] Final setup..."
echo

echo "============================================"
echo "SETUP COMPLETE!"
echo "============================================"
echo
echo "NEXT STEPS:"
echo "1. Start your web server (MAMP/XAMPP) or run: php -S localhost:8000"
echo "2. Visit: http://localhost:8000 or http://localhost/ttpm/"
echo "3. Login with: james_ward / password123"
echo
echo "TESTING:"
echo "- Run all tests: php tests/phpunit-9.phar --configuration phpunit.xml"
echo "- Run specific: php tests/phpunit-9.phar --testsuite unit"
echo "- View results: Tests should pass (33 tests total + TDD placeholders)"
echo
echo "TDD DEVELOPMENT:"
echo "- New test files created for Project, Task, Comment models"
echo "- Use 'markTestSkipped' until implementations are ready"
echo "- Follow Red-Green-Refactor cycle for new features"
echo
echo "Ready to start coding!"
