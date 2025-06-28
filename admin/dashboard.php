<?php
session_start();

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify admin access
requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Add this when creating a new product
function logAdminActivity($conn, $adminName, $type, $productId) {
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_log (admin_name, type, product_id)
        VALUES (:admin_name, :type, :product_id)
    ");
    
    $stmt->execute([
        'admin_name' => $adminName,
        'type' => $type,
        'product_id' => $productId
    ]);
}

// Get statistics
try {
    // Total products
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();

    // Total users
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetchColumn();

    // Products on sale
    $stmt = $conn->query("SELECT COUNT(DISTINCT product_id) FROM discounts WHERE end_date > NOW()");
    $productsOnSale = $stmt->fetchColumn();

    // Low stock products
    $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 10");
    $lowStock = $stmt->fetchColumn();

    // Recent products
    $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FashShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
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
        }

        /* Main layout fixes */
        .dashboard-container {
            display: grid;
            grid-template-columns: auto 1fr; /* Change from fixed width */
            min-height: 100vh;
        }

         .sidebar {
            background: linear-gradient(135deg, rgb(225, 225, 225) 0%, #6366f1 100%);
            padding: 2rem;
            border-right: 1px solid var(--gray-200);
            position: fixed;
            height: 100vh;
            width: 250px;
            z-index: 1000;
            transform: translateX(0); /* Keep sidebar visible by default */
            display: flex;
            flex-direction: column;
        }

        nav {
            flex: 1;
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

        .main-content {
            margin-left: 250px; /* Fixed margin for sidebar width */
            padding: 2rem 4rem;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #818cf8 100%);
            padding: 2.5rem;
            border-radius: 1.5rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 8px 32px rgba(99, 102, 241, 0.2),
                0 2px 8px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: headerAppear 0.6s ease-out;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .welcome-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .dashboard-logo {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: logoSpin 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .dashboard-logo i {
            font-size: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: iconPop 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .welcome-text {
            color: white;
        }

        .dashboard-title {
            font-size: 2.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: titleSlide 0.6s ease-out;
        }

        .last-login {
            display: none;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .theme-toggle, .notifications-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.75rem;
            border-radius: 14px;
            cursor: pointer;
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: buttonPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .theme-toggle:hover, .notifications-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: gradientShift 15s ease infinite;
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, transparent 50%, rgba(255,255,255,0.1) 100%);
            border-radius: 50%;
            transform: translate(30%, -50%);
            animation: glowPulse 4s ease-in-out infinite;
        }

        @keyframes titleSlide {
            0% {
                opacity: 0;
                transform: translateX(-30px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes logoSpin {
            0% {
                opacity: 0;
                transform: rotate(-180deg) scale(0.5);
            }
            100% {
                opacity: 1;
                transform: rotate(0) scale(1);
            }
        }

        @keyframes gradientShift {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes glowPulse {
            0%, 100% {
                opacity: 0.5;
                transform: translate(30%, -50%) scale(1);
            }
            50% {
                opacity: 0.7;
                transform: translate(30%, -50%) scale(1.1);
            }
        }

        @keyframes headerAppear {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        @keyframes buttonPop {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes shimmer {
            from {
                transform: translateX(-50%) rotate(0deg);
            }
            to {
                transform: translateX(50%) rotate(360deg);
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .stat-icon.warning {
            background: var(--warning);
        }

        .stat-icon i {
            font-size: 1.25rem;
            color: white;
        }

        .stat-content h3 {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--success);
        }

        .stat-trend.down {
            color: var(--danger);
        }

        .trend-period {
            color: var(--gray-500);
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }

        .quick-actions {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-800);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            color: var(--gray-800);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: var(--primary);
            color: white;
        }

        .action-card i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .action-card span {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Dark mode support */
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .action-card {
            background: var(--card-bg);
            border-color: var(--gray-200);
        }

        [data-theme="dark"] .stat-content h3 {
            color: var(--gray-400);
        }

        [data-theme="dark"] .stat-value {
            color: var(--gray-100);
        }

        [data-theme="dark"] .trend-period {
            color: var(--gray-500);
        }

        @media (max-width: 1200px) {
            .stats-grid,
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Notifications Panel Styling */
.notifications-panel {
    position: fixed;
    top: 80px; /* Will be adjusted by JavaScript */
    right: 20px;
    width: 320px;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    max-height: 80vh;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    display: none; /* Hidden by default */
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notifications-header {
    position: sticky;
    top: 0;
    background: white;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1;
}

/* Ensure the button is styled correctly */
.notifications-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

        .dashboard-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.action-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    text-decoration: none;
    color: var(--gray-800);
    transition: all 0.3s ease;
    border: 1px solid var(--gray-200);
    text-align: center;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    border-color: var(--primary);
}

.action-icon {
    width: 48px;
    height: 48px;
    background: var(--primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.action-icon i {
    font-size: 1.5rem;
    color: white;
}

.action-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.action-card p {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.metric-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid var(--gray-200);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.metric-header h3 {
    font-size: 1rem;
    font-weight: 600;
}

.metric-item, .demographic-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    gap: 1rem;
}

/* Add to your existing styles */
.recent-section {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-top: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-all-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    transform: translateX(5px);
}

.recent-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.recent-product-card {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;
}

.recent-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.product-image-container {
    position: relative;
    padding-top: 75%;
    background: var(--gray-100);
}

.product-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-status {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    background: var(--success);
    color: white;
}

.product-status.low-stock {
    background: var(--warning);
}

.product-details {
    padding: 1rem;
}

.product-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.product-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.action-link {
    padding: 0.25rem;
    border-radius: 0.25rem;
    color: var(--gray-600);
    transition: all 0.3s ease;
}

.action-link:hover {
    color: var(--primary);
    background: var(--gray-100);
}

/* Dark mode support */
[data-theme="dark"] .recent-section {
    background: var(--card-bg);
}

[data-theme="dark"] .recent-product-card {
    background: var(--card-bg);
    border-color: var(--gray-700);
}

[data-theme="dark"] .product-name {
    color: var(--gray-100);
}

[data-theme="dark"] .info-item {
    color: var(--gray-400);
}

/* Add these styles to properly position and style the logout link */
.sidebar {
    display: flex;
    flex-direction: column;
}

nav {
    flex: 1;
}

.logout-btn {
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    margin-top: 2rem;
    background: transparent; /* Changed from rgba(255, 255, 255, 0.1) to transparent */
}

.logout-btn:hover {
    background: #dc2626; /* Keep the red hover effect */
}

.logout-btn i {
    font-size: 1.25rem;
}

/* Dark mode support */
[data-theme="dark"] .logout-btn {
    color: rgba(255, 255, 255, 0.8);
    background: transparent; /* Changed from rgba(239, 68, 68, 0.1) to transparent */
}

[data-theme="dark"] .logout-btn:hover {
    background: rgba(203, 24, 24, 0.96);
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Updated sidebar to match dashboard styling -->
<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-shopping-bag"></i>
        FashShop
    </div>
    <nav>
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-home"></i>
            Dashboard
        </a>
        <a href="products.php" class="nav-link">
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
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>
</aside>

        <main class="main-content">
            <div class="dashboard-header">
                <div class="header-content">
                    <div class="welcome-section">
                        <div class="dashboard-logo">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="welcome-text">
                            <h1 class="dashboard-title">Welcome, Admin</h1>
                            <p class="last-login">Last login: <?= date('M d, Y H:i') ?></p>
                        </div>
                    </div>
                    <div class="header-controls">
                        <button id="themeToggle" class="theme-toggle" aria-label="Toggle dark mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        <div class="notifications-dropdown">
                            <button id="notificationsBtn" class="notifications-btn" aria-label="Show notifications">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replace the existing stats-grid with this enhanced version -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3>Total Products</h3>
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i>
                <span>12% increase</span>
                <span class="trend-period">vs last month</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3>Total Users</h3>
            <div class="stat-value"><?= number_format($totalUsers) ?></div>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i>
                <span>8% increase</span>
                <span class="trend-period">vs last month</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-content">
            <h3>Products on Sale</h3>
            <div class="stat-value"><?= number_format($productsOnSale) ?></div>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i>
                <span>5% increase</span>
                <span class="trend-period">vs last month</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3>Low Stock Items</h3>
            <div class="stat-value"><?= number_format($lowStock) ?></div>
            <div class="stat-trend down">
                <i class="fas fa-arrow-down"></i>
                <span>3% decrease</span>
                <span class="trend-period">vs last month</span>
            </div>
        </div>
    </div>
</div>

<!-- Add quick actions section -->
<div class="quick-actions">
    <h2 class="section-title">Quick Actions</h2>
    <div class="actions-grid">
        <a href="products.php?action=new" class="action-card">
            <i class="fas fa-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="users.php?action=new" class="action-card">
            <i class="fas fa-user-plus"></i>
            <span>Add User</span>
        </a>
        <a href="reports.php" class="action-card">
            <i class="fas fa-chart-line"></i>
            <span>View Reports</span>
        </a>
        <a href="inventory.php" class="action-card">
            <i class="fas fa-boxes"></i>
            <span>Manage Inventory</span>
        </a>
    </div>
</div>

<!-- Replace the existing recent-section div with this -->
<div class="recent-section">
    <div class="section-header">
        <h2><i class="fas fa-clock"></i> Recent Products</h2>
        <a href="products.php" class="view-all-btn">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="recent-products-grid">
        <?php foreach ($recentProducts as $product): ?>
            <div class="recent-product-card">
                <div class="product-image-container">
                    <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-image">
                    <div class="product-status <?= $product['stock'] < 10 ? 'low-stock' : '' ?>">
                        <?= $product['stock'] < 10 ? 'Low Stock' : 'In Stock' ?>
                    </div>
                </div>
                <div class="product-details">
                    <h4 class="product-name"><?= htmlspecialchars($product['name']) ?></h4>
                    <div class="product-info-grid">
                        <div class="info-item">
                            <i class="fas fa-box"></i>
                            <span><?= $product['stock'] ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <span>€<?= number_format($product['price'], 2) ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= date('M d', strtotime($product['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <a href="products.php?action=edit&id=<?= $product['id'] ?>" class="action-link edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="products.php?action=view&id=<?= $product['id'] ?>" class="action-link view">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
        </main>
    </div>

    <!-- Move notifications panel outside main container -->
    <div class="notifications-panel" id="notificationsPanel">
        <div class="notifications-header">
            <h3>Recent Activities</h3>
            <button class="close-btn">×</button>
        </div>
        <!-- Replace the existing notifications query -->
        <div class="notifications-list">
            <?php
            // Fetch recent activities including product additions
            $stmt = $conn->query("
                SELECT 
                    CASE 
                        WHEN a.type = 'product_add' THEN 'product'
                        WHEN a.type = 'order' THEN 'order'
                        ELSE 'user'
                    END as type,
                    a.created_at,
                    a.admin_name,
                    COALESCE(p.name, '') as product_name,
                    a.id
                FROM (
                    SELECT 'order' as type, created_at, NULL as admin_name, id, NULL as product_id FROM orders
                    UNION ALL
                    SELECT 'user' as type, created_at, NULL as admin_name, id, NULL as product_id FROM users
                    UNION ALL
                    SELECT 'product_add' as type, created_at, admin_name, id, product_id 
                    FROM admin_activity_log
                    WHERE type = 'product_add'
                ) a
                LEFT JOIN products p ON a.product_id = p.id
                ORDER BY a.created_at DESC 
                LIMIT 10
            ");
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activities as $activity): 
                $icon = $activity['type'] === 'order' ? 'shopping-cart' : 
                       ($activity['type'] === 'user' ? 'user' : 'box');
                $title = $activity['type'] === 'order' ? 'New order placed' : 
                        ($activity['type'] === 'user' ? 'New user registered' : 
                        'Product added: ' . htmlspecialchars($activity['product_name']));
            ?>
                <div class="notification-item">
                    <div class="notification-icon">
                        <i class="fas fa-<?= $icon ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">
                            <?= $title ?>
                        </div>
                        <?php if ($activity['type'] === 'product'): ?>
                            <div class="notification-admin">
                                by <?= htmlspecialchars($activity['admin_name']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="notification-time">
                            <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationsBtn = document.getElementById('notificationsBtn');
    const notificationsPanel = document.getElementById('notificationsPanel');
    const closeBtn = document.querySelector('.close-btn');
    
    if (!notificationsBtn || !notificationsPanel || !closeBtn) {
        console.error('Missing required elements for notifications');
        return;
    }
    
    // Make sure panel is initially hidden with proper styling
    notificationsPanel.style.display = 'none';
    notificationsPanel.style.position = 'fixed';
    notificationsPanel.style.zIndex = '10000';
    
    // Toggle notifications panel when clicking the bell icon
    notificationsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Notification button clicked');
        
        // Get button position for accurate panel placement
        const btnRect = notificationsBtn.getBoundingClientRect();
        
        // Toggle panel visibility
        if (notificationsPanel.style.display === 'block') {
            notificationsPanel.style.display = 'none';
        } else {
            // Position panel relative to the button
            notificationsPanel.style.top = (btnRect.bottom + 10) + 'px';
            notificationsPanel.style.right = '20px';
            notificationsPanel.style.display = 'block';
            
            // Debug information
            console.log('Panel position:', {
                top: notificationsPanel.style.top,
                right: notificationsPanel.style.right
            });
        }
    });
    
    // Close notification panel when clicking the close button
    closeBtn.addEventListener('click', function() {
        notificationsPanel.style.display = 'none';
    });
    
    // Close panel when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationsPanel.style.display === 'block' && 
            !notificationsPanel.contains(e.target) && 
            e.target !== notificationsBtn &&
            !notificationsBtn.contains(e.target)) {
            notificationsPanel.style.display = 'none';
        }
    });
    
    // Initialize theme based on localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Theme toggle button
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('i');
    
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    
    // Theme toggle functionality
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
});
</script>
</body>
</html>