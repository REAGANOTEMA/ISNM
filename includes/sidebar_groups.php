<?php
/**
 * Professional Grouped Sidebar Definitions for ISNM ERP
 * Each role gets a sidebar with MAIN, OPERATIONS, MANAGEMENT, REPORTS, COMMUNICATION, ACCOUNT groups.
 * Every group is collapsible. Only department-specific modules are listed.
 */
if (function_exists('getSidebarGroups')) return;

function getSidebarGroups(string $role): array {
    $g = [];
    // ── MAIN ──
    $g['MAIN'] = [
        ['label' => 'Dashboard',    'icon' => 'fas fa-chart-pie',  'page' => 'home'],
        ['label' => 'Overview',     'icon' => 'fas fa-home',       'page' => 'overview'],
        ['label' => 'Analytics',    'icon' => 'fas fa-chart-line', 'page' => 'analytics'],
    ];

    // ── OPERATIONS (role-specific) ──
    $g['OPERATIONS'] = getRoleOperations($role);

    // ── MANAGEMENT ──
    $g['MANAGEMENT'] = [
        ['label' => 'Approvals',    'icon' => 'fas fa-check-double', 'page' => 'approvals'],
        ['label' => 'Tasks',        'icon' => 'fas fa-tasks',       'page' => 'tasks'],
        ['label' => 'Schedules',    'icon' => 'fas fa-calendar-alt', 'page' => 'schedules'],
    ];

    // ── REPORTS ──
    $g['REPORTS'] = [
        ['label' => 'Daily Reports',   'icon' => 'fas fa-calendar-day',  'page' => 'reports-daily'],
        ['label' => 'Monthly Reports', 'icon' => 'fas fa-calendar-alt',  'page' => 'reports-monthly'],
        ['label' => 'Annual Reports',  'icon' => 'fas fa-calendar-check','page' => 'reports-annual'],
        ['label' => 'Exports',         'icon' => 'fas fa-file-export',   'page' => 'exports'],
        ['label' => 'Print Center',    'icon' => 'fas fa-print',         'page' => 'print'],
    ];

    // ── COMMUNICATION ──
    $g['COMMUNICATION'] = [
        ['label' => 'Notifications', 'icon' => 'fas fa-bell',       'page' => 'notifications'],
        ['label' => 'Messages',      'icon' => 'fas fa-envelope',   'page' => 'messages'],
        ['label' => 'Announcements', 'icon' => 'fas fa-bullhorn',   'page' => 'announcements'],
    ];

    // ── ACCOUNT ──
    $g['ACCOUNT'] = [
        ['label' => 'Profile',         'icon' => 'fas fa-user-circle', 'page' => 'profile'],
        ['label' => 'Preferences',     'icon' => 'fas fa-cog',         'page' => 'preferences'],
        ['label' => 'Security',        'icon' => 'fas fa-shield-alt',  'page' => 'security'],
        ['label' => 'Activity Logs',   'icon' => 'fas fa-history',     'page' => 'activity-logs'],
        ['label' => 'Logout',          'icon' => 'fas fa-sign-out-alt','page' => 'logout', 'href' => '../logout.php'],
    ];

    return $g;
}

