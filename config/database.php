<?php
/**
 * SecureVault Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'securevault');
define('DB_PORT', 3306);

// Encryption Settings
define('ENCRYPTION_ALGO', 'aes-256-gcm');
define('ENCRYPTION_KEY_DERIVATION', 'pbkdf2');

// Session Settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('SESSION_NAME', 'securevault_session');

// Category icons with emojis
define('CATEGORY_ICONS', [
    'Gaming' => '🎮',
    'University' => '🎓',
    'Financial' => '💳',
    'Social' => '👥',
    'Work' => '💼',
    'Entertainment' => '🎬',
    'Shopping' => '🛒',
    'Email' => '📧',
    'Cloud' => '☁️',
    'Other' => '📌'
]);
?>
