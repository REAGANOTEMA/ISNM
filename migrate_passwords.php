<?php
/**
 * ISNM PRODUCTION SETUP — staff table + staff_roles + ALL STAFF ACCOUNTS
 *
 * UPLOAD TO PRODUCTION AND RUN ONCE:
 *   https://igangaschoolofnursingandmidwifery.ac.ug/migrate_passwords.php
 *
 * DO NOT use this file on localhost — use .env.local override instead.
 * AFTER RUNNING, DELETE THIS FILE FROM THE SERVER.
 */

error_reporting(E_ERROR | E_PARSE);

echo "<!DOCTYPE html><html><head><title>ISNM Setup</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e293b;color:#e2e8f0;}";
echo "h1{color:#f59e0b;} .ok{color:#10b981;} .fail{color:#ef4444;} .info{color:#60a5fa;}";
echo "pre{background:#0f172a;padding:12px;border-radius:8px;overflow-x:auto;font-size:12px;}</style></head><body>";
echo "<h1>ISNM Production Setup</h1>";

// ── BOOTSTRAP from .env via config/database.php ──
require_once __DIR__ . '/config/database.php';

$conn = getStaffConnection();

// Fallback: if .env wasn't uploaded, connect directly with hosting credentials
if (!$conn) {
    echo "<p class='info'>Trying direct connection...</p>";
    $directCreds = [
        ['host'=>'localhost','user'=>'igangaschoolofl_staffs_db','pass'=>'AgKzJjZZnT5q58jCahs8','db'=>'igangaschoolofl_staffs_db','port'=>3306],
        ['host'=>'localhost','user'=>'igangaschoolofl_staffs_db','pass'=>'AgKzJjZZnT5q58jCahs8','db'=>'igangaschoolofl_staffs_db','port'=>3307],
        ['host'=>'127.0.0.1','user'=>'igangaschoolofl_staffs_db','pass'=>'AgKzJjZZnT5q58jCahs8','db'=>'igangaschoolofl_staffs_db','port'=>3306],
        ['host'=>'127.0.0.1','user'=>'igangaschoolofl_staffs_db','pass'=>'AgKzJjZZnT5q58jCahs8','db'=>'igangaschoolofl_staffs_db','port'=>3307],
    ];
    foreach ($directCreds as $c) {
        $conn = @new mysqli($c['host'], $c['user'], $c['pass'], $c['db'], $c['port']);
        if ($conn && !$conn->connect_error) { break; }
        if ($conn) { $conn->close(); $conn = null; }
    }
}
if (!$conn) {
    die("<p class='fail'>FATAL: Cannot connect to staff database. Upload .env first or check credentials.</p>");
}
$conn->set_charset('utf8mb4');
echo "<p class='ok'>Connected to staff database successfully.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 1: Create staff table if missing (brand-new hosting DB)
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 1: Ensuring staff table exists</h2>";

$r = $conn->query("SHOW TABLES LIKE 'staff'");
if ($r && $r->num_rows > 0) {
    echo "<p class='ok'>staff table already exists.</p>";
} else {
    echo "<p class='info'>Creating staff table...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `staff` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `staff_id` VARCHAR(50) DEFAULT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `position` VARCHAR(255) DEFAULT NULL,
        `department` VARCHAR(255) DEFAULT NULL,
        `role_id` INT DEFAULT 0,
        `phone` VARCHAR(50) DEFAULT NULL,
        `profile_picture` VARCHAR(500) DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'Active',
        `hire_date` DATE DEFAULT NULL,
        `login_attempts` INT DEFAULT 0,
        `locked_until` DATETIME DEFAULT NULL,
        `last_login` DATETIME DEFAULT NULL,
        `password_changed` TINYINT(1) DEFAULT 0,
        `is_first_login` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='ok'>staff table created.</p>";
}

// ══════════════════════════════════════════════════════════════
// STEP 2: Insert staff records if table is empty
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 2: Ensuring staff records exist</h2>";

$countR = $conn->query("SELECT COUNT(*) as cnt FROM staff");
$existingCount = $countR ? (int)$countR->fetch_assoc()['cnt'] : 0;
echo "<p class='info'>Current staff records: $existingCount</p>";

