-- =============================================================================
-- DATABASE SEED SCRIPT: student_data.sql
-- Student CRUD Application (HTML5, CSS3, JavaScript, Core PHP, MySQL)
-- Section 04 – Import Student Dataset
--
-- Source Data:
--   1. PDF 1: Student Details (B.Tech Computer Science and Business Systems)
--   2. PDF 2: Internal Test I Consolidated Marks Statement
--
-- Filters & Business Rules Applied:
--   - Boys records only (28 remaining students).
--   - Excluded student record completely omitted.
--   - PILLI DHARMA TEJA in marks PDF has no register number & not in student details; reported as unmatched.
--   - Real phone numbers replaced with unique, fictional placeholder VARCHAR values.
--   - 7 curriculum subjects imported with 100 maximum marks each.
--   - Exactly 196 marks records (28 students * 7 subjects) inserted.
--   - Attendance status and numeric marks preserved without data alteration.
--   - Idempotent: Safe to re-run using INSERT ... ON DUPLICATE KEY UPDATE.
-- =============================================================================

USE `student_management`;

-- Disable foreign key checks temporarily during bulk refresh
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. INSERT OR UPDATE CURRICULUM SUBJECTS (7 Core Subjects)
-- -----------------------------------------------------------------------------
INSERT INTO `subjects` (`id`, `subject_name`, `maximum_marks`) VALUES
(1, 'Fundamentals of Management', 100),
(2, 'Computer Architecture and Organization', 100),
(3, 'Java Programming', 100),
(4, 'Human Values and Ethics', 100),
(5, 'Formal Language and Automata Theory', 100),
(6, 'Database Management Systems', 100),
(7, 'Computational Statistics', 100)
ON DUPLICATE KEY UPDATE
  `subject_name` = VALUES(`subject_name`),
  `maximum_marks` = VALUES(`maximum_marks`);

-- -----------------------------------------------------------------------------
-- 2. INSERT OR UPDATE STUDENTS (28 Boys Records)
-- -----------------------------------------------------------------------------
INSERT INTO `students` (`id`, `student_name`, `register_no`, `mobile_number`,`dob`) VALUES
(1, 'ABISHEK C', '922525244002', '9876500001', '2007-10-21'),
(2, 'AJAI R', '922525244004', '9876500002', '2007-09-02'),
(3, 'AKILAN S R', '922525244005', '9876500003', '2007-11-23'),
(4, 'ANANDHARAM K', '922525244006', '9876500004', '2008-01-15'),
(5, 'ARUNKRISHNA B', '922525244008', '9876500005', '2007-10-04'),
(6, 'GOPI S', '922525244017', '9876500006', '2007-08-04'),
(7, 'HARIHARASUDHAN K', '922525244018', '9876500007', '2007-12-21'),
(8, 'JAYANTHAN P', '922525244022', '9876500008', '2008-03-07'),
(9, 'KANNAN C', '922525244025', '9876500009', '2008-05-09'),
(10, 'KRISHNAKUMAR A', '922525244026', '9876500010', '2007-10-20'),
(11, 'KUNGUMA SAKTHIVEL M', '922525244027', '9876500011', '2008-04-30'),
(12, 'MADHAN RAJ D', '922525244028', '9876500012', '2008-03-20'),
(13, 'MONITH RAJAA S', '922525244029', '9876500013', '2007-10-10'),
(14, 'MUHAMMADH FAAHIM', '922525244030', '9876500014', '2008-04-04'),
(15, 'MUTHUPANDI K', '922525244031', '9876500015', '2008-05-15'),
(16, 'NADIN S', '922525244032', '9876500016', '2008-07-10'),
(17, 'NAVAMANIKANDAN V', '922525244033', '9876500017', '2008-01-05'),
(18, 'PRATHIP V', '922525244038', '9876500018', '2008-02-03'),
(19, 'PRAVEEN K L', '922525244039', '9876500019', '2007-11-13'),
(20, 'RAGUL V', '922525244041', '9876500020', '2008-09-20'),
(21, 'RAM SURIYA B M', '922525244042', '9876500021', '2007-12-06'),
(22, 'SACHIN R', '922525244045', '9876500022', '2008-03-07'),
(23, 'SAKTHI SUNDARAM M P', '922525244046', '9876500023', '2007-01-02'),
(24, 'SELVA VIGNESHWAR S', '922525244049', '9876500024', '2007-12-30'),
(25, 'SHANMUGA PRIYAN P', '922525244050', '9876500025', '2008-05-16'),
(26, 'SIVASUBRAMANI N', '922525244053', '9876500026', '2007-08-08'),
(27, 'SUKANTH M', '922525244059', '9876500027', '2008-03-11'),
(28, 'VIJAYARAGAVAN A', '922525244064', '9876500028', '2007-11-16')
ON DUPLICATE KEY UPDATE
  `student_name` = VALUES(`student_name`),
  `mobile_number` = VALUES(`mobile_number`),
  `dob` = VALUES(`dob`);

