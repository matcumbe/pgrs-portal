# GNIS Frontend Documentation

## Overview

The GNIS frontend is a web-based interface for interacting with the Geodetic Network Information System. It provides a user-friendly way to search, view, and analyze geodetic control point data.

## Technologies Used

- HTML5
- CSS3 (Bootstrap 5)
- JavaScript (ES6+)
- Leaflet.js (for map functionality)
- Chart.js (for data visualization)

## User Interface Components

### 1. Navigation Bar
- Application logo
- Links (GNIS Home, Explorer, Tracker, etc.)
- User menu (future implementation)
- Help/About links

### 2. Map Interface
- Interactive map using OpenStreetMap tiles
- Station markers with popups
- Map controls (zoom, fullscreen, etc.)
- Layer controls for different station types
- "Add to Cart" functionality for station selection
- Consistent green theme applied to interactive elements (buttons, popups)

### 3. Filter/Search Panel
- Station type selector
- Filter options:
  - Order
  - Accuracy class
  - Region
  - Province
  - City
  - Barangay
- Search radius control
- Search button
- Search by Lat/Lon/Radius controls

### 4. Search Results Panel
- Inline Search Bar: Located in the header for quick filtering of results.
- Tabular view of search results
- Dynamic Elevation Column: Header and content change based on GCP type:
  - Vertical: "Elevation" (uses `elevation` data)
  - Horizontal: "Ell. Height" (uses `ellipsoidal_height` data)
  - Gravity: "Grav. Value" (uses `gravity_value` data)
- Sortable columns (future implementation)
- Pagination controls
- Export options (CSV, JSON)
- "Add to Cart" buttons for station selection

### 5. Selected Points Panel
- List of user-selected stations
- Remove functionality for individual stations
- Actions for selected points (future implementation)

### 6. Station Details Panel
- Station information display
- Historical data visualization
- Related stations
- Edit/Delete options (future implementation)

## Key Features

### 1. Interactive Map
- Display of geodetic control points
- Marker clustering for dense areas
- Custom marker icons for different station types
- Popup information on marker click
- Map bounds auto-adjustment
- Station selection directly from map

### 2. Advanced Search & Filtering
- Multi-criteria filtering via the filter panel
- Real-time, Client-Side Search: Search bar in the results panel filters the *currently displayed* table data and map markers instantly.
- Search is case-insensitive and format-insensitive (ignores spaces, hyphens, etc.).
- Radius-based search via the filter panel
- Search history (future implementation)

### 3. Multiple Station Selection
- "Add to Cart" functionality from both map and search results
- Selected points management
- Individual station removal
- Selected points persistence
- Future batch operations on selected points

### 4. Data Visualization
- Station distribution heatmap
- Elevation profile
- Accuracy class distribution
- Historical data trends

### 5. Data Export
- CSV export
- JSON export
- Print view
- PDF export (future implementation)

## User Interactions

### 1. Map Interactions
- Click on markers to view details
- Click "Add to Cart" in popup to select station
- Drag to pan
- Scroll to zoom
- Double-click to center
- Right-click for context menu

### 2. Search Interactions
- Type in the filter panel dropdowns to filter options
- Click to select filters in the filter panel
- Enter Lat/Lon/Radius and click search button in filter panel
- Type in the Search Results search bar to filter displayed results in real-time

### 3. Results Interactions
- Click column headers to sort
- Click rows to view details
- Click "Add to Cart" to select station
- Use pagination controls
- Click export buttons

### 4. Selected Points Interactions
- View all selected stations
- Click "Remove" to remove individual stations
- Future: Batch operations on selected points
- Future: Export selected points only

## Error Handling

### 1. User Feedback
- Loading indicators
- Error messages
- Success notifications
- Warning prompts

### 2. Error Types
- Network errors
- API errors
- Validation errors
- Map errors
- Selection errors

## Performance Optimizations

### 1. Data Loading
- Lazy loading of markers
- Pagination of results
- Caching of frequent queries
- Debounced search input

### 2. Map Performance
- Marker clustering
- Viewport-based rendering
- Tile caching
- Smooth animations

### 3. UI Performance
- Virtual scrolling for large datasets
- Optimized DOM updates
- CSS transitions
- Image optimization

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Mobile Responsiveness

- Responsive design using Bootstrap grid
- Touch-friendly interactions
- Mobile-optimized map controls
- Adaptive layout for different screen sizes

## Future Enhancements

### 1. User Features
- User authentication
- Saved searches
- Custom markers
- Personal preferences
- Saved station selections

### 2. Analysis Tools
- Distance measurement
- Area calculation
- Elevation profile
- Network analysis
- Batch operations on selected points

### 3. Visualization
- 3D terrain view
- Time-based animations
- Custom themes
- Advanced charts
- Visual representation of selected points

### 4. Integration
- External data sources
- API integration
- Mobile app
- Desktop application
- Data export formats for selected points

## Last Updated
April 19, 2025 