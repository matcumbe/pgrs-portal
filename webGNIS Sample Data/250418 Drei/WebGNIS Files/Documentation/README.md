# WebGNIS - Geodetic Network Information System

## Overview

WebGNIS is a comprehensive web application designed for managing, visualizing, and querying geodetic control point data. It provides tools for both public users to explore geodetic data and administrative users to manage the underlying dataset.

![WebGNIS Logo](../Assets/gnis_logo.png)

## Features

### Public Features
- **Interactive Map Interface**: Explore geodetic stations with a Leaflet-based map.
- **Advanced Filtering**: Filter stations by type, location, and attributes.
- **Station Details**: View comprehensive information about each station.
- **Station Tracking**: Save and monitor specific stations of interest.
- **Responsive Design**: Full functionality on desktop and mobile devices.

### Administrative Features
- **Data Management**: Add, edit, and delete geodetic control points.
- **Bulk Operations**: Import and export station data.
- **Station Types**: Support for vertical, horizontal, and gravity stations.
- **User Authentication**: Secure access to administrative functions.

## Installation

### Prerequisites
- Web server with PHP 7.4+ support
- MySQL 5.7+ database
- Modern web browser (Chrome, Firefox, Edge, Safari)

### Setup Instructions

1. **Clone the repository**
   ```
   git clone https://github.com/your-organization/webgnis.git
   ```

2. **Database Configuration**
   - Import the database structure using the SQL files in the `database` directory
   - Configure database connection in `config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'webgnis');
     ```

3. **Web Server Configuration**
   - Configure your web server to serve the application from the root directory
   - Ensure the web server has write permissions for the `uploads` directory

4. **Test the Installation**
   - Navigate to the application URL in your web browser
   - Log in to the admin interface using the default credentials:
     - Username: admin
     - Password: admin123
   - **Important**: Change the default password immediately after the first login

## Usage

### Public Interface

1. **Home Page**
   - Access the system at `index.html`
   - View general information about the geodetic network

2. **Explorer Interface**
   - Navigate to the Explorer using the top navigation bar
   - Use the filter panel to search for specific stations
   - Click on map markers to view station details
   - Use the tracker feature to save stations of interest

### Admin Interface

1. **Login**
   - Navigate to the Admin Panel via the top navigation bar
   - Enter your credentials to access administrative features

2. **Station Management**
   - View all stations in the tabular interface
   - Use the filter options to narrow down the displayed stations
   - Click the "Add" button to create new stations
   - Use the action buttons to view, edit, or delete existing stations

3. **Form Interface**
   - The station form is organized into tabs (Common, Vertical, Horizontal, Gravity, Location)
   - Only relevant tabs will be displayed based on the selected station type
   - All required fields are marked with an asterisk (*)

## Documentation

The `Documentation` directory contains detailed information about different aspects of the system:

- **ARCHITECTURE.md**: Technical overview of the system architecture and components
- **API.md**: API documentation for developers
- **DATABASE.md**: Database schema and data models
- **FRONTEND.md**: Front-end implementation details
- **DEPLOYMENT.md**: Instructions for deploying the application
- **CONTRIBUTING.md**: Guidelines for contributing to the project

## Development

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Map Visualization**: Leaflet.js
- **Backend**: PHP
- **Database**: MySQL
- **Icons**: Bootstrap Icons and Font Awesome

### Project Structure
```
webgnis/
├── api/                 # API endpoints
├── assets/              # Static resources
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Images and icons
├── database/            # Database scripts
├── Documentation/       # System documentation
├── includes/            # PHP includes
├── uploads/             # User uploads
└── *.html               # Main application pages
```

## Troubleshooting

Common issues and their solutions:

1. **Map doesn't load**
   - Check your internet connection (Leaflet requires online access)
   - Verify that JavaScript is enabled in your browser

2. **Admin login fails**
   - Ensure caps lock is off
   - Reset your password if necessary
   - Check that the database connection is configured correctly

3. **Form submission errors**
   - Verify that all required fields are completed
   - Check the browser console for JavaScript errors
   - Ensure the server has write permissions to the database

## Contributing

We welcome contributions to WebGNIS! Please see the `CONTRIBUTING.md` file for guidelines.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- OpenStreetMap for base map data
- Leaflet.js team for the mapping library
- Bootstrap team for the UI framework
- All contributors who have helped improve WebGNIS

## Last Updated
April 20, 2025 
