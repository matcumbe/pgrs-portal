// utils.js - Utility functions for the WebGNIS application

// Initialize error logging
function logError(location, error) {
    console.error(`Error in ${location}:`, error);
    console.trace(); // Add stack trace
}

// Display error messages
function showError(message, error = null) {
    const errorContainer = document.getElementById('errorMessages');
    if (errorContainer) {
        let errorMessage = message;
        if (error) {
            errorMessage += `<br><small class="text-muted">Details: ${error.message || error}</small>`;
        }
        errorContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${errorMessage}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        errorContainer.classList.remove('d-none');
    }
    console.error('Error:', message, error);
}

// Clear error messages
function clearError() {
    const errorContainer = document.getElementById('errorMessages');
    if (errorContainer) {
        errorContainer.innerHTML = '';
        errorContainer.classList.add('d-none');
    }
}

// Show/hide loading indicator
function toggleLoading(show) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.classList.toggle('d-none', !show);
    }
}

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function for performance
function throttle(func, limit) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func(...args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Standard API request function with error handling
async function apiRequest(endpoint, params = {}) {
    try {
        toggleLoading(true);
        clearError();

        const queryString = new URLSearchParams(params).toString();
        const url = `api.php?path=${endpoint}${queryString ? '&' + queryString : ''}`;
        
        console.log('Making API request to:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || `HTTP error! status: ${response.status}`);
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        showError('API request failed', error);
        throw error;
    } finally {
        toggleLoading(false);
    }
}

// Debug function to check filter values
function logFilterValues() {
    const type = document.querySelector('input[name="gcpType"]:checked')?.value;
    const order = document.getElementById('orderFilter')?.value;
    const accuracy = document.getElementById('accuracyFilter')?.value;
    const region = document.getElementById('region')?.value;
    const province = document.getElementById('province')?.value;
    const city = document.getElementById('city')?.value;
    const barangay = document.getElementById('barangay')?.value;

    console.log('Current filter values:', {
        type,
        order,
        accuracy,
        region,
        province,
        city,
        barangay
    });
}

// Export the utilities
export {
    logError,
    showError,
    clearError,
    toggleLoading,
    debounce,
    throttle,
    apiRequest,
    logFilterValues
}; 