#!/bin/bash
# CS3332 AllStars - TTPM Development Environment Setup
# Cross-platform setup script (Linux/macOS/WSL)

echo "============================================"
echo "CS3332 AllStars TTPM Setup"
echo "Team Task & Project Management System"
echo "============================================"
echo

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if MySQL is available
echo "[1/6] Checking MySQL installation..."
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}ERROR: MySQL not found${NC}"
    echo "Please install MySQL/MariaDB or use XAMPP/MAMP"
    echo "For Ubuntu/Debian: sudo apt install mysql-server"
    echo "For macOS: brew install mysql"
    exit 1
fi
echo -e "${GREEN}✓ MySQL found${NC}"

# Check if PHP is available
echo "[2/6] Checking PHP installation..."
if ! command -v php &> /dev/null; then
    echo -e "${RED}ERROR: PHP not found${NC}"
    echo "Please install PHP 7.4 or higher"
    echo "For Ubuntu/Debian: sudo apt install php php-mysql"
    echo "For macOS: brew install php"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION found${NC}"

# Check required PHP extensions
echo "[3/6] Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mysqli")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        echo -e "${RED}ERROR: PHP extension '$ext' not found${NC}"
        echo "Please install php-$ext"
        exit 1
    fi
done
echo -e "${GREEN}✓ Required PHP extensions found${NC}"

# Create .env file if it doesn't exist
echo "[4/6] Creating environment configuration..."
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
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
EOF
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Create database.php config if it doesn't exist
echo "[5/6] Setting up database configuration..."
if [ ! -f "src/config/database.php" ]; then
    echo "Creating database.php..."
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
        "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}",
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
    echo -e "${GREEN}✓ database.php created${NC}"
else
    echo -e "${GREEN}✓ database.php already exists${NC}"
fi

# Get database credentials
echo "[6/6] Setting up database..."
echo "Setting up MySQL database..."

# Read MySQL credentials from .env or prompt
DB_USER=$(grep DB_USER .env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASS .env | cut -d '=' -f2)

if [ -z "$DB_PASS" ]; then
    echo -n "Enter MySQL root password (or press Enter if no password): "
    read -s DB_PASS
    echo
fi

# Test MySQL connection
echo "Testing MySQL connection..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" -e "SELECT 1;" 2>/dev/null
else
    mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" 2>/dev/null
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Cannot connect to MySQL${NC}"
    echo "Please check your MySQL credentials in .env file"
    exit 1
fi

# Create database schema
echo "Creating database and tables..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" < database/schema.sql
else
    mysql -u "$DB_USER" -p"$DB_PASS" < database/schema.sql
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Failed to create database schema${NC}"
    exit 1
fi

# Load sample data
echo "Loading sample data..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" < database/sample_data.sql
else
    mysql -u "$DB_USER" -p"$DB_PASS" < database/sample_data.sql
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Failed to load sample data${NC}"
    echo "Database schema created but sample data failed"
    exit 1
fi

echo
echo "============================================"
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo "============================================"
echo
echo "Your development environment is ready:"
echo "• Database: ttpm_system created with sample data"
echo "• Test users: james_ward, summer_hill, juan_ledet, alaric_higgins"
echo "• Password for all test users: password123"
echo
echo "To start development server:"
echo "  php -S localhost:8000"
echo "  Then open: http://localhost:8000"
echo
echo "Team members can now:"
echo "1. Clone the repository"
echo "2. Run: chmod +x setup.sh && ./setup.sh"
echo "3. Start coding!"
echo
