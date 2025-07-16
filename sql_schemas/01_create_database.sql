-- Create the database
CREATE DATABASE IF NOT EXISTS webgnis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE webgnis_db;

-- Create core tables
CREATE TABLE IF NOT EXISTS reference_frames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS administrative_regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES administrative_regions(id)
);

CREATE TABLE IF NOT EXISTS station_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_code VARCHAR(50) NOT NULL UNIQUE,
    station_name VARCHAR(255) NOT NULL,
    station_type_id INT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    elevation DECIMAL(10, 3),
    administrative_region_id INT,
    status ENUM('active', 'destroyed', 'unstable', 'unknown') DEFAULT 'unknown',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_type_id) REFERENCES station_types(id),
    FOREIGN KEY (administrative_region_id) REFERENCES administrative_regions(id)
);

CREATE TABLE IF NOT EXISTS station_observations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    observation_date DATE NOT NULL,
    observed_value DECIMAL(15, 5),
    reference_frame_id INT,
    order_class VARCHAR(20),
    encoder VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id),
    FOREIGN KEY (reference_frame_id) REFERENCES reference_frames(id)
);

CREATE TABLE IF NOT EXISTS station_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id)
);

-- Insert initial data
INSERT INTO station_types (name, description) VALUES
('Horizontal Control Point', 'Geodetic control point for horizontal positioning'),
('Vertical Control Point', 'Geodetic control point for vertical positioning'),
('Gravity Station', 'Station for gravity measurements');

INSERT INTO reference_frames (name, description) VALUES
('WGS84', 'World Geodetic System 1984'),
('NAD83', 'North American Datum 1983'),
('NAVD88', 'North American Vertical Datum 1988'); 