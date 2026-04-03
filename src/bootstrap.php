<?php
/**
 * SecureVault Bootstrap - Load all required src files
 * Usage: require_once __DIR__ . '/../src/bootstrap.php';
 */

// Load configuration
require_once __DIR__ . '/../config/database.php';

// Load core classes and functions
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Accounts.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Define constants if not already defined
if (!defined('DB_HOST')) {
    throw new Exception('Database configuration not loaded');
}
?>
