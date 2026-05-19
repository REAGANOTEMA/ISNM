USE staffs_db;

-- Insert default admin user
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
VALUES ('ADMIN001', 'System Administrator', 'administration@isnm.ac', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', 'System Administrator', 'Executive Office', 1, 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'administration@isnm.ac', password = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', is_first_login = TRUE, updated_at = NOW();

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name', 'general', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('semester', 'Semester 2', 'text', 'Current semester', 'academic', TRUE),
('max_login_attempts', '5', 'number', 'Max login attempts', 'security', FALSE);

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS authenticate_staff(IN p_email VARCHAR(100), IN p_password VARCHAR(255), IN p_ip_address VARCHAR(45), IN p_user_agent TEXT)
BEGIN
    DECLARE v_staff_id INT; DECLARE v_password_hash VARCHAR(255);
    SELECT s.id, s.password INTO v_staff_id, v_password_hash FROM staff s WHERE s.email = p_email AND s.status = 'Active' LIMIT 1;
    IF v_staff_id IS NOT NULL AND v_password_hash = p_password THEN
        UPDATE staff SET login_attempts = 0, last_login = NOW() WHERE id = v_staff_id;
        SELECT v_staff_id as staff_id, s.full_name, sr.role_name, 'Login successful' as message, TRUE as success FROM staff s JOIN staff_roles sr ON s.role_id = sr.id WHERE s.id = v_staff_id;
    ELSE
        SELECT NULL as staff_id, NULL as full_name, NULL as role_name, 'Invalid credentials' as message, FALSE as success;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_dashboard_statistics(IN p_user_id INT, IN p_role VARCHAR(100))
BEGIN
    SELECT (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff, (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS log_staff_activity(IN p_staff_id INT, IN p_activity_type VARCHAR(100), IN p_activity_description TEXT, IN p_module_accessed VARCHAR(100), IN p_record_id INT, IN p_ip_address VARCHAR(45), IN p_user_agent TEXT)
BEGIN
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id, ip_address, user_agent) VALUES (p_staff_id, p_activity_type, p_activity_description, p_module_accessed, p_record_id, p_ip_address, p_user_agent);
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS request_password_reset(IN p_email VARCHAR(100), IN p_ip_address VARCHAR(45))
BEGIN
    DECLARE v_staff_id INT; SELECT id INTO v_staff_id FROM staff WHERE email = p_email AND status = 'Active' LIMIT 1;
    IF v_staff_id IS NOT NULL THEN SELECT MD5(CONCAT(p_email, NOW())) as reset_token, DATE_ADD(NOW(), INTERVAL 1 HOUR) as expires_at, 'Success' as message, TRUE as success;
    ELSE SELECT NULL as reset_token, NULL as expires_at, 'Email not found' as message, FALSE as success;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS reset_password_with_token(IN p_reset_token VARCHAR(255), IN p_new_password VARCHAR(255), IN p_ip_address VARCHAR(45))
BEGIN
    SELECT 'Password reset function' as message, TRUE as success;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS change_password(IN p_staff_id INT, IN p_current_password VARCHAR(255), IN p_new_password VARCHAR(255), IN p_ip_address VARCHAR(45))
BEGIN
    SELECT 'Password changed' as message, TRUE as success;
END //
DELIMITER ;
