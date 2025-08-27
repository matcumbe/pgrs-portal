-- File: certificates_schema.sql
-- Purpose: Defines the schema for the certificates table.

USE webgnis_users; -- Use the existing database

DROP TABLE IF EXISTS certificates;

CREATE TABLE certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) NOT NULL UNIQUE, -- The unique transaction code
    request_id INT NOT NULL,                      -- Foreign key to the requests table
    preprocessed_filename VARCHAR(255) NULL,      -- Filename of the admin-generated certificate, e.g., "CSUMGB-YYYYMMDD-USERID-001.pdf"
    processed_filename VARCHAR(255) NULL,        -- Filename of the user-downloadable certificate
    status ENUM('pending_generation', 'preprocessed', 'processed') DEFAULT 'pending_generation', -- Status of the certificate
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_code) REFERENCES transactions(transaction_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
);

-- Optional indexes:
-- CREATE INDEX idx_certificates_request_id ON certificates(request_id);
-- CREATE INDEX idx_certificates_status ON certificates(status); 