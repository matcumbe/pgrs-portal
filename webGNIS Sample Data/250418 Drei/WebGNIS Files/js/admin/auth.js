// Admin Authentication Module

import { AUTH_CREDENTIALS } from './config.js';
import { initializeAdminInterface } from './app.js';

/**
 * Handles user login
 * @param {Event} event - Submit event from login form 
 */
export function handleLogin(event) {
    event.preventDefault();
    console.log("Login attempt...");
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const loginError = document.getElementById('loginError');
    
    console.log("Credentials entered:", { username, password: "***" });
    
    // Simple client-side authentication for demonstration
    if (username === AUTH_CREDENTIALS.username && password === AUTH_CREDENTIALS.password) {
        console.log("Login successful");
        // Hide login form and show admin interface
        document.getElementById('loginContainer').classList.add('hidden');
        document.getElementById('adminInterface').classList.remove('hidden');
        
        // Store auth state in sessionStorage
        sessionStorage.setItem('authenticated', 'true');
        
        // Initialize admin interface
        initializeAdminInterface();
    } else {
        console.log("Login failed");
        // Show error message
        loginError.textContent = 'Invalid username or password';
        loginError.classList.remove('hidden');
    }
}

/**
 * Handles user logout
 */
export function handleLogout() {
    // Clear auth state
    sessionStorage.removeItem('authenticated');
    
    // Hide admin interface and show login form
    document.getElementById('adminInterface').classList.add('hidden');
    document.getElementById('loginContainer').classList.remove('hidden');
    
    // Clear login form
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
    document.getElementById('loginError').classList.add('hidden');
}

/**
 * Checks if user is already authenticated
 * @returns {boolean} Authentication status
 */
export function checkAuthState() {
    // Check if user is already authenticated
    if (sessionStorage.getItem('authenticated') === 'true') {
        document.getElementById('loginContainer').classList.add('hidden');
        document.getElementById('adminInterface').classList.remove('hidden');
        initializeAdminInterface();
        return true;
    }
    return false;
}

/**
 * Initialize the authentication functionality
 */
export function initAuth() {
    // Set up login form handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Set up logout button handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Check authentication state on load
    checkAuthState();
} 