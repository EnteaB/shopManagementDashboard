<?php
require_once '../includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if admin user exists
    $stmt = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch();
    
    if (!$admin) {
        // Create admin user if not exists
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, first_name) VALUES (?, ?, ?, 'admin', 'Admin')");
        $stmt->execute(['admin', $password, 'admin@fashshop.com']);
        echo "✅ Admin user created successfully";
    } else {
        // Update admin password
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$password]);
        echo "✅ Admin password updated successfully";
    }
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}