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

// Map initialization variables
let map;
let markersLayer;
let markersClusterGroup; // To store marker clusters
let overlayLayers = {};  // To hold overlays for layer control
let baseLayers = {};     // To hold base layers for layer control
let layerControl;
let adminBoundaryLayer = null; // Will be loaded from GeoJSON
let hydroLayer;               // NAMRIA Hydrography WMS

// Initialize map with default view
async function initializeMap() {
    try {
        console.log('Initializing map...');
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            throw new Error('Map element not found');
        }

        // Initialize map centered on Metro Manila
        map = L.map('map').setView([14.6, 121.0], 10);

        // NAMRIA base map (your original base)
        const namriaBase = L.tileLayer('https://basemapserver.geoportal.gov.ph/tiles/v2/PGP/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.geoportal.gov.ph/">NAMRIA</a> contributors',
        });

        // Esri Satellite base map
        const esriSatellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and others'
            });

        baseLayers = {
            "NAMRIA": namriaBase,
            "Satellite (Esri)": esriSatellite
        };

        // Add NAMRIA as default
        namriaBase.addTo(map);

        // Initialize marker cluster group and layer group
        markersClusterGroup = L.markerClusterGroup();
        markersLayer = L.layerGroup().addTo(map);

        /* // Initialize hydrography layer (NAMRIA WMS)
        hydroLayer = L.tileLayer.wms('https://giswebservices.denr.gov.ph/geoserver/ows?', {
            layers: 'NAMRIA:Hydrography',
            format: 'image/png',
            transparent: true,
            attribution: 'Hydrography data &copy; NAMRIA'
        }); */

        overlayLayers = {
            //"Hydrography": hydroLayer,
            "Points": markersLayer
            // Admin Boundary will be added once GeoJSON loaded
        };

        // Add layer control with base and overlay layers, collapsed to show layers icon
        layerControl = L.control.layers(baseLayers, overlayLayers, {collapsed: true}).addTo(map);

        // Fetch and load admin boundaries GeoJSON (replace with your path)
        try {
            const response = await fetch('Assets/Provinces.json');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const geojsonData = await response.json();
            loadAdminBoundariesFromGeoJSON(geojsonData);
        } catch (err) {
            console.warn('Could not load admin boundaries GeoJSON:', err);
            // Optionally show error to user
        }

        return map;
    } catch (error) {
        logError('initializeMap', error);
        showError('Failed to initialize map: ' + error.message);
        throw error;
    }
}

// Load admin boundaries from GeoJSON data
function loadAdminBoundariesFromGeoJSON(geojsonData) {
    try {
        if (adminBoundaryLayer) {
            map.removeLayer(adminBoundaryLayer);
        }

        adminBoundaryLayer = L.geoJSON(geojsonData, {
            style: {
                color: '#3388ff',
                weight: 2,
                fillOpacity: 0.1
            },
            onEachFeature: function(feature, layer) {
                if (feature.properties && feature.properties.name) {
                    layer.bindPopup(`<strong>${feature.properties.name}</strong>`);
                }
            }
        });

        adminBoundaryLayer.addTo(map);

        // Add or update overlayLayers control for admin boundaries
        overlayLayers["Provincial Boundary"] = adminBoundaryLayer;
        // Update layer control (remove and add again to refresh overlays) - keep collapsed true
        map.removeControl(layerControl);
        layerControl = L.control.layers(baseLayers, overlayLayers, {collapsed: true}).addTo(map);

    } catch (error) {
        console.error('Error loading admin boundaries:', error);
        showError('Failed to load admin boundaries: ' + error.message);
    }
}