-- -----------------------------------------------------------------------------
-- 3. INSERT OR UPDATE STUDENT MARKS (28 Students * 7 Subjects = 196 Records)
-- -----------------------------------------------------------------------------
INSERT INTO `student_marks` (`student_id`, `subject_id`, `marks_obtained`, `attendance_status`) VALUES
(1, 1, 82.00, 'Present'),
(1, 2, 78.00, 'Present'),
(1, 3, 72.00, 'Present'),
(1, 4, 62.00, 'Present'),
(1, 5, 80.00, 'Present'),
(1, 6, 64.00, 'Present'),
(1, 7, 44.00, 'Present'),
(2, 1, 68.00, 'Present'),
(2, 2, 64.00, 'Present'),
(2, 3, 74.00, 'Present'),
(2, 4, 52.00, 'Present'),
(2, 5, 60.00, 'Present'),
(2, 6, 74.00, 'Present'),
(2, 7, 42.00, 'Present'),
(3, 1, 38.00, 'Present'),
(3, 2, 18.00, 'Present'),
(3, 3, 32.00, 'Present'),
(3, 4, 56.00, 'Present'),
(3, 5, 50.00, 'Present'),
(3, 6, 22.00, 'Present'),
(3, 7, 6.00, 'Present'),
(4, 1, 48.00, 'Present'),
(4, 2, 62.00, 'Present'),
(4, 3, 60.00, 'Present'),
(4, 4, 50.00, 'Present'),
(4, 5, 50.00, 'Present'),
(4, 6, 74.00, 'Present'),
(4, 7, 22.00, 'Present'),
(5, 1, 42.00, 'Present'),
(5, 2, 20.00, 'Present'),
(5, 3, 64.00, 'Present'),
(5, 4, 38.00, 'Present'),
(5, 5, 50.00, 'Present'),
(5, 6, 66.00, 'Present'),
(5, 7, 60.00, 'Present'),
(6, 1, 48.00, 'Present'),
(6, 2, 60.00, 'Present'),
(6, 3, 50.00, 'Present'),
(6, 4, 46.00, 'Present'),
(6, 5, 34.00, 'Present'),
(6, 6, 40.00, 'Present'),
(6, 7, 10.00, 'Present'),
(7, 1, 66.00, 'Present'),
(7, 2, 60.00, 'Present'),
(7, 3, 72.00, 'Present'),
(7, 4, 44.00, 'Present'),
(7, 5, 60.00, 'Present'),
(7, 6, 74.00, 'Present'),
(7, 7, 42.00, 'Present'),
(8, 1, 60.00, 'Present'),
(8, 2, 60.00, 'Present'),
(8, 3, 74.00, 'Present'),
(8, 4, 28.00, 'Present'),
(8, 5, 36.00, 'Present'),
(8, 6, 60.00, 'Present'),
(8, 7, 60.00, 'Present'),
(9, 1, 82.00, 'Present'),
(9, 2, 68.00, 'Present'),
(9, 3, 74.00, 'Present'),
(9, 4, 60.00, 'Present'),
(9, 5, 72.00, 'Present'),
(9, 6, 66.00, 'Present'),
(9, 7, 62.00, 'Present'),
(10, 1, 60.00, 'Present'),
(10, 2, 66.00, 'Present'),
(10, 3, 64.00, 'Present'),
(10, 4, 62.00, 'Present'),
(10, 5, 72.00, 'Present'),
(10, 6, 60.00, 'Present'),
(10, 7, 30.00, 'Present'),
(11, 1, 60.00, 'Present'),
(11, 2, 30.00, 'Present'),
(11, 3, 46.00, 'Present'),
(11, 4, 32.00, 'Present'),
(11, 5, 16.00, 'Present'),
(11, 6, 44.00, 'Present'),
(11, 7, 60.00, 'Present'),
(12, 1, 80.00, 'Present'),
(12, 2, 68.00, 'Present'),
(12, 3, 70.00, 'Present'),
(12, 4, 62.00, 'Present'),
(12, 5, 66.00, 'Present'),
(12, 6, 70.00, 'Present'),
(12, 7, 60.00, 'Present'),
(13, 1, 62.00, 'Present'),
(13, 2, 70.00, 'Present'),
(13, 3, 68.00, 'Present'),
(13, 4, 44.00, 'Present'),
(13, 5, 32.00, 'Present'),
(13, 6, 52.00, 'Present'),
(13, 7, 32.00, 'Present'),
(14, 1, 62.00, 'Present'),
(14, 2, 60.00, 'Present'),
(14, 3, 96.00, 'Present'),
(14, 4, 44.00, 'Present'),
(14, 5, 60.00, 'Present'),
(14, 6, 66.00, 'Present'),
(14, 7, 60.00, 'Present'),
(15, 1, 50.00, 'Present'),
(15, 2, 60.00, 'Present'),
(15, 3, 60.00, 'Present'),
(15, 4, 52.00, 'Present'),
(15, 5, 60.00, 'Present'),
(15, 6, 52.00, 'Present'),
(15, 7, 42.00, 'Present'),
(16, 1, 60.00, 'Present'),
(16, 2, 60.00, 'Present'),
(16, 3, 86.00, 'Present'),
(16, 4, 50.00, 'Present'),
(16, 5, 66.00, 'Present'),
(16, 6, 62.00, 'Present'),
(16, 7, 74.00, 'Present'),
(17, 1, 60.00, 'Present'),
(17, 2, 22.00, 'Present'),
(17, 3, 34.00, 'Present'),
(17, 4, 42.00, 'Present'),
(17, 5, 36.00, 'Present'),
(17, 6, 50.00, 'Present'),
(17, 7, 4.00, 'Present'),
(18, 1, 72.00, 'Present'),
(18, 2, 60.00, 'Present'),
(18, 3, 74.00, 'Present'),
(18, 4, 42.00, 'Present'),
(18, 5, 68.00, 'Present'),
(18, 6, 64.00, 'Present'),
(18, 7, 26.00, 'Present'),
(19, 1, 82.00, 'Present'),
(19, 2, 68.00, 'Present'),
(19, 3, 78.00, 'Present'),
(19, 4, 70.00, 'Present'),
(19, 5, 62.00, 'Present'),
(19, 6, 78.00, 'Present'),
(19, 7, 22.00, 'Present'),
(20, 1, 68.00, 'Present'),
(20, 2, 60.00, 'Present'),
(20, 3, 66.00, 'Present'),
(20, 4, 60.00, 'Present'),
(20, 5, 68.00, 'Present'),
(20, 6, 64.00, 'Present'),
(20, 7, 60.00, 'Present'),
(21, 1, 44.00, 'Present'),
(21, 2, 42.00, 'Present'),
(21, 3, 62.00, 'Present'),
(21, 4, 60.00, 'Present'),
(21, 5, 44.00, 'Present'),
(21, 6, 46.00, 'Present'),
(21, 7, 10.00, 'Present'),
(22, 1, 60.00, 'Present'),
(22, 2, 18.00, 'Present'),
(22, 3, 52.00, 'Present'),
(22, 4, 44.00, 'Present'),
(22, 5, 34.00, 'Present'),
(22, 6, 46.00, 'Present'),
(22, 7, 40.00, 'Present'),
(23, 1, 78.00, 'Present'),
(23, 2, 70.00, 'Present'),
(23, 3, 90.00, 'Present'),
(23, 4, 62.00, 'Present'),
(23, 5, 64.00, 'Present'),
(23, 6, 60.00, 'Present'),
(23, 7, 60.00, 'Present'),
(24, 1, 60.00, 'Present'),
(24, 2, 34.00, 'Present'),
(24, 3, 54.00, 'Present'),
(24, 4, 40.00, 'Present'),
(24, 5, 46.00, 'Present'),
(24, 6, 32.00, 'Present'),
(24, 7, 8.00, 'Present'),
(25, 1, 64.00, 'Present'),
(25, 2, 60.00, 'Present'),
(25, 3, 72.00, 'Present'),
(25, 4, 60.00, 'Present'),
(25, 5, 62.00, 'Present'),
(25, 6, 74.00, 'Present'),
(25, 7, 64.00, 'Present'),
(26, 1, 32.00, 'Present'),
(26, 2, 44.00, 'Present'),
(26, 3, 72.00, 'Present'),
(26, 4, 44.00, 'Present'),
(26, 5, 24.00, 'Present'),
(26, 6, 54.00, 'Present'),
(26, 7, 22.00, 'Present'),
(27, 1, 40.00, 'Present'),
(27, 2, 16.00, 'Present'),
(27, 3, 60.00, 'Present'),
(27, 4, 32.00, 'Present'),
(27, 5, 30.00, 'Present'),
(27, 6, 54.00, 'Present'),
(27, 7, 10.00, 'Present'),
(28, 1, 74.00, 'Present'),
(28, 2, 60.00, 'Present'),
(28, 3, 66.00, 'Present'),
(28, 4, 60.00, 'Present'),
(28, 5, 64.00, 'Present'),
(28, 6, 62.00, 'Present'),
(28, 7, 16.00, 'Present')
ON DUPLICATE KEY UPDATE
  `marks_obtained` = VALUES(`marks_obtained`),
  `attendance_status` = VALUES(`attendance_status`);

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 4. VERIFICATION QUERIES
-- -----------------------------------------------------------------------------
-- Check total students imported (Expected: 28)
SELECT COUNT(*) AS total_students_imported FROM `students`;

-- Check total marks rows imported (Expected: 196)
SELECT COUNT(*) AS total_marks_records FROM `student_marks`;

-- Audit calculation view for all 28 students
SELECT 
    student_id, 
    register_no, 
    student_name, 
    dob, 
    total_marks_obtained, 
    total_maximum_marks, 
    calculated_percentage 
FROM `v_student_percentages`
ORDER BY student_id ASC;
