<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verify users table structure
    $expected_columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'username' => 'VARCHAR(50) NOT NULL UNIQUE',
        'password' => 'VARCHAR(255) NOT NULL',
        'role' => "ENUM('admin','user') NOT NULL DEFAULT 'user'",
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        // Create table if it doesn't exist
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        echo "✅ Created users table<br>";
    }
    
    // Insert admin user if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$hash]);
        echo "✅ Created admin user (username: admin, password: admin123)<br>";
    }
    
    echo "✅ Database verification complete";
    
} catch(Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}