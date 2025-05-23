// search.js - Search functionality for the WebGNIS application
import { logError, showError, debounce } from './utils.js';
import { updateMapMarkers } from './map.js';
import { updateTable } from './stations.js';

// Setup search functionality
function setupSearchListener() {
    const searchInput = document.getElementById('stationNameSearch');
    
    if (searchInput) {
        // Add input event listener for real-time filtering
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            // Get the current stations from the table
            const tableRows = document.getElementById('searchResults').getElementsByTagName('tr');
            const currentStations = [];
            
            // Convert table rows to station objects
            for (let row of tableRows) {
                const cells = row.getElementsByTagName('td');
                if (cells.length >= 5) {
                    currentStations.push({
                        station_name: cells[0].textContent,
                        latitude: parseFloat(cells[1].textContent),
                        longitude: parseFloat(cells[2].textContent),
                        elevation: parseFloat(cells[3].textContent),
                        order: cells[4].textContent
                    });
                }
            }

            if (currentStations.length === 0) {
                console.log('No stations in table to search through');
                return;
            }

            // If search is empty, show all current stations
            if (searchTerm === '') {
                updateTable(currentStations);
                updateMapMarkers(currentStations);
                return;
            }

            // Filter current stations based on search term
            const filteredStations = currentStations.filter(station => {
                const stationName = (station.station_name || '').toLowerCase();
                // Remove special characters and spaces for comparison
                const normalizedStationName = stationName.replace(/[\s-_()]/g, '');
                const normalizedSearchTerm = searchTerm.replace(/[\s-_()]/g, '');
                return normalizedStationName.includes(normalizedSearchTerm);
            });

            console.log(`Found ${filteredStations.length} matches for "${searchTerm}"`);

            // Update only the table rows visibility instead of recreating them
            for (let row of tableRows) {
                const stationName = row.cells[0].textContent.toLowerCase();
                const normalizedStationName = stationName.replace(/[\s-_()]/g, '');
                const normalizedSearchTerm = searchTerm.replace(/[\s-_()]/g, '');
                
                if (normalizedStationName.includes(normalizedSearchTerm)) {
                    row.style.display = ''; // Show matching rows
                } else {
                    row.style.display = 'none'; // Hide non-matching rows
                }
            }

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
    initializeSearch
}; 