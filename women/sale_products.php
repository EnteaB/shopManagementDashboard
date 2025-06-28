<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Use the requireWomenManager function from auth.php
requireWomenManager();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get products on sale for women's category
    $stmt = $conn->prepare("
        SELECT p.*, d.discount_percent 
        FROM products p 
        JOIN discounts d ON p.id = d.product_id 
        WHERE p.category = 'female' 
        AND d.discount_percent > 0
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Database error occurred";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products on Sale - Women's Section</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF69B4;
            --primary-dark: #FF1493;
            --red: #dc3545;
            --green: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .discount-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--red);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .price-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .original-price {
            text-decoration: line-through;
            color: #666;
        }

        .discounted-price {
            color: var(--red);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .discount-settings {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .discount-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .discount-input {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Products on Sale</h1>
            <div class="nav-buttons">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-box"></i> All Products
                </a>
            </div>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="discount-badge">-<?= $product['discount_percent'] ?>%</div>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="product-image">
                <div class="product-details">
                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="price-container">
                        <span class="original-price">€<?= number_format($product['price'], 2) ?></span>
                        <span class="discounted-price">€<?= number_format($product['price'] * (1 - $product['discount_percent']/100), 2) ?></span>
                    </div>
                    <div class="discount-settings">
                        <form class="discount-form" onsubmit="updateDiscount(event, <?= $product['id'] ?>)">
                            <input type="number" 
                                   class="discount-input" 
                                   value="<?= $product['discount_percent'] ?>" 
                                   min="0" 
                                   max="99" 
                                   step="1"
                                   required>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function updateDiscount(event, productId) {
        event.preventDefault();
        const form = event.target;
        const discountPercent = form.querySelector('input').value;

        fetch('update_discount.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                discount_percent: discountPercent
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating discount');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating discount');
        });
    }
    </script>
</body>
</html>