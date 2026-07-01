-- ============================================================
-- ISNM MODULE ROUTES — FIX ALL DASHBOARD LINKS
-- Run ONCE to fix the route column in system_modules
-- Each module gets a proper dashboard path
-- ============================================================

-- LEADERSHIP & STRATEGY (department_id = 1)
UPDATE system_modules SET route = '../dashboards/research-projects.php' WHERE name = 'research_projects';
UPDATE system_modules SET route = '../dashboards/partnerships.php' WHERE name = 'partnerships';

-- ACADEMIC AFFAIRS (department_id = 2)
UPDATE system_modules SET route = '../dashboards/academic-registrar.php?page=academic_records' WHERE name = 'academic_records';
UPDATE system_modules SET route = '../dashboards/exams-results.php' WHERE name = 'exams_results';
UPDATE system_modules SET route = '../dashboards/curriculum-management.php' WHERE name = 'course_management';
UPDATE system_modules SET route = '../dashboards/timetable.php' WHERE name = 'timetable';
UPDATE system_modules SET route = '../dashboards/grade-scales.php' WHERE name = 'grading_system';
UPDATE system_modules SET route = '../dashboards/exams-results.php?page=assessment_scores' WHERE name = 'assessment_scores';
UPDATE system_modules SET route = '../dashboards/academic-calendar.php' WHERE name = 'academic_calendar';
UPDATE system_modules SET route = '../dashboards/director-academics.php?page=academic_reports' WHERE name = 'academic_reports';
UPDATE system_modules SET route = '../dashboards/director-academics.php?page=academic_approvals' WHERE name = 'academic_approvals';
UPDATE system_modules SET route = '../dashboards/quality-assurance.php' WHERE name = 'quality_assurance';

-- FINANCE & ACCOUNTS (department_id = 3)
UPDATE system_modules SET route = '../dashboards/bursar-billing.php' WHERE name = 'fee_management';
UPDATE system_modules SET route = '../dashboards/bursar-payments.php' WHERE name = 'payments';
UPDATE system_modules SET route = '../dashboards/budget-management.php' WHERE name = 'budget_management';
UPDATE system_modules SET route = '../dashboards/bursar-payroll.php' WHERE name = 'payroll';
UPDATE system_modules SET route = '../dashboards/general-ledger.php' WHERE name = 'general_ledger';
UPDATE system_modules SET route = '../dashboards/bursar-tax.php' WHERE name = 'tax_management';
UPDATE system_modules SET route = '../dashboards/bank-reconciliation.php' WHERE name = 'bank_reconciliation';
UPDATE system_modules SET route = '../dashboards/financial-reports.php' WHERE name = 'financial_reports';
UPDATE system_modules SET route = '../dashboards/scholarships-sponsorships.php' WHERE name = 'scholarships_mgmt';
UPDATE system_modules SET route = '../dashboards/bursar-payroll.php?page=bursar_allowances' WHERE name = 'bursar_allowances';
UPDATE system_modules SET route = '../dashboards/storekeeper.php?page=bursar_assets' WHERE name = 'bursar_assets';
UPDATE system_modules SET route = '../dashboards/penalty-configurations.php' WHERE name = 'penalty_config';

-- HR & ADMINISTRATION (department_id = 4)
UPDATE system_modules SET route = '../dashboards/staff-directory.php' WHERE name = 'staff_management';
UPDATE system_modules SET route = '../dashboards/leave-management.php' WHERE name = 'leave_management';
UPDATE system_modules SET route = '../dashboards/staff-attendance.php' WHERE name = 'attendance';
UPDATE system_modules SET route = '../dashboards/recruitment.php' WHERE name = 'recruitment';
UPDATE system_modules SET route = '../dashboards/training-cpd.php' WHERE name = 'training_cpd';
UPDATE system_modules SET route = '../dashboards/performance-appraisal.php' WHERE name = 'appraisals';
UPDATE system_modules SET route = '../dashboards/staff-disciplinary.php' WHERE name = 'disciplinary';
UPDATE system_modules SET route = '../dashboards/resignations.php' WHERE name = 'resignations';
UPDATE system_modules SET route = '../dashboards/hr-manager.php?page=hr_reports' WHERE name = 'hr_reports';
UPDATE system_modules SET route = '../dashboards/hr-manager.php?page=hr_settings' WHERE name = 'hr_settings';
UPDATE system_modules SET route = '../dashboards/professional-licenses.php' WHERE name = 'professional_licenses';

