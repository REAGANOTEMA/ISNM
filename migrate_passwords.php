<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * ISNM PRODUCTION MIGRATION — staff_roles + ALL PASSWORDS
 * ═══════════════════════════════════════════════════════════════
 * 
 * UPLOAD THIS FILE TO YOUR PRODUCTION SERVER AND RUN IT:
 *   https://igangaschoolofnursingandmidwifery.ac.ug/migrate_passwords.php
 * 
 * IT WILL:
 *   1. Create staff_roles table if missing
 *   2. Populate all 40 roles
 *   3. Set all 26 staff passwords
 *   4. Output results
 *   
 * AFTER RUNNING, DELETE THIS FILE FROM THE SERVER.
 * ═══════════════════════════════════════════════════════════════
 */

// Suppress errors for cleaner output
error_reporting(E_ERROR | E_PARSE);

echo "<!DOCTYPE html><html><head><title>ISNM Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e293b;color:#e2e8f0;}";
echo "h1{color:#f59e0b;} .ok{color:#10b981;} .fail{color:#ef4444;} .info{color:#60a5fa;}";
echo "pre{background:#0f172a;padding:12px;border-radius:8px;overflow-x:auto;font-size:12px;}</style></head><body>";
echo "<h1>ISNM Production Migration</h1>";

// ── DATABASE CONNECTION ──
$host = '127.0.0.1';
$port = 3307;
$user = 'igangaschoolofl_staffs_db';
$pass = 'AgKzJjZZnT5q58jCahs8';
$db   = 'igangaschoolofl_staffs_db';

$conn = @new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    // Try without port
    $conn = @new mysqli($host, $user, $pass, $db);
}
if ($conn->connect_error) {
    die("<p class='fail'>FATAL: Cannot connect to database: " . $conn->connect_error . "</p>");
}
$conn->set_charset('utf8mb4');
echo "<p class='ok'>Connected to database successfully.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 1: Create staff_roles table if missing
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 1: Ensuring staff_roles table exists</h2>";

$tableCheck = $conn->query("SHOW TABLES LIKE 'staff_roles'");
if ($tableCheck && $tableCheck->num_rows > 0) {
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
// STEP 2: Populate all roles
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 2: Populating roles</h2>";

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
    [14, 'Senior Lecturers',                'Senior teaching staff',          'staff',     'dashboards/senior-lecturers.php'],
    [15, 'Lecturers',                       'Teaching staff',                 'staff',     'dashboards/lecturers.php'],
    [16, 'Matrons',                         'Hostel management',              'staff',     'dashboards/matrons.php'],
    [17, 'Wardens',                         'Hostel warden',                  'staff',     'dashboards/wardens.php'],
    [18, 'Sickbay',                         'Health services',                'staff',     'dashboards/sickbay.php'],
    [19, 'Drivers',                         'Transport services',             'staff',     'dashboards/drivers.php'],
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
    [31, 'Senior Lecturer',                 'Senior teaching',                'staff',     'dashboards/senior-lecturers.php'],
    [32, 'Lecturer',                        'Teaching',                       'staff',     'dashboards/lecturers.php'],
    [33, 'Security Officer',                'Security',                       'staff',     'dashboards/security.php'],
    [34, 'Driver',                          'Transport',                      'staff',     'dashboards/drivers.php'],
    [35, 'Matron',                          'Hostel',                         'staff',     'dashboards/matrons.php'],
    [36, 'Warden',                          'Hostel warden',                  'staff',     'dashboards/wardens.php'],
    [37, 'Sickbay Nurse',                   'Health services',                'staff',     'dashboards/sickbay.php'],
    [38, 'System Administrator',            'System admin',                   'admin',     'dashboards/director-general.php'],
    [39, 'Computer Lab',                    'Computer lab',                   'staff',     'dashboards/computer_lab.php'],
    [40, 'Skills Lab',                      'Skills lab',                     'staff',     'dashboards/skills-lab.php'],
];

$stmt = $conn->prepare("INSERT INTO staff_roles (id, role_name, role_description, role_level, dashboard_path) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role_name=VALUES(role_name), dashboard_path=VALUES(dashboard_path)");
$inserted = 0;
foreach ($roles as [$id, $name, $desc, $level, $path]) {
    $stmt->bind_param('issss', $id, $name, $desc, $level, $path);
    $stmt->execute();
    $inserted++;
}
$stmt->close();
echo "<p class='ok'>{$inserted} roles configured.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 3: Set all passwords
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 3: Setting all staff passwords</h2>";

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
];

$updated = 0;
$failed = 0;
$stmt = $conn->prepare("UPDATE staff SET password = ?, password_changed = 1, is_first_login = 0, login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE LOWER(email) = ?");

foreach ($credentials as [$email, $plainPassword]) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    $stmt->bind_param('ss', $hash, $email);
    if ($stmt->execute() && $stmt->affected_rows >= 0) {
        echo "<p class='ok'>OK: {$email}</p>";
        $updated++;
    } else {
        echo "<p class='fail'>FAILED: {$email} — " . $stmt->error . "</p>";
        $failed++;
    }
}
$stmt->close();

// ══════════════════════════════════════════════════════════════
// STEP 4: Unlock all accounts
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 4: Unlocking all accounts</h2>";
$conn->query("UPDATE staff SET login_attempts = 0, locked_until = NULL");
echo "<p class='ok'>All accounts unlocked.</p>";

// ══════════════════════════════════════════════════════════════
// STEP 5: Verify
// ══════════════════════════════════════════════════════════════
echo "<h2>Step 5: Verification</h2>";
$verify = $conn->query("SELECT COUNT(*) as c FROM staff WHERE password_changed = 1");
$row = $verify->fetch_assoc();
echo "<p class='info'>Staff with passwords set: {$row['c']}</p>";

$verify2 = $conn->query("SELECT COUNT(*) as c FROM staff_roles");
$row2 = $verify2->fetch_assoc();
echo "<p class='info'>Roles in staff_roles: {$row2['c']}</p>";

$conn->close();

echo "<hr>";
echo "<h1 class='ok'>MIGRATION COMPLETE</h1>";
echo "<p class='info'>Updated: {$updated} passwords | Failed: {$failed} | Roles: {$inserted}</p>";
echo "<p style='color:#f59e0b;font-weight:bold;'>IMPORTANT: Delete this file from the server now!</p>";
echo "<p><a href='staff-login.php' style='color:#60a5fa;'>Go to Login Page →</a></p>";
echo "</body></html>";
