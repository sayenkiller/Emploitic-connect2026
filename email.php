<?php
require_once 'config.php';

class EmailService {
    
    // For local testing - saves email content to files instead of sending
    public static function sendVerificationEmail($email, $name, $token) {
        $verificationLink = SITE_URL . "/verify.php?token=" . urlencode($token);
        
        $subject = "Confirmez votre inscription - " . SITE_NAME;
        $htmlBody = self::getEmailTemplate($name, $verificationLink);
        
        // LOCAL TESTING: Save to file instead of sending
        if (self::isLocalEnvironment()) {
            return self::saveEmailToFile($email, $subject, $htmlBody, $verificationLink);
        }
        
        // PRODUCTION: Actually send email
        return self::sendEmail($email, $subject, $htmlBody);
    }
    
    public static function sendConfirmationEmail($email, $name, $qrCode) {
        $subject = "Votre inscription est confirmée - " . SITE_NAME;
        $htmlBody = self::getConfirmationTemplate($name, $qrCode);
        
        // LOCAL TESTING: Save to file
        if (self::isLocalEnvironment()) {
            return self::saveEmailToFile($email, $subject, $htmlBody, null, $qrCode);
        }
        
        // PRODUCTION: Actually send email
        return self::sendEmail($email, $subject, $htmlBody);
    }
    
    // Check if running on local environment
    private static function isLocalEnvironment() {
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        return in_array($_SERVER['HTTP_HOST'], $localHosts) || 
               strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0;
    }
    
    // Save email content to file for local testing
    private static function saveEmailToFile($email, $subject, $body, $verificationLink = null, $qrCode = null) {
        // Create emails directory if it doesn't exist
        $emailDir = __DIR__ . '/test_emails';
        if (!file_exists($emailDir)) {
            mkdir($emailDir, 0777, true);
        }
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $emailDir . '/' . $timestamp . '_' . preg_replace('/[^a-z0-9]/i', '_', $email) . '.html';
        
        // Create email content with link
        $testContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        .info-box { background: #fff3cd; padding: 20px; margin: 20px 0; border-left: 5px solid #ffc107; }
        .link-box { background: #d4edda; padding: 20px; margin: 20px 0; border-left: 5px solid #28a745; }
        .link { display: inline-block; padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .qr-code { background: #e3f2fd; padding: 20px; margin: 20px 0; border-left: 5px solid #2196f3; font-family: monospace; word-break: break-all; }
    </style>
</head>
<body>
    <div class='info-box'>
        <h2>🧪 MODE TEST LOCAL</h2>
        <p><strong>À:</strong> {$email}</p>
        <p><strong>Sujet:</strong> {$subject}</p>
        <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    </div>";
        
        if ($verificationLink) {
            $testContent .= "
    <div class='link-box'>
        <h3>🔗 Lien de Vérification (Cliquez ici pour tester):</h3>
        <p><a href='{$verificationLink}' class='link' target='_blank'>CLIQUEZ ICI POUR VÉRIFIER</a></p>
        <p style='font-size: 12px; color: #666;'>Ou copiez ce lien: <br><code>{$verificationLink}</code></p>
    </div>";
        }
        
        if ($qrCode) {
            $testContent .= "
    <div class='qr-code'>
        <h3>📱 Votre Code QR:</h3>
        <p><strong>{$qrCode}</strong></p>
    </div>";
        }
        
        $testContent .= "
    <hr>
    <h3>📧 Aperçu de l'email:</h3>
    {$body}
</body>
</html>";
        
        // Save to file
        file_put_contents($filename, $testContent);
        
        // Log success
        error_log("TEST EMAIL saved to: " . $filename);
        
        return true;
    }
    
    // Actual email sending for production
    private static function sendEmail($email, $subject, $htmlBody) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>',
            'Reply-To: ' . SMTP_FROM,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($email, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    private static function getEmailTemplate($name, $verificationLink) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%); padding: 40px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; }
        .content h2 { color: #1a2980; margin-bottom: 20px; }
        .content p { color: #555; line-height: 1.6; margin-bottom: 20px; }
        .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #26d0ce, #1a2980); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
        .footer { background: #f8f8f8; padding: 30px; text-align: center; color: #888; font-size: 14px; }
        .important { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Emploitic Connect 2026</h1>
            <p>Le plus grand salon de l'emploi en Algérie</p>
        </div>
        <div class="content">
            <h2>Bonjour {$name},</h2>
            <p>Merci de vous être inscrit(e) à Emploitic Connect 2026 !</p>
            <p>Pour finaliser votre inscription et recevoir votre code QR d'accès, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :</p>
            <div style="text-align: center;">
                <a href="{$verificationLink}" class="button">Confirmer mon inscription</a>
            </div>
            <div class="important">
                <strong>⚠️ Important :</strong> Ce lien est valable pendant 24 heures.
            </div>
            <p>Si vous n'avez pas demandé cette inscription, vous pouvez ignorer cet email.</p>
            <p><small>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>{$verificationLink}</small></p>
        </div>
        <div class="footer">
            <p>© 2026 Emploitic Connect - Tous droits réservés</p>
            <p>Lot El Yasmine N°1, Draria, 16000 - Alger, Algérie</p>
            <p>recruteur@emploitic.com | +213 560 90 61 16</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    private static function getConfirmationTemplate($name, $qrCode) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%); padding: 40px 20px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 40px 30px; text-align: center; }
        .content h2 { color: #1a2980; margin-bottom: 20px; }
        .content p { color: #555; line-height: 1.6; margin-bottom: 20px; }
        .qr-container { background: #f8f8f8; padding: 30px; margin: 30px 0; border-radius: 10px; }
        .qr-code { font-family: monospace; font-size: 18px; color: #1a2980; background: white; padding: 20px; border-radius: 5px; word-break: break-all; }
        .footer { background: #f8f8f8; padding: 30px; text-align: center; color: #888; font-size: 14px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Inscription Confirmée!</h1>
            <p>Emploitic Connect 2026</p>
        </div>
        <div class="content">
            <div class="success">
                <strong>Félicitations {$name} !</strong><br>
                Votre inscription a été confirmée avec succès.
            </div>
            <h2>Votre Code QR d'Accès</h2>
            <p>Présentez ce code QR à l'entrée du salon :</p>
            <div class="qr-container">
                <div class="qr-code">{$qrCode}</div>
                <p style="margin-top: 20px; font-size: 14px; color: #666;">
                    Conservez ce code précieusement ou imprimez cet email
                </p>
            </div>
            <p><strong>Date de l'événement :</strong> À venir</p>
            <p><strong>Lieu :</strong> Lot El Yasmine N°1, Draria, 16000 - Alger</p>
            <p>Nous avons hâte de vous accueillir à Emploitic Connect 2026 !</p>
        </div>
        <div class="footer">
            <p>© 2026 Emploitic Connect - Tous droits réservés</p>
            <p>recruteur@emploitic.com | +213 560 90 61 16</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>