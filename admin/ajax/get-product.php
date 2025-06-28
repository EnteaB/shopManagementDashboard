<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Product ID is required');
    }

    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        throw new Exception('Invalid product ID');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    echo json_encode($product);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}