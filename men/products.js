document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const menuToggle = document.querySelector('.menu-toggle');
    
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
    });

    // Initialize sidebar state
    if (localStorage.getItem('sidebarOpen') === 'true') {
        sidebar.classList.add('active');
        mainContent.classList.add('shifted');
    }

    // Save sidebar state when changed
    sidebar.addEventListener('transitionend', () => {
        localStorage.setItem('sidebarOpen', sidebar.classList.contains('active'));
    });
});

// Modal functions
function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'flex';
}

function closeAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
    document.getElementById('addProductForm').reset();
}

function editProduct(id) {
    fetch(`ajax/get-product.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                const form = document.getElementById('editProductForm');
                
                form.querySelector('#productId').value = product.id;
                form.querySelector('#name').value = product.name;
                form.querySelector('#category').value = product.subcategory;
                form.querySelector('#description').value = product.description;
                form.querySelector('#price').value = product.price;
                form.querySelector('#stock').value = product.stock;
                form.querySelector('#size').value = product.size;
                
                // Show current image
                const currentImage = document.getElementById('currentImage');
                if (currentImage) {
                    currentImage.src = `../uploads/${product.image}`;
                    currentImage.style.display = 'block';
                }
                
                document.getElementById('editProductModal').style.display = 'flex';
            } else {
                alert('Failed to load product details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load product details');
        });
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('ajax/delete-product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product deleted successfully');
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to delete product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete product: ' + error.message);
        });
    }
}

// Add Product Form Handler
document.getElementById('addProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitButton = this.querySelector('.save-btn');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Saving...';
    
    try {
        const formData = new FormData(this);
        const response = await fetch('ajax/add-product.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Product added successfully');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Failed to add product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Save Product';
    }
});

// Edit Product Form Handler
document.getElementById('editProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitButton = this.querySelector('.save-btn');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Saving...';
    
    try {
        const formData = new FormData(this);
        const response = await fetch('ajax/edit-product.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Product updated successfully');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Failed to update product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Save Changes';
    }
});