if ($existingCount === 0) {
    echo "<p class='info'>Inserting staff records...</p>";

    // We'll insert them with a placeholder password first, then update below
    $placeholder = password_hash('PLACEHOLDER_CHANGE_ME', PASSWORD_BCRYPT);

    $staffList = [
        ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager',  'Computer Lab Manager', 'ICT',               'Computer Lab',      'CLB-001'],
        ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'Director General',    'Director General',     'Executive',         'Director General',  'DG-001'],
        ['ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Chief Executive Officer',         'CEO',                  'Executive',         'CEO',               'CEO-001'],
        ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics', 'Director Academics',   'Academic Affairs',  'Director Academics','DA-001'],
        ['finance@igangaschoolofnursingandmidwifery.ac.ug', 'Director Finance',            'Director Finance',     'Finance',           'Director Finance',  'DF-001'],
        ['principal@igangaschoolofnursingandmidwifery.ac.ug', 'School Principal',           'School Principal',     'Administration',    'School Principal',  'PRIN-001'],
        ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Deputy Principal',       'Deputy Principal',     'Administration',    'Deputy Principal',  'DP-001'],
        ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Academic Registrar', 'Academic Registrar',   'Academic Registrar','Academic Registrar','AR-001'],
        ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'HR Manager',                'HR Manager',           'Human Resources',   'HR Manager',        'HR-001'],
        ['secretary@igangaschoolofnursingandmidwifery.ac.ug', 'School Secretary',           'School Secretary',     'Administration',    'School Secretary',  'SEC-001'],
        ['library@igangaschoolofnursingandmidwifery.ac.ug', 'School Librarian',             'School Librarian',     'Library',           'School Librarian',  'LIB-001'],
        ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Nursing',          'Head of Nursing',      'Nursing',           'Head Nursing',      'NUR-001'],
        ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Midwifery',      'Head of Midwifery',    'Midwifery',         'Head Midwifery',    'MID-001'],
        ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer',     'Senior Lecturer',      'Academic Affairs',  'Senior Lecturer',   'SL-001'],
        ['lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Lecturer',                   'Lecturer',             'Academic Affairs',  'Lecturer',          'LEC-001'],
        ['matron@igangaschoolofnursingandmidwifery.ac.ug', 'Matron',                        'Matron',               'Student Welfare',   'Matron',            'MAT-001'],
        ['warden@igangaschoolofnursingandmidwifery.ac.ug', 'Warden',                        'Warden',               'Student Welfare',   'Warden',            'WAR-001'],
        ['sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'Sickbay Nurse',                'Sickbay Nurse',        'Student Welfare',   'Sickbay',           'SKB-001'],
        ['drivers@igangaschoolofnursingandmidwifery.ac.ug', 'Driver',                       'Driver',               'Transport',         'Driver',            'DRV-001'],
        ['security@igangaschoolofnursingandmidwifery.ac.ug', 'Security Officer',             'Security Officer',     'Security',          'Security',          'SEC-001'],
        ['store@igangaschoolofnursingandmidwifery.ac.ug', 'Storekeeper',                    'Storekeeper',          'Store',             'Storekeeper',       'STO-001'],
        ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'Guild President',        'Guild President',      'Student Government','Guild President',   'G-001'],
        ['admissions@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions',        'Director Admissions',  'Admissions',        'Director Admissions','ADM-001'],
        ['dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT',               'Director ICT',         'ICT',               'Director ICT',      'ICT-001'],
        ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Technician',       'Skills Lab Technician','Skills Laboratory', 'Skills Lab',        'SKL-001'],
        ['bursar@igangaschoolofnursingandmidwifery.ac.ug', 'School Bursar',                  'School Bursar',        'Finance',           'School Bursar',     'BUR-001'],
        ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions & Requirements','Director Admissions','Admissions','Director Admissions','ADM-002'],
        ['directorict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT (Alt)',         'Director ICT',         'ICT',               'Director ICT',      'ICT-002'],
        ['computerlab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager',       'Computer Lab Manager', 'ICT',               'Computer Lab',      'CLB-002'],
        ['skillslab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Manager',           'Skills Lab Manager',   'Skills Laboratory', 'Skills Lab',        'SKL-002'],
    ];

    $ins = $conn->prepare("INSERT IGNORE INTO staff (email, full_name, position, department, role_id, staff_id, password, status, hire_date) VALUES (?, ?, ?, ?, 0, ?, ?, 'Active', CURDATE())");
    $inserted = 0;
    foreach ($staffList as [$email, $name, $pos, $dept, $roleName, $sid]) {
        $ins->bind_param('ssssss', $email, $name, $pos, $dept, $sid, $placeholder);
        if ($ins->execute() && $ins->affected_rows > 0) {
            $inserted++;
        }
    }
    $ins->close();
    echo "<p class='ok'>$inserted staff records inserted.</p>";
} else {
    echo "<p class='info'>Staff table already has data — skipping insert.</p>";
}

