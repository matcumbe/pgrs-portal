# webGNIS Database Documentation
Version 1.2 - Updated: 2024-04-12

## Overview
This database is designed to store and manage geodetic control points (GCPs), benchmarks, and gravity stations for the Philippines. It provides a comprehensive solution for tracking station locations, coordinates, observations, and historical changes.

## Database Structure

### Core Tables
1. `reference_frames`: Stores coordinate reference systems and their epochs
2. `administrative_regions`: Manages PSGC codes and administrative boundaries
3. `station_types`: Defines different types of geodetic stations
4. `stations`: Core table for all geodetic stations
5. `station_locations`: Stores detailed location information
6. `station_coordinates`: Manages multiple coordinate systems and epochs
7. `station_observations`: Records all observations and measurements
8. `station_history`: Tracks changes and events for each station

### Key Features
- Support for multiple coordinate systems and epochs
- Comprehensive monument status tracking
- Detailed location information with PSGC codes
- Historical tracking of station changes
- Quality control and validation rules
- Data cleaning and standardization procedures

## Data Import Procedures

### Data Source Files
The database is designed to work with three main data sources:
1. GCP Data: `GCP Table (All).csv`
2. Benchmark Data: `Benchmark Table (All).csv`
3. Gravity Data: `Gravity.csv`

### Import Process
Due to security restrictions in MySQL/XAMPP, direct LOAD DATA INFILE commands are not available. Instead, use the following process:

1. Create temporary tables for each data type:
```sql
-- For GCP data
CREATE TEMPORARY TABLE temp_gcp (
    stat_name VARCHAR(50),
    region VARCHAR(100),
    province VARCHAR(100),
    municipal VARCHAR(100),
    barangay VARCHAR(100),
    -- ... (see 06_data_import.sql for full structure)
);

-- For Benchmark data
CREATE TEMPORARY TABLE temp_benchmark (
    ID INT,
    stat_name VARCHAR(50),
    -- ... (see 06_data_import.sql for full structure)
);

-- For Gravity data
CREATE TEMPORARY TABLE temp_gravity (
    STATION_CODE VARCHAR(50),
    STATION_NAME VARCHAR(100),
    -- ... (see 06_data_import.sql for full structure)
);
```

2. Import data using phpMyAdmin:
   - Open phpMyAdmin
   - Select the database
   - Click "Import"
   - Choose the CSV file
   - Set format to CSV
   - Configure import settings:
     - Columns separated with: ","
     - Columns enclosed with: '"'
     - Lines terminated with: "\n"
     - First line contains column names: Yes

3. Process the imported data using the provided SQL scripts in `06_data_import.sql`

### Known Issues
- MySQL/XAMPP security settings may prevent direct file loading
- Some CSV files may require preprocessing for proper import
- Date formats may need standardization
- Coordinate systems may need conversion

## Data Validation

### Quality Control Views
1. `vw_missing_coordinates`: Identifies stations without coordinate data
2. `vw_inconsistent_status`: Finds stations with conflicting status information
3. `vw_duplicate_stations`: Detects duplicate station names
4. `vw_multiple_observations`: Lists stations with multiple observations
5. `vw_coordinate_discrepancies`: Identifies coordinate inconsistencies
6. `vw_missing_admin_data`: Finds stations with incomplete location data
7. `vw_temporal_inconsistencies`: Checks for date-related issues
8. `vw_accuracy_issues`: Identifies stations with accuracy concerns
9. `vw_description_issues`: Finds stations with incomplete descriptions

### Data Cleaning Functions
1. `clean_station_name`: Standardizes station names
2. `validate_coordinates`: Ensures coordinate validity
3. `validate_date`: Standardizes date formats
4. `clean_and_insert_gcp`: Comprehensive GCP data cleaning

## Usage Guidelines

### Data Import
1. Prepare CSV files with required columns
2. Create temporary tables using provided SQL
3. Import data using phpMyAdmin
4. Process data using import procedures
5. Check validation views for issues
6. Review and correct any identified problems

### Data Maintenance
1. Regular validation checks using provided views
2. Update station status as needed
3. Record all changes in station history
4. Maintain coordinate reference system information

### Best Practices
1. Always use the cleaning functions for new data
2. Regularly check validation views
3. Maintain complete station descriptions
4. Record all status changes in history
5. Keep coordinate reference systems up to date

## File Structure
1. `01_schema.sql`: Database schema and table definitions
2. `02_triggers_and_functions.sql`: Data validation and cleaning functions
3. `03_validation_queries.sql`: Quality control views
4. `04_data_cleaning.sql`: Data cleaning procedures
5. `05_validation_queries.sql`: Additional validation queries
6. `06_data_import.sql`: Data import procedures

## Version History
- 1.0 (2024-04-11): Initial release
- 1.1 (2024-04-12): Added data import procedures
- 1.2 (2024-04-12): Updated import procedures and documentation for XAMPP compatibility 