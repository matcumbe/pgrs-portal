# admin.js Documentation

## Overview
The `admin.js` file is a comprehensive JavaScript module that powers the WebGNIS Admin Panel. It provides functionality for managing geodetic control points (GCPs) including vertical, horizontal, and gravity stations. The module handles authentication, data fetching, CRUD operations, filtering, and UI management. This file serves as the core of the administrative interface, allowing authorized users to manage the geodetic control point database.

## Authentication and Initialization

### `handleLogin(event)`
- **Purpose**: Handles user login form submission
- **Parameters**: `event` - Form submission event
- **Functionality**: 
  - Prevents default form submission
  - Retrieves username and password from form inputs
  - Makes API request to authenticate credentials
  - Stores authentication token in localStorage upon success
  - Initializes the admin interface upon successful login
  - Displays error message if authentication fails
- **Error Handling**: Shows error message for failed authentication attempts

### `handleLogout()`
- **Purpose**: Handles user logout
- **Functionality**: 
  - Clears authentication token from localStorage
  - Redirects to login page
  - Resets any global state variables
- **Security**: Ensures complete session termination

### `checkAuthState()`
- **Purpose**: Verifies if user is authenticated
- **Functionality**: 
  - Checks for valid authentication token in localStorage
  - Redirects to login page if not authenticated
  - Initializes admin interface if authenticated
- **Security**: Prevents unauthorized access to admin functions

### `initializeAdminInterface()`
- **Purpose**: Sets up the admin interface
- **Functionality**: 
  - Initializes UI components
  - Sets up event listeners
  - Loads initial data (station types, regions)
  - Configures default view settings
  - Sets up pagination and filtering

### `initializeEventListeners()`
- **Purpose**: Sets up event listeners for UI interactions
- **Functionality**: 
  - Attaches event handlers to buttons, forms, and other interactive elements
  - Sets up filter change listeners
  - Configures pagination controls
  - Initializes form submission handlers
  - Sets up location dropdown cascading behavior

## API Communication

### `apiRequest(endpoint, params = {}, method = 'GET', body = null)`
- **Purpose**: Central function for making API requests
- **Parameters**:
  - `endpoint` - API endpoint to call
  - `params` - Query parameters (default: empty object)
  - `method` - HTTP method (default: 'GET')
  - `body` - Request body for POST/PUT requests (default: null)
- **Returns**: Promise resolving to API response data
- **Functionality**:
  - Constructs API URL with endpoint and parameters
  - Adds authentication token to request headers
  - Handles different HTTP methods (GET, POST, PUT, DELETE)
  - Processes response data
  - Manages loading state during request
- **Error Handling**: 
  - Catches and processes API errors
  - Shows error messages for failed requests
  - Handles network errors gracefully
  - Provides detailed error information for debugging

### Location Data Fetching Functions

#### `fetchRegions()`
- **Purpose**: Retrieves all regions
- **Returns**: Promise resolving to regions data
- **Functionality**: 
  - Makes API request to get all regions
  - Updates regions dropdown with fetched data
  - Handles empty or error responses

#### `fetchProvinces(region = '')`
- **Purpose**: Retrieves provinces for a specific region
- **Parameters**: `region` - Region code or name (default: empty string)
- **Returns**: Promise resolving to provinces data
- **Functionality**: 
  - Makes API request to get provinces for the specified region
  - Updates provinces dropdown with fetched data
  - Resets child dropdowns (cities, barangays)

#### `fetchCities(province = '')`
- **Purpose**: Retrieves cities for a specific province
- **Parameters**: `province` - Province code or name (default: empty string)
- **Returns**: Promise resolving to cities data
- **Functionality**: 
  - Makes API request to get cities for the specified province
  - Updates cities dropdown with fetched data
  - Resets child dropdown (barangays)

#### `fetchBarangays(city = '')`
- **Purpose**: Retrieves barangays for a specific city
- **Parameters**: `city` - City code or name (default: empty string)
- **Returns**: Promise resolving to barangays data
- **Functionality**: 
  - Makes API request to get barangays for the specified city
  - Updates barangays dropdown with fetched data

### `getMockData(endpoint)`
- **Purpose**: Generates mock data for testing
- **Parameters**: `endpoint` - API endpoint to mock
- **Returns**: Mock data matching the expected API response format
- **Functionality**: 
  - Generates realistic mock data based on the requested endpoint
  - Provides consistent data structure for testing
  - Includes various data types (regions, provinces, stations)
  - Simulates API response format with status and data properties

## Station Management (CRUD Operations)

