<?php
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
$r = $conn->query("SHOW COLUMNS FROM lab_attendance");
echo "=== lab_attendance columns ===\n";
while ($row = $r->fetch_assoc()) {
    echo "  {$row['Field']} | {$row['Type']}\n";
}

$r2 = $conn->query("SHOW COLUMNS FROM lab_practical_sessions");
echo "\n=== lab_practical_sessions columns ===\n";
while ($row = $r2->fetch_assoc()) {
    echo "  {$row['Field']} | {$row['Type']}\n";
}

$r3 = $conn->query("SHOW COLUMNS FROM payroll_overtime");
echo "\n=== payroll_overtime columns ===\n";
while ($row = $r3->fetch_assoc()) {
    echo "  {$row['Field']} | {$row['Type']}\n";
}

$r4 = $conn->query("SHOW COLUMNS FROM student_welfare_cases");
echo "\n=== student_welfare_cases columns ===\n";
while ($row = $r4->fetch_assoc()) {
    echo "  {$row['Field']} | {$row['Type']}\n";
}
