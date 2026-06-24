<?php
/**
 * Professional Hierarchical Sidebar Navigation
 * Collapsible accordion, smooth animations, role-filtered, auto-expand active.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
    header('Location: ../index.php'); exit();
}

$user_role = $_SESSION['role'];
$user_type = $_SESSION['type'];
$user_name = $_SESSION['full_name'] ?? ($_SESSION['first_name'] ?? 'User');
$user_id   = (int)($_SESSION['user_id'] ?? 0);

// Load profile image
$profileImage = '../images/username.png';
$profileClickHandler = "if(typeof openProfileModal==='function')openProfileModal();";
if ($user_type === 'student') {
    $profileClickHandler = "window.location.href='../student_profile.php'";
    // Try to load student profile photo
    try {
        $rootPath_sb = rtrim(str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2), '/');
        $studentConn = getStudentsConnection();
        if ($studentConn) {
            $q = $studentConn->prepare("SELECT profile_picture, passport_photo FROM students WHERE id = ?");
            $q->bind_param('i', $user_id);
            $q->execute();
            $photoRow = $q->get_result()->fetch_assoc();
            $q->close();
            $photoFile = '';
            if ($photoRow) {
                if (!empty($photoRow['profile_picture'])) $photoFile = $photoRow['profile_picture'];
                elseif (!empty($photoRow['passport_photo'])) $photoFile = $photoRow['passport_photo'];
            }
            if ($photoFile && file_exists(__DIR__ . '/../studentUploads/profile_images/' . $photoFile)) {
                $profileImage = $rootPath_sb . '/studentUploads/profile_images/' . $photoFile . '?v=' . time();
            } elseif ($photoFile && file_exists(__DIR__ . '/../' . $photoFile)) {
                $profileImage = $rootPath_sb . '/' . $photoFile . '?v=' . time();
            }
        }
    } catch (Exception $e) {}
} else {
    // Staff: load from staff_profiles
    $profileFile = __DIR__ . '/profile_settings.php';
    if (file_exists($profileFile)) {
        include_once $profileFile;
        if (function_exists('getStaffProfileImageUrl')) {
            $profileImage = getStaffProfileImageUrl($user_id);
        }
    }
}

require_once __DIR__ . '/module_config.php';

$modules = getFilteredModules($user_role);

$currentPage = basename($_SERVER['PHP_SELF']);

// Per-dashboard module isolation — each page gets only its own section links
$dashboardMap = [
    // ── Executive & Leadership ──
    'director-general.php'  => ['title' => 'Director General',          'icon' => 'fas fa-crown'],
    'ceo.php'               => ['title' => 'CEO',                      'icon' => 'fas fa-crown'],
    'school-principal.php'  => ['title' => 'School Principal',         'icon' => 'fas fa-user-tie'],
    'deputy-principal.php'  => ['title' => 'Deputy Principal',         'icon' => 'fas fa-user-friends'],
    'school-secretary.php'  => ['title' => 'School Secretary',         'icon' => 'fas fa-user-tie'],

    // ── Academic Management ──
    'director-academics.php'=> ['title' => 'Director Academics',       'icon' => 'fas fa-graduation-cap'],
    'academic-registrar.php'=> ['title' => 'Academic Registrar',       'icon' => 'fas fa-clipboard-list'],
    'lecturers.php'         => ['title' => 'Lecturers',                'icon' => 'fas fa-chalkboard-teacher'],
    'senior-lecturers.php'  => ['title' => 'Senior Lecturers',         'icon' => 'fas fa-chalkboard-teacher'],
    'curriculum-management.php' => ['title' => 'Curriculum',           'icon' => 'fas fa-book-open'],
    'timetable.php'         => ['title' => 'Timetable',                'icon' => 'fas fa-calendar-week'],
    'exams-results.php'     => ['title' => 'Exams & Results',          'icon' => 'fas fa-file-alt'],
    'grade-scales.php'      => ['title' => 'Grade Scales & Grading',   'icon' => 'fas fa-sort-amount-up'],
    'academic-calendar.php' => ['title' => 'Academic Calendar',        'icon' => 'fas fa-calendar'],
    'graduation-management.php' => ['title' => 'Graduation',           'icon' => 'fas fa-graduation-cap'],
    'course-registration.php'=> ['title' => 'Course Registration',     'icon' => 'fas fa-clipboard-list'],
    'programs.php'          => ['title' => 'Programs',                 'icon' => 'fas fa-layer-group'],
    'non-teaching-staff.php'=> ['title' => 'Non-Teaching Staff',       'icon' => 'fas fa-users'],
    'research-projects.php' => ['title' => 'Research Projects',        'icon' => 'fas fa-flask'],
    'accreditation.php'     => ['title' => 'Accreditation',            'icon' => 'fas fa-certificate'],
    'quality-assurance.php' => ['title' => 'Quality Assurance',        'icon' => 'fas fa-check-circle'],
    'partnerships.php'      => ['title' => 'Partnerships & Linkages',  'icon' => 'fas fa-handshake'],

    // ── Admissions ──
    'director-admissions.php'=> ['title' => 'Director Admissions',     'icon' => 'fas fa-file-signature'],
    'admission-letters.php' => ['title' => 'Admissions Letters',       'icon' => 'fas fa-file-signature'],
    'intake-planning.php'   => ['title' => 'Intake Planning',          'icon' => 'fas fa-calendar-alt'],

    // ── Finance ──
    'director-finance.php'  => ['title' => 'Director Finance',         'icon' => 'fas fa-chart-line'],
    'school-bursar.php'     => ['title' => 'Bursar',                   'icon' => 'fas fa-money-bill-wave',
        'children' => [
            ['title' => 'Bursar Dashboard',   'route' => 'school-bursar.php?section=overview',            'roles' => '*'],
            ['title' => 'Record Payment',     'route' => 'school-bursar.php?section=record_payment',      'roles' => '*'],
            ['title' => 'Generate Invoice',   'route' => 'school-bursar.php?section=generate_invoice',    'roles' => '*'],
            ['title' => 'Fee Structure',      'route' => 'school-bursar.php?section=fee_structure',       'roles' => '*'],
            ['title' => 'Student Statement',  'route' => 'school-bursar.php?section=student_statement',   'roles' => '*'],
            ['title' => 'Receipt Print',      'route' => 'school-bursar.php?section=receipt_print',       'roles' => '*'],
            ['title' => 'Financial Reports',  'route' => 'school-bursar.php?section=financial_reports',   'roles' => '*'],
            ['title' => 'Budget',             'route' => 'school-bursar.php?section=budget',              'roles' => '*'],
            ['title' => 'Daily Collections',  'route' => 'school-bursar.php?section=daily_collections',   'roles' => '*'],
            ['title' => 'Auto Deductions',    'route' => 'payment-subscriptions.php',                     'roles' => '*'],
        ],
    ],
    'financial-reports.php' => ['title' => 'Financial Reports',        'icon' => 'fas fa-chart-bar'],
    'fee-structure.php'     => ['title' => 'Fee Structure',            'icon' => 'fas fa-money-check-alt'],
    'invoice-generation.php'=> ['title' => 'Invoices',                 'icon' => 'fas fa-file-invoice'],
    'payment-recording.php' => ['title' => 'Payment Recording',        'icon' => 'fas fa-hand-holding-usd'],
    'budget-management.php' => ['title' => 'Budget Management',        'icon' => 'fas fa-chart-pie'],
    'expenditure-tracking.php'=> ['title' => 'Expenditure Tracking',   'icon' => 'fas fa-file-invoice-dollar'],
    'bursar-payroll.php'    => ['title' => 'Payroll',                  'icon' => 'fas fa-money-check'],
    'general-ledger.php'    => ['title' => 'General Ledger',           'icon' => 'fas fa-book'],
    'bank-reconciliation.php'=> ['title' => 'Bank Reconciliation',     'icon' => 'fas fa-university'],
    'student-statements.php'=> ['title' => 'Student Statements',       'icon' => 'fas fa-file-invoice'],
    'payment-subscriptions.php'=> ['title' => 'Auto-Deductions',       'icon' => 'fas fa-sync'],
    'audit-management.php'  => ['title' => 'Audit Management',         'icon' => 'fas fa-clipboard-check'],
    'procurement-oversight.php'=> ['title' => 'Procurement',           'icon' => 'fas fa-shopping-cart'],
    'department-requests.php'=> ['title' => 'Department Requests',     'icon' => 'fas fa-clipboard-list'],
    'ura-reporting.php'     => ['title' => 'URA/Tax Reporting',        'icon' => 'fas fa-file-invoice'],
    'bursar-billing.php'    => ['title' => 'Bursar Billing',           'icon' => 'fas fa-file-invoice'],
    'bursar-ledger.php'     => ['title' => 'Bursar Ledger',            'icon' => 'fas fa-book'],
    'bursar-payments.php'   => ['title' => 'Bursar Payments',          'icon' => 'fas fa-hand-holding-usd'],
    'bursar-tax.php'        => ['title' => 'Bursar Tax',               'icon' => 'fas fa-file-invoice-dollar'],
    'bursar-reports.php'    => ['title' => 'Bursar Reports',           'icon' => 'fas fa-chart-bar'],
    'cost-center-management.php'=> ['title' => 'Cost Centers',         'icon' => 'fas fa-building'],
    'penalty-configurations.php'=> ['title' => 'Penalty Configurations','icon' => 'fas fa-exclamation-triangle'],
    'proof-of-payments.php' => ['title' => 'Proof of Payments',        'icon' => 'fas fa-file-invoice'],
    'donations-management.php'=> ['title' => 'Donations',              'icon' => 'fas fa-hand-holding-usd'],
    'staff_receipt_printing.php' => ['title' => 'Receipt Printing',    'icon' => 'fas fa-print'],

    // ── Human Resources ──
    'hr-manager.php'        => ['title' => 'Human Resources',           'icon' => 'fas fa-users'],
    'staff-directory.php'   => ['title' => 'Staff Directory',          'icon' => 'fas fa-address-book'],
    'staff-attendance.php'  => ['title' => 'Staff Attendance',         'icon' => 'fas fa-clipboard-list'],
    'leave-management.php'  => ['title' => 'Leave Management',         'icon' => 'fas fa-calendar-check'],
    'performance-appraisal.php'=> ['title' => 'Performance Appraisal', 'icon' => 'fas fa-chart-bar'],
    'training-cpd.php'      => ['title' => 'Training & CPD',           'icon' => 'fas fa-chalkboard-teacher'],
    'recruitment.php'       => ['title' => 'Recruitment',              'icon' => 'fas fa-user-plus'],
    'contracts-management.php'=> ['title' => 'Contracts',              'icon' => 'fas fa-file-contract'],
    'staff-disciplinary.php'=> ['title' => 'Staff Disciplinary',       'icon' => 'fas fa-gavel'],
    'onboarding.php'        => ['title' => 'Onboarding & Orientation', 'icon' => 'fas fa-user-check'],
    'resignations.php'      => ['title' => 'Resignations & Exit',      'icon' => 'fas fa-user-times'],
    'duty-rosters.php'      => ['title' => 'Duty Rosters',             'icon' => 'fas fa-calendar-alt'],
    'professional-licenses.php'=> ['title' => 'Professional Licenses', 'icon' => 'fas fa-certificate'],
    'staff_profile_management.php'=> ['title' => 'My Profile',         'icon' => 'fas fa-id-card'],

    // ── Nursing Department ──
    'head-nursing.php'      => ['title' => 'Nursing Department',       'icon' => 'fas fa-user-nurse'],
    'clinical-placement.php'=> ['title' => 'Clinical Placements',      'icon' => 'fas fa-clinic-medical'],
    'lab-practical.php'     => ['title' => 'Lab Practical',            'icon' => 'fas fa-flask'],

    // ── Midwifery Department ──
    'head-midwifery.php'    => ['title' => 'Midwifery Department',     'icon' => 'fas fa-baby'],

    // ── ICT Department ──
    'director-ict.php'      => ['title' => 'ICT Department',           'icon' => 'fas fa-laptop-code'],
    'system-admin.php'      => ['title' => 'System Administration',    'icon' => 'fas fa-cogs'],
    'digital-learning.php'  => ['title' => 'Digital Learning',         'icon' => 'fas fa-laptop'],
    'cybersecurity.php'     => ['title' => 'Cybersecurity',            'icon' => 'fas fa-shield'],
    'ict-policy.php'        => ['title' => 'ICT Policy',               'icon' => 'fas fa-file-alt'],
    'computer_lab.php'      => ['title' => 'Computer Lab',             'icon' => 'fas fa-desktop'],
    'it-support-tickets.php'=> ['title' => 'IT Support Tickets',       'icon' => 'fas fa-ticket-alt'],
    'lab-booking-management.php'=> ['title' => 'Lab Booking',          'icon' => 'fas fa-calendar-check'],

    // ── Library ──
    'school-librarian.php'  => ['title' => 'Library',                  'icon' => 'fas fa-book'],
    'student-library.php'   => ['title' => 'Student Library',          'icon' => 'fas fa-book'],
    'student-library-portal.php'=> ['title' => 'Library Portal',       'icon' => 'fas fa-book-open'],

    // ── Skills Laboratory ──
    'skills-lab.php'        => ['title' => 'Skills Laboratory',        'icon' => 'fas fa-flask'],
    'chemical-inventory.php'=> ['title' => 'Chemical Inventory',       'icon' => 'fas fa-flask'],

    // ── Store & Assets ──
    'storekeeper.php'       => ['title' => 'Store & Assets',           'icon' => 'fas fa-boxes'],
    'inventory-reports.php' => ['title' => 'Inventory Reports',        'icon' => 'fas fa-clipboard-list'],
    'asset-management.php'  => ['title' => 'Asset Management',         'icon' => 'fas fa-building'],

    // ── Security & Transport ──
    'security.php'          => ['title' => 'Security',                 'icon' => 'fas fa-shield-alt'],
    'visitor-access.php'    => ['title' => 'Visitor Access',           'icon' => 'fas fa-door-open'],
    'drivers.php'           => ['title' => 'Fleet Management',         'icon' => 'fas fa-truck'],
    'fuel-trips.php'        => ['title' => 'Fuel & Trip Logs',         'icon' => 'fas fa-gas-pump'],

    // ── Student Services ──
    'matrons.php'           => ['title' => 'Matrons',                  'icon' => 'fas fa-female'],
    'wardens.php'           => ['title' => 'Wardens',                  'icon' => 'fas fa-user-shield'],
    'counseling-welfare.php'=> ['title' => 'Counseling & Welfare',     'icon' => 'fas fa-hand-holding-heart'],
    'meal-accommodation.php'=> ['title' => 'Meals & Accommodation',    'icon' => 'fas fa-utensils'],
    'hostel-management.php' => ['title' => 'Hostel Management',        'icon' => 'fas fa-bed'],
    'student-discipline.php'=> ['title' => 'Student Discipline',       'icon' => 'fas fa-gavel'],
    'student-requests-desk.php'=> ['title' => 'Student Requests Desk', 'icon' => 'fas fa-inbox'],
    'scholarships-sponsorships.php'=> ['title' => 'Scholarships & Sponsorships','icon' => 'fas fa-award'],
    'student-fees.php'      => ['title' => 'Student Fees',             'icon' => 'fas fa-money-bill-wave'],
    'student-results.php'   => ['title' => 'Student Results',          'icon' => 'fas fa-chart-bar'],
    'student-timetable.php' => ['title' => 'Student Timetable',        'icon' => 'fas fa-calendar-alt'],
    'student-course-reg.php'=> ['title' => 'Student Course Registration','icon' => 'fas fa-clipboard-list'],
    'sickbay.php'           => ['title' => 'Sickbay',                  'icon' => 'fas fa-notes-medical',
        'children' => [
            ['title' => 'Dashboard',          'route' => 'sickbay.php?section=dashboard',     'roles' => '*'],
            ['title' => 'Daily Sick Records', 'route' => 'sickbay.php?section=daily-records', 'roles' => '*'],
            ['title' => 'Sickness Directory', 'route' => 'sickbay.php?section=sickness',      'roles' => '*'],
            ['title' => 'Leave Sheet',        'route' => 'sickbay.php?section=leave',         'roles' => '*'],
            ['title' => 'Medicine Stock',     'route' => 'sickbay.php?section=medicine',      'roles' => '*'],
            ['title' => 'Recycle Bin',        'route' => 'sickbay.php?section=recycle-bin',   'roles' => '*'],
            ['title' => 'Health Records',     'route' => 'sickbay.php?section=health-records', 'roles' => '*'],
            ['title' => 'Health Incidents',  'route' => 'sickbay.php?section=health-incidents','roles' => '*'],
            ['title' => 'Audit Trail',        'route' => 'sickbay.php?section=audit',         'roles' => '*'],
            ['title' => 'Settings',           'route' => 'sickbay.php?section=settings',      'roles' => '*'],
        ],
    ],

    // ── Student Management ──
    'student-management.php'=> ['title' => 'Student Management',       'icon' => 'fas fa-users-cog'],
    'student-attendance.php'=> ['title' => 'Student Attendance',       'icon' => 'fas fa-clipboard-check'],
    'student-add.php'       => ['title' => 'Add Student',              'icon' => 'fas fa-user-plus'],
    'student-announcements.php'=> ['title' => 'Announcements',         'icon' => 'fas fa-bullhorn'],
    'student-downloads.php' => ['title' => 'Student Downloads',        'icon' => 'fas fa-download'],
    'student-records.php'   => ['title' => 'Student Records',          'icon' => 'fas fa-folder-open'],

    // ── Communications ──
    'communications.php'    => ['title' => 'Communications',           'icon' => 'fas fa-bullhorn'],
    'news.php'              => ['title' => 'News & Updates',           'icon' => 'fas fa-newspaper'],
    'messaging.php'         => ['title' => 'Messaging',                'icon' => 'fas fa-comments'],
    'notifications.php'     => ['title' => 'Notifications',            'icon' => 'fas fa-bell'],
    'website-pages.php'     => ['title' => 'Website Pages',            'icon' => 'fas fa-globe'],
    'portal-messages.php'   => ['title' => 'Portal Messages',          'icon' => 'fas fa-envelope'],
    'contact-submissions.php'=> ['title' => 'Contact Submissions',     'icon' => 'fas fa-address-card'],
    'institutional-alerts.php'=> ['title' => 'Institutional Alerts',   'icon' => 'fas fa-bell'],
    'volunteer-applications.php'=> ['title' => 'Volunteer Applications','icon' => 'fas fa-hands-helping'],

    // ── Documents & Printing ──
    'document_management.php'=> ['title' => 'Document Management',     'icon' => 'fas fa-folder'],
    'recycle_bin.php'       => ['title' => 'Recycle Bin',              'icon' => 'fas fa-trash'],
    'staff_transcript_generation.php'=> ['title' => 'Transcript Generation','icon' => 'fas fa-file-alt'],
    'print_certificate.php' => ['title' => 'Print Certificate',        'icon' => 'fas fa-file-pdf'],
    'print_transcript.php'  => ['title' => 'Print Transcript',         'icon' => 'fas fa-file-alt'],

    // ── Student Government ──
    'guild-president.php'   => ['title' => 'Guild President',          'icon' => 'fas fa-handshake'],

    // ── Student Self-Service ──
    'student.php'           => ['title' => 'Student Portal',           'icon' => 'fas fa-user-graduate'],
];

if (isset($dashboardMap[$currentPage])) {
    $info = $dashboardMap[$currentPage];
    if (isset($info['children'])) {
        // Merge explicit children (e.g., sickbay, school-bursar sections) into the full module tree
        $customGroup = [
            'title'    => $info['title'],
            'icon'     => $info['icon'],
            'roles'    => '*',
            'children' => $info['children'],
        ];
        // Prepend custom group to the role-filtered module list
        array_unshift($modules, $customGroup);
    }
    // Always keep the full role-filtered module tree so the sidebar shows
    // all accessible category groups, not just current-page isolation.
} else {
    // Pages not in $dashboardMap keep the full role-filtered $modules from module_config
}

// Config: set to true to allow only one parent open at a time
$accordionMode = true;

// Detect current page for active highlighting
$currentDir  = dirname($_SERVER['PHP_SELF']);
?>
<nav class="isnm-sidebar" id="isnmSidebar">
    <div class="sidebar-brand">
        <button class="sidebar-collapse-btn" id="sidebarCollapse" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <img src="../images/school-logo.png" alt="ISNM" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">ISNM</span>
            <span class="brand-sub">Management System</span>
        </div>
    </div>

    <div class="sidebar-user" onclick="<?= $profileClickHandler ?>" style="cursor:pointer" title="Click to update profile">
        <div class="user-avatar-wrap">
            <img src="<?= $profileImage ?>" alt="" class="user-avatar">
            <span class="user-dot"></span>
        </div>
        <div class="user-meta">
            <div class="user-fullname"><?= htmlspecialchars($user_name) ?></div>
            <div class="user-rolename"><?= htmlspecialchars($user_role) ?></div>
        </div>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        <?php if (in_array($currentPage, ['director-admissions.php', 'admission-letters.php', 'intake-planning.php'])): ?>
        <!-- ═══ DIRECTOR ADMISSIONS — PROFESSIONAL SIDEBAR ═══ -->
        <div class="menu-divider"><span><i class="fas fa-file-signature" style="color:#7c3aed;"></i> ADMISSIONS</span></div>
        <div class="menu-group expanded" data-group="dashboard">
            <a href="director-admissions.php" class="child-link <?= basename($_SERVER['PHP_SELF'])==='director-admissions.php' && !isset($_GET['section']) ? 'active' : '' ?>"><span class="menu-icon" style="font-size:0.8rem"><i class="fas fa-chart-pie"></i></span><span class="child-label">Overview</span></a>
        </div>
        <div class="menu-group <?= in_array($currentPage, ['admission-letters.php', 'intake-planning.php']) ? 'expanded' : '' ?>" data-group="applications">
            <div class="menu-group-header" data-target="applications"><span class="menu-icon"><i class="fas fa-file-alt"></i></span><span class="menu-label">Applicants</span><span class="menu-chevron"><i class="fas fa-chevron-down"></i></span></div>
            <div class="menu-children" id="childGroup-applications" style="<?= in_array($currentPage, ['admission-letters.php', 'intake-planning.php']) ? '' : 'max-height:0' ?>">
                <div class="menu-children-inner">
                    <a href="director-admissions.php#applications" class="child-link" data-section="applications"><span class="child-bullet"></span><span class="child-label">Applicant Records</span></a>
                    <a href="admission-letters.php" class="child-link <?= $currentPage==='admission-letters.php' ? 'active' : '' ?>"><span class="child-bullet"></span><span class="child-label">Admission Letters</span></a>
                    <a href="intake-planning.php" class="child-link <?= $currentPage==='intake-planning.php' ? 'active' : '' ?>"><span class="child-bullet"></span><span class="child-label">Intake Planning</span></a>
                </div>
            </div>
        </div>
        <div class="menu-divider"><span><i class="fas fa-clipboard-check" style="color:#059669;"></i> REQUIREMENTS</span></div>
        <div class="menu-group" data-group="requirements">
            <div class="menu-group-header" data-target="requirements"><span class="menu-icon"><i class="fas fa-list-check"></i></span><span class="menu-label">Requirements</span><span class="menu-chevron"><i class="fas fa-chevron-down"></i></span></div>
            <div class="menu-children" id="childGroup-requirements" style="max-height:0">
                <div class="menu-children-inner">
                    <a href="director-admissions.php#requirements" class="child-link" data-section="requirements"><span class="child-bullet"></span><span class="child-label">Requirement Portal</span></a>
                    <a href="director-admissions.php#clearance" class="child-link" data-section="clearance"><span class="child-bullet"></span><span class="child-label">Clearance Overview</span></a>
                </div>
            </div>
        </div>
        <div class="menu-divider"><span><i class="fas fa-chart-bar" style="color:#7c3aed;"></i> REPORTS</span></div>
        <div class="menu-group" data-group="reports">
            <div class="menu-group-header" data-target="reports"><span class="menu-icon"><i class="fas fa-chart-pie"></i></span><span class="menu-label">Reports</span><span class="menu-chevron"><i class="fas fa-chevron-down"></i></span></div>
            <div class="menu-children" id="childGroup-reports" style="max-height:0">
                <div class="menu-children-inner">
                    <a href="director-admissions.php#reports" class="child-link" data-section="reports"><span class="child-bullet"></span><span class="child-label">Admission Reports</span></a>
                    <a href="director-admissions.php?report=clearance" target="_blank" class="child-link"><span class="child-bullet"></span><span class="child-label">Clearance Analytics</span></a>
                </div>
            </div>
        </div>
        <?php elseif ($currentPage === 'director-general.php'): ?>
        <!-- ═══ DIRECTOR GENERAL — EXECUTIVE SIDEBAR ═══ -->
        <div class="menu-divider"><span><i class="fas fa-crown" style="color:#e2b714;"></i> Executive Dashboard</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=overview" class="menu-link dg-nav-item" data-section="executive"><span class="menu-icon"><i class="fas fa-chart-simple"></i></span><span class="menu-label">Institution Overview</span></a>
            <a href="director-general.php?page=departments" class="menu-link dg-nav-item" data-section="departments"><span class="menu-icon"><i class="fas fa-building"></i></span><span class="menu-label">Department Monitoring</span></a>
            <a href="director-general.php?page=performance" class="menu-link dg-nav-item" data-section="performance"><span class="menu-icon"><i class="fas fa-chart-bar"></i></span><span class="menu-label">Director Performance</span></a>
        </div>

        <div class="menu-divider"><span><i class="fas fa-users" style="color:#3b82f6;"></i> People Management</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=staff" class="menu-link dg-nav-item" data-section="staff"><span class="menu-icon"><i class="fas fa-id-badge"></i></span><span class="menu-label">Staff Management</span></a>
            <a href="director-general.php?page=students" class="menu-link dg-nav-item" data-section="student"><span class="menu-icon"><i class="fas fa-user-graduate"></i></span><span class="menu-label">Student Management</span></a>
        </div>

        <div class="menu-divider"><span><i class="fas fa-briefcase" style="color:#059669;"></i> Institution Operations</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=finance" class="menu-link dg-nav-item" data-section="financial"><span class="menu-icon"><i class="fas fa-coins"></i></span><span class="menu-label">Financial Overview</span></a>
            <a href="director-general.php?page=assets" class="menu-link dg-nav-item" data-section="store"><span class="menu-icon"><i class="fas fa-warehouse"></i></span><span class="menu-label">Store &amp; Assets</span></a>
        </div>

        <div class="menu-divider"><span><i class="fas fa-check-circle" style="color:#d97706;"></i> Approvals &amp; Tasks</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=approvals" class="menu-link dg-nav-item" data-section="approvals"><span class="menu-icon"><i class="fas fa-check-double"></i></span><span class="menu-label">Pending Approvals</span></a>
            <a href="director-general.php?page=submissions" class="menu-link dg-nav-item" data-section="services"><span class="menu-icon"><i class="fas fa-inbox"></i></span><span class="menu-label">Pending Submissions</span></a>
            <a href="director-general.php?page=actions" class="menu-link dg-nav-item" data-section="quick"><span class="menu-icon"><i class="fas fa-bolt"></i></span><span class="menu-label">Quick Actions</span></a>
        </div>

        <div class="menu-divider"><span><i class="fas fa-cogs" style="color:#8b5cf6;"></i> System Management</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=users" class="menu-link dg-nav-item" data-section="system-users"><span class="menu-icon"><i class="fas fa-user-shield"></i></span><span class="menu-label">User Management</span></a>
            <a href="director-general.php?page=roles" class="menu-link dg-nav-item" data-section="system-roles"><span class="menu-icon"><i class="fas fa-user-tag"></i></span><span class="menu-label">Role Management</span></a>
            <a href="director-general.php?page=audit" class="menu-link dg-nav-item" data-section="audit"><span class="menu-icon"><i class="fas fa-history"></i></span><span class="menu-label">Audit Logs</span></a>
        </div>

        <div class="menu-divider"><span><i class="fas fa-bullhorn" style="color:#8b5cf6;"></i> Communication</span></div>
        <div class="dg-sidebar-group">
            <a href="director-general.php?page=news" class="menu-link dg-nav-item" data-section="news-management"><span class="menu-icon"><i class="fas fa-newspaper"></i></span><span class="menu-label">News Management</span></a>
            <a href="director-general.php?page=messaging" class="menu-link dg-nav-item" data-section="messaging"><span class="menu-icon"><i class="fas fa-comments"></i></span><span class="menu-label">Staff Messaging</span></a>
            <a href="director-general.php?page=broadcast" class="menu-link dg-nav-item" data-section="broadcast"><span class="menu-icon"><i class="fas fa-bullhorn"></i></span><span class="menu-label">Broadcast Messages</span></a>
            <a href="director-general.php?page=communications" class="menu-link dg-nav-item" data-section="communications"><span class="menu-icon"><i class="fas fa-envelope"></i></span><span class="menu-label">Message History</span></a>
        </div>
        <?php else: ?>
        <!-- ═══ STANDARD SIDEBAR (non-DG) ═══ -->
        <div class="menu-divider"><span><i class="fas fa-th-large" style="color:#3b82f6;"></i> Navigation</span></div>
        <?php foreach ($modules as $parent):
            $parentId = preg_replace('/[^a-z0-9]/', '', strtolower($parent['title']));
            $hasChildren = !empty($parent['children']);
            $hasActiveChild = false;
            if ($hasChildren) {
                foreach ($parent['children'] as $child) {
                    $cr = $child['route'];
                    $hp = strpos($cr, '#');
                    $cmp = $hp !== false ? substr($cr, 0, $hp) : $cr;
                    if (($qp = strpos($cmp, '?')) !== false) $cmp = substr($cmp, 0, $qp);
                    if (basename($cmp) === $currentPage) {
                        $hasActiveChild = true;
                        break;
                    }
                }
            }
            $isStudentMgmt = stripos($parent['title'], 'Student Management') !== false;
        ?>
        <div class="menu-group <?= $hasActiveChild ? 'expanded' : '' ?>" data-group="<?= $parentId ?>">
            <div class="menu-group-header" data-target="<?= $parentId ?>">
                <span class="menu-icon"><i class="<?= $parent['icon'] ?>"></i></span>
                <span class="menu-label"><?= htmlspecialchars($parent['title']) ?></span>
                <?php if ($hasChildren): ?>
                <span class="menu-chevron"><i class="fas fa-chevron-down"></i></span>
                <?php endif; ?>
            </div>
            <?php if ($hasChildren): ?>
            <div class="menu-children" id="childGroup-<?= $parentId ?>" style="<?= $hasActiveChild ? '' : 'max-height:0;' ?>">
                <div class="menu-children-inner">
                    <?php foreach ($parent['children'] as $child):
                        $childRoute = $child['route'];
                        $childHash = '';
                        $childSection = '';
                        $childPath = $childRoute;
                        if (($hashPos = strpos($childRoute, '#')) !== false) {
                            $childHash = substr($childRoute, $hashPos + 1);
                            $childPath = substr($childRoute, 0, $hashPos);
                        }
                        if (($qp = strpos($childPath, '?')) !== false) {
                            parse_str(substr($childPath, $qp + 1), $qparams);
                            $childSection = $qparams['section'] ?? '';
                            $childPath = substr($childPath, 0, $qp);
                        }
                        $childPage = basename($childPath);
                        $isSamePage = ($childPage === $currentPage);
                        $isActive = $isSamePage && (
                            ($childHash !== '' && $childHash === ($_GET['section'] ?? '')) ||
                            ($childSection !== '' && $childSection === ($_GET['section'] ?? '')) ||
                            ($childHash === '' && $childSection === '')
                        );
                        $href = $isSamePage && $childHash ? '#' . $childHash : htmlspecialchars($childRoute);
                    ?>
                    <a href="<?= $href ?>" class="child-link <?= $isActive ? 'active' : '' ?>" <?= $childHash ? 'data-section="'.$childHash.'"' : ($childSection ? 'data-section="'.$childSection.'"' : '') ?> <?= !empty($child['onclick']) ? 'onclick="'.$child['onclick'].';return false;"' : '' ?>>
                        <span class="child-bullet"></span>
                        <span class="child-label"><?= htmlspecialchars($child['title']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        var alertBadge = document.getElementById('alertBadgeSidebar');
        var approvalBadge = document.getElementById('approvalBadgeSidebar');
        if (!alertBadge && !approvalBadge) return;
        function updateBadges() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../ajax/get_counts.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (alertBadge && data.critical_alerts > 0) {
                            alertBadge.textContent = data.critical_alerts;
                            alertBadge.style.display = 'inline';
                        }
                        if (approvalBadge && data.pending_approvals > 0) {
                            approvalBadge.textContent = data.pending_approvals;
                            approvalBadge.style.display = 'inline';
                        }
                    } catch(e) {}
                }
            };
            xhr.onerror = function(){ console.warn('[ISNM] Badge count fetch failed (network).'); };
            xhr.send();
        }
        updateBadges();
        setInterval(updateBadges, 60000);
    })();
    </script>

    <script>
    (function() {
        // Sync URL hash or /director/{page} to sidebar active state
        function syncHash() {
            var sectionId = null;
            var hash = location.hash.replace('#', '');
            if (hash) {
                sectionId = hash;
            } else {
                var match = window.location.pathname.match(/^\/director\/([a-z]+)/);
                if (match) {
                    var pageToSection$1 = {
                        overview:'executive',departments:'departments',performance:'performance',
                        finance:'financial',staff:'staff',students:'student',
                        submissions:'services',approvals:'approvals',assets:'store',
                        communications:'communications',audit:'audit',actions:'quick'
                    };
                    sectionId = pageToSection$1[match[1]] || null;
                }
            }
            if (!sectionId) return;
            var links = document.querySelectorAll('#sidebarMenu .child-link, #sidebarMenu .dg-nav-item');
            for (var i = 0; i < links.length; i++) {
                if (links[i].getAttribute('data-section') === sectionId) {
                    links[i].classList.add('active');
                } else {
                    links[i].classList.remove('active');
                }
            }
        }
        syncHash();
        window.addEventListener('hashchange', syncHash);
    })();
    </script>

    <div class="sidebar-footer">
        <a href="../auth-handler.php?action=logout" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <div class="footer-meta">
            <span>v2.1.0</span>
            <span>&copy; 2026 ISNM</span>
        </div>
    </div>
</nav>

<!-- Mobile overlay + toggle -->
<div class="isnm-overlay" id="isnmOverlay"></div>
<button class="isnm-mobile-toggle" id="isnmMobileToggle" aria-label="Toggle menu">
    <div class="hambox"><span></span><span></span><span></span></div>
</button>

<style>
/* ── Reset & Variables ── */
:root {
    --sidebar-w: 270px;
    --sidebar-bg: #0f172a;
    --sidebar-hover: #1e293b;
    --sidebar-active: #2563eb;
    --sidebar-text: #94a3b8;
    --sidebar-text-active: #ffffff;
    --sidebar-accent: #3b82f6;
    --sidebar-border: #1e293b;
    --sidebar-radius: 8px;
    --sidebar-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Sidebar Base ── */
.isnm-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-w);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    display: flex;
    flex-direction: column;
    z-index: 1050;
    overflow: hidden;
    box-shadow: 0 0 30px rgba(0,0,0,0.3);
    transition: transform var(--sidebar-transition);
}

