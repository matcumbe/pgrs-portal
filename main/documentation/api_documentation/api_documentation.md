# WebGNIS API Documentation

## Overview
The `api.php` file serves as the main API endpoint for the WebGNIS application. It provides access to various geodetic control points (GCP) data including vertical, horizontal, and gravity stations. The API supports querying stations by type, ID, location, and performing radius-based searches.

## Configuration and Setup
- Requires `config.php` for database connection details
- Sets up error logging to `php_errors.log`
- Configures custom error and exception handlers to return JSON responses
- Establishes database connection using mysqli

## Request Handling
- Accepts requests via GET method
- Uses a path parameter to determine the endpoint
- Supports query parameters for filtering and search criteria

## Endpoints

### 1. Get Stations by Type
- **Path**: `/api/stations/{type}`
- **Method**: GET
- **Parameters**: 
  - `type`: Type of station (vertical, horizontal, gravity)
- **Response**: JSON array of stations matching the specified type
- **Function**: `getStationsByType($type)`

### 2. Get Station by ID
- **Path**: `/api/station/{id}`
- **Method**: GET
- **Parameters**: 
  - `id`: Station ID
- **Response**: JSON object containing station details
- **Function**: `getStationById($id)`

### 3. Get Provinces
- **Path**: `/api/provinces`
- **Method**: GET
- **Parameters**: 
  - `region` (optional): Filter by region
- **Response**: JSON array of provinces
- **Function**: `getProvinces($region)`

### 4. Get Cities
- **Path**: `/api/cities`
- **Method**: GET
- **Parameters**: 
  - `province` (optional): Filter by province
- **Response**: JSON array of cities
- **Function**: `getCities($province)`

### 5. Search Stations
- **Path**: `/api/search`
- **Method**: GET
- **Parameters**: Various search criteria (type, order, accuracy, region, province, city, barangay)
- **Response**: JSON array of stations matching search criteria
- **Function**: `searchStations($params)`

### 6. Get Orders
- **Path**: `/api/orders`
- **Method**: GET
- **Parameters**: 
  - `type` (optional): Station type
- **Response**: JSON array of orders
- **Function**: `getOrders($type)`

### 7. Get Accuracy Classes
- **Path**: `/api/accuracy-classes`
- **Method**: GET
- **Response**: JSON array of accuracy classes
- **Function**: `getAccuracyClasses()`

### 8. Get Barangays
- **Path**: `/api/barangays`
- **Method**: GET
- **Parameters**: 
  - `city` (optional): Filter by city
- **Response**: JSON array of barangays
- **Function**: `getUniqueBarangays($city)`

### 9. Get Regions
- **Path**: `/api/regions`
- **Method**: GET
- **Response**: JSON array of regions
- **Function**: `getUniqueRegions()`

### 10. Radius Search
- **Path**: `/api/radius-search`
- **Method**: GET
- **Parameters**: 
  - `lat`: Latitude
  - `lng`: Longitude
  - `radius`: Search radius in kilometers
- **Response**: JSON array of stations within the specified radius
- **Function**: `searchByRadius($params)`

## Helper Functions

### Response Functions
- `sendError($message, $code = 400)`: Sends error response with specified message and HTTP code
- `sendSuccess($data)`: Sends success response with data

### Data Retrieval Functions
- `getStationsByType($type)`: Retrieves stations by type (vertical, horizontal, gravity)
- `getStationById($id)`: Retrieves a specific station by ID
- `getProvinces($region)`: Retrieves provinces, optionally filtered by region
- `getCities($province)`: Retrieves cities, optionally filtered by province
- `getOrders($type)`: Retrieves orders based on station type
- `getAccuracyClasses()`: Retrieves accuracy classes (VGCP only)
- `getUniqueBarangays($city)`: Retrieves unique barangays, optionally filtered by city
- `getUniqueRegions()`: Retrieves unique regions from all station types
- `getUniqueProvinces($region)`: Retrieves unique provinces for a given region
- `getUniqueCities($province)`: Retrieves unique cities for a given province
- `searchStations($params)`: Searches stations based on various criteria
- `searchByRadius($params)`: Searches stations within a specified radius using the Haversine formula

## Error Handling
- Custom error handler converts PHP errors to JSON responses
- Custom exception handler converts exceptions to JSON responses
- Database connection errors are caught and returned as JSON
- Each function includes error handling for database operations

## Database Interaction
- Uses mysqli for database operations
- Implements prepared statements for secure queries
- Handles multiple tables: vgcp_stations, hgcp_stations, grav_stations
- Supports dynamic SQL generation based on search parameters

## Security Considerations
- Uses prepared statements to prevent SQL injection
- Validates input parameters before processing
- Returns appropriate HTTP status codes for different scenarios 