// Create custom marker icons for different orders
function createCustomIcon(color) {
    try {
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

        // Store full dataset globally so dropdowns can access all regions
        window.allStations = stations;

        // Clear existing markers and clusters
        if (markersClusterGroup) {
            markersClusterGroup.clearLayers();
        }

        if (!Array.isArray(stations) || stations.length === 0) {
            console.log('No stations to display');
            return;
        }

        const bounds = L.latLngBounds([]);
        let hasValidCoordinates = false;

        stations.forEach(station => {
            if (station.latitude && station.longitude) {
                let order = station.order || station.elevation_order || station.horizontal_order || '';
                const color = orderColors[order] || '#999999'; // Default gray for unknown order
                
                const sName = station.station_name || '';
                // Escape single quotes for data attribute and JS string arguments
                const escapedSName = sName.replace(/'/g, "\\'");

                const marker = L.marker([station.latitude, station.longitude], {
                    icon: createCustomIcon(color)
                }).bindPopup(`
                    <strong>${sName}</strong><br>
                    Lat: ${station.latitude || ''}<br>
                    Long: ${station.longitude || ''}<br>
                    ${order ? `Order: ${order}<br>` : ''}
                    ${station.accuracy_class ? `Accuracy Class: ${station.accuracy_class}<br>` : ''}
                    <div class="mt-2" style="display: flex; align-items: center; gap: 10px;">
                        <button class="btn btn-sm btn-primary btn-view-description" data-station-name="${escapedSName}">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="directAddToSelected('${station.station_id || ''}', '${escapedSName}')" class="btn btn-sm btn-primary mt-2">
                            <i class="fa fa-cart-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                `);

                markersClusterGroup.addLayer(marker);
                bounds.extend([station.latitude, station.longitude]);
                hasValidCoordinates = true;
            }
        });

        markersLayer.clearLayers();
        markersLayer.addLayer(markersClusterGroup);

        if (hasValidCoordinates && map) {
            map.fitBounds(bounds, { 
                padding: [50, 50],
                maxZoom: 15
            });
        }
    } catch (error) {
        console.error('Error in updateMap:', error);
        showError('Failed to update map: ' + error.message);
    }
}

// Update map markers based on filtered data
function updateMapMarkers(points) {
    if (markersClusterGroup) {
        markersClusterGroup.clearLayers();
    }

    points.forEach(point => {
        if (point.latitude && point.longitude) {
            // Use the same comprehensive order fallback as in updateMap
            let order = point.order || point.elevation_order || point.horizontal_order || '';
            const color = orderColors[order] || '#999999'; // Default gray for unknown order

            const pName = point.stationName || point.station_name || '';
             // Escape single quotes for data attribute and JS string arguments
            const escapedPName = pName.replace(/'/g, "\\'");

            const marker = L.marker([point.latitude, point.longitude], {
                icon: createCustomIcon(color)
            }).bindPopup(`
                <strong>${pName}</strong><br>
                Lat: ${point.latitude}<br>
                Long: ${point.longitude}<br>
                ${order ? `Order: ${order}<br>` : ''}
                ${point.accuracy_class ? `Accuracy Class: ${point.accuracy_class}<br>` : ''}
                <div class="mt-2" style="display: flex; align-items: center; gap: 10px;">
                    <button class="btn btn-sm btn-primary btn-view-description" data-station-name="${escapedPName}">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="directAddToSelected('${point.station_id || ''}', '${escapedPName}')" class="btn btn-sm btn-primary mt-2">
                        <i class="fa fa-cart-plus" aria-hidden="true"></i>
                    </button>
                </div>
            `);

            markersClusterGroup.addLayer(marker);
        }
    });

    markersLayer.clearLayers();
    markersLayer.addLayer(markersClusterGroup);
}

// Helper: Get unique values from allStations (e.g., for Region dropdown)
function getUniqueValuesFromAllStations(fieldName) {
    if (!window.allStations) return [];
    const values = window.allStations.map(s => s[fieldName]).filter(Boolean);
    return [...new Set(values)].sort();
}

// Export map functionality
export {
    initializeMap,
    updateMap,
    updateMapMarkers,
    loadAdminBoundariesFromGeoJSON,
    orderColors,
    getUniqueValuesFromAllStations
};