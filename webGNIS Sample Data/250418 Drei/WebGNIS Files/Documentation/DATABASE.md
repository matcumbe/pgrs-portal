# GNIS Database Documentation

## Overview

The GNIS database ecosystem consists of multiple databases that store information about geodetic control points, user accounts, and data access requests. This document outlines the structure of these databases and their relationships.

## Main GNIS Database

The main GNIS database stores information about three types of geodetic control points: vertical stations (VGCP), horizontal control points (HGCP), and gravity stations. Each type is stored in its own dedicated table with specific fields relevant to that type of station.

### 1. Vertical Stations Table (`vgcp_stations`)

```sql
CREATE TABLE `vgcp_stations` (
  `station_id` VARCHAR(20) PRIMARY KEY,
  `station_name` varchar(100) DEFAULT NULL,
  `station_code` varchar(50) DEFAULT NULL,
  `island_group` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `elevation` decimal(10,3) DEFAULT NULL,
  `bm_plus` decimal(10,3) DEFAULT NULL,
  `accuracy_class` varchar(20) DEFAULT NULL,
  `elevation_order` varchar(10) DEFAULT NULL,
  `elevation_datum` varchar(50) DEFAULT NULL,
  `elevation_authority` varchar(100) DEFAULT NULL,
  `date_established` date DEFAULT NULL,
  `date_last_updated` date DEFAULT NULL,
  `encoder` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status_tag` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Description:** Stores information about vertical geodetic control points.

**Key Columns:**
*   `station_id`: Unique identifier for the station (VARCHAR, PRIMARY KEY).
*   `station_name`: Name of the station (VARCHAR).
*   `station_code`: Specific code for the station (VARCHAR).
*   `island_group`, `region`, `province`, `city`, `barangay`: Location information.
*   `latitude`, `longitude`: Geographic coordinates (DECIMAL).
*   `elevation`: Elevation value in meters (DECIMAL).
*   `bm_plus`: Benchmark plus value (DECIMAL).
*   `accuracy_class`: Class indicating measurement accuracy (VARCHAR). Common values include "1CM", "2CM", "3CM", "5CM", "10CM", "2CM FROM M", etc.
*   `elevation_order`: Order of the elevation measurement (VARCHAR).
*   `elevation_datum`: Reference datum for elevation (VARCHAR).
*   `date_established`, `date_last_updated`: Relevant dates.
*   `is_active`, `status_tag`: Status indicators.

### 2. Horizontal Geodetic Control Points Table (`hgcp_stations`)

```sql
CREATE TABLE `hgcp_stations` (
  `station_id` VARCHAR(20) PRIMARY KEY,
  `station_name` varchar(100) DEFAULT NULL,
  `station_code` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `date_last_updated` date DEFAULT NULL,
  `island_group` varchar(50) DEFAULT NULL,
  `latitude_degrees` int(11) DEFAULT NULL,
  `latitude_minutes` int(11) DEFAULT NULL,
  `latitude_seconds` decimal(10,6) DEFAULT NULL,
  `longitude_degrees` int(11) DEFAULT NULL,
  `longitude_minutes` int(11) DEFAULT NULL,
  `longitude_seconds` decimal(10,6) DEFAULT NULL,
  `utm_northing` decimal(10,3) DEFAULT NULL,
  `utm_easting` decimal(10,3) DEFAULT NULL,
  `utm_zone` varchar(10) DEFAULT NULL,
  `horizontal_datum` varchar(50) DEFAULT NULL,
  `horizontal_order` varchar(10) DEFAULT NULL,
  `date_established` date DEFAULT NULL,
  `ellipsoidal_height` decimal(10,3) DEFAULT NULL,
  `mark_status` varchar(50) DEFAULT NULL,
  `mark_type` varchar(50) DEFAULT NULL,
  `mark_const` varchar(50) DEFAULT NULL,
  `authority` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `encoder` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status_tag` int(11) DEFAULT 1,
  `itrf_lat_dd` int(11) DEFAULT NULL,
  `itrf_lat_mm` int(11) DEFAULT NULL,
  `itrf_lat_ss` decimal(10,6) DEFAULT NULL,
  `itrf_lon_dd` int(11) DEFAULT NULL,
  `itrf_lon_mm` int(11) DEFAULT NULL,
  `itrf_lon_ss` decimal(10,6) DEFAULT NULL,
  `itrf_ell_hgt` decimal(10,3) DEFAULT NULL,
  `itrf_ell_err` decimal(10,3) DEFAULT NULL,
  `itrf_hgt_err` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Description:** Stores detailed information about horizontal geodetic control points.