// ══════════════════════════════════════════════════════════════
// STEP 3: Create staff_roles table if missing
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 3: Ensuring staff_roles table exists</h2>";

$r2 = $conn->query("SHOW TABLES LIKE 'staff_roles'");
if ($r2 && $r2->num_rows > 0) {
    echo "<p class='ok'>staff_roles table already exists.</p>";
} else {
    echo "<p class='info'>Creating staff_roles table...</p>";
    $conn->query("CREATE TABLE IF NOT EXISTS `staff_roles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `role_name` VARCHAR(100) NOT NULL,
        `role_description` TEXT,
        `role_level` VARCHAR(50) DEFAULT 'staff',
        `dashboard_path` VARCHAR(255) DEFAULT NULL,
        `permissions` JSON DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_role_name` (`role_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='ok'>staff_roles table created.</p>";
}

// ══════════════════════════════════════════════════════════════
// STEP 4: Populate all roles
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 4: Populating roles</h2>";

$roles = [
    [1,  'Director General',                'Executive leadership',           'executive', 'dashboards/director-general.php'],
    [2,  'CEO',                             'Chief Executive Officer',        'executive', 'dashboards/ceo.php'],
    [3,  'Director Academics',              'Academic affairs oversight',     'director',  'dashboards/director-academics.php'],
    [4,  'Director Finance',                'Financial oversight',            'director',  'dashboards/director-finance.php'],
    [5,  'Director ICT',                    'ICT department head',            'director',  'dashboards/director-ict.php'],
    [6,  'School Principal',                'School administration head',     'director',  'dashboards/school-principal.php'],
    [7,  'Deputy Principal',                'Deputy school administration',   'manager',   'dashboards/deputy-principal.php'],
    [8,  'Academic Registrar',              'Student records management',     'manager',   'dashboards/academic-registrar.php'],
    [9,  'HR Manager',                      'Human resources management',     'manager',   'dashboards/hr-manager.php'],
    [10, 'School Secretary',                'Administrative support',         'staff',     'dashboards/school-secretary.php'],
    [11, 'School Librarian',                'Library management',             'staff',     'dashboards/school-librarian.php'],
    [12, 'Head Nursing',                    'Nursing department head',        'manager',   'dashboards/head-nursing.php'],
    [13, 'Head Midwifery',                  'Midwifery department head',      'manager',   'dashboards/head-midwifery.php'],
    [14, 'Senior Lecturer',                 'Senior teaching staff',          'staff',     'dashboards/senior-lecturers.php'],
    [15, 'Lecturer',                        'Teaching staff',                 'staff',     'dashboards/lecturers.php'],
    [16, 'Matron',                          'Hostel management',              'staff',     'dashboards/matrons.php'],
    [17, 'Warden',                          'Hostel warden',                  'staff',     'dashboards/wardens.php'],
    [18, 'Sickbay',                         'Health services',                'staff',     'dashboards/sickbay.php'],
    [19, 'Driver',                          'Transport services',             'staff',     'dashboards/drivers.php'],
    [20, 'Security',                        'Security services',              'staff',     'dashboards/security.php'],
    [21, 'Storekeeper',                     'Store management',               'staff',     'dashboards/storekeeper.php'],
    [22, 'Guild President',                 'Student governance',             'student',   'dashboards/guild-president.php'],
    [23, 'Computer Lab Manager',            'Computer lab management',        'staff',     'dashboards/computer_lab.php'],
    [24, 'School Bursar',                   'Financial operations',           'manager',   'dashboards/school-bursar.php'],
    [25, 'Store Keeper',                    'Store operations',               'staff',     'dashboards/storekeeper.php'],
    [26, 'Director Admissions',             'Admissions management',          'director',  'dashboards/director-admissions.php'],
    [27, 'Bursar',                          'Bursar operations',              'manager',   'dashboards/school-bursar.php'],
    [28, 'Director Admissions & Requirements', 'Admissions and requirements', 'director', 'dashboards/director-admissions.php'],
    [29, 'Head of Nursing',                 'Nursing department',             'manager',   'dashboards/head-nursing.php'],
    [30, 'Head of Midwifery',               'Midwifery department',           'manager',   'dashboards/head-midwifery.php'],
    [31, 'Senior Lecturers',                'Senior teaching',                'staff',     'dashboards/senior-lecturers.php'],
    [32, 'Lecturers',                       'Teaching',                       'staff',     'dashboards/lecturers.php'],
    [33, 'Security Officer',                'Security',                       'staff',     'dashboards/security.php'],
    [34, 'Drivers',                         'Transport',                      'staff',     'dashboards/drivers.php'],
    [35, 'Matrons',                         'Hostel',                         'staff',     'dashboards/matrons.php'],
    [36, 'Wardens',                         'Hostel warden',                  'staff',     'dashboards/wardens.php'],
    [37, 'Sickbay Nurse',                   'Health services',                'staff',     'dashboards/sickbay.php'],
    [38, 'System Administrator',            'System admin',                   'admin',     'dashboards/director-general.php'],
    [39, 'Computer Lab',                    'Computer lab',                   'staff',     'dashboards/computer_lab.php'],
    [40, 'Skills Lab',                      'Skills lab',                     'staff',     'dashboards/skills-lab.php'],
];

$stmt = $conn->prepare("INSERT INTO staff_roles (id, role_name, role_description, role_level, dashboard_path) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role_name=VALUES(role_name), dashboard_path=VALUES(dashboard_path)");
$insertedRoles = 0;
foreach ($roles as [$id, $name, $desc, $level, $path]) {
    $stmt->bind_param('issss', $id, $name, $desc, $level, $path);
    $stmt->execute();
    $insertedRoles++;
}
$stmt->close();
echo "<p class='ok'>{$insertedRoles} roles configured.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 5: Update role_id on staff records based on role_name
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 5: Linking staff to roles</h2>";

$updateRole = $conn->prepare("UPDATE staff s JOIN staff_roles sr ON sr.role_name = ? SET s.role_id = sr.id WHERE s.email = ?");
$roleMapping = [
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Computer Lab Manager'],
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',   'Director General'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug',              'CEO'],
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics'],
    ['finance@igangaschoolofnursingandmidwifery.ac.ug',          'Director Finance'],
    ['principal@igangaschoolofnursingandmidwifery.ac.ug',        'School Principal'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'Deputy Principal'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Academic Registrar'],
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'HR Manager'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug',        'School Secretary'],
    ['library@igangaschoolofnursingandmidwifery.ac.ug',          'School Librarian'],
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'Head of Nursing'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'Head of Midwifery'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'Lecturer'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug',           'Matron'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug',           'Warden'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'Sickbay Nurse'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug',          'Driver'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug',         'Security Officer'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug',            'Storekeeper'],
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'Guild President'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug',       'Director Admissions'],
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug',        'Director ICT'],
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Skills Lab'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug',           'School Bursar'],
    ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   'Director Admissions'],
    ['directorict@igangaschoolofnursingandmidwifery.ac.ug',      'Director ICT'],
    ['computerlab@igangaschoolofnursingandmidwifery.ac.ug',      'Computer Lab Manager'],
    ['skillslab@igangaschoolofnursingandmidwifery.ac.ug',        'Skills Lab'],
];
$roleLinked = 0;
foreach ($roleMapping as [$email, $roleName]) {
    $updateRole->bind_param('ss', $roleName, $email);
    if ($updateRole->execute() && $updateRole->affected_rows > 0) {
        $roleLinked++;
    }
}
$updateRole->close();
echo "<p class='ok'>$roleLinked staff linked to roles.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 6: Set all passwords
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 6: Setting all staff passwords</h2>";

