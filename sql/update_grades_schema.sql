-- =====================================================
-- UPDATE GRADES SCHEMA TO SUPPORT TEXT VALUES
-- Allows: Numeric grades (85.5) AND text markers (INC, DROP, etc.)
-- =====================================================

USE SchDB;

-- Backup existing grades data first (optional but recommended)
-- CREATE TABLE tb_grades_backup AS SELECT * FROM tb_grades;

-- Step 1: Modify grade columns from DECIMAL to VARCHAR
ALTER TABLE tb_grades
    MODIFY COLUMN prelim VARCHAR(10) NULL,
    MODIFY COLUMN midterm VARCHAR(10) NULL,
    MODIFY COLUMN finals VARCHAR(10) NULL,
    MODIFY COLUMN semestral VARCHAR(10) NULL;

-- Step 2: Expand status options to include more academic statuses
ALTER TABLE tb_grades
    MODIFY COLUMN status ENUM('PASSED', 'FAILED', 'INCOMPLETE', 'DROPPED', 'PENDING') NULL;

-- Step 3: Update audit log table to match new structure
ALTER TABLE tb_grade_audit_logs
    MODIFY COLUMN old_prelim VARCHAR(10),
    MODIFY COLUMN old_midterm VARCHAR(10),
    MODIFY COLUMN old_finals VARCHAR(10),
    MODIFY COLUMN old_semestral VARCHAR(10),
    MODIFY COLUMN old_status ENUM('PASSED', 'FAILED', 'INCOMPLETE', 'DROPPED', 'PENDING');

ALTER TABLE tb_grade_audit_logs
    MODIFY COLUMN new_prelim VARCHAR(10),
    MODIFY COLUMN new_midterm VARCHAR(10),
    MODIFY COLUMN new_finals VARCHAR(10),
    MODIFY COLUMN new_semestral VARCHAR(10),
    MODIFY COLUMN new_status ENUM('PASSED', 'FAILED', 'INCOMPLETE', 'DROPPED', 'PENDING');

-- =====================================================
-- VERIFICATION QUERY
-- Run this to verify the changes
-- =====================================================
-- DESCRIBE tb_grades;
-- DESCRIBE tb_grade_audit_logs;

-- =====================================================
-- USAGE EXAMPLES
-- =====================================================

-- Example 1: Setting numeric grades
-- UPDATE tb_grades SET prelim = '85.5', midterm = '88.0' WHERE grade_id = 1;

-- Example 2: Setting text grades
-- UPDATE tb_grades SET prelim = 'INC', midterm = '82.5', finals = 'DROP' WHERE grade_id = 2;

-- Example 3: Setting status
-- UPDATE tb_grades SET status = 'INCOMPLETE' WHERE grade_id = 2;

-- =====================================================
-- COMMON GRADE TEXT VALUES
-- =====================================================
-- INC     - Incomplete
-- DROP    - Dropped
-- WP      - Withdrawn Passing
-- WF      - Withdrawn Failing
-- ABS     - Absent
-- NA      - Not Applicable
-- P       - Passing (for pass/fail courses)
-- F       - Failing (for pass/fail courses)
-- =====================================================
