<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if tables exist
    $stmt = $conn->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    // Check access_logs table structure
    if (in_array('access_logs', $tables)) {
        echo "\nAccess_logs table structure:\n";
        $stmt = $conn->query('DESCRIBE access_logs');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} ({$column['Null']})\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>