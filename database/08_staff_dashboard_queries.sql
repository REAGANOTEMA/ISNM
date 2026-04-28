-- ISNM School Management System - Staff Dashboard Queries
-- Comprehensive SQL queries for all staff dashboard operations

USE isnm_db;

-- ========================================
-- STAFF PROFILE AND USER MANAGEMENT
-- ========================================

-- Get staff complete profile information
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    u.role,
    u.type,
    u.status,
    u.created_at as employment_date,
    u.last_login,
    COUNT(DISTINCT c.id) as courses_taught,
    COUNT(DISTINCT sar.student_id) as students_supervised,
    COUNT(DISTINCT e.id) as exams_conducted
FROM users u
LEFT JOIN courses c ON u.id = c.created_by
LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
LEFT JOIN examinations e ON u.id = e.created_by
WHERE u.id = ? AND u.type = 'staff' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.email, u.phone, u.role, u.type, u.status, u.created_at, u.last_login;

-- Get all staff members by department/role
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    u.role,
    u.status,
    u.created_at,
    COUNT(DISTINCT c.id) as courses_assigned,
    CASE 
        WHEN LOWER(u.role) LIKE '%director%' THEN 'Management'
        WHEN LOWER(u.role) LIKE '%principal%' THEN 'Management'
        WHEN LOWER(u.role) LIKE '%registrar%' THEN 'Administration'
        WHEN LOWER(u.role) LIKE '%secretary%' THEN 'Administration'
        WHEN LOWER(u.role) LIKE '%bursar%' OR LOWER(u.role) LIKE '%accountant%' THEN 'Finance'
        WHEN LOWER(u.role) LIKE '%lecturer%' OR LOWER(u.role) LIKE '%senior%' THEN 'Academic'
        WHEN LOWER(u.role) LIKE '%head%' THEN 'Academic'
        WHEN LOWER(u.role) LIKE '%librarian%' THEN 'Support'
        WHEN LOWER(u.role) LIKE '%hr%' THEN 'Support'
        WHEN LOWER(u.role) LIKE '%matron%' OR LOWER(u.role) LIKE '%warden%' THEN 'Student Services'
        WHEN LOWER(u.role) LIKE '%lab%' THEN 'Support'
        WHEN LOWER(u.role) LIKE '%driver%' THEN 'Support'
        WHEN LOWER(u.role) LIKE '%security%' THEN 'Support'
        ELSE 'Other'
    END as department
FROM users u
WHERE u.type = 'staff' AND u.status = 'active'
ORDER BY department, u.full_name;

-- Update staff profile
UPDATE users 
SET full_name = ?, email = ?, phone = ?, updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- ========================================
-- ACADEMIC MANAGEMENT FOR STAFF
-- ========================================

-- Get courses assigned to staff member
SELECT 
    c.id,
    c.course_code,
    c.course_name,
    c.semester,
    c.credits,
    c.description,
    p.program_name,
    COUNT(sar.student_id) as enrolled_students,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_students,
    c.status
FROM courses c
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN student_academic_records sar ON c.id = sar.course_id
WHERE c.created_by = ? OR c.id IN (SELECT course_id FROM course_assignments WHERE staff_id = ?)
GROUP BY c.id, c.course_code, c.course_name, c.semester, c.credits, c.description, p.program_name, c.status
ORDER BY c.course_code;

-- Create new course
INSERT INTO courses (
    course_code, course_name, program_id, semester, credits, description, created_by, status
) VALUES (?, ?, ?, ?, ?, ?, ?, 'active');

-- Update course information
UPDATE courses 
SET course_name = ?, program_id = ?, semester = ?, credits = ?, description = ?, updated_at = NOW()
WHERE id = ? AND (created_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'course_edit'));

-- Delete course (with safety checks)
DELETE FROM courses 
WHERE id = ? AND (created_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'course_delete'));

-- Get student enrollment for staff courses
SELECT 
    u.full_name,
    u.index_number,
    u.phone,
    c.course_code,
    c.course_name,
    sar.semester,
    sar.academic_year,
    sar.status,
    sar.grade,
    sar.gpa_points,
    CASE 
        WHEN sar.status = 'completed' THEN 'Completed'
        WHEN sar.status = 'in_progress' THEN 'In Progress'
        ELSE 'Registered'
    END as enrollment_status