/* ── Brand ── */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px 14px;
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}
.brand-text { display: flex; flex-direction: column; min-width: 0; }
.brand-name { font-size: 18px; font-weight: 700; color: #fff; line-height: 1.2; }
.brand-sub { font-size: 11px; color: var(--sidebar-text); text-transform: uppercase; letter-spacing: 0.5px; }
.sidebar-collapse-btn {
    background: none; border: none; color: var(--sidebar-text); font-size: 16px;
    cursor: pointer; padding: 4px 6px; border-radius: 6px; display: none;
}
.sidebar-collapse-btn:hover { background: var(--sidebar-hover); color: #fff; }

/* ── User ── */
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.user-avatar-wrap { position: relative; flex-shrink: 0; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); }
.user-dot {
    position: absolute; bottom: -1px; right: -1px;
    width: 10px; height: 10px; border-radius: 50%;
    background: #22c55e; border: 2px solid var(--sidebar-bg);
}
.user-meta { min-width: 0; }
.user-fullname { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-rolename { font-size: 11px; color: var(--sidebar-text); text-transform: capitalize; }

/* ── Scrollable Menu ── */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px 0;
}
.sidebar-menu::-webkit-scrollbar { width: 3px; }
.sidebar-menu::-webkit-scrollbar-track { background: transparent; }
.sidebar-menu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* ── Divider ── */
.menu-divider {
    padding: 16px 20px 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.25);
}
.menu-divider span { display: block; }

