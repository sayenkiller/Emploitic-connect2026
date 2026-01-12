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
