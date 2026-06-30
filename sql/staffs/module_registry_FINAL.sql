-- ============================================================
-- ISNM MODULE REGISTRY — FINAL CLEAN SCHEMA
-- Run this ONCE on a fresh database, or to rebuild from scratch.
-- DO NOT run if tables already exist with data.
-- ============================================================

-- ─── DEPARTMENTS (9 groups) ───
CREATE TABLE IF NOT EXISTS module_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    icon VARCHAR(50) DEFAULT 'building',
    color VARCHAR(20) DEFAULT '#3b82f6',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── MODULES ───
CREATE TABLE IF NOT EXISTS system_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    icon VARCHAR(50) DEFAULT 'cube',
    route VARCHAR(200) NOT NULL,
    handler_url VARCHAR(200) DEFAULT NULL,
    tables_json JSON NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_student_module TINYINT(1) DEFAULT 0,
    is_document_module TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES module_departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── ROLE-MODULE PERMISSIONS ───
CREATE TABLE IF NOT EXISTS module_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    role_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_create TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    can_export TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_module_role (module_id, role_id),
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── MODULE AUDIT LOG ───
CREATE TABLE IF NOT EXISTS module_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    staff_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    record_id INT DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════
-- SEED: 9 DEPARTMENTS
-- ═══════════════════════════════════════════════════════════
INSERT IGNORE INTO module_departments (id, name, label, icon, color, sort_order) VALUES
(1, 'leadership',       'Leadership & Strategy',  'crown',         '#1e3a8a', 1),
(2, 'academic',         'Academic Affairs',        'book',          '#3b82f6', 2),
(3, 'finance',          'Finance & Accounts',      'money-bill',    '#10b981', 3),
(4, 'hr',               'HR & Administration',     'users',         '#8b5cf6', 4),
(5, 'student_services', 'Student Services',        'user-graduate', '#f59e0b', 5),
(6, 'operations',       'Operations & Logistics',  'cogs',          '#6366f1', 6),
(7, 'compliance',       'Compliance & Quality',    'shield-alt',    '#ef4444', 7),
(8, 'clinical',         'Clinical & Health',       'heartbeat',     '#ef4444', 8),
(9, 'system',           'System & Settings',       'database',      '#475569', 9);

-- ═══════════════════════════════════════════════════════════
-- SEED: 95 MODULES
-- ═══════════════════════════════════════════════════════════

-- Leadership & Strategy (dept 1)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('academic_approvals',  'Academic Approvals',     1, 'check-double', '/academic/approvals',  '["academic_approvals","approval_stages"]', 1, 'Academic workflow approvals'),
('approval_workflows',  'Approval Workflows',     1, 'tasks',        '/workflow/approvals',  '["approval_requests","approval_actions","approval_workflows","approval_stages"]', 2, 'Multi-level approval workflows'),
('calendar_events',     'Calendar & Events',      1, 'calendar-alt', '/calendar',            '["calendar_events"]', 3, 'Event scheduling'),
('research_projects',   'Research Projects',      1, 'flask',        '/research/projects',   '["research_projects"]', 4, 'Research management'),
('partnerships',        'Partnerships',           1, 'handshake',    '/research/partners',   '["partnerships","partner_schools"]', 5, 'Institutional partnerships'),
('graduation_mgmt',     'Graduation Management',  1, 'graduation-cap','/graduation/manage',  '["graduation_candidates","graduation_approvals"]', 6, 'Graduation management'),
('transcript_requests', 'Transcript Requests',    1, 'file-alt',     '/graduation/transcripts','["registrar_transcript_requests"]', 7, 'Transcript requests'),
('procurement',         'Procurement',            1, 'shopping-cart','/procurement',         '["procurement_requests"]', 8, 'Procurement'),
('task_management',     'Task Management',        1, 'clipboard-list','/workflow/tasks',     '["task_assignments"]', 9, 'Task assignments'),
('scholarships_mgmt',   'Scholarships',           1, 'award',        '/scholarships',        '["scholarships","student_scholarships"]', 10, 'Scholarships');

