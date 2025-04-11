-- Data Import Script
-- Version 1.0
-- Created: 2024-04-12

-- Function to load GCP data from CSV
DELIMITER //
CREATE PROCEDURE import_gcp_data(IN file_path VARCHAR(255))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE station_name VARCHAR(100);
    DECLARE region VARCHAR(100);
    DECLARE province VARCHAR(100);
    DECLARE municipality VARCHAR(100);
    DECLARE barangay VARCHAR(100);
    DECLARE date_est VARCHAR(20);
    DECLARE date_last_r VARCHAR(20);
    DECLARE island VARCHAR(50);
    DECLARE d_lat INT;
    DECLARE m_lat INT;
    DECLARE s_lat DECIMAL(10,6);
    DECLARE d_long INT;
    DECLARE m_long INT;
    DECLARE s_long DECIMAL(10,6);
    DECLARE northing DECIMAL(12,3);
    DECLARE easting DECIMAL(12,3);
    DECLARE zone VARCHAR(10);
    DECLARE ell_hgt DECIMAL(10,3);
    DECLARE mark_stat INT;
    DECLARE mark_type INT;
    DECLARE mark_const INT;
    DECLARE authority VARCHAR(100);
    DECLARE description TEXT;
    DECLARE encoder VARCHAR(100);
    DECLARE status INT;
    DECLARE date_updated VARCHAR(20);
    
    -- Create temporary table for loading data
    CREATE TEMPORARY TABLE temp_gcp (
        station_name VARCHAR(100),
        region VARCHAR(100),
        province VARCHAR(100),
        municipality VARCHAR(100),
        barangay VARCHAR(100),
        date_est VARCHAR(20),
        date_last_r VARCHAR(20),
        island VARCHAR(50),
        d_lat INT,
        m_lat INT,
        s_lat DECIMAL(10,6),
        d_long INT,
        m_long INT,
        s_long DECIMAL(10,6),
        northing DECIMAL(12,3),
        easting DECIMAL(12,3),
        zone VARCHAR(10),
        ell_hgt DECIMAL(10,3),
        mark_stat INT,
        mark_type INT,
        mark_const INT,
        authority VARCHAR(100),
        description TEXT,
        encoder VARCHAR(100),
        status INT,
        date_updated VARCHAR(20)
    );
    
    -- Load data from CSV
    LOAD DATA INFILE file_path
    INTO TABLE temp_gcp
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
    IGNORE 1 ROWS;
    
    -- Process each record
    DECLARE cur CURSOR FOR SELECT * FROM temp_gcp;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO station_name, region, province, municipality, barangay,
                      date_est, date_last_r, island, d_lat, m_lat, s_lat,
                      d_long, m_long, s_long, northing, easting, zone,
                      ell_hgt, mark_stat, mark_type, mark_const, authority,
                      description, encoder, status, date_updated;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Call cleaning procedure for each record
        CALL clean_and_insert_gcp(
            station_name, region, province, municipality, barangay,
            date_est, date_last_r, island, d_lat, m_lat, s_lat,
            d_long, m_long, s_long, northing, easting, zone,
            ell_hgt, mark_stat, mark_type, mark_const, authority,
            description, encoder, status, date_updated
        );
    END LOOP;
    
    CLOSE cur;
    DROP TEMPORARY TABLE temp_gcp;
END //

-- Function to load Gravity data from CSV
CREATE PROCEDURE import_gravity_data(IN file_path VARCHAR(255))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE station_code VARCHAR(50);
    DECLARE station_name VARCHAR(100);
    DECLARE observed_value DECIMAL(15,6);
    DECLARE year_measured INT;
    DECLARE order_class VARCHAR(10);
    DECLARE encoder VARCHAR(100);
    DECLARE date_updated VARCHAR(20);
    
    -- Create temporary table for loading data
    CREATE TEMPORARY TABLE temp_gravity (
        station_code VARCHAR(50),
        station_name VARCHAR(100),
        observed_value DECIMAL(15,6),
        year_measured INT,
        order_class VARCHAR(10),
        encoder VARCHAR(100),
        date_updated VARCHAR(20)
    );
    
    -- Load data from CSV
    LOAD DATA INFILE file_path
    INTO TABLE temp_gravity
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
    IGNORE 1 ROWS;
    
    -- Process each record
    DECLARE cur CURSOR FOR SELECT * FROM temp_gravity;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO station_code, station_name, observed_value,
                      year_measured, order_class, encoder, date_updated;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Insert into stations table
        INSERT INTO stations (
            station_id,
            station_name,
            station_type_id,
            establishment_date,
            monument_status,
            authority,
            remarks
        ) VALUES (
            station_code,
            station_name,
            (SELECT type_id FROM station_types WHERE type_name = 'GRAVITY'),
            STR_TO_DATE(CONCAT(year_measured, '-01-01'), '%Y-%m-%d'),
            'ACTIVE',
            'NGA',
            CONCAT('Gravity station established in ', year_measured)
        ) ON DUPLICATE KEY UPDATE
            station_name = station_name,
            updated_at = CURRENT_TIMESTAMP;
        
        -- Insert into station_observations
        INSERT INTO station_observations (
            station_id,
            observation_type,
            observed_value,
            observation_date,
            order_class,
            encoder
        ) VALUES (
            station_code,
            'GRAVITY',
            observed_value,
            STR_TO_DATE(CONCAT(year_measured, '-01-01'), '%Y-%m-%d'),
            order_class,
            encoder
        );
    END LOOP;
    
    CLOSE cur;
    DROP TEMPORARY TABLE temp_gravity;
END //

DELIMITER ; 