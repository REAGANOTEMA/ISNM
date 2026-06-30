-- ============================================================
-- ISNM MODULE PERMISSIONS — FINAL
-- Run AFTER module_registry_FINAL.sql
-- Uses INSERT IGNORE to skip existing records
-- ============================================================

-- Clear existing permissions first (clean slate)
-- TRUNCATE TABLE module_permissions;

-- Director General (1) — Executive overview + System admin
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_reports'), 1, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='financial_reports'), 1, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='hr_reports'), 1, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='approval_workflows'), 1, 1, 1, 1, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 1, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcripts'), 1, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='quality_assurance'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='research_projects'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='partnerships'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='system_settings'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='user_management'), 1, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='audit_trail'), 1, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='backup_management'), 1, 1, 1, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='recycle_bin'), 1, 1, 0, 0, 1, 0, 0);

-- CEO (2) — Executive oversight only
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_reports'), 2, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='financial_reports'), 2, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='hr_reports'), 2, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='approval_workflows'), 2, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 2, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 2, 1, 1, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 2, 1, 1, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 2, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 2, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='quality_assurance'), 2, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 2, 1, 1, 0, 0, 0, 0);

-- Director Academics (3) — Academic modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='grading_system'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='assessment_scores'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_calendar'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_reports'), 3, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 3, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 3, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcripts'), 3, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 3, 1, 1, 1, 0, 0, 0);

-- Director Finance (4) — Finance modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='fee_management'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='payments'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='budget_management'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='payroll'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='general_ledger'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='tax_management'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bank_reconciliation'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='financial_reports'), 4, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='scholarships_mgmt'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bursar_allowances'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bursar_assets'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 4, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 4, 1, 1, 1, 0, 0, 0);

-- Director ICT (5) — ICT + System modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='it_infrastructure'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='cybersecurity'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='ict_support'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='ict_policy'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='system_logs'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='digital_learning'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 5, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 5, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='system_settings'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='user_management'), 5, 1, 1, 1, 1, 0, 0),
((SELECT id FROM system_modules WHERE name='audit_trail'), 5, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='backup_management'), 5, 1, 1, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='recycle_bin'), 5, 1, 0, 0, 1, 0, 0);

-- School Principal (6) — Academic + Student affairs overview
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='grading_system'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_calendar'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_reports'), 6, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 6, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='staff_management'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='hr_reports'), 6, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='applicant_management'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='enrollment'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_placements'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='nursing_training'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='midwifery'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='approval_workflows'), 6, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 6, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 6, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 6, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcripts'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='quality_assurance'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='penalty_config'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='graduation_mgmt'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 6, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='guild_management'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='sports_events'), 6, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='counseling'), 6, 1, 0, 0, 0, 0, 0);

-- Deputy Principal (7) — Academic + Clinical with CRUD
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='grading_system'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='assessment_scores'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_calendar'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_reports'), 7, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 7, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='applicant_management'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='enrollment'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_placements'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='nursing_training'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='midwifery'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_assessments'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='approval_workflows'), 7, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 7, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcripts'), 7, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='quality_assurance'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='penalty_config'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='graduation_mgmt'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcript_requests'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='guild_management'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='sports_events'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='counseling'), 7, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='volunteer_applications'), 7, 1, 1, 1, 0, 0, 0);

-- Academic Registrar (8) — Academic + Admissions + Graduation
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='grading_system'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='assessment_scores'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_calendar'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='academic_reports'), 8, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='academic_approvals'), 8, 1, 0, 0, 0, 1, 0),
((SELECT id FROM system_modules WHERE name='applicant_management'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='intake_planning'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='admission_letters'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='enrollment'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='graduation_mgmt'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcript_requests'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 8, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 8, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='transcripts'), 8, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 8, 1, 1, 1, 0, 0, 0);

-- HR Manager (9) — HR modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='staff_management'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='leave_management'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='attendance'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='recruitment'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='training_cpd'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='appraisals'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='disciplinary'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='resignations'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='hr_reports'), 9, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='hr_settings'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='professional_licenses'), 9, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 9, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 9, 1, 0, 0, 0, 0, 0);

-- School Secretary (10) — Communications + Calendar
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='notifications'), 10, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='messaging'), 10, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 10, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 10, 1, 1, 1, 0, 0, 0);

-- School Librarian (11) — Library modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='library_catalog'), 11, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='library_borrowing'), 11, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='library_resources'), 11, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='library_fines'), 11, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='library_management'), 11, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 11, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 11, 1, 0, 0, 0, 0, 0);

