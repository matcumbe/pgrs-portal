# GNIS Database Documentation

## Overview

The GNIS database stores information about geodetic control points, their locations, and related metadata. This documentation describes the database schema, relationships, and data types.

## Database Schema

### 1. Stations Table
```sql
CREATE TABLE stations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_name VARCHAR(255) NOT NULL,
    station_type ENUM('PBM', 'BLLM', 'GPS') NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    elevation DECIMAL(10,3),
    order_id INT,
    accuracy_class_id INT,
    region_id INT,
    province_id INT,
    city_id INT,
    barangay_id INT,
    established_date DATE,
    last_observation_date DATE,
    status ENUM('active', 'inactive', 'destroyed') DEFAULT 'active',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (accuracy_class_id) REFERENCES accuracy_classes(id),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    FOREIGN KEY (city_id) REFERENCES cities(id),
    FOREIGN KEY (barangay_id) REFERENCES barangays(id)
);
```

### 2. Orders Table
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3. Accuracy Classes Table
```sql
CREATE TABLE accuracy_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    tolerance DECIMAL(10,3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 4. Regions Table
```sql
CREATE TABLE regions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. Provinces Table
```sql
CREATE TABLE provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    region_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id)
);
```

### 6. Cities Table
```sql
CREATE TABLE cities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    province_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id)
);
```

### 7. Barangays Table
```sql
CREATE TABLE barangays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    city_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);
```

### 8. Observations Table
```sql
CREATE TABLE observations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    station_id INT NOT NULL,
    observation_date DATE NOT NULL,
    observer VARCHAR(100),
    instrument_type VARCHAR(50),
    weather_conditions TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id)
);
```

### 9. Station Selections (Future Implementation)
```sql
CREATE TABLE station_selections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(100) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('temporary', 'submitted', 'processed') DEFAULT 'temporary',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE selection_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    selection_id INT NOT NULL,
    station_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (selection_id) REFERENCES station_selections(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(id)
);
```

### 10. Requests (Future Implementation)
```sql
CREATE TABLE requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    selection_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    organization VARCHAR(100),
    purpose TEXT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (selection_id) REFERENCES station_selections(id)
);
```

## Relationships

### 1. Station Relationships
- One station belongs to one order
- One station belongs to one accuracy class
- One station belongs to one region
- One station belongs to one province
- One station belongs to one city
- One station belongs to one barangay
- One station has many observations
- One station can be in multiple selection_items

### 2. Geographic Relationships
- One region has many provinces
- One province has many cities
- One city has many barangays

### 3. Selection Relationships
- One station_selection has many selection_items
- One station_selection belongs to one user (when authenticated)
- One station_selection has one request

## Indexes

### 1. Primary Indexes
- All tables have a primary key index on `id`

### 2. Foreign Key Indexes
- `stations(order_id)`
- `stations(accuracy_class_id)`
- `stations(region_id)`
- `stations(province_id)`
- `stations(city_id)`
- `stations(barangay_id)`
- `provinces(region_id)`
- `cities(province_id)`
- `barangays(city_id)`
- `observations(station_id)`
- `selection_items(selection_id)`
- `selection_items(station_id)`
- `requests(selection_id)`

### 3. Search Indexes
- `stations(station_name)`
- `stations(station_type)`
- `stations(latitude, longitude)`
- `regions(name)`
- `provinces(name)`
- `cities(name)`
- `barangays(name)`
- `station_selections(session_id)`
- `station_selections(user_id)`

## Data Types

### 1. Numeric Types
- `INT`: For IDs and counts
- `DECIMAL`: For coordinates and measurements
- `FLOAT`: For calculations

### 2. String Types
- `VARCHAR`: For names and codes
- `TEXT`: For descriptions and notes
- `ENUM`: For fixed options

### 3. Date/Time Types
- `DATE`: For dates
- `TIMESTAMP`: For creation and update times

## Constraints

### 1. Primary Key Constraints
- All tables have an `id` primary key
- Auto-incrementing for new records

### 2. Foreign Key Constraints
- Cascading updates and deletes where appropriate
- Referential integrity maintained

### 3. Unique Constraints
- Station names within types
- Region codes
- Province codes within regions
- City codes within provinces
- Barangay codes within cities

## Views

### 1. Station Details View
```sql
CREATE VIEW station_details AS
SELECT 
    s.*,
    o.name as order_name,
    ac.name as accuracy_class_name,
    r.name as region_name,
    p.name as province_name,
    c.name as city_name,
    b.name as barangay_name
FROM stations s
LEFT JOIN orders o ON s.order_id = o.id
LEFT JOIN accuracy_classes ac ON s.accuracy_class_id = ac.id
LEFT JOIN regions r ON s.region_id = r.id
LEFT JOIN provinces p ON s.province_id = p.id
LEFT JOIN cities c ON s.city_id = c.id
LEFT JOIN barangays b ON s.barangay_id = b.id;
```

### 2. Geographic Hierarchy View
```sql
CREATE VIEW geographic_hierarchy AS
SELECT 
    r.name as region_name,
    p.name as province_name,
    c.name as city_name,
    b.name as barangay_name,
    COUNT(s.id) as station_count
FROM regions r
LEFT JOIN provinces p ON r.id = p.region_id
LEFT JOIN cities c ON p.id = c.province_id
LEFT JOIN barangays b ON c.id = b.city_id
LEFT JOIN stations s ON b.id = s.barangay_id
GROUP BY r.name, p.name, c.name, b.name;
```

### 3. Selection Details View (Future Implementation)
```sql
CREATE VIEW selection_details AS
SELECT 
    ss.id as selection_id,
    ss.session_id,
    ss.user_id,
    ss.created_at,
    ss.status,
    COUNT(si.id) as total_items,
    GROUP_CONCAT(s.station_name SEPARATOR ', ') as selected_stations
FROM station_selections ss
LEFT JOIN selection_items si ON ss.id = si.selection_id
LEFT JOIN stations s ON si.station_id = s.id
GROUP BY ss.id, ss.session_id, ss.user_id, ss.created_at, ss.status;
```

## Current Implementation Notes

### Selected Points Management
The current implementation of selected points ("Add to Cart" functionality) is handled entirely in the frontend using JavaScript. Selected points are stored in a client-side array and rendered in the UI. Future versions will implement server-side storage and persistence as outlined in the schema above.

## Future Implementation Plans

### 1. User Session Persistence
- Store selected points in a database linked to user sessions
- Allow users to save and retrieve selections across sessions
- Support for anonymous users with session-based tracking

### 2. Request Processing
- Form submission for requesting certificates or data for selected points
- Status tracking for submitted requests
- Email notifications for request updates

### 3. Administrative Features
- Dashboard for managing submitted requests
- Batch processing of selected points
- Export functionality for selected stations

## Last Updated
May 1, 2024

## Backup and Maintenance

### 1. Backup Procedures
- Daily incremental backups
- Weekly full backups
- Monthly archive backups

### 2. Maintenance Tasks
- Weekly index optimization
- Monthly table optimization
- Quarterly data archiving

## Security

### 1. Access Control
- Role-based access control
- Database user permissions
- IP-based restrictions

### 2. Data Protection
- Encrypted sensitive data
- Audit logging
- Data masking for sensitive fields 