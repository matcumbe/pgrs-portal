-- Use the database
USE webgnis_db;

-- Create view for temporal consistency check
CREATE VIEW vw_temporal_consistency AS
SELECT 
    s.station_code,
    s.station_name,
    so.observation_date,
    so.observation_value,
    so.observation_accuracy,
    so.reference_frame_id
FROM stations s
JOIN station_observations so ON s.id = so.station_id
WHERE so.observation_date > CURRENT_DATE;

-- Create view for quality control check
CREATE VIEW vw_quality_control AS
SELECT 
    s.station_code,
    s.station_name,
    s.status,
    COUNT(so.id) AS observation_count,
    MIN(so.observation_date) AS first_observation,
    MAX(so.observation_date) AS last_observation
FROM stations s
LEFT JOIN station_observations so ON s.id = so.station_id
GROUP BY s.id, s.station_code, s.station_name, s.status
HAVING observation_count = 0 OR first_observation > last_observation;

-- Create view for duplicate stations
CREATE VIEW vw_duplicate_stations AS
SELECT 
    s1.station_code,
    s1.station_name,
    s1.latitude,
    s1.longitude,
    s1.status,
    s2.station_code AS duplicate_code,
    s2.station_name AS duplicate_name
FROM stations s1
JOIN stations s2 ON 
    s1.id < s2.id AND
    s1.station_code = s2.station_code;

-- Create view for missing coordinates
CREATE VIEW vw_missing_coordinates AS
SELECT 
    station_code,
    station_name,
    status
FROM stations
WHERE latitude IS NULL OR longitude IS NULL;

-- Create view for stations with multiple reference frames
CREATE VIEW vw_multiple_reference_frames AS
SELECT 
    s.station_code,
    s.station_name,
    GROUP_CONCAT(DISTINCT rf.reference_frame_name) AS reference_frames
FROM stations s
JOIN station_observations so ON s.id = so.station_id
JOIN reference_frames rf ON so.reference_frame_id = rf.id
GROUP BY s.id, s.station_code, s.station_name
HAVING COUNT(DISTINCT rf.id) > 1; 