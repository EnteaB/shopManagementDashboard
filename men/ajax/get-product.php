<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    if (empty($_GET['id'])) {
        throw new Exception('Product ID is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE id = ? AND category = 'male'
    ");
    
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    echo json_encode([
        'success' => true,
        'product' => $product
    ]);

} catch (Exception $e) {
    error_log("Error fetching product: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}