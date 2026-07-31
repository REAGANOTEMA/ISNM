<?php
/**
 * Bursar Account Setup Script
 * Creates the bursar staff account for login.
 * 
 * Usage: GET /database/setup_bursar.php
 * Safe to run multiple times (idempotent).
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug';
$password = 'bursar@isnm';
$hash = password_hash($password, PASSWORD_DEFAULT);
$fullName = 'School Bursar';
$staffId = 'BUR-001';
$roleId = 10;
$position = 'School Bursar';
$department = 'Finance';

$conn = getStaffConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$results = [];

// 1. Ensure staff_roles has the bursar role
$roleCheck = @$conn->prepare("SELECT id FROM staff_roles WHERE id = ?");
if ($roleCheck) {
    $roleCheck->bind_param('i', $roleId);
    if (!$roleCheck->execute()) { error_log('roleCheck execute failed: ' . ($roleCheck->error ?? 'unknown')); }
    $roleRow = $roleCheck->get_result()->fetch_assoc();
    $roleCheck->close();
    
    if (!$roleRow) {
        $insRole = $conn->prepare("INSERT INTO staff_roles (id, role_name, role_description, hierarchy_level, dashboard_path, is_active) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)");
        if ($insRole) {
            $dashboardPath = 'dashboards/school-bursar.php';
            $insRole->bind_param('isssi', $roleId, $fullName, $fullName, $roleId, $dashboardPath);
            if ($insRole->execute()) {
                $results['role'] = 'Created/updated staff_roles id=' . $roleId;
            } else {
                $results['role_error'] = $insRole->error;
            }
            $insRole->close();
        }
    } else {
        $results['role'] = 'staff_roles id=' . $roleId . ' already exists';
    }
}

// 2. Check if staff account exists
$check = $conn->prepare("SELECT id, role_id, is_active, status FROM staff WHERE LOWER(email) = ? LIMIT 1");
if (!$check) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$check->bind_param('s', $email);
if (!$check->execute()) { error_log('check execute failed: ' . ($check->error ?? 'unknown')); }
$row = $check->get_result()->fetch_assoc();
$check->close();

if ($row) {
    // Update existing account
    $upd = $conn->prepare("UPDATE staff SET password = ?, role_id = ?, is_active = 1, status = 'Active', staff_id = COALESCE(NULLIF(staff_id,''), ?), position = ?, department = ?, locked_until = NULL, login_attempts = 0 WHERE LOWER(email) = ?");
    if ($upd) {
        $upd->bind_param('sissss', $hash, $roleId, $staffId, $position, $department, $email);
        if ($upd->execute()) {
            $results['staff'] = 'Updated existing staff account (id=' . $row['id'] . ')';
        } else {
            $results['staff_error'] = $upd->error;
        }
        $upd->close();
    }
} else {
    // Create new account
    $ins = $conn->prepare("INSERT INTO staff (staff_id, full_name, email, password, role_id, position, department, is_active, status, hire_date, login_attempts, is_first_login, password_changed, staff_category) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Active', CURDATE(), 0, 0, 1, 'non-teaching')");
    if ($ins) {
        $ins->bind_param('sssssss', $staffId, $fullName, $email, $hash, $roleId, $position, $department);
        if ($ins->execute()) {
            $results['staff'] = 'Created new staff account (id=' . $ins->insert_id . ')';
        } else {
            $results['staff_error'] = $ins->error;
        }
        $ins->close();
    }
}

// 3. Ensure bursar_users table has required columns
$buCols = @$conn->query("SHOW COLUMNS FROM bursar_users");
$buColNames = [];
if ($buCols) {
    while ($c = $buCols->fetch_assoc()) $buColNames[] = $c['Field'];
}
$requiredBuCols = [
    'login_attempts' => "INT(11) DEFAULT 0",
    'locked_until'   => "DATETIME DEFAULT NULL",
];
foreach ($requiredBuCols as $colName => $colDef) {
    if (!in_array($colName, $buColNames)) {
        $conn->query("ALTER TABLE bursar_users ADD COLUMN `$colName` $colDef");
        $results['bursar_users_col_' . $colName] = 'ADDED';
    }
}

// 4. Ensure bursar_users table exists and has the account
$bursarTableCheck = @$conn->query("SHOW TABLES LIKE 'bursar_users'");
if ($bursarTableCheck && $bursarTableCheck->num_rows > 0) {
    $bCheck = $conn->prepare("SELECT id FROM bursar_users WHERE email = ? LIMIT 1");
    if ($bCheck) {
        $bCheck->bind_param('s', $email);
        if (!$bCheck->execute()) { error_log('bCheck execute failed: ' . ($bCheck->error ?? 'unknown')); }
        $bRow = $bCheck->get_result()->fetch_assoc();
        $bCheck->close();
        
        if (!$bRow) {
            $bIns = $conn->prepare("INSERT INTO bursar_users (email, password_hash, full_name, role, status, login_attempts, locked_until) VALUES (?, ?, ?, 'bursar', 'active', 0, NULL)");
            if ($bIns) {
                $bIns->bind_param('sss', $email, $hash, $fullName);
                if ($bIns->execute()) {
                    $results['bursar_users'] = 'Created bursar_users entry';
                }
                $bIns->close();
            }
        } else {
            $bUpd = $conn->prepare("UPDATE bursar_users SET password_hash = ?, status = 'active', login_attempts = 0, locked_until = NULL WHERE email = ?");
            if ($bUpd) {
                $bUpd->bind_param('ss', $hash, $email);
                $bUpd->execute();
                $bUpd->close();
            }
            $results['bursar_users'] = 'Updated bursar_users entry';
        }
    }
} else {
    $results['bursar_users'] = 'bursar_users table does not exist (login uses staff table instead)';
}

// 5. Verify the account works
$verify = $conn->prepare("SELECT s.id, s.email, s.role_id, s.status, s.is_active, sr.role_name, sr.dashboard_path FROM staff s LEFT JOIN staff_roles sr ON s.role_id = sr.id WHERE LOWER(s.email) = ? LIMIT 1");
if ($verify) {
    $verify->bind_param('s', $email);
    $verify->execute();
    $vRow = $verify->get_result()->fetch_assoc();
    $verify->close();
    if ($vRow) {
        $results['verify'] = [
            'staff_id' => $vRow['id'],
            'email' => $vRow['email'],
            'role_id' => $vRow['role_id'],
            'role_name' => $vRow['role_name'],
            'dashboard' => $vRow['dashboard_path'],
            'status' => $vRow['status'],
            'active' => $vRow['is_active'],
            'password_valid' => password_verify($password, $hash),
        ];
    }
}

echo json_encode([
    'success' => true,
    'email' => $email,
    'password' => $password,
    'role_id' => $roleId,
    'dashboard' => 'dashboards/school-bursar.php',
    'details' => $results
]);
