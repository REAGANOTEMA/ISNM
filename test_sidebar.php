<?php
/**
 * Quick test: Check sidebar rendering for all roles
 */
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Director General';
$_SESSION['type'] = 'staff';
$_SESSION['full_name'] = 'Test User';

require_once __DIR__ . '/includes/config_enhanced.php';
require_once __DIR__ . '/includes/module_registry.php';

$registry = getModuleRegistry();

$roleMap = [
    'Director General' => 1, 'CEO' => 2, 'Director Academics' => 3,
    'Director Finance' => 4, 'Director ICT' => 5, 'School Principal' => 6,
    'Deputy Principal' => 7, 'Academic Registrar' => 8, 'HR Manager' => 9,
    'School Secretary' => 10, 'School Librarian' => 11, 'Storekeeper' => 21,
    'Guild President' => 22, 'Computer Lab Manager' => 23, 'School Bursar' => 24,
    'Director Admissions & Requirements' => 26, 'Director Admissions' => 28,
    'Head of Nursing' => 29, 'Head of Midwifery' => 30, 'Senior Lecturer' => 31,
    'Lecturer' => 32, 'Security Officer' => 33, 'Driver' => 34,
    'Matron' => 35, 'Warden' => 36, 'Sickbay Nurse' => 37,
    'Computer Lab' => 39, 'Skills Lab Technician' => 40, 'Skills Lab Manager' => 41,
];

echo "<h2>Sidebar Module Test</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Role</th><th>ID</th><th>Modules</th><th>Sidebar Groups</th></tr>";

foreach ($roleMap as $roleName => $roleId) {
    $sidebar = $registry->getSidebarForRole($roleId);
    $moduleCount = 0;
    $groups = [];
    foreach ($sidebar as $dept => $data) {
        $count = count($data['modules']);
        $moduleCount += $count;
        $groups[] = "$dept ($count)";
    }
    $groupStr = implode(', ', $groups);
    echo "<tr><td>$roleName</td><td>$roleId</td><td>$moduleCount</td><td>$groupStr</td></tr>";
}
echo "</table>";
echo "<p>Total roles: " . count($roleMap) . "</p>";