/* ── Menu Items ── */
.menu-item { padding: 0 8px; margin-bottom: 1px; }

.menu-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 14px;
    font-weight: 450;
    transition: all var(--sidebar-transition);
    cursor: pointer;
}
.menu-link:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.menu-link.active {
    background: var(--sidebar-active);
    color: #fff;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}
.menu-icon {
    width: 20px;
    text-align: center;
    font-size: 14px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.menu-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Parent Group Header ── */
.menu-group { padding: 0 8px; margin-bottom: 1px; }
.menu-group-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: var(--sidebar-text);
    border-radius: var(--sidebar-radius);
    font-size: 14px;
    font-weight: 450;
    cursor: pointer;
    transition: all var(--sidebar-transition);
    user-select: none;
}
.menu-group-header:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.menu-group.expanded > .menu-group-header {
    color: var(--sidebar-text-active);
    background: rgba(255,255,255,0.04);
}
.menu-chevron {
    font-size: 11px;
    transition: transform var(--sidebar-transition);
    flex-shrink: 0;
}
.menu-group.expanded .menu-chevron {
    transform: rotate(180deg);
}

/* ── Children (animated) ── */
.menu-children {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.menu-children-inner {
    padding: 2px 0 4px 32px;
}
.child-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 400;
    transition: all var(--sidebar-transition);
    position: relative;
}
.child-link:hover {
    color: var(--sidebar-text-active);
    background: rgba(255,255,255,0.05);
}
.child-link.active {
    color: var(--sidebar-text-active);
    background: rgba(59,130,246,0.15);
    font-weight: 500;
}
.child-link.active::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 18px;
    background: var(--sidebar-accent);
    border-radius: 3px;
}
.child-bullet {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.4;
    flex-shrink: 0;
    transition: all var(--sidebar-transition);
}
.child-link.active .child-bullet {
    opacity: 1;
    background: var(--sidebar-accent);
    box-shadow: 0 0 6px rgba(59,130,246,0.5);
}
.child-link:hover .child-bullet {
    opacity: 0.7;
}
.child-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Extra Links ── */
.sidebar-extra {
    padding: 8px;
    border-top: 1px solid var(--sidebar-border);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.extra-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 13px;
    transition: all var(--sidebar-transition);
}
.extra-link:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.extra-link i { width: 18px; text-align: center; font-size: 13px; }

