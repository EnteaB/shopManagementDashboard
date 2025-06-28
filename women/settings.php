<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Verify women manager access
requireWomenManager();

// Check if session is active
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Rest of your settings.php code
$db = Database::getInstance();
$conn = $db->getConnection();

// Get current manager user instead of admin
$userId = $_SESSION['user_id'] ?? 0;

// Set defaults
$settings = [
    'theme' => 'default',
    'notification_emails' => true,
    'timezone' => 'Europe/London',
    'items_per_page' => 10,
    'language' => 'en',
    'maintenance_mode' => false
];

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if this is a theme update
        if (isset($_POST['theme'])) {
            $theme = $_POST['theme'];
            $_SESSION['manager_theme'] = $theme; // Change from admin_theme
            $message = "Theme updated successfully!";
            $messageType = "success";
        } 
        
        // Check if this is a settings update
        elseif (isset($_POST['save_settings'])) {
            // Update settings
            $settings['notification_emails'] = isset($_POST['notification_emails']) ? true : false;
            $settings['timezone'] = $_POST['timezone'] ?? 'Europe/London';
            $settings['items_per_page'] = intval($_POST['items_per_page']) ?: 10;
            $settings['language'] = $_POST['language'] ?? 'en';
            $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? true : false;
            
            // Save settings (would normally go to database)
            $_SESSION['manager_settings'] = $settings; // Change from admin_settings
            $message = "Settings saved successfully!";
            $messageType = "success";
        }
        
        // Check if this is a password update
        elseif (isset($_POST['update_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate passwords
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("All password fields are required");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            
            // Check current password (mocked for demo)
            // In a real app, validate against database
            
            // Update password (mocked for demo)
            $message = "Password updated successfully!";
            $messageType = "success";
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Load saved theme and settings if they exist
$currentTheme = $_SESSION['manager_theme'] ?? 'default';
if (isset($_SESSION['manager_settings'])) {
    $settings = array_merge($settings, $_SESSION['manager_settings']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Women's Fashion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Default Theme */
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
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-800: #1e293b;
            
            /* Theme override based on selection */
            <?php if ($currentTheme === 'purple'): ?>
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #c4b5fd;
            <?php elseif ($currentTheme === 'green'): ?>
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #a7f3d0;
            <?php elseif ($currentTheme === 'blue'): ?>
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #93c5fd;
            <?php elseif ($currentTheme === 'orange'): ?>
            --primary: #f97316;
            --primary-dark: #ea580c;
            --primary-light: #fed7aa;
            <?php elseif ($currentTheme === 'red'): ?>
            --primary: #ef4444;
            --primary-dark: #dc2626;
            --primary-light: #fecaca;
            <?php endif; ?>
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

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
            padding: 2rem 4rem; /* Reduced from 4rem 8rem */
            margin-left: 0;
            width: 100%;
            transition: all 0.3s ease;
        }

        .main-content.shifted {
            padding-left: calc(4rem + 250px); /* Reduced from 8rem + 250px */
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

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1.5rem 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            margin: 1rem auto 2.5rem;
            width: 85%;
            max-width: 900px;
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
            gap: 0.25rem;
        }

        .header-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.2rem;
        }

        .header-icon i {
            font-size: 1.25rem;
            color: white;
        }

        .header h1 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            width: 100%;
            text-align: center;
        }

        /* Settings specific styles */
        .settings-container {
            width: 95%;
            max-width: 900px;
            margin: 0 auto;
        }

        .settings-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }

        .settings-header {
            background: var(--gray-50);
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .settings-header h2 {
            font-size: 1.25rem;
            color: var(--gray-800);
            margin: 0;
            font-weight: 600;
        }

        .settings-header i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .settings-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-800);
            font-size: 0.95rem;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--gray-50);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: white;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Theme selector styles */
        .theme-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-bottom: 1rem;
        }

        .theme-option {
            width: 110px;
            text-align: center;
        }

        .theme-color {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            margin: 0 auto 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 3px solid var(--gray-100);
            position: relative;
        }

        .theme-color:hover {
            transform: scale(1.1);
        }

        .theme-color.active {
            border-color: var(--gray-800);
        }

        .theme-color.active::after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .theme-name {
            display: block;
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        /* Theme colors */
        .theme-default {
            background: #4f46e5;
        }

        .theme-purple {
            background: #8b5cf6;
        }

        .theme-green {
            background: #10b981;
        }

        .theme-blue {
            background: #3b82f6;
        }

        .theme-orange {
            background: #f97316;
        }
        
        .theme-red {
            background: #ef4444;
        }
        
        /* Message styling */
        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: fadeIn 0.5s ease-out;
        }
        
        .message-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .message-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 2rem;
            }
            
            .main-content.shifted {
                padding-left: calc(2rem + 250px);
            }
            
            .theme-options {
                gap: 0.75rem;
            }
            
            .theme-option {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Menu toggle button -->
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
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
            <a href="products.php" class="nav-link">
                <i class="fas fa-tshirt"></i>
                Products
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
            <a href="settings.php" class="nav-link active">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </nav>
        <a href="../logout.php" class="nav-link logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h1>System Settings</h1>
            </div>
        </div>

        <div class="settings-container">
            <?php if (!empty($message)): ?>
                <div class="message message-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Theme Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-palette"></i>
                    <h2>Theme Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="post">
                        <div class="form-group">
                            <label>Select Theme Color</label>
                            <div class="theme-options">
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-default" value="default" class="sr-only" <?= $currentTheme === 'default' ? 'checked' : '' ?>>
                                    <label for="theme-default">
                                        <div class="theme-color theme-default <?= $currentTheme === 'default' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Indigo</span>
                                    </label>
                                </div>
                                
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-purple" value="purple" class="sr-only" <?= $currentTheme === 'purple' ? 'checked' : '' ?>>
                                    <label for="theme-purple">
                                        <div class="theme-color theme-purple <?= $currentTheme === 'purple' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Purple</span>
                                    </label>
                                </div>
                                
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-green" value="green" class="sr-only" <?= $currentTheme === 'green' ? 'checked' : '' ?>>
                                    <label for="theme-green">
                                        <div class="theme-color theme-green <?= $currentTheme === 'green' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Green</span>
                                    </label>
                                </div>
                                
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-blue" value="blue" class="sr-only" <?= $currentTheme === 'blue' ? 'checked' : '' ?>>
                                    <label for="theme-blue">
                                        <div class="theme-color theme-blue <?= $currentTheme === 'blue' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Blue</span>
                                    </label>
                                </div>
                                
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-orange" value="orange" class="sr-only" <?= $currentTheme === 'orange' ? 'checked' : '' ?>>
                                    <label for="theme-orange">
                                        <div class="theme-color theme-orange <?= $currentTheme === 'orange' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Orange</span>
                                    </label>
                                </div>
                                
                                <div class="theme-option">
                                    <input type="radio" name="theme" id="theme-red" value="red" class="sr-only" <?= $currentTheme === 'red' ? 'checked' : '' ?>>
                                    <label for="theme-red">
                                        <div class="theme-color theme-red <?= $currentTheme === 'red' ? 'active' : '' ?>"></div>
                                        <span class="theme-name">Red</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Apply Theme
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- General Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-sliders-h"></i>
                    <h2>General Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="items_per_page">Items Per Page</label>
                            <input type="number" id="items_per_page" name="items_per_page" value="<?= $settings['items_per_page'] ?>" min="5" max="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="timezone">
                                <option value="Europe/London" <?= $settings['timezone'] === 'Europe/London' ? 'selected' : '' ?>>London (GMT+0)</option>
                                <option value="Europe/Paris" <?= $settings['timezone'] === 'Europe/Paris' ? 'selected' : '' ?>>Paris (GMT+1)</option>
                                <option value="America/New_York" <?= $settings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>New York (GMT-4)</option>
                                <option value="Asia/Tokyo" <?= $settings['timezone'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Tokyo (GMT+9)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language">
                                <option value="en" <?= $settings['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="fr" <?= $settings['language'] === 'fr' ? 'selected' : '' ?>>French</option>
                                <option value="es" <?= $settings['language'] === 'es' ? 'selected' : '' ?>>Spanish</option>
                                <option value="de" <?= $settings['language'] === 'de' ? 'selected' : '' ?>>German</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="notification_emails" name="notification_emails" <?= $settings['notification_emails'] ? 'checked' : '' ?>>
                                <label for="notification_emails">Receive notification emails</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                <label for="maintenance_mode">Enable maintenance mode</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Settings
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Password Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <i class="fas fa-lock"></i>
                    <h2>Change Password</h2>
                </div>
                <div class="settings-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-primary">
                            <i class="fas fa-key"></i>
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const menuToggle = document.querySelector('.menu-toggle');
            
            // Sidebar toggle
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
            
            // Handle theme selection with real-time preview
            const themeColors = document.querySelectorAll('.theme-color');
            const themeRadios = document.querySelectorAll('input[name="theme"]');
            
            themeColors.forEach(color => {
                color.addEventListener('click', function() {
                    const radioId = this.parentElement.getAttribute('for');
                    document.getElementById(radioId).checked = true;
                    
                    // Remove active class from all
                    themeColors.forEach(tc => tc.classList.remove('active'));
                    
                    // Add active to selected
                    this.classList.add('active');
                    
                    // Auto-submit for preview
                    document.querySelector('form').submit();
                });
            });
            
            // Auto-hide message after 5 seconds
            const message = document.querySelector('.message');
            if (message) {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => message.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>