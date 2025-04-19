// Admin Panel JavaScript

// Configuration and Constants
const API_ENDPOINT = 'gcp_admin_api.php';
const AUTH_CREDENTIALS = {
    username: 'admin',
    password: '12345'
};
const ITEMS_PER_PAGE = 10;

// Development settings
const DEV_MODE = false; // Real API mode
const USE_MOCK_DATA = false; // Do not use mock data
const SHOW_API_ERRORS = true; // Show detailed API errors in console

// Global Variables
let currentPage = 1;
let totalPages = 1;
let currentStations = [];
let allRegions = [];
let allProvinces = [];
let allCities = [];
let allBarangays = [];
let currentStationType = 'vertical';
let selectedStation = null;

// Initialize application when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Handle authentication
    const loginForm = document.getElementById('loginForm');
    loginForm.addEventListener('submit', handleLogin);

    // Set up logout functionality
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);

    // Initialize event listeners for main functionality
    initializeEventListeners();
});

// Authentication Functions
function handleLogin(event) {
    event.preventDefault();
    console.log("Login attempt...");
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const loginError = document.getElementById('loginError');
    
    console.log("Credentials entered:", { username, password: "***" });
    console.log("Expected credentials:", { username: AUTH_CREDENTIALS.username, password: "***" });
    
    // Simple client-side authentication for demonstration
    if (username === AUTH_CREDENTIALS.username && password === AUTH_CREDENTIALS.password) {
        console.log("Login successful");
        // Hide login form and show admin interface
        document.getElementById('loginContainer').classList.add('hidden');
        document.getElementById('adminInterface').classList.remove('hidden');
        
        // Store auth state in sessionStorage
        sessionStorage.setItem('authenticated', 'true');
        
        // Initialize admin interface
        initializeAdminInterface();
    } else {
        console.log("Login failed");
        // Show error message
        loginError.textContent = 'Invalid username or password';
        loginError.classList.remove('hidden');
    }
}

function handleLogout() {
    // Clear auth state
    sessionStorage.removeItem('authenticated');
    
    // Hide admin interface and show login form
    document.getElementById('adminInterface').classList.add('hidden');
    document.getElementById('loginContainer').classList.remove('hidden');
    
    // Clear login form
    document.getElementById('username').value = '';
    document.getElementById('password').value = '';
    document.getElementById('loginError').classList.add('hidden');
}

function checkAuthState() {
    // Check if user is already authenticated
    if (sessionStorage.getItem('authenticated') === 'true') {
        document.getElementById('loginContainer').classList.add('hidden');
        document.getElementById('adminInterface').classList.remove('hidden');
        initializeAdminInterface();
    }
}

// Initialize Admin Interface
function initializeAdminInterface() {
    // Fetch initial data
    fetchStationsByType(currentStationType);
    
    // Fetch location data for dropdowns
    fetchLocationData();
}

// Initialize Event Listeners
function initializeEventListeners() {
    // GCP Type radio buttons
    document.querySelectorAll('input[name="gcpType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentStationType = this.value;
            console.log(`Changed station type to: ${currentStationType}`);
            
            // Update table header based on new type
            const valueColumnHeader = document.querySelector('#stationsTable thead tr th:nth-child(4)');
            if (valueColumnHeader) {
                if (currentStationType === 'vertical') {
                    valueColumnHeader.textContent = 'Elevation (m)';
                } else if (currentStationType === 'horizontal') {
                    valueColumnHeader.textContent = 'Ellip. Height (m)';
                } else if (currentStationType === 'gravity') {
                    valueColumnHeader.textContent = 'Gravity (mGal)';
                }
            }
            
            // Update form fields visibility for the current type
            updateFormFieldsVisibility(currentStationType);
            
            // Update form title if form is visible
            if (!document.getElementById('stationForm').classList.contains('hidden')) {
                const typeText = currentStationType.charAt(0).toUpperCase() + currentStationType.slice(1);
                const isEdit = document.getElementById('stationId').value ? true : false;
                const action = isEdit ? 'Edit' : 'Add New';
                document.getElementById('formTitle').textContent = `${action} ${typeText} GCP Station`;
            }
            
            // Clear all filter dropdowns when switching types
            document.getElementById('region').value = '';
            document.getElementById('province').value = '';
            document.getElementById('city').value = '';
            document.getElementById('barangay').value = '';
            
            // Fetch data for the new station type
            fetchStationsByType(currentStationType);
        });
    });
    
    // Filter elements
    const filterElements = ['orderFilter', 'accuracyFilter', 'region', 'province', 'city', 'barangay'];
    filterElements.forEach(elementId => {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });
    
    // Add cascade functionality to location filter dropdowns
    document.getElementById('region').addEventListener('change', function() {
        // When a region is selected, update the province dropdown
        const region = this.value;
        if (region) {
            // Filter provinces by this region
            fetchProvinces(region).then(provinces => {
                populateFilterDropdown('province', provinces.map(item => item.name));
                // Clear child dropdowns
                document.getElementById('province').value = '';
                document.getElementById('city').value = '';
                document.getElementById('barangay').value = '';
                applyFilters();
            }).catch(error => {
                showError('Failed to fetch provinces: ' + error.message);
            });
        } else {
            // If no region selected, show all provinces from the data
            populateFilterDropdown('province', [...new Set(currentStations
                .map(station => station.province)
                .filter(province => province))]);
            applyFilters();
        }
    });
    
    document.getElementById('province').addEventListener('change', function() {
        // When a province is selected, update the city dropdown
        const province = this.value;
        if (province) {
            // Filter cities by this province
            fetchCities(province).then(cities => {
                populateFilterDropdown('city', cities.map(item => item.name));
                // Clear child dropdown
                document.getElementById('city').value = '';
                document.getElementById('barangay').value = '';
                applyFilters();
            }).catch(error => {
                showError('Failed to fetch cities: ' + error.message);
            });
        } else {
            // If no province selected, show all cities from the data
            populateFilterDropdown('city', [...new Set(currentStations
                .map(station => station.city)
                .filter(city => city))]);
            applyFilters();
        }
    });
    
    document.getElementById('city').addEventListener('change', function() {
        // When a city is selected, update the barangay dropdown
        const city = this.value;
        if (city) {
            // Filter barangays by this city
            fetchBarangays(city).then(barangays => {
                populateFilterDropdown('barangay', barangays.map(item => item.name));
                // Clear child dropdown
                document.getElementById('barangay').value = '';
                applyFilters();
            }).catch(error => {
                showError('Failed to fetch barangays: ' + error.message);
            });
        } else {
            // If no city selected, show all barangays from the data
            populateFilterDropdown('barangay', [...new Set(currentStations
                .map(station => station.barangay)
                .filter(barangay => barangay))]);
            applyFilters();
        }
    });
    
    // Reset filters button
    document.getElementById('resetFiltersBtn').addEventListener('click', resetFilters);
    
    // Station name search
    document.getElementById('stationNameSearch').addEventListener('input', debounce(function() {
        applyFilters();
    }, 300));
    
    // Add new station button
    document.getElementById('addNewBtn').addEventListener('click', showAddForm);
    
    // Cancel buttons
    document.getElementById('cancelBtn').addEventListener('click', hideForm);
    document.getElementById('cancelViewBtn').addEventListener('click', hideViewPanel);
    document.getElementById('cancelDeleteBtn').addEventListener('click', hideDeleteConfirmation);
    
    // Form submission
    document.getElementById('stationForm').addEventListener('submit', handleFormSubmit);
    
    // Edit button
    document.getElementById('editBtn').addEventListener('click', function() {
        if (selectedStation) {
            showEditForm(selectedStation);
        }
    });
    
    // Delete buttons
    document.getElementById('deleteBtn').addEventListener('click', showDeleteConfirmation);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteStation);
    
    // Location cascading dropdowns
    document.getElementById('regionInput').addEventListener('change', function() {
        populateProvinceDropdown(this.value);
        document.getElementById('provinceInput').value = '';
        document.getElementById('cityInput').value = '';
        document.getElementById('barangayInput').value = '';
    });
    
    document.getElementById('provinceInput').addEventListener('change', function() {
        populateCityDropdown(this.value);
        document.getElementById('cityInput').value = '';
        document.getElementById('barangayInput').value = '';
    });
    
    document.getElementById('cityInput').addEventListener('change', function() {
        populateBarangayDropdown(this.value);
        document.getElementById('barangayInput').value = '';
    });
    
    // Setup DMS conversion
    setupDMSConversion();
    
    // Check authentication state on load
    checkAuthState();
}

