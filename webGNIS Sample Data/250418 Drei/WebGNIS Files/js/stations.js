// stations.js - Station data and management functionality
import { logError, showError, apiRequest } from './utils.js';
import { updateMap } from './map.js';

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

// Update search results table
function updateSearchResults(stations) {
    try {
        const tbody = document.getElementById('searchResults');
        const thead = tbody.closest('table').querySelector('thead tr'); // Get the header row
        if (!tbody) {
            throw new Error('Search results table body not found');
        }
        if (!thead) {
            throw new Error('Search results table header not found');
        }
        tbody.innerHTML = '';

        // Determine current GCP type
        const gcpType = document.querySelector('input[name="gcpType"]:checked')?.value || 'vertical';

        // Get the header cell for the dynamic column (4th column, index 3)
        const dynamicHeaderCell = thead.cells[3];
        let elevationHeader = 'Elevation';
        let elevationKey = 'elevation';

        if (gcpType === 'horizontal') {
            elevationHeader = 'Ell. Height';
            elevationKey = 'ellipsoidal_height';
        } else if (gcpType === 'gravity') {
            elevationHeader = 'Grav. Value';
            elevationKey = 'gravity_value';
        }

        // Update the header text
        dynamicHeaderCell.textContent = elevationHeader;

        stations.forEach(station => {
            const row = document.createElement('tr');
            row.dataset.stationId = station.station_id;

            // Get the correct value based on GCP type
            const elevationValue = station[elevationKey] || 'N/A';
            const orderValue = station.order || station.elevation_order || station.horizontal_order || 'N/A';
            
            row.innerHTML = `
                <td>${station.station_name || ''}</td>
                <td>${station.latitude || ''}</td>
                <td>${station.longitude || ''}</td>
                <td>${elevationValue}</td>
                <td>${orderValue}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="directAddToSelected('${station.station_id}', '${station.station_name || ''}')">
                        <i class="fa fa-cart-plus" aria-hidden="true"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        logError('updateSearchResults', error);
        showError('Failed to update search results: ' + error.message);
    }
}

// Apply filters with improved error handling
async function applyFilters() {
    try {
        const type = document.querySelector('input[name="gcpType"]:checked').value;
        const order = document.getElementById('orderFilter').value;
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

// Update table with filtered data
function updateTable(points) {
    const tbody = document.getElementById('searchResults');
    tbody.innerHTML = '';

    points.forEach(point => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${point.stationName || point.station_name}</td>
            <td>${point.latitude}</td>
            <td>${point.longitude}</td>
            <td>${point.elevation}</td>
            <td>${point.order}</td>
            <td>
                <button class="btn btn-add-to-cart" onclick="directAddToSelected('${point.stationId || point.station_id}', '${point.stationName || point.station_name}')">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Filter table and map based on search term
function filterTableAndMap(searchTerm) {
    // Filter the data
    const filteredPoints = window.allPoints.filter(point => {
        // Case insensitive search on station name
        // Also handle different formats (remove spaces, special characters)
        const searchableText = (point.stationName || point.station_name || '').toLowerCase()
            .replace(/[\s-_()]/g, ''); // Remove spaces, hyphens, underscores, parentheses
        const processedSearchTerm = searchTerm
            .replace(/[\s-_()]/g, '');
        
        return searchableText.includes(processedSearchTerm);
    });

    // Update table
    updateTable(filteredPoints);
    
    // Update map markers
    updateMapMarkers(filteredPoints);
}

// Export station functionality
export {
    fetchStationsByType,
    updateSearchResults,
    applyFilters,
    updateFiltersBasedOnData,
    updateTable,
    filterTableAndMap
}; 