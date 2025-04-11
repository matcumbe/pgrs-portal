-- Data Cleaning and Validation Scripts
-- Version 1.0
-- Created: 2024-04-12

-- Function to clean station names
DELIMITER //
CREATE FUNCTION clean_station_name(station_name VARCHAR(100)) 
RETURNS VARCHAR(100)
DETERMINISTIC
BEGIN
    DECLARE cleaned_name VARCHAR(100);
    SET cleaned_name = TRIM(station_name);
    -- Remove multiple spaces
    SET cleaned_name = REGEXP_REPLACE(cleaned_name, '\\s+', ' ');
    -- Remove special characters except hyphens and parentheses
    SET cleaned_name = REGEXP_REPLACE(cleaned_name, '[^a-zA-Z0-9\\s\\(\\)\\-]', '');
    RETURN cleaned_name;
END //

-- Function to validate coordinates
CREATE FUNCTION validate_coordinates(
    lat_dms VARCHAR(20),
    lon_dms VARCHAR(20),
    lat_decimal DECIMAL(10,8),
    lon_decimal DECIMAL(11,8)
) 
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    -- Check if at least one format is provided
    IF (lat_dms IS NULL AND lat_decimal IS NULL) OR 
       (lon_dms IS NULL AND lon_decimal IS NULL) THEN
        RETURN FALSE;
    END IF;
    
    -- Validate decimal coordinates
    IF lat_decimal IS NOT NULL AND (lat_decimal < -90 OR lat_decimal > 90) THEN
        RETURN FALSE;
    END IF;
    
    IF lon_decimal IS NOT NULL AND (lon_decimal < -180 OR lon_decimal > 180) THEN
        RETURN FALSE;
    END IF;
    
    RETURN TRUE;
END //

-- Function to validate dates
CREATE FUNCTION validate_date(date_str VARCHAR(20)) 
RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE result DATE;
    SET result = NULL;
    
    -- Try different date formats
    IF date_str IS NOT NULL THEN
        BEGIN
            DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
            SET result = STR_TO_DATE(date_str, '%m/%d/%Y');
        END;
        
        IF result IS NULL THEN
            BEGIN
                DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
                SET result = STR_TO_DATE(date_str, '%Y-%m-%d');
            END;
        END IF;
    END IF;
    
    RETURN result;
END //

-- Procedure to clean and insert GCP data
CREATE PROCEDURE clean_and_insert_gcp(
    IN p_station_name VARCHAR(100),
    IN p_region VARCHAR(100),
    IN p_province VARCHAR(100),
    IN p_municipal VARCHAR(100),
    IN p_barangay VARCHAR(100),
    IN p_date_est VARCHAR(20),
    IN p_date_las_r VARCHAR(20),
    IN p_island VARCHAR(50),
    IN p_d_lat INT,
    IN p_m_lat INT,
    IN p_s_lat DECIMAL(10,6),
    IN p_d_long INT,
    IN p_m_long INT,
    IN p_s_long DECIMAL(10,6),
    IN p_northing DECIMAL(12,3),
    IN p_easting DECIMAL(12,3),
    IN p_zone VARCHAR(10),
    IN p_ell_hgt DECIMAL(10,3),
    IN p_mark_stat INT,
    IN p_mark_type INT,
    IN p_mark_const INT,
    IN p_authority VARCHAR(100),
    IN p_description TEXT,
    IN p_encoder VARCHAR(100),
    IN p_status INT,
    IN p_date_updated VARCHAR(20)
)
BEGIN
    DECLARE v_station_id VARCHAR(50);
    DECLARE v_cleaned_name VARCHAR(100);
    DECLARE v_est_date DATE;
    DECLARE v_last_obs_date DATE;
    DECLARE v_update_date DATE;
    
    -- Clean station name and generate ID
    SET v_cleaned_name = clean_station_name(p_station_name);
    SET v_station_id = CONCAT(SUBSTRING(p_region, 1, 2), '-', 
                            REGEXP_REPLACE(v_cleaned_name, '[^a-zA-Z0-9]', ''));
    
    -- Validate dates
    SET v_est_date = validate_date(p_date_est);
    SET v_last_obs_date = validate_date(p_date_las_r);
    SET v_update_date = validate_date(p_date_updated);
    
    -- Insert into stations table
    INSERT INTO stations (
        station_id,
        station_name,
        establishment_date,
        monument_type,
        monument_status,
        monument_construction,
        authority,
        remarks
    ) VALUES (
        v_station_id,
        v_cleaned_name,
        v_est_date,
        p_mark_type,
        CASE p_mark_stat 
            WHEN 1 THEN 'ACTIVE'
            WHEN 5 THEN 'LOST'
            ELSE 'UNKNOWN'
        END,
        p_mark_const,
        p_authority,
        p_description
    ) ON DUPLICATE KEY UPDATE
        station_name = v_cleaned_name,
        establishment_date = v_est_date,
        monument_type = p_mark_type,
        monument_status = CASE p_mark_stat 
            WHEN 1 THEN 'ACTIVE'
            WHEN 5 THEN 'LOST'
            ELSE 'UNKNOWN'
        END,
        monument_construction = p_mark_const,
        authority = p_authority,
        remarks = p_description,
        updated_at = CURRENT_TIMESTAMP;
    
    -- Insert into station_locations
    INSERT INTO station_locations (
        station_id,
        island,
        region,
        province,
        municipality,
        barangay,
        description
    ) VALUES (
        v_station_id,
        p_island,
        p_region,
        p_province,
        p_municipal,
        p_barangay,
        p_description
    ) ON DUPLICATE KEY UPDATE
        island = p_island,
        region = p_region,
        province = p_province,
        municipality = p_municipal,
        barangay = p_barangay,
        description = p_description,
        updated_at = CURRENT_TIMESTAMP;
    
    -- Insert into station_coordinates
    INSERT INTO station_coordinates (
        station_id,
        latitude_dms,
        longitude_dms,
        northing,
        easting,
        zone,
        ellipsoidal_height,
        observation_date
    ) VALUES (
        v_station_id,
        CONCAT(p_d_lat, '°', p_m_lat, '''', p_s_lat, '"'),
        CONCAT(p_d_long, '°', p_m_long, '''', p_s_long, '"'),
        p_northing,
        p_easting,
        p_zone,
        p_ell_hgt,
        v_last_obs_date
    ) ON DUPLICATE KEY UPDATE
        latitude_dms = CONCAT(p_d_lat, '°', p_m_lat, '''', p_s_lat, '"'),
        longitude_dms = CONCAT(p_d_long, '°', p_m_long, '''', p_s_long, '"'),
        northing = p_northing,
        easting = p_easting,
        zone = p_zone,
        ellipsoidal_height = p_ell_hgt,
        observation_date = v_last_obs_date,
        updated_at = CURRENT_TIMESTAMP;
    
    -- Insert into station_history if there's a status change
    IF p_status = 5 THEN
        INSERT INTO station_history (
            station_id,
            event_type,
            event_date,
            description
        ) VALUES (
            v_station_id,
            'STATUS_CHANGE',
            v_update_date,
            CONCAT('Station marked as LOST on ', v_update_date)
        );
    END IF;
END //

DELIMITER ; 