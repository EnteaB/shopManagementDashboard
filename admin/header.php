<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get notifications count
$notificationCount = isset($_SESSION['notifications']) ? count($_SESSION['notifications']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - FashShop Admin' : 'FashShop Admin' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    
    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <?php if (isset($pageStyles)) echo $pageStyles; ?>
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>">
    
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-left">
            <button id="menu-toggle" class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-brand">
                <i class="fas fa-tshirt"></i>
                <span>FashShop Admin</span>
            </div>
            <div class="search-wrapper">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search anything...">
                </div>
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>

        <div class="nav-right">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="action-btn" title="Add New Product" onclick="location.href='add_product.php'">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="action-btn" title="View Reports" onclick="location.href='reports.php'">
                    <i class="fas fa-chart-bar"></i>
                </button>
            </div>

            <!-- Notifications -->
            <div class="notifications-dropdown">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                    <span class="badge"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-content">
                    <div class="dropdown-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read">Mark all as read</button>
                    </div>
                    <div class="notifications-list">
                        <!-- Notifications will be loaded here via AJAX -->
                    </div>
                    <div class="dropdown-footer">
                        <a href="notifications.php">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-info" data-toggle="dropdown">
                    <img src="<?= isset($_SESSION['avatar']) ? htmlspecialchars($_SESSION['avatar']) : '../assets/img/default-avatar.png' ?>" 
                         alt="Profile" class="avatar">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../includes/logout.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Results Template -->
    <template id="searchResultTemplate">
        <a href="" class="search-result-item">
            <img src="" alt="" class="result-img">
            <div class="result-info">
                <h4 class="result-title"></h4>
                <p class="result-desc"></p>
            </div>
        </a>
    </template>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <?php if (isset($pageScripts)) echo $pageScripts; ?>