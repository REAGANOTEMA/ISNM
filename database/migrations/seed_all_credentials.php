<?php
/**
 * SEED ALL STAFF CREDENTIALS — ISNM ERP
 * Run ONCE on production: php database/migrations/seed_all_credentials.php
 *
 * Uses the EXACT emails and passwords provided by the institution.
 * Each account maps to the correct role_id and dashboard.
 */
$start = microtime(true);
echo "=== ISNM Credential Seeder ===\n\n";

require_once __DIR__ . '/../../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die("ERROR: Cannot connect to staff database.\n");
}
echo "Connected to staff database.\n";

// ── Step 1: Ensure all staff roles exist ────────────────────────
$roles = [
    'Director General',
    'CEO',
    'Director Academics',
    'Director Finance',
    'Director ICT',
    'Director Admissions',
    'School Principal',
    'Deputy Principal',
    'Academic Registrar',
    'School Bursar',
    'School Secretary',
    'HR Manager',
    'School Librarian',
    'Head of Nursing',
    'Head of Midwifery',
    'Senior Lecturer',
    'Lecturer',
    'Security Officer',
    'Storekeeper',
    'Driver',
    'Matron',
    'Warden',
    'Guild President',
    'Sickbay Nurse',
    'System Administrator',
    'Computer Lab',
    'Skills Lab',
];

$roleIds = [];
foreach ($roles as $roleName) {
    $stmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
    $stmt->bind_param('s', $roleName);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $roleIds[$roleName] = (int)$row['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO staff_roles (role_name, description) VALUES (?, ?)");
        $desc = $roleName . ' role for ISNM ERP';
        $stmt2->bind_param('ss', $roleName, $desc);
        $stmt2->execute();
        $roleIds[$roleName] = (int)$stmt2->insert_id;
        $stmt2->close();
        echo "  Created role: $roleName (ID: {$roleIds[$roleName]})\n";
    }
    $stmt->close();
}

// ── Step 2: Define ALL credentials ──────────────────────────────
// Format: [email, password_plaintext, full_name, position, department, role_name, staff_id]
$staff = [
    // ── Executive ──
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026','Director General','Director General','Executive','Director General','DIR-001'],
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug',             'Lovely2God',  'Chief Executive Officer','CEO','Executive','CEO','CEO-001'],

    // ── Academic ──
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Stephen123',  'Director Academics','Director Academics','Academic Affairs','Director Academics','DA-001'],
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God','Academic Registrar','Academic Registrar','Academic Registrar','Academic Registrar','AR-001'],
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','isnm2026',   'Senior Lecturer','Senior Lecturer','Academic Affairs','Senior Lecturer','SL-001'],
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug',       'Isnm4life',   'Lecturer','Lecturer','Academic Affairs','Lecturer','LEC-001'],

    // ── Finance ──
    ['finance@igangaschoolofnursingandmidwifery.ac.ug',         'DorisJoy2026','Director Finance','Director Finance','Finance','Director Finance','DF-001'],

    // ── Administration ──
    ['principal@igangaschoolofnursingandmidwifery.ac.ug',       'isnm2026',    'School Principal','School Principal','Administration','School Principal','PRIN-001'],
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug',   'Isnm2026',    'Deputy Principal','Deputy Principal','Administration','Deputy Principal','DP-001'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug',       'Lovely2God',  'School Secretary','School Secretary','Administration','School Secretary','SEC-001'],

    // ── HR ──
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug',     'Alexis2026',  'HR Manager','HR Manager','Human Resources','HR Manager','HR-001'],

    // ── Admissions ──
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug',      '2268926931',  'Director Admissions','Director Admissions','Admissions','Director Admissions','ADM-001'],

    // ── ICT ──
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug',      'Lovely2God',  'Director ICT','Director ICT','ICT','Director ICT','ICT-001'],
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug',   'Techno123',   'Computer Lab Manager','Computer Lab Manager','ICT','Computer Lab','CL-001'],

    // ── Library ──
    ['library@igangaschoolofnursingandmidwifery.ac.ug',         'isnm2026',    'School Librarian','School Librarian','Library','School Librarian','LIB-001'],

    // ── Nursing & Midwifery ──
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',    'isnm4life',   'Head of Nursing','Head of Nursing','Nursing','Head of Nursing','NUR-001'],
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',  'Life2save',   'Head of Midwifery','Head of Midwifery','Midwifery','Head of Midwifery','MID-001'],

    // ── Student Welfare ──
    ['matron@igangaschoolofnursingandmidwifery.ac.ug',          'Isnm2026',    'Matron','Matron','Student Welfare','Matron','MAT-001'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug',          'Lovely2God',  'Warden','Warden','Student Welfare','Warden','WAR-001'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',         'isnm2026',    'Sickbay Nurse','Sickbay Nurse','Student Welfare','Sickbay Nurse','SKB-001'],

    // ── Skills Lab ──
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug',     'Lovely2God',  'Skills Lab Technician','Skills Lab Technician','Skills Laboratory','Skills Lab','SKL-001'],

    // ── Security & Transport ──
    ['security@igangaschoolofnursingandmidwifery.ac.ug',        'safty1st',    'Security Officer','Security Officer','Security','Security Officer','SEC-001'],
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug',         'isnm4life',   'Driver','Driver','Transport','Driver','DRV-001'],

    // ── Store ──
    ['store@igangaschoolofnursingandmidwifery.ac.ug',           'Isnm4life',   'Storekeeper','Storekeeper','Store','Storekeeper','STO-001'],

    // ── Student Government ──
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'isnm4life',   'Guild President','Guild President','Student Government','Guild President','G-001'],
];

