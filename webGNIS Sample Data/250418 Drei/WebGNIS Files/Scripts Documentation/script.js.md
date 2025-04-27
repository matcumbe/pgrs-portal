# script.js Documentation

## Overview
The `script.js` file is the main JavaScript module for the WebGNIS public interface. It provides functionality for viewing and searching geodetic control points (GCPs) on an interactive map. The module handles map initialization, data fetching, filtering, and UI interactions. This file serves as the core of the public-facing interface, allowing users to explore, search, and select geodetic control points for various applications.

## Map Initialization and Management

### `initializeApplication()`
- **Purpose**: Initializes the main application
- **Functionality**: 
  - Sets up the map with default configuration
  - Initializes global variables for data storage
  - Creates marker layer group for station markers
  - Sets up event listeners for user interactions
  - Configures initial map view centered on Metro Manila
  - Initializes search functionality
  - Sets up filter controls
  - Loads initial data based on default station type

### `initializeMap()`
- **Purpose**: Initializes the Leaflet map
- **Functionality**: 
  - Creates map instance with default view coordinates
  - Sets default zoom level
  - Adds OpenStreetMap tile layer
  - Configures map controls (zoom, attribution)
  - Sets up map event listeners
- **Returns**: Map instance
- **Configuration**: Uses constants for default latitude, longitude, and zoom level

### `createCustomIcon(color)`
- **Purpose**: Creates custom marker icons for different station orders
- **Parameters**: `color` - Color code for the marker
- **Returns**: Leaflet divIcon with custom SVG
- **Functionality**: 
  - Generates unique mask ID to avoid conflicts
  - Creates SVG marker with specified color
  - Configures icon size and anchor points
  - Sets up popup anchor for proper positioning
- **Error Handling**: 
  - Includes try/catch block for SVG generation
  - Falls back to default icon if custom icon creation fails
  - Logs errors for debugging

### `updateMap(stations)`
- **Purpose**: Updates map markers with station data
- **Parameters**: `stations` - Array of station objects
- **Functionality**: 
  - Clears existing markers from the map
  - Creates new markers for each station with appropriate colors
  - Adds popups with station information
  - Adds "Add to Selected" button to popups
  - Extends map bounds to include all stations
  - Adjusts map view to show all markers
  - Handles stations with missing coordinates
- **Error Handling**: 
  - Catches and logs errors during marker creation
  - Shows error message if map update fails
  - Continues processing remaining stations if one fails

## Data Management

### `apiRequest(endpoint, params = {})`
- **Purpose**: Makes API requests with error handling
- **Parameters**:
  - `endpoint` - API endpoint to call
  - `params` - Query parameters (default: empty object)
- **Returns**: Promise resolving to API response data
- **Functionality**: 
  - Constructs API URL with endpoint and parameters
  - Shows loading indicator during request
  - Clears any existing error messages
  - Makes fetch request to API
  - Processes JSON response
  - Validates response status and data
- **Error Handling**: 
  - Shows error message for failed requests
  - Logs detailed error information
  - Handles network errors gracefully
  - Provides user-friendly error messages

### `fetchStationsByType(type)`
- **Purpose**: Fetches stations of a specific type
- **Parameters**: `type` - Station type ('vertical', 'horizontal', or 'gravity')
- **Functionality**: 
  - Makes API request to get stations of specified type
  - Updates global stations array with fetched data
  - Applies current filters to the fetched data
  - Updates map and search results with filtered data
  - Updates filter options based on available data
- **Error Handling**: 
  - Logs errors if fetch fails
  - Continues with empty data set if fetch fails

### `applyFilters()`
- **Purpose**: Applies filters to station data
- **Functionality**: 
  - Retrieves filter values from UI elements
  - Filters stations based on:
    - Station type (vertical, horizontal, gravity)
    - Order (station order)
    - Accuracy (for vertical stations)
    - Location (region, province, city, barangay)
  - Updates map with filtered stations
  - Updates search results table with filtered stations
  - Updates pagination if applicable
  - Logs filter application for debugging

