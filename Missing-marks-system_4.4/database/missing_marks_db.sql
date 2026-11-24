-- ============================================================
-- MISSING MARKS RESOLUTION SYSTEM
-- Kenya School of TVET
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS missing_marks_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE missing_marks_db;

-- ------------------------------------------------------------
-- Table: users
-- Stores both students and administrators
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)        NOT NULL,
    admission_no  VARCHAR(50)         NOT NULL UNIQUE,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    phone         VARCHAR(20)         DEFAULT NULL,
    course        VARCHAR(150)        DEFAULT NULL,
    password      VARCHAR(255)        NOT NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    staff_role    ENUM('Registrar','HOD','Trainer') DEFAULT NULL,
    status        ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: trainers
-- A manageable list of trainer names to speed up reporting
-- (Admin can add more; students can also type a new name)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trainers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    trainer_name  VARCHAR(120) NOT NULL UNIQUE,
    department    VARCHAR(120) DEFAULT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: missing_marks_reports
-- Core table: one row per missing-marks case reported
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS missing_marks_reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reference_no    VARCHAR(20)  NOT NULL UNIQUE,
    user_id         INT          NOT NULL,
    student_name    VARCHAR(120) NOT NULL,
    admission_no    VARCHAR(50)  NOT NULL,
    trainer_name    VARCHAR(120) NOT NULL,
    unit_name       VARCHAR(150) NOT NULL,
    unit_code       VARCHAR(50)  DEFAULT NULL,
    semester        ENUM('Semester 1','Semester 2','Semester 3') NOT NULL,
    academic_year   VARCHAR(20)  NOT NULL,
    exam_type       ENUM('CAT 1','CAT 2','END TERM','Both CATS & End Term','CAT','Main Exam','Assignment','Practical','Other') NOT NULL DEFAULT 'Both CATS & End Term',
    cat1_mark       DECIMAL(5,2) DEFAULT NULL,
    cat2_mark       DECIMAL(5,2) DEFAULT NULL,
    description     TEXT         DEFAULT NULL,
    status          ENUM('Pending','In Review','Resolved','Rejected') NOT NULL DEFAULT 'Pending',
    cat_grade       ENUM('A','A-','B+','B','B-','C+','C','C-','D+','D','D-','E') DEFAULT NULL,
    exam_grade      ENUM('A','A-','B+','B','B-','C+','C','C-','D+','D','D-','E') DEFAULT NULL,
    admin_remarks   TEXT         DEFAULT NULL,
    resolved_by     INT          DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_reports_status (status),
    KEY idx_reports_semester (semester),
    KEY idx_reports_trainer (trainer_name),
    KEY idx_reports_created (created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: report_status_history
-- Full audit trail / timeline for every report
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS report_status_history (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    report_id     INT NOT NULL,
    status        ENUM('Pending','In Review','Resolved','Rejected') NOT NULL,
    remarks       TEXT DEFAULT NULL,
    changed_by    INT DEFAULT NULL,
    changed_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_report FOREIGN KEY (report_id) REFERENCES missing_marks_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: notifications
-- Sample email/SMS/system notifications sent to students when
-- their report moves to verification, is approved, or rejected.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    report_id     INT DEFAULT NULL,
    channel       ENUM('system','email','sms') NOT NULL DEFAULT 'system',
    title         VARCHAR(150) NOT NULL,
    message       TEXT NOT NULL,
    recipient     VARCHAR(150) DEFAULT NULL,   -- the email address / phone number it was "sent" to
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_report FOREIGN KEY (report_id) REFERENCES missing_marks_reports(id) ON DELETE CASCADE,
    KEY idx_notif_user (user_id),
    KEY idx_notif_read (is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data: sample trainers (optional starter list, editable by admin)
-- ------------------------------------------------------------
INSERT IGNORE INTO trainers (trainer_name, department) VALUES
('Mr. James Otieno', 'Information Communication Technology'),
('Ms. Grace Wanjiru', 'Business Studies'),
('Mr. Peter Kamau', 'Electrical & Electronics Engineering'),
('Ms. Mary Achieng', 'Building & Civil Engineering'),
('Mr. Samuel Kiptoo', 'Automotive Engineering'),
('Ms. Lucy Nafula', 'Hospitality & Institutional Management');

-- NOTE:
-- The default Administrator account is NOT inserted here with a
-- hard-coded password hash. For security, run "setup_admin.php"
-- once in your browser after importing this database — it safely
-- creates the admin account using PHP's own password_hash()
-- function. See README.md for full instructions.
--
-- v2 UPDATE:
-- The default admin created by setup_admin.php is assigned the
-- staff_role "Registrar" and can create additional staff accounts
-- (Registrar / HOD / Trainer) from the Staff Portal at
-- admin/staff.php — no need to edit the database by hand to add
-- more staff users.
--
-- v3 UPDATE:
-- Missing marks reports now flow through a clear verification
-- workflow (Submitted → Under Verification → Approved/Rejected),
-- visible as a status tracker on the student dashboard. When a
-- staff member changes a report's status, sample email/SMS/system
-- notifications are logged to the "notifications" table and shown
-- on the student's dashboard.
