-- UBBC Bestination 2026 - Database Schema
-- Run this file via phpMyAdmin or MySQL CLI before using the application.

CREATE DATABASE IF NOT EXISTS bestination2026
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bestination2026;

-- ─── Students ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name           VARCHAR(100) NOT NULL,
    first_name          VARCHAR(100) NOT NULL,
    middle_name         VARCHAR(100) DEFAULT NULL,
    email               VARCHAR(191) NOT NULL UNIQUE,
    mobile              VARCHAR(20)  NOT NULL UNIQUE,
    gender              ENUM('Male','Female') NOT NULL,
    school_name         VARCHAR(200) NOT NULL,
    grade_level         VARCHAR(50)  NOT NULL,
    interested_booth_id TINYINT UNSIGNED DEFAULT NULL,
    qr_token            CHAR(64)     NOT NULL UNIQUE,
    registered_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at        DATETIME     DEFAULT NULL,
    email_sent          TINYINT(1)   NOT NULL DEFAULT 0,
    complete_email_sent TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Booths ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS booths (
    id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(10)  NOT NULL UNIQUE,
    name       VARCHAR(150) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO booths (code, name, sort_order) VALUES
  ('COE',  'College of Engineering', 1),
  ('CAS',  'College of Arts and Sciences', 2),
  ('CBAHM', 'College of Business, Accountancy, and Hospitality Management', 3),
  ('CEDU', 'College of Education', 4),
  ('CON',  'College of Nursing', 5),
  ('CICT', 'College of Information and Communications Technology', 6),
  ('CCJE', 'College of Criminal Justice Education', 7),
  ('CAMS', 'College of Allied Medical Sciences', 8),
  ('CIT',  'College of Industrial Technology', 9);

-- ─── Scans ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scans (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED     NOT NULL,
    booth_id   TINYINT UNSIGNED NOT NULL,
    scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_booth (student_id, booth_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (booth_id)   REFERENCES booths(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Admin Sessions ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_sessions (
    session_token CHAR(64)  NOT NULL PRIMARY KEY,
    created_at    DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at    DATETIME  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Settings ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    key_name VARCHAR(100) NOT NULL PRIMARY KEY,
    value    TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (key_name, value) VALUES
  ('admin_password_hash', ''),
  ('event_name', 'UBBC Bestination 2026'),
  ('registration_open', '1');
