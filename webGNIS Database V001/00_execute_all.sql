-- Execute all SQL files in sequence
-- Version 1.1
-- Created: 2024-04-12
-- Updated: 2024-04-12

-- Drop existing database if it exists
DROP DATABASE IF EXISTS webgnis_db;

-- Create and use the database
CREATE DATABASE webgnis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webgnis_db;

-- Execute schema creation
\. C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Database V001/01_schema.sql

-- Execute triggers and functions
\. C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Database V001/02_triggers_and_functions.sql

-- Execute data cleaning procedures
\. C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Database V001/04_data_cleaning.sql

-- Execute validation queries
\. C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Database V001/03_validation_queries.sql

-- Execute data import procedures
\. C:/Users/cumbe/OneDrive/Desktop/PGRS Portal/webGNIS Database V001/06_data_import.sql

-- Show success message
SELECT 'Database setup completed successfully!' as message; 