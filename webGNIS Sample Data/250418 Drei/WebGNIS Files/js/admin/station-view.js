// Admin Station View Module

import { state, STATION_TYPES } from './config.js';
import { fetchStationById, deleteStation as apiDeleteStation } from './api-client.js';
import { showError, showSuccess } from './ui-utils.js';
import { editStation as showEditForm } from './station-form.js';
import { fetchStationsByType } from './station-service.js';

// Keep track of the current station being viewed or deleted
let selectedStationId = null;

/**
 * Shows detailed view of a station
 * @param {string} stationId - ID of station to view
 */
export async function viewStation(stationId) {
    try {
        const station = await fetchStationById(stationId);
        
        if (!station) {
            showError('Station not found');
            return;
        }
        
        selectedStationId = stationId;
        
        // Format station details for display
        let detailsHtml = `
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">${station.station_name || 'Unnamed Station'}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Station ID</dt>
                        <dd class="col-sm-8">${station.station_id || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Station Code</dt>
                        <dd class="col-sm-8">${station.station_code || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8">${capitalizeFirstLetter(station.type) || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Coordinates</dt>
                        <dd class="col-sm-8">
                            ${station.latitude ? parseFloat(station.latitude).toFixed(6) + '° N, ' : 'N/A, '}
                            ${station.longitude ? parseFloat(station.longitude).toFixed(6) + '° E' : 'N/A'}
                        </dd>
                        
                        <dt class="col-sm-4">Order</dt>
                        <dd class="col-sm-8">${station.order || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Mark Type</dt>
                        <dd class="col-sm-8">${station.mark_type || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Mark Status</dt>
                        <dd class="col-sm-8">${station.mark_status || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Mark Construction</dt>
                        <dd class="col-sm-8">${station.mark_construction || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Establishing Authority</dt>
                        <dd class="col-sm-8">${station.authority || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Date Established</dt>
                        <dd class="col-sm-8">${formatDate(station.date_established) || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Last Updated</dt>
                        <dd class="col-sm-8">${formatDate(station.date_last_updated) || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Encoder</dt>
                        <dd class="col-sm-8">${station.encoder || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Island Group</dt>
                        <dd class="col-sm-8">${station.island_group || 'N/A'}</dd>
                    </dl>
                </div>
            </div>
        `;
        
        // Type-specific details
        if (station.type === STATION_TYPES.VERTICAL) {
            detailsHtml += `
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Vertical GCP Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Elevation</dt>
                            <dd class="col-sm-8">${station.elevation ? parseFloat(station.elevation).toFixed(3) + ' m' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">BM Plus</dt>
                            <dd class="col-sm-8">${station.bm_plus ? parseFloat(station.bm_plus).toFixed(3) + ' m' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Accuracy Class</dt>
                            <dd class="col-sm-8">${station.accuracy_class || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Elevation Order</dt>
                            <dd class="col-sm-8">${station.elevation_order || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Vertical Datum</dt>
                            <dd class="col-sm-8">${station.vertical_datum || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Elevation Authority</dt>
                            <dd class="col-sm-8">${station.elevation_authority || 'N/A'}</dd>
                        </dl>
                    </div>
                </div>
            `;
        } 
        else if (station.type === STATION_TYPES.HORIZONTAL) {
            detailsHtml += `
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Horizontal GCP Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Ellipsoidal Height</dt>
                            <dd class="col-sm-8">${station.ellipsoidal_height ? parseFloat(station.ellipsoidal_height).toFixed(3) + ' m' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Horizontal Order</dt>
                            <dd class="col-sm-8">${station.horizontal_order || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Horizontal Datum</dt>
                            <dd class="col-sm-8">${station.horizontal_datum || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">UTM Coordinates</dt>
                            <dd class="col-sm-8">
                                N: ${station.northing_value ? parseFloat(station.northing_value).toFixed(3) : 'N/A'}, 
                                E: ${station.easting_value ? parseFloat(station.easting_value).toFixed(3) : 'N/A'}
                                ${station.utm_zone ? ' (Zone ' + station.utm_zone + ')' : ''}
                            </dd>
                            
                            <dt class="col-sm-4">ITRF Coordinates</dt>
                            <dd class="col-sm-8">
                                ${station.itrf_latitude ? parseFloat(station.itrf_latitude).toFixed(6) + '° N, ' : 'N/A, '}
                                ${station.itrf_longitude ? parseFloat(station.itrf_longitude).toFixed(6) + '° E' : 'N/A'}
                            </dd>
                            
                            <dt class="col-sm-4">ITRF Ellip. Height</dt>
                            <dd class="col-sm-8">${station.itrf_ellipsoidal_height ? parseFloat(station.itrf_ellipsoidal_height).toFixed(3) + ' m' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">ITRF Error</dt>
                            <dd class="col-sm-8">${station.itrf_error ? parseFloat(station.itrf_error).toFixed(3) + ' m' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">ITRF Height Error</dt>
                            <dd class="col-sm-8">${station.itrf_height_error ? parseFloat(station.itrf_height_error).toFixed(3) + ' m' : 'N/A'}</dd>
                        </dl>
                    </div>
                </div>
            `;
        } 
        else if (station.type === STATION_TYPES.GRAVITY) {
            detailsHtml += `
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Gravity GCP Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Gravity Value</dt>
                            <dd class="col-sm-8">${station.gravity_value ? parseFloat(station.gravity_value).toFixed(3) + ' mGal' : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Standard Deviation</dt>
                            <dd class="col-sm-8">${station.standard_deviation ? parseFloat(station.standard_deviation).toFixed(4) : 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Gravity Order</dt>
                            <dd class="col-sm-8">${station.gravity_order || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Date Measured</dt>
                            <dd class="col-sm-8">${formatDate(station.date_measured) || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Gravity Meter</dt>
                            <dd class="col-sm-8">${station.gravity_meter || 'N/A'}</dd>
                            
                            <dt class="col-sm-4">Gravity Datum</dt>
                            <dd class="col-sm-8">${station.gravity_datum || 'N/A'}</dd>
                        </dl>
                    </div>
                </div>
            `;
        }
        
        // Location details
        detailsHtml += `
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Location Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Region</dt>
                        <dd class="col-sm-8">${station.region || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Province</dt>
                        <dd class="col-sm-8">${station.province || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">City/Municipality</dt>
                        <dd class="col-sm-8">${station.city || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Barangay</dt>
                        <dd class="col-sm-8">${station.barangay || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Site Description</dt>
                        <dd class="col-sm-8">${station.site_description || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Access Instructions</dt>
                        <dd class="col-sm-8">${station.access_instructions || 'N/A'}</dd>
                        
                        <dt class="col-sm-4">Active</dt>
                        <dd class="col-sm-8">${station.is_active !== false ? 'Yes' : 'No'}</dd>
                    </dl>
                </div>
            </div>
        `;
        
        // Update the details container
        document.getElementById('stationDetails').innerHTML = detailsHtml;
        
        // Show the view panel, hide other panels
        document.getElementById('viewPanel').classList.remove('hidden');
        document.getElementById('stationForm').classList.add('hidden');
        document.getElementById('deleteConfirmPanel').classList.add('hidden');
        document.getElementById('welcomePanel').classList.add('hidden');
        
        // Set up button handlers
        document.getElementById('editBtn').onclick = () => {
            editStation(stationId);
        };
        
        document.getElementById('deleteBtn').onclick = () => {
            confirmDeleteStation(stationId);
        };
        
        document.getElementById('cancelViewBtn').onclick = () => {
            hideViewPanel();
        };
        
    } catch (error) {
        showError('Error loading station details: ' + error.message);
    }
}

