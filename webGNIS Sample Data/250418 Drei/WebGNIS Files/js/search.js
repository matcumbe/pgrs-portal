// search.js - Search functionality for the WebGNIS application
import { logError, showError, debounce } from './utils.js';
import { updateMapMarkers } from './map.js';
import { updateTable, updateSearchResults } from './stations.js';

// Global variables to work with pagination
let allFilteredStations = [];

// Reset pagination when search changes
function resetPagination() {
    // This function assumes there's a currentPage variable in stations.js
    if (window.currentPage !== undefined) {
        window.currentPage = 1;
    }
}

// Setup search functionality
function setupSearchListener() {
    const searchInput = document.getElementById('stationNameSearch');
    
    if (searchInput) {
        // Add input event listener for real-time filtering
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            // Use allStationChoices from stations.js, which should hold all stations for the current filters
            let sourceStations = [];
            if (window.allStationChoices && Array.isArray(window.allStationChoices)) {
                sourceStations = [...window.allStationChoices];
            } else if (window.allStations && Array.isArray(window.allStations)) {
                // Fallback to allStations if allStationChoices is not available
                sourceStations = [...window.allStations]; 
            }

            if (sourceStations.length === 0) {
                console.log('No source stations available to search through');
                // Optionally, clear results or show a message if no source stations
                updateSearchResults([]); 
                updateMapMarkers([]);
                return;
            }

            // If search is empty, show all stations that match the current filters
            if (searchTerm === '') {
                resetPagination();
                // applyFilters() from stations.js should repopulate with current filter set
                // Or, if applyFilters isn't desired here, directly use sourceStations
                updateSearchResults(sourceStations);
                updateMapMarkers(sourceStations);
                return;
            }

            // Filter stations based on search term
            const filteredStations = sourceStations.filter(station => {
                const stationName = (station.station_name || station.name || '').toLowerCase();
                // More robust normalization: handle various characters and ensure it's a string
                const normalizedStationName = String(stationName).replace(/[\s-_()\.]/g, ''); // Added dot to regex
                const normalizedSearchTerm = String(searchTerm).replace(/[\s-_()\.]/g, '');
                return normalizedStationName.includes(normalizedSearchTerm);
            });

            console.log(`Found ${filteredStations.length} matches for "${searchTerm}"`);

            // Reset pagination and update the table with filtered results
            resetPagination();
            updateSearchResults(filteredStations);
            
            // Update map with filtered stations
            updateMapMarkers(filteredStations);
        }, 300));
    }
}

// Set up search by radius
function setupRadiusSearch() {
    const pinLat = document.getElementById('pinLat');
    const pinLng = document.getElementById('pinLng');
    const searchRadius = document.getElementById('searchRadius');
    const radiusSearchBtn = document.getElementById('searchByRadiusBtn');
    
    if (pinLat && pinLng && searchRadius && radiusSearchBtn) {
        radiusSearchBtn.addEventListener('click', async () => {
            clearError();
            if (!pinLat.value || !pinLng.value || !searchRadius.value) {
                showError('Please fill in all coordinates and radius fields');
                return;
            }
            
            toggleLoading(true);
            try {
                const response = await fetch(`api.php?path=/api/stations/radius?lat=${pinLat.value}&lng=${pinLng.value}&radius=${searchRadius.value}`);
                if (!response.ok) throw new Error('Radius search failed');
                const data = await response.json();
                
                // Reset pagination for new search
                resetPagination();
                updateSearchResults(data);
            } catch (error) {
                showError('Failed to search by radius: ' + error.message);
            } finally {
                toggleLoading(false);
            }
        });
    }
}

// Initialize search components
function initializeSearch() {
    setupSearchListener();
    setupRadiusSearch();
    
    // Remove the search button since we're using real-time search
    const searchBtn = document.getElementById('searchByNameBtn');
    if (searchBtn) {
        searchBtn.remove();
    }
}

// Export search functionality
export {
    setupSearchListener,
    setupRadiusSearch,
    initializeSearch,
    resetPagination
}; 