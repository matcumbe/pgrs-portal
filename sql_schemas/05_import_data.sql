-- This script provides the SQL commands to import your CSV data into the new tables.
--
-- IMPORTANT NOTES BEFORE RUNNING:
--
-- 1. FILE PATHS: You MUST replace the placeholder paths below (e.g., '/path/to/your/PGRS Portal/...')
--    with the full, absolute path to the CSV files on the computer where the MySQL server is running.
--
-- 2. PERMISSIONS: The MySQL user executing this command needs the `FILE` privilege.
--    You can grant it with: GRANT FILE ON *.* TO 'your_user'@'localhost';
--
-- 3. `secure_file_priv`: This is a MySQL security variable that restricts file operations.
--    You can check its value by running this SQL query:
--    SHOW VARIABLES LIKE 'secure_file_priv';
--
--    - If the value is a directory path (e.g., '/var/lib/mysql-files/'), you MUST move your CSV files
--      into that specific directory and use that path in the commands below.
--    - If the value is EMPTY, you can load files from any path.
--    - If the value is NULL, `LOAD DATA INFILE` is disabled. You may need to edit your MySQL
--      configuration file (`my.ini` on Windows or `my.cnf` on Linux) to set `secure_file_priv = ""`
--      (or a specific directory) and restart the MySQL server.

-- --------------------------------------------------------
-- Import Horizontal Control Point (GCP) data
-- --------------------------------------------------------
LOAD DATA LOCAL INFILE 'C:\\Users\\cumbe\\OneDrive\\Desktop\\PGRS Portal\\webGNIS Sample Data\\250418 Drei\\WebGNIS Files\\assets\\data\\GCP_250625_dms.csv'

INTO TABLE hgcp_stations_new
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\r\n' -- Use '\r\n' for Windows files, '\n' for Linux/macOS
IGNORE 1 ROWS
(
    station_name, island_group, region, province, city_or_municipality, barangay, @year_established, @latitude, latitude_dms,
    @longitude, longitude_dms, description, @year_surveyed, project, @epoch, @latitude_wgs84, latitude_wgs84_dms,
    @longitude_wgs84, longitude_wgs84_dms, @error_ellipse, accuracy_class, @ellipsoidal_ht_wgs84, @height_error,
    order_of_accuracy_wgs84, @egm2008_elevation, @utm_x_wgs84, @utm_y_wgs84, utm_zone_wgs84, @latitude_prs92,
    latitude_prs92_dms, @longitude_prs92, longitude_prs92_dms, @ellipsoidal_ht_prs92, @northing_ptm, @easting_ptm,
    ptm_zone, @utmx_prs92, @utmy_prs92, utm_zone, @latitude_itrf, latitude_itrf_dms, @longitude_itrf, longitude_itrf_dms,
    @error_ellipse_itrf, @ellipsoidal_ht_itrf, @height_error_itrf, @year_computed, encoder, @date_updated, reference_file
)
SET
    year_established = NULLIF(TRIM(@year_established), ''),
    latitude = NULLIF(TRIM(@latitude), ''),
    longitude = NULLIF(TRIM(@longitude), ''),
    year_surveyed = NULLIF(TRIM(@year_surveyed), ''),
    epoch = NULLIF(TRIM(@epoch), ''),
    latitude_wgs84 = NULLIF(TRIM(@latitude_wgs84), ''),
    longitude_wgs84 = NULLIF(TRIM(@longitude_wgs84), ''),
    error_ellipse = NULLIF(TRIM(@error_ellipse), ''),
    ellipsoidal_ht_wgs84 = NULLIF(TRIM(@ellipsoidal_ht_wgs84), ''),
    height_error = NULLIF(TRIM(@height_error), ''),
    egm2008_elevation = NULLIF(TRIM(@egm2008_elevation), ''),
    utm_x_wgs84 = NULLIF(TRIM(@utm_x_wgs84), ''),
    utm_y_wgs84 = NULLIF(TRIM(@utm_y_wgs84), ''),
    latitude_prs92 = NULLIF(TRIM(@latitude_prs92), ''),
    longitude_prs92 = NULLIF(TRIM(@longitude_prs92), ''),
    ellipsoidal_ht_prs92 = NULLIF(TRIM(@ellipsoidal_ht_prs92), ''),
    northing_ptm = NULLIF(TRIM(@northing_ptm), ''),
    easting_ptm = NULLIF(TRIM(@easting_ptm), ''),
    utmx_prs92 = NULLIF(TRIM(@utmx_prs92), ''),
    utmy_prs92 = NULLIF(TRIM(@utmy_prs92), ''),
    latitude_itrf = NULLIF(TRIM(@latitude_itrf), ''),
    longitude_itrf = NULLIF(TRIM(@longitude_itrf), ''),
    error_ellipse_itrf = NULLIF(TRIM(@error_ellipse_itrf), ''),
    ellipsoidal_ht_itrf = NULLIF(TRIM(@ellipsoidal_ht_itrf), ''),
    height_error_itrf = NULLIF(TRIM(@height_error_itrf), ''),
    year_computed = STR_TO_DATE(NULLIF(TRIM(@year_computed), ''), '%Y-%m-%d'),
    date_updated = STR_TO_DATE(NULLIF(TRIM(@date_updated), ''), '%Y-%m-%d');

-- --------------------------------------------------------
-- Import Vertical Control Point (BM) data
-- --------------------------------------------------------
LOAD DATA INFILE 'C:\\Users\\cumbe\\OneDrive\\Desktop\\PGRS Portal\\webGNIS Sample Data\\250418 Drei\\WebGNIS Files\\assets\\data\\BM_250625_dms.csv'
INTO TABLE vgcp_stations_new
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\r\n' -- Use '\r\n' for Windows files, '\n' for Linux/macOS
IGNORE 1 ROWS
(
    station_name, @elevation_m, datum, reference_tide_station, tidal_series, @std_dev, accuracy_class, order_of_accuracy,
    @year_surveyed, fixing_method, @year_computed_x, encoder, @date_updated, reference_file, island_group, region, province,
    city_or_municipality, barangay, @year_established, @latitude, latitude_dms, @longitude, longitude_dms, description, @year_computed_y
)
SET
    elevation_m = NULLIF(TRIM(@elevation_m), ''),
    std_dev = NULLIF(TRIM(@std_dev), ''),
    year_surveyed = NULLIF(TRIM(@year_surveyed), ''),
    year_computed_x = NULLIF(TRIM(@year_computed_x), ''),
    date_updated = STR_TO_DATE(NULLIF(TRIM(@date_updated), ''), '%m/%d/%Y %h:%i:%s %p'),
    year_established = NULLIF(TRIM(@year_established), ''),
    latitude = NULLIF(TRIM(@latitude), ''),
    longitude = NULLIF(TRIM(@longitude), ''),
    year_computed_y = NULLIF(TRIM(@year_computed_y), '');

-- Note: No import script for 'grav_stations_new' is provided as no corresponding CSV data file was available. 