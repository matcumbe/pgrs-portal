# WebGNIS GCP Admin API Documentation

## Overview
The `gcp_admin_api.php` file provides administrative functionality for managing geodetic control points (GCP) in the WebGNIS application. It extends the capabilities of the main API with CRUD operations for stations and location data management. This API is designed for administrative use and includes enhanced error handling and logging.

## Configuration and Setup
- Requires `config.php` for database connection details
- Sets up error logging to `php_errors.log`
- Configures custom error and exception handlers to return JSON responses
- Establishes database connection using mysqli

## Request Handling
- Supports multiple HTTP methods (GET, POST, PUT, DELETE)
- Uses a path parameter to determine the endpoint
- Supports query parameters for filtering
- Handles JSON request bodies for POST and PUT methods

## Endpoints

### 1. Get Stations by Type
- **Path**: `/api/admin/stations/{type}`
- **Method**: GET
- **Parameters**: 
  - `type`: Type of station (vertical, horizontal, gravity)
- **Response**: JSON array of stations matching the specified type
- **Function**: `getStationsByType($type)`

### 2. Get Station by ID
- **Path**: `/api/admin/station/{id}`
- **Method**: GET
- **Parameters**: 
  - `id`: Station ID
- **Response**: JSON object containing station details
- **Function**: `getStationById($id)`

### 3. Create Station
- **Path**: `/api/admin/station`
- **Method**: POST
- **Request Body**: Station data
- **Response**: JSON object containing created station ID
- **Function**: `createStation($data)`

### 4. Update Station
- **Path**: `/api/admin/station/{id}`
- **Method**: PUT
- **Parameters**: 
  - `id`: Station ID
- **Request Body**: Station data to update
- **Response**: JSON object containing update status
- **Function**: `updateStation($id, $data)`

### 5. Delete Station
- **Path**: `/api/admin/station/{id}`
- **Method**: DELETE
- **Parameters**: 
  - `id`: Station ID
- **Response**: JSON object containing deletion status
- **Function**: `deleteStation($id)`

### 6. Get Regions
- **Path**: `/api/admin/regions`
- **Method**: GET
- **Response**: JSON array of regions
- **Function**: `getRegions()`

### 7. Get Provinces
- **Path**: `/api/admin/provinces`
- **Method**: GET
- **Parameters**: 
  - `region` (optional): Filter by region
- **Response**: JSON array of provinces
- **Function**: `getProvinces($region)`

### 8. Get Cities
- **Path**: `/api/admin/cities`
- **Method**: GET
- **Parameters**: 
  - `province` (optional): Filter by province
- **Response**: JSON array of cities
- **Function**: `getCities($province)`

### 9. Get Barangays
- **Path**: `/api/admin/barangays`
- **Method**: GET
- **Parameters**: 
  - `city` (optional): Filter by city
- **Response**: JSON array of barangays
- **Function**: `getBarangays($city)`

## Helper Functions

### Response Functions
- `sendError($message, $code = 400)`: Sends error response with specified message and HTTP code
- `sendSuccess($data)`: Sends success response with data

### Station Management Functions
- `getStationsByType($type)`: Retrieves stations by type (vertical, horizontal, gravity)
- `getStationById($id)`: Retrieves a specific station by ID
- `createStation($data)`: Creates a new station
- `updateStation($id, $data)`: Updates an existing station
- `deleteStation($id)`: Deletes a station
- `deleteAssociatedMeasurements($stationId, $stationTable)`: Deletes measurements associated with a station
- `generateStationId($type)`: Generates a unique station ID based on type

### Location Data Functions
- `getRegions()`: Retrieves all regions
- `getProvinces($region)`: Retrieves provinces, optionally filtered by region
- `getCities($province)`: Retrieves cities, optionally filtered by province
- `getBarangays($city)`: Retrieves barangays, optionally filtered by city

## Error Handling
- Custom error handler converts PHP errors to JSON responses
- Custom exception handler converts exceptions to JSON responses
- Database connection errors are caught and returned as JSON
- Each function includes comprehensive error handling for database operations
- Detailed error logging for debugging purposes

## Database Interaction
- Uses mysqli for database operations
- Implements prepared statements for secure queries
- Handles multiple tables: vgcp_stations, hgcp_stations, grav_stations
- Supports dynamic SQL generation based on provided fields
- Implements cascading deletes for related data

## Security Considerations
- Uses prepared statements to prevent SQL injection
- Validates input data before processing
- Returns appropriate HTTP status codes for different scenarios
- Implements proper error handling to prevent information disclosure

## Administrative Features
- Supports CRUD operations for all station types
- Provides location data management
- Implements station ID generation
- Handles cascading deletes for related measurements
- Includes detailed logging for administrative actions 