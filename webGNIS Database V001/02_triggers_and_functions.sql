-- Triggers and Functions for webGNIS Database
-- Version 1.0
-- Created: 2024-04-11
-- MySQL Compatible Version

DELIMITER //

-- Function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Function to log station history
CREATE TRIGGER log_station_history
AFTER UPDATE ON stations
FOR EACH ROW
BEGIN
    INSERT INTO station_history (
        station_id,
        change_type,
        change_date,
        changed_by,
        old_value,
        new_value,
        remarks
    ) VALUES (
        NEW.station_id,
        'MAINTENANCE',
        CURRENT_TIMESTAMP,
        CURRENT_USER(),
        JSON_OBJECT(
            'station_id', OLD.station_id,
            'station_name', OLD.station_name,
            'station_type_id', OLD.station_type_id,
            'establishment_date', OLD.establishment_date,
            'psgc_code', OLD.psgc_code,
            'original_psgc_code', OLD.original_psgc_code,
            'monument_type', OLD.monument_type,
            'remarks', OLD.remarks
        ),
        JSON_OBJECT(
            'station_id', NEW.station_id,
            'station_name', NEW.station_name,
            'station_type_id', NEW.station_type_id,
            'establishment_date', NEW.establishment_date,
            'psgc_code', NEW.psgc_code,
            'original_psgc_code', NEW.original_psgc_code,
            'monument_type', NEW.monument_type,
            'remarks', NEW.remarks
        ),
        'Station information updated'
    );
END//

-- Trigger to validate observation dates
CREATE TRIGGER validate_observation_date
BEFORE INSERT ON observations
FOR EACH ROW
BEGIN
    DECLARE establishment_date DATE;
    
    -- Get establishment date
    SELECT s.establishment_date INTO establishment_date
    FROM stations s
    WHERE s.station_id = NEW.station_id;
    
    -- Check if observation date is after establishment date
    IF establishment_date > NEW.observation_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Observation date cannot be before station establishment date';
    END IF;
    
    -- Check if observation date is in the future
    IF NEW.observation_date > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Observation date cannot be in the future';
    END IF;
END//

-- Trigger to validate station status transitions
CREATE TRIGGER validate_status_transition
BEFORE INSERT ON station_status
FOR EACH ROW
BEGIN
    DECLARE previous_status VARCHAR(50);
    
    -- Get previous status
    SELECT status INTO previous_status
    FROM station_status
    WHERE station_id = NEW.station_id
    AND effective_date < NEW.effective_date
    ORDER BY effective_date DESC
    LIMIT 1;
    
    -- Check if status transition is valid
    IF previous_status = 'DESTROYED' AND NEW.status != 'DESTROYED' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot change status of a destroyed station';
    END IF;
END//

DELIMITER ;

-- Create triggers for updated_at timestamp
CREATE TRIGGER update_reference_frames_updated_at
    BEFORE UPDATE ON reference_frames
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_administrative_regions_updated_at
    BEFORE UPDATE ON administrative_regions
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_station_types_updated_at
    BEFORE UPDATE ON station_types
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_stations_updated_at
    BEFORE UPDATE ON stations
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_station_status_updated_at
    BEFORE UPDATE ON station_status
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_observations_updated_at
    BEFORE UPDATE ON observations
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_observation_quality_metrics_updated_at
    BEFORE UPDATE ON observation_quality_metrics
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column(); 