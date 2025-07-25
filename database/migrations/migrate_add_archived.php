<?php
// CS3332 AllStars Team Task & Project Management System
// Migration Script: Add is_archived column to projects table
// CS3-12F: Project Archival & Deletion

echo "Starting migration: Add is_archived column to projects table\n";

// Database connection
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

try {
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected to database successfully\n";
    
    // Check if column already exists
    $check_query = "
        SELECT COUNT(*) as column_count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'is_archived'
    ";
    
    $stmt = $mysqli->prepare($check_query);
    $stmt->bind_param("s", $dbname);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $column_exists = $row['column_count'] > 0;
    $stmt->close();
    
    if ($column_exists) {
        echo "Column 'is_archived' already exists in projects table\n";
    } else {
        // Add the column
        $add_column_query = "ALTER TABLE projects ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER description";
        if ($mysqli->query($add_column_query)) {
            echo "✅ Added is_archived column to projects table\n";
        } else {
            throw new Exception("Error adding column: " . $mysqli->error);
        }
    }
    
    // Check if index already exists
    $check_index_query = "
        SELECT COUNT(*) as index_count
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'projects' 
        AND INDEX_NAME = 'idx_projects_archived'
    ";
    
    $stmt = $mysqli->prepare($check_index_query);
    $stmt->bind_param("s", $dbname);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $index_exists = $row['index_count'] > 0;
    $stmt->close();
    
    if ($index_exists) {
        echo "Index 'idx_projects_archived' already exists\n";
    } else {
        // Add index for performance
        $add_index_query = "CREATE INDEX idx_projects_archived ON projects(is_archived)";
        if ($mysqli->query($add_index_query)) {
            echo "✅ Added index on is_archived column\n";
        } else {
            throw new Exception("Error adding index: " . $mysqli->error);
        }
    }
    
    // Verify the migration
    $verify_query = "
        SELECT 
            COLUMN_NAME,
            DATA_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'is_archived'
    ";
    
    $stmt = $mysqli->prepare($verify_query);
    $stmt->bind_param("s", $dbname);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $column_info = $result->fetch_assoc();
        echo "\n✅ Migration completed successfully!\n";
        echo "Column details:\n";
        echo "  Name: " . $column_info['COLUMN_NAME'] . "\n";
        echo "  Type: " . $column_info['DATA_TYPE'] . "\n";
        echo "  Nullable: " . $column_info['IS_NULLABLE'] . "\n";
        echo "  Default: " . ($column_info['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
    } else {
        throw new Exception("Migration verification failed - column not found");
    }
    
    $stmt->close();
    $mysqli->close();
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "You can now use the archive/delete project functionality.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>