/**
 * WebGNIS Users Management
 * Main entry point that imports and initializes the modular components
 */

// Import the modules from the js/users directory
import { usersApi } from './js/users/api-client.js';
import { login, logout, checkAuthStatus, initAuth } from './js/users/auth.js';
import { 
    updateUIByUserRole, 
    showNotification, 
    populateUserForm, 
    extractFormData, 
    updateAuthUI 
} from './js/users/user-ui.js';

// Initialize the app when DOM is ready
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
    
    // Initialize auth and load reference data
    checkAuthStatus().then(status => {
        if (status.authenticated && status.user) {
            showUserInfo(status.user);
        } else {
            hideUserInfo();
        }
    });
    
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
                const userData = result.data.user || result.data;
                localStorage.setItem('gnisUser', JSON.stringify(userData));
                
                // Log user type to console
                console.log('User logged in:', userData);
                console.log('User type:', userData.user_type || 'Not specified');
                
                // Update UI
                showUserInfo(userData);
                
                // Close modal and remove backdrop
                const authModal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                if (authModal) {
                    authModal.hide();
                    // Remove modal backdrop
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
                
                // Reset form
                loginForm.reset();
                
                // Update navigation buttons
                updateNavigationButtons(true);
                
                // Refresh the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 500);
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
        const formData = extractFormData(registerForm);
        
        // Validate required fields
        if (!formData.username || !formData.password || !formData.email || !formData.contact_number || 
            !formData.sex_id || !formData.name_on_certificate) {
            showAlert(registerAlert, 'Please fill in all required fields');
            return;
        }
        
        // Create user data object based on type
        const userTypeValue = document.querySelector('input[name="userType"]:checked').value;
        const userData = {
            ...formData,
            user_type: userTypeValue,
            sex_id: parseInt(formData.sex_id)
        };
        
        // Add type-specific fields for validation
        if (userTypeValue === 'company' && (!formData.company_name || !formData.sector_id || !formData.company_address)) {
            showAlert(registerAlert, 'Please fill in all company fields');
            return;
        } else if (userTypeValue === 'individual' && (!formData.full_name || !formData.address)) {
            showAlert(registerAlert, 'Please fill in all individual fields');
            return;
        }
        
        showLoading(registerForm);
        
        try {
            // Call API to create user
            const result = await usersApi.createUser(userData);
            
            if (result.status === 201 && result.data) {
                // Now login with the created credentials
                const loginResult = await usersApi.login(userData.username, userData.password);
                
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

// Create global instance of the API for backward compatibility
// This keeps the global API object available for any existing code that might use it
window.usersApi = usersApi; 