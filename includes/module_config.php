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
            'roles'    => ['admissions','director','secretary','registrar'],
            'children' => [
                ['title' => 'Director Admissions',    'route' => 'director-admissions.php',           'roles' => ['admissions','director','secretary']],
                ['title' => 'Applications',          'route' => 'director-admissions.php#applications','roles' => ['admissions','director','secretary']],
                ['title' => 'Requirements Portal',   'route' => 'director-admissions.php#requirements','roles' => ['admissions','director','secretary']],
                ['title' => 'Student Directory',     'route' => 'director-admissions.php#directory',  'roles' => ['admissions','director','secretary']],
                ['title' => 'Admissions Reports',    'route' => 'director-admissions.php#reports',    'roles' => ['admissions','director','secretary']],
                ['title' => 'Student Applications',   'route' => 'admission-letters.php?view=applications','roles' => ['admissions','director','secretary']],
                ['title' => 'Requirements & Clearance','route' => 'admission-letters.php?view=clearance','roles' => ['admissions','director','secretary']],
                ['title' => 'Admission Letters',      'route' => 'admission-letters.php',             'roles' => ['admissions','director','secretary']],
                ['title' => 'Intake Planning',        'route' => 'intake-planning.php',               'roles' => ['admissions','director','secretary','registrar']],
            ],
        ],
        [
            'title'    => 'Academic Registrar',
            'icon'     => 'fas fa-clipboard-list',
            'roles'    => ['registrar','director','principal','deputy'],
            'children' => [
                ['title' => 'Overview',             'route' => 'academic-registrar.php#overview',            'roles' => '*'],
                ['title' => 'Admissions',            'route' => 'academic-registrar.php#admissions',          'roles' => '*'],
                ['title' => 'Student Registration',  'route' => 'academic-registrar.php#course-registration', 'roles' => '*'],
                ['title' => 'Student Records',       'route' => 'academic-registrar.php#student-records',     'roles' => '*'],
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
            'title'    => 'Finance',
            'icon'     => 'fas fa-chart-line',
            'roles'    => ['bursar','director','finance','accountant','ceo','principal'],
            'children' => [
                ['title' => 'Finance Dashboard',     'route' => 'director-finance.php',               'roles' => ['director','finance','ceo']],
                ['title' => 'Revenue Management',    'route' => 'director-finance.php#revenue',        'roles' => ['director','finance','ceo']],
                ['title' => 'Expense Management',    'route' => 'director-finance.php#expenses',       'roles' => ['director','finance','ceo']],
                ['title' => 'Student Fees',          'route' => 'director-finance.php#fees',           'roles' => ['director','finance','ceo']],
                ['title' => 'Payment Management',    'route' => 'director-finance.php#payments',       'roles' => ['director','finance','ceo']],
                ['title' => 'Budget Oversight',      'route' => 'director-finance.php#budget',         'roles' => ['director','finance','ceo']],
                ['title' => 'Payroll Overview',      'route' => 'director-finance.php#payroll',        'roles' => ['director','finance','ceo']],
                ['title' => 'Financial Reports',     'route' => 'director-finance.php#reports',        'roles' => ['director','finance','ceo']],
                ['title' => 'Full Financial Reports','route' => 'financial-reports.php',              'roles' => ['bursar','finance','director','accountant','ceo']],
                ['title' => 'Bursar Dashboard',      'route' => 'school-bursar.php',                  'roles' => ['bursar','finance','accountant']],
                ['title' => 'Fee Structure',         'route' => 'fee-structure.php',                  'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Invoice Generation',    'route' => 'invoice-generation.php',             'roles' => ['bursar','finance','accountant']],
                ['title' => 'Payment Recording',     'route' => 'payment-recording.php',              'roles' => ['bursar','finance','accountant']],
                ['title' => 'Receipt Printing',      'route' => 'staff_receipt_printing.php',         'roles' => '*'],
                ['title' => 'Budget Management',     'route' => 'budget-management.php',              'roles' => ['bursar','finance','director']],
                ['title' => 'Expenditure Tracking',  'route' => 'expenditure-tracking.php',           'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Payroll Management',    'route' => 'bursar-payroll.php',                 'roles' => ['bursar','payroll','finance','director','accountant','hr']],
                ['title' => 'General Ledger',        'route' => 'general-ledger.php',                 'roles' => ['bursar','finance','accountant']],
                ['title' => 'Bank Reconciliation',   'route' => 'bank-reconciliation.php',            'roles' => ['bursar','finance','accountant']],
                ['title' => 'Student Statements',    'route' => 'student-statements.php',             'roles' => ['bursar','finance','accountant']],
                ['title' => 'Auto-Deductions',       'route' => 'payment-subscriptions.php',          'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Audit Management',      'route' => 'audit-management.php',               'roles' => ['director','finance','bursar','accountant']],
                ['title' => 'Procurement Oversight', 'route' => 'procurement-oversight.php',          'roles' => ['director','finance','bursar','store']],
                ['title' => 'URA/Tax Reporting',     'route' => 'ura-reporting.php',                  'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Donations Management',  'route' => 'donations-management.php',           'roles' => ['director','bursar','finance','ceo']],
                ['title' => 'Bursar Billing',       'route' => 'bursar-billing.php',                 'roles' => ['bursar','accountant','finance']],
                ['title' => 'Bursar Ledger',        'route' => 'bursar-ledger.php',                  'roles' => ['bursar','accountant','finance']],
                ['title' => 'Bursar Payments',      'route' => 'bursar-payments.php',                'roles' => ['bursar','accountant','finance']],
                ['title' => 'Bursar Tax Reporting', 'route' => 'bursar-tax.php',                     'roles' => ['bursar','accountant','finance']],
                ['title' => 'Bursar Reports',       'route' => 'bursar-reports.php',                 'roles' => ['bursar','accountant','finance']],
                ['title' => 'Cost Center Management','route' => 'cost-center-management.php',         'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Penalty Configurations','route' => 'penalty-configurations.php',          'roles' => ['bursar','finance','director']],
                ['title' => 'Proof of Payments',     'route' => 'proof-of-payments.php',              'roles' => ['bursar','finance','accountant']],
            ],
        ],
        [
            'title'    => 'Human Resources',
            'icon'     => 'fas fa-users',
            'roles'    => ['hr','manager','director','principal','head','lecturer','staff'],
            'children' => [
                ['title' => 'HR Manager',            'route' => 'hr-manager.php',                     'roles' => ['hr','manager','director','principal']],
                ['title' => 'Staff Records',         'route' => 'hr-manager.php#staff-records',        'roles' => ['hr','manager','director','principal']],
                ['title' => 'Attendance',             'route' => 'hr-manager.php#attendance',           'roles' => ['hr','manager','director','principal']],
                ['title' => 'Performance',            'route' => 'hr-manager.php#performance',          'roles' => ['hr','manager','director','head','principal']],
                ['title' => 'Training',               'route' => 'hr-manager.php#training',             'roles' => ['hr','manager','lecturer','head']],
                ['title' => 'Recruitment',            'route' => 'hr-manager.php#recruitment',          'roles' => ['hr','manager','director','principal']],
                ['title' => 'Contracts',              'route' => 'hr-manager.php#contracts',            'roles' => ['hr','manager','director']],
                ['title' => 'Disciplinary',           'route' => 'hr-manager.php#disciplinary',         'roles' => ['hr','manager','director','principal']],
                ['title' => 'Payroll & Benefits',     'route' => 'hr-manager.php#payroll',              'roles' => ['hr','manager','director']],
                ['title' => 'Communications',         'route' => 'hr-manager.php#communications',       'roles' => ['hr','manager','director']],
                ['title' => 'HR Reports',             'route' => 'hr-manager.php#reports',              'roles' => ['hr','manager','director','principal']],
                ['title' => 'Roles & Access',         'route' => 'hr-manager.php#roles',                'roles' => ['hr','manager','director','principal']],
                ['title' => 'Staff Profile',         'route' => 'staff_profile_management.php',       'roles' => '*'],
                ['title' => 'Staff Directory',       'route' => 'staff-directory.php',                'roles' => '*'],
                ['title' => 'Staff Attendance',      'route' => 'staff-attendance.php',               'roles' => ['hr','manager','director']],
                ['title' => 'Leave Management',      'route' => 'leave-management.php',               'roles' => ['hr','manager','director','lecturer','staff']],
                ['title' => 'Performance Appraisal', 'route' => 'performance-appraisal.php',          'roles' => ['hr','manager','director','head']],
                ['title' => 'Training & CPD',        'route' => 'training-cpd.php',                   'roles' => ['hr','manager','lecturer','head']],
                ['title' => 'Recruitment',           'route' => 'recruitment.php',                    'roles' => ['hr','manager','director','principal']],
                ['title' => 'Contracts Management',  'route' => 'contracts-management.php',           'roles' => ['hr','manager','director']],
                ['title' => 'Staff Disciplinary',    'route' => 'staff-disciplinary.php',             'roles' => ['hr','manager','director','principal']],
                ['title' => 'Onboarding & Orientation','route' => 'onboarding.php',                   'roles' => ['hr','manager','director']],
                ['title' => 'Resignations & Exit',   'route' => 'resignations.php',                   'roles' => ['hr','manager','director']],
                ['title' => 'Duty Rosters',          'route' => 'duty-rosters.php',                   'roles' => ['hr','manager','director','head','matron','warden']],
                ['title' => 'Professional Licenses', 'route' => 'professional-licenses.php',          'roles' => ['hr','manager','director','head','nursing','midwifery']],
                ['title' => 'Non-Teaching Staff',    'route' => 'non-teaching-staff.php',             'roles' => ['non-teaching','non teaching','staff','lecturer']],
                ['title' => 'Task Management',       'route' => 'non-teaching-staff.php#tasks',       'roles' => ['non-teaching','non teaching','staff','lecturer']],
                ['title' => 'Staff Attendance',      'route' => 'non-teaching-staff.php#attendance',  'roles' => ['non-teaching','non teaching','staff','lecturer']],
                ['title' => 'Leave Requests',        'route' => 'non-teaching-staff.php#leave',       'roles' => ['non-teaching','non teaching','staff','lecturer']],
                ['title' => 'Training & Development','route' => 'non-teaching-staff.php#training',    'roles' => ['non-teaching','non teaching','staff','lecturer']],
            ],
        ],
        [
            'title'    => 'Nursing Department',
            'icon'     => 'fas fa-user-nurse',
            'roles'    => ['nursing','head','lecturer','midwifery'],
            'children' => [
                ['title' => 'Head of Nursing',       'route' => 'head-nursing.php',                   'roles' => ['head','nursing']],
                ['title' => 'Department Overview',   'route' => 'head-nursing.php#overview',           'roles' => ['head','nursing']],
                ['title' => 'Student Management',    'route' => 'head-nursing.php#students',           'roles' => ['head','nursing']],
                ['title' => 'Programs & Courses',    'route' => 'head-nursing.php#programs',           'roles' => ['head','nursing']],
                ['title' => 'Department Reports',    'route' => 'head-nursing.php#reports',            'roles' => ['head','nursing']],
                ['title' => 'Student Records',       'route' => 'head-nursing.php#student-records',    'roles' => ['head','nursing']],
                ['title' => 'Clinical Logbook',      'route' => 'clinical-placement.php?dept=nursing','roles' => ['nursing','midwifery','head','lecturer']],
                ['title' => 'Practical Assessment',  'route' => 'clinical-placement.php?view=assessment','roles' => ['nursing','head','lecturer']],
                ['title' => 'Skills Training',       'route' => 'skills-lab.php',                    'roles' => ['nursing','head','lecturer']],
                ['title' => 'Clinical Placements',   'route' => 'clinical-placement.php',             'roles' => ['nursing','midwifery','head','lecturer']],
            ],
        ],
        [
            'title'    => 'Midwifery Department',
            'icon'     => 'fas fa-baby',
            'roles'    => ['midwifery','head','lecturer'],
            'children' => [
                ['title' => 'Head of Midwifery',     'route' => 'head-midwifery.php',                 'roles' => ['head','midwifery']],
                ['title' => 'Department Overview',   'route' => 'head-midwifery.php#overview',         'roles' => ['head','midwifery']],
                ['title' => 'Student Management',    'route' => 'head-midwifery.php#students',         'roles' => ['head','midwifery']],
                ['title' => 'Programs & Courses',    'route' => 'head-midwifery.php#programs',         'roles' => ['head','midwifery']],
                ['title' => 'Department Reports',    'route' => 'head-midwifery.php#reports',          'roles' => ['head','midwifery']],
                ['title' => 'Antenatal Care',        'route' => 'clinical-placement.php?dept=midwifery&view=antenatal','roles' => ['midwifery','head','lecturer']],
                ['title' => 'Labor & Delivery',      'route' => 'clinical-placement.php?dept=midwifery&view=delivery','roles' => ['midwifery','head','lecturer']],
                ['title' => 'Postnatal Care',        'route' => 'clinical-placement.php?dept=midwifery&view=postnatal','roles' => ['midwifery','head','lecturer']],
                ['title' => 'Family Planning',       'route' => 'clinical-placement.php?dept=midwifery&view=fp','roles' => ['midwifery','head','lecturer']],
                ['title' => 'Clinical Placements',   'route' => 'clinical-placement.php?dept=midwifery','roles' => ['midwifery','head','lecturer']],
            ],
        ],
        [
            'title'    => 'ICT',
            'icon'     => 'fas fa-laptop-code',
            'roles'    => ['director','ict','it','lab','system admin','principal'],
            'children' => [
                ['title' => 'Director ICT',          'route' => 'director-ict.php',                   'roles' => ['director','ict','it']],
                ['title' => 'ICT Overview',          'route' => 'director-ict.php#overview',           'roles' => ['director','ict','it']],
                ['title' => 'Computer Lab',          'route' => 'director-ict.php#computers',          'roles' => ['director','ict','it','lab']],
                ['title' => 'Support Tickets',       'route' => 'director-ict.php#tickets',            'roles' => ['director','ict','it']],
                ['title' => 'Lab Bookings',          'route' => 'director-ict.php#bookings',           'roles' => ['director','ict','it','lecturer']],
                ['title' => 'Network Devices',       'route' => 'director-ict.php#network',            'roles' => ['director','ict','it']],
                ['title' => 'Software Inventory',    'route' => 'director-ict.php#software',           'roles' => ['director','ict','it']],
                ['title' => 'Maintenance Logs',      'route' => 'director-ict.php#maintenance',        'roles' => ['director','ict','it']],
                ['title' => 'ICT Reports',           'route' => 'director-ict.php#reports',             'roles' => ['director','ict','it']],
                ['title' => 'ICT News',              'route' => 'director-ict.php#news',              'roles' => ['director','ict','it']],
                ['title' => 'Official Duties',       'route' => 'director-ict.php#duties',            'roles' => ['director','ict','it']],
                ['title' => 'Quick Actions',         'route' => 'director-ict.php#actions',           'roles' => ['director','ict','it']],
                ['title' => 'ICT Students',          'route' => 'director-ict.php#students',          'roles' => ['director','ict','it']],
                ['title' => 'Computer Lab',          'route' => 'computer_lab.php',                   'roles' => ['director','ict','it','lab']],
                ['title' => 'Digital Learning',      'route' => 'digital-learning.php',               'roles' => ['director','ict','it','lecturer']],
                ['title' => 'Cybersecurity',         'route' => 'cybersecurity.php',                  'roles' => ['director','ict','it']],
                ['title' => 'ICT Policy',            'route' => 'ict-policy.php',                     'roles' => ['director','ict','it']],
                ['title' => 'System Administration', 'route' => 'system-admin.php',                   'roles' => ['director','ict','it','system admin']],
                ['title' => 'IT Support Tickets',    'route' => 'it-support-tickets.php',             'roles' => ['director','ict','it','lab']],
                ['title' => 'Lab Booking Management','route' => 'lab-booking-management.php',         'roles' => ['director','ict','it','lecturer','lab']],
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
                ['title' => 'Inventory Reports',     'route' => 'inventory-reports.php',              'roles' => ['director','principal','hr','store']],
                ['title' => 'Asset Management',      'route' => 'asset-management.php',               'roles' => ['store','director','manager']],
                ['title' => 'Procurement Oversight', 'route' => 'procurement-oversight.php',          'roles' => ['director','finance','bursar','store']],
                ['title' => 'Department Requests',   'route' => 'department-requests.php',            'roles' => ['store','director','manager','head']],
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
    ];
}
}

if (!function_exists('userCanAccessModule')) {
function userCanAccessModule($moduleRoles, string $userRole): bool {
    if ($moduleRoles === '*' || $moduleRoles === true) return true;
    if (!is_array($moduleRoles)) return false;
    foreach ($moduleRoles as $keyword) {
        if ($keyword !== '' && stripos($userRole, $keyword) !== false) {
            return true;
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
        // Check parent-level role access before processing children
        if (!userCanAccessModule($parent['roles'], $userRole)) {
            continue;
        }
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
