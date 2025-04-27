// Admin Panel Main Entry Point
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin page loaded...');
    
    // Add event listeners for login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const loginError = document.getElementById('loginError');
            
            // Simple client-side authentication for demo
            if (username === 'admin' && password === '12345') {
                console.log("Login successful");
                // Hide login form and show admin interface
                document.getElementById('loginContainer').classList.add('hidden');
                document.getElementById('adminInterface').classList.remove('hidden');
                
                // Store auth state in sessionStorage
                sessionStorage.setItem('authenticated', 'true');
                
                // Initialize admin interface
                initAdminInterface();
            } else {
                console.log("Login failed");
                // Show error message
                loginError.textContent = 'Invalid username or password';
                loginError.classList.remove('hidden');
            }
        });
    }
    
    // Check if already authenticated
    if (sessionStorage.getItem('authenticated') === 'true') {
        document.getElementById('loginContainer').classList.add('hidden');
        document.getElementById('adminInterface').classList.remove('hidden');
        initAdminInterface();
    }
    
    // Set up logout button handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Clear auth state
            sessionStorage.removeItem('authenticated');
            
            // Hide admin interface and show login form
            document.getElementById('adminInterface').classList.add('hidden');
            document.getElementById('loginContainer').classList.remove('hidden');
            
            // Clear login form
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('loginError').classList.add('hidden');
        });
    }
    
    // Set up GCP type radio buttons
    document.querySelectorAll('input[name="gcpType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const newType = this.value;
            console.log(`Switching to ${newType} stations`);
            loadStations(newType);
        });
    });
});

// Main function to initialize the admin interface
function initAdminInterface() {
    console.log("Initializing admin interface...");
    
    // Load stations data
    loadStations('vertical');
    
    // For now, just show a success message in place of real functionality
    const welcomePanel = document.getElementById('welcomePanel');
    if (welcomePanel) {
        welcomePanel.innerHTML = `
            <div class="alert alert-success">
                <h4>Login Successful!</h4>
                <p>The admin interface has been successfully loaded.</p>
                <p>This is a temporary simplified version to bypass the module loading issue.</p>
                <p>Please check the browser console for detailed error messages from the original code.</p>
            </div>
        `;
    }
}

// Function to load stations
function loadStations(type) {
    console.log(`Loading ${type} stations...`);
    
    // Display loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.classList.remove('hidden');
    }
    
    // Generate mock stations
    setTimeout(() => {
        const stations = generateMockStations(type, 10);
        updateStationsTable(stations, type);
        
        // Hide loading indicator
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    }, 500); // Simulate network delay
}

// Function to update the stations table
function updateStationsTable(stations, type) {
    const tableBody = document.getElementById('stationsList');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (stations.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="6" class="text-center">No stations found</td>`;
        tableBody.appendChild(row);
        return;
    }
    
    // Determine which field to use for value column based on type
    let valueField;
    
    if (type === 'vertical') {
        valueField = 'elevation';
    } else if (type === 'horizontal') {
        valueField = 'ellipsoidal_height';
    } else if (type === 'gravity') {
        valueField = 'gravity_value';
    }
    
    // Create table rows
    stations.forEach(station => {
        const row = document.createElement('tr');
        
        // Format the station name with ID
        const stationName = station.station_name || 'Unnamed Station';
        const stationId = station.station_id || '';
        const stationCode = station.station_code ? ` (${station.station_code})` : '';
        
        // Format coordinates for display
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
        
        tableBody.appendChild(row);
    });
}

// Function to generate mock stations (simplified version)
function generateMockStations(type, count) {
    const stations = [];
    
    for (let i = 1; i <= count; i++) {
        const id = `${type.charAt(0).toUpperCase()}${String(i).padStart(5, '0')}`;
        stations.push({
            station_id: id,
            type: type,
            station_name: `${type.charAt(0).toUpperCase()}${type.slice(1)} Station ${i}`,
            station_code: `${type.substring(0, 3).toUpperCase()}-${i}`,
            latitude: 14.0 + (Math.random() * 2),
            longitude: 120.0 + (Math.random() * 2),
            region: 'Region IV-A - CALABARZON',
            province: 'Cavite',
            city: 'Tagaytay',
            barangay: `Barangay ${i % 5 + 1}`,
            ...(type === 'vertical' && { 
                elevation: 100 + (Math.random() * 1000),
                accuracy_class: ['1CM', '2CM', '3CM', '5CM', '10CM'][Math.floor(Math.random() * 5)]
            }),
            ...(type === 'horizontal' && { 
                ellipsoidal_height: 100 + (Math.random() * 1000)
            }),
            ...(type === 'gravity' && { 
                gravity_value: 978000 + (Math.random() * 1000)
            }),
            order: Math.floor(Math.random() * 4) + 1,
            date_established: '2023-01-01',
            date_last_updated: '2023-06-15'
        });
    }
    
    return stations;
} 