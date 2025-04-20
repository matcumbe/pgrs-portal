-- Users Database Schema

-- Drop database if exists to avoid conflicts
DROP DATABASE IF EXISTS webgnis_users;

-- Create database
CREATE DATABASE webgnis_users;
USE webgnis_users;

-- Table for storing sex options
CREATE TABLE sexes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sex_name VARCHAR(50) NOT NULL
);

-- Table for storing sector options
CREATE TABLE sectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(100) NOT NULL
);

-- Main users table for all user types
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Will store hashed passwords
    email VARCHAR(100) NOT NULL UNIQUE,
    contact_number VARCHAR(20),
    user_type ENUM('individual', 'company', 'admin') NOT NULL,
    sex_id INT,
    name_on_certificate VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (sex_id) REFERENCES sexes(id)
);

-- Table for company users details
CREATE TABLE company_details (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(100) NOT NULL,
    company_address TEXT NOT NULL,
    sector_id INT NOT NULL,
    authorized_representative VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES sectors(id)
);

-- Table for individual users details
CREATE TABLE individual_details (
    individual_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert initial values for sexes
INSERT INTO sexes (sex_name) VALUES
('Male'),
('Female');

-- Insert initial values for sectors
INSERT INTO sectors (sector_name) VALUES
('National Government (ENR)'),
('National Government (Others)'),
('Local Government'),
('Government Controlled Corp.'),
('Private (Company)'),
('Private (Individual)'),
('Foreign'),
('N.G.O.'),
('Academia'),
('Legislative'),
('Judiciary');

-- Create admin user
INSERT INTO users (username, password, email, contact_number, user_type, is_active)
VALUES ('admin', '$2y$10$hnGYVtK1mzGMKyhVsFLZbeAXbLiGCnbnuzuV6Dj7uSPa6oHwdcE7C', 'admin@webgnis.gov.ph', '09123456789', 'admin', TRUE);
-- Password is 'admin123' hashed with bcrypt

-- Create indexes for performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_company_details_user_id ON company_details(user_id);
CREATE INDEX idx_company_details_sector_id ON company_details(sector_id);
CREATE INDEX idx_individual_details_user_id ON individual_details(user_id); 