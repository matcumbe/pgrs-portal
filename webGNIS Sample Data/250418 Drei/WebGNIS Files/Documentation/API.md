# GNIS API Documentation

## Overview

The GNIS API provides endpoints for accessing and managing geodetic control point data. All endpoints return JSON responses and follow RESTful conventions.

## Base URL

```
/api.php?path=
```

## Authentication

Currently, the API does not require authentication. Future versions will implement JWT-based authentication.

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

## Endpoints

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

### 3. ~~Search Stations~~
~~GET /api/search~~
// Note: This endpoint might be deprecated or unused as search functionality is now primarily client-side.

**Query Parameters:**
- `type` (required): Station type
- `order` (optional): Order filter
- `accuracy` (optional): Accuracy class filter
- `region` (optional): Region filter
- `province` (optional): Province filter
- `city` (optional): City filter
- `barangay` (optional): Barangay filter

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

### 4. Search by Radius
```
GET /api/radius-search
// Note: Ensure path consistency. API might use `/api/stations/radius` as seen in script.js.
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

### 5. Get Regions
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

### 6. Get Provinces
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

### 7. Get Cities
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

### 8. Get Barangays
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

### 9. Get Orders
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

### 10. Get Accuracy Classes
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

### 11. Submit Selected Points (Future Implementation)
```
POST /api/selected-points
```

**Request Body:**
```json
{
    "station_ids": ["string", "string", ...],
    "user_info": {
        "name": "string",
        "email": "string",
        "organization": "string"
    },
    "purpose": "string"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "request_id": "string",
        "status": "string",
        "timestamp": "string"
    }
}
```

### 12. Get Selected Points Info (Future Implementation)
```
GET /api/selected-points-info
```

**Query Parameters:**
- `station_ids` (required): Comma-separated list of station IDs

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
            "accuracy_class": "string"
        }
    ]
}
```

## Error Codes

- **400 Bad Request:** Missing or invalid parameters
- **404 Not Found:** Endpoint or resource not found
- **500 Internal Server Error:** Server-side error

## Rate Limiting

(Not currently implemented, but planned for future versions)

## Changelog

- **April 19, 2025:** Noted potential deprecation of `/api/search` due to client-side search implementation. Reviewed other endpoints.
- **May 1, 2024:** Initial documentation.

## Last Updated
April 19, 2025

## API Usage Examples

### Example 1: Get Vertical Control Points
```
GET /api.php?path=/api/stations/vertical
```

### Example 2: Search by Region and Province
```
GET /api.php?path=/api/search?type=horizontal&region=Region%20IV-A&province=Cavite
```

### Example 3: Search by Radius
```
GET /api.php?path=/api/radius-search?lat=14.5995&lng=120.9842&radius=10
```

## Future Endpoints

1. User authentication and authorization
2. Data export endpoints (CSV, JSON, Shapefile)
3. Bulk operations on selected points
4. Custom filtering options

## Version History

- v1.0 (April 2024): Initial release
- v1.1 (May 2024): Added station selection and cart functionality

## Last Updated
May 1, 2024 