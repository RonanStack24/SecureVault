<?php
/**
 * Encryption and Utility Functions
 */

require_once 'config.php';

/**
 * Map category name to emoji (avoids database encoding issues)
 */
function getCategoryEmoji($name) {
    $map = [
        'Gaming' => '🎮',
        'University' => '🎓',
        'Financial' => '💰',
        'Social Media' => '📱',
        'Work' => '💼',
        'Entertainment' => '🎬',
        'Shopping' => '🛒',
        'Email' => '📧',
        'Cloud' => '☁️',
        'Other' => '📌',
    ];
    return $map[$name] ?? '📁';
}

/**
 * Generate a cryptographic random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Derive encryption key from master password using PBKDF2
 */
function deriveEncryptionKey($password, $salt) {
    $key = hash_pbkdf2('sha256', $password, $salt, 100000, 32, true);
    return $key;
}

/**
 * Create a salt for password hashing
 */
function createPasswordSalt() {
    return bin2hex(random_bytes(16));
}

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Encrypt password using AES-256-GCM
 */
function encryptPassword($password, $masterKey) {
    // Use 12 bytes (96 bits) IV for GCM - this is the recommended length
    $iv = openssl_random_pseudo_bytes(12);
    $tag = '';
    
    $encrypted = openssl_encrypt(
        $password, 
        ENCRYPTION_ALGO, 
        $masterKey, 
        OPENSSL_RAW_DATA, 
        $iv, 
        $tag,
        '',    // aad (additional authenticated data)
        16     // tag_length
    );
    
    if ($encrypted === false) {
        throw new Exception('Encryption failed: ' . openssl_error_string());
    }
    
    // Combine IV (12 bytes) + encrypted data + tag (16 bytes)
    return base64_encode($iv . $encrypted . $tag);
}

/**
 * Decrypt password using AES-256-GCM
 */
function decryptPassword($encryptedPassword, $masterKey) {
    try {
        if (empty($encryptedPassword) || !$masterKey) {
            return null;
        }
        
        $data = base64_decode($encryptedPassword, true);
        if ($data === false) {
            throw new Exception('Invalid base64 encoding');
        }
        
        $dataLen = strlen($data);
        if ($dataLen < 28) { // Minimum: 12 (IV) + 0 (encrypted) + 16 (tag)
            throw new Exception('Data too short');
        }
        
        // Extract components
        $iv = substr($data, 0, 12);        // 12 bytes
        $tag = substr($data, -16);         // Last 16 bytes
        $encrypted = substr($data, 12, -16); // Everything in between
        
        if (strlen($iv) !== 12 || strlen($tag) !== 16) {
            throw new Exception('Invalid IV or tag length');
        }
        
        $decrypted = openssl_decrypt(
            $encrypted,
            ENCRYPTION_ALGO,
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            ''     // aad (additional authenticated data)
        );
        
        if ($decrypted === false) {
            error_log('Decryption failed: ' . openssl_error_string());
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    } catch (Exception $e) {
        error_log('Password decryption error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Generate a secure session token
 */
function generateSessionToken() {
    return generateRandomString(64);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength (minimum 8 chars, 1 uppercase, 1 number, 1 special char)
 */
function validatePasswordStrength($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
}

/**
 * Calculate password strength percentage
 */
function getPasswordStrength($password) {
    $strength = 0;
    
    if (strlen($password) >= 8) $strength += 20;
    if (strlen($password) >= 12) $strength += 10;
    if (preg_match('/[a-z]/', $password)) $strength += 20;
    if (preg_match('/[A-Z]/', $password)) $strength += 20;
    if (preg_match('/[0-9]/', $password)) $strength += 15;
    if (preg_match('/[@$!%*?&]/', $password)) $strength += 15;
    
    return min(100, $strength);
}

/**
 * JSON response
 */
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $messageType = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $messageType;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
    $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
    
    if (isset($_SESSION['flash_message'])) {
        unset($_SESSION['flash_message']);
    }
    if (isset($_SESSION['flash_type'])) {
        unset($_SESSION['flash_type']);
    }
    
    return ['message' => $message, 'type' => $type];
}