$credentials = [
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Techno123'],
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',   'DorisJoy2026'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug',              'Lovely2God'],
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Stephen123'],
    ['finance@igangaschoolofnursingandmidwifery.ac.ug',          'DorisJoy2026'],
    ['principal@igangaschoolofnursingandmidwifery.ac.ug',        'isnm2026'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'Isnm2026'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God'],
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'Alexis2026'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God'],
    ['library@igangaschoolofnursingandmidwifery.ac.ug',          'isnm2026'],
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'isnm4life'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'Life2save'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'Isnm4life'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug',           'Isnm2026'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug',           'Lovely2God'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'isnm2026'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug',          'isnm4life'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug',         'safty1st'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug',            'Isnm4life'],
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'isnm4life'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug',       '2268926931'],
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God'],
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Lovely2God'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug',           'bursar@isnm'],
    ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   '2268926931'],
    ['directorict@igangaschoolofnursingandmidwifery.ac.ug',      'Lovely2God'],
    ['computerlab@igangaschoolofnursingandmidwifery.ac.ug',      'Techno123'],
    ['skillslab@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God'],
];

$updated = 0;
$failed = 0;
$pwdStmt = $conn->prepare("UPDATE staff SET password = ?, password_changed = 1, is_first_login = 0, login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE LOWER(email) = ?");

