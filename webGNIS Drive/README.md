 # webGNIS Drive Documentation

This directory contains the core data files for the Geodetic Network Information System (GNIS). These files store information about geodetic control points, benchmarks, and gravity stations across the Philippines.

## Data Files

### 1. Benchmark Table (All).csv
Contains comprehensive information about benchmark stations across the Philippines.

#### Key Fields:
- `ID`: Unique identifier for the benchmark
- `stat_name`: Station name/code (e.g., "AL-3", "AL-4")
- `region`: Administrative region (e.g., "REGION V")
- `province`: Province name (e.g., "ALBAY")
- `municipal`: Municipality name
- `barangay`: Barangay name
- `date_est`: Date of establishment
- `date_las_r`: Date of last recovery
- `island`: Island location (e.g., "LUZON")
- `d_lat`, `m_lat`, `s_lat`: Latitude in degrees, minutes, seconds
- `d_long`, `m_long`, `s_long`: Longitude in degrees, minutes, seconds
- `elevation`: Elevation data
- `mark_stat`: Mark status
- `mark_type`: Type of mark
- `mark_const`: Construction details
- `authority`: Authority responsible (e.g., "NAMRIA")
- `description`: Detailed location description
- `encoder`: Person who encoded the data
- `dateUpdated`: Last update timestamp

### 2. GCP Table (All).csv
Contains information about Ground Control Points (GCPs) across the Philippines.

#### Key Fields:
- `stat_name`: Station name/code
- `region`: Administrative region
- `province`: Province name
- `municipal`: Municipality name
- `barangay`: Barangay name
- `date_las_r`: Date of last recovery
- `island`: Island location
- `d_lat`, `m_lat`, `s_lat`: Latitude in degrees, minutes, seconds
- `d_long`, `m_long`, `s_long`: Longitude in degrees, minutes, seconds
- `northing`, `easting`, `zone`: UTM coordinates
- `h_date_ety`: Horizontal date of entry
- `h_datum`: Horizontal datum
- `h_ref`: Horizontal reference
- `hor_authty`: Horizontal authority
- `h_order`: Horizontal order
- `ell_hgt`: Ellipsoidal height
- `mark_stat`: Mark status
- `mark_type`: Type of mark
- `mark_const`: Construction details
- `authority`: Authority responsible
- `latitude`, `longitude`: Decimal coordinates
- `description`: Detailed location description
- `encoder`: Person who encoded the data
- `status`: Current status
- `dateUpdated`: Last update timestamp
- `utmx`, `utmy`, `utmz`: UTM coordinates
- `accuracy_class`: Accuracy classification
- `error_ellipse`: Error ellipse data
- `height_error`: Height error measurement

### 3. Gravity.csv
Contains gravity station data and measurements.

#### Key Fields:
- `STATION_CODE`: Unique station code
- `STATION_NAME`: Station name
- `OBSERVED_VALUE`: Gravity measurement value
- `YEAR_MEASURED`: Year of measurement
- `ORDER`: Order of the station
- `ENCODER`: Person who encoded the data
- `DATE_LAST_UPDATED`: Last update timestamp

## Data Standards

### Coordinate Systems
- Geographic coordinates in degrees, minutes, seconds
- UTM coordinates with zone information
- Elevation data in meters

### Date Formats
- Establishment dates: MM/DD/YYYY
- Last recovery dates: MM/DD/YYYY
- Update timestamps: MM/DD/YYYY HH:MM:SS AM/PM

### Status Codes
- 0: Active
- 1: Lost
- 2: Destroyed
- 3: Temporary
- 4: Unknown

### Accuracy Classes
- 1: First Order
- 2: Second Order
- 3: Third Order
- 4: Fourth Order

## Data Maintenance

### Update Procedures
1. Verify data accuracy before updates
2. Record all changes in the `dateUpdated` field
3. Maintain consistent formatting across all fields
4. Document any status changes
5. Update coordinate information when new measurements are available

### Quality Control
- Regular validation of coordinate data
- Verification of administrative boundaries
- Cross-checking of elevation data
- Validation of monument status
- Review of descriptive information

## Usage Guidelines

### Data Access
- Access through authorized GNIS applications only
- Maintain data integrity during export/import
- Follow established update procedures
- Document all modifications

### Data Export
- Export in original CSV format
- Include all relevant fields
- Maintain data relationships
- Preserve coordinate systems

## Version Control

### File Naming Convention
- Include date in filename (e.g., "Benchmarks_Mar2025.csv")
- Use consistent naming across all files
- Include version information if applicable

### Backup Procedures
- Regular backups of all data files
- Version tracking of significant changes
- Archive of historical data

## Contact Information

For data-related inquiries or issues:
- NAMRIA Geodesy Division
- Technical Support: [Contact Information]
- Data Management: [Contact Information]

## Last Updated
April 2024