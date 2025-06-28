<?php
session_start();

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify admin access
requireWomenManager();

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
    // Total women's products - Using the correct column names from your database
    $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE category = 'female' OR category_type = 'female'");
    $totalProducts = $stmt->fetchColumn();

    // Women's products on sale - Update to use correct columns
    $stmt = $conn->query("
        SELECT COUNT(DISTINCT d.product_id) 
        FROM discounts d
        JOIN products p ON d.product_id = p.id 
        WHERE d.end_date > NOW() AND (p.category = 'female' OR p.category_type = 'female')
    ");
    $productsOnSale = $stmt->fetchColumn();

    // Low stock women's products - Update to use correct columns
    $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 10 AND (category = 'female' OR category_type = 'female')");
    $lowStock = $stmt->fetchColumn();

    // Recent women's products - Update to use correct columns
    $stmt = $conn->query("
        SELECT * FROM products 
        WHERE category = 'female' OR category_type = 'female'
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading products: ' . $e->getMessage() . '</div>';
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
            grid-template-columns: repeat(3, 1fr);
            gap: 3.5rem; /* Increased gap for more spacing between cards */
            margin: 0 auto 4rem; /* Increased bottom margin */
            max-width: 1300px; /* Wider container to accommodate spacing */
            padding: 0 3rem; /* Added more padding on sides */
        }

        .stat-card {
            background: white;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem; /* Increased padding inside cards */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
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
            font-size: 2.5rem; /* Larger value text */
            font-weight: 700;
            color: var(--gray-900);
            margin: 1rem 0; /* More vertical spacing */
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.75rem; /* More space between arrow and text */
            font-size: 0.95rem;
            color: var(--success);
            margin-top: 0.5rem;
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
    top: 80px; /* Will be updated by JavaScript */
    right: 20px;
    width: 320px;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    max-height: 80vh;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    display: none; /* Hidden by default */
}

.notifications-panel.active {
    display: block !important; /* Force display when active */
}

/* Ensure notification button has a clickable area */
.notifications-btn {
    position: relative;
    padding: 0.5rem;
    font-size: 1.25rem;
    cursor: pointer;
    z-index: 1000;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Dark mode support */
[data-theme="dark"] .notifications-panel {
    background: var(--bg-secondary);
    border-color: var(--gray-200);
    color: var(--text-primary);
}

[data-theme="dark"] .notifications-header {
    background: var(--bg-secondary);
    border-color: var(--gray-200);
}
    </style>
    <style>
        /* Dark mode variables */
        :root[data-theme="dark"] {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #94a3b8;
            --gray-50: #1a1a1a;
            --gray-100: #2a2a2a;
            --gray-200: #333333;
            --gray-800: #e2e8f0;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --bg-primary: #121212;
            --bg-secondary: #1a1a1a;
            --card-bg: #1e1e1e;
        }

        /* Enhanced header styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
            gap: 1rem;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Enhanced card styles */
        .stat-card {
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-info {
            position: relative;
        }

        .stat-tooltip {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--gray-800);
            color: var(--gray-50);
            padding: 0.25rem;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: help;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover .stat-tooltip {
            opacity: 1;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .chart-card {
                min-height: 250px;
            }
        }

        /* Add to your existing styles */
.additional-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.charts-container {
    margin-top: 2rem;
}

.chart-row {
    margin-bottom: 2rem;
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.chart-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.chart-select {
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

/* Dark mode support */
[data-theme="dark"] .chart-card {
    background: var(--card-bg);
    border: 1px solid var(--gray-200);
}

[data-theme="dark"] .chart-select {
    background: var(--card-bg);
    color: var(--text-primary);
    border-color: var(--gray-200);
}
    </style>
    <style>
        /* Notifications dropdown styles */
        .notifications-dropdown {
            position: relative;
        }

        .notifications-btn {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 0.25rem;
            font-size: 0.75rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notifications-panel {
            position: fixed;
            top: 0;
            right: 0;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1100;
            max-height: 80vh;
            width: 320px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
        }

        .notifications-panel.active {
            display: block;
            animation: slideDown 0.2s ease-out;
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

        .notifications-header h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .notifications-list {
            padding: 0.5rem 0;
            max-height: calc(80vh - 60px); /* Adjust for header height */
            overflow-y: auto;
        }

        .notification-item {
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border-bottom: 1px solid var (--gray-200);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: var(--gray-50);
            border-left-color: var(--primary);
        }

        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: var(--gray-800);
        }

        .notification-time {
            font-size: 0.75rem;
            color: var (--secondary);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dark mode support */
        :root[data-theme="dark"] .notifications-panel {
            background: var(--bg-secondary);
            border: 1px solid var(--gray-200);
        }

        :root[data-theme="dark"] .notification-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        :root[data-theme="dark"] .notification-title {
            color: var(--text-primary);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-800);
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .close-btn:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        // Add to your existing CSS
        .notification-admin {
            font-size: 0.75rem;
            color: var(--primary);
            margin: 0.25rem 0;
            font-weight: 500;
        }

        /* Dark mode support */
        :root[data-theme="dark"] .notification-admin {
            color: var(--primary-light);
        }

        .theme-toggle:focus,
.notifications-btn:focus {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.theme-toggle {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.notifications-dropdown {
    position: relative;
}

.notifications-btn {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    padding: 0.25rem;
    font-size: 0.75rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}
    </style>
    <style>
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
        <!-- Users link removed -->
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
                            <h1 class="dashboard-title">Welcome, Women's Manager</h1>
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

            <!-- Replace the existing stats-grid with this simplified version -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3>Women's Products</h3>
            <div class="stat-value"><?= number_format($totalProducts) ?></div>
            <div class="stat-trend">
                <i class="fas fa-arrow-<?= $totalProducts == 1 ? 'right' : 'up' ?>"></i>
                <span><?= $totalProducts == 1 ? '1 product in catalog' : '12% increase' ?></span>
                <span class="trend-period"><?= $totalProducts == 1 ? '' : 'vs last month' ?></span>
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

<!-- Update quick actions section to remove user and inventory management -->
<div class="quick-actions">
    <h2 class="section-title">Quick Actions</h2>
    <div class="actions-grid">
        <a href="products.php?action=new" class="action-card">
            <i class="fas fa-plus-circle"></i>
            <span>Add Product</span>
        </a>
        <a href="reports.php" class="action-card">
            <i class="fas fa-chart-line"></i>
            <span>View Reports</span>
        </a>
        <a href="products.php?filter=sale" class="action-card">
            <i class="fas fa-tags"></i>
            <span>Manage Sales</span>
        </a>
        <a href="products.php?filter=categories" class="action-card">
            <i class="fas fa-th-large"></i>
            <span>Product Categories</span>
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
document.addEventListener('DOMContentLoaded', () => {
    console.clear(); // Clear previous console logs for clean debugging
    
    // Theme Toggle Elements
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle?.querySelector('i');
    
    // Notifications Elements
    const notificationsBtn = document.getElementById('notificationsBtn');
    const notificationsPanel = document.getElementById('notificationsPanel');
    const closeBtn = document.querySelector('.close-btn');
    
    console.log("Theme toggle:", themeToggle);
    console.log("Notifications button:", notificationsBtn);
    console.log("Notifications panel:", notificationsPanel);
    
    // Initialize theme based on localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        console.log("Set initial theme icon:", themeIcon.className);
    }
    
    // Theme toggle function - FIX: Added direct style changes and debugging
    if (themeToggle) {
        themeToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // Stop event propagation
            console.log("Theme toggle clicked");
            
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (themeIcon) {
                themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                console.log("Updated theme icon:", themeIcon.className);
            }
        });
    }
    
    // Fix notifications panel - MAJOR FIX: Complete rewrite of the notification panel code
    if (notificationsBtn && notificationsPanel) {
        // Force the panel to be initially hidden
        notificationsPanel.style.display = 'none';
        
        // Create and attach a click handler specifically for the notifications button
        notificationsBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation(); // Stop event propagation
            console.log("Notifications button clicked");
            
            // Check if the panel is currently visible
            const isVisible = getComputedStyle(notificationsPanel).display !== 'none';
            
            if (isVisible) {
                // Hide panel
                notificationsPanel.style.display = 'none';
                notificationsPanel.classList.remove('active');
                console.log("Hiding notifications panel");
            } else {
                // Show panel and position it
                notificationsPanel.style.display = 'block';
                notificationsPanel.classList.add('active');
                
                // Position panel relative to button
                const btnRect = notificationsBtn.getBoundingClientRect();
                notificationsPanel.style.position = 'fixed';
                notificationsPanel.style.top = (btnRect.bottom + 10) + 'px';
                notificationsPanel.style.right = '20px';
                notificationsPanel.style.zIndex = '10000'; // Higher z-index
                
                console.log("Showing notifications panel");
                console.log("Panel position:", notificationsPanel.style.top, notificationsPanel.style.right);
            }
        };
        
        // Close button functionality
        if (closeBtn) {
            closeBtn.onclick = function(e) {
                e.stopPropagation();
                notificationsPanel.style.display = 'none';
                notificationsPanel.classList.remove('active');
                console.log("Close button clicked, hiding panel");
            };
        }
        
        // Close when clicking anywhere else
        document.addEventListener('click', function(e) {
            if (notificationsPanel.style.display === 'block' && 
                !notificationsPanel.contains(e.target) && 
                e.target !== notificationsBtn) {
                notificationsPanel.style.display = 'none';
                notificationsPanel.classList.remove('active');
                console.log("Document clicked, hiding panel");
            }
        });
    }
});
</script>
</body>
</html>