// Admin Station Table Module

import { state, ITEMS_PER_PAGE, TYPE_COLUMN_LABELS } from './config.js';
import { showError } from './ui-utils.js';
import { viewStation, editStation, confirmDeleteStation } from './station-view.js';

/**
 * Updates the stations table with filtered data
 * @param {Array} stations - Stations to display
 */
export function updateStationsTable(stations) {
    state.currentStations = stations || [];
    
    // Apply any active filters
    const filteredStations = applyFilters();
    
    // Update pagination
    updatePagination(filteredStations);
    
    // Get current page items
    const start = (state.currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const currentPageStations = filteredStations.slice(start, end);
    
    // Update table
    const tableBody = document.getElementById('stationsList');
    tableBody.innerHTML = '';
    
    if (currentPageStations.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="6" class="text-center">No stations found</td>`;
        tableBody.appendChild(row);
        return;
    }
    
    // Determine which field to use for value column based on type
    let valueField;
    
    if (state.currentStationType === 'vertical') {
        valueField = 'elevation';
    } else if (state.currentStationType === 'horizontal') {
        valueField = 'ellipsoidal_height';
    } else if (state.currentStationType === 'gravity') {
        valueField = 'gravity_value';
    }
    
    // Create table rows
    currentPageStations.forEach(station => {
        const row = document.createElement('tr');
        
        // Format the station name with ID
        const stationName = station.station_name || 'Unnamed Station';
        const stationId = station.station_id || '';
        const stationCode = station.station_code ? ` (${station.station_code})` : '';
        
        // Format coordinates for display - ensure values are numeric
        const lat = station.latitude ? parseFloat(station.latitude).toFixed(6) + '°' : 'N/A';
        const lng = station.longitude ? parseFloat(station.longitude).toFixed(6) + '°' : 'N/A';
        
        // Format value field based on type
        let valueDisplay = 'N/A';
        if (station[valueField] !== undefined && station[valueField] !== null) {
            valueDisplay = parseFloat(station[valueField]).toFixed(3);
        }
        
        row.innerHTML = `
            <td title="${stationId}">${stationName}${stationCode}</td>
            <td>${lat}</td>
            <td>${lng}</td>
            <td>${valueDisplay}</td>
            <td>${station.order || 'N/A'}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-info view-btn" data-station-id="${stationId}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary edit-btn" data-station-id="${stationId}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-btn" data-station-id="${stationId}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        
        // Add event listeners to action buttons
        const viewBtn = row.querySelector('.view-btn');
        viewBtn.addEventListener('click', () => viewStation(stationId));
        
        const editBtn = row.querySelector('.edit-btn');
        editBtn.addEventListener('click', () => editStation(stationId));
        
        const deleteBtn = row.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', () => confirmDeleteStation(stationId));
        
        tableBody.appendChild(row);
    });
}

/**
 * Updates pagination controls
 * @param {Array} filteredStations - Filtered stations for calculation
 */
export function updatePagination(filteredStations) {
    const paginationElement = document.getElementById('pagination');
    paginationElement.innerHTML = '';
    
    const totalItems = filteredStations ? filteredStations.length : 0;
    state.totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
    
    // Ensure current page is within bounds
    if (state.currentPage > state.totalPages) {
        state.currentPage = 1;
    }
    
    // Create previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${state.currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (state.currentPage > 1) {
            state.currentPage--;
            updateStationsTable(state.currentStations);
        }
    });
    paginationElement.appendChild(prevLi);
    
    // Create page number buttons
    // Limit number of shown pages if there are too many
    const maxVisiblePages = 5;
    let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(state.totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust startPage if we're near the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // Add first page if not visible
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#">1</a>`;
        firstLi.addEventListener('click', (e) => {
            e.preventDefault();
            state.currentPage = 1;
            updateStationsTable(state.currentStations);
        });
        paginationElement.appendChild(firstLi);
        
        // Add ellipsis if there's a gap
        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<a class="page-link" href="#">...</a>`;
            paginationElement.appendChild(ellipsisLi);
        }
    }
    
    // Add page numbers
    for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === state.currentPage ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        pageLi.addEventListener('click', (e) => {
            e.preventDefault();
            state.currentPage = i;
            updateStationsTable(state.currentStations);
        });
        paginationElement.appendChild(pageLi);
    }
    
    // Add last page if not visible
    if (endPage < state.totalPages) {
        // Add ellipsis if there's a gap
        if (endPage < state.totalPages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<a class="page-link" href="#">...</a>`;
            paginationElement.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#">${state.totalPages}</a>`;
        lastLi.addEventListener('click', (e) => {
            e.preventDefault();
            state.currentPage = state.totalPages;
            updateStationsTable(state.currentStations);
        });
        paginationElement.appendChild(lastLi);
    }
    
    // Create next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${state.currentPage === state.totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (state.currentPage < state.totalPages) {
            state.currentPage++;
            updateStationsTable(state.currentStations);
        }
    });
    paginationElement.appendChild(nextLi);
}

