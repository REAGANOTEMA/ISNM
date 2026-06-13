<?php
/**
 * ISNM Database Diagnostics - Check connection status and required tables.
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><title>ISNM Database Diagnostics</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}';
echo '.success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:5px 0;}';
echo '.error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:5px 0;}';
echo '.warning{color:#856404;background:#fff3cd;padding:10px;border-radius:4px;margin:5px 0;}';
echo 'table{width:100%;border-collapse:collapse;margin:10px 0;}';
echo 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}';
echo 'th{background:#f4f4f4;}</style></head><body>';
echo '<h1>ISNM Database Diagnostics</h1>';

$dbs = [
    'staffs' => ['name' => STAFF_DB_NAME, 'conn' => getStaffConnection()],
    'students' => ['name' => STUDENTS_DB_NAME, 'conn' => getStudentsConnection()],
    'website' => ['name' => WEBSITE_DB_NAME, 'conn' => getWebsiteConnection()],
    'ict' => ['name' => ICT_DB_NAME, 'conn' => getICTConnection()],
];

echo '<h2>Database Connection Status</h2>';
echo '<table><tr><th>Database</th><th>Status</th><th>Tables Found</th></tr>';

foreach ($dbs as $key => $db) {
    $conn = $db['conn'];
    if (!$conn) {
        echo '<tr><td>' . htmlspecialchars($db['name']) . '</td><td class="error">✗ Connection failed</td><td>N/A</td></tr>';
        continue;
    }

    $tables = $conn->query('SHOW TABLES');
    $tableCount = $tables ? $tables->num_rows : 0;
    $status = $tableCount > 0 ? '<span class="success">✓ Connected</span>' : '<span class="warning">⚠ No tables found</span>';
    echo '<tr><td>' . htmlspecialchars($db['name']) . '</td><td>' . $status . '</td><td>' . (int) $tableCount . '</td></tr>';
    $conn->close();
}
echo '</table>';

$required = [
    'staffs' => ['staff', 'staff_roles', 'staff_activity_log', 'staff_login_sessions'],
    'students' => ['students', 'student_fees'],
    'website' => ['website_pages', 'website_settings'],
    'ict' => ['ict_equipment', 'computer_lab'],
];

echo '<h2>Required Tables Check</h2>';
foreach ($required as $key => $tables) {
    $conn = null;
    switch ($key) {
        case 'staffs': $conn = getStaffConnection(); break;
        case 'students': $conn = getStudentsConnection(); break;
        case 'website': $conn = getWebsiteConnection(); break;
        case 'ict': $conn = getICTConnection(); break;
    }

    echo '<h3>' . htmlspecialchars($dbs[$key]['name']) . '</h3>';
    if (!$conn) {
        echo '<p class="error">Cannot connect to database</p>';
        continue;
    }

    $result = $conn->query('SHOW TABLES');
    $existing = [];
    if ($result) {
        while ($row = $result->fetch_row()) {
            $existing[] = $row[0];
        }
    }

    foreach ($tables as $table) {
        $class = in_array($table, $existing, true) ? 'success' : ($key === 'students' ? 'warning' : 'error');
        $message = in_array($table, $existing, true) ? '✓ exists' : ($key === 'students' ? '⚠ optional table not found' : '✗ missing');
        echo '<p class="' . $class . '">' . htmlspecialchars($table) . ' ' . $message . '</p>';
    }
    $conn->close();
}

echo '<hr><h2>Next Steps</h2><ol>';
echo '<li>If tables are missing, run the appropriate setup/import script from a trusted local environment.</li>';
echo '<li>After setup, remove diagnostic and setup scripts from the public hosting folder.</li>';
echo '<li>Test login at <a href="staff-login.php">staff-login.php</a> or <a href="student-login.php">student-login.php</a>.</li>';
echo '</ol></body></html>';
?>
