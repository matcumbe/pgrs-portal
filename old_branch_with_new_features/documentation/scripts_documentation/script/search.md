# Search.js Documentation

## Overview
`search.js` provides search functionality for the WebGNIS application, including real-time filtering of stations by name and radius-based searching.

## Functions

### `setupSearchListener()`
Sets up the real-time search functionality for station names.

**Behavior:**
- Finds the station name search input element
- Adds an input event listener with debouncing
- When the user types, filters the current stations based on the search term
- Updates the table and map with the filtered stations
- Handles empty search terms by showing all stations

### `setupRadiusSearch()`
Sets up the radius-based search functionality.

**Behavior:**
- Finds the latitude, longitude, and radius input elements
- Finds the search button
- Adds a click event listener to the button
- When clicked, validates the inputs and makes an API request
- Updates the search results with the response data
- Handles errors appropriately

### `initializeSearch()`
Initializes all search components.

**Behavior:**
- Sets up the search listener for real-time filtering
- Sets up the radius search functionality
- Removes the search button since real-time search is used instead

## Module Exports
The following functions are exported for use in other modules:
- `setupSearchListener`
- `setupRadiusSearch`
- `initializeSearch` 