### `fetchStationsByType(type)`
- **Purpose**: Retrieves stations of a specific type
- **Parameters**: `type` - Station type ('vertical', 'horizontal', or 'gravity')
- **Functionality**: 
  - Makes API request to get stations of the specified type
  - Updates global stations array with fetched data
  - Applies current filters to the fetched data
  - Updates stations table with filtered data
  - Updates pagination controls
  - Updates filter options based on available data
- **Error Handling**: Shows error message if fetch fails

### `fetchStationById(id)`
- **Purpose**: Retrieves a specific station by ID
- **Parameters**: `id` - Station ID
- **Returns**: Promise resolving to station data
- **Functionality**: 
  - Makes API request to get station with the specified ID
  - Returns station data for editing or viewing
- **Error Handling**: Shows error message if fetch fails

### `fetchLocationData()`
- **Purpose**: Retrieves all location data (regions, provinces, cities, barangays)
- **Functionality**: 
  - Fetches regions data
  - Populates regions dropdown
  - Sets up event listeners for cascading dropdowns
- **Error Handling**: Shows error message if fetch fails

### `createStation(stationData)`
- **Purpose**: Creates a new station
- **Parameters**: `stationData` - Object containing station data
- **Returns**: Promise resolving to creation result
- **Functionality**: 
  - Validates station data
  - Makes API request to create new station
  - Refreshes stations list upon success
  - Shows success message
- **Error Handling**: Shows error message if creation fails

### `updateStation(id, stationData)`
- **Purpose**: Updates an existing station
- **Parameters**:
  - `id` - Station ID
  - `stationData` - Updated station data
- **Returns**: Promise resolving to update result
- **Functionality**: 
  - Validates updated station data
  - Makes API request to update station
  - Refreshes stations list upon success
  - Shows success message
- **Error Handling**: Shows error message if update fails

### `deleteStation()`
- **Purpose**: Deletes a station
- **Functionality**: 
  - Makes API request to delete the selected station
  - Removes station from global stations array
  - Updates stations table
  - Updates pagination controls
  - Shows success message
- **Error Handling**: Shows error message if deletion fails

## Location Management

### `populateLocationDropdowns()`
- **Purpose**: Populates all location dropdowns
- **Functionality**: 
  - Fetches regions data
  - Populates regions dropdown
  - Sets up event listeners for cascading dropdowns
  - Initializes province, city, and barangay dropdowns with empty options

### `populateDropdown(elementId, data, valueKey = 'name', textKey = 'name')`
- **Purpose**: Generic function to populate dropdown elements
- **Parameters**:
  - `elementId` - ID of the dropdown element
  - `data` - Array of data objects
  - `valueKey` - Property to use as value (default: 'name')
  - `textKey` - Property to use as display text (default: 'name')
- **Functionality**: 
  - Clears existing options
  - Adds default "Select" option
  - Populates dropdown with data from the provided array
  - Uses specified keys to extract values and display text
  - Preserves current selection if possible

### Location-specific Dropdown Population Functions

#### `populateProvinceDropdown(region)`
- **Purpose**: Populates province dropdown based on selected region
- **Parameters**: `region` - Selected region code or name
- **Functionality**: 
  - Fetches provinces for the selected region
  - Populates provinces dropdown
  - Resets child dropdowns (cities, barangays)

#### `populateCityDropdown(province)`
- **Purpose**: Populates city dropdown based on selected province
- **Parameters**: `province` - Selected province code or name
- **Functionality**: 
  - Fetches cities for the selected province
  - Populates cities dropdown
  - Resets child dropdown (barangays)

#### `populateBarangayDropdown(city)`
- **Purpose**: Populates barangay dropdown based on selected city
- **Parameters**: `city` - Selected city code or name
- **Functionality**: 
  - Fetches barangays for the selected city
  - Populates barangays dropdown

## Data Display and Filtering

### `updateStationsTable(stations)`
- **Purpose**: Updates the stations table with data
- **Parameters**: `stations` - Array of station objects
- **Functionality**: 
  - Clears existing table rows
  - Creates table rows for each station
  - Populates cells with station data
  - Adds action buttons (view, edit, delete)
  - Handles empty data sets
  - Updates pagination controls
  - Applies current filters

### `updatePagination()`
- **Purpose**: Updates pagination controls
- **Functionality**: 
  - Calculates total pages based on filtered stations and items per page
  - Creates page number buttons
  - Highlights current page
  - Adds previous/next buttons
  - Handles edge cases (first/last page)
  - Disables buttons when appropriate

