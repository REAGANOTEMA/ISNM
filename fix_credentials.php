<?php
/**
 * ISNM ERP Credential Fixer
 * Run: php fix_credentials.php
 * Connects as root (no password) and updates all staff passwords + role_ids.
 * For production, change $dbUser/$dbPass below to match your .env.
 */

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'igangaschoolofl_staffs_db';
$dbPort = 3306;

// ── Optional: use .env credentials if they work ──────────────────────
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k); $v = trim($v);
        if ($v !== '' && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
            $v = substr($v, 1, -1);
        }
        if ($k === 'STAFF_DB_USER') $dbUser = $v;
        if ($k === 'STAFF_DB_PASS') $dbPass = $v;
        if ($k === 'STAFF_DB_HOST') $dbHost = $v;
        if ($k === 'STAFF_DB_NAME') $dbName = $v;
        if ($k === 'STAFF_DB_PORT') $dbPort = (int)$v;
    }
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected to $dbName.\n";

$staffAccounts = [
    // LEADERSHIP & STRATEGY
    ['email' => 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'DorisJoy2026', 'full_name' => 'Director General',               'position' => 'Director General',               'department' => 'Executive Office',    'role_name' => 'Director General'],
    ['email' => 'ceo@igangaschoolofnursingandmidwifery.ac.ug',              'password' => 'Lovely2God',    'full_name' => 'Chief Executive Officer',        'position' => 'Chief Executive Officer',        'department' => 'Executive Office',    'role_name' => 'CEO'],
    // ACADEMIC AFFAIRS
    ['email' => 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Stephen123',    'full_name' => 'Director Academics',             'position' => 'Director Academics',             'department' => 'Academic Affairs',    'role_name' => 'Director Academics'],
    ['email' => 'principal@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'isnm2026',      'full_name' => 'School Principal',               'position' => 'School Principal',               'department' => 'Academic Affairs',    'role_name' => 'School Principal'],
    ['email' => 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Isnm2026',      'full_name' => 'Deputy Principal',               'position' => 'Deputy Principal',               'department' => 'Academic Affairs',    'role_name' => 'Deputy Principal'],
    ['email' => 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','password' => 'Lovely2God',    'full_name' => 'Academic Registrar',             'position' => 'Academic Registrar',             'department' => 'Academic Affairs',    'role_name' => 'Academic Registrar'],
    ['email' => 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'isnm4life',     'full_name' => 'Head of Nursing',                'position' => 'Head Nursing',                   'department' => 'Nursing',            'role_name' => 'Head of Nursing'],
    ['email' => 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Life2save',     'full_name' => 'Head of Midwifery',              'position' => 'Head Midwifery',                 'department' => 'Midwifery',           'role_name' => 'Head of Midwifery'],
    ['email' => 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026',      'full_name' => 'Senior Lecturer',                'position' => 'Senior Lecturers',               'department' => 'Academic Affairs',    'role_name' => 'Senior Lecturers'],
    ['email' => 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Isnm4life',     'full_name' => 'Lecturer',                       'position' => 'Lecturers',                      'department' => 'Academic Affairs',    'role_name' => 'Lecturers'],
    // FINANCE & ACCOUNTS
    ['email' => 'finance@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'DorisJoy2026',  'full_name' => 'Director Finance',               'position' => 'Director Finance',               'department' => 'Finance',            'role_name' => 'Director Finance'],
    ['email' => 'bursar@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'bursar@isnm',   'full_name' => 'School Bursar',                  'position' => 'School Bursar',                  'department' => 'Finance',            'role_name' => 'School Bursar'],
    // HR & ADMINISTRATION
    ['email' => 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'password' => 'Alexis2026',    'full_name' => 'HR Manager',                     'position' => 'HR Manager',                     'department' => 'Human Resources',    'role_name' => 'HR Manager'],
    ['email' => 'secretary@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',    'full_name' => 'School Secretary',               'position' => 'School Secretary',               'department' => 'Administration',     'role_name' => 'School Secretary'],
    // STUDENT SERVICES
    ['email' => 'admissions@igangaschoolofnursingandmidwifery.ac.ug',       'password' => '2268926931',    'full_name' => 'Director Admissions',            'position' => 'Director Admissions & Requirements','department' => 'Admissions',       'role_name' => 'Director Admissions & Requirements'],
    ['email' => 'admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   'password' => '2268926931',    'full_name' => 'Admissions Requirements Officer','position' => 'Director Admissions & Requirements','department' => 'Admissions',       'role_name' => 'Director Admissions & Requirements'],
    ['email' => 'library@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',      'full_name' => 'School Librarian',               'position' => 'School Librarian',               'department' => 'Library',           'role_name' => 'School Librarian'],
    ['email' => 'matron@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Isnm2026',      'full_name' => 'Matron',                         'position' => 'Matrons',                        'department' => 'Student Services',   'role_name' => 'Matrons'],
    ['email' => 'warden@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Lovely2God',    'full_name' => 'Warden',                         'position' => 'Wardens',                        'department' => 'Student Services',   'role_name' => 'Wardens'],
    ['email' => 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',      'full_name' => 'Sickbay Nurse',                  'position' => 'Sickbay',                        'department' => 'Health Services',    'role_name' => 'Sickbay'],
    ['email' => 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'password' => 'isnm4life',     'full_name' => 'Guild President',                'position' => 'Guild President',                'department' => 'Student Services',   'role_name' => 'Guild President'],
    // OPERATIONS & LOGISTICS
    ['email' => 'dannybict@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',    'full_name' => 'Director ICT',                   'position' => 'Director ICT',                   'department' => 'ICT',               'role_name' => 'Director ICT'],
    ['email' => 'directorict@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'Lovely2God',    'full_name' => 'Director ICT (Alt)',             'position' => 'Director ICT',                   'department' => 'ICT',               'role_name' => 'Director ICT'],
    ['email' => 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',     'password' => 'Techno123',     'full_name' => 'Computer Lab Manager',           'position' => 'Computer Lab Manager',           'department' => 'ICT',               'role_name' => 'Computer Lab Manager'],
    ['email' => 'computerlab@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'Techno123',     'full_name' => 'Computer Lab Technician',        'position' => 'Computer Lab Manager',           'department' => 'ICT',               'role_name' => 'Computer Lab Manager'],
    ['email' => 'skillslab@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',    'full_name' => 'Skills Lab Manager',             'position' => 'Skills Lab',                     'department' => 'ICT',               'role_name' => 'Skills Lab'],
    ['email' => 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'password' => 'Lovely2God',    'full_name' => 'Skills Lab Technician',          'position' => 'Skills Lab',                     'department' => 'ICT',               'role_name' => 'Skills Lab'],
    ['email' => 'store@igangaschoolofnursingandmidwifery.ac.ug',            'password' => 'Isnm4life',     'full_name' => 'Storekeeper',                    'position' => 'Store Keeper',                   'department' => 'Logistics',         'role_name' => 'Storekeeper'],
    ['email' => 'drivers@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm4life',     'full_name' => 'Driver',                         'position' => 'Drivers',                        'department' => 'Logistics',         'role_name' => 'Drivers'],
    ['email' => 'security@igangaschoolofnursingandmidwifery.ac.ug',         'password' => 'safty1st',      'full_name' => 'Security Officer',               'position' => 'Security',                       'department' => 'Security',          'role_name' => 'Security'],
];

$getRoleId = function($roleName) use ($conn) {
    static $cache = [];
    if (isset($cache[$roleName])) return $cache[$roleName];
    $s = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
    if (!$s) return null;
    $s->bind_param('s', $roleName);
    $s->execute();
    $r = $s->get_result();
    $id = ($r && $r->num_rows > 0) ? (int)$r->fetch_assoc()['id'] : null;
    $s->close();
    $cache[$roleName] = $id;
    return $id;
};

$updated = $inserted = $errors = 0;

foreach ($staffAccounts as $s) {
    $hash = password_hash($s['password'], PASSWORD_BCRYPT);
    $roleId = $getRoleId($s['role_name']);

    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $s['email']);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exists) {
        $sql = "UPDATE staff SET password=?, full_name=?, position=?, department=?, role_id=?, status='Active', login_attempts=0, locked_until=NULL, is_first_login=0, password_changed=1 WHERE id=?";
        $st = $conn->prepare($sql);
        $st->bind_param('ssssisi', $hash, $s['full_name'], $s['position'], $s['department'], $roleId, $exists['id']);
        $st->execute();
        echo "  " . ($st->affected_rows >= 0 ? "UPDATED" : "ERROR") . ": {$s['email']}\n";
        if ($st->affected_rows < 0) $errors++; else $updated++;
        $st->close();
    } else {
        $sql = "INSERT INTO staff (email,password,full_name,position,department,role_id,status,login_attempts,is_first_login,password_changed,created_at,updated_at) VALUES (?,?,?,?,?,?,'Active',0,0,1,NOW(),NOW())";
        $st = $conn->prepare($sql);
        $st->bind_param('sssssi', $s['email'], $hash, $s['full_name'], $s['position'], $s['department'], $roleId);
        $st->execute();
        echo "  " . ($st->affected_rows > 0 ? "INSERTED" : "ERROR") . ": {$s['email']}\n";
        if ($st->affected_rows > 0) $inserted++; else $errors++;
        $st->close();
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated\nInserted: $inserted\nErrors: $errors\nTotal: " . count($staffAccounts) . "\n";
$conn->close();