-- Academic Affairs (dept 2)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('academic_records',    'Academic Records',       2, 'file-alt',      '/academic/records',    '["academic_records","academic_programs"]', 1, 'Student academic records'),
('exams_results',       'Exams & Results',        2, 'clipboard-check','/academic/exams',     '["exam_results","examination_records"]', 2, 'Examination records'),
('course_management',   'Course Management',      2, 'layer-group',   '/academic/courses',    '["course_assignments","course_registrations"]', 3, 'Course management'),
('timetable',           'Timetable',              2, 'calendar',      '/academic/timetable',  '["academic_timetable"]', 4, 'Class timetables'),
('grading_system',      'Grading & GPA',          2, 'star',          '/academic/grading',    '["grade_scale","grades","gpa_settings"]', 5, 'Grading and GPA'),
('assessment_scores',   'Assessment Scores',      2, 'poll',          '/academic/assessments','["assessment_scores","assessments"]', 6, 'Assessment tracking'),
('academic_calendar',   'Academic Calendar',      2, 'calendar-alt',  '/academic/calendar',   '["academic_calendar","semesters"]', 7, 'Academic calendar'),
('academic_reports',    'Academic Reports',       2, 'chart-bar',     '/academic/reports',    '["academic_reports"]', 8, 'Academic reports');

-- Finance & Accounts (dept 3)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('fee_management',      'Fees Management',        3, 'money-bill-wave','/finance/fees',       '["bursar_fee_items","student_invoices"]', 1, 'Fee management'),
('payments',            'Payments & Receipts',    3, 'receipt',       '/finance/payments',    '["bursar_payments","bursar_receipts"]', 2, 'Payment processing'),
('budget_management',   'Budget & Expenses',      3, 'chart-pie',     '/finance/budget',      '["budget_lines","expenses"]', 3, 'Budget management'),
('payroll',             'Payroll',                3, 'money-check',   '/finance/payroll',     '["bursar_payroll","payroll_records"]', 4, 'Salary processing'),
('general_ledger',      'General Ledger',         3, 'book',          '/finance/ledger',      '["bursar_general_ledger","journal_entries"]', 5, 'General ledger'),
('tax_management',      'Tax & VAT',              3, 'file-invoice',  '/finance/tax',         '["bursar_tax_filings"]', 6, 'Tax management'),
('bank_reconciliation', 'Bank Reconciliation',    3, 'university',    '/finance/bank',        '["bank_accounts","bank_reconciliation"]', 7, 'Bank reconciliation'),
('financial_reports',   'Financial Reports',      3, 'chart-line',    '/finance/reports',     '["financial_reports"]', 8, 'Financial reports'),
('bursar_allowances',   'Allowances & Bonuses',   3, 'hand-holding-usd','/finance/allowances','["bursar_allowances"]', 9, 'Allowances'),
('bursar_assets',       'Assets & Depreciation',  3, 'building',      '/finance/assets',      '["bursar_assets"]', 10, 'Asset tracking');

-- HR & Administration (dept 4)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('staff_management',    'Staff Records',          4, 'users',         '/hr/staff',            '["staff","staff_departments"]', 1, 'Staff records'),
('leave_management',    'Leave Management',       4, 'calendar-minus','/hr/leave',            '["leave_requests","leave_balances"]', 2, 'Leave management'),
('attendance',          'Attendance',             4, 'fingerprint',   '/hr/attendance',       '["attendance","staff_attendance"]', 3, 'Staff attendance'),
('recruitment',         'Recruitment',            4, 'user-plus',     '/hr/recruitment',      '["recruitment","job_applications"]', 4, 'Recruitment'),
('training_cpd',        'Training & CPD',         4, 'graduation-cap','/hr/training',         '["employee_training"]', 5, 'Training and CPD'),
('appraisals',          'Appraisals',             4, 'star',          '/hr/appraisals',       '["appraisals","staff_appraisals"]', 6, 'Performance appraisals'),
('disciplinary',        'Disciplinary',           4, 'gavel',         '/hr/disciplinary',     '["disciplinary_actions"]', 7, 'Disciplinary cases'),
('resignations',        'Resignations',           4, 'sign-out-alt',  '/hr/resignations',     '["staff_resignations"]', 8, 'Resignations'),
('hr_reports',          'HR Reports',             4, 'chart-bar',     '/hr/reports',          '["hr_reports"]', 9, 'HR reports'),
('hr_settings',         'HR Settings',            4, 'cogs',          '/hr/settings',         '["hr_settings"]', 10, 'HR settings'),
('professional_licenses','Professional Licenses', 4, 'id-badge',      '/hr/licenses',         '["professional_licenses"]', 11, 'Professional licenses');

