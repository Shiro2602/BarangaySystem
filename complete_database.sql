-- Create and use the database
CREATE DATABASE IF NOT EXISTS brgydb;
USE brgydb;

-- Residents Table
CREATE TABLE IF NOT EXISTS residents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    civil_status VARCHAR(20),
    address TEXT NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    occupation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Officials Table
CREATE TABLE IF NOT EXISTS officials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20),
    term_start DATE NOT NULL,
    term_end DATE NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- Clearance Table
CREATE TABLE IF NOT EXISTS clearances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT,
    purpose TEXT NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE,
    or_number VARCHAR(50),
    amount DECIMAL(10,2),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (resident_id) REFERENCES residents(id)
);

-- Indigency Table
CREATE TABLE IF NOT EXISTS indigency (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT,
    purpose TEXT NOT NULL,
    issue_date DATE NOT NULL,
    or_number VARCHAR(50),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (resident_id) REFERENCES residents(id)
);

-- Blotter Table
CREATE TABLE IF NOT EXISTS blotter (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complainant_id INT,
    respondent_id INT,
    incident_type VARCHAR(100) NOT NULL,
    incident_date DATE NOT NULL,
    incident_location TEXT NOT NULL,
    incident_details TEXT NOT NULL,
    status ENUM('Pending', 'Ongoing', 'Resolved', 'Dismissed') DEFAULT 'Pending',
    resolution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complainant_id) REFERENCES residents(id),
    FOREIGN KEY (respondent_id) REFERENCES residents(id)
);

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'secretary') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `email`, `created_at`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', CURRENT_TIMESTAMP);

-- Insert sample resident
INSERT INTO residents (first_name, middle_name, last_name, birthdate, gender, civil_status, address, contact_number, occupation)
VALUES ('Juan', 'Manuel', 'Dela Cruz', '1990-01-15', 'Male', 'Married', '123 Sample St., Barangay Sample', '09123456789', 'Employee');

-- Insert sample official
INSERT INTO officials (position, first_name, last_name, contact_number, term_start, term_end)
VALUES ('Barangay Chairman', 'Maria', 'Santos', '09987654321', '2023-01-01', '2025-12-31');
