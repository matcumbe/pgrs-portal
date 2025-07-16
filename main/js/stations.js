// js/stations.js
import { updateMapMarkers } from './map.js';
import { logError, showLoading, hideLoading } from './utils.js';

let currentPage = 1;
const aPage = 10;
let currentFilters = {};
let allStations = [];

/**
 * Initializes station-related functionalities.
 * @param {L.Map} map - The Leaflet map instance.
 */
export function initializeStations(map) {
    // This function can be expanded to set up other station-related event listeners if needed.
    // For now, it mainly serves as an entry point.
    console.log('Stations module initialized.');
}

/**
 * Fetches stations from the API based on the provided filters.
 * @param {L.Map} map - The Leaflet map instance.
 * @param {object} filters - The filters to apply to the station search.
 */
export async function fetchStations(map, filters = {}) {
    currentFilters = filters;
        currentPage = 1;
    showLoading();
    try {
        // Clear existing markers before fetching new stations
        updateMapMarkers([], map);

        const params = new URLSearchParams({
            ...filters,
            page: currentPage,
            limit: 1000 // Adjust as needed, or implement full pagination
        });
        
        const response = await fetch(`/api.php?path=api/search/stations&${params.toString()}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (data.status === 'success' && data.data.stations) {
            allStations = data.data.stations;
            updateStationsDisplay(map);
        } else {
            throw new Error(data.message || 'Failed to fetch stations');
        }
    } catch (error) {
        logError('fetchStations', error);
        alert('Failed to fetch station data. Please check the console for more details.');
        allStations = [];
        updateStationsDisplay(map);
    } finally {
        hideLoading();
    }
}

/**
 * Updates the station table and map with the current set of stations.
 * @param {L.Map} map - The Leaflet map instance.
 */
function updateStationsDisplay(map) {
    const searchResultsBody = document.getElementById('searchResults');
    const stationsPagination = document.getElementById('stationsPagination');
    
    searchResultsBody.innerHTML = '';
    stationsPagination.innerHTML = '';

    if (!Array.isArray(allStations) || allStations.length === 0) {
        searchResultsBody.innerHTML = '<tr><td colspan="6" class="text-center">No stations found.</td></tr>';
        updateMapMarkers([], map);
            return;
        }
    
    // Determine current GCP type
    const gcpType = document.querySelector('input[name="gcpType"]:checked')?.value || 'horizontal';
    const paginatedStations = allStations.slice((currentPage - 1) * aPage, currentPage * aPage);

    paginatedStations.forEach(station => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${station.station_name}</td>
            <td>${Number(station.latitude).toFixed(3)}</td>
            <td>${Number(station.longitude).toFixed(3)}</td>
            <td>${station.order_of_accuracy_wgs84 || 'N/A'}</td>
            <td>${station.project || 'N/A'}</td>
            <td>${station.epoch === '0.0' || station.epoch === 'N/A' ? '' : station.epoch}</td>
            <td>
                <button class="btn btn-sm btn-primary btn-view-description" data-station-name="${station.station_name.replace(/'/g, "\\'")}">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </button>
                <button class="btn btn-sm btn-primary" onclick="promptStationTypeAndAddToCart('${station.id || ''}', '${station.station_name.replace(/'/g, "\\'")}', '${gcpType}')">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                </button>
            </td>
        `;
        searchResultsBody.appendChild(row);
    });

    updateMapMarkers(paginatedStations, map);
    setupPagination(map, allStations.length);
}

/**
 * Sets up pagination controls for the station list.
 * @param {L.Map} map - The Leaflet map instance.
 * @param {number} totalStations - The total number of stations.
 */
function setupPagination(map, totalStations) {
    const stationsPagination = document.getElementById('stationsPagination');
    const totalPages = Math.ceil(totalStations / aPage);

    if (totalPages <= 1) {
        stationsPagination.innerHTML = '';
        return;
    }

    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
    prevLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            updateStationsDisplay(map);
        }
    });
    stationsPagination.appendChild(prevLi);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        pageLi.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = i;
            updateStationsDisplay(map);
        });
        stationsPagination.appendChild(pageLi);
    }

    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
    nextLi.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            updateStationsDisplay(map);
        }
    });
    stationsPagination.appendChild(nextLi);
}