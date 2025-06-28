document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-btn');
    const products = document.querySelectorAll('.product-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Filter products
            const category = tab.dataset.category;
            products.forEach(product => {
                if (category === 'all') {
                    product.style.display = 'flex';
                } else if (category === 'discount') {
                    product.style.display = 
                        parseInt(product.dataset.discount) > 0 ? 'flex' : 'none';
                } else {
                    product.style.display = 
                        product.dataset.category === category ? 'flex' : 'none';
                }
            });
        });
    });

    // Discount Modal Functions
    window.showDiscountModal = function(productId) {
        const modal = document.getElementById('discountModal');
        document.getElementById('discount_product_id').value = productId;
        
        // Set minimum date as today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('discount_start').min = today;
        document.getElementById('discount_end').min = today;
        
        modal.classList.add('active');
    }

    window.closeDiscountModal = function() {
        const modal = document.getElementById('discountModal');
        modal.classList.remove('active');
    }

    // Add discount form submission handler
    document.getElementById('discountForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('save_discount.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Discount saved successfully!');
                closeDiscountModal();
                window.location.reload();
            } else {
                throw new Error(result.message || 'Error saving discount');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving discount: ' + error.message);
        } finally {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Close discount modal when clicking outside
    document.getElementById('discountModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDiscountModal();
        }
    });
});

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').classList.add('active');
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}

function editProduct(id) {
    fetch(`get_product.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const product = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('productId').value = product.id;
                document.getElementById('name').value = product.name;
                document.getElementById('description').value = product.description || '';
                document.getElementById('price').value = product.price;
                document.getElementById('stock').value = product.stock;
                
                document.getElementById('productModal').classList.add('active');
            } else {
                throw new Error(data.message || 'Error loading product data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product data: ' + error.message);
        });
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

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
            throw new Error(data.message || 'Failed to delete product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting product: ' + error.message);
    });
}

function addDiscount(productId) {
    // Prevent event bubbling
    event.stopPropagation();
    
    // Show discount modal
    const modal = document.getElementById('discountModal');
    if (!modal) {
        console.error('Discount modal not found');
        return;
    }

    // Set product ID and reset form
    document.getElementById('discount_product_id').value = productId;
    document.getElementById('discountForm').reset();
    
    // Set minimum date as today
    const today = new Date().toISOString().split('T')[0];
    const startDate = document.getElementById('discount_start');
    const endDate = document.getElementById('discount_end');
    
    if (startDate && endDate) {
        startDate.min = today;
        endDate.min = today;
    }
    
    modal.classList.add('active');
}

// Close modal when clicking outside
document.querySelector('.modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Improved filterAndSortProducts function
function filterAndSortProducts() {
    // Get all product cards
    const productCards = document.querySelectorAll('.product-card');
    
    // Get filter values
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const sizeFilter = document.getElementById('sizeFilter').value;
    const minPrice = document.getElementById('minPrice').value ? parseFloat(document.getElementById('minPrice').value) : 0;
    const maxPrice = document.getElementById('maxPrice').value ? parseFloat(document.getElementById('maxPrice').value) : Infinity;
    const sortValue = document.getElementById('sortBy').value;
    
    // Check the active category tab
    const activeTab = document.querySelector('.tab-btn.active');
    const tabCategory = activeTab ? activeTab.dataset.category : 'all';
    
    // Convert NodeList to Array for sorting
    let cards = Array.from(productCards);
    
    // Apply filters first
    cards.forEach(card => {
        // Get data from the card
        const name = card.querySelector('.product-name').textContent.toLowerCase();
        const cardCategory = card.dataset.category;
        const price = parseFloat(card.dataset.price);
        const hasDiscount = card.querySelector('.discounted-price') !== null;
        
        // Apply tab category filter
        let showCard = true;
        
        if (tabCategory === 'discount') {
            showCard = hasDiscount;
        } else if (tabCategory !== 'all') {
            showCard = cardCategory === tabCategory;
        }
        
        // Then apply search and other filters
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = !categoryFilter || cardCategory === categoryFilter;
        const matchesPrice = price >= minPrice && (maxPrice === Infinity || price <= maxPrice);
        
        // Final visibility decision
        showCard = showCard && matchesSearch && matchesCategory && matchesPrice;
        
        // Apply visibility
        card.style.display = showCard ? 'block' : 'none';
    });
    
    // Now sort the visible cards
    const visibleCards = cards.filter(card => card.style.display !== 'none');
    const productsGrid = document.querySelector('.products-grid');
    
    visibleCards.sort((a, b) => {
        const nameA = a.querySelector('.product-name').textContent.toLowerCase();
        const nameB = b.querySelector('.product-name').textContent.toLowerCase();
        const priceA = parseFloat(a.dataset.price);
        const priceB = parseFloat(b.dataset.price);
        
        switch(sortValue) {
            case 'name-asc':
                return nameA.localeCompare(nameB);
            case 'name-desc':
                return nameB.localeCompare(nameA);
            case 'price-asc':
                return priceA - priceB;
            case 'price-desc':
                return priceB - priceA;
            case 'newest':
                // This would require a created_at attribute in the dataset
                // For now, we'll just keep the current order
                return 0;
            default:
                return 0;
        }
    });
    
    // Reorder cards in the DOM
    visibleCards.forEach(card => {
        productsGrid.appendChild(card);
    });
}

// Add this function to show/hide the "No results" message
function updateNoResultsMessage() {
    const visibleCards = Array.from(document.querySelectorAll('.product-card'))
        .filter(card => card.style.display !== 'none');
    
    let noResultsMsg = document.querySelector('.no-results');
    
    if (visibleCards.length === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results';
            noResultsMsg.innerHTML = '<p>No products match your search criteria</p>';
            document.querySelector('.products-grid').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

// Function to handle clearing all filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('sizeFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sortBy').value = 'name-asc';
    
    // Reset category tabs
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector('.tab-btn[data-category="all"]').classList.add('active');
    
    // Apply filters
    filterAndSortProducts();
    updateNoResultsMessage();
}

// Add event listeners when document is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const menuToggle = document.querySelector('.menu-toggle') || document.createElement('button');
    
    if (!menuToggle.classList.contains('menu-toggle')) {
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(menuToggle);
    }
    
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
    });
    
    // Setup filter and sort event listeners
    document.getElementById('searchInput')?.addEventListener('input', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    document.getElementById('categoryFilter')?.addEventListener('change', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    document.getElementById('sizeFilter')?.addEventListener('change', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    document.getElementById('minPrice')?.addEventListener('input', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    document.getElementById('maxPrice')?.addEventListener('input', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    document.getElementById('sortBy')?.addEventListener('change', () => {
        filterAndSortProducts();
        updateNoResultsMessage();
    });
    
    // Setup category tab event listeners
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(t => {
                t.classList.remove('active');
            });
            
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Update filters
            filterAndSortProducts();
            updateNoResultsMessage();
        });
    });
    
    // Apply initial filtering
    filterAndSortProducts();
    updateNoResultsMessage();
});