-- Student Services (dept 5)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('applicant_management','Applicant Management',   5, 'user-plus',     '/admissions/applicants','["applicants"]', 1, 'Applicant management'),
('intake_planning',     'Intake Planning',        5, 'calendar-plus', '/admissions/intakes',  '["intakes","pending_students"]', 2, 'Intake planning'),
('admission_letters',   'Admission Letters',      5, 'mail-bulk',     '/admissions/letters',  '["admission_notifications"]', 3, 'Admission letters'),
('enrollment',          'Enrollment & Registration',5,'clipboard-list','/admissions/enrollment','["registrar_student_registration"]', 4, 'Enrollment'),
('library_catalog',     'Book Catalog',           5, 'book',          '/library/catalog',     '["library_books"]', 5, 'Book catalog'),
('library_borrowing',   'Borrowing',              5, 'hand-holding',  '/library/borrowing',   '["library_borrowing"]', 6, 'Book borrowing'),
('library_resources',   'Digital Resources',      5, 'ebook',         '/library/digital',     '["library_digital_resources"]', 7, 'Digital resources'),
('library_fines',       'Fines & Clearance',      5, 'exclamation-triangle','/library/fines',  '["library_fines"]', 8, 'Library fines'),
('library_management',  'Library Settings',       5, 'cogs',          '/library/settings',    '["library_management"]', 9, 'Library settings'),
('hostel_management',   'Hostel Management',      5, 'home',          '/hostel/manage',       '["hostel_management","hostel_rooms"]', 10, 'Hostel management'),
('meal_tracking',       'Meal Tracking',          5, 'utensils',      '/hostel/meals',        '["meal_tracking"]', 11, 'Meal tracking'),
('sickbay',             'Sickbay',                5, 'medkit',        '/clinical/sickbay',    '["sickbay_settings","daily_sick_records"]', 12, 'Student health'),
('counseling',          'Counseling',             5, 'hands-helping', '/activities/counseling','["counseling_sessions"]', 13, 'Student counseling'),
('guild_management',    'Guild & Student Union',  5, 'trophy',        '/activities/guild',    '["student_activities"]', 14, 'Student guild'),
('sports_events',       'Sports & Events',        5, 'futbol',        '/activities/sports',   '["sports_events"]', 15, 'Sports events'),
('volunteer_applications','Volunteer Applications',5,'hand-holding-heart','/activities/volunteer','["volunteer_applications"]', 16, 'Volunteer program');

