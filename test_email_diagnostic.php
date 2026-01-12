<?php
// Email Diagnostic Tool with Resend.com
// Tests email configuration and sends test emails via Resend.com API
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';
require_once 'email.php';

$testResults = [];
$emailTest = false;
$testEmail = '';

// Check 1: Resend.com API Key
$testResults['resend_api_key'] = 're_2bHTn6EE_2K9szZPYXq3y8TdTWUZmbRzH';
$testResults['resend_api_url'] = 'https://api.resend.com/emails';

// Check 2: Config Values
$testResults['config_smtp_from'] = SMTP_FROM;
$testResults['config_smtp_from_name'] = SMTP_FROM_NAME;
$testResults['config_site_url'] = SITE_URL;
$testResults['config_site_name'] = SITE_NAME;

// Check 3: CURL Available
$testResults['curl_available'] = function_exists('curl_init');

// Check 4: Database Connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    $testResults['database_connected'] = true;
    
    // Check participants
    $stmt = $conn->query("SELECT COUNT(*) as count FROM participants");
    $result = $stmt->fetch();
    $testResults['participants_count'] = $result['count'];
} catch (Exception $e) {
    $testResults['database_connected'] = false;
    $testResults['database_error'] = $e->getMessage();
}

// Check 5: Test Email Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $token = Security::generateToken();
        $verificationLink = SITE_URL . "/verify.php?token=" . urlencode($token);
        
        $testResults['test_email_address'] = $testEmail;
        $testResults['test_email_token'] = substr($token, 0, 20) . '...';
        $testResults['test_verification_link'] = $verificationLink;
        
        // Try to send verification email
        $emailSent = EmailService::sendVerificationEmail($testEmail, 'Test User', $token);
        $testResults['test_email_sent'] = $emailSent;
        
        if ($emailSent) {
            $testResults['test_result'] = '✓ TEST EMAIL SENT - Check your inbox!';
        } else {
            $testResults['test_result'] = '✗ TEST EMAIL FAILED - Check error.log';
        }
        
        $emailTest = true;
    } else {
        $testResults['test_result'] = '✗ Invalid email address';
    }
}

// Check 6: Error Log
$errorLogFile = __DIR__ . '/error.log';
if (file_exists($errorLogFile)) {
    $errorContent = file_get_contents($errorLogFile);
    $errorLines = array_slice(explode("\n", $errorContent), -10);
    $testResults['error_log_exists'] = true;
    $testResults['recent_errors'] = array_filter($errorLines);
} else {
    $testResults['error_log_exists'] = false;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Diagnostic - Emploitic Connect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #1a2980; margin: 20px 0; }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ccc;
            background: #fafafa;
            border-radius: 5px;
        }
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
            color: #155724;
        }
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
            color: #856404;
        }
        .check-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .check-value {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin: 10px 0;
        }
        button {
            background: linear-gradient(135deg, #26d0ce, #1a2980);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { opacity: 0.9; }
        .error-log {
            background: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            color: #666;
        }
        .test-result {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .test-result.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        .test-result.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        .badge {
            display: inline-block;
            background: #26d0ce;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Diagnostic Tool <span class="badge">Resend.com API</span></h1>
        <p style="color: #666; margin-bottom: 20px;">Diagnoses and tests email configuration with Resend.com integration.</p>

        <div class="card">
            <h2>System Checks</h2>
            
            <!-- Check 1: Resend.com API -->
            <div class="check-item success">
                <div class="check-label">✓ Resend.com API Configuration</div>
                <div class="check-value">
                    API Key: <?php echo substr($testResults['resend_api_key'], 0, 20) . '...'; ?><br>
                    Endpoint: <?php echo $testResults['resend_api_url']; ?>
                </div>
            </div>

            <!-- Check 2: CURL -->
            <div class="check-item <?php echo $testResults['curl_available'] ? 'success' : 'error'; ?>">
                <div class="check-label">
                    <?php echo $testResults['curl_available'] ? '✓' : '✗'; ?> CURL Function
                </div>
                <div class="check-value">
                    <?php echo $testResults['curl_available'] ? 'Available (required for API)' : 'NOT available'; ?>
                </div>
            </div>

            <!-- Check 3: Config -->
            <div class="check-item success">
                <div class="check-label">✓ Configuration</div>
                <div class="check-value">
                    From: <?php echo $testResults['config_smtp_from']; ?><br>
                    From Name: <?php echo $testResults['config_smtp_from_name']; ?><br>
                    Site URL: <?php echo $testResults['config_site_url']; ?>
                </div>
            </div>

            <!-- Check 4: Database -->
            <div class="check-item <?php echo $testResults['database_connected'] ? 'success' : 'error'; ?>">
                <div class="check-label">
                    <?php echo $testResults['database_connected'] ? '✓' : '✗'; ?> Database Connection
                </div>
                <div class="check-value">
                    <?php 
                    if ($testResults['database_connected']) {
                        echo 'Connected<br>Participants: ' . $testResults['participants_count'];
                    } else {
                        echo 'Error: ' . $testResults['database_error'];
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Send Test Email via Resend.com</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Send a test verification email to verify Resend.com API is working correctly.
            </p>
            
            <form method="POST">
                <label for="test_email">Email Address:</label>
                <input type="email" id="test_email" name="test_email" placeholder="your.email@gmail.com" required>
                <button type="submit">📧 Send Test Email via Resend</button>
            </form>

            <?php if ($emailTest): ?>
                <div class="test-result <?php echo $testResults['test_email_sent'] ? 'success' : 'error'; ?>">
                    <?php echo $testResults['test_result']; ?>
                </div>
                
                <?php if ($testResults['test_email_sent']): ?>
                    <div class="check-item success">
                        <div class="check-label">✓ Email Successfully Sent</div>
                        <div class="check-value">
                            To: <?php echo $testResults['test_email_address']; ?><br>
                            Verification Link: <?php echo substr($testResults['test_verification_link'], 0, 60) . '...'; ?><br>
                            <br>
                            <strong>Next Step:</strong> Check your email inbox (and spam folder) for the verification email with subject "Confirmez votre inscription"
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Error Log (Last 10 Entries)</h2>
            <?php if ($testResults['error_log_exists']): ?>
                <div class="error-log">
                    <?php 
                    if (!empty($testResults['recent_errors'])) {
                        foreach ($testResults['recent_errors'] as $error) {
                            if (!empty($error)) {
                                echo htmlspecialchars($error) . "\n";
                            }
                        }
                    } else {
                        echo "No errors logged yet.";
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="check-item warning">
                    <div class="check-label">⚠ No error.log file found</div>
                    <div class="check-value">Error logging will be active after first email send attempt.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="background: #f0f8ff; border-left: 4px solid #0066cc;">
            <h2>About This Tool</h2>
            <ul style="margin: 15px 0; padding-left: 20px;">
                <li><strong>Resend.com API:</strong> Professional email delivery service - no domain needed</li>
                <li><strong>API Key:</strong> <?php echo substr($testResults['resend_api_key'], 0, 20) . '...'; ?> (configured)</li>
                <li><strong>Endpoint:</strong> HTTPS - secure connection</li>
                <li><strong>Free Tier:</strong> Unlimited test emails</li>
                <li><strong>Email Format:</strong> Professional HTML emails</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="index.php" style="display: inline-block; padding: 12px 30px; background: #1a2980; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">← Back to Home</a>
            <a href="test_qr.php" style="display: inline-block; padding: 12px 30px; background: #26d0ce; color: #1a2980; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px;">Database Status →</a>
        </div>
    </div>
</body>
</html>