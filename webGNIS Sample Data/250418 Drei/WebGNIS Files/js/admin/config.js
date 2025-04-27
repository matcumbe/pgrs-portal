// Admin Panel Configuration Module

// API Endpoints and Authentication
export const API_ENDPOINT = 'gcp_admin_api.php';
export const AUTH_CREDENTIALS = {
    username: 'admin',
    password: '12345'
};

// Pagination Settings
export const ITEMS_PER_PAGE = 10;

// Development settings
export const DEV_MODE = false; // Real API mode
export const USE_MOCK_DATA = false; // Do not use mock data
export const SHOW_API_ERRORS = true; // Show detailed API errors in console

// Station Types
export const STATION_TYPES = {
    VERTICAL: 'vertical',
    HORIZONTAL: 'horizontal',
    GRAVITY: 'gravity'
};

// Column Labels by Station Type
export const TYPE_COLUMN_LABELS = {
    [STATION_TYPES.VERTICAL]: 'Elevation (m)',
    [STATION_TYPES.HORIZONTAL]: 'Ellip. Height (m)',
    [STATION_TYPES.GRAVITY]: 'Gravity (mGal)'
};

// Store Global State Variables (for modules that need them)
export const state = {
    currentPage: 1,
    totalPages: 1,
    currentStations: [],
    allRegions: [],
    allProvinces: [],
    allCities: [],
    allBarangays: [],
    currentStationType: STATION_TYPES.VERTICAL,
    selectedStation: null
}; 