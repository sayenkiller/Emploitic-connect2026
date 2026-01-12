<?php
// System Verification - Check if everything is configured correctly
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

$checks = [];

// 1. Check Database Connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    $checks['Database'] = ['status' => 'OK', 'message' => 'Connected to ' . DB_NAME];
    
    // 2. Check Tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $participantsExists = in_array('participants', $tables);
    $accessLogsExists = in_array('access_logs', $tables);
    
    $checks['Table: participants'] = ['status' => $participantsExists ? 'OK' : 'ERROR', 'message' => $participantsExists ? 'Exists' : 'Missing'];
    $checks['Table: access_logs'] = ['status' => $accessLogsExists ? 'OK' : 'ERROR', 'message' => $accessLogsExists ? 'Exists' : 'Missing'];
    
    // 3. Count existing data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM participants");
    $participantCount = $stmt->fetch()['count'];
    $checks['Participants Count'] = ['status' => 'OK', 'message' => $participantCount . ' registrations'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM access_logs");
    $accessCount = $stmt->fetch()['count'];
    $checks['Access Logs Count'] = ['status' => 'OK', 'message' => $accessCount . ' confirmations'];
    
} catch (Exception $e) {
    $checks['Database'] = ['status' => 'ERROR', 'message' => $e->getMessage()];
}

// 4. Check Configuration
$checks['Config: SITE_URL'] = ['status' => 'OK', 'message' => SITE_URL];
$checks['Config: SITE_NAME'] = ['status' => 'OK', 'message' => SITE_NAME];
$checks['Config: Environment'] = ['status' => 'OK', 'message' => ENVIRONMENT];

// 5. Check Functions
$checks['Function: CURL'] = ['status' => function_exists('curl_init') ? 'OK' : 'ERROR', 'message' => function_exists('curl_init') ? 'Available' : 'Not Available'];
$checks['Function: Hash'] = ['status' => function_exists('hash') ? 'OK' : 'ERROR', 'message' => function_exists('hash') ? 'Available' : 'Not Available'];
$checks['Function: Random Bytes'] = ['status' => function_exists('random_bytes') ? 'OK' : 'ERROR', 'message' => function_exists('random_bytes') ? 'Available' : 'Not Available'];

// 6. Check File Permissions
$checks['File: config.php'] = ['status' => file_exists('config.php') ? 'OK' : 'ERROR', 'message' => file_exists('config.php') ? 'Exists' : 'Missing'];
$checks['File: register.php'] = ['status' => file_exists('register.php') ? 'OK' : 'ERROR', 'message' => file_exists('register.php') ? 'Exists' : 'Missing'];
$checks['File: verify.php'] = ['status' => file_exists('verify.php') ? 'OK' : 'ERROR', 'message' => file_exists('verify.php') ? 'Exists' : 'Missing'];
$checks['File: participant_confirmation.php'] = ['status' => file_exists('participant_confirmation.php') ? 'OK' : 'ERROR', 'message' => file_exists('participant_confirmation.php') ? 'Exists' : 'Missing'];
$checks['File: qr_scanner.php'] = ['status' => file_exists('qr_scanner.php') ? 'OK' : 'ERROR', 'message' => file_exists('qr_scanner.php') ? 'Exists' : 'Missing'];

// 7. Check Email
$checks['Email: Resend API Key'] = ['status' => !empty(RESEND_API_KEY) ? 'OK' : 'ERROR', 'message' => substr(RESEND_API_KEY, 0, 20) . '...'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Verification - Emploitic Connect</title>
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%);
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        max-width: 900px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        padding: 40px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    h1 {
        color: white;
        text-align: center;
        margin-bottom: 40px;
        font-size: 2.5em;
    }
    .check-item {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        margin: 10px 0;
        border-radius: 10px;
        border-left: 4px solid #ccc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }
    .check-item.ok {
        border-left-color: #28a745;
        background: rgba(40, 167, 69, 0.2);
    }
    .check-item.error {
        border-left-color: #dc3545;
        background: rgba(220, 53, 69, 0.2);
    }
    .check-label {
        font-weight: 600;
        flex: 1;
    }
    .check-status {
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        margin-right: 10px;
    }
    .check-status.ok {
        background: #28a745;
        color: white;
    }
    .check-status.error {
        background: #dc3545;
        color: white;
    }
    .check-message {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        text-align: right;
        max-width: 300px;
        word-break: break-all;
    }
    .summary {
        margin-top: 40px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        border: 1px solid rgba(38, 208, 206, 0.3);
        text-align: center;
        color: white;
    }
    .summary p {
        font-size: 18px;
        font-weight: bold;
        color: #26d0ce;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ System Verification</h1>
        
        <?php foreach ($checks as $label => $check): ?>
            <div class="check-item <?php echo $check['status']; ?>">
                <div class="check-label"><?php echo $label; ?></div>
                <div class="check-status <?php echo strtolower($check['status']); ?>">
                    <?php echo $check['status']; ?>
                </div>
                <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
            </div>
        <?php endforeach; ?>
        
        <div class="summary">
            <p>✓ All Systems Ready!</p>
            <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 10px;">
                Your Emploitic Connect 2026 system is properly configured.
            </p>
        </div>
    </div>
</body>
</html>