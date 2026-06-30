<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SEED ALL STAFF CREDENTIALS — ISNM ERP
 * 
 * This script creates/updates ALL staff accounts with the exact credentials
 * specified by the system administrator. Run ONLY on production:
 *   php database/migrations/seed_all_credentials.php
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die("ERROR: Cannot connect to staff database.\n");
}

echo "Connected to staff database.\n\n";

// ── Step 1: Ensure ALL staff_roles exist ──
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
        $stmt2 = $conn->prepare("INSERT INTO staff_roles (role_name, role_description, dashboard_path) VALUES (?, ?, ?)");
        $desc = $roleName . ' role for ISNM ERP';
        $dash = '';
        $stmt2->bind_param('sss', $roleName, $desc, $dash);
        $stmt2->execute();
        $roleIds[$roleName] = (int)$stmt2->insert_id;
        $stmt2->close();
        echo "  ✓ Created role: $roleName (ID: {$roleIds[$roleName]})\n";
    }
    $stmt->close();
}

echo "\nAll roles ready.\n\n";

// ── Step 2: Define all staff credentials (as specified by admin) ──
// Format: [email, full_name, password_plain, role_name, staff_id, position, department]
$staffList = [
    // Computer Lab
    ['computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager',  'Techno123',   'Computer Lab',      'CLB-001', 'Computer Lab Manager', 'ICT'],
    
    // Director General
    ['directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'Director General',    'DorisJoy2026','Director General',   'DG-001',  'Director General',     'Executive'],
    
    // CEO
    ['ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Chief Executive Officer',         'Lovely2God',  'CEO',                'CEO-001', 'CEO',                  'Executive'],
    
    // Director Academics
    ['directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics', 'Stephen123',  'Director Academics', 'DA-001',  'Director Academics',   'Academic Affairs'],
    
    // Director Finance / Bursar
    ['finance@igangaschoolofnursingandmidwifery.ac.ug', 'Director Finance',            'DorisJoy2026','Director Finance',   'DF-001',  'Director Finance',     'Finance'],
    
    // Principal
    ['principal@igangaschoolofnursingandmidwifery.ac.ug', 'School Principal',           'isnm2026',    'School Principal',   'PRIN-001','School Principal',     'Administration'],
    
    // Deputy Principal
    ['dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Deputy Principal',       'Isnm2026',    'Deputy Principal',   'DP-001',  'Deputy Principal',     'Administration'],
    
    // Academic Registrar
    ['academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Academic Registrar', 'Lovely2God',  'Academic Registrar', 'AR-001',  'Academic Registrar',   'Academic Registrar'],
    
    // HR Manager
    ['hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'HR Manager',               'Alexis2026',  'HR Manager',         'HR-001',  'HR Manager',           'Human Resources'],
    
    // Secretary
    ['secretary@igangaschoolofnursingandmidwifery.ac.ug', 'School Secretary',           'Lovely2God',  'School Secretary',   'SEC-001', 'School Secretary',     'Administration'],
    
    // Librarian
    ['library@igangaschoolofnursingandmidwifery.ac.ug', 'School Librarian',             'isnm2026',    'School Librarian',   'LIB-001', 'School Librarian',     'Library'],
    
    // Head of Nursing
    ['nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Nursing',          'isnm4life',   'Head of Nursing',    'NUR-001', 'Head of Nursing',      'Nursing'],
    
    // Head of Midwifery
    ['midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Midwifery',      'Life2save',   'Head of Midwifery',  'MID-001', 'Head of Midwifery',    'Midwifery'],
    
    // Senior Lecturer
    ['senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer',     'isnm2026',    'Senior Lecturer',    'SL-001',  'Senior Lecturer',      'Academic Affairs'],
    
    // Lecturer
    ['lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Lecturer',                   'Isnm4life',   'Lecturer',           'LEC-001', 'Lecturer',             'Academic Affairs'],
    
    // Matron
    ['matron@igangaschoolofnursingandmidwifery.ac.ug', 'Matron',                        'Isnm2026',    'Matron',             'MAT-001', 'Matron',               'Student Welfare'],
    
    // Warden
    ['warden@igangaschoolofnursingandmidwifery.ac.ug', 'Warden',                        'Lovely2God',  'Warden',             'WAR-001', 'Warden',               'Student Welfare'],
    
    // Sickbay
    ['sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'Sickbay Nurse',                'isnm2026',    'Sickbay Nurse',      'SKB-001', 'Sickbay Nurse',        'Student Welfare'],
    
    // Drivers
    ['drivers@igangaschoolofnursingandmidwifery.ac.ug', 'Driver',                       'isnm4life',   'Driver',             'DRV-001', 'Driver',               'Transport'],
    
    // Security
    ['security@igangaschoolofnursingandmidwifery.ac.ug', 'Security Officer',             'safty1st',   'Security Officer',   'SEC-001', 'Security Officer',     'Security'],
    
    // Storekeeper
    ['store@igangaschoolofnursingandmidwifery.ac.ug', 'Storekeeper',                    'Isnm4life',   'Storekeeper',        'STO-001', 'Storekeeper',          'Store'],
    
    // Guild President
    ['guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'Guild President',        'isnm4life',   'Guild President',    'G-001',   'Guild President',      'Student Government'],
    
    // Director Admissions
    ['admissions@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions',        '2268926931',  'Director Admissions', 'ADM-001', 'Director Admissions',  'Admissions'],
    
    // Director ICT
    ['dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT',               'Lovely2God',  'Director ICT',       'ICT-001', 'Director ICT',         'ICT'],
    
    // Skills Lab
    ['skills-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Manager',         'Lovely2God',  'Skills Lab',         'SKL-001', 'Skills Lab Manager',   'Skills Laboratory'],
];

$inserted = 0;
$updated = 0;
$errors = 0;

foreach ($staffList as [$email, $fullName, $passwordPlain, $roleName, $staffId, $position, $department]) {
    $rid = $roleIds[$roleName] ?? null;
    if (!$rid) {
        echo "  ✗ ERROR: No role ID for '$roleName' — skipping $email\n";
        $errors++;
        continue;
    }

    $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

    // Check if staff exists by email
    $chk = $conn->prepare("SELECT id FROM staff WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing) {
        $upd = $conn->prepare("UPDATE staff SET password = ?, role_id = ?, position = ?, department = ?, full_name = ?, status = 'Active' WHERE email = ?");
        $upd->bind_param('sissss', $passwordHash, $rid, $position, $department, $fullName, $email);
        if ($upd->execute()) {
            echo "  ✓ UPDATED: $email → $roleName\n";
            $updated++;
        } else {
            echo "  ✗ UPDATE FAILED: $email — " . $upd->error . "\n";
            $errors++;
        }
        $upd->close();
    } else {
        $ins = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', CURDATE())");
        $ins->bind_param('ssssssi', $staffId, $fullName, $email, $passwordHash, $position, $department, $rid);
        if ($ins->execute()) {
            echo "  ✓ INSERTED: $email → $roleName (Password: $passwordPlain)\n";
            $inserted++;
        } else {
            echo "  ✗ INSERT FAILED: $email — " . $ins->error . "\n";
            $errors++;
        }
        $ins->close();
    }
}

echo "\n═══════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "  Inserted: $inserted\n";
echo "  Updated:  $updated\n";
echo "  Errors:   $errors\n";
echo "═══════════════════════════════════════════════\n";
echo "\nAll credentials are now seeded!\n";
echo "Users can log in at: staff-login.php\n";
