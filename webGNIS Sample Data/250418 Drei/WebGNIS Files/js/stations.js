// stations.js - Station data and management functionality
import { logError, showError, apiRequest } from './utils.js';
import { updateMap } from './map.js';

// Global variables for pagination
let currentPage = 1;
const itemsPerPage = 10;
let paginatedStations = [];

// Fetch stations by type with improved error handling
async function fetchStationsByType(type) {
    try {
        console.log(`Fetching ${type} stations...`);
        const data = await apiRequest(`/api/stations/${type}`);
        
        if (!data) {
            throw new Error('No response received from API');
        }
        
        if (data.error) {
            throw new Error(`API error: ${data.error}`);
        }
        
        if (!data.data || !Array.isArray(data.data)) {
            console.warn('Invalid or empty data structure received');
            // Use cached data if available, otherwise use empty array
            window.allStations = window.allStations || [];
        } else {
            console.log(`Received ${data.data.length} ${type} stations`);
            window.allStations = data.data;
        }
        
        // Reset pagination when fetching new data
        currentPage = 1;
        applyFilters();
    } catch (error) {
        console.error('Failed to fetch stations:', error);
        showError(`Failed to fetch ${type} stations. Using cached data if available.`, error);
        
        // Try to use cached data if available
        if (!window.allStations || window.allStations.length === 0) {
            window.allStations = [];
            console.log('No cached station data available. Using empty array.');
        }
        
        // Try to apply filters even with cached/empty data
        try {
            applyFilters();
        } catch (filterError) {
            console.error('Error applying filters to cached data:', filterError);
        }
    }
}

// Paginate stations array
function paginateStations(stations, page = 1) {
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return stations.slice(startIndex, endIndex);
}

// Render pagination controls
function renderPagination(stations) {
    const paginationEl = document.getElementById('stationsPagination');
    if (!paginationEl) return;
    
    paginationEl.innerHTML = '';
    
    const totalPages = Math.ceil(stations.length / itemsPerPage);
    if (totalPages <= 1) return; // Don't show pagination if only one page
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>`;
    paginationEl.appendChild(prevLi);
    
    // Page numbers - limit to show 5 pages with ellipsis
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    // Adjust startPage if we're near the end
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    // First page if not in range
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
        paginationEl.appendChild(firstLi);
        
        if (startPage > 2) {
            // Add ellipsis
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<a class="page-link" href="#">...</a>`;
            paginationEl.appendChild(ellipsisLi);
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${currentPage === i ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        paginationEl.appendChild(pageLi);
    }
    
    // Last page if not in range
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            // Add ellipsis
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<a class="page-link" href="#">...</a>`;
            paginationEl.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
        paginationEl.appendChild(lastLi);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>`;
    paginationEl.appendChild(nextLi);
    
    // Add click listeners to pagination buttons
    paginationEl.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', handlePaginationClick);
    });
}

// Handle pagination button clicks
function handlePaginationClick(event) {
    event.preventDefault();
    
    const pageNum = parseInt(event.target.dataset.page);
    if (isNaN(pageNum)) return;
    
    currentPage = pageNum;
    
    // Update table with paginated data
    const paginatedData = paginateStations(paginatedStations, currentPage);
    updateTableWithStations(paginatedData);
    
    // Update pagination controls
    renderPagination(paginatedStations);
    
    // Scroll to top of results
    const resultsTable = document.getElementById('searchResults');
    if (resultsTable) {
        resultsTable.closest('.card').scrollIntoView({ behavior: 'smooth' });
    }
}

// Update search results table with fallback for missing data
function updateSearchResults(stations) {
    try {
        const tbody = document.getElementById('searchResults');
        if (!tbody) {
            throw new Error('Search results table body not found');
        }
        
        // Get the table header
        const thead = tbody.closest('table')?.querySelector('thead tr');
        if (!thead) {
            throw new Error('Search results table header not found');
        }
        
        // Make sure stations is an array
        const stationsArray = Array.isArray(stations) ? stations : [];
        
        // Store the full dataset for pagination
        paginatedStations = stationsArray;
        
        // Get paginated subset
        const paginatedData = paginateStations(stationsArray, currentPage);
        
        // Clear existing rows
        tbody.innerHTML = '';

        if (stationsArray.length === 0) {
            // Add a message row if no data
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `<td colspan="6" class="text-center">No stations found. Try adjusting your filters.</td>`;
            tbody.appendChild(emptyRow);
            
            // Clear pagination
            const paginationEl = document.getElementById('stationsPagination');
            if (paginationEl) paginationEl.innerHTML = '';
            
            return;
        }

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
        if (dynamicHeaderCell) {
            dynamicHeaderCell.textContent = elevationHeader;
        }

        // Add station rows for the current page
        updateTableWithStations(paginatedData);
        
        // Render pagination controls
        renderPagination(stationsArray);
        
    } catch (error) {
        logError('updateSearchResults', error);
        showError('Failed to update search results: ' + error.message);
    }
}

