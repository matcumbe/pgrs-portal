-- Validation Queries
-- Version 1.0
-- Created: 2024-04-12

-- View for stations with missing coordinates
CREATE VIEW vw_missing_coordinates AS
SELECT s.station_id, s.station_name, sl.region, sl.province, sl.municipality
FROM stations s
LEFT JOIN station_locations sl ON s.station_id = sl.station_id
LEFT JOIN station_coordinates sc ON s.station_id = sc.station_id
WHERE sc.station_id IS NULL;

-- View for stations with inconsistent status
CREATE VIEW vw_inconsistent_status AS
SELECT s.station_id, s.station_name, s.monument_status, 
       MAX(sh.event_date) as last_status_change,
       COUNT(sh.history_id) as status_changes
FROM stations s
LEFT JOIN station_history sh ON s.station_id = sh.station_id 
    AND sh.event_type = 'STATUS_CHANGE'
GROUP BY s.station_id, s.station_name, s.monument_status
HAVING (s.monument_status = 'ACTIVE' AND COUNT(sh.history_id) > 0)
    OR (s.monument_status = 'LOST' AND COUNT(sh.history_id) = 0);

-- View for duplicate station names
CREATE VIEW vw_duplicate_stations AS
SELECT station_name, COUNT(*) as count
FROM stations
GROUP BY station_name
HAVING COUNT(*) > 1;

-- View for stations with multiple observations
CREATE VIEW vw_multiple_observations AS
SELECT s.station_id, s.station_name, 
       COUNT(so.observation_id) as observation_count,
       MIN(so.observation_date) as first_observation,
       MAX(so.observation_date) as last_observation
FROM stations s
JOIN station_observations so ON s.station_id = so.station_id
GROUP BY s.station_id, s.station_name
HAVING COUNT(so.observation_id) > 1;

-- View for stations with coordinate discrepancies
CREATE VIEW vw_coordinate_discrepancies AS
SELECT s.station_id, s.station_name,
       sc1.latitude_dms as lat1, sc2.latitude_dms as lat2,
       sc1.longitude_dms as lon1, sc2.longitude_dms as lon2,
       ABS(sc1.ellipsoidal_height - sc2.ellipsoidal_height) as height_diff
FROM stations s
JOIN station_coordinates sc1 ON s.station_id = sc1.station_id
JOIN station_coordinates sc2 ON s.station_id = sc2.station_id
WHERE sc1.coordinate_id < sc2.coordinate_id
AND (
    sc1.latitude_dms != sc2.latitude_dms
    OR sc1.longitude_dms != sc2.longitude_dms
    OR ABS(sc1.ellipsoidal_height - sc2.ellipsoidal_height) > 0.1
);

-- View for stations with missing administrative data
CREATE VIEW vw_missing_admin_data AS
SELECT s.station_id, s.station_name,
       CASE WHEN sl.region IS NULL THEN 'Missing Region' ELSE '' END as region_status,
       CASE WHEN sl.province IS NULL THEN 'Missing Province' ELSE '' END as province_status,
       CASE WHEN sl.municipality IS NULL THEN 'Missing Municipality' ELSE '' END as municipality_status
FROM stations s
LEFT JOIN station_locations sl ON s.station_id = sl.station_id
WHERE sl.region IS NULL 
   OR sl.province IS NULL 
   OR sl.municipality IS NULL;

-- View for stations with temporal inconsistencies
CREATE VIEW vw_temporal_inconsistencies AS
SELECT s.station_id, s.station_name,
       s.establishment_date,
       MIN(so.observation_date) as first_observation,
       MAX(so.observation_date) as last_observation,
       CASE 
           WHEN MIN(so.observation_date) < s.establishment_date THEN 'Observation before establishment'
           WHEN MAX(so.observation_date) > CURRENT_DATE THEN 'Future observation'
           ELSE 'OK'
       END as temporal_status
FROM stations s
LEFT JOIN station_observations so ON s.station_id = so.station_id
GROUP BY s.station_id, s.station_name, s.establishment_date
HAVING MIN(so.observation_date) < s.establishment_date
    OR MAX(so.observation_date) > CURRENT_DATE;

-- View for stations with accuracy issues
CREATE VIEW vw_accuracy_issues AS
SELECT s.station_id, s.station_name,
       sc.accuracy_class,
       sc.horizontal_error,
       sc.vertical_error,
       CASE 
           WHEN sc.horizontal_error > 0.1 THEN 'High horizontal error'
           WHEN sc.vertical_error > 0.1 THEN 'High vertical error'
           WHEN sc.accuracy_class IS NULL THEN 'Missing accuracy class'
           ELSE 'OK'
       END as accuracy_status
FROM stations s
JOIN station_coordinates sc ON s.station_id = sc.station_id
WHERE sc.horizontal_error > 0.1
   OR sc.vertical_error > 0.1
   OR sc.accuracy_class IS NULL;

-- View for stations with description issues
CREATE VIEW vw_description_issues AS
SELECT s.station_id, s.station_name,
       CASE 
           WHEN s.remarks IS NULL THEN 'Missing description'
           WHEN LENGTH(s.remarks) < 50 THEN 'Short description'
           ELSE 'OK'
       END as description_status
FROM stations s
WHERE s.remarks IS NULL OR LENGTH(s.remarks) < 50; 