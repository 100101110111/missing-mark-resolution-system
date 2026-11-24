-- ============================================================
-- MIGRATION SCRIPT: v1 → v2
-- Missing Marks Resolution System — Kenya School of TVET
--
-- Run this ONLY if you already imported the original
-- missing_marks_db.sql and have existing data you want to keep.
-- If you are installing fresh, just import missing_marks_db.sql
-- instead — it already includes everything below.
--
-- How to run: open phpMyAdmin → select missing_marks_db →
-- SQL tab → paste this file's contents → Go.
-- ============================================================

USE missing_marks_db;

-- Add staff role (Registrar / HOD / Trainer) to distinguish staff accounts
ALTER TABLE users
    ADD COLUMN staff_role ENUM('Registrar','HOD','Trainer') DEFAULT NULL AFTER role;

-- Mark any existing admin account(s) as Registrar by default
UPDATE users SET staff_role = 'Registrar' WHERE role = 'admin' AND staff_role IS NULL;

-- Add grade columns for awarding CAT and End Term marks
ALTER TABLE missing_marks_reports
    ADD COLUMN cat_grade  ENUM('A','A-','B+','B','B-','C+','C','C-','D+','D','D-','E') DEFAULT NULL AFTER status,
    ADD COLUMN exam_grade ENUM('A','A-','B+','B','B-','C+','C','C-','D+','D','D-','E') DEFAULT NULL AFTER cat_grade;

-- Indexes for scale (safe to skip if they already exist — MySQL will error, just ignore that line)
ALTER TABLE users ADD KEY idx_users_role (role);
ALTER TABLE users ADD KEY idx_users_status (status);
ALTER TABLE missing_marks_reports ADD KEY idx_reports_status (status);
ALTER TABLE missing_marks_reports ADD KEY idx_reports_semester (semester);
ALTER TABLE missing_marks_reports ADD KEY idx_reports_trainer (trainer_name);
ALTER TABLE missing_marks_reports ADD KEY idx_reports_created (created_at);
