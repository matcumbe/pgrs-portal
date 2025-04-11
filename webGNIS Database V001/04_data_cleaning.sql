-- Data Cleaning Procedures
-- Version 1.2
-- Created: 2024-04-11
-- Updated: 2024-04-12

DELIMITER //

-- Function to clean station names
CREATE FUNCTION clean_station_name(station_name VARCHAR(100)) 
RETURNS VARCHAR(100)
DETERMINISTIC
BEGIN
    DECLARE cleaned_name VARCHAR(100);
    SET cleaned_name = TRIM(station_name);
    SET cleaned_name = REGEXP_REPLACE(cleaned_name, '\\s+', ' ');
    SET cleaned_name = REGEXP_REPLACE(cleaned_name, '[^a-zA-Z0-9\\s\\-\\.]', '');
    RETURN cleaned_name;
END //

-- Function to validate coordinates
CREATE FUNCTION validate_coordinates(
    lat_dms VARCHAR(20),
    lon_dms VARCHAR(20),
    lat_dec DECIMAL(10,8),
    lon_dec DECIMAL(11,8),
    northing DECIMAL(12,3),
    easting DECIMAL(12,3),
    zone VARCHAR(10)
) 
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    -- Check if at least one coordinate format is provided
    IF (lat_dms IS NULL AND lat_dec IS NULL) OR 
       (lon_dms IS NULL AND lon_dec IS NULL) THEN
        RETURN FALSE;
    END IF;
    
    -- Validate decimal coordinates if provided
    IF lat_dec IS NOT NULL AND lon_dec IS NOT NULL THEN
        IF lat_dec < -90 OR lat_dec > 90 OR lon_dec < -180 OR lon_dec > 180 THEN
            RETURN FALSE;
        END IF;
    END IF;
    
    -- Validate UTM coordinates if provided
    IF northing IS NOT NULL AND easting IS NOT NULL AND zone IS NOT NULL THEN
        IF northing < 0 OR easting < 0 OR zone NOT REGEXP '^[0-9]{1,2}[A-Z]$' THEN
            RETURN FALSE;
        END IF;
    END IF;
    
    RETURN TRUE;
END //

-- Function to validate dates
CREATE FUNCTION validate_date(date_str VARCHAR(20)) 
RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE parsed_date DATE;
    
    -- Try different date formats
    SET parsed_date = STR_TO_DATE(date_str, '%Y-%m-%d');
    IF parsed_date IS NOT NULL THEN
        RETURN parsed_date;
    END IF;
    
    SET parsed_date = STR_TO_DATE(date_str, '%m/%d/%Y');
    IF parsed_date IS NOT NULL THEN
        RETURN parsed_date;
    END IF;
    
    SET parsed_date = STR_TO_DATE(date_str, '%Y');
    IF parsed_date IS NOT NULL THEN
        RETURN parsed_date;
    END IF;
    
    RETURN NULL;
END //

