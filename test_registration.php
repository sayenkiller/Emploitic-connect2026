<?php
// test_registration.php - Use this to test the registration system
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Registration System</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f4f4f4;
    }

    .test-box {
        background: white;
        padding: 20px;
        margin: 20px 0;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .success {
        color: green;
    }

    .error {
        color: red;
    }

    .info {
        background: #e3f2fd;
        padding: 15px;
        margin: 10px 0;
        border-left: 4px solid #2196f3;
    }

    h2 {
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    table td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }

    table td:first-child {
        font-weight: bold;
        width: 200px;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        margin: 5px;
    }
    </style>
</head>

<body>
    <h1>🧪 Test du Système d'Inscription</h1>

    <div class="test-box">
        <h2>1. Test de la connexion à la base de données</h2>
        <?php
        try {
            $db = new Database();
            $conn = $db->getConnection();
            echo "<p class='success'>✓ Connexion réussie à la base de données!</p>";
            
            // Check if table exists
            $stmt = $conn->query("SHOW TABLES LIKE 'participants'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ La table 'participants' existe!</p>";
                
                // Count participants
                $stmt = $conn->query("SELECT COUNT(*) as count FROM participants");
                $count = $stmt->fetch()['count'];
                echo "<p class='info'>📊 Nombre de participants enregistrés: <strong>{$count}</strong></p>";
            } else {
                echo "<p class='error'>✗ La table 'participants' n'existe pas!</p>";
                echo "<p>Exécutez le script SQL pour créer la table.</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erreur: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="test-box">
        <h2>2. Configuration</h2>
        <table>
            <tr>
                <td>Base de données:</td>
                <td><?php echo DB_NAME; ?></td>
            </tr>
            <tr>
                <td>Hôte:</td>
                <td><?php echo DB_HOST; ?></td>
            </tr>
            <tr>
                <td>Site URL:</td>
                <td><?php echo SITE_URL; ?></td>
            </tr>
            <tr>
                <td>Environnement:</td>
                <td><?php 
                    $isLocal = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || 
                               strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0;
                    echo $isLocal ? '<span class="info">🏠 LOCAL (mode test activé)</span>' : '<span class="success">🌐 PRODUCTION</span>';
                ?></td>
            </tr>
        </table>
    </div>

    <div class="test-box">
        <h2>3. Test de sécurité</h2>
        <?php
        // Test token generation
        $token = Security::generateToken();
        echo "<p class='success'>✓ Génération de token: <code>{$token}</code></p>";
        
        // Test email validation
        $testEmail = "test@example.com";
        $isValid = Security::validateEmail($testEmail);
        echo "<p class='success'>✓ Validation email ({$testEmail}): " . ($isValid ? "Valide" : "Invalide") . "</p>";
        
        // Test phone validation
        $testPhone = "+213555123456";
        $isValid = Security::validatePhone($testPhone);
        echo "<p class='success'>✓ Validation téléphone ({$testPhone}): " . ($isValid ? "Valide" : "Invalide") . "</p>";
        ?>
    </div>

    <div class="test-box">
        <h2>4. Dernières inscriptions</h2>
        <?php
        try {
            $stmt = $conn->query("
                SELECT id, nom, prenom, email, statut, is_verified, created_at 
                FROM participants 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $participants = $stmt->fetchAll();
            
            if (count($participants) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Statut</th><th>Vérifié</th><th>Date</th></tr>";
                foreach ($participants as $p) {
                    $verified = $p['is_verified'] ? '✓' : '✗';
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>{$p['prenom']} {$p['nom']}</td>";
                    echo "<td>{$p['email']}</td>";
                    echo "<td>{$p['statut']}</td>";
                    echo "<td>{$verified}</td>";
                    echo "<td>{$p['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='info'>Aucune inscription pour le moment.</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="test-box">
        <h2>5. Emails de test activation (Mode Local)</h2>
        <?php
        $emailDir = __DIR__ . '/test_emails';
        if (file_exists($emailDir)) {
            $files = glob($emailDir . '/*.html');
            if (count($files) > 0) {
                echo "<p class='success'>✓ Trouvé " . count($files) . " email(s) de test</p>";
                echo "<ul>";
                foreach (array_slice($files, -5) as $file) {
                    $filename = basename($file);
                    echo "<li><a href='test_emails/{$filename}' target='_blank' class='btn'>{$filename}</a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='info'>Aucun email de test. Faites une inscription pour en générer.</p>";
            }
        } else {
            echo "<p class='info'>Le dossier test_emails sera créé lors de la première inscription.</p>";
        }
        ?>
    </div>

    <div class="test-box">
        <h2>6. Actions</h2>
        <a href="index.php" class="btn">📝 Aller au formulaire d'inscription</a>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">🔄 Rafraîchir</a>
    </div>
</body>

</html>