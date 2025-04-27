/**
 * auth.js - WebGNIS Authentication Module
 * Handles user authentication and session management
 */

import { usersApi } from './api-client.js';

// Authentication state
let currentUser = null;

// Authentication Functions
async function login(username, password) {
    try {
        const result = await usersApi.login(username, password);
        if (result.status === 'success') {
            // Map user_type to role in the user data
            currentUser = {
                ...result.data.user,
                role: result.data.user.user_type
            };
            // Trigger authentication event
            const event = new CustomEvent('webgnis:auth:login', { 
                detail: { user: currentUser } 
            });
            document.dispatchEvent(event);
            
            return { success: true, user: currentUser };
        }
        return { success: false, message: result.message || 'Login failed' };
    } catch (error) {
        console.error('Login error:', error);
        return { success: false, message: error.message || 'Login failed' };
    }
}

async function logout() {
    try {
        await usersApi.logout();
        currentUser = null;
        
        // Trigger logout event
        const event = new CustomEvent('webgnis:auth:logout');
        document.dispatchEvent(event);
        
        return { success: true };
    } catch (error) {
        console.error('Logout error:', error);
        return { success: false, message: error.message };
    }
}

async function checkAuthStatus() {
    if (!usersApi.isAuthenticated()) {
        return { authenticated: false };
    }
    
    try {
        const userData = await usersApi.getCurrentUser();
        if (userData && userData.data) {
            currentUser = userData.data;
            return { authenticated: true, user: currentUser };
        }
        return { authenticated: false };
    } catch (error) {
        console.error('Auth check error:', error);
        usersApi.clearToken();
        return { authenticated: false, error: error.message };
    }
}

function getCurrentUser() {
    return currentUser;
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!usersApi.isAuthenticated()) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// Update navigation based on user role
function updateNavigation() {
    const adminLinks = document.querySelectorAll('.admin-only');
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (currentUser) {
        // Show/hide admin links based on role
        adminLinks.forEach(link => {
            if (currentUser.user_type === 'admin') {
                link.classList.remove('d-none');
            } else {
                link.classList.add('d-none');
            }
        });
        
        // Show logout button, hide login button
        if (loginBtn) loginBtn.classList.add('d-none');
        if (logoutBtn) logoutBtn.classList.remove('d-none');
    } else {
        // Hide all admin links
        adminLinks.forEach(link => link.classList.add('d-none'));
        
        // Show login button, hide logout button
        if (loginBtn) loginBtn.classList.remove('d-none');
        if (logoutBtn) logoutBtn.classList.add('d-none');
    }
}

// Protect admin pages
function protectAdminPage() {
    if (!currentUser || currentUser.role !== 'admin') {
        // Redirect to home page if not admin
        window.location.href = 'home.html';
        return false;
    }
    return true;
}

// Initialize auth and navigation
async function initAuth() {
    const status = await checkAuthStatus();
    if (status.authenticated) {
        // Log user type to console
        console.log('Current user:', status.user);
        console.log('User type:', status.user.user_type || 'Not specified');
        
        // Trigger authentication event
        const event = new CustomEvent('webgnis:auth:login', { 
            detail: { user: status.user } 
        });
        document.dispatchEvent(event);
    }
    updateNavigation();
    return status;
}

// Check for valid role
function hasRole(requiredRole) {
    if (!currentUser || !currentUser.user_type) {
        return false;
    }
    
    // Admin can access everything
    if (currentUser.user_type === 'admin') {
        return true;
    }
    
    return currentUser.user_type === requiredRole;
}

// Add event listeners for login/logout buttons
document.addEventListener('DOMContentLoaded', () => {
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const authModal = document.getElementById('authModal');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            // Show login modal instead of redirecting
            const authModal = new bootstrap.Modal(document.getElementById('authModal'));
            authModal.show();
        });
    }
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            await logout();
            // Remove modal backdrop if it exists
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Update UI after logout
            updateNavigation();
            
            // Redirect to home page
            window.location.href = 'home.html';
        });
    }
    
    // Add event listener to remove backdrop when modal is hidden
    if (authModal) {
        authModal.addEventListener('hidden.bs.modal', function () {
            // Remove modal backdrop
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        });
    }
    
    // Initialize auth state
    initAuth();
});

// Export additional functions
export {
    login,
    logout,
    checkAuthStatus,
    getCurrentUser,
    requireAuth,
    initAuth,
    hasRole,
    updateNavigation,
    protectAdminPage
}; 