function getRoleOperations(string $role): array {
    $roleKey = normalizeRoleKeySidebar($role);

    $definitions = [
        'director_general' => [
            ['label' => 'Department Monitoring','icon'=>'fas fa-building','page'=>'departments'],
            ['label' => 'Performance Dashboard','icon'=>'fas fa-chart-bar','page'=>'performance'],
            ['label' => 'Financial Overview',   'icon'=>'fas fa-coins',    'page'=>'financial'],
            ['label' => 'Staff Management',     'icon'=>'fas fa-users',    'page'=>'staff'],
            ['label' => 'Student Management',   'icon'=>'fas fa-user-graduate','page'=>'student'],
            ['label' => 'System Health',        'icon'=>'fas fa-heartbeat','page'=>'system-health'],
        ],
        'director_ict' => [
            ['label' => 'Assets & Inventory',   'icon'=>'fas fa-desktop',   'page'=>'assets'],
            ['label' => 'Infrastructure',       'icon'=>'fas fa-server',    'page'=>'infrastructure'],
            ['label' => 'Helpdesk',             'icon'=>'fas fa-ticket-alt','page'=>'helpdesk'],
            ['label' => 'Backups',              'icon'=>'fas fa-database',  'page'=>'backups'],
            ['label' => 'Security',             'icon'=>'fas fa-shield-alt','page'=>'security'],
            ['label' => 'Monitoring',           'icon'=>'fas fa-chart-line','page'=>'monitoring'],
            ['label' => 'Website',              'icon'=>'fas fa-globe',     'page'=>'website'],
            ['label' => 'Digital Learning',     'icon'=>'fas fa-laptop',    'page'=>'digital-learning'],
        ],
        'director_academics' => [
            ['label' => 'Academic Programs',    'icon'=>'fas fa-book-open',    'page'=>'programs'],
            ['label' => 'Academic Registrar',   'icon'=>'fas fa-clipboard-list','page'=>'registrar'],
            ['label' => 'Curriculum',           'icon'=>'fas fa-layer-group',  'page'=>'curriculum'],
            ['label' => 'Quality Assurance',    'icon'=>'fas fa-check-circle', 'page'=>'quality'],
            ['label' => 'Examinations',         'icon'=>'fas fa-pen',          'page'=>'examinations'],
            ['label' => 'Research',             'icon'=>'fas fa-flask',        'page'=>'research'],
        ],
        'director_finance' => [
            ['label' => 'Revenue Management',   'icon'=>'fas fa-arrow-up',     'page'=>'revenue'],
            ['label' => 'Budget Management',    'icon'=>'fas fa-calculator',   'page'=>'budget'],
            ['label' => 'Expenditure Control',  'icon'=>'fas fa-file-invoice', 'page'=>'expenditure'],
            ['label' => 'Payroll Oversight',    'icon'=>'fas fa-money-check',  'page'=>'payroll'],
            ['label' => 'Accounting & Ledger',  'icon'=>'fas fa-book',         'page'=>'ledger'],
            ['label' => 'Audit & Compliance',   'icon'=>'fas fa-clipboard-check','page'=>'audit'],
            ['label' => 'Procurement',          'icon'=>'fas fa-shopping-cart','page'=>'procurement'],
            ['label' => 'Assets & Investments', 'icon'=>'fas fa-building',     'page'=>'assets'],
        ],
        'director_admissions' => [
            ['label' => 'Applicants',           'icon'=>'fas fa-users',        'page'=>'applicants'],
            ['label' => 'New Applicant',        'icon'=>'fas fa-user-plus',    'page'=>'new-applicant'],
            ['label' => 'Applicant Records',    'icon'=>'fas fa-folder-open',  'page'=>'applicant-records'],
            ['label' => 'Intake Management',    'icon'=>'fas fa-calendar',     'page'=>'intake'],
            ['label' => 'Requirements',         'icon'=>'fas fa-list-check',   'page'=>'requirements'],
            ['label' => 'Clearance',            'icon'=>'fas fa-clipboard-check','page'=>'clearance'],
            ['label' => 'Registration',         'icon'=>'fas fa-user-graduate','page'=>'registration'],
            ['label' => 'Admission Letters',    'icon'=>'fas fa-file-signature','page'=>'letters'],
        ],
        'principal' => [
            ['label' => 'Academic Oversight',   'icon'=>'fas fa-book-open',    'page'=>'academic'],
            ['label' => 'Student Affairs',      'icon'=>'fas fa-users',        'page'=>'student-affairs'],
            ['label' => 'Staff Oversight',      'icon'=>'fas fa-chalkboard-teacher','page'=>'staff'],
            ['label' => 'Institutional Ops',    'icon'=>'fas fa-building',     'page'=>'operations'],
            ['label' => 'Meetings & Governance','icons'=>'fas fa-handshake',   'page'=>'meetings'],
        ],
        'deputy_principal' => [
            ['label' => 'Academic Monitoring',  'icon'=>'fas fa-book-open',    'page'=>'academic'],
            ['label' => 'Class Monitoring',     'icon'=>'fas fa-chalkboard',   'page'=>'classes'],
            ['label' => 'Student Welfare',      'icon'=>'fas fa-heart',        'page'=>'welfare'],
            ['label' => 'Student Discipline',   'icon'=>'fas fa-gavel',        'page'=>'discipline'],
            ['label' => 'Attendance',           'icon'=>'fas fa-calendar-check','page'=>'attendance'],
            ['label' => 'Clinical Placement',   'icon'=>'fas fa-clinic-medical','page'=>'clinical'],
            ['label' => 'Compliance Tracking',  'icon'=>'fas fa-check-circle', 'page'=>'compliance'],
            ['label' => 'Quality Assurance',    'icon'=>'fas fa-clipboard-check','page'=>'quality'],
        ],
        'academic_registrar' => [
            ['label' => 'Student Records',      'icon'=>'fas fa-folder-open',  'page'=>'student-records'],
            ['label' => 'Programs',             'icon'=>'fas fa-layer-group',  'page'=>'programs'],
            ['label' => 'Courses',              'icon'=>'fas fa-book',         'page'=>'courses'],
            ['label' => 'Examinations',         'icon'=>'fas fa-pen',          'page'=>'examinations'],
            ['label' => 'Results & Grades',     'icon'=>'fas fa-file-alt',     'page'=>'results'],
            ['label' => 'Transcripts',          'icon'=>'fas fa-file-invoice', 'page'=>'transcripts'],
            ['label' => 'Certificates',         'icon'=>'fas fa-certificate',  'page'=>'certificates'],
            ['label' => 'Graduation',           'icon'=>'fas fa-graduation-cap','page'=>'graduation'],
            ['label' => 'Timetable',            'icon'=>'fas fa-calendar-week','page'=>'timetable'],
            ['label' => 'Intakes',              'icon'=>'fas fa-door-open',    'page'=>'intakes'],
        ],
        'secretary' => [
            ['label' => 'Correspondence',       'icon'=>'fas fa-envelope',     'page'=>'correspondence'],
            ['label' => 'Appointments',         'icon'=>'fas fa-calendar-check','page'=>'appointments'],
            ['label' => 'Meetings',             'icon'=>'fas fa-handshake',    'page'=>'meetings'],
            ['label' => 'Document Management',  'icon'=>'fas fa-file-alt',     'page'=>'documents'],
            ['label' => 'Records',              'icon'=>'fas fa-folder',       'page'=>'records'],
        ],
        'hr' => [
            ['label' => 'Staff Directory',      'icon'=>'fas fa-address-book','page'=>'staff-directory'],
            ['label' => 'Staff Attendance',     'icon'=>'fas fa-clipboard-list','page'=>'attendance'],
            ['label' => 'Leave Management',     'icon'=>'fas fa-calendar-alt','page'=>'leave'],
            ['label' => 'Performance',          'icon'=>'fas fa-chart-line',  'page'=>'performance'],
            ['label' => 'Training & CPD',       'icon'=>'fas fa-graduation-cap','page'=>'training'],
            ['label' => 'Recruitment',          'icon'=>'fas fa-user-plus',   'page'=>'recruitment'],
            ['label' => 'Contracts',            'icon'=>'fas fa-file-contract','page'=>'contracts'],
            ['label' => 'Disciplinary',         'icon'=>'fas fa-gavel',       'page'=>'disciplinary'],
            ['label' => 'Licenses',             'icon'=>'fas fa-certificate', 'page'=>'licenses'],
            ['label' => 'Payroll',              'icon'=>'fas fa-money-check', 'page'=>'payroll'],
            ['label' => 'Onboarding',           'icon'=>'fas fa-user-check',  'page'=>'onboarding'],
        ],
        'librarian' => [
            ['label' => 'Catalogue',            'icon'=>'fas fa-book',         'page'=>'catalogue'],
            ['label' => 'Books',                'icon'=>'fas fa-book-open',    'page'=>'books'],
            ['label' => 'Borrowing',            'icon'=>'fas fa-hand-holding', 'page'=>'borrowing'],
            ['label' => 'Returns',              'icon'=>'fas fa-undo',         'page'=>'returns'],
            ['label' => 'Reservations',         'icon'=>'fas fa-calendar-check','page'=>'reservations'],
            ['label' => 'Members',              'icon'=>'fas fa-id-card',      'page'=>'members'],
            ['label' => 'Barcodes',             'icon'=>'fas fa-qrcode',       'page'=>'barcodes'],
            ['label' => 'Inventory',            'icon'=>'fas fa-boxes',        'page'=>'inventory'],
            ['label' => 'Fines',                'icon'=>'fas fa-money-bill',   'page'=>'fines'],
        ],
        'head_nursing' => [
            ['label' => 'Department Overview',  'icon'=>'fas fa-chart-pie',    'page'=>'overview'],
            ['label' => 'Clinical Placements',  'icon'=>'fas fa-clinic-medical','page'=>'clinical'],
            ['label' => 'Nursing Students',     'icon'=>'fas fa-user-nurse',   'page'=>'students'],
            ['label' => 'Timetable',            'icon'=>'fas fa-calendar-week','page'=>'timetable'],
            ['label' => 'Courses',              'icon'=>'fas fa-book-open',    'page'=>'courses'],
            ['label' => 'Department Staff',     'icon'=>'fas fa-users',        'page'=>'staff'],
        ],
        'head_midwifery' => [
            ['label' => 'Department Overview',  'icon'=>'fas fa-chart-pie',    'page'=>'overview'],
            ['label' => 'Clinical Placements',  'icon'=>'fas fa-clinic-medical','page'=>'clinical'],
            ['label' => 'Midwifery Students',   'icon'=>'fas fa-baby',         'page'=>'students'],
            ['label' => 'Timetable',            'icon'=>'fas fa-calendar-week','page'=>'timetable'],
            ['label' => 'Courses',              'icon'=>'fas fa-book-open',    'page'=>'courses'],
            ['label' => 'Department Staff',     'icon'=>'fas fa-users',        'page'=>'staff'],
        ],
        'senior_lecturer' => [
            ['label' => 'My Courses',           'icon'=>'fas fa-book',         'page'=>'my-courses'],
            ['label' => 'Timetable',            'icon'=>'fas fa-calendar-week','page'=>'timetable'],
            ['label' => 'Attendance',           'icon'=>'fas fa-calendar-check','page'=>'attendance'],
            ['label' => 'Marks Entry',          'icon'=>'fas fa-pen',          'page'=>'marks'],
            ['label' => 'Lecture Notes',        'icon'=>'fas fa-file-alt',     'page'=>'notes'],
            ['label' => 'Research',             'icon'=>'fas fa-flask',        'page'=>'research'],
            ['label' => 'Leave',                'icon'=>'fas fa-calendar-alt', 'page'=>'leave'],
        ],
        'lecturer' => [
            ['label' => 'My Courses',           'icon'=>'fas fa-book',         'page'=>'my-courses'],
            ['label' => 'Timetable',            'icon'=>'fas fa-calendar-week','page'=>'timetable'],
            ['label' => 'Attendance',           'icon'=>'fas fa-calendar-check','page'=>'attendance'],
            ['label' => 'CAT Marks',            'icon'=>'fas fa-pen',          'page'=>'cat-marks'],
            ['label' => 'Exam Marks',           'icon'=>'fas fa-file-alt',     'page'=>'exam-marks'],
            ['label' => 'Teaching Materials',   'icon'=>'fas fa-folder-open',  'page'=>'materials'],
            ['label' => 'Student Results',      'icon'=>'fas fa-chart-bar',    'page'=>'results'],
            ['label' => 'Course Reports',        'icon'=>'fas fa-file-invoice','page'=>'reports'],
            ['label' => 'Lesson Plans',         'icon'=>'fas fa-clipboard-list','page'=>'lesson-plans'],
            ['label' => 'Assignments',          'icon'=>'fas fa-tasks',        'page'=>'assignments'],
        ],
        'matron' => [
            ['label' => 'Health Records',       'icon'=>'fas fa-notes-medical','page'=>'health-records'],
            ['label' => 'Hostel Management',    'icon'=>'fas fa-bed',          'page'=>'hostel'],
            ['label' => 'Meals & Accommodation','icon'=>'fas fa-utensils',     'page'=>'meals'],
            ['label' => 'Sick Bay',             'icon'=>'fas fa-plus-circle',  'page'=>'sickbay'],
            ['label' => 'Student Welfare',      'icon'=>'fas fa-heart',        'page'=>'welfare'],
        ],
        'wardens' => [
            ['label' => 'Hostel Management',    'icon'=>'fas fa-bed',          'page'=>'hostel'],
            ['label' => 'Discipline',           'icon'=>'fas fa-gavel',        'page'=>'discipline'],
            ['label' => 'Student Welfare',      'icon'=>'fas fa-heart',        'page'=>'welfare'],
            ['label' => 'Hostel Reports',       'icon'=>'fas fa-file-alt',     'page'=>'reports'],
        ],
        'drivers' => [
            ['label' => 'Trip Requests',        'icon'=>'fas fa-route',        'page'=>'trip-requests'],
            ['label' => 'Assigned Vehicles',    'icon'=>'fas fa-truck',        'page'=>'assigned-vehicles'],
            ['label' => 'Journey Planner',      'icon'=>'fas fa-map-marked-alt','page'=>'journey-planner'],
            ['label' => 'Fuel Requests',        'icon'=>'fas fa-gas-pump',     'page'=>'fuel-requests'],
            ['label' => 'Fuel Records',         'icon'=>'fas fa-file-invoice', 'page'=>'fuel-records'],
            ['label' => 'Mileage',              'icon'=>'fas fa-tachometer-alt','page'=>'mileage'],
            ['label' => 'Maintenance',          'icon'=>'fas fa-tools',        'page'=>'maintenance'],
            ['label' => 'Repairs',              'icon'=>'fas fa-wrench',       'page'=>'repairs'],
            ['label' => 'Vehicle Inspection',   'icon'=>'fas fa-clipboard-check','page'=>'inspection'],
            ['label' => 'Vehicle History',      'icon'=>'fas fa-history',      'page'=>'vehicle-history'],
            ['label' => 'Attendance',           'icon'=>'fas fa-calendar-check','page'=>'attendance'],
            ['label' => 'Journey Reports',      'icon'=>'fas fa-file-alt',     'page'=>'journey-reports'],
            ['label' => 'Incident Reports',     'icon'=>'fas fa-exclamation-triangle','page'=>'incidents'],
        ],
        'security' => [
            ['label' => 'Visitor Registration', 'icon'=>'fas fa-user-plus',    'page'=>'visitor-registration'],
            ['label' => 'Visitor History',      'icon'=>'fas fa-history',      'page'=>'visitor-history'],
            ['label' => 'Visitor Exit',         'icon'=>'fas fa-sign-out-alt', 'page'=>'visitor-exit'],
            ['label' => 'Vehicle Entry',        'icon'=>'fas fa-truck',        'page'=>'vehicle-entry'],
            ['label' => 'Vehicle Exit',         'icon'=>'fas fa-truck-moving', 'page'=>'vehicle-exit'],
            ['label' => 'Incident Reports',     'icon'=>'fas fa-exclamation-triangle','page'=>'incidents'],
            ['label' => 'Emergency Reports',    'icon'=>'fas fa-ambulance',    'page'=>'emergency'],
            ['label' => 'Blacklist',            'icon'=>'fas fa-ban',          'page'=>'blacklist'],
            ['label' => 'Visitor Pass',         'icon'=>'fas fa-id-card',      'page'=>'visitor-pass'],
            ['label' => 'Patrol Reports',       'icon'=>'fas fa-shield-alt',   'page'=>'patrol'],
        ],
        'store' => [
            ['label' => 'Inventory',            'icon'=>'fas fa-boxes',        'page'=>'inventory'],
            ['label' => 'Store Requests',       'icon'=>'fas fa-clipboard-list','page'=>'requests'],
            ['label' => 'Purchase Orders',      'icon'=>'fas fa-shopping-cart','page'=>'purchase-orders'],
            ['label' => 'Suppliers',            'icon'=>'fas fa-handshake',    'page'=>'suppliers'],
            ['label' => 'Stock Adjustments',    'icon'=>'fas fa-edit',         'page'=>'adjustments'],
            ['label' => 'Stock Reports',        'icon'=>'fas fa-chart-bar',    'page'=>'reports'],
        ],
        'computer_lab' => [
            ['label' => 'Computers Inventory',  'icon'=>'fas fa-desktop',      'page'=>'computers'],
            ['label' => 'Lab Bookings',         'icon'=>'fas fa-calendar-check','page'=>'bookings'],
            ['label' => 'Maintenance',          'icon'=>'fas fa-tools',        'page'=>'maintenance'],
            ['label' => 'Usage Statistics',     'icon'=>'fas fa-chart-line',   'page'=>'usage'],
            ['label' => 'Lab Reports',          'icon'=>'fas fa-file-alt',     'page'=>'reports'],
            ['label' => 'Printing Centre',      'icon'=>'fas fa-print',        'page'=>'printing'],
            ['label' => 'Technical Support',    'icon'=>'fas fa-headset',      'page'=>'support'],
            ['label' => 'Software',             'icon'=>'fas fa-code',         'page'=>'software'],
        ],
        'skills_lab' => [
            ['label' => 'Equipment',            'icon'=>'fas fa-tools',        'page'=>'equipment'],
            ['label' => 'Practical Sessions',   'icon'=>'fas fa-flask',        'page'=>'sessions'],
            ['label' => 'Bookings',             'icon'=>'fas fa-calendar-check','page'=>'bookings'],
            ['label' => 'Chemical Inventory',   'icon'=>'fas fa-flask',        'page'=>'chemicals'],
            ['label' => 'Maintenance',          'icon'=>'fas fa-wrench',       'page'=>'maintenance'],
            ['label' => 'Attendance',           'icon'=>'fas fa-calendar-check','page'=>'attendance'],
            ['label' => 'Skills Lab Reports',   'icon'=>'fas fa-file-alt',    'page'=>'reports'],
        ],
        'guild' => [
            ['label' => 'Student Body',         'icon'=>'fas fa-users',        'page'=>'student-body'],
            ['label' => 'Student Welfare',      'icon'=>'fas fa-heart',        'page'=>'welfare'],
            ['label' => 'Events',               'icon'=>'fas fa-calendar-alt', 'page'=>'events'],
            ['label' => 'Feedback',             'icon'=>'fas fa-comment-dots', 'page'=>'feedback'],
            ['label' => 'Guild Reports',        'icon'=>'fas fa-file-alt',     'page'=>'reports'],
        ],
    ];

    return $definitions[$roleKey] ?? $definitions['lecturer'];
}

function normalizeRoleKeySidebar(string $role): string {
    $m = strtolower(trim($role));
    $map = [
        'director general' => 'director_general', 'ceo' => 'director_general',
        'director ict' => 'director_ict', 'director-academics' => 'director_academics',
        'director admissions' => 'director_admissions', 'director finance' => 'director_finance',
        'school principal' => 'principal', 'deputy principal' => 'deputy_principal',
        'academic registrar' => 'academic_registrar', 'school secretary' => 'secretary',
        'hr manager' => 'hr', 'school librarian' => 'librarian',
        'head of nursing' => 'head_nursing', 'head of midwifery' => 'head_midwifery',
        'senior lecturer' => 'senior_lecturer', 'lecturer' => 'lecturer',
        'matron' => 'matron', 'warden' => 'wardens',
        'driver' => 'drivers', 'security' => 'security',
        'storekeeper' => 'store', 'computer lab' => 'computer_lab',
        'skills lab' => 'skills_lab', 'guild president' => 'guild',
    ];
    return $map[$m] ?? 'lecturer';
}
