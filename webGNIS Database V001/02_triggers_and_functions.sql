-- Triggers and Functions for webGNIS Database
-- Version 1.1
-- Created: 2024-04-11
-- Updated: 2024-04-12
-- MySQL Compatible Version

DELIMITER //

-- Trigger to update timestamp on stations table
CREATE TRIGGER before_station_update
BEFORE UPDATE ON stations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

-- Trigger to update timestamp on station_observations table
CREATE TRIGGER before_station_observation_update
BEFORE UPDATE ON station_observations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

-- Trigger to log station history
CREATE TRIGGER after_station_update
AFTER UPDATE ON stations
FOR EACH ROW
BEGIN
    IF NEW.station_id != OLD.station_id THEN
        INSERT INTO station_history (station_id, event_type, event_date, description)
        VALUES (NEW.station_id, 'UPDATE', CURRENT_DATE, 
            CONCAT('Station ID changed from ', OLD.station_id, ' to ', NEW.station_id));
    END IF;
    
    IF NEW.station_name != OLD.station_name THEN
        INSERT INTO station_history (station_id, event_type, event_date, description)
        VALUES (NEW.station_id, 'UPDATE', CURRENT_DATE, 
            CONCAT('Station name changed from ', OLD.station_name, ' to ', NEW.station_name));
    END IF;
    
    IF NEW.monument_status != OLD.monument_status THEN
        INSERT INTO station_history (station_id, event_type, event_date, description)
        VALUES (NEW.station_id, 'STATUS_CHANGE', CURRENT_DATE, 
            CONCAT('Status changed from ', OLD.monument_status, ' to ', NEW.monument_status));
    END IF;
END//

-- Trigger to validate observation dates
CREATE TRIGGER validate_observation_date
BEFORE INSERT ON station_observations
FOR EACH ROW
BEGIN
    IF NEW.observation_date > CURRENT_DATE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Observation date cannot be in the future';
    END IF;
END//

-- Trigger to validate monument status transitions
CREATE TRIGGER validate_monument_status
BEFORE UPDATE ON stations
FOR EACH ROW
BEGIN
    IF OLD.monument_status = 'DESTROYED' AND NEW.monument_status != 'DESTROYED' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot change status of a destroyed monument';
    END IF;
END//

DELIMITER ; 