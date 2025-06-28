function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('image').required = true;
    document.getElementById('productModal').classList.add('active');
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}

async function handleSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    // Log the form data to the console
    console.log('Form Data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ', ' + pair[1]);
    }

    try {
        const response = await fetch('ajax/save_product.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            console.error('Error saving product:', data.message); // Log the error to the console
            alert('Failed to save product. Please check the form and try again.'); // User-friendly message
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save product due to a network error. Please try again.'); // Network error message
    }
    return false;
}

async function editProduct(id) {
    console.log('Editing product with ID:', id);
    try {
        const response = await fetch(`ajax/get_product.php?id=${id}`);
        console.log('Response:', response); // Log the response
        const data = await response.json();
        console.log('Data:', data); // Log the data

        if (data.success) {
            const product = data.product;
            console.log('Product:', product);
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;
            document.getElementById('category').value = product.category;
            document.getElementById('image').value = product.image;
            document.getElementById('productModal').classList.add('active');

            // Add these console.log statements to verify the values
            console.log('Name:', document.getElementById('name').value);
            console.log('Description:', document.getElementById('description').value);
            console.log('Price:', document.getElementById('price').value);
            console.log('Stock:', document.getElementById('stock').value);
            console.log('Category:', document.getElementById('category').value);
            console.log('Image:', document.getElementById('image').value);

        } else {
            alert(data.message || 'Error loading product data');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading product data');
    }
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    fetch('delete_product.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the product element from the DOM
            const productElement = document.querySelector(`[data-product-id="${id}"]`);
            if (productElement) {
                productElement.remove();
            }
            alert('Product deleted successfully');
        } else {
            throw new Error(data.message || 'Failed to delete product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting product: ' + error.message);
    });
}