-- STUDENT SERVICES (department_id = 5)
UPDATE system_modules SET route = '../dashboards/director-admissions.php?page=applicant_management' WHERE name = 'applicant_management';
UPDATE system_modules SET route = '../dashboards/intake-planning.php' WHERE name = 'intake_planning';
UPDATE system_modules SET route = '../dashboards/admission-letters.php' WHERE name = 'admission_letters';
UPDATE system_modules SET route = '../dashboards/director-admissions.php?page=enrollment' WHERE name = 'enrollment';
UPDATE system_modules SET route = '../dashboards/graduation-management.php' WHERE name = 'graduation_mgmt';
UPDATE system_modules SET route = '../dashboards/staff_transcript_generation.php' WHERE name = 'transcript_requests';
UPDATE system_modules SET route = '../dashboards/print_transcript.php' WHERE name = 'transcripts';
UPDATE system_modules SET route = '../dashboards/print_certificate.php' WHERE name = 'certificates';
UPDATE system_modules SET route = '../dashboards/scholarships-sponsorships.php' WHERE name = 'scholarships_mgmt';

-- OPERATIONS & LOGISTICS (department_id = 6)
UPDATE system_modules SET route = '../dashboards/storekeeper.php' WHERE name = 'procurement';
UPDATE system_modules SET route = '../dashboards/storekeeper.php?page=inventory' WHERE name = 'inventory';
UPDATE system_modules SET route = '../dashboards/fuel-trips.php' WHERE name = 'fuel_management';
UPDATE system_modules SET route = '../dashboards/drivers.php' WHERE name = 'vehicle_management';
UPDATE system_modules SET route = '../dashboards/contracts-management.php' WHERE name = 'contracts';
UPDATE system_modules SET route = '../dashboards/cost-center-management.php' WHERE name = 'cost_centers';
UPDATE system_modules SET route = '../dashboards/duty-rosters.php' WHERE name = 'duty_rosters';

-- COMPLIANCE & QUALITY (department_id = 7)
UPDATE system_modules SET route = '../dashboards/system-admin.php?page=system_settings' WHERE name = 'system_settings';
UPDATE system_modules SET route = '../dashboards/system-admin.php?page=user_management' WHERE name = 'user_management';
UPDATE system_modules SET route = '../dashboards/audit-management.php' WHERE name = 'audit_trail';
UPDATE system_modules SET route = '../dashboards/system-admin.php?page=backup_management' WHERE name = 'backup_management';
UPDATE system_modules SET route = '../dashboards/recycle_bin.php' WHERE name = 'recycle_bin';

-- CLINICAL & HEALTH (department_id = 8)
UPDATE system_modules SET route = '../dashboards/clinical-placement.php' WHERE name = 'clinical_placements';
UPDATE system_modules SET route = '../dashboards/head-nursing.php' WHERE name = 'nursing_training';
UPDATE system_modules SET route = '../dashboards/head-midwifery.php' WHERE name = 'midwifery';
UPDATE system_modules SET route = '../dashboards/sickbay.php' WHERE name = 'sickbay';
UPDATE system_modules SET route = '../dashboards/clinical-placement.php?page=clinical_assessments' WHERE name = 'clinical_assessments';
UPDATE system_modules SET route = '../dashboards/head-nursing.php?page=incidents' WHERE name = 'incidents';

