// Admin App Module

import { state } from './config.js';
import { initAuth } from './auth.js';
import { 
    fetchStationsByType, 
    fetchLocationData, 
    setupLocationCascade 
} from './station-service.js';
import { showAddForm, updateFormFieldsVisibility, handleFormSubmit } from './station-form.js';
import { resetFilters } from './station-table.js';

/**
 * Main function to initialize the admin interface
 */
export function initializeAdminInterface() {
    // Fetch initial data
    fetchStationsByType(state.currentStationType);
    
    // Fetch location data for dropdowns
    fetchLocationData();
    
    // Set up event listeners
    initializeEventListeners();
}

/**
 * Sets up event listeners for interactive elements
 */
function initializeEventListeners() {
    // Add new button
    const addNewBtn = document.getElementById('addNewBtn');
    if (addNewBtn) {
        addNewBtn.addEventListener('click', showAddForm);
    }
    
    // Form submit
    const stationForm = document.getElementById('stationForm');
    if (stationForm) {
        stationForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Cancel button
    const cancelBtn = document.getElementById('cancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            document.getElementById('stationForm').classList.add('hidden');
            document.getElementById('welcomePanel').classList.remove('hidden');
        });
    }
    
    // Reset filters button
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }
    
    // Set up the station type radio buttons
    document.querySelectorAll('input[name="gcpType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const newType = this.value;
            
            // Don't fetch if type hasn't changed
            if (newType === state.currentStationType) {
                return;
            }
            
            console.log(`Changed station type to: ${newType}`);
            
            // Update column labels in the table header
            updateTableHeader(newType);
            
            // Update visibility of form fields
            updateFormFieldsVisibility(newType);
            
            // Update state and fetch new data
            state.currentStationType = newType;
            fetchStationsByType(newType);
        });
    });
    
    // Set up location cascade for dropdowns
    setupLocationCascade();
    
    // Station name search
    const searchInput = document.getElementById('stationNameSearch');
    if (searchInput) {
        // Adding debounce to reduce API calls while typing
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Just reapply filters from existing data without fetching
                document.dispatchEvent(new Event('applyFilters'));
            }, 300);
        });
    }
    
    // Create a custom event for applying filters
    document.addEventListener('applyFilters', function() {
        const stationsTable = document.getElementById('stationsTable');
        // Apply filters to current stations
        // This will be handled by the station-table module
    });
}

/**
 * Updates the table header based on station type
 * @param {string} type - Station type
 */
function updateTableHeader(type) {
    const valueColumnHeader = document.querySelector('#stationsTable thead tr th:nth-child(4)');
    
    if (valueColumnHeader) {
        if (type === 'vertical') {
            valueColumnHeader.textContent = 'Elevation (m)';
        } else if (type === 'horizontal') {
            valueColumnHeader.textContent = 'Ellip. Height (m)';
        } else if (type === 'gravity') {
            valueColumnHeader.textContent = 'Gravity (mGal)';
        }
    }
}

// Initialize the app when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize authentication handling
    initAuth();
}); 