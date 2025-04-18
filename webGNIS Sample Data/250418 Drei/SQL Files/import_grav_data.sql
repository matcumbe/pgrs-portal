USE webgnis_db;

-- First, import station data into stations table
LOAD DATA LOCAL INFILE 'C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Sample Data/250418 Drei/GRAV_stand_data_(NCR).csv'
INTO TABLE grav_stations
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Then, insert the gravity measurements
INSERT INTO gravity_measurements (station_id, gravity_value, standard_deviation, date_measured)
SELECT 
    station_id,
    @gravity_value,
    @standard_deviation,
    date_measured
FROM stations
WHERE station_id IS NOT NULL; 