### `applyFilters()`
- **Purpose**: Applies filters to station data
- **Functionality**: 
  - Retrieves filter values from UI elements
  - Filters stations based on:
    - Search term (station name)
    - Order (station order)
    - Accuracy (for vertical stations)
    - Location (region, province, city, barangay)
  - Updates stations table with filtered data
  - Updates pagination controls
  - Logs filter application for debugging

### `resetFilters()`
- **Purpose**: Resets all filters to default values
- **Functionality**: 
  - Clears all filter inputs
  - Resets dropdowns to default "Select" option
  - Refreshes data with no filters applied
  - Updates UI to reflect reset state

### `updateFiltersBasedOnData(stations)`
- **Purpose**: Updates filter options based on available data
- **Parameters**: `stations` - Array of station objects
- **Functionality**: 
  - Extracts unique values for each filter from the data
  - Populates filter dropdowns with unique values
  - Preserves current selections where possible
  - Handles empty data sets
  - Updates order and accuracy filters based on station type

### `populateFilterDropdown(id, values)`
- **Purpose**: Populates a filter dropdown with values
- **Parameters**:
  - `id` - ID of the dropdown element
  - `values` - Array of values to populate
- **Functionality**: 
  - Clears existing options
  - Adds default "Select" option
  - Normalizes and deduplicates values
  - Sorts values alphabetically
  - Preserves current selection if possible

## Form Handling

### `showAddForm()`
- **Purpose**: Shows the form for adding a new station
- **Functionality**: 
  - Resets form fields to default values
  - Shows the form container
  - Updates form title and submit button text
  - Sets up form submission handler for creation
  - Updates form fields visibility based on current station type
  - Initializes DMS conversion for coordinates

### `editStation(stationId)`
- **Purpose**: Initiates editing of a station
- **Parameters**: `stationId` - ID of the station to edit
- **Functionality**: 
  - Fetches station data by ID
  - Displays edit form with station data
  - Sets up form submission handler for updating
  - Updates form fields visibility based on station type
  - Initializes DMS conversion for coordinates

### `showEditForm(station)`
- **Purpose**: Displays the edit form with station data
- **Parameters**: `station` - Station object with data to populate form
- **Functionality**: 
  - Populates form fields with station data
  - Shows the form container
  - Updates form title and submit button text
  - Sets up form submission handler for updating
  - Updates form fields visibility based on station type
  - Initializes DMS conversion for coordinates
  - Handles different data formats (dates, coordinates)

### `hideForm()`
- **Purpose**: Hides the station form
- **Functionality**: 
  - Hides the form container
  - Resets form fields
  - Clears any validation errors
  - Returns to the main view

### `handleFormSubmit(event)`
- **Purpose**: Handles form submission for creating/updating stations
- **Parameters**: `event` - Form submission event
- **Functionality**: 
  - Prevents default form submission
  - Validates form data
  - Collects form data into an object
  - Determines if creating or updating
  - Calls appropriate API function
  - Shows loading state during request
  - Handles success and error responses
  - Refreshes data and UI upon success

### `updateFormFieldsVisibility(type)`
- **Purpose**: Updates form field visibility based on station type
- **Parameters**: `type` - Station type ('vertical', 'horizontal', or 'gravity')
- **Functionality**: 
  - Shows/hides fields relevant to the selected station type
  - Updates field labels and placeholders
  - Handles special fields for each station type
  - Manages required field indicators

## Coordinate System Handling

### `setupDMSConversion()`
- **Purpose**: Sets up event listeners for DMS coordinate conversion
- **Functionality**: 
  - Attaches event listeners to DMS input fields
  - Updates decimal coordinates when DMS values change
  - Handles validation of DMS values
  - Manages coordinate format display

### `updateDecimalCoordinates(type)`
- **Purpose**: Updates decimal coordinates from DMS values
- **Parameters**: `type` - Coordinate type ('lat' or 'lng')
- **Functionality**: 
  - Retrieves DMS values from form inputs
  - Validates DMS values
  - Converts DMS to decimal format
  - Updates decimal coordinate input
  - Handles special cases (negative values, invalid inputs)

### `setDMSFromDecimal(type, decimalValue)`
- **Purpose**: Sets DMS fields from decimal value
- **Parameters**:
  - `type` - Coordinate type ('lat' or 'lng')
  - `decimalValue` - Decimal coordinate value
- **Functionality**: 
  - Validates decimal value
  - Converts decimal to DMS format
  - Updates DMS form fields (degrees, minutes, seconds)
  - Handles special cases (negative values, invalid inputs)

## Station View Functions

