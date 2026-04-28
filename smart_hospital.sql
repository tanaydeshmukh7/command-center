-- ============================================
-- Smart Hospital — Database Schema
-- Run this file to create all required tables
-- ============================================

CREATE DATABASE IF NOT EXISTS `smart_hospital`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smart_hospital`;

-- Drop existing tables to ensure clean schema update
DROP TABLE IF EXISTS `admin_sessions`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `bias_logs`;
DROP TABLE IF EXISTS `ai_analysis`;
DROP TABLE IF EXISTS `allocations`;
DROP TABLE IF EXISTS `resources`;
DROP TABLE IF EXISTS `patients`;

-- -----------------------------------------------
-- Admin users and access tokens
-- -----------------------------------------------
CREATE TABLE `admin_users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `display_name`  VARCHAR(120) NULL,
    `status`        ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `admin_sessions` (
    `id`            BIGINT AUTO_INCREMENT PRIMARY KEY,
    `admin_user_id` INT NOT NULL,
    `token_hash`    CHAR(64) NOT NULL UNIQUE,
    `expires_at`    DATETIME NOT NULL,
    `last_used_at`  DATETIME NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_sessions_user` (`admin_user_id`),
    INDEX `idx_admin_sessions_expires` (`expires_at`),
    FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Patients table
-- -----------------------------------------------
CREATE TABLE `patients` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `patient_code`  VARCHAR(20)  NOT NULL UNIQUE,
    `name`          VARCHAR(150) NOT NULL,
    `age`           INT          NOT NULL,
    `gender`        ENUM('M','F','O') NOT NULL DEFAULT 'O',
    `symptoms`      TEXT         NULL,
    `oxygen_level`  DECIMAL(5,2) NULL,
    `severity`      ENUM('critical','moderate','stable') NOT NULL DEFAULT 'stable',
    `allocation`    VARCHAR(50)  NULL,
    `assigned_resource` VARCHAR(50) NULL,
    `severity_score` DECIMAL(4,2) NULL DEFAULT 0.00,
    `ward`          VARCHAR(50)  NULL,
    `status`        ENUM('admitted','discharged','waiting') NOT NULL DEFAULT 'waiting',
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Hospital resources (beds, equipment, ORs)
-- -----------------------------------------------
CREATE TABLE `resources` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `resource_code` VARCHAR(20)  NOT NULL UNIQUE,
    `name`          VARCHAR(100) NOT NULL,
    `type`          ENUM('icu_bed','general_bed','or_room','ventilator','monitor','wheelchair','discharge_lounge') NOT NULL,
    `ward`          VARCHAR(50)  NOT NULL,
    `status`        ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available',
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Allocations (patient → resource mapping)
-- -----------------------------------------------
CREATE TABLE `allocations` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id`      INT NOT NULL,
    `resource_id`     INT NOT NULL,
    `ai_confidence`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `ai_explanation`  TEXT NULL,
    `ai_rationale`    JSON NULL,
    `status`          ENUM('active','released','pending') NOT NULL DEFAULT 'pending',
    `allocated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `released_at`     TIMESTAMP NULL,
    FOREIGN KEY (`patient_id`)  REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`resource_id`) REFERENCES `resources`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- AI Analysis logs
-- -----------------------------------------------
CREATE TABLE `ai_analysis` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id`      INT NOT NULL,
    `severity_score`  DECIMAL(4,2) NOT NULL,
    `recommended_resource` VARCHAR(100) NULL,
    `confidence`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `rationale`       JSON NULL,
    `status`          VARCHAR(20) DEFAULT 'completed',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Bias detection logs
-- -----------------------------------------------
CREATE TABLE `bias_logs` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `trigger_type`  VARCHAR(100) NOT NULL,
    `adjustment`    VARCHAR(255) NULL,
    `authority`     VARCHAR(100) NOT NULL DEFAULT 'System Auto',
    `status`        ENUM('applied','logged','pending') NOT NULL DEFAULT 'logged',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- Seed: Default hospital resources
-- -----------------------------------------------
INSERT INTO `admin_users` (`email`, `password_hash`, `display_name`, `status`) VALUES
('admin@test', '$2y$12$.hjrbdCZJFc3bkZwDVFE2eMrwbfzaCJ3dV81okwMXi1WWC4LRXA.K', 'Command Center Admin', 'active');

INSERT IGNORE INTO `resources` (`resource_code`, `name`, `type`, `ward`, `status`) VALUES
('ICU-01', 'ICU Bay 01', 'icu_bed', 'ICU-Ward A', 'occupied'),
('ICU-02', 'ICU Bay 02', 'icu_bed', 'ICU-Ward A', 'occupied'),
('ICU-03', 'ICU Bay 03', 'icu_bed', 'ICU-Ward A', 'occupied'),
('ICU-04', 'ICU Bay 04', 'icu_bed', 'ICU-Ward A', 'available'),
('ICU-05', 'ICU Bay 05', 'icu_bed', 'ICU-Ward B', 'available'),
('ICU-06', 'ICU Bay 06', 'icu_bed', 'ICU-Ward B', 'available'),
('ICU-07', 'ICU Bay 07', 'icu_bed', 'ICU-Ward B', 'occupied'),
('ICU-08', 'ICU Bay 08', 'icu_bed', 'ICU-Ward B', 'available'),
('GEN-01', 'General Bed 01', 'general_bed', 'GenMed-12', 'available'),
('GEN-02', 'General Bed 02', 'general_bed', 'GenMed-12', 'occupied'),
('GEN-03', 'General Bed 03', 'general_bed', 'GenMed-14', 'available'),
('GEN-04', 'General Bed 04', 'general_bed', 'GenMed-14', 'available'),
('GEN-05', 'General Bed 05', 'general_bed', 'GenMed-16', 'occupied'),
('GEN-06', 'General Bed 06', 'general_bed', 'GenMed-16', 'available'),
('OR-T1',  'OR Trauma 1',    'or_room',     'Trauma Center', 'available'),
('OR-T2',  'OR Trauma 2',    'or_room',     'Trauma Center', 'occupied'),
('OR-S1',  'OR Surgery 1',   'or_room',     'Surgical Wing', 'available'),
('VENT-01','Ventilator Unit 01','ventilator','ICU-Ward A', 'available'),
('VENT-02','Ventilator Unit 02','ventilator','ICU-Ward A', 'occupied'),
('VENT-03','Ventilator Unit 03','ventilator','ICU-Ward B', 'available'),
('MON-01', 'Cardiac Monitor 01','monitor',  'Tele-Unit 08', 'available'),
('MON-02', 'Cardiac Monitor 02','monitor',  'Tele-Unit 08', 'occupied'),
('DL-01',  'Discharge Lounge 01','discharge_lounge','Discharge Area', 'available'),
('DL-02',  'Discharge Lounge 02','discharge_lounge','Discharge Area', 'available');

-- -----------------------------------------------
-- Seed: Sample patients
-- -----------------------------------------------
INSERT IGNORE INTO `patients` (`patient_code`, `name`, `age`, `gender`, `symptoms`, `oxygen_level`, `severity`, `allocation`, `assigned_resource`, `severity_score`, `ward`, `status`) VALUES
('993-A2X', 'Elias Vance',    62, 'M', 'Acute respiratory distress, chest pain, fever',        86.5, 'critical', 'ICU',                 'ICU-01', 8.40, 'ICU-Ward A',    'admitted'),
('402-B1Y', 'Sarah Connor',   34, 'F', 'Mild headache, nausea',                                 97.0, 'stable',   'General Ward Bed',    'GEN-01', 2.10, 'GenMed-12',     'admitted'),
('771-C9Z', 'Marcus Wright',  45, 'M', 'Elevated heart rate, dizziness, shortness of breath',   91.0, 'moderate', 'High Dependency Bed', 'MON-01', 5.60, 'Tele-Unit 08',  'admitted'),
('105-D4W', 'Anya Corazon',   28, 'F', 'Post-surgery recovery, stable vitals',                  98.5, 'stable',   'Discharge Lounge',    'DL-01',  1.20, 'Discharge Area', 'admitted'),
('559-E7K', 'James Okoro',    71, 'M', 'Cardiac arrest recovery, low oxygen, multi-organ risk', 82.0, 'critical', 'ICU',                 'ICU-02', 9.10, 'ICU-Ward A',    'admitted');

-- -----------------------------------------------
-- Seed: Sample allocations
-- -----------------------------------------------
INSERT IGNORE INTO `allocations` (`patient_id`, `resource_id`, `ai_confidence`, `ai_explanation`, `ai_rationale`, `status`) VALUES
(1, 1, 94.20, 'Patient Elias Vance requires ICU-level care due to acute respiratory distress with critically low SpO2 levels.',
 '{"markers": [{"title": "Respiratory Distress Marker", "icon": "pulmonology", "color": "pink", "detail": "SpO2 levels dropped below 88% sustained for >15 minutes."}, {"title": "Historical Correlation", "icon": "history_edu", "color": "cyan", "detail": "Pattern matches 89% of previous admissions requiring intubation within 4 hours."}, {"title": "Medication Conflict Risk", "icon": "vaccines", "color": "purple", "detail": "Flagged interaction between current IV drip and proposed immediate-response protocols."}]}',
 'active'),
(2, 9, 87.50, 'Patient Sarah Connor has stable vitals suitable for general ward care. No ICU resources needed.',
 '{"markers": [{"title": "Stable Vital Signs", "icon": "vital_signs", "color": "cyan", "detail": "SpO2 at 97%, heart rate normal, blood pressure within range."}, {"title": "Low Severity Assessment", "icon": "check_circle", "color": "cyan", "detail": "AI severity score of 2.1 indicates minimal risk. General bed sufficient."}]}',
 'active'),
(3, 21, 91.30, 'Patient Marcus Wright shows cardiac instability requiring continuous telemetry monitoring.',
 '{"markers": [{"title": "Cardiac Instability", "icon": "monitor_heart", "color": "pink", "detail": "Elevated heart rate of 110 BPM with intermittent arrhythmia detected."}, {"title": "Oxygen Trend Analysis", "icon": "trending_down", "color": "cyan", "detail": "SpO2 declining trend from 95% to 91% over 2 hours suggests worsening condition."}, {"title": "Age-Risk Factor", "icon": "elderly", "color": "purple", "detail": "Age 45 with cardiovascular symptoms increases ICU transfer probability by 34%."}]}',
 'active'),
(4, 23, 95.80, 'Patient Anya Corazon is post-surgical with excellent recovery metrics. Discharge lounge appropriate.',
 '{"markers": [{"title": "Post-Surgery Recovery", "icon": "healing", "color": "cyan", "detail": "All post-operative vitals within normal range for 24+ hours."}, {"title": "Discharge Readiness", "icon": "check_circle", "color": "cyan", "detail": "Meets all discharge criteria. Resource can be freed for incoming patients."}]}',
 'active'),
(5, 2, 96.70, 'Patient James Okoro is in critical condition post cardiac arrest with multi-organ risk requiring immediate ICU resources.',
 '{"markers": [{"title": "Post Cardiac Arrest", "icon": "emergency", "color": "pink", "detail": "Patient experienced cardiac arrest 3 hours ago. Requires continuous cardiac monitoring and ventilator standby."}, {"title": "Multi-Organ Risk", "icon": "warning", "color": "pink", "detail": "Kidney function declining, liver enzymes elevated. Multi-organ failure risk at 45%."}, {"title": "Critical SpO2 Level", "icon": "pulmonology", "color": "purple", "detail": "SpO2 at 82% even with supplemental oxygen. May require intubation within 1 hour."}]}',
 'active');

-- -----------------------------------------------
-- Seed: Sample bias logs
-- -----------------------------------------------
INSERT IGNORE INTO `bias_logs` (`trigger_type`, `adjustment`, `authority`, `status`, `created_at`) VALUES
('Age Skew > 15%',          'Weight +0.5 to >65 cohort',         'System Auto', 'applied', '2023-10-27 14:32:01'),
('Manual Review',           'Reset baseline parameters',          'Admin_Chen',  'logged',  '2023-10-26 09:15:44'),
('Gender Variance > 18%',   'Equalize resource pool allocation',  'System Auto', 'applied', '2023-10-25 17:48:12');
