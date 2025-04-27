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
        if (result.status === 'success' || result.status === 200) {
            // Store user data consistently
            currentUser = result.data.user || result.data;
            
            // Store in localStorage for persistence
            localStorage.setItem('gnisUser', JSON.stringify(currentUser));
            
            // Trigger authentication event
            const event = new CustomEvent('webgnis:auth:login', { 
                detail: { user: currentUser } 
            });
            document.dispatchEvent(event);
            
            // Update navigation
            updateNavigation();
            
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
        
        // Clear localStorage
        localStorage.removeItem('gnisUser');
        
        // Trigger logout event
        const event = new CustomEvent('webgnis:auth:logout');
        document.dispatchEvent(event);
        
        // Update navigation
        updateNavigation();
        
        return { success: true };
    } catch (error) {
        console.error('Logout error:', error);
        return { success: false, message: error.message };
    }
}

async function checkAuthStatus() {
    // First check localStorage
    const storedUser = localStorage.getItem('gnisUser');
    if (storedUser) {
        try {
            currentUser = JSON.parse(storedUser);
        } catch (e) {
            console.error('Failed to parse stored user:', e);
            localStorage.removeItem('gnisUser');
        }
    }
    
    if (!usersApi.isAuthenticated()) {
        currentUser = null;
        localStorage.removeItem('gnisUser');
        return { authenticated: false };
    }
    
    try {
        const userData = await usersApi.getCurrentUser();
        if (userData && userData.data) {
            currentUser = userData.data;
            localStorage.setItem('gnisUser', JSON.stringify(currentUser));
            return { authenticated: true, user: currentUser };
        }
        return { authenticated: false };
    } catch (error) {
        console.error('Auth check error:', error);
        usersApi.clearToken();
        localStorage.removeItem('gnisUser');
        currentUser = null;
        return { authenticated: false, error: error.message };
    }
}

function getCurrentUser() {
    return currentUser;
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!usersApi.isAuthenticated()) {
        window.location.href = 'index.html';
        return false;
    }
    return true;
}

// Update navigation based on user role
function updateNavigation() {
    const adminLinks = document.querySelectorAll('.admin-only');
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const homeLink = document.querySelector('a[href="home.html"]');
    const trackerLink = document.querySelector('a[href="#"]:has(i.fa-map-marker)');
    const aboutLink = document.querySelector('a[href="about.html"]');
    
    if (currentUser) {
        // Show/hide admin links based on role
        adminLinks.forEach(link => {
            if (currentUser.user_type === 'admin') {
                link.classList.remove('d-none');
                
                // Hide Home, Tracker, and About links for admin users
                if (homeLink) homeLink.classList.add('d-none');
                if (trackerLink) trackerLink.classList.add('d-none');
                if (aboutLink) aboutLink.classList.add('d-none');
            } else {
                link.classList.add('d-none');
                
                // Show Home, Tracker, and About links for non-admin users
                if (homeLink) homeLink.classList.remove('d-none');
                if (trackerLink) trackerLink.classList.remove('d-none');
                if (aboutLink) aboutLink.classList.remove('d-none');
            }
        });
        
        // Show logout button, hide login button
        if (loginBtn) loginBtn.classList.add('d-none');
        if (logoutBtn) logoutBtn.classList.remove('d-none');
    } else {
        // Hide all admin links
        adminLinks.forEach(link => link.classList.add('d-none'));
        
        // Show Home, Tracker, and About links for non-authenticated users
        if (homeLink) homeLink.classList.remove('d-none');
        if (trackerLink) trackerLink.classList.remove('d-none');
        if (aboutLink) aboutLink.classList.remove('d-none');
        
        // Show login button, hide logout button
        if (loginBtn) loginBtn.classList.remove('d-none');
        if (logoutBtn) logoutBtn.classList.add('d-none');
    }
}

// Protect admin pages
function protectAdminPage() {
    if (!currentUser || currentUser.user_type !== 'admin') {
        // Redirect to home page if not admin
        window.location.href = 'index.html';
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