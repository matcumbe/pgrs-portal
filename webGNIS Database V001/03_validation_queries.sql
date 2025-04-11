-- Validation Queries for webGNIS Database
-- Version 1.1
-- Created: 2024-04-11
-- Updated: 2024-04-12
-- MySQL Compatible Version

-- 1. Check for temporal consistency in observations
CREATE OR REPLACE VIEW vw_temporal_consistency AS
SELECT 
    s.station_id, 
    s.station_name,
    s.establishment_date,
    MIN(so.observation_date) as first_observation,
    MAX(so.observation_date) as last_observation,
    COUNT(*) as observation_count
FROM stations s
JOIN station_observations so ON s.station_id = so.station_id
GROUP BY s.station_id, s.station_name, s.establishment_date
HAVING MIN(so.observation_date) < s.establishment_date;

-- 2. Identify potentially merged cities
CREATE OR REPLACE VIEW vw_city_changes AS
SELECT 
    s.station_id,
    s.station_name,
    ar1.region_name as original_city,
    ar2.region_name as current_city,
    s.establishment_date,
    so.observation_date,
    so.observed_value
FROM stations s
JOIN administrative_regions ar1 ON s.original_psgc_code = ar1.psgc_code
JOIN administrative_regions ar2 ON s.psgc_code = ar2.psgc_code
JOIN station_observations so ON s.station_id = so.station_id
WHERE s.original_psgc_code != s.psgc_code
ORDER BY s.station_name, so.observation_date;

-- 3. Quality control checks for coordinates
CREATE OR REPLACE VIEW vw_coordinate_quality AS
SELECT 
    c1.station_id,
    s.station_name,
    c1.observation_date as date1,
    c2.observation_date as date2,
    ABS(c1.latitude_decimal - c2.latitude_decimal) as lat_diff,
    ABS(c1.longitude_decimal - c2.longitude_decimal) as lon_diff,
    ABS(c1.orthometric_height - c2.orthometric_height) as height_diff,
    CASE 
        WHEN ABS(c1.latitude_decimal - c2.latitude_decimal) > 0.0001 THEN 'LATITUDE_WARNING'
        WHEN ABS(c1.longitude_decimal - c2.longitude_decimal) > 0.0001 THEN 'LONGITUDE_WARNING'
        WHEN ABS(c1.orthometric_height - c2.orthometric_height) > 0.1 THEN 'HEIGHT_WARNING'
        ELSE 'OK'
    END as quality_status
FROM station_coordinates c1
JOIN station_coordinates c2 
    ON c1.station_id = c2.station_id 
    AND c1.observation_date < c2.observation_date
JOIN stations s ON c1.station_id = s.station_id
WHERE 
    ABS(c1.latitude_decimal - c2.latitude_decimal) > 0.0001 OR
    ABS(c1.longitude_decimal - c2.longitude_decimal) > 0.0001 OR
    ABS(c1.orthometric_height - c2.orthometric_height) > 0.1;

-- 4. Check for duplicate stations with different cities
CREATE OR REPLACE VIEW vw_duplicate_stations AS
SELECT 
    s.station_id,
    s.station_name,
    s.monument_status,
    s.establishment_date,
    ar.region_name,
    ar.valid_from,
    ar.valid_until
FROM stations s
JOIN (
    SELECT station_name, COUNT(DISTINCT psgc_code) as city_count
    FROM stations
    GROUP BY station_name
    HAVING COUNT(DISTINCT psgc_code) > 1
) sd ON s.station_name = sd.station_name
JOIN administrative_regions ar ON s.psgc_code = ar.psgc_code
ORDER BY s.station_name, s.establishment_date;

-- 5. Check for stations with multiple observations
CREATE OR REPLACE VIEW vw_station_observations_summary AS
SELECT 
    station_id,
    COUNT(*) as observation_count,
    MIN(observation_date) as first_observation,
    MAX(observation_date) as last_observation,
    DATEDIFF(MAX(observation_date), MIN(observation_date)) as date_span_days
FROM station_observations
GROUP BY station_id
HAVING COUNT(*) > 1
ORDER BY observation_count DESC;

-- 6. Validate monument status changes
CREATE OR REPLACE VIEW vw_invalid_status_changes AS
SELECT 
    h1.station_id,
    s.station_name,
    h1.description as old_status_description,
    h2.description as new_status_description,
    h1.event_date as old_date,
    h2.event_date as new_date
FROM station_history h1
JOIN station_history h2 
    ON h1.station_id = h2.station_id 
    AND h1.event_date < h2.event_date
    AND h1.event_type = 'STATUS_CHANGE'
    AND h2.event_type = 'STATUS_CHANGE'
JOIN stations s ON h1.station_id = s.station_id
WHERE h1.description LIKE '%DESTROYED%' 
AND h2.description NOT LIKE '%DESTROYED%';

-- 7. Check for stations with missing observations
CREATE OR REPLACE VIEW vw_stations_without_observations AS
SELECT 
    s.station_id,
    s.station_name,
    s.establishment_date,
    s.monument_status,
    s.updated_at as last_update
FROM stations s
LEFT JOIN station_observations so ON s.station_id = so.station_id
WHERE so.station_id IS NULL
ORDER BY s.establishment_date DESC; 