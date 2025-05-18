// utils.js - Utility functions for the WebGNIS application

// *** FIXED VERSION: Prioritizes database data retrieval with proper debugging ***

// Initialize error logging
function logError(location, error) {
    console.error(`Error in ${location}:`, error);
    console.trace();
}

// Display error messages
function showError(message, error = null) {
    console.error('Error:', message, error);
    
    // Try to update UI if available
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

// API request function with proper database prioritization
async function apiRequest(endpoint, params = {}) {
    console.log(`API Request to: ${endpoint}`);

    // Show loading indicator if available
    try {
        toggleLoading(true);
    } catch (e) {}

    // Extract station type from endpoint
    const type = endpoint.includes('vertical') ? 'vertical' : 
                endpoint.includes('horizontal') ? 'horizontal' : 
                endpoint.includes('gravity') ? 'gravity' : 'vertical';

    try {
        // Construct API URL
        const queryString = new URLSearchParams(params).toString();
        let url = `api.php?path=${endpoint}${queryString ? '&' + queryString : ''}`;
        
        console.log(`Attempting primary API: ${url}`);

        // Primary API request
        const response = await fetch(url);
        
        if (response.ok) {
            const text = await response.text();
            try {
                // Parse the response
                const data = JSON.parse(text);
                
                // Check if we got valid data
                if (data.data && Array.isArray(data.data)) {
                    console.log(`SUCCESS: Got ${data.data.length} stations from ${data.source || 'API'}`);
                    
                    // Add a debug message to the page if it's not database data
                    if (data.source === 'fallback') {
                        console.warn("WARNING: Using fallback sample data, not database data!");
                        
                        // Try to show a warning in the UI
                        const warningDiv = document.createElement('div');
                        warningDiv.className = 'alert alert-warning';
                        warningDiv.style.position = 'fixed';
                        warningDiv.style.bottom = '10px';
                        warningDiv.style.right = '10px';
                        warningDiv.style.zIndex = '9999';
                        warningDiv.innerHTML = `<strong>Warning:</strong> Using sample data, not database data!`;
                        document.body.appendChild(warningDiv);
                        
                        // Remove after 5 seconds
                        setTimeout(() => {
                            try {
                                document.body.removeChild(warningDiv);
                            } catch (e) {}
                        }, 5000);
                    }
                    
                    return data;
                } else {
                    throw new Error("Invalid data structure in response");
                }
            } catch (jsonError) {
                console.error("Failed to parse JSON:", jsonError);
                throw jsonError;
            }
        } else {
            console.error(`API returned status: ${response.status}`);
            throw new Error(`HTTP error: ${response.status}`);
        }
    } catch (error) {
        console.warn("Primary API failed, trying fallback:", error.message);
        
        try {
            // Try stations-api.php as fallback
            const fallbackUrl = `stations-api.php?type=${type}`;
            console.log(`Trying fallback API: ${fallbackUrl}`);
            
            const fallbackResponse = await fetch(fallbackUrl);
            
            if (fallbackResponse.ok) {
                const fallbackText = await fallbackResponse.text();
                const fallbackData = JSON.parse(fallbackText);
                
                if (fallbackData.data && Array.isArray(fallbackData.data)) {
                    console.log(`SUCCESS: Got ${fallbackData.data.length} stations from fallback API (${fallbackData.source || 'fallback'})`);
                    
                    // Show warning if not database data
                    if (fallbackData.source !== 'database') {
                        console.warn("WARNING: Using fallback sample data, not database data!");
                        showError("Using fallback sample data - not real database data!");
                    }
                    
                    return fallbackData;
                }
            }
            throw new Error("Fallback API failed too");
        } catch (fallbackError) {
            console.error("All APIs failed. Last error:", fallbackError);
            showError("Failed to load station data. Using local fallback data.");
            
            // Final fallback to client-side data generation
            return {
                success: true,
                data: generateFallbackData(type),
                source: 'client_fallback',
                timestamp: new Date().toISOString()
            };
        }
    } finally {
        // Hide loading indicator
        try {
            toggleLoading(false);
        } catch (e) {}
    }
}

// Generate fallback data when both APIs fail
function generateFallbackData(type, count = 20) {
    console.warn(`EMERGENCY: Generating client-side fallback ${type} data`);
    
    const data = [];
    
    for (let i = 1; i <= count; i++) {
        const stationId = `${type.charAt(0)}${String(i).padStart(3, '0')}`;
        const stationName = `${type.toUpperCase()}-${i}`;
        
        const station = {
            station_id: stationId,
            station_name: stationName,
            latitude: 14.5995 + (Math.random() * 0.2 - 0.1),
            longitude: 120.9842 + (Math.random() * 0.2 - 0.1),
            region: 'NCR',
            province: 'Metro Manila',
            city: 'Manila',
            barangay: `Barangay ${Math.floor(Math.random() * 20) + 1}`
        };
        
        // Add type-specific fields
        if (type === 'vertical') {
            station.elevation = Math.floor(Math.random() * 100) + Math.random();
            station.elevation_order = '1st';
            station.accuracy_class = `Class ${Math.floor(Math.random() * 3) + 1}`;
        } else if (type === 'horizontal') {
            station.ellipsoidal_height = Math.floor(Math.random() * 100) + Math.random();
            station.horizontal_order = '1st';
        } else if (type === 'gravity') {
            station.gravity_value = 978100 + Math.floor(Math.random() * 500);
            station.order = '1st';
        }
        
        data.push(station);
    }
    
    return data;
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

// Detect station type from path
function getStationTypeFromPath(path) {
    if (!path) return 'vertical';
    
    if (path.includes('vertical')) return 'vertical';
    if (path.includes('horizontal')) return 'horizontal';
    if (path.includes('gravity')) return 'gravity';
    
    const match = path.match(/stations\/(\w+)/i);
    if (match && match[1]) {
        const type = match[1].toLowerCase();
        if (['vertical', 'horizontal', 'gravity'].includes(type)) {
            return type;
        }
    }
    
    return 'vertical'; // Default
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