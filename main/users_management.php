<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNIS | Users Management</title>
    <link rel="icon" href="assets/gnis_logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { 
            background-color: #f5f5f5; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .table-responsive { max-height: 60vh; }
        .action-btn { margin-right: 0.5em; }
        .filter-panel {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .table-container {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .table th {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-dark);
            font-weight: 600;
        }
        .table th[data-sort] {
            cursor: pointer;
            position: relative;
        }
        .table th[data-sort]:hover {
            background-color: var(--primary-dark);
        }
        .table th[data-sort]::after {
            content: '↕';
            position: absolute;
            right: 8px;
            opacity: 0.7;
        }
        .table th[data-sort].sorted-asc::after {
            content: '↑';
            opacity: 1;
        }
        .table th[data-sort].sorted-desc::after {
            content: '↓';
            opacity: 1;
        }
        .disabled-field {
            opacity: 0.6;
            pointer-events: none;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        .pagination .page-link {
            color: var(--primary-color);
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .current-user-row {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        .current-user-row:hover {
            background-color: rgba(255, 193, 7, 0.2) !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-success text-white p-3">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <img src="assets/gnis_logo.png" alt="GNIS Logo" class="me-4" style="height: 40px;">
                    <h1 class="mb-0 h3">Geodetic Network Information System (GNIS)</h1>
                </div>
                <div class="d-flex align-items-center accnt">
                    <div id="headerAccountDetails" class="me-3 d-none">
                        <a href="account.html" id="headerUserDisplayName" class="fw-bold ms-2 text-decoration-underline text-dark" style="cursor:pointer"></a>
                        <span id="headerUserType" class="ms-2"></span>
                    </div>
                    <button id="loginBtn" class="btn btn-outline-dark d-none"><i class="fas fa-sign-in-alt"></i> Login</button>
                    <button id="logoutBtn" class="btn btn-outline-dark"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav mx-auto">
                    <a class="nav-link" href="home.php"><i class="fas fa-home"></i> GNIS Home</a>
                    <a class="nav-link" href="index.php"><i class="fas fa-search"></i> Explorer</a>
                    <a class="nav-link" href="tracker.php"><i class="fas fa-map-marker"></i> Tracker</a>
                    <a class="nav-link admin-only" href="admin.php"><i class="fas fa-cog"></i> GCP Management</a>
                    <a class="nav-link admin-only" href="requests_management.php"><i class="fas fa-tasks"></i> Requests Management</a>
                    <a class="nav-link active admin-only" href="users_management.php"><i class="fas fa-users"></i> Users Management</a>
                    <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-3">
        <div id="accessDenied" class="alert alert-danger d-none">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Access denied. Admins only.
            <br><small class="text-muted">Note: Moderators have access to other admin features but cannot manage users.</small>
        </div>
        
        <div id="usersMgmtUI" class="d-none">
            <!-- Filter Panel -->
            <div class="filter-panel">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="searchInput" class="form-label">
                            <i class="fas fa-search me-2"></i>Search
                        </label>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search username, email, name...">
                    </div>
                    <div class="col-md-3">
                        <label for="userTypeFilter" class="form-label">
                            <i class="fas fa-filter me-2"></i>User Type
                        </label>
                        <select id="userTypeFilter" class="form-select">
                            <option value="">All Types</option>
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                            <option value="moderator">Moderator</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" id="addUserBtn">
                            <i class="fas fa-plus me-2"></i>Add User
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" onclick="loadUsers()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
        <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="usersTable">
                        <thead>
                            <tr>
                                <th data-sort="username">Username</th>
                                <th data-sort="email">Email</th>
                                <th data-sort="contact_number">Contact Number</th>
                                <th data-sort="user_type">User Type</th>
                                <th data-sort="sex_id">Sex</th>
                                <th data-sort="name_on_certificate">Name on Certificate</th>
                                <th data-sort="created_at">Created</th>
                                <th data-sort="updated_at">Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal" tabindex="-1" id="userModal">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="fas fa-user-plus me-2"></i>Add User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="userForm">
              <input type="hidden" id="userId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <input type="text" class="form-control" id="userUsername" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="userEmail" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-phone me-2"></i>Contact Number
                                    </label>
                                    <input type="text" class="form-control" id="userContactNumber" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-id-card me-2"></i>Name on Certificate
                                    </label>
                                    <input type="text" class="form-control" id="userNameOnCert" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-users me-2"></i>User Type
                                    </label>
                                    <select class="form-select" id="userType">
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                        <option value="moderator">Moderator</option>
                                        <option value="admin">Admin</option>
                </select>
              </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-venus-mars me-2"></i>Sex
                                    </label>
                                    <select class="form-select" id="userSexId"></select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="passwordField">
                            <label class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="text" class="form-control" id="userPassword">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-save me-2"></i>Save
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal" tabindex="-1" id="confirmModal">
      <div class="modal-dialog">
        <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
          <div class="modal-body" id="confirmModalBody"></div>
          <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmModalYes">
                        <i class="fas fa-check me-2"></i>Yes
                    </button>
          </div>
        </div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script type="module">
// --- Access Control: Only admin ---
import { getCurrentUser, initializeAuth } from './js/users/auth.js';

let currentUser = null;

(async function() {
    await initializeAuth();
    currentUser = getCurrentUser();
    if (!currentUser || currentUser.user_type !== 'admin') {
        document.getElementById('accessDenied').classList.remove('d-none');
        return;
    }
    document.getElementById('usersMgmtUI').classList.remove('d-none');
    
    // Update header with user info
    updateHeaderUI();
    
    // Setup logout functionality
    setupLogout();
    
    loadUsers();
})();

function updateHeaderUI() {
    if (currentUser) {
        const headerAccountDetails = document.getElementById('headerAccountDetails');
        const headerUserDisplayName = document.getElementById('headerUserDisplayName');
        const headerUserType = document.getElementById('headerUserType');
        
        headerAccountDetails.classList.remove('d-none');
        headerUserDisplayName.textContent = currentUser.username || 'Admin';
        headerUserType.textContent = 'Admin Account';
    }
}

function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function() {
            try {
                // Clear local storage
                localStorage.removeItem('webgnis_token');
                localStorage.removeItem('gnisUser');
                
                // Redirect to home page
                window.location.href = 'home.php';
            } catch (error) {
                console.error('Logout error:', error);
                // Still redirect even if there's an error
                window.location.href = 'home.php';
            }
        });
    }
}

// --- State ---
let users = [], total = 0, page = 1, perPage = 20;
let search = '', userType = '';

// --- Load users ---
async function loadUsers() {
    const params = new URLSearchParams({
        list: 1, 
        page, 
        per_page: perPage, 
        search, 
        user_type: userType
    });
    const res = await fetch('users_management_api.php?' + params, {
        headers: { 'Authorization': 'Bearer ' + (localStorage.webgnis_token || '') }
    });
    const data = await res.json();
    if (data.error) { 
        alert(data.error); 
        return; 
    }
    users = data.users; 
    total = data.total;
    renderTable();
    renderPagination();
}

// --- Render table ---
function renderTable() {
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = '';
    users.forEach(u => {
        const isCurrentUser = currentUser && u.user_id == currentUser.user_id;
        const tr = document.createElement('tr');
        if (isCurrentUser) {
            tr.classList.add('current-user-row');
        }
        
        const typeLower = (u.user_type || '').toString().toLowerCase();
        const typeLabel = typeLower ? typeLower.charAt(0).toUpperCase() + typeLower.slice(1) : '-';
        const badgeClass = typeLower === 'admin' ? 'text-bg-danger' : typeLower === 'moderator' ? 'text-bg-info' : typeLower === 'company' ? 'text-bg-warning text-dark' : (typeLower === 'individual' ? 'text-bg-primary' : 'text-bg-secondary');
        tr.innerHTML = `
            <td><strong>${u.username}</strong>${isCurrentUser ? ' <span class="badge bg-warning text-dark">(You)</span>' : ''}</td>
            <td>${u.email}</td>
            <td>${u.contact_number || '<span class="text-muted">-</span>'}</td>
            <td>
                <span class="badge ${badgeClass}">
                    ${typeLabel}
                </span>
            </td>
            <td>${window.sexMap && u.sex_id ? (window.sexMap[u.sex_id] || u.sex_id) : '<span class="text-muted">-</span>'}</td>
            <td>${u.name_on_certificate || '<span class="text-muted">-</span>'}</td>
            <td><small>${u.created_at || ''}</small></td>
            <td><small>${u.updated_at || ''}</small></td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-info" onclick="window.viewUser(${u.user_id})" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="window.editUser(${u.user_id})" title="Edit" ${isCurrentUser ? 'disabled' : ''}>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-dark" onclick="window.resetPassword(${u.user_id})" title="Reset Password" ${isCurrentUser ? 'disabled' : ''}>
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="window.deleteUser(${u.user_id})" title="Delete" ${isCurrentUser ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);
    });
}

// --- Pagination ---
function renderPagination() {
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';
    const totalPages = Math.ceil(total / perPage);
    
    if (totalPages <= 1) return;
    
    // Previous button
    if (page > 1) {
        const li = document.createElement('li');
        li.className = 'page-item';
        li.innerHTML = `<a class="page-link" href="#"><i class="fas fa-chevron-left"></i></a>`;
        li.onclick = e => { e.preventDefault(); page = page - 1; loadUsers(); };
        ul.appendChild(li);
    }
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === page ? ' active' : '');
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.onclick = e => { e.preventDefault(); page = i; loadUsers(); };
        ul.appendChild(li);
    }
    
    // Next button
    if (page < totalPages) {
        const li = document.createElement('li');
        li.className = 'page-item';
        li.innerHTML = `<a class="page-link" href="#"><i class="fas fa-chevron-right"></i></a>`;
        li.onclick = e => { e.preventDefault(); page = page + 1; loadUsers(); };
        ul.appendChild(li);
    }
}

// --- Search & Filters ---
document.getElementById('searchInput').oninput = function() { 
    search = this.value; 
    page = 1; 
    loadUsers(); 
};

document.getElementById('userTypeFilter').onchange = function() { 
    userType = this.value; 
    page = 1; 
    loadUsers(); 
};

document.getElementById('addUserBtn').onclick = function() {
    showUserModal('add');
};

// --- Modal helpers ---
let userModal = new bootstrap.Modal(document.getElementById('userModal'));
let confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

function showUserModal(mode, user = {}) {
    document.getElementById('userModalTitle').innerHTML = mode === 'add' ? 
        '<i class="fas fa-user-plus me-2"></i>Add User' : 
        '<i class="fas fa-user-edit me-2"></i>Edit User';
    
    // Reset visibility/state for all options and fields
    const userTypeSelect = document.getElementById('userType');
    Array.from(userTypeSelect.options).forEach(option => { option.style.display = ''; });
    const sexSelect = document.getElementById('userSexId');
    sexSelect.disabled = false;
    sexSelect.classList.remove('disabled-field');

    document.getElementById('userId').value = user.user_id || '';
    document.getElementById('userUsername').value = user.username || '';
    document.getElementById('userEmail').value = user.email || '';
    document.getElementById('userContactNumber').value = user.contact_number || '';
    document.getElementById('userType').value = user.user_type || 'individual';
    document.getElementById('userSexId').value = user.sex_id || '';
    document.getElementById('userNameOnCert').value = user.name_on_certificate || '';
    document.getElementById('userPassword').value = '';
    
    // Show/hide password field
    document.getElementById('passwordField').style.display = mode === 'add' ? 'block' : 'none';
    
    // Handle field restrictions
    handleUserTypeRestrictions(user.user_type || 'individual', mode, user);
    
    userModal.show();
}

function handleUserTypeRestrictions(userType, mode, user) {
    const userTypeSelect = document.getElementById('userType');
    const sexSelect = document.getElementById('userSexId');
    
    if (userType === 'company') {
        // For Company users, disable Sex field
        sexSelect.disabled = true;
        sexSelect.classList.add('disabled-field');
        sexSelect.value = ''; // Clear the value
        
        if (mode === 'edit') {
            // For existing Company users, restrict User Type changes
            userTypeSelect.disabled = true;
        }
    } else {
        // For Individual/Admin, enable Sex field
        sexSelect.disabled = false;
        sexSelect.classList.remove('disabled-field');
        
        if (mode === 'edit') {
            // For existing users, allow User Type changes but restrict based on current type
            userTypeSelect.disabled = false;
            
            if (user && user.user_type === 'company') {
                // Company users cannot change type
                userTypeSelect.disabled = true;
            } else if (user && user.user_type === 'moderator') {
                // Moderators can only change to admin
                Array.from(userTypeSelect.options).forEach(option => {
                    if (option.value !== 'admin' && option.value !== 'moderator') {
                        option.style.display = 'none';
                    }
                });
            } else if (user && user.user_type === 'admin') {
                // Admins can only change to moderator
                Array.from(userTypeSelect.options).forEach(option => {
                    if (option.value !== 'admin' && option.value !== 'moderator') {
                        option.style.display = 'none';
                    }
                });
            } else {
                // Individual users can change to admin or moderator
                Array.from(userTypeSelect.options).forEach(option => {
                    if (option.value === 'company') {
                        option.style.display = 'none';
                    }
                });
            }
        }
    }
}

// User Type change handler
document.getElementById('userType').onchange = function() {
    const selectedType = this.value;
    const sexSelect = document.getElementById('userSexId');
    
    if (selectedType === 'company') {
        // Disable sex field for company users
        sexSelect.disabled = true;
        sexSelect.classList.add('disabled-field');
        sexSelect.value = ''; // Clear the value
    } else {
        // Enable sex field for individual/admin/moderator users
        sexSelect.disabled = false;
        sexSelect.classList.remove('disabled-field');
    }
};

window.editUser = function(id) {
    const user = users.find(u => u.user_id == id);
    if (user) showUserModal('edit', user);
};

window.viewUser = function(id) {
    const user = users.find(u => u.user_id == id);
    if (!user) return;
    
    const sexName = window.sexMap && user.sex_id ? window.sexMap[user.sex_id] : 'Not specified';
    
    alert(`User Details:
Username: ${user.username}
Email: ${user.email}
Contact Number: ${user.contact_number || 'Not specified'}
User Type: ${user.user_type}
Sex: ${sexName}
Name on Certificate: ${user.name_on_certificate || 'Not specified'}
Created: ${user.created_at || 'Not specified'}
Updated: ${user.updated_at || 'Not specified'}`);
};

window.resetPassword = function(user_id) {
    if (!confirm('Reset password for this user?')) return;
    fetch('users_management_api.php', {
        method: 'POST', 
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + (localStorage.webgnis_token || '')
        }, 
        body: JSON.stringify({action: 'reset_password', user_id})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('New password: ' + d.new_password);
        } else {
            alert(d.error || 'Failed to reset password');
        }
    });
};

window.deleteUser = function(user_id) {
    document.getElementById('confirmModalBody').innerHTML = 
        '<i class="fas fa-exclamation-triangle text-warning me-2"></i>' +
        'Are you sure you want to delete this user? This is a soft delete.';
    confirmModal.show();
    document.getElementById('confirmModalYes').onclick = function() {
        fetch('users_management_api.php', {
            method: 'POST', 
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.webgnis_token || '')
            }, 
            body: JSON.stringify({action: 'delete', user_id})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                loadUsers();
            } else {
                alert(d.error || 'Failed to delete user');
            }
        });
        confirmModal.hide();
    };
};

document.getElementById('userForm').onsubmit = function(e) {
    e.preventDefault();
    const user_id = document.getElementById('userId').value;
    const username = document.getElementById('userUsername').value;
    const email = document.getElementById('userEmail').value;
    const contact_number = document.getElementById('userContactNumber').value;
    const user_type = document.getElementById('userType').value;
    const sex_id = document.getElementById('userSexId').value;
    const name_on_certificate = document.getElementById('userNameOnCert').value;
    const password = document.getElementById('userPassword').value;
    
    const action = user_id ? 'edit' : 'add';
    const payload = {
        action, 
        user_id, 
        username, 
        email, 
        contact_number, 
        user_type, 
        sex_id, 
        name_on_certificate
    };
    
    if (!user_id) {
        payload.password = password;
        if (!payload.password || payload.password.length < 6) {
            alert('Password must be at least 6 characters');
            return;
        }
        payload.user_type = (payload.user_type || '').toLowerCase();
        if (!['individual','company','moderator','admin'].includes(payload.user_type)) {
            alert('Invalid user type');
            return;
        }
    }
    
    fetch('users_management_api.php', {
        method: 'POST', 
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + (localStorage.webgnis_token || '')
        }, 
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            userModal.hide();
            loadUsers();
        } else {
            alert(d.error || 'Failed to save user');
        }
    });
};

// --- Populate sex dropdown and map ---
window.sexMap = {};
async function loadSexes() {
    try {
        const res = await fetch('users_api.php?action=sexes', {
            headers: { 'Authorization': 'Bearer ' + (localStorage.webgnis_token || '') }
        });
        const data = await res.json();
        if (data.data) {
            const sexSelect = document.getElementById('userSexId');
            sexSelect.innerHTML = '<option value="">Select Sex</option>';
            data.data.forEach(sex => {
                window.sexMap[sex.id] = sex.sex_name;
                const option = document.createElement('option');
                option.value = sex.id;
                option.textContent = sex.sex_name;
                sexSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load sexes:', error);
        // Fallback
        const sexSelect = document.getElementById('userSexId');
        sexSelect.innerHTML = '<option value="">Select Sex</option>';
        const sexOptions = ['Male', 'Female'];
        sexOptions.forEach((option, index) => {
            const optionElement = document.createElement('option');
            optionElement.value = index + 1;
            optionElement.textContent = option;
            sexSelect.appendChild(optionElement);
        });
    }
}
loadSexes();

// --- Table sorting ---
let sortCol = '', sortDir = 1;
document.querySelectorAll('#usersTable th[data-sort]').forEach(th => {
    th.onclick = function() {
        const col = th.getAttribute('data-sort');
        
        // Update sort indicators
        document.querySelectorAll('#usersTable th[data-sort]').forEach(header => {
            header.classList.remove('sorted-asc', 'sorted-desc');
        });
        
        if (sortCol === col) {
            sortDir = -sortDir;
        } else {
            sortCol = col;
            sortDir = 1;
        }
        
        if (sortDir === 1) {
            th.classList.add('sorted-asc');
        } else {
            th.classList.add('sorted-desc');
        }
        
        // Sort the data
        users.sort((a, b) => {
            let va = a[col] || '', vb = b[col] || '';
            if (typeof va === 'string') va = va.toLowerCase();
            if (typeof vb === 'string') vb = vb.toLowerCase();
            if (va < vb) return -1 * sortDir;
            if (va > vb) return 1 * sortDir;
            return 0;
        });
        
        renderTable();
    };
});
</script>
</body>
</html> 