/* ── Footer ── */
.sidebar-footer {
    padding: 10px 12px 14px;
    border-top: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}
.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    color: #f87171;
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 13px;
    font-weight: 500;
    transition: all var(--sidebar-transition);
    border: 1px solid rgba(248,113,113,0.15);
}
.logout-btn:hover {
    background: rgba(248,113,113,0.1);
    border-color: rgba(248,113,113,0.3);
}
.footer-meta {
    display: flex;
    justify-content: space-between;
    padding: 8px 4px 0;
    font-size: 10px;
    color: rgba(255,255,255,0.2);
}

/* ── Mobile Overlay ── */
.isnm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    backdrop-filter: blur(2px);
}

/* ── Premium Mobile Toggle (hamburger → X) ── */
.isnm-mobile-toggle {
    display: none;
    position: fixed;
    top: 14px;
    left: 14px;
    z-index: 1060;
    background: var(--sidebar-bg);
    border: 1px solid var(--sidebar-border);
    border-radius: 12px;
    padding: 0;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    width: 44px;
    height: 44px;
    display: none;
    align-items: center;
    justify-content: center;
}
.isnm-mobile-toggle:hover {
    background: var(--sidebar-hover);
    box-shadow: 0 6px 28px rgba(0,0,0,0.35);
    transform: scale(1.05);
}
.isnm-mobile-toggle:active {
    transform: scale(0.95);
}
.isnm-mobile-toggle .hambox {
    position: relative;
    width: 20px;
    height: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
}
.isnm-mobile-toggle .hambox span {
    display: block;
    width: 20px;
    height: 2.5px;
    background: #fff;
    border-radius: 3px;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
}
.isnm-mobile-toggle.active .hambox { gap: 0; }
.isnm-mobile-toggle.active .hambox span:nth-child(1) {
    transform: rotate(45deg) translateY(1px);
    width: 22px;
}
.isnm-mobile-toggle.active .hambox span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.isnm-mobile-toggle.active .hambox span:nth-child(3) {
    transform: rotate(-45deg) translateY(-1px);
    width: 22px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .isnm-sidebar {
        transform: translateX(-100%);
    }
    .isnm-sidebar.open {
        transform: translateX(0);
    }
    .isnm-mobile-toggle {
        display: flex;
    }
    .isnm-overlay.active {
        display: block;
    }
    .sidebar-collapse-btn { display: none; }
}
@media (min-width: 769px) {
    .isnm-sidebar { transform: translateX(0); }
    .isnm-sidebar.collapsed { width: 64px; }
    .isnm-sidebar.collapsed .brand-text,
    .isnm-sidebar.collapsed .user-meta,
    .isnm-sidebar.collapsed .menu-label,
    .isnm-sidebar.collapsed .menu-chevron,
    .isnm-sidebar.collapsed .menu-divider span,
    .isnm-sidebar.collapsed .menu-children,
    .isnm-sidebar.collapsed .extra-link span,
    .isnm-sidebar.collapsed .logout-btn span,
    .isnm-sidebar.collapsed .footer-meta,
    .isnm-sidebar.collapsed .dg-badge,
    .isnm-sidebar.collapsed .dg-sidebar-group { display: none; }
    .isnm-sidebar.collapsed .sidebar-extra { padding: 4px; align-items: center; }
    .isnm-sidebar.collapsed .menu-group-header,
    .isnm-sidebar.collapsed .menu-link,
    .isnm-sidebar.collapsed .dg-nav-item { justify-content: center; padding: 10px 0; }
    .isnm-sidebar.collapsed .menu-icon,
    .isnm-sidebar.collapsed .extra-link i { width: auto; font-size: 16px; margin: 0; }
    .isnm-sidebar.collapsed .extra-link { justify-content: center; padding: 8px 0; width: 48px; margin: 0 auto; }
    .isnm-sidebar.collapsed .logout-btn { justify-content: center; }
    .isnm-sidebar.collapsed .sidebar-brand { justify-content: center; padding: 18px 8px 14px; }
    .isnm-sidebar.collapsed .sidebar-user { justify-content: center; padding: 14px 8px; }
    .isnm-sidebar.collapsed .brand-logo { margin: 0; }
    .sidebar-collapse-btn { display: block; }
}

