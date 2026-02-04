-- =====================================================
-- DATABASE
-- =====================================================
DROP DATABASE IF EXISTS SchDB;
CREATE DATABASE SchDB;
USE SchDB;

-- =====================================================
-- USERS & AUTH
-- =====================================================
CREATE TABLE tb_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','student','teacher') NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SCHOOL YEARS & SEMESTERS
-- =====================================================
CREATE TABLE tb_school_years (
    sy_id INT AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(9) NOT NULL -- e.g. 2024-2025
);

CREATE TABLE tb_semesters (
    sem_id INT AUTO_INCREMENT PRIMARY KEY,
    semester ENUM('1st','2nd') NOT NULL
);

-- =====================================================
-- STUDENTS
-- =====================================================
CREATE TABLE tb_students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    student_no VARCHAR(20) UNIQUE,
    full_name VARCHAR(100),
    program ENUM('Civil Eng','Computer Eng','IT','CS','ACT'),
    FOREIGN KEY (user_id) REFERENCES tb_users(user_id)
);

-- =====================================================
-- TEACHERS
-- =====================================================
CREATE TABLE tb_teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    full_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES tb_users(user_id)
);

-- =====================================================
-- COURSES
-- =====================================================
CREATE TABLE tb_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20),
    course_name VARCHAR(100),
    units INT
);

-- =====================================================
-- FIXED COURSES PER PROGRAM (FOR NOW)
-- =====================================================
CREATE TABLE tb_program_courses (
    program ENUM('Civil Eng','Computer Eng','IT','CS','ACT'),
    course_id INT,
    PRIMARY KEY (program, course_id),
    FOREIGN KEY (course_id) REFERENCES tb_courses(course_id)
);

-- =====================================================
-- COURSE OFFERINGS (WHO TEACHES WHAT, WHEN)
-- =====================================================
CREATE TABLE tb_course_offerings (
    offering_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    teacher_id INT,
    sy_id INT,
    sem_id INT,
    FOREIGN KEY (course_id) REFERENCES tb_courses(course_id),
    FOREIGN KEY (teacher_id) REFERENCES tb_teachers(teacher_id),
    FOREIGN KEY (sy_id) REFERENCES tb_school_years(sy_id),
    FOREIGN KEY (sem_id) REFERENCES tb_semesters(sem_id)
);

-- =====================================================
-- ENROLLMENTS (STUDENT ↔ COURSE)
-- =====================================================
CREATE TABLE tb_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    offering_id INT,
    FOREIGN KEY (student_id) REFERENCES tb_students(student_id),
    FOREIGN KEY (offering_id) REFERENCES tb_course_offerings(offering_id)
);

-- =====================================================
-- GRADES (EDITABLE, PARTIAL, AUTO-COMPUTED)
-- =====================================================
CREATE TABLE tb_grades (
    grade_id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNIQUE,

    prelim DECIMAL(5,2) NULL,
    midterm DECIMAL(5,2) NULL,
    finals DECIMAL(5,2) NULL,

    semestral DECIMAL(5,2) NULL,
    status ENUM('PASSED','FAILED') NULL,

    is_finalized TINYINT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (enrollment_id) REFERENCES tb_enrollments(enrollment_id)
);

-- =====================================================
-- ADMIN GRADE OVERRIDE AUDIT LOGS
-- =====================================================
CREATE TABLE tb_grade_audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    grade_id INT,
    changed_by INT,

    old_prelim DECIMAL(5,2),
    old_midterm DECIMAL(5,2),
    old_finals DECIMAL(5,2),
    old_semestral DECIMAL(5,2),
    old_status ENUM('PASSED','FAILED'),

    new_prelim DECIMAL(5,2),
    new_midterm DECIMAL(5,2),
    new_finals DECIMAL(5,2),
    new_semestral DECIMAL(5,2),
    new_status ENUM('PASSED','FAILED'),

    reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (grade_id) REFERENCES tb_grades(grade_id),
    FOREIGN KEY (changed_by) REFERENCES tb_users(user_id)
);