-- Procedure to clean and insert GCP data
CREATE PROCEDURE clean_and_insert_gcp(
    IN p_station_code VARCHAR(50),
    IN p_station_name VARCHAR(100),
    IN p_region VARCHAR(100),
    IN p_province VARCHAR(100),
    IN p_municipality VARCHAR(100),
    IN p_barangay VARCHAR(100),
    IN p_latitude_dms VARCHAR(20),
    IN p_longitude_dms VARCHAR(20),
    IN p_latitude_decimal DECIMAL(10,8),
    IN p_longitude_decimal DECIMAL(11,8),
    IN p_northing DECIMAL(12,3),
    IN p_easting DECIMAL(12,3),
    IN p_zone VARCHAR(10),
    IN p_ellipsoidal_height DECIMAL(10,3),
    IN p_orthometric_height DECIMAL(10,3),
    IN p_accuracy_class VARCHAR(10),
    IN p_horizontal_error DECIMAL(10,3),
    IN p_vertical_error DECIMAL(10,3),
    IN p_observation_date VARCHAR(20),
    IN p_monument_type VARCHAR(50),
    IN p_monument_status VARCHAR(20),
    IN p_monument_construction VARCHAR(100),
    IN p_authority VARCHAR(100),
    IN p_remarks TEXT
)
BEGIN
    DECLARE v_station_id VARCHAR(50);
    DECLARE v_cleaned_name VARCHAR(100);
    DECLARE v_valid_date DATE;
    DECLARE v_coordinates_valid BOOLEAN;
    DECLARE v_psgc_code VARCHAR(20);
    
    -- Clean station name
    SET v_cleaned_name = clean_station_name(p_station_name);
    
    -- Validate date
    SET v_valid_date = validate_date(p_observation_date);
    
    -- Validate coordinates
    SET v_coordinates_valid = validate_coordinates(
        p_latitude_dms, p_longitude_dms,
        p_latitude_decimal, p_longitude_decimal,
        p_northing, p_easting, p_zone
    );
    
    -- Get PSGC code
    SELECT psgc_code INTO v_psgc_code
    FROM administrative_regions
    WHERE region_name = p_region
    AND province = p_province
    AND municipality = p_municipality
    AND barangay = p_barangay
    LIMIT 1;
    
    -- Insert or update station
    INSERT INTO stations (
        station_id, station_name, psgc_code,
        monument_type, monument_status, monument_construction,
        authority, remarks
    ) VALUES (
        p_station_code, v_cleaned_name, v_psgc_code,
        p_monument_type, p_monument_status, p_monument_construction,
        p_authority, p_remarks
    ) ON DUPLICATE KEY UPDATE
        station_name = v_cleaned_name,
        psgc_code = v_psgc_code,
        monument_type = p_monument_type,
        monument_status = p_monument_status,
        monument_construction = p_monument_construction,
        authority = p_authority,
        remarks = p_remarks;
    
    -- Insert or update location
    INSERT INTO station_locations (
        station_id, region, province, municipality, barangay
    ) VALUES (
        p_station_code, p_region, p_province, p_municipality, p_barangay
    ) ON DUPLICATE KEY UPDATE
        region = p_region,
        province = p_province,
        municipality = p_municipality,
        barangay = p_barangay;
    
    -- Insert or update coordinates if valid
    IF v_coordinates_valid THEN
        INSERT INTO station_coordinates (
            station_id, latitude_dms, longitude_dms,
            latitude_decimal, longitude_decimal,
            northing, easting, zone,
            ellipsoidal_height, orthometric_height,
            accuracy_class, horizontal_error, vertical_error,
            observation_date
        ) VALUES (
            p_station_code, p_latitude_dms, p_longitude_dms,
            p_latitude_decimal, p_longitude_decimal,
            p_northing, p_easting, p_zone,
            p_ellipsoidal_height, p_orthometric_height,
            p_accuracy_class, p_horizontal_error, p_vertical_error,
            v_valid_date
        ) ON DUPLICATE KEY UPDATE
            latitude_dms = p_latitude_dms,
            longitude_dms = p_longitude_dms,
            latitude_decimal = p_latitude_decimal,
            longitude_decimal = p_longitude_decimal,
            northing = p_northing,
            easting = p_easting,
            zone = p_zone,
            ellipsoidal_height = p_ellipsoidal_height,
            orthometric_height = p_orthometric_height,
            accuracy_class = p_accuracy_class,
            horizontal_error = p_horizontal_error,
            vertical_error = p_vertical_error,
            observation_date = v_valid_date;
    END IF;
END //

