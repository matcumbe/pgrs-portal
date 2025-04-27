// Admin Station Form Module

import { state, STATION_TYPES } from './config.js';
import { createStation, updateStation, fetchStationById } from './api-client.js';
import { decimalToDMS, formatDateForInput, showSuccess, showError, toggleLoading } from './ui-utils.js';
import { fetchStationsByType } from './station-service.js';

/**
 * Shows the form for adding a new station
 */
export function showAddForm() {
    // Reset form
    document.getElementById('stationForm').reset();
    document.getElementById('stationId').value = '';
    
    // Set appropriate form title
    const typeText = state.currentStationType.charAt(0).toUpperCase() + state.currentStationType.slice(1);
    document.getElementById('formTitle').textContent = `Add New ${typeText} GCP Station`;
    
    // Show/hide type-specific fields
    updateFormFieldsVisibility(state.currentStationType);
    
    // Show form, hide other panels
    document.getElementById('stationForm').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.add('hidden');
    
    // Set up coordinate input handlers
    setupDMSConversion();
}

/**
 * Loads a station into the edit form
 * @param {string} stationId - ID of station to edit
 */
export async function editStation(stationId) {
    try {
        const station = await fetchStationById(stationId);
        
        if (station) {
            showEditForm(station);
        } else {
            showError('Station not found');
        }
    } catch (error) {
        showError('Error loading station: ' + error.message);
    }
}

/**
 * Populates the form with station data for editing
 * @param {Object} station - Station data to edit
 */
