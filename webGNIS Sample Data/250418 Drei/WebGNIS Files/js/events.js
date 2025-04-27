// events.js - Event handlers for the WebGNIS application
import { showError } from './utils.js';
import { fetchStationsByType, applyFilters } from './stations.js';

// Set up event listeners for location filters to handle cascading updates
function setupFilterEventListeners() {
    // Region change - reset dependent dropdowns
    document.getElementById('region').addEventListener('change', function() {
        // Reset child selections when parent changes
        document.getElementById('province').value = '';
        document.getElementById('city').value = '';
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    // Province change - reset dependent dropdowns
    document.getElementById('province').addEventListener('change', function() {
        // Reset child selections when parent changes
        document.getElementById('city').value = '';
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    // City change - reset dependent dropdown
    document.getElementById('city').addEventListener('change', function() {
        // Reset child selection when parent changes
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    // Barangay change
    document.getElementById('barangay').addEventListener('change', applyFilters);

    // Order filter
    document.getElementById('orderFilter').addEventListener('change', applyFilters);
    
    // Accuracy filter if it exists
    const accuracyFilter = document.getElementById('accuracyFilter');
    if (accuracyFilter) {
        accuracyFilter.addEventListener('change', applyFilters);
    }
}

// Set up event listeners for GCP Type radio buttons
function setupGCPTypeEventListeners() {
    document.querySelectorAll('input[name="gcpType"]').forEach(radio => {
        radio.addEventListener('change', async function() {
            try {
                const type = this.value;
                const accuracyContainer = document.getElementById('accuracyClassContainer');
                if (accuracyContainer) {
                    accuracyContainer.style.display = type === 'vertical' ? 'block' : 'none';
                }
                await fetchStationsByType(type);
            } catch (error) {
                showError('Failed to change GCP type', error);
            }
        });
    });
}

// Initialize default selection
function initializeDefaultSelection() {
    // Initialize with vertical type selected
    const verticalRadio = document.getElementById('verticalType');
    if (verticalRadio) {
        verticalRadio.checked = true;
        fetchStationsByType('vertical');
    }

    // Initialize visibility based on default selection
    const initialType = document.querySelector('input[name="gcpType"]:checked');
    if (initialType) {
        const accuracyContainer = document.getElementById('accuracyClassContainer');
        if (accuracyContainer) {
            accuracyContainer.style.display = initialType.value === 'vertical' ? 'block' : 'none';
        }
    }
}

// Global error handler
function setupGlobalErrorHandler() {
    window.addEventListener('error', function(event) {
        console.error('Global error:', event.error);
        showError('An error occurred: ' + event.error.message);
    });
}

// Initialize all event listeners
function initializeEventListeners() {
    setupFilterEventListeners();
    setupGCPTypeEventListeners();
    initializeDefaultSelection();
    setupGlobalErrorHandler();
}

// Export event functionality
export {
    setupFilterEventListeners,
    setupGCPTypeEventListeners,
    initializeDefaultSelection,
    setupGlobalErrorHandler,
    initializeEventListeners
}; 