<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth-service.php';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>ISNM — Staff Database Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,'Segoe UI',sans-serif;background:#f1f5f9;padding:20px;color:#1e293b}
.container{max-width:800px;margin:0 auto}
h1{font-size:1.5rem;margin-bottom:4px;color:#0f172a}
.sub{color:#64748b;margin-bottom:24px;font-size:0.9rem}
.card{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:16px;overflow:hidden}
.card-header{padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:20px}
.step{margin-bottom:12px;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0;display:flex;align-items:flex-start;gap:12px}
.step .icon{flex-shrink:0;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700}
.step.done .icon{background:#dcfce7;color:#16a34a}
.step.done{border-color:#bbf7d0;background:#f0fdf4}
.step.fail .icon{background:#fef2f2;color:#dc2626}
.step.fail{border-color:#fecaca;background:#fef2f2}
.step.pending .icon{background:#f1f5f9;color:#94a3b8}
.step.pending{border-color:#e2e8f0}
.step .info{flex:1}
.step .info .title{font-weight:600;font-size:0.9rem}
.step .info .detail{font-size:0.8rem;color:#64748b;margin-top:2px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 24px;border-radius:8px;border:none;font-weight:600;font-size:0.9rem;cursor:pointer;text-decoration:none;transition:all .15s}
.btn-primary{background:#2563eb;color:#fff}
.btn-primary:hover{background:#1d4ed8}
.btn-primary:disabled{opacity:.5;cursor:not-allowed}
.btn-success{background:#16a34a;color:#fff}
.btn-outline{background:transparent;border:1px solid #cbd5e1;color:#475569}
.btn-outline:hover{background:#f8fafc}
.btn-group{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}
#output{font-family:'Courier New',monospace;font-size:0.8rem;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;max-height:400px;overflow:auto;white-space:pre-wrap;display:none;margin-top:16px}
.db-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}
.db-item{background:#f8fafc;padding:10px 14px;border-radius:6px;border:1px solid #e2e8f0}
.db-item .name{font-weight:600;font-size:0.85rem}
.db-item .status{font-size:0.8rem;margin-top:2px}
.db-item .status.ok{color:#16a34a}
.db-item .status.ko{color:#dc2626}
</style>
</head>
<body>
<div class="container">
<h1>ISNM — Staff Database Setup</h1>
<p class="sub">Creates required tables and seeds staff accounts for authentication &amp; dashboard access.</p>

<?php
$action = $_POST['action'] ?? '';
$results = [];

function logMsg(&$output, $msg) { $output[] = $msg; }

function runSetup(&$output) {
    $auth = new AuthenticationService();

    // ── Step 1: Check all DB connections ──
    logMsg($output, "=== Checking database connections ===");
    $dbs = [
        'Staff'    => getStaffConnection(),
        'Students' => getStudentsConnection(),
        'Website'  => getWebsiteConnection(),
        'ICT'      => getICTConnection(),
    ];
    foreach ($dbs as $name => $conn) {
        if ($conn) {
            logMsg($output, "  ✓ $name database: CONNECTED");
        } else {
            logMsg($output, "  ✗ $name database: FAILED");
        }
    }

    $conn = $dbs['Staff'];
    if (!$conn) {
        logMsg($output, "ERROR: Cannot connect to staff database. Aborting.");
        return ['success' => false, 'output' => $output, 'dbs' => $dbs, 'staff_roles_created' => false, 'staff_created' => false, 'staff_roles_seeded' => false, 'staff_seeded' => false, 'departments_created' => false, 'departments_seeded' => false, 'staff_count' => 0, 'role_count' => 0, 'dept_count' => 0];
    }

    // ── Step 2: Create staff_roles table ──
    logMsg($output, "\n=== Creating staff_roles table ===");
    $staffRolesCreated = false;
    $r1 = $conn->query("CREATE TABLE IF NOT EXISTS `staff_roles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `role_name` varchar(100) NOT NULL,
        `role_description` text DEFAULT NULL,
        `role_level` int(11) DEFAULT 5,
        `dashboard_path` varchar(255) DEFAULT NULL,
        `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_name` (`role_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    if ($r1) {
        logMsg($output, "  ✓ staff_roles table ready");
        $staffRolesCreated = true;
    } else {
        logMsg($output, "  ✗ Error: " . $conn->error);
    }

    // ── Step 3: Seed staff_roles ──
    logMsg($output, "\n=== Seeding staff_roles ===");
    $rolesSeeded = false;
    $roleCount = 0;
    if ($staffRolesCreated) {
        $roles = [
            ['Director General',                   1, 'dashboards/director-general.php'],
            ['CEO',                                1, 'dashboards/ceo.php'],
            ['Director Academics',                 2, 'dashboards/director-academics.php'],
            ['Director Finance',                   2, 'dashboards/director-finance.php'],
            ['Director ICT',                       2, 'dashboards/director-ict.php'],
            ['Director Admissions',                2, 'dashboards/director-admissions.php'],
            ['School Principal',                   2, 'dashboards/school-principal.php'],
            ['Deputy Principal',                   3, 'dashboards/deputy-principal.php'],
            ['Academic Registrar',                 3, 'dashboards/academic-registrar.php'],
            ['School Bursar',                      3, 'dashboards/school-bursar.php'],
            ['School Secretary',                   4, 'dashboards/school-secretary.php'],
            ['HR Manager',                         3, 'dashboards/hr-manager.php'],
            ['School Librarian',                   4, 'dashboards/school-librarian.php'],
            ['Head of Nursing',                    3, 'dashboards/head-nursing.php'],
            ['Head of Midwifery',                  3, 'dashboards/head-midwifery.php'],
            ['Senior Lecturer',                    4, 'dashboards/senior-lecturers.php'],
            ['Lecturer',                           5, 'dashboards/lecturers.php'],
            ['Security Officer',                   5, 'dashboards/security.php'],
            ['Storekeeper',                        5, 'dashboards/storekeeper.php'],
            ['Driver',                             6, 'dashboards/drivers.php'],
            ['Matron',                             4, 'dashboards/matrons.php'],
            ['Warden',                             5, 'dashboards/wardens.php'],
            ['Guild President',                    4, 'dashboards/guild-president.php'],
            ['Sickbay Nurse',                      5, 'dashboards/sickbay.php'],
            ['System Administrator',               1, 'dashboards/system-admin.php'],
            ['Computer Lab Manager',               4, 'dashboards/computer_lab.php'],
            ['Skills Lab Manager',                 4, 'dashboards/skills-lab.php'],
            ['Skills Lab Technician',              5, 'dashboards/skills-lab.php'],
        ];
        $insRole = $conn->prepare("INSERT IGNORE INTO staff_roles (role_name, role_level, dashboard_path) VALUES (?, ?, ?)");
        if ($insRole) {
            foreach ($roles as $r) {
                $insRole->bind_param('sis', $r[0], $r[1], $r[2]);
                if ($insRole->execute()) {
                    if ($conn->affected_rows > 0) $roleCount++;
                }
            }
            $insRole->close();
            $rolesSeeded = true;
            logMsg($output, "  ✓ $roleCount roles inserted");
        } else {
            logMsg($output, "  ✗ Prepare error: " . $conn->error);
        }
    }

    // ── Step 4: Create staff table ──
    logMsg($output, "\n=== Creating staff table ===");
    $staffCreated = false;
    $r2 = $conn->query("CREATE TABLE IF NOT EXISTS `staff` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` varchar(20) DEFAULT NULL,
        `full_name` varchar(150) NOT NULL,
        `email` varchar(150) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `date_of_birth` date DEFAULT NULL,
        `gender` enum('Male','Female','Other') DEFAULT NULL,
        `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT 'Single',
        `nationality` varchar(100) DEFAULT 'Ugandan',
        `religion` varchar(100) DEFAULT NULL,
        `nin` varchar(20) DEFAULT NULL,
        `password` varchar(255) NOT NULL,
        `role_id` int(11) DEFAULT NULL,
        `position` varchar(150) DEFAULT NULL,
        `department` varchar(150) DEFAULT NULL,
        `status` varchar(20) DEFAULT 'Active',
        `hire_date` date DEFAULT NULL,
        `last_login` datetime DEFAULT NULL,
        `login_attempts` int(11) DEFAULT 0,
        `locked_until` datetime DEFAULT NULL,
        `is_first_login` tinyint(1) DEFAULT 0,
        `password_changed` tinyint(1) DEFAULT 1,
        `profile_photo` varchar(255) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `next_of_kin_name` varchar(150) DEFAULT NULL,
        `next_of_kin_phone` varchar(20) DEFAULT NULL,
        `next_of_kin_relationship` varchar(50) DEFAULT NULL,
        `next_of_kin_address` text DEFAULT NULL,
        `emergency_contact_name` varchar(150) DEFAULT NULL,
        `emergency_contact_phone` varchar(20) DEFAULT NULL,
        `highest_qualification` varchar(200) DEFAULT NULL,
        `year_of_experience` int(11) DEFAULT 0,
        `staff_category` enum('teaching','non-teaching','clinical','administrative') DEFAULT 'non-teaching',
        `contract_end_date` date DEFAULT NULL,
        `resignation_date` date DEFAULT NULL,
        `resignation_reason` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        KEY `role_id` (`role_id`),
        KEY `idx_staff_email` (`email`),
        KEY `idx_staff_status` (`status`),
        KEY `idx_staff_role_status` (`role_id`,`status`),
        KEY `idx_staff_nin` (`nin`),
        KEY `idx_staff_gender` (`gender`),
        KEY `idx_staff_category` (`staff_category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    if ($r2) {
        logMsg($output, "  ✓ staff table ready");
        $staffCreated = true;
    } else {
        logMsg($output, "  ✗ Error: " . $conn->error);
    }

    // ── Step 5: Seed staff accounts ──
    logMsg($output, "\n=== Seeding staff accounts ===");
    $staffSeeded = false;
    $staffCount = 0;
    if ($staffCreated) {
        $staffData = [
            ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'Director General',        'DorisJoy2026', 'Director General',   'DG-001',  'Director General',    'Executive'],
            ['ceo@igangaschoolofnursingandmidwifery.ac.ug',              'Chief Executive Officer', 'Lovely2God',   'CEO',                'CEO-001', 'CEO',                 'Executive'],
            ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics',      'Stephen123',   'Director Academics', 'DA-001',  'Director Academics',  'Academic Affairs'],
            ['finance@igangaschoolofnursingandmidwifery.ac.ug',          'Director Finance',         'DorisJoy2026', 'Director Finance',   'DF-001',  'Director Finance',    'Finance'],
            ['principal@igangaschoolofnursingandmidwifery.ac.ug',        'School Principal',        'isnm2026',     'School Principal',   'PRIN-001','School Principal',    'Administration'],
            ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'Deputy Principal',        'Isnm2026',     'Deputy Principal',   'DP-001',  'Deputy Principal',    'Administration'],
            ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Academic Registrar',      'Lovely2God',   'Academic Registrar', 'AR-001',  'Academic Registrar',  'Academic Registrar'],
            ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'HR Manager',              'Alexis2026',   'HR Manager',         'HR-001',  'HR Manager',          'Human Resources'],
            ['secretary@igangaschoolofnursingandmidwifery.ac.ug',        'School Secretary',        'Lovely2God',   'School Secretary',   'SEC-001', 'School Secretary',    'Administration'],
            ['library@igangaschoolofnursingandmidwifery.ac.ug',          'School Librarian',        'isnm2026',     'School Librarian',   'LIB-001', 'School Librarian',    'Library'],
            ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'Head of Nursing',         'isnm4life',    'Head of Nursing',    'NUR-001', 'Head of Nursing',     'Nursing'],
            ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'Head of Midwifery',       'Life2save',    'Head of Midwifery',  'MID-001', 'Head of Midwifery',   'Midwifery'],
            ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer',        'isnm2026',     'Senior Lecturer',    'SL-001',  'Senior Lecturer',     'Academic Affairs'],
            ['lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'Lecturer',                'Isnm4life',    'Lecturer',           'LEC-001', 'Lecturer',            'Academic Affairs'],
            ['matron@igangaschoolofnursingandmidwifery.ac.ug',           'Matron',                  'Isnm2026',     'Matron',             'MAT-001', 'Matron',              'Student Welfare'],
            ['warden@igangaschoolofnursingandmidwifery.ac.ug',           'Warden',                  'Lovely2God',   'Warden',             'WAR-001', 'Warden',              'Student Welfare'],
            ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'Sickbay Nurse',            'isnm2026',    'Sickbay Nurse',      'SKB-001', 'Sickbay Nurse',       'Student Welfare'],
            ['drivers@igangaschoolofnursingandmidwifery.ac.ug',          'Driver',                   'isnm4life',   'Driver',             'DRV-001', 'Driver',              'Transport'],
            ['security@igangaschoolofnursingandmidwifery.ac.ug',         'Security Officer',         'safty1st',   'Security Officer',   'SEC-001', 'Security Officer',    'Security'],
            ['store@igangaschoolofnursingandmidwifery.ac.ug',            'Storekeeper',              'Isnm4life',   'Storekeeper',        'STO-001', 'Storekeeper',         'Store'],
            ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'Guild President',          'isnm4life',   'Guild President',    'G-001',   'Guild President',     'Student Government'],
            ['admissions@igangaschoolofnursingandmidwifery.ac.ug',      'Director Admissions',      '2268926931',   'Director Admissions', 'ADM-001', 'Director Admissions', 'Admissions'],
            ['dannybict@igangaschoolofnursingandmidwifery.ac.ug',       'Director ICT',             'Lovely2God',   'Director ICT',       'ICT-001', 'Director ICT',        'ICT'],
            ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Skills Lab Manager',       'Lovely2God',   'Skills Lab Manager', 'SKL-001', 'Skills Lab Manager',  'Skills Laboratory'],
            ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug',     'Computer Lab Manager',     'Techno123',    'Computer Lab Manager','CLB-001','Computer Lab Manager','ICT'],
        ];

        $roleMap = [];
        $rr = $conn->query("SELECT id, role_name FROM staff_roles");
        while ($rw = $rr->fetch_assoc()) {
            $roleMap[strtolower(trim($rw['role_name']))] = $rw['id'];
        }

        $ins = $conn->prepare("INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status, is_first_login, password_changed, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', 0, 1, CURDATE())");
        if ($ins) {
            foreach ($staffData as $s) {
                $roleKey = strtolower(trim($s[3]));
                $rid = $roleMap[$roleKey] ?? null;
                if (!$rid) {
                    logMsg($output, "  ✗ No role_id for '{$s[3]}' — skipping {$s[0]}");
                    continue;
                }
                $hash = password_hash($s[2], PASSWORD_BCRYPT);
                $ins->bind_param('ssssssi', $s[4], $s[1], $s[0], $hash, $s[5], $s[6], $rid);
                if ($ins->execute()) {
                    if ($conn->affected_rows > 0) {
                        $staffCount++;
                        logMsg($output, "  ✓ {$s[0]} → {$s[3]}");
                    }
                }
            }
            $ins->close();
            $staffSeeded = true;
            logMsg($output, "  ✓ $staffCount staff accounts inserted (others already existed)");
        }
    }

    // ── Step 6: Create staff_departments table ──
    logMsg($output, "\n=== Creating staff_departments table ===");
    $deptCreated = false;
    $r3 = $conn->query("CREATE TABLE IF NOT EXISTS `staff_departments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `department_name` varchar(150) NOT NULL,
        `department_code` varchar(20) DEFAULT NULL,
        `department_level` int(11) DEFAULT 0,
        `description` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `department_name` (`department_name`),
        UNIQUE KEY `department_code` (`department_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    if ($r3) {
        logMsg($output, "  ✓ staff_departments table ready");
        $deptCreated = true;
    } else {
        logMsg($output, "  ✗ Error: " . $conn->error);
    }

    // ── Step 7: Seed departments ──
    logMsg($output, "\n=== Seeding departments ===");
    $deptCount = 0;
    if ($deptCreated) {
        $departments = [
            ['Executive',           'EXEC', 1],
            ['Administration',      'ADMIN', 2],
            ['Academic Affairs',    'ACAD',  2],
            ['Finance',             'FIN',   3],
            ['Human Resources',     'HR',    3],
            ['Admissions',          'ADM',   3],
            ['Nursing',             'NUR',   3],
            ['Midwifery',           'MID',   3],
            ['ICT',                 'ICT',   3],
            ['Library',             'LIB',   4],
            ['Student Welfare',     'WELF',  4],
            ['Security',            'SEC',   5],
            ['Transport',           'TRANS', 5],
            ['Store',               'STOR',  5],
            ['Skills Laboratory',   'SKILL', 4],
            ['Academic Registrar',  'REG',   3],
            ['Student Government',  'GOV',   5],
            ['Clinical',            'CLIN',  4],
        ];
        $insDept = $conn->prepare("INSERT IGNORE INTO staff_departments (department_name, department_code, department_level) VALUES (?, ?, ?)");
        if ($insDept) {
            foreach ($departments as $d) {
                $insDept->bind_param('ssi', $d[0], $d[1], $d[2]);
                if ($insDept->execute()) {
                    if ($conn->affected_rows > 0) $deptCount++;
                }
            }
            $insDept->close();
            logMsg($output, "  ✓ $deptCount departments inserted");
        }
    }

    $totalRoles = $conn->query("SELECT COUNT(*) c FROM staff_roles")->fetch_assoc()['c'] ?? 0;
    $totalStaff = $conn->query("SELECT COUNT(*) c FROM staff")->fetch_assoc()['c'] ?? 0;
    $totalDepts = $conn->query("SELECT COUNT(*) c FROM staff_departments")->fetch_assoc()['c'] ?? 0;

    logMsg($output, "\n=== SETUP COMPLETE ===");
    logMsg($output, "  staff_roles:    $totalRoles roles");
    logMsg($output, "  staff:          $totalStaff accounts");
    logMsg($output, "  departments:    $totalDepts departments");

    return [
        'success' => true,
        'output' => $output,
        'dbs' => $dbs,
        'staff_roles_created' => $staffRolesCreated,
        'staff_created' => $staffCreated,
        'staff_roles_seeded' => $rolesSeeded,
        'staff_seeded' => $staffSeeded,
        'departments_created' => $deptCreated,
        'departments_seeded' => $deptCreated,
        'staff_count' => $totalStaff,
        'role_count' => $totalRoles,
        'dept_count' => $totalDepts,
    ];
}

if ($action === 'run_setup') {
    $output = [];
    $results = runSetup($output);
}

// Show current status
$staffConn = getStaffConnection();
$staffTables = [];
$totalRoles = 0;
$totalStaff = 0;
$totalDepts = 0;
$errors = [];
if ($staffConn) {
    $tables = $staffConn->query("SHOW TABLES");
    if ($tables) {
        while ($t = $tables->fetch_array()) {
            $staffTables[] = $t[0];
        }
    }
    $rr = $staffConn->query("SELECT COUNT(*) c FROM staff_roles");
    if ($rr) $totalRoles = $rr->fetch_assoc()['c'] ?? 0;
    $sr = $staffConn->query("SELECT COUNT(*) c FROM staff");
    if ($sr) $totalStaff = $sr->fetch_assoc()['c'] ?? 0;
    $dr = $staffConn->query("SELECT COUNT(*) c FROM staff_departments");
    if ($dr) $totalDepts = $dr->fetch_assoc()['c'] ?? 0;
}

$hasStaffRoles = in_array('staff_roles', $staffTables);
$hasStaff = in_array('staff', $staffTables);
$hasDepts = in_array('staff_departments', $staffTables);
$needsSetup = !$hasStaff || !$hasStaffRoles || $totalStaff === 0;
?>

<!-- DB Status Overview -->
<div class="card">
    <div class="card-header">
        <span>Database Connections</span>
    </div>
    <div class="card-body">
        <div class="db-grid">
            <?php foreach (['Staff','Students','Website','ICT'] as $db): 
                $con = $db === 'Staff' ? getStaffConnection() : ($db === 'Students' ? getStudentsConnection() : ($db === 'Website' ? getWebsiteConnection() : getICTConnection()));
            ?>
            <div class="db-item">
                <div class="name"><?= $db ?></div>
                <div class="status <?= $con ? 'ok' : 'ko' ?>"><?= $con ? 'Connected' : 'Not connected' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tables Status -->
<div class="card">
    <div class="card-header">
        <span>Staff Database Tables</span>
    </div>
    <div class="card-body">
        <div class="step <?= $hasStaffRoles ? 'done' : 'fail' ?>">
            <div class="icon"><?= $hasStaffRoles ? '✓' : '✗' ?></div>
            <div class="info">
                <div class="title">staff_roles</div>
                <div class="detail"><?= $totalRoles ?> role<?= $totalRoles !== 1 ? 's' : '' ?> defined</div>
            </div>
        </div>
        <div class="step <?= $hasStaff ? 'done' : 'fail' ?>">
            <div class="icon"><?= $hasStaff ? '✓' : '✗' ?></div>
            <div class="info">
                <div class="title">staff</div>
                <div class="detail"><?= $totalStaff ?> account<?= $totalStaff !== 1 ? 's' : '' ?> registered</div>
            </div>
        </div>
        <div class="step <?= $hasDepts ? 'done' : 'fail' ?>">
            <div class="icon"><?= $hasDepts ? '✓' : '✗' ?></div>
            <div class="info">
                <div class="title">staff_departments</div>
                <div class="detail"><?= $totalDepts ?> department<?= $totalDepts !== 1 ? 's' : '' ?> defined</div>
            </div>
        </div>
    </div>
</div>

<!-- Login Credentials -->
<div class="card">
    <div class="card-header">
        <span>Staff Login Credentials</span>
    </div>
    <div class="card-body" style="font-size:0.85rem">
        <?php if ($hasStaff && $totalStaff > 0): 
            $staffList = $staffConn->query("SELECT s.email, s.full_name, sr.role_name FROM staff s JOIN staff_roles sr ON s.role_id = sr.id ORDER BY sr.role_level, s.full_name");
        ?>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="background:#f1f5f9"><th style="text-align:left;padding:6px 8px">Email</th><th style="text-align:left;padding:6px 8px">Name</th><th style="text-align:left;padding:6px 8px">Role</th><th style="text-align:left;padding:6px 8px">Password</th></tr></thead>
            <tbody>
            <?php while ($s = $staffList->fetch_assoc()): ?>
            <tr><td style="padding:4px 8px;border-top:1px solid #e2e8f0"><?= htmlspecialchars($s['email']) ?></td>
                <td style="padding:4px 8px;border-top:1px solid #e2e8f0"><?= htmlspecialchars($s['full_name']) ?></td>
                <td style="padding:4px 8px;border-top:1px solid #e2e8f0"><?= htmlspecialchars($s['role_name']) ?></td>
                <td style="padding:4px 8px;border-top:1px solid #e2e8f0;font-family:monospace"><?php 
                    $pwMap = [
                        'directorgeneral' => 'DorisJoy2026', 'ceo' => 'Lovely2God', 'directoracademic' => 'Stephen123',
                        'finance' => 'DorisJoy2026', 'principal' => 'isnm2026', 'dep-principal' => 'Isnm2026',
                        'academicregistrar' => 'Lovely2God', 'hr-manager' => 'Alexis2026', 'secretary' => 'Lovely2God',
                        'library' => 'isnm2026', 'nursing-dep' => 'isnm4life', 'midwifery-dep' => 'Life2save',
                        'senior-lecturers' => 'isnm2026', 'lecturers' => 'Isnm4life', 'matron' => 'Isnm2026',
                        'warden' => 'Lovely2God', 'sickbay' => 'isnm2026', 'drivers' => 'isnm4life',
                        'security' => 'safty1st', 'store' => 'Isnm4life', 'guildpresident' => 'isnm4life',
                        'admissions' => '2268926931', 'dannybict' => 'Lovely2God', 'skills-lab' => 'Lovely2God',
                        'computer-lab' => 'Techno123',
                    ];
                    $local = substr($s['email'], 0, strpos($s['email'], '@'));
                    echo htmlspecialchars($pwMap[$local] ?? '—');
                ?></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#64748b">No staff accounts yet. Run setup below to create them.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Run Setup -->
<div class="card">
    <div class="card-header">
        <span>Run Setup</span>
    </div>
    <div class="card-body">
        <p style="font-size:0.9rem;color:#475569;margin-bottom:16px">
            Creates the <strong>staff</strong>, <strong>staff_roles</strong>, and <strong>staff_departments</strong> tables,
            then seeds all 28 roles and 25 staff accounts with their individual passwords.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="run_setup">
            <button type="submit" class="btn btn-primary" <?= !$staffConn ? 'disabled' : '' ?>>
                <?= $needsSetup ? 'Run Full Setup' : 'Re-run Setup (safe — uses INSERT IGNORE)' ?>
            </button>
        </form>
        <?php if (isset($results['output'])): ?>
        <pre id="output" style="display:block"><?php foreach ($results['output'] as $l) echo htmlspecialchars($l) . "\n"; ?></pre>
        <?php endif; ?>
        <?php if (isset($results['success']) && $results['success']): ?>
        <div style="background:#dcfce7;color:#16a34a;padding:12px 16px;border-radius:8px;margin-top:16px;font-weight:600">
            ✓ Setup complete! You can now log in at <a href="staff-login.php" style="color:#2563eb">staff-login.php</a>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
</body>
</html>
