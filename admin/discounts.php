<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT p.*, 
           COALESCE(d.discount_percent, 0) as discount_percent,
           d.id as discount_id,
           d.start_date,
           d.end_date
    FROM products p
    LEFT JOIN discounts d ON p.id = d.product_id
    WHERE d.end_date IS NULL OR d.end_date > NOW()
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4461F2;
            --primary-dark: #3451E2;
            --secondary: #4E5D78;
            --accent: #FFB547;
            --success: #00B074;
            --danger: #FF3B30;
            --info: #5B93FF;
            --light: #F8F9FA;
            --dark: #1C2A53;
            --gray: #8A94A6;
            --white: #ffffff;
            --border: #EFEFEF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--light);
            color: var(--dark);
        }

        .menu-btn {
            position: fixed;
            top: 1rem;
            left: 1rem;
            background: var(--primary);
            color: var(--white);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar {
            position: fixed;
            left: -250px;
            top: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(180deg, #1C2A53 0%, #2A3B6B 100%);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .sidebar.active {
            left: 0;
        }

        .main-content {
            margin-left: 0;
            transition: all 0.3s ease;
            padding: 2rem;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        .page-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .products-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }

        .product-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .discounted-price {
            color: var(--gray);
            text-decoration: line-through;
        }

        .discount-badge {
            background: var(--danger);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .discount-form {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn-apply {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-apply:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-content.shifted {
                margin-left: 0;
            }
            
            .products-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="menu-btn">
        <i class="fas fa-bars"></i>
    </div>

    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tshirt"></i> Products</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="discounts.php" class="active"><i class="fas fa-percent"></i> Discounts</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <h1 class="page-title">Discount Management</h1>
        
        <div class="products-container">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-image">
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-price">
                            <?php if ($product['discount_percent'] > 0): ?>
                                <span class="discounted-price">$<?= number_format($product['price'], 2) ?></span>
                                <span class="current-price">$<?= number_format($product['price'] * (1 - $product['discount_percent']/100), 2) ?></span>
                                <span class="discount-badge"><?= $product['discount_percent'] ?>% OFF</span>
                            <?php else: ?>
                                <span class="current-price">$<?= number_format($product['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <form class="discount-form" onsubmit="applyDiscount(event, <?= $product['id'] ?>)">
                            <div class="form-group">
                                <label>Discount Percentage (%)</label>
                                <input type="number" min="0" max="100" required 
                                       name="discount_percent" 
                                       value="<?= $product['discount_percent'] ?>"
                                       placeholder="Enter discount percentage">
                            </div>
                            <button type="submit" class="btn-apply">
                                <i class="fas fa-check"></i>
                                <?= $product['discount_percent'] > 0 ? 'Update Discount' : 'Apply Discount' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Burger menu functionality
        document.querySelector('.menu-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('shifted');
        });

        // Apply discount functionality
        function applyDiscount(event, productId) {
            event.preventDefault();
            const form = event.target;
            const submitBtn = form.querySelector('.btn-apply');
            submitBtn.disabled = true;
            
            const discountPercent = form.discount_percent.value;

            fetch('ajax/save_discount.php', {
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
                    throw new Error(data.message || 'Failed to apply discount');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
            });
        }
    </script>
</body>
</html>