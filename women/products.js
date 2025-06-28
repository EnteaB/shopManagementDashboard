// Function to show the add/edit modal
function showModal() {
    document.getElementById('productModal').classList.add('active');
}

// Function to close the modal
function closeModal() {
    document.getElementById('productModal').classList.remove('active');
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Product';
}

// Function to show add modal
function showAddModal() {
    closeModal(); // Reset form first
    showModal();
}

// Function to edit product
function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate form with product data
                document.getElementById('productId').value = data.data.id;
                document.getElementById('name').value = data.data.name;
                document.getElementById('description').value = data.data.description || '';
                document.getElementById('price').value = data.data.price;
                document.getElementById('stock').value = data.data.stock;
                
                // Update modal title
                document.getElementById('modalTitle').textContent = 'Edit Product';
                
                // Show modal
                showModal();
            } else {
                alert('Error loading product details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product details');
        });
}

// Function to delete product
function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const element = document.querySelector(`[data-product-id="${id}"]`);
                if (element) {
                    element.remove();
                }
                alert('Product deleted successfully');
            } else {
                alert(data.message || 'Error deleting product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting product');
        });
    }
}

// Handle form submission
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('save_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Product saved successfully');
            window.location.reload(); // Reload to show changes
        } else {
            alert(data.message || 'Error saving product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving product');
    });
});