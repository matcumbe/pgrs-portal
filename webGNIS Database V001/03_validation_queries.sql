-- Validation Queries for webGNIS Database
-- Version 1.0
-- Created: 2024-04-11
-- MySQL Compatible Version

-- 1. Check for temporal consistency in observations
CREATE OR REPLACE VIEW vw_temporal_consistency AS
SELECT 
    s.station_id, 
    s.station_name,
    s.establishment_date,
    MIN(o.observation_date) as first_observation,
    MAX(o.observation_date) as last_observation,
    COUNT(DISTINCT o.epoch) as epoch_count
FROM stations s
JOIN observations o ON s.station_id = o.station_id
GROUP BY s.station_id, s.station_name, s.establishment_date
HAVING MIN(o.observation_date) < s.establishment_date;

-- 2. Identify potentially merged cities
CREATE OR REPLACE VIEW vw_city_changes AS
SELECT 
    s.station_id,
    s.station_name,
    ar1.region_name as original_city,
    ar2.region_name as current_city,
    s.establishment_date,
    o.observation_date,
    o.epoch
FROM stations s
JOIN administrative_regions ar1 ON s.original_psgc_code = ar1.psgc_code
JOIN administrative_regions ar2 ON s.psgc_code = ar2.psgc_code
JOIN observations o ON s.station_id = o.station_id
WHERE s.original_psgc_code != s.psgc_code
ORDER BY s.station_name, o.observation_date;

-- 3. Quality control checks for observations
CREATE OR REPLACE VIEW vw_observation_quality AS
SELECT 
    o1.station_id,
    s.station_name,
    o1.observation_date as date1,
    o2.observation_date as date2,
    ABS(o1.latitude - o2.latitude) as lat_diff,
    ABS(o1.longitude - o2.longitude) as lon_diff,
    ABS(o1.height_orthometric - o2.height_orthometric) as height_diff,
    CASE 
        WHEN ABS(o1.latitude - o2.latitude) > 0.0001 THEN 'LATITUDE_WARNING'
        WHEN ABS(o1.longitude - o2.longitude) > 0.0001 THEN 'LONGITUDE_WARNING'
        WHEN ABS(o1.height_orthometric - o2.height_orthometric) > 0.1 THEN 'HEIGHT_WARNING'
        ELSE 'OK'
    END as quality_status
FROM observations o1
JOIN observations o2 
    ON o1.station_id = o2.station_id 
    AND o1.observation_date < o2.observation_date
JOIN stations s ON o1.station_id = s.station_id
WHERE 
    ABS(o1.latitude - o2.latitude) > 0.0001 OR
    ABS(o1.longitude - o2.longitude) > 0.0001 OR
    ABS(o1.height_orthometric - o2.height_orthometric) > 0.1;

-- 4. Check for duplicate stations with different cities
CREATE OR REPLACE VIEW vw_duplicate_stations AS
WITH station_duplicates AS (
    SELECT station_name, COUNT(DISTINCT psgc_code) as city_count
    FROM stations
    GROUP BY station_name
    HAVING COUNT(DISTINCT psgc_code) > 1
)
SELECT 
    s.*, 
    ar.region_name,
    ar.valid_from,
    ar.valid_until
FROM stations s
JOIN station_duplicates sd ON s.station_name = sd.station_name
JOIN administrative_regions ar ON s.psgc_code = ar.psgc_code
ORDER BY s.station_name, s.establishment_date;

-- 5. Check for stations with multiple epochs
CREATE OR REPLACE VIEW vw_station_epochs AS
SELECT 
    station_id,
    COUNT(DISTINCT epoch) as epoch_count,
    MIN(observation_date) as first_observation,
    MAX(observation_date) as last_observation,
    DATEDIFF(MAX(observation_date), MIN(observation_date)) as date_span_days
FROM observations
GROUP BY station_id
HAVING COUNT(DISTINCT epoch) > 1
ORDER BY epoch_count DESC;

-- 6. Validate status transitions
CREATE OR REPLACE VIEW vw_invalid_status_transitions AS
SELECT 
    s1.station_id,
    s.station_name,
    s1.status as old_status,
    s2.status as new_status,
    s1.effective_date as old_date,
    s2.effective_date as new_date
FROM station_status s1
JOIN station_status s2 
    ON s1.station_id = s2.station_id 
    AND s1.effective_date < s2.effective_date
JOIN stations s ON s1.station_id = s.station_id
WHERE s1.status = 'DESTROYED' AND s2.status != 'DESTROYED';

-- 7. Check for stations with missing observations
CREATE OR REPLACE VIEW vw_stations_without_observations AS
SELECT 
    s.station_id,
    s.station_name,
    s.establishment_date,
    ss.status,
    ss.effective_date as last_status_date
FROM stations s
LEFT JOIN observations o ON s.station_id = o.station_id
LEFT JOIN (
    SELECT station_id, status, effective_date
    FROM station_status
    WHERE (station_id, effective_date) IN (
        SELECT station_id, MAX(effective_date)
        FROM station_status
        GROUP BY station_id
    )
) ss ON s.station_id = ss.station_id
WHERE o.station_id IS NULL
ORDER BY s.establishment_date DESC; 