document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchUser');
    searchInput.addEventListener('input', debounce(function() {
        filterUsers();
    }, 300));
});

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

async function handleSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('ajax/save_user.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'An error occurred');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
    return false;
}

async function editUser(id) {
    try {
        const response = await fetch(`ajax/get_user.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
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

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch('ajax/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Error deleting user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user');
        });
    }
}

async function filterUsers() {
    const search = document.getElementById('searchUser').value;
    const role = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    try {
        const response = await fetch('ajax/filter_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ search, role, status })
        });
        
        const data = await response.json();
        if (data.success) {
            document.querySelector('.users-grid').innerHTML = data.html;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error', 'Error filtering users', 'error');
    }
}

function showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <strong>${title}</strong>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}