<?php
// Test page to send real emails for testing
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';
require_once 'email.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? 'Test User';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Generate a test token
        $token = Security::generateToken();

        // Send verification email with real email flag
        $_GET['send_real_email'] = '1'; // Force real email sending
        $emailSent = EmailService::sendVerificationEmail($email, $name, $token);

        if ($emailSent) {
            $message = "✅ Email de vérification envoyé avec succès à $email";
            $success = true;
        } else {
            $message = "❌ Échec de l'envoi de l'email";
        }
    } else {
        $message = "❌ Adresse email invalide";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Envoi Email Réel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🧪 Test Envoi Email Réel</h1>

    <div class="info">
        <strong>Mode Test:</strong> Cette page permet d'envoyer des emails réels pour tester la fonctionnalité.
        <br><br>
        <strong>Instructions:</strong>
        <ul>
            <li>Entrez une adresse email valide</li>
            <li>Cliquez sur "Envoyer Email Test"</li>
            <li>Vérifiez votre boîte de réception</li>
            <li>Cliquez sur le lien de vérification dans l'email</li>
        </ul>
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="email">Adresse Email:</label>
            <input type="email" id="email" name="email" required placeholder="votre.email@example.com">
        </div>

        <div class="form-group">
            <label for="name">Nom (optionnel):</label>
            <input type="text" id="name" name="name" placeholder="Votre Nom">
        </div>

        <button type="submit">📧 Envoyer Email Test</button>
    </form>

    <?php if ($message): ?>
    <div class="message <?php echo $success ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <hr>
    <p><small><strong>Note:</strong> Cette page force l'envoi d'emails réels même en environnement local pour les tests.</small></p>
</body>
</html>
