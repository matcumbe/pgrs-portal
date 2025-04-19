# GNIS System Architecture Documentation

## Overview

The Geodetic Network Information System (GNIS) is a web-based application for managing and viewing geodetic control points. The system follows a client-server architecture with a frontend built using modern web technologies and a backend API that provides data access and persistence.

## System Components

### 1. Frontend Components

#### 1.1 Public Interface
- **Home Page** (`index.html`): Landing page with introduction to the system.
- **Explorer Interface** (`home.html`): Map-based interface for viewing and filtering geodetic control points.
- **Tracker**: Component for tracking selected points (embedded in Explorer).

#### 1.2 Administrative Interface
- **Admin Panel** (`admin.html`): Interface for managing station data (CRUD operations).
- **Authentication**: Simple login system to restrict access to admin functionalities.

### 2. Backend Components

#### 2.1 API Endpoints
- **Public API** (`api.php`): Endpoints for public access to geodetic data.
- **Admin API** (`gcp_admin_api.php`): Secured endpoints for administrative operations.

#### 2.2 Database
- **MySQL Database**: Stores all geodetic control point data and related metadata.
- **Tables**: Separate tables for different types of stations (vertical, horizontal, gravity).

## Architectural Patterns

### 1. Client-Side Architecture

The frontend follows a component-based architecture with these key features:

#### 1.1 Component Structure
- **Shared Layout Components**: Navigation bar, footer.
- **Map Component**: Uses Leaflet.js to render an interactive map.
- **Filter Panel**: UI for applying various filters to the data.
- **Results Panel**: Displays search results in a tabular format.
- **Station Detail Panel**: Shows detailed information about selected stations.
- **Admin Form**: Tabbed interface for station data entry/editing.

#### 1.2 Data Flow
- Client-side data filtering and manipulation.
- Dynamic UI updates without full page reloads.
- Real-time search on the client side.
- Form data validation before submission.

### 2. Server-Side Architecture

The server-side follows a simple API-based architecture:

#### 2.1 API Design
- RESTful endpoints for data operations.
- JSON response format.
- Parameterized queries for filtering data.
- Simple error handling with appropriate HTTP status codes.

#### 2.2 Authentication
- Basic username/password authentication for administrative functions.
- Session-based authorization (maintained in browser's local storage).

## Key Implementation Aspects

### 1. Form Field Visibility Management

The admin interface dynamically shows/hides form fields based on the selected station type:

```javascript
function updateFormFieldsVisibility(type) {
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
    
    // Handle tab selection logic
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab && activeTab.parentElement.style.display === 'none') {
        // Default to common tab if active tab is now hidden
        const commonTab = document.getElementById('common-tab');
        if (commonTab) {
            const tab = new bootstrap.Tab(commonTab);
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
```

### 2. Accuracy Class Special Handling

The system incorporates special handling for the accuracy_class field to accommodate non-standard values:

```javascript
// Handle accuracy class with special logic
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
```

### 3. DMS Coordinate Handling

The system handles both decimal and DMS (Degrees, Minutes, Seconds) coordinate formats:

```javascript
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
```

### 4. Tabbed Form Interface

The admin form uses a Bootstrap tabbed interface to organize fields by category:

```html
<ul class="nav nav-tabs mb-4" id="stationFormTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="common-tab" data-bs-toggle="tab" data-bs-target="#common-fields" type="button" role="tab" aria-controls="common-fields" aria-selected="true">Common</button>
    </li>
    <li class="nav-item vertical-type-tab" role="presentation">
        <button class="nav-link" id="vertical-tab" data-bs-toggle="tab" data-bs-target="#vertical-fields" type="button" role="tab" aria-controls="vertical-fields" aria-selected="false">Vertical</button>
    </li>
    <li class="nav-item horizontal-type-tab" role="presentation">
        <button class="nav-link" id="horizontal-tab" data-bs-toggle="tab" data-bs-target="#horizontal-fields" type="button" role="tab" aria-controls="horizontal-fields" aria-selected="false">Horizontal</button>
    </li>
    <li class="nav-item gravity-type-tab" role="presentation">
        <button class="nav-link" id="gravity-tab" data-bs-toggle="tab" data-bs-target="#gravity-fields" type="button" role="tab" aria-controls="gravity-fields" aria-selected="false">Gravity</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="location-tab" data-bs-toggle="tab" data-bs-target="#location-fields" type="button" role="tab" aria-controls="location-fields" aria-selected="false">Location</button>
    </li>
</ul>
```

## Technology Stack

### Frontend
- **HTML5**: Structure and content.
- **CSS3/Bootstrap 5.3.2**: Styling and responsive design.
- **JavaScript (ES6+)**: Client-side logic and interactions.
- **Leaflet.js 1.9.4**: Interactive map capabilities.
- **Bootstrap Icons 1.11.1/Font Awesome 5.15.4**: Icon library.

### Backend
- **PHP**: Server-side logic and API endpoints.
- **MySQL**: Relational database for data storage.

### Development & Deployment
- **Version Control**: Git (GitHub).
- **Hosting**: Standard web hosting with PHP and MySQL support.
- **Testing**: Manual testing processes.

## Data Flow

1. **Read Flow**
   - User requests geodetic data via UI.
   - Frontend makes API request to backend.
   - Backend queries database and returns JSON data.
   - Frontend renders data on map and in tables.

2. **Write Flow** (Admin Only)
   - Admin submits form data.
   - Frontend validates data and sends API request.
   - Backend validates data server-side.
   - Backend updates database and returns success/error response.
   - Frontend updates UI based on response.

## Security Considerations

1. **Authentication**
   - Basic username/password authentication for admin functions.
   - Session management via local storage.

2. **Input Validation**
   - Client-side validation for form inputs.
   - Server-side validation for all incoming data.

3. **Future Enhancements**
   - Transition to JWT-based authentication.
   - Role-based access control.
   - HTTPS implementation.
   - API request rate limiting.

## Last Updated
April 20, 2025 