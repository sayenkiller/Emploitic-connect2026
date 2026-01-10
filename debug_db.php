<?php
require_once 'config.php';
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check participants table
    $stmt = $conn->query('SELECT id, nom, prenom, email, is_verified, qr_code FROM participants LIMIT 10');
    $participants = $stmt->fetchAll();

    echo "=== PARTICIPANTS IN DATABASE ===\n";
    if (empty($participants)) {
        echo "No participants found in database.\n";
    } else {
        foreach ($participants as $p) {
            echo "ID: {$p['id']}, Name: {$p['prenom']} {$p['nom']}, Email: {$p['email']}, Verified: {$p['is_verified']}, QR: " . substr($p['qr_code'] ?? 'NULL', 0, 50) . "...\n";
        }
    }

    // Check access_logs table
    $stmt = $conn->query('SELECT COUNT(*) as count FROM access_logs');
    $count = $stmt->fetch();
    echo "\n=== ACCESS LOGS ===\n";
    echo "Total access logs: {$count['count']}\n";

    // Check recent access logs
    if ($count['count'] > 0) {
        $stmt = $conn->query('SELECT * FROM access_logs ORDER BY access_time DESC LIMIT 5');
        $logs = $stmt->fetchAll();
        echo "\nRecent access logs:\n";
        foreach ($logs as $log) {
            echo "ID: {$log['id']}, Participant: {$log['participant_id']}, Time: {$log['access_time']}, QR Scanned: {$log['qr_scanned']}, Scanner: {$log['scanner_id']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>