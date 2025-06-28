<?php
require_once '../includes/auth.php';
requireAdmin();

// Redirect to dashboard
header('Location: dashboard.php');
exit();