-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 06:26 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschoolofl_staffs_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `academic_generate_enrollment_report` (IN `p_academic_year` VARCHAR(20), IN `p_program_code` VARCHAR(20))   BEGIN
    SELECT 
        program,
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
        COUNT(CASE WHEN status = 'Suspended' THEN 1 END) as suspended_students
    FROM universal_student_profiles
    WHERE (p_academic_year IS NULL OR intake_set LIKE CONCAT('%', p_academic_year, '%'))
      AND (p_program_code IS NULL OR program = p_program_code)
    GROUP BY program;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `academic_update_program_coordinator` (IN `p_program_code` VARCHAR(20), IN `p_coordinator_id` INT)   BEGIN
    UPDATE academic_programs 
    SET program_coordinator = p_coordinator_id,
        updated_at = NOW()
    WHERE program_code = p_program_code;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `add_new_student` (IN `p_student_number` VARCHAR(50), IN `p_registration_number` VARCHAR(50), IN `p_index_number` VARCHAR(50), IN `p_national_id` VARCHAR(50), IN `p_first_name` VARCHAR(100), IN `p_surname` VARCHAR(100), IN `p_other_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_phone` VARCHAR(20), IN `p_program` VARCHAR(100), IN `p_year` INT, IN `p_set_name` VARCHAR(50), IN `p_intake_date` DATE, IN `p_date_of_birth` DATE, IN `p_gender` ENUM('Male','Female','Other'), IN `p_nationality` VARCHAR(100), IN `p_address` TEXT, IN `p_guardian_name` VARCHAR(200), IN `p_guardian_phone` VARCHAR(20), IN `p_emergency_contact_name` VARCHAR(100), IN `p_emergency_contact_phone` VARCHAR(20), IN `p_status` ENUM('Active','Inactive','Graduated','Suspended','Withdrawn'), IN `p_added_by` INT)   BEGIN
    DECLARE v_student_id INT;
    DECLARE v_password_hash VARCHAR(255);
    
    -- Default password: 12345678 (student must change on first login)
    SET v_password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP';
    
    -- Insert student record
    INSERT INTO igangaschoolofl_students_db.students (
        student_number, registration_number, index_number, national_student_id_number,
        first_name, surname, other_name, email, phone,
        program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, password, is_first_login, password_changed
    ) VALUES (
        p_student_number, p_registration_number, p_index_number, p_national_id,
        p_first_name, p_surname, p_other_name, p_email, p_phone,
        p_program, p_year, p_set_name, p_intake_date,
        p_date_of_birth, p_gender, p_nationality, p_address,
        p_guardian_name, p_guardian_phone,
        p_emergency_contact_name, p_emergency_contact_phone,
        p_status, v_password_hash, TRUE, FALSE
    );
    
    SET v_student_id = LAST_INSERT_ID();
    
    -- Create student profile record
    INSERT INTO igangaschoolofl_students_db.student_profiles (student_id)
    VALUES (v_student_id);
    
    -- Create default fee records
    INSERT INTO igangaschoolofl_students_db.student_fees (
        student_id, fee_type, amount, due_date, status
    ) VALUES
        (v_student_id, 'Tuition Fee', 500000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Facility Fee', 50000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Registration Fee', 20000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid');
    
    -- Log the action
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id, ip_address)
    VALUES (p_added_by, 'Student Added', CONCAT('Added student: ', p_first_name, ' ', p_surname), 'Student Management', v_student_id, '0.0.0.0');
    
    SELECT v_student_id as student_id, 'Student added successfully' as message, TRUE as success;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_transcript_request` (IN `p_student_id` INT, IN `p_requested_by` INT, IN `p_purpose` TEXT, IN `p_copies` INT)   BEGIN
    DECLARE v_transcript_number VARCHAR(50);
    SET v_transcript_number = CONCAT('TRN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(LAST_INSERT_ID() + 1, 4, '0'));
    
    INSERT INTO registrar_transcripts (
        transcript_number, student_id, requested_by, purpose, copies_requested
    ) VALUES (
        v_transcript_number, p_student_id, p_requested_by, p_purpose, p_copies
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_students_list` (IN `p_program` VARCHAR(100), IN `p_set_name` VARCHAR(50), IN `p_status` VARCHAR(50), IN `p_limit` INT)   BEGIN
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 1000;
    END IF;
    
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        (p_program IS NULL OR program = p_program)
        AND (p_set_name IS NULL OR set_name = p_set_name)
        AND (p_status IS NULL OR status = p_status)
    ORDER BY created_at DESC
    LIMIT p_limit;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_clinical_logbook` (IN `p_student_id` INT)   BEGIN
    SELECT 
        cl.log_date,
        cl.shift,
        cl.patient_name,
        cl.diagnosis,
        cl.interventions,
        cl.outcomes,
        cl.supervisor_initials
    FROM nursing_clinical_logbook cl
    WHERE cl.student_id = p_student_id
    ORDER BY cl.log_date DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_graduation_eligible_students` ()   BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        sp.year_of_study,
        ra.gpa,
        ra.cgpa,
        ra.academic_standing
    FROM universal_student_profiles sp
    JOIN registrar_academic_records ra ON sp.id = ra.student_id
    WHERE sp.year_of_study >= 2 
      AND ra.cgpa >= 2.00
      AND sp.status = 'Active';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_midwifery_students_by_intake` (IN `p_intake_set` VARCHAR(20))   BEGIN
    SELECT 
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.year_of_study,
        ms.semester,
        ms.status,
        ms.photo_path
    FROM midwifery_students ms
    WHERE ms.intake_set = p_intake_set
    ORDER BY ms.student_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_midwifery_students_search` (IN `p_search_term` VARCHAR(255))   BEGIN
    SELECT 
        ms.id,
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.intake_set,
        ms.status,
        ms.photo_path,
        COALESCE(ms.photo_uploaded, FALSE) as has_photo
    FROM midwifery_students ms
    WHERE ms.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ms.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ms.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_nursing_students_by_intake` (IN `p_intake_set` VARCHAR(20))   BEGIN
    SELECT 
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.year_of_study,
        ns.semester,
        ns.status,
        ns.photo_path
    FROM nursing_students ns
    WHERE ns.intake_set = p_intake_set
    ORDER BY ns.student_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_nursing_students_search` (IN `p_search_term` VARCHAR(255))   BEGIN
    SELECT 
        ns.id,
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.intake_set,
        ns.status,
        ns.photo_path,
        COALESCE(ns.photo_uploaded, FALSE) as has_photo
    FROM nursing_students ns
    WHERE ns.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ns.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ns.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_student_by_number` (IN `p_student_number` VARCHAR(50))   BEGIN
    SELECT 
        id, student_number, registration_number, national_student_id_number,
        first_name, surname, other_name,
        CONCAT(first_name, ' ', surname, CASE WHEN other_name IS NOT NULL THEN CONCAT(' ', other_name) ELSE '' END) as full_name,
        email, phone, program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, created_at, updated_at
    FROM igangaschoolofl_students_db.students
    WHERE student_number = p_student_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_student_registration_status` (IN `p_student_id` INT)   BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        rr.registration_number,
        rr.registration_status,
        rr.registration_date,
        rr.academic_year,
        rr.semester
    FROM universal_student_profiles sp
    LEFT JOIN registrar_student_registration rr ON sp.id = rr.student_id
    WHERE sp.id = p_student_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `hr_get_staff_profile` (IN `p_staff_id` INT)   BEGIN
    SELECT 
        s.*,
        sp.bio,
        sp.profile_picture,
        sp.qualifications,
        sp.experience,
        sp.skills,
        sp.education_background,
        sp.certifications,
        sd.document_type,
        sd.document_title,
        sd.file_path,
        sd.upload_date
    FROM staff s
    LEFT JOIN staff_profiles sp ON s.id = sp.staff_id
    LEFT JOIN staff_documents sd ON s.id = sd.staff_id
    WHERE s.id = p_staff_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `hr_search_staff` (IN `p_name` VARCHAR(255), IN `p_department` VARCHAR(100), IN `p_position` VARCHAR(100), IN `p_status` VARCHAR(50))   BEGIN
    SELECT 
        s.id,
        s.staff_id,
        s.full_name,
        s.email,
        s.phone,
        s.position,
        s.department,
        sr.role_name,
        s.status,
        s.hire_date
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE (p_name IS NULL OR s.full_name LIKE CONCAT('%', p_name, '%'))
      AND (p_department IS NULL OR s.department = p_department)
      AND (p_position IS NULL OR s.position LIKE CONCAT('%', p_position, '%'))
      AND (p_status IS NULL OR s.status = p_status)
    ORDER BY s.full_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `hr_update_profile_picture` (IN `p_staff_id` INT, IN `p_photo_path` VARCHAR(500), IN `p_updated_by` INT)   BEGIN
    INSERT INTO staff_profiles (staff_id, profile_picture) 
    VALUES (p_staff_id, p_photo_path)
    ON DUPLICATE KEY UPDATE 
        profile_picture = p_photo_path;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `lab_record_maintenance` (IN `p_equipment_id` INT, IN `p_status` VARCHAR(20), IN `p_notes` TEXT)   BEGIN
    UPDATE lab_equipment 
    SET status = p_status,
        service_notes = CONCAT(COALESCE(service_notes, ''), '\n', p_notes),
        updated_at = NOW()
    WHERE id = p_equipment_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `lab_schedule_session` (IN `p_title` VARCHAR(255), IN `p_skill_name` VARCHAR(255), IN `p_session_date` DATE, IN `p_instructor_id` INT)   BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('LSS', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO lab_skills_sessions (
        session_id, session_title, skill_name, session_date, instructor_id
    ) VALUES (
        v_session_id, p_title, p_skill_name, p_session_date, p_instructor_id
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `lab_update_inventory` (IN `p_item_id` INT, IN `p_new_quantity` DECIMAL(15,2))   BEGIN
    UPDATE lab_inventory 
    SET quantity_on_hand = p_new_quantity,
        status = CASE 
            WHEN p_new_quantity <= 0 THEN 'Out of Stock'
            WHEN p_new_quantity <= reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END,
        updated_at = NOW()
    WHERE id = p_item_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `library_borrow_book` (IN `p_book_id` INT, IN `p_member_id` INT, IN `p_processed_by` INT)   BEGIN
    DECLARE v_transaction_id VARCHAR(50);
    DECLARE v_due_date DATE;
    DECLARE v_current_copies INT;
    DECLARE v_available_copies INT;
    
    SET v_transaction_id = CONCAT('BRW', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    SET v_due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY);
    
    -- Check available copies
    SELECT available_copies INTO v_available_copies 
    FROM library_books WHERE id = p_book_id;
    
    IF v_available_copies > 0 THEN
        INSERT INTO library_borrowing (
            transaction_id, book_id, borrower_id, borrower_name, 
            borrow_date, due_date, processed_by
        ) VALUES (
            v_transaction_id, p_book_id, p_member_id, 
            (SELECT full_name FROM library_members WHERE id = p_member_id),
            CURDATE(), v_due_date, p_processed_by
        );
        
        UPDATE library_books 
        SET available_copies = available_copies - 1
        WHERE id = p_book_id;
        
        UPDATE library_members 
        SET current_books_borrowed = current_books_borrowed + 1
        WHERE id = p_member_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `library_return_book` (IN `p_transaction_id` INT, IN `p_processed_by` INT)   BEGIN
    UPDATE library_borrowing 
    SET return_date = CURDATE(),
        return_status = 'Returned'
    WHERE id = p_transaction_id;
    
    UPDATE library_books lb
    JOIN library_borrowing lbw ON lb.id = lbw.book_id
    SET lb.available_copies = lb.available_copies + 1
    WHERE lbw.id = p_transaction_id;
    
    UPDATE library_members lm
    JOIN library_borrowing lbw ON lm.id = lbw.borrower_id
    SET lm.current_books_borrowed = lm.current_books_borrowed - 1
    WHERE lbw.id = p_transaction_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `library_search_books` (IN `p_title` VARCHAR(255), IN `p_author` VARCHAR(255), IN `p_category` VARCHAR(100), IN `p_status` VARCHAR(50))   BEGIN
    SELECT 
        lb.book_id,
        lb.title,
        lb.author,
        lb.publisher,
        lb.publication_year,
        lb.category,
        lb.total_copies,
        lb.available_copies,
        lb.shelf_location,
        lb.status
    FROM library_books lb
    WHERE (p_title IS NULL OR lb.title LIKE CONCAT('%', p_title, '%'))
      AND (p_author IS NULL OR lb.author LIKE CONCAT('%', p_author, '%'))
      AND (p_category IS NULL OR lb.category = p_category)
      AND (p_status IS NULL OR lb.status = p_status)
    ORDER BY lb.title;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `record_antenatal_visit` (IN `p_student_id` INT, IN `p_patient_name` VARCHAR(255), IN `p_visit_date` DATE, IN `p_blood_pressure` VARCHAR(20), IN `p_fhr` INT)   BEGIN
    DECLARE v_record_id VARCHAR(50);
    SET v_record_id = CONCAT('AN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO midwifery_antenatal_care (
        record_id, student_id, patient_name, antenatal_visit_date, blood_pressure, fetal_heart_rate
    ) VALUES (
        v_record_id, p_student_id, p_patient_name, p_visit_date, p_blood_pressure, p_fhr
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `record_clinical_placement` (IN `p_student_id` INT, IN `p_site` VARCHAR(255), IN `p_start_date` DATE, IN `p_end_date` DATE, IN `p_supervisor` VARCHAR(255))   BEGIN
    DECLARE v_placement_number VARCHAR(50);
    SET v_placement_number = CONCAT('CLIN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO nursing_clinical_placements (
        placement_number, student_id, placement_site, start_date, end_date, supervisor_name
    ) VALUES (
        v_placement_number, p_student_id, p_site, p_start_date, p_end_date, p_supervisor
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `search_students` (IN `p_search_term` VARCHAR(100))   BEGIN
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        student_number LIKE CONCAT('%', p_search_term, '%')
        OR registration_number LIKE CONCAT('%', p_search_term, '%')
        OR index_number LIKE CONCAT('%', p_search_term, '%')
        OR full_name LIKE CONCAT('%', p_search_term, '%')
        OR email LIKE CONCAT('%', p_search_term, '%')
        OR phone LIKE CONCAT('%', p_search_term, '%')
    ORDER BY created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `security_report_incident` (IN `p_incident_type` VARCHAR(50), IN `p_location` VARCHAR(255), IN `p_description` TEXT, IN `p_reported_by` INT, IN `p_severity` VARCHAR(20))   BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('INC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO security_incidents (
        incident_id, incident_type, location, description, reported_by, severity
    ) VALUES (
        v_incident_id, p_incident_type, p_location, p_description, p_reported_by, p_severity
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `security_visitor_checkin` (IN `p_visitor_id` INT, IN `p_checked_by` INT)   BEGIN
    UPDATE security_visitors 
    SET actual_arrival = NOW(),
        status = 'Checked In',
        check_in_by = p_checked_by
    WHERE id = p_visitor_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `security_visitor_checkout` (IN `p_visitor_id` INT, IN `p_checked_by` INT)   BEGIN
    UPDATE security_visitors 
    SET actual_departure = NOW(),
        status = 'Checked Out',
        check_out_by = p_checked_by
    WHERE id = p_visitor_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_student_record` (IN `p_student_id` INT, IN `p_field` VARCHAR(100), IN `p_value` TEXT, IN `p_updated_by` INT)   BEGIN
    DECLARE v_old_value TEXT;
    
    -- Get old value for logging
    CASE p_field
        WHEN 'email' THEN
            SELECT email INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET email = p_value WHERE id = p_student_id;
        WHEN 'phone' THEN
            SELECT phone INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET phone = p_value WHERE id = p_student_id;
        WHEN 'program' THEN
            SELECT program INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET program = p_value WHERE id = p_student_id;
        WHEN 'status' THEN
            SELECT status INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET status = p_value WHERE id = p_student_id;
    END CASE;
    
    -- Log the update
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id)
    VALUES (p_updated_by, 'Student Updated', CONCAT('Updated ', p_field, ' from ', v_old_value, ' to ', p_value), 'Student Management', p_student_id);
    
    SELECT 'Student record updated successfully' as message, TRUE as success;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `welfare_record_case` (IN `p_student_id` INT, IN `p_case_type` VARCHAR(50), IN `p_description` TEXT, IN `p_priority` VARCHAR(20), IN `p_reported_by` INT)   BEGIN
    DECLARE v_case_id VARCHAR(50);
    SET v_case_id = CONCAT('WEL', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_welfare_cases (
        case_id, student_id, case_type, case_description, case_priority, reported_by
    ) VALUES (
        v_case_id, p_student_id, p_case_type, p_description, p_priority, p_reported_by
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `welfare_record_health_incident` (IN `p_student_id` INT, IN `p_incident_type` VARCHAR(50), IN `p_description` TEXT, IN `p_severity` VARCHAR(20), IN `p_reported_by` INT)   BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('HLTH', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_health_incidents (
        incident_id, student_id, incident_type, description, severity, reported_by
    ) VALUES (
        v_incident_id, p_student_id, p_incident_type, p_description, p_severity, p_reported_by
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `welfare_schedule_counseling` (IN `p_student_id` INT, IN `p_counselor_id` INT, IN `p_session_date` DATE, IN `p_session_type` VARCHAR(50))   BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('COUN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_counseling_sessions (
        session_id, student_id, counselor_id, session_date, session_type
    ) VALUES (
        v_session_id, p_student_id, p_counselor_id, p_session_date, p_session_type
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_analytics`
--

CREATE TABLE `academic_analytics` (
  `id` int NOT NULL,
  `analytics_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_enrolled` int DEFAULT NULL,
  `total_graduated` int DEFAULT NULL,
  `total_dropped` int DEFAULT NULL,
  `average_gpa` decimal(3,2) DEFAULT NULL,
  `pass_rate` decimal(5,2) DEFAULT NULL,
  `withdrawal_rate` decimal(5,2) DEFAULT NULL,
  `employment_rate` decimal(5,2) DEFAULT NULL,
  `analysis_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_course_catalog`
--

CREATE TABLE `academic_course_catalog` (
  `id` int NOT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credits` int NOT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theory_hours` int DEFAULT NULL,
  `practical_hours` int DEFAULT NULL,
  `tutorials_hours` int DEFAULT NULL,
  `assessment_method` text COLLATE utf8mb4_unicode_ci,
  `course_coordinator` int DEFAULT NULL,
  `prerequisites` text COLLATE utf8mb4_unicode_ci,
  `learning_outcomes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive','Under Review') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_curriculum_development`
--

CREATE TABLE `academic_curriculum_development` (
  `id` int NOT NULL,
  `curriculum_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revision_number` int DEFAULT '1',
  `changes_made` text COLLATE utf8mb4_unicode_ci,
  `reason_for_changes` text COLLATE utf8mb4_unicode_ci,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('Draft','Under Review','Approved','Implemented') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

CREATE TABLE `academic_programs` (
  `id` int NOT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_years` int DEFAULT '2',
  `total_credits` int DEFAULT NULL,
  `program_coordinator` int DEFAULT NULL,
  `accreditation_status` enum('Accredited','Provisional','Expired','Pending') COLLATE utf8mb4_unicode_ci DEFAULT 'Accredited',
  `accreditation_body` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accreditation_expiry` date DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_reports`
--

CREATE TABLE `academic_reports` (
  `id` int NOT NULL,
  `report_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` enum('Enrollment','Graduation','Performance','Employment','Accreditation','Compliance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pdf_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Draft','Final','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_timetable`
--

CREATE TABLE `academic_timetable` (
  `id` int NOT NULL,
  `timetable_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer_id` int DEFAULT NULL,
  `timetable_status` enum('Draft','Approved','Published','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int NOT NULL,
  `asset_tag` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `purchase_date` date DEFAULT NULL,
  `purchase_value` decimal(15,2) NOT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciation_method` enum('straight_line','reducing_balance','none') COLLATE utf8mb4_unicode_ci DEFAULT 'straight_line',
  `useful_life_years` int DEFAULT '5',
  `salvage_value` decimal(15,2) DEFAULT '0.00',
  `accumulated_depreciation` decimal(15,2) DEFAULT '0.00',
  `net_book_value` decimal(15,2) NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custodian` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','disposed','written_off') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_depreciation`
--

CREATE TABLE `asset_depreciation` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `depreciation_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `depreciation_method` enum('straight_line','reducing_balance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliations`
--

CREATE TABLE `bank_reconciliations` (
  `id` int NOT NULL,
  `bank_account_id` int NOT NULL,
  `statement_date` date NOT NULL,
  `statement_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) NOT NULL,
  `status` enum('draft','completed','adjusted') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `reconciled_by` int DEFAULT NULL,
  `reconciliation_date` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int NOT NULL,
  `budget_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_type` enum('annual','termly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_budget_amount` decimal(15,2) NOT NULL,
  `allocated_amount` decimal(15,2) DEFAULT '0.00',
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','active','closed','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashbook`
--

CREATE TABLE `cashbook` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL,
  `transaction_type` enum('cash_in','cash_out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_account_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_log`
--

CREATE TABLE `communication_log` (
  `id` int NOT NULL,
  `type` enum('sms','email','overdue_notice','payment_confirmation','announcement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_type` enum('student','staff','group') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `recipient_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','sent','failed','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `sent_date` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departmental_budgets`
--

CREATE TABLE `departmental_budgets` (
  `id` int NOT NULL,
  `budget_id` int NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('active','exhausted','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `expense_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','mobile_money') COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_id` int DEFAULT NULL,
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_approvals`
--

CREATE TABLE `expense_approvals` (
  `id` int NOT NULL,
  `expense_id` int NOT NULL,
  `approver_id` int NOT NULL,
  `approval_level` int NOT NULL,
  `status` enum('approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `approval_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `fee_payments`
-- (See below for the actual view)
--
CREATE TABLE `fee_payments` (
`amount_paid` decimal(12,2)
,`created_at` timestamp
,`fee_account_id` int
,`id` int
,`notes` text
,`payment_date` date
,`payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other')
,`processed_by` int
,`receipt_number` varchar(50)
,`status` enum('Pending','Completed','Failed','Reversed')
,`student_id` int
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `reminder_type` enum('gentle','firm','final','overdue') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `method` enum('sms','email','both') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `reminder_number` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_log`
--

CREATE TABLE `financial_audit_log` (
  `id` int NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `user_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_leave_summary`
-- (See below for the actual view)
--
CREATE TABLE `hr_leave_summary` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_performance_summary`
-- (See below for the actual view)
--
CREATE TABLE `hr_performance_summary` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_staff_by_department`
-- (See below for the actual view)
--
CREATE TABLE `hr_staff_by_department` (
`active_staff` decimal(23,0)
,`avg_years_of_service` decimal(16,8)
,`department` varchar(100)
,`inactive_staff` decimal(23,0)
,`total_staff` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_staff_search_view`
-- (See below for the actual view)
--
CREATE TABLE `hr_staff_search_view` (
`account_status` varchar(7)
,`department` varchar(100)
,`email` varchar(100)
,`full_name` varchar(100)
,`hire_date` date
,`id` int
,`last_login` timestamp
,`phone` varchar(20)
,`position` varchar(100)
,`role_name` varchar(100)
,`staff_id` varchar(50)
,`status` enum('Active','Inactive','On Leave','Suspended')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_users`
-- (See below for the actual view)
--
CREATE TABLE `hr_users` (
`created_at` timestamp
,`department` varchar(100)
,`email` varchar(100)
,`full_name` varchar(100)
,`id` int
,`password_hash` varchar(255)
,`phone` varchar(20)
,`position` varchar(100)
,`status` enum('Active','Inactive','On Leave','Suspended')
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int NOT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_value` decimal(15,2) NOT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','issued','damaged','disposed') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_chemical_inventory`
--

CREATE TABLE `lab_chemical_inventory` (
  `id` int NOT NULL,
  `chemical_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemical_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cas_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chemical_formula` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hazard_classification` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_requirements` text COLLATE utf8mb4_unicode_ci,
  `quantity_on_hand` decimal(15,2) DEFAULT NULL,
  `unit_of_measure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `storage_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `msds_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired') COLLATE utf8mb4_unicode_ci DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

CREATE TABLE `lab_equipment` (
  `id` int NOT NULL,
  `equipment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_type` enum('Microscope','Centrifuge','Autoclave','Spectrophotometer','PCR','Incubator','Refrigerator','Freezer','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `calibration_date` date DEFAULT NULL,
  `next_calibration_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Operational','Maintenance','Repair','Retired') COLLATE utf8mb4_unicode_ci DEFAULT 'Operational',
  `last_serviced_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_experiments`
--

CREATE TABLE `lab_experiments` (
  `id` int NOT NULL,
  `experiment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `experiment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `experiment_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `students_enrolled` int DEFAULT NULL,
  `students_completed` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `sickbay_staff_id` int DEFAULT NULL,
  `equipment_used` text COLLATE utf8mb4_unicode_ci,
  `reagents_used` text COLLATE utf8mb4_unicode_ci,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `results` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_inventory`
--

CREATE TABLE `lab_inventory` (
  `id` int NOT NULL,
  `item_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_category` enum('Reagent','Chemical','Consumable','Glassware','Plasticware','Media','Antibody','Enzyme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalog_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_of_measure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity_on_hand` decimal(15,2) DEFAULT '0.00',
  `reorder_level` decimal(15,2) DEFAULT '0.00',
  `storage_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Quarantine') COLLATE utf8mb4_unicode_ci DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_skills_sessions`
--

CREATE TABLE `lab_skills_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `skill_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `target_department` enum('Nursing','Midwifery','Both') COLLATE utf8mb4_unicode_ci DEFAULT 'Both',
  `year_of_study` int DEFAULT NULL,
  `students_expected` int DEFAULT NULL,
  `students_attended` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `instructor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_used` text COLLATE utf8mb4_unicode_ci,
  `materials_used` text COLLATE utf8mb4_unicode_ci,
  `pre_test_score` decimal(5,2) DEFAULT NULL,
  `post_test_score` decimal(5,2) DEFAULT NULL,
  `session_status` enum('Scheduled','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `evaluation_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int NOT NULL,
  `book_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edition` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `publication_place` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subcategory` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `call_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_status` enum('New','Good','Fair','Poor','Damaged') COLLATE utf8mb4_unicode_ci DEFAULT 'Good',
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'UGX',
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'English',
  `pages` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_copy_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Available','Borrowed','Reserved','Lost','On Order','Archiv') COLLATE utf8mb4_unicode_ci DEFAULT 'Available',
  `added_by` int DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_borrowing`
--

CREATE TABLE `library_borrowing` (
  `id` int NOT NULL,
  `transaction_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book_id` int NOT NULL,
  `borrower_type` enum('Student','Staff','External') COLLATE utf8mb4_unicode_ci NOT NULL,
  `borrower_id` int DEFAULT NULL,
  `borrower_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `return_status` enum('Borrowed','Returned','Overdue','Lost') COLLATE utf8mb4_unicode_ci DEFAULT 'Borrowed',
  `late_fee` decimal(10,2) DEFAULT '0.00',
  `fine_paid` tinyint(1) DEFAULT '0',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_digital_resources`
--

CREATE TABLE `library_digital_resources` (
  `id` int NOT NULL,
  `resource_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_type` enum('Ebook','Journal','Video','Audio','Database','Article') COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_creator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `access_level` enum('Public','Members Only','Restricted') COLLATE utf8mb4_unicode_ci DEFAULT 'Members Only',
  `description` text COLLATE utf8mb4_unicode_ci,
  `subject_keywords` text COLLATE utf8mb4_unicode_ci,
  `added_by` int DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

CREATE TABLE `library_fines` (
  `id` int NOT NULL,
  `fine_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_id` int DEFAULT NULL,
  `member_id` int NOT NULL,
  `fine_type` enum('Overdue','Damage','Lost','Reservation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'UGX',
  `description` text COLLATE utf8mb4_unicode_ci,
  `waived` tinyint(1) DEFAULT '0',
  `waived_by` int DEFAULT NULL,
  `waived_date` timestamp NULL DEFAULT NULL,
  `paid` tinyint(1) DEFAULT '0',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_members`
--

CREATE TABLE `library_members` (
  `id` int NOT NULL,
  `member_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_type` enum('Student','Staff','External') COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `membership_expiry` date DEFAULT NULL,
  `max_books_allowed` int DEFAULT '3',
  `current_books_borrowed` int DEFAULT '0',
  `status` enum('Active','Inactive','Suspended','Expired') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_antenatal_care`
--

CREATE TABLE `midwifery_antenatal_care` (
  `id` int NOT NULL,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_age` int DEFAULT NULL,
  `gravida` int DEFAULT NULL,
  `para` int DEFAULT NULL,
  `antenatal_visit_date` date NOT NULL,
  `gestational_age_weeks` int DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `fetal_heart_rate` int DEFAULT NULL,
  `fundal_height_cm` int DEFAULT NULL,
  `presentation` enum('Cephalic','Breech','Transverse') COLLATE utf8mb4_unicode_ci DEFAULT 'Cephalic',
  `pallor` tinyint(1) DEFAULT '0',
  `edema` tinyint(1) DEFAULT '0',
  `proteinuria` tinyint(1) DEFAULT '0',
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `management_plan` text COLLATE utf8mb4_unicode_ci,
  `medication_given` text COLLATE utf8mb4_unicode_ci,
  `next_visit_date` date DEFAULT NULL,
  `supervised_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_family_planning`
--

CREATE TABLE `midwifery_family_planning` (
  `id` int NOT NULL,
  `fp_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_age` int DEFAULT NULL,
  `parity` int DEFAULT NULL,
  `method_selected` enum('Pill','Injection','Implant','IUD','Sterilization','Natural','None') COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_method` enum('Pill','Injection','Implant','IUD','Sterilization','Natural','None') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counseling_done` tinyint(1) DEFAULT '1',
  `complications_history` text COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `supervised_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_labor_delivery`
--

CREATE TABLE `midwifery_labor_delivery` (
  `id` int NOT NULL,
  `delivery_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_age` int DEFAULT NULL,
  `gravida` int DEFAULT NULL,
  `para` int DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time DEFAULT NULL,
  `delivery_type` enum('Spontaneous Vaginal','Assisted','Elective C/S','Emergency C/S','Vacuum','Forceps') COLLATE utf8mb4_unicode_ci DEFAULT 'Spontaneous Vaginal',
  `labor_duration_hours` decimal(5,2) DEFAULT NULL,
  `rupture_of_membranes` tinyint(1) DEFAULT '0',
  `rupture_time` time DEFAULT NULL,
  `oxytocin_used` tinyint(1) DEFAULT '0',
  `episiotomy` tinyint(1) DEFAULT '0',
  `perineal_tear` enum('None','1st Degree','2nd Degree','3rd Degree','4th Degree') COLLATE utf8mb4_unicode_ci DEFAULT 'None',
  `placenta_complete` tinyint(1) DEFAULT '1',
  `blood_loss_ml` int DEFAULT NULL,
  `newborn_gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `newborn_weight_gm` int DEFAULT NULL,
  `newborn_apgar_score` int DEFAULT NULL,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `interventions` text COLLATE utf8mb4_unicode_ci,
  `medications_administered` text COLLATE utf8mb4_unicode_ci,
  `outcome` enum('Live Birth','Still Birth','Maternal Death') COLLATE utf8mb4_unicode_ci DEFAULT 'Live Birth',
  `supervised_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_postnatal_care`
--

CREATE TABLE `midwifery_postnatal_care` (
  `id` int NOT NULL,
  `postnatal_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_number` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `days_post_delivery` int DEFAULT NULL,
  `maternal_condition` text COLLATE utf8mb4_unicode_ci,
  `uterus_involution` tinyint(1) DEFAULT '1',
  `lochia_type` enum('Rubra','Serosa','Alba') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lochia_amount` enum('Scanty','Moderate','Heavy') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perineal_wound_healing` tinyint(1) DEFAULT '1',
  `breastfeeding_status` enum('Exclusive','Partial','None') COLLATE utf8mb4_unicode_ci DEFAULT 'Exclusive',
  `newborn_condition` text COLLATE utf8mb4_unicode_ci,
  `newborn_weight` decimal(5,2) DEFAULT NULL,
  `newborn_feeding_frequency` int DEFAULT NULL,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `advice_given` text COLLATE utf8mb4_unicode_ci,
  `next_visit_date` date DEFAULT NULL,
  `supervised_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_students`
--

CREATE TABLE `midwifery_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `index_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program` enum('Certificate in Midwifery','Diploma in Midwifery','Upgrading Midwifery') COLLATE utf8mb4_unicode_ci DEFAULT 'Diploma in Midwifery',
  `intake_set` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `nationality` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Ugandan',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_children` int DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `photo_upload_date` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Semester 1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_logbook`
--

CREATE TABLE `nursing_clinical_logbook` (
  `id` int NOT NULL,
  `logbook_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `placement_id` int DEFAULT NULL,
  `log_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Night') COLLATE utf8mb4_unicode_ci DEFAULT 'Morning',
  `patient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patient_age` int DEFAULT NULL,
  `patient_gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `procedure_performed` text COLLATE utf8mb4_unicode_ci,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `interventions` text COLLATE utf8mb4_unicode_ci,
  `outcomes` text COLLATE utf8mb4_unicode_ci,
  `supervisor_initials` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logged_by` int DEFAULT NULL,
  `log_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_placements`
--

CREATE TABLE `nursing_clinical_placements` (
  `id` int NOT NULL,
  `placement_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `placement_site` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placement_department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration_days` int DEFAULT NULL,
  `supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objectives` text COLLATE utf8mb4_unicode_ci,
  `learning_outcomes` text COLLATE utf8mb4_unicode_ci,
  `assessment_marks` decimal(5,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `report_submitted` tinyint(1) DEFAULT '0',
  `report_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graded_by` int DEFAULT NULL,
  `graded_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_practical_assessment`
--

CREATE TABLE `nursing_practical_assessment` (
  `id` int NOT NULL,
  `assessment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `assessment_type` enum('OSCE','VIVA','Practical','Clinical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assessment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_conducted` date DEFAULT NULL,
  `max_marks` decimal(5,2) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assessor_id` int DEFAULT NULL,
  `assessor_comments` text COLLATE utf8mb4_unicode_ci,
  `student_comments` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','Conducted','Graded','Reviewed') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_skills_training`
--

CREATE TABLE `nursing_skills_training` (
  `id` int NOT NULL,
  `training_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `skill_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `skill_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `training_date` date NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `competence_level` enum('Beginner','Developing','Competent','Proficient','Expert') COLLATE utf8mb4_unicode_ci DEFAULT 'Beginner',
  `assessment_score` decimal(5,2) DEFAULT NULL,
  `certification_issued` tinyint(1) DEFAULT '0',
  `certificate_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_students`
--

CREATE TABLE `nursing_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `index_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program` enum('Diploma in Nursing','BSc Nursing','Upgrading Nursing') COLLATE utf8mb4_unicode_ci DEFAULT 'Diploma in Nursing',
  `intake_set` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `nationality` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Ugandan',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_county` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `photo_upload_date` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Semester 1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `payment_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_deposit','mobile_money','cheque','card') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_provider` enum('mtn_momo','airtel_money','stanbic_bank','equity_bank','centenary_bank','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_date` date NOT NULL,
  `processed_by` int DEFAULT NULL,
  `status` enum('pending','verified','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `receipt_generated` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int NOT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `receipt_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` int DEFAULT NULL,
  `receipt_data` longtext COLLATE utf8mb4_unicode_ci,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('generated','printed','emailed') COLLATE utf8mb4_unicode_ci DEFAULT 'generated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int NOT NULL,
  `payslip_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_id` int NOT NULL,
  `payment_month` date NOT NULL,
  `base_salary` decimal(15,2) NOT NULL,
  `allowances` json DEFAULT NULL,
  `deductions` json DEFAULT NULL,
  `gross_pay` decimal(15,2) NOT NULL,
  `total_deductions` decimal(15,2) NOT NULL,
  `net_pay` decimal(15,2) NOT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` int DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_config`
--

CREATE TABLE `penalty_config` (
  `id` int NOT NULL,
  `penalty_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `penalty_type` enum('late_payment','service_charge','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `calculation_method` enum('fixed_amount','percentage','daily') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fixed_amount` decimal(15,2) DEFAULT '0.00',
  `percentage_value` decimal(5,2) DEFAULT '0.00',
  `daily_rate` decimal(15,2) DEFAULT '0.00',
  `grace_days` int DEFAULT '0',
  `max_penalty_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_payments`
--

CREATE TABLE `proof_of_payments` (
  `id` int NOT NULL,
  `payment_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_calendar`
--

CREATE TABLE `registrar_academic_calendar` (
  `id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_start` date NOT NULL,
  `semester_end` date NOT NULL,
  `registration_start` date DEFAULT NULL,
  `registration_end` date DEFAULT NULL,
  `add_drop_deadline` date DEFAULT NULL,
  `withdrawal_deadline` date DEFAULT NULL,
  `exam_start` date DEFAULT NULL,
  `exam_end` date DEFAULT NULL,
  `result_publication_date` date DEFAULT NULL,
  `status` enum('Upcoming','Current','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Upcoming',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrar_academic_calendar`
--

INSERT INTO `registrar_academic_calendar` (`id`, `academic_year`, `semester`, `semester_start`, `semester_end`, `registration_start`, `registration_end`, `add_drop_deadline`, `withdrawal_deadline`, `exam_start`, `exam_end`, `result_publication_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025/2026', 'Semester 1', '2025-09-01', '2025-12-15', '2025-08-15', '2025-09-15', '2025-09-30', '2025-10-31', '2025-12-01', '2025-12-15', '2026-01-15', 'Current', NULL, '2026-06-08 11:57:56', '2026-06-08 11:57:56');

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_records`
--

CREATE TABLE `registrar_academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courses_taken` int DEFAULT NULL,
  `credits_earned` int DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `academic_standing` enum('Good Standing','Probation','Suspension') COLLATE utf8mb4_unicode_ci DEFAULT 'Good Standing',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_graduation`
--

CREATE TABLE `registrar_graduation` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `graduation_type` enum('Certificate','Diploma','Degree') COLLATE utf8mb4_unicode_ci DEFAULT 'Diploma',
  `graduation_date` date DEFAULT NULL,
  `ceremony_date` date DEFAULT NULL,
  `certificate_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `graduation_status` enum('Eligible','Not Eligible','Applied','Approved','Graduated','Deferred') COLLATE utf8mb4_unicode_ci DEFAULT 'Eligible',
  `application_date` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT '0',
  `certificate_issued_date` timestamp NULL DEFAULT NULL,
  `graduation_fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('Paid','Unpaid') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_student_registration`
--

CREATE TABLE `registrar_student_registration` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intake_set` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') COLLATE utf8mb4_unicode_ci DEFAULT 'Diploma',
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Semester 1',
  `year_of_study` int DEFAULT '1',
  `registration_date` date NOT NULL,
  `registration_status` enum('Registered','Pending','Rejected','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `registration_fee` decimal(10,2) DEFAULT '0.00',
  `registration_payment_status` enum('Paid','Partial','Unpaid') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `registered_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcripts`
--

CREATE TABLE `registrar_transcripts` (
  `id` int NOT NULL,
  `transcript_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `class_of_degree` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transcript_status` enum('Draft','Requested','Processing','Ready','Issued','Collected') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `requested_by` int DEFAULT NULL,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` int DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issued_date` timestamp NULL DEFAULT NULL,
  `collected_date` timestamp NULL DEFAULT NULL,
  `collection_signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `copies_requested` int DEFAULT '1',
  `copies_issued` int DEFAULT '0',
  `fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('Paid','Unpaid') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `roles`
-- (See below for the actual view)
--
CREATE TABLE `roles` (
`created_at` timestamp
,`description` text
,`id` int
,`name` varchar(100)
,`permissions` json
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `sponsor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sponsor_type` enum('government','ngo','private','religious','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sponsorship_type` enum('full','partial',' conditional') COLLATE utf8mb4_unicode_ci NOT NULL,
  `coverage_percentage` decimal(5,2) DEFAULT '100.00',
  `coverage_details` text COLLATE utf8mb4_unicode_ci,
  `tuition_coverage` tinyint(1) DEFAULT '1',
  `accommodation_coverage` tinyint(1) DEFAULT '0',
  `other_fee_coverage` tinyint(1) DEFAULT '0',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','suspended','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_access_logs`
--

CREATE TABLE `security_access_logs` (
  `id` int NOT NULL,
  `log_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_point` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_date` date NOT NULL,
  `access_time` time DEFAULT NULL,
  `person_type` enum('Staff','Student','Visitor','Vendor','Unknown') COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_id` int DEFAULT NULL,
  `person_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_direction` enum('Entry','Exit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_method` enum('ID Card','Biometric','PIN','Manual') COLLATE utf8mb4_unicode_ci DEFAULT 'ID Card',
  `authorized` tinyint(1) DEFAULT '1',
  `denial_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `captured_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_emergency_contacts`
--

CREATE TABLE `security_emergency_contacts` (
  `id` int NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_type` enum('Police','Hospital','Fire','Ambulance','Internal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secondary_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `response_time_minutes` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_visitors`
--

CREATE TABLE `security_visitors` (
  `id` int NOT NULL,
  `visitor_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visitor_nature` enum('Official','Parent','Guardian','Service Provider','Delivery','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose_of_visit` text COLLATE utf8mb4_unicode_ci,
  `person_to_visit` int DEFAULT NULL,
  `person_to_visit_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_date` date NOT NULL,
  `expected_arrival` time DEFAULT NULL,
  `expected_departure` time DEFAULT NULL,
  `actual_arrival` timestamp NULL DEFAULT NULL,
  `actual_departure` timestamp NULL DEFAULT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `items_carried` text COLLATE utf8mb4_unicode_ci,
  `security_check_passed` tinyint(1) DEFAULT '0',
  `check_in_by` int DEFAULT NULL,
  `check_out_by` int DEFAULT NULL,
  `status` enum('Scheduled','Checked In','On Campus','Checked Out','No Show') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave','Suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_failed_attempt` timestamp NULL DEFAULT NULL,
  `password_changed` tinyint(1) DEFAULT '0',
  `is_first_login` tinyint(1) DEFAULT '1',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `password`, `phone`, `position`, `department`, `role_id`, `status`, `hire_date`, `salary`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `last_login`, `login_attempts`, `locked_until`, `last_failed_attempt`, `password_changed`, `is_first_login`, `two_factor_enabled`, `two_factor_secret`, `created_at`, `updated_at`) VALUES
(1, 'ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Director ICT', 'Information Communication Technology', 6, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 11:56:36', '2026-06-08 12:00:00'),
(2, 'REG001', 'Academic Registrar', 'registrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$registrar@isnmHashedPassword', '+256701000010', 'Academic Registrar', 'Academic Affairs', 8, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 11:57:55', '2026-06-08 11:57:55'),
(3, 'AR002', 'Assistant Registrar', 'assistant_registrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$assistant_registrar@isnmHashedPassword', '+256701000025', 'Assistant Registrar', 'Academic Affairs', 8, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 11:57:55', '2026-06-08 11:57:55'),
(4, 'DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', '+256701000005', 'Director Finance', 'Finance Department', 5, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 11:58:23', '2026-06-08 12:00:00'),
(6, 'ICT002', 'ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Director ICT', 'Information Technology', 6, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(7, 'DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Director General', 'Executive Office', 1, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(8, 'CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Chief Executive Officer', 'Executive Office', 3, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(9, 'DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Director Academics', 'Academic Affairs', 4, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(11, 'SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', NULL, 'School Principal', 'Academic Affairs', 2, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(12, 'DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', NULL, 'Deputy Principal', 'Academic Affairs', 22, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(13, 'AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', NULL, 'Academic Registrar', 'Academic Affairs', 8, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(14, 'HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', NULL, 'HR Manager', 'Human Resources', 7, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(15, 'SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', NULL, 'School Secretary', 'Administrative Office', 21, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(16, 'LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', NULL, 'School Librarian', 'Library Services', 10, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(17, 'HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', NULL, 'Head Nursing', 'Nursing Department', 11, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(18, 'HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', NULL, 'Head Midwifery', 'Midwifery Department', 12, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(19, 'LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', NULL, 'Lecturer', 'Academic Affairs', 13, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(20, 'SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', NULL, 'Senior Lecturer', 'Academic Affairs', 14, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(21, 'NTS001', 'Non-Teaching Staff', 'nonteaching@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Non-Teaching Staff', 'Administrative', 15, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(22, 'LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', NULL, 'Sickbay', 'Support', 16, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(23, 'MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', NULL, 'Matrons', 'Student Affairs', 17, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(24, 'SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', NULL, 'Security', 'Security Services', 18, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(25, 'DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', NULL, 'Drivers', 'Transport', 19, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(26, 'WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', NULL, 'Wardens', 'Student Affairs', 20, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(27, 'STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', NULL, 'Store Keeper', 'Facilities Management', 25, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(28, 'BUR001', 'School Bursar', 'bursar@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'School Bursar', 'Finance Department', 9, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(29, 'BURS002', 'Bursar', 'bursar.assistant@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Bursar', 'Finance Department', 23, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:01', '2026-06-08 12:00:01'),
(30, 'ADM001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Director Admissions & Requirements', 'Admissions', 28, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:02', '2026-06-08 12:00:02'),
(31, 'GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', NULL, 'Guild President', 'Student Affairs', 27, 'Active', '2026-06-08', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-08 12:00:02', '2026-06-08 12:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

CREATE TABLE `staff_roles` (
  `id` int NOT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_description` text COLLATE utf8mb4_unicode_ci,
  `role_level` enum('Executive','Management','Academic','Support','Administrative') COLLATE utf8mb4_unicode_ci DEFAULT 'Academic',
  `dashboard_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{\"all\": true, \"can_view_hr\": true, \"super_admin\": true, \"can_edit_all_data\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_delete_all_data\": true, \"can_manage_all_staff\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_access_all_dashboards\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(2, 'School Principal', 'School academic and administrative leadership with cross-departmental viewing access', 'Executive', 'dashboards/school-principal.php', '{\"staff\": true, \"academic\": true, \"students\": true, \"can_view_hr\": true, \"administrative\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(3, 'CEO', 'Chief Executive Officer for strategic management with cross-departmental viewing access', 'Executive', 'dashboards/ceo.php', '{\"financial\": true, \"strategic\": true, \"can_view_hr\": true, \"operational\": true, \"can_view_reports\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(4, 'Director Academics', 'Academic programs and curriculum oversight with cross-departmental viewing access', 'Management', 'dashboards/director-academics.php', '{\"faculty\": true, \"academic\": true, \"curriculum\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_manage_courses\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(5, 'Director Finance', 'Financial management and oversight with cross-departmental viewing access', 'Management', 'dashboards/director-finance.php', '{\"budgeting\": true, \"financial\": true, \"reporting\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_manage_finances\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(6, 'Director ICT', 'Information Technology management with cross-departmental viewing access', 'Management', 'dashboards/director-ict.php', '{\"ict\": true, \"systems\": true, \"can_view_hr\": true, \"infrastructure\": true, \"can_manage_system\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(7, 'HR Manager', 'Human resources management', 'Management', 'dashboards/hr-manager.php', '{\"hr\": true, \"staff\": true, \"training\": true, \"recruitment\": true, \"can_manage_staff\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(8, 'Academic Registrar', 'Student registration and academic records management', 'Academic', 'dashboards/academic-registrar.php', '{\"academic\": true, \"students\": true, \"transcripts\": true, \"certificates\": true, \"registration\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(9, 'School Bursar', 'Financial operations and fee management', 'Administrative', 'bursar_dashboard.php', '{\"fees\": true, \"financial\": true, \"collections\": true, \"can_manage_fees\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(10, 'School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{\"catalog\": true, \"library\": true, \"resources\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(11, 'Head Nursing', 'Nursing department management', 'Academic', 'dashboards/head-nursing.php', '{\"faculty\": true, \"nursing\": true, \"department\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(12, 'Head Midwifery', 'Midwifery department management', 'Academic', 'dashboards/head-midwifery.php', '{\"faculty\": true, \"midwifery\": true, \"department\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(13, 'Lecturers', 'Teaching and academic staff management', 'Academic', 'dashboards/lecturers.php', '{\"courses\": true, \"teaching\": true, \"lecturers\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(14, 'Senior Lecturers', 'Senior teaching staff management', 'Academic', 'dashboards/senior-lecturers.php', '{\"senior\": true, \"teaching\": true, \"lecturers\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(15, 'Non-Teaching Staff', 'Administrative and support staff', 'Administrative', 'dashboards/non-teaching-staff.php', '{\"support\": true, \"administrative\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(16, 'Sickbay', 'Medical and healthcare support services', 'Support', 'dashboards/sickbay.php', '{\"medical\": true, \"patient\": true, \"healthcare\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(17, 'Matrons', 'Student welfare and residential staff management', 'Support', 'dashboards/matrons.php', '{\"residential\": true, \"student_welfare\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(18, 'Security', 'Campus security and safety management', 'Support', 'dashboards/security.php', '{\"safety\": true, \"security\": true, \"emergency\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(19, 'Drivers', 'Transportation and vehicle management', 'Support', 'dashboards/drivers.php', '{\"vehicles\": true, \"transportation\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(20, 'Wardens', 'Student discipline and residential supervision', 'Support', 'dashboards/wardens.php', '{\"discipline\": true, \"residential\": true, \"student_welfare\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(21, 'School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{\"documentation\": true, \"administrative\": true, \"can_manage_documents\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(22, 'Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{\"academic\": true, \"administrative\": true, \"can_assist_principal\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(23, 'Bursar', 'Financial assistant', 'Administrative', 'bursar_dashboard.php', '{\"fees\": true, \"financial\": true, \"can_assist_bursar\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(24, 'Secretary', 'Administrative assistant', 'Administrative', 'dashboards/school-secretary.php', '{\"documentation\": true, \"administrative\": true, \"can_assist_secretary\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(25, 'Store Keeper', 'Manage store inventory for general utilities and food supplies', 'Support', 'dashboards/storekeeper.php', '{\"store\": true, \"inventory\": true, \"can_manage_store\": true}', '2026-06-08 11:55:01', '2026-06-08 11:55:01'),
(27, 'Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{\"student_affairs\": true}', '2026-06-08 12:00:00', '2026-06-08 12:00:00'),
(28, 'Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{\"admissions\": true, \"requirements\": true}', '2026-06-08 12:00:00', '2026-06-08 12:00:00');

-- --------------------------------------------------------

--
-- Stand-in structure for view `staff_users`
-- (See below for the actual view)
--
CREATE TABLE `staff_users` (
`created_at` timestamp
,`department` varchar(100)
,`email` varchar(100)
,`full_name` varchar(100)
,`id` int
,`is_active` enum('Active','Inactive','On Leave','Suspended')
,`is_verified` tinyint(1)
,`password_hash` varchar(255)
,`phone` varchar(20)
,`role` varchar(100)
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Semester 1',
  `admission_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `guardian_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_counseling_sessions`
--

CREATE TABLE `student_counseling_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `counselor_id` int DEFAULT NULL,
  `counselor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `session_duration_minutes` int DEFAULT NULL,
  `session_type` enum('Individual','Group','Family','Crisis') COLLATE utf8mb4_unicode_ci DEFAULT 'Individual',
  `issues_discussed` text COLLATE utf8mb4_unicode_ci,
  `advice_given` text COLLATE utf8mb4_unicode_ci,
  `referrals_made` text COLLATE utf8mb4_unicode_ci,
  `follow_up_required` tinyint(1) DEFAULT '1',
  `follow_up_date` date DEFAULT NULL,
  `session_outcome` text COLLATE utf8mb4_unicode_ci,
  `student_feedback` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_emergency_contacts`
--

CREATE TABLE `student_emergency_contacts` (
  `id` int NOT NULL,
  `contact_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_address` text COLLATE utf8mb4_unicode_ci,
  `is_primary` tinyint(1) DEFAULT '0',
  `notified` tinyint(1) DEFAULT '0',
  `last_notified` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_fee_accounts`
-- (See below for the actual view)
--
CREATE TABLE `student_fee_accounts` (
`amount_paid` decimal(10,2)
,`balance` decimal(10,2)
,`created_at` timestamp
,`created_by` int
,`due_date` date
,`fee_structure_id` int
,`id` int
,`receipt_number` binary(0)
,`status` enum('Unpaid','Partially Paid','Paid','Waived')
,`student_id` int
,`total_fees` decimal(10,2)
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

CREATE TABLE `student_fee_assignments` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_date` date NOT NULL,
  `status` enum('active','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_incidents`
--

CREATE TABLE `student_health_incidents` (
  `id` int NOT NULL,
  `incident_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `incident_type` enum('Injury','Illness','Allergic Reaction','Mental Health','Emergency','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('Minor','Moderate','Severe','Critical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_aid_provided` tinyint(1) DEFAULT '0',
  `first_aid_description` text COLLATE utf8mb4_unicode_ci,
  `hospitalized` tinyint(1) DEFAULT '0',
  `hospital_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attended_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT '0',
  `parent_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `reported_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` tinyint(1) DEFAULT '0',
  `resolution_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

CREATE TABLE `student_invoices` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_structure_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` int NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

CREATE TABLE `student_penalties` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `penalty_config_id` int NOT NULL,
  `penalty_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `calculated_on` decimal(15,2) DEFAULT NULL,
  `days_late` int DEFAULT NULL,
  `status` enum('pending','paid','waived') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_room_inspections`
--

CREATE TABLE `student_room_inspections` (
  `id` int NOT NULL,
  `inspection_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_id` int DEFAULT NULL,
  `room_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inspection_date` date NOT NULL,
  `inspected_by` int DEFAULT NULL,
  `inspector_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cleanliness_score` int DEFAULT NULL,
  `maintenance_issues` text COLLATE utf8mb4_unicode_ci,
  `disciplinary_issues` text COLLATE utf8mb4_unicode_ci,
  `items_confiscated` text COLLATE utf8mb4_unicode_ci,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reports`
--

CREATE TABLE `ura_reports` (
  `id` int NOT NULL,
  `report_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_data` json DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','submitted','approved') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `users`
-- (See below for the actual view)
--
CREATE TABLE `users` (
`address` text
,`created_at` timestamp
,`dashboard_path` varchar(255)
,`department` varchar(100)
,`email` varchar(100)
,`hire_date` date
,`id` int
,`is_first_login` tinyint(1)
,`last_login` timestamp
,`locked_until` timestamp
,`login_attempts` int
,`password` varchar(255)
,`phone` varchar(20)
,`position` varchar(100)
,`role_id` int
,`role_level` enum('Executive','Management','Academic','Support','Administrative')
,`role_name` varchar(100)
,`status` enum('Active','Inactive','On Leave','Suspended')
,`updated_at` timestamp
,`user_name` varchar(100)
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure for view `fee_payments`
--
DROP TABLE IF EXISTS `fee_payments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fee_payments`  AS SELECT `igangaschoolofl_students_db`.`payments`.`id` AS `id`, `igangaschoolofl_students_db`.`payments`.`student_id` AS `student_id`, `igangaschoolofl_students_db`.`payments`.`invoice_id` AS `fee_account_id`, `igangaschoolofl_students_db`.`payments`.`amount_received` AS `amount_paid`, `igangaschoolofl_students_db`.`payments`.`payment_method` AS `payment_method`, `igangaschoolofl_students_db`.`payments`.`payment_reference` AS `receipt_number`, `igangaschoolofl_students_db`.`payments`.`status` AS `status`, `igangaschoolofl_students_db`.`payments`.`payment_date` AS `payment_date`, `igangaschoolofl_students_db`.`payments`.`notes` AS `notes`, `igangaschoolofl_students_db`.`payments`.`received_by` AS `processed_by`, `igangaschoolofl_students_db`.`payments`.`created_at` AS `created_at`, `igangaschoolofl_students_db`.`payments`.`updated_at` AS `updated_at` FROM `igangaschoolofl_students_db`.`payments` ;

-- --------------------------------------------------------

--
-- Structure for view `hr_leave_summary`
--
DROP TABLE IF EXISTS `hr_leave_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hr_leave_summary`  AS SELECT `staff_leave_requests`.`leave_type` AS `leave_type`, count(0) AS `total_requests`, sum((case when (`staff_leave_requests`.`status` = 'Approved') then 1 else 0 end)) AS `approved`, sum((case when (`staff_leave_requests`.`status` = 'Pending') then 1 else 0 end)) AS `pending`, sum((case when (`staff_leave_requests`.`status` = 'Rejected') then 1 else 0 end)) AS `rejected` FROM `staff_leave_requests` GROUP BY `staff_leave_requests`.`leave_type` ;

-- --------------------------------------------------------

--
-- Structure for view `hr_performance_summary`
--
DROP TABLE IF EXISTS `hr_performance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hr_performance_summary`  AS SELECT `st`.`id` AS `staff_id`, `st`.`full_name` AS `full_name`, `st`.`position` AS `position`, `st`.`department` AS `department`, `sr`.`role_name` AS `role_name`, coalesce(`spf`.`performance_score`,0) AS `avg_performance_score`, `spf`.`rating` AS `latest_rating`, coalesce(`sl`.`total_leaves`,0) AS `total_leaves`, coalesce(`sta`.`attendance_rate`,0) AS `attendance_rate`, coalesce(`stt`.`training_count`,0) AS `training_completed` FROM (((((`staff` `st` left join `staff_performance` `spf` on((`st`.`id` = `spf`.`staff_id`))) left join (select `staff_leave_requests`.`staff_id` AS `staff_id`,count(0) AS `total_leaves` from `staff_leave_requests` group by `staff_leave_requests`.`staff_id`) `sl` on((`st`.`id` = `sl`.`staff_id`))) left join (select `staff_attendance`.`staff_id` AS `staff_id`,((sum((case when (`staff_attendance`.`status` = 'Present') then 1 else 0 end)) * 100.0) / count(0)) AS `attendance_rate` from `staff_attendance` group by `staff_attendance`.`staff_id`) `sta` on((`st`.`id` = `sta`.`staff_id`))) left join (select `staff_training`.`staff_id` AS `staff_id`,count(0) AS `training_count` from `staff_training` where (`staff_training`.`status` = 'Completed') group by `staff_training`.`staff_id`) `stt` on((`st`.`id` = `stt`.`staff_id`))) left join `staff_roles` `sr` on((`st`.`role_id` = `sr`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `hr_staff_by_department`
--
DROP TABLE IF EXISTS `hr_staff_by_department`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hr_staff_by_department`  AS SELECT `staff`.`department` AS `department`, count(0) AS `total_staff`, sum((case when (`staff`.`status` = 'Active') then 1 else 0 end)) AS `active_staff`, sum((case when (`staff`.`status` in ('Suspended','On Leave')) then 1 else 0 end)) AS `inactive_staff`, avg(((to_days(now()) - to_days(`staff`.`hire_date`)) / 365)) AS `avg_years_of_service` FROM `staff` GROUP BY `staff`.`department` ORDER BY `staff`.`department` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `hr_staff_search_view`
--
DROP TABLE IF EXISTS `hr_staff_search_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hr_staff_search_view`  AS SELECT `s`.`id` AS `id`, `s`.`staff_id` AS `staff_id`, `s`.`full_name` AS `full_name`, `s`.`email` AS `email`, `s`.`phone` AS `phone`, `s`.`position` AS `position`, `s`.`department` AS `department`, `sr`.`role_name` AS `role_name`, `s`.`status` AS `status`, `s`.`hire_date` AS `hire_date`, `s`.`last_login` AS `last_login`, (case when (`s`.`locked_until` > now()) then 'Locked' when (`s`.`login_attempts` >= 5) then 'Warning' else 'Active' end) AS `account_status` FROM (`staff` `s` left join `staff_roles` `sr` on((`s`.`role_id` = `sr`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `hr_users`
--
DROP TABLE IF EXISTS `hr_users`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hr_users`  AS SELECT `s`.`id` AS `id`, `s`.`email` AS `email`, `s`.`password` AS `password_hash`, `s`.`full_name` AS `full_name`, `s`.`phone` AS `phone`, `s`.`position` AS `position`, `s`.`department` AS `department`, `s`.`status` AS `status`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM `staff` AS `s` WHERE ((`s`.`department` = 'Human Resources') OR (`s`.`position` like '%HR%')) ;

-- --------------------------------------------------------

--
-- Structure for view `roles`
--
DROP TABLE IF EXISTS `roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `roles`  AS SELECT `staff_roles`.`id` AS `id`, `staff_roles`.`role_name` AS `name`, `staff_roles`.`role_description` AS `description`, `staff_roles`.`permissions` AS `permissions`, `staff_roles`.`created_at` AS `created_at`, `staff_roles`.`updated_at` AS `updated_at` FROM `staff_roles` ;

-- --------------------------------------------------------

--
-- Structure for view `staff_users`
--
DROP TABLE IF EXISTS `staff_users`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `staff_users`  AS SELECT `s`.`id` AS `id`, `s`.`email` AS `email`, `s`.`password` AS `password_hash`, `s`.`full_name` AS `full_name`, `s`.`phone` AS `phone`, `s`.`position` AS `role`, `s`.`department` AS `department`, `s`.`status` AS `is_active`, `s`.`is_first_login` AS `is_verified`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM `staff` AS `s` ;

-- --------------------------------------------------------

--
-- Structure for view `student_fee_accounts`
--
DROP TABLE IF EXISTS `student_fee_accounts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_fee_accounts`  AS SELECT `igangaschoolofl_students_db`.`student_fee_assignments`.`id` AS `id`, `igangaschoolofl_students_db`.`student_fee_assignments`.`student_id` AS `student_id`, `igangaschoolofl_students_db`.`student_fee_assignments`.`fee_structure_id` AS `fee_structure_id`, `igangaschoolofl_students_db`.`student_fee_assignments`.`assigned_amount` AS `total_fees`, `igangaschoolofl_students_db`.`student_fee_assignments`.`paid_amount` AS `amount_paid`, `igangaschoolofl_students_db`.`student_fee_assignments`.`balance` AS `balance`, `igangaschoolofl_students_db`.`student_fee_assignments`.`status` AS `status`, `igangaschoolofl_students_db`.`student_fee_assignments`.`due_date` AS `due_date`, NULL AS `receipt_number`, `igangaschoolofl_students_db`.`student_fee_assignments`.`assigned_by` AS `created_by`, `igangaschoolofl_students_db`.`student_fee_assignments`.`created_at` AS `created_at`, `igangaschoolofl_students_db`.`student_fee_assignments`.`updated_at` AS `updated_at` FROM `igangaschoolofl_students_db`.`student_fee_assignments` ;

-- --------------------------------------------------------

--
-- Structure for view `users`
--
DROP TABLE IF EXISTS `users`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users`  AS SELECT `s`.`id` AS `id`, `s`.`staff_id` AS `username`, `s`.`full_name` AS `user_name`, `s`.`email` AS `email`, `s`.`password` AS `password`, `s`.`position` AS `position`, `s`.`department` AS `department`, `s`.`role_id` AS `role_id`, `sr`.`role_name` AS `role_name`, `sr`.`role_level` AS `role_level`, `sr`.`dashboard_path` AS `dashboard_path`, `s`.`status` AS `status`, `s`.`phone` AS `phone`, `s`.`address` AS `address`, `s`.`hire_date` AS `hire_date`, `s`.`last_login` AS `last_login`, `s`.`login_attempts` AS `login_attempts`, `s`.`locked_until` AS `locked_until`, `s`.`is_first_login` AS `is_first_login`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM (`staff` `s` join `staff_roles` `sr` on((`s`.`role_id` = `sr`.`id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `analytics_id` (`analytics_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_program_code` (`program_code`);

--
-- Indexes for table `academic_course_catalog`
--
ALTER TABLE `academic_course_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `course_coordinator` (`course_coordinator`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_program_code` (`program_code`);

--
-- Indexes for table `academic_curriculum_development`
--
ALTER TABLE `academic_curriculum_development`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `curriculum_id` (`curriculum_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_curriculum_id` (`curriculum_id`);

--
-- Indexes for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `program_coordinator` (`program_coordinator`),
  ADD KEY `idx_program_code` (`program_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `academic_reports`
--
ALTER TABLE `academic_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generated_date` (`generated_date`);

--
-- Indexes for table `academic_timetable`
--
ALTER TABLE `academic_timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `timetable_id` (`timetable_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_timetable_id` (`timetable_id`),
  ADD KEY `idx_program_code` (`program_code`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `idx_asset_tag` (`asset_tag`),
  ADD KEY `idx_category` (`asset_category`);

--
-- Indexes for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_account_id` (`bank_account_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`budget_category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cashbook`
--
ALTER TABLE `cashbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`transaction_date`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `idx_account_code` (`account_code`),
  ADD KEY `idx_account_type` (`account_type`);

--
-- Indexes for table `communication_log`
--
ALTER TABLE `communication_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_date` (`sent_date`);

--
-- Indexes for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_id` (`expense_id`),
  ADD KEY `idx_expense_id` (`expense_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`expense_category`);

--
-- Indexes for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Indexes for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `lab_chemical_inventory`
--
ALTER TABLE `lab_chemical_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chemical_id` (`chemical_id`),
  ADD KEY `idx_chemical_id` (`chemical_id`),
  ADD KEY `idx_chemical_name` (`chemical_name`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_id` (`equipment_id`),
  ADD KEY `idx_equipment_id` (`equipment_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `experiment_id` (`experiment_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `sickbay_staff_id` (`sickbay_staff_id`),
  ADD KEY `idx_experiment_id` (`experiment_id`),
  ADD KEY `idx_experiment_date` (`experiment_date`);

--
-- Indexes for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_id` (`item_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_category` (`item_category`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `completed_by` (`completed_by`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_session_date` (`session_date`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `book_id` (`book_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_return_status` (`return_status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `library_digital_resources`
--
ALTER TABLE `library_digital_resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resource_id` (`resource_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_resource_id` (`resource_id`),
  ADD KEY `idx_title` (`title`);

--
-- Indexes for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fine_id` (`fine_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_fine_id` (`fine_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_paid` (`paid`);

--
-- Indexes for table `library_members`
--
ALTER TABLE `library_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_full_name` (`full_name`);

--
-- Indexes for table `midwifery_antenatal_care`
--
ALTER TABLE `midwifery_antenatal_care`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_id` (`record_id`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_visit_date` (`antenatal_visit_date`);

--
-- Indexes for table `midwifery_family_planning`
--
ALTER TABLE `midwifery_family_planning`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fp_id` (`fp_id`),
  ADD KEY `idx_fp_id` (`fp_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `midwifery_labor_delivery`
--
ALTER TABLE `midwifery_labor_delivery`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_id` (`delivery_id`),
  ADD KEY `idx_delivery_id` (`delivery_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_delivery_date` (`delivery_date`);

--
-- Indexes for table `midwifery_postnatal_care`
--
ALTER TABLE `midwifery_postnatal_care`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `postnatal_id` (`postnatal_id`),
  ADD KEY `idx_postnatal_id` (`postnatal_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `midwifery_students`
--
ALTER TABLE `midwifery_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_intake_set` (`intake_set`);

--
-- Indexes for table `nursing_clinical_logbook`
--
ALTER TABLE `nursing_clinical_logbook`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `logbook_id` (`logbook_id`),
  ADD KEY `logged_by` (`logged_by`),
  ADD KEY `idx_logbook_id` (`logbook_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_log_date` (`log_date`);

--
-- Indexes for table `nursing_clinical_placements`
--
ALTER TABLE `nursing_clinical_placements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placement_number` (`placement_number`),
  ADD KEY `graded_by` (`graded_by`),
  ADD KEY `idx_placement_number` (`placement_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `nursing_practical_assessment`
--
ALTER TABLE `nursing_practical_assessment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assessment_id` (`assessment_id`),
  ADD KEY `assessor_id` (`assessor_id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `nursing_skills_training`
--
ALTER TABLE `nursing_skills_training`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `training_id` (`training_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `idx_training_id` (`training_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `nursing_students`
--
ALTER TABLE `nursing_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_intake_set` (`intake_set`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_payment_ref` (`payment_reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_payment` (`payment_id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslip_number` (`payslip_number`),
  ADD KEY `idx_payslip_number` (`payslip_number`),
  ADD KEY `idx_staff` (`staff_id`);

--
-- Indexes for table `penalty_config`
--
ALTER TABLE `penalty_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment` (`payment_id`);

--
-- Indexes for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_semester` (`semester`);

--
-- Indexes for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_academic_year` (`academic_year`);

--
-- Indexes for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_graduation_status` (`graduation_status`);

--
-- Indexes for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `registered_by` (`registered_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_registration_number` (`registration_number`),
  ADD KEY `idx_registration_date` (`registration_date`);

--
-- Indexes for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transcript_number` (`transcript_number`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `idx_transcript_number` (`transcript_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`transcript_status`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `security_access_logs`
--
ALTER TABLE `security_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_id` (`log_id`),
  ADD KEY `idx_log_id` (`log_id`),
  ADD KEY `idx_access_date` (`access_date`),
  ADD KEY `idx_person_type` (`person_type`);

--
-- Indexes for table `security_emergency_contacts`
--
ALTER TABLE `security_emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_type` (`contact_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `security_visitors`
--
ALTER TABLE `security_visitors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `visitor_id` (`visitor_id`),
  ADD KEY `person_to_visit` (`person_to_visit`),
  ADD KEY `check_in_by` (`check_in_by`),
  ADD KEY `check_out_by` (`check_out_by`),
  ADD KEY `idx_visitor_id` (`visitor_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- Indexes for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_role_name` (`role_name`),
  ADD KEY `idx_role_level` (`role_level`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_program` (`program`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_session_date` (`session_date`);

--
-- Indexes for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact_id` (`contact_id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_fee_structure` (`fee_structure_id`);

--
-- Indexes for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `incident_id` (`incident_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_incident_id` (`incident_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_incident_date` (`incident_date`);

--
-- Indexes for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_room_inspections`
--
ALTER TABLE `student_room_inspections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inspection_id` (`inspection_id`),
  ADD KEY `inspected_by` (`inspected_by`),
  ADD KEY `idx_inspection_id` (`inspection_id`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_inspection_date` (`inspection_date`);

--
-- Indexes for table `ura_reports`
--
ALTER TABLE `ura_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_course_catalog`
--
ALTER TABLE `academic_course_catalog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_curriculum_development`
--
ALTER TABLE `academic_curriculum_development`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_programs`
--
ALTER TABLE `academic_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_reports`
--
ALTER TABLE `academic_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_timetable`
--
ALTER TABLE `academic_timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashbook`
--
ALTER TABLE `cashbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_log`
--
ALTER TABLE `communication_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_chemical_inventory`
--
ALTER TABLE `lab_chemical_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_digital_resources`
--
ALTER TABLE `library_digital_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_fines`
--
ALTER TABLE `library_fines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_members`
--
ALTER TABLE `library_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_antenatal_care`
--
ALTER TABLE `midwifery_antenatal_care`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_family_planning`
--
ALTER TABLE `midwifery_family_planning`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_labor_delivery`
--
ALTER TABLE `midwifery_labor_delivery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_postnatal_care`
--
ALTER TABLE `midwifery_postnatal_care`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_students`
--
ALTER TABLE `midwifery_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_clinical_logbook`
--
ALTER TABLE `nursing_clinical_logbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_clinical_placements`
--
ALTER TABLE `nursing_clinical_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_practical_assessment`
--
ALTER TABLE `nursing_practical_assessment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_skills_training`
--
ALTER TABLE `nursing_skills_training`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_students`
--
ALTER TABLE `nursing_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_config`
--
ALTER TABLE `penalty_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_access_logs`
--
ALTER TABLE `security_access_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_emergency_contacts`
--
ALTER TABLE `security_emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_visitors`
--
ALTER TABLE `security_visitors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `staff_roles`
--
ALTER TABLE `staff_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_invoices`
--
ALTER TABLE `student_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_penalties`
--
ALTER TABLE `student_penalties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_room_inspections`
--
ALTER TABLE `student_room_inspections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ura_reports`
--
ALTER TABLE `ura_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  ADD CONSTRAINT `academic_analytics_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `academic_course_catalog`
--
ALTER TABLE `academic_course_catalog`
  ADD CONSTRAINT `academic_course_catalog_ibfk_1` FOREIGN KEY (`course_coordinator`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `academic_curriculum_development`
--
ALTER TABLE `academic_curriculum_development`
  ADD CONSTRAINT `academic_curriculum_development_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD CONSTRAINT `academic_programs_ibfk_1` FOREIGN KEY (`program_coordinator`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `academic_reports`
--
ALTER TABLE `academic_reports`
  ADD CONSTRAINT `academic_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `academic_timetable`
--
ALTER TABLE `academic_timetable`
  ADD CONSTRAINT `academic_timetable_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `academic_timetable_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  ADD CONSTRAINT `asset_depreciation_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD CONSTRAINT `bank_reconciliations_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`);

--
-- Constraints for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  ADD CONSTRAINT `departmental_budgets_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD CONSTRAINT `expense_approvals_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  ADD CONSTRAINT `lab_experiments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `lab_skills_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_experiments_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_experiments_ibfk_3` FOREIGN KEY (`sickbay_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  ADD CONSTRAINT `lab_inventory_ibfk_1` FOREIGN KEY (`received_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  ADD CONSTRAINT `lab_skills_sessions_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_skills_sessions_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_books`
--
ALTER TABLE `library_books`
  ADD CONSTRAINT `library_books_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  ADD CONSTRAINT `library_borrowing_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_borrowing_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_digital_resources`
--
ALTER TABLE `library_digital_resources`
  ADD CONSTRAINT `library_digital_resources_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD CONSTRAINT `library_fines_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `library_borrowing` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `library_fines_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `library_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `library_members`
--
ALTER TABLE `library_members`
  ADD CONSTRAINT `library_members_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_members_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `midwifery_antenatal_care`
--
ALTER TABLE `midwifery_antenatal_care`
  ADD CONSTRAINT `midwifery_antenatal_care_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `midwifery_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `midwifery_family_planning`
--
ALTER TABLE `midwifery_family_planning`
  ADD CONSTRAINT `midwifery_family_planning_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `midwifery_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `midwifery_labor_delivery`
--
ALTER TABLE `midwifery_labor_delivery`
  ADD CONSTRAINT `midwifery_labor_delivery_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `midwifery_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `midwifery_postnatal_care`
--
ALTER TABLE `midwifery_postnatal_care`
  ADD CONSTRAINT `midwifery_postnatal_care_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `midwifery_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `midwifery_students`
--
ALTER TABLE `midwifery_students`
  ADD CONSTRAINT `midwifery_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nursing_clinical_logbook`
--
ALTER TABLE `nursing_clinical_logbook`
  ADD CONSTRAINT `nursing_clinical_logbook_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `nursing_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_clinical_logbook_ibfk_2` FOREIGN KEY (`logged_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nursing_clinical_placements`
--
ALTER TABLE `nursing_clinical_placements`
  ADD CONSTRAINT `nursing_clinical_placements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `nursing_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_clinical_placements_ibfk_2` FOREIGN KEY (`graded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nursing_practical_assessment`
--
ALTER TABLE `nursing_practical_assessment`
  ADD CONSTRAINT `nursing_practical_assessment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `nursing_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_practical_assessment_ibfk_2` FOREIGN KEY (`assessor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nursing_skills_training`
--
ALTER TABLE `nursing_skills_training`
  ADD CONSTRAINT `nursing_skills_training_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `nursing_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nursing_skills_training_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nursing_students`
--
ALTER TABLE `nursing_students`
  ADD CONSTRAINT `nursing_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  ADD CONSTRAINT `registrar_academic_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  ADD CONSTRAINT `registrar_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_academic_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  ADD CONSTRAINT `registrar_graduation_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_graduation_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  ADD CONSTRAINT `registrar_student_registration_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_student_registration_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_student_registration_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  ADD CONSTRAINT `registrar_transcripts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_transcripts_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_transcripts_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_transcripts_ibfk_4` FOREIGN KEY (`issued_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_visitors`
--
ALTER TABLE `security_visitors`
  ADD CONSTRAINT `security_visitors_ibfk_1` FOREIGN KEY (`person_to_visit`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_visitors_ibfk_2` FOREIGN KEY (`check_in_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_visitors_ibfk_3` FOREIGN KEY (`check_out_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  ADD CONSTRAINT `student_counseling_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_counseling_sessions_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  ADD CONSTRAINT `student_emergency_contacts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  ADD CONSTRAINT `student_health_incidents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `igangaschoolofl_students_db`.`students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_health_incidents_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_room_inspections`
--
ALTER TABLE `student_room_inspections`
  ADD CONSTRAINT `student_room_inspections_ibfk_1` FOREIGN KEY (`inspected_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
