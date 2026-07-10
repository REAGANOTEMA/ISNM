<?php
/**
 * @deprecated This file is no longer used. The active sidebar system is
 *   includes/sidebar.php → includes/sidebar_groups.php (static grouped sidebar)
 *   includes/dynamic_sidebar.php  (DB-driven sidebar override)
 * Kept for reference only. Will be removed in a future cleanup.
 * Last used: never (dead code since inception).
 */
if (function_exists('getSidebarConfig')) return;

function getSidebarConfig(string $role): array {
    $role = normalizeRoleKey($role);
    $configs = getAllSidebarConfigs();
    return $configs[$role] ?? $configs['staff_default'];
}

function normalizeRoleKey(string $role): string {
    $map = [
        'director general' => 'director_general',
        'director-general' => 'director_general',
        'director_general' => 'director_general',
        'director academics' => 'director_academics',
        'director-academics' => 'director_academics',
        'director_academics' => 'director_academics',
        'director finance' => 'director_finance',
        'director-finance' => 'director_finance',
        'director_finance' => 'director_finance',
        'director ict' => 'director_ict',
        'director-ict' => 'director_ict',
        'director_ict' => 'director_ict',
        'director admissions' => 'director_admissions',
        'director-admissions' => 'director_admissions',
        'director_admissions' => 'director_admissions',
        'admissions' => 'director_admissions',
        'admissions officer' => 'director_admissions',
        'admissions clerk' => 'director_admissions',
        'school principal' => 'principal',
        'principal' => 'principal',
        'deputy principal' => 'deputy_principal',
        'deputy-principal' => 'deputy_principal',
        'deputy_principal' => 'deputy_principal',
        'academic registrar' => 'academic_registrar',
        'academic-registrar' => 'academic_registrar',
        'academic_registrar' => 'academic_registrar',
        'school bursar' => 'bursar',
        'bursar' => 'bursar',
        'school secretary' => 'secretary',
        'secretary' => 'secretary',
        'school librarian' => 'librarian',
        'librarian' => 'librarian',
        'hr manager' => 'hr',
        'hr-manager' => 'hr',
        'hr_manager' => 'hr',
        'hr' => 'hr',
        'head of nursing' => 'head_nursing',
        'head-nursing' => 'head_nursing',
        'head_nursing' => 'head_nursing',
        'head of midwifery' => 'head_midwifery',
        'head-midwifery' => 'head_midwifery',
        'head_midwifery' => 'head_midwifery',
        'senior lecturer' => 'senior_lecturer',
        'senior-lecturer' => 'senior_lecturer',
        'senior_lecturer' => 'senior_lecturer',
        'senior lecturers' => 'senior_lecturer',
        'lecturer' => 'lecturer',
        'lecturers' => 'lecturer',
        'teacher' => 'lecturer',
        'teachers' => 'lecturer',
        'non-teaching-staff' => 'non_teaching',
        'non_teaching' => 'non_teaching',
        'matron' => 'matron',
        'matrons' => 'matron',
        'warden' => 'wardens',
        'wardens' => 'wardens',
        'storekeeper' => 'store',
        'store' => 'store',
        'driver' => 'drivers',
        'drivers' => 'drivers',
        'security' => 'security',
        'security officer' => 'security',
        'computer lab' => 'computer_lab',
        'computer_lab' => 'computer_lab',
        'computer laboratory' => 'computer_lab',
        'computer lab manager' => 'computer_lab',
        'computer laboratory manager' => 'computer_lab',
        'skills lab' => 'skills_lab',
        'skills_lab' => 'skills_lab',
        'skills laboratory' => 'skills_lab',
        'skills lab manager' => 'skills_lab',
        'skills lab technician' => 'skills_lab',
        'skills laboratory manager' => 'skills_lab',
        'sickbay nurse' => 'sick_bay',
        'sickbay' => 'sick_bay',
        'sick_bay' => 'sick_bay',
        'guild president' => 'guild',
        'guild' => 'guild',
        'student' => 'student',
        'students' => 'student',
        'system admin' => 'system_admin',
        'system-admin' => 'system_admin',
        'system_admin' => 'system_admin',
        'admin' => 'system_admin',
        'ceo' => 'ceo',
    ];
    return $map[strtolower(trim($role))] ?? 'staff_default';
}