// API Functions
async function apiRequest(endpoint, params = {}, method = 'GET', body = null) {
    try {
        toggleLoading(true);
        
        // Format the path to match the API router's expected format
        // The router in api.php expects paths in format like "/api/stations/vertical"
        const cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
        
        // Create the URL for server environment
        const url = new URL(API_ENDPOINT, window.location.href);
        url.searchParams.append('path', cleanEndpoint);
        
        // Add params to URL
        if (method === 'GET') {
            Object.keys(params).forEach(key => {
                url.searchParams.append(key, params[key]);
            });
        }
        
        // Log the actual URL being requested
        console.log('API Request URL:', url.toString());
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Add body for non-GET requests
        if (method !== 'GET' && body) {
            options.body = JSON.stringify(body);
        }
        
        const response = await fetch(url.toString(), options);
        
        // Check if we received JSON or HTML (error page)
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return data;
        } else {
            // Not JSON, probably an error page
            const text = await response.text();
            console.error("Received non-JSON response:", text.substring(0, 150) + "...");
            throw new Error(`Server returned non-JSON response (${response.status})`);
        }
    } catch (error) {
        showError(error.message);
        console.error('API request failed:', error);
        return { error: error.message };
    } finally {
        toggleLoading(false);
    }
}

// Helper functions for fetching location data
async function fetchRegions() {
    const url = new URL(API_ENDPOINT, window.location.href);
    url.searchParams.append('path', '/api/admin/regions');
    const response = await fetch(url.toString());
    const data = await response.json();
    return data.data || [];
}

async function fetchProvinces(region = '') {
    const url = new URL(API_ENDPOINT, window.location.href);
    url.searchParams.append('path', '/api/admin/provinces');
    if (region) {
        url.searchParams.append('region', region);
    }
    const response = await fetch(url.toString());
    const data = await response.json();
    return data.data || [];
}

async function fetchCities(province = '') {
    const url = new URL(API_ENDPOINT, window.location.href);
    url.searchParams.append('path', '/api/admin/cities');
    if (province) {
        url.searchParams.append('province', province);
    }
    const response = await fetch(url.toString());
    const data = await response.json();
    return data.data || [];
}

async function fetchBarangays(city = '') {
    const url = new URL(API_ENDPOINT, window.location.href);
    url.searchParams.append('path', '/api/admin/barangays');
    if (city) {
        url.searchParams.append('city', city);
    }
    const response = await fetch(url.toString());
    const data = await response.json();
    return data.data || [];
}

// For development - mock API responses as fallback
function getMockData(endpoint) {
    // Extract the endpoint type
    let type = 'vertical';
    if (endpoint.includes('horizontal')) {
        type = 'horizontal';
    } else if (endpoint.includes('gravity')) {
        type = 'gravity';
    }
    
    // Generate sample stations with all fields
    const mockStations = [];
    for (let i = 1; i <= 35; i++) {
        const station = {
            station_id: `${type}-${i}`,
            station_name: `${type.toUpperCase()}-${i.toString().padStart(3, '0')}`,
            latitude: 14.5 + (Math.random() * 0.5),
            longitude: 121.0 + (Math.random() * 0.5),
            monument_type: ['Concrete', 'Steel', 'Brass', 'Aluminum'][Math.floor(Math.random() * 4)],
            year_established: 2000 + Math.floor(Math.random() * 22),
            order: Math.floor(Math.random() * 5).toString(),
            region: 'Region IV-A',
            province: 'Laguna',
            city: 'Los Baños',
            barangay: 'Batong Malake',
            site_description: 'Sample site description with details about the surrounding area.',
            access_instructions: 'Sample access instructions explaining how to reach this point.',
            is_active: Math.random() > 0.1 // mostly active
        };
        
        // Add type-specific fields
        if (type === 'vertical') {
            station.elevation = Math.round(Math.random() * 1000) / 10;
            station.elevation_order = station.order;
            station.accuracy_class = ['1CM', '2CM', '3CM'][Math.floor(Math.random() * 3)];
            station.vertical_datum = ['MSL', 'NAVD88', 'Local'][Math.floor(Math.random() * 3)];
            station.benchmark_description = 'Sample benchmark description';
        } else if (type === 'horizontal') {
            station.ellipsoidal_height = Math.round(Math.random() * 1000) / 10;
            station.horizontal_order = station.order;
            station.horizontal_datum = ['WGS84', 'NAD83', 'PRS92'][Math.floor(Math.random() * 3)];
            station.azimuth_mark = `AZ-${Math.floor(Math.random() * 100)}`;
            station.northing = 1000000 + Math.round(Math.random() * 1000000);
            station.easting = 500000 + Math.round(Math.random() * 500000);
            station.utm_zone = `51${String.fromCharCode(78 + Math.floor(Math.random() * 8))}`;
        } else if (type === 'gravity') {
            station.gravity_value = Math.round(978000 + Math.random() * 2000) / 10;
            station.gravity_order = station.order;
            station.gravity_datum = ['IGSN71', 'Local'][Math.floor(Math.random() * 2)];
            station.gravity_meter = `Gravity Meter Model ${Math.floor(Math.random() * 10) + 1}`;
        }
        
        mockStations.push(station);
    }
    
    // Mock locations data
    if (endpoint.includes('locations')) {
        return {
            regions: [
                { name: 'Region IV-A' },
                { name: 'NCR' },
                { name: 'Region III' }
            ],
            provinces: [
                { name: 'Laguna', region: 'Region IV-A' },
                { name: 'Cavite', region: 'Region IV-A' },
                { name: 'Batangas', region: 'Region IV-A' },
                { name: 'Manila', region: 'NCR' },
                { name: 'Quezon City', region: 'NCR' },
                { name: 'Bulacan', region: 'Region III' }
            ],
            cities: [
                { name: 'Los Baños', province: 'Laguna' },
                { name: 'Calamba', province: 'Laguna' },
                { name: 'Dasmarinas', province: 'Cavite' },
                { name: 'Batangas City', province: 'Batangas' },
                { name: 'Manila', province: 'Manila' },
                { name: 'Quezon City', province: 'Quezon City' },
                { name: 'Malolos', province: 'Bulacan' }
            ],
            barangays: [
                { name: 'Batong Malake', city: 'Los Baños' },
                { name: 'Baybayin', city: 'Los Baños' },
                { name: 'Parian', city: 'Calamba' },
                { name: 'Paciano', city: 'Calamba' }
            ]
        };
    }
    
    // For individual station by ID
    if (endpoint.includes('/id/')) {
        const stationId = endpoint.split('/id/')[1];
        const foundStation = mockStations.find(s => s.station_id === stationId);
        return { data: foundStation || null };
    }

    return { data: mockStations };
}

