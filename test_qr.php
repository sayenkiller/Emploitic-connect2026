<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

echo "=== QR CODE DEBUG TEST ===\n\n";

// Test QR code generation
$testId = 1;
$qrCode = Security::generateQRData($testId);
echo "Generated QR code for ID $testId: $qrCode\n\n";

// Decode it to see what it contains
$decoded = json_decode(base64_decode($qrCode), true);
echo "Decoded QR code: " . print_r($decoded, true) . "\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check participants table
    $stmt = $conn->query('SELECT id, nom, prenom, email, is_verified, qr_code FROM participants ORDER BY id DESC LIMIT 5');
    $participants = $stmt->fetchAll();

    echo "=== RECENT PARTICIPANTS ===\n";
    if (empty($participants)) {
        echo "No participants found in database.\n";
    } else {
        foreach ($participants as $p) {
            echo "ID: {$p['id']}, Name: {$p['prenom']} {$p['nom']}, Email: {$p['email']}, Verified: {$p['is_verified']}\n";
            if ($p['qr_code']) {
                echo "  QR Code: " . substr($p['qr_code'], 0, 50) . "...\n";
                // Try to decode the stored QR code
                $decodedStored = json_decode(base64_decode($p['qr_code']), true);
                if ($decodedStored) {
                    echo "  Decoded: " . print_r($decodedStored, true);
                } else {
                    echo "  Could not decode stored QR code\n";
                }
            } else {
                echo "  No QR Code\n";
            }
            echo "\n";
        }
    }

} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>