/* ── Section Switching ── */
.dashboard-section { display: none; }
.dashboard-section.active { display: block; }

/* ── In-Page Section Tabs ── */
.section-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}
.section-tab {
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.section-tab:hover {
    color: #1e293b;
    background: rgba(37, 99, 235, 0.06);
    border-color: rgba(37, 99, 235, 0.15);
}
.section-tab.active {
    color: #2563eb !important;
    background: rgba(37, 99, 235, 0.1);
    border-color: rgba(37, 99, 235, 0.25);
    font-weight: 600;
}

/* ═══ DG EXECUTIVE SIDEBAR STYLES ═══ */
.dg-sidebar-group {
    padding: 2px 8px;
    margin-bottom: 2px;
}
.dg-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: var(--sidebar-radius);
    font-size: 13px;
    font-weight: 450;
    transition: all var(--sidebar-transition);
    position: relative;
}
.dg-nav-item:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text-active);
}
.dg-nav-item.active {
    background: var(--sidebar-active);
    color: #fff;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}
.dg-nav-item .menu-icon {
    width: 20px;
    text-align: center;
    font-size: 14px;
    flex-shrink: 0;
}
.dg-nav-item .menu-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dg-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 12px;
    background: rgba(255,255,255,0.12);
    color: var(--sidebar-text);
    flex-shrink: 0;
    line-height: 1.6;
}
.dg-badge-warning {
    background: #d97706;
    color: #fff;
}
.dg-badge-danger {
    background: #dc2626;
    color: #fff;
}
.menu-divider span i {
    margin-right: 6px;
    font-size: 12px;
}
</style>

