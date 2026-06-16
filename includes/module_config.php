<?php
/**
 * Central Module Configuration for ISNM Hierarchical Dashboard Navigation
 * Each parent module contains child sub-modules with role-based access.
 */

if (!function_exists('getModuleConfig')) {
function getModuleConfig(): array {
    return [
        [
            'title'    => 'Dashboard',
            'icon'     => 'fas fa-th-large',
            'roles'    => '*',
            'children' => [
                ['title' => 'Dashboard Home',      'route' => 'index.php',                          'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Student Management',
            'icon'     => 'fas fa-user-graduate',
            'roles'    => '*',
            'children' => [
                ['title' => 'Student Records',     'route' => 'student-records.php',               'roles' => '*'],
                ['title' => 'Student List',        'route' => 'student-management.php',            'roles' => ['secretary','ict','registrar','director','principal','school secretary']],
                ['title' => 'Add Student',         'route' => 'student-add.php',                   'roles' => ['secretary','registrar','ict']],
                ['title' => 'Course Registration', 'route' => 'course-registration.php',           'roles' => ['registrar','academics','secretary']],
                ['title' => 'Clinical Placement',  'route' => 'clinical-placement.php',            'roles' => ['nursing','midwifery','head','lecturer']],
                ['title' => 'Student Attendance',  'route' => 'student-attendance.php',            'roles' => ['lecturer','head','nursing','midwifery']],
                ['title' => 'Discipline',          'route' => 'student-discipline.php',            'roles' => ['head','principal','deputy','secretary','matron','warden']],
                ['title' => 'Hostel Management',   'route' => 'hostel-management.php',             'roles' => ['warden','matron','registrar']],
                ['title' => 'Library',             'route' => 'student-library.php',               'roles' => ['librarian','student','lecturer']],
            ],
        ],
        [
            'title'    => 'Academic Management',
            'icon'     => 'fas fa-graduation-cap',
            'roles'    => '*',
            'children' => [
                ['title' => 'Academic Registrar',  'route' => 'academic-registrar.php',            'roles' => ['registrar','director','principal','deputy']],
                ['title' => 'Director Academics',  'route' => 'director-academics.php',            'roles' => ['director','academics','principal','ceo']],
                ['title' => 'School Principal',    'route' => 'school-principal.php',              'roles' => ['school principal','principal']],
                ['title' => 'Principal',           'route' => 'principal.php',                     'roles' => ['principal']],
                ['title' => 'Deputy Principal',    'route' => 'deputy-principal.php',              'roles' => ['deputy','principal']],
                ['title' => 'Admissions',          'route' => 'director-admissions.php',           'roles' => ['admissions','director','secretary']],
                ['title' => 'Lecturers',           'route' => 'lecturers.php',                     'roles' => ['lecturer','senior']],
                ['title' => 'Senior Lecturers',    'route' => 'senior-lecturers.php',              'roles' => ['senior','lecturer']],
                ['title' => 'Head of Nursing',     'route' => 'head-nursing.php',                  'roles' => ['head','nursing']],
                ['title' => 'Head of Midwifery',   'route' => 'head-midwifery.php',                'roles' => ['head','midwifery']],
                ['title' => 'Transcripts',         'route' => 'staff_transcript_generation.php',   'roles' => '*'],
                ['title' => 'Timetable',           'route' => 'timetable.php',                     'roles' => ['registrar','academics','lecturer','head']],
                ['title' => 'Exams & Results',     'route' => 'exams-results.php',                 'roles' => ['registrar','academics','lecturer','head']],
            ],
        ],
        [
            'title'    => 'Financial Management',
            'icon'     => 'fas fa-chart-line',
            'roles'    => '*',
            'children' => [
                ['title' => 'Bursar Dashboard',   'route' => 'bursar.php',                        'roles' => ['bursar','finance','accountant']],
                ['title' => 'School Bursar',       'route' => 'school-bursar.php',                 'roles' => ['school bursar','bursar','accountant']],
                ['title' => 'Finance Director',    'route' => 'director-finance.php',              'roles' => ['director','finance','ceo']],
                ['title' => 'Fee Structure',       'route' => 'fee-structure.php',                 'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Invoice Generation',  'route' => 'invoice-generation.php',            'roles' => ['bursar','finance','accountant']],
                ['title' => 'Payment Recording',   'route' => 'payment-recording.php',             'roles' => ['bursar','finance','accountant']],
                ['title' => 'Receipt Printing',    'route' => 'staff_receipt_printing.php',        'roles' => '*'],
                ['title' => 'Financial Reports',   'route' => 'financial-reports.php',             'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Budget Management',   'route' => 'budget-management.php',             'roles' => ['bursar','finance','director']],
                ['title' => 'Expenditure Tracking','route' => 'expenditure-tracking.php',          'roles' => ['bursar','finance','director','accountant']],
                ['title' => 'Student Statements',  'route' => 'student-statements.php',            'roles' => ['bursar','finance','accountant']],
                ['title' => 'Payroll Management',  'route' => 'bursar-payroll.php',                'roles' => ['bursar','payroll','finance','director']],
                ['title' => 'General Ledger',      'route' => 'general-ledger.php',                'roles' => ['bursar','finance','accountant']],
                ['title' => 'Bank Reconciliation', 'route' => 'bank-reconciliation.php',           'roles' => ['bursar','finance','accountant']],
            ],
        ],
        [
            'title'    => 'Staff & HR Management',
            'icon'     => 'fas fa-users',
            'roles'    => '*',
            'children' => [
                ['title' => 'HR Manager',          'route' => 'hr-manager.php',                    'roles' => ['hr','manager','director','principal']],
                ['title' => 'Staff Profile',       'route' => 'staff_profile_management.php',      'roles' => '*'],
                ['title' => 'Staff Directory',     'route' => 'staff-directory.php',               'roles' => '*'],
                ['title' => 'Staff Attendance',    'route' => 'staff-attendance.php',              'roles' => ['hr','manager','director']],
                ['title' => 'Leave Management',    'route' => 'leave-management.php',              'roles' => ['hr','manager','director','lecturer','staff']],
                ['title' => 'Performance Appraisal','route' => 'performance-appraisal.php',         'roles' => ['hr','manager','director','head']],
                ['title' => 'Training & CPD',      'route' => 'training-cpd.php',                  'roles' => ['hr','manager','lecturer','head']],
                ['title' => 'Recruitment',         'route' => 'recruitment.php',                   'roles' => ['hr','manager','director','principal']],
                ['title' => 'Contracts',           'route' => 'contracts-management.php',          'roles' => ['hr','manager','director']],
                ['title' => 'Disciplinary',        'route' => 'staff-disciplinary.php',            'roles' => ['hr','manager','director','principal']],
                ['title' => 'Non-Teaching Staff',  'route' => 'non-teaching-staff.php',            'roles' => ['non teaching','staff','lecturer']],
                ['title' => 'Payroll',             'route' => 'bursar-payroll.php',                'roles' => ['hr','bursar','payroll','finance','director']],
            ],
        ],
        [
            'title'    => 'Executive',
            'icon'     => 'fas fa-crown',
            'roles'    => '*',
            'children' => [
                ['title' => 'Director General',    'route' => 'director-general.php',              'roles' => '*'],
                ['title' => 'CEO',                 'route' => 'ceo.php',                           'roles' => ['ceo','director']],
                ['title' => 'ICT Director',        'route' => 'director-ict.php',                  'roles' => ['director','ict','it']],
            ],
        ],
        [
            'title'    => 'Student Services',
            'icon'     => 'fas fa-hand-holding-heart',
            'roles'    => '*',
            'children' => [
                ['title' => 'School Secretary',    'route' => 'school-secretary.php',              'roles' => ['school secretary','secretary']],
                ['title' => 'Secretary',           'route' => 'secretary.php',                     'roles' => ['school secretary','secretary']],
                ['title' => 'Librarian',           'route' => 'school-librarian.php',              'roles' => ['librarian','director']],
                ['title' => 'Sickbay',             'route' => 'sickbay.php',                       'roles' => ['sickbay','matron','nursing']],
                ['title' => 'Matrons',             'route' => 'matrons.php',                       'roles' => ['matron','warden']],
                ['title' => 'Wardens',             'route' => 'wardens.php',                       'roles' => ['warden','matron']],
                ['title' => 'Student Announcements','route' => 'student-announcements.php',        'roles' => '*'],
                ['title' => 'Communication',       'route' => 'communications.php',                'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Security & Transport',
            'icon'     => 'fas fa-shield-alt',
            'roles'    => '*',
            'children' => [
                ['title' => 'Security',            'route' => 'security.php',                      'roles' => ['security','director']],
                ['title' => 'Fleet Management',    'route' => 'drivers.php',                       'roles' => ['driver','director']],
            ],
        ],
        [
            'title'    => 'Store & Assets',
            'icon'     => 'fas fa-boxes',
            'roles'    => '*',
            'children' => [
                ['title' => 'Storekeeper',         'route' => 'storekeeper.php',                   'roles' => ['storekeeper','store','inventory']],
                ['title' => 'Inventory Reports',   'route' => 'inventory-reports.php',             'roles' => ['director','principal','hr','store']],
                ['title' => 'Asset Management',    'route' => 'asset-management.php',              'roles' => ['store','director','manager']],
            ],
        ],
        [
            'title'    => 'Communications',
            'icon'     => 'fas fa-bullhorn',
            'roles'    => '*',
            'children' => [
                ['title' => 'News Management',     'route' => '../news.php',                       'roles' => '*'],
                ['title' => 'SMS / E-mail',        'route' => '../messaging.php',                  'roles' => '*'],
                ['title' => 'Notifications',       'route' => '../notifications.php',              'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Documents & Printing',
            'icon'     => 'fas fa-print',
            'roles'    => '*',
            'children' => [
                ['title' => 'Document Templates',  'route' => 'document_management.php',           'roles' => '*'],
            ],
        ],
        [
            'title'    => 'ICT',
            'icon'     => 'fas fa-laptop-code',
            'roles'    => '*',
            'children' => [
                ['title' => 'Computer Lab',        'route' => '../computer_lab.php',               'roles' => ['director','ict','it','lab']],
            ],
        ],
        [
            'title'    => 'Student Government',
            'icon'     => 'fas fa-handshake',
            'roles'    => '*',
            'children' => [
                ['title' => 'Guild President',     'route' => 'guild-president.php',               'roles' => ['guild president','student']],
            ],
        ],
        [
            'title'    => 'Student Self-Service',
            'icon'     => 'fas fa-user-circle',
            'roles'    => '*',
            'children' => [
                ['title' => 'My Profile',          'route' => 'student.php',                       'roles' => ['student']],
                ['title' => 'My Fees',             'route' => '../student-fees.php',               'roles' => ['student']],
                ['title' => 'My Results',          'route' => '../student-results.php',            'roles' => ['student']],
                ['title' => 'My Timetable',        'route' => '../student-timetable.php',          'roles' => ['student']],
                ['title' => 'Course Registration', 'route' => '../student-course-reg.php',         'roles' => ['student']],
                ['title' => 'Library Portal',      'route' => '../student-library-portal.php',     'roles' => ['student']],
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
