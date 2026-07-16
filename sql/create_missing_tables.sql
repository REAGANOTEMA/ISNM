-- ============================================================
-- ISNM: Missing Tables Migration
-- Tables referenced in PHP but missing from SQL dumps.
-- All tables below belong to the `igangaschool_staffs` database
-- and are required by legacy PHP CRUD files in the assets/ directory.
-- ============================================================

-- 1. admins — referenced by assets/updateProfilePic.php, assets/loadProfilePic.php, etc.
CREATE TABLE IF NOT EXISTS `admins` (
  `id` VARCHAR(50) NOT NULL,
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `fname` VARCHAR(100) DEFAULT NULL,
  `lname` VARCHAR(100) DEFAULT NULL,
  `dob` DATE DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_s_no` (`s_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. notice — referenced by assets/createNotice.php, assets/deleteNotice.php, etc.
CREATE TABLE IF NOT EXISTS `notice` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` VARCHAR(50) DEFAULT NULL,
  `editor_id` VARCHAR(50) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT DEFAULT NULL,
  `importance` VARCHAR(50) DEFAULT NULL,
  `file` VARCHAR(500) DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. notes — referenced by assets/uploadNotes.php, assets/deleteNote.php, etc.
CREATE TABLE IF NOT EXISTS `notes` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` VARCHAR(50) DEFAULT NULL,
  `editor_id` VARCHAR(50) DEFAULT NULL,
  `class` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(100) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `file` VARCHAR(500) DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_no`),
  KEY `idx_class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. syllabus — referenced by assets/uploadSllyabus.php, assets/fetchSyllabus.php, etc.
CREATE TABLE IF NOT EXISTS `syllabus` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `class` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `file` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`s_no`),
  KEY `idx_class_subject` (`class`, `subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. time_table — referenced by assets/updateTimeTable.php, assets/fetchTimeTable.php
CREATE TABLE IF NOT EXISTS `time_table` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `class` VARCHAR(50) DEFAULT NULL,
  `section` VARCHAR(50) DEFAULT NULL,
  `start_time` VARCHAR(20) DEFAULT NULL,
  `end_time` VARCHAR(20) DEFAULT NULL,
  `mon` VARCHAR(100) DEFAULT NULL,
  `tue` VARCHAR(100) DEFAULT NULL,
  `wed` VARCHAR(100) DEFAULT NULL,
  `thu` VARCHAR(100) DEFAULT NULL,
  `fri` VARCHAR(100) DEFAULT NULL,
  `sat` VARCHAR(100) DEFAULT NULL,
  `editor_id` VARCHAR(50) DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_no`),
  KEY `idx_class_section` (`class`, `section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. feedback — referenced by assets/sendFeedback.php, assets/getStudentDetailsAndFeedback.php, etc.
CREATE TABLE IF NOT EXISTS `feedback` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` VARCHAR(50) DEFAULT NULL,
  `receiver_id` VARCHAR(50) DEFAULT NULL,
  `msg` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_no`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_receiver` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. reminders — referenced by assets/addReminder.php, assets/deleteReminder.php, etc.
CREATE TABLE IF NOT EXISTS `reminders` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `id` VARCHAR(50) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_no`),
  KEY `idx_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. buses — referenced by assets/saveBus.php, assets/deleteBus.php, etc.
CREATE TABLE IF NOT EXISTS `buses` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `bus_id` VARCHAR(50) NOT NULL,
  `bus_title` VARCHAR(100) DEFAULT NULL,
  `bus_number` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`s_no`),
  KEY `idx_bus_id` (`bus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. bus_root — referenced by assets/saveAllBusStops.php, assets/deleteBusStop.php, etc.
CREATE TABLE IF NOT EXISTS `bus_root` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `bus_id` VARCHAR(50) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `arrival_time` VARCHAR(20) DEFAULT NULL,
  `serial` INT(11) DEFAULT NULL,
  PRIMARY KEY (`s_no`),
  KEY `idx_bus_id` (`bus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. bus_staff — referenced by assets/saveBus.php, assets/deleteBus.php, etc.
CREATE TABLE IF NOT EXISTS `bus_staff` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `id` VARCHAR(50) NOT NULL,
  `bus_id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) DEFAULT NULL,
  `contact` VARCHAR(30) DEFAULT NULL,
  `role` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`s_no`),
  KEY `idx_bus_id` (`bus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. marks — referenced by assets/uploadMarks.php, assets/fetchSubjectiveResults.php, etc.
CREATE TABLE IF NOT EXISTS `marks` (
  `s_no` INT(11) NOT NULL AUTO_INCREMENT,
  `exam_id` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(100) DEFAULT NULL,
  `student_id` VARCHAR(50) DEFAULT NULL,
  `marks` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`s_no`),
  KEY `idx_exam_id` (`exam_id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
