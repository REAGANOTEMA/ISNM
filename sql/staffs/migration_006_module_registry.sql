-- ============================================================
-- ISNM MODULE REGISTRY SYSTEM
-- Migration 006: Core module registry, departments, permissions
-- ============================================================

-- ─── DEPARTMENTS ───
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

-- ─── MODULES (THE HEART OF THE SYSTEM) ───
CREATE TABLE IF NOT EXISTS system_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    icon VARCHAR(50) DEFAULT 'cube',
    route VARCHAR(200) NOT NULL,
    handler_url VARCHAR(200) DEFAULT NULL,
    tables_json JSON NOT NULL COMMENT 'All tables this module touches',
    description TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_student_module TINYINT(1) DEFAULT 0 COMMENT '1 = student portal only',
    is_document_module TINYINT(1) DEFAULT 0 COMMENT '1 = document center',
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
    action VARCHAR(50) NOT NULL COMMENT 'view/create/edit/delete/approve',
    record_id INT DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES system_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════
-- SEED: DEPARTMENTS
-- ═══════════════════════════════════════════════════════════
INSERT INTO module_departments (name, label, icon, color, sort_order) VALUES
('academic',       'Academic Affairs',      'book',         '#3b82f6', 1),
('finance',        'Finance & Bursar',      'money-bill',   '#10b981', 2),
('hr',             'Human Resources',       'users',        '#8b5cf6', 3),
('admissions',     'Admissions',            'user-plus',    '#f59e0b', 4),
('ict',            'ICT & Systems',         'laptop',       '#6366f1', 5),
('library',        'Library Services',      'book-open',    '#ec4899', 6),
('accommodation',  'Hostel & Accommodation','home',         '#14b8a6', 7),
('clinical',       'Clinical & Health',     'heartbeat',    '#ef4444', 8),
('transport',      'Transport',             'bus',          '#f97316', 9),
('security',       'Security & Access',     'shield-alt',   '#64748b', 10),
('communication',  'Communications',        'envelope',     '#0ea5e9', 11),
('documents',      'Document Center',       'folder-open',  '#a855f7', 12),
('quality',        'Quality & Compliance',  'check-circle', '#22c55e', 13),
('research',       'Research & Partners',   'flask',        '#06b6d4', 14),
('graduation',     'Graduation & Awards',   'graduation-cap','#eab308', 15),
('scholarships',   'Scholarships',          'award',        '#f43f5e', 16),
('procurement',    'Procurement',           'shopping-cart', '#84cc16', 17),
('workflow',       'Tasks & Workflow',      'tasks',        '#a78bfa', 18),
('calendar',       'Calendar & Events',     'calendar-alt', '#fb923c', 19),
('system',         'System Administration', 'cogs',         '#475569', 20),
('website',        'Website & Content',     'globe',        '#2dd4bf', 21),
('student_activities','Student Activities', 'trophy',       '#e879f9', 22),
('student_portal', 'Student Portal',        'graduation-cap','#3b82f6', 23)
ON DUPLICATE KEY UPDATE label=VALUES(label);

-- ═══════════════════════════════════════════════════════════
-- SEED: MODULES (Functional units, NOT 1-per-table)
-- ═══════════════════════════════════════════════════════════

-- ── ACADEMIC AFFAIRS ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('academic_records',      'Academic Records',       1, 'file-alt',       '/academic/records',       '["academic_records","academic_programs","academic_course_catalog","academic_curriculum_development","registrar_academic_records"]', 1, 'Student academic records, transcripts, GPA'),
('exams_results',         'Exams & Results',        1, 'clipboard-check','/academic/exams',         '["exam_results","exam_schedules","examination_records","examination_results","national_exam_results","exams","result_approvals","result_publication","result_publications"]', 2, 'Examination records, grading, results publication'),
('course_management',     'Course Management',      1, 'layer-group',    '/academic/courses',       '["course_assignments","course_registrations","classes","subjects","academic_analytics"]', 3, 'Course assignments, registration, subjects'),
('timetable',             'Timetable',              1, 'calendar',       '/academic/timetable',     '["academic_timetable","timetables","student_timetables"]', 4, 'Class timetables and scheduling'),
('grading_system',        'Grading & GPA',          1, 'star',           '/academic/grading',       '["grade_scale","grade_scales","grades","grade_change_history","gpa_settings","grading_approval_workflow","grading_approval_workflow_log","grading_notifications"]', 5, 'Grading scales, GPA calculation, grade changes'),
('assessment_scores',     'Assessment Scores',      1, 'poll',           '/academic/assessments',   '["assessment_scores","assessments"]', 6, 'Continuous assessment tracking'),
('academic_calendar',     'Academic Calendar',      1, 'calendar-alt',   '/academic/calendar',      '["academic_calendar","registrar_academic_calendar","semesters"]', 7, 'Academic terms, semesters, calendar'),
('academic_reports',      'Academic Reports',       1, 'chart-bar',      '/academic/reports',       '["academic_reports","academic_summary","academic_audit_logs"]', 8, 'Academic analytics and reports'),
('academic_approvals',    'Academic Approvals',     1, 'check-double',   '/academic/approvals',     '["academic_approvals","approval_stages"]', 9, 'Academic workflow approvals');

