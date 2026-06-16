<?php
/**
 * Central Module Configuration for ISNM Hierarchical Dashboard Navigation
 * Each parent module contains child sub-modules with role-based access.
 */

if (!function_exists('getModuleConfig')) {
function getModuleConfig(): array {
    return [
        [
            'title'    => 'Student Management',
            'icon'     => 'fas fa-user-graduate',
            'roles'    => '*',
            'children' => [
                ['title' => 'Student Records',     'route' => 'student-records.php',               'roles' => '*'],
                ['title' => 'Full Student Panel',  'route' => 'student-management.php',            'roles' => ['secretary','ict','registrar','director','principal','school secretary']],
                ['title' => 'Student Self-Service','route' => 'student.php',                       'roles' => ['student']],
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
            ],
        ],
        [
            'title'    => 'Staff & HR',
            'icon'     => 'fas fa-users',
            'roles'    => '*',
            'children' => [
                ['title' => 'HR Manager',          'route' => 'hr-manager.php',                    'roles' => ['hr','manager','director','principal']],
                ['title' => 'Staff Profile',       'route' => 'staff_profile_management.php',      'roles' => '*'],
                ['title' => 'Non-Teaching Staff',  'route' => 'non-teaching-staff.php',            'roles' => ['non teaching','staff','lecturer']],
                ['title' => 'Payroll',             'route' => 'bursar-payroll.php',                'roles' => ['bursar','payroll','finance','director']],
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
            'title'    => 'Finance',
            'icon'     => 'fas fa-chart-line',
            'roles'    => '*',
            'children' => [
                ['title' => 'Bursar',              'route' => 'bursar.php',                        'roles' => ['bursar','finance','accountant']],
                ['title' => 'School Bursar',       'route' => 'school-bursar.php',                 'roles' => ['school bursar','bursar','accountant']],
                ['title' => 'Finance Director',    'route' => 'director-finance.php',              'roles' => ['director','finance','ceo']],
                ['title' => 'Inventory Reports',   'route' => 'inventory-reports.php',             'roles' => ['director','principal','hr','store']],
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
            ],
        ],
        [
            'title'    => 'Communications',
            'icon'     => 'fas fa-bullhorn',
            'roles'    => '*',
            'children' => [
                ['title' => 'News Management',     'route' => '../news.php',                       'roles' => '*'],
            ],
        ],
        [
            'title'    => 'Documents & Printing',
            'icon'     => 'fas fa-print',
            'roles'    => '*',
            'children' => [
                ['title' => 'Document Templates',  'route' => 'document_management.php',           'roles' => '*'],
                ['title' => 'Receipt Printing',    'route' => 'staff_receipt_printing.php',         'roles' => '*'],
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
