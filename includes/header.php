<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashShop - Fashion Management System</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome and StyleSheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo isset($isAdmin) ? '../css/admin.css' : 'css/style.css'; ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-tshirt"></i>
                <a href="<?php echo isset($isAdmin) ? '../index.php' : 'index.php'; ?>">FashShop</a>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <nav class="nav-menu">
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="../admin/dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="../admin/manage_users.php" class="nav-link">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a href="../admin/manage_products.php" class="nav-link">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a href="../admin/reports.php" class="nav-link">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    <?php elseif($_SESSION['role'] === 'manager_women'): ?>
                        <a href="../manager/women_dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="../manager/women/products.php" class="nav-link">
                            <i class="fas fa-female"></i> Women's Products
                        </a>
                    <?php elseif($_SESSION['role'] === 'manager_men'): ?>
                        <a href="../manager/men_dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                        <a href="../manager/men/products.php" class="nav-link">
                            <i class="fas fa-male"></i> Men's Products
                        </a>
                    <?php endif; ?>
                    <div class="user-menu">
                        <span class="username">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <a href="../includes/logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <div class="content-wrapper">