-- ============================================================
-- ISNM MODULE REGISTRY RESTRUCTURE (FIXED)
-- Migration 007: Update department groupings
-- ============================================================

-- ─── UPDATE EXISTING DEPARTMENTS TO NEW GROUPINGS ───

-- Group 1: Leadership & Strategy
UPDATE module_departments SET name='leadership', label='Leadership & Strategy', icon='crown', color='#1e3a8a', sort_order=1 WHERE id=1;
UPDATE module_departments SET name='academic', label='Academic Affairs', icon='book', color='#3b82f6', sort_order=2 WHERE id=2;
UPDATE module_departments SET name='finance', label='Finance & Accounts', icon='money-bill', color='#10b981', sort_order=3 WHERE id=3;
UPDATE module_departments SET name='hr', label='HR & Administration', icon='users', color='#8b5cf6', sort_order=4 WHERE id=4;
UPDATE module_departments SET name='student_services', label='Student Services', icon='user-graduate', color='#f59e0b', sort_order=5 WHERE id=5;
UPDATE module_departments SET name='operations', label='Operations & Logistics', icon='cogs', color='#6366f1', sort_order=6 WHERE id=6;
UPDATE module_departments SET name='compliance', label='Compliance & Quality', icon='shield-alt', color='#ef4444', sort_order=7 WHERE id=7;
UPDATE module_departments SET name='system', label='System & Settings', icon='database', color='#475569', sort_order=8 WHERE id=8;

-- ─── DELETE departments 9-23 (the old ones we're consolidating) ───
-- First move any modules back to a default department
UPDATE system_modules SET department_id = 6 WHERE department_id IN (9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
DELETE FROM module_departments WHERE id > 8;

-- ─── RE-MAP MODULES TO NEW DEPARTMENTS ───

-- Leadership & Strategy (1) - approvals, research, graduation, procurement, tasks
UPDATE system_modules SET department_id = 1 WHERE name IN (
    'academic_approvals', 'approval_workflows', 'calendar_events',
    'research_projects', 'partnerships', 'graduation_mgmt', 'transcript_requests',
    'procurement', 'task_management', 'scholarships_mgmt'
);

-- Academic Affairs (2)
UPDATE system_modules SET department_id = 2 WHERE name IN (
    'academic_records', 'exams_results', 'course_management', 'timetable',
    'grading_system', 'assessment_scores', 'academic_calendar', 'academic_reports',
    'clinical_placements', 'nursing_training', 'midwifery', 'clinical_assessments',
    'incidents'
);

-- Finance & Accounts (3)
UPDATE system_modules SET department_id = 3 WHERE name IN (
    'fee_management', 'payments', 'budget_management', 'payroll',
    'general_ledger', 'tax_management', 'bank_reconciliation', 'financial_reports',
    'bursar_allowances', 'bursar_assets'
);

-- HR & Administration (4)
UPDATE system_modules SET department_id = 4 WHERE name IN (
    'staff_management', 'leave_management', 'attendance', 'recruitment',
    'training_cpd', 'appraisals', 'disciplinary', 'resignations',
    'hr_reports', 'hr_settings', 'professional_licenses'
);

-- Student Services (5) - admissions, library, hostel, health, student portal
UPDATE system_modules SET department_id = 5 WHERE name IN (
    'applicant_management', 'intake_planning', 'admission_letters', 'enrollment',
    'library_catalog', 'library_borrowing', 'library_resources', 'library_fines', 'library_management',
    'hostel_management', 'meal_tracking',
    'sickbay', 'counseling', 'guild_management', 'sports_events', 'volunteer_applications',
    'my_academic', 'my_exams', 'my_fees', 'my_timetable', 'my_profile',
    'my_documents', 'my_requests', 'my_discipline', 'my_welfare'
);

-- Operations & Logistics (6) - security, transport, comms, documents
UPDATE system_modules SET department_id = 6 WHERE name IN (
    'vehicle_management', 'access_control', 'visitor_management', 'security_patrols', 'emergency',
    'notifications', 'messaging', 'announcements',
    'document_center', 'certificates', 'transcripts'
);

-- Compliance & Quality (7)
UPDATE system_modules SET department_id = 7 WHERE name IN (
    'quality_assurance', 'penalty_config', 'digital_learning'
);

-- System & Settings (8) - ICT, website, system admin
UPDATE system_modules SET department_id = 8 WHERE name IN (
    'system_settings', 'user_management', 'audit_trail', 'backup_management', 'recycle_bin',
    'website_content', 'news_management', 'it_infrastructure', 'cybersecurity',
    'ict_support', 'ict_policy', 'system_logs'
);

-- ─── VERIFY ───
SELECT d.label as department, COUNT(m.id) as modules
FROM module_departments d
LEFT JOIN system_modules m ON d.id = m.department_id
WHERE d.is_active = 1
GROUP BY d.id, d.label, d.sort_order
ORDER BY d.sort_order;
