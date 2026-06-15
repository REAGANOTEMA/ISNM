-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 09:04 AM
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
        program_code,
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
        AVG(gpa) as average_gpa
    FROM universal_student_profiles
    WHERE academic_year = p_academic_year 
      AND (p_program_code IS NULL OR program = p_program_code)
    GROUP BY program_code;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `authenticate_staff` (IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_ip_address` VARCHAR(45), IN `p_user_agent` TEXT)   BEGIN
    DECLARE v_staff_id INT; DECLARE v_password_hash VARCHAR(255);
    SELECT s.id, s.password INTO v_staff_id, v_password_hash FROM staff s WHERE s.email = p_email AND s.status = 'Active' LIMIT 1;
    IF v_staff_id IS NOT NULL AND v_password_hash = p_password THEN
        UPDATE staff SET login_attempts = 0, last_login = NOW() WHERE id = v_staff_id;
        SELECT v_staff_id as staff_id, s.full_name, sr.role_name, 'Login successful' as message, TRUE as success FROM staff s JOIN staff_roles sr ON s.role_id = sr.id WHERE s.id = v_staff_id;
    ELSE
        SELECT NULL as staff_id, NULL as full_name, NULL as role_name, 'Invalid credentials' as message, FALSE as success;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `change_password` (IN `p_staff_id` INT, IN `p_current_password` VARCHAR(255), IN `p_new_password` VARCHAR(255), IN `p_ip_address` VARCHAR(45))   BEGIN
    SELECT 'Password changed' as message, TRUE as success;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_student_photo` (IN `p_student_id` INT, IN `p_deleted_by` INT)   BEGIN
    UPDATE universal_student_profiles 
    SET photo_path = NULL,
        photo_uploaded = FALSE,
        updated_by = p_deleted_by,
        updated_at = NOW()
    WHERE id = p_student_id;
    
    INSERT INTO student_photos (student_id, photo_action, action_by, notes)
    VALUES (p_student_id, 'delete', p_deleted_by, 'Photo deleted');
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_students` ()   BEGIN
    SELECT 
        set_name as intake_set,
        COUNT(*) as total_students,
        COUNT(CASE WHEN profile_picture IS NOT NULL THEN 1 END) as students_with_photos
    FROM igangaschoolofl_students_db.students
    GROUP BY set_name
    ORDER BY set_name DESC;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_dashboard_statistics` (IN `p_user_id` INT, IN `p_role` VARCHAR(100))   BEGIN
    -- Return statistics based on user role
    IF p_role = 'Director General' OR p_role = 'School Principal' OR p_role = 'CEO' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM student_admissions WHERE admission_status = 'Pending') as pending_applications,
            (SELECT COUNT(DISTINCT program) FROM students WHERE status = 'Active') as active_programs,
            (SELECT SUM(amount) FROM financial_records WHERE record_type = 'Collection' AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_collections;
    ELSEIF p_role = 'Security' THEN
        CALL get_security_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Warden' THEN
        CALL get_wardens_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Driver' THEN
        CALL get_drivers_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Matron' THEN
        CALL get_matrons_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Sickbay' THEN
        CALL get_sickbay_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'School Librarian' THEN
        CALL get_school_librarian_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'HR Manager' THEN
        SELECT 
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM recruitment_applications WHERE status = 'Received') as pending_applications,
            (SELECT COUNT(*) FROM staff_leave_requests WHERE status = 'Pending') as pending_leaves,
            (SELECT COUNT(*) FROM staff_training WHERE status = 'Scheduled') as upcoming_trainings;
    ELSEIF p_role = 'School Bursar' OR p_role = 'Bursar' OR p_role = 'Director Finance' THEN
        SELECT 
            (SELECT SUM(amount) FROM payment_records WHERE payment_date = CURDATE()) as today_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_collections,
            (SELECT SUM(balance) FROM fee_accounts WHERE status != 'Paid') as outstanding_fees,
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students;
    ELSEIF p_role = 'Academic Registrar' OR p_role = 'Director Academics' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE position LIKE '%Lecturer%' AND status = 'Active') as total_lecturers,
            (SELECT COUNT(DISTINCT course_code) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT AVG(gpa) FROM student_academic_profiles WHERE academic_status = 'Good Standing') as avg_gpa;
    ELSEIF p_role = 'Head of Nursing' OR p_role = 'Head of Midwifery' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE program LIKE CONCAT('%', p_role, '%') AND status = 'Active') as department_students,
            (SELECT COUNT(*) FROM staff WHERE department = p_role AND status = 'Active') as department_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT COUNT(*) FROM clinical_placements WHERE status = 'In Progress') as active_placements;
    ELSE
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = p_user_id AND status = 'Active') as assigned_courses,
            (SELECT COUNT(*) FROM examination_records WHERE lecturer_id = p_user_id AND grade_status = 'Draft') as pending_grades;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_drivers_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE assigned_driver = p_user_id AND status = 'Available') as available_vehicles,
        (SELECT COUNT(*) FROM trip_logs WHERE driver_id = p_user_id AND trip_date = CURDATE() AND status = 'In Transit') as active_trips,
        (SELECT COUNT(*) FROM trip_logs WHERE driver_id = p_user_id AND trip_date = CURDATE() AND status = 'Completed') as completed_trips_today,
        (SELECT COUNT(*) FROM route_schedules WHERE driver_id = p_user_id AND status = 'Active') as assigned_routes,
        (SELECT SUM(fuel_quantity) FROM fuel_management WHERE filled_by = p_user_id AND fueling_date = CURDATE()) as fuel_consumed_today,
        (SELECT COUNT(*) FROM vehicles WHERE status = 'Maintenance') as vehicles_in_maintenance;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_matrons_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM student_welfare_cases WHERE assigned_warden = p_user_id AND status IN ('Open', 'In Progress')) as open_welfare_cases,
        (SELECT COUNT(*) FROM counseling_sessions WHERE counselor_id = p_user_id AND scheduled_date = CURDATE()) as todays_counseling_sessions,
        (SELECT COUNT(*) FROM health_incidents WHERE reported_by = p_user_id AND DATE(incident_date) = CURDATE()) as health_incidents_today,
        (SELECT COUNT(*) FROM health_incidents WHERE severity IN('Severe', 'Critical') AND status != 'Closed') as critical_health_cases,
        (SELECT COUNT(*) FROM meal_tracking WHERE served_by = p_user_id AND meal_date = CURDATE()) as meals_served_today,
        (SELECT COUNT(*) FROM room_inspections WHERE inspector_id = p_user_id AND inspection_date = CURDATE()) as todays_inspections;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_school_librarian_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM library_management WHERE status = 'Available') as available_books,
        (SELECT COUNT(*) FROM library_transactions WHERE transaction_type = 'Borrow' AND DATE(borrow_date) = CURDATE()) as books_borrowed_today,
        (SELECT COUNT(*) FROM library_transactions WHERE transaction_type = 'Return' AND DATE(return_date) = CURDATE()) as books_returned_today,
        (SELECT COUNT(*) FROM library_transactions WHERE status = 'Overdue') as overdue_books,
        (SELECT COUNT(*) FROM library_management WHERE status = 'Borrowed') as books_on_loan,
        (SELECT COUNT(*) FROM library_management WHERE status = 'Reserved') as reserved_books;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_security_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status = 'In Progress') as active_patrols,
        (SELECT COUNT(*) FROM security_incidents WHERE DATE(incident_date) = CURDATE()) as incidents_today,
        (SELECT COUNT(*) FROM access_control_logs WHERE DATE(access_time) = CURDATE()) as access_entries_today,
        (SELECT COUNT(*) FROM security_equipment WHERE status = 'Operational') as operational_equipment,
        (SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status = 'Scheduled') as scheduled_patrols,
        (SELECT COUNT(*) FROM security_incidents WHERE severity = 'High' AND status != 'Closed') as high_priority_incidents;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_sickbay_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM lab_equipment_maintenance WHERE technician_id = p_user_id AND status = 'Scheduled') as scheduled_maintenance,
        (SELECT COUNT(*) FROM lab_equipment_maintenance WHERE technician_id = p_user_id AND status = 'In Progress') as maintenance_in_progress,
        (SELECT COUNT(*) FROM lab_safety_records WHERE inspector_id = p_user_id AND inspection_date = CURDATE()) as todays_inspections,
        (SELECT COUNT(*) FROM chemical_inventory WHERE status = 'Low Stock') as low_stock_chemicals,
        (SELECT COUNT(*) FROM chemical_inventory WHERE expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as expiring_soon,
        (SELECT COUNT(*) FROM skills_lab_sessions WHERE lecturer_id = p_user_id AND session_date = CURDATE()) as todays_lab_sessions;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_staff_performance_summary` (IN `p_staff_id` INT)   BEGIN
    SELECT 
        s.staff_id,
        s.full_name,
        s.position,
        s.department,
        sr.role_name,
        (SELECT AVG(performance_score) FROM staff_performance WHERE staff_id = p_staff_id) as avg_performance,
        (SELECT COUNT(*) FROM staff_training WHERE staff_id = p_staff_id AND status = 'Completed') as completed_trainings,
        (SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = p_staff_id AND status = 'Active') as active_courses,
        (SELECT COUNT(*) FROM staff_leave_requests WHERE staff_id = p_staff_id AND status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE())) as approved_leaves
    FROM staff s
    JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.id = p_staff_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_student_academic_summary` (IN `p_student_id` INT)   BEGIN
    SELECT 
        s.student_number,
        s.first_name,
        s.last_name,
        s.program,
        s.year_of_study,
        s.semester,
        sap.gpa,
        sap.academic_status,
        (SELECT COUNT(*) FROM examination_records WHERE student_id = p_student_id) as total_exams,
        (SELECT COUNT(*) FROM course_registrations WHERE student_id = p_student_id AND status = 'Registered') as registered_courses
    FROM students s
    LEFT JOIN student_academic_profiles sap ON s.id = sap.student_id
    WHERE s.id = p_student_id;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_student_fee_status` (IN `p_student_id` INT)   BEGIN
    SELECT 
        s.student_number,
        s.first_name,
        s.last_name,
        s.program,
        COALESCE(SUM(fa.amount), 0) as total_fees,
        COALESCE(SUM(fa.paid_amount), 0) as total_paid,
        COALESCE(SUM(fa.balance), 0) as outstanding_balance,
        CASE 
            WHEN COALESCE(SUM(fa.balance), 0) = 0 THEN 'Cleared'
            WHEN COALESCE(SUM(fa.balance), 0) > 0 THEN 'Not Cleared'
        END as fee_status
    FROM students s
    LEFT JOIN fee_accounts fa ON s.id = fa.student_id
    WHERE s.id = p_student_id
    GROUP BY s.id;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_wardens_dashboard_statistics` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM student_welfare_cases WHERE assigned_warden = p_user_id AND status IN ('Open', 'In Progress')) as open_welfare_cases,
        (SELECT COUNT(*) FROM counseling_sessions WHERE counselor_id = p_user_id AND scheduled_date = CURDATE()) as todays_counseling_sessions,
        (SELECT COUNT(*) FROM room_inspections WHERE inspection_date = CURDATE()) as todays_inspections,
        (SELECT COUNT(*) FROM student_discipline WHERE status = 'Pending') as pending_discipline_cases,
        (SELECT COUNT(*) FROM duty_rosters WHERE warden_id = p_user_id AND duty_date = CURDATE()) as todays_duties,
        (SELECT COUNT(*) FROM visitor_logs WHERE visit_date = CURDATE() AND status = 'Checked In') as current_visitors;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_staff_activity` (IN `p_staff_id` INT, IN `p_activity_type` VARCHAR(100), IN `p_activity_description` TEXT, IN `p_module_accessed` VARCHAR(100), IN `p_record_id` INT, IN `p_ip_address` VARCHAR(45), IN `p_user_agent` TEXT)   BEGIN
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id, ip_address, user_agent) VALUES (p_staff_id, p_activity_type, p_activity_description, p_module_accessed, p_record_id, p_ip_address, p_user_agent);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `print_student_profile` (IN `p_student_id` INT, IN `p_printed_by` INT)   BEGIN
    INSERT INTO student_profile_edits (student_id, field_changed, action_type, edited_by)
    VALUES (p_student_id, 'print', 'photo_print', p_printed_by);
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `request_password_reset` (IN `p_email` VARCHAR(100), IN `p_ip_address` VARCHAR(45))   BEGIN
    DECLARE v_staff_id INT; SELECT id INTO v_staff_id FROM staff WHERE email = p_email AND status = 'Active' LIMIT 1;
    IF v_staff_id IS NOT NULL THEN SELECT MD5(CONCAT(p_email, NOW())) as reset_token, DATE_ADD(NOW(), INTERVAL 1 HOUR) as expires_at, 'Success' as message, TRUE as success;
    ELSE SELECT NULL as reset_token, NULL as expires_at, 'Email not found' as message, FALSE as success;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `reset_password_with_token` (IN `p_reset_token` VARCHAR(255), IN `p_new_password` VARCHAR(255), IN `p_ip_address` VARCHAR(45))   BEGIN
    SELECT 'Password reset function' as message, TRUE as success;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_student_photo` (IN `p_student_id` INT, IN `p_new_photo_path` VARCHAR(500), IN `p_updated_by` INT)   BEGIN
    UPDATE universal_student_profiles 
    SET photo_path = p_new_photo_path,
        photo_uploaded = TRUE,
        photo_upload_date = NOW(),
        updated_by = p_updated_by,
        updated_at = NOW()
    WHERE id = p_student_id;
    
    INSERT INTO student_photos (student_id, new_photo_path, photo_action, action_by, notes)
    VALUES (p_student_id, p_new_photo_path, 'upload', p_updated_by, 'Photo updated');
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
  `analytics_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `total_enrolled` int DEFAULT NULL,
  `total_graduated` int DEFAULT NULL,
  `total_dropped` int DEFAULT NULL,
  `average_gpa` decimal(3,2) DEFAULT NULL,
  `pass_rate` decimal(5,2) DEFAULT NULL,
  `withdrawal_rate` decimal(5,2) DEFAULT NULL,
  `employment_rate` decimal(5,2) DEFAULT NULL,
  `analysis_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

