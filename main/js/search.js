// js/search.js
import { fetchStations } from './stations.js';
import { logError, showLoading, hideLoading } from './utils.js';

let locationsData = {};

/**
 * Fetches the location hierarchy from the API.
 */
async function fetchLocations() {
    try {
        console.log('Fetching locations from API...'); // Debugging line
        const gcpType = document.querySelector('input[name="gcpType"]:checked').value;
        const response = await fetch(`api.php?path=api/locations&view=tree&type=${gcpType}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('API Response:', data); // Debugging line
        if (data.status === 'success') {
            locationsData = data.data;
            console.log('Locations data stored:', locationsData); // Debugging line
        } else {
            throw new Error(data.message || 'Failed to fetch locations');
        }
    } catch (error) {
        logError('fetchLocations', error);
        alert('Failed to load location data. Please try again later.');
    }
}

/**
 * Populates a dropdown with options.
 * @param {HTMLElement} element - The select element to populate.
 * @param {Array<string>} options - The options to add.
 * @param {string} defaultOptionText - The text for the default option.
 */
function populateDropdown(element, options, defaultOptionText) {
    element.innerHTML = `<option value="">${defaultOptionText}</option>`;
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option;
        optionElement.textContent = option;
        element.appendChild(optionElement);
    });
}

/**
 * Populates all location dropdowns on initialization.
 */
export async function populateLocationDropdowns() {
    await fetchLocations();
    const regionSelect = document.getElementById('region');
    if (regionSelect) {
        const regions = Object.keys(locationsData).sort();
        console.log('Populating regions dropdown with:', regions); // Debugging line
        populateDropdown(regionSelect, regions, 'Select Region');
    } else {
        console.error('Region select element not found.');
    }
}

/**
 * Sets up event listeners for filter dropdowns.
 */
export function setupFilterListeners() {
    const regionSelect = document.getElementById('region');
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    const gcpTypeRadios = document.querySelectorAll('input[name="gcpType"]');

    gcpTypeRadios.forEach(radio => {
        radio.addEventListener('change', async () => {
            console.log('GCP Type changed, re-populating locations...');
            await populateLocationDropdowns();
            // Clear the lower-level dropdowns
            populateDropdown(provinceSelect, [], 'Select Province');
            populateDropdown(citySelect, [], 'Select City/Municipality');
            populateDropdown(barangaySelect, [], 'Select Barangay');
        });
    });

    regionSelect.addEventListener('change', () => {
        const selectedRegion = regionSelect.value;
        const provinces = selectedRegion ? Object.keys(locationsData[selectedRegion]).sort() : [];
        populateDropdown(provinceSelect, provinces, 'Select Province');
        populateDropdown(citySelect, [], 'Select City/Municipality');
        populateDropdown(barangaySelect, [], 'Select Barangay');
    });

    provinceSelect.addEventListener('change', () => {
        const selectedRegion = regionSelect.value;
        const selectedProvince = provinceSelect.value;
        const cities = (selectedRegion && selectedProvince && locationsData[selectedRegion] && locationsData[selectedRegion][selectedProvince]) ? Object.keys(locationsData[selectedRegion][selectedProvince]).sort() : [];
        populateDropdown(citySelect, cities, 'Select City/Municipality');
        populateDropdown(barangaySelect, [], 'Select Barangay');
    });

    citySelect.addEventListener('change', () => {
        const selectedRegion = regionSelect.value;
        const selectedProvince = provinceSelect.value;
        const selectedCity = citySelect.value;
        const barangays = (selectedRegion && selectedProvince && selectedCity && locationsData[selectedRegion] && locationsData[selectedRegion][selectedProvince] && locationsData[selectedRegion][selectedProvince][selectedCity]) ? locationsData[selectedRegion][selectedProvince][selectedCity].sort() : [];
        populateDropdown(barangaySelect, barangays, 'Select Barangay');
    });
}

/**
 * Handles the search functionality.
 * @param {L.Map} map - The Leaflet map instance.
 */
export async function handleSearch(map) {
    const gcpType = document.querySelector('input[name="gcpType"]:checked').value;
    const region = document.getElementById('region').value;
    const province = document.getElementById('province').value;
    const city = document.getElementById('city').value;
    const barangay = document.getElementById('barangay').value;
    const order = document.getElementById('orderFilter').value;
    const stationName = document.getElementById('stationNameSearch').value;

    if (!region || !province) {
        alert('Please select a region and province before searching.');
                return;
            }
            
    const filters = {
        type: gcpType,
        region: region,
        province: province,
        city: city,
        barangay: barangay,
        order: order,
        search: stationName
    };

    showLoading();
    try {
        await fetchStations(map, filters);
            } catch (error) {
        logError('handleSearch', error);
        alert('An error occurred while searching for stations.');
            } finally {
        hideLoading();
    }
}

/**
 * Handles clearing the search filters.
 */
export function handleClear() {
    document.getElementById('region').value = '';
    document.getElementById('province').value = '';
    document.getElementById('city').value = '';
    document.getElementById('barangay').value = '';
    document.getElementById('orderFilter').value = '';
    document.getElementById('stationNameSearch').value = '';

    // Reset dropdowns to their initial state
    populateLocationDropdowns();

    // Optionally, clear the map and results table
    // This depends on the desired UX
} 