### `updateFiltersBasedOnData(stations)`
- **Purpose**: Updates filter options based on available data
- **Parameters**: `stations` - Array of station objects
- **Functionality**: 
  - Extracts unique values for each filter from the data
  - Populates filter dropdowns with unique values
  - Preserves current selections where possible
  - Updates order filter based on station type
  - Updates accuracy filter for vertical stations
  - Updates location filters (region, province, city, barangay)
  - Handles empty data sets

## UI Management

### `updateSearchResults(stations)`
- **Purpose**: Updates the search results table
- **Parameters**: `stations` - Array of station objects
- **Functionality**: 
  - Clears existing table rows
  - Creates table rows for each station
  - Populates cells with station data
  - Adds "Add to Selected" button to each row
  - Updates column headers based on station type
  - Handles empty data sets
  - Updates pagination if applicable
- **Error Handling**: 
  - Catches and logs errors during table update
  - Shows error message if table update fails

### `toggleLoading(show)`
- **Purpose**: Shows/hides loading indicator
- **Parameters**: `show` - Boolean indicating whether to show loading indicator
- **Functionality**: 
  - Shows/hides loading spinner
  - Disables/enables interactive elements during loading
  - Provides visual feedback during API requests

### `showError(message, error = null)`
- **Purpose**: Displays error message
- **Parameters**:
  - `message` - Error message to display
  - `error` - Optional error object for additional details
- **Functionality**: 
  - Creates error alert element
  - Displays error message in UI
  - Adds error details if provided
  - Adds dismiss button to alert
  - Logs error to console for debugging
  - Automatically hides error after 5 seconds

### `clearError()`
- **Purpose**: Clears error messages
- **Functionality**: 
  - Removes error messages from the UI
  - Hides error container
  - Resets error state

## Selected Points Management

### `directAddToSelected(stationId, stationName)`
- **Purpose**: Adds a station to the selected points list
- **Parameters**:
  - `stationId` - ID of the station to add
  - `stationName` - Name of the station
- **Functionality**: 
  - Checks if station is already in the selected list
  - Adds station to global selected points list if not already present
  - Updates selected points table
  - Logs addition for debugging
- **Global Impact**: Modifies `window.selectedPointsList` array

### `removeFromSelected(stationId)`
- **Purpose**: Removes a station from the selected points list
- **Parameters**: `stationId` - ID of the station to remove
- **Functionality**: 
  - Finds and removes station from global selected points list
  - Updates selected points table
  - Logs removal for debugging
- **Global Impact**: Modifies `window.selectedPointsList` array

### `updateSelectedPointsTable()`
- **Purpose**: Updates the selected points table
- **Functionality**: 
  - Clears existing table rows
  - Creates table rows for each selected point
  - Adds remove button to each row
  - Handles empty selection list
  - Updates UI to reflect current selections

## Search Functionality

### `setupSearchListener()`
- **Purpose**: Sets up search input event listener
- **Functionality**: 
  - Attaches input event listener to search input
  - Uses debounce to limit function calls
  - Filters table and map based on search term
  - Provides real-time search results

### `filterTableAndMap(searchTerm)`
- **Purpose**: Filters table and map based on search term
- **Parameters**: `searchTerm` - Search term to filter by
- **Functionality**: 
  - Normalizes search term (lowercase, trim)
  - Filters stations based on name
  - Updates table with filtered stations
  - Updates map markers to show only filtered stations
  - Handles empty search term (shows all stations)
  - Logs search results for debugging

### `updateTable(points)`
- **Purpose**: Updates the stations table with filtered data
- **Parameters**: `points` - Array of filtered station objects
- **Functionality**: 
  - Clears existing table rows
  - Creates table rows for each point
  - Populates cells with point data
  - Adds "Add to Selected" button to each row
  - Handles empty data sets

