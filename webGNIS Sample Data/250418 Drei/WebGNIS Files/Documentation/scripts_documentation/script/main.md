# Main.js Documentation

## Overview
`main.js` serves as the main entry point for the WebGNIS application. It initializes all components and sets up the application when the page loads.

## Functions

### `initializeApplication()`
Initializes all components of the application.

**Behavior:**
- Initializes the map
- Sets up global variables:
  - `window.allStations`: Array to store all stations
  - `window.selectedPointsList`: Array to store selected points
  - `window.currentStationType`: String to store the current station type
  - `window.allPoints`: Array to store all points
- Initializes event listeners
- Initializes search functionality
- Handles errors appropriately

## Event Listeners
- `DOMContentLoaded`: Calls `initializeApplication()` when the page is fully loaded

## Global Exports
- `initializeApplication`: Exposed to the global scope for use in inline HTML event handlers and legacy code

## Module Imports
- `logError` from `./utils.js`
- `initializeMap` from `./map.js`
- `initializeEventListeners` from `./events.js`
- `initializeSearch` from `./search.js` 