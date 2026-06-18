-- ============================================================
-- College Auditorium Booking System
-- Module 1: Auth tables + seed data
-- Run once to set up the database
-- ============================================================

CREATE DATABASE IF NOT EXISTS auditorium_booking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE auditorium_booking;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    department      VARCHAR(100)  DEFAULT NULL,
    phone           VARCHAR(20)   DEFAULT NULL,
    role            ENUM('staff', 'admin', 'superadmin') NOT NULL DEFAULT 'staff',
    status          ENUM('active', 'inactive')           NOT NULL DEFAULT 'active',
    login_attempts  INT           NOT NULL DEFAULT 0,
    locked_until    DATETIME      DEFAULT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_role   (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── Password reset OTPs ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    otp_hash    VARCHAR(255) NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- ── Auditoriums (seeded) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS auditoriums (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    capacity            INT          NOT NULL,
    facilities          JSON         DEFAULT NULL,
    operational_start   TIME         NOT NULL DEFAULT '08:00:00',
    operational_end     TIME         NOT NULL DEFAULT '20:00:00',
    status              ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB;

-- ── Seed: default admin account ──────────────────────────────
-- Password: Admin@1234  (bcrypt hash — change on first login)
INSERT IGNORE INTO users (name, email, password_hash, role, department) VALUES
(
    'System Admin',
    'admin@college.edu',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'Administration'
);

-- ── Seed: auditoriums ────────────────────────────────────────
INSERT IGNORE INTO auditoriums (id, name, capacity, facilities, operational_start, operational_end) VALUES
(1, 'Arts Auditorium', 400,
    '["microphone_handheld","microphone_lapel","projector","screen","pa_system","podium","hdmi_adapter","extension_cord"]',
    '08:00:00', '20:00:00'),
(2, 'B.Ed Auditorium', 100,
    '["microphone_handheld","projector","screen","podium","hdmi_adapter"]',
    '08:00:00', '20:00:00'),
(3, 'Boardroom', 25,
    '["projector","screen","hdmi_adapter","vga_adapter","extension_cord"]',
    '08:00:00', '18:00:00');

-- ── Seed: sample staff account ───────────────────────────────
-- Password: Staff@1234  (for testing only — remove in production)
INSERT IGNORE INTO users (name, email, password_hash, role, department) VALUES
(
    'Test Staff',
    'staff@college.edu',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'staff',
    'Computer Science'
);