-- SYSTEM & SETTINGS (department_id = 9)
UPDATE system_modules SET route = '../dashboards/director-ict.php?page=it_infrastructure' WHERE name = 'it_infrastructure';
UPDATE system_modules SET route = '../dashboards/cybersecurity.php' WHERE name = 'cybersecurity';
UPDATE system_modules SET route = '../dashboards/it-support-tickets.php' WHERE name = 'ict_support';
UPDATE system_modules SET route = '../dashboards/ict-policy.php' WHERE name = 'ict_policy';
UPDATE system_modules SET route = '../dashboards/system-admin.php?page=system_logs' WHERE name = 'system_logs';
UPDATE system_modules SET route = '../dashboards/digital-learning.php' WHERE name = 'digital_learning';
UPDATE system_modules SET route = '../dashboards/school-librarian.php?page=library_catalog' WHERE name = 'library_catalog';
UPDATE system_modules SET route = '../dashboards/school-librarian.php?page=library_borrowing' WHERE name = 'library_borrowing';
UPDATE system_modules SET route = '../dashboards/school-librarian.php?page=library_resources' WHERE name = 'library_resources';
UPDATE system_modules SET route = '../dashboards/school-librarian.php?page=library_fines' WHERE name = 'library_fines';
UPDATE system_modules SET route = '../dashboards/school-librarian.php?page=library_management' WHERE name = 'library_management';
UPDATE system_modules SET route = '../dashboards/hostel-management.php' WHERE name = 'hostel_management';
UPDATE system_modules SET route = '../dashboards/meal-accommodation.php' WHERE name = 'meal_tracking';
UPDATE system_modules SET route = '../dashboards/notifications.php' WHERE name = 'notifications';
UPDATE system_modules SET route = '../dashboards/messaging.php' WHERE name = 'messaging';
UPDATE system_modules SET route = '../dashboards/student-announcements.php' WHERE name = 'announcements';
UPDATE system_modules SET route = '../dashboards/document_management.php' WHERE name = 'document_center';
UPDATE system_modules SET route = '../dashboards/academic-calendar.php?page=calendar_events' WHERE name = 'calendar_events';
UPDATE system_modules SET route = '../dashboards/guild-president.php?page=guild_management' WHERE name = 'guild_management';
UPDATE system_modules SET route = '../dashboards/guild-president.php?page=sports_events' WHERE name = 'sports_events';
UPDATE system_modules SET route = '../dashboards/counseling-welfare.php' WHERE name = 'counseling';
UPDATE system_modules SET route = '../dashboards/volunteer-applications.php' WHERE name = 'volunteer_applications';
UPDATE system_modules SET route = '../dashboards/security.php?page=access_control' WHERE name = 'access_control';
UPDATE system_modules SET route = '../dashboards/visitor-access.php' WHERE name = 'visitor_management';
UPDATE system_modules SET route = '../dashboards/security.php?page=security_patrols' WHERE name = 'security_patrols';
UPDATE system_modules SET route = '../dashboards/security.php?page=emergency' WHERE name = 'emergency';
UPDATE system_modules SET route = '../dashboards/intake-planning.php' WHERE name = 'admission_requirements';
UPDATE system_modules SET route = '../dashboards/onboarding.php' WHERE name = 'onboarding';

-- STUDENT PORTAL modules (is_student_module = 1)
UPDATE system_modules SET route = '../student.php?page=my_academic' WHERE name = 'my_academic';
UPDATE system_modules SET route = '../student.php?page=my_exams' WHERE name = 'my_exams';
UPDATE system_modules SET route = '../student.php?page=my_fees' WHERE name = 'my_fees';
UPDATE system_modules SET route = '../student.php?page=my_timetable' WHERE name = 'my_timetable';
UPDATE system_modules SET route = '../student-profile.php' WHERE name = 'my_profile';
UPDATE system_modules SET route = '../student.php?page=my_documents' WHERE name = 'my_documents';
UPDATE system_modules SET route = '../student.php?page=my_requests' WHERE name = 'my_requests';
UPDATE system_modules SET route = '../student.php?page=my_discipline' WHERE name = 'my_discipline';
UPDATE system_modules SET route = '../student.php?page=my_welfare' WHERE name = 'my_welfare';

-- Verify
SELECT m.name, m.route, d.label as department
FROM system_modules m
JOIN module_departments d ON m.department_id = d.id
WHERE m.is_active = 1
ORDER BY d.sort_order, m.sort_order;
