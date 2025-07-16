/**
 * user-ui.js - WebGNIS User Interface Module
 * Handles UI-related functionality for user management
 */

import { getCurrentUser, hasRole } from './auth.js';

// UI Visibility Controller
function updateUIByUserRole() {
    const currentUser = getCurrentUser();
    
    if (!currentUser) {
        hideAllRestrictedElements();
        return;
    }
    
    // Show/hide elements based on user role
    document.querySelectorAll('[data-role-access]').forEach(element => {
        const requiredRoles = element.dataset.roleAccess.split(',');
        const hasAccess = requiredRoles.some(role => hasRole(role.trim()));
        
        element.style.display = hasAccess ? '' : 'none';
    });
    
    // Update user info displays
    updateUserDisplays(currentUser);
}

function hideAllRestrictedElements() {
    document.querySelectorAll('[data-role-access]').forEach(element => {
        element.style.display = 'none';
    });
}

function updateUserDisplays(user) {
    // Update username displays
    document.querySelectorAll('.user-display-name').forEach(element => {
        element.textContent = user.name || user.username;
    });
    
    // Update user role displays
    document.querySelectorAll('.user-display-role').forEach(element => {
        element.textContent = user.role || '';
    });
    
    // Set user avatar if available
    if (user.avatar) {
        document.querySelectorAll('.user-avatar').forEach(element => {
            if (element.tagName === 'IMG') {
                element.src = user.avatar;
            } else {
                element.style.backgroundImage = `url(${user.avatar})`;
            }
        });
    }
}

// User list rendering
function renderUserList(users, targetElement) {
    const container = document.querySelector(targetElement);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (!users || users.length === 0) {
        container.innerHTML = '<tr><td colspan="5" class="text-center">No users found</td></tr>';
        return;
    }
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.dataset.userId = user.id;
        
        // Create user row based on data
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.name || '-'}</td>
            <td>${user.email || '-'}</td>
            <td>${user.role || '-'}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary edit-user-btn" data-user-id="${user.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        container.appendChild(row);
    });
    
    // Attach event listeners to buttons
    attachUserListEventListeners(container);
}

function attachUserListEventListeners(container) {
    // Edit user buttons
    container.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const userId = event.currentTarget.dataset.userId;
            // Dispatch edit event
            const editEvent = new CustomEvent('webgnis:user:edit', {
                detail: { userId }
            });
            document.dispatchEvent(editEvent);
        });
    });
    
    // Delete user buttons
    container.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            const userId = event.currentTarget.dataset.userId;
            if (confirm('Are you sure you want to delete this user?')) {
                // Dispatch delete event
                const deleteEvent = new CustomEvent('webgnis:user:delete', {
                    detail: { userId }
                });
                document.dispatchEvent(deleteEvent);
            }
        });
    });
}

// User form management
function clearUserForm(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    
    form.reset();
    
    // Reset any hidden ID fields
    const idField = form.querySelector('[name="user_id"]');
    if (idField) idField.value = '';
    
    // Reset password field to required if it was changed
    const passwordField = form.querySelector('[name="password"]');
    if (passwordField) {
        passwordField.required = true;
        passwordField.placeholder = 'Enter password';
    }
}

// Populate form fields from user data
function populateUserForm(form, userData) {
    if (!form || !userData) return;
    
    // Set user ID in hidden field if it exists
    if (userData.id) {
        const idField = form.querySelector('[name="user_id"]');
        if (idField) idField.value = userData.id;
    }
    
    // Find all form elements that match user data properties
    Array.from(form.elements).forEach(element => {
        if (element.name && userData.hasOwnProperty(element.name)) {
            if (element.type === 'checkbox') {
                element.checked = !!userData[element.name];
            } else if (element.type === 'radio') {
                element.checked = (element.value === userData[element.name]);
            } else {
                element.value = userData[element.name] || '';
            }
        }
    });
    
    // If editing existing user, password field may be optional
    const passwordField = form.querySelector('[name="password"]');
    if (passwordField && userData.id) {
        passwordField.required = false;
        passwordField.placeholder = 'Leave blank to keep current password';
    }
}

