<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify women manager access
requireAdmin();

// Define product categories
$categories = [
    'dresses' => ['icon' => 'fa-dress', 'title' => 'Dresses', 'gender' => 'female'],
    'tops' => ['icon' => 'fa-tshirt', 'title' => 'Tops & T-Shirts', 'gender' => 'both'],
    'bottoms' => ['icon' => 'fa-socks', 'title' => 'Pants & Skirts', 'gender' => 'both'],
    'outerwear' => ['icon' => 'fa-vest', 'title' => 'Jackets & Coats', 'gender' => 'both'],
    'accessories' => ['icon' => 'fa-gem', 'title' => 'Accessories', 'gender' => 'both'],
    'suits' => ['icon' => 'fa-user-tie', 'title' => 'Suits', 'gender' => 'male']
];

// Get selected category and gender from URL
$selectedCategory = $_GET['category'] ?? 'all';
$selectedGender = $_GET['gender'] ?? 'all';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get products based on category and gender
    if ($selectedCategory !== 'all' && $selectedGender !== 'all') {
        $stmt = $conn->prepare("
            SELECT p.*, d.discount_percent 
            FROM products p 
            LEFT JOIN discounts d ON p.id = d.product_id 
                AND CURRENT_TIMESTAMP BETWEEN d.start_date AND d.end_date
            WHERE p.category = :gender AND p.subcategory = :subcategory
            ORDER BY p.created_at DESC
        ");
        $stmt->execute(['gender' => $selectedGender, 'subcategory' => $selectedCategory]);
    } 
    elseif ($selectedGender !== 'all') {
        $stmt = $conn->prepare("
            SELECT p.*, d.discount_percent 
            FROM products p 
            LEFT JOIN discounts d ON p.id = d.product_id 
                AND CURRENT_TIMESTAMP BETWEEN d.start_date AND d.end_date
            WHERE p.category = :gender
            ORDER BY p.created_at DESC
        ");
        $stmt->execute(['gender' => $selectedGender]);
    }
    else {
        $stmt = $conn->prepare("
            SELECT p.*, d.discount_percent 
            FROM products p 
            LEFT JOIN discounts d ON p.id = d.product_id 
                AND CURRENT_TIMESTAMP BETWEEN d.start_date AND d.end_date
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

        /* Update the logo styling */
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 2.5rem;
        }

        /* Update nav links to match products page */
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

        .logo i,
        .nav-link i {
            color: white;
        }

        .nav-link i {
            font-size: 1rem; /* Match icon size */
            width: 1.5rem; /* Fixed width for icons */
            text-align: center; /* Center icons */
        }

        /* Remove the old logout button styles */
        /* And add these new styles */
.nav-link[href="../logout.php"] {
    margin-top: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.nav-link[href="../logout.php"]:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #fff;
}

.nav-link[href="../logout.php"] i {
    color: rgba(255, 255, 255, 0.9);
}

/* Add these styles for the logout button */
.logout-btn {
    position: fixed;
    bottom: 2rem;
    left: 2rem;
    width: calc(250px - 4rem);
    padding: 0.75rem;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.logout-btn:hover {
    background: #dc2626;
}

/* To ensure the nav doesn't overlap with the logout button */
.sidebar nav {
    margin-bottom: 4rem;
}

        /* Update the logout button to match */
        /* .logout-btn {
            position: fixed;
            bottom: 1.5rem; /* Less space from bottom 
            left: 1rem; /* Match sidebar padding 
            width: calc(250px - 2rem); /* Match sidebar width 
            padding: 0.75rem 1rem;
            background: rgba(239, 68, 68, 0.9); /* More transparent red 
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .logout-btn:hover {
            background: #dc2626;
        } */

        .main-content {
            padding: 4rem 2rem;
            margin-left: 0;
            width: 100%;
            transition: all 0.3s ease;
        }

        /* Update the main content shifted state */
        .main-content.shifted {
            padding-left: calc(8rem + 250px);
        }

        /* Update the menu toggle button styles to match products page */
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

        /* Products Header Styles */
      /* Update Products Header Styles */
.products-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #818cf8 100%);
    padding: 1.5rem;
    border-radius: 1rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.2);
    width: 110%;
    margin: 0 auto 2rem auto;
}

.header-title {
    color: white;
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.025em;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
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
            gap: 0.75rem;
            width: 110%;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin: 0 auto 1.5rem auto;
            overflow-x: auto;
            scrollbar-width: none;
            position: relative;
        }

        .category-nav::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 30px;
            background: linear-gradient(to right, transparent, white);
            pointer-events: none;
        }

        .category-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 2rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            color: var(--gray-800);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: auto;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .category-btn:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 100%);
            color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        /* Enhanced gender filter styling */
