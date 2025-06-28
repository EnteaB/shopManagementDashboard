<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify women manager access
requireWomenManager();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'No product ID provided']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get product details ensuring it belongs to women's category
    $stmt = $conn->prepare("
        SELECT p.*, COALESCE(d.discount_percent, 0) as discount 
        FROM products p 
        LEFT JOIN discounts d ON p.id = d.product_id 
        WHERE p.id = ? AND p.category = 'female'
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (PDOException $e) {
    error_log("Error fetching product: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}