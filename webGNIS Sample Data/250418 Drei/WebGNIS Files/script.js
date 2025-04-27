// script.js - WebGNIS legacy script
// This file now acts as a bridge to the modular structure in /js directory

// Import the main module
import './js/main.js';

// This file is kept for backward compatibility
// All functionality has been moved to modular files in the /js directory:
// 
// - main.js:    Main entry point and application initialization
// - utils.js:   Utility functions (logging, errors, debounce, etc.)
// - map.js:     Map functionality (markers, map updates)
// - stations.js: Station data handling and filtering
// - cart.js:    Selected points/cart functionality
// - search.js:  Search functionality (name search, radius search)
// - events.js:  Event listeners and UI interactions
//
// The application has been segmented for better maintainability.
// New features should be added to the appropriate module.