// Update the data extraction in fetch functions
async function fetchStationsByType(type) {
    try {
        const data = await apiRequest(`/api/admin/stations/${type}`);
        
        // Check for errors
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Get the stations array from the response
        const stations = data.data || [];
        
        // Process the data
        currentStations = stations;
        
        // Log data for debugging
        console.log(`Received ${stations.length} ${type} stations:`, stations);
        
        // Update the table with the stations
        updateStationsTable(currentStations);
        
        // Update the filters based on the data
        updateFiltersBasedOnData(currentStations);
        
        // Clear all location filter dropdowns except region
        document.getElementById('province').value = '';
        document.getElementById('city').value = '';
        document.getElementById('barangay').value = '';
        
        // Ensure we have the latest location data for this type
        fetchLocationData().then(() => {
            console.log('Refreshed location data after type change');
        });
    } catch (error) {
        showError('Failed to fetch stations: ' + error.message);
    }
}

async function fetchStationById(id) {
    try {
        // Get data from API
        const data = await apiRequest(`/api/admin/station/${id}`);
        return data.data;
    } catch (error) {
        showError('Failed to fetch station: ' + error.message);
        return null;
    }
}

async function fetchLocationData() {
    try {
        // Use separate API calls for each location type
        const regionsData = await apiRequest('/api/admin/regions');
        const provincesData = await apiRequest('/api/admin/provinces');
        const citiesData = await apiRequest('/api/admin/cities');
        const barangaysData = await apiRequest('/api/admin/barangays');
        
        // Process the data
        allRegions = regionsData.data || [];
        allProvinces = provincesData.data || [];
        allCities = citiesData.data || [];
        allBarangays = barangaysData.data || [];
        
        // Log the received data for debugging
        console.log('Locations data received:', {
            regions: allRegions,
            provinces: allProvinces,
            cities: allCities,
            barangays: allBarangays
        });
        
        populateLocationDropdowns();
    } catch (error) {
        showError('Failed to fetch location data: ' + error.message);
    }
}

// Other CRUD operations
async function createStation(stationData) {
    try {
        const data = await apiRequest('/api/admin/station', {}, 'POST', stationData);
        
        showSuccess('Station created successfully');
        fetchStationsByType(currentStationType);
        return true;
    } catch (error) {
        showError('Failed to create station: ' + error.message);
        return false;
    }
}

async function updateStation(id, stationData) {
    try {
        const data = await apiRequest(`/api/admin/station/${id}`, {}, 'PUT', stationData);
        
        showSuccess('Station updated successfully');
        fetchStationsByType(currentStationType);
        return true;
    } catch (error) {
        showError('Failed to update station: ' + error.message);
        return false;
    }
}

async function deleteStation() {
    if (!selectedStation) {
        showError('No station selected for deletion');
        return;
    }
    
    try {
        const data = await apiRequest(`/api/admin/station/${selectedStation.station_id}`, {}, 'DELETE');
        
        showSuccess('Station deleted successfully');
        hideDeleteConfirmation();
        hideViewPanel();
        fetchStationsByType(currentStationType);
    } catch (error) {
        showError('Failed to delete station: ' + error.message);
    }
}

// Populate Location Dropdowns
function populateLocationDropdowns() {
    // Populate filter dropdowns
    populateDropdown('region', allRegions);
    populateDropdown('province', allProvinces);
    populateDropdown('city', allCities);
    populateDropdown('barangay', allBarangays);
    
    // Populate form dropdowns
    populateDropdown('regionInput', allRegions);
    populateDropdown('provinceInput', allProvinces);
    populateDropdown('cityInput', allCities);
    populateDropdown('barangayInput', allBarangays);
}

function populateDropdown(elementId, data, valueKey = 'name', textKey = 'name') {
    const dropdown = document.getElementById(elementId);
    if (!dropdown) return;
    
    // Save current selection
    const currentValue = dropdown.value;
    
    // Clear dropdown except first option
    while (dropdown.options.length > 1) {
        dropdown.remove(1);
    }
    
    // Add new options
    data.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueKey];
        option.textContent = item[textKey];
        dropdown.appendChild(option);
    });
    
    // Restore selection if possible
    if (currentValue) {
        dropdown.value = currentValue;
    }
}

function populateProvinceDropdown(region) {
    // Use the API to fetch provinces filtered by region
    if (region) {
        // Make API call to get provinces for this region
        fetchProvinces(region).then(provinces => {
            populateDropdown('provinceInput', provinces);
        }).catch(error => {
            showError('Failed to fetch provinces: ' + error.message);
        });
    } else {
        // If no region, show all provinces
        populateDropdown('provinceInput', allProvinces);
    }
}

function populateCityDropdown(province) {
    // Use the API to fetch cities filtered by province
    if (province) {
        // Make API call to get cities for this province
        fetchCities(province).then(cities => {
            populateDropdown('cityInput', cities);
        }).catch(error => {
            showError('Failed to fetch cities: ' + error.message);
        });
    } else {
        // If no province selected, show all cities from the data
        populateDropdown('cityInput', allCities);
    }
}

function populateBarangayDropdown(city) {
    // Use the API to fetch barangays filtered by city
    if (city) {
        // Make API call to get barangays for this city
        fetchBarangays(city).then(barangays => {
            populateDropdown('barangayInput', barangays);
        }).catch(error => {
            showError('Failed to fetch barangays: ' + error.message);
        });
    } else {
        // If no city, show all barangays
        populateDropdown('barangayInput', allBarangays);
    }
}

