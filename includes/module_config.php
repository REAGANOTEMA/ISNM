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
            'roles'    => '*',
            'children' => [
                ['title' => 'Director General',       'route' => 'director-general.php',              'roles' => ['director general','ceo','system admin']],
                ['title' => 'CEO Dashboard',          'route' => 'ceo.php',                          'roles' => ['ceo','director general']],
                ['title' => 'School Principal',       'route' => 'school-principal.php',             'roles' => ['school principal','principal']],
                ['title' => 'Principal',              'route' => 'principal.php',                    'roles' => ['principal']],
                ['title' => 'Deputy Principal',       'route' => 'deputy-principal.php',             'roles' => ['deputy','principal']],
                ['title' => 'School Secretary',       'route' => 'school-secretary.php',             'roles' => ['school secretary','secretary']],
                ['title' => 'Secretary',              'route' => 'secretary.php',                    'roles' => ['school secretary','secretary']],
                ['title' => 'Institution Overview',   'route' => 'director-general.php#executive',   'roles' => ['director general','ceo','principal']],
                ['title' => 'Department Monitoring',  'route' => 'director-general.php#departments', 'roles' => ['director general','ceo','principal']],
                ['title' => 'Director Performance',   'route' => 'director-general.php#performance', 'roles' => ['director general','ceo']],
            ],
        ],
        [
            'title'    => 'Academic Management',
            'icon'     => 'fas fa-graduation-cap',
            'roles'    => '*',
            'children' => [
                ['title' => 'Director Academics',     'route' => 'director-academics.php',            'roles' => ['director','academics','principal','ceo']],
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
            ],
        ],
        [
            'title'    => 'Admissions',
            'icon'     => 'fas fa-file-signature',
            'roles'    => '*',
            'children' => [
                ['title' => 'Director Admissions',    'route' => 'director-admissions.php',           'roles' => ['admissions','director','secretary']],
                ['title' => 'Student Applications',   'route' => 'admission-letters.php?view=applications','roles' => ['admissions','director','secretary']],
                ['title' => 'Requirements & Clearance','route' => 'admission-letters.php?view=clearance','roles' => ['admissions','director','secretary']],
                ['title' => 'Admission Letters',      'route' => 'admission-letters.php',             'roles' => ['admissions','director','secretary']],
                ['title' => 'Intake Planning',        'route' => 'intake-planning.php',               'roles' => ['admissions','director','secretary','registrar']],
            ],
        ],
        [
            'title'    => 'Registrar',
            'icon'     => 'fas fa-clipboard-list',
            'roles'    => '*',
            'children' => [
                ['title' => 'Student Records',        'route' => 'student-records.php',               'roles' => '*'],
                ['title' => 'Student List',           'route' => 'student-management.php',            'roles' => ['secretary','ict','registrar','director','principal','school secretary']],
                ['title' => 'Student Directory',      'route' => '../student-directory.php',          'roles' => '*'],
                ['title' => 'Add Student',            'route' => 'student-add.php',                   'roles' => ['secretary','registrar','ict']],
                ['title' => 'Course Registration',    'route' => 'course-registration.php',           'roles' => ['registrar','academics','secretary']],
                ['title' => 'Transcripts',            'route' => 'staff_transcript_generation.php',   'roles' => '*'],
                ['title' => 'Certificates',           'route' => '../print_certificate.php',          'roles' => '*'],
                ['title' => 'Student Attendance',     'route' => 'student-attendance.php',            'roles' => ['lecturer','head','nursing','midwifery']],
            ],
        ],
        [
            'title'    => 'Finance',
            'icon'     => 'fas fa-chart-line',
            'roles'    => '*',
            'children' => [
                ['title' => 'Director Finance',      'route' => 'director-finance.php',               'roles' => ['director','finance','ceo']],
                ['title' => 'Bursar Dashboard',      'route' => 'school-bursar.php',                  'roles' => ['bursar','finance','accountant']],
                ['title' => 'Fee Structure',         'route' => 'fee-structure.php',                  'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Invoice Generation',    'route' => 'invoice-generation.php',             'roles' => ['bursar','finance','accountant']],
                ['title' => 'Payment Recording',     'route' => 'payment-recording.php',              'roles' => ['bursar','finance','accountant']],
                ['title' => 'Receipt Printing',      'route' => 'staff_receipt_printing.php',         'roles' => '*'],
                ['title' => 'Financial Reports',     'route' => 'financial-reports.php',              'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Budget Management',     'route' => 'budget-management.php',              'roles' => ['bursar','finance','director']],
                ['title' => 'Expenditure Tracking',  'route' => 'expenditure-tracking.php',           'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Payroll Management',    'route' => 'bursar-payroll.php',                 'roles' => ['bursar','payroll','finance','director']],
                ['title' => 'General Ledger',        'route' => 'general-ledger.php',                 'roles' => ['bursar','finance','accountant']],
                ['title' => 'Bank Reconciliation',   'route' => 'bank-reconciliation.php',            'roles' => ['bursar','finance','accountant']],
                ['title' => 'Student Statements',    'route' => 'student-statements.php',             'roles' => ['bursar','finance','accountant']],
                ['title' => 'Auto-Deductions',       'route' => 'payment-subscriptions.php',          'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Audit Management',      'route' => 'audit-management.php',               'roles' => ['director','finance','bursar','accountant']],
                ['title' => 'Procurement Oversight', 'route' => 'procurement-oversight.php',          'roles' => ['director','finance','bursar','store']],
                ['title' => 'URA/Tax Reporting',     'route' => 'ura-reporting.php',                  'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Donations Management',  'route' => 'donations-management.php',           'roles' => ['director','bursar','finance','ceo']],
            ],
        ],
        [
            'title'    => 'Human Resources',
            'icon'     => 'fas fa-users',
            'roles'    => '*',
            'children' => [
                ['title' => 'HR Manager',            'route' => 'hr-manager.php',                     'roles' => ['hr','manager','director','principal']],
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
                ['title' => 'Non-Teaching Staff',    'route' => 'non-teaching-staff.php',             'roles' => ['non teaching','staff','lecturer']],
            ],
        ],
        [
            'title'    => 'Nursing Department',
            'icon'     => 'fas fa-user-nurse',
            'roles'    => '*',
            'children' => [
                ['title' => 'Head of Nursing',       'route' => 'head-nursing.php',                   'roles' => ['head','nursing']],
                ['title' => 'Clinical Logbook',      'route' => 'clinical-placement.php?dept=nursing','roles' => ['nursing','midwifery','head','lecturer']],
                ['title' => 'Practical Assessment',  'route' => 'clinical-placement.php?view=assessment','roles' => ['nursing','head','lecturer']],
                ['title' => 'Skills Training',       'route' => 'skills-lab.php',                    'roles' => ['nursing','head','lecturer']],
                ['title' => 'Clinical Placements',   'route' => 'clinical-placement.php',             'roles' => ['nursing','midwifery','head','lecturer']],
            ],
        ],
        [
            'title'    => 'Midwifery Department',
            'icon'     => 'fas fa-baby',
            'roles'    => '*',
            'children' => [
                ['title' => 'Head of Midwifery',     'route' => 'head-midwifery.php',                 'roles' => ['head','midwifery']],
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
            'roles'    => '*',
            'children' => [
                ['title' => 'Director ICT',          'route' => 'director-ict.php',                   'roles' => ['director','ict','it']],
                ['title' => 'Computer Lab',          'route' => '../computer_lab.php',                'roles' => ['director','ict','it','lab']],
                ['title' => 'Digital Learning',      'route' => 'digital-learning.php',               'roles' => ['director','ict','it','lecturer']],
                ['title' => 'Cybersecurity',         'route' => 'cybersecurity.php',                  'roles' => ['director','ict','it']],
                ['title' => 'ICT Policy',            'route' => 'ict-policy.php',                     'roles' => ['director','ict','it']],
                ['title' => 'System Administration', 'route' => 'system-admin.php',                   'roles' => ['director','ict','it','system admin']],
            ],
        ],
        [
            'title'    => 'Library',
            'icon'     => 'fas fa-book',
            'roles'    => '*',
            'children' => [
                ['title' => 'Librarian Dashboard',   'route' => 'school-librarian.php',               'roles' => ['librarian','director']],
                ['title' => 'Library Books',         'route' => 'student-library.php?view=books',     'roles' => ['librarian','student','lecturer']],
                ['title' => 'Borrowing & Returns',   'route' => 'student-library.php?view=borrowing', 'roles' => ['librarian','student','lecturer']],
                ['title' => 'Library Fines',         'route' => 'student-library.php?view=fines',     'roles' => ['librarian']],
                ['title' => 'Digital Resources',     'route' => 'student-library.php?view=digital',   'roles' => ['librarian','lecturer']],
            ],
        ],
        [
            'title'    => 'Skills Laboratory',
            'icon'     => 'fas fa-flask',
            'roles'    => '*',
            'children' => [
                ['title' => 'Skills Lab Manager',    'route' => 'skills-lab.php',                     'roles' => ['skills lab','lab','director','principal']],
                ['title' => 'Lab Equipment',          'route' => 'skills-lab.php?view=equipment',     'roles' => ['skills lab','lab','lecturer','head']],
                ['title' => 'Practical Sessions',     'route' => 'skills-lab.php?view=sessions',      'roles' => ['skills lab','lab','lecturer','head']],
                ['title' => 'Lab Experiments',        'route' => 'lab-practical.php',                 'roles' => ['lecturer','head','nursing','midwifery','lab']],
                ['title' => 'Lab Safety',             'route' => 'skills-lab.php?view=safety',        'roles' => ['skills lab','lab','lecturer']],
            ],
        ],
        [
            'title'    => 'Store & Assets',
            'icon'     => 'fas fa-boxes',
            'roles'    => '*',
            'children' => [
                ['title' => 'Storekeeper',           'route' => 'storekeeper.php',                    'roles' => ['storekeeper','store','inventory']],
                ['title' => 'Inventory Reports',     'route' => 'inventory-reports.php',              'roles' => ['director','principal','hr','store']],
                ['title' => 'Asset Management',      'route' => 'asset-management.php',               'roles' => ['store','director','manager']],
                ['title' => 'Procurement Oversight', 'route' => 'procurement-oversight.php',          'roles' => ['director','finance','bursar','store']],
            ],
        ],
        [
            'title'    => 'Security & Transport',
            'icon'     => 'fas fa-shield-alt',
            'roles'    => '*',
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
            'roles'    => '*',
            'children' => [
                ['title' => 'Matrons',               'route' => 'matrons.php',                        'roles' => ['matron','warden']],
                ['title' => 'Wardens',               'route' => 'wardens.php',                        'roles' => ['warden','matron']],
                ['title' => 'Sickbay',               'route' => 'sickbay.php',                        'roles' => ['sickbay','matron','nursing']],
                ['title' => 'Counseling & Welfare',  'route' => 'counseling-welfare.php',              'roles' => ['director','principal','deputy','matron','warden','secretary']],
                ['title' => 'Hostel Management',     'route' => 'hostel-management.php',              'roles' => ['warden','matron','registrar']],
                ['title' => 'Meal & Accommodation',  'route' => 'meal-accommodation.php',             'roles' => ['matron','warden','registrar','director']],
                ['title' => 'Student Discipline',    'route' => 'student-discipline.php',             'roles' => ['head','principal','deputy','secretary','matron','warden']],
                ['title' => 'Student Requests Desk', 'route' => 'student-requests-desk.php',          'roles' => ['secretary','registrar','director','principal','deputy']],
                ['title' => 'Scholarships & Sponsorships','route' => 'scholarships-sponsorships.php', 'roles' => ['bursar','finance','director','registrar','secretary']],
                ['title' => 'Student Announcements', 'route' => 'student-announcements.php',          'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Communications',
            'icon'     => 'fas fa-bullhorn',
            'roles'    => '*',
            'children' => [
                ['title' => 'News Management',       'route' => '../news.php',                        'roles' => '*'],
                ['title' => 'Announcements',         'route' => 'student-announcements.php',          'roles' => '*'],
                ['title' => 'SMS / Email',           'route' => '../messaging.php',                   'roles' => '*'],
                ['title' => 'Notifications',          'route' => '../notifications.php',              'roles' => '*'],
                ['title' => 'Website Pages',         'route' => 'website-pages.php',                  'roles' => ['director','ict','it','secretary']],
                ['title' => 'Portal Messages',        'route' => 'portal-messages.php',               'roles' => ['director','secretary','ict','it']],
                ['title' => 'Contact Submissions',    'route' => 'contact-submissions.php',           'roles' => ['director','secretary','ict','it']],
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
            'roles'    => '*',
            'children' => [
                ['title' => 'My Profile',            'route' => 'student.php',                        'roles' => ['student']],
                ['title' => 'My Fees',               'route' => '../student-fees.php',                'roles' => ['student']],
                ['title' => 'My Results',            'route' => '../student-results.php',             'roles' => ['student']],
                ['title' => 'My Timetable',          'route' => '../student-timetable.php',           'roles' => ['student']],
                ['title' => 'Course Registration',   'route' => '../student-course-reg.php',          'roles' => ['student']],
                ['title' => 'Library Portal',        'route' => '../student-library-portal.php',      'roles' => ['student']],
            ],
        ],
        [
            'title'    => 'Student Government',
            'icon'     => 'fas fa-handshake',
            'roles'    => '*',
            'children' => [
                ['title' => 'Guild President',       'route' => 'guild-president.php',                'roles' => ['guild president','student']],
            ],
        ],
        [
            'title'    => 'Approvals & Workflow',
            'icon'     => 'fas fa-check-double',
            'roles'    => '*',
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
