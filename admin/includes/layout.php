<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/FashShop/">
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
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <?php if (isset($pageStyles)) echo $pageStyles; ?>
</head>
<body>
    <?php requireAdmin(); ?>
    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <div class="admin-container">
        <!-- Sidebar -->
        <?php require_once 'sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button id="sidebar-toggle" class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search anything...">
                        <div id="search-results" class="search-results"></div>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Notifications -->
                    <div class="notification-dropdown">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <?php if (isset($_SESSION['notifications_count']) && $_SESSION['notifications_count'] > 0): ?>
                            <span class="badge"><?= htmlspecialchars($_SESSION['notifications_count']) ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-content notifications-panel">
                            <div class="dropdown-header">
                                <h3>Notifications</h3>
                                <a href="#" class="mark-all-read">Mark all as read</a>
                            </div>
                            <div class="notifications-list">
                                <!-- Notifications will be loaded here -->
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
                            <span class="user-name"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?></span>
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
            </header>

            <!-- Breadcrumb -->
            <?php if (isset($breadcrumbs)): ?>
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <?php foreach ($breadcrumbs as $label => $url): ?>
                    <?php if ($url): ?>
                        <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
                        <i class="fas fa-chevron-right"></i>
                    <?php else: ?>
                        <span><?= htmlspecialchars($label) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="content-wrapper">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <?php if (isset($content)) echo $content; ?>
            </div>
        </main>
    </div>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/admin.js"></script>
    <?php if (isset($pageScripts)) echo $pageScripts; ?>
</body>
</html>