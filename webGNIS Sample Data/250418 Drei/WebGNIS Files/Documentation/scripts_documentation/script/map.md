# Map.js Documentation

## Overview
`map.js` provides map functionality for the WebGNIS application, including map initialization, marker creation, and updating the map based on station data.

## Constants
- `orderColors`: An object that maps order values to color codes for marker styling

## Global Variables
- `map`: The Leaflet map instance
- `markersLayer`: A Leaflet layer group for managing markers

## Functions

### `initializeMap()`
Initializes the Leaflet map with default settings.

**Behavior:**
- Creates a new Leaflet map centered on Metro Manila
- Adds the NAMRIA tile layer
- Initializes the markers layer group
- Returns the map instance
- Handles errors appropriately

### `createCustomIcon(color)`
Creates a custom marker icon with the specified color.

**Parameters:**
- `color` (string): The color code for the marker

**Behavior:**
- Creates a unique mask ID to avoid conflicts
- Returns a Leaflet divIcon with SVG markup
- Includes a pin shape with the specified color
- Handles errors by returning a default icon if custom creation fails

### `updateMap(stations)`
Updates the map with the provided stations.

**Parameters:**
- `stations` (array): An array of station objects to display on the map

**Behavior:**
- Clears existing markers
- Stores the stations globally
- Creates a marker for each station with appropriate styling
- Adds popup information to each marker
- Adjusts the map view to show all markers
- Handles errors appropriately

### `updateMapMarkers(points)`
Updates the map markers based on the provided points.

**Parameters:**
- `points` (array): An array of point objects to display on the map

**Behavior:**
- Clears existing markers
- Creates a marker for each point with appropriate styling
- Adds popup information to each marker
- Adjusts the map view to show all markers

## Module Exports
The following functions and constants are exported for use in other modules:
- `initializeMap`
- `updateMap`
- `updateMapMarkers`
- `orderColors` 