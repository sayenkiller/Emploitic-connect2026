<?php
// Email Service - Resend.com API
// Production version - reliable email delivery

require_once 'config.php';

class EmailService {

    private static $resendApiKey = 're_2bHTn6EE_2K9szZPYXq3y8TdTWUZmbRzH';
    private static $resendApiUrl = 'https://api.resend.com/emails';
    private static $resendFromEmail = 'onboarding@resend.dev';
    private static $resendFromName = 'Emploitic Connect 2026';

    /**
     * Send verification email with confirmation link
     */
    public static function sendVerificationEmail($email, $name, $token) {
        $verificationLink = SITE_URL . "/verify.php?token=" . urlencode($token);
        $subject = "Confirmez votre inscription - " . SITE_NAME;
        $htmlBody = self::getVerificationTemplate($name, $verificationLink);
        
        return self::sendViaResend($email, $subject, $htmlBody);
    }

    /**
     * Send confirmation email with QR code
     */
    public static function sendConfirmationEmail($email, $name, $qrCode) {
        $subject = "Votre inscription est confirmée - " . SITE_NAME;
        $htmlBody = self::getConfirmationTemplate($name, $qrCode);
        
        return self::sendViaResend($email, $subject, $htmlBody);
    }

    /**
     * Send email via Resend.com API
     * Returns true if successful, false otherwise
     */
    private static function sendViaResend($to, $subject, $htmlBody) {
        try {
            $payload = array(
                "from" => self::$resendFromName . " <" . self::$resendFromEmail . ">",
                "to" => $to,
                "subject" => $subject,
                "html" => $htmlBody,
                "reply_to" => SMTP_FROM
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$resendApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::$resendApiKey
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("[" . date('Y-m-d H:i:s') . "] Resend cURL Error: " . $error);
                return false;
            }

            $result = json_decode($response, true);

            // Success = HTTP 200 and email ID returned
            if ($httpCode === 200 && isset($result['id'])) {
                error_log("[" . date('Y-m-d H:i:s') . "] ✓ Email sent to: " . $to . " (ID: " . $result['id'] . ")");
                return true;
            } else {
                error_log("[" . date('Y-m-d H:i:s') . "] ✗ Resend failed: HTTP " . $httpCode . " - " . $response);
                return false;
            }

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Email Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verification email template
     */
    private static function getVerificationTemplate($name, $verificationLink) {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f4f4f4; 
            line-height: 1.6;
        }
        .container { 
            max-width: 600px; 
            margin: 40px auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%); 
            padding: 40px 30px; 
            text-align: center; 
            color: white; 
        }
        .header h1 { 
            font-size: 28px; 
            margin-bottom: 10px; 
            margin-top: 0;
        }
        .content { 
            padding: 40px 30px; 
            color: #333; 
            line-height: 1.6; 
        }
        .button { 
            display: inline-block; 
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%); 
            color: white !important; 
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
            margin: 20px 0; 
        }
        .footer { 
            background: #f8f8f8; 
            padding: 30px; 
            text-align: center; 
            color: #888; 
            font-size: 14px; 
            border-top: 1px solid #eee;
        }
        .link-text {
            word-break: break-all;
            color: #666;
            font-size: 12px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue à Emploitic Connect 2026</h1>
            <p>Confirmez votre inscription</p>
        </div>
        
        <div class="content">
            <p>Bonjour <strong>$name</strong>,</p>
            
            <p>Merci pour votre inscription à <strong>Emploitic Connect 2026</strong>!</p>
            
            <p>Pour confirmer votre participation et recevoir votre code QR d'accès, cliquez sur le bouton ci-dessous:</p>
            
            <center>
                <a href="$verificationLink" class="button">Confirmer mon inscription</a>
            </center>
            
            <p><strong>Si le bouton ne fonctionne pas:</strong></p>
            <div class="link-text">$verificationLink</div>
            
            <p><strong>Important:</strong> Ce lien est valable 24 heures.</p>
            
            <p>Nous avons hâte de vous accueillir!</p>
        </div>
        
        <div class="footer">
            <p><strong>Emploitic Connect 2026</strong></p>
            <p>Le plus grand salon de l'emploi en Algérie</p>
            <p>recruteur@emploitic.com | +213 560 90 61 16</p>
            <p style="margin-top: 15px; font-size: 12px;">© 2026 Emploitic Connect</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Confirmation email template with QR code
     */
    private static function getConfirmationTemplate($name, $qrCode) {
        // Generate QR code image URL
        $qrCodeImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrCode);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f4f4f4; 
            line-height: 1.6;
        }
        .container { 
            max-width: 600px; 
            margin: 40px auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%); 
            padding: 40px 30px; 
            text-align: center; 
            color: white; 
        }
        .header h1 { 
            font-size: 28px; 
            margin-bottom: 10px; 
            margin-top: 0;
        }
        .content { 
            padding: 40px 30px; 
            color: #333; 
            line-height: 1.6; 
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
            border-radius: 5px;
        }
        .qr-container { 
            background: #f8f8f8; 
            padding: 30px; 
            margin: 30px 0; 
            border-radius: 10px;
            text-align: center;
        }
        .qr-code { 
            font-family: 'Courier New', monospace; 
            font-size: 11px; 
            color: #1a2980; 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            word-break: break-all;
            border: 2px dashed #26d0ce;
            max-height: 300px;
            overflow: auto;
        }
        .footer { 
            background: #f8f8f8; 
            padding: 30px; 
            text-align: center; 
            color: #888; 
            font-size: 14px; 
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Inscription Confirmée!</h1>
            <p>Emploitic Connect 2026</p>
        </div>
        
        <div class="content">
            <div class="success-box">
                <strong>Félicitations $name!</strong><br>
                Votre inscription a été confirmée avec succès.
            </div>
            
            <h2 style="color: #1a2980;">Votre Code QR d'Accès</h2>
            <p>Présentez ce code QR à l'entrée du salon:</p>
            
            <div class="qr-container">
                <img src="$qrCodeImageUrl" alt="QR Code" style="max-width: 200px; height: auto;">
                <div class="qr-code" style="margin-top: 20px;">$qrCode</div>
                <p style="margin-top: 20px; font-size: 12px; color: #666;">
                    Conservez ce code ou imprimez cet email
                </p>
            </div>
            
            <h3 style="color: #1a2980;">Informations Pratiques</h3>
            <p>
                <strong>Lieu:</strong> Lot El Yasmine N°1, Draria, 16000 - Alger<br>
                <strong>Contact:</strong> +213 560 90 61 16<br>
                <strong>Email:</strong> recruteur@emploitic.com
            </p>
            
            <p>Nous avons hâte de vous accueillir à Emploitic Connect 2026!</p>
        </div>
        
        <div class="footer">
            <p><strong>Emploitic Connect 2026</strong></p>
            <p>Le plus grand salon de l'emploi en Algérie</p>
            <p>recruteur@emploitic.com | +213 560 90 61 16</p>
            <p style="margin-top: 15px; font-size: 12px;">© 2026 Emploitic Connect</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>