-- Storekeeper (21) — Procurement + Assets
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='procurement'), 21, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bursar_assets'), 21, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 21, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 21, 1, 0, 0, 0, 0, 0);

-- Guild President (22) — Student Activities
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='guild_management'), 22, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='sports_events'), 22, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='counseling'), 22, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='volunteer_applications'), 22, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 22, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='announcements'), 22, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 22, 1, 0, 0, 0, 0, 0);

-- Computer Lab Manager (23) — ICT Support + Digital Learning
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='ict_support'), 23, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='ict_policy'), 23, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='digital_learning'), 23, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 23, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 23, 1, 0, 0, 0, 0, 0);

-- School Bursar (24) — Finance modules
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='fee_management'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='payments'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='budget_management'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='payroll'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='general_ledger'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='tax_management'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bank_reconciliation'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='financial_reports'), 24, 1, 0, 0, 0, 0, 1),
((SELECT id FROM system_modules WHERE name='scholarships_mgmt'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bursar_allowances'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='bursar_assets'), 24, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 24, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 24, 1, 0, 0, 0, 0, 0);

-- Director Admissions (26) — Admissions + Enrollment
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='applicant_management'), 26, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='intake_planning'), 26, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='admission_letters'), 26, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='enrollment'), 26, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 26, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 26, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 26, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 26, 1, 0, 0, 0, 0, 0);

-- Director Admissions (28) — same as 26
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='applicant_management'), 28, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='intake_planning'), 28, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='admission_letters'), 28, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='enrollment'), 28, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 28, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='document_center'), 28, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='certificates'), 28, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 28, 1, 0, 0, 0, 0, 0);

-- Head of Nursing (29) — Nursing/Clinical
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='nursing_training'), 29, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_placements'), 29, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_assessments'), 29, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='sickbay'), 29, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='incidents'), 29, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 29, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 29, 1, 0, 0, 0, 0, 0);

-- Head of Midwifery (30) — Midwifery/Clinical
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='midwifery'), 30, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_placements'), 30, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_assessments'), 30, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='incidents'), 30, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 30, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 30, 1, 0, 0, 0, 0, 0);

-- Senior Lecturer (31) — Academic
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 31, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 31, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 31, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 31, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='grading_system'), 31, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='assessment_scores'), 31, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 31, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 31, 1, 0, 0, 0, 0, 0);

-- Lecturer (32) — Academic (basic)
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='academic_records'), 32, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='exams_results'), 32, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='course_management'), 32, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='timetable'), 32, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='assessment_scores'), 32, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 32, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 32, 1, 0, 0, 0, 0, 0);

-- Security Officer (33) — Security
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='access_control'), 33, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='visitor_management'), 33, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='security_patrols'), 33, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='emergency'), 33, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 33, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 33, 1, 0, 0, 0, 0, 0);

-- Driver (34) — Transport
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='vehicle_management'), 34, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 34, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 34, 1, 0, 0, 0, 0, 0);

-- Matron (35) — Hostel + Health
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='hostel_management'), 35, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='meal_tracking'), 35, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 35, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 35, 1, 0, 0, 0, 0, 0);

-- Warden (36) — Hostel
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='hostel_management'), 36, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='meal_tracking'), 36, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 36, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 36, 1, 0, 0, 0, 0, 0);

-- Sickbay Nurse (37) — Sickbay + Health
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='sickbay'), 37, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='clinical_assessments'), 37, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='incidents'), 37, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 37, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 37, 1, 0, 0, 0, 0, 0);

-- Computer Lab (39) — ICT
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='ict_support'), 39, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='digital_learning'), 39, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 39, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 39, 1, 0, 0, 0, 0, 0);

-- Skills Lab Technician (40) — ICT
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='ict_support'), 40, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='digital_learning'), 40, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 40, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 40, 1, 0, 0, 0, 0, 0);

-- Skills Lab Manager (41) — ICT
INSERT IGNORE INTO module_permissions (module_id, role_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
((SELECT id FROM system_modules WHERE name='ict_support'), 41, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='digital_learning'), 41, 1, 1, 1, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='notifications'), 41, 1, 0, 0, 0, 0, 0),
((SELECT id FROM system_modules WHERE name='calendar_events'), 41, 1, 0, 0, 0, 0, 0);

-- Verify
SELECT sr.role_name, COUNT(mp.id) as modules
FROM module_permissions mp
JOIN staff_roles sr ON mp.role_id = sr.id
GROUP BY mp.role_id, sr.role_name
ORDER BY mp.role_id;
