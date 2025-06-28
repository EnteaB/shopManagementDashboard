<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
    
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ../login.php');
        exit();
    }
}

function requireWomenManager() {
    error_log("Checking women manager access - Session data: " . print_r($_SESSION, true));
    
    if (!isLoggedIn()) {
        error_log("Not logged in - redirecting to login");
        header('Location: ../login.php');
        exit();
    }
    
    if ($_SESSION['role'] !== 'manager_women') {
        error_log("Not women manager role - current role: " . $_SESSION['role']);
        header('Location: ../login.php');
        exit();
    }
}

function requireMenManager() {
    error_log("Checking men manager access - Session data: " . print_r($_SESSION, true));
    
    if (!isLoggedIn()) {
        error_log("Not logged in - redirecting to login");
        header('Location: ../login.php');
        exit();
    }
    
    if ($_SESSION['role'] !== 'manager_men') {
        error_log("Not men manager role - current role: " . $_SESSION['role']);
        header('Location: ../login.php');
        exit();
    }
}

function checkRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}