-- ── FINANCE & BURSAR ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('fee_management',        'Fees Management',        2, 'money-bill-wave','/finance/fees',           '["bursar_fee_items","fee_accounts","fee_adjustments","student_fees","student_fee_accounts","student_fee_assignments","student_invoices","bursar_invoices","invoice_records","fee_structure","fee_structures","late_payment_settings"]', 1, 'Fee structure, accounts, invoicing'),
('payments',              'Payments & Receipts',    2, 'receipt',        '/finance/payments',       '["bursar_payments","bursar_receipts","bursar_payment_verification","fee_payments","payment_records","payment_methods","payment_routes","payment_approvals","payments","proof_of_payments","payment_receipts"]', 2, 'Payment processing, receipts, verification'),
('budget_management',     'Budget & Expenses',      2, 'chart-pie',      '/finance/budget',         '["budget_lines","bursar_budget_items","departmental_budgets","bursar_expenses","expenses","expenditures","expenditure_tracking","cost_centers","cost_center_management"]', 3, 'Budget planning, expense tracking'),
('payroll',               'Payroll',                2, 'money-check',    '/finance/payroll',        '["bursar_payroll","payroll_records","payroll_runs","payroll_payslips","payroll_payments","payroll_employees","payroll_items","payroll_periods","payroll_settings","payroll_allowances","payroll_allowance_types","payroll_deductions","payroll_deduction_types","payroll_employee_allowances","payroll_employee_deductions","payroll_bonuses","payroll_bonus","payroll_loans","payroll_overtime","payroll_approval_history","payroll_approvals","payroll_audit_logs","staff_salaries","salary_structures","payslips","subscription_deductions"]', 4, 'Salary processing, payslips, deductions'),
('general_ledger',        'General Ledger',         2, 'book',           '/finance/ledger',         '["bursar_general_ledger","bursar_chart_of_accounts","bursar_cashbook","cashbook","general_ledger","journal_entries","journal_entry_lines"]', 5, 'Chart of accounts, journal entries'),
('tax_management',        'Tax & VAT',              2, 'file-invoice',   '/finance/tax',            '["bursar_tax_filings","bursar_tax_periods","bursar_tax_records","bursar_vat_reports","bursar_withholding_tax","ura_reports"]', 6, 'Tax filings, VAT reports, URA'),
('bank_reconciliation',   'Bank Reconciliation',    2, 'university',     '/finance/bank',           '["bank_accounts","bank_reconciliation","bank_reconciliations"]', 7, 'Bank accounts, reconciliation'),
('financial_reports',     'Financial Reports',      2, 'chart-line',     '/finance/reports',        '["financial_records","financial_audit_log","financial_reports","advanced_reports","bursar_daily_collections"]', 8, 'Financial analytics and reports'),
('scholarships_mgmt',     'Scholarships & Sponsorships', 2, 'award',     '/finance/scholarships',   '["bursar_scholarships","bursar_sponsorships","scholarships","student_scholarships"]', 9, 'Scholarship and sponsorship management'),
('bursar_allowances',     'Allowances & Bonuses',   2, 'hand-holding-usd','/finance/allowances',    '["bursar_allowances","bursar_deductions","bursar_discounts","bursar_penalties","bursar_penalty_config"]', 10, 'Allowances, deductions, penalties'),
('bursar_assets',         'Assets & Depreciation',  2, 'building',      '/finance/assets',         '["bursar_assets","asset_depreciation","finance_assets"]', 11, 'Asset tracking and depreciation');