FROM student_academic_records sar
JOIN users u ON sar.student_id = u.id
JOIN courses c ON sar.course_id = c.id
WHERE c.created_by = ? OR c.id IN (SELECT course_id FROM course_assignments WHERE staff_id = ?)
ORDER BY sar.academic_year DESC, sar.semester DESC, u.full_name;

-- ========================================
-- EXAMINATION MANAGEMENT FOR STAFF
-- ========================================

-- Get examinations created by staff
SELECT 
    e.id,
    e.exam_name,
    e.exam_type,
    e.exam_date,
    e.exam_duration,
    e.total_marks,
    e.passing_marks,
    c.course_code,
    c.course_name,
    COUNT(er.id) as submitted_results,
    COUNT(CASE WHEN er.verified = TRUE THEN 1 END) as verified_results,
    e.status
FROM examinations e
JOIN courses c ON e.course_id = c.id
LEFT JOIN exam_results er ON e.id = er.exam_id
WHERE e.created_by = ?
GROUP BY e.id, e.exam_name, e.exam_type, e.exam_date, e.exam_duration, e.total_marks, e.passing_marks, c.course_code, c.course_name, e.status
ORDER BY e.exam_date DESC;

-- Create new examination
INSERT INTO examinations (
    course_id, exam_name, exam_type, total_marks, passing_marks, exam_date, exam_duration, created_by, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled');

-- Update examination
UPDATE examinations 
SET exam_name = ?, exam_type = ?, total_marks = ?, passing_marks = ?, exam_date = ?, exam_duration = ?, updated_at = NOW()
WHERE id = ? AND created_by = ?;

-- Get exam results for staff's examinations
SELECT 
    er.id,
    u.full_name,
    u.index_number,
    e.exam_name,
    e.exam_type,
    er.marks_obtained,
    er.grade,
    er.remarks,
    ROUND((er.marks_obtained / e.total_marks) * 100, 2) as percentage,
    CASE 
        WHEN er.marks_obtained >= e.passing_marks THEN 'PASS'
        ELSE 'FAIL'
    END as result_status,
    er.verified,
    er.submitted_at,
    er.verified_at
FROM exam_results er
JOIN examinations e ON er.exam_id = e.id
JOIN users u ON er.student_id = u.id
WHERE e.created_by = ?
ORDER BY e.exam_date DESC, u.full_name;

-- Submit exam results
INSERT INTO exam_results (
    exam_id, student_id, marks_obtained, grade, remarks, submitted_by
) VALUES (?, ?, ?, ?, ?, ?);

-- Verify exam results
UPDATE exam_results 
SET verified = TRUE, verified_by = ?, verified_at = NOW()
WHERE id = ? AND exam_id IN (SELECT id FROM examinations WHERE created_by = ?);

-- ========================================
-- ATTENDANCE MANAGEMENT FOR STAFF
-- ========================================

-- Get attendance records for staff's courses
SELECT 
    ar.id,
    ar.attendance_date,
    ar.attendance_status,
    u.full_name,
    u.index_number,
    c.course_code,
    c.course_name,
    ar.notes,
    ar.created_at
FROM attendance_records ar
JOIN users u ON ar.student_id = u.id
JOIN courses c ON ar.course_id = c.id
WHERE ar.marked_by = ?
ORDER BY ar.attendance_date DESC, c.course_code, u.full_name;

-- Mark attendance
INSERT INTO attendance_records (
    student_id, course_id, attendance_date, attendance_status, marked_by, notes
) VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
    attendance_status = VALUES(attendance_status),
    marked_by = VALUES(marked_by),
    notes = VALUES(notes),
    updated_at = NOW();

-- Get attendance summary by course
SELECT 
    c.course_code,
    c.course_name,
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_sessions,
    COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_sessions,
    COUNT(CASE WHEN ar.attendance_status = 'late' THEN 1 END) as late_sessions,
    ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_rate
FROM attendance_records ar
JOIN courses c ON ar.course_id = c.id
WHERE ar.marked_by = ?
GROUP BY c.id, c.course_code, c.course_name
ORDER BY attendance_rate DESC;

-- ========================================
-- GRADE MANAGEMENT FOR STAFF
-- ========================================

-- Get grading summary for staff courses
SELECT 
    c.course_code,
    c.course_name,
    COUNT(sar.id) as total_students,
    COUNT(CASE WHEN sar.grade IS NOT NULL THEN 1 END) as graded_students,
    COUNT(CASE WHEN sar.grade IS NULL THEN 1 END) as pending_grades,
    ROUND(AVG(sar.gpa_points), 2) as average_gpa,
    MIN(sar.grade) as lowest_grade,
    MAX(sar.grade) as highest_grade
FROM student_academic_records sar
JOIN courses c ON sar.course_id = c.id
WHERE c.created_by = ? AND sar.status = 'completed'
GROUP BY c.id, c.course_code, c.course_name
ORDER BY c.course_code;

-- Update student grades
UPDATE student_academic_records 
SET grade = ?, grade_letter = ?, gpa_points = ?, status = 'completed', updated_at = NOW()
WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE created_by = ?);

