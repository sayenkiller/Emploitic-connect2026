<?php
// Simple database connection test
require_once 'config.php';

echo "<h1>Database Connection Test</h1>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    echo "<p>Attempting to connect to database...</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
    echo "<p>Username: " . DB_USER . "</p>";

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    echo "<p style='color: green; font-weight: bold;'>✓ Database connection successful!</p>";

    // Test if tables exist
    echo "<p>Checking if tables exist...</p>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'participants'");
    $participantsExists = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW TABLES LIKE 'access_logs'");
    $accessLogsExists = $stmt->rowCount() > 0;

    echo "<p>Participants table: " . ($participantsExists ? "<span style='color: green;'>✓ Exists</span>" : "<span style='color: red;'>✗ Missing</span>") . "</p>";
    echo "<p>Access_logs table: " . ($accessLogsExists ? "<span style='color: green;'>✓ Exists</span>" : "<span style='color: red;'>✗ Missing</span>") . "</p>";

    if (!$participantsExists || !$accessLogsExists) {
        echo "<p style='color: orange;'>Tables missing. Attempting to create them...</p>";

        $sql = "
        CREATE TABLE IF NOT EXISTS participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            telephone VARCHAR(20) NOT NULL,
            statut ENUM('etudiant', 'diplome', 'emploi', 'professionnel') NOT NULL,
            domaine VARCHAR(255) DEFAULT NULL,
            verification_token VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            qr_code VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            INDEX idx_email (email),
            INDEX idx_token (verification_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS access_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            participant_id INT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            qr_scanned TINYINT(1) DEFAULT 1,
            scanner_id VARCHAR(100) DEFAULT NULL,
            FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
            INDEX idx_participant (participant_id),
            INDEX idx_access_time (access_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Tables created successfully!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Database connection failed!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";

    // Check if it's a database not found error
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p style='color: orange;'>The database '" . DB_NAME . "' does not exist. Please check your database name in config.php</p>";
        echo "<p>Available databases might be listed in your InfinityFree control panel.</p>";
    }
}
?>