-- ── HUMAN RESOURCES ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('staff_management',      'Staff Records',          3, 'users',          '/hr/staff',               '["staff","hr_users","staff_profiles","staff_departments","departments","employment_contracts","employment_details","staff_contracts","staff_salaries","salary_structures"]', 1, 'Staff profiles, contracts, departments'),
('leave_management',      'Leave Management',       3, 'calendar-minus', '/hr/leave',               '["leave_requests","leave_balances","leave_balance","leave_types","leaves","staff_leave_requests"]', 2, 'Leave requests, balances, types'),
('attendance',            'Attendance',             3, 'fingerprint',    '/hr/attendance',          '["attendance","attendance_status","staff_attendance","staff_login_sessions"]', 3, 'Staff attendance tracking'),
('recruitment',           'Recruitment',            3, 'user-plus',      '/hr/recruitment',         '["recruitment","recruitment_applications","recruitment_jobs","job_applications","job_offers","job_vacancies","interview_scheduling"]', 4, 'Job postings, applications, interviews'),
('training_cpd',          'Training & CPD',         3, 'graduation-cap', '/hr/training',            '["employee_training","staff_training","trainings"]', 5, 'Staff training and continuing professional development'),
('appraisals',            'Appraisals',             3, 'star',           '/hr/appraisals',          '["appraisals","staff_appraisals","appraisal_periods","appraisal_ratings","performance_indicators","performance_metrics","performance_reviews"]', 6, 'Performance appraisals and reviews'),
('disciplinary',          'Disciplinary',           3, 'gavel',          '/hr/disciplinary',        '["disciplinary_actions","disciplinary_cases","disciplinary_records","staff_disciplinary"]', 7, 'Disciplinary cases and actions'),
('resignations',          'Resignations',           3, 'sign-out-alt',   '/hr/resignations',        '["staff_resignations"]', 8, 'Resignation processing'),
('hr_reports',            'HR Reports',             3, 'chart-bar',      '/hr/reports',             '["hr_reports","hr_activity_log","hr_activity_logs","staff_audit_logs","staff_activity_log"]', 9, 'HR analytics and activity logs'),
('hr_settings',           'HR Settings',            3, 'cogs',           '/hr/settings',            '["hr_settings"]', 10, 'HR system configuration'),
('professional_licenses', 'Professional Licenses', 3, 'id-badge',       '/hr/licenses',            '["professional_licenses","staff_licenses"]', 11, 'Staff professional license tracking');

-- ── ADMISSIONS ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('applicant_management',  'Applicant Management',   4, 'user-plus',      '/admissions/applicants',  '["applicants","applicant_messages","applicant_requirement_status","admission_requirements","admission_activity_logs"]', 1, 'Application processing, requirements'),
('intake_planning',       'Intake Planning',        4, 'calendar-plus',  '/admissions/intakes',     '["intakes","student_admissions","pending_students"]', 2, 'Intake planning, student admissions'),
('admission_letters',     'Admission Letters',      4, 'mail-bulk',      '/admissions/letters',     '["admission_notifications"]', 3, 'Admission letter generation'),
('enrollment',            'Enrollment & Registration',4, 'clipboard-list','/admissions/enrollment',  '["registrar_student_registration","student_course_registrations","course_catalog","course_prerequisites"]', 4, 'Student enrollment and course registration');

-- ── ICT & SYSTEMS ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('it_infrastructure',     'IT Infrastructure',      5, 'server',         '/ict/infrastructure',     '["it_infrastructure"]', 1, 'Servers, network, infrastructure'),
('cybersecurity',         'Cybersecurity',          5, 'shield-alt',     '/ict/security',           '["api_keys","backup_management"]', 2, 'Security policies, API keys, backups'),
('ict_support',           'ICT Support',            5, 'headset',        '/ict/support',            '["it_support_tickets"]', 3, 'IT helpdesk and support tickets'),
('ict_policy',            'ICT Policy',             5, 'file-contract',  '/ict/policy',             '["ict_policy"]', 4, 'ICT policies and procedures'),
('system_logs',           'System Logs',            5, 'list-alt',       '/ict/logs',               '["system_logs","error_logs","audit_trail","analytics_cache"]', 5, 'System and error logs'),
('digital_learning',      'Digital Learning',       5, 'laptop',         '/ict/e-learning',         '["digital_learning","teaching_resources"]', 6, 'E-learning platform management');

