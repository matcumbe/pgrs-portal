// main.js - Main entry point for the WebGNIS application
import { logError } from './utils.js';
import { initializeMap } from './map.js';
import { initializeEventListeners } from './events.js';
import { initializeSearch } from './search.js';

// Initialize application
function initializeApplication() {
    try {
        console.log('Initializing application...');
        
        // Initialize the map
        const map = initializeMap();
        
        // Initialize global variables
        window.allStations = [];
        window.selectedPointsList = [];
        window.currentStationType = '';
        window.allPoints = [];
        
        // Initialize event listeners
        initializeEventListeners();
        
        // Initialize search functionality
        initializeSearch();
        
        console.log('Initialization complete!');
    } catch (error) {
        logError('Application initialization', error);
    }
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        initializeApplication();
    } catch (error) {
        logError('Application initialization', error);
    }
});

// For use in inline HTML event handlers and legacy code
window.initializeApplication = initializeApplication; 