<?php
require_once __DIR__ . '/config/database.php';
$staffConn = getStaffConnection();
if (!$staffConn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'accounts' => []]);
    exit;
}

$staffData = [
    ['email' => 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Techno123', 'full_name' => 'Computer Lab Manager', 'role_name' => 'Computer Lab Manager', 'department' => 'Information Technology'],
    ['email' => 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'DorisJoy2026', 'full_name' => 'Director General', 'role_name' => 'Director General', 'department' => 'Executive Office'],
    ['email' => 'ceo@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Lovely2God', 'full_name' => 'Chief Executive Officer', 'role_name' => 'CEO', 'department' => 'Executive Office'],
    ['email' => 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Stephen123', 'full_name' => 'Director Academics', 'role_name' => 'Director Academics', 'department' => 'Academic Affairs'],
    ['email' => 'finance@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'DorisJoy2026', 'full_name' => 'Director Finance', 'role_name' => 'Director Finance', 'department' => 'Finance Department'],
    ['email' => 'principal@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026', 'full_name' => 'School Principal', 'role_name' => 'School Principal', 'department' => 'Academic Affairs'],
    ['email' => 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Isnm2026', 'full_name' => 'Deputy Principal', 'role_name' => 'Deputy Principal', 'department' => 'Academic Affairs'],
    ['email' => 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Lovely2God', 'full_name' => 'Academic Registrar', 'role_name' => 'Academic Registrar', 'department' => 'Academic Affairs'],
    ['email' => 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Alexis2026', 'full_name' => 'HR Manager', 'role_name' => 'HR Manager', 'department' => 'Human Resources'],
    ['email' => 'secretary@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Lovely2God', 'full_name' => 'School Secretary', 'role_name' => 'School Secretary', 'department' => 'Administrative Support'],
    ['email' => 'library@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026', 'full_name' => 'School Librarian', 'role_name' => 'School Librarian', 'department' => 'Library Services'],
    ['email' => 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm4life', 'full_name' => 'Head of Nursing', 'role_name' => 'Head Nursing', 'department' => 'Nursing Department'],
    ['email' => 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Life2save', 'full_name' => 'Head of Midwifery', 'role_name' => 'Head Midwifery', 'department' => 'Midwifery Department'],
    ['email' => 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026', 'full_name' => 'Senior Lecturers', 'role_name' => 'Senior Lecturers', 'department' => 'Academic Affairs'],
    ['email' => 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Isnm4life', 'full_name' => 'Lecturers', 'role_name' => 'Lecturers', 'department' => 'Academic Affairs'],
    ['email' => 'matron@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Isnm2026', 'full_name' => 'Matrons', 'role_name' => 'Matrons', 'department' => 'Student Affairs'],
    ['email' => 'warden@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Lovely2God', 'full_name' => 'Warden', 'role_name' => 'Wardens', 'department' => 'Student Affairs'],
    ['email' => 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm2026', 'full_name' => 'Sickbay', 'role_name' => 'Sickbay', 'department' => 'Student Affairs'],
    ['email' => 'drivers@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm4life', 'full_name' => 'Drivers', 'role_name' => 'Drivers', 'department' => 'Support'],
    ['email' => 'security@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'safty1st', 'full_name' => 'Security', 'role_name' => 'Security', 'department' => 'Security Services'],
    ['email' => 'store@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Isnm4life', 'full_name' => 'Store Keeper', 'role_name' => 'Store Keeper', 'department' => 'Support'],
    ['email' => 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'isnm4life', 'full_name' => 'Guild President', 'role_name' => 'Guild President', 'department' => 'Student Affairs'],
    ['email' => 'admissions@igangaschoolofnursingandmidwifery.ac.ug', 'password' => '2268926931', 'full_name' => 'Director Admissions & Requirements', 'role_name' => 'Director Admissions & Requirements', 'department' => 'Academic Affairs'],
    ['email' => 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'Lovely2God', 'full_name' => 'Director ICT', 'role_name' => 'Director ICT', 'department' => 'Information Technology'],
    ['email' => 'bursar@igangaschoolofnursingandmidwifery.ac.ug', 'password' => 'bursar@isnm', 'full_name' => 'School Bursar', 'role_name' => 'School Bursar', 'department' => 'Finance Department'],
];

$roles = [
    'Computer Lab Manager',
    'Director General',
    'CEO',
    'Director Academics',
    'Director Finance',
    'School Principal',
    'Deputy Principal',
    'Academic Registrar',
    'HR Manager',
    'School Secretary',
    'School Librarian',
    'Head Nursing',
    'Head Midwifery',
    'Senior Lecturers',
    'Lecturers',
    'Matrons',
    'Wardens',
    'Sickbay',
    'Drivers',
    'Security',
    'Store Keeper',
    'Guild President',
    'Director Admissions & Requirements',
    'Director ICT',
    'School Bursar',
];

foreach ($roles as $role) {
    $check = $staffConn->prepare('SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1');
    $check->bind_param('s', $role);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows === 0) {
        $level = 'Administrative';
        if (in_array($role, ['Director General', 'CEO', 'Principal'], true)) {
            $level = 'Executive';
        } elseif (in_array($role, ['Director Academics', 'Director Finance', 'Director Admissions & Requirements', 'Director ICT', 'Deputy Principal'], true)) {
            $level = 'Management';
        } elseif (in_array($role, ['Academic Registrar', 'HR Manager', 'Head Nursing', 'Head Midwifery', 'Senior Lecturers', 'Lecturers'], true)) {
            $level = 'Academic';
        } elseif (in_array($role, ['School Secretary', 'School Librarian', 'School Bursar'], true)) {
            $level = 'Support';
        }
        $ins = $staffConn->prepare("INSERT INTO staff_roles (role_name, role_description, role_level, dashboard_path) VALUES (?, ?, ?, ?)");
        $desc = ucfirst(strtolower($role)) . ' role';
        $dash = 'dashboards/default.php';
        $ins->bind_param('ssss', $role, $desc, $level, $dash);
        $ins->execute();
    }
}

$results = [];
foreach ($staffData as $account) {
    $hashed = password_hash($account['password'], PASSWORD_DEFAULT);
    $roleId = null;
    $roleStmt = $staffConn->prepare('SELECT id FROM staff_roles WHERE role_name = ? LIMIT 1');
    if ($roleStmt) {
        $roleStmt->bind_param('s', $account['role_name']);
        $roleStmt->execute();
        $roleRes = $roleStmt->get_result();
        if ($roleRes && $roleRow = $roleRes->fetch_assoc()) {
            $roleId = (int)$roleRow['id'];
        }
    }

    $upd = $staffConn->prepare('UPDATE staff SET password = ?, position = ?, department = ?, status = \'Active\', password_changed = FALSE, is_first_login = TRUE, role_id = ? WHERE email = ?');
    if ($upd) {
        $upd->bind_param('sssis', $hashed, $account['full_name'], $account['department'], $roleId, $account['email']);
        $updated = $upd->execute();
        $affected = $upd->affected_rows;
        $upd->close();
    } else {
        $updated = false;
        $affected = 0;
    }

    if ($updated && $affected > 0) {
        $results[] = ['email' => $account['email'], 'status' => 'updated', 'role' => $account['role_name']];
    } else {
        $staffId = str_replace('@', '', $account['email']);
        $phone = '+256701000001';
        $ins = $staffConn->prepare('INSERT INTO staff (staff_id, full_name, email, password, phone, position, department, role_id, status, password_changed, is_first_login, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'Active\', FALSE, TRUE, NOW())');
        if ($ins) {
            $ins->bind_param('sssssssi', $staffId, $account['full_name'], $account['email'], $hashed, $phone, $account['full_name'], $account['department'], $roleId);
            $inserted = $ins->execute();
            $ins->close();
            $results[] = ['email' => $account['email'], 'status' => $inserted ? 'created' : 'create_failed', 'role' => $account['role_name']];
        } else {
            $results[] = ['email' => $account['email'], 'status' => 'create_failed', 'role' => $account['role_name']];
        }
    }
}

echo json_encode(['success' => true, 'accounts' => $results]);
$staffConn->close();
