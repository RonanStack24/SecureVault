<?php
/**
 * SecureVault Auto-Setup - Clean Database Initialization with Emoji Categories
 * This creates the database structure and loads demo data
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/functions.php';

try {
    echo "🚀 Starting SecureVault Setup...\n";
    
    // Connect to MySQL
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($connection->connect_error) {
        throw new Exception('❌ Connection Error: ' . $connection->connect_error);
    }
    echo "✅ Connected to MySQL\n";
    
    // Create database
    if (!$connection->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME)) {
        throw new Exception('❌ Error creating database: ' . $connection->error);
    }
    echo "✅ Database created\n";
    
    // Select database
    if (!$connection->select_db(DB_NAME)) {
        throw new Exception('❌ Error selecting database: ' . $connection->error);
    }
    
    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            master_key_salt VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_category (user_id, name)
        )",
        
        "CREATE TABLE IF NOT EXISTS accounts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            service_name VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password_encrypted TEXT NOT NULL,
            website_url VARCHAR(255),
            category_id INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (session_token)
        )"
    ];
    
    foreach ($tables as $table) {
        if (!$connection->query($table)) {
            throw new Exception('❌ Error creating table: ' . $connection->error);
        }
    }
    echo "✅ Schema created (4 tables)\n";
    
    // Add demo user
    $demoUsername = 'demo';
    $demoEmail = 'demo@securevault.local';
    $demoPassword = 'Demo@123456';
    
    $passwordHash = hashPassword($demoPassword);
    $masterKeySalt = createPasswordSalt();
    
    $stmt = $connection->prepare('INSERT IGNORE INTO users (username, email, password_hash, master_key_salt) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        throw new Exception('❌ Prepare failed: ' . $connection->error);
    }
    $stmt->bind_param('ssss', $demoUsername, $demoEmail, $passwordHash, $masterKeySalt);
    if (!$stmt->execute()) {
        throw new Exception('❌ User insert failed: ' . $stmt->error);
    }
    $demoUserId = $connection->insert_id;
    echo "✅ Demo user created\n";
    
    if ($demoUserId) {
        // Create categories with emoji icons
        $categories = [
            ['Gaming', '🎮'],
            ['University', '🎓'],
            ['Financial', '💳']
        ];
        
        $catStmt = $connection->prepare('INSERT INTO categories (user_id, name, icon) VALUES (?, ?, ?)');
        if (!$catStmt) {
            throw new Exception('❌ Category prepare failed: ' . $connection->error);
        }
        
        foreach ($categories as $cat) {
            list($catName, $icon) = $cat;
            $catStmt->bind_param('iss', $demoUserId, $catName, $icon);
            if (!$catStmt->execute()) {
                throw new Exception('❌ Category insert failed: ' . $catStmt->error);
            }
        }
        echo "✅ Categories created with emojis\n";
        
        // Add sample accounts
        $encryptionKey = deriveEncryptionKey($demoPassword, $masterKeySalt);
        
        $samples = [
            ['Gaming', 'ShadowWalker_01', 'password123!', 'https://steam.com'],
            ['University', 'student_2024', 'UniPass@2024', 'https://myuniversity.edu'],
            ['Financial', 'vault_investor_99', 'SecureVault!99', 'https://cryptoexchange.com'],
        ];
        
        // Get category IDs
        $catResult = $connection->query('SELECT id, name FROM categories WHERE user_id = ' . $demoUserId);
        if (!$catResult) {
            throw new Exception('❌ Category query failed: ' . $connection->error);
        }
        $categoryMap = [];
        while ($row = $catResult->fetch_assoc()) {
            $categoryMap[$row['name']] = $row['id'];
        }
        
        $accountStmt = $connection->prepare('INSERT INTO accounts (user_id, service_name, username, password_encrypted, category_id, website_url, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$accountStmt) {
            throw new Exception('❌ Account prepare failed: ' . $connection->error);
        }
        
        foreach ($samples as $sample) {
            $catId = $categoryMap[$sample[0]];
            $encryptedPwd = encryptPassword($sample[2], $encryptionKey);
            $serviceName = match($sample[0]) {
                'Gaming' => 'Steam',
                'University' => 'UC Main Portal',
                'Financial' => 'Crypto Exchange',
            };
            $notes = 'Sample account for testing';
            
            $accountStmt->bind_param('issssss', $demoUserId, $serviceName, $sample[1], $encryptedPwd, $catId, $sample[3], $notes);
            if (!$accountStmt->execute()) {
                throw new Exception('❌ Account insert failed: ' . $accountStmt->error);
            }
        }
        echo "✅ Sample accounts created and encrypted\n";
    }
    
    $connection->close();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ SETUP COMPLETE!\n";
    echo str_repeat("=", 50) . "\n\n";
    echo "Demo Account Credentials:\n";
    echo "  📧 Username: demo\n";
    echo "  🔐 Password: Demo@123456\n\n";
    echo "Visit: http://localhost/SecureVault/\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
