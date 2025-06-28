<?php
<?php
/**
 * Generate a URL-friendly slug from a string
 * @param string $string The string to convert to a slug
 * @param string $separator The separator to use (default: hyphen)
 * @return string The generated slug
 */
function generateSlug($string, $separator = '-') {
    // Convert to lowercase and remove accents/special characters
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', $separator, $string);
    $string = preg_replace('/-+/', $separator, $string);
    $string = trim($string, $separator);
    return $string;
}

/**
 * Create a unique slug for a product
 * @param PDO $conn Database connection
 * @param string $name Product name to generate slug from
 * @param int|null $id Product ID to exclude from uniqueness check (for updates)
 * @return string Unique slug
 */
function createUniqueSlug($conn, $name, $id = null) {
    $baseSlug = generateSlug($name);
    $slug = $baseSlug;
    $counter = 1;
    
    // Check if slug exists
    while (true) {
        $query = "SELECT COUNT(*) FROM products WHERE slug = :slug";
        $params = [':slug' => $slug];
        
        // If updating an existing product, exclude it from the check
        if ($id !== null) {
            $query .= " AND id != :id";
            $params[':id'] = $id;
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            break; // Slug is unique
        }
        
        // Try with a counter appended
        $slug = $baseSlug . '-' . $counter++;
    }
    
    return $slug;
}