// Update table with station data
function updateTableWithStations(stations) {
    const tbody = document.getElementById('searchResults');
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Determine current GCP type
    const gcpType = document.querySelector('input[name="gcpType"]:checked')?.value || 'vertical';
    
    // Get the correct elevation key based on GCP type
    let elevationKey = 'elevation';
    if (gcpType === 'horizontal') {
        elevationKey = 'ellipsoidal_height';
    } else if (gcpType === 'gravity') {
        elevationKey = 'gravity_value';
    }
    
    // Add station rows
    stations.forEach(station => {
        if (!station) return; // Skip null/undefined entries
        
        const row = document.createElement('tr');
        row.dataset.stationId = station.station_id || '';

        // Get the correct value based on GCP type with fallbacks
        const elevationValue = station[elevationKey] || 'N/A';
        const orderValue = station.order || station.elevation_order || station.horizontal_order || 'N/A';
        const stationName = station.station_name || station.name || station.station_id || 'Unknown';
        
        // Use safe values for coordinates
        const lat = parseFloat(station.latitude) || 0;
        const lng = parseFloat(station.longitude) || 0;
        
        row.innerHTML = `
            <td>${stationName}</td>
            <td>${lat.toFixed(6)}</td>
            <td>${lng.toFixed(6)}</td>
            <td>${elevationValue}</td>
            <td>${orderValue}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="directAddToSelected('${station.station_id || ''}', '${stationName.replace(/'/g, "\\'")}', '${gcpType}')">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Apply filters with improved error handling
async function applyFilters() {
    try {
        // Ensure allStations is always an array
        window.allStations = window.allStations || [];
        
        // Get current filter values with error handling
        const typeRadio = document.querySelector('input[name="gcpType"]:checked');
        const type = typeRadio ? typeRadio.value : 'vertical';
        
        const orderFilter = document.getElementById('orderFilter');
        const order = orderFilter ? orderFilter.value : '';
        
        const regionFilter = document.getElementById('region');
        const region = regionFilter ? regionFilter.value : '';
        
        const provinceFilter = document.getElementById('province');
        const province = provinceFilter ? provinceFilter.value : '';
        
        const cityFilter = document.getElementById('city');
        const city = cityFilter ? cityFilter.value : '';
        
        const barangayFilter = document.getElementById('barangay');
        const barangay = barangayFilter ? barangayFilter.value : '';

        // Start with all stations and apply filters
        let filteredStations = [...window.allStations];
        
        // Apply order filter if specified
        if (order) {
            filteredStations = filteredStations.filter(station => {
                if (!station) return false;
                const stationOrder = station.order || station.elevation_order || station.horizontal_order || '';
                return stationOrder === order;
            });
        }

        // Apply region filter if specified
        if (region) {
            filteredStations = filteredStations.filter(station => 
                station && station.region === region
            );
        }

        // Apply province filter if specified
        if (province) {
            filteredStations = filteredStations.filter(station => 
                station && station.province === province
            );
        }

        // Apply city filter if specified
        if (city) {
            filteredStations = filteredStations.filter(station => 
                station && station.city === city
            );
        }

        // Apply barangay filter if specified
        if (barangay) {
            filteredStations = filteredStations.filter(station => 
                station && station.barangay === barangay
            );
        }

        // Update map with filtered stations
        if (typeof updateMap === 'function') {
            try {
                updateMap(filteredStations);
            } catch (mapError) {
                console.error('Error updating map:', mapError);
            }
        }

        // Update search results table with filtered stations
        updateSearchResults(filteredStations);
        
        // Update filters based on filtered data
        try {
            updateFiltersBasedOnData(filteredStations);
        } catch (filterError) {
            console.error('Error updating filters:', filterError);
        }
    } catch (error) {
        console.error('Error in applyFilters:', error);
        showError('Failed to apply filters: ' + error.message);
    }
}

// Update filters based on available data
async function updateFiltersBasedOnData(stations) {
    try {
        // Ensure allStations is always an array
        window.allStations = window.allStations || [];
        
        // Get unique orders with fallback
        const uniqueOrders = [...new Set(window.allStations
            .filter(station => station) // Filter out null/undefined
            .map(station => station.order || station.elevation_order || station.horizontal_order)
            .filter(order => order))]; // Filter out null/undefined/empty
        
        // Update order dropdown while preserving current selection
        const orderFilter = document.getElementById('orderFilter');
        if (orderFilter) {
            const currentOrder = orderFilter.value;
            orderFilter.innerHTML = '<option value="">Select Order</option>';
            uniqueOrders.sort().forEach(order => {
                orderFilter.innerHTML += `<option value="${order}" ${order === currentOrder ? 'selected' : ''}>${order}</option>`;
            });
        }

        // Get unique location values
        const uniqueRegions = [...new Set(window.allStations
            .filter(station => station && station.region)
            .map(station => station.region))];
        
        const uniqueProvinces = [...new Set(window.allStations
            .filter(station => station && station.province)
            .map(station => station.province))];
        
        const uniqueCities = [...new Set(window.allStations
            .filter(station => station && station.city)
            .map(station => station.city))];
        
        const uniqueBarangays = [...new Set(window.allStations
            .filter(station => station && station.barangay)
            .map(station => station.barangay))];

        // Get current selections with fallbacks
        const regionFilter = document.getElementById('region');
        const currentRegion = regionFilter ? regionFilter.value : '';
        
        const provinceFilter = document.getElementById('province');
        const currentProvince = provinceFilter ? provinceFilter.value : '';
        
        const cityFilter = document.getElementById('city');
        const currentCity = cityFilter ? cityFilter.value : '';
        
        const barangayFilter = document.getElementById('barangay');
        const currentBarangay = barangayFilter ? barangayFilter.value : '';

        // Update region dropdown
        if (regionFilter) {
            regionFilter.innerHTML = '<option value="">Select Region</option>';
            uniqueRegions.sort().forEach(region => {
                regionFilter.innerHTML += `<option value="${region}" ${region === currentRegion ? 'selected' : ''}>${region}</option>`;
            });
        }

        // Update province dropdown
        if (provinceFilter) {
            provinceFilter.innerHTML = '<option value="">Select Province</option>';
            let filteredProvinces = uniqueProvinces;
            if (currentRegion) {
                filteredProvinces = [...new Set(window.allStations
                    .filter(station => station && station.region === currentRegion && station.province)
                    .map(station => station.province))];
            }
            filteredProvinces.sort().forEach(province => {
                provinceFilter.innerHTML += `<option value="${province}" ${province === currentProvince ? 'selected' : ''}>${province}</option>`;
            });
        }

        // Update city dropdown
        if (cityFilter) {
            cityFilter.innerHTML = '<option value="">Select City/Municipality</option>';
            if (currentProvince) {
                const filteredCities = [...new Set(window.allStations
                    .filter(station => station && station.province === currentProvince && station.city)
                    .map(station => station.city))];
                filteredCities.sort().forEach(city => {
                    cityFilter.innerHTML += `<option value="${city}" ${city === currentCity ? 'selected' : ''}>${city}</option>`;
                });
            }
        }

        // Update barangay dropdown
        if (barangayFilter) {
            barangayFilter.innerHTML = '<option value="">Select Barangay</option>';
            if (currentCity) {
                const filteredBarangays = [...new Set(window.allStations
                    .filter(station => station && station.city === currentCity && station.barangay)
                    .map(station => station.barangay))];
                filteredBarangays.sort().forEach(barangay => {
                    barangayFilter.innerHTML += `<option value="${barangay}" ${barangay === currentBarangay ? 'selected' : ''}>${barangay}</option>`;
                });
            }
        }

    } catch (error) {
        console.error('Error in updateFiltersBasedOnData:', error);
        showError('Failed to update filter options: ' + error.message);
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
                <button class="btn btn-add-to-cart" onclick="directAddToSelected('${point.stationId || point.station_id}', '${point.stationName || point.station_name}', '${document.querySelector('input[name="gcpType"]:checked').value}')">
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
    filterTableAndMap,
    // New pagination exports
    paginateStations,
    renderPagination,
    handlePaginationClick,
    updateTableWithStations,
    // Expose current page variable for search.js
    currentPage
}; 