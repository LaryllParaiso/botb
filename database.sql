-- ============================================
-- Battle of the Bands Tabulator System
-- NEUST 2026 — Full Schema + Seed Data
-- ============================================

CREATE DATABASE IF NOT EXISTS botb_tabulator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE botb_tabulator;

-- ============================================
-- 1. Rounds lookup table
-- ============================================
CREATE TABLE rounds (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  ENUM('elimination', 'grand_finals') NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO rounds (name) VALUES ('elimination'), ('grand_finals');

-- ============================================
-- 2. Users (admin + judges)
-- ============================================
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin', 'judge') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed admin: email=admin@botb.com password=Admin@2026
-- Password hash generated via password_hash('Admin@2026', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password, role) VALUES
  ('System Admin', 'admin@botb.com', '$2y$12$0TnKH0tWk39JEOu.KDJJdODZgN3sr5v3J3E9UEI2xvNT2CdKwg1J2', 'admin');

-- ============================================
-- 3. Criteria (3NF: weights not repeated in scores)
-- ============================================
CREATE TABLE criteria (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  round_id INT NOT NULL,
  name     VARCHAR(100) NOT NULL,
  weight   DECIMAL(5,2) NOT NULL,
  FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Elimination criteria (3 criteria, total 100%)
INSERT INTO criteria (round_id, name, weight) VALUES
  (1, 'Musicality', 50.00),
  (1, 'Originality', 30.00),
  (1, 'Stage Presence', 20.00);

-- Seed Grand Finals criteria (5 criteria, total 100%)
INSERT INTO criteria (round_id, name, weight) VALUES
  (2, 'Musicality', 30.00),
  (2, 'Creativity & Originality', 25.00),
  (2, 'Stage Presence & Audience Engagement', 20.00),
  (2, 'Overall Impact', 10.00),
  (2, 'Original Composition', 15.00);

-- ============================================
-- 4. Bands
-- ============================================
CREATE TABLE bands (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(150) NOT NULL,
  round_id          INT NOT NULL,
  performance_order INT NOT NULL,
  is_active         TINYINT(1) DEFAULT 0,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 5. Scores
-- ============================================
CREATE TABLE scores (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  judge_id     INT NOT NULL,
  band_id      INT NOT NULL,
  criteria_id  INT NOT NULL,
  score        DECIMAL(5,2) NOT NULL CHECK (score BETWEEN 0 AND 100),
  is_finalized TINYINT(1) DEFAULT 0,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_score (judge_id, band_id, criteria_id),
  FOREIGN KEY (judge_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (band_id)     REFERENCES bands(id) ON DELETE CASCADE,
  FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;
