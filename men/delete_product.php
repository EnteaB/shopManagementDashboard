<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Debug incoming data
error_log("POST data received: " . print_r($_POST, true));

try {
    // Check for product ID
    if (!isset($_POST['id'])) {
        throw new Exception('Product ID is required');
    }

    $id = intval($_POST['id']);
    if ($id <= 0) {
        throw new Exception('Invalid product ID');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // First verify it's a men's product
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND category = 'male'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Product not found or not in men\'s category');
    }

    // Delete associated discounts first
    $stmt = $conn->prepare("DELETE FROM discounts WHERE product_id = ?");
    $stmt->execute([$id]);

    // Then delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND category = 'male'");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete product');
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}