<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Verify men manager access
requireMenManager();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['product_id'] ?? null;
$discountPercent = $data['discount_percent'] ?? null;

if (!$productId || !is_numeric($discountPercent)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if product exists and belongs to men's category
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND category = 'male'");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    // First, deactivate any existing discounts
    $stmt = $conn->prepare("UPDATE discounts SET end_date = NOW() WHERE product_id = ? AND end_date IS NULL");
    $stmt->execute([$productId]);

    // Then add new discount if percentage is greater than 0
    if ($discountPercent > 0) {
        $stmt = $conn->prepare("
            INSERT INTO discounts (product_id, discount_percent, start_date) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$productId, $discountPercent]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Discount updated successfully']);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Error updating discount: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}