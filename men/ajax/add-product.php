<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Validate inputs
    if (empty($_POST['name']) || empty($_POST['category']) || empty($_POST['size']) || 
        empty($_POST['description']) || empty($_POST['price']) || empty($_POST['stock'])) {
        throw new Exception('All fields are required');
    }

    // Generate unique slug from name
    function generateSlug($string) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
        return $slug . '-' . uniqid();
    }

    $slug = generateSlug($_POST['name']);

    // Handle file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        throw new Exception('Product image is required');
    }

    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileInfo = pathinfo($_FILES['image']['name']);
    $fileName = uniqid() . '.' . $fileInfo['extension'];
    $uploadFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        throw new Exception('Failed to upload image');
    }

    // Insert product with slug
    $stmt = $conn->prepare("
        INSERT INTO products (
            name, 
            slug,
            category,
            subcategory,
            size,
            description,
            price,
            stock,
            image,
            created_at
        ) VALUES (
            :name,
            :slug,
            'male',
            :subcategory,
            :size,
            :description,
            :price,
            :stock,
            :image,
            NOW()
        )
    ");

    $params = [
        'name' => $_POST['name'],
        'slug' => $slug,
        'subcategory' => $_POST['category'],
        'size' => strtoupper($_POST['size']),
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock' => $_POST['stock'],
        'image' => $fileName
    ];

    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully',
        'productId' => $conn->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log("Error adding product: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}