foreach ($credentials as [$email, $plainPassword]) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    $pwdStmt->bind_param('ss', $hash, $email);
    if ($pwdStmt->execute() && $pwdStmt->affected_rows >= 0) {
        echo "<p class='ok'>OK: {$email}</p>";
        $updated++;
    } else {
        echo "<p class='fail'>FAILED: {$email} - " . $pwdStmt->error . "</p>";
        $failed++;
    }
}
$pwdStmt->close();

// ══════════════════════════════════════════════════════════════
// STEP 7: Unlock all accounts
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 7: Unlocking all accounts</h2>";
$conn->query("UPDATE staff SET login_attempts = 0, locked_until = NULL");
echo "<p class='ok'>All accounts unlocked.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 8: Verify
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 8: Verification</h2>";
$v1 = $conn->query("SELECT COUNT(*) as c FROM staff");
$row1 = $v1->fetch_assoc();
echo "<p class='info'>Staff records: {$row1['c']}</p>";

$v2 = $conn->query("SELECT COUNT(*) as c FROM staff WHERE password_changed = 1");
$row2 = $v2->fetch_assoc();
echo "<p class='info'>Staff with passwords set: {$row2['c']}</p>";

$v3 = $conn->query("SELECT COUNT(*) as c FROM staff WHERE role_id > 0");
$row3 = $v3->fetch_assoc();
echo "<p class='info'>Staff with role linked: {$row3['c']}</p>";

$v4 = $conn->query("SELECT COUNT(*) as c FROM staff_roles");
$row4 = $v4->fetch_assoc();
echo "<p class='info'>Roles in staff_roles: {$row4['c']}</p>";

$conn->close();

$allOk = ($row2['c'] > 0 && $row4['c'] > 0);
echo "<hr>";
echo "<h1 class='" . ($allOk ? "ok" : "fail") . "'>" . ($allOk ? "SETUP COMPLETE" : "SETUP PARTIAL - CHECK ERRORS ABOVE") . "</h1>";
echo "<p class='info'>Passwords set: {$updated} | Failed: {$failed} | Roles: {$insertedRoles}</p>";
echo "<p style='color:#f59e0b;font-weight:bold;'>IMPORTANT: Delete this file from the server now!</p>";
echo "<p><a href='staff-login.php' style='color:#60a5fa;'>Go to Login Page →</a></p>";
echo "</body></html>";
