<?php
/**
 * Seed all 30 staff accounts with correct credentials on production/hosting.
 * Uses password_hash(PASSWORD_BCRYPT) matching auth-service.php.
 * Safe to run multiple times (updates matching records, skips if same).
 *
 * USAGE: php sql/seed_production_credentials.php
 */

require __DIR__ . '/../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die("FATAL: Could not connect to staff DB. Check .env credentials.\n");
}

$accounts = [
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 'Director General'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug',              'Lovely2God',   'CEO'],
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Stephen123',   'Director Academics'],
    ['principal@igangaschoolofnursingandmidwifery.ac.ug',        'isnm2026',     'School Principal'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'Isnm2026',     'Deputy Principal'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God',   'Academic Registrar'],
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'isnm4life',    'Head of Nursing'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'Life2save',    'Head of Midwifery'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026',     'Senior Lecturer'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'Isnm4life',    'Lecturer'],
    ['finance@igangaschoolofnursingandmidwifery.ac.ug',          'DorisJoy2026', 'Director Finance'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug',           'bursar@isnm',  'School Bursar'],
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'Alexis2026',   'HR Manager'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God',   'School Secretary'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug',       '2268926931',   'Director Admissions'],
    ['admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   '2268926931',   'Director Admissions & Requirements'],
    ['library@igangaschoolofnursingandmidwifery.ac.ug',          'isnm2026',     'School Librarian'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug',           'Isnm2026',     'Matron'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug',           'Lovely2God',   'Warden'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'isnm2026',     'Sickbay Nurse'],
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'isnm4life',    'Guild President'],
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God',   'Director ICT'],
    ['directorict@igangaschoolofnursingandmidwifery.ac.ug',      'Lovely2God',   'Director ICT'],
    ['computerlab@igangaschoolofnursingandmidwifery.ac.ug',      'Techno123',    'Computer Lab Manager'],
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug',     'Techno123',    'Computer Lab Manager'],
    ['skillslab@igangaschoolofnursingandmidwifery.ac.ug',        'Lovely2God',   'Skills Lab Manager'],
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'Lovely2God',   'Skills Lab Technician'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug',            'Isnm4life',    'Storekeeper'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug',          'isnm4life',    'Driver'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug',         'safty1st',     'Security Officer'],
];

echo "Seeding " . count($accounts) . " staff credentials...\n";

$stmt = $conn->prepare("UPDATE staff SET password = ?, email = ?, full_name = ?, status = 'active', login_attempts = 0, locked_until = NULL WHERE email = ?");
if (!$stmt) {
    die("Prepare error: " . $conn->error . "\n");
}

$stmt->bind_param("ssss", $hash, $email, $name, $lookupEmail);

$updated = 0;
$notFound = 0;

foreach ($accounts as [$email, $password, $name]) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $lookupEmail = $email;
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    if ($stmt->affected_rows > 0) {
        $updated++;
        echo "  OK  $email\n";
    } else {
        $esc = $conn->real_escape_string($email);
        $check = $conn->query("SELECT id, email FROM staff WHERE email = '$esc'");
        if ($check && $check->num_rows > 0) {
            echo "  SAME $email (already up to date)\n";
        } else {
            echo "  MISS $email (not found in staff table)\n";
            $notFound++;
        }
    }
}

$stmt->close();
echo "\nDone. Updated: $updated, Not found: $notFound\n";

if ($notFound > 0) {
    echo "\nNOTE: $notFound accounts not found. If the hosting staff table is empty,\n";
    echo "first export from localhost:\n";
    echo "  mysqldump -u root -p isnm_school_portal staff > staff_backup.sql\n";
    echo "Then import on hosting:\n";
    echo "  mysql -u HOSTING_USER -p igangaschool_staffs < staff_backup.sql\n";
    echo "Then re-run: php sql/seed_production_credentials.php\n";
}
