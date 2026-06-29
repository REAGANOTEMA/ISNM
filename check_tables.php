<?php
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

// Check payroll_allowance_types structure
echo "=== payroll_allowance_types ===\n";
$r = $conn->query("SHOW CREATE TABLE payroll_allowance_types");
$row = $r->fetch_assoc();
echo $row['Create Table'] . "\n\n";

// Check payroll_deduction_types structure
echo "=== payroll_deduction_types ===\n";
$r = $conn->query("SHOW CREATE TABLE payroll_deduction_types");
$row = $r->fetch_assoc();
echo $row['Create Table'] . "\n\n";

// Check existing data
echo "=== Existing allowance types ===\n";
$r = $conn->query("SELECT * FROM payroll_allowance_types");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}

echo "\n=== Existing deduction types ===\n";
$r = $conn->query("SELECT * FROM payroll_deduction_types");
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