-- Get grade distribution
SELECT 
    CASE 
        WHEN grade >= 70 THEN 'A (70-100)'
        WHEN grade >= 60 THEN 'B (60-69)'
        WHEN grade >= 50 THEN 'C (50-59)'
        WHEN grade >= 40 THEN 'D (40-49)'
        ELSE 'F (0-39)'
    END as grade_range,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0) / (SELECT COUNT(*) FROM student_academic_records WHERE course_id = ? AND grade IS NOT NULL), 2) as percentage
FROM student_academic_records
WHERE course_id = ? AND grade IS NOT NULL
GROUP BY 
    CASE 
        WHEN grade >= 70 THEN 'A (70-100)'
        WHEN grade >= 60 THEN 'B (60-69)'
        WHEN grade >= 50 THEN 'C (50-59)'
        WHEN grade >= 40 THEN 'D (40-49)'
        ELSE 'F (0-39)'
    END
ORDER BY grade DESC;

-- ========================================
-- COMMUNICATION MANAGEMENT FOR STAFF
-- ========================================

-- Get messages sent/received by staff
SELECT 
    m.id,
    m.subject,
    m.message_text,
    m.message_type,
    m.priority,
    m.status,
    m.sent_at,
    m.read_at,
    CASE 
        WHEN m.sender_id = ? THEN 'SENT'
        ELSE 'RECEIVED'
    END as message_direction,
    CASE 
        WHEN m.recipient_id IS NULL THEN 'BROADCAST'
        ELSE 'INDIVIDUAL'
    END as recipient_type,
    recipient.full_name as recipient_name,
    sender.full_name as sender_name
FROM messages m
JOIN users sender ON m.sender_id = sender.id
LEFT JOIN users recipient ON m.recipient_id = recipient.id
WHERE (m.sender_id = ? OR m.recipient_id = ?)
  AND m.status != 'archived'
ORDER BY m.sent_at DESC;

-- Send message to individual
INSERT INTO messages (
    sender_id, recipient_id, subject, message_text, message_type, priority, status, sent_at
) VALUES (?, ?, ?, ?, 'individual', ?, 'sent', NOW());

-- Send broadcast message
INSERT INTO messages (
    sender_id, subject, message_text, message_type, priority, status, sent_at
) VALUES (?, ?, ?, 'broadcast', ?, 'sent', NOW());

-- Get message statistics
SELECT 
    COUNT(*) as total_messages,
    COUNT(CASE WHEN sender_id = ? THEN 1 END) as sent_messages,
    COUNT(CASE WHEN recipient_id = ? THEN 1 END) as received_messages,
    COUNT(CASE WHEN status = 'read' THEN 1 END) as read_messages,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_messages
FROM messages
WHERE (sender_id = ? OR recipient_id = ?) AND status != 'archived';

-- ========================================
-- FINANCIAL MANAGEMENT FOR STAFF
-- ========================================