.gender-filter {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.5rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    width: 110%;
    margin: 0 auto 1.5rem auto;
}

.gender-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 2rem;
    border-radius: 0.75rem;
    text-decoration: none;
    color: var(--gray-800);
    font-weight: 500;
    transition: all 0.3s ease;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.gender-btn i {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.gender-btn:hover {
    background: var(--gray-100);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.gender-btn:hover i {
    transform: scale(1.2);
}

.gender-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Search and Sort Styles */
.search-sort-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 110%;
    margin: 0 auto 1.5rem auto;
    gap: 1rem;
}

.search-bar {
    flex: 1;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.search-bar:focus-within {
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.15);
}

.search-bar input {
    flex: 1;
    border: none;
    outline: none;
    padding: 0.5rem;
    font-size: 0.9rem;
    color: var(--gray-800);
    background: transparent;
}

.search-bar button {
    background: none;
    border: none;
    color: var(--primary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.search-bar button:hover {
    background: rgba(79, 70, 229, 0.1);
    transform: scale(1.1);
}

.sort-dropdown {
    min-width: 200px;
    position: relative;
    background: white;
    border-radius: 0.75rem;
    padding: 0.25rem 0.5rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.sort-dropdown select {
    width: 100%;
    appearance: none;
    background: transparent;
    border: none;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    color: var(--gray-800);
    cursor: pointer;
    outline: none;
}

.sort-dropdown i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    pointer-events: none;
}

@media (max-width: 768px) {
    .search-sort-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .search-bar {
        width: 100%;
    }
    
    .sort-dropdown {
        width: 100%;
        min-width: auto;
    }
}
        /* Make product images smaller and improve layout */
.products-section {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
    padding: 1rem 0;
}

.product-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 85%; /* Make images shorter */
    overflow: hidden;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
}

/* Improve discount badge */
.discount-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: var(--primary);
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.35rem 0.75rem;
    border-radius: 1rem;
    z-index: 5;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
    transform-origin: center;
    transition: transform 0.3s ease;
}

.product-card:hover .discount-badge {
    transform: scale(1.1);
}

/* Improve product info section */
.product-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.25rem 0;
}

.current-price {
    font-weight: 600;
    color: var(--primary-dark);
    font-size: 1.1rem;
}

.original-price {
    text-decoration: line-through;
    color: var(--secondary);
    font-size: 0.85rem;
}

.discounted-price {
    font-weight: 600;
    color: var(--primary-dark);
    font-size: 1.1rem;
}