/**
 * Applies all active filters to the stations list
 * @returns {Array} Filtered stations
 */
export function applyFilters() {
    const stations = state.currentStations || [];
    
    if (!stations.length) {
        return [];
    }
    
    let filtered = [...stations];
    
    // Filter by station name search
    const nameSearch = document.getElementById('stationNameSearch').value.toLowerCase().trim();
    if (nameSearch) {
        filtered = filtered.filter(station => {
            const name = (station.station_name || '').toLowerCase();
            const code = (station.station_code || '').toLowerCase();
            return name.includes(nameSearch) || code.includes(nameSearch);
        });
    }
    
    // Filter by order
    const orderFilter = document.getElementById('orderFilter').value;
    if (orderFilter) {
        filtered = filtered.filter(station => {
            return station.order == orderFilter;
        });
    }
    
    // Filter by accuracy class (for vertical stations)
    if (state.currentStationType === 'vertical') {
        const accuracyFilter = document.getElementById('accuracyFilter').value;
        if (accuracyFilter) {
            filtered = filtered.filter(station => {
                return station.accuracy_class === accuracyFilter;
            });
        }
    }
    
    // Filter by location (region, province, city, barangay)
    const region = document.getElementById('region').value;
    if (region) {
        filtered = filtered.filter(station => {
            return station.region === region;
        });
    }
    
    const province = document.getElementById('province').value;
    if (province) {
        filtered = filtered.filter(station => {
            return station.province === province;
        });
    }
    
    const city = document.getElementById('city').value;
    if (city) {
        filtered = filtered.filter(station => {
            return station.city === city;
        });
    }
    
    const barangay = document.getElementById('barangay').value;
    if (barangay) {
        filtered = filtered.filter(station => {
            return station.barangay === barangay;
        });
    }
    
    return filtered;
}

/**
 * Resets all filter controls and applies no filters
 */
export function resetFilters() {
    // Reset filter inputs
    document.getElementById('stationNameSearch').value = '';
    document.getElementById('orderFilter').value = '';
    
    if (document.getElementById('accuracyFilter')) {
        document.getElementById('accuracyFilter').value = '';
    }
    
    document.getElementById('region').value = '';
    document.getElementById('province').value = '';
    document.getElementById('city').value = '';
    document.getElementById('barangay').value = '';
    
    // Update the stations table
    updateStationsTable(state.currentStations);
}

/**
 * Updates filter dropdown options based on available data
 * @param {Array} stations - Stations data to extract filter options from
 */
export function updateFiltersBasedOnData(stations) {
    if (!stations || !stations.length) {
        return;
    }
    
    // Extract unique values for each filter
    const orders = [...new Set(stations.map(station => station.order).filter(Boolean))];
    orders.sort((a, b) => a - b);
    
    // For vertical stations, get accuracy classes
    if (state.currentStationType === 'vertical') {
        const accuracyClasses = [...new Set(stations.map(station => station.accuracy_class).filter(Boolean))];
        accuracyClasses.sort();
        
        const accuracyFilter = document.getElementById('accuracyFilter');
        populateFilterDropdown('accuracyFilter', accuracyClasses);
        
        // Show accuracy class filter
        const container = document.getElementById('accuracyClassContainer');
        if (container) {
            container.style.display = '';
        }
    } else {
        // Hide accuracy class filter for non-vertical types
        const container = document.getElementById('accuracyClassContainer');
        if (container) {
            container.style.display = 'none';
        }
    }
    
    // Fill order filter dropdown
    populateFilterDropdown('orderFilter', orders);
    
    // Extract location data from stations
    const regions = [...new Set(stations.map(station => station.region).filter(Boolean))];
    regions.sort();
    populateFilterDropdown('region', regions);
    
    const provinces = [...new Set(stations.map(station => station.province).filter(Boolean))];
    provinces.sort();
    populateFilterDropdown('province', provinces);
    
    const cities = [...new Set(stations.map(station => station.city).filter(Boolean))];
    cities.sort();
    populateFilterDropdown('city', cities);
    
    const barangays = [...new Set(stations.map(station => station.barangay).filter(Boolean))];
    barangays.sort();
    populateFilterDropdown('barangay', barangays);
}

/**
 * Populates a filter dropdown with options
 * @param {string} id - ID of dropdown element
 * @param {Array} values - Values to add as options
 */
export function populateFilterDropdown(id, values) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    
    // Get current value
    const currentValue = dropdown.value;
    
    // Clear options except the first one (placeholder)
    while (dropdown.options.length > 1) {
        dropdown.remove(1);
    }
    
    // Add new options
    values.forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        dropdown.appendChild(option);
    });
    
    // Restore current value if it exists in the new options
    dropdown.value = currentValue;
    
    // If the current value is no longer in the options, reset to empty
    if (dropdown.value !== currentValue) {
        dropdown.value = '';
    }
} 