**Key Columns:**
*   `station_id`: Unique identifier for the station (VARCHAR, PRIMARY KEY).
*   `station_name`, `station_code`: Station identifiers.
*   `latitude`, `longitude`: Decimal coordinates (DECIMAL).
*   `latitude_degrees`, `latitude_minutes`, `latitude_seconds`: DMS format for latitude.
*   `longitude_degrees`, `longitude_minutes`, `longitude_seconds`: DMS format for longitude.
*   `utm_northing`, `utm_easting`, `utm_zone`: UTM coordinate information.
*   `horizontal_datum`: Reference datum for horizontal measurements.
*   `horizontal_order`: Order of the horizontal measurement.
*   `ellipsoidal_height`: Height above the ellipsoid (DECIMAL).
*   `itrf_*` fields: ITRF coordinate information (International Terrestrial Reference Frame).

### 3. Gravity Stations Table (`grav_stations`)

```sql
CREATE TABLE `grav_stations` (
  `station_id` VARCHAR(20) PRIMARY KEY,
  `station_name` varchar(100) DEFAULT NULL,
  `station_code` varchar(50) DEFAULT NULL,
  `island_group` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `gravity_value` decimal(10,3) DEFAULT NULL,
  `standard_deviation` decimal(10,5) DEFAULT NULL,
  `date_measured` date DEFAULT NULL,
  `gravity_order` varchar(10) DEFAULT NULL,
  `gravity_datum` varchar(50) DEFAULT NULL,
  `gravity_meter` varchar(100) DEFAULT NULL,
  `encoder` varchar(100) DEFAULT NULL,
  `date_last_updated` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status_tag` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Description:** Stores information about gravity measurement stations.

**Key Columns:**
*   `station_id`: Unique identifier for the station (VARCHAR, PRIMARY KEY).
*   `station_name`, `station_code`: Station identifiers.
*   `latitude`, `longitude`: Geographic coordinates (DECIMAL).
*   `gravity_value`: Measured gravity value (DECIMAL).
*   `standard_deviation`: Standard deviation of the measurement (DECIMAL).
*   `date_measured`: Date when the measurement was taken (DATE).
*   `gravity_order`: Order/quality of the gravity measurement.
*   `gravity_datum`: Reference datum for gravity measurements.
*   `gravity_meter`: Equipment used for measurement.

## Common Fields Across Tables

All three tables share several common fields:
- `station_id`: Primary key, unique identifier
- `station_name`: Name of the station
- `station_code`: Code assigned to the station
- `latitude`, `longitude`: Geographic coordinates
- `region`, `province`, `city`, `barangay`: Location hierarchy
- `description`: Text description of the station
- `encoder`: Person who encoded the data
- `date_last_updated`: Last update timestamp
- `is_active`, `status_tag`: Status indicators

## Type-Specific Fields

### Vertical Stations
- `elevation`: Height measurement in meters
- `bm_plus`: Benchmark plus value
- `accuracy_class`: Accuracy classification (e.g., "2CM", "3CM FROM M")
- `elevation_order`: Order/precision of elevation measurement
- `elevation_datum`: Reference datum for elevation

### Horizontal Stations
- `ellipsoidal_height`: Height above ellipsoid
- `horizontal_order`: Order/precision of horizontal measurement
- `horizontal_datum`: Reference datum (e.g., "WGS84", "PRS92")
- UTM coordinates: `utm_northing`, `utm_easting`, `utm_zone`
- ITRF coordinates: Various `itrf_*` fields for international reference frame

### Gravity Stations
- `gravity_value`: Measured gravity value
- `standard_deviation`: Precision measurement
- `gravity_order`: Order/precision of gravity measurement
- `gravity_datum`: Reference datum for gravity
- `gravity_meter`: Measurement equipment used

## Special Field: Accuracy Class

The `accuracy_class` field in the `vgcp_stations` table requires special handling due to variations in data format. Common values include:

- "1CM" - 1 centimeter accuracy
- "2CM" - 2 centimeter accuracy
- "3CM" - 3 centimeter accuracy
- "5CM" - 5 centimeter accuracy
- "10CM" - 10 centimeter accuracy
- "2CM FROM M" - 2 centimeter from monument
- "3CM FROM M" - 3 centimeter from monument
- "5CM FROM M" - 5 centimeter from monument
- "0 CM" - Zero centimeter (default)

The application's frontend handles these variations by dynamically creating dropdown options for non-standard values.

## Database Relationships

The database design follows a segregated approach where each station type has its own dedicated table. There are no explicit foreign key relationships between these tables. The integration happens at the application level based on:

1. Common location hierarchies (region, province, city, barangay)
2. Geographic proximity (latitude, longitude)
3. Station naming conventions

