<?php
// Simulate the full auth flow for bursar and HR
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

function testStaffAuth($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT s.id, s.email, s.password, s.status, s.full_name, sr.role_name 
        FROM staff s LEFT JOIN staff_roles sr ON s.role_id = sr.id 
        WHERE LOWER(s.email) = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) return ['success' => false, 'reason' => 'not_found'];
    if (strtolower($user['status']) !== 'active') return ['success' => false, 'reason' => 'inactive'];
    if (!password_verify($password, $user['password'])) return ['success' => false, 'reason' => 'wrong_password'];
    
    return ['success' => true, 'user' => $user];
}

$domain = '@igangaschoolofnursingandmidwifery.ac.ug';

$tests = [
    ['email' => 'bursar' . $domain, 'pass' => 'Isnm2026', 'name' => 'Bursar (role_id=24)'],
    ['email' => 'bursar.assistant' . $domain, 'pass' => 'Isnm2026', 'name' => 'Bursar Assistant (id=51)'],
    ['email' => 'hr-manager' . $domain, 'pass' => 'Alexis2026', 'name' => 'HR Manager (role_id=9)'],
    ['email' => 'finance' . $domain, 'pass' => 'DorisJoy2026', 'name' => 'Director Finance (role_id=4)'],
];

foreach ($tests as $t) {
    $result = testStaffAuth($conn, $t['email'], $t['pass']);
    if ($result['success']) {
        $u = $result['user'];
        echo "PASS: {$t['name']} - id={$u['id']} role={$u['role_name']} status={$u['status']}\n";
    } else {
        echo "FAIL: {$t['name']} ({$t['email']}) - {$result['reason']}\n";
    }
}

// Also check bursar_users table if it exists
echo "\n=== Checking bursar_users table ===\n";
$r = $conn->query("SHOW TABLES LIKE 'bursar_users'");
if ($r->num_rows > 0) {
    $r2 = $conn->query("SELECT id, email, role, status FROM bursar_users");
    while ($row = $r2->fetch_assoc()) {
        echo "  bursar_users: id={$row['id']} email={$row['email']} role={$row['role']} status={$row['status']}\n";
    }
} else {
    echo "  bursar_users table does NOT exist\n";
}

// Check hr_users table
echo "\n=== Checking hr_users table ===\n";
$r = $conn->query("SHOW TABLES LIKE 'hr_users'");
if ($r->num_rows > 0) {
    $r2 = $conn->query("SELECT id, email, role, status FROM hr_users");
    while ($row = $r2->fetch_assoc()) {
        echo "  hr_users: id={$row['id']} email={$row['email']} role={$row['role']} status={$row['status']}\n";
    }
} else {
    echo "  hr_users table does NOT exist\n";
}

// Check staff_roles for bursar/HR
echo "\n=== Staff Roles (Bursar/HR related) ===\n";
$r = $conn->query("SELECT id, role_name, dashboard_path FROM staff_roles WHERE role_name LIKE '%ursar%' OR role_name LIKE '%HR%' OR role_name LIKE '%Finance%'");
while ($row = $r->fetch_assoc()) {
    echo "  id={$row['id']} role={$row['role_name']} dashboard={$row['dashboard_path']}\n";
}
