<?php
/**
 * SecureVault Account Management
 * Path: src/Accounts.php
 */

class Accounts {
    /**
     * Get user's encryption key from master password
     */
    public static function getEncryptionKey($userId, $masterPassword) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('SELECT master_key_salt FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $user = $result->fetch_assoc();
        return deriveEncryptionKey($masterPassword, $user['master_key_salt']);
    }

    /**
     * Get all categories for user
     */
    public static function getCategories($userId) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('SELECT id, name, icon FROM categories WHERE user_id = ? ORDER BY name ASC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get all accounts for user
     */
    public static function getAll($userId) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('
            SELECT a.id, a.service_name, a.username, a.website_url, a.category_id, c.name as category_name, 
                   c.icon as category_icon, a.created_at, a.updated_at
            FROM accounts a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.user_id = ?
            ORDER BY c.name ASC, a.service_name ASC
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get accounts by category
     */
    public static function getByCategory($userId, $categoryId) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('
            SELECT id, service_name, username, website_url, category_id, created_at, updated_at
            FROM accounts
            WHERE user_id = ? AND category_id = ?
            ORDER BY service_name ASC
        ');
        $stmt->bind_param('ii', $userId, $categoryId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get single account with decrypted password
     */
    public static function getOne($userId, $accountId, $encryptionKey) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('
            SELECT id, service_name, username, password_encrypted, website_url, notes, category_id, created_at, updated_at
            FROM accounts
            WHERE id = ? AND user_id = ?
        ');
        $stmt->bind_param('ii', $accountId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $account = $result->fetch_assoc();
        $account['password'] = decryptPassword($account['password_encrypted'], $encryptionKey);
        
        return $account;
    }

    /**
     * Add new account
     */
    public static function create($userId, $serviceName, $username, $password, $categoryId, $websiteUrl, $notes, $encryptionKey) {
        $db = Database::getInstance()->getConnection();
        
        // Validation
        if (empty($serviceName)) {
            return ['success' => false, 'message' => 'Service name is required'];
        }
        
        if (empty($username)) {
            return ['success' => false, 'message' => 'Username is required'];
        }
        
        if (empty($password)) {
            return ['success' => false, 'message' => 'Password is required'];
        }
        
        // Encrypt password
        $encryptedPassword = encryptPassword($password, $encryptionKey);
        
        $stmt = $db->prepare('
            INSERT INTO accounts (user_id, service_name, username, password_encrypted, category_id, website_url, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('ississs', $userId, $serviceName, $username, $encryptedPassword, $categoryId, $websiteUrl, $notes);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account added successfully', 'id' => $db->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add account'];
        }
    }

    /**
     * Update account
     */
    public static function update($userId, $accountId, $serviceName, $username, $password, $categoryId, $websiteUrl, $notes, $encryptionKey) {
        $db = Database::getInstance()->getConnection();
        
        // Verify ownership
        $stmt = $db->prepare('SELECT id FROM accounts WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $accountId, $userId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        // Encrypt password
        $encryptedPassword = encryptPassword($password, $encryptionKey);
        
        $stmt = $db->prepare('
            UPDATE accounts 
            SET service_name = ?, username = ?, password_encrypted = ?, category_id = ?, website_url = ?, notes = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ');
        $stmt->bind_param('ssisssii', $serviceName, $username, $encryptedPassword, $categoryId, $websiteUrl, $notes, $accountId, $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update account'];
        }
    }

    /**
     * Delete account
     */
    public static function delete($userId, $accountId) {
        $db = Database::getInstance()->getConnection();
        
        // Verify ownership
        $stmt = $db->prepare('SELECT id FROM accounts WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $accountId, $userId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        $stmt = $db->prepare('DELETE FROM accounts WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $accountId, $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete account'];
        }
    }

    /**
     * Get vault statistics
     */
    public static function getStats($userId) {
        $db = Database::getInstance()->getConnection();
        
        // Total accounts
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM accounts WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        
        // By category
        $stmt = $db->prepare('
            SELECT c.name, c.icon, COUNT(a.id) as count
            FROM categories c
            LEFT JOIN accounts a ON c.id = a.category_id
            WHERE c.user_id = ?
            GROUP BY c.id, c.name, c.icon
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $byCategory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return [
            'total' => $total,
            'by_category' => $byCategory
        ];
    }
}
?>
