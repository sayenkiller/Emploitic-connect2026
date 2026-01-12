<?php
// Registration Handler - Fixed for Resend.com API
// Works on InfinityFree - With CSRF protection and Rate Limiting
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';
require_once 'email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $input = $_POST;
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue']);
        exit;
    }
    
    // CSRF Token Validation
    if (!isset($input['csrf_token']) || !Security::validateCSRFToken($input['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
        exit;
    }
    
    // Rate Limiting - Check IP address
    $clientIP = Security::getClientIP();
    if (!Security::checkRateLimit($clientIP, 5, 300)) { // 5 attempts per 5 minutes
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.']);
        exit;
    }
    
    // Sanitize inputs
    $nom = Security::sanitizeInput($input['nom'] ?? '');
    $prenom = Security::sanitizeInput($input['prenom'] ?? '');
    $email = Security::sanitizeInput($input['email'] ?? '');
    $telephone = Security::sanitizeInput($input['telephone'] ?? '');
    $statut = Security::sanitizeInput($input['statut'] ?? '');
    $domaine = Security::sanitizeInput($input['domaine'] ?? '');
    
    // Validation
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
        $errors[] = 'Numéro de téléphone invalide';
    }
    $validStatuses = ['etudiant', 'diplome', 'emploi', 'professionnel'];
    if (!in_array($statut, $validStatuses)) {
        $errors[] = 'Statut invalide';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create tables if needed
    $db->createTables();
    
    // Check if email already exists and is verified
    $stmt = $conn->prepare("SELECT id, is_verified FROM participants WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing && $existing['is_verified']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà inscrite et vérifiée']);
        exit;
    }
    
    // Delete unverified registrations with same email
    if ($existing && !$existing['is_verified']) {
        $stmt = $conn->prepare("DELETE FROM participants WHERE email = ? AND is_verified = 0");
        $stmt->execute([$email]);
    }
    
    // Generate verification token
    $token = Security::generateToken();
    $hashedToken = Security::hashToken($token);
    
    // Get client IP
    $clientIP = Security::getClientIP();
    
    // Insert participant with unverified status
    $sql = "INSERT INTO participants (nom, prenom, email, telephone, statut, domaine, verification_token, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $prenom, $email, $telephone, $statut, $domaine, $hashedToken, $clientIP]);
    
    $participantId = $conn->lastInsertId();
    
    error_log("[" . date('Y-m-d H:i:s') . "] New registration: " . $prenom . " " . $nom . " - Email: " . $email . " - ID: " . $participantId);
    
    // Send verification email using Resend API
    $fullName = $prenom . ' ' . $nom;
    $emailSent = EmailService::sendVerificationEmail($email, $fullName, $token);
    
    if ($emailSent) {
        error_log("[" . date('Y-m-d H:i:s') . "] Verification email sent successfully to: " . $email);
    } else {
        error_log("[" . date('Y-m-d H:i:s') . "] WARNING: Email delivery failed for: " . $email);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie! Veuillez vérifier votre email pour confirmer votre inscription.',
        'participant_id' => $participantId,
        'email_sent' => $emailSent
    ]);
    
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données. Veuillez réessayer.'
    ]);
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue. Veuillez réessayer.'
    ]);
}
?>