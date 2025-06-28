<?php

session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// Verify admin access
requireAdmin();

header('Content-Type: application/json');

try {
    // Validate input
    if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['category']) || 
        empty($_POST['price']) || empty($_POST['stock'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Sanitize inputs
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['name']));
    $category = htmlspecialchars(trim($_POST['category']));
    $size = htmlspecialchars(trim($_POST['size']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);

    // Generate slug from name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . time();
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Check if image was uploaded
    if (!empty($_FILES['image']['name'])) {
        // Get current image to delete later
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $oldImage = $stmt->fetchColumn();
        
        // Upload new image
        $uploadDir = '../../uploads/';
        $fileName = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            // Update product with new image
            $stmt = $conn->prepare("UPDATE products SET 
                name = ?, slug = ?, description = ?, price = ?, 
                stock = ?, subcategory = ?, size = ?, image = ?, 
                updated_at = NOW() 
                WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $price, $stock, $category, $size, $fileName, $id]);
            
            // Delete old image
            if ($oldImage && file_exists($uploadDir . $oldImage)) {
                @unlink($uploadDir . $oldImage);
            }
        } else {
            throw new Exception('Failed to upload image');
        }
    } else {
        // Update product without changing image
        $stmt = $conn->prepare("UPDATE products SET 
            name = ?, slug = ?, description = ?, price = ?, 
            stock = ?, subcategory = ?, size = ?, 
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $price, $stock, $category, $size, $id]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Update Product Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>