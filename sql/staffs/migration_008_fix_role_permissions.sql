-- Migration 008: Fix module_permissions for each role
-- Only role-specific modules, no cross-role leakage

-- Delete bad permissions for roles that have too many modules
DELETE FROM module_permissions WHERE role_id IN (1, 2, 6, 7);

-- =====================================================
-- ROLE 1: Director General (id=1)
-- Executive overview + System administration (super admin)
-- NOT: Individual payroll, individual exam scores, individual fee payments
-- =====================================================
INSERT INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
-- Executive Overview (READ only)
(1, 8, 1, 0, 0, 0, 0, 1),   -- academic_reports
(1, 17, 1, 0, 0, 0, 0, 1),  -- financial_reports
(1, 29, 1, 0, 0, 0, 0, 1),  -- hr_reports
-- Approvals
(1, 75, 1, 1, 1, 0, 1, 0),  -- approval_workflows
(1, 9, 1, 0, 0, 0, 1, 0),   -- academic_approvals
-- Notifications & Calendar
(1, 60, 1, 1, 1, 0, 0, 0),  -- notifications
(1, 62, 1, 1, 1, 0, 0, 0),  -- announcements
(1, 76, 1, 1, 1, 0, 0, 0),  -- calendar_events
-- Documents
(1, 63, 1, 1, 1, 0, 0, 0),  -- document_center
(1, 64, 1, 1, 1, 0, 0, 0),  -- certificates
(1, 65, 1, 0, 0, 0, 0, 0),  -- transcripts
-- Quality & Partnerships
(1, 66, 1, 1, 1, 0, 0, 0),  -- quality_assurance
(1, 69, 1, 1, 1, 0, 0, 0),  -- partnerships
(1, 68, 1, 1, 1, 0, 0, 0),  -- research_projects
-- System Administration (DG is super admin)
(1, 77, 1, 1, 1, 1, 0, 0),  -- system_settings
(1, 78, 1, 1, 1, 1, 0, 0),  -- user_management
(1, 79, 1, 0, 0, 0, 0, 1),  -- audit_trail
(1, 80, 1, 1, 0, 0, 0, 0),  -- backup_management
(1, 81, 1, 0, 0, 1, 0, 0);  -- recycle_bin

-- =====================================================
-- ROLE 2: CEO (id=2)
-- Executive oversight only (no system admin, limited create/edit)
-- =====================================================
INSERT INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
-- Executive Overview (READ only)
(2, 8, 1, 0, 0, 0, 0, 1),   -- academic_reports
(2, 17, 1, 0, 0, 0, 0, 1),  -- financial_reports
(2, 29, 1, 0, 0, 0, 0, 1),  -- hr_reports
-- Approvals
(2, 75, 1, 0, 0, 0, 1, 0),  -- approval_workflows
(2, 9, 1, 0, 0, 0, 1, 0),   -- academic_approvals
-- Notifications & Calendar
(2, 60, 1, 1, 0, 0, 0, 0),  -- notifications
(2, 62, 1, 1, 0, 0, 0, 0),  -- announcements
(2, 76, 1, 1, 0, 0, 0, 0),  -- calendar_events
-- Documents (READ only)
(2, 63, 1, 0, 0, 0, 0, 0),  -- document_center
(2, 64, 1, 0, 0, 0, 0, 0),  -- certificates
-- Quality (READ only)
(2, 66, 1, 0, 0, 0, 0, 0);  -- quality_assurance

