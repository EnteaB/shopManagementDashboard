<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once '../includes/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        
        // Check if category has products
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $response['message'] = 'Cannot delete category with existing products';
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Category deleted successfully';
            } else {
                $response['message'] = 'Error deleting category';
            }
        }
        $stmt->close();
    } else {
        $name = trim($_POST['categoryName']);
        $id = isset($_POST['categoryId']) ? $_POST['categoryId'] : null;
        
        if (empty($name)) {
            $response['message'] = 'Category name is required';
        } else {
            if ($id) {
                // Update existing category
                $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->bind_param("si", $name, $id);
            } else {
                // Add new category
                $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->bind_param("s", $name);
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $id ? 'Category updated successfully' : 'Category added successfully';
            } else {
                $response['message'] = 'Error processing category';
            }
            $stmt->close();
        }
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>