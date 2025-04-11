# webGNIS Prototype

A web-based Geodetic Network Information System (GNIS) for managing geodetic control points in the Philippines.

## Overview

The webGNIS Prototype is a comprehensive system designed to support geodetic and mapping activities in the Philippines. It provides a user-friendly interface for managing and accessing geodetic control points (GCPs), including their geographic locations, coordinates, and related information.

## Features

### Core Functionality
1. **Explorer**
   - Horizontal Control Points Explorer
   - Vertical Control Points Explorer
   - Shapefile Generation

2. **Tracker**
   - Certificate Tracking System
   - Request Status Monitoring

3. **Simulator**
   - GCP Simulation Tools
   - Data Visualization

4. **Management**
   - Certificate Management
   - Control Points Management
   - Data Import/Export

### Key Components
- Interactive map interface
- Comprehensive data management
- Certificate generation system
- User authentication and authorization
- Data export capabilities (Shapefile, PDF)

## System Requirements

### Server Requirements
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Apache Web Server
- GD Library (for image processing)
- PHPMailer (for email functionality)
- FPDF (for PDF generation)

### Client Requirements
- Modern web browser (Chrome, Firefox, Edge)
- JavaScript enabled
- Internet connection

## Installation

1. **Database Setup**
   - Import the database schema from `if0_36589195_webgnisdb.sql`
   - Configure database connection in PHP files

2. **Web Server Configuration**
   - Place all files in your web server directory
   - Ensure proper file permissions
   - Configure Apache rewrite rules (if needed)

3. **Dependencies**
   - Install PHPMailer in the `PHPMailer` directory
   - Install FPDF in the `fpdf` directory
   - Configure email settings in relevant PHP files

## Directory Structure

```
webGNIS Prototype/
├── PHPMailer/          # Email functionality
├── fpdf/              # PDF generation
├── images/            # System images and icons
├── Shapefile/         # Shapefile generation
├── certificates/      # Certificate templates
├── dev/              # Development files
├── old/              # Archived files
├── shp/              # Shapefile data
├── *.php             # Main application files
├── *.html            # Static pages
└── if0_36589195_webgnisdb.sql  # Database schema
```

## Usage

### Accessing the System
1. Open the web application in your browser
2. Navigate through the main menu:
   - Explorer: View and search control points
   - Tracker: Monitor certificate requests
   - Simulator: Simulate control point scenarios
   - Management: Administer system data

### Data Management
1. **Adding Control Points**
   - Navigate to Management > Control Points
   - Click "Add New"
   - Fill in required information
   - Save the record

2. **Generating Certificates**
   - Navigate to Management > Certificates
   - Select the control point
   - Generate and download certificate

3. **Exporting Data**
   - Use the Explorer interface
   - Select desired control points
   - Choose export format (Shapefile/PDF)

## Security

- User authentication required for management functions
- Input validation on all forms
- Secure file handling
- Regular data backups recommended

## Support

For technical support or issues:
1. Check the error logs in `error_log.txt`
2. Review the documentation
3. Contact system administrator

## License

This project is proprietary software. Unauthorized distribution or modification is prohibited.

## Version History

- Initial Release: April 2024
- Current Version: 1.0

## Credits

Developed by the NAMRIA Geodetic Network Information System Team 