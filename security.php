<?php
class Security {
    
    // Sanitize input data
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate phone number (Algerian format)
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        // Accept various Algerian phone formats
        return preg_match('/^(\+213|0)[5-7][0-9]{8}$/', $phone) || preg_match('/^[0-9]{10,}$/', $phone);
    }
    
    // Generate secure token
    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    // Hash token for database storage
    public static function hashToken($token) {
        return hash('sha256', $token);
    }
    
    // Generate QR code data
    public static function generateQRData($participantId) {
        $data = [
            'id' => $participantId,
            'event' => SITE_NAME,
            'timestamp' => time()
        ];
        return json_encode($data);
    }
    
    // Prevent SQL injection - use prepared statements
    public static function validateInput($data, $type = 'string') {
        switch($type) {
            case 'email':
                return self::validateEmail($data);
            case 'phone':
                return self::validatePhone($data);
            case 'string':
                return is_string($data) && strlen($data) > 0 && strlen($data) <= 255;
            default:
                return false;
        }
    }
    
    // Rate limiting - DISABLED for unlimited registrations
    // Returns true to allow all requests
    public static function checkRateLimit($identifier, $maxAttempts = null, $timeWindow = null) {
        // No rate limiting - allow all registrations
        return true;
    }
    
    // CSRF Token
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Get client IP
    public static function getClientIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
?>