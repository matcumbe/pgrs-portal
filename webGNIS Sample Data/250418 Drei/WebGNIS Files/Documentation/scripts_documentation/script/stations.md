# Stations.js Documentation

## Overview
`stations.js` provides functionality for managing station data in the WebGNIS application. It handles fetching, filtering, and displaying station information, as well as updating the UI based on user selections.

## Global Variables
- `window.allStations`: An array that stores all stations fetched from the API
- `window.allPoints`: An array that stores all points for filtering and searching

## Functions

### `fetchStationsByType(type)`
Fetches stations of a specific type from the API.

**Parameters:**
- `type` (string): The type of stations to fetch (e.g., 'vertical', 'horizontal', 'gravity')

**Behavior:**
- Makes an API request to get stations of the specified type
- Stores the results in the global allStations variable
- Applies any active filters to the fetched data

### `updateSearchResults(stations)`
Updates the search results table with the provided stations.

**Parameters:**
- `stations` (array): An array of station objects to display

**Behavior:**
- Clears the current table
- Determines the current GCP type to set the appropriate column headers
- Creates a row for each station with its details
- Adds an "Add to Cart" button for each station
- Handles errors appropriately

### `applyFilters()`
Applies all active filters to the station data.

**Behavior:**
- Gets the current values of all filter elements
- Filters the stations based on the selected criteria:
  - GCP type
  - Order
  - Region
  - Province
  - City
  - Barangay
- Updates the map and search results with the filtered data
- Updates the filter dropdowns based on the available data

### `updateFiltersBasedOnData(stations)`
Updates the filter dropdowns based on the available station data.

**Parameters:**
- `stations` (array): The filtered stations to base the dropdown options on

**Behavior:**
- Extracts unique values for each filter from the station data
- Updates each dropdown with the appropriate options
- Preserves the current selection in each dropdown
- Implements cascading filters (e.g., provinces depend on selected region)

### `updateTable(points)`
Updates the search results table with the provided points.

**Parameters:**
- `points` (array): An array of point objects to display

**Behavior:**
- Clears the current table
- Creates a row for each point with its details
- Adds an "Add to Cart" button for each point

### `filterTableAndMap(searchTerm)`
Filters the table and map based on a search term.

**Parameters:**
- `searchTerm` (string): The term to search for in station names

**Behavior:**
- Filters the points based on the search term
- Updates the table with the filtered points
- Updates the map markers with the filtered points

## Module Exports
The following functions are exported for use in other modules:
- `fetchStationsByType`
- `updateSearchResults`
- `applyFilters`
- `updateFiltersBasedOnData`
- `updateTable`
- `filterTableAndMap` 