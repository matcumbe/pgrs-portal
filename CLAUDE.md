\# WebGNIS Project Documentation



\## Overview

WebGNIS (Web-based Geodetic Network Information System) is a comprehensive web application for managing and providing access to geodetic control point data in the Philippines. The system is developed for NAMRIA (National Mapping and Resource Information Authority) to support surveying, mapping, and spatial data management activities.



\## Project Structure



\### Core Files

\- \*\*index.php\*\* - Main application entry point (GCP Explorer)

\- \*\*home.php\*\* - Landing page with system overview

\- \*\*admin.php\*\* - Administrative interface for GCP management

\- \*\*tracker.php\*\* - Request tracking interface

\- \*\*about.php\*\* - About page

\- \*\*account.php\*\* - User account management

\- \*\*requests\_management.php\*\* - Admin requests management



\### Backend APIs

\- \*\*api.php\*\* - Main API endpoint for station data

\- \*\*users\_api.php\*\* - User management API

\- \*\*gcp\_admin\_api.php\*\* - GCP administrative operations

\- \*\*requests\_api.php\*\* - Request management API

\- \*\*transactions\_api.php\*\* - Transaction handling

\- \*\*cart\_api.php\*\* - Shopping cart functionality

\- \*\*certificates\_api.php\*\* - Certificate generation

\- \*\*stations-api.php\*\* - Station data operations



\### Configuration

\- \*\*config.php\*\* - Database and application configuration

\- \*\*users\_config.php\*\* - User-specific configuration

\- \*\*app.yaml\*\* - Google App Engine deployment configuration

\- \*\*composer.json\*\* - PHP dependencies



\### Frontend JavaScript (Modular Architecture)

\- \*\*js/main.js\*\* - Main application initialization

\- \*\*js/map.js\*\* - Leaflet map functionality with clustering

\- \*\*js/stations.js\*\* - Station data management and filtering

\- \*\*js/search.js\*\* - Search functionality

\- \*\*js/events.js\*\* - Event handling

\- \*\*js/cart.js\*\* - Shopping cart management

\- \*\*js/payment.js\*\* - Payment processing

\- \*\*js/utils.js\*\* - Utility functions

\- \*\*js/users/\*\* - User management modules

&nbsp; - \*\*auth.js\*\* - Authentication logic

&nbsp; - \*\*user-service.js\*\* - User service layer

&nbsp; - \*\*api-client.js\*\* - API client wrapper

&nbsp; - \*\*user-ui.js\*\* - UI components



\### Assets and Data

\- \*\*assets/gnis\_logo.png\*\* - System logo

\- \*\*assets/Provinces.json\*\* - Administrative boundaries

\- \*\*assets/data/\*\* - GCP data files (CSV format)

\- \*\*assets/payment\_proofs/\*\* - Payment proof uploads

\- \*\*assets/processed\_certs/\*\* - Generated certificates

\- \*\*assets/lib/\*\* - Third-party libraries (QR code, PDF)



\## System Architecture



\### Frontend Architecture

\- \*\*Responsive Design\*\*: Bootstrap 5 for mobile-first UI

\- \*\*Interactive Maps\*\*: Leaflet.js with marker clustering

\- \*\*Modular JavaScript\*\*: ES6 modules for maintainability

\- \*\*Progressive Enhancement\*\*: Works without JavaScript for basic functionality



\### Backend Architecture

\- \*\*REST API\*\*: Clean API endpoints for all operations

\- \*\*Database\*\*: MySQL with optimized schema

\- \*\*Authentication\*\*: JWT-based session management

\- \*\*File Management\*\*: Integrated file upload and processing



\### Database Schema

\- \*\*Users Tables\*\*: Individual and company user management

\- \*\*Station Tables\*\*: 

&nbsp; - `hgcp\_stations\_new` - Horizontal geodetic control points

&nbsp; - `vgcp\_stations\_new` - Vertical geodetic control points

&nbsp; - `grav\_stations\_new` - Gravity control points

\- \*\*Request Tables\*\*: Certificate requests and tracking

\- \*\*Transaction Tables\*\*: Payment and billing



\## Key Features



\### 1. GCP Explorer (index.php)

\- \*\*Interactive Map\*\*: Leaflet-based map with cluster markers

