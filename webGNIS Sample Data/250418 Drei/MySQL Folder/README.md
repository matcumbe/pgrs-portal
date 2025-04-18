# Geodetic Network Information System (GNIS)

A web-based system for managing and visualizing geodetic control points in the Philippines.

## Project Overview

The GNIS is designed to provide a comprehensive platform for:
- Visualizing geodetic control points on an interactive map
- Filtering and searching for specific control points
- Managing different types of control points (Vertical, Horizontal, Gravity)
- Providing detailed information about each control point
- Selecting multiple points for certificates or further analysis

## Documentation

- [API Documentation](API.md) - Detailed API endpoints and usage
- [Frontend Documentation](FRONTEND.md) - Frontend components and features
- [Database Documentation](DATABASE.md) - Database structure and schema

## Current State

### Working Features
- Interactive map display using Leaflet.js
- Filtering by GCP type (Vertical, Horizontal, Gravity)
- Location-based filtering (Region, Province, City, Barangay)
- Order and accuracy class filtering
- Search by station name
- Radius-based search
- Real-time map updates
- Responsive design
- Multiple point selection ("Add to Cart" functionality)
- Selected points management

### Known Issues
1. Map bounds error (Fixed)
2. API endpoint duplication (Fixed)
3. Error handling improvements (In Progress)
4. "Add to Cart" functionality (Fixed)

### Required Fixes
1. ~~Map bounds error in updateMap function~~
2. ~~API endpoint cleanup and standardization~~
3. ~~"Add to Cart" functionality for multiple point selection~~
4. Enhanced error handling and user feedback
5. Performance optimizations for large datasets
6. Security improvements

## Technical Stack

### Frontend
- HTML5, CSS3, JavaScript
- Bootstrap 5.3.2
- Leaflet.js 1.9.4
- Font Awesome icons

### Backend
- PHP
- MySQL
- RESTful API architecture

## Setup Instructions

1. Database Setup:
   ```sql
   -- Import the provided SQL files in the following order:
   -- 1. vgcp_stations.sql
   -- 2. hgcp_stations.sql
   -- 3. grav_stations.sql
   ```

2. Web Server Requirements:
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Apache/Nginx web server
   - mod_rewrite enabled

3. Configuration:
   - Copy `config.example.php` to `config.php`
   - Update database credentials in `config.php`
   - Set appropriate file permissions

## Development Status

### Completed
- Basic map functionality
- Filter system implementation
- API endpoint structure
- Database integration
- Multiple point selection functionality

### In Progress
- Error handling improvements
- Performance optimizations
- Documentation updates

### Planned
- User authentication
- Data export functionality
- Advanced search features
- Mobile app integration
- Selected points processing and export

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contact

For any questions or concerns, please contact the development team.

## Last Updated
May 1, 2024 