.product-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-800);
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.product-stock i {
    color: var(--primary);
    font-size: 0.9rem;
}

        .category-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transform: translateY(-2px);
        }

        /* Enhanced product cards */
        .product-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            padding-top: 100%;
            overflow: hidden;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.08);
        }

        .product-info {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-actions {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.5rem;
            z-index: 10;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .action-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .delete-btn {
            background: var(--danger);
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.15);
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
/* Add these styles for the out-of-stock indicator */
.out-of-stock-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: #ef4444;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.35rem 0.75rem;
    border-radius: 1rem;
    z-index: 5;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.out-of-stock-badge i {
    font-size: 0.8rem;
}

.stock-warning {
    color: #ef4444;
    font-weight: 500;
}

.out-of-stock .product-image-wrapper {
    opacity: 0.8;
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
/* Professional styling for Add Product button */
.add-product-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.add-product-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.add-product-btn:active {
    transform: translateY(1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.add-product-btn i {
    font-size: 1.1rem;
}

/* Add a subtle shine effect on hover */
.add-product-btn::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        to bottom right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.1) 100%
    );
    transform: rotate(30deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.add-product-btn:hover::before {
    transform: rotate(30deg) translate(10%, 10%);
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

/* Add these styles to make the edit product image smaller */
.current-image {
    width: 100%;
    display: flex;
    justify-content: center;
    margin: 0.5rem 0;
}

.current-image img {
    max-width: 120px;  /* Make the image smaller */
    max-height: 120px;
    object-fit: contain;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* Improve file input styling */
#editImage {
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

/* Make the entire edit form more compact */
#editProductModal .form-group {
    margin: 0.5rem 1rem;
}

#editProductModal .form-group label {
    font-size: 0.85rem;
}

#editProductModal textarea {
    min-height: 60px;
}

/* Add these styles for the discount button */
.discount-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.discount-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
}

/* Loading indicator styles */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(79, 70, 229, 0.3);
    border-radius: 50%;
    border-top-color: var(--primary);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Style for products with discount */
.product-card.has-discount {
    box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.3), 0 4px 15px rgba(0, 0, 0, 0.1);
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
                <i class="fas fa-bag"></i>
               FashShop
            </div>
            <!-- Update the navigation section -->
<nav>
    <a href="dashboard.php" class="nav-link">
        <i class="fas fa-home"></i>
        Dashboard
    </a>
    <a href="products.php" class="nav-link active">
        <i class="fas fa-tshirt"></i>
        Products
    </a>
    <a href="users.php" class="nav-link">
        <i class="fas fa-users"></i>
        Users
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

            <div class="gender-filter">
                <a href="?gender=all" class="gender-btn <?= $selectedGender === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    All
                </a>
                <a href="?gender=female" class="gender-btn <?= $selectedGender === 'female' ? 'active' : '' ?>">
                    <i class="fas fa-female"></i>
                    Women
                </a>
                <a href="?gender=male" class="gender-btn <?= $selectedGender === 'male' ? 'active' : '' ?>">
                    <i class="fas fa-male"></i>
                    Men
                </a>
            </div>

            <nav class="category-nav">
                <a href="?category=all" 
                   class="category-btn <?= $selectedCategory === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-border-all"></i>
                
                </a>
                <?php foreach ($categories as $key => $category): ?>
                    <a href="?category=<?= $key ?>" 
                       class="category-btn <?= $selectedCategory === $key ? 'active' : '' ?>">
                        <i class="fas <?= $category['icon'] ?>"></i>
                        <?= $category['title'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Add this after the category-nav section -->
<div class="search-sort-container">
    <div class="search-bar">
        <input type="text" id="productSearch" placeholder="Search products...">
        <button id="searchButton">
            <i class="fas fa-search"></i>
        </button>
    </div>
    <div class="sort-dropdown">
        <select id="productSort">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="price_low">Price: Low to High</option>
            <option value="price_high">Price: High to Low</option>
            <option value="name_asc">Name: A to Z</option>
            <option value="name_desc">Name: Z to A</option>
        </select>
        <i class="fas fa-sort"></i>
    </div>
</div>


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
                    <div class="product-card <?= !empty($product['discount_percent']) ? 'has-discount' : '' ?>" onclick="viewProductDetail(<?= $product['id'] ?>)">
                        <div class="product-image-wrapper">
                            <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="product-image">
                            <?php if (!empty($product['discount_percent'])): ?>
                                <div class="discount-badge">
                                    <?= $product['discount_percent'] ?>% OFF
                                </div>
                            <?php endif; ?>
                             <?php if ($product['stock'] <= 0): ?>
                    <div class="out-of-stock-badge">
                        <i class="fas fa-exclamation-circle"></i> Restock
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
                           <div class="product-stock <?= $product['stock'] <= 0 ? 'stock-warning' : '' ?>">
                    <i class="fas <?= $product['stock'] <= 0 ? 'fa-exclamation-circle' : 'fa-box' ?>"></i>
                    Stock: <?= $product['stock'] ?>
                </div>
                            
                            <div class="product-actions" onclick="event.stopPropagation()">
                                <button class="action-btn discount-btn" onclick="manageDiscount(<?= $product['id'] ?>, <?= isset($product['discount_percent']) ? $product['discount_percent'] : 0 ?>)" title="Set discount">
                                    <i class="fas fa-percent"></i>
                                </button>
                                <button class="action-btn edit-btn" onclick="editProduct(<?= $product['id'] ?>)" title="Edit product">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteProduct(<?= $product['id'] ?>)" title="Delete product">
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
    <label for="productGender">Gender Category</label>
    <select id="productGender" name="gender" required>
        <option value="">Select Gender</option>
        <option value="female">Women's</option>
        <option value="male">Men's</option>
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
// Replace the existing addProductForm event listener with this one
document.getElementById('addProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        
        // Get the gender directly from the gender select field
        const genderSelect = document.getElementById('productGender');
        if (genderSelect && genderSelect.value) {
            // Use the selected gender value instead of determining it from the category
            formData.set('gender', genderSelect.value);
        }

        const response = await fetch('ajax/add-product.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Invalid server response');
        }
        
        if (data.success) {
            alert('Product added successfully');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add product: ' + error.message);
    } finally {
        submitBtn.disabled = false;
    }
});

function editProduct(id) {
    if (!id) {
        alert('Invalid product ID');
        return;
    }
    
    // Fetch the product details
    fetch(`ajax/get-product.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(product => {
            // Fill the edit form with product data
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editCategory').value = product.subcategory;
            document.getElementById('editSize').value = product.size;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editStock').value = product.stock;
            
            // Display current image
            const currentImage = document.getElementById('currentImage');
            if (currentImage) {
                currentImage.src = `../uploads/${product.image}`;
            }
            
            // Show the modal
            document.getElementById('editProductModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching product details:', error);
            alert('Failed to load product details: ' + error.message);
        });
}


        // Add this event listener for the edit product form
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    const formData = new FormData(this);
    const productId = document.getElementById('editProductId').value;
    
    fetch('ajax/update-product.php', {
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
            alert('Product updated successfully');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Failed to update product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update product: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
    });
});

// Function to close the edit modal
function closeEditModal() {
    document.getElementById('editProductModal').style.display = 'none';
}

// Add this function to your JavaScript section
function manageDiscount(id, currentDiscount = 0) {
    if (!id) {
        alert('Invalid product ID');
        return;
    }
    
    const discountValue = prompt("Enter discount percentage (0-99):", currentDiscount);
    
    if (discountValue === null) {
        return; // User cancelled
    }
    
    // Validate input
    const discount = parseInt(discountValue);
    if (isNaN(discount) || discount < 0 || discount > 99) {
        alert("Please enter a valid discount percentage between 0 and 99");
        return;
    }
    
    // Create form data for the request
    const formData = new FormData();
    formData.append('product_id', id);
    formData.append('discount', discount);
    
    // Show loading indicator
    const loadingEl = document.createElement('div');
    loadingEl.className = 'loading-overlay';
    loadingEl.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loadingEl);
    
    fetch('ajax/set-discount.php', {
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
            alert(discount > 0 ? 
                `Discount of ${discount}% set successfully!` : 
                'Discount removed successfully!');
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to set discount');
        }
    })
    .catch(error => {
        console.error('Discount error:', error);
        alert('Failed to set discount: ' + error.message);
    })
    .finally(() => {
        // Remove loading indicator
        document.body.removeChild(loadingEl);
    });
}

// Add this function to your JavaScript section
function deleteProduct(id) {
    if (!id) {
        alert('Invalid product ID');
        return;
    }
    
    // Confirm before deleting
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        return;
    }
    
    // Show loading indicator
    const loadingEl = document.createElement('div');
    loadingEl.className = 'loading-overlay';
    loadingEl.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loadingEl);
    
    fetch(`ajax/delete-product.php?id=${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
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
            // Fade out the product card before removing it
            const productCard = document.querySelector(`.product-card[data-id="${id}"]`);
            if (productCard) {
                productCard.style.opacity = '0';
                productCard.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    productCard.remove();
                }, 300);
            } else {
                // If we can't find the card, just reload the page
                window.location.reload();
            }
        } else {
            throw new Error(data.message || 'Failed to delete product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete product: ' + error.message);
    })
    .finally(() => {
        // Remove loading indicator
        document.body.removeChild(loadingEl);
    });
}

