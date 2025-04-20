/**
 * WebGNIS Users Management
 * JavaScript API client for interacting with the users API
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
        const url = `${this.baseUrl}${endpoint}`;
        
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
        const result = await this.request('/login', 'POST', { username, password });
        if (result.data && result.data.token) {
            this.setToken(result.data.token);
        }
        return result;
    }

    async logout() {
        this.clearToken();
    }

    // User management methods
    async getAllUsers() {
        return await this.request('/users', 'GET');
    }

    async getUserById(userId) {
        return await this.request(`/users/${userId}`, 'GET');
    }

    async createUser(userData) {
        return await this.request('/users', 'POST', userData);
    }

    async updateUser(userId, userData) {
        return await this.request(`/users/${userId}`, 'PUT', userData);
    }

    async deleteUser(userId) {
        return await this.request(`/users/${userId}`, 'DELETE');
    }

    // Company methods
    async getAllCompanies() {
        return await this.request('/company', 'GET');
    }

    async getCompanyById(companyId) {
        return await this.request(`/company/${companyId}`, 'GET');
    }

    // Individual methods
    async getAllIndividuals() {
        return await this.request('/individual', 'GET');
    }

    async getIndividualById(individualId) {
        return await this.request(`/individual/${individualId}`, 'GET');
    }

    // Reference data methods
    async getSectors() {
        return await this.request('/sectors', 'GET');
    }

    async getSexes() {
        return await this.request('/sexes', 'GET');
    }
}

// Example usage
const usersApi = new UsersAPI('/users_api.php');

// Login example
async function loginExample() {
    try {
        const result = await usersApi.login('admin', 'admin123');
        console.log('Login successful:', result);
    } catch (error) {
        console.error('Login failed:', error);
    }
}

// Create user example
async function createUserExample() {
    const userData = {
        username: 'newuser',
        password: 'password123',
        email: 'newuser@example.com',
        contact_number: '09123456789',
        user_type: 'individual',
        sex_id: 1,
        name_on_certificate: 'John Doe',
        full_name: 'John Doe',
        address: '123 Main St'
    };
    
    try {
        const result = await usersApi.createUser(userData);
        console.log('User created:', result);
    } catch (error) {
        console.error('User creation failed:', error);
    }
}

// Update user example
async function updateUserExample(userId) {
    const userData = {
        contact_number: '09987654321',
        individual_details: {
            address: '456 New St'
        }
    };
    
    try {
        const result = await usersApi.updateUser(userId, userData);
        console.log('User updated:', result);
    } catch (error) {
        console.error('User update failed:', error);
    }
}

// Get all users example
async function getAllUsersExample() {
    try {
        const result = await usersApi.getAllUsers();
        console.log('All users:', result);
    } catch (error) {
        console.error('Failed to get users:', error);
    }
} 