# WebGNIS - Geodetic Network Information System

## Overview

WebGNIS (Geodetic Network Information System) is a web-based application for viewing, exploring, and managing geodetic control points. The system provides a user-friendly interface for searching, filtering, and visualizing geodetic stations across the Philippines.

<<<<<<< HEAD
## Key Features
=======
![WebGNIS Logo](../Assets/gnis_logo.png)
>>>>>>> 88ef3e5ab7d31f85435027343a539ba89e703feb

- **Interactive Map:** View all geodetic control points on an interactive map.
- **Advanced Filtering:** Filter stations by various criteria (type, location, order, etc.).
- **Radius Search:** Find stations within a specified distance from a point.
- **Data Management:** Admin interface for adding, editing, and managing station data.
- **Responsive Design:** Works on desktop and mobile devices.
- **User Authentication:** Secure login system with different user roles.
- **Data Request System:** Request access to GCP data with integrated payment tracking.

## System Components

### Frontend

- **Public Interface:**
  - Home page with system overview
  - Explorer with map-based search
  - User registration and login
  - Request management portal
  - Payment upload interface

- **Admin Interface:**
  - Station management console
  - Data import/export tools
  - User management
  - Ticket and payment verification

### Backend

- **API Endpoints:**
  - Public API for station data access
  - Admin API for data management
  - Users API for account management
  - Tickets API for handling data requests

- **Databases:**
  - Main GNIS database (geodetic control points)
  - Users database (user accounts and profiles)
  - Tickets database (data requests and payments)

## Ticket System

The system includes a comprehensive ticket system that enables users to:

1. Select specific points of interest from the map
2. Create data access requests with specified purpose
3. Submit payment via LinkBiz payment gateway
4. Upload payment receipts for verification
5. Track request status through the entire workflow

Administrators can:

1. Review and approve payment evidence
2. Process data requests
3. Mark requests as completed
4. Generate reports on ticket and payment activity

## Installation

Refer to [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) for detailed setup instructions.

## Documentation

- [User Guide](./USER_GUIDE.md) - End-user documentation
- [Developer Guide](./DEVELOPER_GUIDE.md) - Development information
- [Architecture](./ARCHITECTURE.md) - System architecture overview
- [API Documentation](./API.md) - API endpoint details
- [Database Schema](./DATABASE.md) - Database structure

## License

This project is licensed under the appropriate governmental policies for geodetic data management.

## Contact Information

For further information, contact the development team at:
- Email: [webgnis@example.gov](mailto:webgnis@example.gov)
- Phone: +63 123 456 7890

## Last Updated
<<<<<<< HEAD

May 1, 2025 
=======
April 20, 2025 
>>>>>>> 88ef3e5ab7d31f85435027343a539ba89e703feb