\- \*\*Advanced Filtering\*\*: By type, location, order, accuracy

\- \*\*Search\*\*: Real-time station name search

\- \*\*Cart System\*\*: Add stations to request cart

\- \*\*Responsive Tables\*\*: Paginated results with sorting



\### 2. Administrative Interface (admin.php)

\- \*\*CRUD Operations\*\*: Create, Read, Update, Delete GCP stations

\- \*\*Bulk Operations\*\*: Import/export functionality

\- \*\*Data Validation\*\*: Comprehensive form validation

\- \*\*User Management\*\*: Role-based access control



\### 3. Request Management

\- \*\*Certificate Generation\*\*: PDF certificates with QR codes

\- \*\*Payment Integration\*\*: GCash/bank transfer support

\- \*\*Request Tracking\*\*: Status updates and notifications

\- \*\*Bulk Processing\*\*: Handle multiple requests efficiently



\### 4. User System

\- \*\*Dual Registration\*\*: Individual and company accounts

\- \*\*Role-Based Access\*\*: User, admin, super admin roles

\- \*\*Profile Management\*\*: Complete user profile system

\- \*\*Session Management\*\*: Secure JWT-based authentication



\## Technical Implementation



\### Map Functionality (js/map.js)

\- \*\*Base Layers\*\*: NAMRIA and Esri Satellite imagery

\- \*\*Marker Clustering\*\*: Performance optimization for large datasets

\- \*\*Color Coding\*\*: Order-based marker colors

\- \*\*Popups\*\*: Interactive station information

\- \*\*Boundary Overlays\*\*: Provincial administrative boundaries



\### Station Management (js/stations.js)

\- \*\*Pagination\*\*: Efficient data loading

\- \*\*Filtering\*\*: Multi-level location filtering

\- \*\*Search\*\*: Fuzzy search with normalization

\- \*\*Data Caching\*\*: Client-side caching for performance



\### Authentication System (js/users/auth.js)

\- \*\*JWT Tokens\*\*: Secure session management

\- \*\*Role Verification\*\*: Page-level access control

\- \*\*Auto-logout\*\*: Session timeout handling

\- \*\*Password Security\*\*: Hashed password storage



\## Configuration Details



\### Database Configuration (config.php)

```php

// Environment-aware configuration

define('DB\_HOST', getenv('GAE\_APPLICATION') ? getenv('DB\_SOCKET\_1') : '127.0.0.1');

define('DB\_USER', getenv('GAE\_APPLICATION') ? getenv('DB\_USER\_1') : 'root');

define('DB\_NAME', 'webgnis\_db');

```



\### Application Settings

\- \*\*Pricing\*\*: Configurable certificate prices

\- \*\*File Limits\*\*: Upload size and type restrictions

\- \*\*Maps\*\*: Default coordinates and zoom levels

\- \*\*Search\*\*: Result limits and pagination



\## Development Workflows



\### File Structure Patterns

\- \*\*Modular JavaScript\*\*: Each feature in separate modules

\- \*\*API-First Design\*\*: All operations through REST endpoints

\- \*\*Error Handling\*\*: Comprehensive error logging and user feedback

\- \*\*Performance\*\*: Optimized queries and caching



\### Code Quality Standards

\- \*\*ES6 Modules\*\*: Modern JavaScript architecture

\- \*\*Error Boundaries\*\*: Try-catch blocks in all async operations

\- \*\*Input Validation\*\*: Both client and server-side validation

\- \*\*Security\*\*: SQL injection prevention, XSS protection



\## Deployment Configuration



\### Google App Engine (app.yaml)

\- \*\*PHP Runtime\*\*: Version 8.1

\- \*\*Database\*\*: Cloud SQL integration

\- \*\*File Storage\*\*: Cloud Storage for uploads

\- \*\*Environment Variables\*\*: Secure configuration



\### Local Development

\- \*\*XAMPP/WAMP\*\*: Local server setup

\- \*\*MySQL\*\*: Database server

\- \*\*Composer\*\*: PHP dependency management



\## API Documentation



\### Station Endpoints

\- `GET /api/stations/{type}` - Get stations by type

\- `GET /api/stations/{type}?province={name}` - Filter by province

\- `GET /api/provinces` - Get all provinces



\### User Endpoints