-- ── LIBRARY ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('library_catalog',       'Book Catalog',           6, 'book',           '/library/catalog',        '["library_books"]', 1, 'Book catalog management'),
('library_borrowing',     'Borrowing',              6, 'hand-holding',   '/library/borrowing',      '["library_borrowing","library_transactions","library_members"]', 2, 'Book borrowing and returns'),
('library_resources',     'Digital Resources',      6, 'ebook',          '/library/digital',        '["library_digital_resources"]', 3, 'Digital library resources'),
('library_fines',         'Fines & Clearance',      6, 'exclamation-triangle','/library/fines',      '["library_fines","library_clearance"]', 4, 'Library fines and clearance'),
('library_management',    'Library Settings',       6, 'cogs',           '/library/settings',       '["library_management"]', 5, 'Library system configuration');

-- ── ACCOMMODATION ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('hostel_management',     'Hostel Management',      7, 'home',           '/hostel/manage',          '["hostel_management","hostel_inspections","hostel_maintenance_requests","hostel_clearance","student_hostel_allocations","hostel_allocations","hostel_rooms"]', 1, 'Hostel allocation, inspections, maintenance'),
('meal_tracking',         'Meal Tracking',          7, 'utensils',       '/hostel/meals',           '["meal_tracking"]', 2, 'Meal plan tracking');

-- ── CLINICAL & HEALTH ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('clinical_placements',   'Clinical Placements',    8, 'hospital',       '/clinical/placements',    '["clinical_placements","nursing_clinical_placements","nursing_clinical_logbook","clinical_rotations","clinical_placement","clinical_placements_students"]', 1, 'Clinical placement management'),
('nursing_training',      'Nursing Training',       8, 'user-nurse',     '/clinical/nursing',       '["nursing_practical_assessment","nursing_skills_training","nursing_students","midwifery_students"]', 2, 'Nursing practical training'),
('midwifery',             'Midwifery',              8, 'baby',           '/clinical/midwifery',     '["midwifery_antenatal_care","midwifery_family_planning","midwifery_labor_delivery","midwifery_postnatal_care"]', 3, 'Midwifery clinical records'),
('sickbay',               'Sickbay',                8, 'medkit',         '/clinical/sickbay',       '["sickbay_settings","daily_sick_records","health_incidents","student_health_records","student_health_incidents","student_sick_leave","sickness_directory","medicine_stock","medicine_stock_transactions"]', 4, 'Student health and sickbay'),
('clinical_assessments',  'Clinical Assessments',   8, 'clipboard-check','/clinical/assessments',   '["clinical_assessments","clinical_training"]', 5, 'Clinical assessment tracking'),
('incidents',             'Incident Reports',       8, 'exclamation-circle','/clinical/incidents',  '["incident_reports"]', 6, 'Health incident reporting');

-- ── TRANSPORT ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('vehicle_management',    'Vehicles',               9, 'bus',            '/transport/vehicles',     '["fuel_management","trip_logs","route_schedules"]', 1, 'Vehicle fleet, fuel, trips');

-- ── SECURITY ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('access_control',        'Access Control',         10, 'key',           '/security/access',        '["access_control_logs","access_logs","security_access_logs"]', 1, 'Access logs and control'),
('visitor_management',    'Visitors',               10, 'id-card',       '/security/visitors',      '["security_visitors","visitor_logs"]', 2, 'Visitor registration and tracking'),
('security_patrols',      'Patrols & Equipment',    10, 'shield-alt',    '/security/patrols',       '["security_patrols","security_equipment"]', 3, 'Patrol schedules, equipment'),
('emergency',             'Emergency',              10, 'exclamation-triangle','/security/emergency', '["security_emergency_contacts","emergency_contacts"]', 4, 'Emergency contacts and procedures');

