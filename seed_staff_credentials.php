<?php
/**
 * ISNM Staff Credentials Seeder
 * Inserts/updates all staff accounts with correct bcrypt passwords and role_id.
 * Usage: php seed_staff_credentials.php
 */

require_once __DIR__ . '/config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    echo "ERROR: Cannot connect to staff database. Check .env or run fix_credentials.php directly.\n";
    exit(1);
}

$staffAccounts = [
    ['email' => 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'DorisJoy2026', 'full_name' => 'Director General',               'position' => 'Director General',               'department' => 'Executive Office',    'role_name' => 'Director General'],
    ['email' => 'ceo@igangaschoolofnursingandmidwifery.ac.ug',              'password' => 'Lovely2God',    'full_name' => 'Chief Executive Officer',        'position' => 'Chief Executive Officer',        'department' => 'Executive Office',    'role_name' => 'CEO'],
    ['email' => 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Stephen123',    'full_name' => 'Director Academics',             'position' => 'Director Academics',             'department' => 'Academic Affairs',    'role_name' => 'Director Academics'],
    ['email' => 'principal@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'isnm2026',      'full_name' => 'School Principal',               'position' => 'School Principal',               'department' => 'Academic Affairs',    'role_name' => 'School Principal'],
    ['email' => 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Isnm2026',      'full_name' => 'Deputy Principal',               'position' => 'Deputy Principal',               'department' => 'Academic Affairs',    'role_name' => 'Deputy Principal'],
    ['email' => 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','password' => 'Lovely2God',    'full_name' => 'Academic Registrar',             'position' => 'Academic Registrar',             'department' => 'Academic Affairs',    'role_name' => 'Academic Registrar'],
    ['email' => 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'isnm4life',     'full_name' => 'Head of Nursing',                'position' => 'Head Nursing',                   'department' => 'Nursing',            'role_name' => 'Head of Nursing'],
    ['email' => 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Life2save',     'full_name' => 'Head of Midwifery',              'position' => 'Head Midwifery',                 'department' => 'Midwifery',           'role_name' => 'Head of Midwifery'],
    ['email' => 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026',      'full_name' => 'Senior Lecturer',                'position' => 'Senior Lecturers',               'department' => 'Academic Affairs',    'role_name' => 'Senior Lecturers'],
    ['email' => 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Isnm4life',     'full_name' => 'Lecturer',                       'position' => 'Lecturers',                      'department' => 'Academic Affairs',    'role_name' => 'Lecturers'],
    ['email' => 'finance@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'DorisJoy2026',  'full_name' => 'Director Finance',               'position' => 'Director Finance',               'department' => 'Finance',            'role_name' => 'Director Finance'],
    ['email' => 'bursar@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'bursar@isnm',   'full_name' => 'School Bursar',                  'position' => 'School Bursar',                  'department' => 'Finance',            'role_name' => 'School Bursar'],
    ['email' => 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'password' => 'Alexis2026',    'full_name' => 'HR Manager',                     'position' => 'HR Manager',                     'department' => 'Human Resources',    'role_name' => 'HR Manager'],
    ['email' => 'secretary@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',    'full_name' => 'School Secretary',               'position' => 'School Secretary',               'department' => 'Administration',     'role_name' => 'School Secretary'],
    ['email' => 'admissions@igangaschoolofnursingandmidwifery.ac.ug',       'password' => '2268926931',    'full_name' => 'Director Admissions',            'position' => 'Director Admissions & Requirements','department' => 'Admissions',       'role_name' => 'Director Admissions & Requirements'],
    ['email' => 'admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   'password' => '2268926931',    'full_name' => 'Admissions Requirements Officer','position' => 'Director Admissions & Requirements','department' => 'Admissions',       'role_name' => 'Director Admissions & Requirements'],
    ['email' => 'library@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',      'full_name' => 'School Librarian',               'position' => 'School Librarian',               'department' => 'Library',           'role_name' => 'School Librarian'],
    ['email' => 'matron@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Isnm2026',      'full_name' => 'Matron',                         'position' => 'Matrons',                        'department' => 'Student Services',   'role_name' => 'Matrons'],
    ['email' => 'warden@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Lovely2God',    'full_name' => 'Warden',                         'position' => 'Wardens',                        'department' => 'Student Services',   'role_name' => 'Wardens'],
    ['email' => 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',      'full_name' => 'Sickbay Nurse',                  'position' => 'Sickbay',                        'department' => 'Health Services',    'role_name' => 'Sickbay'],
    ['email' => 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'password' => 'isnm4life',     'full_name' => 'Guild President',                'position' => 'Guild President',                'department' => 'Student Services',   'role_name' => 'Guild President'],
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
    if (!$s) { echo "  WARNING: Cannot query staff_roles\n"; return null; }
    $s->bind_param('s', $roleName);
    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
    $r = $s->get_result();
    $id = ($r && $r->num_rows > 0) ? (int)$r->fetch_assoc()['id'] : null;
    if (!$id) echo "  WARNING: No staff_roles row for '$roleName'\n";
    $s->close();
    $cache[$roleName] = $id;
    return $id;
};

echo "Seeding staff credentials...\n";
$updated = $inserted = $errors = 0;

foreach ($staffAccounts as $s) {
    $hash = password_hash($s['password'], PASSWORD_BCRYPT);
    $roleId = $getRoleId($s['role_name']);

    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $s['email']);
    if (!$chk->execute()) { error_log('$chk execute failed: ' . ($chk->error ?? 'unknown')); };
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing) {
        $st = $conn->prepare("UPDATE staff SET password=?, full_name=?, position=?, department=?, role_id=?, status='Active', login_attempts=0, locked_until=NULL, is_first_login=0, password_changed=1 WHERE id=?");
        $st->bind_param('ssssii', $hash, $s['full_name'], $s['position'], $s['department'], $roleId, $existing['id']);
        if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); };
        if ($st->error) { echo "  ERROR: {$s['email']} - {$st->error}\n"; $errors++; }
        else { echo "  Updated: {$s['email']} ({$s['position']})\n"; $updated++; }
        $st->close();
    } else {
        $st = $conn->prepare("INSERT INTO staff (email,password,full_name,position,department,role_id,status,login_attempts,is_first_login,password_changed,created_at,updated_at) VALUES (?,?,?,?,?,?,'Active',0,0,1,NOW(),NOW())");
        $st->bind_param('sssssi', $s['email'], $hash, $s['full_name'], $s['position'], $s['department'], $roleId);
        if (!$st->execute()) { error_log('$st execute failed: ' . ($st->error ?? 'unknown')); };
        if ($st->error) { echo "  ERROR: {$s['email']} - {$st->error}\n"; $errors++; }
        else { echo "  Inserted: {$s['email']} ({$s['position']})\n"; $inserted++; }
        $st->close();
    }
}

echo "\n=== Summary ===\n";
echo "Updated: $updated\nInserted: $inserted\nErrors: $errors\nTotal: " . count($staffAccounts) . "\n";
$conn->close();
