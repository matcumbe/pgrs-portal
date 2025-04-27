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
            currentUser = result.data.user;
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

// Initialize authentication on page load
async function initAuth() {
    const status = await checkAuthStatus();
    if (status.authenticated) {
        // Trigger authentication event
        const event = new CustomEvent('webgnis:auth:login', { 
            detail: { user: status.user } 
        });
        document.dispatchEvent(event);
    }
    return status;
}

// Check for valid role
function hasRole(requiredRole) {
    if (!currentUser || !currentUser.role) {
        return false;
    }
    
    // Admin can access everything
    if (currentUser.role === 'admin') {
        return true;
    }
    
    if (Array.isArray(requiredRole)) {
        return requiredRole.includes(currentUser.role);
    }
    
    return currentUser.role === requiredRole;
}

export {
    login,
    logout,
    checkAuthStatus,
    getCurrentUser,
    requireAuth,
    initAuth,
    hasRole
}; 