### `updateMapMarkers(points)`
- **Purpose**: Updates map markers based on filtered data
- **Parameters**: `points` - Array of filtered station objects
- **Functionality**: 
  - Clears existing markers from the map
  - Creates new markers for each point
  - Adds popups with point information
  - Adds "Add to Selected" button to popups
  - Adjusts map view to show all markers
  - Handles empty data sets

## Utility Functions

### `debounce(func, wait)`
- **Purpose**: Debounces function calls for performance
- **Parameters**:
  - `func` - Function to debounce
  - `wait` - Delay in milliseconds
- **Returns**: Debounced function
- **Functionality**: 
  - Limits function execution frequency
  - Cancels pending executions when called again within delay
  - Improves performance for frequently called functions
  - Used for search input and filter changes

### `throttle(func, limit)`
- **Purpose**: Throttles function calls for performance
- **Parameters**:
  - `func` - Function to throttle
  - `limit` - Time limit in milliseconds
- **Returns**: Throttled function
- **Functionality**: 
  - Limits function execution to once per time limit
  - Ignores additional calls within the time limit
  - Improves performance for frequently called functions
  - Used for map updates and scroll events

### `logError(location, error)`
- **Purpose**: Logs errors with location information
- **Parameters**:
  - `location` - Location where error occurred
  - `error` - Error object
- **Functionality**: 
  - Logs error to console with location context
  - Adds stack trace for debugging
  - Provides consistent error logging format
  - Helps identify error sources

## Event Listeners

### Location Filter Event Listeners
- **Region dropdown change event**:
  - Resets province, city, and barangay selections
  - Updates province dropdown with regions for selected region
  - Applies filters to update results
- **Province dropdown change event**:
  - Resets city and barangay selections
  - Updates city dropdown with cities for selected province
  - Applies filters to update results
- **City dropdown change event**:
  - Resets barangay selection
  - Updates barangay dropdown with barangays for selected city
  - Applies filters to update results
- **Barangay dropdown change event**:
  - Applies filters to update results

### GCP Type Radio Button Event Listeners
- **Vertical type radio button change event**:
  - Shows accuracy class filter
  - Fetches vertical stations
  - Updates UI for vertical station type
- **Horizontal type radio button change event**:
  - Hides accuracy class filter
  - Fetches horizontal stations
  - Updates UI for horizontal station type
- **Gravity type radio button change event**:
  - Hides accuracy class filter
  - Fetches gravity stations
  - Updates UI for gravity station type

### Filter Event Listeners
- **Order filter change event**:
  - Applies filters to update results
- **Accuracy filter change event**:
  - Applies filters to update results
- **Region filter change event**:
  - Applies filters to update results
- **Province filter change event**:
  - Applies filters to update results
- **City filter change event**:
  - Applies filters to update results
- **Barangay filter change event**:
  - Applies filters to update results

## Global Variables
- `allStations` - Array of all stations fetched from API
- `selectedPointsList` - Array of selected stations for certificate requests
- `currentStationType` - Currently selected station type ('vertical', 'horizontal', or 'gravity')
- `markersLayer` - Leaflet layer group for station markers
- `orderColors` - Object mapping order values to colors for markers
- `map` - Leaflet map instance
- `isLoading` - Boolean indicating if an API request is in progress
- `filteredStations` - Array of stations after applying filters

## Constants
- `DEFAULT_LATITUDE` - Default map center latitude (14.6)
- `DEFAULT_LONGITUDE` - Default map center longitude (121.0)
- `DEFAULT_ZOOM` - Default map zoom level (10)
- `MAX_ZOOM` - Maximum map zoom level (19)
- `MIN_ZOOM` - Minimum map zoom level (5)
- `ITEMS_PER_PAGE` - Number of items to display per page (10)
- `DEBOUNCE_DELAY` - Delay for debounced functions (300ms)
- `THROTTLE_LIMIT` - Time limit for throttled functions (100ms) 