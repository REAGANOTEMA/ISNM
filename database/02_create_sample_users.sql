-- ISNM School Management System Sample Users
-- Create sample students and staff for testing

USE isnm_school;

-- Insert sample students (3-field login: index_number, full_name, phone)
INSERT INTO users (index_number, full_name, phone, role, type, status) VALUES
-- Nursing Students
('U001/CM/001/24', 'Aisha Nakato', '0772123456', 'student', 'student', 'active'),
('U002/CM/002/24', 'Mariam Nalwoga', '0772123457', 'student', 'student', 'active'),
('U003/CM/003/24', 'Sarah Namulindwa', '0772123458', 'student', 'student', 'active'),
('U004/CM/004/24', 'Grace Babirye', '0772123459', 'student', 'student', 'active'),
('U005/CM/005/24', 'Joyce Nankya', '0772123460', 'student', 'student', 'active'),

-- Midwifery Students
('U001/CN/001/24', 'Fatuma Nakato', '0772123461', 'student', 'student', 'active'),
('U002/CN/002/24', 'Zaituni Nalwoga', '0772123462', 'student', 'student', 'active'),
('U003/CN/003/24', 'Aisha Namulindwa', '0772123463', 'student', 'student', 'active'),
('U004/CN/004/24', 'Mariam Babirye', '0772123464', 'student', 'student', 'active'),
('U005/CN/005/24', 'Sarah Nankya', '0772123465', 'student', 'student', 'active'),

-- Diploma Students
('U001/DMORDN/001/24', 'Grace Nakato', '0772123466', 'student', 'student', 'active'),
('U002/DMORDN/002/24', 'Joyce Nalwoga', '0772123467', 'student', 'student', 'active'),
('U003/DMORDN/003/24', 'Fatuma Namulindwa', '0772123468', 'student', 'student', 'active'),
('U004/DMORDN/004/24', 'Zaituni Babirye', '0772123469', 'student', 'student', 'active'),
('U005/DMORDN/005/24', 'Aisha Nankya', '0772123470', 'student', 'student', 'active');

-- Insert sample staff (email + password login)
-- Password for all staff accounts is: password123 (hashed below)
INSERT INTO users (full_name, email, phone, password, role, type, status) VALUES
-- Administration
('Dr. John Mugisha', 'john.mugisha@isnm.ac.ug', '0772123471', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director General', 'staff', 'active'),
('Dr. Peter Lutaaya', 'peter.lutaaya@isnm.ac.ug', '0772123472', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Principal', 'staff', 'active'),
('Mr. Henry Mugisha', 'henry.mugisha@isnm.ac.ug', '0772123473', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Academic Registrar', 'staff', 'active'),
('Mrs. Sarah Namulindwa', 'sarah.namulindwa@isnm.ac.ug', '0772123474', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Secretary', 'staff', 'active'),
('Mr. Joseph Nankya', 'joseph.nankya@isnm.ac.ug', '0772123475', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Bursar', 'staff', 'active'),

-- Directors
('Dr. Grace Nakato', 'grace.nakato@isnm.ac.ug', '0772123476', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director Academics', 'staff', 'active'),
('Dr. Joyce Nalwoga', 'joyce.nalwoga@isnm.ac.ug', '0772123477', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director Finance', 'staff', 'active'),
('Dr. Fatuma Namulindwa', 'fatuma.namulindwa@isnm.ac.ug', '0772123478', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT', 'staff', 'active'),
('Dr. Zaituni Babirye', 'zaituni.babirye@isnm.ac.ug', '0772123479', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Deputy Principal', 'staff', 'active'),
('Dr. Aisha Nankya', 'aisha.nankya@isnm.ac.ug', '0772123480', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Senior Lecturers', 'staff', 'active'),

-- Academic Staff
('Mr. Mariam Nakato', 'mariam.nakato@isnm.ac.ug', '0772123481', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
('Mrs. Sarah Nalwoga', 'sarah.nalwoga@isnm.ac.ug', '0772123482', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
('Dr. Grace Namulindwa', 'grace.namulindwa@isnm.ac.ug', '0772123483', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
('Mrs. Joyce Babirye', 'joyce.babirye@isnm.ac.ug', '0772123484', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
('Mr. Fatuma Nankya', 'fatuma.nankya@isnm.ac.ug', '0772123485', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),

-- Support Staff
('Mrs. Zaituni Nakato', 'zaituni.nakato@isnm.ac.ug', '0772123486', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Librarian', 'staff', 'active'),
('Mr. Aisha Nalwoga', 'aisha.nalwoga@isnm.ac.ug', '0772123487', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Manager', 'staff', 'active'),
('Mrs. Mariam Namulindwa', 'mariam.namulindwa@isnm.ac.ug', '0772123488', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Head Nursing', 'staff', 'active'),
('Mr. Sarah Babirye', 'sarah.babirye@isnm.ac.ug', '0772123489', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Head Midwifery', 'staff', 'active'),
('Mrs. Joyce Nankya', 'joyce.nankya@isnm.ac.ug', '0772123490', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Matrons', 'staff', 'active'),
('Mr. Grace Nakato', 'grace.nakato@isnm.ac.ug', '0772123491', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Wardens', 'staff', 'active'),
('Mrs. Fatuma Nalwoga', 'fatuma.nalwoga@isnm.ac.ug', '0772123492', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lab Technicians', 'staff', 'active'),
('Mr. Zaituni Namulindwa', 'zaituni.namulindwa@isnm.ac.ug', '0772123493', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Drivers', 'staff', 'active'),
('Mrs. Aisha Babirye', 'aisha.babirye@isnm.ac.ug', '0772123494', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Security', 'staff', 'active'),
('Mr. Mariam Nankya', 'mariam.nankya@isnm.ac.ug', '0772123495', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Non-Teaching Staff', 'staff', 'active');

-- Create views for easy access to user data
CREATE OR REPLACE VIEW active_students AS
SELECT 
    id,
    index_number,
    full_name,
    phone,
    role,
    type,
    status,
    created_at,
    last_login
FROM users 
WHERE type = 'student' AND status = 'active';

CREATE OR REPLACE VIEW active_staff AS
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    type,
    status,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' AND status = 'active';

CREATE OR REPLACE VIEW user_login_stats AS
SELECT 
    u.id,
    u.full_name,
    u.role,
    u.type,
    u.last_login,
    u.login_attempts,
    COUNT(la.id) as total_attempts,
    COUNT(CASE WHEN la.success = TRUE THEN 1 END) as successful_logins,
    COUNT(CASE WHEN la.success = FALSE THEN 1 END) as failed_logins
FROM users u
LEFT JOIN login_attempts la ON (u.email = la.user_identifier OR u.index_number = la.user_identifier)
WHERE u.status = 'active'
GROUP BY u.id, u.full_name, u.role, u.type, u.last_login, u.login_attempts;

-- Success message
SELECT 'Sample users created successfully!' as message;
SELECT 'Default password for all staff accounts: password123' as note;
