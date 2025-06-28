<?php
require_once '../includes/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Define users to create/update
    $users = [
        [
            'username' => 'admin',
            'password' => 'admin123',
            'email' => 'admin@fashshop.com',
            'role' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User'
        ],
        [
            'username' => 'manager_women',
            'password' => 'women123',
            'email' => 'women@fashshop.com',
            'role' => 'manager_women',
            'first_name' => 'Women',
            'last_name' => 'Manager'
        ],
        [
            'username' => 'manager_men',
            'password' => 'men123',
            'email' => 'men@fashshop.com',
            'role' => 'manager_men',
            'first_name' => 'Men',
            'last_name' => 'Manager'
        ]
    ];

    foreach ($users as $user) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        $exists = $stmt->fetch();

        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);

        if ($exists) {
            // Update existing user
            $stmt = $conn->prepare("
                UPDATE users 
                SET password = ?, email = ?, role = ?, first_name = ?, last_name = ?
                WHERE username = ?
            ");
            $stmt->execute([
                $hashedPassword,
                $user['email'],
                $user['role'],
                $user['first_name'],
                $user['last_name'],
                $user['username']
            ]);
            echo "✅ Updated user: {$user['username']}\n";
        } else {
            // Create new user
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, email, role, first_name, last_name)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['username'],
                $hashedPassword,
                $user['email'],
                $user['role'],
                $user['first_name'],
                $user['last_name']
            ]);
            echo "✅ Created user: {$user['username']}\n";
        }
    }

    echo "\nLogin credentials:\n";
    echo "-------------------\n";
    echo "Admin:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n\n";
    echo "Women's Manager:\n";
    echo "Username: manager_women\n";
    echo "Password: women123\n\n";
    echo "Men's Manager:\n";
    echo "Username: manager_men\n";
    echo "Password: men123\n";

} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}