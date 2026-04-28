-- ISNM School Management System - Student Dashboard Queries
-- Comprehensive SQL queries for student dashboard operations

USE isnm_school;

-- ========================================
-- STUDENT PROFILE AND ACADEMIC INFORMATION
-- ========================================

-- Get student complete profile information
SELECT 
    u.id,
    u.full_name,
    u.index_number,
    u.phone,
    u.email,
    u.created_at as admission_date,
    u.last_login,
    p.program_name,
    p.program_type,
    p.duration_years,
    CASE 
        WHEN u.index_number LIKE '%/CM/%' THEN 'Certificate in Midwifery'
        WHEN u.index_number LIKE '%/CN/%' THEN 'Certificate in Nursing'
        WHEN u.index_number LIKE '%/DMORDN/%' THEN 'Diploma in Midwifery'
        ELSE 'Unknown Program'
    END as program,
    CASE 
        WHEN u.index_number LIKE '%/CM/%' THEN 'CM'
        WHEN u.index_number LIKE '%/CN/%' THEN 'CN'
        WHEN u.index_number LIKE '%/DMORDN/%' THEN 'DMORDN'
        ELSE 'Unknown'
    END as program_code
FROM users u
LEFT JOIN programs p ON (
    (u.index_number LIKE '%/CM/%' AND p.program_code = 'CM') OR
    (u.index_number LIKE '%/CN/%' AND p.program_code = 'CN') OR
    (u.index_number LIKE '%/DMORDN/%' AND p.program_code = 'DMORDN')
)
WHERE u.id = ? AND u.type = 'student' AND u.status = 'active';

-- Get student academic records and GPA
SELECT 
    c.course_code,
    c.course_name,
    sar.semester,
    sar.academic_year,
    sar.grade,
    sar.grade_letter,
    sar.gpa_points,
    sar.status,
    c.credits,
    CASE 
        WHEN sar.grade >= 70 THEN 'A'
        WHEN sar.grade >= 60 THEN 'B'
        WHEN sar.grade >= 50 THEN 'C'
        WHEN sar.grade >= 40 THEN 'D'
        ELSE 'F'
    END as calculated_grade
FROM student_academic_records sar
JOIN courses c ON sar.course_id = c.id
WHERE sar.student_id = ?
ORDER BY sar.academic_year, sar.semester, c.course_code;

-- Calculate student GPA and academic statistics
SELECT 
    COUNT(*) as total_courses,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
    COUNT(CASE WHEN sar.status = 'in_progress' THEN 1 END) as in_progress_courses,
    AVG(sar.gpa_points) as current_gpa,
    SUM(c.credits) as total_credits,
    SUM(CASE WHEN sar.status = 'completed' THEN c.credits ELSE 0 END) as completed_credits,
    MAX(sar.academic_year) as current_academic_year,
    MAX(sar.semester) as current_semester
FROM student_academic_records sar
JOIN courses c ON sar.course_id = c.id
WHERE sar.student_id = ? AND sar.status IN ('completed', 'in_progress');

-- ========================================
-- STUDENT FEE INFORMATION
-- ========================================

-- Get student fee account details
SELECT 
    sfa.id,
    sfa.academic_year,
    sfa.semester,
    sfa.total_fee,
    sfa.amount_paid,
    sfa.balance,
    sfa.payment_status,
    sfa.due_date,
    sfa.last_payment_date,
    CASE 
        WHEN sfa.due_date < CURDATE() AND sfa.balance > 0 THEN 'OVERDUE'
        WHEN sfa.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND sfa.balance > 0 THEN 'DUE SOON'
        WHEN sfa.balance = 0 THEN 'PAID'
        ELSE 'PENDING'
    END as payment_urgency,
    fs.tuition_fee,
    fs.registration_fee,
    fs.library_fee,
    fs.lab_fee,
    fs.examination_fee,
    fs.other_fees
FROM student_fee_accounts sfa
JOIN fee_structure fs ON (
    sfa.academic_year = fs.academic_year AND 
    sfa.semester = fs.semester AND 
    fs.program_id = fs.program_id
)
WHERE sfa.student_id = ?
ORDER BY sfa.academic_year DESC, sfa.semester DESC;

