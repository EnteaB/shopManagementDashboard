<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify women manager access
requireAdmin();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'No product ID provided']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Delete associated discounts first
    $stmt = $conn->prepare("DELETE FROM discounts WHERE product_id = ?");
    $stmt->execute([$id]);

    // Then delete the product, ensuring it belongs to women's category
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND category = 'female'");
    $result = $stmt->execute([$id]);

    if ($result && $stmt->rowCount() > 0) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Product not found or not in women\'s category']);
    }
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}