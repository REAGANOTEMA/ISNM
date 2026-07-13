<?php
/**
 * Central Module Configuration for ISNM Hierarchical Dashboard Navigation
 * Organized by actual SQL database departments and tables.
 * Each parent module contains child sub-modules with role-based access.
 */

if (!function_exists('getModuleConfig')) {
function getModuleConfig(): array {
    return [
        [
            'title'    => 'Executive',
            'icon'     => 'fas fa-crown',
            'roles'    => ['director general','ceo','principal','deputy','secretary','director'],
            'children' => [
                ['title' => 'Director General',       'route' => 'director-general.php',              'roles' => ['director general','ceo','system admin']],
                ['title' => 'CEO Dashboard',          'route' => 'ceo.php',                          'roles' => ['ceo','director general']],
                ['title' => 'School Principal',       'route' => 'school-principal.php',             'roles' => ['school principal','principal']],
                ['title' => 'Deputy Principal',       'route' => 'deputy-principal.php',             'roles' => ['deputy','principal']],
                ['title' => 'Teaching & Learning',   'route' => 'deputy-principal.php#teaching',     'roles' => ['deputy','principal']],
                ['title' => 'Student Affairs',       'route' => 'deputy-principal.php#students',     'roles' => ['deputy','principal']],
                ['title' => 'Examinations',          'route' => 'deputy-principal.php#examinations', 'roles' => ['deputy','principal']],
                ['title' => 'Clinical Training',     'route' => 'deputy-principal.php#clinical',     'roles' => ['deputy','principal']],
                ['title' => 'School Secretary',       'route' => 'school-secretary.php',             'roles' => ['school secretary','secretary']],
                ['title' => 'Institution Overview',   'route' => 'director-general.php#executive',   'roles' => ['director general','ceo','principal']],
                ['title' => 'Department Monitoring',  'route' => 'director-general.php#departments', 'roles' => ['director general','ceo','principal']],
                ['title' => 'Director Performance',   'route' => 'director-general.php#performance', 'roles' => ['director general','ceo']],
                ['title' => 'Financial Overview',    'route' => 'director-general.php#financial',   'roles' => ['director general','ceo']],
                ['title' => 'Staff Management',      'route' => 'director-general.php#staff',       'roles' => ['director general','ceo']],
                ['title' => 'Student Management',    'route' => 'director-general.php#student',     'roles' => ['director general','ceo','principal']],
                ['title' => 'Pending Submissions',  'route' => 'director-general.php#services',    'roles' => ['director general','ceo']],
                ['title' => 'Store & Assets',        'route' => 'director-general.php#store',       'roles' => ['director general','ceo']],
                ['title' => 'Communications',        'route' => 'director-general.php#communications', 'roles' => ['director general','ceo']],
                ['title' => 'Quick Actions',         'route' => 'director-general.php#quick',       'roles' => ['director general','ceo']],
                ['title' => 'Pending Approvals',    'route' => 'director-general.php#approvals',  'roles' => ['director general','ceo']],
                ['title' => 'Audit Trail',          'route' => 'director-general.php#audit',      'roles' => ['director general','ceo','system admin']],
            ],
        ],
        [
            'title'    => 'Academic Management',
            'icon'     => 'fas fa-graduation-cap',
            'roles'    => ['director','academics','registrar','lecturer','head','senior','principal'],
            'children' => [
                ['title' => 'Director Academics',     'route' => 'director-academics.php',            'roles' => ['director','academics','principal','ceo']],
                ['title' => 'Program Management',    'route' => 'director-academics.php#programs',   'roles' => ['director','academics','principal','ceo']],
                ['title' => 'Examinations',          'route' => 'director-academics.php#exams',      'roles' => ['director','academics','principal','ceo']],
                ['title' => 'Academic Reports',      'route' => 'director-academics.php#reports',    'roles' => ['director','academics','principal','ceo']],
                ['title' => 'Academic Registrar',     'route' => 'academic-registrar.php',            'roles' => ['registrar','director','principal','deputy']],
                ['title' => 'Programs',               'route' => '../programs.php',                   'roles' => ['registrar','academics','director']],
                ['title' => 'Courses',                'route' => 'course-registration.php',           'roles' => ['registrar','academics','secretary']],
                ['title' => 'Curriculum Management',  'route' => 'curriculum-management.php',         'roles' => ['director','academics','principal','head']],
                ['title' => 'Timetable',              'route' => 'timetable.php',                     'roles' => ['registrar','academics','lecturer','head']],
                ['title' => 'Exams & Results',        'route' => 'exams-results.php',                 'roles' => ['registrar','academics','lecturer','head']],
                ['title' => 'Grade Scales & Grading', 'route' => 'grade-scales.php',                  'roles' => ['academics','registrar','director','principal','head','lecturer']],
                ['title' => 'Academic Calendar',      'route' => 'academic-calendar.php',             'roles' => ['director','academics','registrar','principal']],
                ['title' => 'Graduation Management',  'route' => 'graduation-management.php',         'roles' => ['registrar','academics','director','principal']],
                ['title' => 'Research Projects',      'route' => 'research-projects.php',             'roles' => ['director','academics','principal','head','lecturer']],
                ['title' => 'Accreditation & Compliance','route' => 'accreditation.php',              'roles' => ['director','academics','principal','head']],
                ['title' => 'Quality Assurance',      'route' => 'quality-assurance.php',             'roles' => ['director','academics','principal','head']],
                ['title' => 'Partnerships & Linkages','route' => 'partnerships.php',                  'roles' => ['director','principal','ceo','head']],
                ['title' => 'Lecturers',             'route' => 'lecturers.php',                     'roles' => ['lecturer','teacher','head']],
                ['title' => 'Course Management',     'route' => 'lecturers.php#courses',              'roles' => ['lecturer','teacher','head']],
                ['title' => 'Student Management',    'route' => 'lecturers.php#students',             'roles' => ['lecturer','teacher','head']],
                ['title' => 'Grade Entry',           'route' => 'lecturers.php#grades',               'roles' => ['lecturer','teacher','head']],
                ['title' => 'Senior Lecturers',      'route' => 'senior-lecturers.php',               'roles' => ['senior','lecturer','head']],
                ['title' => 'Course Delivery',       'route' => 'senior-lecturers.php#courses',       'roles' => ['senior','lecturer','head']],
                ['title' => 'Schedule Overview',     'route' => 'senior-lecturers.php#schedule',      'roles' => ['senior','lecturer','head']],
                ['title' => 'Assessment Planning',   'route' => 'senior-lecturers.php#assessments',   'roles' => ['senior','lecturer','head']],
                ['title' => 'Research & Resources',  'route' => 'senior-lecturers.php#resources',     'roles' => ['senior','lecturer','head']],
            ],
        ],
        [
            'title'    => 'Admissions',
            'icon'     => 'fas fa-file-signature',
            'roles'    => ['admissions','director','secretary','Director Admissions'],
            'children' => [
                ['title' => 'Director Admissions',    'route' => 'director-admissions.php',           'roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Dashboard',             'route' => 'director-admissions.php#overview',   'roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'New Applicant',          'route' => 'director-admissions.php#new_applicant','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Applicant Records',      'route' => 'director-admissions.php#applicant_records','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Student Search',         'route' => 'director-admissions.php#student_search','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Intake Management',      'route' => 'director-admissions.php#intake_management','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Admission Approvals',    'route' => 'director-admissions.php#admission_approvals','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Requirement Portal',     'route' => 'director-admissions.php#requirement_portal','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Clearance',              'route' => 'director-admissions.php#requirement_clearance','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Verification',           'route' => 'director-admissions.php#requirement_verification','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Tracking',               'route' => 'director-admissions.php#requirement_tracking','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Registration Readiness', 'route' => 'director-admissions.php#registration_readiness','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Student Registration',   'route' => 'director-admissions.php#student_registration','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Student Activation',     'route' => 'director-admissions.php#student_activation','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Document Verification',  'route' => 'director-admissions.php#document_verification','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Reports',                'route' => 'director-admissions.php#admission_reports','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Intake Statistics',      'route' => 'director-admissions.php#intake_statistics','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Messaging',              'route' => 'director-admissions.php#applicant_messaging','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Notifications',          'route' => 'director-admissions.php#notifications','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Requirement Alerts',     'route' => 'director-admissions.php#requirement_alerts','roles' => ['admissions','director','secretary','Director Admissions']],
                ['title' => 'Student Applications',   'route' => 'admission-letters.php?view=applications','roles' => ['admissions','director','secretary']],
                ['title' => 'Requirements & Clearance','route' => 'admission-letters.php?view=clearance','roles' => ['admissions','director','secretary']],
                ['title' => 'Admission Letters',      'route' => 'admission-letters.php',             'roles' => ['admissions','director','secretary']],
                ['title' => 'Intake Planning',        'route' => 'intake-planning.php',               'roles' => ['admissions','director','secretary']],
            ],
        ],
        [
            'title'    => 'Academic Registrar',
            'icon'     => 'fas fa-clipboard-list',
            'roles'    => ['registrar','director','principal','deputy'],
            'children' => [
                ['title' => 'Overview',             'route' => 'academic-registrar.php#overview',            'roles' => '*'],
                ['title' => 'Student Registration',  'route' => 'academic-registrar.php#course-registration', 'roles' => '*'],
                ['title' => 'Student Records',       'route' => 'academic-registrar.php#student-records',     'roles' => '*'],
                ['title' => 'Student Records (By Set)','route' => 'student-records.php',                     'roles' => ['director','academics','registrar','principal','head','lecturer']],
                ['title' => 'Programmes',            'route' => 'academic-registrar.php#programs',            'roles' => '*'],
                ['title' => 'Courses',               'route' => 'academic-registrar.php#courses',             'roles' => '*'],
                ['title' => 'Grading Management',    'route' => 'academic-registrar.php#grading',             'roles' => '*'],
                ['title' => 'Transcripts',           'route' => 'academic-registrar.php#transcripts',         'roles' => '*'],
                ['title' => 'Certificates',          'route' => 'academic-registrar.php#certificates',        'roles' => '*'],
                ['title' => 'Graduation',            'route' => 'academic-registrar.php#graduation',          'roles' => '*'],
                ['title' => 'Reports',               'route' => 'academic-registrar.php#reports',             'roles' => '*'],
                ['title' => 'Calendar',              'route' => 'academic-registrar.php#academic-calendar',   'roles' => '*'],
                ['title' => 'Notifications',         'route' => 'academic-registrar.php#notifications',       'roles' => '*'],
                ['title' => 'Settings',              'route' => 'academic-registrar.php#settings',            'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Overview',
            'icon'     => 'fas fa-chart-pie',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Financial Analytics','route' => 'school-bursar.php?section=financial_reports','roles' => '*'],
                ['title' => 'Daily Collections',  'route' => 'school-bursar.php?section=daily_collections','roles' => '*'],
                ['title' => 'Debtors List',       'route' => 'school-bursar.php?section=debtors_list','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Student Fees',
            'icon'     => 'fas fa-user-graduate',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Fee Structures',    'route' => 'school-bursar.php?section=fee_structure','roles' => '*'],
                ['title' => 'Student Billing',   'route' => 'school-bursar.php?section=generate_invoice','roles' => '*'],
                ['title' => 'Bulk Billing',      'route' => 'school-bursar.php?section=bulk_billing','roles' => '*'],
                ['title' => 'Fee Adjustments',   'route' => 'school-bursar.php?section=fee_adjustments','roles' => '*'],
                ['title' => 'Student Search',    'route' => 'school-bursar.php?section=student_search','roles' => '*'],
                ['title' => 'Add / Edit Student','route' => 'school-bursar.php?section=student_add','roles' => '*'],
                ['title' => 'Student Statements','route' => 'school-bursar.php?section=student_statement','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Payments',
            'icon'     => 'fas fa-money-bill-wave',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Record Payments',       'route' => 'school-bursar.php?section=record_payment','roles' => '*'],
                ['title' => 'Payment Verification',  'route' => 'school-bursar.php?section=payment_verification','roles' => '*'],
                ['title' => 'Receipts',              'route' => 'school-bursar.php?section=receipt_print','roles' => '*'],
                ['title' => 'Refunds',               'route' => 'school-bursar.php?section=refunds','roles' => '*'],
                ['title' => 'Late Payment',          'route' => 'school-bursar.php?section=late_payment','roles' => '*'],
                ['title' => 'Payment Approvals',     'route' => 'school-bursar.php?section=payment_approvals','roles' => '*'],
                ['title' => 'Payment Providers',     'route' => 'school-bursar.php?page=payment-providers','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Payroll',
            'icon'     => 'fas fa-money-check-alt',
            'roles'    => ['bursar','finance','accountant','payroll','hr','manager','auditor'],
            'children' => [
                ['title' => 'Payroll Dashboard', 'route' => 'payroll.php','roles' => '*'],
                ['title' => 'Enterprise Payroll', 'route' => 'payroll.php','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Budgets & Expenditure',
            'icon'     => 'fas fa-wallet',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Budget Planning',     'route' => 'school-bursar.php?section=budget','roles' => '*'],
                ['title' => 'Expenditure Tracking','route' => 'school-bursar.php?section=expenditure','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Accounts',
            'icon'     => 'fas fa-book',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'General Ledger',      'route' => 'school-bursar.php?section=ledger','roles' => '*'],
                ['title' => 'Chart of Accounts',   'route' => 'school-bursar.php?section=chart_of_accounts','roles' => '*'],
                ['title' => 'Cashbook',            'route' => 'school-bursar.php?section=cashbook','roles' => '*'],
                ['title' => 'Bank Reconciliation', 'route' => 'school-bursar.php?section=reconciliation','roles' => '*'],
                ['title' => 'Tax / URA',           'route' => 'school-bursar.php?section=tax_reports','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Requisitions',
            'icon'     => 'fas fa-clipboard-list',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Pending Requisitions',  'route' => 'school-bursar.php?section=requisitions','roles' => '*'],
                ['title' => 'Approved Requisitions', 'route' => 'school-bursar.php?section=requisitions&filter=approved','roles' => '*'],
                ['title' => 'Rejected Requisitions', 'route' => 'school-bursar.php?section=requisitions&filter=rejected','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Communications',
            'icon'     => 'fas fa-envelope',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Messages',         'route' => 'school-bursar.php?section=communications','roles' => '*'],
                ['title' => 'Financial Notices','route' => 'school-bursar.php?section=communications&tab=notices','roles' => '*'],
                ['title' => 'Sent Messages',    'route' => 'school-bursar.php?section=communications&tab=sent','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Reports',
            'icon'     => 'fas fa-file-alt',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Collection Reports','route' => 'school-bursar.php?section=financial_reports','roles' => '*'],
                ['title' => 'Debtors Reports',   'route' => 'school-bursar.php?section=debtors_list','roles' => '*'],
                ['title' => 'Audit Reports',     'route' => 'school-bursar.php?section=audit_trail','roles' => '*'],
                ['title' => 'Financial Clearance','route' => 'school-bursar.php?section=clearance','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Tools',
            'icon'     => 'fas fa-cog',
            'roles'    => ['bursar','finance','accountant'],
            'children' => [
                ['title' => 'Fee Reminders',         'route' => 'school-bursar.php?section=fee_reminders','roles' => '*'],
                ['title' => 'Auto Deductions',       'route' => 'payment-subscriptions.php','roles' => '*'],
                ['title' => 'Penalty Configurations','route' => 'penalty-configurations.php','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Finance',
            'icon'     => 'fas fa-chart-line',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Director Finance',   'route' => 'director-finance.php','roles' => '*'],
                ['title' => 'Financial Reports',  'route' => 'director-finance.php#reports','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Human Resources',
            'icon'     => 'fas fa-users',
            'roles'    => ['hr','manager','director','principal','head','lecturer','staff'],
            'children' => [
                // ── A. HR Core ──
                ['title' => 'HR Dashboard',           'route' => 'hr-manager.php',                     'roles' => ['hr','manager','director','principal']],
                ['title' => 'Staff Records',          'route' => 'hr-manager.php#staff',                'roles' => ['hr','manager','director','principal']],
                ['title' => 'Roles & Departments',    'route' => 'staff-directory.php',                'roles' => ['hr','manager','director']],
                ['title' => 'Staff Profile',          'route' => 'staff_profile_management.php',       'roles' => '*'],
                ['title' => 'Staff Directory',        'route' => 'staff-directory.php',                'roles' => '*'],
                ['title' => 'Documents',              'route' => 'staff_profile_management.php',       'roles' => '*'],
                // ── B. Attendance ──
                ['title' => 'Attendance Logs',        'route' => 'staff-attendance.php',               'roles' => ['hr','manager','director']],
                ['title' => 'Shift Scheduling',       'route' => 'duty-rosters.php',                   'roles' => ['hr','manager','director','head','matron','warden']],
                // ── C. Leave Management ──
                ['title' => 'Leave Management',       'route' => 'hr-manager.php#leave',                'roles' => ['hr','manager','director','principal']],
                ['title' => 'Leave Dashboard',        'route' => 'leave-management.php',               'roles' => ['hr','manager','director','lecturer','staff']],
                // ── D. Performance ──
                ['title' => 'Performance',            'route' => 'hr-manager.php#performance',          'roles' => ['hr','manager','director','head','principal']],
                ['title' => 'Appraisals',             'route' => 'performance-appraisal.php',          'roles' => ['hr','manager','director','head']],
                // ── E. Training & CPD ──
                ['title' => 'Training & CPD',         'route' => 'hr-manager.php#training',             'roles' => ['hr','manager','lecturer','head']],
                ['title' => 'CPD Dashboard',          'route' => 'training-cpd.php',                   'roles' => ['hr','manager','lecturer','head']],
                // ── F. Recruitment ──
                ['title' => 'Recruitment',            'route' => 'hr-manager.php#recruitment',          'roles' => ['hr','manager','director','principal']],
                ['title' => 'Job Vacancies',          'route' => 'recruitment.php',                    'roles' => ['hr','manager','director','principal']],
                ['title' => 'Onboarding',             'route' => 'onboarding.php',                     'roles' => ['hr','manager','director']],
                ['title' => 'Resignations',           'route' => 'resignations.php',                   'roles' => ['hr','manager','director']],
                // ── G. Payroll (HR View) ──
                ['title' => 'Payroll (HR View)',      'route' => 'hr-manager.php#payroll',              'roles' => ['hr','manager','director']],
                ['title' => 'Salary View',            'route' => '../payroll.php',                      'roles' => ['hr','manager']],
                ['title' => 'Payslips',               'route' => '../payroll.php?section=payslips',     'roles' => ['hr','manager']],
                // ── H. Disciplinary ──
                ['title' => 'Disciplinary',           'route' => 'hr-manager.php#disciplinary',         'roles' => ['hr','manager','director','principal']],
                ['title' => 'Cases',                  'route' => 'staff-disciplinary.php',             'roles' => ['hr','manager','director','principal']],
                // ── I. Licensing & Compliance ──
                ['title' => 'Licensing',              'route' => 'hr-manager.php#licensing',            'roles' => ['hr','manager','director','head','nursing','midwifery']],
                ['title' => 'Professional Licenses',  'route' => 'professional-licenses.php',          'roles' => ['hr','manager','director','head','nursing','midwifery']],
                // ── J. Deployment & Rotation ──
                ['title' => 'Deployment & Rotation',  'route' => 'hr-manager.php#deployment',           'roles' => ['hr','manager','director','head']],
                ['title' => 'Clinical Placements',    'route' => 'clinical-placement.php',             'roles' => ['hr','manager','nursing','midwifery','head','lecturer']],
                ['title' => 'Duty Rosters',           'route' => 'duty-rosters.php',                   'roles' => ['hr','manager','director','head','matron','warden']],
                // ── K. Communication ──
                ['title' => 'Communications',         'route' => 'hr-manager.php#comms',                'roles' => ['hr','manager','director']],
                ['title' => 'News & Announcements',   'route' => '../news.php',                         'roles' => ['hr','manager']],
                // ── L. Reports ──
                ['title' => 'HR Reports',             'route' => 'hr-manager.php#reports',              'roles' => ['hr','manager','director','principal']],
                // ── M. Settings ──
                ['title' => 'HR Settings',            'route' => 'hr-manager.php#settings',             'roles' => ['hr','manager','director','principal']],
                // ── Non-Teaching Staff ──
                ['title' => 'Non-Teaching Staff',     'route' => 'non-teaching-staff.php',             'roles' => ['non-teaching','non teaching','staff','lecturer']],
            ],
        ],
        [
            'title'    => 'Nursing Department',
            'icon'     => 'fas fa-user-nurse',
            'roles'    => ['nursing','head nursing','head'],
            'children' => [
                ['title' => 'Head of Nursing',       'route' => 'head-nursing.php',                   'roles' => ['head nursing','nursing']],
                ['title' => 'Department Overview',   'route' => 'head-nursing.php#overview',           'roles' => ['head nursing','nursing']],
                ['title' => 'Student Management',    'route' => 'head-nursing.php#students',           'roles' => ['head nursing','nursing']],
                ['title' => 'Programs & Courses',    'route' => 'head-nursing.php#programs',           'roles' => ['head nursing','nursing']],
                ['title' => 'Department Reports',    'route' => 'head-nursing.php#reports',            'roles' => ['head nursing','nursing']],
                ['title' => 'Student Records',       'route' => 'head-nursing.php#student-records',    'roles' => ['head nursing','nursing']],
                ['title' => 'Clinical Logbook',      'route' => 'clinical-placement.php?dept=nursing','roles' => ['nursing','midwifery','head','lecturer']],
                ['title' => 'Practical Assessment',  'route' => 'clinical-placement.php?view=assessment','roles' => ['nursing','head nursing','lecturer']],
                ['title' => 'Skills Training',       'route' => 'skills-lab.php',                    'roles' => ['nursing','head nursing','lecturer']],
                ['title' => 'Clinical Placements',   'route' => 'clinical-placement.php',             'roles' => ['nursing','midwifery','head','lecturer']],
            ],
        ],
        [
            'title'    => 'Midwifery Department',
            'icon'     => 'fas fa-baby',
            'roles'    => ['midwifery','head midwifery','head'],
            'children' => [
                ['title' => 'Head of Midwifery',     'route' => 'head-midwifery.php',                 'roles' => ['head midwifery','midwifery']],
                ['title' => 'Department Overview',   'route' => 'head-midwifery.php#overview',         'roles' => ['head midwifery','midwifery']],
                ['title' => 'Student Management',    'route' => 'head-midwifery.php#students',         'roles' => ['head midwifery','midwifery']],
                ['title' => 'Programs & Courses',    'route' => 'head-midwifery.php#programs',         'roles' => ['head midwifery','midwifery']],
                ['title' => 'Department Reports',    'route' => 'head-midwifery.php#reports',          'roles' => ['head midwifery','midwifery']],
                ['title' => 'Antenatal Care',        'route' => 'clinical-placement.php?dept=midwifery&view=antenatal','roles' => ['midwifery','head midwifery','lecturer']],
                ['title' => 'Labor & Delivery',      'route' => 'clinical-placement.php?dept=midwifery&view=delivery','roles' => ['midwifery','head midwifery','lecturer']],
                ['title' => 'Postnatal Care',        'route' => 'clinical-placement.php?dept=midwifery&view=postnatal','roles' => ['midwifery','head midwifery','lecturer']],
                ['title' => 'Family Planning',       'route' => 'clinical-placement.php?dept=midwifery&view=fp','roles' => ['midwifery','head midwifery','lecturer']],
                ['title' => 'Clinical Placements',   'route' => 'clinical-placement.php?dept=midwifery','roles' => ['midwifery','head midwifery','lecturer']],
            ],
        ],
        [
            'title'    => 'ICT',
            'icon'     => 'fas fa-laptop-code',
            'roles'    => ['director','ict','it','system admin','principal'],
            'children' => [
                // ── Dashboard ──
                ['title' => 'ICT Dashboard',           'route' => 'director-ict.php',                     'roles' => ['director','ict','it','system admin']],
                ['title' => 'System Status',            'route' => 'director-ict.php?tab=dashboard',        'roles' => ['director','ict','it']],
                ['title' => 'Critical Alerts',          'route' => 'director-ict.php?tab=security#alerts', 'roles' => ['director','ict','it']],
                ['title' => 'Notifications',            'route' => 'director-ict.php?tab=monitoring',      'roles' => ['director','ict','it']],
                // ── ICT Administration ──
                ['title' => 'ICT Staff',                'route' => '../staff-directory.php',                'roles' => ['director','ict','it']],
                ['title' => 'ICT Policies',             'route' => '../ict-policy.php',                    'roles' => ['director','ict','it']],
                // ── ICT Asset Management ──
                ['title' => 'Asset Register',           'route' => 'director-ict.php?tab=assets',           'roles' => ['director','ict','it']],
                ['title' => 'Asset Assignments',        'route' => 'director-ict.php?tab=assets#assign',    'roles' => ['director','ict','it']],
                ['title' => 'Asset Maintenance',        'route' => 'director-ict.php?tab=assets#maint',     'roles' => ['director','ict','it']],
                ['title' => 'Asset Categories',         'route' => 'director-ict.php?tab=settings#cats',    'roles' => ['director','ict','it']],
                // ── Infrastructure Management ──
                ['title' => 'Infrastructure',           'route' => 'director-ict.php?tab=infrastructure',   'roles' => ['director','ict','it']],
                ['title' => 'Servers',                  'route' => 'director-ict.php?tab=infrastructure',   'roles' => ['director','ict','it']],
                ['title' => 'Network Devices',          'route' => 'director-ict.php?tab=infrastructure',   'roles' => ['director','ict','it']],
                ['title' => 'WiFi Management',          'route' => 'director-ict.php?tab=infrastructure#wifi','roles' => ['director','ict','it']],
                ['title' => 'Network Logs',             'route' => 'director-ict.php?tab=infrastructure#logs','roles' => ['director','ict','it']],
                // ── User & Access Management ──
                ['title' => 'User Management',          'route' => 'director-ict.php?tab=users',             'roles' => ['director','ict','it','system admin']],
                ['title' => 'Staff Accounts',           'route' => 'director-ict.php?tab=users',             'roles' => ['director','ict','it']],
                ['title' => 'Login Sessions',           'route' => 'director-ict.php?tab=users#logins',      'roles' => ['director','ict','it']],
                // ── ERP System Management ──
                ['title' => 'ERP System',               'route' => 'director-ict.php?tab=erp',              'roles' => ['director','ict','it','system admin']],
                ['title' => 'Module Permissions',       'route' => 'director-ict.php?tab=erp',              'roles' => ['director','ict','it']],
                ['title' => 'System Config',            'route' => 'director-ict.php?tab=erp',              'roles' => ['director','ict','it','system admin']],
                // ── Website & Portal ──
                ['title' => 'Website & Portal',          'route' => 'director-ict.php?tab=website',           'roles' => ['director','ict','it']],
                ['title' => 'Website Status',            'route' => 'director-ict.php?tab=website',           'roles' => ['director','ict','it']],
                ['title' => 'News & Updates',            'route' => 'director-ict.php?tab=website',           'roles' => ['director','ict','it']],
                // ── Approvals ──
                ['title' => 'ICT Approvals',             'route' => 'director-ict.php?tab=approvals',         'roles' => ['director','ict','it']],
                ['title' => 'Pending Approvals',         'route' => 'director-ict.php?tab=approvals',         'roles' => ['director','ict','it']],
                // ── Database Management ──
                ['title' => 'Database Info',            'route' => 'director-ict.php?tab=dashboard#db',     'roles' => ['director','ict','it']],
                ['title' => 'Backups & Recovery',       'route' => 'director-ict.php?tab=backups',          'roles' => ['director','ict','it','system admin']],
                ['title' => 'Backup History',           'route' => 'director-ict.php?tab=backups#history',  'roles' => ['director','ict','it']],
                // ── Help Desk ──
                ['title' => 'Help Desk',                'route' => 'director-ict.php?tab=helpdesk',         'roles' => ['director','ict','it']],
                ['title' => 'Support Tickets',          'route' => 'director-ict.php?tab=helpdesk',         'roles' => ['director','ict','it']],
                ['title' => 'Security Logs',            'route' => 'director-ict.php?tab=helpdesk#security','roles' => ['director','ict','it']],
                // ── Cybersecurity ──
                ['title' => 'Security Dashboard',       'route' => 'director-ict.php?tab=security',         'roles' => ['director','ict','it','system admin']],
                ['title' => 'Security Events',          'route' => 'director-ict.php?tab=security',         'roles' => ['director','ict','it']],
                ['title' => 'Failed Logins',            'route' => 'director-ict.php?tab=security#logins',  'roles' => ['director','ict','it']],
                ['title' => 'Audit Trail',              'route' => 'director-ict.php?tab=security#audit',   'roles' => ['director','ict','it']],
                ['title' => 'Active Alerts',            'route' => 'director-ict.php?tab=security#alerts',  'roles' => ['director','ict','it']],
                // ── Monitoring ──
                ['title' => 'System Monitoring',        'route' => 'director-ict.php?tab=monitoring',       'roles' => ['director','ict','it']],
                ['title' => 'System Health',            'route' => 'director-ict.php?tab=monitoring#health','roles' => ['director','ict','it']],
                // ── ICT Reports ──
                ['title' => 'ICT Reports',              'route' => '../ict-policy.php',                     'roles' => ['director','ict','it','principal']],
                // ── Settings ──
                ['title' => 'ICT Settings',             'route' => 'director-ict.php?tab=settings',         'roles' => ['director','ict','it','system admin']],
                ['title' => 'Security Settings',         'route' => 'director-ict.php?tab=settings#security','roles' => ['director','ict','it','system admin']],
                // ── External dashboards (oversight, not daily mgmt) ──
                ['title' => 'Computer Lab (Oversight)', 'route' => '../computer_lab.php',                  'roles' => ['director','ict','it']],
                ['title' => 'Cybersecurity',            'route' => '../cybersecurity.php',                  'roles' => ['director','ict','it']],
                ['title' => 'ICT Policy',               'route' => '../ict-policy.php',                    'roles' => ['director','ict','it']],
                ['title' => 'System Administration',    'route' => '../system-admin.php',                  'roles' => ['director','ict','it','system admin']],
            ],
        ],
        [
            'title'    => 'Computer Department',
            'icon'     => 'fas fa-desktop',
            'roles'    => ['lab','lab manager','computer lab','ict','it','director'],
            'children' => [
                // ── A. Dashboard ──
                ['title' => 'Lab Manager Dashboard',   'route' => 'computer_lab.php',                    'roles' => ['lab','lab manager','computer lab','ict','it','director']],
                ['title' => 'Today\'s Statistics',      'route' => 'computer_lab.php?tab=dashboard',       'roles' => ['lab','lab manager','computer lab','ict','it','director']],
                // ── B. Student ICT Services ──
                ['title' => 'Student ID Cards',         'route' => 'computer_lab.php?tab=id-cards',        'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Generate ID Card',         'route' => 'computer_lab.php?tab=id-cards&section=generate', 'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Verify ID Card',           'route' => 'computer_lab.php?tab=id-cards&section=verify',   'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Bulk ID Printing',         'route' => 'computer_lab.php?tab=id-cards&section=bulk',     'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'ID Card Replacements',     'route' => 'computer_lab.php?tab=id-cards&section=replace',  'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── C. Computer Laboratory ──
                ['title' => 'Computers',                'route' => 'computer_lab.php?tab=computers',        'roles' => ['lab','lab manager','computer lab','ict','it','director']],
                ['title' => 'Practical Sessions',       'route' => 'computer_lab.php?tab=sessions',         'roles' => ['lab','lab manager','computer lab','ict','it','lecturer']],
                ['title' => 'Lab Attendance',           'route' => 'computer_lab.php?tab=attendance',       'roles' => ['lab','lab manager','computer lab','ict','it','lecturer']],
                ['title' => 'Equipment',                'route' => 'computer_lab.php?tab=equipment',        'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Lab Rooms',                'route' => 'computer_lab.php?tab=settings#rooms',   'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── D. Printing Centre ──
                ['title' => 'Printing Jobs',            'route' => 'computer_lab.php?tab=printing',         'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Printing Charges',         'route' => 'computer_lab.php?tab=printing#charges', 'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Print History',            'route' => 'computer_lab.php?tab=printing#history', 'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── E. Technical Support ──
                ['title' => 'Technical Support',        'route' => 'computer_lab.php?tab=support',          'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Repair Requests',          'route' => 'computer_lab.php?tab=support#repairs',  'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Maintenance Logs',         'route' => 'computer_lab.php?tab=computers#maint',  'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── F. Software Management ──
                ['title' => 'Software Inventory',       'route' => 'computer_lab.php?tab=software',         'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Installations',            'route' => 'computer_lab.php?tab=software#install', 'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── G. Inventory & Consumables ──
                ['title' => 'Consumables Stock',        'route' => 'computer_lab.php?tab=inventory',        'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Equipment Inventory',      'route' => 'computer_lab.php?tab=equipment',        'roles' => ['lab','lab manager','computer lab','ict','it']],
                // ── H. Reports ──
                ['title' => 'Lab Reports',              'route' => 'computer_lab.php?tab=reports',          'roles' => ['lab','lab manager','computer lab','ict','it','director','principal']],
                // ── I. Settings ──
                ['title' => 'Lab Settings',             'route' => 'computer_lab.php?tab=settings',         'roles' => ['lab','lab manager','computer lab','ict','it']],
                ['title' => 'Printing Charges Config',  'route' => 'computer_lab.php?tab=settings#print',   'roles' => ['lab','lab manager','computer lab','ict','it']],
            ],
        ],
        [
            'title'    => 'Library',
            'icon'     => 'fas fa-book',
            'roles'    => ['librarian','director','student','lecturer'],
            'children' => [
                ['title' => 'Librarian Dashboard',   'route' => 'school-librarian.php',               'roles' => ['librarian','director']],
                ['title' => 'Books Management',      'route' => 'school-librarian.php#books',         'roles' => ['librarian','director']],
                ['title' => 'Borrowing & Circulation','route' => 'school-librarian.php#circulation',  'roles' => ['librarian','director']],
                ['title' => 'Library Members',        'route' => 'school-librarian.php#members',       'roles' => ['librarian','director']],
                ['title' => 'Book Acquisition',       'route' => 'school-librarian.php#acquisition',   'roles' => ['librarian','director']],
                ['title' => 'Library Books',         'route' => 'student-library.php?view=books',     'roles' => ['librarian','student','lecturer']],
                ['title' => 'Borrowing & Returns',   'route' => 'student-library.php?view=borrowing', 'roles' => ['librarian','student','lecturer']],
                ['title' => 'Library Fines',         'route' => 'student-library.php?view=fines',     'roles' => ['librarian']],
                ['title' => 'Digital Resources',     'route' => 'student-library.php?view=digital',   'roles' => ['librarian','lecturer']],
            ],
        ],
        [
            'title'    => 'Skills Laboratory',
            'icon'     => 'fas fa-flask',
            'roles'    => ['skills lab','lab','lecturer','head','nursing','midwifery'],
            'children' => [
                ['title' => 'Skills Lab Manager',    'route' => 'skills-lab.php',                     'roles' => ['skills lab','lab','director','principal']],
                ['title' => 'Lab Equipment',          'route' => 'skills-lab.php?view=equipment',     'roles' => ['skills lab','lab','lecturer','head']],
                ['title' => 'Practical Sessions',     'route' => 'skills-lab.php?view=sessions',      'roles' => ['skills lab','lab','lecturer','head']],
                ['title' => 'Lab Experiments',        'route' => 'lab-practical.php',                 'roles' => ['lecturer','head','nursing','midwifery','lab']],
                ['title' => 'Lab Safety',             'route' => 'skills-lab.php?view=safety',        'roles' => ['skills lab','lab','lecturer']],
                ['title' => 'Chemical Inventory',    'route' => 'chemical-inventory.php',             'roles' => ['lab','lecturer','head','nursing','skills lab']],
            ],
        ],
        [
            'title'    => 'Store & Assets',
            'icon'     => 'fas fa-boxes',
            'roles'    => ['storekeeper','store','inventory','director','finance','bursar'],
            'children' => [
                ['title' => 'Storekeeper',           'route' => 'storekeeper.php',                    'roles' => ['storekeeper','store','inventory']],
                ['title' => 'Inventory Reports',     'route' => 'inventory-reports.php',              'roles' => ['director','principal','hr','store','storekeeper']],
                ['title' => 'Asset Management',      'route' => 'asset-management.php',               'roles' => ['store','storekeeper','director','manager']],
                ['title' => 'Procurement Oversight', 'route' => 'procurement-oversight.php',          'roles' => ['director','finance','bursar','store','storekeeper']],
                ['title' => 'Department Requests',   'route' => 'department-requests.php',            'roles' => ['store','storekeeper','director','manager','head']],
            ],
        ],
        [
            'title'    => 'Security & Transport',
            'icon'     => 'fas fa-shield-alt',
            'roles'    => ['security','driver','director','principal'],
            'children' => [
                ['title' => 'Security',              'route' => 'security.php',                       'roles' => ['security','director']],
                ['title' => 'Visitor Access',         'route' => 'visitor-access.php',                'roles' => ['security','director','manager']],
                ['title' => 'Fleet Management',       'route' => 'drivers.php',                       'roles' => ['driver','director']],
                ['title' => 'Fuel & Trip Logs',       'route' => 'fuel-trips.php',                    'roles' => ['driver','director','manager']],
            ],
        ],
        [
            'title'    => 'Student Services',
            'icon'     => 'fas fa-hand-holding-heart',
            'roles'    => ['matron','warden','sickbay','nursing','director','principal','deputy','secretary','registrar','head','lecturer','bursar','finance'],
            'children' => [
                ['title' => 'Matrons',               'route' => 'matrons.php',                        'roles' => ['matron','warden']],
                ['title' => 'Welfare (Matron)',      'route' => 'matrons.php#students',               'roles' => ['matron','warden']],
                ['title' => 'Counseling (Matron)',   'route' => 'matrons.php#counseling',             'roles' => ['matron','warden']],
                ['title' => 'Health Services',       'route' => 'matrons.php#health',                 'roles' => ['matron','warden','nursing']],
                ['title' => 'Accommodation (Matron)','route' => 'matrons.php#accommodation',          'roles' => ['matron','warden']],
                ['title' => 'Discipline (Matron)',   'route' => 'matrons.php#discipline',             'roles' => ['matron','warden']],
                ['title' => 'Wardens',               'route' => 'wardens.php',                        'roles' => ['warden','matron']],
                ['title' => 'Welfare (Warden)',      'route' => 'wardens.php#students',               'roles' => ['warden','matron']],
                ['title' => 'Counseling (Warden)',   'route' => 'wardens.php#counseling',             'roles' => ['warden','matron']],
                ['title' => 'Discipline (Warden)',   'route' => 'wardens.php#discipline',             'roles' => ['warden','matron']],
                ['title' => 'Accommodation (Warden)','route' => 'wardens.php#accommodation',          'roles' => ['warden','matron']],
                ['title' => 'Security Oversight',     'route' => 'wardens.php#security',               'roles' => ['warden','matron','security']],
                ['title' => 'Sickbay',               'route' => 'sickbay.php',                        'roles' => ['sickbay','matron','nursing']],
                ['title' => 'Counseling & Welfare',  'route' => 'counseling-welfare.php',              'roles' => ['director','principal','deputy','matron','warden','secretary']],
                ['title' => 'Hostel Management',     'route' => 'hostel-management.php',              'roles' => ['warden','matron','registrar']],
                ['title' => 'Meal & Accommodation',  'route' => 'meal-accommodation.php',             'roles' => ['matron','warden','registrar','director']],
                ['title' => 'Student Discipline',    'route' => 'student-discipline.php',             'roles' => ['head','principal','deputy','secretary','matron','warden']],
                ['title' => 'Student Requests Desk', 'route' => 'student-requests-desk.php',          'roles' => ['secretary','registrar','director','principal','deputy']],
                ['title' => 'Scholarships & Sponsorships','route' => 'scholarships-sponsorships.php', 'roles' => ['bursar','finance','director','registrar','secretary']],
                ['title' => 'Student Announcements', 'route' => 'student-announcements.php',          'roles' => '*'],
                ['title' => 'Student Management',   'route' => 'student-management.php',             'roles' => ['registrar','secretary','director','principal','deputy','head','lecturer']],
                ['title' => 'Student Attendance',   'route' => 'student-attendance.php',             'roles' => ['registrar','secretary','lecturer','head']],
                ['title' => 'Add Student',           'route' => 'student-add.php',                    'roles' => ['registrar','secretary','admissions']],
            ],
        ],
        [
            'title'    => 'Communications',
            'icon'     => 'fas fa-bullhorn',
            'roles'    => ['director','secretary','ict','it','principal'],
            'children' => [
                ['title' => 'Communications Hub',   'route' => 'communications.php',                 'roles' => ['director','secretary','ict','it']],
                ['title' => 'News Management',       'route' => '../news.php',                        'roles' => '*'],
                ['title' => 'Announcements',         'route' => 'student-announcements.php',          'roles' => '*'],
                ['title' => 'SMS / Email',           'route' => '../messaging.php',                   'roles' => '*'],
                ['title' => 'Notifications',          'route' => '../notifications.php',              'roles' => '*'],
                ['title' => 'Website Pages',         'route' => 'website-pages.php',                  'roles' => ['director','ict','it','secretary']],
                ['title' => 'Portal Messages',        'route' => 'portal-messages.php',               'roles' => ['director','secretary','ict','it']],
                ['title' => 'Contact Submissions',    'route' => 'contact-submissions.php',           'roles' => ['director','secretary','ict','it']],
                ['title' => 'Institutional Alerts',  'route' => 'institutional-alerts.php',           'roles' => ['director','secretary','ict','principal']],
                ['title' => 'Volunteer Applications','route' => 'volunteer-applications.php',          'roles' => ['director','secretary','hr']],
            ],
        ],
        [
            'title'    => 'Documents & Printing',
            'icon'     => 'fas fa-print',
            'roles'    => '*',
            'children' => [
                ['title' => 'Certificates',          'route' => '../print_certificate.php',           'roles' => '*'],
                ['title' => 'Transcripts',           'route' => '../print_transcript.php',            'roles' => '*'],
                ['title' => 'Transcript Generation (Staff)','route' => 'staff_transcript_generation.php', 'roles' => ['registrar','director','academics','principal']],
                ['title' => 'Receipt Printing',      'route' => 'staff_receipt_printing.php',         'roles' => '*'],
                ['title' => 'Document Templates',    'route' => 'document_management.php',            'roles' => '*'],
                ['title' => 'Student Downloads',     'route' => 'student-downloads.php',              'roles' => ['director','secretary','registrar','ict']],
                ['title' => 'Recycle Bin',           'route' => 'recycle_bin.php',                    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Student Self-Service',
            'icon'     => 'fas fa-user-circle',
            'roles'    => ['student'],
            'children' => [
                ['title' => 'My Profile',            'route' => 'student.php',                        'roles' => ['student']],
                ['title' => 'My Fees',               'route' => '../student-fees.php',                'roles' => ['student']],
                ['title' => 'Fee Statement',         'route' => '../student-fees.php?view=statement', 'roles' => ['student']],
                ['title' => 'Pay Fees Online',       'route' => '../student-fees.php?view=pay',       'roles' => ['student']],
                ['title' => 'Download Receipts',     'route' => '../student-fees.php?view=receipts',  'roles' => ['student']],
                ['title' => 'My Results',            'route' => '../student-results.php',             'roles' => ['student']],
                ['title' => 'My Timetable',          'route' => '../student-timetable.php',           'roles' => ['student']],
                ['title' => 'Course Registration',   'route' => '../student-course-reg.php',          'roles' => ['student']],
                ['title' => 'Library Portal',        'route' => '../student-library-portal.php',      'roles' => ['student']],
            ],
        ],
        [
            'title'    => 'Student Government',
            'icon'     => 'fas fa-handshake',
            'roles'    => ['guild president','student'],
            'children' => [
                ['title' => 'Guild President',       'route' => 'guild-president.php',                'roles' => ['guild president','student']],
            ],
        ],
        [
            'title'    => 'Approvals & Workflow',
            'icon'     => 'fas fa-check-double',
            'roles'    => ['director general','ceo','director'],
            'children' => [
                ['title' => 'Pending Approvals',     'route' => 'director-general.php#approvals',     'roles' => ['director general','ceo','director']],
                ['title' => 'Finance Approvals',     'route' => 'director-finance.php#approvals',     'roles' => ['director general','finance','director']],
                ['title' => 'Academic Approvals',    'route' => 'director-academics.php#approvals',   'roles' => ['director general','academics','director']],
                ['title' => 'Admission Approvals',   'route' => 'director-admissions.php#approvals',  'roles' => ['director general','admissions','director']],
                ['title' => 'System Approvals',      'route' => 'director-ict.php#approvals',         'roles' => ['director general','ict','director']],
            ],
        ],
        [
            'title'    => 'Settings',
            'icon'     => 'fas fa-cog',
            'roles'    => '*',
            'children' => [
                ['title' => 'My Profile',            'route' => 'staff_profile_management.php',       'roles' => '*'],
                ['title' => 'System Settings',       'route' => 'system-admin.php',                   'roles' => ['director','ict','it','system admin']],
                ['title' => 'Backup & Restore',      'route' => 'system-admin.php?view=backup',       'roles' => ['director','ict','it','system admin']],
                ['title' => 'Audit Trail',           'route' => 'director-general.php#audit',         'roles' => ['director general','ceo','finance','director']],
            ],
        ],

        // ── School Secretary Module Groups ──
        [
            'title'    => 'Secretary Dashboard',
            'icon'     => 'fas fa-chart-pie',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Dashboard Home',     'route' => 'school-secretary.php?section=home',              'roles' => '*'],
                ['title' => 'Admin Overview',     'route' => 'school-secretary.php?section=home',              'roles' => '*'],
                ['title' => 'Daily Activities',   'route' => 'school-secretary.php?section=home',              'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Communications',
            'icon'     => 'fas fa-comments',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Internal Messages',  'route' => 'school-secretary.php?section=comms',            'roles' => '*'],
                ['title' => 'Staff Directory',    'route' => 'school-secretary.php?section=staff_search',     'roles' => '*'],
                ['title' => 'Student Directory',  'route' => 'school-secretary.php?section=student_search',   'roles' => '*'],
                ['title' => 'Announcements',      'route' => 'school-secretary.php?section=announcements',    'roles' => '*'],
                ['title' => 'Circulars',          'route' => 'school-secretary.php?section=circulars',         'roles' => '*'],
                ['title' => 'Notices',            'route' => 'school-secretary.php?section=notices',           'roles' => '*'],
                ['title' => 'Communication Logs', 'route' => 'school-secretary.php?section=comm_logs',        'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Correspondence',
            'icon'     => 'fas fa-envelope-open-text',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Incoming Mail',      'route' => 'school-secretary.php?section=incoming_mail',    'roles' => '*'],
                ['title' => 'Outgoing Mail',      'route' => 'school-secretary.php?section=outgoing_mail',    'roles' => '*'],
                ['title' => 'Official Letters',   'route' => 'school-secretary.php?section=letters',           'roles' => '*'],
                ['title' => 'Document Tracking',  'route' => 'school-secretary.php?section=document_tracking','roles' => '*'],
                ['title' => 'Archive',            'route' => 'school-secretary.php?section=correspondence_archive','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Meetings',
            'icon'     => 'fas fa-handshake',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Meeting Schedule',   'route' => 'school-secretary.php?section=meetings',         'roles' => '*'],
                ['title' => 'Meeting Requests',   'route' => 'school-secretary.php?section=meeting_requests', 'roles' => '*'],
                ['title' => 'Meeting Minutes',    'route' => 'school-secretary.php?section=meetings&tab=minutes','roles' => '*'],
                ['title' => 'Executive Meetings', 'route' => 'school-secretary.php?section=exec_meetings',    'roles' => '*'],
                ['title' => 'Dept Meetings',      'route' => 'school-secretary.php?section=dept_meetings',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Appointments',
            'icon'     => 'fas fa-calendar-check',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Appointment Requests','route' => 'school-secretary.php?section=appointments',    'roles' => '*'],
                ['title' => 'Visitor Management',  'route' => 'school-secretary.php?section=visitor_mgmt',    'roles' => '*'],
                ['title' => 'Office Calendar',     'route' => 'school-secretary.php?section=calendar',        'roles' => '*'],
                ['title' => 'Executive Appts',     'route' => 'school-secretary.php?section=exec_appointments','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Document Mgmt',
            'icon'     => 'fas fa-folder',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Document Registry',  'route' => 'school-secretary.php?section=documents',        'roles' => '*'],
                ['title' => 'Document Filing',    'route' => 'school-secretary.php?section=doc_filing',       'roles' => '*'],
                ['title' => 'Scanned Documents',  'route' => 'school-secretary.php?section=scanned_docs',     'roles' => '*'],
                ['title' => 'Templates',          'route' => 'school-secretary.php?section=templates',        'roles' => '*'],
                ['title' => 'Archives',           'route' => 'school-secretary.php?section=archives',         'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Request Mgmt',
            'icon'     => 'fas fa-clipboard-list',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Pending Requests',   'route' => 'school-secretary.php?section=requests',         'roles' => '*'],
                ['title' => 'Assigned Requests',  'route' => 'school-secretary.php?section=assigned_requests','roles' => '*'],
                ['title' => 'Follow-Ups',         'route' => 'school-secretary.php?section=follow_ups',       'roles' => '*'],
                ['title' => 'Request Tracking',   'route' => 'school-secretary.php?section=request_tracking', 'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Records',
            'icon'     => 'fas fa-database',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Student Lookup',     'route' => 'school-secretary.php?section=student_search',   'roles' => '*'],
                ['title' => 'Staff Lookup',       'route' => 'school-secretary.php?section=staff_search',     'roles' => '*'],
                ['title' => 'Contact Directory',  'route' => 'school-secretary.php?section=contact_directory','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Reports',
            'icon'     => 'fas fa-chart-bar',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Communication',     'route' => 'school-secretary.php?section=reports&type=communications','roles' => '*'],
                ['title' => 'Appointments',      'route' => 'school-secretary.php?section=reports&type=appointments','roles' => '*'],
                ['title' => 'Documents',         'route' => 'school-secretary.php?section=reports&type=documents','roles' => '*'],
                ['title' => 'Activity',          'route' => 'school-secretary.php?section=reports&type=activity','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Sec Admissions',
            'icon'     => 'fas fa-user-graduate',
            'roles'    => ['secretary','school secretary'],
            'children' => [
                ['title' => 'Applicant Search',     'route' => 'school-secretary.php?section=admissions',       'roles' => '*'],
                ['title' => 'Admission Letters',     'route' => 'school-secretary.php?section=admissions&view=letters', 'roles' => '*'],
                ['title' => 'Admission Reports',     'route' => 'school-secretary.php?section=admissions&view=reports', 'roles' => '*'],
            ],
        ],

        // ── Director Finance Module Groups ──
        [
            'title'    => 'Revenue Management',
            'icon'     => 'fas fa-chart-line',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Revenue Summary',      'route' => 'director-finance.php?section=revenue_summary',  'roles' => '*'],
                ['title' => 'Revenue Sources',      'route' => 'director-finance.php?section=revenue_sources',  'roles' => '*'],
                ['title' => 'Revenue Trends',       'route' => 'director-finance.php?section=revenue_trends',   'roles' => '*'],
                ['title' => 'Revenue Analysis',     'route' => 'director-finance.php?section=revenue_analysis',  'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Fee Oversight',
            'icon'     => 'fas fa-money-check-alt',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Fee Performance',      'route' => 'director-finance.php?section=fee_performance',    'roles' => '*'],
                ['title' => 'Fee Collection',       'route' => 'director-finance.php?section=fee_collection',     'roles' => '*'],
                ['title' => 'Outstanding Balances', 'route' => 'director-finance.php?section=outstanding_balances','roles' => '*'],
                ['title' => 'Clearance Monitoring', 'route' => 'director-finance.php?section=clearance_monitoring','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Payment Oversight',
            'icon'     => 'fas fa-hand-holding-usd',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Payment Verification', 'route' => 'director-finance.php?section=payment_verification',  'roles' => '*'],
                ['title' => 'High Value Payments',  'route' => 'director-finance.php?section=high_value',            'roles' => '*'],
                ['title' => 'Suspicious Payments',  'route' => 'director-finance.php?section=suspicious',            'roles' => '*'],
                ['title' => 'Failed Payments',      'route' => 'director-finance.php?section=failed_payments',       'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Budget Management',
            'icon'     => 'fas fa-wallet',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Budget Planning',      'route' => 'director-finance.php?section=budget_planning',       'roles' => '*'],
                ['title' => 'Budget Monitoring',    'route' => 'director-finance.php?section=budget_monitoring',     'roles' => '*'],
                ['title' => 'Budget Variance',      'route' => 'director-finance.php?section=budget_variance',       'roles' => '*'],
                ['title' => 'Budget Approvals',     'route' => 'director-finance.php?section=budget_approvals',      'roles' => '*'],
                ['title' => 'Budget Adjustments',   'route' => 'director-finance.php?section=budget_adjustments',    'roles' => '*'],
                ['title' => 'Budget Requests',      'route' => 'director-finance.php?section=budget_requests',       'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Expenditure Control',
            'icon'     => 'fas fa-file-invoice-dollar',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Expenditure Monitor',  'route' => 'director-finance.php?section=expenditure_monitoring',  'roles' => '*'],
                ['title' => 'Department Spending',  'route' => 'director-finance.php?section=dept_expenditures',       'roles' => '*'],
                ['title' => 'Operating Expenses',   'route' => 'director-finance.php?section=operating_expenses',      'roles' => '*'],
                ['title' => 'Capital Expenses',     'route' => 'director-finance.php?section=capital_expenses',        'roles' => '*'],
                ['title' => 'Expenditure Approvals','route' => 'director-finance.php?section=expenditure_approvals',   'roles' => '*'],
                ['title' => 'Procurement Finance',  'route' => 'director-finance.php?section=procurement_finance',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Payroll Oversight',
            'icon'     => 'fas fa-money-check',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Payroll Review',       'route' => 'director-finance.php?section=payroll_review',        'roles' => '*'],
                ['title' => 'Salary Analysis',      'route' => 'director-finance.php?section=salary_analysis',       'roles' => '*'],
                ['title' => 'Allowance Monitor',    'route' => 'director-finance.php?section=allowance_monitoring',   'roles' => '*'],
                ['title' => 'Deduction Monitor',    'route' => 'director-finance.php?section=deduction_monitoring',   'roles' => '*'],
                ['title' => 'Payroll Approvals',    'route' => 'director-finance.php?section=payroll_approvals',      'roles' => '*'],
                ['title' => 'Payroll History',      'route' => 'director-finance.php?section=payroll_history',        'roles' => '*'],
                ['title' => 'Payroll Audit',        'route' => 'director-finance.php?section=payroll_audit',          'roles' => '*'],
                ['title' => 'Staff Cost Analytics', 'route' => 'director-finance.php?section=staff_cost_analytics',   'roles' => '*'],
                ['title' => 'Payroll Forecasting',  'route' => 'director-finance.php?section=payroll_forecasting',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Accounting & Ledger',
            'icon'     => 'fas fa-book',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'General Ledger',       'route' => 'director-finance.php?section=general_ledger',        'roles' => '*'],
                ['title' => 'Ledger Review',        'route' => 'director-finance.php?section=ledger_review',         'roles' => '*'],
                ['title' => 'Income Statement',     'route' => 'director-finance.php?section=income_statement',       'roles' => '*'],
                ['title' => 'Balance Sheet',        'route' => 'director-finance.php?section=balance_sheet',          'roles' => '*'],
                ['title' => 'Cash Flow',            'route' => 'director-finance.php?section=cash_flow',              'roles' => '*'],
                ['title' => 'Bank Reconciliation',  'route' => 'director-finance.php?section=bank_reconciliation',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Audit & Compliance',
            'icon'     => 'fas fa-clipboard-check',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Audit Logs',           'route' => 'director-finance.php?section=audit_logs',            'roles' => '*'],
                ['title' => 'Audit Findings',       'route' => 'director-finance.php?section=audit_findings',        'roles' => '*'],
                ['title' => 'Audit Reviews',        'route' => 'director-finance.php?section=audit_reviews',         'roles' => '*'],
                ['title' => 'Compliance Overview',  'route' => 'director-finance.php?section=compliance_overview',   'roles' => '*'],
                ['title' => 'URA Compliance',       'route' => 'director-finance.php?section=ura_compliance',        'roles' => '*'],
                ['title' => 'Regulatory Compliance','route' => 'director-finance.php?section=regulatory_compliance', 'roles' => '*'],
                ['title' => 'Risk Register',        'route' => 'director-finance.php?section=risk_register',         'roles' => '*'],
                ['title' => 'Compliance Alerts',    'route' => 'director-finance.php?section=compliance_alerts',     'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Procurement Finance',
            'icon'     => 'fas fa-shopping-cart',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Purchase Requests',    'route' => 'director-finance.php?section=purchase_requests',     'roles' => '*'],
                ['title' => 'Quotations',           'route' => 'director-finance.php?section=quotations',            'roles' => '*'],
                ['title' => 'Supplier Evaluation',  'route' => 'director-finance.php?section=supplier_evaluation',   'roles' => '*'],
                ['title' => 'Supplier Payments',    'route' => 'director-finance.php?section=supplier_payments',     'roles' => '*'],
                ['title' => 'Supplier Performance', 'route' => 'director-finance.php?section=supplier_performance',   'roles' => '*'],
                ['title' => 'Supplier Balances',    'route' => 'director-finance.php?section=outstanding_supplier_balances','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Assets & Investments',
            'icon'     => 'fas fa-building',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Asset Register',       'route' => 'director-finance.php?section=asset_register',         'roles' => '*'],
                ['title' => 'Asset Valuation',      'route' => 'director-finance.php?section=asset_valuation',        'roles' => '*'],
                ['title' => 'Depreciation Tracking', 'route' => 'director-finance.php?section=depreciation_tracking',  'roles' => '*'],
                ['title' => 'Capital Projects',     'route' => 'director-finance.php?section=capital_projects',       'roles' => '*'],
                ['title' => 'Project Performance',  'route' => 'director-finance.php?section=project_performance',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Financial Communications',
            'icon'     => 'fas fa-envelope',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Finance Messages',     'route' => 'director-finance.php?section=finance_messages',        'roles' => '*'],
                ['title' => 'Finance Notices',      'route' => 'director-finance.php?section=finance_notices',         'roles' => '*'],
                ['title' => 'Approval Requests',    'route' => 'director-finance.php?section=approval_requests',        'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Reports & Analytics',
            'icon'     => 'fas fa-file-alt',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Financial Reports',    'route' => 'director-finance.php?section=finance_reports',          'roles' => '*'],
                ['title' => 'Executive Summary',   'route' => 'director-finance.php?section=finance_reports&type=executive','roles' => '*'],
                ['title' => 'Trend Analysis',       'route' => 'director-finance.php?section=finance_reports&type=trends','roles' => '*'],
                ['title' => 'Export Reports',       'route' => 'director-finance.php?section=finance_reports&type=export','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Approval Center',
            'icon'     => 'fas fa-check-double',
            'roles'    => ['director','finance','ceo'],
            'children' => [
                ['title' => 'Pending Approvals',    'route' => 'director-finance.php?section=approval_center',          'roles' => '*'],
                ['title' => 'Approval History',     'route' => 'director-finance.php?section=approval_center&tab=history','roles' => '*'],
                ['title' => 'Approval Workflow',    'route' => 'director-finance.php?section=approval_center&tab=workflow','roles' => '*'],
            ],
        ],

        // ── Principal Module Groups ──
        [
            'title'    => 'Academic Oversight',
            'icon'     => 'fas fa-graduation-cap',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Academic Dashboard',   'route' => 'school-principal.php?section=academic_dashboard', 'roles' => '*'],
                ['title' => 'Program Performance',  'route' => 'school-principal.php?section=program_performance', 'roles' => '*'],
                ['title' => 'Exam Monitoring',      'route' => 'school-principal.php?section=exam_monitoring',    'roles' => '*'],
                ['title' => 'Result Approvals',     'route' => 'school-principal.php?section=result_approvals',   'roles' => '*'],
                ['title' => 'Clinical Training',    'route' => 'school-principal.php?section=clinical_training',  'roles' => '*'],
                ['title' => 'Academic Quality',     'route' => 'school-principal.php?section=academic_quality',   'roles' => '*'],
                ['title' => 'Graduation Readiness', 'route' => 'school-principal.php?section=graduation_readiness','roles' => '*'],
                ['title' => 'Academic Compliance',  'route' => 'school-principal.php?section=academic_compliance','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Student Affairs',
            'icon'     => 'fas fa-user-graduate',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Student Management',   'route' => 'school-principal.php?section=student_management',  'roles' => '*'],
                ['title' => 'Student Welfare',      'route' => 'school-principal.php?section=student_welfare',     'roles' => '*'],
                ['title' => 'Discipline Oversight', 'route' => 'school-principal.php?section=discipline_oversight','roles' => '*'],
                ['title' => 'Student Appeals',      'route' => 'school-principal.php?section=student_appeals',     'roles' => '*'],
                ['title' => 'Student Progress',     'route' => 'school-principal.php?section=student_progress',    'roles' => '*'],
                ['title' => 'Student Risk Monitor', 'route' => 'school-principal.php?section=student_risk',        'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Staff Oversight',
            'icon'     => 'fas fa-users',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Staff Overview',       'route' => 'school-principal.php?section=staff_overview',          'roles' => '*'],
                ['title' => 'Dept Performance',     'route' => 'school-principal.php?section=department_performance',  'roles' => '*'],
                ['title' => 'Staff Attendance',     'route' => 'school-principal.php?section=staff_attendance',        'roles' => '*'],
                ['title' => 'Staff Appraisals',     'route' => 'school-principal.php?section=staff_appraisals',        'roles' => '*'],
                ['title' => 'Staff Development',    'route' => 'school-principal.php?section=staff_development',       'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Institutional Operations',
            'icon'     => 'fas fa-building',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Strategic Plans',      'route' => 'school-principal.php?section=strategic_plans',         'roles' => '*'],
                ['title' => 'Institutional KPIs',   'route' => 'school-principal.php?section=institutional_kpis',      'roles' => '*'],
                ['title' => 'Quality Assurance',    'route' => 'school-principal.php?section=quality_assurance',        'roles' => '*'],
                ['title' => 'Accreditation',        'route' => 'school-principal.php?section=accreditation_readiness',  'roles' => '*'],
                ['title' => 'Compliance Monitor',   'route' => 'school-principal.php?section=compliance_monitoring',    'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Meetings & Governance',
            'icon'     => 'fas fa-handshake',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Meetings',             'route' => 'school-principal.php?section=meetings',                'roles' => '*'],
                ['title' => 'Executive Meetings',   'route' => 'school-principal.php?section=executive_meetings',       'roles' => '*'],
                ['title' => 'Academic Board',       'route' => 'school-principal.php?section=academic_board',           'roles' => '*'],
                ['title' => 'Committee Meetings',   'route' => 'school-principal.php?section=committee_meetings',       'roles' => '*'],
                ['title' => 'Action Tracking',      'route' => 'school-principal.php?section=action_tracking',          'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Principal Approvals',
            'icon'     => 'fas fa-check-double',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Approval Center',      'route' => 'school-principal.php?section=approval_center',         'roles' => '*'],
                ['title' => 'Graduation Approvals', 'route' => 'school-principal.php?section=graduation_readiness',     'roles' => '*'],
                ['title' => 'Student Appeals',      'route' => 'school-principal.php?section=student_appeals',          'roles' => '*'],
                ['title' => 'Recommendations',      'route' => 'school-principal.php?section=approval_center&tab=recommendations','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Principal Reports',
            'icon'     => 'fas fa-file-alt',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Academic Reports',     'route' => 'school-principal.php?section=academic_reports',        'roles' => '*'],
                ['title' => 'Student Reports',      'route' => 'school-principal.php?section=student_reports',         'roles' => '*'],
                ['title' => 'Institutional Reports','route' => 'school-principal.php?section=institutional_reports',   'roles' => '*'],
                ['title' => 'QA Reports',           'route' => 'school-principal.php?section=qa_reports',               'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Principal Communications',
            'icon'     => 'fas fa-envelope',
            'roles'    => ['school principal','principal'],
            'children' => [
                ['title' => 'Messages',             'route' => 'school-principal.php?section=communications',           'roles' => '*'],
                ['title' => 'Notices',              'route' => 'school-principal.php?section=notices',                  'roles' => '*'],
                ['title' => 'Announcements',        'route' => 'school-principal.php?section=announcements',           'roles' => '*'],
                ['title' => 'Meeting Invitations',  'route' => 'school-principal.php?section=meeting_invitations',     'roles' => '*'],
            ],
        ],

        // ── Deputy Principal Module Groups ──
        [
            'title'    => 'Academic Support',
            'icon'     => 'fas fa-book-open',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Academic Monitoring',  'route' => 'deputy-principal.php?section=academic_monitoring',        'roles' => '*'],
                ['title' => 'Class Monitoring',     'route' => 'deputy-principal.php?section=class_monitoring',           'roles' => '*'],
                ['title' => 'Timetable Oversight',  'route' => 'deputy-principal.php?section=timetable_oversight',        'roles' => '*'],
                ['title' => 'Attendance Monitor',   'route' => 'deputy-principal.php?section=attendance_monitoring',     'roles' => '*'],
                ['title' => 'Clinical Placement',   'route' => 'deputy-principal.php?section=clinical_placement_monitoring','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Student Support',
            'icon'     => 'fas fa-hands-helping',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Student Welfare',      'route' => 'deputy-principal.php?section=student_welfare',           'roles' => '*'],
                ['title' => 'Student Discipline',   'route' => 'deputy-principal.php?section=student_discipline',        'roles' => '*'],
                ['title' => 'Support Cases',        'route' => 'deputy-principal.php?section=student_support',           'roles' => '*'],
                ['title' => 'Appeals Tracking',     'route' => 'deputy-principal.php?section=student_appeals_tracking',  'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Operational Monitoring',
            'icon'     => 'fas fa-clipboard-check',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Dept Follow-Ups',      'route' => 'deputy-principal.php?section=department_followups',      'roles' => '*'],
                ['title' => 'Compliance Tracking',  'route' => 'deputy-principal.php?section=compliance_tracking',       'roles' => '*'],
                ['title' => 'Institutional Activities','route' => 'deputy-principal.php?section=institutional_activities','roles' => '*'],
                ['title' => 'Task Monitoring',      'route' => 'deputy-principal.php?section=task_monitoring',            'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Deputy Approvals',
            'icon'     => 'fas fa-file-signature',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Review & Forward',     'route' => 'deputy-principal.php?section=approvals',                 'roles' => '*'],
                ['title' => 'Recommendations',      'route' => 'deputy-principal.php?section=approvals&tab=recommend','roles' => '*'],
            ],
        ],
        [
            'title'    => 'Deputy Reports',
            'icon'     => 'fas fa-chart-bar',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Monitoring Reports',   'route' => 'deputy-principal.php?section=monitoring_reports',        'roles' => '*'],
                ['title' => 'Attendance Reports',   'route' => 'deputy-principal.php?section=attendance_reports',        'roles' => '*'],
                ['title' => 'Welfare Reports',      'route' => 'deputy-principal.php?section=welfare_reports',           'roles' => '*'],
                ['title' => 'Department Reports',   'route' => 'deputy-principal.php?section=department_reports',        'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Deputy QA',
            'icon'     => 'fas fa-star',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Teaching Quality',     'route' => 'deputy-principal.php?section=teaching_quality',          'roles' => '*'],
                ['title' => 'Clinical Training QA', 'route' => 'deputy-principal.php?section=clinical_training_reviews', 'roles' => '*'],
                ['title' => 'Compliance Reviews',   'route' => 'deputy-principal.php?section=compliance_reviews',        'roles' => '*'],
                ['title' => 'Improvement Tracking', 'route' => 'deputy-principal.php?section=improvement_tracking',      'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Deputy Communications',
            'icon'     => 'fas fa-comments',
            'roles'    => ['deputy'],
            'children' => [
                ['title' => 'Communications',       'route' => 'deputy-principal.php?section=communications',            'roles' => '*'],
                ['title' => 'Direct Messages',      'route' => 'deputy-principal.php?section=communications&tab=messages','roles' => '*'],
            ],
        ],
    ];
}
}

if (!function_exists('userCanAccessModule')) {
function userCanAccessModule($moduleRoles, string $userRole): bool {
    if ($moduleRoles === '*' || $moduleRoles === true) return true;
    if (!is_array($moduleRoles)) return false;
    $userRoleLower = strtolower(trim($userRole));
    foreach ($moduleRoles as $keyword) {
        $kw = strtolower(trim($keyword));
        if ($kw === '') continue;
        // Exact match (case-insensitive)
        if ($userRoleLower === $kw) return true;
        // Substring match: keyword must start at a word boundary
        // (position 0 or preceded by a space).
        // We do NOT check what follows — this correctly handles:
        //   'lecturer' → 'Lecturers' (plural)     ✓
        //   'lecturer' → 'Senior Lecturers'       ✓
        //   'director' → 'Director General'       ✓
        //   'nursing'  → 'Head Nursing'           ✓
        //   'lab'      → 'skills lab'             ✓ (only in Skills Lab)
        //   'lab manager' → 'Computer Lab Manager' ✓ (in ICT)
        $pos = strpos($userRoleLower, $kw);
        if ($pos !== false) {
            $before = $pos === 0 ? '' : $userRoleLower[$pos - 1];
            if ($before === '' || $before === ' ') return true;
        }
    }
    return false;
}
}

if (!function_exists('getFilteredModules')) {
function getFilteredModules(string $userRole): array {
    $all = getModuleConfig();
    $filtered = [];
    foreach ($all as $parent) {
        $kids = [];
        foreach ($parent['children'] as $child) {
            if (userCanAccessModule($child['roles'], $userRole)) {
                $kids[] = $child;
            }
        }
        if (!empty($kids)) {
            $filtered[] = [
                'title'    => $parent['title'],
                'icon'     => $parent['icon'],
                'roles'    => $parent['roles'],
                'children' => $kids,
            ];
        }
    }
    return $filtered;
}
}

/**
 * Check whether a role should see a given parent-level sidebar module.
 * Parent roles act as a default allow; individual child roles extend access.
 * This function is retained for legacy checks but no longer gates the sidebar.
 */
if (!function_exists('userCanAccessParentModule')) {
function userCanAccessParentModule($parentRoles, string $userRole): bool {
    return userCanAccessModule($parentRoles, $userRole);
}
}
