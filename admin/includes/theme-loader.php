<?php
// Load admin theme
function loadAdminTheme() {
    // Default theme
    $theme = [
        'primary' => '#4f46e5',
        'primary-dark' => '#4338ca',
        'primary-light' => '#a5b4fc'
    ];
    
    // Check for saved theme
    if (isset($_SESSION['admin_theme'])) {
        $themeName = $_SESSION['admin_theme'];
        
        switch ($themeName) {
            case 'purple':
                $theme = [
                    'primary' => '#8b5cf6',
                    'primary-dark' => '#7c3aed',
                    'primary-light' => '#c4b5fd'
                ];
                break;
            case 'green':
                $theme = [
                    'primary' => '#10b981',
                    'primary-dark' => '#059669',
                    'primary-light' => '#a7f3d0'
                ];
                break;
            case 'blue':
                $theme = [
                    'primary' => '#3b82f6',
                    'primary-dark' => '#2563eb',
                    'primary-light' => '#93c5fd'
                ];
                break;
            case 'orange':
                $theme = [
                    'primary' => '#f97316',
                    'primary-dark' => '#ea580c',
                    'primary-light' => '#fed7aa'
                ];
                break;
            case 'red':
                $theme = [
                    'primary' => '#ef4444',
                    'primary-dark' => '#dc2626',
                    'primary-light' => '#fecaca'
                ];
                break;
        }
    }
    
    return $theme;
}

// Generate CSS variables for theme
function getThemeCssVariables() {
    $theme = loadAdminTheme();
    
    return "
        --primary: {$theme['primary']};
        --primary-dark: {$theme['primary-dark']};
        --primary-light: {$theme['primary-light']};
    ";
}
?>