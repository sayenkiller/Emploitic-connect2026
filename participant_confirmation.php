<?php
// Participant Confirmation & Access Logging
// Works on InfinityFree production server
// Displays participants and logs access to access_logs table

require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

$message = '';
$success = false;
$participant = null;
$previewMode = false;
$searchType = '';
$searchValue = '';
$scannerId = '';
$participantList = [];

// Handle AJAX requests for live access confirmation
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchType = $_POST['search_type'] ?? '';
        $searchValue = trim($_POST['search_value'] ?? '');
        $scannerId = trim($_POST['scanner_id'] ?? '');
        $scannerId = ($scannerId === '' || $scannerId === 'undefined') ? 'Scanner' : $scannerId;
        $confirmAccess = isset($_POST['confirm_access']);
        $participantId = isset($_POST['participant_id']) ? (int)$_POST['participant_id'] : null;

        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Direct confirmation by participant ID
            if ($confirmAccess && $participantId) {
                $stmt = $conn->prepare("SELECT id, nom, prenom, email, telephone, statut, domaine FROM participants WHERE id = ? AND is_verified = 1");
                $stmt->execute([$participantId]);
                $participant = $stmt->fetch();

                if ($participant) {
                    $participantIdVal = $participant['id'];
                    $nom = $participant['nom'];
                    $prenom = $participant['prenom'];
                    
                    // Check if already logged in access_logs
                    $stmt = $conn->prepare("SELECT id FROM access_logs WHERE participant_id = ? LIMIT 1");
                    $stmt->execute([$participantIdVal]);
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Accès déjà confirmé pour ce participant.']);
                        exit;
                    }

                    // Log access with nom and prenom
                    $stmt = $conn->prepare("INSERT INTO access_logs (participant_id, nom, prenom, scanner_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$participantIdVal, $nom, $prenom, $scannerId]);

                    echo json_encode(['success' => true, 'message' => 'Accès confirmé avec succès pour ' . htmlspecialchars($prenom . ' ' . $nom) . '!']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Participant non trouvé ou non vérifié.']);
                    exit;
                }
            }

            // Search logic
            if (!in_array($searchType, ['name', 'email', 'qr_code'])) {
                echo json_encode(['success' => false, 'message' => 'Type de recherche invalide.']);
                exit;
            }

            $sql = "SELECT id, nom, prenom, email, telephone, statut, domaine, qr_code, is_verified FROM participants WHERE ";
            $params = [];

            switch ($searchType) {
                case 'name':
                    $names = explode(' ', $searchValue);
                    $nameConditions = [];
                    foreach ($names as $name) {
                        $name = "%" . $name . "%";
                        $nameConditions[] = "(nom LIKE ? OR prenom LIKE ?)";
                        $params[] = $name;
                        $params[] = $name;
                    }
                    $sql .= implode(' AND ', $nameConditions);
                    break;

                case 'email':
                    $sql .= "email = ?";
                    $params[] = $searchValue;
                    break;

                case 'qr_code':
                    $sql .= "qr_code = ?";
                    $params[] = $searchValue;
                    break;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $participants = $stmt->fetchAll();

            if (empty($participants)) {
                echo json_encode(['success' => false, 'message' => 'Aucun participant trouvé.']);
                exit;
            }

            $participantList = [];
            foreach ($participants as $p) {
                $participantList[] = [
                    'id' => $p['id'],
                    'nom' => htmlspecialchars($p['nom']),
                    'prenom' => htmlspecialchars($p['prenom']),
                    'email' => maskEmail($p['email']),
                    'telephone' => maskPhone($p['telephone']),
                    'statut' => htmlspecialchars($p['statut']),
                    'domaine' => htmlspecialchars($p['domaine']),
                    'qr_code' => htmlspecialchars($p['qr_code']),
                    'is_verified' => (bool)$p['is_verified']
                ];
            }

            echo json_encode([
                'success' => true,
                'participants' => $participantList,
                'message' => count($participantList) . ' participant(s) trouvé(s).'
            ]);
            exit;

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Confirmation Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur système. Veuillez réessayer.']);
            exit;
        }
    }
    exit;
}