-- Get payment collection statistics
SELECT 
    COUNT(*) as total_transactions,
    SUM(pt.amount) as total_collected,
    COUNT(CASE WHEN pt.payment_method = 'cash' THEN 1 END) as cash_payments,
    COUNT(CASE WHEN pt.payment_method = 'bank_transfer' THEN 1 END) as bank_transfers,
    COUNT(CASE WHEN pt.payment_method = 'mobile_money' THEN 1 END) as mobile_money_payments,
    COUNT(CASE WHEN pt.payment_method = 'cheque' THEN 1 END) as cheque_payments,
    DATE(pt.payment_date) as payment_date
FROM payment_transactions pt
WHERE pt.collected_by = ? AND pt.status = 'completed'
GROUP BY DATE(pt.payment_date)
ORDER BY payment_date DESC;

-- Record payment collection
INSERT INTO payment_transactions (
    student_id, fee_account_id, transaction_id, amount, payment_method, receipt_number, paid_by, collected_by, notes, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed');

-- Get fee collection summary by program
SELECT 
    p.program_name,
    COUNT(pt.id) as total_payments,
    SUM(pt.amount) as total_collected,
    AVG(pt.amount) as average_payment
FROM payment_transactions pt
JOIN student_fee_accounts sfa ON pt.fee_account_id = sfa.id
JOIN users u ON sfa.student_id = u.id
JOIN programs p ON (
    (u.index_number LIKE '%/CM/%' AND p.program_code = 'CM') OR
    (u.index_number LIKE '%/CN/%' AND p.program_code = 'CN') OR
    (u.index_number LIKE '%/DMORDN/%' AND p.program_code = 'DMORDN')
)
WHERE pt.collected_by = ? AND pt.status = 'completed'
GROUP BY p.program_name
ORDER BY total_collected DESC;

-- ========================================
-- LIBRARY MANAGEMENT FOR STAFF
-- ========================================

-- Get library books management
SELECT 
    COUNT(*) as total_books,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as available_books,
    COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed_books,
    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_books,
    COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost_books
FROM books
WHERE added_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'library_manage');

-- Add new book
INSERT INTO books (
    book_title, author, isbn, publisher, publication_year, category, total_copies, available_copies, location, description, added_by, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available');

-- Update book information
UPDATE books 
SET book_title = ?, author = ?, publisher = ?, publication_year = ?, category = ?, total_copies = ?, location = ?, description = ?, updated_at = NOW()
WHERE id = ? AND (added_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'library_edit'));

-- Get book loans management
SELECT 
    bl.id,
    b.book_title,
    b.author,
    u.full_name,
    u.index_number,
    bl.loan_date,
    bl.due_date,
    bl.return_date,
    bl.status,
    bl.fine_amount,
    DATEDIFF(CURDATE(), bl.due_date) as days_overdue
FROM book_loans bl
JOIN books b ON bl.book_id = b.id
JOIN users u ON bl.student_id = u.id
WHERE bl.issued_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'library_manage')
ORDER BY bl.loan_date DESC;

-- Issue book to student
INSERT INTO book_loans (
    book_id, student_id, loan_date, due_date, issued_by, status
) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), ?, 'borrowed');

-- Return book
UPDATE book_loans 
SET return_date = CURDATE(), status = 'returned', returned_by = ?, updated_at = NOW()
WHERE id = ? AND student_id = ?;

-- ========================================
-- HOSTEL MANAGEMENT FOR STAFF
-- ========================================

-- Get hostel occupancy statistics
SELECT 
    h.hostel_name,
    h.hostel_code,
    h.total_rooms,
    h.total_capacity,
    h.current_occupancy,
    ROUND((h.current_occupancy * 100.0) / h.total_capacity, 2) as occupancy_rate,
    COUNT(CASE WHEN r.status = 'available' THEN 1 END) as available_rooms,
    COUNT(CASE WHEN r.status = 'occupied' THEN 1 END) as occupied_rooms
