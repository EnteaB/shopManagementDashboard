document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    // Setup global search
    setupGlobalSearch();
    
    // Setup notifications
    setupNotifications();
    
    // Setup user menu
    setupUserMenu();
    
    // Setup sidebar navigation
    setupSidebarNav();
}

function setupGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleGlobalSearch, 300));
    }
}

function setupNotifications() {
    const notifyBtn = document.querySelector('.notification-btn');
    if (notifyBtn) {
        notifyBtn.addEventListener('click', toggleNotifications);
    }
}

function setupUserMenu() {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', function() {
            this.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });
    }
}

function setupSidebarNav() {
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-item');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function handleGlobalSearch(e) {
    const searchTerm = e.target.value.trim();
    if (searchTerm.length > 2) {
        fetch(`ajax/search.php?term=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchResults(data.results);
                }
            })
            .catch(error => console.error('Error:', error));
    }
}

function displaySearchResults(results) {
    // Implement search results display logic
}

function toggleNotifications() {
    // Implement notifications panel toggle
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }, 100);
}