-- Data Import Script
-- Version 1.5
-- Created: 2024-04-11
-- Updated: 2024-04-12

-- Import GCP data
CREATE TEMPORARY TABLE temp_gcp (
    station_code VARCHAR(50),
    station_name VARCHAR(100),
    region VARCHAR(100),
    province VARCHAR(100),
    municipality VARCHAR(100),
    barangay VARCHAR(100),
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
    observation_date VARCHAR(20),
    monument_type VARCHAR(50),
    monument_status VARCHAR(20),
    monument_construction VARCHAR(100),
    authority VARCHAR(100),
    remarks TEXT
);

LOAD DATA LOCAL INFILE 'C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Drive/GCPs_Mar2025.csv'
INTO TABLE temp_gcp
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Process GCP data
INSERT INTO stations (
    station_id, station_name, station_type_id, establishment_date,
    monument_type, monument_status, monument_construction, authority, remarks
)
SELECT 
    station_code, station_name, 
    (SELECT type_id FROM station_types WHERE type_name = 'GCP'),
    STR_TO_DATE(observation_date, '%m/%d/%Y'),
    monument_type, monument_status, monument_construction, authority, remarks
FROM temp_gcp;

INSERT INTO station_locations (
    station_id, region, province, municipality, barangay
)
SELECT 
    station_code, region, province, municipality, barangay
FROM temp_gcp;

INSERT INTO station_coordinates (
    station_id, latitude_dms, longitude_dms, latitude_decimal, longitude_decimal,
    northing, easting, zone, ellipsoidal_height, orthometric_height,
    accuracy_class, horizontal_error, vertical_error, observation_date
)
SELECT 
    station_code, latitude_dms, longitude_dms, latitude_decimal, longitude_decimal,
    northing, easting, zone, ellipsoidal_height, orthometric_height,
    accuracy_class, horizontal_error, vertical_error, STR_TO_DATE(observation_date, '%m/%d/%Y')
FROM temp_gcp;

DROP TEMPORARY TABLE IF EXISTS temp_gcp;

-- Import Benchmark data
CREATE TEMPORARY TABLE temp_benchmark (
    station_code VARCHAR(50),
    station_name VARCHAR(100),
    region VARCHAR(100),
    province VARCHAR(100),
    municipality VARCHAR(100),
    barangay VARCHAR(100),
    latitude_dms VARCHAR(20),
    longitude_dms VARCHAR(20),
    latitude_decimal DECIMAL(10,8),
    longitude_decimal DECIMAL(11,8),
    northing DECIMAL(12,3),
    easting DECIMAL(12,3),
    zone VARCHAR(10),
    orthometric_height DECIMAL(10,3),
    accuracy_class VARCHAR(10),
    vertical_error DECIMAL(10,3),
    observation_date VARCHAR(20),
    monument_type VARCHAR(50),
    monument_status VARCHAR(20),
    monument_construction VARCHAR(100),
    authority VARCHAR(100),
    remarks TEXT
);

LOAD DATA LOCAL INFILE 'C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Drive/Benchmarks_Mar2025.csv'
INTO TABLE temp_benchmark
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Process Benchmark data
INSERT INTO stations (
    station_id, station_name, station_type_id, establishment_date,
    monument_type, monument_status, monument_construction, authority, remarks
)
SELECT 
    station_code, station_name, 
    (SELECT type_id FROM station_types WHERE type_name = 'BENCHMARK'),
    STR_TO_DATE(observation_date, '%m/%d/%Y'),
    monument_type, monument_status, monument_construction, authority, remarks
FROM temp_benchmark;

INSERT INTO station_locations (
    station_id, region, province, municipality, barangay
)
SELECT 
    station_code, region, province, municipality, barangay
FROM temp_benchmark;

INSERT INTO station_coordinates (
    station_id, latitude_dms, longitude_dms, latitude_decimal, longitude_decimal,
    northing, easting, zone, orthometric_height,
    accuracy_class, vertical_error, observation_date
)
SELECT 
    station_code, latitude_dms, longitude_dms, latitude_decimal, longitude_decimal,
    northing, easting, zone, orthometric_height,
    accuracy_class, vertical_error, STR_TO_DATE(observation_date, '%m/%d/%Y')
FROM temp_benchmark;

DROP TEMPORARY TABLE IF EXISTS temp_benchmark;

-- Import Gravity data
CREATE TEMPORARY TABLE temp_gravity (
    station_code VARCHAR(50),
    station_name VARCHAR(100),
    observed_value DECIMAL(15,6),
    year_measured VARCHAR(20),
    order_class VARCHAR(10),
    encoder VARCHAR(100),
    date_last_updated VARCHAR(20)
);

LOAD DATA LOCAL INFILE 'C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Drive/Gravity.csv'
INTO TABLE temp_gravity
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Process Gravity data
INSERT INTO stations (
    station_id, station_name, station_type_id, establishment_date,
    monument_status, authority
)
SELECT 
    station_code, station_name, 
    (SELECT type_id FROM station_types WHERE type_name = 'GRAVITY'),
    STR_TO_DATE(CONCAT(year_measured, '-01-01'), '%Y-%m-%d'),
    'ACTIVE', 'NGA'
FROM temp_gravity;

INSERT INTO station_observations (
    station_id, observation_type, observed_value, observation_date,
    order_class, encoder
)
SELECT 
    station_code, 'GRAVITY', observed_value,
    STR_TO_DATE(CONCAT(year_measured, '-01-01'), '%Y-%m-%d'),
    order_class, encoder
FROM temp_gravity;

DROP TEMPORARY TABLE IF EXISTS temp_gravity; 