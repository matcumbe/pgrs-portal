// Admin API Client Module

import { API_ENDPOINT, USE_MOCK_DATA, SHOW_API_ERRORS } from './config.js';
import { getMockData } from './mock-data.js';
import { showError } from './ui-utils.js';

/**
 * Makes an API request to the server
 * @param {string} endpoint - The API endpoint to call
 * @param {Object} params - Query parameters
 * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
 * @param {Object} body - Request body for POST/PUT requests
 * @returns {Promise} - Promise resolving to the API response
 */
export async function apiRequest(endpoint, params = {}, method = 'GET', body = null) {
    // If in mock mode, return mock data instead of making a real request
    if (USE_MOCK_DATA) {
        return getMockData(endpoint);
    }

    try {
        // Build URL with query parameters
        let url = `${API_ENDPOINT}?path=${encodeURIComponent(endpoint)}`;
        
        // Add additional parameters if provided
        for (const key in params) {
            url += `&${key}=${encodeURIComponent(params[key])}`;
        }
        
        // Configure request options
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Add request body for POST and PUT requests
        if (body && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(body);
        }
        
        // Make the fetch request
        const response = await fetch(url, options);
        const data = await response.json();
        
        // Check for API errors
        if (!response.ok) {
            throw new Error(data.error || `HTTP error ${response.status}`);
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // The API returns data inside a 'data' property if successful
        return data.data || data;
        
    } catch (error) {
        if (SHOW_API_ERRORS) {
            console.error('API Error:', error);
        }
        throw error;
    }
}

// Location Data API Functions
export async function fetchRegions() {
    try {
        return await apiRequest('/api/admin/regions');
    } catch (error) {
        showError('Failed to fetch regions: ' + error.message);
        return [];
    }
}

export async function fetchProvinces(region = '') {
    try {
        const params = region ? { region } : {};
        return await apiRequest('/api/admin/provinces', params);
    } catch (error) {
        showError('Failed to fetch provinces: ' + error.message);
        return [];
    }
}

export async function fetchCities(province = '') {
    try {
        const params = province ? { province } : {};
        return await apiRequest('/api/admin/cities', params);
    } catch (error) {
        showError('Failed to fetch cities: ' + error.message);
        return [];
    }
}

export async function fetchBarangays(city = '') {
    try {
        const params = city ? { city } : {};
        return await apiRequest('/api/admin/barangays', params);
    } catch (error) {
        showError('Failed to fetch barangays: ' + error.message);
        return [];
    }
}

// Station Data API Functions
export async function fetchStationsByType(type) {
    try {
        return await apiRequest(`/api/admin/stations/${type}`);
    } catch (error) {
        showError('Failed to fetch stations: ' + error.message);
        return [];
    }
}

export async function fetchStationById(id) {
    try {
        return await apiRequest(`/api/admin/station/${id}`);
    } catch (error) {
        showError('Failed to fetch station details: ' + error.message);
        return null;
    }
}

export async function createStation(stationData) {
    try {
        // Make sure date fields are properly formatted
        const formattedData = formatDateFields(stationData);
        const result = await apiRequest('/api/admin/station', {}, 'POST', formattedData);
        return result;
    } catch (error) {
        showError('Failed to create station: ' + error.message);
        throw error;
    }
}

export async function updateStation(id, stationData) {
    try {
        // Make sure date fields are properly formatted
        const formattedData = formatDateFields(stationData);
        const result = await apiRequest(`/api/admin/station/${id}`, {}, 'PUT', formattedData);
        return result;
    } catch (error) {
        showError('Failed to update station: ' + error.message);
        throw error;
    }
}

export async function deleteStation(id) {
    try {
        return await apiRequest(`/api/admin/station/${id}`, {}, 'DELETE');
    } catch (error) {
        showError('Failed to delete station: ' + error.message);
        throw error;
    }
}

/**
 * Ensures date fields are formatted correctly for the API
 * @param {Object} data - The station data
 * @returns {Object} - The formatted data
 */
function formatDateFields(data) {
    const formattedData = {...data};
    
    // Format date fields to YYYY-MM-DD
    const dateFields = [
        'date_established', 
        'date_last_updated', 
        'date_measured',
        'horizontal_date_entry',
        'horizontal_date_computed',
        'elevation_date_entry',
        'elevation_date_computed'
    ];
    
    dateFields.forEach(field => {
        if (formattedData[field]) {
            // Handle both string dates and Date objects
            if (formattedData[field] instanceof Date) {
                const d = formattedData[field];
                formattedData[field] = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            } else if (typeof formattedData[field] === 'string' && formattedData[field].trim() === '') {
                // Remove empty strings for date fields
                delete formattedData[field];
            }
        }
    });
    
    // Convert numeric strings to actual numbers for the database
    const numericFields = [
        'latitude', 'longitude', 'elevation', 'ellipsoidal_height', 
        'gravity_value', 'standard_deviation', 'order', 'utm_northing',
        'utm_easting', 'utm_zone', 'bm_plus', 'horizontal_order',
        'elevation_order', 'mark_type', 'mark_status', 'mark_const'
    ];
    
    numericFields.forEach(field => {
        if (formattedData[field] !== undefined && formattedData[field] !== null && formattedData[field] !== '') {
            if (!isNaN(formattedData[field])) {
                formattedData[field] = Number(formattedData[field]);
            }
        } else if (formattedData[field] === '') {
            // Remove empty strings for numeric fields
            delete formattedData[field];
        }
    });
    
    return formattedData;
} 