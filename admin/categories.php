<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$isAdmin = true;
require_once '../includes/header.php';
require_once '../includes/db.php';

// Fetch all categories
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result();
?>

<div class="admin-dashboard">
    <?php require_once 'sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Category Management</h1>
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>

        <div class="category-grid">
            <?php while ($category = $categories->fetch_assoc()): ?>
            <div class="category-card">
                <div class="category-header">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <div class="category-actions">
                        <button class="btn btn-primary" onclick="editCategory(<?php echo $category['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <p>Products in this category: 
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                    $stmt->bind_param("i", $category['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->fetch_assoc();
                    echo $count['count'];
                    ?>
                </p>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <h2 id="modalTitle">Add Category</h2>
        <form id="categoryForm">
            <input type="hidden" id="categoryId" name="categoryId">
            <div class="form-group">
                <label for="categoryName">Category Name</label>
                <input type="text" id="categoryName" name="categoryName" class="form-control" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryForm = document.getElementById('categoryForm');
    categoryForm.addEventListener('submit', handleFormSubmit);
});

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryModal').classList.add('active');
}

function closeModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('category_process.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        fetch('category_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php 
$stmt->close();
$conn->close();
require_once '../includes/footer.php'; 
?>