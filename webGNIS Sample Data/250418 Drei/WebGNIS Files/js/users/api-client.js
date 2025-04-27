/**
 * api-client.js - WebGNIS Users API Client
 * API client for interacting with the users API
 */

class UsersAPI {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('webgnis_token') || null;
    }

    // Set the authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('webgnis_token', token);
    }

    // Clear the authentication token
    clearToken() {
        this.token = null;
        localStorage.removeItem('webgnis_token');
    }

    // Check if user is authenticated
    isAuthenticated() {
        return this.token !== null;
    }

    // Helper method to make API requests
    async request(endpoint, method = 'GET', data = null) {
        // Construct the URL properly without using path segments
        // This will add query parameters instead of path segments
        let url = this.baseUrl;
        if (endpoint.startsWith('/')) {
            endpoint = endpoint.substring(1);
        }
        if (endpoint) {
            url += `?action=${endpoint}`;
        }
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const options = {
            method,
            headers
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            console.log(`Making API request to: ${url}`);
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
        } catch (error) {
            console.error('API request error:', error);
            throw error;
        }
    }

    // Authentication methods
    async login(username, password) {
        const result = await this.request('login', 'POST', { username, password });
        if (result.data && result.data.token) {
            this.setToken(result.data.token);
        }
        return result;
    }

    async logout() {
        try {
            // Call the logout endpoint if it exists
            await this.request('logout', 'POST');
        } catch (error) {
            // Just ignore errors as we'll clear the token anyway
            console.warn('Logout API call failed, but proceeding with local logout');
        } finally {
            this.clearToken();
        }
    }

    // User management methods
    async getAllUsers() {
        return await this.request('users', 'GET');
    }

    async getUserById(userId) {
        return await this.request(`users/${userId}`, 'GET');
    }

    async createUser(userData) {
        return await this.request('users', 'POST', userData);
    }

    async updateUser(userId, userData) {
        return await this.request(`users/${userId}`, 'PUT', userData);
    }

    async deleteUser(userId) {
        return await this.request(`users/${userId}`, 'DELETE');
    }

    // Company methods
    async getAllCompanies() {
        return await this.request('company', 'GET');
    }

    async getCompanyById(companyId) {
        return await this.request(`company/${companyId}`, 'GET');
    }

    // Individual methods
    async getAllIndividuals() {
        return await this.request('individual', 'GET');
    }

    async getIndividualById(individualId) {
        return await this.request(`individual/${individualId}`, 'GET');
    }

    // Reference data methods
    async getSectors() {
        return await this.request('sectors', 'GET');
    }

    async getSexes() {
        return await this.request('sexes', 'GET');
    }

    // Get current user profile
    async getCurrentUser() {
        if (!this.isAuthenticated()) {
            return null;
        }
        return await this.request('users/me', 'GET');
    }

    // Update password
    async updatePassword(userId, currentPassword, newPassword) {
        return await this.request('change_password', 'POST', {
            user_id: userId,
            current_password: currentPassword,
            new_password: newPassword
        });
    }

    // Request certificates
    async requestCertificate(pointIds, requestDetails) {
        return await this.request('certificates/request', 'POST', {
            point_ids: pointIds,
            ...requestDetails
        });
    }
}

// Create and export global instance of the API
const usersApi = new UsersAPI('users_api.php');

export { UsersAPI, usersApi }; 