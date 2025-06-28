<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-crown fa-2x" style="color: #4f46e5;"></i>
        <h1>FashShop</h1>
    </div>
    
    <nav>
        <div class="nav-section">
            <h2 class="nav-section-title">Main</h2>
            <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="reports.php" class="nav-item <?= $current_page === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </div>

        <div class="nav-section">
            <h2 class="nav-section-title">Management</h2>
            <a href="products.php" class="nav-item <?= $current_page === 'products.php' ? 'active' : '' ?>">
                <i class="fas fa-tshirt"></i>
                <span>Products</span>
            </a>
            <a href="users.php" class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="discounts.php" class="nav-item <?= $current_page === 'discounts.php' ? 'active' : '' ?>">
                <i class="fas fa-percent"></i>
                <span>Discounts</span>
            </a>
        </div>

        <div class="nav-logout">
            <a href="../logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>