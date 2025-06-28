<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Verify admin access
requireAdmin();

header('Content-Type: application/json');

// Check required fields
if (!isset($_POST['product_id']) || !isset($_POST['discount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize input
$productId = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
$discount = filter_var($_POST['discount'], FILTER_VALIDATE_INT);

if (!$productId || $discount === false || $discount < 0 || $discount > 99) {
    echo json_encode(['success' => false, 'message' => 'Invalid input values']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // First check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check if discounts table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'discounts'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create discounts table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS discounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            discount_percent INT NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    }
    
    // Delete any existing discount for this product
    $stmt = $conn->prepare("DELETE FROM discounts WHERE product_id = ?");
    $stmt->execute([$productId]);
    
    // If discount is greater than 0, add new discount
    if ($discount > 0) {
        // Set discount to start now and end in 30 days
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $conn->prepare("INSERT INTO discounts (product_id, discount_percent, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productId, $discount, $startDate, $endDate]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Discount updated successfully']);
    
} catch (PDOException $e) {
    // Roll back transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Discount Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>