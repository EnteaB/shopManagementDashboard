<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Validate product ID
    if (empty($_POST['id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($productId === false) {
        throw new Exception('Invalid product ID');
    }

    // Get product image before deletion
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute(['id' => $productId]);

    // Delete image file if it exists
    if ($product['image']) {
        $imagePath = '../../uploads/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully'
    ]);

} catch (Exception $e) {
    error_log("Error deleting product: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}