export function showEditForm(station) {
    // Set station type radio button
    document.querySelector(`input[name="gcpType"][value="${station.type}"]`).checked = true;
    state.currentStationType = station.type;
    
    // Update form fields visibility for this station type
    updateFormFieldsVisibility(station.type);
    
    // Set form title
    const typeText = station.type.charAt(0).toUpperCase() + station.type.slice(1);
    document.getElementById('formTitle').textContent = `Edit ${typeText} GCP Station`;
    
    // Populate the form with station data
    const form = document.getElementById('stationForm');
    
    // Set station ID (hidden field)
    document.getElementById('stationId').value = station.station_id || '';
    
    // Common fields
    document.getElementById('stationName').value = station.station_name || '';
    document.getElementById('stationCode').value = station.station_code || '';
    
    // Coordinates
    if (station.latitude) {
        setDMSFromDecimal('lat', parseFloat(station.latitude));
    }
    
    if (station.longitude) {
        setDMSFromDecimal('lng', parseFloat(station.longitude));
    }
    
    // Common tab fields
    if (station.mark_type) {
        document.getElementById('markType').value = station.mark_type;
    }
    
    if (station.mark_status) {
        document.getElementById('markStatus').value = station.mark_status;
    }
    
    if (station.mark_construction) {
        document.getElementById('markConstruction').value = station.mark_construction;
    }
    
    if (station.authority) {
        document.getElementById('authority').value = station.authority;
    }
    
    if (station.date_established) {
        document.getElementById('dateEstablished').value = formatDateForInput(station.date_established);
    }
    
    if (station.date_last_updated) {
        document.getElementById('dateLastUpdated').value = formatDateForInput(station.date_last_updated);
    }
    
    if (station.encoder) {
        document.getElementById('encoder').value = station.encoder;
    }
    
    if (station.island_group) {
        document.getElementById('islandGroup').value = station.island_group;
    }
    
    if (station.order) {
        document.getElementById('orderInput').value = station.order;
    }
    
    // Type-specific fields
    if (station.type === STATION_TYPES.VERTICAL) {
        if (station.elevation) {
            document.getElementById('elevation').value = station.elevation;
        }
        
        if (station.bm_plus) {
            document.getElementById('bmPlus').value = station.bm_plus;
        }
        
        if (station.accuracy_class) {
            document.getElementById('accuracyClass').value = station.accuracy_class;
        }
        
        if (station.elevation_order) {
            document.getElementById('elevationOrder').value = station.elevation_order;
        }
        
        if (station.vertical_datum) {
            document.getElementById('verticalDatum').value = station.vertical_datum;
        }
        
        if (station.elevation_authority) {
            document.getElementById('elevationAuthority').value = station.elevation_authority;
        }
    } 
    else if (station.type === STATION_TYPES.HORIZONTAL) {
        if (station.ellipsoidal_height) {
            document.getElementById('ellipsoidalHeight').value = station.ellipsoidal_height;
        }
        
        if (station.horizontal_order) {
            document.getElementById('horizontalOrder').value = station.horizontal_order;
        }
        
        if (station.horizontal_datum) {
            document.getElementById('horizontalDatum').value = station.horizontal_datum;
        }
        
        if (station.northing_value) {
            document.getElementById('northingValue').value = station.northing_value;
        }
        
        if (station.easting_value) {
            document.getElementById('eastingValue').value = station.easting_value;
        }
        
        if (station.utm_zone) {
            document.getElementById('utmZone').value = station.utm_zone;
        }
        
        // ITRF Coordinates
        if (station.itrf_latitude) {
            const dms = decimalToDMS(station.itrf_latitude);
            document.getElementById('itrfLatDd').value = dms.degrees;
            document.getElementById('itrfLatMm').value = dms.minutes;
            document.getElementById('itrfLatSs').value = dms.seconds;
        }
        
        if (station.itrf_longitude) {
            const dms = decimalToDMS(station.itrf_longitude);
            document.getElementById('itrfLonDd').value = dms.degrees;
            document.getElementById('itrfLonMm').value = dms.minutes;
            document.getElementById('itrfLonSs').value = dms.seconds;
        }
        
        if (station.itrf_ellipsoidal_height) {
            document.getElementById('itrfEllHgt').value = station.itrf_ellipsoidal_height;
        }
        
        if (station.itrf_error) {
            document.getElementById('itrfEllErr').value = station.itrf_error;
        }
        
        if (station.itrf_height_error) {
            document.getElementById('itrfHgtErr').value = station.itrf_height_error;
        }
    } 
    else if (station.type === STATION_TYPES.GRAVITY) {
        if (station.gravity_value) {
            document.getElementById('gravityValue').value = station.gravity_value;
        }
        
        if (station.standard_deviation) {
            document.getElementById('standardDeviation').value = station.standard_deviation;
        }
        
        if (station.gravity_order) {
            document.getElementById('gravityOrder').value = station.gravity_order;
        }
        
        if (station.date_measured) {
            document.getElementById('dateMeasured').value = formatDateForInput(station.date_measured);
        }
        
        if (station.gravity_meter) {
            document.getElementById('gravityMeter').value = station.gravity_meter;
        }
        
        if (station.gravity_datum) {
            document.getElementById('gravityDatum').value = station.gravity_datum;
        }
    }
    
    // Location tab
    if (station.region) {
        document.getElementById('regionInput').value = station.region;
    }
    
    if (station.province) {
        document.getElementById('provinceInput').value = station.province;
    }
    
    if (station.city) {
        document.getElementById('cityInput').value = station.city;
    }
    
    if (station.barangay) {
        document.getElementById('barangayInput').value = station.barangay;
    }
    
    if (station.site_description) {
        document.getElementById('siteDescription').value = station.site_description;
    }
    
    if (station.access_instructions) {
        document.getElementById('accessInstructions').value = station.access_instructions;
    }
    
    document.getElementById('isActive').checked = station.is_active !== false;
    
    // Show form, hide other panels
    document.getElementById('stationForm').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.add('hidden');
    
    // Set up coordinate input handlers
    setupDMSConversion();
}

/**
 * Hides the station form
 */
export function hideForm() {
    document.getElementById('stationForm').classList.add('hidden');
    document.getElementById('welcomePanel').classList.remove('hidden');
}

/**
 * Handles form submission
 * @param {Event} event - Submit event
 */
export async function handleFormSubmit(event) {
    event.preventDefault();
    
    // Validate form
    if (!validateForm()) {
        showError('Please fill out all required fields.');
        return;
    }
    
    // Validate coordinates
    if (!validateCoordinates()) {
        showError('Invalid coordinates. Please check latitude and longitude values.');
        return;
    }
    
    // Collect form data
    const formData = collectFormData();
    
    try {
        toggleLoading(true);
        
        // Determine if this is an add or edit operation
        const isEdit = formData.station_id && formData.station_id.trim() !== '';
        
        let result;
        if (isEdit) {
            // Update existing station
            result = await updateStation(formData.station_id, formData);
            showSuccess('Station updated successfully');
        } else {
            // Create new station
            result = await createStation(formData);
            showSuccess('Station created successfully');
        }
        
        // Refresh the stations list
        await fetchStationsByType(state.currentStationType);
        
        // Hide the form
        hideForm();
        
    } catch (error) {
        showError('Error saving station: ' + error.message);
    } finally {
        toggleLoading(false);
    }
}

/**
 * Collects data from the form
 * @returns {Object} Station data object
 */
