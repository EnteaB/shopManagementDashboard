<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify admin access
requireMenManager();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get date range from parameters or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

try {
    // Fetch statistics
    $stats = [
        'total_sales' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0,
        'top_products' => [],
        'sales_by_category' => [],
    ];

    // Get total sales and orders
    $stmt = $conn->prepare("
        SELECT COUNT(*) as order_count, 
               SUM(total_amount) as total_sales
        FROM orders 
        WHERE order_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['total_orders'] = $result['order_count'] ?? 0;
    $stats['total_sales'] = $result['total_sales'] ?? 0;
    $stats['avg_order_value'] = $stats['total_orders'] > 0 ? 
        $stats['total_sales'] / $stats['total_orders'] : 0;

    // Get top selling products
    $stmt = $conn->prepare("
        SELECT p.name, p.category, 
               COUNT(*) as units_sold,
               SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.order_date BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY units_sold DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sales by category
    $stmt = $conn->prepare("
        SELECT p.category,
               COUNT(*) as orders,
               SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.order_date BETWEEN ? AND ?
        GROUP BY p.category
    ");
    $stmt->execute([$start_date, $end_date]);
    $stats['sales_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #a5b4fc;
            --secondary: #64748b;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --info: #06b6d4;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-800: #1e293b;
            --chart1: #4f46e5;
            --chart2: #22c55e;
            --chart3: #eab308;
            --chart4: #ef4444;
            --chart5: #06b6d4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        /* Update these styles to improve horizontal layout */
       /* Find and replace the body style */
body {
    background: linear-gradient(135deg,rgb(189, 204, 243) 0%, #f0f5ff 100%);
    min-height: 100vh;
}

/* Find and replace the .main-content style */
.main-content {
    padding: 4rem 8rem;
    margin-left: 0;
    width: 100%;
    transition: all 0.3s ease;
    background: transparent; /* Add this to ensure transparency */
}
        /* Update these sidebar styles to match products page exactly */
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
            padding: 4rem 8rem;
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

      .header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 1rem 3rem;
        border-radius: 0.75rem;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        margin: 1rem auto 2.5rem;
       width: 100%;           /* Full width */
    max-width: none;
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: slideDown 0.5s ease-out;
    }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            animation: shine 2s infinite;
        }

        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem; /* Further reduced gap */
        }

        .header-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 40px; /* Further reduced size */
            height: 40px; /* Further reduced size */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.2rem; /* Further reduced margin */
        }

        .header-icon i {
            font-size: 1.25rem; /* Further reduced size */
            color: white;
        }

        .header h1 {
            color: white;
            font-size: 1.5rem; /* Further reduced size */
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1); /* Reduced shadow */
            width: 100%;
            text-align: center;
        }

        @keyframes slideDown {
            from, to { transform: none; opacity: 1; }
        }

        @keyframes shine {
            from, to { transform: none; }
        }

        @keyframes fadeIn {
            from, to { transform: none; opacity: 1; }
        }

        .header {
            animation: none;
        }

        .header::before {
            animation: none;
            display: none; /* Remove the animated element */
        }

        /* Updated stats grid for better horizontal spacing */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Force 3 columns */
            gap: 1.5rem; /* Increased gap */
            margin-bottom: 3rem; /* Increased margin */
            width: 95%;
            max-width: 1200px;
        }

        .stat-card {
            background: white;
            padding: 1.75rem; /* Increased padding */
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, transparent 50%, rgba(79, 70, 229, 0.05) 100%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .stat-card:hover {
            transform: none;
        }

        .stat-card h3 {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 1.25rem; /* Increased margin */
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stat-card h3 i {
            color: var(--primary);
            background: var(--primary-light);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card.sales h3 i {
            color: var(--chart1);
            background: rgba(79, 70, 229, 0.1);
        }

        .stat-card.orders h3 i {
            color: var(--chart2);
            background: rgba(34, 197, 94, 0.1);
        }

        .stat-card.average h3 i {
            color: var(--chart3);
            background: rgba(234, 179, 8, 0.1);
        }

        .stat-value {
            font-size: 1.75rem; /* Reduced font size */
            font-weight: 700;
            color: var(--gray-800);
            margin: 0.75rem 0; /* Increased margin */
        }

        .stat-trend {
            font-size: 0.875rem;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-trend.down {
            color: var(--danger);
        }

        /* Make chart containers more compact */
        .chart-container {
            padding: 1.75rem; /* Increased padding */
            margin-bottom: 3rem; /* Increased margin */
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            width: 95%;
            max-width: 1200px;
            position: relative;
            overflow: hidden;
        }

        .chart-container:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3 {
            font-size: 1.25rem;
            color: var (--gray-800);
            margin-bottom: 2rem; /* Increased margin */
            padding-bottom: 1.25rem; /* Increased padding */
            border-bottom: 1px solid var (--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chart-container h3 i {
            color: var(--primary);
        }

        .date-filter {
            background: white;
            padding: 1.75rem; /* Increased padding */
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem; /* Increased margin */
            display: flex;
            gap: 1rem;
            align-items: center;
            border: 1px solid var(--gray-200);
            width: 95%;
            max-width: 1200px;
        }

        .date-filter input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .date-filter input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .date-filter button {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .date-filter button:hover {
            background: var(--primary-dark);
            transform: none;
        }

        /* Create a two-column layout for charts */
        .charts-row {
            display: flex;
            gap: 1rem;
            width: 95%;
            max-width: 1200px;
            margin-bottom: 1.5rem;
        }

        .chart-half {
            flex: 1;
            background: white;
            padding: 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            height: 300px;
            min-height: 300px;
            max-height: 300px;
            overflow: hidden;
        }

        canvas {
            max-width: 100%;
            height: 250px !important;
            max-height: 250px !important;
        }

        .data-table {
            width: 100%;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .data-table th {
            background: var(--gray-50);
            padding: 1.25rem 1.75rem; /* Increased padding */
            font-weight: 600;
            color: var(--gray-800);
            text-align: left;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table th:first-child {
            border-top-left-radius: 0.5rem;
        }

        .data-table th:last-child {
            border-top-right-radius: 0.5rem;
        }

        .data-table td {
            padding: 1.25rem 1.75rem; /* Increased padding */
            color: var (--secondary);
            border-bottom: 1px solid var (--gray-200);
        }

        .data-table tbody tr:hover td {
            background: var(--gray-50);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 0.5rem;
        }

        .data-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 0.5rem;
        }

        /* Updated media queries */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr); /* Keep 3 columns */
            }
            
            .charts-row {
                flex-direction: column;
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .date-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .date-filter input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Menu toggle button -->
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Replace the existing sidebar nav section -->
<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-female"></i>
        Men's Fashion
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
        <a href="reports.php" class="nav-link active">
            <i class="fas fa-chart-bar"></i>
            Reports
        </a>
        <a href="settings.php" class="nav-link">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="../logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </nav>
</aside>

    <main class="main-content">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1>Sales Reports</h1>
            </div>
        </div>

        <div class="date-filter">
            <input type="date" id="start_date" value="<?= $start_date ?>">
            <input type="date" id="end_date" value="<?= $end_date ?>">
            <button onclick="updateReport()">
                <i class="fas fa-sync"></i>
                Update Report
            </button>
        </div>

        <!-- Remove this section -->
<!--         <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Sales Performance</h3>
        </div> -->

        <!-- Update the stats cards when they have 0 values -->
        <div class="stats-grid">
            <div class="stat-card sales">
                <h3><i class="fas fa-euro-sign"></i> Total Sales</h3>
                <div class="stat-value">
                    <?= $stats['total_sales'] > 0 ? '€' . number_format($stats['total_sales'], 2) : 'No sales yet' ?>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    8% from previous period
                </div>
            </div>
            <div class="stat-card orders">
                <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                <div class="stat-value">
                    <?= $stats['total_orders'] > 0 ? number_format($stats['total_orders']) : 'No orders yet' ?>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    5% from previous period
                </div>
            </div>
            <div class="stat-card average">
                <h3><i class="fas fa-calculator"></i> Average Order Value</h3>
                <div class="stat-value">
                    <?= $stats['avg_order_value'] > 0 ? '€' . number_format($stats['avg_order_value'], 2) : 'N/A' ?>
                </div>
                <div class="stat-trend down">
                    <i class="fas fa-arrow-down"></i>
                    2% from previous period
                </div>
            </div>
        </div>

        <!-- Update the top products details section -->
        <div class="chart-container">
            <h3><i class="fas fa-list"></i> Top Products Details</h3>
            <?php if (count($stats['top_products']) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td><?= number_format($product['units_sold']) ?></td>
                            <td>€<?= number_format($product['revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div style="text-align: center; padding: 2rem; color: var(--secondary);">
                        <i class="fas fa-shopping-bag" style="font-size: 2rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                        <h4 style="margin-bottom: 0.5rem;">No product data available</h4>
                        <p>Once customers start making purchases, your top-selling products will appear here.</p>
                        <p style="margin-top: 1rem; font-size: 0.875rem;">
                            Consider <a href="promotions.php" style="color: var(--primary); text-decoration: none;">creating a promotion</a> 
                            to boost your sales!
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add these chart sections to women's reports.php -->
<div class="charts-row">
    <div class="chart-half">
        <h3><i class="fas fa-chart-line"></i> Sales Trend</h3>
        <canvas id="salesTrendChart"></canvas>
    </div>
    <div class="chart-half">
        <h3><i class="fas fa-chart-pie"></i> Category Distribution</h3>
        <canvas id="categoryPieChart"></canvas>
    </div>
</div>

<div class="charts-row">
    <div class="chart-half">
        <h3><i class="fas fa-chart-bar"></i> Top Products</h3>
        <canvas id="topProductsChart"></canvas>
    </div>
    <div class="chart-half">
        <h3><i class="fas fa-calendar-alt"></i> Monthly Performance</h3>
        <canvas id="monthlyPerformanceChart"></canvas>
    </div>
</div>
    </main>

    <script>
    // Remove all the existing JavaScript at the bottom of the file and replace with this:
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const menuToggle = document.querySelector('.menu-toggle');
        
        // This is the key fix - ensure the toggle button works
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
            
            // Store sidebar state
            const isOpen = sidebar.classList.contains('active');
            localStorage.setItem('sidebarOpen', isOpen);
        });
    
        // Restore sidebar state
        const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
        if (sidebarOpen) {
            sidebar.classList.add('active');
            mainContent.classList.add('shifted');
        }
        
        // Function to update report based on date range
        window.updateReport = function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            window.location.href = `reports.php?start_date=${startDate}&end_date=${endDate}`;
        };
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Add this for static charts
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.animation = false;
        Chart.defaults.animations = false;
        Chart.defaults.transitions = {duration: 0};
        
        // Static options for all charts
        const staticOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            animations: {
                colors: false,
                x: false,
                y: false
            },
            responsiveAnimationDuration: 0,
            elements: {
                point: {
                    radius: 3,
                    hoverRadius: 3
                },
                line: {
                    tension: 0
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 8,
                        font: {
                            size: 10
                        }
                    }
                },
                tooltip: {
                    enabled: false // Disable tooltips to prevent movement
                }
            },
            interaction: {
                mode: null, // Disable interactions
                intersect: false
            },
            hover: {mode: null}, // Disable hover state
            layout: {
                padding: 0
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        color: '#64748b'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.5)'
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        color: '#64748b'
                    }
                }
            }
        };

        // 1. Sales Trend Chart - completely static
        new Chart(document.getElementById('salesTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Sales (€)',
                    data: [12500, 19200, 15700, 16800, 18600, 21400, 22000],
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff'
                }]
            },
            options: staticOptions
        });

        // 2. Category Pie Chart - completely static
        new Chart(document.getElementById('categoryPieChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Dresses', 'Tops', 'Skirts', 'Shoes', 'Accessories'],
                datasets: [{
                    data: [35, 25, 15, 15, 10],
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(6, 182, 212, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...staticOptions,
                cutout: '65%'
            }
        });

        // 3. Top Products Chart - completely static
        new Chart(document.getElementById('topProductsChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Summer Dress', 'Silk Blouse', 'Evening Gown', 'Leather Boots', 'Statement Necklace'],
                datasets: [{
                    label: 'Units Sold',
                    data: [124, 98, 87, 65, 42],
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderRadius: 6
                }]
            },
            options: staticOptions
        });

        // 4. Monthly Performance Chart - completely static
        new Chart(document.getElementById('monthlyPerformanceChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [12500, 19200, 15700, 16800, 18600, 21400],
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderRadius: 6
                }, {
                    label: 'Orders',
                    data: [105, 152, 126, 134, 148, 171],
                    type: 'line',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff'
                }]
            },
            options: staticOptions
        });
    });
    </script>
</body>
</html>