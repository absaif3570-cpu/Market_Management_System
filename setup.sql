-- ============================================================
--  Market Management System — Database Setup
--  Run this in phpMyAdmin or MySQL CLI before first use
-- ============================================================

CREATE DATABASE IF NOT EXISTS market_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE market_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','vendor','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Shops
CREATE TABLE IF NOT EXISTS shops (
    shop_id    INT AUTO_INCREMENT PRIMARY KEY,
    shop_name  VARCHAR(100) NOT NULL UNIQUE,
    owner_name VARCHAR(100) NOT NULL,
    location   VARCHAR(150) NOT NULL,
    rent       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Vendors  (shop_id FK — no more string matching)
-- Vendors
CREATE TABLE IF NOT EXISTS vendors (
    vendor_id  INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    shop_id    INT NOT NULL,
    user_id    INT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(shop_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    payment_id     INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id      INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Bank Transfer','Mobile Banking') NOT NULL DEFAULT 'Cash',
    payment_date   DATE NOT NULL,
    note           VARCHAR(255) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Default admin account  (password: admin123)
INSERT IGNORE INTO users (username, email, password, role)
VALUES ('admin', 'admin@mms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