-- Get student payment history
SELECT 
    pt.transaction_id,
    pt.amount,
    pt.payment_method,
    pt.payment_date,
    pt.receipt_number,
    pt.paid_by,
    pt.notes,
    u.full_name as collected_by_name,
    pt.status
FROM payment_transactions pt
JOIN users u ON pt.collected_by = u.id
WHERE pt.student_id = ?
ORDER BY pt.payment_date DESC;

-- Get total fee summary across all semesters
SELECT 
    SUM(sfa.total_fee) as total_all_semesters,
    SUM(sfa.amount_paid) as total_paid,
    SUM(sfa.balance) as total_balance,
    COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END) as paid_semesters,
    COUNT(CASE WHEN sfa.payment_status = 'partial' THEN 1 END) as partial_semesters,
    COUNT(CASE WHEN sfa.payment_status = 'unpaid' THEN 1 END) as unpaid_semesters
FROM student_fee_accounts sfa
WHERE sfa.student_id = ?;

-- ========================================
-- STUDENT ATTENDANCE RECORDS
-- ========================================

-- Get student attendance summary
SELECT 
    COUNT(*) as total_days,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_days,
    COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_days,
    COUNT(CASE WHEN ar.attendance_status = 'late' THEN 1 END) as late_days,
    COUNT(CASE WHEN ar.attendance_status = 'excused' THEN 1 END) as excused_days,
    ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_percentage,
    ar.semester,
    ar.academic_year
FROM attendance_records ar
WHERE ar.student_id = ?
GROUP BY ar.semester, ar.academic_year
ORDER BY ar.academic_year DESC, ar.semester DESC;

-- Get detailed attendance records
SELECT 
    ar.attendance_date,
    ar.attendance_status,
    c.course_code,
    c.course_name,
    u.full_name as marked_by_name,
    ar.notes,
    ar.created_at
FROM attendance_records ar
JOIN courses c ON ar.course_id = c.id
JOIN users u ON ar.marked_by = u.id
WHERE ar.student_id = ?
ORDER BY ar.attendance_date DESC;

-- Get attendance by course
SELECT 
    c.course_code,
    c.course_name,
    COUNT(*) as total_classes,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_classes,
    COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_classes,
    ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_percentage
FROM attendance_records ar
JOIN courses c ON ar.course_id = c.id
WHERE ar.student_id = ?
GROUP BY c.id, c.course_code, c.course_name
ORDER BY attendance_percentage DESC;

-- ========================================
-- STUDENT EXAMINATION RESULTS
-- ========================================

-- Get student exam results
SELECT 
    e.exam_name,
    e.exam_type,
    e.exam_date,
    e.total_marks,
    e.passing_marks,
    er.marks_obtained,
    er.grade,
    er.remarks,
    ROUND((er.marks_obtained / e.total_marks) * 100, 2) as percentage,
    CASE 
        WHEN er.marks_obtained >= e.passing_marks THEN 'PASS'
        ELSE 'FAIL'
    END as result_status,
    er.verified,
    er.submitted_at
FROM exam_results er
JOIN examinations e ON er.exam_id = e.id
WHERE er.student_id = ?
ORDER BY e.exam_date DESC;

-- Get student exam summary
SELECT 
    COUNT(*) as total_exams,
    COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) as passed_exams,
    COUNT(CASE WHEN er.marks_obtained < e.passing_marks THEN 1 END) as failed_exams,
    ROUND(AVG((er.marks_obtained / e.total_marks) * 100), 2) as average_percentage,
    ROUND(AVG(er.marks_obtained), 2) as average_marks,
    MAX(er.marks_obtained) as highest_marks,
    MIN(er.marks_obtained) as lowest_marks
FROM exam_results er
JOIN examinations e ON er.exam_id = e.id
WHERE er.student_id = ?;

-- ========================================
-- STUDENT MESSAGES AND COMMUNICATIONS
-- ========================================

-- Get student messages (sent and received)
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
    sender.full_name as sender_name,
    CASE 
        WHEN sender.type = 'staff' THEN sender.role
        ELSE 'Student'
    END as sender_role
FROM messages m
JOIN users sender ON m.sender_id = sender.id
WHERE (m.recipient_id = ? OR m.sender_id = ?)
  AND m.status != 'archived'
ORDER BY m.sent_at DESC;

-- Get unread messages count for student
SELECT 
    COUNT(*) as unread_count