-- Operations & Logistics (dept 6)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('vehicle_management',  'Vehicles',               6, 'bus',           '/transport/vehicles',  '["fuel_management","trip_logs"]', 1, 'Vehicle management'),
('access_control',      'Access Control',         6, 'key',           '/security/access',     '["access_control_logs"]', 2, 'Access control'),
('visitor_management',  'Visitors',               6, 'id-card',       '/security/visitors',   '["security_visitors"]', 3, 'Visitor management'),
('security_patrols',    'Patrols & Equipment',    6, 'shield-alt',    '/security/patrols',    '["security_patrols"]', 4, 'Security patrols'),
('emergency',           'Emergency',              6, 'exclamation-triangle','/security/emergency','["emergency_contacts"]', 5, 'Emergency'),
('notifications',       'Notifications',          6, 'bell',          '/comms/notifications', '["notifications","sms_logs","email_logs"]', 6, 'Notifications'),
('messaging',           'Messaging',              6, 'envelope',      '/comms/messaging',     '["communications","portal_messages"]', 7, 'Messaging'),
('announcements',       'Announcements',          6, 'bullhorn',      '/comms/announcements', '["announcements"]', 8, 'Announcements'),
('document_center',     'Document Center',        6, 'folder-open',   '/documents',           '["file_uploads","generated_documents"]', 9, 'Document center'),
('certificates',        'Certificates',           6, 'certificate',   '/documents/certificates','["certificate_templates","certificates"]', 10, 'Certificates'),
('transcripts',         'Transcripts',            6, 'file-alt',      '/documents/transcripts','["transcript_items","transcripts"]', 11, 'Transcripts');

-- Compliance & Quality (dept 7)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('quality_assurance',   'Quality Assurance',      7, 'check-circle',  '/quality/assurance',   '["quality_assurance","compliance_records"]', 1, 'Quality assurance'),
('penalty_config',      'Penalty Configuration',  7, 'exclamation',   '/quality/penalties',   '["penalty_config"]', 2, 'Penalty configuration'),
('digital_learning',    'Digital Learning',       7, 'laptop',        '/ict/e-learning',      '["digital_learning"]', 3, 'Digital learning');

-- Clinical & Health (dept 8)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('clinical_placements', 'Clinical Placements',    8, 'hospital',      '/clinical/placements', '["clinical_placements"]', 1, 'Clinical placements'),
('nursing_training',    'Nursing Training',       8, 'user-nurse',    '/clinical/nursing',    '["nursing_practical_assessment"]', 2, 'Nursing training'),
('midwifery',           'Midwifery',              8, 'baby',          '/clinical/midwifery',  '["midwifery_antenatal_care"]', 3, 'Midwifery'),
('clinical_assessments','Clinical Assessments',   8, 'clipboard-check','/clinical/assessments','["clinical_assessments"]', 4, 'Clinical assessments'),
('incidents',           'Incident Reports',       8, 'exclamation-circle','/clinical/incidents','["incident_reports"]', 5, 'Incident reports');

-- System & Settings (dept 9)
INSERT IGNORE INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('system_settings',     'System Settings',        9, 'cogs',          '/admin/settings',      '["system_settings"]', 1, 'System settings'),
('user_management',     'User Management',        9, 'users-cog',     '/admin/users',         '["users","staff_roles"]', 2, 'User management'),
('audit_trail',         'Audit Trail',            9, 'history',       '/admin/audit',         '["audit_trail","system_logs"]', 3, 'Audit trail'),
('backup_management',   'Backup & Recovery',      9, 'database',      '/admin/backup',        '["backup_management"]', 4, 'Backup management'),
('recycle_bin',         'Recycle Bin',            9, 'trash-alt',     '/admin/recycle',       '["recycle_bin"]', 5, 'Recycle bin'),
('website_content',     'Website Content',        9, 'globe',         '/website/content',     '["news_images"]', 6, 'Website content'),
('news_management',     'News & Announcements',   9, 'newspaper',     '/website/news',        '["director_news"]', 7, 'News management'),
('it_infrastructure',   'IT Infrastructure',      9, 'server',        '/ict/infrastructure',  '["it_infrastructure"]', 8, 'IT infrastructure'),
('cybersecurity',       'Cybersecurity',          9, 'shield-alt',    '/ict/security',        '["api_keys"]', 9, 'Cybersecurity'),
('ict_support',         'ICT Support',            9, 'headset',       '/ict/support',         '["it_support_tickets"]', 10, 'ICT support'),
('ict_policy',          'ICT Policy',             9, 'file-contract', '/ict/policy',          '["ict_policy"]', 11, 'ICT policy'),
('system_logs',         'System Logs',            9, 'list-alt',      '/ict/logs',            '["system_logs","error_logs"]', 12, 'System logs');
