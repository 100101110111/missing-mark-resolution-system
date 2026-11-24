-- Missing Marks Resolution System
-- Add numeric CAT 1 and CAT 2 marks.
-- CAT 1: 0-30; CAT 2: 0-70.
USE missing_marks_db;

ALTER TABLE missing_marks_reports
    ADD COLUMN cat1_mark DECIMAL(5,2) DEFAULT NULL AFTER exam_type,
    ADD COLUMN cat2_mark DECIMAL(5,2) DEFAULT NULL AFTER cat1_mark;
