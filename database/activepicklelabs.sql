-- =========================================================
-- Active Picklelabs Database
-- Import this file in phpMyAdmin (XAMPP) or via:
--   mysql -u root -p < activepicklelabs.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS activepicklelabs CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE activepicklelabs;

-- ---------------------------------------------------------
-- Users (clients + admins)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client','admin') NOT NULL DEFAULT 'client',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Courts
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    court_name VARCHAR(60) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
    status ENUM('available','maintenance') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Services (shown on the public "Our Services" section)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(40) DEFAULT 'sparkle',
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bookings (court rental / open play requests)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    court_id INT NOT NULL,
    booking_type ENUM('private','open_play') NOT NULL DEFAULT 'private',
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    players INT NOT NULL DEFAULT 2,
    notes VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Site settings (single row, edited from admin/settings.php)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY DEFAULT 1,
    site_name VARCHAR(100) NOT NULL DEFAULT 'Active Picklelabs',
    tagline VARCHAR(255) DEFAULT 'The Picklelab That Gives You Ultimate Experience',
    email VARCHAR(150) DEFAULT 'info@activepicklelabs.com',
    phone VARCHAR(30) DEFAULT '+0936-345-5567',
    instagram VARCHAR(100) DEFAULT 'ActivePicklelabs',
    address VARCHAR(255) DEFAULT 'Downtown Sports Complex'
) ENGINE=InnoDB;

INSERT INTO settings (id, site_name, tagline, email, phone, instagram, address)
VALUES (1, 'Active Picklelabs', 'The Picklelab That Gives You Ultimate Experience',
        'info@activepicklelabs.com', '+0936-345-5567', 'ActivePicklelabs', 'Downtown Sports Complex')
ON DUPLICATE KEY UPDATE site_name = VALUES(site_name);

-- ---------------------------------------------------------
-- Default admin account is NOT seeded here, because a bcrypt hash
-- typed into an SQL file can't be guaranteed to match a real password.
-- After importing this file, open database/create_admin.php ONCE in your
-- browser to create the first admin account with a properly hashed
-- password, then delete that file for security.
-- ---------------------------------------------------------

-- ---------------------------------------------------------
-- Seed courts
-- ---------------------------------------------------------
INSERT INTO courts (court_name, description, hourly_rate, status) VALUES
('Court 1', 'Outdoor court, lights available', 300.00, 'available'),
('Court 2', 'Outdoor court, lights available', 300.00, 'available'),
('Court 3', 'Indoor court, air-conditioned', 400.00, 'available'),
('Court 4', 'Indoor court, air-conditioned', 400.00, 'available');

-- ---------------------------------------------------------
-- Seed services (matches the "Our Services" mockup section)
-- ---------------------------------------------------------
INSERT INTO services (title, description, icon, display_order) VALUES
('Premium Court Rental', 'Book a private court by the hour for you and your group.', 'court', 1),
('Private Coaching Session', 'One-on-one coaching with a top rated pickleball player.', 'coach', 2),
('Group Training Class', 'Structured group classes for players of every level.', 'group', 3),
('Tournament Hosting Service', 'Full-service tournament setup, brackets, and officiating.', 'trophy', 4),
('Pickle Gear Rental', 'Paddles, balls, and gear rental at the front desk.', 'gear', 5),
('Flexible Court Scheduling', 'Open play sessions and flexible booking windows.', 'calendar', 6);
