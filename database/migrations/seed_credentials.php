<?php
/**
 * Seed all ISNM staff credentials into the staff table.
 * Run ONCE on production: php database/migrations/seed_credentials.php
 * Password for all accounts: Techno123
 */
$passwordHash = password_hash('Techno123', PASSWORD_BCRYPT);

require_once __DIR__ . '/../../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die("ERROR: Cannot connect to staff database.\n");
}

echo "Connected to staff database.\n";

// First ensure staff_roles exist
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
];

$roleIds = [];
foreach ($roles as $roleName) {
    $stmt = $conn->prepare("SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1");
    $stmt->bind_param('s', $roleName);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $roleIds[$roleName] = (int)$row['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO staff_roles (role_name, role_description) VALUES (?, ?)");
        $desc = $roleName . ' role for ISNM ERP';
        $stmt2->bind_param('ss', $roleName, $desc);
        if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
        $roleIds[$roleName] = (int)$stmt2->insert_id;
        $stmt2->close();
        echo "Created role: $roleName (ID: {$roleIds[$roleName]})\n";
    }
    $stmt->close();
}

$staff = [
    // [email, full_name, position, department, role_name, staff_id]
    ['info@igangaschoolofnursingandmidwifery.ac.ug', 'Director General',       'Director General',       'Executive',           'Director General',     'DIR-001'],
    ['ceo@isnm.ac.ug',                                'Chief Executive Officer','CEO',                    'Executive',           'CEO',                  'CEO-001'],
    ['academicoffice@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics',  'Director Academics', 'Academic Affairs', 'Director Academics',   'DA-001'],
    ['financeoffice@igangaschoolofnursingandmidwifery.ac.ug',  'Director Finance',    'Director Finance',   'Finance',            'Director Finance',     'DF-001'],
    ['ict@igangaschoolofnursingandmidwifery.ac.ug',            'Director ICT',        'Director ICT',       'ICT',                'Director ICT',         'ICT-001'],
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug',     'Director Admissions', 'Director Admissions','Admissions',         'Director Admissions',  'ADM-001'],
    ['principal@isnm.ac.ug',                            'School Principal',       'School Principal',      'Administration',      'School Principal',     'PRIN-001'],
    ['deputyprincipal@isnm.ac.ug',                      'Deputy Principal',       'Deputy Principal',      'Administration',      'Deputy Principal',     'DP-001'],
    ['registrar@igangaschoolofnursingandmidwifery.ac.ug',      'Academic Registrar',     'Academic Registrar',   'Academic Registrar',  'Academic Registrar',   'AR-001'],
    ['bursar@igangaschoolofnursingandmidwifery.ac.ug',         'School Bursar',          'School Bursar',        'Finance',            'School Bursar',        'BUR-001'],
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug',      'School Secretary',       'School Secretary',     'Administration',     'School Secretary',     'SEC-001'],
    ['hr@igangaschoolofnursingandmidwifery.ac.ug',             'HR Manager',             'HR Manager',           'Human Resources',    'HR Manager',           'HR-001'],
    ['librarian@isnm.ac.ug',                            'School Librarian',       'School Librarian',      'Library',             'School Librarian',    'LIB-001'],
    ['nursing@igangaschoolofnursingandmidwifery.ac.ug',        'Head of Nursing',        'Head of Nursing',      'Nursing',            'Head of Nursing',      'NUR-001'],
    ['midwifery@igangaschoolofnursingandmidwifery.ac.ug',      'Head of Midwifery',      'Head of Midwifery',    'Midwifery',          'Head of Midwifery',    'MID-001'],
    ['seniorlecturer@isnm.ac.ug',                       'Senior Lecturer',        'Senior Lecturer',       'Academic Affairs',   'Senior Lecturer',      'SL-001'],
    ['lecturer@igangaschoolofnursingandmidwifery.ac.ug',      'Lecturer',               'Lecturer',             'Academic Affairs',   'Lecturer',             'LEC-001'],
    ['security@igangaschoolofnursingandmidwifery.ac.ug',      'Security Officer',       'Security Officer',     'Security',           'Security Officer',     'SEC-001'],
    ['store@igangaschoolofnursingandmidwifery.ac.ug',         'Storekeeper',            'Storekeeper',          'Store',              'Storekeeper',          'STO-001'],
    ['transport@igangaschoolofnursingandmidwifery.ac.ug',     'Driver',                 'Driver',               'Transport',          'Driver',               'DRV-001'],
    ['matron@igangaschoolofnursingandmidwifery.ac.ug',        'Matron',                 'Matron',               'Student Welfare',    'Matron',               'MAT-001'],
    ['warden@igangaschoolofnursingandmidwifery.ac.ug',        'Warden',                 'Warden',               'Student Welfare',    'Warden',               'WAR-001'],
    ['guild@isnm.ac.ug',                                'Guild President',        'Guild President',       'Student Government', 'Guild President',      'G-001'],
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug',      'Sickbay Nurse',          'Sickbay Nurse',         'Student Welfare',    'Sickbay Nurse',        'SKB-001'],
    ['admin@igangaschoolofnursingandmidwifery.ac.ug',        'System Administrator',   'System Administrator',  'ICT',                'System Administrator', 'SYS-001'],
];

$inserted = 0;
$skipped = 0;
foreach ($staff as [$email, $fullName, $position, $department, $roleName, $staffId]) {
    // Check if staff already exists
    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    if (!$chk->execute()) { error_log('$chk execute failed: ' . ($chk->error ?? 'unknown')); };
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exists) {
        // Update existing: set password and role if not already set correctly
        $upd = $conn->prepare("UPDATE staff SET password = ?, role_id = ?, status = 'Active' WHERE email = ?");
        $rid = $roleIds[$roleName] ?? null;
        $upd->bind_param('sis', $passwordHash, $rid, $email);
        if (!$upd->execute()) { error_log('$upd execute failed: ' . ($upd->error ?? 'unknown')); };
        $upd->close();
        echo "UPDATED: $email ($fullName)\n";
        $inserted++;
        continue;
    }

    $rid = $roleIds[$roleName] ?? null;
    if (!$rid) {
        echo "SKIP: No role ID for '$roleName' ($email)\n";
        $skipped++;
        continue;
    }

    $ins = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', CURDATE())");
    $ins->bind_param('ssssssi', $staffId, $fullName, $email, $passwordHash, $position, $department, $rid);
    if (!$ins->execute()) { error_log('$ins execute failed: ' . ($ins->error ?? 'unknown')); };
    $ins->close();
    echo "INSERTED: $email ($fullName) as $roleName\n";
    $inserted++;
}

echo "\nDone. $inserted accounts processed, $skipped skipped.\n";
