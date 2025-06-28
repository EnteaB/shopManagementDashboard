<?php require_once 'header.php'; ?>

<div class="content">
    <div class="page-header">
        <h2>User Management</h2>
        <button class="btn btn-primary" onclick="showAddUserModal()">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="search-filter">
                <input type="text" id="userSearch" placeholder="Search users...">
                <select id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="manager_women">Women's Manager</option>
                    <option value="manager_men">Men's Manager</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <!-- Data will be loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New User</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form id="userForm">
            <input type="hidden" id="userId" name="userId">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
                <small class="hint">Leave empty to keep current password when editing</small>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="manager_women">Women's Manager</option>
                    <option value="manager_men">Men's Manager</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    loadUsers();
    
    // Search functionality
    $('#userSearch').on('input', function() {
        filterUsers();
    });
    
    // Role filter
    $('#roleFilter').on('change', function() {
        filterUsers();
    });
    
    // Form submission
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
});

function loadUsers() {
    $.ajax({
        url: 'ajax/get_users.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUsers(response.users);
            }
        }
    });
}

function displayUsers(users) {
    const tbody = $('#userTableBody');
    tbody.empty();
    
    users.forEach(user => {
        tbody.append(`
            <tr>
                <td>${user.id}</td>
                <td>${user.username}</td>
                <td><span class="badge ${user.role}">${user.role}</span></td>
                <td><span class="status active">Active</span></td>
                <td>
                    <button class="btn-icon" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

function saveUser() {
    const formData = new FormData($('#userForm')[0]);
    
    $.ajax({
        url: 'ajax/save_user.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                closeModal();
                loadUsers();
                showNotification('User saved successfully');
            } else {
                showNotification(response.message, 'error');
            }
        }
    });
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        $.ajax({
            url: 'ajax/delete_user.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    loadUsers();
                    showNotification('User deleted successfully');
                } else {
                    showNotification(response.message, 'error');
                }
            }
        });
    }
}

// Add more functions for editing, filtering, etc.
</script>