-- ── COMMUNICATIONS ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('notifications',         'Notifications',          11, 'bell',          '/comms/notifications',    '["notifications","notification_logs","notification_reads","email_notifications_queue","email_logs","sms_logs","dg_read_notifications","institutional_alerts","alerts"]', 1, 'System notifications, email, SMS'),
('messaging',             'Messaging',              11, 'envelope',      '/comms/messaging',        '["communications","staff_communications","staff_messages","portal_messages","student_communication_messages","student_messages","messages","secretary_messages","financial_messages","financial_notices","financial_notices"]', 2, 'Internal messaging system'),
('announcements',         'Announcements',          11, 'bullhorn',      '/comms/announcements',    '["announcements","hr_announcements","director_news","principal_notices","circulars"]', 3, 'System-wide announcements');

-- ── DOCUMENT CENTER ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, is_document_module, description) VALUES
('document_center',       'Document Center',        12, 'folder-open',    '/documents',              '["file_uploads","generated_documents","document_generation_log","document_templates","document_print_configs","document_settings","document_tracking"]', 1, 1, 'Central document storage'),
('certificates',          'Certificates',           12, 'certificate',    '/documents/certificates', '["certificate_templates","certificate_uploads","certificate_verification","certificates","registrar_certificates"]', 2, 1, 'Certificate templates and generation'),
('transcripts',           'Transcripts',            12, 'file-alt',       '/documents/transcripts',  '["transcript_items","transcript_templates","transcripts","registrar_transcript_requests"]', 3, 1, 'Transcript generation and management');

-- ── QUALITY & COMPLIANCE ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('quality_assurance',     'Quality Assurance',      13, 'check-circle',   '/quality/assurance',      '["quality_assurance","compliance_records","compliance_requirements","compliance_tracking","compliance_alerts","accreditation_management"]', 1, 'Quality and accreditation'),
('penalty_config',        'Penalty Configuration',  13, 'exclamation',    '/quality/penalties',      '["penalty_config","penalty_configurations"]', 2, 'Penalty rules and configuration');

-- ── RESEARCH ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('research_projects',     'Research Projects',      14, 'flask',          '/research/projects',      '["research_projects"]', 1, 'Research project management'),
('partnerships',          'Partnerships',           14, 'handshake',      '/research/partnerships',  '["partnerships","partner_schools"]', 2, 'Institutional partnerships');

-- ── GRADUATION ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('graduation_mgmt',       'Graduation Management',  15, 'graduation-cap', '/graduation/manage',      '["graduation_candidates","graduation_approvals","registrar_graduation"]', 1, 'Graduation candidates and approvals'),
('transcript_requests',   'Transcript Requests',    15, 'file-alt',       '/graduation/transcripts', '["registrar_transcript_requests"]', 2, 'Transcript request processing');

-- ── SCHOLARSHIPS ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('scholarships_mgmt',     'Scholarships',           16, 'award',          '/scholarships',           '["scholarships","student_scholarships"]', 1, 'Scholarship management');

-- ── PROCUREMENT ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('procurement',           'Procurement',            17, 'shopping-cart',  '/procurement',            '["procurement_requests"]', 1, 'Procurement requests and processing');

-- ── WORKFLOW ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('task_management',       'Task Management',        18, 'tasks',          '/workflow/tasks',         '["task_assignments"]', 1, 'Task assignments and tracking'),
('approval_workflows',    'Approval Workflows',     18, 'check-double',   '/workflow/approvals',     '["approval_requests","approval_actions","approval_workflows","approval_stages"]', 2, 'Multi-level approval workflows');

-- ── CALENDAR ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('calendar_events',       'Calendar & Events',      19, 'calendar-alt',   '/calendar',               '["calendar_events"]', 1, 'Event scheduling and management');

-- ── SYSTEM ADMIN ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('system_settings',       'System Settings',        20, 'cogs',           '/admin/settings',         '["system_settings"]', 1, 'System-wide configuration'),
('user_management',       'User Management',        20, 'users-cog',      '/admin/users',            '["users","roles","permissions","staff_roles","data_ownership_rules","data_sync_status"]', 2, 'User and role management'),
('audit_trail',           'Audit Trail',            20, 'history',        '/admin/audit',            '["audit_trail","system_logs","error_logs","financial_audit_log","hr_activity_log","staff_audit_logs"]', 3, 'System audit trail'),
('backup_management',     'Backup & Recovery',      20, 'database',       '/admin/backup',           '["backup_management"]', 4, 'Database backup management'),
('recycle_bin',           'Recycle Bin',            20, 'trash-alt',      '/admin/recycle',          '["recycle_bin","students_trash"]', 5, 'Deleted records recovery');

