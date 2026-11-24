-- ============================================================
-- MIGRATION SCRIPT: v2 → v3
-- Missing Marks Resolution System — Kenya School of TVET
--
-- Run this if you already have the v2 database (with staff_role,
-- cat_grade, exam_grade) and just want to add the new
-- notifications feature (status tracking + sample email/SMS
-- notifications on the student dashboard).
--
-- If you are installing fresh, just import missing_marks_db.sql
-- instead — it already includes everything below.
--
-- How to run: open phpMyAdmin → select missing_marks_db →
-- SQL tab → paste this file's contents → Go.
-- ============================================================

USE missing_marks_db;

CREATE TABLE IF NOT EXISTS notifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    report_id     INT DEFAULT NULL,
    channel       ENUM('system','email','sms') NOT NULL DEFAULT 'system',
    title         VARCHAR(150) NOT NULL,
    message       TEXT NOT NULL,
    recipient     VARCHAR(150) DEFAULT NULL,
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_report FOREIGN KEY (report_id) REFERENCES missing_marks_reports(id) ON DELETE CASCADE,
    KEY idx_notif_user (user_id),
    KEY idx_notif_read (is_read)
) ENGINE=InnoDB;
