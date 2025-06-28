<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $field = $_POST['field'];
    $value = $_POST['value'];

    // Validate field name to prevent SQL injection
    if (in_array($field, ['username', 'email', 'role', 'status'])) {
        $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'User updated successfully';
        } else {
            $response['message'] = 'Error updating user';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Invalid field';
    }
}

header('Content-Type: application/json');
echo json_encode($response);