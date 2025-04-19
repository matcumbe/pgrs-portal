# GNIS API Documentation

## Overview

The GNIS API provides endpoints for accessing and managing geodetic control point data. All endpoints return JSON responses and follow RESTful conventions. The API is split into two main sections: the public API (`api.php`) and the admin API (`gcp_admin_api.php`).

## Base URLs

### Public API
```
/api.php?path=
```

### Admin API
```
/gcp_admin_api.php?path=
```

## Authentication

Currently, the public API does not require authentication. The admin API uses a simple username/password login system. Future versions will implement JWT-based authentication.

## Response Format

### Success Response
```json
{
    "success": true,
    "data": [...],
    "timestamp": "YYYY-MM-DD HH:MM:SS"
}
```

### Error Response
```json
{
    "error": "Error message",
    "details": "Additional error details",
    "timestamp": "YYYY-MM-DD HH:MM:SS"
}
```

## Public API Endpoints

### 1. Get Stations by Type
```
GET /api/stations/{type}
```

**Parameters:**
- `type` (required): Type of station (vertical, horizontal, gravity)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "station_id": "string",
            "station_name": "string",
            "latitude": "number",
            "longitude": "number",
            "elevation": "number",
            "order": "string",
            "accuracy_class": "string",
            "region": "string",
            "province": "string",
            "city": "string",
            "barangay": "string"
        }
    ]
}
```

### 2. Get Station by ID
```
GET /api/station/{id}
```

**Parameters:**
- `id` (required): Station ID

**Response:**
```json
{
    "success": true,
    "data": {
        "station_id": "string",
        "station_name": "string",
        "latitude": "number",
        "longitude": "number",
        "elevation": "number",
        "order": "string",
        "accuracy_class": "string",
        "region": "string",
        "province": "string",
        "city": "string",
        "barangay": "string"
    }
}
```

### 3. Search by Radius
```
GET /api/radius-search
```

**Query Parameters:**
- `lat` (required): Center latitude
- `lng` (required): Center longitude
- `radius` (required): Search radius in kilometers

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "station_id": "string",
            "station_name": "string",
            "latitude": "number",
            "longitude": "number",
            "elevation": "number",
            "distance": "number"
        }
    ]
}
```

### 4. Get Regions
```
GET /api/regions
```

**Response:**
```json
{
    "success": true,
    "data": [
        "Region 1",
        "Region 2",
        ...
    ]
}
```

### 5. Get Provinces
```
GET /api/provinces
```

**Query Parameters:**
- `region` (optional): Filter by region

**Response:**
```json
{
    "success": true,
    "data": [
        "Province 1",
        "Province 2",
        ...
    ]
}
```

### 6. Get Cities
```
GET /api/cities
```

**Query Parameters:**
- `province` (optional): Filter by province

**Response:**
```json
{
    "success": true,
    "data": [
        "City 1",
        "City 2",
        ...
    ]
}
```

### 7. Get Barangays
```
GET /api/barangays
```

**Query Parameters:**
- `city` (optional): Filter by city

**Response:**
```json
{
    "success": true,
    "data": [
        "Barangay 1",
        "Barangay 2",
        ...
    ]
}
```

### 8. Get Orders
```
GET /api/orders
```

**Query Parameters:**
- `type` (required): Station type

**Response:**
```json
{
    "success": true,
    "data": [
        "Order 1",
        "Order 2",
        ...
    ]
}
```

### 9. Get Accuracy Classes
```
GET /api/accuracy-classes
```

**Response:**
```json
{
    "success": true,
    "data": [
        "Class 1",
        "Class 2",
        ...
    ]
}
```

## Admin API Endpoints

### 1. Get Stations by Type (Admin)
```
GET /api/admin/stations/{type}
```