FROM messages m
WHERE m.recipient_id = ? 
  AND m.status = 'delivered'
  AND m.read_at IS NULL;

-- ========================================
-- STUDENT LIBRARY RECORDS
-- ========================================

-- Get student library loans
SELECT 
    bl.id,
    b.book_title,
    b.author,
    b.isbn,
    bl.loan_date,
    bl.due_date,
    bl.return_date,
    bl.status,
    bl.fine_amount,
    bl.fine_paid,
    CASE 
        WHEN bl.due_date < CURDATE() AND bl.return_date IS NULL THEN 'OVERDUE'
        WHEN bl.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND bl.return_date IS NULL THEN 'DUE SOON'
        ELSE 'NORMAL'
    END as loan_status,
    DATEDIFF(CURDATE(), bl.due_date) as days_overdue
FROM book_loans bl
JOIN books b ON bl.book_id = b.id
WHERE bl.student_id = ?
ORDER BY bl.loan_date DESC;

-- Get student library summary
SELECT 
    COUNT(*) as total_books_loaned,
    COUNT(CASE WHEN bl.status = 'returned' THEN 1 END) as books_returned,
    COUNT(CASE WHEN bl.status = 'borrowed' THEN 1 END) as books_borrowed,
    COUNT(CASE WHEN bl.status = 'overdue' THEN 1 END) as books_overdue,
    SUM(bl.fine_amount) as total_fine,
    SUM(CASE WHEN bl.fine_paid = TRUE THEN bl.fine_amount ELSE 0 END) as fine_paid,
    SUM(CASE WHEN bl.fine_paid = FALSE THEN bl.fine_amount ELSE 0 END) as fine_unpaid
FROM book_loans bl
WHERE bl.student_id = ?;

-- ========================================
-- STUDENT HOSTEL INFORMATION
-- ========================================

-- Get student hostel allocation
SELECT 
    ra.id,
    h.hostel_name,
    h.hostel_code,
    r.room_number,
    r.room_type,
    r.capacity,
    r.current_occupancy,
    ra.allocation_date,
    ra.vacate_date,
    ra.status,
    h.warden_name,
    h.warden_contact,
    ra.notes
FROM room_allocations ra
JOIN rooms r ON ra.room_id = r.id
JOIN hostels h ON r.hostel_id = h.id
WHERE ra.student_id = ? AND ra.status = 'active';

-- Get roommates information
SELECT 
    u.full_name,
    u.phone,
    u.index_number,
    ra.allocation_date
FROM room_allocations ra
JOIN rooms r ON ra.room_id = r.id
JOIN users u ON ra.student_id = u.id
WHERE r.id = (SELECT room_id FROM room_allocations WHERE student_id = ? AND status = 'active')
  AND ra.status = 'active'
  AND u.id != ?;

-- ========================================
-- STUDENT SCHEDULE AND TIMETABLE
-- ========================================

-- Get student current semester courses
SELECT 
    c.course_code,
    c.course_name,
    c.credits,
    sar.semester,
    sar.academic_year,
    sar.status,
    CASE 
        WHEN sar.status = 'completed' THEN 'Completed'
        WHEN sar.status = 'in_progress' THEN 'In Progress'
        ELSE 'Registered'
    END as course_status
FROM student_academic_records sar
JOIN courses c ON sar.course_id = c.id
WHERE sar.student_id = ?
  AND sar.academic_year = (SELECT MAX(academic_year) FROM student_academic_records WHERE student_id = ?)
  AND sar.semester = (SELECT MAX(semester) FROM student_academic_records WHERE student_id = ? AND academic_year = (SELECT MAX(academic_year) FROM student_academic_records WHERE student_id = ?))
ORDER BY c.course_code;

-- Get upcoming examinations
SELECT 
    e.exam_name,
    e.exam_type,
    e.exam_date,
    e.exam_duration,
    c.course_code,
    c.course_name,
    e.total_marks,
    e.passing_marks,
    DATEDIFF(e.exam_date, CURDATE()) as days_until_exam
FROM examinations e
JOIN courses c ON e.course_id = c.id
JOIN student_academic_records sar ON c.id = sar.course_id
WHERE sar.student_id = ?
  AND e.exam_date >= CURDATE()
  AND e.status IN ('scheduled', 'in_progress')
ORDER BY e.exam_date ASC;

