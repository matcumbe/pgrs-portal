-- New schema for Horizontal, Vertical, and Gravity control point stations.
-- Designed based on the provided CSV files and API logic.

-- Drop existing tables if they exist to start fresh.
DROP TABLE IF EXISTS `hgcp_stations_new`;
DROP TABLE IF EXISTS `vgcp_stations_new`;
DROP TABLE IF EXISTS `grav_stations_new`;

-- --------------------------------------------------------

--
-- Table structure for table `hgcp_stations_new`
-- Based on GCP_250625_dms.csv
--
CREATE TABLE `hgcp_stations_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `station_name` VARCHAR(255),
  `island_group` VARCHAR(255),
  `region` VARCHAR(255),
  `province` VARCHAR(255),
  `city_or_municipality` VARCHAR(255),
  `barangay` VARCHAR(255),
  `year_established` INT,
  `latitude` DECIMAL(10, 8),
  `latitude_dms` VARCHAR(255),
  `longitude` DECIMAL(11, 8),
  `longitude_dms` VARCHAR(255),
  `description` TEXT,
  `year_surveyed` INT,
  `project` VARCHAR(255),
  `epoch` DECIMAL(10, 2),
  `latitude_wgs84` DECIMAL(10, 8),
  `latitude_wgs84_dms` VARCHAR(255),
  `longitude_wgs84` DECIMAL(11, 8),
  `longitude_wgs84_dms` VARCHAR(255),
  `error_ellipse` DECIMAL(10, 4),
  `accuracy_class` VARCHAR(255),
  `ellipsoidal_ht_wgs84` DECIMAL(10, 4),
  `height_error` DECIMAL(10, 4),
  `order_of_accuracy_wgs84` VARCHAR(255),
  `egm2008_elevation` DECIMAL(10, 4),
  `utm_x_wgs84` DECIMAL(15, 5),
  `utm_y_wgs84` DECIMAL(15, 5),
  `utm_zone_wgs84` VARCHAR(10),
  `latitude_prs92` DECIMAL(10, 8),
  `latitude_prs92_dms` VARCHAR(255),
  `longitude_prs92` DECIMAL(11, 8),
  `longitude_prs92_dms` VARCHAR(255),
  `ellipsoidal_ht_prs92` DECIMAL(10, 4),
  `northing_ptm` DECIMAL(15, 5),
  `easting_ptm` DECIMAL(15, 5),
  `ptm_zone` VARCHAR(10),
  `utm_x_prs92` DECIMAL(15, 5),
  `utm_y_prs92` DECIMAL(15, 5),
  `utm_zone_prs92` VARCHAR(10),
  `latitude_itrf` DECIMAL(10, 8),
  `latitude_itrf_dms` VARCHAR(255),
  `longitude_itrf` DECIMAL(11, 8),
  `longitude_itrf_dms` VARCHAR(255),
  `error_ellipse_itrf` DECIMAL(10, 4),
  `ellipsoidal_ht_itrf` DECIMAL(10, 4),
  `height_error_itrf` DECIMAL(10, 4),
  `year_computed` DATE,
  `encoder` VARCHAR(255),
  `date_updated` DATETIME,
  `reference_file` VARCHAR(255),
  INDEX `idx_station_name` (`station_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vgcp_stations_new`
-- Based on BM_250625_dms.csv
--
CREATE TABLE `vgcp_stations_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `station_name` VARCHAR(255),
  `elevation_m` DECIMAL(10, 6),
  `datum` VARCHAR(255),
  `reference_tide_station` VARCHAR(255),
  `tidal_series` VARCHAR(255),
  `std_dev` DECIMAL(10, 6),
  `accuracy_class` VARCHAR(255),
  `order_of_accuracy` VARCHAR(255),
  `year_surveyed` INT,
  `fixing_method` VARCHAR(255),
  `year_computed_x` INT,
  `encoder` VARCHAR(255),
  `date_updated` DATETIME,
  `reference_file` VARCHAR(255),
  `island_group` VARCHAR(255),
  `region` VARCHAR(255),
  `province` VARCHAR(255),
  `city_or_municipality` VARCHAR(255),
  `barangay` VARCHAR(255),
  `year_established` INT,
  `latitude` DECIMAL(10, 8),
  `latitude_dms` VARCHAR(255),
  `longitude` DECIMAL(11, 8),
  `longitude_dms` VARCHAR(255),
  `description` TEXT,
  `year_computed_y` INT,
  INDEX `idx_station_name` (`station_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `grav_stations_new`
-- Based on API logic and common station fields
--
CREATE TABLE `grav_stations_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `station_name` VARCHAR(255),
  `latitude` DECIMAL(10, 8),
  `longitude` DECIMAL(11, 8),
  `region` VARCHAR(255),
  `province` VARCHAR(255),
  `city` VARCHAR(255),
  `barangay` VARCHAR(255),
  `description` TEXT,
  `gravity_value` DECIMAL(15, 5),
  `g_order` VARCHAR(255),
  INDEX `idx_station_name` (`station_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Note: The APIs at 'api.php' and 'stations-api.php' will need to be updated
-- to use these new table names (e.g., 'hgcp_stations_new', 'vgcp_stations_new', 'grav_stations_new'). 