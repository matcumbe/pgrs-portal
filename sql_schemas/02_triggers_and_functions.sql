-- Use the database
USE webgnis_db;

-- Create function to update timestamps
DELIMITER //

CREATE TRIGGER before_station_update
BEFORE UPDATE ON stations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER before_station_observation_update
BEFORE UPDATE ON station_observations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

-- Create trigger to log station history
CREATE TRIGGER after_station_update
AFTER UPDATE ON stations
FOR EACH ROW
BEGIN
    IF NEW.station_code != OLD.station_code THEN
        INSERT INTO station_history (station_id, field_name, old_value, new_value, changed_by)
        VALUES (NEW.id, 'station_code', OLD.station_code, NEW.station_code, CURRENT_USER());
    END IF;
    
    IF NEW.station_name != OLD.station_name THEN
        INSERT INTO station_history (station_id, field_name, old_value, new_value, changed_by)
        VALUES (NEW.id, 'station_name', OLD.station_name, NEW.station_name, CURRENT_USER());
    END IF;
    
    IF NEW.status != OLD.status THEN
        INSERT INTO station_history (station_id, field_name, old_value, new_value, changed_by)
        VALUES (NEW.id, 'status', OLD.status, NEW.status, CURRENT_USER());
    END IF;
END//

-- Create function to validate observation dates
CREATE TRIGGER validate_observation_date
BEFORE INSERT ON station_observations
FOR EACH ROW
BEGIN
    IF NEW.observation_date > CURRENT_DATE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Observation date cannot be in the future';
    END IF;
END//

-- Create function to validate status transitions
CREATE TRIGGER validate_status_transition
BEFORE UPDATE ON stations
FOR EACH ROW
BEGIN
    IF OLD.status = 'destroyed' AND NEW.status != 'destroyed' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot change status of a destroyed station';
    END IF;
END//

DELIMITER ; 