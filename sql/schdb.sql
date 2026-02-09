-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 02:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_courses`
--

CREATE TABLE `tb_courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(100) DEFAULT NULL,
  `units` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_courses`
--

INSERT INTO `tb_courses` (`course_id`, `course_code`, `course_name`, `units`) VALUES
(1, 'CE101', 'Surveying 1', 3),
(2, 'CE102', 'Engineering Mechanics', 4),
(3, 'CE103', 'Materials Science', 3),
(4, 'CE104', 'CAD and Design', 3),
(5, 'CE105', 'Structural Analysis', 4),
(6, 'CPE201', 'Digital Logic', 3),
(7, 'CPE202', 'Microprocessors', 4),
(8, 'CPE203', 'Circuit Analysis', 3),
(9, 'CPE204', 'Embedded Systems', 4),
(10, 'CPE205', 'Control Systems', 3),
(11, 'IT301', 'Database Design', 3),
(12, 'IT302', 'Web Development', 4),
(13, 'IT303', 'Software Engineering', 4),
(14, 'IT304', 'Network Security', 3),
(15, 'IT305', 'System Administration', 3),
(16, 'CS401', 'Data Structures', 4),
(17, 'CS402', 'Algorithms', 4),
(18, 'CS403', 'Artificial Intelligence', 3),
(19, 'CS404', 'Machine Learning', 4),
(20, 'CS405', 'Compiler Design', 3),
(21, 'ACT501', 'Financial Accounting', 3),
(22, 'ACT502', 'Management Accounting', 3),
(23, 'ACT503', 'Auditing', 3),
(24, 'ACT504', 'Tax Accounting', 3),
(25, 'ACT505', 'Advanced Accounting', 4);

-- --------------------------------------------------------

--
-- Table structure for table `tb_course_offerings`
--

CREATE TABLE `tb_course_offerings` (
  `offering_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `sy_id` int(11) DEFAULT NULL,
  `sem_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_course_offerings`
--

