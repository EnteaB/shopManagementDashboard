<?php
require_once '../includes/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Define manager credentials
$username = 'manager_men';
$password = 'me';           // Plain text password to match others
$email = 'manager_men@fashop.com';
$role = 'manager_men';

try {
    // First check if user exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        echo "User already exists. Use these credentials to login:\n";
        echo "----------------------------------------\n";
        echo "Username: manager_men\n";
        echo "Password: me\n";
        exit;
    }

    // Insert new manager with plain text password
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$username, $password, $role]);
    
    echo "Men's manager account created successfully!\n";
    echo "----------------------------------------\n";
    echo "Username: manager_men\n";
    echo "Password: me\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}