// main.js - Main entry point for the WebGNIS application
import { initializeMap } from './map.js';
import { initializeStations } from './stations.js';
import { initializeAuth } from './users/auth.js';
import { initializeCart } from './cart.js';
import { populateLocationDropdowns, setupFilterListeners, handleSearch, handleClear } from './search.js';

/**
 * Prompts the user to select a station type if the station is horizontal,
 * then adds the station to the cart.
 * @param {string} stationId - The ID of the station.
 * @param {string} stationName - The name of the station.
 * @param {string} stationType - The general type of the station (e.g., 'horizontal').
 */
function promptStationTypeAndAddToCart(stationId, stationName, stationType) {
    if (stationType.toLowerCase() === 'horizontal') {
        const stationTypeModalElement = document.getElementById('stationTypeModal');
        const stationTypeModal = new bootstrap.Modal(stationTypeModalElement);
        
        // Store station details on the modal element to be retrieved by the button click handlers
        stationTypeModalElement.dataset.stationId = stationId;
        stationTypeModalElement.dataset.stationName = stationName;

        stationTypeModal.show();
    } else {
        // For vertical and gravity stations, add them directly to the cart.
        window.addToCart(stationName, stationId, stationType);
    }
}
window.promptStationTypeAndAddToCart = promptStationTypeAndAddToCart;


document.addEventListener('DOMContentLoaded', async () => {
    // Initialize map and wait for it to be ready
    const map = await initializeMap();
    
    // Initialize user authentication
    initializeAuth();
    
    // Initialize cart functionality
    initializeCart();
    
    // Initialize station-related functionalities and pass the map instance
    initializeStations(map);
    
    // Populate location dropdowns
    await populateLocationDropdowns();
    
    // Set up listeners for the filter controls
    setupFilterListeners();
    
    // Set up search and clear buttons
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', () => handleSearch(map));
    }
    
    const clearBtn = document.getElementById('clearBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', handleClear);
    }

    // --- Modal and Button Event Listeners ---

    // Listener for the "View Description" buttons
    const descriptionModal = new bootstrap.Modal(document.getElementById('descriptionModal'));
    const descriptionImage = document.getElementById('descriptionImage');
    const noDescriptionMessage = document.getElementById('noDescriptionMessage');

    document.body.addEventListener('click', function(event) {
        if (event.target.closest('.btn-view-description')) {
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

    // Listeners for the station type selection modal (for horizontal stations)
    const stationTypeModalElement = document.getElementById('stationTypeModal');
    const stationTypeModalInstance = bootstrap.Modal.getInstance(stationTypeModalElement) || new bootstrap.Modal(stationTypeModalElement);

    const modalRegularButton = document.getElementById('modalRegularButton');
    if (modalRegularButton) {
        modalRegularButton.addEventListener('click', () => {
            const { stationId, stationName } = stationTypeModalElement.dataset;
            window.addToCart(stationName, stationId, 'horizontal'); // 'horizontal' for Reference
            stationTypeModalInstance.hide();
        });
    }

    const modalCaapButton = document.getElementById('modalCaapButton');
    if (modalCaapButton) {
        modalCaapButton.addEventListener('click', () => {
            const { stationId, stationName } = stationTypeModalElement.dataset;
            window.addToCart(stationName, stationId, 'caap'); // 'caap' for CAAP (EGM 2008)
            stationTypeModalInstance.hide();
        });
    }
});