<script>
(function() {
    const SIDEBAR = document.getElementById('isnmSidebar');
    const OVERLAY = document.getElementById('isnmOverlay');
    const MOBILE_TOGGLE = document.getElementById('isnmMobileToggle');
    const ACCORDION_MODE = <?= $accordionMode ? 'true' : 'false' ?>;
    const STORAGE_KEY = 'isnm_sidebar_v2';

    // ── Restore expanded state ──
    function restoreState() {
        try {
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            if (saved.expanded) {
                saved.expanded.forEach(function(id) {
                    const group = document.querySelector('.menu-group[data-group="' + id + '"]');
                    const children = document.getElementById('childGroup-' + id);
                    if (group && children) {
                        group.classList.add('expanded');
                        children.style.maxHeight = children.scrollHeight + 'px';
                    }
                });
            }
        } catch(e) {}
    }
    restoreState();

    // ── Save expanded state ──
    function saveState() {
        var expanded = [];
        document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
            expanded.push(g.dataset.group);
        });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ expanded: expanded }));
        } catch(e) {}
    }

    // ── Toggle children with smooth animation ──
    function toggleGroup(header) {
        var group = header.closest('.menu-group');
        if (!group) return;
        var targetId = header.dataset.target;
        var children = document.getElementById('childGroup-' + targetId);
        if (!children) return;

        var isExpanding = !group.classList.contains('expanded');

        // Accordion: close other groups
        if (isExpanding && ACCORDION_MODE) {
            document.querySelectorAll('.menu-group.expanded').forEach(function(other) {
                if (other !== group) {
                    var otherChildren = document.getElementById('childGroup-' + other.dataset.group);
                    other.classList.remove('expanded');
                    if (otherChildren) otherChildren.style.maxHeight = '0';
                }
            });
        }

        if (isExpanding) {
            group.classList.add('expanded');
            children.style.maxHeight = children.scrollHeight + 'px';
        } else {
            group.classList.remove('expanded');
            children.style.maxHeight = '0';
        }

        saveState();
    }

    // ── Attach click listeners to group headers ──
    document.querySelectorAll('.menu-group-header').forEach(function(header) {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            toggleGroup(this);
        });
    });

    // ── Desktop collapse toggle ──
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        SIDEBAR.classList.toggle('collapsed');
        try {
            localStorage.setItem(STORAGE_KEY + '_collapsed', SIDEBAR.classList.contains('collapsed'));
        } catch(e) {}
    });
    // Restore collapsed state
    try {
        if (localStorage.getItem(STORAGE_KEY + '_collapsed') === 'true' && window.innerWidth > 768) {
            SIDEBAR.classList.add('collapsed');
        }
    } catch(e) {}

    // ── Mobile toggle ──
    if (MOBILE_TOGGLE) {
        MOBILE_TOGGLE.addEventListener('click', function() {
            SIDEBAR.classList.toggle('open');
            OVERLAY.classList.toggle('active');
            this.classList.toggle('active');
            document.body.style.overflow = SIDEBAR.classList.contains('open') ? 'hidden' : '';
        });
    }

    function closeMobile() {
        SIDEBAR.classList.remove('open');
        OVERLAY.classList.remove('active');
        if (MOBILE_TOGGLE) MOBILE_TOGGLE.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (OVERLAY) OVERLAY.addEventListener('click', closeMobile);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && SIDEBAR.classList.contains('open')) closeMobile();
    });

    // ── Close mobile on child link click ──
    document.querySelectorAll('.child-link, .dg-nav-item').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeMobile();
        });
    });

    // ── Re-calculate max-height on window resize (for open groups) ──
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            document.querySelectorAll('.menu-group.expanded').forEach(function(g) {
                var children = document.getElementById('childGroup-' + g.dataset.group);
                if (children) children.style.maxHeight = children.scrollHeight + 'px';
            });
        }, 200);
    });

    // ── Section ↔ page URL mapping ──
    var sectionToPage = (function() {
        var m = {
            executive:     'overview',
            departments:   'departments',
            performance:   'performance',
            financial:     'finance',
            staff:         'staff',
            student:       'students',
            services:      'submissions',
            approvals:     'approvals',
            store:         'assets',
            communications:'communications',
            audit:         'audit',
            quick:         'actions',
            'system-users':'users',
            'system-roles':'roles',
            messaging:     'messaging',
            broadcast:     'broadcast'
        };
        return function(s) { return m[s] || s; };
    })();
    var pageToSection = (function() {
        var m = {
            overview:      'executive',
            departments:   'departments',
            performance:   'performance',
            finance:       'financial',
            staff:         'staff',
            students:      'student',
            submissions:   'services',
            approvals:     'approvals',
            assets:        'store',
            communications:'communications',
            audit:         'audit',
            actions:       'quick',
            users:         'system-users',
            roles:         'system-roles',
            messaging:     'messaging',
            broadcast:     'broadcast'
        };
        return function(p) { return m[p] || p; };
    })();

    // ── Detect active route prefix (/director or /ceo) ──
    function getDgPrefix() {
        if (window.location.pathname.indexOf('/ceo/') === 0) return '/ceo';
        return '/director';
    }

    // ── Section Switching ──
    // Shows the target .dashboard-section, pushes a clean /director/{page} or /ceo/{page} URL.
    function switchToSection(sectionId) {
        if (!sectionId) return;
        document.querySelectorAll('.dashboard-section').forEach(function(s) {
            s.classList.toggle('active', s.dataset.section === sectionId);
        });
        document.querySelectorAll('.child-link[data-section], .dg-nav-item[data-section]').forEach(function(l) {
            l.classList.toggle('active', l.dataset.section === sectionId);
        });
        document.querySelectorAll('.section-tab').forEach(function(t) {
            t.classList.toggle('active', (t.dataset.tab || t.dataset.section) === sectionId);
        });
        location.hash = '#' + sectionId;
    }
    window.switchToSection = switchToSection;

    // Click handler for DG nav items
    document.querySelectorAll('.dg-nav-item[data-section]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            switchToSection(this.dataset.section);
        });
    });

    // Click handler for standard child links (hash-based)
    document.querySelectorAll('.child-link[data-section]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href').charAt(0) === '#') {
                e.preventDefault();
                switchToSection(this.dataset.section);
            }
        });
    });

    // ── popstate: back/forward with clean URLs ──
    window.addEventListener('popstate', function(e) {
        var match = window.location.pathname.match(/^\/(director|ceo)\/([a-z]+)/);
        if (match) {
            var section = pageToSection(match[2]);
            if (section) {
                document.querySelectorAll('.dashboard-section').forEach(function(s) {
                    s.classList.toggle('active', s.dataset.section === section);
                });
                document.querySelectorAll('.child-link[data-section], .dg-nav-item[data-section]').forEach(function(l) {
                    l.classList.toggle('active', l.dataset.section === section);
                });
                document.querySelectorAll('.section-tab').forEach(function(t) {
                    t.classList.toggle('active', (t.dataset.tab || t.dataset.section) === section);
                });
            }
        }
    });

    // ── Keep hashchange as fallback ──
    window.addEventListener('hashchange', function() {
        var hash = location.hash.replace('#', '');
        if (hash) {
            document.querySelectorAll('.child-link[data-section], .dg-nav-item[data-section]').forEach(function(l) {
                l.classList.toggle('active', l.dataset.section === hash);
            });
        }
    });

    // On page load: check URL path for /director/{page} or /ceo/{page}, fall back to hash
    function initSection() {
        var sectionId = null;
        var match = window.location.pathname.match(/^\/(director|ceo)\/([a-z]+)/);
        if (match) sectionId = pageToSection(match[2]);
        if (!sectionId) {
            var hash = window.location.hash.replace('#', '');
            if (hash) sectionId = hash;
        }
        if (sectionId) {
            var safe = sectionId.replace(/"/g, '');
            var target = document.querySelector('.dashboard-section[data-section="' + safe + '"]');
            if (target) {
                document.querySelectorAll('.dashboard-section').forEach(function(s) {
                    s.classList.toggle('active', s.dataset.section === safe);
                });
                document.querySelectorAll('.child-link[data-section], .dg-nav-item[data-section]').forEach(function(l) {
                    l.classList.toggle('active', l.dataset.section === safe);
                });
                document.querySelectorAll('.section-tab').forEach(function(t) {
                    t.classList.toggle('active', (t.dataset.tab || t.dataset.section) === safe);
                });
                document.querySelectorAll('.child-link[data-section="' + safe + '"]').forEach(function(l) {
                    var group = l.closest('.menu-group');
                    if (group && !group.classList.contains('expanded')) {
                        group.classList.add('expanded');
                        var children = document.getElementById('childGroup-' + group.dataset.group);
                        if (children) children.style.maxHeight = children.scrollHeight + 'px';
                    }
                });
            }
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSection);
    } else {
        initSection();
    }
})();
</script>
<?php
$sidebarRendered = true;

// Include universal settings modal
require_once __DIR__ . '/settings_modal.php';
?>