-- =====================================================
-- SEED DATA
-- =====================================================

-- ADMIN USER
INSERT INTO tb_users (username, password_hash, role, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- TEACHERS (5 users)
INSERT INTO tb_users (username, password_hash, role, is_active) VALUES
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1),
('teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1),
('teacher4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1),
('teacher5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1);

-- STUDENTS (5 users)
INSERT INTO tb_users (username, password_hash, role, is_active) VALUES
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1);

-- TEACHER PROFILES
INSERT INTO tb_teachers (user_id, full_name) VALUES
(2, 'Mr. John Smith'),
(3, 'Ms. Sarah Johnson'),
(4, 'Dr. Michael Brown'),
(5, 'Prof. Emily Davis'),
(6, 'Dr. Robert Wilson');

-- STUDENT PROFILES (5 students per program)
-- Civil Eng (5 students)
INSERT INTO tb_students (user_id, student_no, full_name, program) VALUES
(7, 'STU001', 'Alice Garcia', 'Civil Eng'),
(8, 'STU002', 'Bob Martinez', 'Civil Eng'),
(9, 'STU003', 'Carol Rodriguez', 'Civil Eng'),
(10, 'STU004', 'David Lee', 'Civil Eng'),
(11, 'STU005', 'Eva Chen', 'Civil Eng');

-- Additional students for other programs (20 more students)
INSERT INTO tb_users (username, password_hash, role, is_active) VALUES
('student6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student11', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student12', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student13', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student14', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student16', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student17', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student18', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student19', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student21', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student22', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student23', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('student25', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1);

-- Computer Eng (5 students)
INSERT INTO tb_students (user_id, student_no, full_name, program) VALUES
(12, 'STU006', 'Frank Wilson', 'Computer Eng'),
(13, 'STU007', 'Grace Taylor', 'Computer Eng'),
(14, 'STU008', 'Henry Anderson', 'Computer Eng'),
(15, 'STU009', 'Iris Thomas', 'Computer Eng'),
(16, 'STU010', 'Jack Moore', 'Computer Eng');

-- IT (5 students)
INSERT INTO tb_students (user_id, student_no, full_name, program) VALUES
(17, 'STU011', 'Karen Jackson', 'IT'),
(18, 'STU012', 'Leo White', 'IT'),
(19, 'STU013', 'Mia Harris', 'IT'),
(20, 'STU014', 'Noah Martin', 'IT'),
(21, 'STU015', 'Olivia Clark', 'IT');

-- CS (5 students)
INSERT INTO tb_students (user_id, student_no, full_name, program) VALUES
(22, 'STU016', 'Paul Lewis', 'CS'),
(23, 'STU017', 'Quinn Walker', 'CS'),
(24, 'STU018', 'Rachel Hall', 'CS'),
(25, 'STU019', 'Sam Allen', 'CS'),
(26, 'STU020', 'Tina Young', 'CS');

-- ACT (5 students)
INSERT INTO tb_students (user_id, student_no, full_name, program) VALUES
(27, 'STU021', 'Uma King', 'ACT'),
(28, 'STU022', 'Victor Wright', 'ACT'),
(29, 'STU023', 'Wendy Scott', 'ACT'),
(30, 'STU024', 'Xavier Green', 'ACT'),
(31, 'STU025', 'Yara Adams', 'ACT');

-- COURSES (5 courses per program = 25 total)
INSERT INTO tb_courses (course_code, course_name, units) VALUES
-- Civil Eng
('CE101', 'Surveying 1', 3),
('CE102', 'Engineering Mechanics', 4),
('CE103', 'Materials Science', 3),
('CE104', 'CAD and Design', 3),
('CE105', 'Structural Analysis', 4),
-- Computer Eng
('CPE201', 'Digital Logic', 3),
('CPE202', 'Microprocessors', 4),
('CPE203', 'Circuit Analysis', 3),
('CPE204', 'Embedded Systems', 4),
('CPE205', 'Control Systems', 3),
-- IT
('IT301', 'Database Design', 3),
('IT302', 'Web Development', 4),
('IT303', 'Software Engineering', 4),
('IT304', 'Network Security', 3),
('IT305', 'System Administration', 3),
-- CS
('CS401', 'Data Structures', 4),
('CS402', 'Algorithms', 4),
('CS403', 'Artificial Intelligence', 3),
('CS404', 'Machine Learning', 4),
('CS405', 'Compiler Design', 3),
-- ACT
('ACT501', 'Financial Accounting', 3),
('ACT502', 'Management Accounting', 3),
('ACT503', 'Auditing', 3),
('ACT504', 'Tax Accounting', 3),
('ACT505', 'Advanced Accounting', 4);

-- PROGRAM TO COURSES MAPPING
-- Civil Eng
INSERT INTO tb_program_courses (program, course_id) VALUES
('Civil Eng', 1), ('Civil Eng', 2), ('Civil Eng', 3), ('Civil Eng', 4), ('Civil Eng', 5),
-- Computer Eng
('Computer Eng', 6), ('Computer Eng', 7), ('Computer Eng', 8), ('Computer Eng', 9), ('Computer Eng', 10),
-- IT
('IT', 11), ('IT', 12), ('IT', 13), ('IT', 14), ('IT', 15),
-- CS
('CS', 16), ('CS', 17), ('CS', 18), ('CS', 19), ('CS', 20),
-- ACT
('ACT', 21), ('ACT', 22), ('ACT', 23), ('ACT', 24), ('ACT', 25);

-- SCHOOL YEARS
INSERT INTO tb_school_years (school_year) VALUES
('2024-2025'),
('2025-2026');

-- SEMESTERS
INSERT INTO tb_semesters (semester) VALUES
('1st'),
('2nd');

-- COURSE OFFERINGS (Teachers teaching courses, SY 2025-2026, 1st Semester)
INSERT INTO tb_course_offerings (course_id, teacher_id, sy_id, sem_id) VALUES
(1, 1, 2, 1), (2, 2, 2, 1), (3, 3, 2, 1), (4, 4, 2, 1), (5, 5, 2, 1),
(6, 1, 2, 1), (7, 2, 2, 1), (8, 3, 2, 1), (9, 4, 2, 1), (10, 5, 2, 1),
(11, 1, 2, 1), (12, 2, 2, 1), (13, 3, 2, 1), (14, 4, 2, 1), (15, 5, 2, 1),
(16, 1, 2, 1), (17, 2, 2, 1), (18, 3, 2, 1), (19, 4, 2, 1), (20, 5, 2, 1),
(21, 1, 2, 1), (22, 2, 2, 1), (23, 3, 2, 1), (24, 4, 2, 1), (25, 5, 2, 1);

-- ENROLLMENTS (Each student enrolled in 5 courses)
-- Student 1-5 (Civil Eng): Courses 1-5
INSERT INTO tb_enrollments (student_id, offering_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5),
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5),
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5),
(5, 1), (5, 2), (5, 3), (5, 4), (5, 5),
-- Student 6-10 (Computer Eng): Courses 6-10
(6, 6), (6, 7), (6, 8), (6, 9), (6, 10),
(7, 6), (7, 7), (7, 8), (7, 9), (7, 10),
(8, 6), (8, 7), (8, 8), (8, 9), (8, 10),
(9, 6), (9, 7), (9, 8), (9, 9), (9, 10),
(10, 6), (10, 7), (10, 8), (10, 9), (10, 10),
-- Student 11-15 (IT): Courses 11-15
(11, 11), (11, 12), (11, 13), (11, 14), (11, 15),
(12, 11), (12, 12), (12, 13), (12, 14), (12, 15),
(13, 11), (13, 12), (13, 13), (13, 14), (13, 15),
(14, 11), (14, 12), (14, 13), (14, 14), (14, 15),
(15, 11), (15, 12), (15, 13), (15, 14), (15, 15),
-- Student 16-20 (CS): Courses 16-20
(16, 16), (16, 17), (16, 18), (16, 19), (16, 20),
(17, 16), (17, 17), (17, 18), (17, 19), (17, 20),
(18, 16), (18, 17), (18, 18), (18, 19), (18, 20),
(19, 16), (19, 17), (19, 18), (19, 19), (19, 20),
(20, 16), (20, 17), (20, 18), (20, 19), (20, 20),
-- Student 21-25 (ACT): Courses 21-25
(21, 21), (21, 22), (21, 23), (21, 24), (21, 25),
(22, 21), (22, 22), (22, 23), (22, 24), (22, 25),
(23, 21), (23, 22), (23, 23), (23, 24), (23, 25),
(24, 21), (24, 22), (24, 23), (24, 24), (24, 25),
(25, 21), (25, 22), (25, 23), (25, 24), (25, 25);

-- GRADES (Random grades for each enrollment)
INSERT INTO tb_grades (enrollment_id, prelim, midterm, finals, semestral, status, is_finalized) VALUES
-- Student 1-5 (Civil Eng)
(1, 85.50, 88.00, 90.25, 87.92, 'PASSED', 1),
(2, 78.00, 82.50, 85.75, 82.08, 'PASSED', 1),
(3, 92.00, 89.50, 91.00, 90.83, 'PASSED', 1),
(4, 76.25, 80.00, 83.50, 79.92, 'PASSED', 1),
(5, 88.75, 86.00, 89.25, 88.00, 'PASSED', 1),
(6, 90.00, 87.50, 92.00, 89.83, 'PASSED', 1),
(7, 82.50, 85.00, 88.25, 85.25, 'PASSED', 1),
(8, 79.00, 81.50, 84.00, 81.50, 'PASSED', 1),
(9, 87.25, 89.00, 90.50, 88.92, 'PASSED', 1),
(10, 75.00, 78.50, 82.00, 78.50, 'PASSED', 1),
(11, 91.50, 88.75, 93.00, 91.08, 'PASSED', 1),
(12, 83.00, 86.50, 87.75, 85.75, 'PASSED', 1),
(13, 77.50, 80.25, 83.50, 80.42, 'PASSED', 1),
(14, 89.00, 91.00, 92.50, 90.83, 'PASSED', 1),
(15, 81.75, 84.00, 86.25, 84.00, 'PASSED', 1),
(16, 86.50, 88.25, 90.00, 88.25, 'PASSED', 1),
(17, 74.00, 77.50, 80.00, 77.17, 'PASSED', 1),
(18, 93.00, 90.50, 94.25, 92.58, 'PASSED', 1),
(19, 80.25, 83.75, 85.50, 83.17, 'PASSED', 1),
(20, 88.00, 85.50, 89.75, 87.75, 'PASSED', 1),
-- Student 6-10 (Computer Eng)
(21, 87.00, 89.50, 91.25, 89.25, 'PASSED', 1),
(22, 79.50, 82.00, 84.75, 82.08, 'PASSED', 1),
(23, 91.00, 88.50, 92.50, 90.67, 'PASSED', 1),
(24, 76.75, 79.25, 82.00, 79.33, 'PASSED', 1),
(25, 85.25, 87.75, 89.00, 87.33, 'PASSED', 1),
(26, 90.50, 92.00, 93.75, 92.08, 'PASSED', 1),
(27, 83.75, 86.25, 88.50, 86.17, 'PASSED', 1),
(28, 78.00, 81.00, 83.25, 80.75, 'PASSED', 1),
(29, 86.50, 88.75, 90.25, 88.50, 'PASSED', 1),
(30, 75.25, 78.00, 80.50, 77.92, 'PASSED', 1),
(31, 92.75, 90.25, 94.00, 92.33, 'PASSED', 1),
(32, 84.00, 86.50, 88.00, 86.17, 'PASSED', 1),
(33, 77.25, 80.50, 82.75, 80.17, 'PASSED', 1),
(34, 89.50, 91.25, 93.00, 91.25, 'PASSED', 1),
(35, 82.00, 84.75, 86.50, 84.42, 'PASSED', 1),
(36, 88.25, 90.00, 91.75, 90.00, 'PASSED', 1),
(37, 74.50, 77.00, 79.25, 76.92, 'PASSED', 1),
(38, 93.50, 91.00, 95.00, 93.17, 'PASSED', 1),
(39, 81.00, 83.50, 85.25, 83.25, 'PASSED', 1),
(40, 87.75, 89.25, 90.50, 89.17, 'PASSED', 1),
-- Student 11-15 (IT)
(41, 86.00, 88.50, 90.75, 88.42, 'PASSED', 1),
(42, 78.50, 81.25, 83.50, 81.08, 'PASSED', 1),
(43, 90.25, 87.75, 91.50, 89.83, 'PASSED', 1),
(44, 75.75, 78.50, 81.00, 78.42, 'PASSED', 1),
(45, 84.50, 86.75, 88.25, 86.50, 'PASSED', 1),
(46, 91.75, 93.00, 94.50, 93.08, 'PASSED', 1),
(47, 82.25, 85.00, 87.25, 84.83, 'PASSED', 1),
(48, 77.00, 79.75, 82.00, 79.58, 'PASSED', 1),
(49, 85.75, 87.50, 89.75, 87.67, 'PASSED', 1),
(50, 74.25, 77.25, 79.50, 77.00, 'PASSED', 1),
(51, 93.25, 91.50, 95.25, 93.33, 'PASSED', 1),
(52, 83.50, 85.75, 87.50, 85.58, 'PASSED', 1),
(53, 76.50, 79.00, 81.25, 78.92, 'PASSED', 1),
(54, 88.75, 90.50, 92.25, 90.50, 'PASSED', 1),
(55, 81.25, 83.75, 85.75, 83.58, 'PASSED', 1),
(56, 87.50, 89.75, 91.00, 89.42, 'PASSED', 1),
(57, 73.75, 76.50, 78.75, 76.33, 'PASSED', 1),
(58, 94.00, 92.25, 96.00, 94.08, 'PASSED', 1),
(59, 80.50, 82.75, 84.50, 82.58, 'PASSED', 1),
(60, 86.25, 88.00, 89.50, 87.92, 'PASSED', 1),
-- Student 16-20 (CS)
(61, 89.25, 91.00, 93.50, 91.25, 'PASSED', 1),
(62, 79.00, 81.75, 84.00, 81.58, 'PASSED', 1),
(63, 92.50, 90.00, 94.75, 92.42, 'PASSED', 1),
(64, 76.00, 78.75, 81.50, 78.75, 'PASSED', 1),
(65, 85.00, 87.25, 89.50, 87.25, 'PASSED', 1),
(66, 90.75, 92.50, 94.00, 92.42, 'PASSED', 1),
(67, 83.25, 85.50, 87.75, 85.50, 'PASSED', 1),
(68, 77.75, 80.00, 82.25, 80.00, 'PASSED', 1),
(69, 86.75, 88.25, 90.00, 88.33, 'PASSED', 1),
(70, 75.50, 78.25, 80.75, 78.17, 'PASSED', 1),
(71, 93.75, 91.75, 95.50, 93.67, 'PASSED', 1),
(72, 84.25, 86.00, 88.25, 86.17, 'PASSED', 1),
(73, 76.75, 79.50, 81.75, 79.33, 'PASSED', 1),
(74, 89.00, 90.75, 92.75, 90.83, 'PASSED', 1),
(75, 82.50, 84.25, 86.00, 84.25, 'PASSED', 1),
(76, 88.50, 90.25, 92.00, 90.25, 'PASSED', 1),
(77, 74.00, 76.75, 79.00, 76.58, 'PASSED', 1),
(78, 94.50, 92.75, 96.50, 94.58, 'PASSED', 1),
(79, 80.75, 83.00, 85.00, 82.92, 'PASSED', 1),
(80, 87.00, 89.00, 90.75, 88.92, 'PASSED', 1),
-- Student 21-25 (ACT)
(81, 85.75, 87.50, 89.25, 87.50, 'PASSED', 1),
(82, 78.25, 80.75, 83.00, 80.67, 'PASSED', 1),
(83, 91.25, 88.75, 92.75, 90.92, 'PASSED', 1),
(84, 75.50, 78.00, 80.25, 77.92, 'PASSED', 1),
(85, 84.75, 86.50, 88.50, 86.58, 'PASSED', 1),
(86, 90.00, 91.75, 93.25, 91.67, 'PASSED', 1),
(87, 82.75, 85.25, 87.00, 85.00, 'PASSED', 1),
(88, 77.50, 80.25, 82.50, 80.08, 'PASSED', 1),
(89, 86.00, 87.75, 89.50, 87.75, 'PASSED', 1),
(90, 74.75, 77.50, 79.75, 77.33, 'PASSED', 1),
(91, 92.25, 90.50, 94.25, 92.33, 'PASSED', 1),
(92, 83.75, 85.50, 87.25, 85.50, 'PASSED', 1),
(93, 76.25, 78.75, 81.00, 78.67, 'PASSED', 1),
(94, 88.50, 90.00, 91.75, 90.08, 'PASSED', 1),
(95, 81.50, 83.25, 85.50, 83.42, 'PASSED', 1),
(96, 87.25, 89.00, 90.50, 88.92, 'PASSED', 1),
(97, 73.50, 76.25, 78.50, 76.08, 'PASSED', 1),
(98, 93.00, 91.25, 95.00, 93.08, 'PASSED', 1),
(99, 80.00, 82.50, 84.25, 82.25, 'PASSED', 1),
(100, 86.50, 88.25, 90.00, 88.25, 'PASSED', 1),
-- Additional enrollments with some failing grades
(101, 68.00, 72.50, 70.00, 70.17, 'FAILED', 1),
(102, 85.50, 88.00, 90.25, 87.92, 'PASSED', 1),
(103, 92.00, 89.50, 91.00, 90.83, 'PASSED', 1),
(104, 65.00, 68.50, 67.00, 66.83, 'FAILED', 1),
(105, 88.75, 86.00, 89.25, 88.00, 'PASSED', 1),
(106, 78.50, 81.25, 83.50, 81.08, 'PASSED', 1),
(107, 69.00, 71.50, 70.25, 70.25, 'FAILED', 1),
(108, 90.25, 87.75, 91.50, 89.83, 'PASSED', 1),
(109, 84.50, 86.75, 88.25, 86.50, 'PASSED', 1),
(110, 91.75, 93.00, 94.50, 93.08, 'PASSED', 1),
(111, 82.25, 85.00, 87.25, 84.83, 'PASSED', 1),
(112, 67.50, 70.00, 68.75, 68.75, 'FAILED', 1),
(113, 85.75, 87.50, 89.75, 87.67, 'PASSED', 1),
(114, 93.25, 91.50, 95.25, 93.33, 'PASSED', 1),
(115, 83.50, 85.75, 87.50, 85.58, 'PASSED', 1),
(116, 87.50, 89.75, 91.00, 89.42, 'PASSED', 1),
(117, 94.00, 92.25, 96.00, 94.08, 'PASSED', 1),
(118, 80.50, 82.75, 84.50, 82.58, 'PASSED', 1),
(119, 86.25, 88.00, 89.50, 87.92, 'PASSED', 1),
(120, 89.25, 91.00, 93.50, 91.25, 'PASSED', 1),
(121, 66.00, 69.50, 68.00, 67.83, 'FAILED', 1),
(122, 92.50, 90.00, 94.75, 92.42, 'PASSED', 1),
(123, 85.00, 87.25, 89.50, 87.25, 'PASSED', 1),
(124, 90.75, 92.50, 94.00, 92.42, 'PASSED', 1),
(125, 83.25, 85.50, 87.75, 85.50, 'PASSED', 1);