function collectFormData() {
    // Get current form values
    const stationId = document.getElementById('stationId').value.trim();
    const stationType = document.querySelector('input[name="gcpType"]:checked').value;
    const stationName = document.getElementById('stationName').value.trim();
    const stationCode = document.getElementById('stationCode').value.trim();
    
    // Common fields
    const data = {
        type: stationType,
        station_id: stationId || '', // Will be generated by API if empty
        station_name: stationName,
        station_code: stationCode,
        region: document.getElementById('regionInput').value,
        province: document.getElementById('provinceInput').value,
        city: document.getElementById('cityInput').value,
        barangay: document.getElementById('barangayInput').value,
        island_group: document.getElementById('islandGroup').value,
        order: document.getElementById('orderInput').value,
        mark_type: document.getElementById('markType').value,
        mark_status: document.getElementById('markStatus').value,
        mark_const: document.getElementById('markConstruction').value,
        authority: document.getElementById('authority').value,
        encoder: document.getElementById('encoder').value,
        date_established: document.getElementById('dateEstablished').value,
        date_last_updated: document.getElementById('dateLastUpdated').value,
        description: document.getElementById('siteDescription').value,
        access_instructions: document.getElementById('accessInstructions').value,
        latitude: parseFloat(document.getElementById('latitude').value) || null,
        longitude: parseFloat(document.getElementById('longitude').value) || null,
        is_active: document.getElementById('isActive').checked
    };
    
    // Type-specific fields
    if (stationType === STATION_TYPES.VERTICAL) {
        data.elevation = document.getElementById('elevation').value ? parseFloat(document.getElementById('elevation').value) : null;
        data.bm_plus = document.getElementById('bmPlus').value ? parseFloat(document.getElementById('bmPlus').value) : null;
        data.accuracy_class = document.getElementById('accuracyClass').value || null;
        data.elevation_order = document.getElementById('elevationOrder').value || null;
        data.vertical_datum = document.getElementById('verticalDatum').value || null;
        data.elevation_authority = document.getElementById('elevationAuthority').value || null;
    } 
    else if (stationType === STATION_TYPES.HORIZONTAL) {
        data.ellipsoidal_height = document.getElementById('ellipsoidalHeight').value ? parseFloat(document.getElementById('ellipsoidalHeight').value) : null;
        data.horizontal_order = document.getElementById('horizontalOrder').value || null;
        data.horizontal_datum = document.getElementById('horizontalDatum').value || null;
        data.utm_northing = document.getElementById('northingValue').value ? parseFloat(document.getElementById('northingValue').value) : null;
        data.utm_easting = document.getElementById('eastingValue').value ? parseFloat(document.getElementById('eastingValue').value) : null;
        data.utm_zone = document.getElementById('utmZone').value || null;
        
        // ITRF coordinates - calculate decimal lat/lng from DMS
        data.itrf_latitude = calculateDecimalFromDMS(
            document.getElementById('itrfLatDd').value,
            document.getElementById('itrfLatMm').value,
            document.getElementById('itrfLatSs').value
        );
        data.itrf_longitude = calculateDecimalFromDMS(
            document.getElementById('itrfLonDd').value,
            document.getElementById('itrfLonMm').value,
            document.getElementById('itrfLonSs').value
        );
        data.itrf_ellipsoidal_height = document.getElementById('itrfEllHgt').value ? parseFloat(document.getElementById('itrfEllHgt').value) : null;
        data.itrf_error = document.getElementById('itrfEllErr').value ? parseFloat(document.getElementById('itrfEllErr').value) : null;
        data.itrf_height_error = document.getElementById('itrfHgtErr').value ? parseFloat(document.getElementById('itrfHgtErr').value) : null;
    } 
    else if (stationType === STATION_TYPES.GRAVITY) {
        data.gravity_value = document.getElementById('gravityValue').value ? parseFloat(document.getElementById('gravityValue').value) : null;
        data.standard_deviation = document.getElementById('standardDeviation').value ? parseFloat(document.getElementById('standardDeviation').value) : null;
        data.gravity_order = document.getElementById('gravityOrder').value || null;
        data.date_measured = document.getElementById('dateMeasured').value || null;
        data.gravity_meter = document.getElementById('gravityMeter').value || null;
        data.gravity_datum = document.getElementById('gravityDatum').value || null;
    }
    
    // Clean up data object - remove empty strings for optional fields
    Object.keys(data).forEach(key => {
        if (data[key] === '') {
            delete data[key];
        }
    });
    
    return data;
}

