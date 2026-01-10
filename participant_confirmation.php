<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

$message = '';
$success = false;
$participant = null;
$previewMode = false;

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    // Handle form submission for AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchType = $_POST['search_type'] ?? '';
        $searchValue = trim($_POST['search_value'] ?? '');
        $scannerId = trim($_POST['scanner_id'] ?? '');
        $scannerId = ($scannerId === '' || $scannerId === 'undefined') ? '' : $scannerId;
        $confirmAccess = isset($_POST['confirm_access']);
        $participantId = isset($_POST['participant_id']) ? (int)$_POST['participant_id'] : null;

        try {
            $db = new Database();
            $conn = $db->getConnection();

            $found = false;

            // Handle direct confirmation by participant ID
            if ($confirmAccess && $participantId) {
                // Check if participant exists and is verified
                $stmt = $conn->prepare("SELECT id, nom, prenom, email, telephone, statut, domaine FROM participants WHERE id = ? AND is_verified = 1");
                $stmt->execute([$participantId]);
                $participant = $stmt->fetch();

                if ($participant) {
                    $combined = $participant['prenom'] . ' ' . $participant['nom'];
                    // Check if already confirmed
                    $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                    $stmt->execute([$combined]);
                    $alreadyConfirmed = $stmt->fetch() !== false;

                    if ($alreadyConfirmed) {
                        $message = 'Ce participant a déjà confirmé son accès.';
                    } else {
                        // Determine if this was a QR scan based on search_type
                        $qrScanned = ($searchType === 'qr_code') ? 'YES' : 'NO';

                        // Log the access
                        $stmt = $conn->prepare("
                            INSERT INTO access_logs (participant_id, access_time, qr_scanned, scanner_id)
                            VALUES (?, NOW(), ?, ?)
                        ");
                        $stmt->execute([$combined, $qrScanned, $scannerId]);

                        $message = 'Confirmation réussie! Accès enregistré pour ' . $participant['prenom'] . ' ' . $participant['nom'] . '.';
                        $success = true;
                    }
                } else {
                    $message = 'Participant non trouvé ou non vérifié.';
                }
            } elseif ($searchType === 'qr_code') {
                // Check if the scanned QR code exists in the participants table
                $stmt = $conn->prepare("
                    SELECT id, nom, prenom, email, telephone, statut, domaine, is_verified, qr_code
                    FROM participants
                    WHERE qr_code = ? AND is_verified = 1
                    LIMIT 1
                ");
                $stmt->execute([$searchValue]);
                $participant = $stmt->fetch();
                $found = $participant !== false;

                if (!$found) {
                    $message = 'Participant non trouvé. Ce QR code n\'est pas valide ou n\'appartient à aucun participant vérifié.';
                } else {
                    $combined = $participant['prenom'] . ' ' . $participant['nom'];
                    // Check if already confirmed
                    $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                    $stmt->execute([$combined]);
                    $alreadyConfirmed = $stmt->fetch() !== false;

                    if ($alreadyConfirmed) {
                        $message = 'Ce participant a déjà confirmé son accès.';
                    } else {
                        if ($confirmAccess) {
                            // User confirmed - log the access
                            $stmt = $conn->prepare("
                                INSERT INTO access_logs (participant_id, access_time, qr_scanned, scanner_id)
                                VALUES (?, NOW(), 'YES', ?)
                            ");
                            $stmt->execute([$combined, $scannerId]);

                            $message = 'Confirmation réussie! Accès enregistré pour ' . $participant['prenom'] . ' ' . $participant['nom'] . '.';
                            $success = true;
                        } else {
                            // Preview mode - show participant info for confirmation
                            $message = 'Participant trouvé! Vérifiez les informations ci-dessous et confirmez l\'accès.';
                            $previewMode = true;
                        }
                    }
                }
            } else {
            // Search by name, email, or phone - show preview for confirmation
            $whereClause = '';
            $params = [];

            switch ($searchType) {
                case 'name':
                    $whereClause = "CONCAT(nom, ' ', prenom) LIKE ? OR CONCAT(prenom, ' ', nom) LIKE ?";
                    $params = ["%$searchValue%", "%$searchValue%"];
                    break;
                case 'email':
                    $whereClause = "email LIKE ?";
                    $params = ["%$searchValue%"];
                    break;
                case 'phone':
                    $whereClause = "telephone LIKE ?";
                    $params = ["%$searchValue%"];
                    break;
                default:
                    throw new Exception('Type de recherche invalide');
            }

                $stmt = $conn->prepare("
                    SELECT id, nom, prenom, email, telephone, statut, domaine, is_verified, qr_code
                    FROM participants
                    WHERE $whereClause AND is_verified = 1
                    LIMIT 1
                ");
                $stmt->execute($params);
                $participant = $stmt->fetch();
                $found = $participant !== false;

                if ($found && $participant) {
                    $combined = $participant['prenom'] . ' ' . $participant['nom'];
                    // Check if already confirmed
                    $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                    $stmt->execute([$combined]);
                    $alreadyConfirmed = $stmt->fetch() !== false;

                    if ($alreadyConfirmed) {
                        $message = 'Ce participant a déjà confirmé son accès.';
                    } else {
                        if ($confirmAccess) {
                            // User confirmed - log the access
                            $stmt = $conn->prepare("
                                INSERT INTO access_logs (participant_id, access_time, qr_scanned, scanner_id)
                                VALUES (?, NOW(), 'NO', ?)
                            ");
                            $stmt->execute([$combined, $scannerId]);

                            $message = 'Confirmation réussie! Accès enregistré pour ' . $participant['prenom'] . ' ' . $participant['nom'] . '.';
                            $success = true;
                        } else {
                            // Preview mode - show participant info for confirmation
                            $message = 'Participant trouvé! Vérifiez les informations ci-dessous et confirmez l\'accès.';
                            $previewMode = true;
                        }
                    }
                } else {
                    $message = 'Participant non trouvé ou non vérifié.';
                }
        }
    } catch (Exception $e) {
        error_log("Confirmation Error: " . $e->getMessage());
        $message = 'Une erreur est survenue lors de la confirmation: ' . $e->getMessage();
    }
}

    echo json_encode([
        'message' => $message,
        'success' => $success,
        'previewMode' => $previewMode,
        'participant' => $participant,
        'search_type' => $searchType,
        'scanner_id' => $scannerId
    ]);
    exit;
}

// Handle regular form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchType = $_POST['search_type'] ?? '';
    $searchValue = trim($_POST['search_value'] ?? '');
    $scannerId = trim($_POST['scanner_id'] ?? '');
    $scannerId = $scannerId === '' ? null : $scannerId;
    $confirmAccess = isset($_POST['confirm_access']);

    try {
        $db = new Database();
        $conn = $db->getConnection();

        $found = false;

        if ($searchType === 'qr_code') {
            // Check if the scanned QR code exists in the participants table
            $stmt = $conn->prepare("
                SELECT id, nom, prenom, email, telephone, statut, domaine, is_verified, qr_code
                FROM participants
                WHERE qr_code = ? AND is_verified = 1
                LIMIT 1
            ");
            $stmt->execute([$searchValue]);
            $participant = $stmt->fetch();
            $found = $participant !== false;

            if (!$found) {
                $message = 'Participant non trouvé. Ce QR code n\'est pas valide ou n\'appartient à aucun participant vérifié.';
            } else {
                $combined = $participant['prenom'] . ' ' . $participant['nom'];
                // Check if already confirmed
                $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                $stmt->execute([$combined]);
                $alreadyConfirmed = $stmt->fetch() !== false;

                if ($alreadyConfirmed) {
                    $message = 'Ce participant a déjà confirmé son accès.';
                } else {
                    if ($confirmAccess) {
                        // User confirmed - log the access
                        $stmt = $conn->prepare("
                            INSERT INTO access_logs (participant_id, access_time, qr_scanned, scanner_id)
                            VALUES (?, NOW(), 'YES', ?)
                        ");
                        $stmt->execute([$combined, $scannerId]);

                        $message = 'Confirmation réussie! Accès enregistré pour ' . $participant['prenom'] . ' ' . $participant['nom'] . '.';
                        $success = true;
                    } else {
                        // Preview mode - show participant info for confirmation
                        $message = 'Participant trouvé! Vérifiez les informations ci-dessous et confirmez l\'accès.';
                        $previewMode = true;
                    }
                }
            }
        } else {
            // Search by name, email, or phone - show preview for confirmation
            $whereClause = '';
            $params = [];

                switch ($searchType) {
                    case 'name':
                        $whereClause = "CONCAT(nom, ' ', prenom) LIKE ? OR CONCAT(prenom, ' ', nom) LIKE ?";
                        $params = ["%$searchValue%", "%$searchValue%"];
                        break;
                    case 'email':
                        $whereClause = "email LIKE ?";
                        $params = ["%$searchValue%"];
                        break;
                    case 'phone':
                        $whereClause = "telephone LIKE ?";
                        $params = ["%$searchValue%"];
                        break;
                    default:
                        throw new Exception('Type de recherche invalide');
                }

            $stmt = $conn->prepare("
                SELECT id, nom, prenom, email, telephone, statut, domaine, is_verified, qr_code
                FROM participants
                WHERE $whereClause AND is_verified = 1
                LIMIT 1
            ");
            $stmt->execute($params);
            $participant = $stmt->fetch();
            $found = $participant !== false;

            if ($found && $participant) {
                $combined = $participant['prenom'] . ' ' . $participant['nom'];
                // Check if already confirmed
                $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                $stmt->execute([$combined]);
                $alreadyConfirmed = $stmt->fetch() !== false;

                if ($alreadyConfirmed) {
                    $message = 'Ce participant a déjà confirmé son accès.';
                } else {
                        if ($confirmAccess) {
                            // User confirmed - log the access
                            $stmt = $conn->prepare("
                                INSERT INTO access_logs (participant_id, access_time, qr_scanned, scanner_id)
                                VALUES (?, NOW(), 'NO', ?)
                            ");
                            $stmt->execute([$combined, $scannerId]);

                            $message = 'Confirmation réussie! Accès enregistré pour ' . $participant['prenom'] . ' ' . $participant['nom'] . '.';
                            $success = true;
                        } else {
                        // Preview mode - show participant info for confirmation
                        $message = 'Participant trouvé! Vérifiez les informations ci-dessous et confirmez l\'accès.';
                        $previewMode = true;
                    }
                }
            } else {
                $message = 'Participant non trouvé ou non vérifié.';
            }
        }

    } catch (Exception $e) {
        error_log("Confirmation Error: " . $e->getMessage());
        $message = 'Une erreur est survenue lors de la confirmation.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation des Participants - Emploitic Connect</title>
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
        padding: 20px;
    }

    .container {
        max-width: 800px;
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
        margin-bottom: 30px;
        font-size: 2.5em;
    }

    .search-section {
        background: rgba(255, 255, 255, 0.05);
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }

    .search-tabs {
        display: flex;
        margin-bottom: 20px;
        border-radius: 10px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.1);
    }

    .tab {
        flex: 1;
        padding: 15px;
        border: none;
        background: transparent;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab.active {
        background: #26d0ce;
        color: #1a2980;
        font-weight: bold;
    }

    .search-form {
        display: none;
    }

    .search-form.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: white;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 15px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.9);
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #26d0ce;
        outline: none;
        box-shadow: 0 0 0 3px rgba(38, 208, 206, 0.3);
    }

    .btn {
        display: inline-block;
        padding: 15px 40px;
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        color: white;
        text-decoration: none;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(38, 208, 206, 0.3);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(38, 208, 206, 0.5);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .result-section {
        margin-top: 30px;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
    }

    .result-section.success {
        background: rgba(40, 167, 69, 0.2);
        border: 2px solid #28a745;
    }

    .result-section.error {
        background: rgba(220, 53, 69, 0.2);
        border: 2px solid #dc3545;
    }

    .result-section h2 {
        color: white;
        margin-bottom: 20px;
        font-size: 1.8em;
    }

    .result-section p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1em;
        margin-bottom: 20px;
    }

    .participant-info {
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        text-align: left;
    }

    .participant-info h3 {
        color: #26d0ce;
        margin-bottom: 15px;
    }

    .participant-info p {
        color: white;
        margin-bottom: 8px;
    }

    .qr-scanner {
        text-align: center;
        margin-top: 20px;
    }

    .qr-scanner video {
        width: 100%;
        max-width: 400px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .scanner-instructions {
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
        }

        h1 {
            font-size: 2em;
        }

        .search-tabs {
            flex-direction: column;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Confirmation des Participants</h1>

        <div class="search-section">
            <div class="search-tabs">
                <button class="tab active" onclick="switchTab('manual')">Recherche Manuelle</button>
                <button class="tab" onclick="switchTab('qr')">Scanner QR Code</button>
            </div>

            <!-- Manual Search Form -->
            <form id="manual-form" class="search-form active" onsubmit="handleManualSearch(event)">
                <input type="hidden" name="search_type" id="search_type" value="name">

                <div class="form-group">
                    <label for="search_method">Méthode de recherche:</label>
                    <select id="search_method" onchange="updateSearchType()">
                        <option value="name">Nom et Prénom</option>
                        <option value="email">Adresse Email</option>
                        <option value="phone">Numéro de Téléphone</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search_value">Valeur de recherche:</label>
                    <input type="text" id="search_value" name="search_value" required
                        placeholder="Entrez le nom, email ou téléphone">
                </div>

                <div class="form-group">
                    <label for="scanner_id">ID de l'agent de reception:</label>
                    <input type="text" id="scanner_id" name="scanner_id" value="" placeholder="Agent de Réception">
                </div>

                <button type="submit" class="btn">Rechercher & Confirmer</button>
            </form>

            <!-- QR Scanner Form -->
            <form id="qr-form" class="search-form" method="POST">
                <input type="hidden" name="search_type" value="qr_code">
                <input type="hidden" id="qr_search_value" name="search_value" value="">

                <div class="qr-scanner">
                    <div class="scanner-instructions">
                        Positionnez le QR code devant la caméra pour scanner automatiquement
                    </div>
                    <video id="qr-video" autoplay playsinline></video>
                    <div id="qr-result"></div>
                </div>

                <div class="form-group">
                    <label for="qr_scanner_id">ID de l'agent de reception:</label>
                    <input type="text" id="qr_scanner_id" name="scanner_id" value="" placeholder="Agent de Réception">
                </div>

                <button type="submit" class="btn" id="qr-submit-btn" disabled>Confirmer le Scan</button>
            </form>
        </div>

        <?php if (!empty($message)): ?>
        <div class="result-section <?php echo $success ? 'success' : ($previewMode ? 'preview' : 'error'); ?>">
            <h2><?php echo $success ? '✓ Confirmation Réussie' : ($previewMode ? '👤 Participant Trouvé' : '✗ Erreur'); ?>
            </h2>
            <p><?php echo htmlspecialchars($message); ?></p>

            <?php if (($success || $previewMode) && $participant): ?>
            <div class="participant-info">
                <h3>Informations du Participant</h3>
                <p><strong>Nom:</strong>
                    <?php echo htmlspecialchars($participant['prenom'] . ' ' . $participant['nom']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($participant['email']); ?></p>
                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($participant['telephone']); ?></p>
                <p><strong>Statut:</strong> <?php echo htmlspecialchars($participant['statut']); ?></p>
                <?php if (!empty($participant['domaine'])): ?>
                <p><strong>Domaine:</strong> <?php echo htmlspecialchars($participant['domaine']); ?></p>
                <?php endif; ?>

                <?php if ($previewMode): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="search_type" value="<?php echo htmlspecialchars($searchType); ?>">
                        <input type="hidden" name="search_value" value="<?php echo htmlspecialchars($searchValue); ?>">
                        <input type="hidden" name="scanner_id" value="<?php echo htmlspecialchars($scannerId); ?>">
                        <input type="hidden" name="confirm_access" value="1">
                        <button type="submit" class="btn"
                            style="background: linear-gradient(135deg, #28a745, #20c997);">
                            ✓ Confirmer l'Accès
                        </button>
                    </form>
                    <button onclick="resetForm()" class="btn"
                        style="background: linear-gradient(135deg, #dc3545, #fd7e14); margin-left: 10px;">
                        ✗ Annuler
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <script>
        setTimeout(function() {
            window.location.reload();
        }, 5000);
        </script>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script>
    let codeReader;
    let isScanning = false;

    function switchTab(tab) {
        // Update tabs
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');

        // Update forms
        document.querySelectorAll('.search-form').forEach(f => f.classList.remove('active'));
        document.getElementById(tab + '-form').classList.add('active');

        // Start/stop QR scanner
        if (tab === 'qr') {
            startQRScanner();
        } else {
            stopQRScanner();
        }
    }

    function updateSearchType() {
        const method = document.getElementById('search_method').value;
        document.getElementById('search_type').value = method;

        const input = document.getElementById('search_value');
        switch (method) {
            case 'name':
                input.placeholder = 'Entrez le nom et prénom';
                break;
            case 'email':
                input.placeholder = 'Entrez l\'adresse email';
                break;
            case 'phone':
                input.placeholder = 'Entrez le numéro de téléphone';
                break;
        }
    }

    async function startQRScanner() {
        try {
            codeReader = new ZXing.BrowserMultiFormatReader();
            const videoInputDevices = await codeReader.listVideoInputDevices();

            if (videoInputDevices.length === 0) {
                alert('Aucune caméra trouvée. Veuillez utiliser la recherche manuelle.');
                return;
            }

            const selectedDeviceId = videoInputDevices[0].deviceId;
            isScanning = true;

            codeReader.decodeFromVideoDevice(selectedDeviceId, 'qr-video', (result, err) => {
                if (result) {
                    const qrCode = result.text;
                    document.getElementById('qr-result').innerHTML =
                        '<p style="color: #28a745; font-weight: bold;">QR Code détecté! Vérification en cours...</p>';

                    // Send AJAX request to check QR code
                    fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: new URLSearchParams({
                                'search_type': 'qr_code',
                                'search_value': qrCode,
                                'scanner_id': document.getElementById('qr_scanner_id').value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            displayResult(data);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('qr-result').innerHTML =
                                '<p style="color: #dc3545; font-weight: bold;">Erreur lors de la vérification du QR code.</p>';
                        });
                }
                if (err && !(err instanceof ZXing.NotFoundException)) {
                    console.error(err);
                }
            });
        } catch (err) {
            console.error('Erreur lors du démarrage du scanner:', err);
            alert('Erreur lors du démarrage du scanner QR. Veuillez utiliser la recherche manuelle.');
        }
    }

    function stopQRScanner() {
        if (codeReader && isScanning) {
            codeReader.reset();
            isScanning = false;
        }
        document.getElementById('qr-result').innerHTML = '';
        document.getElementById('qr-submit-btn').disabled = true;
    }

    function displayResult(data) {
        let resultHtml = '';

        if (data.success) {
            let participantInfoHtml = '';
            if (data.participant && data.search_type === 'qr_code') {
                let domaineHtml = data.participant.domaine ?
                    `<p><strong>Domaine:</strong> ${data.participant.domaine}</p>` : '';
                participantInfoHtml = `
                    <div class="participant-info">
                        <h3>Informations du Participant</h3>
                        <p><strong>Nom:</strong> ${data.participant.prenom} ${data.participant.nom}</p>
                        <p><strong>Email:</strong> ${data.participant.email}</p>
                        <p><strong>Téléphone:</strong> ${data.participant.telephone}</p>
                        <p><strong>Statut:</strong> ${data.participant.statut}</p>
                        ${domaineHtml}
                    </div>
                `;
            }
            resultHtml = `
                <div class="result-section success">
                    <h2>✓ Confirmation Réussie</h2>
                    <p>${data.message}</p>
                    ${participantInfoHtml}
                </div>
            `;
        } else if (data.previewMode) {
            let domaineHtml = data.participant && data.participant.domaine ?
                `<p><strong>Domaine:</strong> ${data.participant.domaine}</p>` : '';
            resultHtml = `
                <div class="result-section preview">
                    <h2>👤 Participant Trouvé</h2>
                    <p>${data.message}</p>
                    ${data.participant ? `
                        <div class="participant-info">
                            <h3>Informations du Participant</h3>
                            <p><strong>Nom:</strong> ${data.participant.prenom} ${data.participant.nom}</p>
                            <p><strong>Email:</strong> ${data.participant.email}</p>
                            <p><strong>Téléphone:</strong> ${data.participant.telephone}</p>
                            <p><strong>Statut:</strong> ${data.participant.statut}</p>
                            ${domaineHtml}
                            <div style="margin-top: 20px; text-align: center;">
                                <button onclick="confirmAccess('${data.participant.id}', '${data.search_type}')" class="btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                    ✓ Confirmer l'Accès
                                </button>
                                <button onclick="resetForm()" class="btn" style="background: linear-gradient(135deg, #dc3545, #fd7e14); margin-left: 10px;">
                                    ✗ Annuler
                                </button>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            resultHtml = `
                <div class="result-section error">
                    <h2>✗ Erreur</h2>
                    <p>${data.message}</p>
                </div>
            `;
        }

        // Remove existing result section if present
        const existingResult = document.querySelector('.result-section');
        if (existingResult) {
            existingResult.remove();
        }

        // Add new result section
        const container = document.querySelector('.container');
        container.insertAdjacentHTML('beforeend', resultHtml);

        // Clear QR result message
        document.getElementById('qr-result').innerHTML = '';

        // If success, reset forms immediately to avoid reload warning, and remove result after 5 seconds
        if (data.success) {
            document.getElementById('manual-form').reset();
            document.getElementById('qr-form').reset();
            document.getElementById('qr_search_value').value = '';
            document.getElementById('qr-submit-btn').disabled = true;
            setTimeout(function() {
                const resultSection = document.querySelector('.result-section');
                if (resultSection) {
                    resultSection.remove();
                }
            }, 5000);
        }
    }


    function handleManualSearch(event) {
        event.preventDefault();

        const formData = new FormData(event.target);
        const searchType = formData.get('search_type');
        const searchValue = formData.get('search_value');
        const scannerId = formData.get('scanner_id');

        // Send AJAX request
        fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'search_type': searchType,
                    'search_value': searchValue,
                    'scanner_id': scannerId
                })
            })
            .then(response => response.json())
            .then(data => {
                displayResult(data);
            })
            .catch(error => {
                console.error('Error:', error);
                displayResult({
                    success: false,
                    message: 'Erreur lors de la recherche.'
                });
            });
    }

    function confirmAccess(participantId, searchType) {
        // Get scanner_id from the current active form
        let scannerId = '';
        const activeForm = document.querySelector('.search-form.active');
        if (activeForm.id === 'manual-form') {
            scannerId = document.getElementById('scanner_id').value;
        } else if (activeForm.id === 'qr-form') {
            scannerId = document.getElementById('qr_scanner_id').value;
        }

        // Send AJAX request to confirm access
        fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'participant_id': participantId,
                    'search_type': searchType,
                    'scanner_id': scannerId,
                    'confirm_access': '1'
                })
            })
            .then(response => response.json())
            .then(data => {
                displayResult(data);
            })
            .catch(error => {
                console.error('Error:', error);
                displayResult({
                    success: false,
                    message: 'Erreur lors de la confirmation de l\'accès.'
                });
            });
    }

    function resetForm() {
        // Clear all forms and results
        document.getElementById('manual-form').reset();
        document.getElementById('qr-form').reset();
        document.getElementById('qr_search_value').value = '';
        document.getElementById('qr-result').innerHTML = '';
        document.getElementById('qr-submit-btn').disabled = true;

        // Remove result section
        const resultSection = document.querySelector('.result-section');
        if (resultSection) {
            resultSection.remove();
        }

        // Reset to manual search tab
        switchTab('manual');
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateSearchType();
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        stopQRScanner();
    });
    </script>
</body>

</html>