FROM hostels h
LEFT JOIN rooms r ON h.id = r.hostel_id
WHERE ? IN (SELECT user_id FROM user_permissions WHERE permission = 'hostel_manage') OR 1 = 1
GROUP BY h.id, h.hostel_name, h.hostel_code, h.total_rooms, h.total_capacity, h.current_occupancy
ORDER BY h.hostel_name;

-- Get room allocations
SELECT 
    ra.id,
    u.full_name,
    u.index_number,
    u.phone,
    h.hostel_name,
    r.room_number,
    r.room_type,
    ra.allocation_date,
    ra.vacate_date,
    ra.status
FROM room_allocations ra
JOIN users u ON ra.student_id = u.id
JOIN rooms r ON ra.room_id = r.id
JOIN hostels h ON r.hostel_id = h.id
WHERE ra.allocated_by = ? OR ? IN (SELECT user_id FROM user_permissions WHERE permission = 'hostel_manage')
ORDER BY ra.allocation_date DESC;

-- Allocate room to student
INSERT INTO room_allocations (
    student_id, room_id, allocation_date, allocated_by, status
) VALUES (?, ?, CURDATE(), ?, 'active');

-- Update room allocation
UPDATE room_allocations 
SET vacate_date = ?, status = 'vacated', updated_at = NOW()
WHERE id = ? AND student_id = ?;

-- ========================================
-- REPORTING AND ANALYTICS FOR STAFF
-- ========================================

