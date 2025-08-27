// main.js - Main entry point for the WebGNIS application
import { logError } from './utils.js';
import { initializeMap } from './map.js';
import { initializeEventListeners } from './events.js';
import { initializeSearch } from './search.js';
import { fetchStationsByType } from './stations.js';

// Initialize application
function initializeApplication() {
    try {
        console.log('Initializing application...');
        
        // Initialize the map
        const map = initializeMap();
        
        // Initialize global variables
        window.allStations = [];
        window.selectedPointsList = [];
        window.currentStationType = '';
        window.allPoints = [];
        
        // Initialize event listeners
        initializeEventListeners();
        
        // Initialize search functionality
        initializeSearch();
        
        console.log('Initialization complete!');
    } catch (error) {
        logError('Application initialization', error);
    }
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    try {
        // Set Horizontal GCP type as default
        const horizontalRadio = document.querySelector('input[name="gcpType"][value="horizontal"]');
        if (horizontalRadio) {
            horizontalRadio.checked = true;
        }

        // Fetch horizontal stations by default
        if (typeof fetchStationsByType === 'function') {
            fetchStationsByType('horizontal');
        } else {
            console.error('Failed to initialize: fetchStationsByType function not found. Ensure stations.js is loaded as a module and exports the function.');
        }

        initializeApplication();

        // Initialize the description modal once
        const descriptionModalElement = document.getElementById('descriptionModal');
        const descriptionModal = new bootstrap.Modal(descriptionModalElement);
        const descriptionImage = document.getElementById('descriptionImage');
        const noDescriptionMessage = document.getElementById('noDescriptionMessage');

        // Add event listener for view description buttons
        document.body.addEventListener('click', function(event) {
            if (event.target.closest('.btn-view-description')) { // Use closest to handle clicks on icon within button
                const button = event.target.closest('.btn-view-description');
                const stationName = button.dataset.stationName;
                
                const imageUrl = 'https://storage.googleapis.com/desc-images/' + stationName.replace(/ /g, "_") + '.jpg';
                
                descriptionImage.src = imageUrl;
                descriptionImage.classList.remove('d-none');
                noDescriptionMessage.classList.add('d-none');

                descriptionImage.onerror = function() {
                    descriptionImage.classList.add('d-none');
                    noDescriptionMessage.classList.remove('d-none');
                };
                
                descriptionModal.show();
            }
        });

    } catch (error) {
        logError('Application initialization', error);
    }
});

// For use in inline HTML event handlers and legacy code
window.initializeApplication = initializeApplication; 