// Update Stations Table
function updateStationsTable(stations) {
    const tbody = document.getElementById('stationsList');
    if (!tbody) return;
    
    // Update the table header for the value column based on station type
    const valueColumnHeader = document.querySelector('#stationsTable thead tr th:nth-child(4)');
    if (valueColumnHeader) {
        if (currentStationType === 'vertical') {
            valueColumnHeader.textContent = 'Elevation (m)';
        } else if (currentStationType === 'horizontal') {
            valueColumnHeader.textContent = 'Ellip. Height (m)';
        } else if (currentStationType === 'gravity') {
            valueColumnHeader.textContent = 'Gravity (mGal)';
        }
    }
    
    // Clear table
    tbody.innerHTML = '';
    
    // Calculate pagination
    totalPages = Math.ceil(stations.length / ITEMS_PER_PAGE);
    
    // Adjust current page if needed
    if (currentPage > totalPages) {
        currentPage = totalPages || 1;
    }
    
    // Get current page items
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const currentPageItems = stations.slice(startIndex, endIndex);
    
    // Add rows to table
    currentPageItems.forEach(station => {
        const row = document.createElement('tr');
        
        // Choose which field to display based on station type
        let value = '';
        let order = '';
        
        if (currentStationType === 'vertical') {
            value = station.elevation || station.bm_plus || '';
            order = station.elevation_order || station.order || '';
        } else if (currentStationType === 'horizontal') {
            value = station.ellipsoidal_height || station.ellipz || '';
            order = station.horizontal_order || station.order || '';
        } else if (currentStationType === 'gravity') {
            value = station.gravity_value || '';
            order = station.gravity_order || station.order || '';
        }
        
        row.innerHTML = `
            <td>${station.station_name || ''}</td>
            <td>${station.latitude || ''}</td>
            <td>${station.longitude || ''}</td>
            <td>${value}</td>
            <td>${order}</td>
            <td>
                <button class="btn btn-sm btn-primary view-btn" data-id="${station.station_id}">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-warning edit-btn" data-id="${station.station_id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="${station.station_id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const stationId = this.getAttribute('data-id');
            viewStation(stationId);
        });
    });
    
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const stationId = this.getAttribute('data-id');
            editStation(stationId);
        });
    });
    
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const stationId = this.getAttribute('data-id');
            confirmDeleteStation(stationId);
        });
    });
    
    // Update pagination
    updatePagination();
}

// Update Pagination Controls
function updatePagination() {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;
    
    pagination.innerHTML = '';
    
    // Don't show pagination if only one page
    if (totalPages <= 1) return;
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>`;
    pagination.appendChild(prevLi);
    
    // Page numbers
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        pagination.appendChild(li);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>`;
    pagination.appendChild(nextLi);
    
    // Add event listeners
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.getAttribute('data-page'));
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                updateStationsTable(currentStations);
            }
        });
    });
}

// Filter Functions
function applyFilters() {
    const type = currentStationType;
    const orderValue = document.getElementById('orderFilter').value;
    const accuracyValue = document.getElementById('accuracyFilter').value;
    const regionValue = document.getElementById('region').value;
    const provinceValue = document.getElementById('province').value;
    const cityValue = document.getElementById('city').value;
    const barangayValue = document.getElementById('barangay').value;
    const searchTerm = document.getElementById('stationNameSearch').value.toLowerCase().trim();
    
    console.log('Applying filters:', {
        type,
        orderValue,
        accuracyValue,
        regionValue,
        provinceValue,
        cityValue,
        barangayValue,
        searchTerm
    });
    
    // Get all stations of current type
    let filteredStations = currentStations;
    console.log(`Starting with ${filteredStations.length} stations`);
    
    // Apply search filter
    if (searchTerm) {
        filteredStations = filteredStations.filter(station => {
            const stationName = (station.station_name || '').toLowerCase();
            const stationCode = (station.station_code || '').toLowerCase();
            return stationName.includes(searchTerm) || stationCode.includes(searchTerm);
        });
        console.log(`After search filter: ${filteredStations.length} stations`);
    }
    
    // Apply order filter based on station type
    if (orderValue) {
        if (type === 'vertical') {
            filteredStations = filteredStations.filter(station => {
                const stationOrder = station.elevation_order || station.order || '';
                return stationOrder === orderValue;
            });
        } else if (type === 'horizontal') {
            filteredStations = filteredStations.filter(station => {
                const stationOrder = station.horizontal_order || station.order || '';
                return stationOrder === orderValue;
            });
        } else if (type === 'gravity') {
            filteredStations = filteredStations.filter(station => {
                const stationOrder = station.gravity_order || station.order || '';
                return stationOrder === orderValue;
            });
        }
        console.log(`After order filter: ${filteredStations.length} stations`);
    }
    
    // Apply accuracy filter for vertical stations
    if (type === 'vertical' && accuracyValue) {
        filteredStations = filteredStations.filter(station => 
            station.accuracy_class === accuracyValue
        );
        console.log(`After accuracy filter: ${filteredStations.length} stations`);
    }
    
    // Apply location filters
    if (regionValue) {
        filteredStations = filteredStations.filter(station => 
            station.region === regionValue
        );
        console.log(`After region filter: ${filteredStations.length} stations`);
    }
    
    if (provinceValue) {
        filteredStations = filteredStations.filter(station => 
            station.province === provinceValue
        );
        console.log(`After province filter: ${filteredStations.length} stations`);
    }
    
    if (cityValue) {
        filteredStations = filteredStations.filter(station => {
            // Normalize station city name for comparison
            const stationCity = station.city || '';
            let normalizedStationCity = stationCity;
            
            // Handle "CITY OF" prefix
            if (stationCity.toUpperCase().startsWith('CITY OF ')) {
                normalizedStationCity = stationCity.substring(8).trim();
            }
            
            // Compare with both original and normalized city name
            return stationCity === cityValue || normalizedStationCity === cityValue;
        });
        console.log(`After city filter: ${filteredStations.length} stations`);
    }
    
    if (barangayValue) {
        filteredStations = filteredStations.filter(station => 
            station.barangay === barangayValue
        );
        console.log(`After barangay filter: ${filteredStations.length} stations`);
    }
    
    // Reset pagination to first page
    currentPage = 1;
    
    // Update table with filtered stations
    updateStationsTable(filteredStations);
}

function resetFilters() {
    // Reset all filter inputs
    document.getElementById('stationNameSearch').value = '';
    document.getElementById('orderFilter').value = '';
    document.getElementById('accuracyFilter').value = '';
    document.getElementById('region').value = '';
    document.getElementById('province').value = '';
    document.getElementById('city').value = '';
    document.getElementById('barangay').value = '';
    
    // Fetch all stations again
    fetchStationsByType(currentStationType);
}

// Update filters based on available data
function updateFiltersBasedOnData(stations) {
    // Get unique values for each filter based on station type
    let uniqueOrders = [];
    
    if (currentStationType === 'vertical') {
        uniqueOrders = [...new Set(stations
            .map(station => station.elevation_order || station.order)
            .filter(order => order))];
            
        const uniqueAccuracyClasses = [...new Set(stations
            .filter(station => station.accuracy_class)
            .map(station => station.accuracy_class))];
            
        // Update dropdowns
        populateFilterDropdown('accuracyFilter', uniqueAccuracyClasses);
        
        // Show accuracy filter for vertical stations
        const accuracyContainer = document.getElementById('accuracyClassContainer');
        if (accuracyContainer) {
            accuracyContainer.style.display = 'block';
        }
    } else if (currentStationType === 'horizontal') {
        uniqueOrders = [...new Set(stations
            .map(station => station.horizontal_order || station.order)
            .filter(order => order))];
            
        // Hide accuracy filter for horizontal stations
        const accuracyContainer = document.getElementById('accuracyClassContainer');
        if (accuracyContainer) {
            accuracyContainer.style.display = 'none';
        }
    } else if (currentStationType === 'gravity') {
        uniqueOrders = [...new Set(stations
            .map(station => station.gravity_order || station.order)
            .filter(order => order))];
            
        // Hide accuracy filter for gravity stations
        const accuracyContainer = document.getElementById('accuracyClassContainer');
        if (accuracyContainer) {
            accuracyContainer.style.display = 'none';
        }
    }
    
    // Update order dropdown
    populateFilterDropdown('orderFilter', uniqueOrders);
    
    // Get location data for the current stations
    const uniqueRegions = [...new Set(stations
        .map(station => station.region)
        .filter(region => region))];
    
    // For the initial load, only populate the highest level (regions)
    // This avoids the issue of having all locations loaded at once
    populateFilterDropdown('region', uniqueRegions);
    
    // Clear other location dropdowns to start fresh
    populateFilterDropdown('province', []);
    populateFilterDropdown('city', []);
    populateFilterDropdown('barangay', []);
}

function populateFilterDropdown(id, values) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    
    // Save current selection
    const currentValue = dropdown.value;
    
    // Clear dropdown except first option
    while (dropdown.options.length > 1) {
        dropdown.remove(1);
    }
    
    // Normalize and deduplicate values
    let normalizedValues = [];
    if (id === 'city') {
        // For cities, normalize names by removing prefixes like "CITY OF"
        const cityMap = new Map();
        values.forEach(city => {
            if (!city) return;
            
            // Normalize city name
            let normalizedCity = city;
            if (city.toUpperCase().startsWith('CITY OF ')) {
                normalizedCity = city.substring(8).trim();
            }
            
            // Keep the shorter version if both exist
            if (!cityMap.has(normalizedCity.toUpperCase()) || 
                city.length < cityMap.get(normalizedCity.toUpperCase()).length) {
                cityMap.set(normalizedCity.toUpperCase(), normalizedCity);
            }
        });
        
        normalizedValues = Array.from(cityMap.values());
    } else {
        // For other dropdowns, just remove duplicates
        normalizedValues = [...new Set(values.filter(v => v))];
    }
    
    // Add new options
    normalizedValues.sort().forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        dropdown.appendChild(option);
    });
    
    // Restore selection if possible
    if (currentValue && normalizedValues.includes(currentValue)) {
        dropdown.value = currentValue;
    }
}

// Form Functions
function showAddForm() {
    // Reset form
    document.getElementById('stationForm').reset();
    document.getElementById('stationId').value = '';
    
    // Reset DMS fields
    document.getElementById('latDegrees').value = '';
    document.getElementById('latMinutes').value = '';
    document.getElementById('latSeconds').value = '';
    document.getElementById('lngDegrees').value = '';
    document.getElementById('lngMinutes').value = '';
    document.getElementById('lngSeconds').value = '';
    
    // Update form title based on station type
    const typeText = currentStationType.charAt(0).toUpperCase() + currentStationType.slice(1);
    document.getElementById('formTitle').textContent = `Add New ${typeText} GCP Station`;
    
    // Show form and hide other panels
    document.getElementById('stationForm').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.add('hidden');
    
    // Update fields visibility based on current type
    updateFormFieldsVisibility(currentStationType);
}

async function editStation(stationId) {
    console.log('Edit station called with ID:', stationId);
    
    // Find station in current list
    let station = currentStations.find(s => s.station_id === stationId);
    
    // If not found, fetch from API
    if (!station) {
        console.log('Station not found in currentStations, fetching from API');
        station = await fetchStationById(stationId);
    }
    
    if (!station) {
        showError('Station not found');
        return;
    }
    
    // Store selected station
    selectedStation = station;
    console.log('Selected station for editing:', station);
    
    // Show edit form
    showEditForm(station);
}

function showEditForm(station) {
    // Debug - log the station data
    console.log('Showing edit form with station data:', station);

    // Update form title based on station type
    const typeText = currentStationType.charAt(0).toUpperCase() + currentStationType.slice(1);
    document.getElementById('formTitle').textContent = `Edit ${typeText} GCP Station`;
    
    // Show form and hide other panels
    const stationForm = document.getElementById('stationForm');
    const viewPanel = document.getElementById('viewPanel');
    const deleteConfirmPanel = document.getElementById('deleteConfirmPanel');
    const welcomePanel = document.getElementById('welcomePanel');
    
    console.log('Form elements:', {
        stationForm,
        viewPanel,
        deleteConfirmPanel,
        welcomePanel
    });
    
    if (stationForm) {
        stationForm.classList.remove('hidden');
    } else {
        console.error('Station form element not found');
    }
    
    if (viewPanel) viewPanel.classList.add('hidden');
    if (deleteConfirmPanel) deleteConfirmPanel.classList.add('hidden');
    if (welcomePanel) welcomePanel.classList.add('hidden');
    
    // Fill form with station data - main fields
    document.getElementById('stationId').value = station.station_id || station.ucode || '';
    document.getElementById('stationName').value = station.station_name || station.new_station_name || '';
    document.getElementById('stationCode').value = station.station_code || '';
    
    // Set DMS fields from decimal values
    if (station.latitude || station.latitude_decimal) {
        const lat = station.latitude || station.latitude_decimal;
        setDMSFromDecimal('lat', lat);
        document.getElementById('latitude').value = lat;
    } else if (station.latitude_degrees && station.latitude_minutes && station.latitude_seconds) {
        // If DMS values are available, set them directly
        document.getElementById('latDegrees').value = station.latitude_degrees;
        document.getElementById('latMinutes').value = station.latitude_minutes;
        document.getElementById('latSeconds').value = station.latitude_seconds;
        
        // Calculate decimal value
        updateDecimalCoordinates('lat');
    } else {
        // Clear the fields if no latitude
        document.getElementById('latDegrees').value = '';
        document.getElementById('latMinutes').value = '';
        document.getElementById('latSeconds').value = '';
        document.getElementById('latitude').value = '';
    }
    
    if (station.longitude || station.longitude_decimal) {
        const lng = station.longitude || station.longitude_decimal;
        setDMSFromDecimal('lng', lng);
        document.getElementById('longitude').value = lng;
    } else if (station.longitude_degrees && station.longitude_minutes && station.longitude_seconds) {
        // If DMS values are available, set them directly
        document.getElementById('lngDegrees').value = station.longitude_degrees;
        document.getElementById('lngMinutes').value = station.longitude_minutes;
        document.getElementById('lngSeconds').value = station.longitude_seconds;
        
        // Calculate decimal value
        updateDecimalCoordinates('lng');
    } else {
        // Clear the fields if no longitude
        document.getElementById('lngDegrees').value = '';
        document.getElementById('lngMinutes').value = '';
        document.getElementById('lngSeconds').value = '';
        document.getElementById('longitude').value = '';
    }
    
    // Set common fields - try multiple field names from the database schema
    document.getElementById('markType').value = station.mark_type || '';
    document.getElementById('markStatus').value = station.mark_status || '';
    document.getElementById('markConstruction').value = station.mark_const || '';
    document.getElementById('authority').value = station.authority || '';
    
    // Handle dates
    if (station.date_established) {
        document.getElementById('dateEstablished').value = formatDateForInput(station.date_established);
    } else if (station.date_est_year && station.date_est_month && station.date_est_day) {
        const dateString = `${station.date_est_year}-${station.date_est_month.toString().padStart(2, '0')}-${station.date_est_day.toString().padStart(2, '0')}`;
        document.getElementById('dateEstablished').value = dateString;
    }
    
    if (station.date_last_updated) {
        document.getElementById('dateLastUpdated').value = formatDateForInput(station.date_last_updated);
    }
    
    // Other common fields
    document.getElementById('encoder').value = station.encoder || '';
    document.getElementById('islandGroup').value = station.island_group || '';
    document.getElementById('orderInput').value = station.order || '';
    document.getElementById('siteDescription').value = station.site_description || station.description || '';
    document.getElementById('accessInstructions').value = station.access_instructions || '';
    document.getElementById('isActive').checked = station.is_active !== false && station.status_tag !== 0;
    
    // Set type-specific fields
    if (currentStationType === 'vertical') {
        document.getElementById('elevation').value = station.elevation || '';
        document.getElementById('bmPlus').value = station.bm_plus || '';
        
        // Handle accuracy class with special logic to ensure the value exists in the dropdown
        const accuracyClass = station.accuracy_class || '';
        const accuracySelect = document.getElementById('accuracyClass');
        
        // Check if the value exists in the dropdown
        let valueExists = false;
        for (let i = 0; i < accuracySelect.options.length; i++) {
            if (accuracySelect.options[i].value === accuracyClass) {
                valueExists = true;
                break;
            }
        }
        
        // If the value doesn't exist and isn't empty, add it
        if (!valueExists && accuracyClass !== '') {
            const newOption = document.createElement('option');
            newOption.value = accuracyClass;
            newOption.text = accuracyClass;
            accuracySelect.add(newOption);
        }
        
        // Set the value
        accuracySelect.value = accuracyClass;
        
        document.getElementById('elevationOrder').value = station.elevation_order || station.order || '';
        document.getElementById('verticalDatum').value = station.elevation_datum || '';
        document.getElementById('elevationAuthority').value = station.elevation_authority || '';
    } else if (currentStationType === 'horizontal') {
        document.getElementById('ellipsoidalHeight').value = station.ellipsoidal_height || '';
        document.getElementById('horizontalOrder').value = station.horizontal_order || station.order || '';
        document.getElementById('horizontalDatum').value = station.horizontal_datum || '';
        
        // UTM coordinates
        document.getElementById('northingValue').value = station.utm_northing || station.northing || '';
        document.getElementById('eastingValue').value = station.utm_easting || station.easting || '';
        document.getElementById('utmZone').value = station.utm_zone || '';
        
        // ITRF coordinates
        document.getElementById('itrfLatDd').value = station.itrf_lat_dd || '';
        document.getElementById('itrfLatMm').value = station.itrf_lat_mm || '';
        document.getElementById('itrfLatSs').value = station.itrf_lat_ss || '';
        document.getElementById('itrfLonDd').value = station.itrf_lon_dd || '';
        document.getElementById('itrfLonMm').value = station.itrf_lon_mm || '';
        document.getElementById('itrfLonSs').value = station.itrf_lon_ss || '';
        document.getElementById('itrfEllHgt').value = station.itrf_ell_hgt || '';
        document.getElementById('itrfEllErr').value = station.itrf_ell_err || '';
        document.getElementById('itrfHgtErr').value = station.itrf_hgt_err || '';
    } else if (currentStationType === 'gravity') {
        document.getElementById('gravityValue').value = station.gravity_value || '';
        document.getElementById('standardDeviation').value = station.standard_deviation || '';
        
        if (station.date_measured) {
            document.getElementById('dateMeasured').value = formatDateForInput(station.date_measured);
        }
        
        document.getElementById('gravityOrder').value = station.gravity_order || station.order || '';
        document.getElementById('gravityDatum').value = station.gravity_datum || '';
        document.getElementById('gravityMeter').value = station.gravity_meter || '';
    }
    
    // Set location dropdowns
    if (station.region) {
        document.getElementById('regionInput').value = station.region;
        populateProvinceDropdown(station.region);
    }
    
    if (station.province) {
        document.getElementById('provinceInput').value = station.province;
        populateCityDropdown(station.province);
    }
    
    if (station.city) {
        document.getElementById('cityInput').value = station.city;
        populateBarangayDropdown(station.city);
    }
    
    if (station.barangay) {
        document.getElementById('barangayInput').value = station.barangay;
    }
    
    // Update fields visibility based on current type
    updateFormFieldsVisibility(currentStationType);
}

function hideForm() {
    document.getElementById('stationForm').classList.add('hidden');
    document.getElementById('welcomePanel').classList.remove('hidden');
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    // Ensure decimal coordinates are updated
    updateDecimalCoordinates('lat');
    updateDecimalCoordinates('lng');
    
    // Get common form data
    const formData = {
        type: currentStationType,
        station_id: document.getElementById('stationId').value,
        station_name: document.getElementById('stationName').value,
        station_code: document.getElementById('stationCode').value,
        latitude: parseFloat(document.getElementById('latitude').value) || null,
        longitude: parseFloat(document.getElementById('longitude').value) || null,
        
        // DMS values
        latitude_degrees: parseInt(document.getElementById('latDegrees').value) || null,
        latitude_minutes: parseInt(document.getElementById('latMinutes').value) || null,
        latitude_seconds: parseFloat(document.getElementById('latSeconds').value) || null,
        longitude_degrees: parseInt(document.getElementById('lngDegrees').value) || null,
        longitude_minutes: parseInt(document.getElementById('lngMinutes').value) || null,
        longitude_seconds: parseFloat(document.getElementById('lngSeconds').value) || null,
        
        // Common fields
        mark_type: document.getElementById('markType').value || null,
        mark_status: document.getElementById('markStatus').value || null,
        mark_const: document.getElementById('markConstruction').value || null,
        authority: document.getElementById('authority').value || null,
        date_established: document.getElementById('dateEstablished').value || null,
        date_last_updated: document.getElementById('dateLastUpdated').value || null,
        encoder: document.getElementById('encoder').value || null,
        island_group: document.getElementById('islandGroup').value || null,
        order: document.getElementById('orderInput').value || null,
        site_description: document.getElementById('siteDescription').value || null,
        description: document.getElementById('siteDescription').value || null,  // Duplicate for different field names
        access_instructions: document.getElementById('accessInstructions').value || null,
        is_active: document.getElementById('isActive').checked,
        status_tag: document.getElementById('isActive').checked ? 1 : 0,
        
        // Location
        region: document.getElementById('regionInput').value,
        province: document.getElementById('provinceInput').value,
        city: document.getElementById('cityInput').value,
        barangay: document.getElementById('barangayInput').value,
    };
    
    // Add type-specific fields
    if (currentStationType === 'vertical') {
        formData.elevation = parseFloat(document.getElementById('elevation').value) || null;
        formData.bm_plus = parseFloat(document.getElementById('bmPlus').value) || null;
        formData.elevation_order = document.getElementById('elevationOrder').value;
        formData.accuracy_class = document.getElementById('accuracyClass').value;
        formData.elevation_datum = document.getElementById('verticalDatum').value;
        formData.elevation_authority = document.getElementById('elevationAuthority').value;
    } else if (currentStationType === 'horizontal') {
        formData.ellipsoidal_height = parseFloat(document.getElementById('ellipsoidalHeight').value) || null;
        formData.horizontal_order = document.getElementById('horizontalOrder').value;
        formData.horizontal_datum = document.getElementById('horizontalDatum').value;
        
        // UTM coordinates
        formData.utm_northing = parseFloat(document.getElementById('northingValue').value) || null;
        formData.northing = formData.utm_northing; // Duplicate for different field names
        formData.utm_easting = parseFloat(document.getElementById('eastingValue').value) || null;
        formData.easting = formData.utm_easting; // Duplicate for different field names
        formData.utm_zone = document.getElementById('utmZone').value;
        
        // ITRF coordinates
        formData.itrf_lat_dd = parseInt(document.getElementById('itrfLatDd').value) || null;
        formData.itrf_lat_mm = parseInt(document.getElementById('itrfLatMm').value) || null;
        formData.itrf_lat_ss = parseFloat(document.getElementById('itrfLatSs').value) || null;
        formData.itrf_lon_dd = parseInt(document.getElementById('itrfLonDd').value) || null;
        formData.itrf_lon_mm = parseInt(document.getElementById('itrfLonMm').value) || null;
        formData.itrf_lon_ss = parseFloat(document.getElementById('itrfLonSs').value) || null;
        formData.itrf_ell_hgt = parseFloat(document.getElementById('itrfEllHgt').value) || null;
        formData.itrf_ell_err = parseFloat(document.getElementById('itrfEllErr').value) || null;
        formData.itrf_hgt_err = parseFloat(document.getElementById('itrfHgtErr').value) || null;
    } else if (currentStationType === 'gravity') {
        formData.gravity_value = parseFloat(document.getElementById('gravityValue').value) || null;
        formData.standard_deviation = parseFloat(document.getElementById('standardDeviation').value) || null;
        formData.date_measured = document.getElementById('dateMeasured').value || null;
        formData.gravity_order = document.getElementById('gravityOrder').value;
        formData.gravity_datum = document.getElementById('gravityDatum').value;
        formData.gravity_meter = document.getElementById('gravityMeter').value;
    }
    
    // Get station ID for edit mode
    const stationId = document.getElementById('stationId').value;
    
    console.log('Submitting form data:', formData);
    
    let success = false;
    if (stationId) {
        // Update existing station
        success = await updateStation(stationId, formData);
    } else {
        // Create new station
        success = await createStation(formData);
    }
    
    if (success) {
        hideForm();
    }
}

function updateFormFieldsVisibility(type) {
    console.log('Updating form fields visibility for type:', type);
    
    // Show/hide tabs based on station type
    const verticalTabs = document.querySelectorAll('.vertical-type-tab');
    const horizontalTabs = document.querySelectorAll('.horizontal-type-tab');
    const gravityTabs = document.querySelectorAll('.gravity-type-tab');
    
    // Hide all type-specific tabs first
    verticalTabs.forEach(tab => tab.style.display = 'none');
    horizontalTabs.forEach(tab => tab.style.display = 'none');
    gravityTabs.forEach(tab => tab.style.display = 'none');
    
    // Show only tabs relevant to the current station type
    if (type === 'vertical') {
        verticalTabs.forEach(tab => tab.style.display = '');
    } else if (type === 'horizontal') {
        horizontalTabs.forEach(tab => tab.style.display = '');
    } else if (type === 'gravity') {
        gravityTabs.forEach(tab => tab.style.display = '');
    }
    
    // Get the appropriate tab and select it
    // If the current active tab is hidden, switch to common tab
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab && activeTab.parentElement.style.display === 'none') {
        // Default to common tab if active tab is now hidden
        const commonTab = document.getElementById('common-tab');
        if (commonTab) {
            const tab = new bootstrap.Tab(commonTab);
            tab.show();
        }
    } else {
        // If appropriate type tab exists and is visible, select it
        const tabElement = document.getElementById(`${type}-tab`);
        if (tabElement && tabElement.parentElement.style.display !== 'none') {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
    
    // Update the value column header in the table based on type
    const valueColumnHeader = document.querySelector('#stationsTable thead tr th:nth-child(4)');
    if (valueColumnHeader) {
        if (type === 'vertical') {
            valueColumnHeader.textContent = 'Elevation (m)';
        } else if (type === 'horizontal') {
            valueColumnHeader.textContent = 'Ellip. Height (m)';
        } else if (type === 'gravity') {
            valueColumnHeader.textContent = 'Gravity (mGal)';
        }
    }
}

// Handle DMS to decimal conversion
function setupDMSConversion() {
    // Latitude DMS fields
    const latDegrees = document.getElementById('latDegrees');
    const latMinutes = document.getElementById('latMinutes');
    const latSeconds = document.getElementById('latSeconds');
    const latitude = document.getElementById('latitude');
    
    // Longitude DMS fields
    const lngDegrees = document.getElementById('lngDegrees');
    const lngMinutes = document.getElementById('lngMinutes');
    const lngSeconds = document.getElementById('lngSeconds');
    const longitude = document.getElementById('longitude');
    
    // Update hidden decimal value when DMS values change
    [latDegrees, latMinutes, latSeconds].forEach(field => {
        field.addEventListener('input', () => {
            updateDecimalCoordinates('lat');
        });
    });
    
    [lngDegrees, lngMinutes, lngSeconds].forEach(field => {
        field.addEventListener('input', () => {
            updateDecimalCoordinates('lng');
        });
    });
}

// Update decimal coordinates from DMS fields
function updateDecimalCoordinates(type) {
    if (type === 'lat') {
        const degrees = parseFloat(document.getElementById('latDegrees').value) || 0;
        const minutes = parseFloat(document.getElementById('latMinutes').value) || 0;
        const seconds = parseFloat(document.getElementById('latSeconds').value) || 0;
        
        const decimal = degrees + (minutes / 60) + (seconds / 3600);
        document.getElementById('latitude').value = decimal.toFixed(6);
    } else {
        const degrees = parseFloat(document.getElementById('lngDegrees').value) || 0;
        const minutes = parseFloat(document.getElementById('lngMinutes').value) || 0;
        const seconds = parseFloat(document.getElementById('lngSeconds').value) || 0;
        
        const decimal = degrees + (minutes / 60) + (seconds / 3600);
        document.getElementById('longitude').value = decimal.toFixed(6);
    }
}

// Convert decimal to DMS and set form fields
function setDMSFromDecimal(type, decimalValue) {
    if (!decimalValue && decimalValue !== 0) return;
    
    // Get absolute value and determine sign
    const absolute = Math.abs(parseFloat(decimalValue));
    const sign = parseFloat(decimalValue) < 0 ? -1 : 1;
    
    // Calculate DMS values
    const degrees = Math.floor(absolute);
    const minutesFloat = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesFloat);
    const seconds = ((minutesFloat - minutes) * 60).toFixed(3);
    
    // Set the DMS fields
    if (type === 'lat') {
        document.getElementById('latDegrees').value = sign * degrees;
        document.getElementById('latMinutes').value = minutes;
        document.getElementById('latSeconds').value = seconds;
    } else {
        document.getElementById('lngDegrees').value = sign * degrees;
        document.getElementById('lngMinutes').value = minutes;
        document.getElementById('lngSeconds').value = seconds;
    }
}

// Station View Functions
async function viewStation(stationId) {
    // Find station in current list
    let station = currentStations.find(s => s.station_id === stationId);
    
    // If not found, fetch from API
    if (!station) {
        station = await fetchStationById(stationId);
    }
    
    if (!station) {
        showError('Station not found');
        return;
    }
    
    // Debug - log the station data to examine all properties
    console.log('Station data:', station);
    
    // Store selected station
    selectedStation = station;
    
    // Update station details display
    const detailsContainer = document.getElementById('stationDetails');
    
    // Choose which field to display based on station type
    let typeSpecificDetails = '';
    
    if (currentStationType === 'vertical') {
        // Check all possible field variations from the database schema
        const elevation = station.elevation || station.bm_plus || '0.000';
        const elevationOrder = station.elevation_order || station.order || '0';
        const accuracyClass = station.accuracy_class || 'N/A';
        const verticalDatum = station.vertical_datum || station.elevation_datum || station.elevation_established_datum || 'N/A';
        const benchmarkDesc = station.benchmark_description || station.description || station.inscription || '';
        
        typeSpecificDetails = `
            <div class="mb-3">
                <strong>Elevation:</strong> ${elevation} m
            </div>
            <div class="mb-3">
                <strong>Elevation Order:</strong> ${elevationOrder}
            </div>
            <div class="mb-3">
                <strong>Accuracy Class:</strong> ${accuracyClass}
            </div>
            <div class="mb-3">
                <strong>Vertical Datum:</strong> ${verticalDatum}
            </div>
            <div class="mb-3">
                <strong>Benchmark Description:</strong> ${benchmarkDesc}
            </div>
        `;
    } else if (currentStationType === 'horizontal') {
        // Check all possible field variations from the database schema
        const ellHeight = station.ellipsoidal_height || station.ellipz || '0.000';
        const horizontalOrder = station.horizontal_order || station.order || '0';
        const horizontalDatum = station.horizontal_datum || '0';
        const azimuthMark = station.azimuth_mark || '';
        const northing = station.northing || station.utm_northing || station.utm_y || '0.000';
        const easting = station.easting || station.utm_easting || station.utm_x || '0.000';
        const utmZone = station.utm_zone || station.utm_zone_alt || station.utm_zone_wgs84 || '0.0';
        
        typeSpecificDetails = `
            <div class="mb-3">
                <strong>Ellipsoidal Height:</strong> ${ellHeight} m
            </div>
            <div class="mb-3">
                <strong>Horizontal Order:</strong> ${horizontalOrder}
            </div>
            <div class="mb-3">
                <strong>Horizontal Datum:</strong> ${horizontalDatum}
            </div>
            <div class="mb-3">
                <strong>Azimuth Mark:</strong> ${azimuthMark}
            </div>
            <div class="mb-3">
                <strong>Northing:</strong> ${northing} m
            </div>
            <div class="mb-3">
                <strong>Easting:</strong> ${easting} m
            </div>
            <div class="mb-3">
                <strong>UTM Zone:</strong> ${utmZone}
            </div>
        `;
    } else if (currentStationType === 'gravity') {
        // Check all possible field variations from the database schema
        const gravityValue = station.gravity_value || '0.000';
        const gravityOrder = station.gravity_order || station.order || '0';
        const gravityDatum = station.gravity_datum || '';
        const gravityMeter = station.gravity_meter || '';
        
        typeSpecificDetails = `
            <div class="mb-3">
                <strong>Gravity Value:</strong> ${gravityValue} mGal
            </div>
            <div class="mb-3">
                <strong>Gravity Order:</strong> ${gravityOrder}
            </div>
            <div class="mb-3">
                <strong>Gravity Datum:</strong> ${gravityDatum}
            </div>
            <div class="mb-3">
                <strong>Gravity Meter:</strong> ${gravityMeter}
            </div>
        `;
    }
    
    // Safely get property values with fallbacks from all potential fields based on database schema
    const stationIdValue = station.station_id || station.ucode || '';
    const stationName = station.station_name || station.new_station_name || '';
    
    // Format latitude and longitude - try all possible field names
    const latitude = station.latitude || station.latitude_decimal || 
        (station.latitude_degrees && station.latitude_minutes && station.latitude_seconds ? 
        `${station.latitude_degrees}° ${station.latitude_minutes}' ${station.latitude_seconds}"` : '0.000000');
    
    const longitude = station.longitude || station.longitude_decimal || 
        (station.longitude_degrees && station.longitude_minutes && station.longitude_seconds ? 
        `${station.longitude_degrees}° ${station.longitude_minutes}' ${station.longitude_seconds}"` : '0.000000');
    
    // Get other fields with appropriate fallbacks
    const monumentType = station.mark_type || station.mark_const || '';
    const yearEstablished = station.date_established || station.date_est_year || '';
    const order = station.order || station.elevation_order || station.horizontal_order || station.gravity_order || '0';
    const region = station.region || '';
    const province = station.province || '';
    const city = station.city || '';
    const barangay = station.barangay || '';
    const siteDescription = station.site_description || station.description || '';
    const accessInstructions = station.access_instructions || '';
    const isActive = (station.is_active !== false && station.status_tag !== 0) ? 'Active' : 'Inactive';
    
    // Convert decimal coordinates to DMS for display
    const latDMS = (typeof latitude === 'number') ? decimalToDMS(latitude) : latitude;
    const lngDMS = (typeof longitude === 'number') ? decimalToDMS(longitude) : longitude;
    
    detailsContainer.innerHTML = `
        <div class="mb-3">
            <strong>Station ID:</strong> ${stationIdValue}
        </div>
        <div class="mb-3">
            <strong>Station Name:</strong> ${stationName}
        </div>
        <div class="mb-3">
            <strong>Coordinates:</strong>
            <div>Latitude: ${latDMS}</div>
            <div>Longitude: ${lngDMS}</div>
            <div>Decimal: ${typeof latitude === 'number' ? latitude : '0.000000'}, ${typeof longitude === 'number' ? longitude : '0.000000'}</div>
        </div>
        <div class="mb-3">
            <strong>Monument Type:</strong> ${monumentType}
        </div>
        <div class="mb-3">
            <strong>Year Established:</strong> ${yearEstablished}
        </div>
        <div class="mb-3">
            <strong>Order:</strong> ${order}
        </div>
        ${typeSpecificDetails}
        <div class="mb-3">
            <strong>Location:</strong>
            <div>Region: ${region}</div>
            <div>Province: ${province}</div>
            <div>City/Municipality: ${city}</div>
            <div>Barangay: ${barangay}</div>
        </div>
        <div class="mb-3">
            <strong>Site Description:</strong> ${siteDescription}
        </div>
        <div class="mb-3">
            <strong>Access Instructions:</strong> ${accessInstructions}
        </div>
        <div class="mb-3">
            <strong>Status:</strong> ${isActive}
        </div>
    `;
    
    // Show view panel and hide other panels
    document.getElementById('viewPanel').classList.remove('hidden');
    document.getElementById('stationForm').classList.add('hidden');
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.add('hidden');
}

function hideViewPanel() {
    document.getElementById('viewPanel').classList.add('hidden');
    document.getElementById('welcomePanel').classList.remove('hidden');
    selectedStation = null;
}

function confirmDeleteStation(stationId) {
    // Find station in current list
    const station = currentStations.find(s => s.station_id === stationId);
    
    if (!station) {
        showError('Station not found');
        return;
    }
    
    // Store selected station
    selectedStation = station;
    
    // Show delete confirmation panel
    document.getElementById('deleteConfirmPanel').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
}

function showDeleteConfirmation() {
    document.getElementById('deleteConfirmPanel').classList.remove('hidden');
    document.getElementById('viewPanel').classList.add('hidden');
}

function hideDeleteConfirmation() {
    document.getElementById('deleteConfirmPanel').classList.add('hidden');
    document.getElementById('viewPanel').classList.remove('hidden');
}

// Helper function to convert decimal coordinates to DMS string format
function decimalToDMS(decimal) {
    if (!decimal && decimal !== 0) return 'N/A';
    
    const absolute = Math.abs(parseFloat(decimal));
    const degrees = Math.floor(absolute);
    const minutesFloat = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesFloat);
    const seconds = ((minutesFloat - minutes) * 60).toFixed(3);
    
    const direction = decimal >= 0 ? 'N' : 'S';
    
    return `${degrees}° ${minutes}' ${seconds}" ${direction}`;
}

// Helper function to format dates for input fields
function formatDateForInput(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return ''; // Invalid date
        
        return date.toISOString().split('T')[0]; // Format as YYYY-MM-DD
    } catch (error) {
        console.error('Error formatting date:', error);
        return '';
    }
}

// Utility Functions
function toggleLoading(show) {
    document.getElementById('loadingIndicator').classList.toggle('hidden', !show);
}

function showError(message) {
    const errorContainer = document.getElementById('errorMessages');
    errorContainer.textContent = message;
    errorContainer.classList.remove('hidden');
    
    // Hide after 5 seconds
    setTimeout(() => {
        errorContainer.classList.add('hidden');
    }, 5000);
}

function showSuccess(message) {
    // Create a temporary success message
    const successElement = document.createElement('div');
    successElement.className = 'alert alert-success alert-dismissible fade show';
    successElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to the error container
    const errorContainer = document.getElementById('errorMessages');
    errorContainer.innerHTML = '';
    errorContainer.appendChild(successElement);
    errorContainer.classList.remove('hidden');
    
    // Hide after 3 seconds
    setTimeout(() => {
        errorContainer.classList.add('hidden');
    }, 3000);
}

// Debounce function for search input
function debounce(func, delay) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
} 