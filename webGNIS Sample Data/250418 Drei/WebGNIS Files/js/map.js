// map.js - Map functionality for WebGNIS application
import { logError, showError } from './utils.js';

// Define marker colors for different orders
const orderColors = {
    '0': '#FF0000',    // Red
    '1': '#0000FF',    // Blue
    '2': '#00FF00',    // Green
    '3': '#FFA500',    // Orange
    '4': '#800080',    // Purple
    '5': '#008080',    // Teal
    '6': '#FFD700',    // Gold
    '7': '#4B0082'     // Indigo
};

// Map initialization
let map;
let markersLayer;

// Initialize map with default view
function initializeMap() {
    try {
        console.log('Initializing map...');
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            throw new Error('Map element not found');
        }

        map = L.map('map').setView([14.6, 121.0], 10); // Centered on Metro Manila

        // Add OpenStreetMap tiles
        L.tileLayer('https://basemapserver.geoportal.gov.ph/tiles/v2/PGP/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.geoportal.gov.ph/">NAMRIA</a> contributors',
        }).addTo(map);

        // Initialize markers layer group
        markersLayer = L.layerGroup().addTo(map);
        
        return map;
    } catch (error) {
        logError('initializeMap', error);
        showError('Failed to initialize map: ' + error.message);
        throw error;
    }
}

// Create custom marker icons for different orders
function createCustomIcon(color) {
    try {
        // Unique mask ID to avoid conflicts if called multiple times quickly
        const maskId = `pinHoleMask-${Math.random().toString(36).substr(2, 9)}`;
        return L.divIcon({
            className: 'custom-marker',
            html: `<svg width="24" height="36" viewBox="0 0 24 36">
                <defs>
                    <mask id="${maskId}">
                        <rect width="100%" height="100%" fill="white"/>
                        <circle cx="12" cy="12" r="4" fill="black"/>
                    </mask>
                </defs>
                <path fill="${color}" d="M12 0C5.4 0 0 5.4 0 12c0 7.2 12 24 12 24s12-16.8 12-24c0-6.6-5.4-12-12-12z" mask="url(#${maskId})" stroke="#000000" stroke-width="1"/>
                <circle cx="12" cy="12" r="4" fill="transparent" stroke="#000000" stroke-width="1"/>
            </svg>`,
            iconSize: [24, 36],
            iconAnchor: [12, 36],
            popupAnchor: [0, -36]
        });
    } catch (error) {
        logError('createCustomIcon', error);
        return L.divIcon(); // Return default icon if custom one fails
    }
}

// Update map markers with colors
function updateMap(stations) {
    try {
        console.log(`Updating map with ${stations.length} stations`);
        
        // Clear existing markers
        if (markersLayer) {
            markersLayer.clearLayers();
        }

        if (!Array.isArray(stations) || stations.length === 0) {
            console.log('No stations to display');
            return;
        }

        // Store stations globally
        window.allStations = stations;

        // Initialize bounds
        const bounds = L.latLngBounds([]);
        let hasValidCoordinates = false;

        stations.forEach(station => {
            if (station.latitude && station.longitude) {
                let order = station.order || station.elevation_order || station.horizontal_order || '';
                const color = orderColors[order] || '#999999'; // Default gray for unknown order
                
                const marker = L.marker([station.latitude, station.longitude], {
                    icon: createCustomIcon(color)
                }).bindPopup(`
                    <strong>${station.station_name || ''}</strong><br>
                    Lat: ${station.latitude || ''}<br>
                    Long: ${station.longitude || ''}<br>
                    ${order ? `Order: ${order}<br>` : ''}
                    ${station.accuracy_class ? `Accuracy Class: ${station.accuracy_class}<br>` : ''}
                    <button onclick="directAddToSelected('${station.station_id}', '${station.station_name || ''}')" class="btn btn-sm btn-primary mt-2">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                    </button>
                `);
                
                markersLayer.addLayer(marker);
                bounds.extend([station.latitude, station.longitude]);
                hasValidCoordinates = true;
            }
        });

        // Only fit bounds if we have valid coordinates
        if (hasValidCoordinates && map) {
            map.fitBounds(bounds, { 
                padding: [50, 50],
                maxZoom: 15 // Prevent zooming in too close
            });
        }
    } catch (error) {
        console.error('Error in updateMap:', error);
        showError('Failed to update map: ' + error.message);
    }
}

// Update map markers based on filtered data
function updateMapMarkers(points) {
    // Clear existing markers
    if (markersLayer) {
        markersLayer.clearLayers();
    }

    // Add new markers
    points.forEach(point => {
        if (point.latitude && point.longitude) {
            let order = point.order || '';
            const color = orderColors[order] || '#999999'; // Default gray for unknown order
            
            const marker = L.marker([point.latitude, point.longitude], {
                icon: createCustomIcon(color)
            }).bindPopup(`
                <strong>${point.stationName || point.station_name}</strong><br>
                Lat: ${point.latitude}<br>
                Long: ${point.longitude}<br>
                Order: ${point.order}<br>
                ${point.accuracyClass ? `Accuracy Class: ${point.accuracyClass}<br>` : ''}
                <button class="btn btn-add-to-cart mt-2" onclick="directAddToSelected('${point.stationId || point.station_id}', '${point.stationName || point.station_name}')">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                </button>
            `);
            
            markersLayer.addLayer(marker);
        }
    });

    // Adjust map view if there are markers
    if (markersLayer.getLayers().length > 0) {
        map.fitBounds(markersLayer.getBounds().pad(0.1));
    }
}

// Export map functionality
export {
    initializeMap,
    updateMap,
    updateMapMarkers,
    orderColors
}; 