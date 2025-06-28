<?php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';
require_once '../includes/auth.php';

session_start();
requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check if slug column exists
    $columns = $conn->query("SHOW COLUMNS FROM products LIKE 'slug'")->fetchAll();
    
    if (empty($columns)) {
        // Add slug column if it doesn't exist
        $conn->exec("ALTER TABLE products ADD COLUMN slug VARCHAR(255) UNIQUE");
        echo "Added slug column to products table.<br>";
        
        // Update existing products with slugs
        $stmt = $conn->query("SELECT id, name FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['name']), '-'));
            $slug = $baseSlug;
            
            // Ensure slug uniqueness
            $counter = 1;
            while (true) {
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
                $checkStmt->execute([$slug, $product['id']]);
                $count = $checkStmt->fetchColumn();
                
                if ($count == 0) break;
                
                $slug = $baseSlug . '-' . $counter++;
            }
            
            $updateStmt = $conn->prepare("UPDATE products SET slug = ? WHERE id = ?");
            $updateStmt->execute([$slug, $product['id']]);
        }
        
        echo "Updated existing products with slugs.<br>";
    } else {
        echo "Slug column already exists.<br>";
    }
    
    echo "Database structure check completed.";
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>