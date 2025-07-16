# Utils.js Documentation

## Overview
`utils.js` provides utility functions for the WebGNIS application, including error handling, API requests, and performance optimization functions.

## Functions

### `logError(location, error)`
Logs an error to the console with location information and stack trace.

**Parameters:**
- `location` (string): The location or function where the error occurred
- `error` (Error): The error object or message

**Behavior:**
- Logs the error to the console with location context
- Adds a stack trace for debugging

### `showError(message, error)`
Displays an error message in the UI.

**Parameters:**
- `message` (string): The main error message to display
- `error` (Error, optional): Additional error details

**Behavior:**
- Finds the error container element
- Creates an alert with the error message
- If additional error details are provided, adds them below the main message
- Makes the error container visible
- Logs the error to the console

### `clearError()`
Clears any displayed error messages.

**Behavior:**
- Finds the error container element
- Clears its content
- Hides the error container

### `toggleLoading(show)`
Shows or hides the loading indicator.

**Parameters:**
- `show` (boolean): Whether to show or hide the loading indicator

**Behavior:**
- Finds the loading indicator element
- Toggles its visibility based on the show parameter

### `debounce(func, wait)`
Creates a debounced version of a function to limit how often it can be called.

**Parameters:**
- `func` (Function): The function to debounce
- `wait` (number): The delay in milliseconds

**Behavior:**
- Returns a new function that will only execute after the specified delay
- If called again before the delay expires, resets the timer
- Useful for limiting API calls or expensive operations during rapid user input

### `throttle(func, limit)`
Creates a throttled version of a function to limit how often it can be called.

**Parameters:**
- `func` (Function): The function to throttle
- `limit` (number): The minimum time between executions in milliseconds

**Behavior:**
- Returns a new function that will only execute once within the specified time limit
- If called again before the limit expires, the call is ignored
- Useful for limiting how often a function can be called during continuous events

### `apiRequest(endpoint, params)`
Makes an API request with error handling and loading state management.

**Parameters:**
- `endpoint` (string): The API endpoint to request
- `params` (object, optional): Query parameters to include in the request

**Behavior:**
- Shows the loading indicator
- Clears any existing errors
- Constructs the URL with the endpoint and parameters
- Makes a fetch request to the API
- Parses the JSON response
- Handles errors appropriately
- Hides the loading indicator when done
- Returns the response data

### `logFilterValues()`
Logs the current values of all filters to the console for debugging.

**Behavior:**
- Gets the current values of all filter elements
- Logs them to the console in a structured format

## Module Exports
The following functions are exported for use in other modules:
- `logError`
- `showError`
- `clearError`
- `toggleLoading`
- `debounce`
- `throttle`
- `apiRequest`
- `logFilterValues` 