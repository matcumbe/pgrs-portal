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

    // Request certificates
    async requestCertificate(pointIds, requestDetails) {
        return await this.request('certificates/request', 'POST', {
            point_ids: pointIds,
            ...requestDetails
        });
    }
}

// Create global instance of the API
const usersApi = new UsersAPI('users_api.php');

// User Authentication Module
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const requestCertBtn = document.getElementById('requestCertBtn');
    const userInfoSection = document.getElementById('userInfoSection');
    const userDisplayName = document.getElementById('userDisplayName');
    const userType = document.getElementById('userType');
    const logoutBtn = document.getElementById('logoutBtn');
    const requestCertificateBtn = document.getElementById('requestCertificateBtn');
    
    // Auth Modal Elements
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginAlert = document.getElementById('loginAlert');
    const registerAlert = document.getElementById('registerAlert');
    
    // Register form toggle for Individual/Company fields
    const individualType = document.getElementById('individualType');
    const companyType = document.getElementById('companyType');
    const individualFields = document.getElementById('individualFields');
    const companyFields = document.getElementById('companyFields');
    
    // API loading states
    let isLoading = false;
    let sexData = [];
    let sectorData = [];
    
    // Check for logged in user on page load
    checkLoginStatus();
    loadReferenceData();
    
    // Event Listeners
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    if (requestCertificateBtn) {
        requestCertificateBtn.addEventListener('click', handleCertificateRequest);
    }
    
    // Toggle individual/company fields
    if (individualType && companyType) {
        individualType.addEventListener('change', toggleUserTypeFields);
        companyType.addEventListener('change', toggleUserTypeFields);
    }
    
    // API loading indicator functions
    function showLoading(form) {
        isLoading = true;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
        }
    }
    
    function hideLoading(form) {
        isLoading = false;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.textContent || 'Submit';
        }
    }
    
    // Load reference data (sectors and sexes)
    async function loadReferenceData() {
        try {
            // Load sexes
            const sexSelect = document.getElementById('registerSex');
            if (sexSelect) {
                try {
                    const result = await usersApi.getSexes();
                    sexData = result.data || [];
                    
                    // Clear options
                    sexSelect.innerHTML = '<option value="">Select Sex</option>';
                    
                    // Add options from API
                    sexData.forEach(sex => {
                        const option = document.createElement('option');
                        option.value = sex.id;
                        option.textContent = sex.sex_name;
                        sexSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Failed to load sexes:', error);
                    // Fallback to hardcoded values
                    const sexOptions = ['Male', 'Female', 'Prefer not to say'];
                    sexOptions.forEach((option, index) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = index + 1;
                        optionElement.textContent = option;
                        sexSelect.appendChild(optionElement);
                    });
                }
            }
            
            // Load sectors
            const sectorSelect = document.getElementById('registerSector');
            if (sectorSelect) {
                try {
                    const result = await usersApi.getSectors();
                    sectorData = result.data || [];
                    
                    // Clear options
                    sectorSelect.innerHTML = '<option value="">Select Sector</option>';
                    
                    // Add options from API
                    sectorData.forEach(sector => {
                        const option = document.createElement('option');
                        option.value = sector.id;
                        option.textContent = sector.sector_name;
                        sectorSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error('Failed to load sectors:', error);
                    // Fallback to hardcoded values
                    const sectorOptions = [
                        'Government', 
                        'Private - Construction', 
                        'Private - Real Estate', 
                        'Private - Mining', 
                        'Private - Other',
                        'NGO',
                        'Academic'
                    ];
                    sectorOptions.forEach((option, index) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = index + 1;
                        optionElement.textContent = option;
                        sectorSelect.appendChild(optionElement);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading reference data:', error);
        }
    }
    
    // Check login status using the API
    async function checkLoginStatus() {
        try {
            if (usersApi.isAuthenticated()) {
                const userData = await usersApi.getCurrentUser();
                if (userData && userData.data) {
                    // Save user data to localStorage for convenience
                    localStorage.setItem('gnisUser', JSON.stringify(userData.data));
                    showUserInfo(userData.data);
                } else {
                    // Token might be invalid or expired
                    usersApi.clearToken();
                    localStorage.removeItem('gnisUser');
                    hideUserInfo();
                }
            } else {
                // No token found
                localStorage.removeItem('gnisUser');
                hideUserInfo();
            }
        } catch (error) {
            console.error('Error checking login status:', error);
            usersApi.clearToken();
            localStorage.removeItem('gnisUser');
            hideUserInfo();
        }
    }
    
    // Update UI for logged in user
    function showUserInfo(userData) {
        if (requestCertBtn && userInfoSection) {
            requestCertBtn.classList.add('d-none');
            userInfoSection.classList.remove('d-none');
            
            // Display user name
            userDisplayName.textContent = userData.username || userData.full_name || 'User';
            
            // Display account type
            const accountType = userData.user_type || 'individual';
            userType.textContent = (accountType === 'company') ? 'Company Account' : 'Individual Account';
        }
    }
    
    // Update UI for logged out state
    function hideUserInfo() {
        if (requestCertBtn && userInfoSection) {
            requestCertBtn.classList.remove('d-none');
            userInfoSection.classList.add('d-none');
        }
    }
    
    // Handle login form submission
    async function handleLogin(event) {
        event.preventDefault();
        
        if (isLoading) return;
        
        const username = document.getElementById('loginUsername').value;
        const password = document.getElementById('loginPassword').value;
        
        // Simple validation
        if (!username || !password) {
            showAlert(loginAlert, 'Please fill in all fields');
            return;
        }
        
        showLoading(loginForm);
        
        try {
            // Call the API for login
            const result = await usersApi.login(username, password);
            
            if (result.status === 200 && result.data) {
                // Store user data
                localStorage.setItem('gnisUser', JSON.stringify(result.data.user || result.data));
                
                // Update UI
                showUserInfo(result.data.user || result.data);
                
                // Close modal
                const authModal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                if (authModal) {
                    authModal.hide();
                }
                
                // Reset form
                loginForm.reset();
            } else {
                throw new Error(result.message || 'Login failed');
            }
        } catch (error) {
            showAlert(loginAlert, error.message || 'Login failed. Please check your credentials.');
        } finally {
            hideLoading(loginForm);
        }
    }
    
    // Handle registration form submission
    async function handleRegister(event) {
        event.preventDefault();
        
        if (isLoading) return;
        
        // Get form values
        const username = document.getElementById('registerUsername').value;
        const password = document.getElementById('registerPassword').value;
        const email = document.getElementById('registerEmail').value;
        const contact = document.getElementById('registerContact').value;
        const sexId = document.getElementById('registerSex').value;
        const nameOnCert = document.getElementById('registerNameOnCert').value;
        const userTypeValue = document.querySelector('input[name="userType"]:checked').value;
        
        // Validate required fields
        if (!username || !password || !email || !contact || !sexId || !nameOnCert) {
            showAlert(registerAlert, 'Please fill in all required fields');
            return;
        }
        
        // Create user data object based on type
        const userData = {
            username,
            password,
            email,
            contact_number: contact,
            user_type: userTypeValue,
            sex_id: parseInt(sexId),
            name_on_certificate: nameOnCert
        };
        
        // Add type-specific fields
        if (userTypeValue === 'individual') {
            userData.full_name = document.getElementById('registerFullName').value;
            userData.address = document.getElementById('registerAddress').value;
        } else {
            userData.company_name = document.getElementById('registerCompanyName').value;
            userData.company_address = document.getElementById('registerCompanyAddress').value;
            userData.sector_id = parseInt(document.getElementById('registerSector').value);
            userData.authorized_representative = document.getElementById('registerRepresentative').value;
        }
        
        showLoading(registerForm);
        
        try {
            // Call API to create user
            const result = await usersApi.createUser(userData);
            
            if (result.status === 201 && result.data) {
                // Now login with the created credentials
                const loginResult = await usersApi.login(username, password);
                
                if (loginResult.status === 200 && loginResult.data) {
                    // Store user data
                    localStorage.setItem('gnisUser', JSON.stringify(loginResult.data.user || loginResult.data));
                    
                    // Update UI
                    showUserInfo(loginResult.data.user || loginResult.data);
                    
                    // Close modal
                    const authModal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                    if (authModal) {
                        authModal.hide();
                    }
                    
                    // Reset form
                    registerForm.reset();
                    
                    // Show success message
                    alert('Registration successful! You are now logged in.');
                } else {
                    // Registration succeeded but login failed
                    alert('Registration successful! Please login with your credentials.');
                    
                    // Switch to login tab
                    document.getElementById('login-tab').click();
                }
            } else {
                throw new Error(result.message || 'Registration failed');
            }
        } catch (error) {
            showAlert(registerAlert, error.message || 'Registration failed. Please try again.');
        } finally {
            hideLoading(registerForm);
        }
    }
    
    // Handle logout
    async function handleLogout() {
        try {
            await usersApi.logout();
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear local storage and update UI
            localStorage.removeItem('gnisUser');
            hideUserInfo();
        }
    }

    // Handle certificate request
    async function handleCertificateRequest() {
        // Get selected points from the global array
        const selectedPoints = window.selectedPointsList || [];
        
        if (selectedPoints.length === 0) {
            alert('Please select at least one point before requesting a certificate.');
            return;
        }
        
        // Get point IDs
        const pointIds = selectedPoints.map(point => point.id);
        
        try {
            const result = await usersApi.requestCertificate(pointIds, {
                request_date: new Date().toISOString().split('T')[0],
                request_purpose: 'Explorer Application'
            });
            
            if (result.status === 200) {
                alert(`Certificate request submitted successfully! Reference ID: ${result.data.reference_id || 'N/A'}`);
                
                // Clear selected points after successful request
                window.selectedPointsList = [];
                updateSelectedPointsTable();
            } else {
                throw new Error(result.message || 'Failed to request certificate');
            }
        } catch (error) {
            alert(`Error requesting certificate: ${error.message}`);
        }
    }
    
    // Toggle between individual and company fields in registration form
    function toggleUserTypeFields() {
        const isCompany = companyType.checked;
        
        if (isCompany) {
            individualFields.classList.add('d-none');
            companyFields.classList.remove('d-none');
        } else {
            individualFields.classList.remove('d-none');
            companyFields.classList.add('d-none');
        }
    }
    
    // Show alert messages in the forms
    function showAlert(alertElement, message) {
        if (alertElement) {
            alertElement.textContent = message;
            alertElement.classList.remove('d-none');
            
            // Hide alert after 5 seconds
            setTimeout(() => {
                alertElement.classList.add('d-none');
            }, 5000);
        }
    }
}); 