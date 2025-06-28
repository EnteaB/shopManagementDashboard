<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Ensure user is admin
requireAdmin();

$response = ['success' => false, 'message' => ''];

try {
    // Update allowed roles to match form values
    $allowedRoles = ['admin', 'manager_women', 'manager_men', 'user'];
    $role = $_POST['role'] ?? '';
    
    if (!in_array($role, $allowedRoles)) {
        throw new Exception('Invalid role selected. Role: ' . $role);
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']); // Add this line to get the role

    if (empty($username) || empty($email) || empty($role)) {
        throw new Exception('Required fields are missing');
    }

    // Check for duplicate username, excluding current user if editing
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $id ?? 0]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Username already exists');
    }

    // Check for duplicate email, excluding current user if editing
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id ?? 0]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Email already exists');
    }

    // Continue with user creation/update
    if ($id) {
        // Update existing user
        if (!empty($password)) {
            // Update with new password
            // Validate password strength for new passwords
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            
            // Hash the password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashedPassword, $role, $id]);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $id]);
        }
    } else {
        // Create new user
        if (empty($password)) {
            throw new Exception('Password is required for new users');
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        $id = $conn->lastInsertId();
    }

    // Fetch updated user data
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['message'] = $id ? 'User updated successfully' : 'User added successfully';
    $response['user'] = $user;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);