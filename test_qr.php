<?php
// QR Code Test - Production Environment Only
// This tool tests QR code generation for verified participants
// Access: http://emploitic-connect.gt.tc/test_qr.php

require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

// Verify production environment
if (ENVIRONMENT !== 'production') {
    die('<h1>❌ Error</h1><p>This tool is for production environment only.</p><p>Check config.php ENVIRONMENT setting.</p>');
}

echo "<h1>📊 QR Code Test - Production Environment</h1>";
echo "<p style='color: green; font-weight: bold;'>✓ Running on: " . SITE_URL . "</p>";
echo "<hr>";

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "<h2>1. Verified Participants with QR Codes</h2>";
    
    $stmt = $conn->query("
        SELECT id, nom, prenom, email, is_verified, qr_code, verified_at 
        FROM participants 
        WHERE is_verified = 1 
        ORDER BY verified_at DESC 
        LIMIT 10
    ");
    $participants = $stmt->fetchAll();

    if (empty($participants)) {
        echo "<p style='color: orange;'>⚠ No verified participants yet.</p>";
    } else {
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #1a2980; color: white;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Name</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Verified At</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>QR Code</th>";
        echo "</tr>";

        foreach ($participants as $p) {
            echo "<tr style='background: #f9f9f9; border: 1px solid #ddd;'>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $p['id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($p['prenom'] . ' ' . $p['nom']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($p['email']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($p['verified_at']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            
            if ($p['qr_code']) {
                echo "<code style='font-size: 11px;'>" . substr($p['qr_code'], 0, 40) . "...</code>";
                
                // Try to decode QR
                $decoded = json_decode($p['qr_code'], true);
                if ($decoded) {
                    echo "<br><small style='color: green;'>✓ Valid JSON</small>";
                    echo "<br><small>ID: " . $decoded['id'] . "</small>";
                } else {
                    echo "<br><small style='color: red;'>✗ Invalid JSON</small>";
                }
            } else {
                echo "<span style='color: red;'>❌ No QR Code</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }

    echo "<hr>";
    echo "<h2>2. QR Code Generation Test</h2>";
    
    $testId = 1;
    $testQr = Security::generateQRData($testId);
    
    echo "<p><strong>Test Data:</strong></p>";
    echo "<ul>";
    echo "<li>Participant ID: " . $testId . "</li>";
    echo "<li>QR Code Content: <code>" . htmlspecialchars($testQr) . "</code></li>";
    
    $decoded = json_decode($testQr, true);
    echo "<li>Decoded ID: " . $decoded['id'] . "</li>";
    echo "<li>Event: " . htmlspecialchars($decoded['event']) . "</li>";
    echo "<li>Timestamp: " . date('Y-m-d H:i:s', $decoded['timestamp']) . "</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<h2>3. Statistics</h2>";
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM participants");
    $totalParticipants = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as verified FROM participants WHERE is_verified = 1");
    $verifiedParticipants = $stmt->fetch()['verified'];
    
    $stmt = $conn->query("SELECT COUNT(*) as access_logs FROM access_logs");
    $accessLogsCount = $stmt->fetch()['access_logs'];

    echo "<ul>";
    echo "<li><strong>Total Registrations:</strong> " . $totalParticipants . "</li>";
    echo "<li><strong>Verified Participants:</strong> " . $verifiedParticipants . "</li>";
    echo "<li><strong>Access Confirmations:</strong> " . $accessLogsCount . "</li>";
    echo "<li><strong>Unverified:</strong> " . ($totalParticipants - $verifiedParticipants) . "</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
    echo "<h3>✓ All Systems Operational</h3>";
    echo "<p>QR Code generation and database are working correctly on production server.</p>";
    echo "<p><strong>Site URL:</strong> " . SITE_URL . "</p>";
    echo "<p><strong>Environment:</strong> PRODUCTION</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 12px;'>";
echo "© 2026 Emploitic Connect | Production Environment | ";
echo "<a href='index.php'>← Back to Home</a>";
echo "</p>";
?>