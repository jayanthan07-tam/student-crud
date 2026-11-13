-- =============================================================================
-- DATABASE SCHEMA: student_management
-- Student CRUD Application (HTML5, CSS3, JavaScript, Core PHP, MySQL)
-- Section 02 – Database Design
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `student_management`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `student_management`;

-- Drop existing tables to ensure clean initialization
DROP VIEW IF EXISTS `v_student_percentages`;
DROP TABLE IF EXISTS `student_marks`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;

-- -----------------------------------------------------------------------------
-- Table 1: students
-- Stores primary demographic, identification, and contact records
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_name` VARCHAR(100) NOT NULL,
  `register_no` VARCHAR(30) NOT NULL,
  `mobile_number` VARCHAR(20) DEFAULT NULL,
  `dob` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_students_register_no` (`register_no`),
  KEY `idx_students_name` (`student_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table 2: subjects
-- Master catalog of academic subjects and their maximum marks
-- -----------------------------------------------------------------------------
CREATE TABLE `subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_name` VARCHAR(100) NOT NULL,
  `maximum_marks` INT NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`),
  KEY `idx_subjects_name` (`subject_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table 3: student_marks
-- Junction table tracking marks obtained and attendance status per subject
-- Unique constraint enforces one mark record per student-subject pair
-- -----------------------------------------------------------------------------
CREATE TABLE `student_marks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `marks_obtained` DECIMAL(5,2) DEFAULT NULL,
  `attendance_status` ENUM('Present', 'Absent') NOT NULL DEFAULT 'Present',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_subject` (`student_id`, `subject_id`),
  KEY `idx_student_marks_student_id` (`student_id`),
  KEY `idx_student_marks_subject_id` (`subject_id`),
  KEY `idx_student_marks_attendance` (`attendance_status`),
  CONSTRAINT `fk_marks_student`
    FOREIGN KEY (`student_id`)
    REFERENCES `students` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_marks_subject`
    FOREIGN KEY (`subject_id`)
    REFERENCES `subjects` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Initial Master Subjects (All 7 Semester Subjects, 100 Max Marks Each)
-- -----------------------------------------------------------------------------
INSERT INTO `subjects` (`id`, `subject_name`, `maximum_marks`) VALUES
(1, 'Fundamentals of Management', 100),
(2, 'Computer Architecture and Organization', 100),
(3, 'Java Programming', 100),
(4, 'Human Values and Ethics', 100),
(5, 'Formal Language and Automata Theory', 100),
(6, 'Database Management Systems', 100),
(7, 'Computational Statistics', 100);

-- -----------------------------------------------------------------------------
-- Dynamic Percentage Calculation View: v_student_percentages
-- 
-- Percentage Rule:
--   Percentage = (Total Marks Obtained / Total Attended Maximum Marks) * 100
-- Specific Criteria:
--   - Excludes absent subjects from both obtained marks and maximum marks.
--   - If the student is marked 'Absent' in all subjects, displays 'Absent'.
--   - Does NOT store percentage in the students table.
--   - Treats missing marks as NULL (missing data, not 0).
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `v_student_percentages` AS
SELECT 
    s.id AS student_id,
    s.register_no,
    s.student_name,
    s.dob,
    s.mobile_number,
    COUNT(sub.id) AS total_subjects_registered,
    COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) AS present_count,
    COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) AS absent_count,
    COUNT(sm.marks_obtained) AS graded_subjects_count,
    SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) AS total_marks_obtained,
    SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END) AS total_maximum_marks,
    CASE 
        -- If student was Absent in all registered subjects, output 'Absent'
        WHEN COUNT(CASE WHEN sm.attendance_status = 'Absent' THEN 1 END) > 0
             AND COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN 1 END) = 0 THEN 'Absent'
        -- If no subjects enrolled or incomplete Present marks exist
        WHEN COUNT(sub.id) = 0
             OR COUNT(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NULL THEN 1 END) > 0
             OR COUNT(sub.id) < (SELECT COUNT(*) FROM `subjects`) THEN NULL
        -- Dynamic Percentage Formula: (Total Marks Obtained / Total Attended Maximum Marks) * 100
        ELSE ROUND(
            (SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sm.marks_obtained ELSE 0 END) / 
             NULLIF(SUM(CASE WHEN sm.attendance_status = 'Present' AND sm.marks_obtained IS NOT NULL THEN sub.maximum_marks ELSE 0 END), 0)) * 100, 2
        )
    END AS calculated_percentage
FROM `students` s
LEFT JOIN `student_marks` sm ON s.id = sm.student_id
LEFT JOIN `subjects` sub ON sm.subject_id = sub.id
GROUP BY s.id, s.register_no, s.student_name, s.dob, s.mobile_number;