-- =====================================================
-- ROLE 6: School Principal (id=6)
-- Academic overview + Student affairs + HR overview + Clinical
-- NOT: Financial details, individual exam scores, individual payroll
-- =====================================================
INSERT INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
-- Academic Overview (READ only — principal reviews, doesn't enter data)
(6, 1, 1, 0, 0, 0, 0, 0),   -- academic_records
(6, 2, 1, 0, 0, 0, 0, 0),   -- exams_results
(6, 3, 1, 0, 0, 0, 0, 0),   -- course_management
(6, 4, 1, 0, 0, 0, 0, 0),   -- timetable
(6, 5, 1, 0, 0, 0, 0, 0),   -- grading_system
(6, 7, 1, 0, 0, 0, 0, 0),   -- academic_calendar
(6, 8, 1, 0, 0, 0, 0, 1),   -- academic_reports
(6, 9, 1, 0, 0, 0, 1, 0),   -- academic_approvals
-- Student Affairs
(6, 32, 1, 0, 0, 0, 0, 0),  -- applicant_management
(6, 35, 1, 0, 0, 0, 0, 0),  -- enrollment
(6, 70, 1, 0, 0, 0, 0, 0),  -- graduation_mgmt
-- HR Overview (READ only)
(6, 21, 1, 0, 0, 0, 0, 0),  -- staff_management
(6, 29, 1, 0, 0, 0, 0, 1),  -- hr_reports
-- Clinical Overview
(6, 49, 1, 0, 0, 0, 0, 0),  -- clinical_placements
(6, 50, 1, 0, 0, 0, 0, 0),  -- nursing_training
(6, 51, 1, 0, 0, 0, 0, 0),  -- midwifery
-- Approvals
(6, 75, 1, 0, 0, 0, 1, 0),  -- approval_workflows
-- Notifications & Calendar
(6, 60, 1, 1, 1, 0, 0, 0),  -- notifications
(6, 62, 1, 1, 1, 0, 0, 0),  -- announcements
(6, 76, 1, 1, 1, 0, 0, 0),  -- calendar_events
-- Documents
(6, 63, 1, 1, 1, 0, 0, 0),  -- document_center
(6, 64, 1, 0, 0, 0, 0, 0),  -- certificates
(6, 65, 1, 0, 0, 0, 0, 0),  -- transcripts
-- Quality
(6, 66, 1, 0, 0, 0, 0, 0),  -- quality_assurance
(6, 67, 1, 0, 0, 0, 0, 0),  -- penalty_config
-- Student Welfare
(6, 84, 1, 0, 0, 0, 0, 0),  -- guild_management
(6, 85, 1, 0, 0, 0, 0, 0),  -- sports_events
(6, 86, 1, 0, 0, 0, 0, 0);  -- counseling

-- =====================================================
-- ROLE 7: Deputy Principal (id=7)
-- Academic + Student Affairs + Clinical (with create/edit)
-- =====================================================
INSERT INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
-- Academic
(7, 1, 1, 1, 1, 0, 0, 0),   -- academic_records
(7, 2, 1, 1, 1, 0, 0, 0),   -- exams_results
(7, 3, 1, 1, 1, 0, 0, 0),   -- course_management
(7, 4, 1, 1, 1, 0, 0, 0),   -- timetable
(7, 5, 1, 1, 1, 0, 0, 0),   -- grading_system
(7, 6, 1, 1, 1, 0, 0, 0),   -- assessment_scores
(7, 7, 1, 1, 1, 0, 0, 0),   -- academic_calendar
(7, 8, 1, 0, 0, 0, 0, 1),   -- academic_reports
(7, 9, 1, 0, 0, 0, 1, 0),   -- academic_approvals
-- Student Affairs
(7, 32, 1, 1, 1, 0, 0, 0),  -- applicant_management
(7, 35, 1, 1, 1, 0, 0, 0),  -- enrollment
(7, 70, 1, 1, 1, 0, 0, 0),  -- graduation_mgmt
(7, 71, 1, 1, 1, 0, 0, 0),  -- transcript_requests
-- Clinical
(7, 49, 1, 1, 1, 0, 0, 0),  -- clinical_placements
(7, 50, 1, 1, 1, 0, 0, 0),  -- nursing_training
(7, 51, 1, 1, 1, 0, 0, 0),  -- midwifery
(7, 53, 1, 1, 1, 0, 0, 0),  -- clinical_assessments
-- Approvals
(7, 75, 1, 0, 0, 0, 1, 0),  -- approval_workflows
-- Notifications & Calendar
(7, 60, 1, 1, 1, 0, 0, 0),  -- notifications
(7, 62, 1, 1, 1, 0, 0, 0),  -- announcements
(7, 76, 1, 1, 1, 0, 0, 0),  -- calendar_events
-- Documents
(7, 63, 1, 1, 1, 0, 0, 0),  -- document_center
(7, 64, 1, 0, 0, 0, 0, 0),  -- certificates
(7, 65, 1, 0, 0, 0, 0, 0),  -- transcripts
-- Quality
(7, 66, 1, 1, 1, 0, 0, 0),  -- quality_assurance
(7, 67, 1, 1, 1, 0, 0, 0),  -- penalty_config
-- Student Welfare
(7, 84, 1, 1, 1, 0, 0, 0),  -- guild_management
(7, 85, 1, 1, 1, 0, 0, 0),  -- sports_events
(7, 86, 1, 1, 1, 0, 0, 0),  -- counseling
(7, 87, 1, 1, 1, 0, 0, 0);  -- volunteer_applications