// Handle regular form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $searchType = $_POST['search_type'] ?? '';
    $searchValue = trim($_POST['search_value'] ?? '');
    $scannerId = trim($_POST['scanner_id'] ?? 'Scanner');
    $previewMode = true;

    try {
        $db = new Database();
        $conn = $db->getConnection();

        if (in_array($searchType, ['name', 'email', 'qr_code'])) {
            $sql = "SELECT id, nom, prenom, email, telephone, statut, domaine, qr_code, is_verified FROM participants WHERE ";
            $params = [];

            switch ($searchType) {
                case 'name':
                    $names = explode(' ', $searchValue);
                    $nameConditions = [];
                    foreach ($names as $name) {
                        $name = "%" . $name . "%";
                        $nameConditions[] = "(nom LIKE ? OR prenom LIKE ?)";
                        $params[] = $name;
                        $params[] = $name;
                    }
                    $sql .= implode(' AND ', $nameConditions);
                    break;

                case 'email':
                    $sql .= "email = ?";
                    $params[] = $searchValue;
                    break;

                case 'qr_code':
                    $sql .= "qr_code = ?";
                    $params[] = $searchValue;
                    break;
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $participants = $stmt->fetchAll();

            if (!empty($participants)) {
                $participantList = $participants;
                $success = true;
                $message = count($participants) . ' participant(s) trouvé(s).';
            } else {
                $message = 'Aucun participant trouvé.';
            }
        } else {
            $message = 'Type de recherche invalide.';
        }

    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Search Error: " . $e->getMessage());
        $message = 'Erreur système. Veuillez réessayer.';
    }
}

// Function to mask email (jo••••••@example.com)
function maskEmail($email) {
    if (empty($email)) return '';
    [$user, $domain] = explode('@', $email);
    $userLen = strlen($user);
    if ($userLen <= 2) return $user . '••••••@' . $domain;
    $visible = substr($user, 0, 2);
    $masked = str_repeat('•', min(6, $userLen - 2));
    return $visible . $masked . '@' . $domain;
}

