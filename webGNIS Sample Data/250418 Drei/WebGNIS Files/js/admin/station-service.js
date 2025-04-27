// Admin Station Service Module

import { state } from './config.js';
import { 
    fetchStationsByType as apiGetStations, 
    fetchRegions, 
    fetchProvinces, 
    fetchCities, 
    fetchBarangays 
} from './api-client.js';
import { toggleLoading, showError } from './ui-utils.js';
import { updateStationsTable, updateFiltersBasedOnData } from './station-table.js';
import { populateDropdown } from './dropdown-utils.js';

/**
 * Fetches stations based on type and updates the UI
 * @param {string} type - Type of stations to fetch (vertical, horizontal, gravity)
 */
export async function fetchStationsByType(type) {
    try {
        toggleLoading(true);
        
        const stations = await apiGetStations(type);
        
        // Update the state
        state.currentStations = stations;
        state.currentStationType = type;
        
        // Update the stations table
        updateStationsTable(stations);
        
        // Update filter options based on the data
        updateFiltersBasedOnData(stations);
        
        return stations;
    } catch (error) {
        showError('Failed to fetch stations: ' + error.message);
        return [];
    } finally {
        toggleLoading(false);
    }
}

/**
 * Fetches location data for drop-downs
 */
export async function fetchLocationData() {
    try {
        // Fetch regions
        const regions = await fetchRegions();
        state.allRegions = regions;
        
        // Populate dropdowns for form and filters
        populateLocationDropdowns();
        
    } catch (error) {
        showError('Failed to fetch location data: ' + error.message);
    }
}

/**
 * Populates location dropdowns with available data
 */
export function populateLocationDropdowns() {
    // Populate region dropdowns (both filter and form)
    populateDropdown('region', state.allRegions, 'name', 'name');
    populateDropdown('regionInput', state.allRegions, 'name', 'name');
}

/**
 * Sets up the location dropdown cascade for the form
 */
export function setupLocationCascade() {
    // Add cascade functionality to location filter dropdowns in the form
    document.getElementById('regionInput').addEventListener('change', function() {
        const region = this.value;
        populateProvinceDropdown(region, 'Input');
    });
    
    document.getElementById('provinceInput').addEventListener('change', function() {
        const province = this.value;
        populateCityDropdown(province, 'Input');
    });
    
    document.getElementById('cityInput').addEventListener('change', function() {
        const city = this.value;
        populateBarangayDropdown(city, 'Input');
    });
    
    // Also set up the cascade for the filter panel
    document.getElementById('region').addEventListener('change', function() {
        const region = this.value;
        populateProvinceDropdown(region);
    });
    
    document.getElementById('province').addEventListener('change', function() {
        const province = this.value;
        populateCityDropdown(province);
    });
    
    document.getElementById('city').addEventListener('change', function() {
        const city = this.value;
        populateBarangayDropdown(city);
    });
}

/**
 * Populates the province dropdown based on selected region
 * @param {string} region - Selected region
 * @param {string} suffix - Suffix for the dropdown ID (e.g., 'Input' for the form)
 */
async function populateProvinceDropdown(region, suffix = '') {
    if (!region) {
        // Clear the dropdown if no region is selected
        populateDropdown(`province${suffix}`, []);
        populateDropdown(`city${suffix}`, []);
        populateDropdown(`barangay${suffix}`, []);
        return;
    }
    
    try {
        const provinces = await fetchProvinces(region);
        state.allProvinces = provinces;
        
        // Populate the dropdown
        populateDropdown(`province${suffix}`, provinces, 'name', 'name');
        
        // Clear child dropdowns
        populateDropdown(`city${suffix}`, []);
        populateDropdown(`barangay${suffix}`, []);
    } catch (error) {
        showError('Failed to fetch provinces: ' + error.message);
    }
}

/**
 * Populates the city dropdown based on selected province
 * @param {string} province - Selected province
 * @param {string} suffix - Suffix for the dropdown ID (e.g., 'Input' for the form)
 */
async function populateCityDropdown(province, suffix = '') {
    if (!province) {
        // Clear the dropdown if no province is selected
        populateDropdown(`city${suffix}`, []);
        populateDropdown(`barangay${suffix}`, []);
        return;
    }
    
    try {
        const cities = await fetchCities(province);
        state.allCities = cities;
        
        // Populate the dropdown
        populateDropdown(`city${suffix}`, cities, 'name', 'name');
        
        // Clear child dropdown
        populateDropdown(`barangay${suffix}`, []);
    } catch (error) {
        showError('Failed to fetch cities: ' + error.message);
    }
}

/**
 * Populates the barangay dropdown based on selected city
 * @param {string} city - Selected city
 * @param {string} suffix - Suffix for the dropdown ID (e.g., 'Input' for the form)
 */
async function populateBarangayDropdown(city, suffix = '') {
    if (!city) {
        // Clear the dropdown if no city is selected
        populateDropdown(`barangay${suffix}`, []);
        return;
    }
    
    try {
        const barangays = await fetchBarangays(city);
        state.allBarangays = barangays;
        
        // Populate the dropdown
        populateDropdown(`barangay${suffix}`, barangays, 'name', 'name');
    } catch (error) {
        showError('Failed to fetch barangays: ' + error.message);
    }
} 