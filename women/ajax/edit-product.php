<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    if (empty($_POST['id'])) {
        throw new Exception('Product ID is required');
    }

    // Handle file upload if new image is provided
    $fileName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../../uploads/';
        $fileInfo = pathinfo($_FILES['image']['name']);
        $fileName = uniqid() . '.' . $fileInfo['extension'];
        $uploadFile = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            throw new Exception('Failed to upload new image');
        }
    }

    // Update product
    $sql = "UPDATE products SET 
            name = :name,
            subcategory = :subcategory,
            size = :size,
            description = :description,
            price = :price,
            stock = :stock";
    
    if ($fileName) {
        $sql .= ", image = :image";
    }
    
    $sql .= " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    
    $params = [
        'id' => $_POST['id'],
        'name' => $_POST['name'],
        'subcategory' => $_POST['category'],
        'size' => strtoupper($_POST['size']),
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock' => $_POST['stock']
    ];

    if ($fileName) {
        $params['image'] = $fileName;
    }

    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Error updating product: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}