// Show notifications to user
function showNotification(message, type = 'info') {
    // Get or create notification container
    let notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.top = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '1000';
        document.body.appendChild(notificationContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.padding = '15px 20px';
    notification.style.margin = '10px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
    notification.style.cursor = 'pointer';
    notification.style.maxWidth = '300px';
    notification.style.wordBreak = 'break-word';
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#d4edda';
            notification.style.color = '#155724';
            notification.style.borderLeft = '5px solid #28a745';
            break;
        case 'danger':
            notification.style.backgroundColor = '#f8d7da';
            notification.style.color = '#721c24';
            notification.style.borderLeft = '5px solid #dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#fff3cd';
            notification.style.color = '#856404';
            notification.style.borderLeft = '5px solid #ffc107';
            break;
        default: // info
            notification.style.backgroundColor = '#cce5ff';
            notification.style.color = '#004085';
            notification.style.borderLeft = '5px solid #007bff';
    }
    
    notification.innerHTML = message;
    
    // Add click to dismiss
    notification.addEventListener('click', () => {
        notification.remove();
    });
    
    // Add to container
    notificationContainer.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize UI elements
function initUserUI() {
    // Set up event listeners for auth events
    document.addEventListener('webgnis:auth:login', () => {
        updateUIByUserRole();
    });
    
    document.addEventListener('webgnis:auth:logout', () => {
        hideAllRestrictedElements();
    });
    
    // Initial UI update
    updateUIByUserRole();
}

// Extract form data into an object
function extractFormData(form) {
    if (!form) return {};
    
    const formData = {};
    
    Array.from(form.elements).forEach(element => {
        if (element.name) {
            if (element.type === 'checkbox') {
                formData[element.name] = element.checked;
            } else if (element.type === 'radio') {
                if (element.checked) {
                    formData[element.name] = element.value;
                }
            } else if (element.value) {
                formData[element.name] = element.value;
            }
        }
    });
    
    return formData;
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        return dateString; // Return original if not valid date
    }
    
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Render a user table from user data
function renderUserTable(container, users, onEdit, onDelete) {
    if (!container || !Array.isArray(users)) return;
    
    const table = document.createElement('table');
    table.className = 'table table-striped';
    
    // Create header
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    `;
    table.appendChild(thead);
    
    // Create body
    const tbody = document.createElement('tbody');
    
    users.forEach(user => {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>${user.id || '-'}</td>
            <td>${user.username || '-'}</td>
            <td>${(user.first_name || '') + ' ' + (user.last_name || '')}</td>
            <td>${user.email || '-'}</td>
            <td>${user.role || '-'}</td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-primary edit-btn">Edit</button>
                <button class="btn btn-sm btn-danger delete-btn">Delete</button>
            </td>
        `;
        
        // Add event listeners
        if (typeof onEdit === 'function') {
            tr.querySelector('.edit-btn').addEventListener('click', () => onEdit(user));
        }
        
        if (typeof onDelete === 'function') {
            tr.querySelector('.delete-btn').addEventListener('click', () => onDelete(user));
        }
        
        tbody.appendChild(tr);
    });
    
    table.appendChild(tbody);
    
    // Clear and append table
    container.innerHTML = '';
    container.appendChild(table);
}

// Show confirmation dialog
function confirmAction(message) {
    return window.confirm(message);
}

// Update UI based on user authentication status
function updateAuthUI(isAuthenticated, user) {
    // Get login/logout elements
    const loginElements = document.querySelectorAll('.login-required');
    const logoutElements = document.querySelectorAll('.logout-required');
    const userNameElements = document.querySelectorAll('.user-name');
    const adminElements = document.querySelectorAll('.admin-only');
    
    // Update visibility based on authentication status
    loginElements.forEach(el => {
        el.style.display = isAuthenticated ? 'block' : 'none';
    });
    
    logoutElements.forEach(el => {
        el.style.display = isAuthenticated ? 'none' : 'block';
    });
    
    // Update user name display
    if (isAuthenticated && user) {
        userNameElements.forEach(el => {
            el.textContent = user.username || 'User';
        });
        
        // Handle admin-only elements
        const isAdmin = user.user_type === 'admin';
        adminElements.forEach(el => {
            el.style.display = isAdmin ? 'block' : 'none';
        });
    }
}

// Initialize registration form
function initRegistrationForm() {
    // Initialize sex dropdown
    const sexSelect = document.getElementById('registerSex');
    if (sexSelect) {
        sexSelect.innerHTML = `
            <option value="">Select Sex</option>
            <option value="M">Male</option>
            <option value="F">Female</option>
            <option value="O">Other</option>
        `;
    }

    // Initialize sector dropdown
    const sectorSelect = document.getElementById('registerSector');
    if (sectorSelect) {
        sectorSelect.innerHTML = `
            <option value="">Select Sector</option>
            <option value="government">Government</option>
            <option value="private">Private</option>
            <option value="academic">Academic</option>
            <option value="ngo">NGO</option>
        `;
    }

    // Add event listeners for user type toggle
    const individualType = document.getElementById('individualType');
    const companyType = document.getElementById('companyType');
    const individualFields = document.getElementById('individualFields');
    const companyFields = document.getElementById('companyFields');

    if (individualType && companyType && individualFields && companyFields) {
        individualType.addEventListener('change', () => {
            individualFields.classList.remove('d-none');
            companyFields.classList.add('d-none');
        });

        companyType.addEventListener('change', () => {
            individualFields.classList.add('d-none');
            companyFields.classList.remove('d-none');
        });
    }
}

// Initialize UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initUserUI();
    initRegistrationForm();
});

export {
    updateUIByUserRole,
    renderUserList,
    populateUserForm,
    clearUserForm,
    showNotification,
    initUserUI,
    extractFormData,
    formatDate,
    renderUserTable,
    confirmAction,
    updateAuthUI
}; 