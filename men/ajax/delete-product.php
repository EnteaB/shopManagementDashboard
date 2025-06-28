<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['id'])) {
        throw new Exception('Product ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get the product image filename before deletion
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND category = 'male'");
    $stmt->execute([$_POST['id']]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND category = 'male'");
    $stmt->execute([$_POST['id']]);

    if ($stmt->rowCount() > 0) {
        // Delete the image file if it exists
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
    } else {
        throw new Exception('Failed to delete product');
    }

} catch (Exception $e) {
    error_log("Error deleting product: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}