# GNIS Database Documentation

## Overview

The GNIS database stores information about geodetic control points, specifically gravity stations and horizontal control points, along with their locations and related metadata. This documentation describes the database schema based on the `webgnis_db.sql` file.

## Database Schema

### 1. Gravity Stations Table (`grav_stations`)

```sql
CREATE TABLE `grav_stations` (
  `station_id` varchar(20) DEFAULT NULL,
  `station_name` varchar(100) DEFAULT NULL,
  `island_group` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `gravity_value` decimal(10,3) DEFAULT NULL,
  `standard_deviation` decimal(10,3) DEFAULT NULL,
  `date_measured` date DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `encoder` varchar(100) DEFAULT NULL,
  `date_last_updated` date DEFAULT NULL,
  `reference_file` varchar(255) DEFAULT NULL,
  `station_code` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Description:** Stores information about gravity measurement stations.

**Columns:**
*   `station_id`: Unique identifier for the station (VARCHAR).
*   `station_name`: Name of the station (VARCHAR).
*   `island_group`: Island group location (VARCHAR).
*   `region`: Region location (VARCHAR).
*   `province`: Province location (VARCHAR).
*   `city`: City/Municipality location (VARCHAR).
*   `barangay`: Barangay location (VARCHAR).
*   `latitude`: Latitude coordinate (DECIMAL).
*   `longitude`: Longitude coordinate (DECIMAL).
*   `description`: Textual description of the station (TEXT).
*   `gravity_value`: Measured gravity value (DECIMAL).
*   `standard_deviation`: Standard deviation of the measurement (DECIMAL).
*   `date_measured`: Date when the measurement was taken (DATE).
*   `order`: Order of the station (INT).
*   `encoder`: Name or identifier of the person who encoded the data (VARCHAR).
*   `date_last_updated`: Date when the record was last updated (DATE).
*   `reference_file`: Reference file associated with the station (VARCHAR).
*   `station_code`: Specific code for the station (VARCHAR).

### 2. Horizontal Geodetic Control Points Table (`hgcp_stations`)

```sql
CREATE TABLE `hgcp_stations` (
  `station_id` varchar(20) DEFAULT NULL,
  `station_name` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
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
  `utm_zone` decimal(5,1) DEFAULT NULL,
  `horizontal_date_entry` date DEFAULT NULL,
  `horizontal_datum` int(11) DEFAULT NULL,
  `horizontal_reference` varchar(100) DEFAULT NULL,
  `horizontal_authority` varchar(100) DEFAULT NULL,
  `horizontal_order` int(11) DEFAULT NULL,
  `date_established` date DEFAULT NULL,
  `date_est_month` int(11) DEFAULT NULL,
  `date_est_year` int(11) DEFAULT NULL,
  `date_est_day` int(11) DEFAULT NULL,
  `horizontal_date_computed` date DEFAULT NULL,
  `horizontal_fix` int(11) DEFAULT NULL,
  `ellipsoidal_height` decimal(10,3) DEFAULT NULL,
  `mark_status` int(11) DEFAULT NULL,
  `mark_type` int(11) DEFAULT NULL,
  `mark_const` int(11) DEFAULT NULL,
  `authority` varchar(100) DEFAULT NULL,
  `wgs84_north_degrees` int(11) DEFAULT NULL,
  `wgs84_north_minutes` int(11) DEFAULT NULL,
  `wgs84_north_seconds` decimal(10,6) DEFAULT NULL,
  `wgs84_east_degrees` int(11) DEFAULT NULL,
  `wgs84_east_minutes` int(11) DEFAULT NULL,
  `wgs84_east_seconds` decimal(10,6) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `ellipz` decimal(10,3) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `encoder` varchar(100) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `IsAdopted` int(11) DEFAULT NULL,
  `AdoptedBy` varchar(100) DEFAULT NULL,
  `dateUpdated` datetime DEFAULT NULL,
  `utm_x` decimal(10,3) DEFAULT NULL,
  `utm_y` decimal(10,3) DEFAULT NULL,
  `utm_zone_alt` int(11) DEFAULT NULL,
  `utm_x_wgs84` decimal(10,3) DEFAULT NULL,
  `utm_y_wgs84` decimal(10,3) DEFAULT NULL,
  `utm_zone_wgs84` int(11) DEFAULT NULL,
  `accuracy_class` decimal(5,1) DEFAULT NULL,
  `error_ellipse` decimal(10,3) DEFAULT NULL,
  `height_error` decimal(10,3) DEFAULT NULL,
  `itrf_lat_dd` int(11) DEFAULT NULL,
  `itrf_lat_mm` int(11) DEFAULT NULL,
  `itrf_lat_ss` decimal(10,6) DEFAULT NULL,
  `itrf_lon_dd` int(11) DEFAULT NULL,
  `itrf_lon_mm` int(11) DEFAULT NULL,
  `itrf_lon_ss` decimal(10,6) DEFAULT NULL,
  `itrf_ell_hgt` decimal(10,3) DEFAULT NULL,
  `itrf_ell_err` decimal(10,3) DEFAULT NULL,
  `itrf_hgt_err` decimal(10,3) DEFAULT NULL,
  `latitude_decimal` decimal(10,6) DEFAULT NULL,
  `longitude_decimal` decimal(10,6) DEFAULT NULL,
  `status_tag` int(11) DEFAULT NULL,
  `epoch` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Description:** Stores detailed information about horizontal geodetic control points.

## Relationships

*   Relationships between `grav_stations` and `hgcp_stations` or with other potential tables are not explicitly defined by foreign keys in the provided `webgnis_db.sql` schema.

## Indexes

*   Indexes are likely present on primary keys (if any were intended but not explicitly defined as such) and potentially other columns for performance. Specific index information is not available from the provided SQL dump's `CREATE TABLE` statements alone.

## Data Types

*   `varchar`: Variable-length strings.
*   `decimal`: Fixed-point numbers, used for coordinates, measurements, etc.
*   `int`: Integer numbers.
*   `text`: Long text descriptions.
*   `date`: Date values.
*   `datetime`: Date and time values.

## Constraints

*   No explicit primary key or foreign key constraints are defined in the provided `CREATE TABLE` statements for `grav_stations` and `hgcp_stations`.
*   `DEFAULT NULL` is used for most columns, indicating they can store NULL values.

## Views

*   No views related to `grav_stations` or `hgcp_stations` are defined in the provided `webgnis_db.sql`.

## Implementation Notes

*   The database schema consists of two main tables: `grav_stations` for gravity data and `hgcp_stations` for horizontal control point data.
*   Data integrity relies on application logic as explicit constraints (like primary/foreign keys) are missing in the provided schema definitions.

## Last Updated

(Please update with the current date when finalizing)