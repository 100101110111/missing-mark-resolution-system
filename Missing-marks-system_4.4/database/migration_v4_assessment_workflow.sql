-- ============================================================
-- MISSING MARKS RESOLUTION SYSTEM — v4 assessment workflow
-- CAT 1: 0-30
-- CAT 2: 0-70
-- Both CATS & End Term: CATs numeric + End Term A-E
-- ============================================================
USE missing_marks_db;

-- Keep legacy values so existing reports do not become invalid.
ALTER TABLE missing_marks_reports
    MODIFY COLUMN exam_type ENUM(
        'CAT 1',
        'CAT 2',
        'END TERM',
        'Both CATS & End Term',
        'CAT',
        'Main Exam',
        'Assignment',
        'Practical',
        'Other'
    ) NOT NULL DEFAULT 'Both CATS & End Term';

-- Existing CAT reports from v3 used numeric CAT 1 + CAT 2 fields.
-- They are migrated into the new combined workflow so both marks remain usable.
UPDATE missing_marks_reports
SET exam_type = 'Both CATS & End Term'
WHERE exam_type = 'CAT';

-- Legacy CAT letter grades are no longer part of the new awarding UI.
-- End Term grades continue in exam_grade.