// Add this function to your JavaScript section at the bottom of the file
function viewProductDetail(id) {
    if (!id) {
        return;
    }
    
    // Show loading state
    const modal = document.getElementById('productDetailModal');
    modal.style.display = 'flex';
    document.getElementById('detailName').textContent = 'Loading...';
    
    // Fetch product details
    fetch(`ajax/get-product.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(product => {
            // Fill the modal with product data
            document.getElementById('detailName').textContent = product.name;
            document.getElementById('detailImage').src = `../uploads/${product.image}`;
            document.getElementById('detailDescription').textContent = product.description || 'No description available';
            document.getElementById('detailStock').textContent = `In Stock: ${product.stock}`;
            
            // Get the category name
            const categories = <?php echo json_encode($categories); ?>;
            const categoryName = categories[product.subcategory]?.title || product.subcategory;
            document.getElementById('detailCategory').textContent = `${product.category === 'male' ? 'Men\'s' : 'Women\'s'} ${categoryName}`;
            
            document.getElementById('detailSize').textContent = `Size: ${product.size}`;
            
            // Handle pricing and discount
            if (product.discount_percent) {
                const originalPrice = parseFloat(product.price);
                const discountedPrice = originalPrice * (1 - product.discount_percent/100);
                
                document.getElementById('detailPrice').textContent = `€${discountedPrice.toFixed(2)}`;
                document.getElementById('detailOriginalPrice').textContent = `€${originalPrice.toFixed(2)}`;
                document.getElementById('detailOriginalPrice').style.display = 'inline';
                
                document.getElementById('detailDiscount').textContent = `${product.discount_percent}% OFF`;
                document.getElementById('detailDiscount').style.display = 'inline';
            } else {
                document.getElementById('detailPrice').textContent = `€${parseFloat(product.price).toFixed(2)}`;
                document.getElementById('detailOriginalPrice').style.display = 'none';
                document.getElementById('detailDiscount').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading product details:', error);
            document.getElementById('detailName').textContent = 'Error loading product';
            document.getElementById('detailDescription').textContent = 'Failed to load product details: ' + error.message;
        });
}

// Close product detail modal
function closeProductDetail() {
    document.getElementById('productDetailModal').style.display = 'none';
}

/* Add this JavaScript to make search and sort work */
document.addEventListener('DOMContentLoaded', function() {
    const productSearch = document.getElementById('productSearch');
    const searchButton = document.getElementById('searchButton');
    const productSort = document.getElementById('productSort');
    const productsSection = document.querySelector('.products-section');
    
    // Initialize products array from DOM
    let products = Array.from(document.querySelectorAll('.product-card'));
    let originalOrder = [...products]; // Keep original order for reference
    
    // Search functionality
    function searchProducts() {
        const searchTerm = productSearch.value.toLowerCase().trim();
        
        products.forEach(product => {
            const productName = product.querySelector('.product-name').textContent.toLowerCase();
            const productDescription = product.querySelector('.product-description')?.textContent?.toLowerCase() || '';
            const isMatch = productName.includes(searchTerm);
            product.style.display = isMatch ? 'flex' : 'none';
        });
        
        // Show "no products found" message if needed
        checkEmptyResults();
    }
    
    // Sort functionality
    function sortProducts() {
        const sortValue = productSort.value;
        const visibleProducts = products.filter(p => p.style.display !== 'none');
        
        visibleProducts.sort((a, b) => {
            switch(sortValue) {
                case 'newest':
                    // Use original order for newest first
                    return originalOrder.indexOf(a) - originalOrder.indexOf(b);
                
                case 'oldest':
                    // Reverse of newest
                    return originalOrder.indexOf(b) - originalOrder.indexOf(a);
                
                case 'price_low':
                    return getPriceValue(a) - getPriceValue(b);
                
                case 'price_high':
                    return getPriceValue(b) - getPriceValue(a);
                
                case 'name_asc':
                    const nameA = a.querySelector('.product-name').textContent.toLowerCase();
                    const nameB = b.querySelector('.product-name').textContent.toLowerCase();
                    return nameA.localeCompare(nameB);
                
                case 'name_desc':
                    const nameDescA = a.querySelector('.product-name').textContent.toLowerCase();
                    const nameDescB = b.querySelector('.product-name').textContent.toLowerCase();
                    return nameDescB.localeCompare(nameDescA);
                
                default:
                    return 0;
            }
        });
        
        // Remove all products and reappend in sorted order
        visibleProducts.forEach(product => productsSection.appendChild(product));
    }
    
    // Helper function to get product price value (handles discounted price)
    function getPriceValue(productEl) {
        // Look for discounted price first
        const discountedPrice = productEl.querySelector('.discounted-price');
        if (discountedPrice) {
            return parseFloat(discountedPrice.textContent.replace('€', '').trim());
        }
        
        // Otherwise, get the regular price
        const regularPrice = productEl.querySelector('.current-price');
        if (regularPrice) {
            return parseFloat(regularPrice.textContent.replace('€', '').trim());
        }
        
        return 0; // Fallback
    }
    
    // Check if there are no visible products and show message if needed
    function checkEmptyResults() {
        const visibleProducts = products.filter(p => p.style.display !== 'none');
        
        let emptyState = document.querySelector('.empty-search-results');
        
        if (visibleProducts.length === 0) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'empty-search-results';
                emptyState.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>No products found</h3>
                    <p>Try a different search term or clear the search</p>
                    <button id="clearSearch" class="clear-search-btn">Clear Search</button>
                `;
                productsSection.appendChild(emptyState);
                
                // Add event listener to clear search button
                document.getElementById('clearSearch').addEventListener('click', () => {
                    productSearch.value = '';
                    searchProducts();
                    sortProducts();
                });
            }
        } else if (emptyState) {
            emptyState.remove();
        }
    }
    
    // Event listeners
    searchButton.addEventListener('click', () => {
        searchProducts();
        sortProducts();
    });
    
    productSearch.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
            sortProducts();
        }
        // Auto-search after typing
        if (productSearch.value.length >= 2) {
            searchProducts();
            sortProducts();
        }
    });
    
    productSort.addEventListener('change', sortProducts);
    
    // Initialize
    sortProducts();
});

