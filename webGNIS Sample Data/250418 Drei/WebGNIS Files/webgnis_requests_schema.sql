-- webgnis_requests_schema.sql
-- Schema for the WebGNIS Requests Management System

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS webgnis_requests;

-- Use the database
USE webgnis_requests;

-- Requests table
CREATE TABLE IF NOT EXISTS requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NULL,
    status ENUM('Not Paid', 'Paid', 'Pending', 'Expired', 'Approved', 'Not Approved') DEFAULT 'Not Paid',
    request_reference VARCHAR(50) UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    admin_notes TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES webgnis_users.users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Request Items table
CREATE TABLE IF NOT EXISTS request_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    station_id VARCHAR(50) NOT NULL,
    station_name VARCHAR(100) NOT NULL,
    station_type ENUM('horizontal', 'benchmark', 'gravity') NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    payment_method ENUM('Cash Deposit', 'Link Biz', 'Bank Transfer') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_reference VARCHAR(100),
    proof_document VARCHAR(255),
    status ENUM('Not Paid', 'Paid', 'Pending', 'Expired', 'Approved', 'Not Approved') DEFAULT 'Not Paid',
    verification_date TIMESTAMP NULL,
    verified_by INT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES webgnis_users.users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart Items table - For persistent storage of user cart items
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    station_id VARCHAR(50) NOT NULL,
    station_name VARCHAR(100) NOT NULL,
    station_type ENUM('horizontal', 'benchmark', 'gravity') NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(100) NULL, -- For non-logged in users
    UNIQUE KEY unique_user_station (user_id, station_id),
    FOREIGN KEY (user_id) REFERENCES webgnis_users.users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for faster lookups
CREATE INDEX idx_requests_user_id ON requests(user_id);
CREATE INDEX idx_request_items_request_id ON request_items(request_id);
CREATE INDEX idx_transactions_request_id ON transactions(request_id);
CREATE INDEX idx_cart_items_user_id ON cart_items(user_id);
CREATE INDEX idx_cart_items_session_id ON cart_items(session_id); 