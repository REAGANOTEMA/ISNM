<?php
/**
 * ISNM Database Diagnostics - Check all connection details and table status
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>ISNM Database Diagnostics</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:20px auto;padding:20px;}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:5px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:5px 0;}";
echo ".info{color:#17a2b8;background:#d1ecf1;padding:10px;border-radius:4px;margin:5px 0;}";
echo ".warning{color:#ffc107;background:#fff3cd;padding:10px;border-radius:4px;margin:5px 0;}";
echo "table{width:100%;border-collapse:collapse;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
echo "th{background:#f4f4f4;}</style></head><body>";
echo "<h1>ISNM Database Diagnostics</h1>";

// Database configuration
$dbs = [
    'igangaschoolofl_staffs_db' => ['host' => 'localhost', 'user' => 'igangaschoolofl_staffs_db', 'pass' => 'AgKzJjZZnT5q58jCahs8'],
    'igangaschoolofl_students_db' => ['host' => 'localhost', 'user' => 'igangaschoolofl_students_db', 'pass' => 'hbkKdmMHUfHTHuxWKPRf'],
    'igangaschoolofl_website_db' => ['host' => 'localhost', 'user' => 'igangaschoolofl_website_db', 'pass' => 'AaCH75gXpekcFQj5wPZn'],
    'igangaschoolofl_ict' => ['host' => 'localhost', 'user' => 'igangaschoolofl_ict', 'pass' => 'HHCrQVjr6QNKzSEVtx9J'],
];

echo "<h2>Database Connection Status</h2>";
echo "<table><tr><th>Database</th><th>Status</th><th>Tables Found</th></tr>";

foreach ($dbs as $name => $creds) {
    $conn = new mysqli($creds['host'], $creds['user'], $creds['pass'], $name, 3306);
    if ($conn->connect_error) {
        echo "<tr><td>$name</td><td class='error'>✗ CONN FAILED</td><td>N/A</td></tr>";
    } else {
        $tables = $conn->query("SHOW TABLES");
        $tableCount = $tables ? $tables->num_rows : 0;
        $status = $tableCount > 0 ? "<span class='success'>✓ Connected</span>" : "<span class='warning'>⚠ No tables</span>";
        echo "<tr><td>$name</td><td>$status</td><td>$tableCount</td></tr>";
        $conn->close();
    }
}
echo "</table>";

echo "<h2>Required Tables Check</h2>";
$conn_staff = new mysqli('localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db', 3306);
$conn_students = new mysqli('localhost', 'igangaschoolofl_students_db', 'hbkKdmMHUfHTHuxWKPRf', 'igangaschoolofl_students_db', 3306);
$conn_website = new mysqli('localhost', 'igangaschoolofl_website_db', 'AaCH75gXpekcFQj5wPZn', 'igangaschoolofl_website_db', 3306);
$conn_ict = new mysqli('localhost', 'igangaschoolofl_ict', 'HHCrQVjr6QNKzSEVtx9J', 'igangaschoolofl_ict', 3306);

$required = [
    'staffs_db' => ['staff', 'staff_roles', 'staff_activity_log', 'staff_login_sessions'],
    'students_db' => ['students', 'student_fees'],
    'website_db' => ['website_pages', 'website_settings'],
    'ict_db' => ['ict_equipment', 'computer_lab'],
];

echo "<h3>Staffs Database (igangaschoolofl_staffs_db)</h3>";
if (!$conn_staff->connect_error) {
    $tables = $conn_staff->query("SHOW TABLES");
    $existing = [];
    while ($row = $tables->fetch_row()) $existing[] = $row[0];
    foreach ($required['staffs_db'] as $table) {
        echo in_array($table, $existing) ? "<p class='success'>✓ $table exists</p>" : "<p class='error'>✗ $table MISSING</p>";
    }
} else {
    echo "<p class='error'>Cannot connect to staffs database</p>";
}

echo "<h3>Students Database (igangaschoolofl_students_db)</h3>";
if (!$conn_students->connect_error) {
    $tables = $conn_students->query("SHOW TABLES");
    $existing = [];
    while ($row = $tables->fetch_row()) $existing[] = $row[0];
    foreach ($required['students_db'] as $table) {
        echo in_array($table, $existing) ? "<p class='success'>✓ $table exists</p>" : "<p class='warning'>⚠ $table not found (optional)</p>";
    }
} else {
    echo "<p class='error'>Cannot connect to students database</p>";
}

echo "<h2>Staff Accounts</h2>";
if (!$conn_staff->connect_error) {
    $result = $conn_staff->query("SELECT role_name FROM staff_roles LIMIT 10");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>✓ Role: {$row['role_name']}</p>";
        }
    } else {
        echo "<p class='warning'>No staff roles found - run setup_database.php</p>";
    }
    
    $result = $conn_staff->query("SELECT staff_id, full_name, email, position FROM staff LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table><tr><th>Staff ID</th><th>Name</th><th>Email</th><th>Position</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['staff_id']}</td><td>{$row['full_name']}</td><td>{$row['email']}</td><td>{$row['position']}</td></tr>";
        }
        echo "</table>";
    }
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If tables are missing, run <a href='setup_database.php'>setup_database.php</a></li>";
echo "<li>Default staff password: <strong>staff@123</strong></li>";
echo "<li>After setup, delete setup_database.php for security</li>";
echo "<li>Test login at <a href='staff-login.php'>staff-login.php</a></li>";
echo "</ol>";

echo "</body></html>";
?>