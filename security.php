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
        return preg_match('/^(\+213|0)[5-7][0-9]{8}$/', $phone);
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
    
    // Rate limiting
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 3600) {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $currentTime = time();
        $key = hash('sha256', $identifier);
        
        // Clean old entries
        if (isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = array_filter(
                $_SESSION['rate_limit'][$key],
                function($timestamp) use ($currentTime, $timeWindow) {
                    return ($currentTime - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limit'][$key] = [];
        }
        
        // Check limit
        if (count($_SESSION['rate_limit'][$key]) >= $maxAttempts) {
            return false;
        }
        
        // Add new attempt
        $_SESSION['rate_limit'][$key][] = $currentTime;
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
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
?>