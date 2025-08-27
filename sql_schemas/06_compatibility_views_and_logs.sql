-- Compatibility setup for WebGNIS (XAMPP/MySQL)
-- - Creates activity log table in webgnis_users
-- - Creates views mapping legacy table names to new *_stations_new tables
--
-- How to use:
-- 1) Open phpMyAdmin (or mysql CLI) connected to your local MySQL.
-- 2) Run this whole script once. It is idempotent-safe for repeat runs.

-- =============================
-- Activity log table (webgnis_users)
-- =============================
CREATE DATABASE IF NOT EXISTS `webgnis_users` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `webgnis_users`;

-- Note: Using LONGTEXT for details for broad compatibility; JSON can be used on MySQL 5.7+
CREATE TABLE IF NOT EXISTS `station_activity_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `station_id` VARCHAR(100) NULL,
  `admin_user` VARCHAR(100) NULL,
  `action` ENUM('add','update','delete') NOT NULL,
  `details` LONGTEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_station_activity_log_timestamp` (`timestamp`),
  KEY `idx_station_activity_log_station_id` (`station_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================
-- Back-compat views (webgnis_db)
-- =============================
-- These views expose legacy table names expected by some scripts
-- and point them to the newer *_stations_new tables used by the main branch.

CREATE DATABASE IF NOT EXISTS `webgnis_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `webgnis_db`;

-- Horizontal GCPs
DROP VIEW IF EXISTS `hgcp_stations`;
CREATE VIEW `webgnis_db`.`hgcp_stations` AS
SELECT * FROM `webgnis_db`.`hgcp_stations_new`;

-- Vertical GCPs
DROP VIEW IF EXISTS `vgcp_stations`;
CREATE VIEW `webgnis_db`.`vgcp_stations` AS
SELECT * FROM `webgnis_db`.`vgcp_stations_new`;

-- Gravity stations
DROP VIEW IF EXISTS `grav_stations`;
CREATE VIEW `webgnis_db`.`grav_stations` AS
SELECT * FROM `webgnis_db`.`grav_stations_new`;

-- Done.

