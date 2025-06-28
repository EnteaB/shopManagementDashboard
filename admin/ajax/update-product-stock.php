<?php
// filepath: c:\xampp\htdocs\FashShop\admin\ajax\update-product-stock.php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Ensure user is admin
requireAdmin();

// Check if required data is provided
if (!isset($_POST['id']) || !isset($_POST['stock'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$productId = (int)$_POST['id'];
$stock = (int)$_POST['stock'];

// Validate inputs
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if ($stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock cannot be negative']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Update only the stock field
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $result = $stmt->execute([$stock, $productId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product stock updated successfully']);
    } else {
        throw new Exception('Failed to update stock');
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}