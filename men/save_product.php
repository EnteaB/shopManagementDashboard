<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/image_utils.php';

// Add function to generate slug
function generateSlug($name) {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Verify men manager access
requireMenManager();

// Debug logging
error_log("Received POST data: " . print_r($_POST, true));
error_log("Received FILES data: " . print_r($_FILES, true));

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Handle file upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($filetype, $allowed)) {
            throw new Exception('Invalid image format');
        }

        $newname = uniqid() . '.' . $filetype;
        $upload_dir = '../assets/images/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $upload_path = $upload_dir . $newname;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'assets/images/products/' . $newname;
        } else {
            throw new Exception('Failed to upload image');
        }
    }

    // Prepare data for database
    $id = isset($_POST['id']) ? trim($_POST['id']) : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $slug = generateSlug($name);

    // Add uniqueness to slug if needed
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?? 0]);
        if (!$stmt->fetch()) break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Database operation
    if ($id) {
        // Update existing product
        $sql = "UPDATE products SET 
                name = ?, 
                description = ?, 
                price = ?,
                stock = ?,
                category = 'male',
                slug = ?";
        
        $params = [$name, $description, $price, $stock, $slug];
        
        // Only update image if new one was uploaded
        if ($image_path) {
            $sql .= ", image = ?";
            $params[] = $image_path;
        }
        
        $sql .= " WHERE id = ? AND category = 'male'";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        // Insert new product
        $stmt = $conn->prepare("
            INSERT INTO products (name, slug, description, price, stock, image, category_type, sizes) 
            VALUES (?, ?, ?, ?, ?, ?, 'male', ?)
        ");
        
        // Add sizes handling
        $sizes = isset($_POST['sizes']) ? json_encode($_POST['sizes']) : '[]';
        
        $stmt->execute([
            $name, 
            $slug, 
            $description, 
            $price, 
            $stock, 
            $image_path, 
            $sizes
        ]);
    }

    // Get the inserted/updated product for the response
    if (!$id) {
        $id = $conn->lastInsertId();
    }

    // Fetch the complete product data
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['message'] = $id ? 'Product updated successfully' : 'Product added successfully';
    $response['product'] = $product;

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Error in save_product.php: " . $e->getMessage());
}

// Debug image paths if needed
if (isset($_GET['debug'])) {
    echo "<div style='background: white; padding: 20px; margin: 20px;'>";
    echo "<h3>Debug Information:</h3>";
    foreach ($products as $product) {
        $imagePath = $product['image'] ?? '';
        $fullImagePath = getImagePath($imagePath);
        echo "<div style='margin-bottom: 10px;'>";
        echo "Product: " . htmlspecialchars($product['name']) . "<br>";
        echo "Stored Path: " . htmlspecialchars($imagePath) . "<br>";
        echo "Full Path: " . htmlspecialchars($fullImagePath) . "<br>";
        echo "File exists: " . (file_exists('../' . $imagePath) ? 'Yes' : 'No') . "<br>";
        echo "</div>";
    }
    echo "</div>";
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);