// ── Step 3: Create/update each staff account ────────────────────
$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($staff as [$email, $plainPassword, $fullName, $position, $department, $roleName, $staffId]) {
    $rid = $roleIds[$roleName] ?? null;
    if (!$rid) {
        echo "  SKIP: No role ID for '$roleName' ($email)\n";
        $skipped++;
        continue;
    }

    // Hash password
    $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

    // Check if exists
    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exists) {
        $upd = $conn->prepare("UPDATE staff SET password = ?, role_id = ?, position = ?, department = ?, full_name = ?, status = 'Active' WHERE email = ?");
        $upd->bind_param('sissss', $passwordHash, $rid, $position, $department, $fullName, $email);
        $upd->execute();
        $upd->close();
        echo "  UPDATED: $email ($fullName) [$roleName]\n";
        $updated++;
    } else {
        $ins = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', CURDATE())");
        $ins->bind_param('ssssssi', $staffId, $fullName, $email, $passwordHash, $position, $department, $rid);
        $ins->execute();
        $ins->close();
        echo "  INSERTED: $email ($fullName) [$roleName]\n";
        $inserted++;
    }
}

// ── Step 4: Also keep legacy emails as fallback ─────────────────
$legacyEmails = [
    ['info@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 'Director General', 'Director General', 'Executive', 'Director General', 'DIR-000'],
    ['ict@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 'Director ICT', 'Director ICT', 'ICT', 'Director ICT', 'ICT-000'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 'School Bursar', 'School Bursar', 'Finance', 'School Bursar', 'BUR-001'],
    ['admin@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 'System Administrator', 'System Administrator', 'ICT', 'System Administrator', 'SYS-001'],
];

foreach ($legacyEmails as [$email, $plainPassword, $fullName, $position, $department, $roleName, $staffId]) {
    $rid = $roleIds[$roleName] ?? null;
    if (!$rid) continue;

    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$exists) {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $ins = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', CURDATE())");
        $ins->bind_param('ssssssi', $staffId, $fullName, $email, $passwordHash, $position, $department, $rid);
        $ins->execute();
        $ins->close();
        echo "  INSERTED (legacy): $email ($fullName) [$roleName]\n";
        $inserted++;
    }
}

$elapsed = round(microtime(true) - $start, 2);
echo "\n=== COMPLETE ===\n";
echo "  Inserted: $inserted\n";
echo "  Updated: $updated\n";
echo "  Skipped: $skipped\n";
echo "  Time: {$elapsed}s\n";
echo "\nAll accounts use the passwords provided by the institution.\n";
