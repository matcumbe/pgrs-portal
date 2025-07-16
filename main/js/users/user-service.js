/**
 * user-service.js - WebGNIS User Service Module
 * Handles API calls related to users
 */

import { showNotification } from './user-ui.js';

// API endpoint
const API_BASE_URL = '../api/users_api.php';

// Fetch all users from the API
async function fetchUsers() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=list_users`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch users');
        }
        
        return data.users || [];
    } catch (error) {
        console.error('Error fetching users:', error);
        showNotification('Failed to load users: ' + error.message, 'danger');
        return [];
    }
}

// Get a single user by ID
async function fetchUser(userId) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=get_user&id=${userId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch user');
        }
        
        return data.user || null;
    } catch (error) {
        console.error('Error fetching user:', error);
        showNotification('Failed to load user details: ' + error.message, 'danger');
        return null;
    }
}

// Create a new user
async function createUser(userData) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=create_user`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to create user');
        }
        
        showNotification('User created successfully', 'success');
        return data.user || null;
    } catch (error) {
        console.error('Error creating user:', error);
        showNotification('Failed to create user: ' + error.message, 'danger');
        return null;
    }
}

// Update an existing user
async function updateUser(userId, userData) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=update_user&id=${userId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to update user');
        }
        
        showNotification('User updated successfully', 'success');
        return data.user || null;
    } catch (error) {
        console.error('Error updating user:', error);
        showNotification('Failed to update user: ' + error.message, 'danger');
        return null;
    }
}

// Delete a user
async function deleteUser(userId) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=delete_user&id=${userId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to delete user');
        }
        
        showNotification('User deleted successfully', 'success');
        return true;
    } catch (error) {
        console.error('Error deleting user:', error);
        showNotification('Failed to delete user: ' + error.message, 'danger');
        return false;
    }
}

// Update user password
async function updatePassword(userId, currentPassword, newPassword) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=change_password`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                user_id: userId,
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to update password');
        }
        
        showNotification('Password updated successfully', 'success');
        return true;
    } catch (error) {
        console.error('Error updating password:', error);
        showNotification('Failed to update password: ' + error.message, 'danger');
        return false;
    }
}

// Get user roles
async function fetchRoles() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=list_roles`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to fetch roles');
        }
        
        return data.roles || [];
    } catch (error) {
        console.error('Error fetching roles:', error);
        showNotification('Failed to load roles: ' + error.message, 'danger');
        return [];
    }
}

export {
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    updatePassword,
    fetchRoles
}; 