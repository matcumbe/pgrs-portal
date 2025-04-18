// Initialize error logging
function logError(location, error) {
    console.error(`Error in ${location}:`, error);
    console.trace(); // Add stack trace
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        initializeApplication();
    } catch (error) {
        logError('Application initialization', error);
    }
});

function initializeApplication() {
    console.log('Initializing application...');

    // Initialize the map
    console.log('Initializing map...');
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        throw new Error('Map element not found');
    }

    let map = L.map('map').setView([14.6, 121.0], 10); // Centered on Metro Manila

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Initialize global variables
    window.allStations = [];
    window.selectedPointsList = []; // Use an array instead of a Set
    window.currentStationType = '';

    // Initialize markers layer group
    let markersLayer = L.layerGroup().addTo(map);

    // Define marker colors for different orders
    const orderColors = {
        '0': '#FF0000',    // Red
        '1': '#0000FF',    // Blue
        '2': '#00FF00',    // Green
        '3': '#FFA500',    // Orange
        '4': '#800080',    // Purple
        '5': '#008080',    // Teal
        '6': '#FFD700',    // Gold
        '7': '#4B0082'     // Indigo
    };

    // Create custom marker icons for different orders
    function createCustomIcon(color) {
        try {
            return L.divIcon({
                className: 'custom-marker',
                html: `<svg width="24" height="36" viewBox="0 0 24 36">
                    <path fill="${color}" d="M12 0C5.4 0 0 5.4 0 12c0 7.2 12 24 12 24s12-16.8 12-24c0-6.6-5.4-12-12-12z"/>
                    <circle fill="white" cx="12" cy="12" r="4"/>
                </svg>`,
                iconSize: [24, 36],
                iconAnchor: [12, 36],
                popupAnchor: [0, -36]
            });
        } catch (error) {
            logError('createCustomIcon', error);
            return L.divIcon(); // Return default icon if custom one fails
        }
    }

    // Update map markers with colors
    function updateMap(stations) {
        try {
            console.log('Updating map with stations:', stations);
            
            // Clear existing markers
            if (markersLayer) {
                markersLayer.clearLayers();
            }

            if (!Array.isArray(stations) || stations.length === 0) {
                console.log('No stations to display');
                return;
            }

            // Store stations globally
            window.allStations = stations;

            // Initialize bounds
            const bounds = L.latLngBounds([]);
            let hasValidCoordinates = false;

            stations.forEach(station => {
                if (station.latitude && station.longitude) {
                    let order = '';
                    // Get the correct order based on station type
                    if (station.hasOwnProperty('elevation_order')) {
                        order = station.elevation_order;
                    } else if (station.hasOwnProperty('horizontal_order')) {
                        order = station.horizontal_order;
                    } else if (station.hasOwnProperty('order')) {
                        order = station.order;
                    }
                    
                    const color = orderColors[order] || '#999999'; // Default gray for unknown order
                    const marker = L.marker([station.latitude, station.longitude], {
                        icon: createCustomIcon(color)
                    }).bindPopup(`
                        <strong>${station.station_name || ''}</strong><br>
                        Lat: ${station.latitude || ''}<br>
                        Long: ${station.longitude || ''}<br>
                        ${order ? `Order: ${order}<br>` : ''}
                        ${station.accuracy_class ? `Accuracy Class: ${station.accuracy_class}<br>` : ''}
                        <button onclick="directAddToSelected('${station.station_id}', '${station.station_name || ''}')" class="btn btn-sm btn-primary mt-2">
                            Add to Cart
                        </button>
                    `);
                    
                    if (markersLayer) {
                        markersLayer.addLayer(marker);
                    }
                    
                    bounds.extend([station.latitude, station.longitude]);
                    hasValidCoordinates = true;
                }
            });

            // Only fit bounds if we have valid coordinates
            if (hasValidCoordinates && map) {
                map.fitBounds(bounds, { 
                    padding: [50, 50],
                    maxZoom: 15 // Prevent zooming in too close
                });
            }
        } catch (error) {
            console.error('Error in updateMap:', error);
            window.showError('Failed to update map: ' + error.message);
        }
    }

    // Update search results
    function updateSearchResults(stations) {
        try {
            const tbody = document.getElementById('searchResults');
            if (!tbody) {
                throw new Error('Search results table body not found');
            }
            tbody.innerHTML = '';

            stations.forEach(station => {
                const row = document.createElement('tr');
                row.dataset.stationId = station.station_id;
                
                row.innerHTML = `
                    <td>${station.station_name || ''}</td>
                    <td>${station.latitude || ''}</td>
                    <td>${station.longitude || ''}</td>
                    <td>${station.elevation || 'N/A'}</td>
                    <td>${station.order || station.elevation_order || station.horizontal_order || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="directAddToSelected('${station.station_name}', '${station.station_name || ''}')">
                            Add to Cart
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (error) {
            logError('updateSearchResults', error);
            window.showError('Failed to update search results: ' + error.message);
        }
    }

    // Show/hide loading indicator
    function toggleLoading(show) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.classList.toggle('d-none', !show);
        }
    }

    // Display error messages
    window.showError = function(message, error = null) {
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

    // Fetch stations by type with improved error handling
    async function fetchStationsByType(type) {
        try {
            const data = await apiRequest(`/api/stations/${type}`);
            window.allStations = data.data;
            applyFilters();
        } catch (error) {
            console.error('Failed to fetch stations:', error);
        }
    }

    // Apply filters with improved error handling
    async function applyFilters() {
        try {
            const type = document.querySelector('input[name="gcpType"]:checked').value;
            const order = document.getElementById('orderFilter').value;
            const accuracy = document.getElementById('accuracyFilter').value;
            const region = document.getElementById('region').value;
            const province = document.getElementById('province').value;
            const city = document.getElementById('city').value;
            const barangay = document.getElementById('barangay').value;

            let filteredStations = window.allStations || [];

            if (order) {
                filteredStations = filteredStations.filter(station => {
                    const stationOrder = station.order || station.elevation_order || station.horizontal_order;
                    return stationOrder === order;
                });
            }

            if (type === 'vertical' && accuracy) {
                filteredStations = filteredStations.filter(station => 
                    station.accuracy_class === accuracy
                );
            }

            if (region) {
                filteredStations = filteredStations.filter(station => 
                    station.region === region
                );
            }

            if (province) {
                filteredStations = filteredStations.filter(station => 
                    station.province === province
                );
            }

            if (city) {
                filteredStations = filteredStations.filter(station => 
                    station.city === city
                );
            }

            if (barangay) {
                filteredStations = filteredStations.filter(station => 
                    station.barangay === barangay
                );
            }

            updateMap(filteredStations);
            updateSearchResults(filteredStations);
            updateFiltersBasedOnData(filteredStations);
        } catch (error) {
            showError('Failed to apply filters: ' + error.message);
            console.error('Error in applyFilters:', error);
        }
    }

    // Update filters based on available data
    async function updateFiltersBasedOnData(stations) {
        try {
            // Get unique orders from all stations
            const uniqueOrders = [...new Set(window.allStations.map(station => 
                station.order || station.elevation_order || station.horizontal_order
            ).filter(order => order))];
            
            // Update order dropdown while preserving current selection
            const orderFilter = document.getElementById('orderFilter');
            const currentOrder = orderFilter.value;
            orderFilter.innerHTML = '<option value="">Select Order</option>';
            uniqueOrders.sort().forEach(order => {
                orderFilter.innerHTML += `<option value="${order}" ${order === currentOrder ? 'selected' : ''}>${order}</option>`;
            });

            // Update accuracy class dropdown for vertical GCP type
            const type = document.querySelector('input[name="gcpType"]:checked').value;
            if (type === 'vertical') {
                const uniqueAccuracyClasses = [...new Set(window.allStations
                    .filter(station => station.accuracy_class)
                    .map(station => station.accuracy_class))];
                
                const accuracyFilter = document.getElementById('accuracyFilter');
                const currentAccuracy = accuracyFilter.value;
                accuracyFilter.innerHTML = '<option value="">Select Accuracy Class</option>';
                uniqueAccuracyClasses.sort().forEach(accuracy => {
                    accuracyFilter.innerHTML += `<option value="${accuracy}" ${accuracy === currentAccuracy ? 'selected' : ''}>${accuracy}</option>`;
                });
            }

            // Get all unique locations from all stations
            const uniqueRegions = [...new Set(window.allStations.map(station => station.region).filter(region => region))];
            const uniqueProvinces = [...new Set(window.allStations.map(station => station.province).filter(province => province))];
            const uniqueCities = [...new Set(window.allStations.map(station => station.city).filter(city => city))];
            const uniqueBarangays = [...new Set(window.allStations.map(station => station.barangay).filter(barangay => barangay))];

            // Get current selections
            const currentRegion = document.getElementById('region').value;
            const currentProvince = document.getElementById('province').value;
            const currentCity = document.getElementById('city').value;
            const currentBarangay = document.getElementById('barangay').value;

            // Update region dropdown (always show all regions)
            const regionFilter = document.getElementById('region');
            regionFilter.innerHTML = '<option value="">Select Region</option>';
            uniqueRegions.sort().forEach(region => {
                regionFilter.innerHTML += `<option value="${region}" ${region === currentRegion ? 'selected' : ''}>${region}</option>`;
            });

            // Update province dropdown (show all provinces if no region selected, otherwise show provinces for selected region)
            const provinceFilter = document.getElementById('province');
            provinceFilter.innerHTML = '<option value="">Select Province</option>';
            let filteredProvinces = uniqueProvinces;
            if (currentRegion) {
                filteredProvinces = [...new Set(window.allStations
                    .filter(station => station.region === currentRegion)
                    .map(station => station.province)
                    .filter(province => province))];
            }
            filteredProvinces.sort().forEach(province => {
                provinceFilter.innerHTML += `<option value="${province}" ${province === currentProvince ? 'selected' : ''}>${province}</option>`;
            });

            // Update city dropdown (show cities for selected province only)
            const cityFilter = document.getElementById('city');
            cityFilter.innerHTML = '<option value="">Select City/Municipality</option>';
            if (currentProvince) {
                const filteredCities = [...new Set(window.allStations
                    .filter(station => station.province === currentProvince)
                    .map(station => station.city)
                    .filter(city => city))];
                filteredCities.sort().forEach(city => {
                    cityFilter.innerHTML += `<option value="${city}" ${city === currentCity ? 'selected' : ''}>${city}</option>`;
                });
            }

            // Update barangay dropdown (show barangays for selected city only)
            const barangayFilter = document.getElementById('barangay');
            barangayFilter.innerHTML = '<option value="">Select Barangay</option>';
            if (currentCity) {
                const filteredBarangays = [...new Set(window.allStations
                    .filter(station => station.city === currentCity)
                    .map(station => station.barangay)
                    .filter(barangay => barangay))];
                filteredBarangays.sort().forEach(barangay => {
                    barangayFilter.innerHTML += `<option value="${barangay}" ${barangay === currentBarangay ? 'selected' : ''}>${barangay}</option>`;
                });
            }

        } catch (error) {
            showError('Failed to update filter options: ' + error.message);
            console.error('Error in updateFiltersBasedOnData:', error);
        }
    }

    // Set up event listeners for location filters to handle cascading updates
    document.getElementById('region').addEventListener('change', function() {
        // Reset child selections when parent changes
        document.getElementById('province').value = '';
        document.getElementById('city').value = '';
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    document.getElementById('province').addEventListener('change', function() {
        // Reset child selections when parent changes
        document.getElementById('city').value = '';
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    document.getElementById('city').addEventListener('change', function() {
        // Reset child selection when parent changes
        document.getElementById('barangay').value = '';
        applyFilters();
    });

    // Set up event listeners for GCP Type radio buttons
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

    // Set up event listeners for all filters
    const filters = ['orderFilter', 'accuracyFilter', 'region', 'province', 'city', 'barangay'];
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });

    // Initialize with vertical type selected
    const verticalRadio = document.getElementById('verticalType');
    if (verticalRadio) {
        verticalRadio.checked = true;
        fetchStationsByType('vertical');
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

    // Add debug logging to key functions
    const originalApplyFilters = applyFilters;
    applyFilters = async function() {
        console.log('Applying filters...');
        logFilterValues();
        await originalApplyFilters.call(this);
    };

    const originalUpdateFiltersBasedOnData = updateFiltersBasedOnData;
    updateFiltersBasedOnData = function(stations) {
        console.log('Updating filters with stations:', stations);
        return originalUpdateFiltersBasedOnData.call(this, stations);
    };

    // Set up search by name
    const searchInput = document.getElementById('stationNameSearch');
    const searchBtn = document.getElementById('searchByNameBtn');
    if (searchInput && searchBtn) {
        searchBtn.addEventListener('click', async () => {
            clearError();
            toggleLoading(true);
            try {
                const response = await fetch(`api.php?path=/api/stations/search?name=${encodeURIComponent(searchInput.value)}`);
                if (!response.ok) throw new Error('Search failed');
                const data = await response.json();
                updateSearchResults(data);
            } catch (error) {
                showError('Failed to search stations: ' + error.message);
            } finally {
                toggleLoading(false);
            }
        });
    }

    // Set up search by radius
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

    // Initialize visibility based on default selection
    const initialType = document.querySelector('input[name="gcpType"]:checked');
    if (initialType) {
        const accuracyContainer = document.getElementById('accuracyClassContainer');
        if (accuracyContainer) {
            accuracyContainer.style.display = initialType.value === 'vertical' ? 'block' : 'none';
        }
    }

    console.log('Initialization complete!');
}

// Add global error handler
window.addEventListener('error', function(event) {
    console.error('Global error:', event.error);
    showError('An error occurred: ' + event.error.message);
});

// Map initialization with configuration
function initializeMap() {
    const map = L.map('map').setView(
        [DEFAULT_LATITUDE, DEFAULT_LONGITUDE],
        DEFAULT_ZOOM
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: MAX_ZOOM,
        minZoom: MIN_ZOOM,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    return map;
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

// SIMPLIFIED ADD FUNCTION
window.directAddToSelected = function(stationId, stationName) {
    // Create the list if it doesn't exist
    if (!window.selectedPointsList) {
        window.selectedPointsList = [];
    }
    
    // Check if this ID is already in the list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            console.log("Already in cart:", stationId);
            return; // Exit if already in list
        }
    }
    
    // Add to the list
    window.selectedPointsList.push({
        id: stationId,
        name: stationName
    });
    
    // Refresh the table
    updateSelectedPointsTable();
    
    console.log("Added to cart:", stationId);
    console.log("Current list:", window.selectedPointsList);
};

// SIMPLIFIED REMOVE FUNCTION
window.removeFromSelected = function(stationId) {
    // If no list, nothing to remove
    if (!window.selectedPointsList) {
        return;
    }
    
    // Find and remove
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            window.selectedPointsList.splice(i, 1);
            break;
        }
    }
    
    // Refresh the table
    updateSelectedPointsTable();
    
    console.log("Removed from cart:", stationId);
    console.log("Current list:", window.selectedPointsList);
};

// SIMPLIFIED TABLE UPDATE
function updateSelectedPointsTable() {
    const tableBody = document.getElementById('selectedPoints');
    if (!tableBody) {
        console.error("Selected points table not found");
        return;
    }
    
    // Clear the table
    tableBody.innerHTML = '';
    
    // If no list, nothing to show
    if (!window.selectedPointsList || window.selectedPointsList.length === 0) {
        return;
    }
    
    // Add each item to the table
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        const item = window.selectedPointsList[i];
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeFromSelected('${item.id}')">
                    Remove
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    }
} 