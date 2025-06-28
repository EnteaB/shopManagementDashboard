<?php
session_start();
require_once 'includes/auth.php';

// First check if user is actually logged in with valid credentials
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // User is logged in, redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'men_manager':
            header('Location: men/dashboard.php');
            break;
        case 'women_manager':
            header('Location: women/dashboard.php');
            break;
        default:
            header('Location: shop/index.php');
            break;
    }
} else {
    // Not logged in, redirect to login
    header('Location: login.php');
}
exit();