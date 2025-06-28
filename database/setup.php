<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function showStatus($message, $success = true) {
    echo sprintf(
        '<div style="color: %s; padding: 10px; margin: 5px 0;">%s %s</div>',
        $success ? 'green' : 'red',
        $success ? '✅' : '❌',
        htmlspecialchars($message)
    );
}

try {
    $pdo = new PDO(
        "mysql:host=localhost",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Drop existing database and recreate
    $pdo->exec("DROP DATABASE IF EXISTS fashshop");
    $pdo->exec("CREATE DATABASE fashshop");
    $pdo->exec("USE fashshop");
    showStatus("Database recreated successfully");

    // Create tables
    $queries = [
        // Users table
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Categories table
        "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT,
            image VARCHAR(255),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        )",

        // Products table
        "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            sale_price DECIMAL(10,2),
            stock INT DEFAULT 0,
            category_id INT,
            image VARCHAR(255),
            status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
            featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )",

        // Orders table
        "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            billing_address TEXT NOT NULL,
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",

        // Order Items table
        "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )",

        // Product Images table
        "CREATE TABLE product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",

        // Product Reviews table
        "CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    ];

    // Execute each query
    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
    showStatus("All tables created successfully");

    // Create admin user
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, first_name, last_name) VALUES (?, ?, ?, 'admin', 'Admin', 'User')");
    $stmt->execute(['admin', $hash, 'admin@fashshop.com']);
    showStatus("Admin user created (username: admin, password: admin123)");

    // Insert sample categories
    $categories = [
        ['Men', 'men', 'Men\'s Fashion'],
        ['Women', 'women', 'Women\'s Fashion'],
        ['Accessories', 'accessories', 'Fashion Accessories']
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    showStatus("Sample categories created");

    showStatus("Database setup completed successfully!");

} catch (PDOException $e) {
    showStatus("Error: " . $e->getMessage(), false);
}
?>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
div { background: white; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 10px; }
</style>