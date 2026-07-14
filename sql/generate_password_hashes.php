<?php
/**
 * ISNM Staff Password Migration Script
 * Run ONCE to set all 30 staff passwords to the correct values.
 * 
 * Usage: php sql/generate_password_hashes.php
 * Or visit: http://localhost/ISNM/sql/generate_password_hashes.php (with ?run=1)
 * 
 * SECURITY: Delete this file after running!
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$staffConn = getStaffConnection();
if (!$staffConn) {
    die("ERROR: Cannot connect to staff database.");
}

$accounts = [
    ['email' => 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',   'password' => 'DorisJoy2026', 'role' => 'Director General'],
    ['email' => 'ceo@igangaschoolofnursingandmidwifery.ac.ug',              'password' => 'Lovely2God',   'role' => 'CEO'],
    ['email' => 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Stephen123',   'role' => 'Director Academics'],
    ['email' => 'principal@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'isnm2026',     'role' => 'School Principal'],
    ['email' => 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Isnm2026',     'role' => 'Deputy Principal'],
    ['email' => 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','password' => 'Lovely2God',   'role' => 'Academic Registrar'],
    ['email' => 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'isnm4life',    'role' => 'Head Nursing'],
    ['email' => 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',    'password' => 'Life2save',    'role' => 'Head Midwifery'],
    ['email' => 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026',     'role' => 'Senior Lecturers'],
    ['email' => 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Isnm4life',    'role' => 'Lecturers'],
    ['email' => 'finance@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'DorisJoy2026', 'role' => 'Director Finance'],
    ['email' => 'bursar@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'bursar@isnm',  'role' => 'School Bursar'],
    ['email' => 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',       'password' => 'Alexis2026',   'role' => 'HR Manager'],
    ['email' => 'secretary@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',   'role' => 'School Secretary'],
    ['email' => 'admissions@igangaschoolofnursingandmidwifery.ac.ug',       'password' => '2268926931',   'role' => 'Director Admissions & Requirements'],
    ['email' => 'admissions-req@igangaschoolofnursingandmidwifery.ac.ug',   'password' => '2268926931',   'role' => 'Director Admissions & Requirements'],
    ['email' => 'library@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',     'role' => 'School Librarian'],
    ['email' => 'matron@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Isnm2026',     'role' => 'Matrons'],
    ['email' => 'warden@igangaschoolofnursingandmidwifery.ac.ug',           'password' => 'Lovely2God',   'role' => 'Wardens'],
    ['email' => 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm2026',     'role' => 'Sickbay'],
    ['email' => 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   'password' => 'isnm4life',    'role' => 'Guild President'],
    ['email' => 'dannybict@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',   'role' => 'Director ICT'],
    ['email' => 'directorict@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'Lovely2God',   'role' => 'Director ICT'],
    ['email' => 'computerlab@igangaschoolofnursingandmidwifery.ac.ug',      'password' => 'Techno123',    'role' => 'Computer Lab Manager'],
    ['email' => 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',     'password' => 'Techno123',    'role' => 'Computer Lab Manager'],
    ['email' => 'skillslab@igangaschoolofnursingandmidwifery.ac.ug',        'password' => 'Lovely2God',   'role' => 'Skills Lab'],
    ['email' => 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug',       'password' => 'Lovely2God',   'role' => 'Skills Lab'],
    ['email' => 'store@igangaschoolofnursingandmidwifery.ac.ug',            'password' => 'Isnm4life',    'role' => 'Storekeeper'],
    ['email' => 'drivers@igangaschoolofnursingandmidwifery.ac.ug',          'password' => 'isnm4life',    'role' => 'Drivers'],
    ['email' => 'security@igangaschoolofnursingandmidwifery.ac.ug',         'password' => 'safty1st',     'role' => 'Security'],
];

$run = isset($_GET['run']) && $_GET['run'] === '1';

if (!$run) {
    echo "<!DOCTYPE html><html><head><title>ISNM Password Migration</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body><div class='container mt-5'>";
    echo "<h2>ISNM Staff Password Migration</h2>";
    echo "<div class='alert alert-warning'>This script will update all 30 staff passwords. Click the button below to run.</div>";
    echo "<table class='table table-bordered'><thead><tr><th>Email</th><th>Role</th><th>Password</th></tr></thead><tbody>";
    foreach ($accounts as $a) {
        echo "<tr><td>{$a['email']}</td><td>{$a['role']}</td><td><code>{$a['password']}</code></td></tr>";
    }
    echo "</tbody></table>";
    echo "<a href='?run=1' class='btn btn-danger btn-lg' onclick=\"return confirm('Update all 30 staff passwords?')\">Run Migration Now</a>";
    echo "</div></body></html>";
    exit;
}

echo "<!DOCTYPE html><html><head><title>Migration Results</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body><div class='container mt-5'>";
echo "<h2>Password Migration Results</h2>";
echo "<table class='table table-bordered table-sm'><thead><tr><th>#</th><th>Email</th><th>Role</th><th>Status</th></tr></thead><tbody>";

$success = 0;
$failed = 0;

foreach ($accounts as $i => $a) {
    $hash = password_hash($a['password'], PASSWORD_DEFAULT);
    $email = $a['email'];
    
    $stmt = $staffConn->prepare("UPDATE staff SET password = ? WHERE LOWER(email) = LOWER(?)");
    if (!$stmt) {
        echo "<tr class='table-danger'><td>" . ($i+1) . "</td><td>{$email}</td><td>{$a['role']}</td><td>PREPARE FAILED: {$staffConn->error}</td></tr>";
        $failed++;
        continue;
    }
    $stmt->bind_param('ss', $hash, $email);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($ok && $affected >= 0) {
        echo "<tr class='table-success'><td>" . ($i+1) . "</td><td>{$email}</td><td>{$a['role']}</td><td>UPDATED ({$affected} row)</td></tr>";
        $success++;
    } else {
        echo "<tr class='table-danger'><td>" . ($i+1) . "</td><td>{$email}</td><td>{$a['role']}</td><td>FAILED</td></tr>";
        $failed++;
    }
}

echo "</tbody></table>";
echo "<div class='alert " . ($failed === 0 ? 'alert-success' : 'alert-warning') . "'>";
echo "<strong>Success: {$success}</strong> | <strong>Failed: {$failed}</strong>";
echo "</div>";

// Verify one password
$verifyEmail = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug';
$verifyPw = 'DorisJoy2026';
$stmt = $staffConn->prepare("SELECT password FROM staff WHERE LOWER(email) = LOWER(?) LIMIT 1");
$stmt->bind_param('s', $verifyEmail);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
    $match = password_verify($verifyPw, $row['password']);
    echo "<div class='alert alert-info'>Verification (Director General): " . ($match ? 'PASSWORD MATCHES!' : 'MISMATCH') . "</div>";
}

echo "<div class='alert alert-danger'><strong>SECURITY:</strong> Delete this file after running!</div>";
echo "<a href='../staff-login.php' class='btn btn-primary'>Go to Login Page</a>";
echo "</div></body></html>";
