-- Create the database
CREATE DATABASE IF NOT EXISTS webgnis_db;
USE webgnis_db;

-- Create GRAV table (mirroring GRAV_stand_data_(NCR).csv)
CREATE TABLE grav_stations (
    station_id VARCHAR(20),
    station_name VARCHAR(100),
    island_group VARCHAR(50),
    region VARCHAR(100),
    province VARCHAR(100),
    city VARCHAR(100),
    barangay VARCHAR(100),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    description TEXT,
    gravity_value DECIMAL(10, 3),
    standard_deviation DECIMAL(10, 3),
    date_measured DATE,
    `order` INT,
    encoder VARCHAR(100),
    date_last_updated DATE,
    reference_file VARCHAR(255),
    station_code VARCHAR(50)
);

-- Create HGCP table (mirroring HGCP_stand_data(NCR)_processed.csv)
CREATE TABLE hgcp_stations (
    station_id VARCHAR(20),
    station_name VARCHAR(100),
    region VARCHAR(100),
    province VARCHAR(100),
    city VARCHAR(100),
    barangay VARCHAR(100),
    date_last_updated DATE,
    island_group VARCHAR(50),
    latitude_degrees INT,
    latitude_minutes INT,
    latitude_seconds DECIMAL(10, 6),
    longitude_degrees INT,
    longitude_minutes INT,
    longitude_seconds DECIMAL(10, 6),
    utm_northing DECIMAL(10, 3),
    utm_easting DECIMAL(10, 3),
    utm_zone DECIMAL(5, 1),
    horizontal_date_entry DATE,
    horizontal_datum INT,
    horizontal_reference VARCHAR(100),
    horizontal_authority VARCHAR(100),
    horizontal_order INT,
    date_established DATE,
    date_est_month INT,
    date_est_year INT,
    date_est_day INT,
    horizontal_date_computed DATE,
    horizontal_fix INT,
    ellipsoidal_height DECIMAL(10, 3),
    mark_status INT,
    mark_type INT,
    mark_const INT,
    authority VARCHAR(100),
    wgs84_north_degrees INT,
    wgs84_north_minutes INT,
    wgs84_north_seconds DECIMAL(10, 6),
    wgs84_east_degrees INT,
    wgs84_east_minutes INT,
    wgs84_east_seconds DECIMAL(10, 6),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    ellipz DECIMAL(10, 3),
    description TEXT,
    encoder VARCHAR(100),
    status INT,
    IsAdopted INT,
    AdoptedBy VARCHAR(100),
    dateUpdated DATETIME,
    utm_x DECIMAL(10, 3),
    utm_y DECIMAL(10, 3),
    utm_zone_alt INT,
    utm_x_wgs84 DECIMAL(10, 3),
    utm_y_wgs84 DECIMAL(10, 3),
    utm_zone_wgs84 INT,
    accuracy_class DECIMAL(5, 1),
    error_ellipse DECIMAL(10, 3),
    height_error DECIMAL(10, 3),
    itrf_lat_dd INT,
    itrf_lat_mm INT,
    itrf_lat_ss DECIMAL(10, 6),
    itrf_lon_dd INT,
    itrf_lon_mm INT,
    itrf_lon_ss DECIMAL(10, 6),
    itrf_ell_hgt DECIMAL(10, 3),
    itrf_ell_err DECIMAL(10, 3),
    itrf_hgt_err DECIMAL(10, 3),
    latitude_decimal DECIMAL(10, 6),
    longitude_decimal DECIMAL(10, 6),
    status_tag INT,
    epoch DECIMAL(10, 2)
);

-- Create VGCP table (mirroring VGCP_stand_data(NCR)_processed.csv)
CREATE TABLE vgcp_stations (
    station_id VARCHAR(20),
    station_name VARCHAR(100),
    new_station_name VARCHAR(100),
    region VARCHAR(100),
    province VARCHAR(100),
    city VARCHAR(100),
    barangay VARCHAR(100),
    date_established DATE,
    date_last_updated DATE,
    island_group VARCHAR(50),
    latitude_degrees INT,
    latitude_minutes INT,
    latitude_seconds DECIMAL(10, 6),
    longitude_degrees INT,
    longitude_minutes INT,
    longitude_seconds DECIMAL(10, 6),
    horizontal_coords_source VARCHAR(100),
    horizontal_date_entry DATE,
    horizontal_datum INT,
    horizontal_reference VARCHAR(100),
    elevation_horizontal_datum VARCHAR(100),
    horizontal_authority VARCHAR(100),
    horizontal_order INT,
    horizontal_date_computed DATE,
    horizontal_fix INT,
    elevation DECIMAL(10, 3),
    ellipsoidal_height DECIMAL(10, 3),
    elevation_date_entry DATE,
    elevation_datum INT,
    elevation_established_datum VARCHAR(100),
    elevation_authority VARCHAR(100),
    elevation_order INT,
    elevation_date_computed DATE,
    elevation_fix INT,
    mark_status INT,
    mark_type INT,
    mark_const INT,
    authority VARCHAR(100),
    wgs84ND INT,
    wgs84NM INT,
    wgs84NS DECIMAL(10, 6),
    wgs84ED INT,
    wgs84EM INT,
    wgs84ES DECIMAL(10, 6),
    ellipz DECIMAL(10, 3),
    image VARCHAR(255),
    inscription TEXT,
    description TEXT,
    encoder VARCHAR(100),
    ucode INT,
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    date_updated DATETIME,
    bm_plus DECIMAL(10, 3),
    accuracy_class VARCHAR(10),
    lon VARCHAR(50),
    lat VARCHAR(50),
    elevation_alt DECIMAL(10, 3),
    latitude_decimal DECIMAL(10, 6),
    longitude_decimal DECIMAL(10, 6),
    status_tag INT,
    epoch DECIMAL(10, 2)
);

-- Create gravity measurements table
CREATE TABLE gravity_measurements (
    measurement_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(20),
    gravity_value DECIMAL(10, 3) NULL,
    standard_deviation DECIMAL(10, 3) NULL,
    date_measured DATE NULL,
    FOREIGN KEY (station_id) REFERENCES grav_stations(station_id)
);

-- Create HGCP measurements table
CREATE TABLE hgcp_measurements (
    measurement_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(20),
    utm_northing DECIMAL(10, 3) NULL,
    utm_easting DECIMAL(10, 3) NULL,
    utm_zone DECIMAL(5, 1) NULL,
    ellipsoidal_height DECIMAL(10, 3) NULL,
    horizontal_accuracy DECIMAL(10, 3) NULL,
    date_measured DATE NULL,
    FOREIGN KEY (station_id) REFERENCES hgcp_stations(station_id)
);

-- Create VGCP measurements table
CREATE TABLE vgcp_measurements (
    measurement_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(20),
    elevation DECIMAL(10, 3) NULL,
    bm_plus DECIMAL(10, 3) NULL,
    vertical_accuracy DECIMAL(10, 3) NULL,
    date_measured DATE NULL,
    FOREIGN KEY (station_id) REFERENCES vgcp_stations(station_id)
);

-- Create indexes for better query performance
CREATE INDEX idx_station_name ON grav_stations(station_name);
CREATE INDEX idx_station_code ON grav_stations(station_code);
CREATE INDEX idx_location ON grav_stations(city, barangay);
CREATE INDEX idx_measurement_date ON gravity_measurements(date_measured);
CREATE INDEX idx_hgcp_date ON hgcp_stations(date_last_updated);
CREATE INDEX idx_vgcp_date ON vgcp_stations(date_last_updated); 