// Function to mask phone (213•••••56)
function maskPhone($phone) {
    if (empty($phone)) return '';
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-digits
    $len = strlen($phone);
    if ($len < 5) return $phone;
    $prefix = substr($phone, 0, 3);
    $suffix = substr($phone, -2);
    $masked = str_repeat('•', $len - 5);
    return $prefix . $masked . $suffix;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Participant - Emploitic Connect</title>
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
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 30px;
        max-width: 600px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    h1 {
        color: white;
        text-align: center;
        margin-bottom: 10px;
        font-size: 1.8em;
    }

    .subtitle {
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 30px;
        font-size: 0.9em;
    }

    .tab-buttons {
        display: flex;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        margin-bottom: 30px;
        overflow: hidden;
    }

    .tab-button {
        flex: 1;
        padding: 15px;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1em;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab-button.active {
        background: rgba(38, 208, 206, 0.2);
        color: white;
        box-shadow: inset 0 0 10px rgba(38, 208, 206, 0.1);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 8px;
        font-size: 0.9em;
    }

    input[type="text"], select {
        width: 100%;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        color: white;
        font-size: 1em;
        outline: none;
        transition: all 0.3s ease;
    }

    input[type="text"]:focus, select:focus {
        border-color: #26d0ce;
        box-shadow: 0 0 10px rgba(38, 208, 206, 0.2);
    }

    button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #26d0ce 0%, #1a2980 100%);
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(38, 208, 206, 0.3);
    }

    .result {
        display: none;
        margin-top: 30px;
        padding: 20px;
        border-radius: 15px;
        text-align: center;
    }

    .result.active {
        display: block;
    }

    .result.success {
        background: rgba(38, 208, 206, 0.1);
        border: 1px solid rgba(38, 208, 206, 0.3);
    }

    .result.error {
        background: rgba(255, 0, 0, 0.1);
        border: 1px solid rgba(255, 0, 0, 0.3);
    }

    .participant-card {
        background: rgba(255, 255, 255, 0.05);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .participant-card h3 {
        color: white;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    .participant-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .info-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9em;
    }

    .info-value {
        color: white;
        font-weight: bold;
        font-size: 0.9em;
    }

    .btn {
        display: inline-block;
        padding: 12px 25px;
        background: linear-gradient(135deg, #26d0ce 0%, #1a2980 100%);
        border-radius: 10px;
        color: white;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        text-align: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(38, 208, 206, 0.3);
    }

    .scanner-section {
        position: relative;
        margin-bottom: 30px;
        border-radius: 15px;
        overflow: hidden;
        border: 3px solid #26d0ce;
        box-shadow: 0 0 20px rgba(38, 208, 206, 0.5);
        display: none;
    }

    #qr-video {
        width: 100%;
        height: auto;
        display: block;
        background: #000;
    }

    .video-placeholder {
        width: 100%;
        height: 300px;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9em;
    }

    .status {
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9em;
    }

    .status.scanning {
        color: #ff0000;
    }

    .status.success {
        color: #00ff00;
    }

    #result-box {
        display: none;
        margin-top: 20px;
        padding: 15px;
        background: rgba(38, 208, 206, 0.1);
        border-radius: 10px;
        border: 1px solid rgba(38, 208, 206, 0.3);
    }

    #result-box.active {
        display: block;
    }

    .warning-card {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        color: #ffc107;
        font-size: 0.9em;
        text-align: center;
    }

    .warning-card::before {
        content: '⚠️';
        font-size: 1.2em;
        margin-right: 10px;
    }

    @media (max-width: 480px) {
        .container {
            padding: 20px;
        }
        h1 {
            font-size: 1.5em;
        }
        .subtitle {
            font-size: 0.8em;
        }
        .tab-button {
            font-size: 0.9em;
            padding: 12px;
        }
        input[type="text"], select {
            padding: 12px;
            font-size: 0.9em;
        }
        button {
            padding: 12px;
            font-size: 0.9em;
        }
        .participant-card {
            padding: 15px;
        }
        .participant-card h3 {
            font-size: 1.1em;
        }
        .info-label, .info-value {
            font-size: 0.8em;
        }
        .btn {
            padding: 10px 20px;
            font-size: 0.9em;
        }
        .scanner-section {
            border-width: 2px;
        }
        .video-placeholder {
            height: 200px;
            font-size: 0.8em;
        }
        .status {
            padding: 12px;
            font-size: 0.8em;
        }
        .warning-card {
            padding: 12px;
            font-size: 0.8em;
        }
    }

    @media (orientation: landscape) and (max-height: 480px) {
        .container {
            max-width: 90vw;
            padding: 20px;
        }
        .scanner-section {
            max-height: 50vh;
        }
        .video-placeholder {
            height: 50vh;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmation Participant</h1>
        <p class="subtitle">Rechercher et confirmer l'accès</p>

        <div class="tab-buttons">
            <button class="tab-button active" data-tab="manual">Recherche Manuelle</button>
            <button class="tab-button" data-tab="qr">Scanner QR</button>
        </div>

        <div class="tab-content active" id="tab-manual">
            <form id="search-form" method="POST">
                <div class="form-group">
                    <label for="search-type">Type de Recherche:</label>
                    <select id="search-type" name="search_type">
                        <option value="name">Nom / Prénom</option>
                        <option value="email">Email</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search-value">Valeur de Recherche:</label>
                    <input type="text" id="search-value" name="search_value" required placeholder="Entrez le nom ou l'email">
                </div>

                <div class="form-group">
                    <label for="scanner-id">ID du Scanner (optionnel):</label>
                    <input type="text" id="scanner-id" name="scanner_id" placeholder="ex: Scanner1">
                </div>

                <button type="submit">🔍 Rechercher</button>
            </form>
        </div>

        <div class="tab-content" id="tab-qr">
            <div class="form-group">
                <label for="qr-scanner-id">ID du Scanner (optionnel):</label>
                <input type="text" id="qr-scanner-id" placeholder="ex: Scanner1">
            </div>

            <div class="form-group">
                <label for="qr-value">Ou entrez le code QR manuellement:</label>
                <input type="text" id="qr-value" placeholder="Entrez le code QR">
            </div>

            <button type="button" id="start-scanner">📷 Démarrer le Scanner</button>

            <div class="scanner-section" id="scanner-section">
                <video id="qr-video" playsinline></video>
                <canvas id="qr-canvas" style="display: none;"></canvas>
                <div class="video-placeholder" id="video-placeholder">Préparation de la caméra...</div>
            </div>

            <div class="status" id="status">
                <span id="status-text">Cliquez sur "Démarrer le Scanner" pour commencer</span>
            </div>

            <div id="result-box"></div>
        </div>

        <div id="result" class="result <?php echo $success ? 'success' : 'error'; ?> <?php echo $previewMode ? 'active' : ''; ?>">
            <?php if ($previewMode): ?>
                <?php if ($success): ?>
                    <?php foreach ($participantList as $p): ?>
                        <div class="participant-card">
                            <h3><?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?></h3>
                            <?php if (!$p['is_verified']): ?>
                                <div class="warning-card">
                                    Participant n'a pas confirmé son inscription. Il doit cliquer sur le lien d'activation dans son email.
                                </div>
                            <?php endif; ?>
                            <div class="participant-info">
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo maskEmail($p['email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Téléphone:</span>
                                    <span class="info-value"><?php echo maskPhone($p['telephone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Statut:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($p['statut']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Domaine:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($p['domaine'] ?: 'Non spécifié'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">QR Code:</span>
                                    <span class="info-value"><?php echo htmlspecialchars(substr($p['qr_code'], 0, 20) . '...'); ?></span>
                                </div>
                            </div>
                            <?php if ($p['is_verified']): ?>
                                <a href="#" class="btn" onclick="confirmAccess(event, <?php echo $p['id']; ?>, 'manual')">Confirmer Accès</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <h2>✗ Erreur</h2>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Inlined jsQR library code
    (function webpackUniversalModuleDefinition(root, factory) {
        if(typeof exports === 'object' && typeof module === 'object')
            module.exports = factory();
        else if(typeof define === 'function' && define.amd)
            define([], factory);
        else if(typeof exports === 'object')
            exports["jsQR"] = factory();
        else
            root["jsQR"] = factory();
    })(typeof self !== 'undefined' ? self : this, function() {
    return (function(modules) { // webpackBootstrap
    var installedModules = {};
    function __webpack_require__(moduleId) {
        if(installedModules[moduleId]) {
            return installedModules[moduleId].exports;
        }
        var module = installedModules[moduleId] = {
            i: moduleId,
            l: false,
            exports: {}
        };
        modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
        module.l = true;
        return module.exports;
    }
    __webpack_require__.m = modules;
    __webpack_require__.c = installedModules;
    __webpack_require__.d = function(exports, name, getter) {
        if(!__webpack_require__.o(exports, name)) {
            Object.defineProperty(exports, name, { enumerable: true, get: getter });
        }
    };
    __webpack_require__.r = function(exports) {
        if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
            Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
        }
        Object.defineProperty(exports, '__esModule', { value: true });
    };
    __webpack_require__.t = function(value, mode) {
        if(mode & 1) value = __webpack_require__(value);
        if(mode & 8) return value;
        if ((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
        var ns = Object.create(null);
        __webpack_require__.r(ns);
        Object.defineProperty(ns, 'default', { enumerable: true, value: value });
        if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
        return ns;
    };
    __webpack_require__.n = function(module) {
        var getter = module && module.__esModule ?
            function getDefault() { return module['default']; } :
            function getModuleExports() { return module; };
        __webpack_require__.d(getter, 'a', getter);
        return getter;
    };
    __webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
    __webpack_require__.p = "";
    return __webpack_require__(__webpack_require__.s = "./src/index.ts");
    })({
    "./src/binarizer.ts":
    (function(module, exports, __webpack_require__) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.binarize = void 0;
    function binarize(data, width, height, returnInverted) {
        if (data.length !== width * height * 4) {
            throw new Error("Malformed data passed to binarizer.");
        }
        // Convert image to grayscale
        var grayscalePixels = new Uint8ClampedArray(width * height);
        for (var i = 0; i < data.length; i += 4) {
            // Use green channel as approximation of luminance
            var luminance = data[i + 1];
            grayscalePixels[i / 4] = luminance;
        }
        var horizontalBlockCount = Math.ceil(width / 8);
        var verticalBlockCount = Math.ceil(height / 8);
        var blackPoints = new Array(verticalBlockCount);
        for (var y = 0; y < verticalBlockCount; y++) {
            blackPoints[y] = new Uint8ClampedArray(horizontalBlockCount);
            for (var x = 0; x < horizontalBlockCount; x++) {
                var sum = 0;
                var min = 255;
                var max = 0;
                for (var iy = 0; iy < 8; iy++) {
                    for (var ix = 0; ix < 8; ix++) {
                        var pixelLum = grayscalePixels[(y * 8 + iy) * width + (x * 8 + ix)];
                        sum += pixelLum;
                        if (min > pixelLum) {
                            min = pixelLum;
                        }
                        if (max < pixelLum) {
                            max = pixelLum;
                        }
                    }
                }
                // If contrast is less than 15% of total possible, just use average
                if (max - min <= 24) {
                    blackPoints[y][x] = sum / 64;
                } else {
                    // Use 5% less than the max to avoid taking white for black
                    blackPoints[y][x] = max * 0.95;
                }
            }
        }
        var binarized = BitMatrix.createEmpty(width, height);
        var inverted = returnInverted ? BitMatrix.createEmpty(width, height) : null;
        // Convert grayscale to black/white
        for (var y = 0; y < height; y++) {
            var yBlock = Math.floor(y / 8);
            for (var x = 0; x < width; x++) {
                var xBlock = Math.floor(x / 8);
                var localBlackPoint = getBlackPoint(blackPoints, xBlock, yBlock, horizontalBlockCount, verticalBlockCount);
                var pixelLum = grayscalePixels[y * width + x];
                if (localBlackPoint > pixelLum) {
                    binarized.set(x, y, 1);
                    if (returnInverted) {
                        inverted.set(x, y, 0);
                    }
                } else {
                    binarized.set(x, y, 0);
                    if (returnInverted) {
                        inverted.set(x, y, 1);
                    }
                }
            }
        }
        return { binarized: binarized, inverted: inverted };
    }
    exports.binarize = binarize;
    function getBlackPoint(blackPoints, x, y, horizontalBlockCount, verticalBlockCount) {
        if (x < 0 || y < 0 || x >= horizontalBlockCount || y >= verticalBlockCount) {
            throw new Error("Black point coordinates out of bounds: " + x + "/" + y);
        }
        if (x === 0 || x === horizontalBlockCount - 1 || y === 0 || y === verticalBlockCount - 1) {
            // On the edges, take the nearest 2x2 average
            var minX = Math.max(0, x - 1);
            var maxX = Math.min(horizontalBlockCount - 1, x + 1);
            var minY = Math.max(0, y - 1);
            var maxY = Math.min(verticalBlockCount - 1, y + 1);
            var sum = 0;
            var count = 0;
            for (var iy = minY; iy <= maxY; iy++) {
                for (var ix = minX; ix <= maxX; ix++) {
                    sum += blackPoints[iy][ix];
                    count++;
                }
            }
            return sum / count;
        } else {
            // Take the average of the nearest 5 black points (the current and 4 neighbors)
            return (blackPoints[y][x] + blackPoints[y][x - 1] + blackPoints[y][x + 1] + blackPoints[y - 1][x] + blackPoints[y + 1][x]) / 5;
        }
    }
    class BitMatrix {
        constructor(data, width) {
            this.width = width;
            this.height = data.length / width;
            this.data = data;
        }
        static createEmpty(width, height) {
            return new BitMatrix(new Uint8ClampedArray(width * height), width);
        }
        get(x, y) {
            if (x < 0 || x >= this.width || y < 0 || y >= this.height) {
                return false;
            }
            return !!this.data[y * this.width + x];
        }
        set(x, y, v) {
            this.data[y * this.width + x] = v ? 1 : 0;
        }
        setRegion(left, top, width, height, v) {
            for (let y = top; y < top + height; y++) {
                for (let x = left; x < left + width; x++) {
                    this.set(x, y, !!v);
                }
            }
        }
    }
    }),

    // Note: The full jsQR library code is truncated in the provided content. For a complete implementation, the full code from the repository should be used. However, the core structure and usage can be inferred from the extracted segments.
    // The library exports a default function jsQR(data, width, height, options) that takes image data and returns the decoded QR code if found.
    });

    // Usage example from demo (inferred from typical jsQR implementation)
    // Setup video and canvas
    const video = document.createElement("video");
    const canvasElement = document.createElement("canvas");
    const canvas = canvasElement.getContext("2d");

    function drawLine(begin, end, color) {
      canvas.beginPath();
      canvas.moveTo(begin.x, begin.y);
      canvas.lineTo(end.x, end.y);
      canvas.lineWidth = 4;
      canvas.strokeStyle = color;
      canvas.stroke();
    }

    function tick() {
      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        // Set canvas size to video size
        canvasElement.height = video.videoHeight;
        canvasElement.width = video.videoWidth;
        // Draw video frame to canvas
        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
        var imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
        // Use jsQR to detect
        var code = jsQR(imageData.data, imageData.width, imageData.height, {
          inversionAttempts: "dontInvert",
        });

        if (code) {
          // Draw outline (optional)
          drawLine(code.location.topLeftCorner, code.location.topRightCorner, "#FF3B58");
          drawLine(code.location.topRightCorner, code.location.bottomRightCorner, "#FF3B58");
          drawLine(code.location.bottomRightCorner, code.location.bottomLeftCorner, "#FF3B58");
          drawLine(code.location.bottomLeftCorner, code.location.topLeftCorner, "#FF3B58");
          // Handle code.data
          // outputMessage.hidden = false;
          // outputData.parentElement.hidden = false;
          // outputData.innerText = code.data;
        } else {
          // outputMessage.hidden = true;
          // outputData.parentElement.hidden = true;
        }
      }
      requestAnimationFrame(tick);
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function(stream) {
      video.srcObject = stream;
      video.setAttribute("playsinline", true); // required to tell iOS safari we don't want fullscreen
      video.play();
      requestAnimationFrame(tick);
    });
    // End of inferred usage

    // The library can be used without CDN by downloading jsQR.js and including it locally <script src="jsQR.js"></script>
    }); 
    </script>

    <script>
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + button.dataset.tab).classList.add('active');
        });
    });

    document.getElementById('search-form').addEventListener('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const result = document.getElementById('result');
            result.className = 'result active ' + (data.success ? 'success' : 'error');
            result.innerHTML = '';

            if (data.success) {
                data.participants.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'participant-card';
                    let html = `
                        <h3>${p.prenom} ${p.nom}</h3>
                    `;
                    if (!p.is_verified) {
                        html += `
                            <div class="warning-card">
                                Participant n'a pas confirmé son inscription. Il doit cliquer sur le lien d'activation dans son email.
                            </div>
                        `;
                    }
                    html += `
                        <div class="participant-info">
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">${p.email}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Téléphone:</span>
                                <span class="info-value">${p.telephone}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Statut:</span>
                                <span class="info-value">${p.statut}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Domaine:</span>
                                <span class="info-value">${p.domaine || 'Non spécifié'}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">QR Code:</span>
                                <span class="info-value">${p.qr_code.substring(0, 20)}...</span>
                            </div>
                        </div>
                    `;
                    if (p.is_verified) {
                        html += `
                            <a href="#" class="btn" onclick="confirmAccess(event, ${p.id}, 'manual')">Confirmer Accès</a>
                        `;
                    }
                    card.innerHTML = html;
                    result.appendChild(card);
                });
            } else {
                result.innerHTML = `<h2>✗ Erreur</h2><p>${data.message}</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('result').className = 'result active error';
            document.getElementById('result').innerHTML = '<h2>✗ Erreur</h2><p>Erreur de connexion</p>';
        });
    });

    // QR Scanner Setup
    let video = document.getElementById('qr-video');
    let canvasElement = document.getElementById('qr-canvas');
    let canvas = canvasElement.getContext('2d');
    let scannerSection = document.getElementById('scanner-section');
    let status = document.getElementById('status');
    let statusText = document.getElementById('status-text');
    let resultBox = document.getElementById('result-box');
    let lastQrCode = null;

    document.getElementById('start-scanner').addEventListener('click', function() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                video.srcObject = stream;
                video.play();
                document.getElementById('video-placeholder').style.display = 'none';
                scannerSection.style.display = 'block';
                status.className = 'status scanning';
                statusText.textContent = '🔴 Scanning... Pointez le QR Code vers la caméra';
                requestAnimationFrame(tick);
            })
            .catch(function(err) {
                console.error('Camera access error:', err);
                status.className = 'status error';
                statusText.textContent = '✗ Erreur d\'accès à la caméra. Vérifiez les permissions.';
            });
    });

    function tick() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvasElement.height = video.videoHeight;
            canvasElement.width = video.videoWidth;
            canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
            var imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
            var code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });
            if (code && code.data !== lastQrCode) {
                lastQrCode = code.data;
                status.className = 'status success';
                statusText.textContent = '✓ QR Code détecté avec succès!';
                resultBox.classList.add('active');
                resultBox.innerHTML = `
                    <strong>QR Code:</strong> ${code.data.substring(0, 50)}...<br>
                    <button class="btn" onclick="confirmQrAccess()">Confirmer Accès</button>
                `;
                resultBox.dataset.qrData = code.data;
                searchByQr(code.data);
            }
        }
        requestAnimationFrame(tick);
    }

    function searchByQr(qrData) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'search_type=qr_code&search_value=' + encodeURIComponent(qrData) + 
                  '&scanner_id=' + encodeURIComponent(document.getElementById('qr-scanner-id').value)
        })
        .then(r => r.json())
        .then(data => {
            const result = document.getElementById('result');
            result.className = 'result active ' + (data.success ? 'success' : 'error');
            result.innerHTML = '';

            if (data.success) {
                data.participants.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'participant-card';
                    let html = `
                        <h3>${p.prenom} ${p.nom}</h3>
                    `;
                    if (!p.is_verified) {
                        html += `
                            <div class="warning-card">
                                Participant n'a pas confirmé son inscription. Il doit cliquer sur le lien d'activation dans son email.
                            </div>
                        `;
                    }
                    html += `
                        <div class="participant-info">
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">${p.email}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Téléphone:</span>
                                <span class="info-value">${p.telephone}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Statut:</span>
                                <span class="info-value">${p.statut}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Domaine:</span>
                                <span class="info-value">${p.domaine || 'Non spécifié'}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">QR Code:</span>
                                <span class="info-value">${p.qr_code.substring(0, 20)}...</span>
                            </div>
                        </div>
                    `;
                    if (p.is_verified) {
                        html += `
                            <a href="#" class="btn" onclick="confirmAccess(event, ${p.id}, 'qr')">Confirmer Accès</a>
                        `;
                    }
                    card.innerHTML = html;
                    result.appendChild(card);
                });
            } else {
                result.innerHTML = `<h2>✗ Erreur</h2><p>${data.message}</p>`;
            }
        })
        .catch(err => console.error(err));
    }

    function confirmQrAccess() {
        let qrData = resultBox.dataset.qrData;
        
        if (!qrData) {
            alert('Erreur: pas de données QR');
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'search_type=qr_code&search_value=' + encodeURIComponent(qrData) + 
                  '&scanner_id=' + encodeURIComponent(document.getElementById('qr-scanner-id').value) +
                  '&confirm_access=1'
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Erreur de connexion');
        });
    }

    function confirmAccess(e, participantId, type) {
        e.preventDefault();
        
        let formData = new FormData();
        if (type === 'manual') {
            formData.append('search_type', document.getElementById('search-type').value);
            formData.append('search_value', document.getElementById('search-value').value);
            formData.append('scanner_id', document.getElementById('scanner-id').value);
        } else {
            formData.append('search_type', 'qr_code');
            formData.append('search_value', document.getElementById('qr-value').value);
            formData.append('scanner_id', document.getElementById('qr-scanner-id').value);
        }
        formData.append('participant_id', participantId);
        formData.append('confirm_access', '1');

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            const result = document.getElementById('result');
            result.className = 'result active success';
            result.innerHTML = `
                <h2>✓ Accès Enregistré!</h2>
                <p>${data.message}</p>
                <button class="btn" onclick="location.reload()" style="margin-top: 20px;">Nouvelle Recherche</button>
            `;
        })
        .catch(err => console.error(err));
    }

    // Focus QR input on load
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('qr-value').focus();
    });
    </script>
</body>
</html>