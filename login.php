<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Login attempt - User data: " . print_r($user, true));

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            error_log("Login successful - Session data: " . print_r($_SESSION, true));

            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'manager_women':
                    header('Location: women/dashboard.php');
                    break;
                case 'manager_men':
                    header('Location: men/dashboard.php');
                    break;
                default:
                    header('Location: index.php');
            }
            exit();
        } else {
            $error = "Invalid username or password";
            error_log("Login failed - Invalid credentials for username: $username");
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Database error during login: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashShop Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --text: #1f2937;
            --background: #f9fafb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2rem;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 450px;
            transform: translateY(0);
            transition: transform 0.3s ease;
            animation: fadeIn 1s ease-out;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            animation: logoFloat 3s ease-in-out infinite;
        }

        .brand-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-tagline {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.3rem;
            color: #9ca3af;
            transition: all 0.3s ease;
        }

        .form-group input:focus + i {
            color: var(--primary);
            transform: scale(1.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .remember-me input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--primary);
        }

        .login-button {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        @media (max-width: 640px) {
            .login-container {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <svg class="logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20.9 9.5C20.9 15.5 12 21.5 12 21.5C12 21.5 3.1 15.5 3.1 9.5C3.1 5.4 7.1 2.5 12 2.5C16.9 2.5 20.9 5.4 20.9 9.5Z" fill="url(#gradient)" stroke="#4f46e5" stroke-width="2"/>
                <path d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z" fill="white" stroke="#4f46e5" stroke-width="2"/>
                <defs>
                    <linearGradient id="gradient" x1="3.1" y1="2.5" x2="20.9" y2="21.5" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#6366f1"/>
                        <stop offset="100%" stop-color="#ec4899"/>
                    </linearGradient>
                </defs>
            </svg>
            <h1 class="brand-name">FashShop</h1>
            <p class="brand-tagline">Elevate Your Style</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>
    </div>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Add hover effect for icons
        const icons = document.querySelectorAll('.form-group i:not(.password-toggle)');
        icons.forEach(icon => {
            icon.addEventListener('mouseover', function() {
                this.style.transform = 'scale(1.2)';
            });
            icon.addEventListener('mouseout', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>

<?php if (defined('DEBUG') && DEBUG): ?>
<div style="margin-top: 20px; color: #666; font-size: 12px;">
    Debug info:
    <pre>
    <?php
    if (isset($_SESSION)) {
        print_r($_SESSION);
    }
    ?>
    </pre>
</div>
<?php endif; ?>
</body>
</html>