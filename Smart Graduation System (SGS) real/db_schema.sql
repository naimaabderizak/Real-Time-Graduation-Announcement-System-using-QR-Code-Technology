-- Database for Smart Graduation System
CREATE DATABASE IF NOT EXISTS sgs_db;
USE sgs_db;

-- Table to store student information
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    faculty VARCHAR(255) NOT NULL,
    qr_path VARCHAR(255) DEFAULT NULL,
    is_scanned TINYINT(1) DEFAULT 0,
    scanned_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user (password is 'admin123' - should be changed later)
-- Note: In a real app, passwords should be hashed using PHP password_hash()
INSERT IGNORE INTO admin_users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
