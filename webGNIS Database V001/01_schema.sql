-- webGNIS Database Schema
-- Version 1.1
-- Created: 2024-04-11
-- Updated: 2024-04-12
-- MySQL Compatible Version

-- Core Tables
CREATE TABLE reference_frames (
    frame_id INT AUTO_INCREMENT PRIMARY KEY,
    frame_name VARCHAR(50) NOT NULL,
    epoch DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE administrative_regions (
    region_id INT AUTO_INCREMENT PRIMARY KEY,
    psgc_code VARCHAR(20) UNIQUE NOT NULL,
    region_name VARCHAR(100) NOT NULL,
    parent_region_id INT,
    valid_from DATE NOT NULL,
    valid_until DATE,
    superseded_by_psgc VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_region_id) REFERENCES administrative_regions(region_id)
);

CREATE TABLE station_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    category ENUM('HORIZONTAL', 'VERTICAL', 'GRAVITY') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE stations (
    station_id VARCHAR(50) PRIMARY KEY,
    station_name VARCHAR(100) NOT NULL,
    station_type_id INT,
    establishment_date DATE,
    psgc_code VARCHAR(20),
    original_psgc_code VARCHAR(20),
    monument_type VARCHAR(50),
    monument_status ENUM('ACTIVE', 'LOST', 'DESTROYED', 'UNKNOWN') DEFAULT 'ACTIVE',
    monument_construction VARCHAR(100),
    authority VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_type_id) REFERENCES station_types(type_id),
    FOREIGN KEY (psgc_code) REFERENCES administrative_regions(psgc_code)
);

CREATE TABLE station_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(50) NOT NULL,
    island VARCHAR(50),
    region VARCHAR(100),
    province VARCHAR(100),
    municipality VARCHAR(100),
    barangay VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(station_id)
);

CREATE TABLE station_coordinates (
    coordinate_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(50) NOT NULL,
    reference_frame_id INT,
    latitude_dms VARCHAR(20),
    longitude_dms VARCHAR(20),
    latitude_decimal DECIMAL(10,8),
    longitude_decimal DECIMAL(11,8),
    northing DECIMAL(12,3),
    easting DECIMAL(12,3),
    zone VARCHAR(10),
    ellipsoidal_height DECIMAL(10,3),
    orthometric_height DECIMAL(10,3),
    accuracy_class VARCHAR(10),
    horizontal_error DECIMAL(10,3),
    vertical_error DECIMAL(10,3),
    observation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(station_id),
    FOREIGN KEY (reference_frame_id) REFERENCES reference_frames(frame_id)
);

CREATE TABLE station_observations (
    observation_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(50) NOT NULL,
    observation_type ENUM('HORIZONTAL', 'VERTICAL', 'GRAVITY') NOT NULL,
    observed_value DECIMAL(15,6),
    observation_date DATE,
    reference_frame_id INT,
    order_class VARCHAR(10),
    authority VARCHAR(100),
    encoder VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(station_id),
    FOREIGN KEY (reference_frame_id) REFERENCES reference_frames(frame_id)
);

CREATE TABLE station_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    station_id VARCHAR(50) NOT NULL,
    event_type ENUM('ESTABLISHMENT', 'REOBSERVATION', 'STATUS_CHANGE', 'UPDATE') NOT NULL,
    event_date DATE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(station_id)
);

-- Indexes
CREATE INDEX idx_station_name ON stations(station_name);
CREATE INDEX idx_station_type ON stations(station_type_id);
CREATE INDEX idx_station_location ON station_locations(region, province, municipality);
CREATE INDEX idx_station_coordinates ON station_coordinates(station_id, reference_frame_id);
CREATE INDEX idx_station_observations ON station_observations(station_id, observation_date);
CREATE INDEX idx_station_history ON station_history(station_id, event_date); 