<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$currentPage = 'sales_report';
$pageTitle = 'Sales Report';

// Get date range from GET parameters or set defaults
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch sales data
try {
    $sql = "SELECT 
                p.name as product_name,
                p.category,
                COUNT(*) as total_sold,
                SUM(p.price * (1 - IFNULL(p.discount_percentage, 0)/100)) as total_revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.order_date BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_revenue DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    die("Error fetching sales data: " . $e->getMessage());
}

ob_start();
?>

<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Sales Report</h2>
    <div class="header-actions">
        <form method="get" class="date-range-form">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit" class="btn-primary">Generate Report</button>
        </form>
    </div>
</div>

<div class="sales-summary">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <h3>Total Revenue</h3>
            <?php
            $totalRevenue = 0;
            while ($row = $result->fetch_assoc()) {
                $totalRevenue += $row['total_revenue'];
            }
            $result->data_seek(0); // Reset result pointer
            ?>
            <p class="stat-number">$<?= number_format($totalRevenue, 2) ?></p>
        </div>
    </div>
</div>

<div class="sales-table">
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th>Total Sold</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= htmlspecialchars($row['total_sold']) ?></td>
                        <td>$<?= number_format($row['total_revenue'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No sales data available for the selected period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>