-- ── WEBSITE ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('website_content',       'Website Content',        21, 'globe',          '/website/content',        '["news_images","news_subscribers","news_views"]', 1, 'Website content management'),
('news_management',       'News & Announcements',   21, 'newspaper',      '/website/news',           '["director_news"]', 2, 'News articles and publishing');

-- ── STUDENT ACTIVITIES ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, description) VALUES
('guild_management',      'Guild & Student Union',  22, 'trophy',         '/activities/guild',       '["student_activities"]', 1, 'Student guild management'),
('sports_events',         'Sports & Events',        22, 'futbol',         '/activities/sports',      '["sports_events","sports_teams"]', 2, 'Sports events and teams'),
('counseling',            'Counseling',             22, 'hands-helping',  '/activities/counseling',  '["counseling_sessions","student_counseling_sessions"]', 3, 'Student counseling services'),
('volunteer_applications','Volunteer Applications', 22, 'hand-holding-heart','/activities/volunteer', '["volunteer_applications"]', 4, 'Volunteer program');

-- ── STUDENT PORTAL (ISOLATED) ──
INSERT INTO system_modules (name, label, department_id, icon, route, tables_json, sort_order, is_student_module, description) VALUES
('my_academic',           'My Academic Records',    23, 'book',           '/student/academic',       '["student_academic_records","student_academic_profiles","student_progression"]', 1, 1, 'Personal academic records'),
('my_exams',              'My Exams & Results',     23, 'clipboard-check','/student/exams',          '["exam_results","exam_schedules","national_exam_results"]', 2, 1, 'Personal exam results'),
('my_fees',               'My Fees & Payments',     23, 'money-bill',     '/student/fees',           '["student_fees","student_fee_accounts","student_fee_assignments","student_invoices","student_penalties"]', 3, 1, 'Personal fee records'),
('my_timetable',          'My Timetable',           23, 'calendar',       '/student/timetable',      '["student_timetables"]', 4, 1, 'Personal timetable'),
('my_profile',            'My Profile',             23, 'user',           '/student/profile',        '["student_profiles","student_emergency_contacts","student_guardian"]', 5, 1, 'Personal profile'),
('my_documents',          'My Documents',           23, 'folder',         '/student/documents',      '["student_documents","student_downloads"]', 6, 1, 'Personal documents'),
('my_requests',           'My Requests',            23, 'paper-plane',    '/student/requests',       '["student_requests","student_messages","portal_messages"]', 7, 1, 'Student requests and messages'),
('my_discipline',         'My Discipline',          23, 'exclamation',    '/student/discipline',     '["student_discipline","student_discipline_records"]', 8, 1, 'Disciplinary records'),
('my_welfare',            'My Welfare',             23, 'heart',          '/student/welfare',        '["student_welfare_cases","student_counseling_sessions"]', 9, 1, 'Welfare and counseling');

-- ═══════════════════════════════════════════════════════════
-- SEED: ROLE PERMISSIONS
-- Role IDs: 1=DG, 2=CEO, 3=DirAcad, 4=DirFin, 5=DirICT,
--   6=Principal, 7=DeputyPrincipal, 8=AcadRegistrar, 9=HRManager,
--   10=Secretary, 11=Librarian, 12=HeadNursing, 13=HeadMidwifery,
--   14=SeniorLecturer, 15=Lecturer, 16=Matron, 17=Warden,
--   18=SickbayNurse, 19=Driver, 20=SecurityOfficer, 21=Storekeeper,
--   22=GuildPresident, 23=ComputerLab, 24=Bursar, 25=StoreKeeper,
--   26=DirectorAdmissions
-- ═══════════════════════════════════════════════════════════

-- Director General (1) — FULL ACCESS TO ALL
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 1, 1, 1, 1, 1, 1, 1 FROM system_modules;

-- CEO (2) — READ ALL + APPROVE ALL
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 2, 1, 0, 0, 0, 1, 1 FROM system_modules;

-- Director Academics (3) — Academic modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 3, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 1;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 3, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE name IN ('calendar_events','notifications','announcements','document_center','certificates','transcripts');

-- Director Finance (4) — Finance modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 4, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 2;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 4, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE name IN ('calendar_events','notifications','document_center');

