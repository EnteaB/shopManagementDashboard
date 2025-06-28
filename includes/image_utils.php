<?php
function normalizeImagePath($path) {
    // Remove any leading ../ or ./ from the path
    $path = ltrim($path, './');
    
    // Handle empty or null paths
    if (empty($path)) {
        return 'assets/images/products.jpg';
    }
    
    // Remove any double slashes
    $path = preg_replace('#/+#', '/', $path);
    
    // If path doesn't include directory structure, add it
    if (strpos($path, '/') === false) {
        $path = 'assets/images/products/' . $path;
    }
    // If path doesn't start with assets/, add it
    elseif (strpos($path, 'assets/') !== 0) {
        $path = 'assets/' . $path;
    }
    
    return $path;
}

function getFullImageUrl($path) {
    if (empty($path)) {
        return '../assets/images/products.jpg';
    }
    
    $normalizedPath = normalizeImagePath($path);
    
    // Check if file exists in multiple possible locations
    $possiblePaths = [
        __DIR__ . '/../' . $normalizedPath,
        __DIR__ . '/../assets/images/products/' . basename($path),
        __DIR__ . '/../../' . $normalizedPath,
        __DIR__ . '/../../assets/images/products/' . basename($path)
    ];
    
    foreach ($possiblePaths as $possiblePath) {
        if (file_exists($possiblePath)) {
            return '../' . $normalizedPath;
        }
    }
    
    error_log("Image not found: " . implode(", ", $possiblePaths));
    return '../assets/images/placeholder.jpg';
}

/**
 * Gets the correct image path for a product image
 * 
 * @param string $imagePath The image path from the database
 * @return string The formatted path for HTML display
 */
function getImagePath($imagePath) {
    // If path is empty, return placeholder
    if (empty($imagePath)) {
        return '../assets/images/placeholder.jpg';
    }
    
    // Check if it's already a full path or just filename
    if (strpos($imagePath, '/') !== false) {
        // It's a path - make sure it's properly formatted
        $path = (strpos($imagePath, '../') === 0) ? $imagePath : '../' . ltrim($imagePath, '/');
    } else {
        // It's just a filename - append the uploads directory
        $path = '../uploads/' . $imagePath;
    }
    
    // Verify file exists
    if (file_exists($path)) {
        return $path;
    } else {
        // Log missing file for debugging
        error_log("Image not found: " . $path);
        return '../assets/images/placeholder.jpg';
    }
}

function debugImagePath($path) {
    $normalized = normalizeImagePath($path);
    $possiblePaths = [
        __DIR__ . '/../' . $normalized,
        __DIR__ . '/../assets/images/products/' . basename($path),
        __DIR__ . '/../../' . $normalized,
        __DIR__ . '/../../assets/images/products/' . basename($path)
    ];
    
    $exists = false;
    $foundPath = '';
    foreach ($possiblePaths as $fullPath) {
        if (file_exists($fullPath)) {
            $exists = true;
            $foundPath = $fullPath;
            break;
        }
    }
    
    return [
        'original' => $path,
        'normalized' => $normalized,
        'checked_paths' => $possiblePaths,
        'found_path' => $foundPath,
        'exists' => $exists
    ];
}
?>