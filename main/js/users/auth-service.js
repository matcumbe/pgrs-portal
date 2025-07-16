/**
 * auth-service.js - WebGNIS Authentication Service Module
 * Handles authentication related functionality
 */

import { showNotification } from './user-ui.js';

// API endpoint
const API_BASE_URL = '../api/users_api.php';

// Key for storing user data in local storage
const USER_STORAGE_KEY = 'webgnis_user';

// Get currently logged in user from local storage
function getCurrentUser() {
    const userData = localStorage.getItem(USER_STORAGE_KEY);
    return userData ? JSON.parse(userData) : null;
}

// Save user data to local storage
function setCurrentUser(user) {
    if (user) {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    } else {
        localStorage.removeItem(USER_STORAGE_KEY);
    }
    
    // Dispatch a custom event to notify about user change
    document.dispatchEvent(new CustomEvent('userChanged', {
        detail: { user }
    }));
}

// Check if user has a specific role
function hasRole(role) {
    const user = getCurrentUser();
    return user && user.role === role;
}

// Check if the user is authenticated
function isAuthenticated() {
    return getCurrentUser() !== null;
}

// Log in a user
async function login(username, password) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=login`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Login failed');
        }
        
        // Save user data
        setCurrentUser(data.user);
        
        // Dispatch login event
        document.dispatchEvent(new CustomEvent('userLoggedIn', {
            detail: { user: data.user }
        }));
        
        showNotification('Login successful. Welcome!', 'success');
        return data.user;
    } catch (error) {
        console.error('Login error:', error);
        showNotification('Login failed: ' + error.message, 'danger');
        return null;
    }
}

// Log out the current user
async function logout() {
    try {
        const response = await fetch(`${API_BASE_URL}?action=logout`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Logout failed');
        }
        
        // Clear user data
        setCurrentUser(null);
        
        // Dispatch logout event
        document.dispatchEvent(new CustomEvent('userLoggedOut'));
        
        showNotification('You have been logged out successfully', 'info');
        return true;
    } catch (error) {
        console.error('Logout error:', error);
        showNotification('Logout failed: ' + error.message, 'danger');
        return false;
    }
}

// Register a new user
async function register(userData) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=register`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Registration failed');
        }
        
        showNotification('Registration successful. You can now log in.', 'success');
        return true;
    } catch (error) {
        console.error('Registration error:', error);
        showNotification('Registration failed: ' + error.message, 'danger');
        return false;
    }
}

// Initialize authentication - check if user is already logged in
function initAuth() {
    const user = getCurrentUser();
    
    if (user) {
        // Dispatch user logged in event
        document.dispatchEvent(new CustomEvent('userLoggedIn', {
            detail: { user }
        }));
    }
    
    return user;
}

export {
    getCurrentUser,
    setCurrentUser,
    hasRole,
    isAuthenticated,
    login,
    logout,
    register,
    initAuth
}; 