/**
 * Shows/hides form fields based on station type
 * @param {string} type - Station type
 */
export function updateFormFieldsVisibility(type) {
    // Hide all type-specific fields first
    document.querySelectorAll('.vertical-field, .horizontal-field, .gravity-field').forEach(field => {
        field.classList.add('hidden');
    });
    
    // Show fields based on type
    document.querySelectorAll(`.${type}-field`).forEach(field => {
        field.classList.remove('hidden');
    });
    
    // Update the station type radio buttons
    const radioBtn = document.querySelector(`input[name="gcpType"][value="${type}"]`);
    if (radioBtn) {
        radioBtn.checked = true;
    }
}

/**
 * Sets up DMS conversion functionality for coordinates
 */
export function setupDMSConversion() {
    // Set up latitude DMS fields
    const latFields = ['latDegrees', 'latMinutes', 'latSeconds'];
    latFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('input', () => updateDecimalCoordinates('lat'));
    });
    
    // Set up longitude DMS fields
    const lngFields = ['lngDegrees', 'lngMinutes', 'lngSeconds'];
    lngFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('input', () => updateDecimalCoordinates('lng'));
    });
}

/**
 * Updates hidden decimal coordinate fields from DMS input
 * @param {string} type - Coordinate type ('lat' or 'lng')
 */
export function updateDecimalCoordinates(type) {
    let decimal = 0;
    let degrees = 0;
    let minutes = 0;
    let seconds = 0;
    
    if (type === 'lat') {
        degrees = parseFloat(document.getElementById('latDegrees').value) || 0;
        minutes = parseFloat(document.getElementById('latMinutes').value) || 0;
        seconds = parseFloat(document.getElementById('latSeconds').value) || 0;
    } else {
        degrees = parseFloat(document.getElementById('lngDegrees').value) || 0;
        minutes = parseFloat(document.getElementById('lngMinutes').value) || 0;
        seconds = parseFloat(document.getElementById('lngSeconds').value) || 0;
    }
    
    // Calculate decimal value
    decimal = calculateDecimalFromDMS(degrees, minutes, seconds);
    
    // Update hidden field
    document.getElementById(type === 'lat' ? 'latitude' : 'longitude').value = decimal;
}

/**
 * Sets DMS input fields from decimal coordinate
 * @param {string} type - Coordinate type ('lat' or 'lng')
 * @param {number} decimalValue - Decimal coordinate value
 */
export function setDMSFromDecimal(type, decimalValue) {
    if (!decimalValue && decimalValue !== 0) return;
    
    const dms = decimalToDMS(decimalValue);
    
    if (type === 'lat') {
        document.getElementById('latDegrees').value = dms.degrees;
        document.getElementById('latMinutes').value = dms.minutes;
        document.getElementById('latSeconds').value = dms.seconds;
        document.getElementById('latitude').value = decimalValue;
    } else {
        document.getElementById('lngDegrees').value = dms.degrees;
        document.getElementById('lngMinutes').value = dms.minutes;
        document.getElementById('lngSeconds').value = dms.seconds;
        document.getElementById('longitude').value = decimalValue;
    }
}

/**
 * Calculates decimal coordinate from DMS components
 * @param {number} degrees - Degrees component
 * @param {number} minutes - Minutes component
 * @param {number} seconds - Seconds component
 * @returns {number} Decimal coordinate
 */
function calculateDecimalFromDMS(degrees, minutes, seconds) {
    degrees = parseFloat(degrees) || 0;
    minutes = parseFloat(minutes) || 0;
    seconds = parseFloat(seconds) || 0;
    
    const sign = degrees < 0 ? -1 : 1;
    const absDegrees = Math.abs(degrees);
    
    return sign * (absDegrees + minutes / 60 + seconds / 3600);
}

/**
 * Validates the coordinate inputs
 * @returns {boolean} Whether coordinates are valid
 */
function validateCoordinates() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    
    if (isNaN(lat) || isNaN(lng)) {
        return false;
    }
    
    if (lat < -90 || lat > 90) {
        return false;
    }
    
    if (lng < -180 || lng > 180) {
        return false;
    }
    
    return true;
}

// Function to validate form fields
function validateForm() {
    // Required fields depending on station type
    const requiredFields = [
        'stationName',
        'regionInput',
        'provinceInput',
        'cityInput',
        'latitude',
        'longitude'
    ];
    
    // Check required fields
    const emptyRequired = requiredFields.find(id => {
        const input = document.getElementById(id);
        return !input || !input.value.trim();
    });
    
    return !emptyRequired;
} 