-- Get comprehensive dashboard statistics
SELECT 
    -- Academic Statistics
    (SELECT COUNT(*) FROM courses WHERE created_by = ?) as total_courses,
    (SELECT COUNT(*) FROM examinations WHERE created_by = ?) as total_examinations,
    (SELECT COUNT(*) FROM exam_results er JOIN examinations e ON er.exam_id = e.id WHERE e.created_by = ?) as total_results,
    
    -- Student Statistics
    (SELECT COUNT(DISTINCT sar.student_id) FROM student_academic_records sar JOIN courses c ON sar.course_id = c.id WHERE c.created_by = ?) as total_students,
    (SELECT AVG(sar.gpa_points) FROM student_academic_records sar JOIN courses c ON sar.course_id = c.id WHERE c.created_by = ? AND sar.status = 'completed') as average_gpa,
    
    -- Attendance Statistics
    (SELECT COUNT(*) FROM attendance_records WHERE marked_by = ?) as total_attendance_records,
    (SELECT ROUND((COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) FROM attendance_records WHERE marked_by = ?) as attendance_rate,
    
    -- Communication Statistics
    (SELECT COUNT(*) FROM messages WHERE sender_id = ?) as total_messages_sent,
    (SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND read_at IS NULL) as unread_messages,
    
    -- Financial Statistics
    (SELECT COUNT(*) FROM payment_transactions WHERE collected_by = ? AND status = 'completed') as total_payments_collected,
    (SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE collected_by = ? AND status = 'completed') as total_amount_collected;

-- Get recent activities
SELECT 
    al.action,
    al.description,
    al.table_name,
    al.created_at,
    u.full_name as user_name
FROM activity_logs al
JOIN users u ON al.user_id = u.id
WHERE al.user_id = ? OR al.table_name IN ('courses', 'examinations', 'attendance_records', 'messages', 'payment_transactions')
ORDER BY al.created_at DESC
LIMIT 10;

-- ========================================
-- STORED PROCEDURES FOR STAFF OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to get staff dashboard data
CREATE PROCEDURE IF NOT EXISTS get_staff_dashboard_data(
    IN p_staff_id INT
)
BEGIN
    -- Get staff basic info
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.last_login
    FROM users u
    WHERE u.id = p_staff_id AND u.type = 'staff';
    
    -- Get academic statistics
    SELECT 
        COUNT(DISTINCT c.id) as courses_taught,
        COUNT(DISTINCT sar.student_id) as students_supervised,
        COUNT(DISTINCT e.id) as exams_conducted
    FROM users u
    LEFT JOIN courses c ON u.id = c.created_by
    LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
    LEFT JOIN examinations e ON u.id = e.created_by
    WHERE u.id = p_staff_id;
    
    -- Get recent activities
    SELECT 
        action,
        description,
        created_at
    FROM activity_logs
    WHERE user_id = p_staff_id
    ORDER BY created_at DESC
    LIMIT 5;
END //

-- Procedure to create course with validation
CREATE PROCEDURE IF NOT EXISTS create_course(
    IN p_course_code VARCHAR(20),
    IN p_course_name VARCHAR(255),
    IN p_program_id INT,
    IN p_semester VARCHAR(20),
    IN p_credits DECIMAL(4,1),
    IN p_description TEXT,
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_course_id INT
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    -- Check if course code already exists
    SELECT COUNT(*) INTO v_count
    FROM courses 
    WHERE course_code = p_course_code;
    
    IF v_count > 0 THEN
        SET p_result = 'Course code already exists';
        SET p_success = FALSE;
        SET p_course_id = NULL;
    ELSE
        -- Insert new course
        INSERT INTO courses (
            course_code, course_name, program_id, semester, credits, description, created_by, status
        ) VALUES (
            p_course_code, p_course_name, p_program_id, p_semester, p_credits, p_description, p_created_by, 'active'
        );
        
        SET p_course_id = LAST_INSERT_ID();
        SET p_result = 'Course created successfully';
        SET p_success = TRUE;
        
        -- Log activity
        INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
        VALUES (p_created_by, 'COURSE_CREATE', CONCAT('Created course: ', p_course_name), 'courses', p_course_id);
    END IF;
END //

-- Procedure to submit exam results
CREATE PROCEDURE IF NOT EXISTS submit_exam_results(
    IN p_exam_id INT,
    IN p_student_id INT,
    IN p_marks_obtained DECIMAL(5,2),
    IN p_grade VARCHAR(2),
    IN p_remarks TEXT,
    IN p_submitted_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_exam_exists INT DEFAULT 0;
    DECLARE v_student_exists INT DEFAULT 0;
    DECLARE v_total_marks DECIMAL(5,2);
    DECLARE v_passing_marks DECIMAL(5,2);
    
    -- Check if exam exists and belongs to staff
    SELECT COUNT(*) INTO v_exam_exists
    FROM examinations 
    WHERE id = p_exam_id AND created_by = p_submitted_by;
    
    -- Check if student exists
    SELECT COUNT(*) INTO v_student_exists
    FROM users 
    WHERE id = p_student_id AND type = 'student';
    
    IF v_exam_exists = 0 THEN
        SET p_result = 'Exam not found or access denied';
        SET p_success = FALSE;
    ELSEIF v_student_exists = 0 THEN
        SET p_result = 'Student not found';
        SET p_success = FALSE;
    ELSE
        -- Get exam details for validation
        SELECT total_marks, passing_marks INTO v_total_marks, v_passing_marks
        FROM examinations WHERE id = p_exam_id;
        
        -- Validate marks
        IF p_marks_obtained < 0 OR p_marks_obtained > v_total_marks THEN
            SET p_result = 'Invalid marks obtained';
            SET p_success = FALSE;
        ELSE
            -- Insert or update exam result
            INSERT INTO exam_results (
                exam_id, student_id, marks_obtained, grade, remarks, submitted_by
            ) VALUES (
                p_exam_id, p_student_id, p_marks_obtained, p_grade, p_remarks, p_submitted_by
            )
            ON DUPLICATE KEY UPDATE 
                marks_obtained = VALUES(marks_obtained),
                grade = VALUES(grade),
                remarks = VALUES(remarks),
                submitted_by = VALUES(submitted_by),
                submitted_at = NOW(),
                verified = FALSE;
            
            SET p_result = 'Exam result submitted successfully';
            SET p_success = TRUE;
            
            -- Log activity
            INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
            VALUES (p_submitted_by, 'EXAM_RESULT_SUBMIT', CONCAT('Submitted result for exam ID: ', p_exam_id), 'exam_results', LAST_INSERT_ID());
        END IF;
    END IF;
END //

DELIMITER ;

-- Success message
SELECT 'Staff dashboard queries created successfully!' as message;
SELECT 'All staff dashboard operations, views, and stored procedures are ready for use' as note;