## Implementation Notes

- Station IDs use VARCHAR type to accommodate alphanumeric identifiers
- The system uses `is_active` and `status_tag` flags for soft deletion
- Date fields use the MySQL DATE type for proper date handling
- Decimal fields use appropriate precision for coordinates and measurements
- The `accuracy_class` field uses VARCHAR to accommodate various formats

## User Management Database (webgnis_users)

The `webgnis_users` database stores user account information, authentication details, and user profiles.

### 1. Users Table
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact_number VARCHAR(50) NOT NULL,
    user_type ENUM('individual', 'company', 'admin') NOT NULL,
    sex_id INT,
    name_on_certificate VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (sex_id) REFERENCES sexes(id)
);
```

### 2. Individual Details Table
```sql
CREATE TABLE individual_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    birth_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 3. Company Details Table
```sql
CREATE TABLE company_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    sector_id INT NOT NULL,
    company_address TEXT NOT NULL,
    authorized_representative VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES sectors(id)
);
```

### 4. Sectors Table
```sql
CREATE TABLE sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(100) NOT NULL UNIQUE
);
```

### 5. Sexes Table
```sql
CREATE TABLE sexes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sex_name VARCHAR(50) NOT NULL UNIQUE
);
```

## Ticket System Database (webgnis_tickets)

The `webgnis_tickets` database manages data access requests, payments, and processing status.

### 1. Tickets Table
```sql
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'awaiting_payment', 'payment_uploaded', 'verified', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    purpose VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES webgnis_users.users(user_id) ON DELETE CASCADE
);
```

**Description:** Main table for tracking data requests.

**Key Columns:**
* `ticket_id`: Unique identifier for the ticket.
* `user_id`: Reference to the user who created the request.
* `request_date`: When the request was submitted.
* `status`: Current status of the request.
* `purpose`: Why the data is being requested.
* `total_amount`: Total cost of the requested data.
* `notes`: Additional information about the request.

### 2. Ticket Items Table
```sql
CREATE TABLE ticket_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    gcp_id VARCHAR(50) NOT NULL,
    gcp_type VARCHAR(50) NOT NULL,
    coordinates VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) DEFAULT 0.00,
    CONSTRAINT fk_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

**Description:** Stores information about the specific GCP stations requested.

**Key Columns:**
* `item_id`: Unique identifier for the ticket item.
* `ticket_id`: Reference to the parent ticket.
* `gcp_id`: Reference to the station ID.
* `gcp_type`: Type of GCP (vertical, horizontal, gravity).
* `coordinates`: Geographic coordinates of the station.
* `price`: Price for this specific item.

### 3. Payments Table
```sql
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    reference_number VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'LinkBiz',
    amount DECIMAL(10, 2) NOT NULL,
    receipt_image VARCHAR(255), /* Path to uploaded receipt image */
    payment_date DATETIME,
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    verified_by VARCHAR(100),
    verification_date DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

**Description:** Tracks payment information for ticket requests.

**Key Columns:**
* `payment_id`: Unique identifier for the payment.
* `ticket_id`: Reference to the ticket being paid for.
* `reference_number`: Payment reference from LinkBiz.
* `receipt_image`: Path to the uploaded receipt image.
* `verification_status`: Whether the payment has been verified.
* `verified_by`: Admin who verified the payment.

### 4. Ticket History Table
```sql
CREATE TABLE ticket_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

**Description:** Maintains an audit trail of status changes for tickets.

**Key Columns:**
* `history_id`: Unique identifier for the history entry.
* `ticket_id`: Reference to the ticket.
* `status`: Status value that was set.
* `changed_by`: User who made the change.
* `notes`: Any notes associated with the status change.

## Database Relationships

### Cross-Database Relationships

The system uses cross-database foreign key relationships to maintain data integrity:

1. The `tickets` table in `webgnis_tickets` references the `users` table in `webgnis_users` via the `user_id` field.
2. The `ticket_items` table references GCP stations in the main GNIS database via the `gcp_id` and `gcp_type` fields (logical relationship, not enforced by FK constraints).

### Key Workflow Relationships

- Each user (from `webgnis_users.users`) can have multiple tickets (in `webgnis_tickets.tickets`).
- Each ticket can have multiple ticket items (GCP stations).
- Each ticket can have one payment record.
- Each ticket can have multiple history entries tracking status changes.

## Implementation Notes

- The `webgnis_tickets` database relies on cross-database foreign key references, which requires proper permissions.
- File uploads for receipt images are stored in a designated directory with references in the database.
- All financial transactions are tracked with appropriate audit trails.
- Status changes are logged to maintain a complete history of request processing.

## Last Updated

May 1, 2025