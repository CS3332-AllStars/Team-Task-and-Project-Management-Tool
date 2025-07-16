<?php
// Debug script to see what's happening to the SQL filtering
echo "🔍 Debugging SQL file processing...\n\n";

$filePath = __DIR__ . '/../../database/sample_data.sql';
echo "📁 Reading file: {$filePath}\n";

if (!file_exists($filePath)) {
    echo "❌ File not found!\n";
    exit(1);
}

$sql = file_get_contents($filePath);
echo "📊 Original file size: " . strlen($sql) . " characters\n";
echo "📊 Original statement count (rough): " . substr_count($sql, ';') . "\n\n";

// Show first few lines
echo "📄 First 10 lines of original file:\n";
$lines = explode("\n", $sql);
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo "   " . ($i+1) . ": " . $lines[$i] . "\n";
}
echo "\n";

// Apply our filtering
echo "🧹 Applying filtering...\n";

// Remove the specific TRUNCATE section at the beginning
$sql = preg_replace('/-- Clear existing data.*?SET FOREIGN_KEY_CHECKS = 1;/s', '', $sql);
echo "📊 After TRUNCATE removal: " . strlen($sql) . " characters\n";

// Remove the USE statement as well
$sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql);
echo "📊 After USE removal: " . strlen($sql) . " characters\n";

// Replace TRUNCATE statements with DELETE to avoid foreign key issues
$sql = preg_replace('/TRUNCATE\s+TABLE\s+(\w+);?/i', 'DELETE FROM $1;', $sql);
echo "📊 After TRUNCATE->DELETE replacement: " . strlen($sql) . " characters\n";

// Remove verification queries at the end (they cause PDO buffering issues)
$sql = preg_replace('/SELECT\s+[^;]*\s+as\s+(info|metric)[^;]*;/i', '', $sql);
echo "📊 After SELECT removal: " . strlen($sql) . " characters\n\n";

// Show first few lines after filtering
echo "📄 First 10 lines after filtering:\n";
$filteredLines = explode("\n", $sql);
$lineNum = 1;
$shown = 0;
foreach ($filteredLines as $line) {
    $line = trim($line);
    if (!empty($line) && !str_starts_with($line, '--')) {
        echo "   {$lineNum}: {$line}\n";
        $shown++;
        if ($shown >= 10) break;
    }
    $lineNum++;
}

// Split and count statements
$statements = explode(';', $sql);
$validStatements = 0;
$insertStatements = 0;

echo "\n📊 Statement analysis:\n";
foreach ($statements as $statement) {
    $statement = trim($statement);
    
    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'SELECT') === 0) {
        continue;
    }
    
    $validStatements++;
    if (stripos($statement, 'INSERT') === 0) {
        $insertStatements++;
    }
}

echo "   Valid statements: {$validStatements}\n";
echo "   INSERT statements: {$insertStatements}\n";
?>
