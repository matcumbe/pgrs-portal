USE webgnis_db;

-- First, import station data into stations table
LOAD DATA LOCAL INFILE 'C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Sample Data/250418 Drei/VGCP_stand_data(NCR)_processed.csv'
INTO TABLE vgcp_stations
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES;

-- Then, insert the VGCP measurements
INSERT INTO vgcp_measurements (station_id, vgcp_value, standard_deviation, date_measured)
SELECT 
    station_id,
    @vgcp_value,
    @standard_deviation,
    date_measured
FROM stations
WHERE station_id IS NOT NULL; 