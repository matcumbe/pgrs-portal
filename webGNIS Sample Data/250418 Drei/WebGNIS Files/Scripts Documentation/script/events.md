# Events.js Documentation

## Overview
`events.js` provides event handling functionality for the WebGNIS application, including filter event listeners, GCP type event listeners, and global error handling.

## Functions

### `setupFilterEventListeners()`
Sets up event listeners for location filters to handle cascading updates.

**Behavior:**
- Adds change event listeners to region, province, city, and barangay dropdowns
- Implements cascading reset behavior (e.g., changing region resets province, city, and barangay)
- Adds change event listener to order filter
- Adds change event listener to accuracy filter if it exists
- Calls `applyFilters()` when any filter changes

### `setupGCPTypeEventListeners()`
Sets up event listeners for GCP Type radio buttons.

**Behavior:**
- Adds change event listeners to all GCP Type radio buttons
- When a type is selected:
  - Shows/hides the accuracy class container based on the selected type
  - Fetches stations of the selected type
- Handles errors appropriately

### `initializeDefaultSelection()`
Initializes the default GCP Type selection.

**Behavior:**
- Selects the vertical type radio button by default
- Fetches vertical stations
- Sets the visibility of the accuracy class container based on the default selection

### `setupGlobalErrorHandler()`
Sets up a global error handler for uncaught errors.

**Behavior:**
- Adds an error event listener to the window
- Logs the error to the console
- Displays the error message in the UI

### `initializeEventListeners()`
Initializes all event listeners.

**Behavior:**
- Sets up filter event listeners
- Sets up GCP Type event listeners
- Initializes default selection
- Sets up global error handler

## Module Exports
The following functions are exported for use in other modules:
- `setupFilterEventListeners`
- `setupGCPTypeEventListeners`
- `initializeDefaultSelection`
- `setupGlobalErrorHandler`
- `initializeEventListeners`

## Module Imports
- `showError` from `./utils.js`
- `fetchStationsByType` and `applyFilters` from `./stations.js` 