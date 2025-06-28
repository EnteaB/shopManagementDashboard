<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify women manager access
requireWomenManager();

// Define product categories
$categories = [
    'dresses' => ['icon' => 'fa-dress', 'title' => 'Dresses'],
    'tops' => ['icon' => 'fa-tshirt', 'title' => 'Tops & T-Shirts'],
    'bottoms' => ['icon' => 'fa-socks', 'title' => 'Pants & Skirts'],
    'outerwear' => ['icon' => 'fa-vest', 'title' => 'Jackets & Coats'],
    'accessories' => ['icon' => 'fa-gem', 'title' => 'Accessories']
];

// Get selected category from URL
$selectedCategory = $_GET['category'] ?? 'all';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get products based on category
    if ($selectedCategory !== 'all') {
        $stmt = $conn->prepare("
            SELECT p.*, d.discount_percent 
            FROM products p 
            LEFT JOIN discounts d ON p.id = d.product_id 
                AND CURRENT_TIMESTAMP BETWEEN d.start_date AND d.end_date
            WHERE p.category = 'female' AND p.subcategory = :subcategory
            ORDER BY p.created_at DESC
        ");
        $stmt->execute(['subcategory' => $selectedCategory]);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, d.discount_percent 
            FROM products p 
            LEFT JOIN discounts d ON p.id = d.product_id 
                AND CURRENT_TIMESTAMP BETWEEN d.start_date AND d.end_date
            WHERE p.category = 'female'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Women's Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
       :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #818cf8;
            --secondary: #64748b;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-800: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg,rgb(189, 204, 243) 0%, #f0f5ff 100%);
            min-height: 100vh;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: auto 1fr;
            min-height: 100vh;
        }

        /* Sidebar styles */
 .sidebar {
            background: linear-gradient(135deg,rgb(225, 225, 225) 0%, #6366f1 100%);
            padding: 2rem;
            border-right: 1px solid var(--gray-200);
            position: fixed;
            height: 100vh;
            width: 250px;
            transition: all 0.3s ease;
            z-index: 1000;
            transform: translateX(-250px); /* Keep sidebar hidden by default */
        }

        .sidebar.active {
            transform: translateX(0); /* Show sidebar when active */
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 2.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .menu-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            background: white;
            border: none;
            padding: 0.4rem;
            border-radius: 0.4rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
        }

        .menu-toggle i {
            font-size: 0.875rem;
            color: var(--gray-800);
        }

        .main-content {
            padding: 4rem 8rem;
            margin-left: 0;
            width: 100%;
            transition: all 0.3s ease;
        }

        .main-content.shifted {
            padding-left: calc(8rem + 250px);
        }


        /* Products Header Styles */
      /* Update Products Header Styles */
.products-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #818cf8 100%);
    padding: 1rem;
    border-radius: 0.75rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
    width: 100%;
    margin-bottom: 1.5rem;
}

.header-title {
    color: white;
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: -0.025em;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 2;
    animation: titleAppear 0.6s ease-out forwards;
}

.header-design {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0.5rem 0;
    gap: 1rem;
}

.design-icon {
    color: white;
    font-size: 1.5rem;
    animation: iconPop 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes titleAppear {
    0% {
        opacity: 0;
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes iconPop {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}
        /* Add sparkle effect */
        .products-header::before,
        .products-header::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
            animation: sparkle 3s ease-in-out infinite;
        }

        .products-header::before {
            top: -50px;
            left: -50px;
            animation-delay: 0.5s;
        }

        .products-header::after {
            bottom: -50px;
            right: -50px;
            animation-delay: 1s;
        }

        @keyframes sparkle {
            0%, 100% {
                opacity: 0;
                transform: scale(0.5) translate(0, 0);
            }
            50% {
                opacity: 0.3;
                transform: scale(1) translate(50px, 50px);
            }
        }

        /* Add these new styles */
        .category-nav {
            display: flex;
            justify-content: flex-start;
            gap: 1rem; /* Increased gap between categories */
            width: 90%;
            padding: 1.25rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 0 auto 1.5rem auto;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .category-nav::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem; /* Increased gap between icon and text */
            padding: 0.75rem 1.25rem; /* Increased padding */
            border: 1px solid var(--gray-200);
            border-radius: 2rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            color: var(--gray-800);
            font-size: 0.9rem; /* Increased font size */
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: 140px; /* Added minimum width */
            justify-content: center; /* Center content */
        }

        .category-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .category-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3);
            transform: translateY(-2px);
        }

        .category-btn i {
            font-size: 1rem; /* Increased icon size */
        }

        .products-section {
            display: grid;
            width: 90%;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 2rem;
        }

        .product-card {
            background: var(--gray-50);
            border-radius: 1rem;
            width: 100%;
            overflow: hidden;
            transition: all 0.3s ease;
            margin: 0;
            margin-bottom: 1rem;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1.25rem 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 90%;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 1.5rem;
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-product-btn {
            padding: 0.6rem 1.25rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .add-product-btn:hover {
            background: var(--primary-dark);
        }

        /* Add these styles in your <style> section */
        .products-section {
            background: linear-gradient(135deg, #fff5f8 0%, #fff 100%);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: 1rem;
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            padding-top: 120%; /* 1:1.2 aspect ratio */
            overflow: hidden;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .discount-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 2px 8px rgba(255, 105, 180, 0.3);
            animation: bounce 1s ease infinite;
        }

        .product-info {
            padding: 1rem;
            background: linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 100%);
        }

        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .original-price {
            font-size: 0.9rem;
            color: var(--secondary);
            text-decoration: line-through;
            opacity: 0.7;
        }
   

.current-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: var(--gray-50);
}

        .discounted-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2563eb;
        }

        .product-stock {
            font-size: 0.8rem;
            color: var(--secondary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-stock::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .action-btn {
            padding: 0.4rem;
            border: none;
            border-radius: 0.5rem;
            background: var(--gray-50);
            color: var(--gray-800);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-3px);
            }
        }

        /* Category buttons enhancement */
        .category-btn {
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            border: 1px solid var(--gray-200);
            padding: 0.75rem 1.25rem;
            border-radius: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-size: 0.9rem;
            min-width: 140px;
        }

        .category-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3);
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

       /* Update the modal-content styles */