// Add this function to handle out of stock products with discount prompt
function restockOrDiscount(id, stock) {
    if (!id) {
        alert('Invalid product ID');
        return;
    }
    
    // If stock is already 0, offer discount options
    if (stock <= 0) {
        const actionChoice = confirm("This product is out of stock. Would you like to add a discount to attract pre-orders?");
        
        if (actionChoice) {
            // User wants to set a discount
            const discountValue = prompt("Enter discount percentage for pre-orders (0-99):", "15");
            
            if (discountValue === null) {
                return; // User cancelled
            }
            
            // Validate input
            const discount = parseInt(discountValue);
            if (isNaN(discount) || discount < 0 || discount > 99) {
                alert("Please enter a valid discount percentage between 0 and 99");
                return;
            }
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('product_id', id);
            formData.append('discount', discount);
            formData.append('is_preorder', true); // Flag as pre-order discount
            
            // Show loading indicator
            const loadingEl = document.createElement('div');
            loadingEl.className = 'loading-overlay';
            loadingEl.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingEl);
            
            fetch('ajax/set-discount.php', {
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
                    alert(`Pre-order discount of ${discount}% set successfully!`);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to set discount');
                }
            })
            .catch(error => {
                console.error('Discount error:', error);
                alert('Failed to set discount: ' + error.message);
            })
            .finally(() => {
                // Remove loading indicator
                document.body.removeChild(loadingEl);
            });
        } else {
            // User chose not to set a discount, prompt for restock instead
            const restockAmount = prompt("Enter quantity to restock:", "10");
            
            if (restockAmount === null) {
                return; // User cancelled
            }
            
            // Validate input
            const quantity = parseInt(restockAmount);
            if (isNaN(quantity) || quantity <= 0) {
                alert("Please enter a valid quantity greater than 0");
                return;
            }
            
            // Show loading indicator
            const loadingEl = document.createElement('div');
            loadingEl.className = 'loading-overlay';
            loadingEl.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingEl);
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('id', id);
            formData.append('stock', quantity);
            
            fetch('ajax/update-product-stock.php', {
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
                    alert(`Product restocked with ${quantity} units successfully!`);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to restock product');
                }
            })
            .catch(error => {
                console.error('Restock error:', error);
                alert('Failed to restock product: ' + error.message);
            })
            .finally(() => {
                // Remove loading indicator
                document.body.removeChild(loadingEl);
            });
        }
    }
}
    </script>
</body>
</html>
