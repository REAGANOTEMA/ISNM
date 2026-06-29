<?php
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
$r = $conn->query("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema='igangaschoolofl_staffs_db'");
echo "Total tables: {$r->fetch_assoc()['c']}\n";

// Verify all 4 new tables
foreach (['payroll_bonus','payroll_periods','payroll_items','lab_equipment_checkout'] as $t) {
    $r = $conn->query("SELECT COUNT(*) c FROM `$t`");
    echo "$t: {$r->fetch_assoc()['c']} rows\n";
}

// Verify key columns exist
$checks = [
    ['staff', 'reset_token'],
    ['staff', 'reset_expiry'],
    ['store_requests', 'fulfilled_by'],
    ['store_requests', 'forwarded_to_role'],
    ['security_incidents', 'incident_date'],
    ['security_incidents', 'severity'],
    ['student_welfare_cases', 'severity'],
    ['student_welfare_cases', 'case_description'],
    ['student_welfare_cases', 'reported_by'],
    ['lab_practical_sessions', 'course_name'],
    ['lab_practical_sessions', 'instructor_name'],
    ['payroll_overtime', 'payroll_employee_id'],
    ['payroll_overtime', 'overtime_type'],
    ['payroll_overtime', 'hours_worked'],
    ['payroll_overtime', 'status'],
    ['leave_balance', 'remaining_days'],
];

echo "\n=== Column verification ===\n";
foreach ($checks as $c) {
    $r = $conn->query("SHOW COLUMNS FROM `{$c[0]}` LIKE '{$c[1]}'");
    echo "{$c[0]}.{$c[1]}: " . ($r->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
}