-- Director ICT (5) — ICT + System modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 5, 1, 1, 1, 1, 1, 1 FROM system_modules WHERE department_id IN (5, 20);
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 5, 1, 0, 0, 0, 0, 1 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Principal (6) — Academic + HR + Quality + everything except Finance
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 6, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id IN (1,3,4,6,7,8,11,13,15,19,22);
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 6, 1, 0, 0, 0, 0, 1 FROM system_modules WHERE department_id = 2;

-- Deputy Principal (7) — Academic + Quality
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 7, 1, 1, 1, 0, 0, 1 FROM system_modules WHERE department_id IN (1,13,22);
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 7, 1, 0, 0, 0, 0, 1 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Academic Registrar (8) — Academic records + Admissions + Graduation
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 8, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id IN (1,4,15);
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 8, 1, 0, 0, 0, 0, 1 FROM system_modules WHERE name IN ('certificates','transcripts','document_center','calendar_events','notifications');

-- HR Manager (9) — HR modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 9, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 3;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 9, 1, 0, 0, 0, 0, 1 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Secretary (10) — Communications + Calendar + limited HR
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 10, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE department_id IN (11,19);
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 10, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('staff_management','document_center','certificates','notifications');

-- Librarian (11) — Library modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 11, 1, 1, 1, 0, 0, 1 FROM system_modules WHERE department_id = 6;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 11, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Head of Nursing (12) — Nursing/Clinical + Academic
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 12, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id IN (1,8) AND name IN ('academic_records','exams_results','course_management','timetable','grading_system','assessment_scores','clinical_placements','nursing_training','clinical_assessments','incidents');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 12, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Head of Midwifery (13) — Midwifery/Clinical + Academic
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 13, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id IN (1,8) AND name IN ('academic_records','exams_results','course_management','timetable','grading_system','assessment_scores','clinical_placements','midwifery','clinical_assessments','incidents');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 13, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Senior Lecturer (14) — Academic (limited)
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 14, 1, 1, 1, 0, 0, 1 FROM system_modules WHERE name IN ('academic_records','exams_results','course_management','timetable','grading_system','assessment_scores','academic_calendar');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 14, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Lecturer (15) — Academic (basic)
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 15, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('academic_records','exams_results','course_management','timetable','grading_system','assessment_scores');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 15, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Matron (16) — Accommodation + Health
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 16, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE department_id IN (7,8) AND name IN ('hostel_management','meal_tracking','sickbay');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 16, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Warden (17) — Accommodation
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 17, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('hostel_management','meal_tracking');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 17, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Sickbay Nurse (18) — Sickbay + Health
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 18, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('sickbay','clinical_assessments','incidents');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 18, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Driver (19) — Transport only
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 19, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('vehicle_management');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 19, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Security Officer (20) — Security only
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 20, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE department_id = 10;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 20, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Storekeeper (21/25) — Inventory + Store
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 21, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('it_infrastructure','ict_support');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 25, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('it_infrastructure','ict_support');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 21, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 25, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Guild President (22) — Student Activities + limited academic
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 22, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE department_id = 22;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 22, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications','announcements');

-- Computer Lab Manager (23) — ICT Support + Digital Learning
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 23, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('ict_support','digital_learning','ict_policy');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 23, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Bursar (24/27) — Finance modules
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 24, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 2;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 27, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 2;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 24, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 27, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

-- Director Admissions (26/28) — Admissions + Enrollment
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 26, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 4;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 28, 1, 1, 1, 0, 1, 1 FROM system_modules WHERE department_id = 4;
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 26, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications','document_center','certificates');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 28, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications','document_center','certificates');

-- Skills Lab Technician (40) — Lab/Inventory
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 40, 1, 1, 1, 0, 0, 0 FROM system_modules WHERE name IN ('ict_support','digital_learning');
INSERT INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export)
SELECT id, 40, 1, 0, 0, 0, 0, 0 FROM system_modules WHERE name IN ('calendar_events','notifications');

SELECT CONCAT('Created: ', COUNT(*), ' modules') FROM system_modules;
SELECT CONCAT('Departments: ', COUNT(*)) FROM module_departments;
SELECT CONCAT('Permissions: ', COUNT(*)) FROM module_permissions;