.modal-content {
    width: 70%; /* Reduced from 80% */
    max-width: 400px; /* Reduced from 500px */
    margin: 4rem auto;
    padding: 1rem; /* Reduced padding */
    border-radius: 0.75rem;
}

/* Update form elements to be more compact */
.form-group {
    margin: 0.5rem 0.75rem; /* Reduced margins */
}

.form-group label {
    font-size: 0.85rem; /* Smaller label text */
    margin-bottom: 0.25rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.5rem; /* Reduced padding */
    font-size: 0.85rem; /* Smaller input text */
}

/* Make the image preview smaller */
.current-image {
    width: 40%; /* Reduced from 50% */
    height: 150px; /* Reduced from 200px */
    margin: 8px auto; /* Center the image and reduce margin */
    display: block;
}

/* Update modal header */
.modal-header {
    padding: 0.75rem 1rem; /* Reduced padding */
}

.modal-header h2 {
    font-size: 1.1rem; /* Smaller header text */
}

/* Update modal footer */
.modal-footer {
    padding: 0.75rem 1rem; /* Reduced padding */
    gap: 0.5rem; /* Reduced gap between buttons */
}

.btn-cancel,
.btn-save {
    padding: 0.5rem 1rem; /* Reduced padding */
    font-size: 0.85rem; /* Smaller button text */
}

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--gray-800);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-800);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-800);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 1rem;
            color: var(--gray-800);
            background: var(--gray-50);
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-cancel,
        .btn-save {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        .btn-cancel:hover {
            background: var(--gray-300);
        }

        .btn-save {
            background: var(--primary);
            color: white;
        }

        .btn-save:hover {
            background: var(--primary-dark);
        }

        .image-preview {
            margin-top: 0.5rem;
            width: 100%;
            height: 200px;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            background-size: cover;
            background-position: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1200;
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 2rem auto;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn 0.3s ease-out;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            color: var(--gray-800);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-800);
        }

        .form-group {
            margin: 1rem 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 0.95rem;
        }

        .image-preview {
            margin-top: 1rem;
            width: 100%;
            max-height: 200px;
            border-radius: 0.5rem;
            overflow: hidden;
            display: none;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-cancel {
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            background: white;
            color: var(--gray-800);
            cursor: pointer;
        }

        .btn-save {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            background: var(--primary);
            color: white;
            cursor: pointer;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-10%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Update modal styles for smaller size */
        .modal-content {
            width: 80%;
            max-width: 500px;
            margin: 3rem auto;
        }

        .modal-header {
            padding: 1rem;
        }

        .modal-header h2 {
            font-size: 1.1rem;
        }

        .form-group {
            margin: 0.75rem 1rem;
        }

        .form-group label {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .image-preview {
            margin-top: 0.75rem;
            max-height: 150px;
        }

        .modal-footer {
            padding: 1rem;
            gap: 0.75rem;
        }

        .btn-cancel,
        .btn-save {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        textarea {
            min-height: 80px;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .action-btn {
            padding: 0.5rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        /* Product Detail Modal */
#productDetailModal {
    display: none;
    position: fixed;
    z-index: 2100;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.product-detail-modal .modal-content {
    width: 90%;
    max-width: 500px;
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
}

.product-detail-modal .close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-800);
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.product-detail-image {
    width: 100%;
    padding-top: 100%;
    position: relative;
    overflow: hidden;
    border-radius: 0.5rem;
}

.product-detail-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-detail-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0;
}

.detail-price-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.detail-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-dark);
}

.detail-original-price {
    font-size: 0.9rem;
    color: var(--secondary);
    text-decoration: line-through;
    opacity: 0.7;
}

.detail-discount-badge {
    background: var(--primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 600;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(255, 105, 180, 0.3);
}

.detail-stock,
.detail-category,
.detail-size {
    font-size: 0.9rem;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-stock i,
.detail-category i,
.detail-size i {
    font-size: 1.1rem;
    color: var(--primary);
}

.detail-description {
    font-size: 0.9rem;
    color: var(--gray-700);
}

.detail-description h3 {
    font-weight: 500;
    margin-bottom: 0.5rem;
}
    </style>
</head>
<body>
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-female"></i>
                Women's Fashion
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="products.php" class="nav-link active">
                    <i class="fas fa-tshirt"></i>
                    Products
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </nav>
            <a href="../logout.php" class="logout-btn nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </aside>

        <main class="main-content">
            <div class="products-header">
                <h1 class="header-title">Products</h1>
                <div class="header-design">
                    <div class="design-element"></div>
                    <i class="fas fa-tshirt design-icon"></i>
                    <div class="design-element"></div>
                </div>
               
            </div>

            <nav class="category-nav">
                <a href="?category=all" 
                   class="category-btn <?= $selectedCategory === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-border-all"></i>
                    All Products
                </a>
                <?php foreach ($categories as $key => $category): ?>
                    <a href="?category=<?= $key ?>" 
                       class="category-btn <?= $selectedCategory === $key ? 'active' : '' ?>">
                        <i class="fas <?= $category['icon'] ?>"></i>
                        <?= $category['title'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="category-header">
                <h2 class="category-title">
                    <i class="fas <?= $selectedCategory === 'all' ? 'fa-border-all' : $categories[$selectedCategory]['icon'] ?? '' ?>"></i>
                    <?= $selectedCategory === 'all' ? 'All Products' : ($categories[$selectedCategory]['title'] ?? 'Products') ?>
                </h2>
                <button class="add-product-btn" onclick="showAddProductModal()">
                    <i class="fas fa-plus"></i>
                    Add Product
                </button>
            </div>

            <div class="products-section">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="product-image">
                            <?php if (!empty($product['discount_percent'])): ?>
                                <div class="discount-badge">
                                    <?= $product['discount_percent'] ?>% OFF
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">
                                <?php if (!empty($product['discount_percent'])): ?>
                                    <span class="original-price">€<?= number_format($product['price'], 2) ?></span>
                                    <span class="discounted-price">
                                        €<?= number_format($product['price'] * (1 - $product['discount_percent']/100), 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="current-price">€<?= number_format($product['price'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-stock">
                                Stock: <?= $product['stock'] ?>
                            </div>
                            <div class="product-actions">
                                <button class="action-btn edit-btn" onclick="editProduct(<?= $product['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteProduct(<?= $product['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="close-btn" onclick="closeAddProductModal()">×</button>
            </div>
            <form id="addProductForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="productName">Product Name</label>
                    <input type="text" id="productName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $key => $category): ?>
                            <option value="<?= $key ?>"><?= $category['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="size">Size</label>
                    <select id="size" name="size" required>
                        <option value="">Select Size</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                        <option value="XXL">XXL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price (€)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="stock" min="0" required>
                </div>

                <div class="form-group">
                    <label for="productImage">Product Image</label>
                    <input type="file" id="productImage" name="image" accept="image/*" required>
                    <div id="imagePreview" class="image-preview"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddProductModal()">Cancel</button>
                    <button type="submit" class="btn-save">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add this before closing </body> tag -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <button class="close-btn" onclick="document.getElementById('editProductModal').style.display='none'">&times;</button>
            </div>
            <form id="editProductForm" enctype="multipart/form-data">
                <input type="hidden" id="editProductId" name="id">
                
                <div class="form-group">
                    <label for="editName">Product Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="editCategory">Category</label>
                    <select id="editCategory" name="category" required>
                        <?php foreach ($categories as $key => $category): ?>
                            <option value="<?= $key ?>"><?= $category['title'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editSize">Size</label>
                    <select id="editSize" name="size" required>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="editPrice">Price (€)</label>
                    <input type="number" id="editPrice" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="editStock">Stock</label>
                    <input type="number" id="editStock" name="stock" min="0" required>
                </div>

                <div class="form-group">
                    <label for="editImage">Product Image</label>
                    <input type="file" id="editImage" name="image" accept="image/*">
                    <div class="current-image">
                        <img id="currentImage" src="" alt="Current product image">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('editProductModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div id="productDetailModal" class="modal product-detail-modal">
        <div class="modal-content product-detail-content">
            <span class="close-btn" onclick="closeProductDetail()">&times;</span>
            <div class="product-detail-grid">
                <div class="product-detail-image">
                    <img id="detailImage" src="" alt="Product Image">
                </div>
                <div class="product-detail-info">
                    <h2 id="detailName" class="detail-name"></h2>
                    <div class="detail-price-section">
                        <span id="detailPrice" class="detail-price"></span>
                        <span id="detailOriginalPrice" class="detail-original-price"></span>
                        <span id="detailDiscount" class="detail-discount-badge"></span>
                    </div>
                    <div class="detail-stock">
                        <i class="fas fa-box"></i>
                        <span id="detailStock"></span>
                    </div>
                    <div class="detail-category">
                        <i class="fas fa-tag"></i>
                        <span id="detailCategory"></span>
                    </div>
                    <div class="detail-size">
                        <i class="fas fa-ruler"></i>
                        <span id="detailSize"></span>
                    </div>
                    <div class="detail-description">
                        <h3>Description</h3>
                        <p id="detailDescription"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const menuToggle = document.querySelector('.menu-toggle');
            
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('shifted');
                
                const isOpen = sidebar.classList.contains('active');
                localStorage.setItem('sidebarOpen', isOpen);
            });
        
            const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
            if (sidebarOpen) {
                sidebar.classList.add('active');
                mainContent.classList.add('shifted');
            }
        });

        function showAddProductModal() {
            const modal = document.getElementById('addProductModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeAddProductModal() {
            const modal = document.getElementById('addProductModal');
            const form = document.getElementById('addProductForm');
            const preview = document.getElementById('imagePreview');
            
            if (modal) modal.style.display = 'none';
            if (form) form.reset();
            if (preview) preview.style.display = 'none';
        }

        // Update the form submission handler
        document.getElementById('addProductForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('.btn-save');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Adding...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('ajax/add-product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to add product');
                }
                
                if (data.success) {
                    alert('Product added successfully!');
                    closeAddProductModal();
                    // Refresh the current category view
                    const currentCategory = new URLSearchParams(window.location.search).get('category') || 'all';
                    window.location.href = `products.php?category=${currentCategory}`;
                } else {
                    throw new Error(data.message || 'Failed to add product');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Add Product';
            }
        });

        // Update the image preview handler
        document.getElementById('productImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
                preview.style.display = 'none';
            }
        });

        function deleteProduct(id) {
            if (!id) {
                alert('Invalid product ID');
                return;
            }

            if (confirm('Are you sure you want to delete this product?')) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('ajax/delete-product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully');
                        // Refresh the page to show updated product list
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to delete product');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete product: ' + error.message);
                });
            }
        }

        // Add this to your existing <script> tag
        function editProduct(id) {
            // Fetch product details
            fetch(`ajax/get-product.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        
                        // Populate form fields
                        document.getElementById('editProductId').value = product.id;
                        document.getElementById('editName').value = product.name;
                        document.getElementById('editCategory').value = product.subcategory;
                        document.getElementById('editSize').value = product.size;
                        document.getElementById('editDescription').value = product.description;
                        document.getElementById('editPrice').value = product.price;
                        document.getElementById('editStock').value = product.stock;
                        
                        // Show current image
                        const currentImage = document.getElementById('currentImage');
                        currentImage.src = `../uploads/${product.image}`;
                        currentImage.style.display = 'block';
                        
                        // Show modal
                        document.getElementById('editProductModal').style.display = 'flex';
                    } else {
                        alert('Failed to load product details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load product details');
                });
        }

        // Add form submission handler for edit form
        document.getElementById('editProductForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('.btn-save');
            submitButton.disabled = true;
            submitButton.innerHTML = 'Saving...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('ajax/edit-product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Product updated successfully');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to update product');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Save Changes';
            }
        });

        // Add image preview for edit form
        document.getElementById('editImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('currentImage');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        function closeProductDetail() {
            const modal = document.getElementById('productDetailModal');
            if (modal) modal.style.display = 'none';
        }

        // Add this function to open the product detail modal
        function openProductDetail(product) {
            // Set product details
            document.getElementById('detailName').innerText = product.name;
            document.getElementById('detailPrice').innerText = `€${parseFloat(product.price).toFixed(2)}`;
            document.getElementById('detailStock').innerText = product.stock;
            document.getElementById('detailCategory').innerText = product.subcategory;
            document.getElementById('detailSize').innerText = product.size;
            document.getElementById('detailDescription').innerText = product.description;
            
            // Set image
            const detailImage = document.getElementById('detailImage');
            detailImage.src = `../uploads/${product.image}`;
            detailImage.alt = product.name;
            
            // Set discount details
            const discountBadge = document.getElementById('detailDiscount');
            if (product.discount_percent && product.discount_percent > 0) {
                discountBadge.innerText = `${product.discount_percent}% OFF`;
                discountBadge.style.display = 'inline-block';
            } else {
                discountBadge.style.display = 'none';
            }

            // Open modal
            const modal = document.getElementById('productDetailModal');
            if (modal) modal.style.display = 'flex';
        }
    </script>
</body>
</html>