-- ========================================
-- STUDENT DASHBOARD STATISTICS
-- ========================================

-- Get comprehensive student dashboard statistics
SELECT 
    -- Academic Statistics
    (SELECT COUNT(*) FROM student_academic_records WHERE student_id = ? AND status = 'completed') as completed_courses,
    (SELECT AVG(gpa_points) FROM student_academic_records WHERE student_id = ? AND status = 'completed') as current_gpa,
    (SELECT COUNT(*) FROM courses c JOIN student_academic_records sar ON c.id = sar.course_id WHERE sar.student_id = ? AND sar.status = 'in_progress') as current_courses,
    
    -- Fee Statistics
    (SELECT SUM(balance) FROM student_fee_accounts WHERE student_id = ? AND academic_year = '2024-2025') as current_balance,
    (SELECT payment_status FROM student_fee_accounts WHERE student_id = ? AND academic_year = '2024-2025' ORDER BY due_date DESC LIMIT 1) as payment_status,
    
    -- Attendance Statistics
    (SELECT ROUND((COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) 
     FROM attendance_records WHERE student_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as attendance_rate,
    
    -- Library Statistics
    (SELECT COUNT(*) FROM book_loans WHERE student_id = ? AND status = 'borrowed') as books_borrowed,
    (SELECT SUM(fine_amount) FROM book_loans WHERE student_id = ? AND fine_paid = FALSE) as unpaid_fines,
    
    -- Messages Statistics
    (SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND status = 'delivered' AND read_at IS NULL) as unread_messages,
    
    -- Hostel Statistics
    (SELECT COUNT(*) FROM room_allocations WHERE student_id = ? AND status = 'active') as hostel_allocated,
    
    -- Recent Activity
    (SELECT last_login FROM users WHERE id = ?) as last_login;

-- ========================================
-- STORED PROCEDURES FOR STUDENT OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to get student dashboard data
CREATE PROCEDURE IF NOT EXISTS get_student_dashboard_data(
    IN p_student_id INT
)
BEGIN
    -- Get student basic info
    SELECT 
        u.id,
        u.full_name,
        u.index_number,
        u.phone,
        u.last_login,
        CASE 
            WHEN u.index_number LIKE '%/CM/%' THEN 'Certificate in Midwifery'
            WHEN u.index_number LIKE '%/CN/%' THEN 'Certificate in Nursing'
            WHEN u.index_number LIKE '%/DMORDN/%' THEN 'Diploma in Midwifery'
            ELSE 'Unknown Program'
        END as program
    FROM users u
    WHERE u.id = p_student_id AND u.type = 'student';
    
    -- Get academic summary
    SELECT 
        COUNT(*) as total_courses,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_courses,
        AVG(gpa_points) as current_gpa
    FROM student_academic_records
    WHERE student_id = p_student_id AND status IN ('completed', 'in_progress');
    
    -- Get fee summary
    SELECT 
        SUM(total_fee) as total_fees,
        SUM(amount_paid) as total_paid,
        SUM(balance) as total_balance,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_semesters
    FROM student_fee_accounts
    WHERE student_id = p_student_id;
    
    -- Get attendance summary
    SELECT 
        COUNT(*) as total_classes,
        COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) as present_classes,
        ROUND((COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_percentage
    FROM attendance_records
    WHERE student_id = p_student_id AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    
    -- Get unread messages count
    SELECT COUNT(*) as unread_count
    FROM messages
    WHERE recipient_id = p_student_id AND status = 'delivered' AND read_at IS NULL;
END //

-- Procedure to update student profile
CREATE PROCEDURE IF NOT EXISTS update_student_profile(
    IN p_student_id INT,
    IN p_phone VARCHAR(20),
    IN p_email VARCHAR(255)
)
BEGIN
    UPDATE users 
    SET phone = p_phone,
        email = p_email,
        updated_at = NOW()
    WHERE id = p_student_id AND type = 'student';
    
    -- Log activity
    INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
    VALUES (p_student_id, 'PROFILE_UPDATE', 'Student updated profile information', 'users', p_student_id);
    
    SELECT 'Profile updated successfully' as result;
END //

DELIMITER ;

-- Success message
SELECT 'Student dashboard queries created successfully!' as message;
SELECT 'All student dashboard operations, views, and stored procedures are ready for use' as note;
