// Admin UI Utilities Module

/**
 * Converts decimal coordinate to degrees, minutes, seconds format
 * @param {number} decimal - Decimal coordinate value
 * @returns {Object} DMS format object with degrees, minutes, seconds
 */
export function decimalToDMS(decimal) {
    const absValue = Math.abs(decimal);
    const degrees = Math.floor(absValue);
    const minutesDecimal = (absValue - degrees) * 60;
    const minutes = Math.floor(minutesDecimal);
    const seconds = (minutesDecimal - minutes) * 60;
    
    // Round seconds to 3 decimal places
    const roundedSeconds = Math.round(seconds * 1000) / 1000;
    
    return {
        degrees: decimal < 0 ? -degrees : degrees,
        minutes,
        seconds: roundedSeconds
    };
}

/**
 * Formats date for input elements
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date (YYYY-MM-DD)
 */
export function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

/**
 * Shows/hides the loading indicator
 * @param {boolean} show - Whether to show the loading indicator
 */
export function toggleLoading(show) {
    const loader = document.getElementById('loadingIndicator');
    if (loader) {
        loader.classList.toggle('hidden', !show);
    }
}

/**
 * Shows error message
 * @param {string} message - Error message to display
 */
export function showError(message) {
    console.error(message);
    const errorElement = document.getElementById('errorMessages');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorElement.classList.add('hidden');
        }, 5000);
    } else {
        // Fallback to alert if error element doesn't exist
        alert('Error: ' + message);
    }
}

/**
 * Shows success message
 * @param {string} message - Success message to display
 */
export function showSuccess(message) {
    // Try to create a toast notification
    let toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.top = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '1050';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.style.backgroundColor = '#d4edda';
    toast.style.color = '#155724';
    toast.style.border = '1px solid #c3e6cb';
    toast.style.borderRadius = '4px';
    toast.style.padding = '10px 15px';
    toast.style.marginBottom = '10px';
    toast.style.boxShadow = '0 0.25rem 0.75rem rgba(0, 0, 0, 0.1)';
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" style="margin-left: 10px;">×</button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
    
    // Add click handler to close button
    toast.querySelector('.btn-close').addEventListener('click', () => {
        toast.remove();
    });
}

/**
 * Creates a debounced function to avoid rapid executions
 * @param {Function} func - Function to debounce
 * @param {number} delay - Delay in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
} 