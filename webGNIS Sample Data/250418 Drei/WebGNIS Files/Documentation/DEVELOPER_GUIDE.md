# WebGNIS Developer Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Tech Stack](#tech-stack)
4. [Setting Up the Development Environment](#setting-up-the-development-environment)
5. [Database Schema](#database-schema)
6. [API Documentation](#api-documentation)
7. [Frontend Components](#frontend-components)
8. [Authentication and Authorization](#authentication-and-authorization)
9. [Development Guidelines](#development-guidelines)
10. [Testing](#testing)
11. [Deployment](#deployment)
12. [Troubleshooting](#troubleshooting)

## Introduction

This developer guide provides comprehensive information about the WebGNIS (Geodetic Network Information System) application architecture, coding standards, and technical implementation details. It serves as the primary reference for developers working on maintaining or extending the system.

## Project Structure

The WebGNIS project follows a typical web application structure:

```
WebGNIS/
│
├── api/                   # Backend API endpoints
│   ├── controllers/       # Request handlers
│   ├── models/            # Data models
│   ├── routes/            # API route definitions
│   └── services/          # Business logic
│
├── public/                # Static files accessible to the public
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files (admin.js, explorer.js, etc.)
│   └── images/            # Image assets
│
├── views/                 # HTML templates
│   ├── admin.html         # Admin panel interface
│   ├── explorer.html      # Public explorer interface
│   ├── home.html          # Landing page
│   ├── index.html         # Main entry point
│   └── components/        # Reusable HTML components
│
├── utils/                 # Utility functions
│
└── config/                # Configuration files
```

## Tech Stack

WebGNIS uses the following technologies:

- **Frontend**:
  - HTML5, CSS3, JavaScript (ES6+)
  - Bootstrap 5 for responsive design
  - Font Awesome for icons
  - Leaflet.js for interactive maps

- **Backend**:
  - Node.js with Express.js framework
  - PostgreSQL with PostGIS extension for spatial data
  - Sequelize ORM for database interactions

- **Authentication**:
  - JSON Web Tokens (JWT) for secure API access
  - Bcrypt for password hashing

- **Testing**:
  - Jest for unit testing
  - Cypress for end-to-end testing

## Setting Up the Development Environment

### Prerequisites

- Node.js (v14.x or later)
- PostgreSQL (v12.x or later) with PostGIS extension
- Git

### Installation Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/organization/webgnis.git
   cd webgnis
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Set up environment variables:
   Create a `.env` file in the root directory with the following variables:
   ```
   DB_HOST=localhost
   DB_PORT=5432
   DB_NAME=webgnis
   DB_USER=postgres
   DB_PASSWORD=yourpassword
   JWT_SECRET=your_jwt_secret
   PORT=3000
   ```

4. Set up the database:
   ```bash
   psql -U postgres -c "CREATE DATABASE webgnis;"
   psql -U postgres -d webgnis -c "CREATE EXTENSION postgis;"
   npm run db:migrate
   npm run db:seed
   ```

5. Start the development server:
   ```bash
   npm run dev
   ```

The application should now be running at `http://localhost:3000`.

## Database Schema

WebGNIS uses a PostgreSQL database with PostGIS extension for spatial data management. The core tables are:

### Stations Table

```sql
CREATE TABLE stations (
    id SERIAL PRIMARY KEY,
    station_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    station_type ENUM('vertical', 'horizontal', 'gravity') NOT NULL,
    status ENUM('active', 'inactive', 'destroyed', 'uncertain') NOT NULL,
    established_date DATE,
    last_updated TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);
```

### Station Details Tables

```sql
CREATE TABLE vertical_stations (
    id SERIAL PRIMARY KEY,
    station_id INTEGER REFERENCES stations(id),
    height NUMERIC(10, 4),
    height_type ENUM('orthometric', 'ellipsoidal'),
    height_datum VARCHAR(50),
    accuracy_class VARCHAR(20),
    mark_type VARCHAR(50)
);

CREATE TABLE horizontal_stations (
    id SERIAL PRIMARY KEY,
    station_id INTEGER REFERENCES stations(id),
    latitude NUMERIC(12, 8),
    longitude NUMERIC(12, 8),
    ellipsoidal_height NUMERIC(10, 4),
    reference_frame VARCHAR(50),
    epoch VARCHAR(20),
    accuracy_class VARCHAR(20),
    mark_type VARCHAR(50)
);

CREATE TABLE gravity_stations (
    id SERIAL PRIMARY KEY,
    station_id INTEGER REFERENCES stations(id),
    gravity_value NUMERIC(12, 6),
    gravity_datum VARCHAR(50),
    accuracy_class VARCHAR(20),
    mark_type VARCHAR(50)
);
```

### Location Table

```sql
CREATE TABLE locations (
    id SERIAL PRIMARY KEY,
    station_id INTEGER REFERENCES stations(id),
    region VARCHAR(100),
    province VARCHAR(100),
    municipality VARCHAR(100),
    barangay VARCHAR(100),
    sitio VARCHAR(100),
    address TEXT,
    geom GEOMETRY(Point, 4326)
);

-- Spatial index for efficient location queries
CREATE INDEX locations_geom_idx ON locations USING GIST (geom);
```

### Users Table

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('admin', 'editor', 'viewer') NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE
);
```

## API Documentation

The WebGNIS API follows RESTful principles and provides the following endpoints:

### Authentication

- `POST /api/auth/login` - Authenticate user and get JWT token
- `POST /api/auth/logout` - Invalidate JWT token
- `GET /api/auth/profile` - Get current user profile

### Stations Management

- `GET /api/stations` - Get all stations (with filtering options)
- `GET /api/stations/:id` - Get station by ID
- `POST /api/stations` - Create new station
- `PUT /api/stations/:id` - Update station
- `DELETE /api/stations/:id` - Delete station

### Specific Station Types

- `GET /api/stations/vertical` - Get vertical stations
- `GET /api/stations/horizontal` - Get horizontal stations
- `GET /api/stations/gravity` - Get gravity stations

### Location Queries

- `GET /api/locations/near` - Get stations near a point
- `GET /api/locations/within` - Get stations within a polygon

### Users Management (Admin only)

- `GET /api/users` - Get all users
- `POST /api/users` - Create a new user
- `PUT /api/users/:id` - Update user
- `DELETE /api/users/:id` - Delete user

### Request/Response Format

All API requests and responses use JSON format. Example response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "station_id": "VGCP-001",
    "name": "Mount Apo BM",
    "station_type": "vertical",
    "status": "active",
    "details": {
      "height": 2954.0,
      "height_type": "orthometric",
      "height_datum": "MSL",
      "accuracy_class": "2nd Order"
    },
    "location": {
      "latitude": 6.9894,
      "longitude": 125.2706,
      "region": "Region XI",
      "province": "Davao del Sur",
      "municipality": "Kidapawan City"
    }
  }
}
```

Error response:

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Invalid credentials"
  }
}
```

## Frontend Components

### Explorer Components

The Explorer interface is built with several key components:

- **Map Container** - Leaflet.js map for visualizing geodetic stations
- **Filter Panel** - Sidebar with filtering options
- **Results Table** - Tabular view of filtered stations
- **Station Details Modal** - Popup for viewing comprehensive station information

Relevant files:
- `public/js/explorer.js` - Main Explorer functionality
- `public/js/map.js` - Map initialization and interaction
- `views/explorer.html` - HTML structure

### Admin Interface Components

The Admin panel consists of:

- **Authentication Modal** - Login form for admin access
- **Stations Table** - Sortable, filterable table of all stations
- **Station Form** - Tabbed form for adding/editing stations
- **Import/Export Tools** - Utilities for bulk data operations

Relevant files:
- `public/js/admin.js` - Main admin functionality including form management
- `views/admin.html` - HTML structure with tabbed form interface

## Authentication and Authorization

### Authentication Flow

1. User enters credentials in the admin login form
2. Credentials are sent to `/api/auth/login` endpoint
3. Server validates credentials and returns JWT token if valid
4. Token is stored in localStorage and included in subsequent API requests
5. Protected routes check for valid token before processing requests

### Authorization Levels

- **Public** - Access to Explorer and general information
- **Viewer** - Basic admin access (view-only)
- **Editor** - Can add/edit stations
- **Admin** - Full system access including user management

### Implementation Details

- JWT tokens expire after 24 hours
- Password reset functionality available via email
- Failed login attempts are rate-limited to prevent brute force attacks
- Sensitive operations require recent authentication

## Development Guidelines

### Coding Standards

- **JavaScript**: Follow Airbnb JavaScript Style Guide
- **HTML/CSS**: Use Bootstrap 5 conventions
- **Naming**: Use camelCase for JavaScript variables and functions
- **Comments**: Document complex logic and functions with JSDoc

### Git Workflow

1. Create feature branch from `dev`: `git checkout -b feature/feature-name`
2. Make changes and test thoroughly
3. Commit with descriptive messages: `git commit -m "Add feature: description"`
4. Push branch and create pull request to `dev`
5. After review and testing, merge to `dev`
6. Periodically merge `dev` to `main` for releases

### Version Control

- Use semantic versioning (MAJOR.MINOR.PATCH)
- Document all changes in CHANGELOG.md
- Tag releases in git

## Testing

### Unit Testing

Unit tests use Jest framework. Run with:

```bash
npm run test:unit
```

Test files should be located next to the file they test with a `.test.js` suffix.

### End-to-End Testing

E2E tests use Cypress. Run with:

```bash
npm run test:e2e
```

Test scenarios cover critical user paths:
- Public user station search and filtering
- Admin authentication
- Station CRUD operations

### Test Coverage

Aim for minimum 80% test coverage on all new code. Generate coverage report:

```bash
npm run test:coverage
```

## Deployment

### Production Build

Create a production build with:

```bash
npm run build
```

This creates optimized static assets in the `dist` directory.

### Deployment Options

1. **Manual Deployment**:
   ```bash
   npm run build
   scp -r dist/* user@server:/var/www/webgnis
   ```

2. **Docker Deployment**:
   ```bash
   docker build -t webgnis .
   docker run -p 80:3000 -e DB_HOST=db_host webgnis
   ```

3. **CI/CD Pipeline**:
   The repository includes GitHub Actions workflows for automated testing and deployment.

## Troubleshooting

### Common Issues

#### Database Connection Errors

- Check that PostgreSQL service is running
- Verify connection parameters in `.env` file
- Ensure PostGIS extension is enabled

#### Map Display Problems

- Check browser console for JavaScript errors
- Verify that Leaflet.js is properly loaded
- Ensure map container has appropriate height/width

#### Authentication Failures

- Clear localStorage and try logging in again
- Check server logs for JWT validation errors
- Verify that clock is synchronized between client and server

### Debugging Tools

- Browser Developer Tools for frontend issues
- Morgan logging middleware for HTTP request logging
- Winston logger for backend error logging

---

*Last Updated: April 20, 2025* 