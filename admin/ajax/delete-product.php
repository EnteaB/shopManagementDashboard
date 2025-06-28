<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Ensure only admin can delete products
requireAdmin();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$productId = (int) $_GET['id'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // First, check if the product exists
    $checkStmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $checkStmt->execute([$productId]);
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete any discounts first (foreign key constraint)
    $discountStmt = $conn->prepare("DELETE FROM discounts WHERE product_id = ?");
    $discountStmt->execute([$productId]);
    
    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    // Commit transaction
    $conn->commit();
    
    // Delete the product image from the filesystem
    if (!empty($product['image'])) {
        $imagePath = "../../uploads/" . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>