-- Procedure to clean and insert benchmark data
CREATE PROCEDURE clean_and_insert_benchmark(
    IN p_station_code VARCHAR(50),
    IN p_station_name VARCHAR(100),
    IN p_region VARCHAR(100),
    IN p_province VARCHAR(100),
    IN p_municipality VARCHAR(100),
    IN p_barangay VARCHAR(100),
    IN p_latitude_dms VARCHAR(20),
    IN p_longitude_dms VARCHAR(20),
    IN p_latitude_decimal DECIMAL(10,8),
    IN p_longitude_decimal DECIMAL(11,8),
    IN p_northing DECIMAL(12,3),
    IN p_easting DECIMAL(12,3),
    IN p_zone VARCHAR(10),
    IN p_orthometric_height DECIMAL(10,3),
    IN p_accuracy_class VARCHAR(10),
    IN p_vertical_error DECIMAL(10,3),
    IN p_observation_date VARCHAR(20),
    IN p_monument_type VARCHAR(50),
    IN p_monument_status VARCHAR(20),
    IN p_monument_construction VARCHAR(100),
    IN p_authority VARCHAR(100),
    IN p_remarks TEXT
)
BEGIN
    DECLARE v_station_id VARCHAR(50);
    DECLARE v_cleaned_name VARCHAR(100);
    DECLARE v_valid_date DATE;
    DECLARE v_coordinates_valid BOOLEAN;
    DECLARE v_psgc_code VARCHAR(20);
    
    -- Clean station name
    SET v_cleaned_name = clean_station_name(p_station_name);
    
    -- Validate date
    SET v_valid_date = validate_date(p_observation_date);
    
    -- Validate coordinates
    SET v_coordinates_valid = validate_coordinates(
        p_latitude_dms, p_longitude_dms,
        p_latitude_decimal, p_longitude_decimal,
        p_northing, p_easting, p_zone
    );
    
    -- Get PSGC code
    SELECT psgc_code INTO v_psgc_code
    FROM administrative_regions
    WHERE region_name = p_region
    AND province = p_province
    AND municipality = p_municipality
    AND barangay = p_barangay
    LIMIT 1;
    
    -- Insert or update station
    INSERT INTO stations (
        station_id, station_name, psgc_code,
        monument_type, monument_status, monument_construction,
        authority, remarks
    ) VALUES (
        p_station_code, v_cleaned_name, v_psgc_code,
        p_monument_type, p_monument_status, p_monument_construction,
        p_authority, p_remarks
    ) ON DUPLICATE KEY UPDATE
        station_name = v_cleaned_name,
        psgc_code = v_psgc_code,
        monument_type = p_monument_type,
        monument_status = p_monument_status,
        monument_construction = p_monument_construction,
        authority = p_authority,
        remarks = p_remarks;
    
    -- Insert or update location
    INSERT INTO station_locations (
        station_id, region, province, municipality, barangay
    ) VALUES (
        p_station_code, p_region, p_province, p_municipality, p_barangay
    ) ON DUPLICATE KEY UPDATE
        region = p_region,
        province = p_province,
        municipality = p_municipality,
        barangay = p_barangay;
    
    -- Insert or update coordinates if valid
    IF v_coordinates_valid THEN
        INSERT INTO station_coordinates (
            station_id, latitude_dms, longitude_dms,
            latitude_decimal, longitude_decimal,
            northing, easting, zone,
            orthometric_height,
            accuracy_class, vertical_error,
            observation_date
        ) VALUES (
            p_station_code, p_latitude_dms, p_longitude_dms,
            p_latitude_decimal, p_longitude_decimal,
            p_northing, p_easting, p_zone,
            p_orthometric_height,
            p_accuracy_class, p_vertical_error,
            v_valid_date
        ) ON DUPLICATE KEY UPDATE
            latitude_dms = p_latitude_dms,
            longitude_dms = p_longitude_dms,
            latitude_decimal = p_latitude_decimal,
            longitude_decimal = p_longitude_decimal,
            northing = p_northing,
            easting = p_easting,
            zone = p_zone,
            orthometric_height = p_orthometric_height,
            accuracy_class = p_accuracy_class,
            vertical_error = p_vertical_error,
            observation_date = v_valid_date;
    END IF;
END //

-- Procedure to clean and insert gravity data
CREATE PROCEDURE clean_and_insert_gravity(
    IN p_station_code VARCHAR(50),
    IN p_station_name VARCHAR(100),
    IN p_observed_value DECIMAL(15,6),
    IN p_year_measured VARCHAR(20),
    IN p_order_class VARCHAR(10),
    IN p_encoder VARCHAR(100),
    IN p_date_last_updated VARCHAR(20)
)
BEGIN
    DECLARE v_cleaned_name VARCHAR(100);
    DECLARE v_valid_date DATE;
    
    -- Clean station name
    SET v_cleaned_name = clean_station_name(p_station_name);
    
    -- Validate date
    SET v_valid_date = validate_date(p_year_measured);
    
    -- Insert or update station
    INSERT INTO stations (
        station_id, station_name
    ) VALUES (
        p_station_code, v_cleaned_name
    ) ON DUPLICATE KEY UPDATE
        station_name = v_cleaned_name;
    
    -- Insert or update observation
    INSERT INTO station_observations (
        station_id, observation_type, observed_value,
        observation_date, order_class, encoder
    ) VALUES (
        p_station_code, 'GRAVITY', p_observed_value,
        v_valid_date, p_order_class, p_encoder
    ) ON DUPLICATE KEY UPDATE
        observed_value = p_observed_value,
        observation_date = v_valid_date,
        order_class = p_order_class,
        encoder = p_encoder;
END //

DELIMITER ; 