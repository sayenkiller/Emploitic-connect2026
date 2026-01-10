<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';
require_once 'email.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $hashedToken = Security::hashToken($token);
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Find participant with this token
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, created_at, is_verified
            FROM participants
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$hashedToken]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            $message = 'Lien de vérification invalide ou expiré.';
        } elseif ($participant['is_verified']) {
            $message = 'Votre inscription a déjà été confirmée.';
            $success = true;
            // Get existing QR code for already verified users
            $stmt = $conn->prepare("SELECT qr_code FROM participants WHERE id = ?");
            $stmt->execute([$participant['id']]);
            $qrResult = $stmt->fetch();
            $qrCode = $qrResult ? $qrResult['qr_code'] : null;
            // Generate QR code image URL for existing users
            $qrCodeImageUrl = $qrCode ? "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($qrCode) : null;
        } else {
            // Check if token is expired (24 hours)
            $createdAt = strtotime($participant['created_at']);
            if (time() - $createdAt > TOKEN_EXPIRY) {
                $message = 'Ce lien a expiré. Veuillez vous réinscrire.';
            } else {
                // Generate QR code
                $qrCode = Security::generateQRData($participant['id']);

                // Update participant
                $stmt = $conn->prepare("
                    UPDATE participants
                    SET is_verified = 1, verified_at = NOW(), qr_code = ?
                    WHERE id = ?
                ");
                $stmt->execute([$qrCode, $participant['id']]);

                // Generate QR code image URL
                $qrCodeImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrCode);

                // Send confirmation email with QR code
                $fullName = $participant['prenom'] . ' ' . $participant['nom'];
                EmailService::sendConfirmationEmail($participant['email'], $fullName, $qrCode);

                $message = 'Votre inscription a été confirmée avec succès!';
                $success = true;
                $showQRCode = true;
            }
        }
    } catch (Exception $e) {
        error_log("Verification Error: " . $e->getMessage());
        $message = 'Une erreur est survenue lors de la vérification.';
    }
} else {
    $message = 'Lien de vérification manquant.';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification - Emploitic Connect</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .container {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        padding: 60px 40px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        max-width: 500px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
        background: <?php echo $success ? 'linear-gradient(135deg, #26d0ce, #1a2980)': 'linear-gradient(135deg, #ff6b6b, #c92a2a)';
        ?>;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 50px;
        animation: bounce 0.6s ease;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    h1 {
        color: white;
        margin-bottom: 20px;
        font-size: 2em;
    }

    p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1em;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    .btn {
        display: inline-block;
        padding: 15px 40px;
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(38, 208, 206, 0.3);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(38, 208, 206, 0.5);
    }

    .qr-section {
        margin: 40px 0;
        padding: 30px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        border: 1px solid rgba(38, 208, 206, 0.3);
    }

    .qr-code-display {
        background: rgba(255, 255, 255, 0.9);
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        display: inline-block;
    }

    .qr-code {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        color: #1a2980;
        font-weight: bold;
        letter-spacing: 1px;
        word-break: break-all;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon"><?php echo $success ? '✓' : '✗'; ?></div>
        <h1><?php echo $success ? 'Inscription Confirmée!' : 'Erreur de Vérification'; ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>

        <?php if ($success && isset($qrCode) && isset($qrCodeImageUrl)): ?>
        <div class="qr-section">
            <h2 style="color: #26d0ce; margin-bottom: 20px;">Votre Code QR d'Accès</h2>
            <div class="qr-code-display">
                <img src="<?php echo htmlspecialchars($qrCodeImageUrl); ?>" alt="QR Code"
                    style="max-width: 100%; height: auto;">
            </div>
            <p style="font-size: 0.9em; color: rgba(255, 255, 255, 0.7); margin-top: 15px;">
                Présentez ce code QR à l'entrée du salon<br>
                <small>Conservez-le précieusement ou imprimez cette page</small>
            </p>
        </div>
        <?php endif; ?>

        <a href="<?php echo SITE_URL; ?>" class="btn">Retour à l'accueil</a>
    </div>
</body>

</html>