-- Database Schema for SpeedyRental

CREATE DATABASE IF NOT EXISTS speedy_rental;
USE speedy_rental;

-- Users Table (Admins/Managers)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Manager') DEFAULT 'Manager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clients Table
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    category ENUM('Sedan', 'SUV', 'Luxury', 'Compact', 'Van') NOT NULL,
    daily_rate DECIMAL(10, 2) NOT NULL,
    status ENUM('Available', 'Rented', 'Maintenance') DEFAULT 'Available',
    mileage INT DEFAULT 0,
    last_maintenance_date DATE,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reservations Table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    damage_log TEXT, -- Digital Damage Log
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Seed Data (for testing)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'); -- password: password

INSERT INTO clients (full_name, phone, license_number, address) VALUES 
('Ahmed Benali', '+212600112233', 'A123456', 'Casablanca, Morocco'),
('Sarah Johnson', '+15550199', 'US-TX-98765', 'Houston, TX');

INSERT INTO vehicles (registration_number, brand, model, category, daily_rate, status, mileage, image_url) VALUES 
('12345-A-1', 'Dacia', 'Logan', 'Sedan', 250.00, 'Available', 15000, 'https://placehold.co/600x400/png?text=Dacia+Logan'),
('67890-B-6', 'Range Rover', 'Evoque', 'SUV', 1200.00, 'Rented', 45000, 'https://placehold.co/600x400/png?text=Range+Rover'),
('11223-D-26', 'Clio', '5', 'Compact', 300.00, 'Maintenance', 82000, 'https://placehold.co/600x400/png?text=Clio+5');