### `viewStation(stationId)`
- **Purpose**: Displays detailed view of a station
- **Parameters**: `stationId` - ID of the station to view
- **Functionality**: 
  - Fetches station data by ID
  - Displays station details in a view panel
  - Formats data for display (coordinates, dates)
  - Shows/hides fields based on station type
  - Adds edit and delete buttons
  - Handles different data formats

### `hideViewPanel()`
- **Purpose**: Hides the station view panel
- **Functionality**: 
  - Hides the view panel container
  - Clears view panel content
  - Returns to the main view

### `confirmDeleteStation(stationId)`
- **Purpose**: Initiates station deletion process
- **Parameters**: `stationId` - ID of the station to delete
- **Functionality**: 
  - Stores station ID for deletion
  - Shows delete confirmation panel
  - Displays station name in confirmation message
  - Sets up confirmation and cancel buttons

### `showDeleteConfirmation()`
- **Purpose**: Shows the delete confirmation panel
- **Functionality**: 
  - Shows the confirmation panel container
  - Displays confirmation message with station name
  - Sets up confirmation and cancel buttons
  - Handles button click events

### `hideDeleteConfirmation()`
- **Purpose**: Hides the delete confirmation panel
- **Functionality**: 
  - Hides the confirmation panel container
  - Clears confirmation message
  - Returns to the previous view

## Utility Functions

### `decimalToDMS(decimal)`
- **Purpose**: Converts decimal coordinates to DMS string format
- **Parameters**: `decimal` - Decimal coordinate value
- **Returns**: Formatted DMS string (e.g., "12° 34' 56.789" N")
- **Functionality**: 
  - Converts decimal to degrees, minutes, seconds
  - Formats DMS string with appropriate symbols
  - Handles negative values (direction)
  - Rounds seconds to appropriate precision

### `formatDateForInput(dateString)`
- **Purpose**: Formats date string for input fields
- **Parameters**: `dateString` - Date string to format
- **Returns**: Formatted date string (YYYY-MM-DD)
- **Functionality**: 
  - Parses date string
  - Formats date as YYYY-MM-DD for input fields
  - Handles different date formats
  - Returns empty string for invalid dates

### `toggleLoading(show)`
- **Purpose**: Shows/hides loading indicator
- **Parameters**: `show` - Boolean indicating whether to show loading indicator
- **Functionality**: 
  - Shows/hides loading spinner
  - Disables/enables interactive elements during loading
  - Provides visual feedback during API requests

### `showError(message)`
- **Purpose**: Displays error message
- **Parameters**: `message` - Error message to display
- **Functionality**: 
  - Shows error message in UI
  - Logs error to console
  - Automatically hides message after 5 seconds
  - Handles multiple error messages

### `showSuccess(message)`
- **Purpose**: Displays success message
- **Parameters**: `message` - Success message to display
- **Functionality**: 
  - Shows success message in UI
  - Automatically hides message after 3 seconds
  - Handles multiple success messages

### `debounce(func, delay)`
- **Purpose**: Debounces function calls for performance
- **Parameters**:
  - `func` - Function to debounce
  - `delay` - Delay in milliseconds
- **Returns**: Debounced function
- **Functionality**: 
  - Limits function execution frequency
  - Cancels pending executions when called again within delay
  - Improves performance for frequently called functions
  - Used for search input and filter changes

## Station Type-Specific Features

### Vertical Stations
- **Elevation**: Height above sea level
- **Accuracy class**: Classification of vertical accuracy
- **Vertical datum**: Reference surface for elevation measurements
- **Elevation order**: Classification of vertical control point

### Horizontal Stations
- **Ellipsoidal height**: Height above reference ellipsoid
- **UTM coordinates**: 
  - Northing: Distance north from equator
  - Easting: Distance east from central meridian
  - Zone: UTM zone number
- **ITRF coordinates**: International Terrestrial Reference Frame coordinates
- **Horizontal order and datum**: Classification and reference system

### Gravity Stations
- **Gravity value**: Measured gravity value
- **Standard deviation**: Statistical measure of uncertainty
- **Gravity order and datum**: Classification and reference system
- **Gravity meter**: Instrument used for measurement

## Global Variables
- `currentStationType` - Currently selected station type ('vertical', 'horizontal', or 'gravity')
- `currentStations` - Array of currently displayed stations
- `selectedStation` - Currently selected station for editing/viewing
- `ITEMS_PER_PAGE` - Number of items to display per page (default: 10)
- `currentPage` - Current page number for pagination
- `filteredStations` - Array of stations after applying filters
- `isLoading` - Boolean indicating if an API request is in progress
- `map` - Leaflet map instance for station visualization
- `markers` - Array of map markers for stations 