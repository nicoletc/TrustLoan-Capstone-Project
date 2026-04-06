-- TrustLoan – Microfinance credit assessment (Ghana, informal sector)
-- MySQL / MariaDB. Run in phpMyAdmin or: mysql -u root -p < settings/trustloan.sql
-- This file is the single schema + seeds source; former alter/seed/backfill SQL files have been merged here.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Database
-- ---------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS trustloan;
USE trustloan;

-- ---------------------------------------------------------------------------
-- Borrowers (front-end users: sign-in, login, documents, loan flow)
-- ---------------------------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  phone VARCHAR(20) NOT NULL COMMENT 'E.164 or national format',
  password_hash VARCHAR(255) DEFAULT NULL COMMENT 'Set after first login/set-password',
  full_name VARCHAR(120) DEFAULT NULL COMMENT 'From login form or Ghana Card',
  role TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=admin (Admin pages), 2=customer (everyone who signs up)',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Phone verification (USSD/OTP)
-- ---------------------------------------------------------------------------
CREATE TABLE verification_codes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  phone VARCHAR(20) NOT NULL,
  code VARCHAR(10) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_verification_codes_phone (phone),
  KEY ix_verification_codes_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Applications (verifications queue → applicants; documents, loan amount, status)
-- ---------------------------------------------------------------------------
CREATE TABLE applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  requested_amount DECIMAL(12,2) NOT NULL COMMENT 'GH¢',
  repayment_weeks TINYINT UNSIGNED DEFAULT 12,
  business_type VARCHAR(60) DEFAULT NULL,
  business_duration VARCHAR(40) DEFAULT NULL,
  business_location VARCHAR(120) DEFAULT NULL,
  ghana_card_front_path VARCHAR(255) DEFAULT NULL,
  ghana_card_back_path VARCHAR(255) DEFAULT NULL,
  status ENUM('new','in_progress','approved','rejected') NOT NULL DEFAULT 'new',
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  officer_id INT UNSIGNED DEFAULT NULL COMMENT 'Admin user who reviewed',
  notes TEXT DEFAULT NULL,
  approval_congratulations_shown TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = borrower has seen congrats on first login after approval',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_applications_user (user_id),
  KEY ix_applications_status (status),
  KEY ix_applications_submitted (submitted_at),
  CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application photos (2–3: stall, goods, at work)
CREATE TABLE application_photos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_application_photos_application (application_id),
  CONSTRAINT fk_application_photos_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Guarantors (one per application)
-- ---------------------------------------------------------------------------
CREATE TABLE guarantors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  relationship VARCHAR(40) NOT NULL,
  occupation VARCHAR(120) DEFAULT NULL COMMENT 'What the person does (e.g. Trader, Teacher)',
  status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  confirmed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guarantors_application (application_id),
  CONSTRAINT fk_guarantors_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- MFIs and application → MFI selection