function getAllSidebarConfigs(): array {
    return [
        // ═══════════════════════════════════════════════
        // CEO — full access to everything
        // ═══════════════════════════════════════════════
        'ceo' => [
            'brand' => 'Executive Office',
            'brand_icon' => 'fas fa-crown',
            'dashboard_link' => 'dashboards/ceo.php',
            'modules' => [
                'dashboard' => ['label' => 'Executive Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/ceo.php'],
                'strategic' => ['label' => 'Strategic Overview', 'icon' => 'fas fa-chart-line', 'link' => 'dashboards/ceo.php?view=strategic'],
                'institution_stats' => ['label' => 'Institution Stats', 'icon' => 'fas fa-university', 'link' => 'dashboards/ceo.php?view=stats'],
                'director_general' => ['label' => 'Director General', 'icon' => 'fas fa-user-tie', 'link' => 'dashboards/director-general.php'],
                'director_academics' => ['label' => 'Director Academics', 'icon' => 'fas fa-book', 'link' => 'dashboards/director-academics.php'],
                'director_finance' => ['label' => 'Director Finance', 'icon' => 'fas fa-coins', 'link' => 'dashboards/director-finance.php'],
                'director_ict' => ['label' => 'Director ICT', 'icon' => 'fas fa-laptop', 'link' => 'dashboards/director-ict.php'],
                'admissions' => ['label' => 'Admissions', 'icon' => 'fas fa-user-plus', 'link' => 'dashboards/director-admissions.php'],
                'audit' => ['label' => 'Audit Trail', 'icon' => 'fas fa-clipboard-check', 'link' => 'dashboards/audit-management.php'],
                'quality' => ['label' => 'Quality Assurance', 'icon' => 'fas fa-shield-alt', 'link' => 'dashboards/quality-assurance.php'],
                'reports' => ['label' => 'Executive Reports', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/financial-reports.php'],
                'settings' => ['label' => 'System Settings', 'icon' => 'fas fa-cog', 'link' => 'dashboards/system-admin.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Director General
        // ═══════════════════════════════════════════════
        'director_general' => [
            'brand' => 'Director General',
            'brand_icon' => 'fas fa-user-tie',
            'dashboard_link' => 'dashboards/director-general.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/director-general.php'],
                'institution' => ['label' => 'Institution Overview', 'icon' => 'fas fa-university', 'link' => 'dashboards/director-general.php?view=overview'],
                'staff' => ['label' => 'Staff Management', 'icon' => 'fas fa-users', 'link' => 'dashboards/director-general.php?view=staff'],
                'departments' => ['label' => 'Departments', 'icon' => 'fas fa-sitemap', 'link' => 'dashboards/director-general.php?view=departments'],
                'approvals' => ['label' => 'Approvals', 'icon' => 'fas fa-check-double', 'link' => 'dashboards/director-general.php?view=approvals'],
                'announcements' => ['label' => 'Announcements', 'icon' => 'fas fa-bullhorn', 'link' => 'dashboards/director-general.php?view=announcements'],
                'reports' => ['label' => 'Reports', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/financial-reports.php'],
                'news' => ['label' => 'News Management', 'icon' => 'fas fa-newspaper', 'link' => 'dashboards/news.php'],
                'health' => ['label' => 'System Health', 'icon' => 'fas fa-heartbeat', 'link' => 'includes/dg_system_health.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Director Academics
        // ═══════════════════════════════════════════════
        'director_academics' => [
            'brand' => 'Academic Directorate',
            'brand_icon' => 'fas fa-graduation-cap',
            'dashboard_link' => 'dashboards/director-academics.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/director-academics.php'],
                'programs' => ['label' => 'Programs', 'icon' => 'fas fa-sitemap', 'link' => 'dashboards/director-academics.php?view=programs'],
                'curriculum' => ['label' => 'Curriculum', 'icon' => 'fas fa-book', 'link' => 'dashboards/curriculum-management.php'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/timetable.php'],
                'exams' => ['label' => 'Examinations', 'icon' => 'fas fa-pencil-alt', 'link' => 'dashboards/exams-results.php'],
                'academic_calendar' => ['label' => 'Academic Calendar', 'icon' => 'fas fa-calendar', 'link' => 'dashboards/academic-calendar.php'],
                'quality' => ['label' => 'Quality Assurance', 'icon' => 'fas fa-shield-alt', 'link' => 'dashboards/quality-assurance.php'],
                'accreditation' => ['label' => 'Accreditation', 'icon' => 'fas fa-certificate', 'link' => 'dashboards/accreditation.php'],
                'reports' => ['label' => 'Academic Reports', 'icon' => 'fas fa-chart-bar', 'link' => 'dashboards/director-academics.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Director Finance
        // ═══════════════════════════════════════════════
        'director_finance' => [
            'brand' => 'Finance Directorate',
            'brand_icon' => 'fas fa-coins',
            'dashboard_link' => 'dashboards/director-finance.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/director-finance.php'],
                'budget' => ['label' => 'Budget Management', 'icon' => 'fas fa-calculator', 'link' => 'dashboards/budget-management.php'],
                'expenditure' => ['label' => 'Expenditure', 'icon' => 'fas fa-money-bill-wave', 'link' => 'dashboards/expenditure-tracking.php'],
                'financial_reports' => ['label' => 'Financial Reports', 'icon' => 'fas fa-file-invoice', 'link' => 'dashboards/financial-reports.php'],
                'cost_centers' => ['label' => 'Cost Centers', 'icon' => 'fas fa-layer-group', 'link' => 'dashboards/cost-center-management.php'],
                'procurement' => ['label' => 'Procurement', 'icon' => 'fas fa-shopping-cart', 'link' => 'dashboards/procurement-oversight.php'],
                'ura' => ['label' => 'URA Reporting', 'icon' => 'fas fa-file-invoice-dollar', 'link' => 'dashboards/ura-reporting.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Director ICT
        // ═══════════════════════════════════════════════
        'director_ict' => [
            'brand' => 'ICT Directorate',
            'brand_icon' => 'fas fa-laptop-code',
            'dashboard_link' => 'dashboards/director-ict.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/director-ict.php'],
                'assets' => ['label' => 'ICT Assets', 'icon' => 'fas fa-server', 'link' => 'dashboards/asset-management.php'],
                'support' => ['label' => 'IT Support', 'icon' => 'fas fa-headset', 'link' => 'dashboards/it-support-tickets.php'],
                'network' => ['label' => 'Network', 'icon' => 'fas fa-network-wired', 'link' => 'dashboards/cybersecurity.php'],
                'lab' => ['label' => 'Computer Lab', 'icon' => 'fas fa-desktop', 'link' => 'dashboards/computer_lab.php'],
                'digital' => ['label' => 'Digital Learning', 'icon' => 'fas fa-globe', 'link' => 'dashboards/digital-learning.php'],
                'policy' => ['label' => 'ICT Policy', 'icon' => 'fas fa-file-contract', 'link' => 'dashboards/ict-policy.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Director Admissions
        // ═══════════════════════════════════════════════
        'director_admissions' => [
            'brand' => 'Admissions',
            'brand_icon' => 'fas fa-user-plus',
            'dashboard_link' => 'dashboards/director-admissions.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/director-admissions.php'],
                'applications' => ['label' => 'Applications', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/director-admissions.php?view=applications'],
                'intake' => ['label' => 'Intake Planning', 'icon' => 'fas fa-calendar-plus', 'link' => 'dashboards/intake-planning.php'],
                'onboarding' => ['label' => 'Onboarding', 'icon' => 'fas fa-user-check', 'link' => 'dashboards/onboarding.php'],
                'admission_letters' => ['label' => 'Admission Letters', 'icon' => 'fas fa-envelope', 'link' => 'dashboards/admission-letters.php'],
                'reports' => ['label' => 'Admission Reports', 'icon' => 'fas fa-chart-pie', 'link' => 'dashboards/director-admissions.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Principal
        // ═══════════════════════════════════════════════
        'principal' => [
            'brand' => 'Principal',
            'brand_icon' => 'fas fa-user-graduate',
            'dashboard_link' => 'dashboards/school-principal.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/school-principal.php'],
                'students' => ['label' => 'Student Management', 'icon' => 'fas fa-user-friends', 'link' => 'dashboards/student-management.php'],
                'staff' => ['label' => 'Staff', 'icon' => 'fas fa-chalkboard-teacher', 'link' => 'dashboards/staff-directory.php'],
                'academics' => ['label' => 'Academics', 'icon' => 'fas fa-book-open', 'link' => 'dashboards/school-principal.php?view=academics'],
                'discipline' => ['label' => 'Discipline', 'icon' => 'fas fa-gavel', 'link' => 'dashboards/student-discipline.php'],
                'attendance' => ['label' => 'Attendance', 'icon' => 'fas fa-calendar-check', 'link' => 'dashboards/school-principal.php?view=attendance'],
                'graduation' => ['label' => 'Graduation', 'icon' => 'fas fa-graduation-cap', 'link' => 'dashboards/graduation-management.php'],
                'reports' => ['label' => 'Reports', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/school-principal.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Deputy Principal
        // ═══════════════════════════════════════════════
        'deputy_principal' => [
            'brand' => 'Deputy Principal',
            'brand_icon' => 'fas fa-user-tie',
            'dashboard_link' => 'dashboards/deputy-principal.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/deputy-principal.php'],
                'students' => ['label' => 'Students', 'icon' => 'fas fa-users', 'link' => 'dashboards/deputy-principal.php?view=students'],
                'discipline' => ['label' => 'Discipline', 'icon' => 'fas fa-gavel', 'link' => 'dashboards/deputy-principal.php?view=discipline'],
                'welfare' => ['label' => 'Welfare', 'icon' => 'fas fa-hand-holding-heart', 'link' => 'dashboards/deputy-principal.php?view=welfare'],
                'placements' => ['label' => 'Clinical Placements', 'icon' => 'fas fa-clinic-medical', 'link' => 'dashboards/deputy-principal.php?view=placements'],
                'meetings' => ['label' => 'Meetings', 'icon' => 'fas fa-handshake', 'link' => 'dashboards/deputy-principal.php?view=meetings'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/deputy-principal.php?view=timetable'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Academic Registrar
        // ═══════════════════════════════════════════════
        'academic_registrar' => [
            'brand' => 'Academic Registrar',
            'brand_icon' => 'fas fa-clipboard-list',
            'dashboard_link' => 'dashboards/academic-registrar.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/academic-registrar.php'],
                'students' => ['label' => 'Student Records', 'icon' => 'fas fa-address-book', 'link' => 'dashboards/academic-registrar.php?view=students'],
                'exams' => ['label' => 'Exams Management', 'icon' => 'fas fa-pencil-alt', 'link' => 'dashboards/exams-results.php'],
                'results' => ['label' => 'Results', 'icon' => 'fas fa-chart-bar', 'link' => 'dashboards/exams-results.php?view=results'],
                'transcripts' => ['label' => 'Transcripts', 'icon' => 'fas fa-file-pdf', 'link' => 'dashboards/academic-registrar.php?view=transcripts'],
                'certificates' => ['label' => 'Certificates', 'icon' => 'fas fa-certificate', 'link' => 'dashboards/academic-registrar.php?view=certificates'],
                'graduation' => ['label' => 'Graduation', 'icon' => 'fas fa-graduation-cap', 'link' => 'dashboards/graduation-management.php'],
                'course_reg' => ['label' => 'Course Registration', 'icon' => 'fas fa-user-plus', 'link' => 'dashboards/course-registration.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Bursar
        // ═══════════════════════════════════════════════
        'bursar' => [
            'brand' => 'Bursar',
            'brand_icon' => 'fas fa-calculator',
            'dashboard_link' => 'dashboards/school-bursar.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/school-bursar.php'],
                'billing' => ['label' => 'Billing', 'icon' => 'fas fa-file-invoice', 'link' => 'dashboards/bursar-billing.php'],
                'payments' => ['label' => 'Payments', 'icon' => 'fas fa-money-bill', 'link' => 'dashboards/bursar-payments.php'],
                'requirements' => ['label' => 'Requirements', 'icon' => 'fas fa-clipboard-check', 'link' => 'dashboards/bursar-requirements.php'],
                'ledger' => ['label' => 'Ledger', 'icon' => 'fas fa-book', 'link' => 'dashboards/bursar-ledger.php'],
                'payroll' => ['label' => 'Payroll', 'icon' => 'fas fa-wallet', 'link' => 'dashboards/bursar-payroll.php'],
                'tax' => ['label' => 'Tax', 'icon' => 'fas fa-file-invoice-dollar', 'link' => 'dashboards/bursar-tax.php'],
                'fee_structure' => ['label' => 'Fee Structure', 'icon' => 'fas fa-tags', 'link' => 'dashboards/fee-structure.php'],
                'bank' => ['label' => 'Bank Reconciliation', 'icon' => 'fas fa-university', 'link' => 'dashboards/bank-reconciliation.php'],
                'reports' => ['label' => 'Financial Reports', 'icon' => 'fas fa-chart-pie', 'link' => 'dashboards/bursar-reports.php'],
                'proof_of_payments' => ['label' => 'Proof of Payments', 'icon' => 'fas fa-receipt', 'link' => 'dashboards/proof-of-payments.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Secretary
        // ═══════════════════════════════════════════════
        'secretary' => [
            'brand' => 'School Secretary',
            'brand_icon' => 'fas fa-user-tie',
            'dashboard_link' => 'dashboards/school-secretary.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/school-secretary.php'],
                'appointments' => ['label' => 'Appointments', 'icon' => 'fas fa-calendar-check', 'link' => 'dashboards/school-secretary.php?view=appointments'],
                'documents' => ['label' => 'Document Filing', 'icon' => 'fas fa-folder', 'link' => 'dashboards/school-secretary.php?view=documents'],
                'meetings' => ['label' => 'Meetings', 'icon' => 'fas fa-handshake', 'link' => 'dashboards/school-secretary.php?view=meetings'],
                'communications' => ['label' => 'Communications', 'icon' => 'fas fa-envelope', 'link' => 'dashboards/school-secretary.php?view=communications'],
                'requests' => ['label' => 'Request Tracking', 'icon' => 'fas fa-clipboard-list', 'link' => 'dashboards/school-secretary.php?view=requests'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // HR Manager
        // ═══════════════════════════════════════════════
        'hr' => [
            'brand' => 'Human Resources',
            'brand_icon' => 'fas fa-users-cog',
            'dashboard_link' => 'dashboards/hr-manager.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/hr-manager.php'],
                'staff' => ['label' => 'Staff Records', 'icon' => 'fas fa-id-badge', 'link' => 'dashboards/hr-manager.php?view=staff'],
                'recruitment' => ['label' => 'Recruitment', 'icon' => 'fas fa-user-plus', 'link' => 'dashboards/recruitment.php'],
                'leave' => ['label' => 'Leave Management', 'icon' => 'fas fa-calendar-minus', 'link' => 'dashboards/leave-management.php'],
                'appraisals' => ['label' => 'Performance', 'icon' => 'fas fa-chart-line', 'link' => 'dashboards/performance-appraisal.php'],
                'training' => ['label' => 'Training & CPD', 'icon' => 'fas fa-chalkboard', 'link' => 'dashboards/training-cpd.php'],
                'resignations' => ['label' => 'Resignations', 'icon' => 'fas fa-user-minus', 'link' => 'dashboards/resignations.php'],
                'disciplinary' => ['label' => 'Disciplinary', 'icon' => 'fas fa-gavel', 'link' => 'dashboards/staff-disciplinary.php'],
                'payroll' => ['label' => 'Payroll', 'icon' => 'fas fa-wallet', 'link' => 'dashboards/bursar-payroll.php'],
                'reports' => ['label' => 'HR Reports', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/hr-manager.php?view=reports'],
                'contracts' => ['label' => 'Contracts', 'icon' => 'fas fa-file-signature', 'link' => 'dashboards/contracts-management.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Librarian
        // ═══════════════════════════════════════════════
        'librarian' => [
            'brand' => 'Library',
            'brand_icon' => 'fas fa-book',
            'dashboard_link' => 'dashboards/school-librarian.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/school-librarian.php'],
                'books' => ['label' => 'Books Catalog', 'icon' => 'fas fa-book', 'link' => 'dashboards/school-librarian.php?view=books'],
                'borrowing' => ['label' => 'Borrowing', 'icon' => 'fas fa-hand-holding', 'link' => 'dashboards/school-librarian.php?view=borrowing'],
                'members' => ['label' => 'Members', 'icon' => 'fas fa-users', 'link' => 'dashboards/school-librarian.php?view=members'],
                'fines' => ['label' => 'Fines', 'icon' => 'fas fa-exclamation-triangle', 'link' => 'dashboards/school-librarian.php?view=fines'],
                'digital' => ['label' => 'Digital Resources', 'icon' => 'fas fa-globe', 'link' => 'dashboards/school-librarian.php?view=digital'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Head of Nursing
        // ═══════════════════════════════════════════════
        'head_nursing' => [
            'brand' => 'Nursing Department',
            'brand_icon' => 'fas fa-user-md',
            'dashboard_link' => 'dashboards/head-nursing.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/head-nursing.php'],
                'students' => ['label' => 'Nursing Students', 'icon' => 'fas fa-user-nurse', 'link' => 'dashboards/head-nursing.php?view=students'],
                'clinical' => ['label' => 'Clinical Placements', 'icon' => 'fas fa-clinic-medical', 'link' => 'dashboards/clinical-placement.php'],
                'skills_lab' => ['label' => 'Skills Lab', 'icon' => 'fas fa-flask', 'link' => 'dashboards/skills-lab.php'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/head-nursing.php?view=timetable'],
                'courses' => ['label' => 'Courses', 'icon' => 'fas fa-book-open', 'link' => 'dashboards/head-nursing.php?view=courses'],
                'staff' => ['label' => 'Department Staff', 'icon' => 'fas fa-users', 'link' => 'dashboards/head-nursing.php?view=staff'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Head of Midwifery
        // ═══════════════════════════════════════════════
        'head_midwifery' => [
            'brand' => 'Midwifery Department',
            'brand_icon' => 'fas fa-baby',
            'dashboard_link' => 'dashboards/head-midwifery.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/head-midwifery.php'],
                'students' => ['label' => 'Midwifery Students', 'icon' => 'fas fa-user-nurse', 'link' => 'dashboards/head-midwifery.php?view=students'],
                'clinical' => ['label' => 'Clinical Placements', 'icon' => 'fas fa-clinic-medical', 'link' => 'dashboards/clinical-placement.php'],
                'skills_lab' => ['label' => 'Skills Lab', 'icon' => 'fas fa-flask', 'link' => 'dashboards/skills-lab.php'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/head-midwifery.php?view=timetable'],
                'courses' => ['label' => 'Courses', 'icon' => 'fas fa-book-open', 'link' => 'dashboards/head-midwifery.php?view=courses'],
                'staff' => ['label' => 'Department Staff', 'icon' => 'fas fa-users', 'link' => 'dashboards/head-midwifery.php?view=staff'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Senior Lecturer / Lecturer
        // ═══════════════════════════════════════════════
        'senior_lecturer' => [
            'brand' => 'Senior Lecturer',
            'brand_icon' => 'fas fa-chalkboard-teacher',
            'dashboard_link' => 'dashboards/senior-lecturers.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/senior-lecturers.php'],
                'classes' => ['label' => 'My Classes', 'icon' => 'fas fa-users', 'link' => 'dashboards/senior-lecturers.php?view=classes'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/senior-lecturers.php?view=timetable'],
                'attendance' => ['label' => 'Attendance', 'icon' => 'fas fa-calendar-check', 'link' => 'dashboards/senior-lecturers.php?view=attendance'],
                'marks' => ['label' => 'Marks Entry', 'icon' => 'fas fa-edit', 'link' => 'dashboards/senior-lecturers.php?view=marks'],
                'notes' => ['label' => 'Lecture Notes', 'icon' => 'fas fa-sticky-note', 'link' => 'dashboards/senior-lecturers.php?view=notes'],
                'research' => ['label' => 'Research', 'icon' => 'fas fa-flask', 'link' => 'dashboards/research-projects.php'],
                'leaves' => ['label' => 'Leave', 'icon' => 'fas fa-calendar-minus', 'link' => 'dashboards/leave-management.php'],
            ],
        ],

        'lecturer' => [
            'brand' => 'Lecturer',
            'brand_icon' => 'fas fa-chalkboard-teacher',
            'dashboard_link' => 'dashboards/lecturers.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/lecturers.php'],
                'classes' => ['label' => 'My Classes', 'icon' => 'fas fa-users', 'link' => 'dashboards/lecturers.php?view=classes'],
                'timetable' => ['label' => 'Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/lecturers.php?view=timetable'],
                'attendance' => ['label' => 'Attendance', 'icon' => 'fas fa-calendar-check', 'link' => 'dashboards/lecturers.php?view=attendance'],
                'marks' => ['label' => 'Marks Entry', 'icon' => 'fas fa-edit', 'link' => 'dashboards/lecturers.php?view=marks'],
                'notes' => ['label' => 'Lecture Notes', 'icon' => 'fas fa-sticky-note', 'link' => 'dashboards/lecturers.php?view=notes'],
                'leaves' => ['label' => 'Leave', 'icon' => 'fas fa-calendar-minus', 'link' => 'dashboards/leave-management.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Non-Teaching Staff
        // ═══════════════════════════════════════════════
        'non_teaching' => [
            'brand' => 'Staff Portal',
            'brand_icon' => 'fas fa-user',
            'dashboard_link' => 'dashboards/non-teaching-staff.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/non-teaching-staff.php'],
                'attendance' => ['label' => 'My Attendance', 'icon' => 'fas fa-calendar-check', 'link' => 'dashboards/non-teaching-staff.php?view=attendance'],
                'leaves' => ['label' => 'Leave', 'icon' => 'fas fa-calendar-minus', 'link' => 'dashboards/leave-management.php'],
                'profile' => ['label' => 'My Profile', 'icon' => 'fas fa-id-card', 'link' => 'dashboards/staff_profile_management.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Skills Lab
        // ═══════════════════════════════════════════════
        'skills_lab' => [
            'brand' => 'Skills Laboratory',
            'brand_icon' => 'fas fa-flask',
            'dashboard_link' => 'dashboards/skills-lab.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/skills-lab.php'],
                'equipment' => ['label' => 'Equipment', 'icon' => 'fas fa-tools', 'link' => 'dashboards/skills-lab.php?view=equipment'],
                'sessions' => ['label' => 'Practical Sessions', 'icon' => 'fas fa-chalkboard', 'link' => 'dashboards/skills-lab.php?view=sessions'],
                'bookings' => ['label' => 'Bookings', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/lab-booking-management.php'],
                'inventory' => ['label' => 'Chemical Inventory', 'icon' => 'fas fa-flask', 'link' => 'dashboards/chemical-inventory.php'],
                'maintenance' => ['label' => 'Maintenance', 'icon' => 'fas fa-wrench', 'link' => 'dashboards/skills-lab.php?view=maintenance'],
                'attendance' => ['label' => 'Attendance', 'icon' => 'fas fa-clipboard-list', 'link' => 'dashboards/lab-practical.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Computer Lab
        // ═══════════════════════════════════════════════
        'computer_lab' => [
            'brand' => 'Computer Laboratory',
            'brand_icon' => 'fas fa-desktop',
            'dashboard_link' => 'dashboards/computer_lab.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/computer_lab.php'],
                'computers' => ['label' => 'Computers', 'icon' => 'fas fa-desktop', 'link' => 'dashboards/computer_lab.php?view=computers'],
                'bookings' => ['label' => 'Lab Bookings', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/lab-booking-management.php'],
                'maintenance' => ['label' => 'Maintenance', 'icon' => 'fas fa-wrench', 'link' => 'dashboards/computer_lab.php?view=maintenance'],
                'usage' => ['label' => 'Usage Stats', 'icon' => 'fas fa-chart-bar', 'link' => 'dashboards/computer_lab.php?view=stats'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Sick Bay
        // ═══════════════════════════════════════════════
        'sick_bay' => [
            'brand' => 'Sick Bay',
            'brand_icon' => 'fas fa-ambulance',
            'dashboard_link' => 'dashboards/sickbay.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/sickbay.php'],
                'records' => ['label' => 'Patient Records', 'icon' => 'fas fa-notes-medical', 'link' => 'dashboards/sickbay.php?view=records'],
                'medicine' => ['label' => 'Medicine Stock', 'icon' => 'fas fa-pills', 'link' => 'dashboards/sickbay.php?view=medicine'],
                'sick_leave' => ['label' => 'Sick Leave', 'icon' => 'fas fa-bed', 'link' => 'dashboards/sickbay.php?view=sick-leave'],
                'directory' => ['label' => 'Sickness Directory', 'icon' => 'fas fa-book-medical', 'link' => 'dashboards/sickbay.php?view=directory'],
                'reports' => ['label' => 'Health Reports', 'icon' => 'fas fa-chart-bar', 'link' => 'dashboards/sickbay.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Matron
        // ═══════════════════════════════════════════════
        'matron' => [
            'brand' => 'Matron',
            'brand_icon' => 'fas fa-female',
            'dashboard_link' => 'dashboards/matrons.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/matrons.php'],
                'health' => ['label' => 'Health Records', 'icon' => 'fas fa-notes-medical', 'link' => 'dashboards/matrons.php?view=health'],
                'hostel' => ['label' => 'Hostel Management', 'icon' => 'fas fa-bed', 'link' => 'dashboards/hostel-management.php'],
                'meal' => ['label' => 'Meal & Accommodation', 'icon' => 'fas fa-utensils', 'link' => 'dashboards/meal-accommodation.php'],
                'sickbay' => ['label' => 'Sick Bay', 'icon' => 'fas fa-ambulance', 'link' => 'dashboards/sickbay.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Wardens
        // ═══════════════════════════════════════════════
        'wardens' => [
            'brand' => 'Wardens',
            'brand_icon' => 'fas fa-shield-alt',
            'dashboard_link' => 'dashboards/wardens.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/wardens.php'],
                'hostel' => ['label' => 'Hostel', 'icon' => 'fas fa-bed', 'link' => 'dashboards/hostel-management.php'],
                'discipline' => ['label' => 'Discipline', 'icon' => 'fas fa-gavel', 'link' => 'dashboards/student-discipline.php'],
                'welfare' => ['label' => 'Student Welfare', 'icon' => 'fas fa-hand-holding-heart', 'link' => 'dashboards/counseling-welfare.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Store
        // ═══════════════════════════════════════════════
        'store' => [
            'brand' => 'Store',
            'brand_icon' => 'fas fa-warehouse',
            'dashboard_link' => 'dashboards/storekeeper.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/storekeeper.php'],
                'inventory' => ['label' => 'Inventory', 'icon' => 'fas fa-boxes', 'link' => 'dashboards/storekeeper.php?view=inventory'],
                'requests' => ['label' => 'Store Requests', 'icon' => 'fas fa-clipboard-list', 'link' => 'dashboards/storekeeper.php?view=requests'],
                'orders' => ['label' => 'Purchase Orders', 'icon' => 'fas fa-shopping-cart', 'link' => 'dashboards/storekeeper.php?view=orders'],
                'suppliers' => ['label' => 'Suppliers', 'icon' => 'fas fa-truck', 'link' => 'dashboards/storekeeper.php?view=suppliers'],
                'reports' => ['label' => 'Stock Reports', 'icon' => 'fas fa-chart-bar', 'link' => 'dashboards/storekeeper.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Drivers
        // ═══════════════════════════════════════════════
        'drivers' => [
            'brand' => 'Transport',
            'brand_icon' => 'fas fa-bus',
            'dashboard_link' => 'dashboards/drivers.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/drivers.php'],
                'fleet' => ['label' => 'Fleet', 'icon' => 'fas fa-truck', 'link' => 'dashboards/drivers.php?view=fleet'],
                'fuel' => ['label' => 'Fuel & Trips', 'icon' => 'fas fa-gas-pump', 'link' => 'dashboards/fuel-trips.php'],
                'trips' => ['label' => 'Trip Logs', 'icon' => 'fas fa-route', 'link' => 'dashboards/drivers.php?view=trips'],
                'maintenance' => ['label' => 'Maintenance', 'icon' => 'fas fa-wrench', 'link' => 'dashboards/drivers.php?view=maintenance'],
                'insurance' => ['label' => 'Insurance', 'icon' => 'fas fa-file-contract', 'link' => 'dashboards/drivers.php?view=insurance'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Security
        // ═══════════════════════════════════════════════
        'security' => [
            'brand' => 'Security',
            'brand_icon' => 'fas fa-shield-alt',
            'dashboard_link' => 'dashboards/security.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/security.php'],
                'visitors' => ['label' => 'Visitor Log', 'icon' => 'fas fa-address-book', 'link' => 'dashboards/visitor-access.php'],
                'incidents' => ['label' => 'Incidents', 'icon' => 'fas fa-exclamation-triangle', 'link' => 'dashboards/security.php?view=incidents'],
                'patrol' => ['label' => 'Patrol Logs', 'icon' => 'fas fa-clipboard-list', 'link' => 'dashboards/security.php?view=patrol'],
                'access' => ['label' => 'Access Control', 'icon' => 'fas fa-door-open', 'link' => 'dashboards/security.php?view=access'],
                'reports' => ['label' => 'Security Reports', 'icon' => 'fas fa-file-alt', 'link' => 'dashboards/security.php?view=reports'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Guild President
        // ═══════════════════════════════════════════════
        'guild' => [
            'brand' => 'Guild President',
            'brand_icon' => 'fas fa-users',
            'dashboard_link' => 'dashboards/guild-president.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/guild-president.php'],
                'students' => ['label' => 'Student Body', 'icon' => 'fas fa-user-friends', 'link' => 'dashboards/guild-president.php?view=students'],
                'welfare' => ['label' => 'Welfare', 'icon' => 'fas fa-hand-holding-heart', 'link' => 'dashboards/counseling-welfare.php'],
                'events' => ['label' => 'Events', 'icon' => 'fas fa-calendar-alt', 'link' => 'dashboards/guild-president.php?view=events'],
                'feedback' => ['label' => 'Feedback', 'icon' => 'fas fa-comment', 'link' => 'dashboards/guild-president.php?view=feedback'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Student
        // ═══════════════════════════════════════════════
        'student' => [
            'brand' => 'Student Portal',
            'brand_icon' => 'fas fa-user-graduate',
            'dashboard_link' => 'student_panel/index.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'student_panel/index.php'],
                'timetable' => ['label' => 'My Timetable', 'icon' => 'fas fa-calendar-alt', 'link' => 'student_panel/timetable.php'],
                'exams' => ['label' => 'Examinations', 'icon' => 'fas fa-pencil-alt', 'link' => 'student_panel/exam.php'],
                'progress' => ['label' => 'My Progress', 'icon' => 'fas fa-chart-line', 'link' => 'student_panel/progress.php'],
                'fees' => ['label' => 'Fee Payments', 'icon' => 'fas fa-money-bill', 'link' => 'student_panel/fee-payment.php'],
                'receipts' => ['label' => 'Receipts', 'icon' => 'fas fa-receipt', 'link' => 'student_panel/check-fee-recipt.php'],
                'library' => ['label' => 'Library', 'icon' => 'fas fa-book', 'link' => 'dashboards/student-library.php'],
                'workspace' => ['label' => 'Workspace', 'icon' => 'fas fa-folder-open', 'link' => 'student_panel/workspace.php'],
                'bus' => ['label' => 'Bus Tracking', 'icon' => 'fas fa-bus', 'link' => 'student_panel/buslocation.php'],
                'password' => ['label' => 'Change Password', 'icon' => 'fas fa-key', 'link' => 'student_panel/password.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // System Admin
        // ═══════════════════════════════════════════════
        'system_admin' => [
            'brand' => 'System Admin',
            'brand_icon' => 'fas fa-cogs',
            'dashboard_link' => 'dashboards/system-admin.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/system-admin.php'],
                'users' => ['label' => 'User Management', 'icon' => 'fas fa-users-cog', 'link' => 'dashboards/system-admin.php?view=users'],
                'roles' => ['label' => 'Roles & Permissions', 'icon' => 'fas fa-shield-alt', 'link' => 'dashboards/system-admin.php?view=roles'],
                'backup' => ['label' => 'Backup', 'icon' => 'fas fa-database', 'link' => 'dashboards/system-admin.php?view=backup'],
                'audit' => ['label' => 'Audit Logs', 'icon' => 'fas fa-clipboard-list', 'link' => 'dashboards/audit-management.php'],
                'settings' => ['label' => 'System Settings', 'icon' => 'fas fa-cog', 'link' => 'dashboards/system-admin.php?view=settings'],
                'recycle' => ['label' => 'Recycle Bin', 'icon' => 'fas fa-trash-restore', 'link' => 'dashboards/recycle_bin.php'],
            ],
        ],

        // ═══════════════════════════════════════════════
        // Default staff (fallback)
        // ═══════════════════════════════════════════════
        'staff_default' => [
            'brand' => 'Staff Portal',
            'brand_icon' => 'fas fa-user',
            'dashboard_link' => 'dashboards/non-teaching-staff.php',
            'modules' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'link' => 'dashboards/non-teaching-staff.php'],
                'profile' => ['label' => 'My Profile', 'icon' => 'fas fa-user', 'link' => 'dashboards/staff_profile_management.php'],
                'messages' => ['label' => 'Messages', 'icon' => 'fas fa-envelope', 'link' => 'dashboards/portal-messages.php'],
                'notifications' => ['label' => 'Notifications', 'icon' => 'fas fa-bell', 'link' => 'dashboards/notifications.php'],
            ],
        ],
    ];
}

/**
 * Render the sidebar HTML from config.
 */
function renderSidebar(array $sidebar): void {
    $brand = htmlspecialchars($sidebar['brand'] ?? 'ISNM');
    $brandIcon = htmlspecialchars($sidebar['brand_icon'] ?? 'fas fa-school');
    $dashboardLink = htmlspecialchars($sidebar['dashboard_link'] ?? 'index.php');
    $modules = $sidebar['modules'] ?? [];
    $currentFile = basename($_SERVER['PHP_SELF']);
    ?>
    <aside class="isnm-sidebar" id="isnmSidebar">
        <div class="sidebar-brand">
            <a href="<?= $dashboardLink ?>">
                <i class="<?= $brandIcon ?>"></i>
                <span><?= $brand ?></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <?php foreach ($modules as $key => $mod):
                    $link = htmlspecialchars($mod['link'] ?? '#');
                    $label = htmlspecialchars($mod['label'] ?? ucfirst($key));
                    $icon = htmlspecialchars($mod['icon'] ?? 'fas fa-circle');
                    $isActive = strpos($_SERVER['REQUEST_URI'], $link) !== false || (basename($link) === $currentFile);
                ?>
                <li class="<?= $isActive ? 'active' : '' ?>">
                    <a href="<?= $link ?>">
                        <i class="<?= $icon ?>"></i>
                        <span><?= $label ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= htmlspecialchars($dashboardLink) ?>" class="sidebar-home">
                <i class="fas fa-home"></i> <span>Home</span>
            </a>
        </div>
    </aside>
    <?php
}

/**
 * Render sidebar CSS inline (avoids extra HTTP request).
 */
function renderSidebarStyles(): void {
    ?>
    <style>
    .isnm-sidebar {
        position: fixed; top: 0; left: 0; bottom: 0; width: 260px;
        background: linear-gradient(180deg, #1a237e 0%, #283593 100%);
        color: #fff; z-index: 1030; display: flex; flex-direction: column;
        transition: transform 0.3s ease; overflow-y: auto;
    }
    .sidebar-brand {
        padding: 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-brand a {
        color: #fff; text-decoration: none; display: flex; align-items: center; gap: 12px;
        font-size: 15px; font-weight: 700; letter-spacing: 0.3px;
    }
    .sidebar-brand i { font-size: 24px; }
    .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
    .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
    .sidebar-nav li a {
        display: flex; align-items: center; gap: 12px; padding: 11px 20px;
        color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px;
        transition: all 0.2s; border-left: 3px solid transparent;
    }
    .sidebar-nav li a:hover {
        background: rgba(255,255,255,0.08); color: #fff; border-left-color: rgba(255,255,255,0.3);
    }
    .sidebar-nav li.active a {
        background: rgba(255,255,255,0.12); color: #fff; border-left-color: #60a5fa;
        font-weight: 600;
    }
    .sidebar-nav li a i { width: 20px; text-align: center; font-size: 16px; }
    .sidebar-footer {
        padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar-footer a {
        color: rgba(255,255,255,0.6); text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 13px;
    }
    .sidebar-footer a:hover { color: #fff; }
    .sidebar-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1029;
    }
    .sidebar-overlay.open { display: block; }
    .sidebar-toggle {
        display: none; position: fixed; top: 10px; left: 10px; z-index: 1040;
        background: #1a237e; color: #fff; border: none; width: 40px; height: 40px;
        border-radius: 8px; font-size: 18px; cursor: pointer;
    }
    @media (max-width: 768px) {
        .isnm-sidebar { transform: translateX(-100%); }
        .isnm-sidebar.open { transform: translateX(0); }
        .sidebar-toggle { display: flex; align-items: center; justify-content: center; }
        .main, .main-content { margin-left: 0 !important; }
    }
    @media (min-width: 769px) {
        .main, .main-content { margin-left: 260px; }
    }
    </style>
    <?php
}
