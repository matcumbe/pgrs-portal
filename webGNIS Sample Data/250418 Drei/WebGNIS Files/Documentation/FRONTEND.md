# GNIS Frontend Documentation

## Overview

The GNIS frontend is a web-based interface for interacting with the Geodetic Network Information System. It provides a user-friendly way to search, view, and analyze geodetic control point data, as well as an administrative interface for managing station data.

## Technologies Used

- HTML5
- CSS3 (Bootstrap 5.3.2)
- JavaScript (ES6+)
- Leaflet.js 1.9.4 (for map functionality)
- Font Awesome 5.15.4 (for icons)
- Bootstrap Icons 1.11.1 (for additional icons)

## User Interface Components

### 1. Navigation Bar
- Application logo (NAMRIA logo)
- Links (GNIS Home, Explorer, Tracker, Management, About Us)
- User menu (Admin Panel)
- Centered navigation with `mx-auto` class for proper alignment

### 2. Map Interface (Explorer)
- Interactive map using OpenStreetMap tiles
- Station markers with popups
- Map controls (zoom, fullscreen, etc.)
- Layer controls for different station types
- "Add to Cart" functionality for station selection
- Consistent green theme applied to interactive elements (buttons, popups)

### 3. Filter/Search Panel
- Station type selector with custom styled radio buttons
- Filter options:
  - Order
  - Accuracy class (shown/hidden based on station type)
  - Region
  - Province
  - City
  - Barangay
- Search radius control
- Coordinates input for pinpoint searches

### 4. Search Results Panel
- Inline Search Bar: Located in the header for quick filtering of results.
- Tabular view of search results
- Dynamic Elevation Column: Header and content change based on GCP type:
  - Vertical: "Elevation (m)" (uses `elevation` data)
  - Horizontal: "Ellip. Height (m)" (uses `ellipsoidal_height` data)
  - Gravity: "Gravity (mGal)" (uses `gravity_value` data)
- "Add to Cart" buttons for station selection

### 5. Selected Points Panel
- List of user-selected stations
- Remove functionality for individual stations

### 6. Admin Interface
- Login panel with username/password authentication
- Station type selector (Vertical, Horizontal, Gravity)
- Station management table with pagination
- CRUD operations (Add, Edit, View, Delete)
- Bootstrap tabbed form interface with sections:
  - Common fields
  - Type-specific fields (Vertical, Horizontal, Gravity)
  - Location fields
- Advanced form handling:
  - DMS (Degrees, Minutes, Seconds) input with automatic conversion
  - Type-specific fields visibility management
  - Dynamic accuracy class dropdown with option creation
  - Proper error handling and validation

## Key Features

### 1. Interactive Map
- Display of geodetic control points
- Custom marker icons for different station types
- Popup information on marker click
- Map bounds auto-adjustment
- Station selection directly from map

### 2. Advanced Search & Filtering
- Multi-criteria filtering via the filter panel
- Real-time, Client-Side Search: Search bar in the results panel filters the *currently displayed* table data and map markers instantly.
- Search is case-insensitive and format-insensitive (ignores spaces, hyphens, etc.).
- Radius-based search via the filter panel

### 3. Multiple Station Selection
- "Add to Cart" functionality from both map and search results
- Selected points management
- Individual station removal

### 4. Administrative Interface
- Complete CRUD operations for all station types
- Form with tabbed interface for better organization
- Type-specific form fields that show/hide based on selected station type
- Proper handling of coordinates in both decimal and DMS formats
- Special handling for accuracy class values from various data sources
- Dynamic location dropdown population (Region → Province → City → Barangay)
- Confirmation dialogs for delete operations

## User Interactions

### 1. Map Interactions
- Click on markers to view details
- Click "Add to Cart" in popup to select station
- Drag to pan
- Scroll to zoom
- Double-click to center

### 2. Search Interactions
- Type in the filter panel dropdowns to filter options
- Click to select filters in the filter panel
- Enter Lat/Lon/Radius and click search button in filter panel
- Type in the Search Results search bar to filter displayed results in real-time

### 3. Results Interactions
- Click rows to view details
- Click "Add to Cart" to select station

### 4. Selected Points Interactions
- View all selected stations
- Click "Remove" to remove individual stations

### 5. Admin Interactions
- Login with username/password
- Filter/search stations by various criteria
- Add new stations with the Add button
- Edit existing stations with the Edit button
- View station details with the View button
- Delete stations with the Delete button
- Navigate form sections using tabs
- Submit form data with validation

## Error Handling

### 1. User Feedback
- Loading indicators
- Error messages
- Success notifications
- Confirmation dialogs

### 2. Error Types
- Network errors
- API errors
- Validation errors
- Map errors
- Selection errors
- Form submission errors

## Performance Optimizations

### 1. Data Loading
- Lazy loading of markers
- Caching of frequent queries
- Debounced search input

### 2. Map Performance
- Viewport-based rendering
- Tile caching
- Smooth animations

### 3. UI Performance
- Optimized DOM updates
- CSS transitions
- Bootstrap components for responsive design

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Mobile Responsiveness

- Responsive design using Bootstrap grid
- Touch-friendly interactions
- Mobile-optimized map controls
- Adaptive layout for different screen sizes

## Implementation Notes

### 1. Form Field Visibility
The admin interface uses JavaScript to control the visibility of form fields based on the selected station type:
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
}
```

### 2. Accuracy Class Handling
The admin interface handles accuracy class values specially to accommodate values that might not be in the predefined dropdown:
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

## Last Updated
April 20, 2025 