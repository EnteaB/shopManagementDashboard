<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager_women') {
    header('Location: ../login.php');
    exit();
}

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

    // Check if product exists and belongs to women's category
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND category = 'female'");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Update or insert discount
    $stmt = $conn->prepare("
        INSERT INTO discounts (product_id, discount_percent)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE discount_percent = ?
    ");
    $stmt->execute([$productId, $discountPercent, $discountPercent]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}