\- `POST /users\_api.php?action=login` - User authentication

\- `POST /users\_api.php?action=users` - User registration

\- `GET /users\_api.php?action=users/me` - Current user info



\### Request Endpoints

\- `POST /requests\_api.php?action=create` - Create certificate request

\- `GET /requests\_api.php?action=track` - Track request status

\- `POST /requests\_api.php?action=update` - Update request



\## Security Considerations



\### Authentication \& Authorization

\- \*\*JWT Tokens\*\*: Secure session management

\- \*\*Role-Based Access\*\*: Admin, user, and guest permissions

\- \*\*Input Sanitization\*\*: SQL injection prevention

\- \*\*File Upload Security\*\*: Type and size validation



\### Data Protection

\- \*\*Password Hashing\*\*: Secure password storage

\- \*\*Session Timeout\*\*: Automatic logout

\- \*\*CORS Headers\*\*: Cross-origin request security

\- \*\*Error Handling\*\*: No sensitive data in error messages



\## Performance Optimizations



\### Frontend Performance

\- \*\*Lazy Loading\*\*: Load data on demand

\- \*\*Pagination\*\*: Limit table results

\- \*\*Marker Clustering\*\*: Optimize map rendering

\- \*\*Caching\*\*: Client-side data caching



\### Backend Performance

\- \*\*Database Indexing\*\*: Optimized queries

\- \*\*Connection Pooling\*\*: Efficient database connections

\- \*\*Error Logging\*\*: Separate error handling

\- \*\*Output Buffering\*\*: Clean response handling



\## Testing \& Quality Assurance



\### Testing Files

\- \*\*test\_admin\_api.php\*\* - API testing

\- \*\*test\_certificate\_generation.php\*\* - Certificate testing

\- \*\*test\_all\_gcp\_types.php\*\* - GCP type testing



\### Error Handling

\- \*\*Comprehensive Logging\*\*: All errors logged to php\_errors.log

\- \*\*User-Friendly Messages\*\*: Clean error display

\- \*\*Fallback Mechanisms\*\*: Graceful degradation



\## Future Enhancements



\### Potential Features

\- \*\*Email Notifications\*\*: Automated status updates

\- \*\*Advanced Search\*\*: Full-text search capabilities

\- \*\*Mobile App\*\*: Native mobile application

\- \*\*API Documentation\*\*: Interactive API docs

\- \*\*Analytics Dashboard\*\*: Usage statistics



\### Technical Improvements

\- \*\*TypeScript\*\*: Type-safe JavaScript

\- \*\*Unit Testing\*\*: Comprehensive test suite

\- \*\*CI/CD Pipeline\*\*: Automated deployment

\- \*\*Performance Monitoring\*\*: Real-time metrics



\## Maintenance



\### Regular Tasks

\- \*\*Database Backup\*\*: Automated backups

\- \*\*Log Rotation\*\*: Manage log file sizes

\- \*\*Security Updates\*\*: Keep dependencies updated

\- \*\*Performance Monitoring\*\*: Track system metrics



\### Monitoring

\- \*\*Error Logs\*\*: Monitor php\_errors.log

\- \*\*Database Performance\*\*: Query optimization

\- \*\*User Activity\*\*: Track usage patterns

\- \*\*System Health\*\*: Server monitoring



\## Version History



\### Current Version: v0.1.6

\- Complete user interface for all core functions

\- Request tracking system

\- Admin request management

\- Certificate generation

\- Payment integration

\- No email system (planned for future)



\### Previous Versions

\- v0.1.5.1: Tracker payment fixes

\- v0.1.5: Core functionality implementation

\- Earlier versions: Foundation development



\## Dependencies



\### PHP Dependencies (composer.json)

\- PDF generation libraries

\- QR code generation

\- Database abstraction layers



\### JavaScript Dependencies

\- Bootstrap 5.3.2

\- Leaflet.js 1.9.4

\- Font Awesome 5.15.4

\- Leaflet MarkerCluster



\### Database Requirements

\- MySQL 5.7+ or MariaDB 10.3+

\- Specific table schema for GCP data

\- User and session management tables



This documentation provides a comprehensive overview of the WebGNIS system architecture, implementation details, and operational considerations. The system is designed to be scalable, maintainable, and secure while providing an excellent user experience for geodetic data management.

