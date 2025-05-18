-- Requests Management Database Schema

-- Create tables in the existing database
USE webgnis_users;

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS request_items;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS request_statuses;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;

-- Table for storing carts
CREATE TABLE carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Can be null for non-logged in users
    session_id VARCHAR(64) NOT NULL, -- For tracking non-logged in users
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (session_id)
);

-- Table for storing cart items
CREATE TABLE cart_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    station_id VARCHAR(50) NOT NULL,
    station_name VARCHAR(100) NULL, -- Store station name for display
    station_type ENUM('horizontal', 'vertical', 'gravity') NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE,
    INDEX (cart_id)
);

-- Table for payment methods
CREATE TABLE payment_methods (
    payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT NOT NULL DEFAULT 0
);

-- Table for request status options
CREATE TABLE request_statuses (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    color_code VARCHAR(10) NOT NULL DEFAULT '#777777'
);

-- Table for requests
CREATE TABLE requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    remarks TEXT NULL,
    exp_date TIMESTAMP NULL, -- For tracking when a request expires (15 days after creation)
    transaction_code VARCHAR(50) NULL, -- For tracking the transaction code
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES request_statuses(status_id),
    INDEX (user_id),
    INDEX (status_id),
    INDEX (request_date),
    INDEX (exp_date)
);

-- Table for request items (stations requested)
CREATE TABLE request_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    station_id VARCHAR(50) NOT NULL,
    station_name VARCHAR(100) NULL, -- Store station name for display
    station_type ENUM('horizontal', 'vertical', 'gravity') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    INDEX (request_id)
);

-- Table for payment transactions
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) NOT NULL UNIQUE, -- Format: CSUMGB-YYYYMMDD-<userid>-001
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    status_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    payment_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) NOT NULL,
    payment_reference VARCHAR(100) NULL, -- For receipt numbers, transaction IDs, etc.
    payment_proof_file VARCHAR(255) NULL, -- Path to uploaded proof of payment
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date of payment or transaction date
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL, -- Admin user ID who verified the payment
    verified_date TIMESTAMP NULL,
    remarks TEXT NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES request_statuses(status_id),
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(payment_method_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    INDEX (request_id),
    INDEX (user_id),
    INDEX (status_id),
    INDEX (payment_date)
);

-- Insert initial payment methods
INSERT INTO payment_methods (method_name, display_order) VALUES
('Link Biz', 1),
('Bank Transfer', 2),
('Cash Deposit', 3);

-- Insert initial request statuses
INSERT INTO request_statuses (status_name, description, color_code) VALUES
('Not Paid', 'User has not yet paid the requests', '#dc3545'), -- red
('Paid', 'User has paid the requests', '#ffc107'), -- yellow
('Pending', 'User has paid, action is on NAMRIA', '#fd7e14'), -- orange
('Approved', 'NAMRIA has approved the request, ready for download', '#28a745'), -- green
('Not Approved', 'NAMRIA denied the request', '#6c757d'), -- gray
('Expired', 'Not paid requests and no payment received after 15 days', '#343a40'); -- dark gray 