-- ---------------------------------------------------------------------------
CREATE TABLE mfis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  area_slug VARCHAR(60) NOT NULL COMMENT 'e.g. adenta, madina, tema',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_mfis_area (area_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_mfi (
  application_id INT UNSIGNED NOT NULL,
  mfi_id INT UNSIGNED NOT NULL,
  area VARCHAR(60) DEFAULT NULL COMMENT 'User-selected area at time of choice',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  KEY ix_application_mfi_mfi (mfi_id),
  CONSTRAINT fk_application_mfi_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE,
  CONSTRAINT fk_application_mfi_mfi FOREIGN KEY (mfi_id) REFERENCES mfis (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Groups (after approval; group head, members, repayment status)
-- ---------------------------------------------------------------------------
CREATE TABLE groups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mfi_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL COMMENT 'e.g. Group A – Adenta',
  repayment_status ENUM('good','watch','bad') NOT NULL DEFAULT 'good',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_groups_mfi (mfi_id),
  CONSTRAINT fk_groups_mfi FOREIGN KEY (mfi_id) REFERENCES mfis (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE group_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('member','head') NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_group_members_group_user (group_id, user_id),
  KEY ix_group_members_user (user_id),
  CONSTRAINT fk_group_members_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
  CONSTRAINT fk_group_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Meeting locations (per group: where and when)
-- ---------------------------------------------------------------------------
CREATE TABLE meeting_locations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL COMMENT 'e.g. Adenta Market, near taxi rank',
  meeting_day TINYINT UNSIGNED NOT NULL COMMENT '0=Sun, 1=Mon, … 6=Sat',
  meeting_time TIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_meeting_locations_group (group_id),
  CONSTRAINT fk_meeting_locations_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Loans (active / overdue; amount borrowed, balance, next due)
-- ---------------------------------------------------------------------------
CREATE TABLE loans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  application_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NOT NULL,
  amount_borrowed DECIMAL(12,2) NOT NULL COMMENT 'GH¢',
  balance_remaining DECIMAL(12,2) NOT NULL COMMENT 'GH¢',
  next_due_date DATE NOT NULL,
  status ENUM('active','overdue','closed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_loans_user (user_id),
  KEY ix_loans_status (status),
  KEY ix_loans_next_due (next_due_date),
  CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_loans_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE RESTRICT,
  CONSTRAINT fk_loans_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loan_repayments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id INT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  amount_due DECIMAL(12,2) NOT NULL,
  amount_paid DECIMAL(12,2) DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Only set on insert and when recording payment; no auto-update',
  PRIMARY KEY (id),
  KEY ix_loan_repayments_loan (loan_id),
  KEY ix_loan_repayments_due (due_date),
  CONSTRAINT fk_loan_repayments_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Credit score (per user; used on Check credit score page)
-- ---------------------------------------------------------------------------
CREATE TABLE credit_scores (
  user_id INT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL COMMENT '0–100 or similar',
  calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_credit_scores_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admin users (Settings: manage users removed; keep for officer_id, audit)
-- ---------------------------------------------------------------------------
CREATE TABLE admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','officer','verifier') NOT NULL DEFAULT 'officer',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link applications to officer (reviewer)
ALTER TABLE applications
  ADD CONSTRAINT fk_applications_officer FOREIGN KEY (officer_id) REFERENCES admin_users (id) ON DELETE SET NULL;

-- Seed one admin user (email: admin@trustloan.local, password: password – change after first login)
INSERT INTO admin_users (name, email, password_hash, role) VALUES
  ('Admin', 'admin@trustloan.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ---------------------------------------------------------------------------
-- Settings (Admin Settings page: loan products, repayment, penalties, risk, notifications)
-- ---------------------------------------------------------------------------
CREATE TABLE settings_loan_products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(60) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_loan_products_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings_repayment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  collection_day TINYINT UNSIGNED NOT NULL COMMENT '0=Sun … 6=Sat',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings_penalties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(60) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_penalties_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings_risk (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(60) NOT NULL COMMENT 'e.g. high_min, med_min',
  setting_value VARCHAR(60) NOT NULL COMMENT 'e.g. 5001, 3000',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_risk_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings_notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(60) NOT NULL COMMENT 'e.g. payment_reminder, overdue_notice',
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_notifications_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Audit logs (Admin: view changes)
-- ---------------------------------------------------------------------------
CREATE TABLE audit_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(120) NOT NULL COMMENT 'e.g. Approved verification, Recorded payment',
  entity_type VARCHAR(40) DEFAULT NULL COMMENT 'e.g. application, loan',
  entity_id INT UNSIGNED DEFAULT NULL,
  details TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_logs_admin (admin_user_id),
  KEY ix_audit_logs_created (created_at),
  CONSTRAINT fk_audit_logs_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Seed data (optional: MFIs, default settings)
-- ---------------------------------------------------------------------------
-- Ensure single MFI "Adenta Municipal" exists (idempotent)
UPDATE mfis SET name = 'Adenta Municipal' WHERE area_slug = 'adenta' LIMIT 1;
INSERT INTO mfis (name, area_slug)
SELECT 'Adenta Municipal', 'adenta'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mfis LIMIT 1);
/* Future: add more MFIs when needed. */

INSERT INTO settings_loan_products (setting_key, setting_value) VALUES
  ('min_amount', '500'),
  ('max_first_loan', '5000'),
  ('max_repeat_loan', '10000'),
  ('default_duration_weeks', '12'),
  ('interest_rate', '2.5% per month');

INSERT INTO settings_repayment (collection_day) VALUES (3); -- Wednesday

INSERT INTO settings_penalties (setting_key, setting_value) VALUES
  ('late_payment', 'GH¢ 5 per week late'),
  ('missed_meeting', 'GH¢ 10 per absence');

INSERT INTO settings_risk (setting_key, setting_value) VALUES
  ('high_min', '5001'),
  ('med_min', '3000');

INSERT INTO settings_notifications (setting_key, setting_value) VALUES
  ('payment_reminder', 'Your repayment of {amount} is due on {date}. Pay via *233*8# or the app.'),
  ('overdue_notice', 'Your loan is overdue. Please pay {amount} to avoid penalties.');

-- ---------------------------------------------------------------------------
-- One-time backfill (safe to re-run): set guarantor status to confirmed for already-approved applications
-- ---------------------------------------------------------------------------
UPDATE guarantors g
INNER JOIN applications a ON a.id = g.application_id
SET g.status = 'confirmed', g.confirmed_at = COALESCE(g.confirmed_at, NOW())
WHERE a.status = 'approved' AND g.status = 'pending';

SET FOREIGN_KEY_CHECKS = 1;
