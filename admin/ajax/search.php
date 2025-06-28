<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$term = isset($_GET['term']) ? $_GET['term'] : '';
$response = ['success' => false, 'results' => []];

if (strlen($term) >= 3) {
    // Search users
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username LIKE ?");
    $searchTerm = "%$term%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search products
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE name LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search categories
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE name LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $response = [
        'success' => true,
        'results' => [
            'users' => $users,
            'products' => $products,
            'categories' => $categories
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);