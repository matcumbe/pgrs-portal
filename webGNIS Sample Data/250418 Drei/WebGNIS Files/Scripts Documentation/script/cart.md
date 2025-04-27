# Cart.js Documentation

## Overview
`cart.js` provides functionality for managing selected points (stations) in the WebGNIS application. It handles adding and removing stations from a cart-like interface, maintaining a list of selected points, and updating the UI accordingly.

## Global Variables
- `window.selectedPointsList`: An array that stores the list of selected stations with their IDs and names.

## Functions

### `directAddToSelected(stationId, stationName)`
Adds a station to the selected points list if it's not already present.

**Parameters:**
- `stationId` (string): The unique identifier of the station
- `stationName` (string): The name of the station

**Behavior:**
- Checks if the station is already in the list
- If not, adds it to the list and updates the UI
- Logs the action to the console

### `removeFromSelected(stationId)`
Removes a station from the selected points list.

**Parameters:**
- `stationId` (string): The unique identifier of the station to remove

**Behavior:**
- Finds and removes the station from the list
- Updates the UI
- Logs the action to the console

### `updateSelectedPointsTable()`
Updates the selected points table in the UI to reflect the current state of the selected points list.

**Behavior:**
- Clears the current table
- If the list is empty, does nothing
- Otherwise, adds a row for each selected point with a remove button

### `addToCart(stationName)`
Alternative UI method to add a station to the cart by name.

**Parameters:**
- `stationName` (string): The name of the station to add

**Behavior:**
- Checks if the station is already in the cart
- If not, adds a new row to the selected points table

### `removeFromCart(button)`
Removes a station from the cart using the UI button.

**Parameters:**
- `button` (HTMLElement): The button element that triggered the removal

**Behavior:**
- Gets the station name from the row
- Removes the station from the selectedPointsList if present
- Removes the row from the table

## Event Listeners
- `DOMContentLoaded`: Initializes the cart by updating the selected points table when the page loads

## Global Exports
The following functions are exposed to the global scope for use in inline event handlers:
- `directAddToSelected`
- `removeFromSelected`
- `addToCart`
- `removeFromCart`
- `updateSelectedPointsTable`

## Module Exports
When used as a module, the following functions are exported:
- `directAddToSelected`
- `removeFromSelected`
- `updateSelectedPointsTable`
- `addToCart`
- `removeFromCart` 