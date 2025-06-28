<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get and decode the JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'User ID is required']));
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Don't allow deleting own account
    if ($data['id'] == $_SESSION['user_id']) {
        throw new Exception("You cannot delete your own account");
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    if ($stmt->rowCount() === 0) {
        throw new Exception("User not found");
    }
    
    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt->execute([$data['id']])) {
        throw new Exception("Failed to delete user");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}