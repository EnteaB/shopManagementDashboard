<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once 'includes/theme-loader.php'; // Include theme loader

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch all users
try {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching users");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --primary-light: #a5b4fc;
        --secondary: #64748b;
        --success: #22c55e;
        --warning: #eab308;
        --danger: #ef4444;
        --info: #06b6d4;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-800: #1e293b;
        
        /* Apply dynamic theme variables */
        <?php echo getThemeCssVariables(); ?>
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    body {
        background: linear-gradient(135deg,rgb(189, 204, 243) 0%, #f0f5ff 100%);
        min-height: 100vh;
    }

    .sidebar {
        background: linear-gradient(135deg, rgb(225, 225, 225) 0%, var(--primary) 100%);
        padding: 2rem;
        border-right: 1px solid var(--gray-200);
        position: fixed;
        height: 100vh;
        width: 250px;
        transition: all 0.3s ease;
        z-index: 1000;
        transform: translateX(-250px);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
        margin-bottom: 2.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .logout-btn {
        position: fixed;
        bottom: 1.5rem;
        left: 1rem;
        width: calc(250px - 2rem);
        padding: 0.75rem 1rem;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .logout-btn:hover {
        background: #dc2626;
    }

    .main-content {
        padding: 4rem 8rem;
        margin-left: 0;
        width: 100%;
        transition: all 0.3s ease;
        background: transparent; /* Add this to ensure transparency */
    }

    .main-content.shifted {
        padding-left: calc(8rem + 250px);
    }

    .menu-toggle {
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 1100;
        background: white;
        border: none;
        padding: 0.4rem;
        border-radius: 0.4rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
    }

    .menu-toggle i {
        font-size: 0.875rem;
        color: var(--gray-800);
    }

    .header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 1rem 3rem;
        border-radius: 0.75rem;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        margin: 1rem auto 2.5rem;
       width: 100%;           /* Full width */
    max-width: none;
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: slideDown 0.5s ease-out;
    }

    .header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        animation: shine 2s infinite;
    }

    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .header-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.2rem;
    }

    .header-icon i {
        font-size: 1.25rem;
        color: white;
    }

    .header h1 {
        color: white;
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        width: 100%;
        text-align: center;
    }

    /* User management specific styles */
    .users-container {
        width: 95%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .tools-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .search-filter {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .search-input {
        padding: 0.65rem 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 0.5rem;
        width: 280px;
        font-size: 0.95rem;
        background-color: white;
    }

    .filter-select {
        padding: 0.65rem 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 0.5rem;
        background-color: white;
        font-size: 0.95rem;
    }

    .add-user-btn {
        padding: 0.75rem 1.5rem;
        background: var(--success);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .add-user-btn:hover {
        background: #16a34a;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .users-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .user-card {
        background: white;
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid var(--gray-200);
    }

    .user-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .user-header {
        background: var(--gray-50);
        padding: 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.25rem;
        font-weight: 600;
    }

    .user-name {
        display: flex;
        flex-direction: column;
    }

    .username {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    .user-email {
        font-size: 0.875rem;
        color: var(--secondary);
    }

    .user-body {
        padding: 1.25rem;
    }

    .user-role {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .role-admin {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--primary);
    }

    .role-manager_women {
        background-color: rgba(236, 72, 153, 0.1);
        color: #ec4899;
    }

    .role-manager_men {
        background-color: rgba(14, 165, 233, 0.1);
        color: #0ea5e9;
    }

    .role-user {
        background-color: rgba(100, 116, 139, 0.1);
        color: var(--secondary);
    }

    .user-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 1.25rem;
    }

    .user-btn {
        padding: 0.65rem 1.25rem;
        border-radius: 0.5rem;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-edit {
        background: var(--primary-light);
        color: var(--primary-dark);
    }

    .btn-edit:hover {
        background: var(--primary);
        color: white;
    }

    .btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .btn-delete:hover {
        background: var(--danger);
        color: white;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 1100;
    }

    .modal.active {
        display: flex;
        animation: fadeIn 0.3s ease-out;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .modal-content h2 {
        margin-bottom: 1.5rem;
        color: var(--gray-800);
        font-size: 1.5rem;
        font-weight: 600;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--gray-100);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--gray-800);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--gray-200);
        border-radius: 0.5rem;
        font-size: 0.95rem;
        background: var(--gray-50);
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .form-group small {
        display: block;
        margin-top: 0.5rem;
        color: var(--secondary);
        font-size: 0.875rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .form-actions button {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        flex: 1;
    }

    .btn-submit {
        background: var(--primary);
        color: white;
    }

    .btn-submit:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-cancel {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .btn-cancel:hover {
        background: var(--gray-300);
        transform: translateY(-2px);
    }

    .pagination {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .page-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background: white;
        border: 1px solid var(--gray-200);
        cursor: pointer;
    }

    .page-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .no-results {
        text-align: center;
        padding: 2rem;
        background: white;
        border-radius: 0.75rem;
        color: var(--secondary);
        font-size: 1.1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--gray-200);
    }

    /* Updated styles for a smaller user detail modal */
.user-detail-modal {
    width: 90%;
    max-width: 500px; /* Matches the add user modal max-width */
    padding: 0;
    border-radius: 1rem; /* Match the add user modal border-radius */
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); /* Match the add user modal shadow */
}

/* Smaller header */
.user-detail-header {
    padding: 1.25rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
}

/* Smaller avatar */
.user-detail-avatar {
    width: 45px;
    height: 45px;
    font-size: 1.1rem;
    margin-right: 0.75rem;
    background: rgba(122, 37, 37, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Smaller title */
.user-detail-name h2 {
    font-size: 1.4rem;
    margin: 0 0 0.25rem 0;
}

/* Smaller content sections */
.user-detail-grid {
    gap: 1rem;
    padding: 1.25rem;
}

.user-detail-section {
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

/* Remove the activity section to make it shorter */
.activity-section {
    display: none;
}

/* Smaller footer */
.user-detail-footer {
    padding: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.user-detail-footer .user-btn {
    padding: 0.4rem 0.75rem;
    font-size: 0.8rem;
}

/* Close button styles */
.close-detail-btn {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.user-detail-section h3 {
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-detail-section h3 i {
    color: var(--primary);
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.4rem 0;
    font-size: 0.85rem;
    border-bottom: 1px dotted var(--gray-200);
}

.detail-item:last-child {
    border-bottom: none;
}

.admin-notice {
    background: rgba(99, 102, 241, 0.1);
    padding: 0.5rem;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: var(--primary-dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.admin-notice i {
    color: var(--primary);
}
    </style>
</head>
<body>
    <button class="menu-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-shopping-bag"></i>
            FashShop
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="products.php" class="nav-link">
                <i class="fas fa-tshirt"></i>
                Products
            </a>
            <a href="users.php" class="nav-link active">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </nav>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h1>User Management</h1>
            </div>
        </div>

        <div class="users-container">
            <div class="tools-bar">
                <div class="search-filter">
                    <input type="text" id="searchUser" class="search-input" placeholder="Search users...">
                    <select id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="manager_women">Women's Manager</option>
                        <option value="manager_men">Men's Manager</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <button class="add-user-btn" onclick="showAddModal()">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </button>
            </div>

            <div class="users-grid">
                <?php foreach ($users as $user): ?>
                <div class="user-card" data-user-id="<?= $user['id'] ?>" onclick="viewUserDetails(<?= $user['id'] ?>)">
                    <div class="user-header">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                        <div class="user-name">
                            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                            <span class="user-email"><?= htmlspecialchars($user['email'] ?? 'No email') ?></span>
                        </div>
                    </div>
                    <div class="user-body">
                        <span class="user-role role-<?= $user['role'] ?>">
                            <?= str_replace('_', ' ', ucfirst($user['role'])) ?>
                        </span>
                        
                        <div class="user-actions" onclick="event.stopPropagation()">
                            <button class="user-btn btn-edit" onclick="editUser(<?= $user['id'] ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="user-btn btn-delete" onclick="deleteUser(<?= $user['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if(count($users) === 0): ?>
            <div class="no-results">
                <i class="fas fa-search"></i> No users found
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New User</h2>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
               <div class="form-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <small class="hint" style="display: none;">Leave empty to keep current password</small>
    <small class="password-hint">Password must be at least 8 char</small>
</div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="manager_women">Women's Manager</option>
                        <option value="manager_men">Men's Manager</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Save User</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const menuToggle = document.querySelector('.menu-toggle');
        
        // Sidebar toggle
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
            
            // Store sidebar state
            const isOpen = sidebar.classList.contains('active');
            localStorage.setItem('sidebarOpen', isOpen);
        });
    
        // Restore sidebar state
        const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
        if (sidebarOpen) {
            sidebar.classList.add('active');
            mainContent.classList.add('shifted');
        }

        // Search filter
        const searchInput = document.getElementById('searchUser');
        const roleFilter = document.getElementById('roleFilter');
        
        if (searchInput) {
            searchInput.addEventListener('input', filterUsers);
        }
        
        if (roleFilter) {
            roleFilter.addEventListener('change', filterUsers);
        }
    });
    // Add this function to validate password length
function validatePassword(password, isEditMode) {
    // If in edit mode and password is empty, it's valid (keeping existing password)
    if (isEditMode && password === '') {
        return true;
    }
    
    // Check for minimum length
    if (password.length < 8) {
        alert('Password must be at least 8 characters long');
        return false;
    }
    
    return true;
}


    function filterUsers() {
        const searchTerm = document.getElementById('searchUser').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
        const userCards = document.querySelectorAll('.user-card');
        
        let visibleCount = 0;
        
        userCards.forEach(card => {
            const username = card.querySelector('.username').textContent.toLowerCase();
            const email = card.querySelector('.user-email').textContent.toLowerCase();
            const role = card.querySelector('.user-role').textContent.toLowerCase();
            
            // Fix role matching - Improved normalization
            let normalizedRole;
            if (role.includes("women ")) {
                normalizedRole = "manager_women";
            } else if (role.includes("men ")) {
                normalizedRole = "manager_men";
            } else if (role.includes("admin")) {
                normalizedRole = "admin";
            } else {
                normalizedRole = "user";
            }
            
            const matchesSearch = username.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = roleFilter === '' || normalizedRole === roleFilter;
            
            if (matchesSearch && matchesRole) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide "no results" message
        const noResults = document.querySelector('.no-results');
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function showAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('password').required = true;
        document.querySelector('.hint').style.display = 'none';
        document.getElementById('userModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('active');
    }

    async function editUser(userId) {
        try {
            const response = await fetch(`ajax/get_user.php?id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('userId').value = data.user.id;
                document.getElementById('username').value = data.user.username;
                document.getElementById('email').value = data.user.email;
                document.getElementById('role').value = data.user.role;
                document.getElementById('password').required = false;
                document.querySelector('.hint').style.display = 'block';
                document.getElementById('userModal').classList.add('active');
            } else {
                alert(data.message || 'Error loading user data');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading user data');
        }
    }

    function deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }
        
        fetch('ajax/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`.user-card[data-user-id="${userId}"]`);
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                card.style.transition = 'all 0.3s ease';
                setTimeout(() => card.remove(), 300);
                alert('User deleted successfully');
            } else {
                throw new Error(data.message || 'Failed to delete user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }

    // Update the form submit event listener to keep modal open on validation errors
document.getElementById('userForm').addEventListener('submit', function(e) {
    // Always prevent default form submission first
    e.preventDefault();
    
    // Get password and check if we're in edit mode
    const password = document.getElementById('password').value;
    const isEditMode = document.getElementById('userId').value !== '';
    
    // Validate password - if not valid, just return without closing modal
    if (!validatePassword(password, isEditMode)) {
        return; // Stop execution here if validation fails, but leave modal open
    }
    
    // Only proceed if validation passes
    const formData = new FormData(this);
    const submitBtn = this.querySelector('.btn-submit');
    submitBtn.disabled = true;

    fetch('ajax/save_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Refresh to show updated user list
            closeModal(); // Only close modal on success
        } else {
            // Re-enable the submit button but don't close the modal
            submitBtn.disabled = false;
            throw new Error(data.message || 'Failed to save user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
        submitBtn.disabled = false; // Re-enable the submit button
        // Don't close the modal on error
    });
});

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Updated viewUserDetails function with role-specific information
function viewUserDetails(userId) {
    // Get the user card to extract basic information
    const userCard = document.querySelector(`.user-card[data-user-id="${userId}"]`);
    const username = userCard.querySelector('.username').textContent;
    const email = userCard.querySelector('.user-email').textContent;
    const role = userCard.querySelector('.user-role').textContent;
    const avatar = userCard.querySelector('.user-avatar').textContent;
    
    // Create different content based on user role
    let detailSections = '';
    
    // Check if user is admin - show minimal info
    if (role.toLowerCase().includes('admin')) {
        detailSections = `
            <div class="user-detail-section">
                <h3><i class="fas fa-shield-alt"></i> Admin Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${email}</span>
                </div>
                <div class="admin-notice">
                    <i class="fas fa-info-circle"></i>
                    Additional admin details are restricted for security reasons.
                </div>
            </div>`;
    } 
    // Manager-specific information
    else if (role.toLowerCase().includes('manager')) {
        const hireDate = formatDate(generateRandomDate(new Date(2020, 0, 1), new Date(2022, 11, 31)));
        const departmentType = role.toLowerCase().includes('women') ? "Women's" : "Men's";
        const recentActions = [
            {action: `Updated ${departmentType} inventory`, date: formatDate(generateRandomDate(new Date(Date.now() - 86400000*2), new Date()))},
            {action: `Added new ${departmentType} collection`, date: formatDate(generateRandomDate(new Date(Date.now() - 86400000*5), new Date()))}
        ];
        
        detailSections = `
            <div class="user-detail-section">
                <h3><i class="fas fa-briefcase"></i> Manager Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value">${departmentType} Collection</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Start Date:</span>
                    <span class="detail-value">${hireDate}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${email}</span>
                </div>
            </div>
            <div class="user-detail-section">
                <h3><i class="fas fa-tasks"></i> Recent Activities</h3>
                ${recentActions.map(item => `
                    <div class="detail-item">
                        <span class="detail-label">${item.date}</span>
                        <span class="detail-value">${item.action}</span>
                    </div>
                `).join('')}
            </div>`;
    } 
    // Regular user information
    else {
        const joinDate = formatDate(generateRandomDate(new Date(2022, 0, 1), new Date()));
        const lastLogin = formatDate(generateRandomDate(new Date(2023, 0, 1), new Date()));
        const purchaseCount = Math.floor(Math.random() * 20);
        const totalSpent = (Math.random() * 1000 + 50).toFixed(2);
        
        detailSections = `
            <div class="user-detail-section">
                <h3><i class="fas fa-user"></i> User Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${email}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Join Date:</span>
                    <span class="detail-value">${joinDate}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Last Login:</span>
                    <span class="detail-value">${lastLogin}</span>
                </div>
            </div>
            <div class="user-detail-section">
                <h3><i class="fas fa-shopping-bag"></i> Shopping Stats</h3>
                <div class="detail-item">
                    <span class="detail-label">Orders:</span>
                    <span class="detail-value">${purchaseCount}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Total Spent:</span>
                    <span class="detail-value">€${totalSpent}</span>
                </div>
            </div>`;
    }
    
    // Create the modal HTML
    let modalHTML = `
    <div id="userDetailModal" class="modal active">
        <div class="modal-content user-detail-modal">
            <div class="user-detail-header">
                <div class="user-detail-avatar">${avatar}</div>
                <div class="user-detail-name">
                    <h2>${username}</h2>
                    <span class="user-detail-role role-${role.toLowerCase().replace(' ', '_')}">${role}</span>
                </div>
                <button class="close-detail-btn" onclick="closeUserDetailModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="user-detail-grid">
                ${detailSections}
            </div>
            
            <div class="user-detail-footer">
                <button class="user-btn btn-edit" onclick="editUser(${userId}); closeUserDetailModal();">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="user-btn btn-delete" onclick="deleteUser(${userId}); closeUserDetailModal();">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    `;
    
    // Add the modal to the document
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Helper function to close the user detail modal
function closeUserDetailModal() {
    const modal = document.getElementById('userDetailModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Helper function to generate a random date between two dates
function generateRandomDate(start, end) {
    return new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
}

// Helper function to format a date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper function to get an icon for activity type
function getActivityIcon(activityType) {
    switch (activityType) {
        case 'Logged in': return 'fa-sign-in-alt';
        case 'Updated profile': return 'fa-user-edit';
        case 'Made purchase': return 'fa-shopping-cart';
        case 'Added item to cart': return 'fa-cart-plus';
        case 'Wrote review': return 'fa-comment';
        default: return 'fa-circle';
    }
}
    </script>
</body>
</html>