**Parameters:**
- `type` (required): Type of station (vertical, horizontal, gravity)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "station_id": "string",
            "station_name": "string",
            "latitude": "number",
            "longitude": "number",
            "elevation": "number",
            "order": "string",
            "accuracy_class": "string",
            "region": "string",
            "province": "string",
            "city": "string",
            "barangay": "string"
        }
    ]
}
```

### 2. Get Station by ID (Admin)
```
GET /api/admin/station/{id}
```

**Parameters:**
- `id` (required): Station ID

**Response:**
```json
{
    "success": true,
    "data": {
        "station_id": "string",
        "station_name": "string",
        "latitude": "number",
        "longitude": "number",
        "elevation": "number",
        "order": "string",
        "accuracy_class": "string",
        "region": "string",
        "province": "string",
        "city": "string",
        "barangay": "string"
    }
}
```

### 3. Create Station
```
POST /api/admin/station
```

**Request Body:**
```json
{
    "type": "string", // vertical, horizontal, gravity
    "station_name": "string",
    "station_code": "string",
    "latitude": "number",
    "longitude": "number",
    // Common fields
    "mark_type": "string",
    "mark_status": "string",
    "mark_const": "string",
    "authority": "string",
    "region": "string",
    "province": "string",
    "city": "string",
    "barangay": "string",
    // Type-specific fields (examples)
    "elevation": "number", // For vertical
    "accuracy_class": "string", // For vertical
    "ellipsoidal_height": "number", // For horizontal
    "gravity_value": "number", // For gravity
    // Other fields based on type...
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": "string"
    }
}
```

### 4. Update Station
```
PUT /api/admin/station/{id}
```

**Parameters:**
- `id` (required): Station ID

**Request Body:** Same as Create Station

**Response:**
```json
{
    "success": true,
    "data": {
        "id": "string",
        "affected_rows": "number"
    }
}
```

### 5. Delete Station
```
DELETE /api/admin/station/{id}
```

**Parameters:**
- `id` (required): Station ID

**Response:**
```json
{
    "success": true,
    "data": {
        "id": "string",
        "status": "deleted"
    }
}
```

### 6. Get Admin Regions
```
GET /api/admin/regions
```

**Response:**
```json
{
    "success": true,
    "data": [
        {"name": "Region 1"},
        {"name": "Region 2"},
        ...
    ]
}
```

### 7. Get Admin Provinces
```
GET /api/admin/provinces
```

**Query Parameters:**
- `region` (optional): Filter by region

**Response:**
```json
{
    "success": true,
    "data": [
        {"name": "Province 1"},
        {"name": "Province 2"},
        ...
    ]
}
```

### 8. Get Admin Cities
```
GET /api/admin/cities
```

**Query Parameters:**
- `province` (optional): Filter by province

**Response:**
```json
{
    "success": true,
    "data": [
        {"name": "City 1"},
        {"name": "City 2"},
        ...
    ]
}
```

### 9. Get Admin Barangays
```
GET /api/admin/barangays
```

**Query Parameters:**
- `city` (optional): Filter by city

**Response:**
```json
{
    "success": true,
    "data": [
        {"name": "Barangay 1"},
        {"name": "Barangay 2"},
        ...
    ]
}
```

## Error Codes

- **400 Bad Request:** Missing or invalid parameters
- **404 Not Found:** Endpoint or resource not found
- **500 Internal Server Error:** Server-side error

## API Implementation Notes

### Station Type Handling
The API handles three different types of stations, each with their own database table:
- `vertical`: Stored in the `vgcp_stations` table
- `horizontal`: Stored in the `hgcp_stations` table
- `gravity`: Stored in the `grav_stations` table

### Field Mappings
Different types of stations have different field requirements:

**Vertical Stations:**
- `elevation`: Required
- `accuracy_class`: Required
- `elevation_order`: Optional

**Horizontal Stations:**
- `ellipsoidal_height`: Required
- `horizontal_order`: Required
- `utm_northing`, `utm_easting`, `utm_zone`: Optional

**Gravity Stations:**
- `gravity_value`: Required
- `standard_deviation`: Optional
- `gravity_order`: Optional

## API Usage Examples

### Example 1: Get Vertical Control Points
```
GET /api.php?path=/api/stations/vertical
```

### Example 2: Create a New Horizontal Station (Admin)
```
POST /gcp_admin_api.php?path=/api/admin/station
```
```json
{
    "type": "horizontal",
    "station_name": "Test Horizontal Station",
    "latitude": 14.6590,
    "longitude": 121.0640,
    "ellipsoidal_height": 20.456,
    "horizontal_order": "2",
    "mark_type": "2"
}
```

### Example 3: Search by Radius
```
GET /api.php?path=/api/radius-search?lat=14.5995&lng=120.9842&radius=10
```

## Changelog

- **April 20, 2025:** Added documentation for admin API endpoints.
- **April 19, 2025:** Noted deprecation of `/api/search` due to client-side search implementation.
- **May 1, 2024:** Initial documentation.

## Last Updated
April 20, 2025 