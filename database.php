<?php
require_once 'config.php';

class Database {
    private $conn = null;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']));
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Create tables if they don't exist
    public function createTables() {
        $sql = "CREATE TABLE IF NOT EXISTS participants (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->conn->exec($sql);

            // Alter table to add columns if they don't exist (for existing databases)
            try {
                $this->conn->exec("ALTER TABLE access_logs ADD COLUMN nom VARCHAR(100) NOT NULL DEFAULT ''");
            } catch(PDOException $e) {
                // Column might already exist, ignore
            }
            try {
                $this->conn->exec("ALTER TABLE access_logs ADD COLUMN prenom VARCHAR(100) NOT NULL DEFAULT ''");
            } catch(PDOException $e) {
                // Column might already exist, ignore
            }

            return true;
        } catch(PDOException $e) {
            error_log("Table Creation Error: " . $e->getMessage());
            return false;
        }
    }
}
?>