/**
 * Hides the station view panel
 */
export function hideViewPanel() {
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.remove('hidden');
    selectedStationId = null;
}

/**
 * Shows delete confirmation dialog for a station
 * @param {string} stationId - ID of station to delete
 */
export function confirmDeleteStation(stationId) {
    selectedStationId = stationId;
    
    // Show delete confirmation panel, hide other panels
    document.getElementById('deleteConfirmPanel').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('stationForm').classList.add('hidden');
    document.getElementById('welcomePanel').classList.add('hidden');
    
    // Set up confirmation button handlers
    document.getElementById('confirmDeleteBtn').onclick = deleteStation;
    document.getElementById('cancelDeleteBtn').onclick = hideDeleteConfirmation;
}

/**
 * Shows delete confirmation dialog
 */
export function showDeleteConfirmation() {
    document.getElementById('deleteConfirmPanel').classList.remove('hidden');
}

/**
 * Hides delete confirmation dialog
 */
export function hideDeleteConfirmation() {
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.remove('hidden');
}

/**
 * Deletes the current station
 */
export async function deleteStation() {
    if (!selectedStationId) {
        showError('No station selected for deletion');
        return;
    }
    
    try {
        await apiDeleteStation(selectedStationId);
        showSuccess('Station deleted successfully');
        
        // Refresh stations list
        await fetchStationsByType(state.currentStationType);
        
        // Hide panels
        hideDeleteConfirmation();
        
    } catch (error) {
        showError('Error deleting station: ' + error.message);
    }
}

/**
 * Wrapper function for editing a station that other modules can call
 * @param {string} stationId - ID of station to edit
 */
export function editStation(stationId) {
    showEditForm(stationId);
}

/**
 * Formats a date for display
 * @param {string} dateString - Date string to format
 * @returns {string} Formatted date string
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(undefined, options);
}

/**
 * Capitalizes the first letter of a string
 * @param {string} string - String to capitalize
 * @returns {string} Capitalized string
 */
function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
} 