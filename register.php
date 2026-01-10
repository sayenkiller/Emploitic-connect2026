<?php
// DEBUG VERSION of register.php - Replace temporarily to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';
require_once 'email.php';

// Log function for debugging
function debugLog($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $logMessage .= ' - ' . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, __DIR__ . '/debug.log');
}

debugLog("=== REGISTRATION START ===");
debugLog("Request Method", $_SERVER['REQUEST_METHOD']);
debugLog("Content Type", $_SERVER['CONTENT_TYPE'] ?? 'not set');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("ERROR: Wrong method");
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    debugLog("Reading input data...");

    // Get POST data (FormData from fetch)
    $input = $_POST;
    debugLog("POST data", $input);

    if (empty($input)) {
        debugLog("ERROR: No input data received");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Aucune donnée reçue',
            'debug' => [
                'post' => $_POST,
                'get' => $_GET
            ]
        ]);
        exit;
    }
    
    // Rate limiting (increased for testing)
    debugLog("Checking rate limit...");
    $clientIP = Security::getClientIP();
    debugLog("Client IP", $clientIP);

    if (!Security::checkRateLimit($clientIP, 20, 3600)) { // Increased to 20 attempts per hour for testing
        debugLog("ERROR: Rate limit exceeded");
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
        exit;
    }
    
    // Sanitize and validate inputs
    debugLog("Sanitizing inputs...");
    $nom = Security::sanitizeInput($input['nom'] ?? '');
    $prenom = Security::sanitizeInput($input['prenom'] ?? '');
    $email = Security::sanitizeInput($input['email'] ?? '');
    $telephone = Security::sanitizeInput($input['telephone'] ?? '');
    $statut = Security::sanitizeInput($input['statut'] ?? '');
    $domaine = Security::sanitizeInput($input['domaine'] ?? '');
    
    debugLog("Sanitized data", [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'statut' => $statut,
        'domaine' => $domaine
    ]);
    
    // Validation
    debugLog("Validating inputs...");
    $errors = [];
    
    if (empty($nom) || strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères';
    }
    
    if (empty($prenom) || strlen($prenom) < 2) {
        $errors[] = 'Le prénom doit contenir au moins 2 caractères';
    }
    
    if (!Security::validateEmail($email)) {
        $errors[] = 'Adresse email invalide';
    }
    
    if (!Security::validatePhone($telephone)) {
        $errors[] = 'Numéro de téléphone invalide (format algérien requis)';
    }
    
    $validStatuses = ['etudiant', 'diplome', 'emploi', 'professionnel'];
    if (!in_array($statut, $validStatuses)) {
        $errors[] = 'Statut invalide';
    }
    
    if (!empty($errors)) {
        debugLog("Validation errors", $errors);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    debugLog("Validation passed! Connecting to database...");
    
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    debugLog("Database connected successfully");
    
    // Check if email already exists
    debugLog("Checking for existing email...");
    $stmt = $conn->prepare("SELECT id, is_verified FROM participants WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    debugLog("Existing check result", $existing);
    
    if ($existing) {
        if ($existing['is_verified']) {
            debugLog("ERROR: Email already verified");
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà inscrite et vérifiée']);
            exit;
        } else {
            debugLog("Deleting old unverified registration...");
            $stmt = $conn->prepare("DELETE FROM participants WHERE email = ? AND is_verified = 0");
            $stmt->execute([$email]);
            debugLog("Old registration deleted");
        }
    }
    
    // Generate verification token
    debugLog("Generating verification token...");
    $token = Security::generateToken();
    $hashedToken = Security::hashToken($token);
    debugLog("Token generated", ['token' => $token, 'hashed' => $hashedToken]);
    
    // Insert participant
    debugLog("Inserting participant into database...");
    $sql = "INSERT INTO participants (nom, prenom, email, telephone, statut, domaine, verification_token, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    debugLog("SQL Query", $sql);
    
    $stmt = $conn->prepare($sql);
    $params = [$nom, $prenom, $email, $telephone, $statut, $domaine, $hashedToken, $clientIP];
    debugLog("SQL Parameters", $params);
    
    $result = $stmt->execute($params);
    debugLog("Insert result", $result);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        debugLog("ERROR: Insert failed", $errorInfo);
        throw new Exception('Erreur lors de l\'insertion des données: ' . print_r($errorInfo, true));
    }
    
    $insertedId = $conn->lastInsertId();
    debugLog("Participant inserted with ID", $insertedId);
    
    // Send verification email
    debugLog("Sending verification email...");
    $fullName = $prenom . ' ' . $nom;
    $emailSent = EmailService::sendVerificationEmail($email, $fullName, $token);
    debugLog("Email sent result", $emailSent);
    
    if (!$emailSent) {
        debugLog("WARNING: Failed to send verification email");
        error_log("Failed to send verification email to: " . $email);
    }
    
    debugLog("=== REGISTRATION SUCCESS ===");
    
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie! Veuillez vérifier votre email pour confirmer votre inscription.',
        'email_sent' => $emailSent,
        'debug' => [
            'participant_id' => $insertedId,
            'token_generated' => true,
            'email_attempted' => true
        ]
    ]);
    
} catch (Exception $e) {
    debugLog("EXCEPTION CAUGHT", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue. Veuillez réessayer.',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

debugLog("=== REGISTRATION END ===\n\n");
?>