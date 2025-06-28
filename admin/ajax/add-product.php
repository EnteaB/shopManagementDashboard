<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

try {
    // Validate input
    if (empty($_POST['name']) || empty($_POST['category']) || 
        empty($_POST['price']) || empty($_POST['stock']) || 
        empty($_POST['description']) || empty($_POST['size']) ||
        empty($_POST['gender'])) {
        throw new Exception('All fields are required');
    }

    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $category = htmlspecialchars(trim($_POST['category']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    $description = htmlspecialchars(trim($_POST['description']));
    $size = htmlspecialchars(trim($_POST['size']));

    // Generate slug from name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    // Ensure slug uniqueness by adding a timestamp if needed
    $slug = $slug . '-' . time();

    // Handle image upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Product image is required');
    }

    $uploadDir = '../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageFileType, $allowedTypes)) {
        throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
    }

    $imageName = uniqid() . '.' . $imageFileType;
    $targetPath = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        throw new Exception('Failed to upload image');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO products (name, category, subcategory, price, stock, description, image, size, slug) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $name,
            $gender,
            $category,
            $price,
            $stock,
            $description,
            $imageName,
            $size,
            $slug
        ]);

        if (!$success) {
            throw new Exception('Failed to add product to database');
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully'
        ]);

    } catch (Exception $e) {
        // Rollback and cleanup
        $conn->rollBack();
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        throw $e;
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;