INSERT INTO `tb_course_offerings` (`offering_id`, `course_id`, `teacher_id`, `sy_id`, `sem_id`) VALUES
(1, 1, 1, 2, 1),
(2, 2, 2, 2, 1),
(3, 3, 3, 2, 1),
(4, 4, 4, 2, 1),
(5, 5, 5, 2, 1),
(6, 6, 1, 2, 1),
(7, 7, 2, 2, 1),
(8, 8, 3, 2, 1),
(9, 9, 4, 2, 1),
(10, 10, 5, 2, 1),
(11, 11, 1, 2, 1),
(12, 12, 2, 2, 1),
(13, 13, 3, 2, 1),
(14, 14, 4, 2, 1),
(15, 15, 5, 2, 1),
(16, 16, 1, 2, 1),
(17, 17, 2, 2, 1),
(18, 18, 3, 2, 1),
(19, 19, 4, 2, 1),
(20, 20, 5, 2, 1),
(21, 21, 1, 2, 1),
(22, 22, 2, 2, 1),
(23, 23, 3, 2, 1),
(24, 24, 4, 2, 1),
(25, 25, 5, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_enrollments`
--

CREATE TABLE `tb_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `offering_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_enrollments`
--

INSERT INTO `tb_enrollments` (`enrollment_id`, `student_id`, `offering_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 1, 5),
(6, 2, 1),
(7, 2, 2),
(8, 2, 3),
(9, 2, 4),
(10, 2, 5),
(11, 3, 1),
(12, 3, 2),
(13, 3, 3),
(14, 3, 4),
(15, 3, 5),
(16, 4, 1),
(17, 4, 2),
(18, 4, 3),
(19, 4, 4),
(20, 4, 5),
(21, 5, 1),
(22, 5, 2),
(23, 5, 3),
(24, 5, 4),
(25, 5, 5),
(26, 6, 6),
(27, 6, 7),
(28, 6, 8),
(29, 6, 9),
(30, 6, 10),
(31, 7, 6),
(32, 7, 7),
(33, 7, 8),
(34, 7, 9),
(35, 7, 10),
(36, 8, 6),
(37, 8, 7),
(38, 8, 8),
(39, 8, 9),
(40, 8, 10),
(41, 9, 6),
(42, 9, 7),
(43, 9, 8),
(44, 9, 9),
(45, 9, 10),
(46, 10, 6),
(47, 10, 7),
(48, 10, 8),
(49, 10, 9),
(50, 10, 10),
(51, 11, 11),
(52, 11, 12),
(53, 11, 13),
(54, 11, 14),
(55, 11, 15),
(56, 12, 11),
(57, 12, 12),
(58, 12, 13),
(59, 12, 14),
(60, 12, 15),
(61, 13, 11),
(62, 13, 12),
(63, 13, 13),
(64, 13, 14),
(65, 13, 15),
(66, 14, 11),
(67, 14, 12),
(68, 14, 13),
(69, 14, 14),
(70, 14, 15),
(71, 15, 11),
(72, 15, 12),
(73, 15, 13),
(74, 15, 14),
(75, 15, 15),
(76, 16, 16),
(77, 16, 17),
(78, 16, 18),
(79, 16, 19),
(80, 16, 20),
(81, 17, 16),
(82, 17, 17),
(83, 17, 18),
(84, 17, 19),
(85, 17, 20),
(86, 18, 16),
(87, 18, 17),
(88, 18, 18),
(89, 18, 19),
(90, 18, 20),
(91, 19, 16),
(92, 19, 17),
(93, 19, 18),
(94, 19, 19),
(95, 19, 20),
(96, 20, 16),
(97, 20, 17),
(98, 20, 18),
(99, 20, 19),
(100, 20, 20),
(101, 21, 21),
(102, 21, 22),
(103, 21, 23),
(104, 21, 24),
(105, 21, 25),
(106, 22, 21),
(107, 22, 22),
(108, 22, 23),
(109, 22, 24),
(110, 22, 25),
(111, 23, 21),
(112, 23, 22),
(113, 23, 23),
(114, 23, 24),
(115, 23, 25),
(116, 24, 21),
(117, 24, 22),
(118, 24, 23),
(119, 24, 24),
(120, 24, 25),
(121, 25, 21),
(122, 25, 22),
(123, 25, 23),
(124, 25, 24),
(125, 25, 25);

-- --------------------------------------------------------

--
-- Table structure for table `tb_grades`
--

CREATE TABLE `tb_grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `prelim` decimal(5,2) DEFAULT NULL,
  `midterm` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `semestral` decimal(5,2) DEFAULT NULL,
  `status` enum('PASSED','FAILED') DEFAULT NULL,
  `is_finalized` tinyint(4) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_grades`
--

INSERT INTO `tb_grades` (`grade_id`, `enrollment_id`, `prelim`, `midterm`, `finals`, `semestral`, `status`, `is_finalized`, `updated_at`) VALUES
(1, 1, 85.50, 88.00, 90.25, 87.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(2, 2, 78.00, 82.50, 85.75, 82.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(3, 3, 92.00, 89.50, 91.00, 90.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(4, 4, 76.25, 80.00, 83.50, 79.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(5, 5, 88.75, 86.00, 89.25, 88.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(6, 6, 90.00, 87.50, 92.00, 89.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(7, 7, 82.50, 85.00, 88.25, 85.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(8, 8, 79.00, 81.50, 84.00, 81.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(9, 9, 87.25, 89.00, 90.50, 88.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(10, 10, 75.00, 78.50, 82.00, 78.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(11, 11, 91.50, 88.75, 93.00, 91.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(12, 12, 83.00, 86.50, 87.75, 85.75, 'PASSED', 1, '2026-02-02 14:46:49'),
(13, 13, 77.50, 80.25, 83.50, 80.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(14, 14, 89.00, 91.00, 92.50, 90.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(15, 15, 81.75, 84.00, 86.25, 84.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(16, 16, 86.50, 88.25, 90.00, 88.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(17, 17, 74.00, 77.50, 80.00, 77.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(18, 18, 93.00, 90.50, 94.25, 92.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(19, 19, 80.25, 83.75, 85.50, 83.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(20, 20, 88.00, 85.50, 89.75, 87.75, 'PASSED', 1, '2026-02-02 14:46:49'),
(21, 21, 0.02, NULL, NULL, NULL, NULL, 1, '2026-02-02 14:46:58'),
(22, 22, 79.50, 82.00, 84.75, 82.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(23, 23, 91.00, 88.50, 92.50, 90.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(24, 24, 76.75, 79.25, 82.00, 79.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(25, 25, 85.25, 87.75, 89.00, 87.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(26, 26, 90.50, 92.00, 93.75, 92.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(27, 27, 83.75, 86.25, 88.50, 86.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(28, 28, 78.00, 81.00, 83.25, 80.75, 'PASSED', 1, '2026-02-02 14:46:49'),
(29, 29, 86.50, 88.75, 90.25, 88.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(30, 30, 75.25, 78.00, 80.50, 77.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(31, 31, 92.75, 90.25, 94.00, 92.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(32, 32, 84.00, 86.50, 88.00, 86.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(33, 33, 77.25, 80.50, 82.75, 80.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(34, 34, 89.50, 91.25, 93.00, 91.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(35, 35, 82.00, 84.75, 86.50, 84.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(36, 36, 88.25, 90.00, 91.75, 90.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(37, 37, 74.50, 77.00, 79.25, 76.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(38, 38, 93.50, 91.00, 95.00, 93.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(39, 39, 81.00, 83.50, 85.25, 83.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(40, 40, 87.75, 89.25, 90.50, 89.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(41, 41, 86.00, 88.50, 90.75, 88.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(42, 42, 78.50, 81.25, 83.50, 81.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(43, 43, 90.25, 87.75, 91.50, 89.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(44, 44, 75.75, 78.50, 81.00, 78.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(45, 45, 84.50, 86.75, 88.25, 86.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(46, 46, 91.75, 93.00, 94.50, 93.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(47, 47, 82.25, 85.00, 87.25, 84.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(48, 48, 77.00, 79.75, 82.00, 79.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(49, 49, 85.75, 87.50, 89.75, 87.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(50, 50, 74.25, 77.25, 79.50, 77.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(51, 51, 93.25, 91.50, 95.25, 93.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(52, 52, 83.50, 85.75, 87.50, 85.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(53, 53, 76.50, 79.00, 81.25, 78.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(54, 54, 88.75, 90.50, 92.25, 90.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(55, 55, 81.25, 83.75, 85.75, 83.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(56, 56, 87.50, 89.75, 91.00, 89.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(57, 57, 73.75, 76.50, 78.75, 76.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(58, 58, 94.00, 92.25, 96.00, 94.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(59, 59, 80.50, 82.75, 84.50, 82.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(60, 60, 86.25, 88.00, 89.50, 87.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(61, 61, 89.25, 91.00, 93.50, 91.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(62, 62, 79.00, 81.75, 84.00, 81.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(63, 63, 92.50, 90.00, 94.75, 92.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(64, 64, 76.00, 78.75, 81.50, 78.75, 'PASSED', 1, '2026-02-02 14:46:49'),
(65, 65, 85.00, 87.25, 89.50, 87.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(66, 66, 90.75, 92.50, 94.00, 92.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(67, 67, 83.25, 85.50, 87.75, 85.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(68, 68, 77.75, 80.00, 82.25, 80.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(69, 69, 86.75, 88.25, 90.00, 88.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(70, 70, 75.50, 78.25, 80.75, 78.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(71, 71, 93.75, 91.75, 95.50, 93.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(72, 72, 84.25, 86.00, 88.25, 86.17, 'PASSED', 1, '2026-02-02 14:46:49'),
(73, 73, 76.75, 79.50, 81.75, 79.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(74, 74, 89.00, 90.75, 92.75, 90.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(75, 75, 82.50, 84.25, 86.00, 84.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(76, 76, 88.50, 90.25, 92.00, 90.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(77, 77, 74.00, 76.75, 79.00, 76.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(78, 78, 94.50, 92.75, 96.50, 94.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(79, 79, 80.75, 83.00, 85.00, 82.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(80, 80, 87.00, 89.00, 90.75, 88.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(81, 81, 85.75, 87.50, 89.25, 87.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(82, 82, 78.25, 80.75, 83.00, 80.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(83, 83, 91.25, 88.75, 92.75, 90.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(84, 84, 75.50, 78.00, 80.25, 77.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(85, 85, 84.75, 86.50, 88.50, 86.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(86, 86, 90.00, 91.75, 93.25, 91.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(87, 87, 82.75, 85.25, 87.00, 85.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(88, 88, 77.50, 80.25, 82.50, 80.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(89, 89, 86.00, 87.75, 89.50, 87.75, 'PASSED', 1, '2026-02-02 14:46:49'),
(90, 90, 74.75, 77.50, 79.75, 77.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(91, 91, 92.25, 90.50, 94.25, 92.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(92, 92, 83.75, 85.50, 87.25, 85.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(93, 93, 76.25, 78.75, 81.00, 78.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(94, 94, 88.50, 90.00, 91.75, 90.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(95, 95, 81.50, 83.25, 85.50, 83.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(96, 96, 87.25, 89.00, 90.50, 88.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(97, 97, 73.50, 76.25, 78.50, 76.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(98, 98, 93.00, 91.25, 95.00, 93.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(99, 99, 80.00, 82.50, 84.25, 82.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(100, 100, 86.50, 88.25, 90.00, 88.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(101, 101, 68.09, 72.50, 70.00, 70.20, 'FAILED', 1, '2026-02-02 14:47:25'),
(102, 102, 85.50, 88.00, 90.25, 87.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(103, 103, 70.00, 70.00, 70.00, 70.00, 'FAILED', 1, '2026-02-03 00:48:01'),
(104, 104, 65.00, 68.50, 67.00, 66.83, 'FAILED', 1, '2026-02-02 14:46:49'),
(105, 105, 88.75, 86.00, 89.25, 88.00, 'PASSED', 1, '2026-02-02 14:46:49'),
(106, 106, 78.50, 81.25, 83.50, 81.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(107, 107, 69.00, 71.50, 70.25, 70.25, 'FAILED', 1, '2026-02-02 14:46:49'),
(108, 108, 90.25, 87.75, 91.50, 89.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(109, 109, 84.50, 86.75, 88.25, 86.50, 'PASSED', 1, '2026-02-02 14:46:49'),
(110, 110, 91.75, 93.00, 94.50, 93.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(111, 111, 82.25, 85.00, 87.25, 84.83, 'PASSED', 1, '2026-02-02 14:46:49'),
(112, 112, 67.50, 70.00, 68.75, 68.75, 'FAILED', 1, '2026-02-02 14:46:49'),
(113, 113, 85.75, 87.50, 89.75, 87.67, 'PASSED', 1, '2026-02-02 14:46:49'),
(114, 114, 93.25, 91.50, 95.25, 93.33, 'PASSED', 1, '2026-02-02 14:46:49'),
(115, 115, 83.50, 85.75, 87.50, 85.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(116, 116, 87.50, 89.75, 91.00, 89.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(117, 117, 94.00, 92.25, 96.00, 94.08, 'PASSED', 1, '2026-02-02 14:46:49'),
(118, 118, 80.50, 82.75, 84.50, 82.58, 'PASSED', 1, '2026-02-02 14:46:49'),
(119, 119, 86.25, 88.00, 89.50, 87.92, 'PASSED', 1, '2026-02-02 14:46:49'),
(120, 120, 89.25, 91.00, 93.50, 91.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(121, 121, 66.00, 69.50, 68.00, 67.83, 'FAILED', 1, '2026-02-02 14:46:49'),
(122, 122, 92.50, 90.00, 94.75, 92.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(123, 123, 85.00, 87.25, 89.50, 87.25, 'PASSED', 1, '2026-02-02 14:46:49'),
(124, 124, 90.75, 92.50, 94.00, 92.42, 'PASSED', 1, '2026-02-02 14:46:49'),
(125, 125, 83.25, 85.50, 87.75, 85.50, 'PASSED', 1, '2026-02-02 14:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `tb_grade_audit_logs`
--

CREATE TABLE `tb_grade_audit_logs` (
  `audit_id` int(11) NOT NULL,
  `grade_id` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `old_prelim` decimal(5,2) DEFAULT NULL,
  `old_midterm` decimal(5,2) DEFAULT NULL,
  `old_finals` decimal(5,2) DEFAULT NULL,
  `old_semestral` decimal(5,2) DEFAULT NULL,
  `old_status` enum('PASSED','FAILED') DEFAULT NULL,
  `new_prelim` decimal(5,2) DEFAULT NULL,
  `new_midterm` decimal(5,2) DEFAULT NULL,
  `new_finals` decimal(5,2) DEFAULT NULL,
  `new_semestral` decimal(5,2) DEFAULT NULL,
  `new_status` enum('PASSED','FAILED') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_grade_audit_logs`
--

INSERT INTO `tb_grade_audit_logs` (`audit_id`, `grade_id`, `changed_by`, `old_prelim`, `old_midterm`, `old_finals`, `old_semestral`, `old_status`, `new_prelim`, `new_midterm`, `new_finals`, `new_semestral`, `new_status`, `reason`, `changed_at`) VALUES
(1, 21, 1, 87.00, 89.50, 91.25, 89.25, 'PASSED', 0.02, NULL, NULL, NULL, NULL, 'a', '2026-02-02 14:46:58'),
(2, 101, 1, 68.00, 72.50, 70.00, 70.17, 'FAILED', 68.09, 72.50, 70.00, 70.20, 'FAILED', 'a', '2026-02-02 14:47:25');

-- --------------------------------------------------------

--
-- Table structure for table `tb_program_courses`
--

CREATE TABLE `tb_program_courses` (
  `program` enum('Civil Eng','Computer Eng','IT','CS','ACT') NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_program_courses`
--

INSERT INTO `tb_program_courses` (`program`, `course_id`) VALUES
('Civil Eng', 1),
('Civil Eng', 2),
('Civil Eng', 3),
('Civil Eng', 4),
('Civil Eng', 5),
('Computer Eng', 6),
('Computer Eng', 7),
('Computer Eng', 8),
('Computer Eng', 9),
('Computer Eng', 10),
('IT', 11),
('IT', 12),
('IT', 13),
('IT', 14),
('IT', 15),
('CS', 16),
('CS', 17),
('CS', 18),
('CS', 19),
('CS', 20),
('ACT', 21),
('ACT', 22),
('ACT', 23),
('ACT', 24),
('ACT', 25);

-- --------------------------------------------------------

--
-- Table structure for table `tb_school_years`
--

CREATE TABLE `tb_school_years` (
  `sy_id` int(11) NOT NULL,
  `school_year` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_school_years`
--

INSERT INTO `tb_school_years` (`sy_id`, `school_year`) VALUES
(1, '2024-2025'),
(2, '2025-2026');

-- --------------------------------------------------------

--
-- Table structure for table `tb_semesters`
--

CREATE TABLE `tb_semesters` (
  `sem_id` int(11) NOT NULL,
  `semester` enum('1st','2nd') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_semesters`
--

INSERT INTO `tb_semesters` (`sem_id`, `semester`) VALUES
(1, '1st'),
(2, '2nd');

-- --------------------------------------------------------

--
-- Table structure for table `tb_students`
--

CREATE TABLE `tb_students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_no` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `program` enum('Civil Eng','Computer Eng','IT','CS','ACT') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_students`
--

INSERT INTO `tb_students` (`student_id`, `user_id`, `student_no`, `full_name`, `program`) VALUES
(1, 7, 'STU001', 'Alice Garcia', 'Civil Eng'),
(2, 8, 'STU002', 'Bob Martinez', 'Civil Eng'),
(3, 9, 'STU003', 'Carol Rodriguez', 'Civil Eng'),
(4, 10, 'STU004', 'David Lee', 'Civil Eng'),
(5, 11, 'STU005', 'Eva Chen', 'Civil Eng'),
(6, 12, 'STU006', 'Frank Wilson', 'Computer Eng'),
(7, 13, 'STU007', 'Grace Taylor', 'Computer Eng'),
(8, 14, 'STU008', 'Henry Anderson', 'Computer Eng'),
(9, 15, 'STU009', 'Iris Thomas', 'Computer Eng'),
(10, 16, 'STU010', 'Jack Moore', 'Computer Eng'),
(11, 17, 'STU011', 'Karen Jackson', 'IT'),
(12, 18, 'STU012', 'Leo White', 'IT'),
(13, 19, 'STU013', 'Mia Harris', 'IT'),
(14, 20, 'STU014', 'Noah Martin', 'IT'),
(15, 21, 'STU015', 'Olivia Clark', 'IT'),
(16, 22, 'STU016', 'Paul Lewis', 'CS'),
(17, 23, 'STU017', 'Quinn Walker', 'CS'),
(18, 24, 'STU018', 'Rachel Hall', 'CS'),
(19, 25, 'STU019', 'Sam Allen', 'CS'),
(20, 26, 'STU020', 'Tina Young', 'CS'),
(21, 27, 'STU021', 'Uma King', 'ACT'),
(22, 28, 'STU022', 'Victor Wright', 'ACT'),
(23, 29, 'STU023', 'Wendy Scott', 'ACT'),
(24, 30, 'STU024', 'Xavier Green', 'ACT'),
(25, 31, 'STU025', 'Yara Adams', 'ACT');

-- --------------------------------------------------------

--
-- Table structure for table `tb_teachers`
--

CREATE TABLE `tb_teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_teachers`
--

INSERT INTO `tb_teachers` (`teacher_id`, `user_id`, `full_name`) VALUES
(1, 2, 'Mr. John Smith'),
(2, 3, 'Ms. Sarah Johnson'),
(3, 4, 'Dr. Michael Brown'),
(4, 5, 'Prof. Emily Davis'),
(5, 6, 'Dr. Robert Wilson');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','student','teacher') NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`user_id`, `username`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-02-02 14:46:48'),
(2, 'teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '2026-02-02 14:46:48'),
(3, 'teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '2026-02-02 14:46:48'),
(4, 'teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '2026-02-02 14:46:48'),
(5, 'teacher4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '2026-02-02 14:46:48'),
(6, 'teacher5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 1, '2026-02-02 14:46:48'),
(7, 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(8, 'student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(9, 'student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(10, 'student4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(11, 'student5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(12, 'student6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(13, 'student7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(14, 'student8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(15, 'student9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(16, 'student10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(17, 'student11', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(18, 'student12', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(19, 'student13', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(20, 'student14', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(21, 'student15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(22, 'student16', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(23, 'student17', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(24, 'student18', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(25, 'student19', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(26, 'student20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(27, 'student21', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(28, 'student22', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(29, 'student23', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(30, 'student24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48'),
(31, 'student25', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, '2026-02-02 14:46:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_courses`
--
ALTER TABLE `tb_courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `tb_course_offerings`
--
ALTER TABLE `tb_course_offerings`
  ADD PRIMARY KEY (`offering_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `sy_id` (`sy_id`),
  ADD KEY `sem_id` (`sem_id`);

--
-- Indexes for table `tb_enrollments`
--
ALTER TABLE `tb_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `offering_id` (`offering_id`);

--
-- Indexes for table `tb_grades`
--
ALTER TABLE `tb_grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `tb_grade_audit_logs`
--
ALTER TABLE `tb_grade_audit_logs`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `tb_program_courses`
--
ALTER TABLE `tb_program_courses`
  ADD PRIMARY KEY (`program`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `tb_school_years`
--
ALTER TABLE `tb_school_years`
  ADD PRIMARY KEY (`sy_id`);

--
-- Indexes for table `tb_semesters`
--
ALTER TABLE `tb_semesters`
  ADD PRIMARY KEY (`sem_id`);

--
-- Indexes for table `tb_students`
--
ALTER TABLE `tb_students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_no` (`student_no`);

--
-- Indexes for table `tb_teachers`
--
ALTER TABLE `tb_teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_courses`
--
ALTER TABLE `tb_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tb_course_offerings`
--
ALTER TABLE `tb_course_offerings`
  MODIFY `offering_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tb_enrollments`
--
ALTER TABLE `tb_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `tb_grades`
--
ALTER TABLE `tb_grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `tb_grade_audit_logs`
--
ALTER TABLE `tb_grade_audit_logs`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_school_years`
--
ALTER TABLE `tb_school_years`
  MODIFY `sy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_semesters`
--
ALTER TABLE `tb_semesters`
  MODIFY `sem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_students`
--
ALTER TABLE `tb_students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tb_teachers`
--
ALTER TABLE `tb_teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_course_offerings`
--
ALTER TABLE `tb_course_offerings`
  ADD CONSTRAINT `tb_course_offerings_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tb_courses` (`course_id`),
  ADD CONSTRAINT `tb_course_offerings_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `tb_teachers` (`teacher_id`),
  ADD CONSTRAINT `tb_course_offerings_ibfk_3` FOREIGN KEY (`sy_id`) REFERENCES `tb_school_years` (`sy_id`),
  ADD CONSTRAINT `tb_course_offerings_ibfk_4` FOREIGN KEY (`sem_id`) REFERENCES `tb_semesters` (`sem_id`);

--
-- Constraints for table `tb_enrollments`
--
ALTER TABLE `tb_enrollments`
  ADD CONSTRAINT `tb_enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tb_students` (`student_id`),
  ADD CONSTRAINT `tb_enrollments_ibfk_2` FOREIGN KEY (`offering_id`) REFERENCES `tb_course_offerings` (`offering_id`);

--
-- Constraints for table `tb_grades`
--
ALTER TABLE `tb_grades`
  ADD CONSTRAINT `tb_grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `tb_enrollments` (`enrollment_id`);

--
-- Constraints for table `tb_grade_audit_logs`
--
ALTER TABLE `tb_grade_audit_logs`
  ADD CONSTRAINT `tb_grade_audit_logs_ibfk_1` FOREIGN KEY (`grade_id`) REFERENCES `tb_grades` (`grade_id`),
  ADD CONSTRAINT `tb_grade_audit_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `tb_users` (`user_id`);

--
-- Constraints for table `tb_program_courses`
--
ALTER TABLE `tb_program_courses`
  ADD CONSTRAINT `tb_program_courses_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tb_courses` (`course_id`);

--
-- Constraints for table `tb_students`
--
ALTER TABLE `tb_students`
  ADD CONSTRAINT `tb_students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`user_id`);

--
-- Constraints for table `tb_teachers`
--
ALTER TABLE `tb_teachers`
  ADD CONSTRAINT `tb_teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