CREATE TABLE `academic_calendar` (
  `id` int NOT NULL,
  `calendar_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `semester_start_date` date NOT NULL,
  `semester_end_date` date NOT NULL,
  `exam_start_date` date NOT NULL,
  `exam_end_date` date NOT NULL,
  `result_publication_date` date DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `add_drop_deadline` date DEFAULT NULL,
  `withdrawal_deadline` date DEFAULT NULL,
  `status` enum('Upcoming','Current','Completed','Cancelled') DEFAULT 'Upcoming',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT INTO `academic_calendar` (`id`, `calendar_id`, `academic_year`, `semester`, `semester_start_date`, `semester_end_date`, `exam_start_date`, `exam_end_date`, `result_publication_date`, `registration_deadline`, `add_drop_deadline`, `withdrawal_deadline`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CAL-2024-2025-S1', '2024-2025', 'Semester 1', '2024-09-01', '2024-12-15', '2024-12-01', '2024-12-15', '2025-01-15', '2024-09-15', '2024-09-30', '2024-10-31', 'Current', NULL, NULL, '2026-06-14 19:50:49', '2026-06-14 19:50:49'),
(2, 'CAL-2024-2025-S2', '2024-2025', 'Semester 2', '2025-02-01', '2025-05-31', '2025-05-15', '2025-05-31', '2025-06-15', '2025-02-15', '2025-02-28', '2025-03-31', 'Upcoming', NULL, NULL, '2026-06-14 19:50:49', '2026-06-14 19:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `academic_course_catalog`
--

CREATE TABLE `academic_course_catalog` (
  `id` int NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(255) NOT NULL,
  `credits` int NOT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `theory_hours` int DEFAULT NULL,
  `practical_hours` int DEFAULT NULL,
  `tutorials_hours` int DEFAULT NULL,
  `assessment_method` text,
  `course_coordinator` int DEFAULT NULL,
  `prerequisites` text,
  `learning_outcomes` text,
  `status` enum('Active','Inactive','Under Review') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_curriculum_development`
--

CREATE TABLE `academic_curriculum_development` (
  `id` int NOT NULL,
  `curriculum_id` varchar(50) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `revision_number` int DEFAULT '1',
  `changes_made` text,
  `reason_for_changes` text,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('Draft','Under Review','Approved','Implemented') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

CREATE TABLE `academic_programs` (
  `id` int NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') NOT NULL,
  `department` varchar(100) NOT NULL,
  `duration_years` int DEFAULT '2',
  `total_credits` int DEFAULT NULL,
  `program_coordinator` int DEFAULT NULL,
  `accreditation_status` enum('Accredited','Provisional','Expired','Pending') DEFAULT 'Accredited',
  `accreditation_body` varchar(255) DEFAULT NULL,
  `accreditation_expiry` date DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_records`
--

CREATE TABLE `academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `lecturer_id` int DEFAULT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `assessment_type` enum('Exam','Assignment','Quiz','Project','Attendance') NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `remarks` text,
  `graded_by` int DEFAULT NULL,
  `grading_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_reports`
--

CREATE TABLE `academic_reports` (
  `id` int NOT NULL,
  `report_id` varchar(50) NOT NULL,
  `report_type` enum('Enrollment','Graduation','Performance','Employment','Accreditation','Compliance') NOT NULL,
  `report_period` varchar(50) DEFAULT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `report_data` longtext,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pdf_path` varchar(500) DEFAULT NULL,
  `status` enum('Draft','Final','Archived') DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_timetable`
--

CREATE TABLE `academic_timetable` (
  `id` int NOT NULL,
  `timetable_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `program_code` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `lecturer_id` int DEFAULT NULL,
  `timetable_status` enum('Draft','Approved','Published','Cancelled') DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `access_control_logs`
--

CREATE TABLE `access_control_logs` (
  `id` int NOT NULL,
  `log_number` varchar(50) NOT NULL,
  `access_type` enum('Entry','Exit','Vehicle Entry','Vehicle Exit','Visitor Check-in','Visitor Check-out') NOT NULL,
  `person_type` enum('Student','Staff','Visitor','Unknown') NOT NULL,
  `person_id` int DEFAULT NULL,
  `person_name` varchar(200) DEFAULT NULL,
  `access_point` varchar(100) NOT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `access_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `purpose` varchar(200) DEFAULT NULL,
  `status` enum('Authorized','Unauthorized','Pending') DEFAULT 'Authorized',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accreditation_management`
--

CREATE TABLE `accreditation_management` (
  `id` int NOT NULL,
  `accreditation_code` varchar(50) NOT NULL,
  `program_name` varchar(200) NOT NULL,
  `accrediting_body` varchar(200) NOT NULL,
  `accreditation_type` enum('Initial','Renewal','Re-accreditation','Special') NOT NULL,
  `application_date` date DEFAULT NULL,
  `site_visit_date` date DEFAULT NULL,
  `accreditation_status` enum('Pending','Under Review','Approved','Conditional','Denied','Expired') DEFAULT 'Pending',
  `expiry_date` date DEFAULT NULL,
  `report_file` varchar(500) DEFAULT NULL,
  `compliance_notes` text,
  `responsible_person` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `activity` enum('Login','Logout','Dashboard Access','Student View','Student Edit','Student Delete','Export','Print','Settings Change','Document Generate','Exam Create','Exam Schedule','Timetable Update','Certificate Generate','Report Generate','Bulk Import','Payment Process','Leave Request','Performance Review','Training Assign','Document Upload','System Update') NOT NULL,
  `activity_description` text,
  `module_accessed` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `old_values` longtext,
  `new_values` longtext,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `status` enum('success','failure') DEFAULT 'success',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `advanced_reports`
--

CREATE TABLE `advanced_reports` (
  `id` int NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `report_type` enum('student','staff','financial','academic','performance','attendance','comprehensive') NOT NULL,
  `report_query` longtext NOT NULL,
  `report_parameters` json DEFAULT NULL,
  `report_template` longtext,
  `is_scheduled` tinyint(1) DEFAULT '0',
  `schedule_frequency` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `recipients` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `all_students_view`
-- (See below for the actual view)
--
CREATE TABLE `all_students_view` (
`id` int
,`student_number` varchar(50)
,`national_id` varchar(50)
,`index_number` varchar(50)
,`registration_number` varchar(50)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`full_name` varchar(255)
,`email` varchar(100)
,`phone` varchar(20)
,`date_of_birth` date
,`gender` enum('Male','Female','Other')
,`program` varchar(100)
,`program_type` enum('Certificate','Diploma','Degree')
,`intake_set` varchar(20)
,`intake_date` date
,`year_of_study` int
,`semester` varchar(50)
,`academic_year` varchar(20)
,`address` text
,`district` varchar(100)
,`county` varchar(100)
,`sub_county` varchar(100)
,`parish` varchar(100)
,`village` varchar(100)
,`guardian_name` varchar(200)
,`guardian_phone` varchar(20)
,`guardian_relationship` varchar(50)
,`guardian_address` text
,`emergency_contact_name` varchar(100)
,`emergency_contact_phone` varchar(20)
,`emergency_contact_relationship` varchar(50)
,`nationality` varchar(50)
,`religion` varchar(50)
,`marital_status` enum('Single','Married','Divorced','Widowed')
,`photo_path` varchar(500)
,`photo_uploaded` tinyint(1)
,`photo_upload_date` timestamp
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','Transferred')
,`enrollment_status` enum('Full Time','Part Time','Distance')
,`gpa` decimal(3,2)
,`cgpa` decimal(3,2)
,`created_by` int
,`updated_by` int
,`created_at` timestamp
,`updated_at` timestamp
,`photo_status` text
);

-- --------------------------------------------------------

--
-- Table structure for table `analytics_cache`
--

CREATE TABLE `analytics_cache` (
  `id` int NOT NULL,
  `cache_key` varchar(255) NOT NULL,
  `cache_type` enum('student_stats','staff_stats','financial_stats','performance_stats','attendance_stats','course_stats') DEFAULT 'student_stats',
  `cache_data` longtext,
  `expiry_time` timestamp NULL DEFAULT ((now() + interval 1 hour)),
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `permissions` json DEFAULT NULL,
  `allowed_origins` text,
  `rate_limit` int DEFAULT '1000',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_periods`
--

CREATE TABLE `appraisal_periods` (
  `id` int NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `period_code` varchar(20) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `review_deadline` date DEFAULT NULL,
  `status` enum('open','closed','archived') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appraisal_periods`
--

INSERT INTO `appraisal_periods` (`id`, `period_name`, `period_code`, `start_date`, `end_date`, `review_deadline`, `status`, `created_at`) VALUES
(1, 'Annual 2025', 'AP2025', '2025-01-01', '2025-12-31', '2026-01-31', 'open', '2026-05-23 13:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_ratings`
--

CREATE TABLE `appraisal_ratings` (
  `id` int NOT NULL,
  `appraisal_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `rating` decimal(3,1) NOT NULL,
  `comments` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `asset_category_id` int NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `description` text,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `depreciation_start_date` date DEFAULT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT '0.00',
  `book_value` decimal(15,2) DEFAULT NULL,
  `status` enum('new','in_use','under_maintenance','deprecated','disposed') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) DEFAULT NULL,
  `description` text,
  `depreciation_rate` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_depreciation`
--

CREATE TABLE `asset_depreciation` (
  `id` int NOT NULL,
  `asset_id` int NOT NULL,
  `depreciation_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `depreciation_method` enum('straight_line','reducing_balance') NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `attendance_status` enum('present','absent','late','half_day','on_leave') DEFAULT 'present',
  `remarks` text,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_management`
--

CREATE TABLE `backup_management` (
  `id` int NOT NULL,
  `backup_name` varchar(200) NOT NULL,
  `backup_type` enum('full','incremental','differential','snapshot') DEFAULT 'full',
  `backup_path` varchar(500) NOT NULL,
  `backup_size` bigint DEFAULT NULL,
  `compression_type` enum('none','gzip','zip','7z') DEFAULT 'gzip',
  `backup_status` enum('in_progress','completed','failed','cancelled') DEFAULT 'in_progress',
  `backup_tables` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` varchar(50) DEFAULT NULL,
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('draft','completed','adjusted') DEFAULT 'draft',
  `reconciled_by` int DEFAULT NULL,
  `reconciliation_date` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int NOT NULL,
  `budget_code` varchar(50) NOT NULL,
  `cost_center_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `budget_period` enum('annual','semester','quarterly','monthly') DEFAULT 'annual',
  `budget_start_date` date NOT NULL,
  `budget_end_date` date NOT NULL,
  `total_budget` decimal(15,2) NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `status` enum('draft','submitted','approved','active','closed','archived') DEFAULT 'draft',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_lines`
--

CREATE TABLE `budget_lines` (
  `id` int NOT NULL,
  `budget_id` int NOT NULL,
  `line_number` int NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `account_code` varchar(50) DEFAULT NULL,
  `budgeted_amount` decimal(15,2) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_records`
--

CREATE TABLE `budget_records` (
  `id` int NOT NULL,
  `budget_code` varchar(50) NOT NULL,
  `budget_name` varchar(200) NOT NULL,
  `budget_category` enum('Academic','Administrative','Operations','Capital Projects','Research','Student Services','Staff Development','Maintenance','Other') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `currency` varchar(10) DEFAULT 'UGX',
  `status` enum('Draft','Approved','Active','Closed','Cancelled') DEFAULT 'Draft',
  `description` text,
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_settings`
--

CREATE TABLE `bursar_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bursar_settings`
--

INSERT INTO `bursar_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `updated_at`) VALUES
(1, 'institution_name', 'Iganga School of Nursing and Midwifery', 'text', 'Institution full name', 1, '2026-05-23 13:25:49'),
(2, 'institution_short_code', 'ISNM', 'text', 'Institution short code', 1, '2026-05-23 13:25:49'),
(3, 'institution_address', 'Iganga, Uganda', 'text', 'Institution address', 1, '2026-05-23 13:25:49'),
(4, 'institution_phone', '+256-701-000-000', 'text', 'Institution contact phone', 1, '2026-05-23 13:25:49'),
(5, 'institution_email', 'info@igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution email', 1, '2026-05-23 13:25:49'),
(6, 'institution_website', 'www.igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution website', 1, '2026-05-23 13:25:49'),
(7, 'currency_symbol', 'UGX', 'text', 'Currency symbol', 1, '2026-05-23 13:25:49'),
(8, 'currency_code', 'UGX', 'text', 'Currency code', 1, '2026-05-23 13:25:49'),
(9, 'decimal_places', '2', 'number', 'Decimal places for currency', 1, '2026-05-23 13:25:49'),
(10, 'current_academic_year', '2025/2026', 'text', 'Current academic year', 1, '2026-05-23 13:25:49'),
(11, 'enable_mobile_money_integration', 'true', 'boolean', 'Enable mobile money integration', 1, '2026-05-23 13:25:49'),
(12, 'enable_bank_integration', 'false', 'boolean', 'Enable bank integration', 1, '2026-05-23 13:25:49'),
(13, 'receipt_prefix', 'REC', 'text', 'Receipt number prefix', 1, '2026-05-23 13:25:49'),
(14, 'invoice_prefix', 'INV', 'text', 'Invoice number prefix', 1, '2026-05-23 13:25:49'),
(15, 'grace_period_days', '7', 'number', 'Grace period before penalty in days', 1, '2026-05-23 13:25:49'),
(16, 'require_payment_verification', 'true', 'boolean', 'Require verification for bank deposits', 1, '2026-05-23 13:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `bursar_users`
--

CREATE TABLE `bursar_users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('bursar','accounts_assistant','auditor') DEFAULT 'bursar',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bursar_users`
--

INSERT INTO `bursar_users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', 'placeholder', 'Bursar', '+256784383085', 'bursar', 'active', NULL, '2026-05-23 13:25:49', '2026-05-23 13:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `cache_management`
--

CREATE TABLE `cache_management` (
  `id` int NOT NULL,
  `cache_key` varchar(255) NOT NULL,
  `cache_type` enum('system','user','data','reports','templates','dashboard','session') DEFAULT 'system',
  `cache_data` longtext,
  `expiry_time` timestamp NULL DEFAULT ((now() + interval 1 hour)),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashbook`
--

CREATE TABLE `cashbook` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL,
  `transaction_type` enum('cash_in','cash_out') NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `receipt_amount` decimal(15,2) DEFAULT '0.00',
  `payment_amount` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `received_from_or_paid_to` varchar(255) DEFAULT NULL,
  `authorized_by` int DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `sub_type` varchar(100) DEFAULT NULL,
  `description` text,
  `normal_balance` enum('debit','credit') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `sub_type`, `description`, `normal_balance`, `status`, `created_at`) VALUES
(1, '1000', 'Bank Account', 'asset', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(2, '1100', 'Cash', 'asset', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(3, '1200', 'Accounts Receivable', 'asset', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(4, '1500', 'Fixed Assets', 'asset', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(5, '2000', 'Accounts Payable', 'liability', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(6, '2100', 'Staff Salaries Payable', 'liability', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(7, '3000', 'Retained Earnings', 'equity', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(8, '4000', 'Tuition Fee Revenue', 'revenue', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(9, '4100', 'Accommodation Revenue', 'revenue', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(10, '4200', 'Other Fee Revenue', 'revenue', NULL, NULL, 'credit', 'active', '2026-05-23 13:25:49'),
(11, '5000', 'Staff Salaries', 'expense', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(12, '5100', 'Utilities', 'expense', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(13, '5200', 'Supplies', 'expense', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(14, '5300', 'Maintenance', 'expense', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49'),
(15, '5400', 'Miscellaneous', 'expense', NULL, NULL, 'debit', 'active', '2026-05-23 13:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `chemical_inventory`
--

CREATE TABLE `chemical_inventory` (
  `id` int NOT NULL,
  `chemical_code` varchar(50) NOT NULL,
  `chemical_name` varchar(200) NOT NULL,
  `chemical_type` enum('Acid','Base','Solvent','Reagent','Indicator','Other') NOT NULL,
  `cas_number` varchar(50) DEFAULT NULL,
  `hazard_class` enum('Flammable','Corrosive','Toxic','Reactive','Oxidizer','Non-hazardous') DEFAULT 'Non-hazardous',
  `storage_location` varchar(100) DEFAULT NULL,
  `quantity_on_hand` decimal(10,2) NOT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'ml',
  `reorder_level` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `received_by` int NOT NULL,
  `status` enum('In Stock','Low Stock','Expired','Discontinued') DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements`
--

CREATE TABLE `clinical_placements` (
  `id` int NOT NULL,
  `placement_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `placement_site` varchar(200) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Scheduled','In Progress','Completed') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_log`
--

CREATE TABLE `communication_log` (
  `id` int NOT NULL,
  `type` enum('sms','email','overdue_notice','payment_confirmation','announcement') NOT NULL,
  `recipient_type` enum('student','staff','group') NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `recipient_contact` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `status` enum('pending','sent','failed','delivered') DEFAULT 'pending',
  `sent_date` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_records`
--

CREATE TABLE `compliance_records` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `compliance_type` enum('Background Check','Medical Examination','Police Clearance','License Renewal','Certification','Training','Other') NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Valid','Expiring Soon','Expired','Pending') DEFAULT 'Valid',
  `document_file` varchar(500) DEFAULT NULL,
  `notes` text,
  `reminder_sent` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_tracking`
--

CREATE TABLE `compliance_tracking` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `compliance_item` varchar(255) NOT NULL,
  `requirement_type` varchar(100) DEFAULT NULL,
  `required_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','overdue') DEFAULT 'not_started',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` int NOT NULL,
  `cost_center_code` varchar(20) NOT NULL,
  `cost_center_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `budget_owner` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cost_centers`
--

INSERT INTO `cost_centers` (`id`, `cost_center_code`, `cost_center_name`, `department`, `manager_name`, `budget_owner`, `status`, `created_at`) VALUES
(1, 'CC001', 'Administration', 'General', NULL, NULL, 'active', '2026-05-23 13:25:49'),
(2, 'CC002', 'Nursing Department', 'Academic', NULL, NULL, 'active', '2026-05-23 13:25:49'),
(3, 'CC003', 'Midwifery Department', 'Academic', NULL, NULL, 'active', '2026-05-23 13:25:49'),
(4, 'CC004', 'Facilities', 'Support', NULL, NULL, 'active', '2026-05-23 13:25:49'),
(5, 'CC005', 'Library', 'Support', NULL, NULL, 'active', '2026-05-23 13:25:49'),
(6, 'CC006', 'ICT', 'Support', NULL, NULL, 'active', '2026-05-23 13:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `id` int NOT NULL,
  `session_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `counselor_id` int NOT NULL,
  `session_type` enum('Individual','Group','Family','Crisis Intervention') NOT NULL,
  `topic` varchar(200) NOT NULL,
  `scheduled_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','Rescheduled') DEFAULT 'Scheduled',
  `session_notes` text,
  `action_plan` text,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int NOT NULL,
  `lecturer_id` int NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `class_schedule` json DEFAULT NULL,
  `classroom` varchar(50) DEFAULT NULL,
  `total_students` int DEFAULT '0',
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `assigned_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_registrations`
--

CREATE TABLE `course_registrations` (
  `id` int NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `status` enum('Registered','Dropped','Completed') DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_updates`
--

CREATE TABLE `dashboard_updates` (
  `id` int NOT NULL,
  `update_type` enum('new_feature','system_update','data_refresh','alert','maintenance') NOT NULL,
  `update_title` varchar(200) NOT NULL,
  `update_description` text,
  `update_data` json DEFAULT NULL,
  `target_users` json DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_sync_status`
--

CREATE TABLE `data_sync_status` (
  `id` int NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `last_sync` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('success','failed','in_progress','pending') DEFAULT 'pending',
  `sync_details` text,
  `records_synced` int DEFAULT '0',
  `error_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departmental_budgets`
--

CREATE TABLE `departmental_budgets` (
  `id` int NOT NULL,
  `budget_id` int NOT NULL,
  `department` varchar(100) NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL,
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('active','exhausted','closed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_news`
--

CREATE TABLE `director_news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_actions`
--

CREATE TABLE `disciplinary_actions` (
  `id` int NOT NULL,
  `disciplinary_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `incident_id` int DEFAULT NULL,
  `action_type` enum('verbal_warning','written_warning','final_warning','suspension','dismissal','other') NOT NULL,
  `action_date` date NOT NULL,
  `reason` text NOT NULL,
  `action_description` longtext,
  `duration_days` int DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `action_letter_date` date DEFAULT NULL,
  `action_letter_file` varchar(255) DEFAULT NULL,
  `staff_acknowledgment` tinyint(1) DEFAULT '0',
  `acknowledgment_date` timestamp NULL DEFAULT NULL,
  `appeal_allowed` tinyint(1) DEFAULT '0',
  `appeal_deadline` date DEFAULT NULL,
  `status` enum('issued','acknowledged','appealed','closed') DEFAULT 'issued',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_records`
--

CREATE TABLE `disciplinary_records` (
  `id` int NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `reported_date` date NOT NULL,
  `incident_type` enum('Absence','Lateness','Misconduct','Insubordination','Negligence','Harassment','Theft','Fraud','Other') NOT NULL,
  `description` text NOT NULL,
  `severity` enum('Minor','Moderate','Major','Critical') NOT NULL,
  `witnesses` text,
  `evidence_files` text,
  `action_taken` enum('Verbal Warning','Written Warning','Suspension','Demotion','Termination','Other') NOT NULL,
  `action_details` text,
  `reporter_id` int DEFAULT NULL,
  `status` enum('Pending','Under Investigation','Resolved','Appealed','Closed') DEFAULT 'Pending',
  `resolution_date` date DEFAULT NULL,
  `resolution_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_generation_log`
--

CREATE TABLE `document_generation_log` (
  `id` int NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_id` varchar(50) DEFAULT NULL,
  `generated_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_templates`
--

CREATE TABLE `document_templates` (
  `id` int NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_type` enum('transcript','certificate','receipt','invoice','payslip','report','timetable','exam_schedule','leave_form','performance_review','id_card','contract') NOT NULL,
  `template_content` longtext NOT NULL,
  `template_variables` json DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `document_templates`
--

INSERT INTO `document_templates` (`id`, `template_name`, `template_type`, `template_content`, `template_variables`, `is_default`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Standard Transcript', 'transcript', '<html><body><h1>Academic Transcript</h1><table border=\"1\"><tr><td>Student Name:</td><td>{{student_name}}</td></tr><tr><td>Student ID:</td><td>{{student_id}}</td></tr></table></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(2, 'Professional Certificate', 'certificate', '<html><body><h1>Certificate of Completion</h1><p>This is to certify that <strong>{{student_name}}</strong> has successfully completed the <strong>{{program}}</strong> program.</p></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(3, 'Standard Receipt', 'receipt', '<html><body><h1>Payment Receipt</h1><table border=\"1\"><tr><td>Receipt No:</td><td>{{receipt_number}}</td></tr><tr><td>Amount:</td><td>{{amount}}</td></tr></table></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(4, 'Payslip Template', 'payslip', '<html><body><h1>Payslip</h1><table border=\"1\"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Net Salary:</td><td>{{net_salary}}</td></tr><tr><td>Tax:</td><td>{{tax}}</td></tr><tr><td>Allowance:</td><td>{{allowance}}</td></tr></table></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(5, 'Student ID Card', 'id_card', '<html><body><h1>Student ID Card</h1><div style=\"border: 2px solid #000; padding: 20px; width: 300px;\"><p><strong>Name:</strong> {{student_name}}</p><p><strong>ID:</strong> {{student_id}}</p><p><strong>Program:</strong> {{program}}</p></div></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(6, 'Leave Request Form', 'leave_form', '<html><body><h1>Leave Request Form</h1><table border=\"1\"><tr><td>Employee Name:</td><td>{{employee_name}}</td></tr><tr><td>Leave Type:</td><td>{{leave_type}}</td></tr><tr><td>Duration:</td><td>{{duration}}</td></tr><tr><td>Reason:</td><td>{{reason}}</td></tr></table></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(7, 'Performance Review', 'performance_review', '<html><body><h1>Performance Review</h1><table border=\"1\"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Period:</td><td>{{review_period}}</td></tr><tr><td>Rating:</td><td>{{rating}}</td></tr><tr><td>Comments:</td><td>{{comments}}</td></tr></table></body></html>', NULL, 1, NULL, '2026-06-14 19:50:36', '2026-06-14 19:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster`
--

CREATE TABLE `duty_roster` (
  `id` int NOT NULL,
  `roster_id` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `duty_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duty_type` varchar(100) DEFAULT NULL,
  `unit_assigned` varchar(255) DEFAULT NULL,
  `supervisor` int DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_rosters`
--

CREATE TABLE `duty_rosters` (
  `id` int NOT NULL,
  `roster_number` varchar(50) NOT NULL,
  `warden_id` int NOT NULL,
  `duty_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Evening','Night') NOT NULL,
  `duty_area` enum('Hostel A','Hostel B','Common Areas','Perimeter','Full Campus') NOT NULL,
  `status` enum('Scheduled','On Duty','Completed','Absent','Replaced') DEFAULT 'Scheduled',
  `replacement_warden` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications_queue`
--

CREATE TABLE `email_notifications_queue` (
  `id` int NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `email_content` longtext NOT NULL,
  `email_type` enum('notification','report','alert','reminder','system') DEFAULT 'notification',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','sent','failed','cancelled') DEFAULT 'pending',
  `send_attempts` int DEFAULT '0',
  `scheduled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int NOT NULL,
  `contact_name` varchar(200) NOT NULL,
  `contact_type` enum('Police','Fire','Ambulance','Hospital','School Administration','Security Chief','Other') NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `alternative_phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `priority` enum('Primary','Secondary','Tertiary') DEFAULT 'Primary',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_contracts`
--

CREATE TABLE `employment_contracts` (
  `id` int NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `contract_start_date` date NOT NULL,
  `contract_end_date` date NOT NULL,
  `contract_type` enum('permanent','fixed_term','probation') DEFAULT 'permanent',
  `contract_duration_months` int DEFAULT NULL,
  `renewal_reminder_date` date DEFAULT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `terms_and_conditions` text,
  `contract_status` enum('active','expiring','expired','renewed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_details`
--

CREATE TABLE `employment_details` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `job_category` varchar(100) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `sub_department` varchar(100) DEFAULT NULL,
  `staff_category_id` int DEFAULT NULL,
  `employment_type` enum('permanent','contract','temporary','part_time') DEFAULT 'permanent',
  `grade` varchar(20) DEFAULT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `reports_to` int DEFAULT NULL,
  `employment_start_date` date NOT NULL,
  `employment_end_date` date DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `contract_renewal_date` date DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `office_contact` varchar(20) DEFAULT NULL,
  `professional_license` varchar(100) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `license_issuing_body` varchar(255) DEFAULT NULL,
  `nursing_council_number` varchar(50) DEFAULT NULL,
  `council_number_expiry` date DEFAULT NULL,
  `qualification_level` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `years_of_experience` int DEFAULT NULL,
  `previous_employer` varchar(255) DEFAULT NULL,
  `previous_position` varchar(255) DEFAULT NULL,
  `reason_for_leaving` text,
  `status` enum('active','inactive','on_leave','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

CREATE TABLE `error_logs` (
  `id` int NOT NULL,
  `error_message` text NOT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `file_affected` varchar(255) DEFAULT NULL,
  `line_number` int DEFAULT NULL,
  `stack_trace` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_records`
--

CREATE TABLE `examination_records` (
  `id` int NOT NULL,
  `exam_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `exam_type` enum('Mid-Semester','Final','Supplementary') NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `workflow_id` int DEFAULT NULL,
  `continuous_assessment_marks` decimal(5,2) DEFAULT '0.00',
  `final_exam_marks` decimal(5,2) DEFAULT '0.00',
  `total_marks_calculated` decimal(5,2) GENERATED ALWAYS AS ((`continuous_assessment_marks` + `final_exam_marks`)) STORED,
  `lecturer_id` int DEFAULT NULL,
  `hod_id` int DEFAULT NULL,
  `grade_status` enum('Draft','Submitted','Under Review','Approved','Published','Rejected') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `examination_records`
--
DELIMITER $$
CREATE TRIGGER `log_grade_change_trigger` AFTER UPDATE ON `examination_records` FOR EACH ROW BEGIN
    IF OLD.grade != NEW.grade OR OLD.continuous_assessment_marks != NEW.continuous_assessment_marks OR OLD.final_exam_marks != NEW.final_exam_marks THEN
        INSERT INTO grade_change_history (
            workflow_number,
            examination_record_id,
            changed_by,
            previous_grade,
            new_grade,
            previous_ca_marks,
            new_ca_marks,
            previous_exam_marks,
            new_exam_marks,
            change_reason
        ) VALUES (
            (SELECT workflow_number FROM grading_approval_workflow WHERE examination_record_id = NEW.id LIMIT 1),
            NEW.id,
            NEW.lecturer_id,
            OLD.grade,
            NEW.grade,
            OLD.continuous_assessment_marks,
            NEW.continuous_assessment_marks,
            OLD.final_exam_marks,
            NEW.final_exam_marks,
            'Grade updated via dashboard'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--

CREATE TABLE `expenditures` (
  `id` int NOT NULL,
  `expenditure_code` varchar(50) NOT NULL,
  `cost_center_id` int NOT NULL,
  `budget_id` int DEFAULT NULL,
  `budget_line_id` int DEFAULT NULL,
  `expense_date` date NOT NULL,
  `expense_category` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'UGX',
  `receipt_reference` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `payment_method` enum('cash','cheque','bank_transfer','mobile_money','card') NOT NULL,
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `paid_by` int DEFAULT NULL,
  `paid_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','paid','rejected','cancelled') DEFAULT 'pending',
  `supporting_documents` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_records`
--

CREATE TABLE `expenditure_records` (
  `id` int NOT NULL,
  `expenditure_number` varchar(50) NOT NULL,
  `budget_id` int DEFAULT NULL,
  `expenditure_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `category` enum('Salaries','Utilities','Supplies','Maintenance','Equipment','Travel','Training','Capital Expenditure','Other') NOT NULL,
  `description` text NOT NULL,
  `vendor_name` varchar(200) DEFAULT NULL,
  `payment_method` enum('Cash','Bank Transfer','Cheque','Credit Card','Other') NOT NULL,
  `status` enum('Pending','Approved','Paid','Rejected') DEFAULT 'Pending',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `expense_id` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `expense_category` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','mobile_money') NOT NULL,
  `budget_id` int DEFAULT NULL,
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `receipt_path` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_approvals`
--

CREATE TABLE `expense_approvals` (
  `id` int NOT NULL,
  `expense_id` int NOT NULL,
  `approver_id` int NOT NULL,
  `approval_level` int NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `comments` text,
  `approval_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_accounts`
--

CREATE TABLE `fee_accounts` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) GENERATED ALWAYS AS ((`amount` - `paid_amount`)) STORED,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--

CREATE TABLE `fee_adjustments` (
  `id` int NOT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `adjustment_type` enum('Discount','Waiver','Penalty','Refund','Correction') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `reason` text NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Applied') DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `reminder_type` enum('due_reminder','overdue_reminder','final_notice') NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_message` text,
  `delivery_method` enum('sms','email','both') DEFAULT 'sms',
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_date` timestamp NULL DEFAULT NULL,
  `response_received` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int NOT NULL,
  `fee_code` varchar(50) NOT NULL,
  `fee_name` varchar(200) NOT NULL,
  `fee_category` enum('Tuition','Registration','Library','Laboratory','Clinical','Hostel','Examination','Identity Card','Medical','Sports','Other') NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `due_date` date DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_log`
--

CREATE TABLE `financial_audit_log` (
  `id` int NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_records`
--

CREATE TABLE `financial_records` (
  `id` int NOT NULL,
  `record_type` enum('Collection','Payment','Refund','Adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `description` text,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

CREATE TABLE `financial_reports` (
  `id` int NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('daily_collection','weekly_collection','monthly_collection','debtors_list','revenue_summary','student_statement','budget_vs_actual','trial_balance','income_statement','general_report') NOT NULL,
  `report_period_start` date NOT NULL,
  `report_period_end` date NOT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `report_data` longtext,
  `pdf_path` varchar(255) DEFAULT NULL,
  `excel_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','finalized','archived') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_management`
--

CREATE TABLE `fuel_management` (
  `id` int NOT NULL,
  `fuel_number` varchar(50) NOT NULL,
  `vehicle_id` int NOT NULL,
  `fuel_type` enum('Petrol','Diesel','Electric') DEFAULT 'Diesel',
  `fuel_quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS ((`fuel_quantity` * `unit_cost`)) STORED,
  `fueling_date` date NOT NULL,
  `fueling_station` varchar(200) DEFAULT NULL,
  `odometer_reading` decimal(10,2) DEFAULT NULL,
  `filled_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE `general_ledger` (
  `id` int NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `account_code` varchar(50) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `currency` varchar(10) DEFAULT 'UGX',
  `description` text NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_documents`
--

CREATE TABLE `generated_documents` (
  `id` int NOT NULL,
  `document_type` enum('Transcript','Result Slip','Certificate','Receipt','Payslip','Report','Invoice','Timetable','Exam Schedule','Leave Form','Performance Review') NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `generated_by` int NOT NULL,
  `document_title` varchar(200) NOT NULL,
  `document_content` longtext NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `template_used` int DEFAULT NULL,
  `generation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '0',
  `access_code` varchar(50) DEFAULT NULL,
  `download_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_change_history`
--

CREATE TABLE `grade_change_history` (
  `id` int NOT NULL,
  `workflow_number` varchar(50) NOT NULL,
  `examination_record_id` int NOT NULL,
  `changed_by` int DEFAULT NULL,
  `previous_grade` varchar(5) DEFAULT NULL,
  `new_grade` varchar(5) DEFAULT NULL,
  `previous_ca_marks` decimal(5,2) DEFAULT NULL,
  `new_ca_marks` decimal(5,2) DEFAULT NULL,
  `previous_exam_marks` decimal(5,2) DEFAULT NULL,
  `new_exam_marks` decimal(5,2) DEFAULT NULL,
  `change_reason` text,
  `change_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_scales`
--

CREATE TABLE `grade_scales` (
  `id` int NOT NULL,
  `grade_letter` varchar(5) NOT NULL,
  `grade_point` decimal(3,2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `grade_description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grade_scales`
--

INSERT INTO `grade_scales` (`id`, `grade_letter`, `grade_point`, `min_percentage`, `max_percentage`, `grade_description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'A', 4.00, 80.00, 100.00, 'Excellent', 1, '2026-06-14 19:50:48', '2026-06-14 19:50:48'),
(2, 'B', 3.00, 70.00, 79.99, 'Very Good', 1, '2026-06-14 19:50:48', '2026-06-14 19:50:48'),
(3, 'C', 2.00, 60.00, 69.99, 'Good', 1, '2026-06-14 19:50:48', '2026-06-14 19:50:48'),
(4, 'D', 1.00, 50.00, 59.99, 'Satisfactory', 1, '2026-06-14 19:50:48', '2026-06-14 19:50:48'),
(5, 'F', 0.00, 0.00, 49.99, 'Fail', 1, '2026-06-14 19:50:48', '2026-06-14 19:50:48');

-- --------------------------------------------------------

--
-- Table structure for table `grading_approval_workflow`
--

CREATE TABLE `grading_approval_workflow` (
  `id` int NOT NULL,
  `workflow_number` varchar(50) NOT NULL,
  `examination_record_id` int NOT NULL,
  `current_stage` enum('Lecturer Entry','HOD Review','Registrar Approval','Principal Final Approval','Published','Rejected') DEFAULT 'Lecturer Entry',
  `submitted_by` int DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hod_reviewed_by` int DEFAULT NULL,
  `hod_reviewed_at` timestamp NULL DEFAULT NULL,
  `hod_comments` text,
  `hod_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `registrar_approved_by` int DEFAULT NULL,
  `registrar_approved_at` timestamp NULL DEFAULT NULL,
  `registrar_comments` text,
  `registrar_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `principal_approved_by` int DEFAULT NULL,
  `principal_approved_at` timestamp NULL DEFAULT NULL,
  `principal_comments` text,
  `principal_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `published_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_notifications`
--

CREATE TABLE `grading_notifications` (
  `id` int NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `workflow_number` varchar(50) NOT NULL,
  `recipient_id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `notification_type` enum('Grade Submitted','HOD Review Required','Registrar Approval Required','Principal Approval Required','Grade Published','Grade Rejected','Grade Modified') NOT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_incidents`
--

CREATE TABLE `health_incidents` (
  `id` int NOT NULL,
  `incident_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `incident_type` enum('Illness','Injury','Accident','Allergic Reaction','Other') NOT NULL,
  `symptoms` text NOT NULL,
  `severity` enum('Minor','Moderate','Severe','Critical') DEFAULT 'Moderate',
  `incident_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(200) DEFAULT NULL,
  `action_taken` text,
  `treatment_given` text,
  `referred_to` varchar(200) DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT '0',
  `parent_notification_time` timestamp NULL DEFAULT NULL,
  `status` enum('Reported','Under Observation','Resolved','Referred','Closed') DEFAULT 'Reported',
  `reported_by` int NOT NULL,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_allocations`
--

CREATE TABLE `hostel_allocations` (
  `id` int NOT NULL,
  `allocation_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `room_id` int NOT NULL,
  `allocation_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Ended','Transferred') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_management`
--

CREATE TABLE `hostel_management` (
  `id` int NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int NOT NULL,
  `occupied` int DEFAULT '0',
  `room_type` enum('Single','Double','Dormitory') NOT NULL,
  `gender` enum('Male','Female','Mixed') NOT NULL,
  `status` enum('Available','Occupied','Under Maintenance') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_activity_logs`
--

CREATE TABLE `hr_activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `old_values` longtext,
  `new_values` longtext,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `status` enum('success','failure') DEFAULT 'success',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_leave_summary`
-- (See below for the actual view)
--
CREATE TABLE `hr_leave_summary` (
`leave_type` enum('Annual','Sick','Maternity','Paternity','Study','Compassionate','Unpaid')
,`total_requests` bigint
,`approved` decimal(23,0)
,`pending` decimal(23,0)
,`rejected` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_performance_summary`
-- (See below for the actual view)
--
CREATE TABLE `hr_performance_summary` (
`staff_id` int
,`full_name` varchar(100)
,`position` varchar(100)
,`department` varchar(100)
,`role_name` varchar(100)
,`avg_performance_score` decimal(5,2)
,`latest_rating` enum('Outstanding','Excellent','Good','Satisfactory','Needs Improvement')
,`total_leaves` bigint
,`attendance_rate` decimal(31,5)
,`training_completed` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `hr_reports`
--

CREATE TABLE `hr_reports` (
  `id` int NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('staff_list','attendance_report','leave_summary','turnover_analysis','salary_summary','training_report','performance_report','general_report') NOT NULL,
  `report_period_start` date DEFAULT NULL,
  `report_period_end` date DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `report_data` longtext,
  `pdf_path` varchar(255) DEFAULT NULL,
  `excel_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','finalized','archived') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_settings`
--

CREATE TABLE `hr_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hr_settings`
--

INSERT INTO `hr_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `updated_at`) VALUES
(1, 'institution_name', 'Iganga School of Nursing and Midwifery', 'text', 'Institution name', 1, '2026-05-23 13:28:00'),
(2, 'institution_phone', '+256-701-000-000', 'text', 'Institution phone', 1, '2026-05-23 13:28:00'),
(3, 'institution_email', 'hr@igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution email', 1, '2026-05-23 13:28:00'),
(4, 'currency', 'UGX', 'text', 'Currency for salaries', 1, '2026-05-23 13:28:00'),
(5, 'payroll_frequency', 'monthly', 'text', 'Payroll processing frequency', 1, '2026-05-23 13:28:00'),
(6, 'banking_integration', 'false', 'boolean', 'Enable banking integration', 1, '2026-05-23 13:28:00'),
(7, 'leave_accrual_method', 'annual', 'text', 'Leave accrual method', 1, '2026-05-23 13:28:00'),
(8, 'contract_renewal_notice_days', '30', 'number', 'Days notice for contract renewal', 1, '2026-05-23 13:28:00'),
(9, 'license_renewal_notice_days', '60', 'number', 'Days notice for license renewal', 1, '2026-05-23 13:28:00');

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_staff_by_department`
-- (See below for the actual view)
--
CREATE TABLE `hr_staff_by_department` (
`department` varchar(100)
,`total_staff` bigint
,`active_staff` decimal(23,0)
,`inactive_staff` decimal(23,0)
,`avg_years_of_service` decimal(16,8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `hr_staff_search_view`
-- (See below for the actual view)
--
CREATE TABLE `hr_staff_search_view` (
`id` int
,`staff_id` varchar(50)
,`full_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
,`position` varchar(100)
,`department` varchar(100)
,`role_name` varchar(100)
,`status` enum('Active','Inactive','On Leave','Suspended')
,`hire_date` date
,`last_login` timestamp
,`account_status` varchar(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `hr_users`
--

CREATE TABLE `hr_users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('hr_manager','hr_assistant','director','head_of_department','payroll_officer') DEFAULT 'hr_manager',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hr_users`
--

INSERT INTO `hr_users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'hr@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$hr@isnmHashedPasswordValue', 'HR Manager', NULL, 'hr_manager', 'active', NULL, '2026-05-23 13:27:59', '2026-05-23 13:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` int NOT NULL,
  `incident_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `incident_category` varchar(100) DEFAULT NULL,
  `incident_description` longtext NOT NULL,
  `severity` enum('minor','moderate','severe') DEFAULT 'moderate',
  `witnesses` text,
  `reported_by` int DEFAULT NULL,
  `reported_date` date DEFAULT NULL,
  `investigation_status` enum('open','under_investigation','closed') DEFAULT 'open',
  `investigation_findings` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_scheduling`
--

CREATE TABLE `interview_scheduling` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `interview_round` int NOT NULL,
  `interview_type` enum('phone','panel','technical','final') DEFAULT 'panel',
  `interview_date` date NOT NULL,
  `interview_time` time DEFAULT NULL,
  `interview_location` varchar(255) DEFAULT NULL,
  `interview_panel` longtext,
  `interview_questions` text,
  `conducted_by` int DEFAULT NULL,
  `interview_notes` text,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `recommendation` enum('proceed','reject','hold') DEFAULT 'hold',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_category` enum('Office Supplies','Laboratory Equipment','Medical Supplies','Furniture','Computers','Books','Uniforms','Food','Cleaning Supplies','Transport','Security','Hospitality','Other') NOT NULL,
  `description` text,
  `department` varchar(100) DEFAULT 'General',
  `report_to` varchar(100) DEFAULT 'HR Manager',
  `unit_of_measure` varchar(50) NOT NULL,
  `quantity_on_hand` int DEFAULT '0',
  `reorder_level` int DEFAULT '10',
  `unit_cost` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `location` varchar(100) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Discontinued') DEFAULT 'In Stock',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_code`, `item_name`, `item_category`, `description`, `department`, `report_to`, `unit_of_measure`, `quantity_on_hand`, `reorder_level`, `unit_cost`, `currency`, `location`, `supplier`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'NUR001', 'Surgical Masks', 'Medical Supplies', 'Disposable surgical masks for patient care', 'Nursing', 'HR Manager', 'boxes', 120, 15, 12.50, 'UGX', 'Nursing Store', 'MedSupply Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(2, 'MID001', 'Midwifery Kits', 'Medical Supplies', 'Delivery and emergency midwifery kits', 'Midwifery', 'HR Manager', 'sets', 35, 5, 105.00, 'UGX', 'Midwifery Store', 'HealthEquip Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(3, 'SCK001', 'Patient First Aid Kits', 'Medical Supplies', 'Portable first aid kits for sickbay emergencies', 'Sickbay', 'School Principal', 'kits', 18, 3, 75.00, 'UGX', 'Sickbay Storage', 'CarePlus Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(4, 'LIB001', 'Reference Books', 'Books', 'Professional reference books for library use', 'Library', 'School Librarian', 'pcs', 210, 20, 45.00, 'UGX', 'Library Shelves', 'EduBooks Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(5, 'ICT001', 'Network Switch', 'Computers', 'Managed network switch for campus ICT infrastructure', 'ICT', 'Director ICT', 'pcs', 8, 2, 420.00, 'UGX', 'ICT Server Room', 'TechNet Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(6, 'SEC001', 'Security Badges', 'Security', 'Access control badges for security staff', 'Security', 'Director General', 'pcs', 120, 20, 5.00, 'UGX', 'Security Office', 'SecureID Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(7, 'BRS001', 'Official Ledger Books', 'Office Supplies', 'Ledgers for bursar financial records', 'Bursar', 'School Bursar', 'pcs', 60, 10, 18.00, 'UGX', 'Bursar Office', 'OfficeMate Ltd', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40'),
(8, 'HRM001', 'Employee File Folders', 'Office Supplies', 'Folders for HR employee records', 'HR', 'HR Manager', 'pcs', 220, 30, 2.20, 'UGX', 'HR Office', 'Stationery Co', 'In Stock', NULL, '2026-06-14 19:50:40', '2026-06-14 19:50:40');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_category` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_value` decimal(15,2) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('available','issued','damaged','disposed') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_reports`
--

CREATE TABLE `inventory_reports` (
  `id` int NOT NULL,
  `report_number` varchar(50) NOT NULL,
  `inventory_id` int NOT NULL,
  `reported_by` int DEFAULT NULL,
  `report_to` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT 'General',
  `report_type` enum('Low Stock','Damage','Request','Adjustment','Transfer','Other') NOT NULL DEFAULT 'Request',
  `report_notes` text,
  `request_status` enum('Open','In Review','Resolved','Closed') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `inventory_id` int NOT NULL,
  `transaction_type` enum('Purchase','Sale','Issue','Return','Adjustment','Transfer','Damage','Loss') NOT NULL,
  `transaction_date` date NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `reason` text,
  `performed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_records`
--

CREATE TABLE `invoice_records` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `status` enum('Draft','Sent','Partial','Paid','Overdue','Cancelled') DEFAULT 'Draft',
  `payment_terms` text,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_infrastructure`
--

CREATE TABLE `it_infrastructure` (
  `id` int NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_type` enum('Computer','Server','Network Device','Printer','Projector','Software License','Other') NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `specification` text,
  `location` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Operational','Under Maintenance','Out of Service','Retired') DEFAULT 'Operational',
  `assigned_to` int DEFAULT NULL,
  `maintained_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `vacancy_id` int NOT NULL,
  `applicant_first_name` varchar(100) NOT NULL,
  `applicant_last_name` varchar(100) NOT NULL,
  `applicant_email` varchar(255) NOT NULL,
  `applicant_phone` varchar(20) DEFAULT NULL,
  `application_date` date NOT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `cover_letter` text,
  `qualifications` text,
  `years_of_experience` int DEFAULT NULL,
  `current_employer` varchar(255) DEFAULT NULL,
  `notice_period_days` int DEFAULT NULL,
  `application_status` enum('received','reviewing','shortlisted','rejected','interviewed','offered','hired','withdrawn') DEFAULT 'received',
  `shortlist_date` date DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_feedback` text,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `offer_date` date DEFAULT NULL,
  `offer_accepted` tinyint(1) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `id` int NOT NULL,
  `offer_number` varchar(50) NOT NULL,
  `application_id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `offer_date` date NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `salary_offered` decimal(15,2) DEFAULT NULL,
  `salary_currency` varchar(3) DEFAULT 'UGX',
  `contract_type` varchar(50) DEFAULT NULL,
  `contract_duration_months` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `benefits_details` text,
  `employment_terms` text,
  `offer_status` enum('sent','accepted','rejected','withdrawn') DEFAULT 'sent',
  `acceptance_date` date DEFAULT NULL,
  `response_deadline` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_vacancies`
--

CREATE TABLE `job_vacancies` (
  `id` int NOT NULL,
  `vacancy_code` varchar(50) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `position_type` enum('internal','external') DEFAULT 'external',
  `number_of_positions` int NOT NULL,
  `job_description` longtext,
  `required_qualifications` text,
  `required_experience` text,
  `salary_range_min` decimal(15,2) DEFAULT NULL,
  `salary_range_max` decimal(15,2) DEFAULT NULL,
  `salary_currency` varchar(3) DEFAULT 'UGX',
  `posting_date` date NOT NULL,
  `closing_date` date NOT NULL,
  `job_benefits` text,
  `reporting_to` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('open','closed','filled','cancelled') DEFAULT 'open',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_chemical_inventory`
--

CREATE TABLE `lab_chemical_inventory` (
  `id` int NOT NULL,
  `chemical_id` varchar(50) NOT NULL,
  `chemical_name` varchar(255) NOT NULL,
  `cas_number` varchar(50) DEFAULT NULL,
  `chemical_formula` varchar(100) DEFAULT NULL,
  `hazard_classification` varchar(100) DEFAULT NULL,
  `storage_requirements` text,
  `quantity_on_hand` decimal(15,2) DEFAULT NULL,
  `unit_of_measure` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `msds_file` varchar(500) DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired') DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

CREATE TABLE `lab_equipment` (
  `id` int NOT NULL,
  `equipment_id` varchar(50) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `equipment_type` enum('Microscope','Centrifuge','Autoclave','Spectrophotometer','PCR','Incubator','Refrigerator','Freezer','Other') NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `calibration_date` date DEFAULT NULL,
  `next_calibration_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Operational','Maintenance','Repair','Retired') DEFAULT 'Operational',
  `last_serviced_by` varchar(255) DEFAULT NULL,
  `service_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_maintenance`
--

CREATE TABLE `lab_equipment_maintenance` (
  `id` int NOT NULL,
  `maintenance_number` varchar(50) NOT NULL,
  `equipment_id` int NOT NULL,
  `equipment_name` varchar(200) NOT NULL,
  `maintenance_type` enum('Preventive','Corrective','Calibration','Inspection','Repair') NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `technician_id` int NOT NULL,
  `maintenance_description` text,
  `parts_used` text,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','Overdue') DEFAULT 'Scheduled',
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_experiments`
--

CREATE TABLE `lab_experiments` (
  `id` int NOT NULL,
  `experiment_id` varchar(50) NOT NULL,
  `experiment_name` varchar(255) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `experiment_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `students_enrolled` int DEFAULT NULL,
  `students_completed` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `lab_technician_id` int DEFAULT NULL,
  `equipment_used` text,
  `reagents_used` text,
  `observations` text,
  `results` text,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_inventory`
--

CREATE TABLE `lab_inventory` (
  `id` int NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_category` enum('Reagent','Chemical','Consumable','Glassware','Plasticware','Media','Antibody','Enzyme') NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `catalog_number` varchar(100) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `unit_of_measure` varchar(50) DEFAULT NULL,
  `quantity_on_hand` decimal(15,2) DEFAULT '0.00',
  `reorder_level` decimal(15,2) DEFAULT '0.00',
  `storage_location` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Quarantine') DEFAULT 'In Stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_safety_records`
--

CREATE TABLE `lab_safety_records` (
  `id` int NOT NULL,
  `safety_number` varchar(50) NOT NULL,
  `lab_id` int NOT NULL,
  `inspection_type` enum('Safety Inspection','Equipment Check','Chemical Safety','Fire Safety','General Inspection') NOT NULL,
  `inspection_date` date NOT NULL,
  `inspector_id` int NOT NULL,
  `safety_score` decimal(5,2) DEFAULT NULL,
  `overall_status` enum('Excellent','Good','Fair','Poor','Critical') DEFAULT 'Good',
  `findings` text,
  `hazards_identified` text,
  `corrective_actions` text,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_skills_sessions`
--

CREATE TABLE `lab_skills_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(50) NOT NULL,
  `session_title` varchar(255) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `target_department` enum('Nursing','Midwifery','Both') DEFAULT 'Both',
  `year_of_study` int DEFAULT NULL,
  `students_expected` int DEFAULT NULL,
  `students_attended` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `instructor_name` varchar(255) DEFAULT NULL,
  `equipment_used` text,
  `materials_used` text,
  `pre_test_score` decimal(5,2) DEFAULT NULL,
  `post_test_score` decimal(5,2) DEFAULT NULL,
  `session_status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `evaluation_notes` text,
  `completed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balance`
--

CREATE TABLE `leave_balance` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `total_days` int NOT NULL,
  `used_days` decimal(5,1) DEFAULT '0.0',
  `remaining_days` decimal(5,1) DEFAULT NULL,
  `carried_forward_days` decimal(5,1) DEFAULT '0.0',
  `last_updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int NOT NULL,
  `leave_request_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `number_of_days` decimal(5,1) DEFAULT NULL,
  `reason` text,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `approval_comments` text,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int NOT NULL,
  `leave_type_name` varchar(100) NOT NULL,
  `leave_type_code` varchar(20) DEFAULT NULL,
  `days_per_year` int NOT NULL,
  `is_paid` tinyint(1) DEFAULT '1',
  `requires_approval` tinyint(1) DEFAULT '1',
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `leave_type_name`, `leave_type_code`, `days_per_year`, `is_paid`, `requires_approval`, `description`, `status`, `created_at`) VALUES
(1, 'Annual Leave', 'AL', 20, 1, 1, NULL, 'active', '2026-05-23 13:27:59'),
(2, 'Sick Leave', 'SL', 10, 1, 1, NULL, 'active', '2026-05-23 13:27:59'),
(3, 'Maternity Leave', 'ML', 60, 1, 1, NULL, 'active', '2026-05-23 13:27:59'),
(4, 'Paternity Leave', 'PL', 5, 1, 1, NULL, 'active', '2026-05-23 13:27:59'),
(5, 'Study Leave', 'STL', 5, 0, 1, NULL, 'active', '2026-05-23 13:27:59'),
(6, 'Bereavement Leave', 'BL', 3, 1, 1, NULL, 'active', '2026-05-23 13:27:59'),
(7, 'Unpaid Leave', 'UL', 0, 0, 1, NULL, 'active', '2026-05-23 13:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int NOT NULL,
  `book_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `editor` varchar(255) DEFAULT NULL,
  `edition` varchar(50) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `issn` varchar(20) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `publication_place` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `call_number` varchar(50) DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(100) DEFAULT NULL,
  `condition_status` enum('New','Good','Fair','Poor','Damaged') DEFAULT 'Good',
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'UGX',
  `language` varchar(50) DEFAULT 'English',
  `pages` int DEFAULT NULL,
  `description` text,
  `keywords` text,
  `cover_image` varchar(500) DEFAULT NULL,
  `digital_copy_path` varchar(500) DEFAULT NULL,
  `status` enum('Available','Borrowed','Reserved','Lost','On Order','Archiv') DEFAULT 'Available',
  `added_by` int DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_borrowing`
--

CREATE TABLE `library_borrowing` (
  `id` int NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `book_id` int NOT NULL,
  `borrower_type` enum('Student','Staff','External') NOT NULL,
  `borrower_id` int DEFAULT NULL,
  `borrower_name` varchar(255) DEFAULT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `return_status` enum('Borrowed','Returned','Overdue','Lost') DEFAULT 'Borrowed',
  `late_fee` decimal(10,2) DEFAULT '0.00',
  `fine_paid` tinyint(1) DEFAULT '0',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_digital_resources`
--

CREATE TABLE `library_digital_resources` (
  `id` int NOT NULL,
  `resource_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `resource_type` enum('Ebook','Journal','Video','Audio','Database','Article') NOT NULL,
  `author_creator` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size_mb` decimal(10,2) DEFAULT NULL,
  `access_level` enum('Public','Members Only','Restricted') DEFAULT 'Members Only',
  `description` text,
  `subject_keywords` text,
  `added_by` int DEFAULT NULL,
  `added_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

CREATE TABLE `library_fines` (
  `id` int NOT NULL,
  `fine_id` varchar(50) NOT NULL,
  `transaction_id` int DEFAULT NULL,
  `member_id` int NOT NULL,
  `fine_type` enum('Overdue','Damage','Lost','Reservation') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'UGX',
  `description` text,
  `waived` tinyint(1) DEFAULT '0',
  `waived_by` int DEFAULT NULL,
  `waived_date` timestamp NULL DEFAULT NULL,
  `paid` tinyint(1) DEFAULT '0',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_management`
--

CREATE TABLE `library_management` (
  `id` int NOT NULL,
  `book_id` varchar(50) NOT NULL,
  `book_title` varchar(200) NOT NULL,
  `author` varchar(200) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `publication_year` int DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(50) DEFAULT NULL,
  `status` enum('Available','Borrowed','Reserved','Lost','Under Repair') DEFAULT 'Available',
  `added_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_members`
--

CREATE TABLE `library_members` (
  `id` int NOT NULL,
  `member_id` varchar(50) NOT NULL,
  `member_type` enum('Student','Staff','External') NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `membership_expiry` date DEFAULT NULL,
  `max_books_allowed` int DEFAULT '3',
  `current_books_borrowed` int DEFAULT '0',
  `status` enum('Active','Inactive','Suspended','Expired') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_transactions`
--

CREATE TABLE `library_transactions` (
  `id` int NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `book_id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `transaction_type` enum('Borrow','Return','Reserve','Renew') NOT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Active','Returned','Overdue','Lost') DEFAULT 'Active',
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_tracking`
--

CREATE TABLE `meal_tracking` (
  `id` int NOT NULL,
  `meal_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Snack') NOT NULL,
  `meal_date` date NOT NULL,
  `meal_served` tinyint(1) DEFAULT '0',
  `meal_skipped` tinyint(1) DEFAULT '0',
  `skip_reason` varchar(200) DEFAULT NULL,
  `special_dietary_requirements` text,
  `allergies_noted` text,
  `served_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_antenatal_care`
--

CREATE TABLE `midwifery_antenatal_care` (
  `id` int NOT NULL,
  `record_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_age` int DEFAULT NULL,
  `gravida` int DEFAULT NULL,
  `para` int DEFAULT NULL,
  `antenatal_visit_date` date NOT NULL,
  `gestational_age_weeks` int DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `fetal_heart_rate` int DEFAULT NULL,
  `fundal_height_cm` int DEFAULT NULL,
  `presentation` enum('Cephalic','Breech','Transverse') DEFAULT 'Cephalic',
  `pallor` tinyint(1) DEFAULT '0',
  `edema` tinyint(1) DEFAULT '0',
  `proteinuria` tinyint(1) DEFAULT '0',
  `diagnosis` text,
  `management_plan` text,
  `medication_given` text,
  `next_visit_date` date DEFAULT NULL,
  `supervised_by` varchar(255) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_family_planning`
--

CREATE TABLE `midwifery_family_planning` (
  `id` int NOT NULL,
  `fp_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_age` int DEFAULT NULL,
  `parity` int DEFAULT NULL,
  `method_selected` enum('Pill','Injection','Implant','IUD','Sterilization','Natural','None') NOT NULL,
  `previous_method` enum('Pill','Injection','Implant','IUD','Sterilization','Natural','None') DEFAULT NULL,
  `counseling_done` tinyint(1) DEFAULT '1',
  `complications_history` text,
  `follow_up_date` date DEFAULT NULL,
  `supervised_by` varchar(255) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_labor_delivery`
--

CREATE TABLE `midwifery_labor_delivery` (
  `id` int NOT NULL,
  `delivery_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_age` int DEFAULT NULL,
  `gravida` int DEFAULT NULL,
  `para` int DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time DEFAULT NULL,
  `delivery_type` enum('Spontaneous Vaginal','Assisted','Elective C/S','Emergency C/S','Vacuum','Forceps') DEFAULT 'Spontaneous Vaginal',
  `labor_duration_hours` decimal(5,2) DEFAULT NULL,
  `rupture_of_membranes` tinyint(1) DEFAULT '0',
  `rupture_time` time DEFAULT NULL,
  `oxytocin_used` tinyint(1) DEFAULT '0',
  `episiotomy` tinyint(1) DEFAULT '0',
  `perineal_tear` enum('None','1st Degree','2nd Degree','3rd Degree','4th Degree') DEFAULT 'None',
  `placenta_complete` tinyint(1) DEFAULT '1',
  `blood_loss_ml` int DEFAULT NULL,
  `newborn_gender` enum('Male','Female','Other') DEFAULT NULL,
  `newborn_weight_gm` int DEFAULT NULL,
  `newborn_apgar_score` int DEFAULT NULL,
  `complications` text,
  `interventions` text,
  `medications_administered` text,
  `outcome` enum('Live Birth','Still Birth','Maternal Death') DEFAULT 'Live Birth',
  `supervised_by` varchar(255) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_postnatal_care`
--

CREATE TABLE `midwifery_postnatal_care` (
  `id` int NOT NULL,
  `postnatal_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `visit_number` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `days_post_delivery` int DEFAULT NULL,
  `maternal_condition` text,
  `uterus_involution` tinyint(1) DEFAULT '1',
  `lochia_type` enum('Rubra','Serosa','Alba') DEFAULT NULL,
  `lochia_amount` enum('Scanty','Moderate','Heavy') DEFAULT NULL,
  `perineal_wound_healing` tinyint(1) DEFAULT '1',
  `breastfeeding_status` enum('Exclusive','Partial','None') DEFAULT 'Exclusive',
  `newborn_condition` text,
  `newborn_weight` decimal(5,2) DEFAULT NULL,
  `newborn_feeding_frequency` int DEFAULT NULL,
  `complications` text,
  `advice_given` text,
  `next_visit_date` date DEFAULT NULL,
  `supervised_by` varchar(255) DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_students`
--

CREATE TABLE `midwifery_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `program` enum('Certificate in Midwifery','Diploma in Midwifery','Upgrading Midwifery') DEFAULT 'Diploma in Midwifery',
  `intake_set` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Ugandan',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `no_of_children` int DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `photo_upload_date` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') DEFAULT 'Active',
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) DEFAULT 'Semester 1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_images`
--

CREATE TABLE `news_images` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `role_target` varchar(50) DEFAULT NULL,
  `notification_type` enum('info','success','warning','error','alert','reminder','system') DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_logbook`
--

CREATE TABLE `nursing_clinical_logbook` (
  `id` int NOT NULL,
  `logbook_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `placement_id` int DEFAULT NULL,
  `log_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Night') DEFAULT 'Morning',
  `patient_name` varchar(255) DEFAULT NULL,
  `patient_age` int DEFAULT NULL,
  `patient_gender` enum('Male','Female','Other') DEFAULT NULL,
  `diagnosis` text,
  `procedure_performed` text,
  `observations` text,
  `interventions` text,
  `outcomes` text,
  `supervisor_initials` varchar(10) DEFAULT NULL,
  `logged_by` int DEFAULT NULL,
  `log_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_placements`
--

CREATE TABLE `nursing_clinical_placements` (
  `id` int NOT NULL,
  `placement_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `placement_site` varchar(255) NOT NULL,
  `placement_department` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration_days` int DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_contact` varchar(20) DEFAULT NULL,
  `objectives` text,
  `learning_outcomes` text,
  `assessment_marks` decimal(5,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `report_submitted` tinyint(1) DEFAULT '0',
  `report_file` varchar(500) DEFAULT NULL,
  `graded_by` int DEFAULT NULL,
  `graded_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_practical_assessment`
--

CREATE TABLE `nursing_practical_assessment` (
  `id` int NOT NULL,
  `assessment_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `assessment_type` enum('OSCE','VIVA','Practical','Clinical') NOT NULL,
  `assessment_name` varchar(255) NOT NULL,
  `date_conducted` date DEFAULT NULL,
  `max_marks` decimal(5,2) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `assessor_id` int DEFAULT NULL,
  `assessor_comments` text,
  `student_comments` text,
  `status` enum('Scheduled','Conducted','Graded','Reviewed') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_skills_training`
--

CREATE TABLE `nursing_skills_training` (
  `id` int NOT NULL,
  `training_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `training_date` date NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `competence_level` enum('Beginner','Developing','Competent','Proficient','Expert') DEFAULT 'Beginner',
  `assessment_score` decimal(5,2) DEFAULT NULL,
  `certification_issued` tinyint(1) DEFAULT '0',
  `certificate_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_students`
--

CREATE TABLE `nursing_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `program` enum('Diploma in Nursing','BSc Nursing','Upgrading Nursing') DEFAULT 'Diploma in Nursing',
  `intake_set` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Ugandan',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `photo_upload_date` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') DEFAULT 'Active',
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) DEFAULT 'Semester 1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_checklist`
--

CREATE TABLE `onboarding_checklist` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `checklist_item` varchar(255) NOT NULL,
  `item_category` varchar(100) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_date` timestamp NULL DEFAULT NULL,
  `completed_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partnerships`
--

CREATE TABLE `partnerships` (
  `id` int NOT NULL,
  `partnership_code` varchar(50) NOT NULL,
  `partner_name` varchar(200) NOT NULL,
  `partner_type` enum('Hospital','University','NGO','Government','Industry','International') NOT NULL,
  `partnership_type` enum('Clinical Training','Research','Funding','Exchange Program','Consultancy','Other') NOT NULL,
  `description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending','Terminated') DEFAULT 'Pending',
  `mou_file` varchar(500) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `responsible_person` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount_received` decimal(15,2) NOT NULL,
  `payment_method_id` int NOT NULL,
  `payment_method` enum('cash','bank_deposit','mobile_money','cheque','card','online') NOT NULL,
  `payment_provider` enum('mtn_momo','airtel_money','stanbic_bank','equity_bank','centenary_bank','dfcu_bank','standard_chartered','other') DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_date` date NOT NULL,
  `processed_by` int DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `status` enum('pending','verified','approved','rejected','bounced') DEFAULT 'pending',
  `verification_notes` text,
  `receipt_generated` tinyint(1) DEFAULT '0',
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `requires_verification` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`, `description`, `requires_verification`, `is_active`, `created_at`) VALUES
(1, 'Cash', 'Direct cash payment', 0, 1, '2026-05-23 13:25:49'),
(2, 'Bank Deposit', 'Bank deposit payment', 1, 1, '2026-05-23 13:25:49'),
(3, 'Mobile Money (MTN)', 'MTN Mobile Money (MTN MoMo)', 1, 1, '2026-05-23 13:25:49'),
(4, 'Mobile Money (Airtel)', 'Airtel Money payment', 1, 1, '2026-05-23 13:25:49'),
(5, 'Cheque', 'Cheque payment', 1, 1, '2026-05-23 13:25:49'),
(6, 'Card', 'Credit/Debit card payment', 0, 1, '2026-05-23 13:25:49'),
(7, 'Online', 'Online bank transfer', 1, 1, '2026-05-23 13:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `receipt_code` varchar(50) DEFAULT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `receipt_time` time DEFAULT NULL,
  `printed_date` timestamp NULL DEFAULT NULL,
  `printed_by` int DEFAULT NULL,
  `emailed_date` timestamp NULL DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `receipt_data` longtext,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('generated','printed','emailed','viewed') DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--

CREATE TABLE `payment_records` (
  `id` int NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `student_id` int NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Credit Card','Cheque','Direct Debit','Other') NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed','Refunded','Cancelled') DEFAULT 'Pending',
  `proof_of_payment_file` varchar(500) DEFAULT NULL,
  `notes` text,
  `processed_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `payment_records`
--
DELIMITER $$
CREATE TRIGGER `log_financial_transaction` AFTER INSERT ON `payment_records` FOR EACH ROW BEGIN
    INSERT INTO financial_records (
        record_type,
        amount,
        currency,
        description,
        reference_number,
        payment_method,
        recorded_by,
        student_id,
        transaction_date
    ) VALUES (
        'Collection',
        NEW.amount,
        NEW.currency,
        CONCAT('Payment - ', NEW.payment_reference),
        NEW.payment_number,
        NEW.payment_method,
        NEW.processed_by,
        NEW.student_id,
        NEW.payment_date
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_fee_account_balance` AFTER INSERT ON `payment_records` FOR EACH ROW BEGIN
    UPDATE fee_accounts 
    SET paid_amount = paid_amount + NEW.amount,
        status = CASE 
            WHEN amount - (paid_amount + NEW.amount) <= 0 THEN 'Paid'
            WHEN paid_amount + NEW.amount > 0 THEN 'Partially Paid'
            ELSE 'Unpaid'
        END
    WHERE student_id = NEW.student_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` varchar(4) NOT NULL,
  `gross_salary` decimal(10,2) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `total_fees_collected` decimal(10,2) DEFAULT '0.00',
  `net_payment` decimal(10,2) NOT NULL,
  `payslip_number` varchar(50) DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `processing_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int NOT NULL,
  `payslip_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `salary_month` varchar(20) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `deductions` decimal(15,2) DEFAULT NULL,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `payment_method` enum('bank_transfer','cash','cheque') DEFAULT 'bank_transfer',
  `payment_date` date DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `viewed_by_employee` tinyint(1) DEFAULT '0',
  `viewed_date` timestamp NULL DEFAULT NULL,
  `status` enum('generated','approved','paid') DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_config`
--

CREATE TABLE `penalty_config` (
  `id` int NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `penalty_type` enum('late_payment','service_charge','other') NOT NULL,
  `calculation_method` enum('fixed_amount','percentage','daily') NOT NULL,
  `fixed_amount` decimal(15,2) DEFAULT '0.00',
  `percentage_value` decimal(5,2) DEFAULT '0.00',
  `daily_rate` decimal(15,2) DEFAULT '0.00',
  `grace_days` int DEFAULT '0',
  `max_penalty_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_configurations`
--

CREATE TABLE `penalty_configurations` (
  `id` int NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `penalty_type` enum('late_payment','service_charge','processing_fee','other') NOT NULL,
  `calculation_method` enum('fixed_amount','percentage','daily_rate') NOT NULL,
  `fixed_amount` decimal(15,2) DEFAULT '0.00',
  `percentage_value` decimal(5,2) DEFAULT '0.00',
  `daily_rate` decimal(15,2) DEFAULT '0.00',
  `grace_days` int DEFAULT '0',
  `max_penalty_amount` decimal(15,2) DEFAULT '0.00',
  `applicable_to` enum('all_fees','tuition_only','specific_fees') DEFAULT 'all_fees',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penalty_configurations`
--

INSERT INTO `penalty_configurations` (`id`, `penalty_name`, `penalty_type`, `calculation_method`, `fixed_amount`, `percentage_value`, `daily_rate`, `grace_days`, `max_penalty_amount`, `applicable_to`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Late Payment Penalty', 'late_payment', 'percentage', 0.00, 5.00, 0.00, 7, 500000.00, 'all_fees', 'active', '2026-05-23 13:25:49', '2026-05-23 13:25:49'),
(2, 'Service Charge', 'service_charge', 'fixed_amount', 0.00, 0.00, 0.00, 0, 0.00, 'all_fees', 'active', '2026-05-23 13:25:49', '2026-05-23 13:25:49'),
(3, 'Processing Fee', 'processing_fee', 'fixed_amount', 0.00, 0.00, 0.00, 0, 50000.00, 'all_fees', 'active', '2026-05-23 13:25:49', '2026-05-23 13:25:49'),
(4, 'Late Payment Penalty', 'late_payment', 'percentage', 0.00, 5.00, 0.00, 7, 500000.00, 'all_fees', 'active', '2026-05-23 16:06:46', '2026-05-23 16:06:46'),
(5, 'Service Charge', 'service_charge', 'fixed_amount', 0.00, 0.00, 0.00, 0, 0.00, 'all_fees', 'active', '2026-05-23 16:06:46', '2026-05-23 16:06:46'),
(6, 'Processing Fee', 'processing_fee', 'fixed_amount', 0.00, 0.00, 0.00, 0, 50000.00, 'all_fees', 'active', '2026-05-23 16:06:46', '2026-05-23 16:06:46');

-- --------------------------------------------------------

--
-- Table structure for table `performance_indicators`
--

CREATE TABLE `performance_indicators` (
  `id` int NOT NULL,
  `indicator_code` varchar(50) NOT NULL,
  `indicator_name` varchar(255) NOT NULL,
  `indicator_category` varchar(100) DEFAULT NULL,
  `description` text,
  `measurement_type` varchar(50) DEFAULT NULL,
  `target_value` varchar(100) DEFAULT NULL,
  `weight` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `performance_indicators`
--

INSERT INTO `performance_indicators` (`id`, `indicator_code`, `indicator_name`, `indicator_category`, `description`, `measurement_type`, `target_value`, `weight`, `status`, `created_at`) VALUES
(1, 'PI001', 'Punctuality', 'Attendance', NULL, 'score', NULL, 10, 'active', '2026-05-23 13:27:59'),
(2, 'PI002', 'Work Quality', 'Performance', NULL, 'score', NULL, 25, 'active', '2026-05-23 13:27:59'),
(3, 'PI003', 'Teamwork', 'Behavior', NULL, 'score', NULL, 15, 'active', '2026-05-23 13:27:59'),
(4, 'PI004', 'Initiative', 'Competency', NULL, 'score', NULL, 20, 'active', '2026-05-23 13:27:59'),
(5, 'PI005', 'Communication', 'Skills', NULL, 'score', NULL, 15, 'active', '2026-05-23 13:27:59'),
(6, 'PI006', 'Compliance', 'Conduct', NULL, 'score', NULL, 15, 'active', '2026-05-23 13:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `metric_type` enum('response_time','actions_completed','errors_encountered','login_frequency','document_generation','data_export') NOT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `metric_unit` varchar(20) DEFAULT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') DEFAULT 'daily',
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professional_licenses`
--

CREATE TABLE `professional_licenses` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `license_type` varchar(255) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `issuing_body` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `license_document` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','pending_renewal','suspended') DEFAULT 'active',
  `renewal_reminder_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_level` enum('certificate','diploma','degree') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `duration_years` int DEFAULT NULL,
  `description` text,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_payments`
--

CREATE TABLE `proof_of_payments` (
  `id` int NOT NULL,
  `payment_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int DEFAULT NULL,
  `verified_date` timestamp NULL DEFAULT NULL,
  `verification_notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_assurance`
--

CREATE TABLE `quality_assurance` (
  `id` int NOT NULL,
  `qa_code` varchar(50) NOT NULL,
  `assessment_type` enum('Course Review','Program Review','Department Review','Institutional Review','Student Feedback','Staff Evaluation') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `assessment_period` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `assessed_by` int DEFAULT NULL,
  `findings` text,
  `recommendations` text,
  `action_plan` text,
  `status` enum('Scheduled','In Progress','Completed','Follow-up Required') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `real_time_updates`
--

CREATE TABLE `real_time_updates` (
  `id` int NOT NULL,
  `update_type` enum('new_student','staff_change','system_alert','data_sync','feature_update') NOT NULL,
  `update_title` varchar(200) NOT NULL,
  `update_description` text,
  `update_data` json DEFAULT NULL,
  `target_users` json DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_templates`
--

CREATE TABLE `receipt_templates` (
  `id` int NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_type` enum('Fee Payment','Registration','Transcript','Certificate','General') NOT NULL,
  `template_content` longtext NOT NULL,
  `template_variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `receipt_templates`
--

INSERT INTO `receipt_templates` (`id`, `template_name`, `template_type`, `template_content`, `template_variables`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Fee Payment Receipt', 'Fee Payment', '<h2>ISNM FEE PAYMENT RECEIPT</h2><p><strong>Receipt No:</strong> {{receipt_number}}</p><p><strong>Student:</strong> {{student_name}}</p><p><strong>Amount:</strong> UGX {{amount}}</p><p><strong>Date:</strong> {{date}}</p><p><strong>Payment Method:</strong> {{payment_method}}</p>', '{\"date\": \"date\", \"amount\": \"number\", \"student_name\": \"string\", \"payment_method\": \"string\", \"receipt_number\": \"string\"}', 1, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_applications`
--

CREATE TABLE `recruitment_applications` (
  `id` int NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `job_id` int NOT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `applicant_email` varchar(100) NOT NULL,
  `applicant_phone` varchar(20) DEFAULT NULL,
  `applicant_address` text,
  `cv_file` varchar(500) DEFAULT NULL,
  `cover_letter_file` varchar(500) DEFAULT NULL,
  `other_documents` text,
  `application_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Received','Under Review','Shortlisted','Interview Scheduled','Interviewed','Offer Extended','Accepted','Rejected','Withdrawn') DEFAULT 'Received',
  `interview_date` date DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `interview_notes` text,
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_jobs`
--

CREATE TABLE `recruitment_jobs` (
  `id` int NOT NULL,
  `job_code` varchar(50) NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `job_category` enum('Academic','Administrative','Support','Management','Technical') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `job_type` enum('Full Time','Part Time','Contract','Temporary','Internship') NOT NULL,
  `description` text NOT NULL,
  `requirements` text,
  `qualifications` text,
  `responsibilities` text,
  `salary_range` varchar(100) DEFAULT NULL,
  `vacancies` int DEFAULT '1',
  `application_deadline` date DEFAULT NULL,
  `status` enum('Draft','Open','Closed','On Hold') DEFAULT 'Draft',
  `posted_by` int DEFAULT NULL,
  `posted_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_calendar`
--

CREATE TABLE `registrar_academic_calendar` (
  `id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `semester_start` date NOT NULL,
  `semester_end` date NOT NULL,
  `registration_start` date DEFAULT NULL,
  `registration_end` date DEFAULT NULL,
  `add_drop_deadline` date DEFAULT NULL,
  `withdrawal_deadline` date DEFAULT NULL,
  `exam_start` date DEFAULT NULL,
  `exam_end` date DEFAULT NULL,
  `result_publication_date` date DEFAULT NULL,
  `status` enum('Upcoming','Current','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `registrar_academic_calendar`
--

INSERT INTO `registrar_academic_calendar` (`id`, `academic_year`, `semester`, `semester_start`, `semester_end`, `registration_start`, `registration_end`, `add_drop_deadline`, `withdrawal_deadline`, `exam_start`, `exam_end`, `result_publication_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025/2026', 'Semester 1', '2025-09-01', '2025-12-15', '2025-08-15', '2025-09-15', '2025-09-30', '2025-10-31', '2025-12-01', '2025-12-15', '2026-01-15', 'Current', NULL, '2026-05-23 13:13:32', '2026-05-23 13:13:32'),
(2, '2025/2026', 'Semester 1', '2025-09-01', '2025-12-15', '2025-08-15', '2025-09-15', '2025-09-30', '2025-10-31', '2025-12-01', '2025-12-15', '2026-01-15', 'Current', NULL, '2026-06-14 18:43:40', '2026-06-14 18:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_records`
--

CREATE TABLE `registrar_academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `courses_taken` int DEFAULT NULL,
  `credits_earned` int DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `academic_standing` enum('Good Standing','Probation','Suspension') DEFAULT 'Good Standing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_graduation`
--

CREATE TABLE `registrar_graduation` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `graduation_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `graduation_date` date DEFAULT NULL,
  `ceremony_date` date DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `graduation_status` enum('Eligible','Not Eligible','Applied','Approved','Graduated','Deferred') DEFAULT 'Eligible',
  `application_date` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT '0',
  `certificate_issued_date` timestamp NULL DEFAULT NULL,
  `graduation_fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_student_registration`
--

CREATE TABLE `registrar_student_registration` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `intake_set` varchar(20) DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) DEFAULT 'Semester 1',
  `year_of_study` int DEFAULT '1',
  `registration_date` date NOT NULL,
  `registration_status` enum('Registered','Pending','Rejected','Cancelled') DEFAULT 'Pending',
  `registration_fee` decimal(10,2) DEFAULT '0.00',
  `registration_payment_status` enum('Paid','Partial','Unpaid') DEFAULT 'Unpaid',
  `registered_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcripts`
--

CREATE TABLE `registrar_transcripts` (
  `id` int NOT NULL,
  `transcript_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `class_of_degree` varchar(50) DEFAULT NULL,
  `transcript_status` enum('Draft','Requested','Processing','Ready','Issued','Collected') DEFAULT 'Draft',
  `requested_by` int DEFAULT NULL,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` int DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issued_date` timestamp NULL DEFAULT NULL,
  `collected_date` timestamp NULL DEFAULT NULL,
  `collection_signature` varchar(255) DEFAULT NULL,
  `purpose` text,
  `copies_requested` int DEFAULT '1',
  `copies_issued` int DEFAULT '0',
  `fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_clearances`
--

CREATE TABLE `requirement_clearances` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `item_id` int NOT NULL,
  `cleared` tinyint(1) DEFAULT '0',
  `cleared_by` int DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `requirement_clearances`
--
DELIMITER $$
CREATE TRIGGER `trg_req_cleared_at` BEFORE UPDATE ON `requirement_clearances` FOR EACH ROW BEGIN
    IF NEW.cleared = TRUE AND OLD.cleared = FALSE THEN
        SET NEW.cleared_at = NOW();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_items`
--

CREATE TABLE `requirement_items` (
  `id` int NOT NULL,
  `item_name` varchar(120) NOT NULL,
  `item_category` enum('General Supplies','Cleaning Materials','Stationery','Personal Protective Equipment','Other') NOT NULL DEFAULT 'General Supplies',
  `description` text,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `requirement_items`
--

INSERT INTO `requirement_items` (`id`, `item_name`, `item_category`, `description`, `is_mandatory`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Surgical Gloves', 'Personal Protective Equipment', NULL, 1, 1, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(2, 'Examination Gloves', 'Personal Protective Equipment', NULL, 1, 2, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(3, 'Photocopying Ream', 'Stationery', NULL, 1, 3, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(4, 'Ruled Paper Reams', 'Stationery', NULL, 1, 4, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(5, 'Omo', 'Cleaning Materials', NULL, 1, 5, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(6, 'Toilet Papers', 'Cleaning Materials', NULL, 1, 6, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(7, 'Compound Brooms', 'Cleaning Materials', NULL, 1, 7, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(8, 'Soft Brooms', 'Cleaning Materials', NULL, 1, 8, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(9, 'Rake', 'Cleaning Materials', NULL, 1, 9, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(10, 'Cobweb Brush', 'Cleaning Materials', NULL, 1, 10, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(11, 'Scrubbing Brush', 'Cleaning Materials', NULL, 1, 11, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(12, 'Squeezer', 'Cleaning Materials', NULL, 1, 12, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(13, 'Toilet Brush', 'Cleaning Materials', NULL, 1, 13, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(14, 'JIK', 'Cleaning Materials', NULL, 1, 14, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(15, 'Vim', 'Cleaning Materials', NULL, 1, 15, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(16, 'Mops', 'Cleaning Materials', NULL, 1, 16, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(17, 'Sanitizer', 'General Supplies', NULL, 1, 17, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(18, 'Liquid Soap', 'General Supplies', NULL, 1, 18, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(19, 'Face Masks', 'Personal Protective Equipment', NULL, 1, 19, '2026-05-23 13:59:02', '2026-05-23 13:59:02'),
(20, 'Heavy Duty Gloves', 'Personal Protective Equipment', NULL, 1, 20, '2026-05-23 13:59:02', '2026-05-23 13:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `requirement_messages`
--

CREATE TABLE `requirement_messages` (
  `id` int NOT NULL,
  `sender_type` enum('matron','secretary','admissions') NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_type` enum('matron','secretary','admissions') NOT NULL,
  `recipient_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_projects`
--

CREATE TABLE `research_projects` (
  `id` int NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `project_title` varchar(200) NOT NULL,
  `project_description` text,
  `lead_researcher` int NOT NULL,
  `research_team` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `funding_source` varchar(200) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `status` enum('Proposal','Ongoing','Completed','On Hold','Cancelled') DEFAULT 'Proposal',
  `publication_details` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_publication`
--

CREATE TABLE `result_publication` (
  `id` int NOT NULL,
  `publication_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `publication_date` timestamp NULL DEFAULT NULL,
  `status` enum('Draft','Scheduled','Published','Withdrawn') DEFAULT 'Draft',
  `published_by` int DEFAULT NULL,
  `scheduled_date` timestamp NULL DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_inspections`
--

CREATE TABLE `room_inspections` (
  `id` int NOT NULL,
  `inspection_number` varchar(50) NOT NULL,
  `room_id` int NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspector_id` int NOT NULL,
  `cleanliness_score` decimal(3,2) DEFAULT NULL,
  `condition_score` decimal(3,2) DEFAULT NULL,
  `overall_status` enum('Excellent','Good','Fair','Poor','Critical') DEFAULT 'Good',
  `findings` text,
  `maintenance_required` tinyint(1) DEFAULT '0',
  `maintenance_notes` text,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `route_schedules`
--

CREATE TABLE `route_schedules` (
  `id` int NOT NULL,
  `route_code` varchar(50) NOT NULL,
  `route_name` varchar(200) NOT NULL,
  `route_type` enum('Morning','Evening','Both') DEFAULT 'Both',
  `departure_time` time NOT NULL,
  `return_time` time DEFAULT NULL,
  `start_point` varchar(200) NOT NULL,
  `end_point` varchar(200) NOT NULL,
  `stops` json DEFAULT NULL,
  `distance_km` decimal(10,2) DEFAULT NULL,
  `estimated_duration_minutes` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `days_of_operation` varchar(50) DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday',
  `status` enum('Active','Inactive','Seasonal') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_components`
--

CREATE TABLE `salary_components` (
  `id` int NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_type` enum('allowance','deduction') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `is_percentage` tinyint(1) DEFAULT '0',
  `percentage_of` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `structure_code` varchar(50) NOT NULL,
  `structure_start_date` date NOT NULL,
  `structure_end_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL,
  `housing_allowance` decimal(15,2) DEFAULT '0.00',
  `transport_allowance` decimal(15,2) DEFAULT '0.00',
  `medical_allowance` decimal(15,2) DEFAULT '0.00',
  `other_allowances` longtext,
  `total_allowances` decimal(15,2) DEFAULT '0.00',
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `nssf_deduction` decimal(15,2) DEFAULT '0.00',
  `income_tax_rate` decimal(5,2) DEFAULT NULL,
  `other_deductions` longtext,
  `total_deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) NOT NULL,
  `scholarship_code` varchar(50) DEFAULT NULL,
  `sponsor_name` varchar(255) NOT NULL,
  `sponsor_type` enum('government','ngo','private_company','individual','religious','institutional','other') NOT NULL,
  `sponsorship_type` enum('full','partial','conditional') NOT NULL,
  `coverage_percentage` decimal(5,2) DEFAULT '100.00',
  `coverage_details` text,
  `tuition_coverage` tinyint(1) DEFAULT '1',
  `accommodation_coverage` tinyint(1) DEFAULT '0',
  `clinical_coverage` tinyint(1) DEFAULT '0',
  `other_fee_coverage` tinyint(1) DEFAULT '0',
  `amount_per_semester` decimal(15,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `renewable` tinyint(1) DEFAULT '1',
  `conditions` text,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','expired','suspended','completed','terminated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_index`
--

CREATE TABLE `search_index` (
  `id` int NOT NULL,
  `entity_type` enum('staff','student','document','course','department') NOT NULL,
  `entity_id` int NOT NULL,
  `searchable_content` longtext,
  `keywords` json DEFAULT NULL,
  `keywords_text` text GENERATED ALWAYS AS (json_unquote(json_extract(`keywords`,_utf8mb4'$.*'))) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_access_logs`
--

CREATE TABLE `security_access_logs` (
  `id` int NOT NULL,
  `log_id` varchar(50) NOT NULL,
  `access_point` varchar(255) NOT NULL,
  `access_date` date NOT NULL,
  `access_time` time DEFAULT NULL,
  `person_type` enum('Staff','Student','Visitor','Vendor','Unknown') NOT NULL,
  `person_id` int DEFAULT NULL,
  `person_name` varchar(255) DEFAULT NULL,
  `access_direction` enum('Entry','Exit') NOT NULL,
  `access_method` enum('ID Card','Biometric','PIN','Manual') DEFAULT 'ID Card',
  `authorized` tinyint(1) DEFAULT '1',
  `denial_reason` varchar(255) DEFAULT NULL,
  `captured_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_emergency_contacts`
--

CREATE TABLE `security_emergency_contacts` (
  `id` int NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_type` enum('Police','Hospital','Fire','Ambulance','Internal') NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `secondary_phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `response_time_minutes` int DEFAULT NULL,
  `notes` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_equipment`
--

CREATE TABLE `security_equipment` (
  `id` int NOT NULL,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` enum('CCTV Camera','Access Control System','Metal Detector','Radio','Alarm System','Fire Extinguisher','Emergency Light','Other') NOT NULL,
  `location` varchar(200) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Operational','Under Maintenance','Out of Service','Retired') DEFAULT 'Operational',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `maintained_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incidents`
--

CREATE TABLE `security_incidents` (
  `id` int NOT NULL,
  `incident_number` varchar(50) NOT NULL,
  `incident_type` enum('Unauthorized Access','Theft','Vandalism','Assault','Parking Violation','Vehicle Entry','Visitor Check-in','Emergency','Other') NOT NULL,
  `incident_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Reported','Under Investigation','Resolved','Closed') DEFAULT 'Reported',
  `reported_by` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `resolution_notes` text,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_patrols`
--

CREATE TABLE `security_patrols` (
  `id` int NOT NULL,
  `patrol_number` varchar(50) NOT NULL,
  `guard_id` int NOT NULL,
  `patrol_route` varchar(200) NOT NULL,
  `patrol_area` enum('Main Gate','Academic Block','Hostel Area','Parking Lot','Library','Laboratory','Sports Field','Perimeter','Full Campus') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `patrol_date` date NOT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','On Break') DEFAULT 'Scheduled',
  `observations` text,
  `incidents_found` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_visitors`
--

CREATE TABLE `security_visitors` (
  `id` int NOT NULL,
  `visitor_id` varchar(50) NOT NULL,
  `visitor_name` varchar(255) NOT NULL,
  `visitor_phone` varchar(20) DEFAULT NULL,
  `visitor_email` varchar(100) DEFAULT NULL,
  `visitor_company` varchar(255) DEFAULT NULL,
  `visitor_nature` enum('Official','Parent','Guardian','Service Provider','Delivery','Other') NOT NULL,
  `purpose_of_visit` text,
  `person_to_visit` int DEFAULT NULL,
  `person_to_visit_name` varchar(255) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `expected_arrival` time DEFAULT NULL,
  `expected_departure` time DEFAULT NULL,
  `actual_arrival` timestamp NULL DEFAULT NULL,
  `actual_departure` timestamp NULL DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `items_carried` text,
  `security_check_passed` tinyint(1) DEFAULT '0',
  `check_in_by` int DEFAULT NULL,
  `check_out_by` int DEFAULT NULL,
  `status` enum('Scheduled','Checked In','On Campus','Checked Out','No Show') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills_laboratory`
--

CREATE TABLE `skills_laboratory` (
  `id` int NOT NULL,
  `lab_code` varchar(50) NOT NULL,
  `lab_name` varchar(200) NOT NULL,
  `lab_type` enum('Nursing Skills Lab','Midwifery Skills Lab','Anatomy Lab','Physiology Lab','Other') NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `equipment_list` text,
  `in_charge` int DEFAULT NULL,
  `status` enum('Active','Under Maintenance','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills_lab_sessions`
--

CREATE TABLE `skills_lab_sessions` (
  `id` int NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `lab_id` int NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `lecturer_id` int DEFAULT NULL,
  `session_topic` varchar(200) DEFAULT NULL,
  `session_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `student_group` varchar(100) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smart_suggestions`
--

CREATE TABLE `smart_suggestions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `suggestion_type` enum('action','report','shortcut','reminder','tip') NOT NULL,
  `suggestion_text` text NOT NULL,
  `suggestion_data` json DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `context` varchar(100) DEFAULT NULL,
  `is_dismissed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sponsorships`
--

CREATE TABLE `sponsorships` (
  `id` int NOT NULL,
  `sponsorship_code` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `sponsor_name` varchar(200) NOT NULL,
  `sponsor_type` enum('Government','NGO','Private','Corporate','Individual','Scholarship','Other') NOT NULL,
  `sponsorship_type` enum('Full Tuition','Partial Tuition','Full Fees','Partial Fees','Living Expenses','Books','Other') NOT NULL,
  `coverage_percentage` decimal(5,2) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Expired','Terminated') DEFAULT 'Active',
  `terms_and_conditions` text,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave','Suspended') DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `address` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_failed_attempt` timestamp NULL DEFAULT NULL,
  `password_changed` tinyint(1) DEFAULT '0',
  `is_first_login` tinyint(1) DEFAULT '1',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `password`, `phone`, `position`, `department`, `role_id`, `status`, `hire_date`, `salary`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `last_login`, `login_attempts`, `locked_until`, `last_failed_attempt`, `password_changed`, `is_first_login`, `two_factor_enabled`, `two_factor_secret`, `created_at`, `updated_at`) VALUES
(50, 'DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$RU6PrzIHTgggFW3sUy.e8eYEvlvzikGAU6RRa8pgv9c/x647piOqK', NULL, 'Director General', 'Executive Office', 1, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, '2026-06-15 04:56:44', 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:56:44'),
(51, 'CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$rBRqOoVh5m0Igx4kdXtpfOFwPIDweterjIksjISwN4FCu..HteWam', NULL, 'Chief Executive Officer', 'Executive Office', 3, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(52, 'SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$l6XsX6XqY.Pbcd7XDzGjxeOufB1j9XfcNlv3kJGs3.MX79JWKs4ti', NULL, 'School Principal', 'Academic Affairs', 2, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(53, 'SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$m3Fosy0PWOX2NDhe.H83bOrdOamiuFvKjjv3gmL591/c/f7UU6Utm', NULL, 'School Secretary', 'Administrative Office', 21, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(54, 'AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Cbs9kpWc7uh2KbzRTr9qNuKUmKBAG7UDxb7SE4TbebziRQRlSy8YW', NULL, 'Academic Registrar', 'Academic Affairs', 8, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(55, 'BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0z4Ii3PfeqVdR3uul1iczO5YJ2NjVg49Qla8PQ0GpUufYd0v5saXS', NULL, 'School Bursar', 'Finance Department', 9, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(56, 'HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$hesFYTZgh9X4Q2FyXU/4neiB7vkoIr15zwMa.R17g4DhWx2umLk22', NULL, 'HR Manager', 'Human Resources', 7, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(57, 'DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HbQJxmFSl2mCzxW83atloemF/UBA7sg9RKA6TqN7Mb9iKTrTdACtm', NULL, 'Director Academics', 'Academic Affairs', 4, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(58, 'DI001', 'Director ICT', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$5BFMhfh8zO9myR6Ha8w.g.UL0PCEIcIWXGeTbPmOF1lQizIU0Wsm2', NULL, 'Director ICT', 'Information Technology', 6, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(59, 'DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$uZQlJ0YNKT7FzCD7cjEVNOaXDD7xnPbqT.2Nv6qBO5O5KgaMx7NBC', NULL, 'Director Finance', 'Finance Department', 5, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(60, 'LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$3E1cG3FKr.3hRqZr.9a.j.ljYkuj/zAl376Gb8oakdPHw0nLrCqgu', NULL, 'School Librarian', 'Library Services', 10, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(61, 'HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$C6xhOfyPi4nj/kwaZfmoWeExbQHfnbceO7enAKt/oqs9jrdD4e7JK', NULL, 'Head Nursing', 'Nursing Department', 11, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(62, 'HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$LHAORiiXnly8kcd1sCJOP.r9/kJAq65lvEszWfk7DWcTeYPnNqYIO', NULL, 'Head Midwifery', 'Midwifery Department', 12, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(63, 'LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$dqYUVn3eri6frqS.fmqeGuvSyQ1jXZGcGoOTgqmSi0ccxuAkejS/S', NULL, 'Lecturer', 'Academic Affairs', 13, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(64, 'SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Z7GAj95kaxlSA1vf3ebGG.fg8uNhH0dce8FtksFqTUbk6/tRS5yAW', NULL, 'Senior Lecturer', 'Academic Affairs', 14, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(65, 'LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$RR6yvXWHLGgbWPpQN09Jv.LX9PvVfqnBvSTIz1gP6CsH4qMNkGxyO', NULL, 'Sickbay', 'Support', 16, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(66, 'MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$BC8eNBiywm3cjp1CrGNNqefq28VFu5/ww6ZK73C7QYs8VarKkcwea', NULL, 'Matrons', 'Student Affairs', 17, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(67, 'SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$X9ngKJfjBhCXBlHYUEaZcuTRE60vcbKyyYRZJEPNZXAEeYrUUGYZO', NULL, 'Security', 'Security Services', 18, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(68, 'DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$cogVgG3L7gIkPuGxrxzKm.kCRxTq9HnffahEUnSPACv.s8JBAsNeK', NULL, 'Drivers', 'Transport', 19, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(69, 'WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$FxhwWDM4Xp0bDs5WRAxbyObpbvZDvOOHf52yiamFGTmdGUQpdAUcK', NULL, 'Wardens', 'Student Affairs', 20, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(70, 'DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$9fOKtTQAgB/elswK9HgmE.BwFEvqipIFnSmcvJPeXpEgb3KXa8D.m', NULL, 'Deputy Principal', 'Academic Affairs', 22, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(71, 'STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$M6cl6Y9PoVugM6mlWnyEBeGOChjv8fsi3tDMFMqP43bOG6TGELDje', NULL, 'Store Keeper', 'Facilities Management', 25, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(72, 'CL001', 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Computer Lab Manager', 'Information Technology', 6, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:27:20'),
(73, 'GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$wLikyrgK4SzFsYDJ5BpOnOUXG9/qOh2DuoT6ud/jVVF/eQxfEKxDC', NULL, 'Guild President', 'Student Affairs', 26, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49'),
(74, 'ADM001', 'Director Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ObioUw9rfd59rbVBHxw60.e7n83Fmzjbtr.ZPJJgqRroTg1DI1KHy', NULL, 'Director Admissions & Requirements', 'Admissions', 27, 'Active', '2026-06-14', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, 1, 0, NULL, '2026-06-15 04:06:49', '2026-06-15 04:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff_access_control`
--

CREATE TABLE `staff_access_control` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `access_level` enum('None','Read','Write','Delete','Admin') DEFAULT 'Read',
  `granted_by` int DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `access_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

CREATE TABLE `staff_activity_log` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `activity_type` enum('Login','Logout','Dashboard Access','Data View','Data Edit','Data Delete','Export','Print','Settings Change','Account Created','Account Updated') NOT NULL,
  `activity_description` text,
  `module_accessed` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_activity_log`
--

INSERT INTO `staff_activity_log` (`id`, `staff_id`, `activity_type`, `activity_description`, `module_accessed`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(5, 72, 'Account Created', 'ICT Department official account created/updated', 'authentication', NULL, 'SYSTEM', 'Account Setup Script', '2026-06-15 04:24:32'),
(6, 72, 'Account Created', 'ICT Department official account created/updated', 'authentication', NULL, 'SYSTEM', 'Account Setup Script', '2026-06-15 04:25:07'),
(7, 50, 'Login', 'Staff member logged into the system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 04:56:44'),
(8, 50, 'Login', 'User logged in successfully', 'authentication', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-15 04:56:44');

-- --------------------------------------------------------

--
-- Table structure for table `staff_announcements`
--

CREATE TABLE `staff_announcements` (
  `id` int NOT NULL,
  `announcement_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `announcement_type` varchar(100) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `announcement_date` date NOT NULL,
  `posted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_appraisals`
--

CREATE TABLE `staff_appraisals` (
  `id` int NOT NULL,
  `appraisal_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `appraisal_period_id` int NOT NULL,
  `appraisee_name` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `appraiser_id` int DEFAULT NULL,
  `appraiser_name` varchar(255) DEFAULT NULL,
  `appraisal_date` date DEFAULT NULL,
  `overall_rating` decimal(3,1) DEFAULT NULL,
  `overall_comments` text,
  `strengths` text,
  `areas_for_improvement` text,
  `goals_achieved` text,
  `goals_not_achieved` text,
  `training_recommendations` text,
  `promotion_recommended` tinyint(1) DEFAULT '0',
  `performance_status` enum('exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT 'meets_expectations',
  `status` enum('draft','submitted','reviewed','finalized') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','Half Day','On Leave') NOT NULL,
  `work_hours` decimal(4,2) DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT '0.00',
  `remarks` text,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_audit_logs`
--

CREATE TABLE `staff_audit_logs` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_affected` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_categories`
--

CREATE TABLE `staff_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) DEFAULT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_categories`
--

INSERT INTO `staff_categories` (`id`, `category_name`, `category_code`, `description`, `status`, `created_at`) VALUES
(1, 'Teaching Staff', 'TS', 'Nurses, Midwives, Instructors', 'active', '2026-05-23 13:27:59'),
(2, 'Administrative', 'AD', 'Office and administrative staff', 'active', '2026-05-23 13:27:59'),
(3, 'Support Staff', 'SS', 'Cleaners, drivers, support personnel', 'active', '2026-05-23 13:27:59'),
(4, 'Managerial', 'MG', 'Managers, directors, supervisors', 'active', '2026-05-23 13:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `staff_contracts`
--

CREATE TABLE `staff_contracts` (
  `id` int NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `contract_type` enum('Permanent','Probation','Fixed Term','Contract','Consultancy','Internship') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `job_title` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `contract_terms` text,
  `benefits` text,
  `probation_period` int DEFAULT '6',
  `notice_period` int DEFAULT '30',
  `status` enum('Active','Expired','Terminated','Suspended','Renewed') DEFAULT 'Active',
  `signed_date` date DEFAULT NULL,
  `signed_by` int DEFAULT NULL,
  `contract_file` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_dashboard_access`
--

CREATE TABLE `staff_dashboard_access` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `dashboard_path` varchar(255) NOT NULL,
  `access_level` enum('Full','Read Only','Limited') DEFAULT 'Full',
  `granted_by` int DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--

CREATE TABLE `staff_departments` (
  `id` int NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `description` text,
  `head_of_department_id` int DEFAULT NULL,
  `parent_department_id` int DEFAULT NULL,
  `department_level` enum('Executive','Management','Academic','Support','Administrative') DEFAULT 'Academic',
  `budget` decimal(15,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_departments`
--

INSERT INTO `staff_departments` (`id`, `department_name`, `department_code`, `description`, `head_of_department_id`, `parent_department_id`, `department_level`, `budget`, `location`, `contact_email`, `contact_phone`, `created_at`, `updated_at`) VALUES
(1, 'Executive Office', 'EXEC', 'School executive management and strategic planning', NULL, NULL, 'Executive', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(2, 'Academic Affairs', 'ACAD', 'Academic programs and student services', NULL, NULL, 'Academic', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(3, 'Finance Department', 'FIN', 'Financial management and operations', NULL, NULL, 'Administrative', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(4, 'Human Resources', 'HR', 'Staff management and development', NULL, NULL, 'Administrative', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(5, 'Information Technology', 'ICT', 'IT infrastructure and support', NULL, NULL, 'Support', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(6, 'Nursing Department', 'NUR', 'Nursing education and training', NULL, NULL, 'Academic', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(7, 'Midwifery Department', 'MID', 'Midwifery education and training', NULL, NULL, 'Academic', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(8, 'Library Services', 'LIB', 'Library and information resources', NULL, NULL, 'Support', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(9, 'Student Affairs', 'STU', 'Student welfare and support services', NULL, NULL, 'Support', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(10, 'Security Services', 'SEC', 'Campus security and safety', NULL, NULL, 'Support', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(11, 'Facilities Management', 'FAC', 'Physical infrastructure management', NULL, NULL, 'Support', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37'),
(12, 'Quality Assurance', 'QA', 'Academic quality and standards', NULL, NULL, 'Academic', NULL, NULL, NULL, NULL, '2026-06-14 19:50:37', '2026-06-14 19:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `staff_documents`
--

CREATE TABLE `staff_documents` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `document_type` enum('CV','Contract','Certificate','ID','Passport','Academic','Professional','Profile Picture','Other') NOT NULL,
  `document_title` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` date DEFAULT NULL,
  `is_confidential` tinyint(1) DEFAULT '0',
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_requests`
--

CREATE TABLE `staff_leave_requests` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type` enum('Annual','Sick','Maternity','Paternity','Study','Compassionate','Unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int NOT NULL,
  `reason` text,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `approval_remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_attempts`
--

CREATE TABLE `staff_login_attempts` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT '0',
  `failure_reason` varchar(255) DEFAULT NULL,
  `staff_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_sessions`
--

CREATE TABLE `staff_login_sessions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `device_info` text,
  `browser_info` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_login_sessions`
--

INSERT INTO `staff_login_sessions` (`id`, `staff_id`, `session_token`, `device_info`, `browser_info`, `ip_address`, `user_agent`, `is_active`, `created_at`, `last_activity`, `expires_at`) VALUES
(3, 50, 'os4h7bo4283hnll87ubnlap28t', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, '2026-06-15 04:56:44', '2026-06-15 04:56:44', '2026-06-15 05:26:44');

--
-- Triggers `staff_login_sessions`
--
DELIMITER $$
CREATE TRIGGER `log_staff_activity` AFTER INSERT ON `staff_login_sessions` FOR EACH ROW BEGIN
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, ip_address, user_agent)
    VALUES (NEW.staff_id, 'Login', 'Staff member logged into the system', NEW.ip_address, NEW.user_agent);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `staff_login_view`
-- (See below for the actual view)
--
CREATE TABLE `staff_login_view` (
`id` int
,`staff_id` varchar(50)
,`full_name` varchar(100)
,`email` varchar(100)
,`position` varchar(100)
,`department` varchar(100)
,`role_name` varchar(100)
,`role_level` enum('Executive','Management','Academic','Support','Administrative')
,`dashboard_path` varchar(255)
,`status` enum('Active','Inactive','On Leave','Suspended')
,`last_login` timestamp
,`login_attempts` int
,`locked_until` timestamp
,`is_first_login` tinyint(1)
,`account_status` varchar(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `staff_notifications`
--

CREATE TABLE `staff_notifications` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_password_resets`
--

CREATE TABLE `staff_password_resets` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_used` tinyint(1) DEFAULT '0',
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_performance`
--

CREATE TABLE `staff_performance` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `evaluation_period` varchar(50) NOT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `rating` enum('Outstanding','Excellent','Good','Satisfactory','Needs Improvement') NOT NULL,
  `strengths` text,
  `areas_for_improvement` text,
  `goals` text,
  `evaluator_id` int DEFAULT NULL,
  `evaluation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_permissions`
--

CREATE TABLE `staff_permissions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `module` varchar(100) NOT NULL,
  `permission_level` enum('Read','Write','Delete','Admin','Super Admin') DEFAULT 'Read',
  `granted_by` int DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_permissions`
--

INSERT INTO `staff_permissions` (`id`, `staff_id`, `module`, `permission_level`, `granted_by`, `granted_at`, `expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 72, 'computer_lab', 'Admin', 72, '2026-06-15 04:24:32', NULL, 1, '2026-06-15 04:24:32', '2026-06-15 04:24:32'),
(2, 72, 'it_inventory', 'Admin', 72, '2026-06-15 04:24:32', NULL, 1, '2026-06-15 04:24:32', '2026-06-15 04:24:32'),
(3, 72, 'it_support', 'Admin', 72, '2026-06-15 04:24:32', NULL, 1, '2026-06-15 04:24:32', '2026-06-15 04:24:32'),
(4, 72, 'computer_lab', 'Admin', 72, '2026-06-15 04:25:07', NULL, 1, '2026-06-15 04:25:07', '2026-06-15 04:25:07'),
(5, 72, 'it_inventory', 'Admin', 72, '2026-06-15 04:25:07', NULL, 1, '2026-06-15 04:25:07', '2026-06-15 04:25:07'),
(6, 72, 'it_support', 'Admin', 72, '2026-06-15 04:25:07', NULL, 1, '2026-06-15 04:25:07', '2026-06-15 04:25:07'),
(7, 72, 'computer_lab', 'Admin', 72, '2026-06-15 04:27:20', NULL, 1, '2026-06-15 04:27:20', '2026-06-15 04:27:20'),
(8, 72, 'it_inventory', 'Admin', 72, '2026-06-15 04:27:20', NULL, 1, '2026-06-15 04:27:20', '2026-06-15 04:27:20'),
(9, 72, 'it_support', 'Admin', 72, '2026-06-15 04:27:20', NULL, 1, '2026-06-15 04:27:20', '2026-06-15 04:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `bio` text,
  `profile_picture` varchar(255) DEFAULT NULL,
  `qualifications` text,
  `experience` text,
  `skills` text,
  `achievements` text,
  `education_background` text,
  `certifications` text,
  `professional_memberships` text,
  `research_interests` text,
  `publications` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_promotions`
--

CREATE TABLE `staff_promotions` (
  `id` int NOT NULL,
  `promotion_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `previous_position` varchar(200) NOT NULL,
  `new_position` varchar(200) NOT NULL,
  `previous_salary` decimal(10,2) NOT NULL,
  `new_salary` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `effective_date` date NOT NULL,
  `promotion_reason` text,
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Implemented') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_qualifications`
--

CREATE TABLE `staff_qualifications` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `qualification_name` varchar(255) NOT NULL,
  `qualification_level` varchar(100) DEFAULT NULL,
  `field_of_study` varchar(255) DEFAULT NULL,
  `institution_name` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `certificate_file` varchar(255) DEFAULT NULL,
  `status` enum('verified','pending','expired') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_records`
--

CREATE TABLE `staff_records` (
  `id` int NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `staff_code` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_primary` varchar(20) DEFAULT NULL,
  `phone_secondary` varchar(20) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `home_address` text,
  `residential_address` text,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `next_of_kin_name` varchar(255) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
  `next_of_kin_relationship` varchar(50) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','on_leave','suspended','retired','resigned') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_resignations`
--

CREATE TABLE `staff_resignations` (
  `id` int NOT NULL,
  `resignation_number` varchar(50) NOT NULL,
  `staff_id` int NOT NULL,
  `resignation_date` date NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `notice_period_days` int DEFAULT '30',
  `handover_notes` text,
  `exit_interview_date` date DEFAULT NULL,
  `exit_interview_notes` text,
  `status` enum('Submitted','Accepted','In Progress','Completed','Rejected') DEFAULT 'Submitted',
  `approved_by` int DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

CREATE TABLE `staff_roles` (
  `id` int NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_description` text,
  `role_level` enum('Executive','Management','Academic','Support','Administrative') DEFAULT 'Academic',
  `dashboard_path` varchar(255) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{\"all\": true, \"can_view_hr\": true, \"super_admin\": true, \"can_edit_all_data\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_delete_all_data\": true, \"can_manage_all_staff\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_access_all_dashboards\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(2, 'School Principal', 'School academic and administrative leadership with cross-departmental viewing access', 'Executive', 'dashboards/school-principal.php', '{\"staff\": true, \"academic\": true, \"students\": true, \"can_view_hr\": true, \"administrative\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(3, 'CEO', 'Chief Executive Officer for strategic management with cross-departmental viewing access', 'Executive', 'dashboards/ceo.php', '{\"financial\": true, \"strategic\": true, \"can_view_hr\": true, \"operational\": true, \"can_view_reports\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(4, 'Director Academics', 'Academic programs and curriculum oversight with cross-departmental viewing access', 'Management', 'dashboards/director-academics.php', '{\"faculty\": true, \"academic\": true, \"curriculum\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_manage_courses\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(5, 'Director Finance', 'Financial management and oversight with cross-departmental viewing access', 'Management', 'dashboards/director-finance.php', '{\"budgeting\": true, \"financial\": true, \"reporting\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_manage_finances\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(6, 'Director ICT', 'Information Technology management with cross-departmental viewing access', 'Management', 'dashboards/director-ict.php', '{\"ict\": true, \"systems\": true, \"can_view_hr\": true, \"infrastructure\": true, \"can_manage_system\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(7, 'HR Manager', 'Human resources management', 'Management', 'dashboards/hr-manager.php', '{\"hr\": true, \"staff\": true, \"training\": true, \"recruitment\": true, \"can_manage_staff\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(8, 'Academic Registrar', 'Student registration and academic records management', 'Academic', 'dashboards/academic-registrar.php', '{\"academic\": true, \"students\": true, \"transcripts\": true, \"certificates\": true, \"registration\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(9, 'School Bursar', 'Financial operations and fee management', 'Administrative', 'bursar_dashboard.php', '{\"fees\": true, \"financial\": true, \"collections\": true, \"can_manage_fees\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(10, 'School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{\"catalog\": true, \"library\": true, \"resources\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(11, 'Head Nursing', 'Nursing department management', 'Academic', 'dashboards/head-nursing.php', '{\"faculty\": true, \"nursing\": true, \"department\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(12, 'Head Midwifery', 'Midwifery department management', 'Academic', 'dashboards/head-midwifery.php', '{\"faculty\": true, \"midwifery\": true, \"department\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(13, 'Lecturers', 'Teaching and academic staff management', 'Academic', 'dashboards/lecturers.php', '{\"courses\": true, \"teaching\": true, \"lecturers\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(14, 'Senior Lecturers', 'Senior teaching staff management', 'Academic', 'dashboards/senior-lecturers.php', '{\"senior\": true, \"teaching\": true, \"lecturers\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(16, 'Sickbay', 'Medical and healthcare support services', 'Support', 'dashboards/sickbay.php', '{\"medical\": true, \"patient\": true, \"healthcare\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(17, 'Matrons', 'Student welfare and residential staff management', 'Support', 'dashboards/matrons.php', '{\"residential\": true, \"student_welfare\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(18, 'Security', 'Campus security and safety management', 'Support', 'dashboards/security.php', '{\"safety\": true, \"security\": true, \"emergency\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(19, 'Drivers', 'Transportation and vehicle management', 'Support', 'dashboards/drivers.php', '{\"vehicles\": true, \"transportation\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(20, 'Wardens', 'Student discipline and residential supervision', 'Support', 'dashboards/wardens.php', '{\"discipline\": true, \"residential\": true, \"student_welfare\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(21, 'School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{\"documentation\": true, \"administrative\": true, \"can_manage_documents\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(22, 'Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{\"academic\": true, \"administrative\": true, \"can_assist_principal\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(25, 'Store Keeper', 'Manage store inventory for general utilities and food supplies', 'Support', 'dashboards/storekeeper.php', '{\"store\": true, \"inventory\": true, \"can_manage_store\": true}', '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(26, 'Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{\"student_affairs\": true}', '2026-06-15 03:57:32', '2026-06-15 03:57:32'),
(27, 'Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{\"admissions\": true, \"requirements\": true}', '2026-06-15 03:57:32', '2026-06-15 03:57:32'),
(52, 'Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{\"ict\": true, \"it_support\": true, \"lab_management\": true}', '2026-06-15 04:24:02', '2026-06-15 04:24:02');

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--

CREATE TABLE `staff_salaries` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT '0.00',
  `overtime_rate` decimal(10,2) DEFAULT '0.00',
  `nssf_tax` decimal(10,2) DEFAULT '0.00',
  `paye_tax` decimal(10,2) DEFAULT '0.00',
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_training`
--

CREATE TABLE `staff_training` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `training_title` varchar(200) NOT NULL,
  `training_provider` varchar(200) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `training_type` enum('Internal','External','Online','Workshop','Conference') NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `certificate_obtained` tinyint(1) DEFAULT '0',
  `certificate_file` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_categories`
--

CREATE TABLE `store_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 'fas fa-box',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `store_categories`
--

INSERT INTO `store_categories` (`id`, `category_name`, `description`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'General Utilities', 'Office supplies, cleaning, electrical, and general maintenance items', 'fas fa-tools', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(2, 'Food Store Supplies', 'Food items, cooking ingredients, and kitchen supplies', 'fas fa-utensils', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(3, 'Medical Supplies', 'Medical consumables, gloves, dressings, and clinical items', 'fas fa-kit-medical', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(4, 'Cleaning & Hygiene', 'Cleaning agents, sanitizers, and hygiene products', 'fas fa-pump-soap', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(5, 'Office Stationery', 'Paper, writing materials, filing and office stationery', 'fas fa-pen-ruler', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(6, 'Electrical & Hardware', 'Electrical fittings, tools, and hardware items', 'fas fa-bolt', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(7, 'Kitchen & Dining', 'Kitchen utensils, dining items, and catering supplies', 'fas fa-kitchen-set', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(8, 'Furniture & Storage', 'Furniture, shelves, filing cabinets, and storage items', 'fas fa-couch', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(9, 'ICT Supplies', 'Computer consumables, printer supplies, and ICT accessories', 'fas fa-laptop', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(10, 'Teaching & Training', 'Teaching aids, simulation supplies, and training materials', 'fas fa-chalkboard-user', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `store_inventory`
--

CREATE TABLE `store_inventory` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `reorder_level` decimal(15,3) DEFAULT '10.000',
  `unit_price` decimal(15,2) DEFAULT '0.00',
  `location` varchar(100) DEFAULT 'Main Store',
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `store_inventory`
--

INSERT INTO `store_inventory` (`id`, `category_id`, `item_name`, `description`, `quantity`, `unit`, `reorder_level`, `unit_price`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Surgical Gloves', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(2, 1, 'Binding Tape', NULL, 0.000, 'rolls', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(3, 1, 'Examination Gloves', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(4, 1, 'Masking Tape', NULL, 0.000, 'rolls', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(5, 1, 'Sink Pumps', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(6, 1, 'Ruled Reams', NULL, 0.000, 'reams', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(7, 1, 'Requirements Clearance Books', NULL, 0.000, 'books', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(8, 1, 'Receipt Books', NULL, 0.000, 'books', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(9, 1, 'Photocopying Reams', NULL, 0.000, 'reams', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(10, 1, 'Payment Voucher Books', NULL, 0.000, 'books', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(11, 1, 'Binding Rings', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(12, 1, 'Ring Binder Files', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(13, 1, 'Box Files', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(14, 1, 'Counter Books', NULL, 0.000, 'books', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(15, 1, 'Layer File Trays', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(16, 1, 'Atlas Files', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(17, 1, 'Domiciliary Kit Bags', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(18, 1, 'PVC Covers', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(19, 1, 'Laminating Paper', NULL, 0.000, 'packs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(20, 1, 'Liquid Soap', NULL, 0.000, 'liters', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(21, 1, 'Toilet Papers', NULL, 0.000, 'rolls', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(22, 1, 'Insulation Tape', NULL, 0.000, 'rolls', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(23, 1, 'Carbon Papers', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(24, 1, 'Blackboard Dusters', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:34:59', '2026-06-14 18:34:59'),
(25, 4, 'Omo (Detergent)', NULL, 0.000, 'kg', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(26, 4, 'Vim (Cleaning Powder)', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(27, 4, 'Jik (Bleach)', NULL, 0.000, 'liters', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(28, 4, 'Scrubbing Brushes', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(29, 4, 'Squeezers', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(30, 4, 'Mops', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(31, 4, 'Toilet Brushes', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(32, 4, 'Cobweb Brushes', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(33, 4, 'Soft Brooms', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(34, 4, 'Compound Brooms', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(35, 4, 'Rakes', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(36, 4, 'Stainless Steel Cleaner', NULL, 0.000, 'liters', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(37, 4, 'Floor Polish', NULL, 0.000, 'liters', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(38, 4, 'Air Freshener', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(39, 4, 'Hand Sanitizer', NULL, 0.000, 'liters', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(40, 4, 'Disposable Gloves (Cleaning)', NULL, 0.000, 'pairs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(41, 4, 'Dustbins', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(42, 4, 'Dustpans', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(43, 4, 'Buckets', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(44, 4, 'Wheelbarrows', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(45, 5, 'A3 Envelopes', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(46, 5, 'A4 Envelopes', NULL, 0.000, 'packs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(47, 5, 'A5 Envelopes', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(48, 5, 'Markers (Permanent)', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(49, 5, 'Markers (Whiteboard)', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(50, 5, 'Color Papers', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(51, 5, 'Staple Wires', NULL, 0.000, 'boxes', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(52, 5, 'Paper Clips', NULL, 0.000, 'boxes', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(53, 5, 'Chalk (White)', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(54, 5, 'Chalk (Colored)', NULL, 0.000, 'boxes', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(55, 5, 'Pens (Blue)', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(56, 5, 'Pens (Black)', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(57, 5, 'Pens (Red)', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(58, 5, 'Pencils', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(59, 5, 'Rubbers (Erasers)', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(60, 5, 'Office Glue', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(61, 5, 'Stick Glue', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(62, 5, 'Sticky Notes', NULL, 0.000, 'pads', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(63, 5, 'Stapler Machines', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(64, 5, 'Stapler Removers', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(65, 5, 'Hole Punchers', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(66, 5, 'Rulers (30cm)', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(67, 5, 'Scissors', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(68, 5, 'Calculators (Basic)', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(69, 5, 'Bulldog Clips', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(70, 5, 'Highlighter Markers', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(71, 5, 'Correction Fluid', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(72, 5, 'Correction Tape', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(73, 5, 'Manila Envelopes', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(74, 5, 'Sticker Labels', NULL, 0.000, 'sheets', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(75, 6, 'Double Gang Switches', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(76, 6, 'Single Gang Switches', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(77, 6, 'Lamp Holders', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(78, 6, 'Single Sockets', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(79, 6, 'Double Sockets', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(80, 6, 'Bulbs (LED 10W)', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(81, 6, 'Bulbs (LED 20W)', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(82, 6, 'Bulbs (LED 40W)', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(83, 6, 'Mounting Boxes', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(84, 6, 'PVC Conduit Pipes', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(85, 6, 'Electrical Cables (1.5mm)', NULL, 0.000, 'meters', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(86, 6, 'Electrical Cables (2.5mm)', NULL, 0.000, 'meters', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(87, 6, 'Socket Spanners', NULL, 0.000, 'sets', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(88, 6, 'Screwdrivers Set', NULL, 0.000, 'sets', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(89, 6, 'Hammers', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(90, 6, 'Combination Pliers', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(91, 6, 'Long Nose Pliers', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(92, 6, 'Measuring Tapes', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(93, 6, 'Padlocks', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(94, 6, 'Door Handles', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(95, 6, 'Door Hinges', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(96, 6, 'WD-40 Lubricant', NULL, 0.000, 'cans', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(97, 6, 'Painter Masking Tape', NULL, 0.000, 'rolls', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(98, 6, 'PVC Glue', NULL, 0.000, 'cans', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(99, 6, 'Super Glue', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(100, 2, 'Posho (Maize Flour)', NULL, 0.000, 'kg', 500.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(101, 2, 'Rice', NULL, 0.000, 'kg', 300.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(102, 2, 'Beans', NULL, 0.000, 'kg', 300.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(103, 2, 'Salt', NULL, 0.000, 'kg', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(104, 2, 'Cooking Oil', NULL, 0.000, 'liters', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(105, 2, 'Sugar', NULL, 0.000, 'kg', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(106, 2, 'Plates (Melamine)', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(107, 2, 'Plates (Ceramic)', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(108, 2, 'Cups (Plastic)', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(109, 2, 'Cups (Ceramic)', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(110, 2, 'Tablespoons', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(111, 2, 'Teaspoons', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(112, 2, 'Forks', NULL, 0.000, 'pcs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(113, 2, 'Kitchen Knives', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(114, 2, 'Sauce Pans', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(115, 2, 'Cooking Pots (Large)', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(116, 2, 'Cooking Pots (Medium)', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(117, 2, 'Frying Pans', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(118, 2, 'Thermos Flasks', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(119, 2, 'Water Jugs', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(120, 2, 'Charcoal', NULL, 0.000, 'bags', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(121, 2, 'Firewood', NULL, 0.000, 'bundles', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(122, 2, 'Tea Leaves', NULL, 0.000, 'kg', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(123, 2, 'Milk Powder', NULL, 0.000, 'kg', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(124, 2, 'Baking Flour', NULL, 0.000, 'kg', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(125, 2, 'Tomato Paste', NULL, 0.000, 'cans', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(126, 2, 'Onions', NULL, 0.000, 'kg', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(127, 2, 'Irish Potatoes', NULL, 0.000, 'kg', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(128, 2, 'Matooke (Green Bananas)', NULL, 0.000, 'bunches', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(129, 2, 'Cassava Flour', NULL, 0.000, 'kg', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(130, 2, 'Ghee', NULL, 0.000, 'kg', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(131, 2, 'Groundnut Paste', NULL, 0.000, 'kg', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(132, 2, 'Soy Flour', NULL, 0.000, 'kg', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(133, 2, 'Cabbage', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(134, 2, 'Dried Fish', NULL, 0.000, 'kg', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(135, 2, 'Pasta (Spaghetti/Macaroni)', NULL, 0.000, 'kg', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(136, 7, 'Chopping Boards', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(137, 7, 'Kitchen Towels', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(138, 7, 'Kitchen Aprons', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(139, 7, 'Oven Gloves', NULL, 0.000, 'pairs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(140, 7, 'Colanders', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(141, 7, 'Measuring Cups', NULL, 0.000, 'sets', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(142, 7, 'Water Dispensers', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(143, 7, 'Ice Cube Trays', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(144, 7, 'Food Storage Containers', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(145, 7, 'Serving Trays', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(146, 3, 'Sterile Surgical Gloves', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(147, 3, 'Latex Examination Gloves', NULL, 0.000, 'boxes', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(148, 3, 'Nitrile Examination Gloves', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(149, 3, 'Surgical Face Masks', NULL, 0.000, 'boxes', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(150, 3, 'N95 Face Masks', NULL, 0.000, 'boxes', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(151, 3, 'Syringes (5ml)', NULL, 0.000, 'packs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(152, 3, 'Syringes (10ml)', NULL, 0.000, 'packs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(153, 3, 'Cotton Wool', NULL, 0.000, 'rolls', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(154, 3, 'Gauze Swabs', NULL, 0.000, 'packs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(155, 3, 'Crepe Bandages', NULL, 0.000, 'rolls', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(156, 3, 'Elastic Bandages', NULL, 0.000, 'rolls', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(157, 3, 'Medical Adhesive Tape', NULL, 0.000, 'rolls', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(158, 3, 'Wound Dressings (Plaster)', NULL, 0.000, 'packs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(159, 3, 'Dettol Antiseptic', NULL, 0.000, 'liters', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(160, 3, 'Methylated Spirit', NULL, 0.000, 'liters', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(161, 3, 'Hydrogen Peroxide', NULL, 0.000, 'liters', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(162, 3, 'Betadine Solution', NULL, 0.000, 'liters', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(163, 3, 'Digital Thermometers', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(164, 3, 'Manual BP Machines', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(165, 3, 'Digital BP Machines', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(166, 3, 'Stethoscopes', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(167, 3, 'Tongue Depressors', NULL, 0.000, 'packs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(168, 3, 'Urine Test Strips', NULL, 0.000, 'packs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(169, 3, 'Specimen Containers', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(170, 3, 'Sharps Disposal Containers', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(171, 3, 'Disposable Bed Sheets', NULL, 0.000, 'packs', 50.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(172, 3, 'Disposable Protective Gowns', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(173, 3, 'Disposable Shoe Covers', NULL, 0.000, 'pairs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(174, 3, 'Disposable Hair Caps', NULL, 0.000, 'pcs', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(175, 9, 'HP Toner Cartridges', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(176, 9, 'Canon Toner Cartridges', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(177, 9, 'Epson Toner Cartridges', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(178, 9, 'A4 Printing Paper', NULL, 0.000, 'reams', 100.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(179, 9, 'A3 Printing Paper', NULL, 0.000, 'reams', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(180, 9, 'Flash Drives (16GB)', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(181, 9, 'Flash Drives (32GB)', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(182, 9, 'External Hard Drives (1TB)', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(183, 9, 'USB Keyboards', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(184, 9, 'USB Mice', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(185, 9, 'Mouse Pads', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(186, 9, 'USB Cables', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(187, 9, 'HDMI Cables', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(188, 9, 'VGA Cables', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(189, 9, 'Power Extension Strips', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(190, 9, 'UPS Batteries', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(191, 9, 'Cat6 Ethernet Cables', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(192, 9, 'Webcams', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(193, 9, 'Headphones', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(194, 9, 'Printer Label Sheets', NULL, 0.000, 'packs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:00', '2026-06-14 18:35:00'),
(195, 8, 'Office Desks', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(196, 8, 'Office Chairs', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(197, 8, 'Visitor Chairs', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(198, 8, '4-Drawer Filing Cabinets', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(199, 8, 'Bookshelves', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(200, 8, 'Large Whiteboards', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(201, 8, 'Small Whiteboards', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(202, 8, 'Cork Notice Boards', NULL, 0.000, 'pcs', 15.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(203, 8, 'Conference Tables', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(204, 8, 'Metal Storage Shelves', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(205, 8, 'Personal Lockers', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(206, 8, 'Waste Paper Baskets', NULL, 0.000, 'pcs', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(207, 10, 'Skeleton Anatomical Models', NULL, 0.000, 'pcs', 3.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(208, 10, 'Organ Anatomical Models', NULL, 0.000, 'sets', 3.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(209, 10, 'Resuscitation Mannequins', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(210, 10, 'Injection Practice Pads', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(211, 10, 'IV Training Arms', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(212, 10, 'Catheterization Models', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(213, 10, 'Baby Delivery Simulators', NULL, 0.000, 'pcs', 3.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(214, 10, 'First Aid Kits', NULL, 0.000, 'kits', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(215, 10, 'Portable Projectors', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(216, 10, 'Projector Screens', NULL, 0.000, 'pcs', 5.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(217, 10, 'Flip Chart Stands', NULL, 0.000, 'pcs', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(218, 10, 'Flip Chart Pads', NULL, 0.000, 'pads', 30.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(219, 10, 'Nursing Wall Charts', NULL, 0.000, 'sets', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(220, 10, 'Midwifery Wall Charts', NULL, 0.000, 'sets', 10.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01'),
(221, 10, 'Educational DVDs', NULL, 0.000, 'pcs', 20.000, 0.00, 'Main Store', 'active', '2026-06-14 18:35:01', '2026-06-14 18:35:01');

-- --------------------------------------------------------

--
-- Table structure for table `store_inventory_transactions`
--

CREATE TABLE `store_inventory_transactions` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `transaction_type` enum('add','remove','adjust','request_fulfilled','order_received','returned','damaged') NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `quantity_before` decimal(15,3) DEFAULT '0.000',
  `quantity_after` decimal(15,3) DEFAULT '0.000',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `reason` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_orders`
--

CREATE TABLE `store_orders` (
  `id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `supplier` varchar(200) DEFAULT 'Internal Requisition',
  `notes` text,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','pending_approval','approved','ordered','partially_received','received','cancelled') DEFAULT 'draft',
  `requested_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_order_items`
--

CREATE TABLE `store_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity_ordered` decimal(15,3) NOT NULL,
  `quantity_received` decimal(15,3) DEFAULT '0.000',
  `unit_price` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `status` enum('pending','received','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_requests`
--

CREATE TABLE `store_requests` (
  `id` int NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `requested_by` int NOT NULL,
  `department` varchar(200) DEFAULT NULL,
  `notes` text,
  `urgency` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','approved','partially_fulfilled','fulfilled','rejected','forwarded') DEFAULT 'pending',
  `forwarded_to` int DEFAULT NULL,
  `forwarded_to_role` varchar(100) DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `fulfilled_by` int DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_request_items`
--

CREATE TABLE `store_request_items` (
  `id` int NOT NULL,
  `request_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity_requested` decimal(15,3) NOT NULL,
  `quantity_fulfilled` decimal(15,3) DEFAULT '0.000',
  `unit_price` decimal(15,2) DEFAULT '0.00',
  `notes` text,
  `status` enum('pending','fulfilled','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` text,
  `program` varchar(100) NOT NULL,
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) DEFAULT 'Semester 1',
  `admission_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn') DEFAULT 'Active',
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_profiles`
--

CREATE TABLE `student_academic_profiles` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_of_study` int NOT NULL,
  `semester` varchar(50) NOT NULL,
  `gpa` decimal(3,2) DEFAULT '0.00',
  `academic_status` enum('Good Standing','Probation','Suspension') DEFAULT 'Good Standing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_activities`
--

CREATE TABLE `student_activities` (
  `id` int NOT NULL,
  `activity_number` varchar(50) NOT NULL,
  `activity_name` varchar(200) NOT NULL,
  `activity_type` enum('Sports','Cultural','Academic','Social','Religious','Workshop','Other') NOT NULL,
  `description` text,
  `activity_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `organizer_id` int NOT NULL,
  `max_participants` int DEFAULT NULL,
  `current_participants` int DEFAULT '0',
  `status` enum('Planning','Open for Registration','Registration Closed','In Progress','Completed','Cancelled') DEFAULT 'Planning',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_admissions`
--

CREATE TABLE `student_admissions` (
  `id` int NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `program` varchar(100) NOT NULL,
  `admission_date` date NOT NULL,
  `admission_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_counseling_sessions`
--

CREATE TABLE `student_counseling_sessions` (
  `id` int NOT NULL,
  `session_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `counselor_id` int DEFAULT NULL,
  `counselor_name` varchar(255) DEFAULT NULL,
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `session_duration_minutes` int DEFAULT NULL,
  `session_type` enum('Individual','Group','Family','Crisis') DEFAULT 'Individual',
  `issues_discussed` text,
  `advice_given` text,
  `referrals_made` text,
  `follow_up_required` tinyint(1) DEFAULT '1',
  `follow_up_date` date DEFAULT NULL,
  `session_outcome` text,
  `student_feedback` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_data_imports`
--

CREATE TABLE `student_data_imports` (
  `id` int NOT NULL,
  `source_file` varchar(255) NOT NULL,
  `intake_set` varchar(50) DEFAULT NULL,
  `total_records` int DEFAULT NULL,
  `imported_records` int DEFAULT NULL,
  `skipped_records` int DEFAULT NULL,
  `import_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `imported_by` int DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `error_log` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline`
--

CREATE TABLE `student_discipline` (
  `id` int NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `incident_type` enum('Absence','Misconduct','Academic Dishonesty','Other') NOT NULL,
  `action_taken` enum('Warning','Probation','Suspension','Expulsion') NOT NULL,
  `status` enum('Pending','Resolved','Closed') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_emergency_contacts`
--

CREATE TABLE `student_emergency_contacts` (
  `id` int NOT NULL,
  `contact_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_relationship` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_address` text,
  `is_primary` tinyint(1) DEFAULT '0',
  `notified` tinyint(1) DEFAULT '0',
  `last_notified` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

CREATE TABLE `student_fee_assignments` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) NOT NULL,
  `program_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `year_of_study` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `assigned_date` date NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_incidents`
--

CREATE TABLE `student_health_incidents` (
  `id` int NOT NULL,
  `incident_id` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `incident_type` enum('Injury','Illness','Allergic Reaction','Mental Health','Emergency','Other') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `severity` enum('Minor','Moderate','Severe','Critical') NOT NULL,
  `first_aid_provided` tinyint(1) DEFAULT '0',
  `first_aid_description` text,
  `hospitalized` tinyint(1) DEFAULT '0',
  `hospital_name` varchar(255) DEFAULT NULL,
  `attended_by` varchar(255) DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT '0',
  `parent_phone` varchar(20) DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `reported_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved` tinyint(1) DEFAULT '0',
  `resolution_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_records`
--

CREATE TABLE `student_health_records` (
  `id` int NOT NULL,
  `record_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `allergies` text,
  `chronic_conditions` text,
  `medications` text,
  `emergency_contact_name` varchar(200) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `insurance_provider` varchar(200) DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `last_checkup_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

CREATE TABLE `student_invoices` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_index_number` varchar(20) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `fee_structure_id` int NOT NULL,
  `fee_assignment_id` int DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` int NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `tuition_amount` decimal(15,2) DEFAULT '0.00',
  `accommodation_amount` decimal(15,2) DEFAULT '0.00',
  `clinical_amount` decimal(15,2) DEFAULT '0.00',
  `library_amount` decimal(15,2) DEFAULT '0.00',
  `examination_amount` decimal(15,2) DEFAULT '0.00',
  `registration_amount` decimal(15,2) DEFAULT '0.00',
  `technology_amount` decimal(15,2) DEFAULT '0.00',
  `other_amount` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT '0.00',
  `penalties_amount` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled','written_off') DEFAULT 'pending',
  `payment_status` enum('not_started','in_progress','completed','overdue') DEFAULT 'not_started',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

CREATE TABLE `student_penalties` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `penalty_config_id` int NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `calculated_on` decimal(15,2) DEFAULT NULL,
  `days_late` int DEFAULT NULL,
  `penalty_date` date NOT NULL,
  `status` enum('pending','paid','waived','written_off') DEFAULT 'pending',
  `waived_by` int DEFAULT NULL,
  `waived_reason` text,
  `paid_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_photos`
--

CREATE TABLE `student_photos` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `old_photo_path` varchar(500) DEFAULT NULL,
  `new_photo_path` varchar(500) DEFAULT NULL,
  `photo_action` enum('upload','update','delete','print') NOT NULL,
  `action_by` int DEFAULT NULL,
  `action_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profile_edits`
--

CREATE TABLE `student_profile_edits` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `field_changed` varchar(100) NOT NULL,
  `old_value` text,
  `new_value` text,
  `edit_reason` text,
  `edited_by` int DEFAULT NULL,
  `edit_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `action_type` enum('edit','photo_update','photo_delete','photo_print') DEFAULT 'edit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_reports`
--

CREATE TABLE `student_reports` (
  `id` int NOT NULL,
  `report_number` varchar(50) NOT NULL,
  `report_type` enum('student_list','single_profile','bulk_export','id_cards','transcripts','class_list','search_results') NOT NULL,
  `report_format` enum('pdf','excel','csv','html') DEFAULT 'pdf',
  `generated_by` int DEFAULT NULL,
  `generation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `report_data` longtext,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `records_count` int DEFAULT NULL,
  `status` enum('generated','printed','downloaded','archived') DEFAULT 'generated',
  `print_count` int DEFAULT '0',
  `last_printed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_room_inspections`
--

CREATE TABLE `student_room_inspections` (
  `id` int NOT NULL,
  `inspection_id` varchar(50) NOT NULL,
  `room_id` int DEFAULT NULL,
  `room_number` varchar(50) NOT NULL,
  `inspection_date` date NOT NULL,
  `inspected_by` int DEFAULT NULL,
  `inspector_name` varchar(255) DEFAULT NULL,
  `cleanliness_score` int DEFAULT NULL,
  `maintenance_issues` text,
  `disciplinary_issues` text,
  `items_confiscated` text,
  `action_taken` text,
  `follow_up_required` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_search_index`
--

CREATE TABLE `student_search_index` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `search_field` varchar(50) NOT NULL,
  `search_value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_search_view`
-- (See below for the actual view)
--
CREATE TABLE `student_search_view` (
`id` int
,`student_number` varchar(50)
,`national_id` varchar(50)
,`index_number` varchar(50)
,`registration_number` varchar(50)
,`full_name` varchar(255)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
,`program` varchar(100)
,`intake_set` varchar(20)
,`year_of_study` int
,`semester` varchar(50)
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','Transferred')
,`district` varchar(100)
,`guardian_name` varchar(200)
,`guardian_phone` varchar(20)
,`current_photo` varchar(500)
,`has_photo` int
,`staff_dashboard` binary(0)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_welfare_cases`
--

CREATE TABLE `student_welfare_cases` (
  `id` int NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `case_type` enum('Academic Support','Personal Counseling','Financial Support','Health Issues','Disciplinary Issues','Homesickness','Family Problems','Other') NOT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `case_description` text NOT NULL,
  `immediate_actions` text,
  `status` enum('Open','In Progress','Under Review','Resolved','Closed') DEFAULT 'Open',
  `assigned_warden` int NOT NULL,
  `follow_up_required` tinyint(1) DEFAULT '1',
  `follow_up_date` date DEFAULT NULL,
  `parent_contacted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int NOT NULL,
  `log_type` enum('info','warning','error','debug','security','audit') NOT NULL,
  `log_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `log_message` text NOT NULL,
  `context_data` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` enum('text','number','boolean','file','json','email','url') DEFAULT 'text',
  `description` text,
  `category` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name for display on documents', 'general', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(2, 'school_address', 'P.O. Box 12345, Kampala, Uganda', 'text', 'School address for documents', 'general', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(3, 'school_phone', '+256 123 456 789', 'text', 'School phone number', 'general', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(4, 'school_email', 'info@isnm.edu.ug', 'email', 'School email address', 'general', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(5, 'school_website', 'www.isnm.edu.ug', 'url', 'School website URL', 'general', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(6, 'academic_year', '2025/2026', 'text', 'Current academic year', 'academic', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(7, 'semester', 'Semester 2', 'text', 'Current semester', 'academic', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(8, 'currency', 'UGX', 'text', 'Default currency', 'financial', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(9, 'max_login_attempts', '5', 'number', 'Maximum login attempts before account lock', 'security', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(10, 'session_timeout', '30', 'number', 'Session timeout in minutes', 'security', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(11, 'password_min_length', '8', 'number', 'Minimum password length', 'security', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(12, 'enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication', 'security', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(13, 'auto_backup_enabled', 'true', 'boolean', 'Enable automatic backups', 'system', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(14, 'backup_frequency', 'daily', 'text', 'Backup frequency', 'system', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(15, 'max_upload_size', '10485760', 'number', 'Maximum upload file size in bytes', 'system', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(16, 'default_language', 'en', 'text', 'Default system language', 'ui', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(17, 'timezone', 'Africa/Kampala', 'text', 'System timezone', 'ui', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(18, 'email_notifications_enabled', 'true', 'boolean', 'Enable email notifications', 'notifications', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(19, 'sms_notifications_enabled', 'false', 'boolean', 'Enable SMS notifications', 'notifications', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(20, 'dashboard_refresh_interval', '60', 'number', 'Dashboard auto-refresh interval in seconds', 'ui', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(21, 'enable_real_time_updates', 'true', 'boolean', 'Enable real-time updates', 'system', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(22, 'max_api_requests_per_hour', '1000', 'number', 'Maximum API requests per hour', 'api', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(23, 'enable_audit_logging', 'true', 'boolean', 'Enable audit logging', 'security', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(24, 'document_retention_days', '365', 'number', 'Document retention period in days', 'documents', 0, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(25, 'enable_advanced_search', 'true', 'boolean', 'Enable advanced search functionality', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(26, 'enable_smart_suggestions', 'true', 'boolean', 'Enable smart suggestions', 'ui', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(27, 'enable_performance_monitoring', 'true', 'boolean', 'Enable performance monitoring', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(28, 'enable_data_sync', 'true', 'boolean', 'Enable data synchronization', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(29, 'enable_real_time_notifications', 'true', 'boolean', 'Enable real-time notifications', 'notifications', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(30, 'enable_email_queue', 'true', 'boolean', 'Enable email notification queue', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(31, 'enable_analytics_cache', 'true', 'boolean', 'Enable analytics caching', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(32, 'enable_backup_management', 'true', 'boolean', 'Enable backup management', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(33, 'enable_api_access', 'true', 'boolean', 'Enable API access', 'api', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(34, 'enable_user_preferences', 'true', 'boolean', 'Enable user preferences', 'ui', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(35, 'enable_advanced_reports', 'true', 'boolean', 'Enable advanced reports', 'reports', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(36, 'enable_system_logging', 'true', 'boolean', 'Enable comprehensive system logging', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(37, 'enable_search_indexing', 'true', 'boolean', 'Enable search indexing', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(38, 'enable_data_sync_status', 'true', 'boolean', 'Enable data sync status tracking', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(39, 'enable_performance_metrics', 'true', 'boolean', 'Enable performance metrics', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(40, 'enable_smart_suggestions_db', 'true', 'boolean', 'Enable smart suggestions database', 'ui', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(41, 'enable_email_notifications_queue', 'true', 'boolean', 'Enable email notifications queue', 'notifications', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(42, 'enable_backup_automation', 'true', 'boolean', 'Enable backup automation', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36'),
(43, 'enable_system_health_monitoring', 'true', 'boolean', 'Enable system health monitoring', 'system', 1, '2026-06-14 19:50:36', '2026-06-14 19:50:36');

-- --------------------------------------------------------

--
-- Table structure for table `training_programs`
--

CREATE TABLE `training_programs` (
  `id` int NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_category` varchar(100) DEFAULT NULL,
  `description` text,
  `provider_name` varchar(255) DEFAULT NULL,
  `provider_contact` varchar(255) DEFAULT NULL,
  `target_audience` varchar(255) DEFAULT NULL,
  `program_duration_days` int DEFAULT NULL,
  `program_start_date` date DEFAULT NULL,
  `program_end_date` date DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `budget_required` decimal(15,2) DEFAULT NULL,
  `status` enum('planned','ongoing','completed','cancelled') DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_generation_log`
--

CREATE TABLE `transcript_generation_log` (
  `id` int NOT NULL,
  `transcript_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `generation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `purpose` varchar(200) DEFAULT NULL,
  `copies` int DEFAULT '1',
  `status` enum('Pending','Approved','Generated','Rejected','Collected') DEFAULT 'Pending',
  `approval_comments` text,
  `file_path` varchar(500) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_logs`
--

CREATE TABLE `trip_logs` (
  `id` int NOT NULL,
  `trip_number` varchar(50) NOT NULL,
  `vehicle_id` int NOT NULL,
  `driver_id` int NOT NULL,
  `route_name` varchar(200) NOT NULL,
  `trip_type` enum('Morning Route','Evening Route','Field Trip','Medical Transfer','Other') NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `trip_date` date NOT NULL,
  `start_location` varchar(200) NOT NULL,
  `end_location` varchar(200) NOT NULL,
  `passengers_count` int DEFAULT '0',
  `distance_km` decimal(10,2) DEFAULT NULL,
  `fuel_consumed` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Transit','Completed','Cancelled','Delayed') DEFAULT 'Scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `universal_student_profiles`
--

CREATE TABLE `universal_student_profiles` (
  `id` int NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `intake_set` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) DEFAULT 'Semester 1',
  `academic_year` varchar(20) DEFAULT NULL,
  `address` text,
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `parish` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_address` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Ugandan',
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `photo_path` varchar(500) DEFAULT NULL,
  `photo_uploaded` tinyint(1) DEFAULT '0',
  `photo_upload_date` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','Transferred') DEFAULT 'Active',
  `enrollment_status` enum('Full Time','Part Time','Distance') DEFAULT 'Full Time',
  `gpa` decimal(3,2) DEFAULT '0.00',
  `cgpa` decimal(3,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `universal_student_profiles`
--
DELIMITER $$
CREATE TRIGGER `update_full_name` BEFORE UPDATE ON `universal_student_profiles` FOR EACH ROW BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reporting`
--

CREATE TABLE `ura_reporting` (
  `id` int NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_type` enum('VAT Return','Income Tax','Paye Tax','Withholding Tax','Customs','Other') NOT NULL,
  `reporting_period` varchar(50) NOT NULL,
  `tax_year` varchar(10) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `submission_date` date DEFAULT NULL,
  `status` enum('Draft','Submitted','Accepted','Rejected','Amended') DEFAULT 'Draft',
  `receipt_number` varchar(50) DEFAULT NULL,
  `prepared_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reports`
--

CREATE TABLE `ura_reports` (
  `id` int NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_period` varchar(20) NOT NULL,
  `report_data` json DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('draft','submitted','approved') DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `users`
-- (See below for the actual view)
--
CREATE TABLE `users` (
`id` int
,`username` varchar(50)
,`user_name` varchar(100)
,`email` varchar(100)
,`password` varchar(255)
,`position` varchar(100)
,`department` varchar(100)
,`role_id` int
,`role_name` varchar(100)
,`role_level` enum('Executive','Management','Academic','Support','Administrative')
,`dashboard_path` varchar(255)
,`status` enum('Active','Inactive','On Leave','Suspended')
,`phone` varchar(20)
,`address` text
,`hire_date` date
,`last_login` timestamp
,`login_attempts` int
,`locked_until` timestamp
,`is_first_login` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` longtext,
  `preference_type` enum('ui','notifications','security','workflow','display') DEFAULT 'ui',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int NOT NULL,
  `vehicle_code` varchar(50) NOT NULL,
  `vehicle_name` varchar(200) NOT NULL,
  `vehicle_type` enum('Bus','Van','Car','Motorcycle','Other') NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `capacity` int NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `fuel_type` enum('Petrol','Diesel','Electric','Hybrid') DEFAULT 'Diesel',
  `status` enum('Available','In Use','Maintenance','Out of Service','Retired') DEFAULT 'Available',
  `purchase_date` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `assigned_driver` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id` int NOT NULL,
  `log_number` varchar(50) NOT NULL,
  `visitor_name` varchar(200) NOT NULL,
  `visitor_type` enum('Parent','Guardian','Official','Contractor','Delivery','Other') NOT NULL,
  `visitor_id_number` varchar(100) DEFAULT NULL,
  `visitor_phone` varchar(20) DEFAULT NULL,
  `purpose` varchar(200) NOT NULL,
  `person_visiting` varchar(200) NOT NULL,
  `visit_date` date NOT NULL,
  `check_in_time` time NOT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('Checked In','Checked Out','Overstay') DEFAULT 'Checked In',
  `authorized_by` int NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_history`
--

CREATE TABLE `work_history` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `position_title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `key_achievements` text,
  `reason_for_transfer_or_departure` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure for view `all_students_view`
--
DROP TABLE IF EXISTS `all_students_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `all_students_view`  AS SELECT `sp`.`id` AS `id`, `sp`.`student_number` AS `student_number`, `sp`.`national_id` AS `national_id`, `sp`.`index_number` AS `index_number`, `sp`.`registration_number` AS `registration_number`, `sp`.`first_name` AS `first_name`, `sp`.`middle_name` AS `middle_name`, `sp`.`last_name` AS `last_name`, `sp`.`full_name` AS `full_name`, `sp`.`email` AS `email`, `sp`.`phone` AS `phone`, `sp`.`date_of_birth` AS `date_of_birth`, `sp`.`gender` AS `gender`, `sp`.`program` AS `program`, `sp`.`program_type` AS `program_type`, `sp`.`intake_set` AS `intake_set`, `sp`.`intake_date` AS `intake_date`, `sp`.`year_of_study` AS `year_of_study`, `sp`.`semester` AS `semester`, `sp`.`academic_year` AS `academic_year`, `sp`.`address` AS `address`, `sp`.`district` AS `district`, `sp`.`county` AS `county`, `sp`.`sub_county` AS `sub_county`, `sp`.`parish` AS `parish`, `sp`.`village` AS `village`, `sp`.`guardian_name` AS `guardian_name`, `sp`.`guardian_phone` AS `guardian_phone`, `sp`.`guardian_relationship` AS `guardian_relationship`, `sp`.`guardian_address` AS `guardian_address`, `sp`.`emergency_contact_name` AS `emergency_contact_name`, `sp`.`emergency_contact_phone` AS `emergency_contact_phone`, `sp`.`emergency_contact_relationship` AS `emergency_contact_relationship`, `sp`.`nationality` AS `nationality`, `sp`.`religion` AS `religion`, `sp`.`marital_status` AS `marital_status`, `sp`.`photo_path` AS `photo_path`, `sp`.`photo_uploaded` AS `photo_uploaded`, `sp`.`photo_upload_date` AS `photo_upload_date`, `sp`.`status` AS `status`, `sp`.`enrollment_status` AS `enrollment_status`, `sp`.`gpa` AS `gpa`, `sp`.`cgpa` AS `cgpa`, `sp`.`created_by` AS `created_by`, `sp`.`updated_by` AS `updated_by`, `sp`.`created_at` AS `created_at`, `sp`.`updated_at` AS `updated_at`, (case when (`sp`.`photo_uploaded` = true) then concat('Photo Available: ',`sp`.`photo_path`) else 'No Photo Available' end) AS `photo_status` FROM `universal_student_profiles` AS `sp` ;

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
-- Structure for view `staff_login_view`
--
DROP TABLE IF EXISTS `staff_login_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `staff_login_view`  AS SELECT `s`.`id` AS `id`, `s`.`staff_id` AS `staff_id`, `s`.`full_name` AS `full_name`, `s`.`email` AS `email`, `s`.`position` AS `position`, `s`.`department` AS `department`, `sr`.`role_name` AS `role_name`, `sr`.`role_level` AS `role_level`, `sr`.`dashboard_path` AS `dashboard_path`, `s`.`status` AS `status`, `s`.`last_login` AS `last_login`, `s`.`login_attempts` AS `login_attempts`, `s`.`locked_until` AS `locked_until`, `s`.`is_first_login` AS `is_first_login`, (case when (`s`.`locked_until` > now()) then 'Locked' when (`s`.`login_attempts` >= 5) then 'Warning' else 'Active' end) AS `account_status` FROM (`staff` `s` join `staff_roles` `sr` on((`s`.`role_id` = `sr`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `student_search_view`
--
DROP TABLE IF EXISTS `student_search_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_search_view`  AS SELECT `sp`.`id` AS `id`, `sp`.`student_number` AS `student_number`, `sp`.`national_id` AS `national_id`, `sp`.`index_number` AS `index_number`, `sp`.`registration_number` AS `registration_number`, `sp`.`full_name` AS `full_name`, `sp`.`first_name` AS `first_name`, `sp`.`last_name` AS `last_name`, `sp`.`email` AS `email`, `sp`.`phone` AS `phone`, `sp`.`program` AS `program`, `sp`.`intake_set` AS `intake_set`, `sp`.`year_of_study` AS `year_of_study`, `sp`.`semester` AS `semester`, `sp`.`status` AS `status`, `sp`.`district` AS `district`, `sp`.`guardian_name` AS `guardian_name`, `sp`.`guardian_phone` AS `guardian_phone`, `sp`.`photo_path` AS `current_photo`, coalesce(`sp`.`photo_uploaded`,false) AS `has_photo`, NULL AS `staff_dashboard` FROM `universal_student_profiles` AS `sp` ;

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
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `calendar_id` (`calendar_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_calendar_id` (`calendar_id`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `graded_by` (`graded_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_academic_year` (`academic_year`);

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
-- Indexes for table `access_control_logs`
--
ALTER TABLE `access_control_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_number` (`log_number`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_log_number` (`log_number`),
  ADD KEY `idx_access_type` (`access_type`),
  ADD KEY `idx_access_time` (`access_time`),
  ADD KEY `idx_access_point` (`access_point`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `accreditation_management`
--
ALTER TABLE `accreditation_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accreditation_code` (`accreditation_code`),
  ADD KEY `responsible_person` (`responsible_person`),
  ADD KEY `idx_accreditation_code` (`accreditation_code`),
  ADD KEY `idx_status` (`accreditation_status`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity` (`activity`),
  ADD KEY `idx_module_accessed` (`module_accessed`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `advanced_reports`
--
ALTER TABLE `advanced_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_name` (`report_name`),
  ADD KEY `idx_report_name` (`report_name`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_cache_key` (`cache_key`),
  ADD KEY `idx_cache_type` (`cache_type`),
  ADD KEY `idx_expiry_time` (`expiry_time`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_key_name` (`key_name`),
  ADD KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `appraisal_periods`
--
ALTER TABLE `appraisal_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `period_name` (`period_name`);

--
-- Indexes for table `appraisal_ratings`
--
ALTER TABLE `appraisal_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appraisal` (`appraisal_id`),
  ADD KEY `indicator_id` (`indicator_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `idx_category` (`asset_category_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`staff_id`,`attendance_date`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_status` (`attendance_status`);

--
-- Indexes for table `backup_management`
--
ALTER TABLE `backup_management`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_name` (`backup_name`),
  ADD KEY `idx_backup_type` (`backup_type`),
  ADD KEY `idx_backup_status` (`backup_status`),
  ADD KEY `idx_created_by` (`created_by`);

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
  ADD UNIQUE KEY `budget_code` (`budget_code`),
  ADD KEY `idx_cost_center` (`cost_center_id`),
  ADD KEY `idx_year` (`academic_year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_budget` (`budget_id`);

--
-- Indexes for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `budget_code` (`budget_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_budget_code` (`budget_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `bursar_settings`
--
ALTER TABLE `bursar_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `bursar_users`
--
ALTER TABLE `bursar_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cache_management`
--
ALTER TABLE `cache_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_cache_key` (`cache_key`),
  ADD KEY `idx_cache_type` (`cache_type`),
  ADD KEY `idx_expiry_time` (`expiry_time`);

--
-- Indexes for table `cashbook`
--
ALTER TABLE `cashbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`transaction_date`);

--
-- Indexes for table `cash_book`
--
ALTER TABLE `cash_book`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_reference` (`reference_id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`);

--
-- Indexes for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chemical_code` (`chemical_code`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_chemical_code` (`chemical_code`),
  ADD KEY `idx_chemical_type` (`chemical_type`),
  ADD KEY `idx_hazard_class` (`hazard_class`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placement_number` (`placement_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_placement_number` (`placement_number`);

--
-- Indexes for table `communication_log`
--
ALTER TABLE `communication_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_date` (`sent_date`);

--
-- Indexes for table `compliance_records`
--
ALTER TABLE `compliance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_compliance_type` (`compliance_type`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cost_center_code` (`cost_center_code`);

--
-- Indexes for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_number` (`session_number`),
  ADD KEY `idx_session_number` (`session_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_counselor_id` (`counselor_id`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_academic_year` (`academic_year`);

--
-- Indexes for table `course_registrations`
--
ALTER TABLE `course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_registration_number` (`registration_number`);

--
-- Indexes for table `dashboard_updates`
--
ALTER TABLE `dashboard_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_update_type` (`update_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `data_sync_status`
--
ALTER TABLE `data_sync_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_sync_status` (`sync_status`),
  ADD KEY `idx_last_sync` (`last_sync`);

--
-- Indexes for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_id` (`budget_id`);

--
-- Indexes for table `director_news`
--
ALTER TABLE `director_news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_published_at` (`published_at`);

--
-- Indexes for table `disciplinary_actions`
--
ALTER TABLE `disciplinary_actions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `disciplinary_number` (`disciplinary_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `idx_case_number` (`case_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `document_generation_log`
--
ALTER TABLE `document_generation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_generated_by` (`generated_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_name` (`template_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_template_name` (`template_name`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `duty_roster`
--
ALTER TABLE `duty_roster`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roster_id` (`roster_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_duty_date` (`duty_date`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roster_number` (`roster_number`),
  ADD KEY `replacement_warden` (`replacement_warden`),
  ADD KEY `idx_roster_number` (`roster_number`),
  ADD KEY `idx_warden_id` (`warden_id`),
  ADD KEY `idx_duty_date` (`duty_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `email_notifications_queue`
--
ALTER TABLE `email_notifications_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_recipient_email` (`recipient_email`),
  ADD KEY `idx_email_type` (`email_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_type` (`contact_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_end_date` (`contract_end_date`);

--
-- Indexes for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `staff_category_id` (`staff_category_id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_error_code` (`error_code`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `examination_records`
--
ALTER TABLE `examination_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_number` (`exam_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_exam_number` (`exam_number`),
  ADD KEY `workflow_id` (`workflow_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `hod_id` (`hod_id`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenditure_code` (`expenditure_code`),
  ADD KEY `idx_cost_center` (`cost_center_id`),
  ADD KEY `idx_budget` (`budget_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expense_date` (`expense_date`);

--
-- Indexes for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenditure_number` (`expenditure_number`),
  ADD KEY `budget_id` (`budget_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `idx_expenditure_number` (`expenditure_number`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `fee_accounts`
--
ALTER TABLE `fee_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_fee_type` (`fee_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adjustment_number` (`adjustment_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_adjustment_number` (`adjustment_number`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fee_code` (`fee_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_fee_code` (`fee_code`),
  ADD KEY `idx_fee_category` (`fee_category`);

--
-- Indexes for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `idx_record_type` (`record_type`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_recorded_by` (`recorded_by`);

--
-- Indexes for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generated_date` (`generated_date`);

--
-- Indexes for table `fuel_management`
--
ALTER TABLE `fuel_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fuel_number` (`fuel_number`),
  ADD KEY `filled_by` (`filled_by`),
  ADD KEY `idx_fuel_number` (`fuel_number`),
  ADD KEY `idx_vehicle_id` (`vehicle_id`),
  ADD KEY `idx_fueling_date` (`fueling_date`);

--
-- Indexes for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_number` (`entry_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_entry_number` (`entry_number`),
  ADD KEY `idx_account_code` (`account_code`);

--
-- Indexes for table `generated_documents`
--
ALTER TABLE `generated_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `access_code` (`access_code`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_generated_by` (`generated_by`),
  ADD KEY `idx_generation_date` (`generation_date`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_access_code` (`access_code`),
  ADD KEY `idx_is_public` (`is_public`);

--
-- Indexes for table `grade_change_history`
--
ALTER TABLE `grade_change_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_workflow_number` (`workflow_number`),
  ADD KEY `idx_examination_record_id` (`examination_record_id`),
  ADD KEY `idx_change_timestamp` (`change_timestamp`);

--
-- Indexes for table `grade_scales`
--
ALTER TABLE `grade_scales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_letter` (`grade_letter`),
  ADD KEY `idx_grade_letter` (`grade_letter`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `grading_approval_workflow`
--
ALTER TABLE `grading_approval_workflow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workflow_number` (`workflow_number`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `hod_reviewed_by` (`hod_reviewed_by`),
  ADD KEY `registrar_approved_by` (`registrar_approved_by`),
  ADD KEY `principal_approved_by` (`principal_approved_by`),
  ADD KEY `idx_workflow_number` (`workflow_number`),
  ADD KEY `idx_current_stage` (`current_stage`),
  ADD KEY `idx_examination_record_id` (`examination_record_id`);

--
-- Indexes for table `grading_notifications`
--
ALTER TABLE `grading_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notification_id` (`notification_id`),
  ADD KEY `workflow_number` (`workflow_number`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_notification_id` (`notification_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_notification_type` (`notification_type`);

--
-- Indexes for table `health_incidents`
--
ALTER TABLE `health_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `incident_number` (`incident_number`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_incident_number` (`incident_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `allocation_number` (`allocation_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_allocation_number` (`allocation_number`);

--
-- Indexes for table `hostel_management`
--
ALTER TABLE `hostel_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`);

--
-- Indexes for table `hr_activity_logs`
--
ALTER TABLE `hr_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `hr_reports`
--
ALTER TABLE `hr_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `idx_report_type` (`report_type`);

--
-- Indexes for table `hr_settings`
--
ALTER TABLE `hr_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `hr_users`
--
ALTER TABLE `hr_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `incident_number` (`incident_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `interview_scheduling`
--
ALTER TABLE `interview_scheduling`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application` (`application_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_item_code` (`item_code`),
  ADD KEY `idx_inventory_department` (`department`),
  ADD KEY `idx_inventory_report_to` (`report_to`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_number` (`report_number`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_inventory_report_number` (`report_number`),
  ADD KEY `idx_inventory_report_status` (`request_status`),
  ADD KEY `idx_inventory_report_department` (`department`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `idx_transaction_number` (`transaction_number`);

--
-- Indexes for table `invoice_records`
--
ALTER TABLE `invoice_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `it_infrastructure`
--
ALTER TABLE `it_infrastructure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `maintained_by` (`maintained_by`),
  ADD KEY `idx_asset_code` (`asset_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `idx_vacancy` (`vacancy_id`),
  ADD KEY `idx_status` (`application_status`),
  ADD KEY `idx_email` (`applicant_email`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `offer_number` (`offer_number`),
  ADD KEY `idx_application` (`application_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `job_vacancies`
--
ALTER TABLE `job_vacancies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vacancy_code` (`vacancy_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_closing_date` (`closing_date`);

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
-- Indexes for table `lab_equipment_maintenance`
--
ALTER TABLE `lab_equipment_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maintenance_number` (`maintenance_number`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `idx_maintenance_number` (`maintenance_number`),
  ADD KEY `idx_equipment_id` (`equipment_id`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `experiment_id` (`experiment_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `lab_technician_id` (`lab_technician_id`),
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
-- Indexes for table `lab_safety_records`
--
ALTER TABLE `lab_safety_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `safety_number` (`safety_number`),
  ADD KEY `inspector_id` (`inspector_id`),
  ADD KEY `idx_safety_number` (`safety_number`),
  ADD KEY `idx_lab_id` (`lab_id`),
  ADD KEY `idx_inspection_date` (`inspection_date`),
  ADD KEY `idx_overall_status` (`overall_status`);

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
-- Indexes for table `leave_balance`
--
ALTER TABLE `leave_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_balance` (`staff_id`,`leave_type_id`,`academic_year`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_year` (`academic_year`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_request_number` (`leave_request_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_type_name` (`leave_type_name`);

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
-- Indexes for table `library_management`
--
ALTER TABLE `library_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `book_id` (`book_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `library_transactions`
--
ALTER TABLE `library_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_transaction_number` (`transaction_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `meal_tracking`
--
ALTER TABLE `meal_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meal_number` (`meal_number`),
  ADD KEY `served_by` (`served_by`),
  ADD KEY `idx_meal_number` (`meal_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_meal_date` (`meal_date`),
  ADD KEY `idx_meal_type` (`meal_type`);

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
-- Indexes for table `news_images`
--
ALTER TABLE `news_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news_id` (`news_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role_target` (`role_target`),
  ADD KEY `idx_notification_type` (`notification_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_expires_at` (`expires_at`);

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
-- Indexes for table `onboarding_checklist`
--
ALTER TABLE `onboarding_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `partnership_code` (`partnership_code`),
  ADD KEY `responsible_person` (`responsible_person`),
  ADD KEY `idx_partnership_code` (`partnership_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_reference` (`payment_reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_name` (`method_name`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_payment_number` (`payment_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslip_number` (`payslip_number`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_month_year` (`month`,`year`),
  ADD KEY `idx_processing_date` (`processing_date`),
  ADD KEY `idx_payslip_number` (`payslip_number`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslip_number` (`payslip_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_salary_month` (`salary_month`);

--
-- Indexes for table `penalty_config`
--
ALTER TABLE `penalty_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `performance_indicators`
--
ALTER TABLE `performance_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `indicator_code` (`indicator_code`);

--
-- Indexes for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_metric_type` (`metric_type`),
  ADD KEY `idx_period_type` (`period_type`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Indexes for table `professional_licenses`
--
ALTER TABLE `professional_licenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_program_code` (`program_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment` (`payment_id`);

--
-- Indexes for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qa_code` (`qa_code`),
  ADD KEY `assessed_by` (`assessed_by`),
  ADD KEY `idx_qa_code` (`qa_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `real_time_updates`
--
ALTER TABLE `real_time_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_update_type` (`update_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `receipt_templates`
--
ALTER TABLE `receipt_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_name` (`template_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_template_name` (`template_name`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `recruitment_applications`
--
ALTER TABLE `recruitment_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_application_number` (`application_number`),
  ADD KEY `idx_job_id` (`job_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `recruitment_jobs`
--
ALTER TABLE `recruitment_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_code` (`job_code`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `idx_job_code` (`job_code`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_student_id` (`student_id`),
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
-- Indexes for table `requirement_clearances`
--
ALTER TABLE `requirement_clearances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_item` (`student_id`,`item_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_cleared` (`cleared`);

--
-- Indexes for table `requirement_items`
--
ALTER TABLE `requirement_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_name` (`item_name`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_category` (`item_category`);

--
-- Indexes for table `requirement_messages`
--
ALTER TABLE `requirement_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `research_projects`
--
ALTER TABLE `research_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `lead_researcher` (`lead_researcher`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_project_code` (`project_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `result_publication`
--
ALTER TABLE `result_publication`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publication_id` (`publication_id`),
  ADD KEY `published_by` (`published_by`),
  ADD KEY `idx_publication_id` (`publication_id`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `room_inspections`
--
ALTER TABLE `room_inspections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inspection_number` (`inspection_number`),
  ADD KEY `inspector_id` (`inspector_id`),
  ADD KEY `idx_inspection_number` (`inspection_number`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_inspection_date` (`inspection_date`),
  ADD KEY `idx_overall_status` (`overall_status`);

--
-- Indexes for table `route_schedules`
--
ALTER TABLE `route_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_code` (`route_code`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `idx_route_code` (`route_code`),
  ADD KEY `idx_route_type` (`route_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `structure_code` (`structure_code`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scholarship_code` (`scholarship_code`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_index_number` (`student_index_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sponsor_type` (`sponsor_type`);

--
-- Indexes for table `search_index`
--
ALTER TABLE `search_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_entity_id` (`entity_id`);
ALTER TABLE `search_index` ADD FULLTEXT KEY `idx_searchable_content` (`searchable_content`);
ALTER TABLE `search_index` ADD FULLTEXT KEY `idx_keywords` (`keywords_text`);

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
-- Indexes for table `security_equipment`
--
ALTER TABLE `security_equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_code` (`equipment_code`),
  ADD KEY `maintained_by` (`maintained_by`),
  ADD KEY `idx_equipment_code` (`equipment_code`),
  ADD KEY `idx_equipment_type` (`equipment_type`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `incident_number` (`incident_number`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_incident_number` (`incident_number`),
  ADD KEY `idx_incident_type` (`incident_type`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `security_patrols`
--
ALTER TABLE `security_patrols`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patrol_number` (`patrol_number`),
  ADD KEY `idx_patrol_number` (`patrol_number`),
  ADD KEY `idx_guard_id` (`guard_id`),
  ADD KEY `idx_patrol_date` (`patrol_date`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `skills_laboratory`
--
ALTER TABLE `skills_laboratory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_code` (`lab_code`),
  ADD KEY `in_charge` (`in_charge`),
  ADD KEY `idx_lab_code` (`lab_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `skills_lab_sessions`
--
ALTER TABLE `skills_lab_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `lab_id` (`lab_id`),
  ADD KEY `lecturer_id` (`lecturer_id`),
  ADD KEY `idx_session_code` (`session_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `smart_suggestions`
--
ALTER TABLE `smart_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_suggestion_type` (`suggestion_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_is_dismissed` (`is_dismissed`);

--
-- Indexes for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sponsorship_code` (`sponsorship_code`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sponsorship_code` (`sponsorship_code`);

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
-- Indexes for table `staff_access_control`
--
ALTER TABLE `staff_access_control`
  ADD PRIMARY KEY (`id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_module_name` (`module_name`),
  ADD KEY `idx_access_level` (`access_level`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_module_accessed` (`module_accessed`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_announcements`
--
ALTER TABLE `staff_announcements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `announcement_number` (`announcement_number`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_announcement_date` (`announcement_date`);

--
-- Indexes for table `staff_appraisals`
--
ALTER TABLE `staff_appraisals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appraisal_number` (`appraisal_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_period` (`appraisal_period_id`),
  ADD KEY `appraiser_id` (`appraiser_id`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_audit_logs`
--
ALTER TABLE `staff_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_categories`
--
ALTER TABLE `staff_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `signed_by` (`signed_by`),
  ADD KEY `idx_contract_number` (`contract_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_dashboard_access`
--
ALTER TABLE `staff_dashboard_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_dashboard_path` (`dashboard_path`),
  ADD KEY `idx_access_level` (`access_level`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `staff_departments`
--
ALTER TABLE `staff_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `head_of_department_id` (`head_of_department_id`),
  ADD KEY `idx_department_name` (`department_name`),
  ADD KEY `idx_department_code` (`department_code`),
  ADD KEY `idx_parent` (`parent_department_id`),
  ADD KEY `idx_level` (`department_level`);

--
-- Indexes for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_upload_date` (`upload_date`);

--
-- Indexes for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_leave_type` (`leave_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Indexes for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_attempt_time` (`attempt_time`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `staff_password_resets`
--
ALTER TABLE `staff_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_used` (`is_used`);

--
-- Indexes for table `staff_performance`
--
ALTER TABLE `staff_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_evaluation_period` (`evaluation_period`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_permission_level` (`permission_level`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `staff_promotions`
--
ALTER TABLE `staff_promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_number` (`promotion_number`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_promotion_number` (`promotion_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_qualifications`
--
ALTER TABLE `staff_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `staff_records`
--
ALTER TABLE `staff_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_resignations`
--
ALTER TABLE `staff_resignations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resignation_number` (`resignation_number`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_resignation_number` (`resignation_number`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_role_name` (`role_name`),
  ADD KEY `idx_role_level` (`role_level`);

--
-- Indexes for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_effective_date` (`effective_date`);

--
-- Indexes for table `staff_training`
--
ALTER TABLE `staff_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_training_type` (`training_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `store_categories`
--
ALTER TABLE `store_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_category_name` (`category_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `store_inventory`
--
ALTER TABLE `store_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `store_inventory_transactions`
--
ALTER TABLE `store_inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `store_orders`
--
ALTER TABLE `store_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_by` (`requested_by`);

--
-- Indexes for table `store_order_items`
--
ALTER TABLE `store_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Indexes for table `store_requests`
--
ALTER TABLE `store_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD KEY `idx_request_number` (`request_number`),
  ADD KEY `idx_requested_by` (`requested_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_forwarded_to` (`forwarded_to`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `store_request_items`
--
ALTER TABLE `store_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_item_id` (`item_id`);

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
-- Indexes for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_activities`
--
ALTER TABLE `student_activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activity_number` (`activity_number`),
  ADD KEY `organizer_id` (`organizer_id`),
  ADD KEY `idx_activity_number` (`activity_number`),
  ADD KEY `idx_activity_date` (`activity_date`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_admissions`
--
ALTER TABLE `student_admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_admission_number` (`admission_number`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_date` (`date`);

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
-- Indexes for table `student_data_imports`
--
ALTER TABLE `student_data_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_source_file` (`source_file`),
  ADD KEY `idx_import_date` (`import_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_discipline`
--
ALTER TABLE `student_discipline`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_case_number` (`case_number`);

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
  ADD KEY `idx_index_number` (`student_index_number`),
  ADD KEY `idx_program` (`program_id`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `fee_structure_id` (`fee_structure_id`);

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
-- Indexes for table `student_health_records`
--
ALTER TABLE `student_health_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_number` (`record_number`),
  ADD KEY `idx_record_number` (`record_number`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_index_number` (`student_index_number`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `fee_structure_id` (`fee_structure_id`),
  ADD KEY `fee_assignment_id` (`fee_assignment_id`);

--
-- Indexes for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_photos`
--
ALTER TABLE `student_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_action_date` (`action_date`);

--
-- Indexes for table `student_profile_edits`
--
ALTER TABLE `student_profile_edits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_edit_date` (`edit_date`),
  ADD KEY `idx_field_changed` (`field_changed`);

--
-- Indexes for table `student_reports`
--
ALTER TABLE `student_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_number` (`report_number`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generation_date` (`generation_date`);

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
-- Indexes for table `student_search_index`
--
ALTER TABLE `student_search_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_search_field` (`search_field`),
  ADD KEY `idx_search_value` (`search_value`);
ALTER TABLE `student_search_index` ADD FULLTEXT KEY `idx_search_full` (`search_value`);

--
-- Indexes for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `idx_case_number` (`case_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_case_type` (`case_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_warden` (`assigned_warden`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_log_level` (`log_level`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`),
  ADD KEY `idx_setting_type` (`setting_type`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_public` (`is_public`);

--
-- Indexes for table `training_programs`
--
ALTER TABLE `training_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `transcript_generation_log`
--
ALTER TABLE `transcript_generation_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transcript_number` (`transcript_number`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_transcript_number` (`transcript_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trip_number` (`trip_number`),
  ADD KEY `idx_trip_number` (`trip_number`),
  ADD KEY `idx_vehicle_id` (`vehicle_id`),
  ADD KEY `idx_driver_id` (`driver_id`),
  ADD KEY `idx_trip_date` (`trip_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `universal_student_profiles`
--
ALTER TABLE `universal_student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD UNIQUE KEY `index_number` (`index_number`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_national_id` (`national_id`),
  ADD KEY `idx_index_number` (`index_number`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_program` (`program`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_intake_set` (`intake_set`),
  ADD KEY `idx_academic_year` (`academic_year`);

--
-- Indexes for table `ura_reporting`
--
ALTER TABLE `ura_reporting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `prepared_by` (`prepared_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_report_code` (`report_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `ura_reports`
--
ALTER TABLE `ura_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_preference` (`user_id`,`preference_key`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_preference_key` (`preference_key`),
  ADD KEY `idx_preference_type` (`preference_type`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_code` (`vehicle_code`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD KEY `idx_vehicle_code` (`vehicle_code`),
  ADD KEY `idx_license_plate` (`license_plate`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_driver` (`assigned_driver`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_number` (`log_number`),
  ADD KEY `authorized_by` (`authorized_by`),
  ADD KEY `idx_log_number` (`log_number`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_visitor_type` (`visitor_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `work_history`
--
ALTER TABLE `work_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `academic_records`
--
ALTER TABLE `academic_records`
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
-- AUTO_INCREMENT for table `access_control_logs`
--
ALTER TABLE `access_control_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accreditation_management`
--
ALTER TABLE `accreditation_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `advanced_reports`
--
ALTER TABLE `advanced_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisal_periods`
--
ALTER TABLE `appraisal_periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appraisal_ratings`
--
ALTER TABLE `appraisal_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup_management`
--
ALTER TABLE `backup_management`
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
-- AUTO_INCREMENT for table `budget_lines`
--
ALTER TABLE `budget_lines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_records`
--
ALTER TABLE `budget_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_settings`
--
ALTER TABLE `bursar_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `bursar_users`
--
ALTER TABLE `bursar_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cache_management`
--
ALTER TABLE `cache_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashbook`
--
ALTER TABLE `cashbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_log`
--
ALTER TABLE `communication_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_records`
--
ALTER TABLE `compliance_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_registrations`
--
ALTER TABLE `course_registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_updates`
--
ALTER TABLE `dashboard_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_sync_status`
--
ALTER TABLE `data_sync_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_news`
--
ALTER TABLE `director_news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_actions`
--
ALTER TABLE `disciplinary_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_generation_log`
--
ALTER TABLE `document_generation_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `duty_roster`
--
ALTER TABLE `duty_roster`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_notifications_queue`
--
ALTER TABLE `email_notifications_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_details`
--
ALTER TABLE `employment_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examination_records`
--
ALTER TABLE `examination_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
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
-- AUTO_INCREMENT for table `fee_accounts`
--
ALTER TABLE `fee_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_management`
--
ALTER TABLE `fuel_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_ledger`
--
ALTER TABLE `general_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_documents`
--
ALTER TABLE `generated_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grade_change_history`
--
ALTER TABLE `grade_change_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_scales`
--
ALTER TABLE `grade_scales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grading_approval_workflow`
--
ALTER TABLE `grading_approval_workflow`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_notifications`
--
ALTER TABLE `grading_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_incidents`
--
ALTER TABLE `health_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_management`
--
ALTER TABLE `hostel_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_activity_logs`
--
ALTER TABLE `hr_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_reports`
--
ALTER TABLE `hr_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_settings`
--
ALTER TABLE `hr_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hr_users`
--
ALTER TABLE `hr_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_scheduling`
--
ALTER TABLE `interview_scheduling`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_records`
--
ALTER TABLE `invoice_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_infrastructure`
--
ALTER TABLE `it_infrastructure`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_vacancies`
--
ALTER TABLE `job_vacancies`
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
-- AUTO_INCREMENT for table `lab_equipment_maintenance`
--
ALTER TABLE `lab_equipment_maintenance`
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
-- AUTO_INCREMENT for table `lab_safety_records`
--
ALTER TABLE `lab_safety_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balance`
--
ALTER TABLE `leave_balance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `library_management`
--
ALTER TABLE `library_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_members`
--
ALTER TABLE `library_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_transactions`
--
ALTER TABLE `library_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_tracking`
--
ALTER TABLE `meal_tracking`
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
-- AUTO_INCREMENT for table `news_images`
--
ALTER TABLE `news_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
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
-- AUTO_INCREMENT for table `onboarding_checklist`
--
ALTER TABLE `onboarding_checklist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partnerships`
--
ALTER TABLE `partnerships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
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
-- AUTO_INCREMENT for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `performance_indicators`
--
ALTER TABLE `performance_indicators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professional_licenses`
--
ALTER TABLE `professional_licenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `real_time_updates`
--
ALTER TABLE `real_time_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipt_templates`
--
ALTER TABLE `receipt_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recruitment_applications`
--
ALTER TABLE `recruitment_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment_jobs`
--
ALTER TABLE `recruitment_jobs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `requirement_clearances`
--
ALTER TABLE `requirement_clearances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_items`
--
ALTER TABLE `requirement_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `requirement_messages`
--
ALTER TABLE `requirement_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_projects`
--
ALTER TABLE `research_projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_publication`
--
ALTER TABLE `result_publication`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_inspections`
--
ALTER TABLE `room_inspections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `route_schedules`
--
ALTER TABLE `route_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_index`
--
ALTER TABLE `search_index`
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
-- AUTO_INCREMENT for table `security_equipment`
--
ALTER TABLE `security_equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incidents`
--
ALTER TABLE `security_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_patrols`
--
ALTER TABLE `security_patrols`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_visitors`
--
ALTER TABLE `security_visitors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills_laboratory`
--
ALTER TABLE `skills_laboratory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills_lab_sessions`
--
ALTER TABLE `skills_lab_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smart_suggestions`
--
ALTER TABLE `smart_suggestions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsorships`
--
ALTER TABLE `sponsorships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `staff_access_control`
--
ALTER TABLE `staff_access_control`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff_announcements`
--
ALTER TABLE `staff_announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_appraisals`
--
ALTER TABLE `staff_appraisals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_audit_logs`
--
ALTER TABLE `staff_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_categories`
--
ALTER TABLE `staff_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_dashboard_access`
--
ALTER TABLE `staff_dashboard_access`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `staff_departments`
--
ALTER TABLE `staff_departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `staff_documents`
--
ALTER TABLE `staff_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_password_resets`
--
ALTER TABLE `staff_password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_performance`
--
ALTER TABLE `staff_performance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_promotions`
--
ALTER TABLE `staff_promotions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_qualifications`
--
ALTER TABLE `staff_qualifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_records`
--
ALTER TABLE `staff_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_resignations`
--
ALTER TABLE `staff_resignations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_roles`
--
ALTER TABLE `staff_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_training`
--
ALTER TABLE `staff_training`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_categories`
--
ALTER TABLE `store_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `store_inventory`
--
ALTER TABLE `store_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT for table `store_inventory_transactions`
--
ALTER TABLE `store_inventory_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_orders`
--
ALTER TABLE `store_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_order_items`
--
ALTER TABLE `store_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_requests`
--
ALTER TABLE `store_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_request_items`
--
ALTER TABLE `store_request_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_activities`
--
ALTER TABLE `student_activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_admissions`
--
ALTER TABLE `student_admissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_data_imports`
--
ALTER TABLE `student_data_imports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_discipline`
--
ALTER TABLE `student_discipline`
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
-- AUTO_INCREMENT for table `student_health_records`
--
ALTER TABLE `student_health_records`
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
-- AUTO_INCREMENT for table `student_photos`
--
ALTER TABLE `student_photos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profile_edits`
--
ALTER TABLE `student_profile_edits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_reports`
--
ALTER TABLE `student_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_room_inspections`
--
ALTER TABLE `student_room_inspections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_search_index`
--
ALTER TABLE `student_search_index`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `training_programs`
--
ALTER TABLE `training_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcript_generation_log`
--
ALTER TABLE `transcript_generation_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_logs`
--
ALTER TABLE `trip_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `universal_student_profiles`
--
ALTER TABLE `universal_student_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ura_reporting`
--
ALTER TABLE `ura_reporting`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ura_reports`
--
ALTER TABLE `ura_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_history`
--
ALTER TABLE `work_history`
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
-- Constraints for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD CONSTRAINT `academic_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD CONSTRAINT `academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `academic_records_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `academic_records_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Constraints for table `access_control_logs`
--
ALTER TABLE `access_control_logs`
  ADD CONSTRAINT `access_control_logs_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `accreditation_management`
--
ALTER TABLE `accreditation_management`
  ADD CONSTRAINT `accreditation_management_ibfk_1` FOREIGN KEY (`responsible_person`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `advanced_reports`
--
ALTER TABLE `advanced_reports`
  ADD CONSTRAINT `advanced_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD CONSTRAINT `analytics_cache_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `appraisal_ratings`
--
ALTER TABLE `appraisal_ratings`
  ADD CONSTRAINT `appraisal_ratings_ibfk_1` FOREIGN KEY (`appraisal_id`) REFERENCES `staff_appraisals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appraisal_ratings_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `performance_indicators` (`id`);

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`asset_category_id`) REFERENCES `asset_categories` (`id`);

--
-- Constraints for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  ADD CONSTRAINT `asset_depreciation_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `backup_management`
--
ALTER TABLE `backup_management`
  ADD CONSTRAINT `backup_management_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD CONSTRAINT `bank_reconciliations_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`);

--
-- Constraints for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD CONSTRAINT `budget_lines_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD CONSTRAINT `budget_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cache_management`
--
ALTER TABLE `cache_management`
  ADD CONSTRAINT `cache_management_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  ADD CONSTRAINT `chemical_inventory_ibfk_1` FOREIGN KEY (`received_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  ADD CONSTRAINT `clinical_placements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `compliance_records`
--
ALTER TABLE `compliance_records`
  ADD CONSTRAINT `compliance_records_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `compliance_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  ADD CONSTRAINT `compliance_tracking_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `counseling_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `counseling_sessions_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD CONSTRAINT `course_assignments_ibfk_1` FOREIGN KEY (`lecturer_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `course_assignments_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `course_registrations`
--
ALTER TABLE `course_registrations`
  ADD CONSTRAINT `course_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  ADD CONSTRAINT `departmental_budgets_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disciplinary_actions`
--
ALTER TABLE `disciplinary_actions`
  ADD CONSTRAINT `disciplinary_actions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplinary_actions_ibfk_2` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `disciplinary_actions_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `staff_records` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  ADD CONSTRAINT `disciplinary_records_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `disciplinary_records_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `document_generation_log`
--
ALTER TABLE `document_generation_log`
  ADD CONSTRAINT `document_generation_log_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD CONSTRAINT `document_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `duty_roster`
--
ALTER TABLE `duty_roster`
  ADD CONSTRAINT `duty_roster_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  ADD CONSTRAINT `duty_rosters_ibfk_1` FOREIGN KEY (`warden_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `duty_rosters_ibfk_2` FOREIGN KEY (`replacement_warden`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `email_notifications_queue`
--
ALTER TABLE `email_notifications_queue`
  ADD CONSTRAINT `email_notifications_queue_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  ADD CONSTRAINT `employment_contracts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD CONSTRAINT `employment_details_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employment_details_ibfk_2` FOREIGN KEY (`staff_category_id`) REFERENCES `staff_categories` (`id`);

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `examination_records`
--
ALTER TABLE `examination_records`
  ADD CONSTRAINT `examination_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `examination_records_ibfk_2` FOREIGN KEY (`workflow_id`) REFERENCES `grading_approval_workflow` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `examination_records_ibfk_3` FOREIGN KEY (`lecturer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `examination_records_ibfk_4` FOREIGN KEY (`hod_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD CONSTRAINT `expenditures_ibfk_1` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`),
  ADD CONSTRAINT `expenditures_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`);

--
-- Constraints for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  ADD CONSTRAINT `expenditure_records_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budget_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `expenditure_records_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD CONSTRAINT `expense_approvals_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_accounts`
--
ALTER TABLE `fee_accounts`
  ADD CONSTRAINT `fee_accounts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fee_accounts_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD CONSTRAINT `fee_adjustments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fee_adjustments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoice_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fee_adjustments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `financial_records_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fuel_management`
--
ALTER TABLE `fuel_management`
  ADD CONSTRAINT `fuel_management_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fuel_management_ibfk_2` FOREIGN KEY (`filled_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD CONSTRAINT `general_ledger_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `generated_documents`
--
ALTER TABLE `generated_documents`
  ADD CONSTRAINT `generated_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `generated_documents_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `generated_documents_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `grade_change_history`
--
ALTER TABLE `grade_change_history`
  ADD CONSTRAINT `grade_change_history_ibfk_1` FOREIGN KEY (`examination_record_id`) REFERENCES `examination_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grade_change_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `grading_approval_workflow`
--
ALTER TABLE `grading_approval_workflow`
  ADD CONSTRAINT `grading_approval_workflow_ibfk_1` FOREIGN KEY (`examination_record_id`) REFERENCES `examination_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_approval_workflow_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_approval_workflow_ibfk_3` FOREIGN KEY (`hod_reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_approval_workflow_ibfk_4` FOREIGN KEY (`registrar_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_approval_workflow_ibfk_5` FOREIGN KEY (`principal_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `grading_notifications`
--
ALTER TABLE `grading_notifications`
  ADD CONSTRAINT `grading_notifications_ibfk_1` FOREIGN KEY (`workflow_number`) REFERENCES `grading_approval_workflow` (`workflow_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_notifications_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grading_notifications_ibfk_3` FOREIGN KEY (`sender_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `health_incidents`
--
ALTER TABLE `health_incidents`
  ADD CONSTRAINT `health_incidents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `health_incidents_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  ADD CONSTRAINT `hostel_allocations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hostel_allocations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `hostel_management` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `incident_reports_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incident_reports_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `staff_records` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interview_scheduling`
--
ALTER TABLE `interview_scheduling`
  ADD CONSTRAINT `interview_scheduling_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  ADD CONSTRAINT `inventory_reports_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_reports_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoice_records`
--
ALTER TABLE `invoice_records`
  ADD CONSTRAINT `invoice_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `invoice_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `it_infrastructure`
--
ALTER TABLE `it_infrastructure`
  ADD CONSTRAINT `it_infrastructure_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `it_infrastructure_ibfk_2` FOREIGN KEY (`maintained_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`vacancy_id`) REFERENCES `job_vacancies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_offers_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_equipment_maintenance`
--
ALTER TABLE `lab_equipment_maintenance`
  ADD CONSTRAINT `lab_equipment_maintenance_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  ADD CONSTRAINT `lab_experiments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `lab_skills_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_experiments_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_experiments_ibfk_3` FOREIGN KEY (`lab_technician_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  ADD CONSTRAINT `lab_inventory_ibfk_1` FOREIGN KEY (`received_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_safety_records`
--
ALTER TABLE `lab_safety_records`
  ADD CONSTRAINT `lab_safety_records_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `skills_laboratory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lab_safety_records_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  ADD CONSTRAINT `lab_skills_sessions_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_skills_sessions_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_balance`
--
ALTER TABLE `leave_balance`
  ADD CONSTRAINT `leave_balance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balance_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff_records` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `library_management`
--
ALTER TABLE `library_management`
  ADD CONSTRAINT `library_management_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `library_members`
--
ALTER TABLE `library_members`
  ADD CONSTRAINT `library_members_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `library_members_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_transactions`
--
ALTER TABLE `library_transactions`
  ADD CONSTRAINT `library_transactions_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_management` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `library_transactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `library_transactions_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `library_transactions_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `meal_tracking`
--
ALTER TABLE `meal_tracking`
  ADD CONSTRAINT `meal_tracking_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `meal_tracking_ibfk_2` FOREIGN KEY (`served_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
  ADD CONSTRAINT `midwifery_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `nursing_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `onboarding_checklist`
--
ALTER TABLE `onboarding_checklist`
  ADD CONSTRAINT `onboarding_checklist_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD CONSTRAINT `partnerships_ibfk_1` FOREIGN KEY (`responsible_person`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `payment_receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD CONSTRAINT `payment_records_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_records_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD CONSTRAINT `performance_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `professional_licenses`
--
ALTER TABLE `professional_licenses`
  ADD CONSTRAINT `professional_licenses_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD CONSTRAINT `proof_of_payments_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  ADD CONSTRAINT `quality_assurance_ibfk_1` FOREIGN KEY (`assessed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `real_time_updates`
--
ALTER TABLE `real_time_updates`
  ADD CONSTRAINT `real_time_updates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `receipt_templates`
--
ALTER TABLE `receipt_templates`
  ADD CONSTRAINT `receipt_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `recruitment_applications`
--
ALTER TABLE `recruitment_applications`
  ADD CONSTRAINT `recruitment_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `recruitment_jobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recruitment_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `recruitment_jobs`
--
ALTER TABLE `recruitment_jobs`
  ADD CONSTRAINT `recruitment_jobs_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  ADD CONSTRAINT `registrar_academic_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  ADD CONSTRAINT `registrar_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  ADD CONSTRAINT `registrar_graduation_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_graduation_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  ADD CONSTRAINT `registrar_student_registration_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_student_registration_ibfk_2` FOREIGN KEY (`registered_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_student_registration_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  ADD CONSTRAINT `registrar_transcripts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrar_transcripts_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_transcripts_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `registrar_transcripts_ibfk_4` FOREIGN KEY (`issued_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `requirement_clearances`
--
ALTER TABLE `requirement_clearances`
  ADD CONSTRAINT `requirement_clearances_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `requirement_clearances_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `requirement_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `research_projects`
--
ALTER TABLE `research_projects`
  ADD CONSTRAINT `research_projects_ibfk_1` FOREIGN KEY (`lead_researcher`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `research_projects_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `result_publication`
--
ALTER TABLE `result_publication`
  ADD CONSTRAINT `result_publication_ibfk_1` FOREIGN KEY (`published_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `room_inspections`
--
ALTER TABLE `room_inspections`
  ADD CONSTRAINT `room_inspections_ibfk_1` FOREIGN KEY (`inspector_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `route_schedules`
--
ALTER TABLE `route_schedules`
  ADD CONSTRAINT `route_schedules_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `route_schedules_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `salary_structures_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_equipment`
--
ALTER TABLE `security_equipment`
  ADD CONSTRAINT `security_equipment_ibfk_1` FOREIGN KEY (`maintained_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD CONSTRAINT `security_incidents_ibfk_1` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `security_incidents_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `security_patrols`
--
ALTER TABLE `security_patrols`
  ADD CONSTRAINT `security_patrols_ibfk_1` FOREIGN KEY (`guard_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `security_visitors`
--
ALTER TABLE `security_visitors`
  ADD CONSTRAINT `security_visitors_ibfk_1` FOREIGN KEY (`person_to_visit`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_visitors_ibfk_2` FOREIGN KEY (`check_in_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_visitors_ibfk_3` FOREIGN KEY (`check_out_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `skills_laboratory`
--
ALTER TABLE `skills_laboratory`
  ADD CONSTRAINT `skills_laboratory_ibfk_1` FOREIGN KEY (`in_charge`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `skills_lab_sessions`
--
ALTER TABLE `skills_lab_sessions`
  ADD CONSTRAINT `skills_lab_sessions_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `skills_laboratory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `skills_lab_sessions_ibfk_2` FOREIGN KEY (`lecturer_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `smart_suggestions`
--
ALTER TABLE `smart_suggestions`
  ADD CONSTRAINT `smart_suggestions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD CONSTRAINT `sponsorships_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sponsorships_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_access_control`
--
ALTER TABLE `staff_access_control`
  ADD CONSTRAINT `staff_access_control_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_access_control_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD CONSTRAINT `staff_activity_log_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_appraisals`
--
ALTER TABLE `staff_appraisals`
  ADD CONSTRAINT `staff_appraisals_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_appraisals_ibfk_2` FOREIGN KEY (`appraisal_period_id`) REFERENCES `appraisal_periods` (`id`),
  ADD CONSTRAINT `staff_appraisals_ibfk_3` FOREIGN KEY (`appraiser_id`) REFERENCES `staff_records` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_audit_logs`
--
ALTER TABLE `staff_audit_logs`
  ADD CONSTRAINT `staff_audit_logs_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  ADD CONSTRAINT `staff_contracts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_contracts_ibfk_2` FOREIGN KEY (`signed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_dashboard_access`
--
ALTER TABLE `staff_dashboard_access`
  ADD CONSTRAINT `staff_dashboard_access_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_dashboard_access_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_departments`
--
ALTER TABLE `staff_departments`
  ADD CONSTRAINT `staff_departments_ibfk_1` FOREIGN KEY (`head_of_department_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_departments_ibfk_2` FOREIGN KEY (`parent_department_id`) REFERENCES `staff_departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD CONSTRAINT `staff_documents_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  ADD CONSTRAINT `staff_leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  ADD CONSTRAINT `staff_login_attempts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  ADD CONSTRAINT `staff_login_sessions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_notifications`
--
ALTER TABLE `staff_notifications`
  ADD CONSTRAINT `staff_notifications_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_password_resets`
--
ALTER TABLE `staff_password_resets`
  ADD CONSTRAINT `staff_password_resets_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_performance`
--
ALTER TABLE `staff_performance`
  ADD CONSTRAINT `staff_performance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_performance_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD CONSTRAINT `staff_permissions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_promotions`
--
ALTER TABLE `staff_promotions`
  ADD CONSTRAINT `staff_promotions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_promotions_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_qualifications`
--
ALTER TABLE `staff_qualifications`
  ADD CONSTRAINT `staff_qualifications_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_resignations`
--
ALTER TABLE `staff_resignations`
  ADD CONSTRAINT `staff_resignations_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_resignations_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  ADD CONSTRAINT `staff_salaries_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_salaries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_training`
--
ALTER TABLE `staff_training`
  ADD CONSTRAINT `staff_training_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `store_inventory`
--
ALTER TABLE `store_inventory`
  ADD CONSTRAINT `store_inventory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_inventory_transactions`
--
ALTER TABLE `store_inventory_transactions`
  ADD CONSTRAINT `store_inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_order_items`
--
ALTER TABLE `store_order_items`
  ADD CONSTRAINT `store_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `store_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_request_items`
--
ALTER TABLE `store_request_items`
  ADD CONSTRAINT `store_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `store_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_request_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  ADD CONSTRAINT `student_academic_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_activities`
--
ALTER TABLE `student_activities`
  ADD CONSTRAINT `student_activities_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_admissions`
--
ALTER TABLE `student_admissions`
  ADD CONSTRAINT `student_admissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  ADD CONSTRAINT `student_counseling_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_counseling_sessions_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_discipline`
--
ALTER TABLE `student_discipline`
  ADD CONSTRAINT `student_discipline_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  ADD CONSTRAINT `student_emergency_contacts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD CONSTRAINT `student_fee_assignments_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `student_fee_assignments_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`);

--
-- Constraints for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  ADD CONSTRAINT `student_health_incidents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_health_incidents_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_health_records`
--
ALTER TABLE `student_health_records`
  ADD CONSTRAINT `student_health_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD CONSTRAINT `student_invoices_ibfk_1` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`),
  ADD CONSTRAINT `student_invoices_ibfk_2` FOREIGN KEY (`fee_assignment_id`) REFERENCES `student_fee_assignments` (`id`);

--
-- Constraints for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD CONSTRAINT `student_penalties_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`);

--
-- Constraints for table `student_photos`
--
ALTER TABLE `student_photos`
  ADD CONSTRAINT `student_photos_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profile_edits`
--
ALTER TABLE `student_profile_edits`
  ADD CONSTRAINT `student_profile_edits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_reports`
--
ALTER TABLE `student_reports`
  ADD CONSTRAINT `student_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_room_inspections`
--
ALTER TABLE `student_room_inspections`
  ADD CONSTRAINT `student_room_inspections_ibfk_1` FOREIGN KEY (`inspected_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_search_index`
--
ALTER TABLE `student_search_index`
  ADD CONSTRAINT `student_search_index_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `universal_student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  ADD CONSTRAINT `student_welfare_cases_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_welfare_cases_ibfk_2` FOREIGN KEY (`assigned_warden`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transcript_generation_log`
--
ALTER TABLE `transcript_generation_log`
  ADD CONSTRAINT `transcript_generation_log_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transcript_generation_log_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `transcript_generation_log_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD CONSTRAINT `trip_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `trip_logs_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ura_reporting`
--
ALTER TABLE `ura_reporting`
  ADD CONSTRAINT `ura_reporting_ibfk_1` FOREIGN KEY (`prepared_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ura_reporting_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`assigned_driver`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD CONSTRAINT `visitor_logs_ibfk_1` FOREIGN KEY (`authorized_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `work_history`
--
ALTER TABLE `work_history`
  ADD CONSTRAINT `work_history_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_records` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
