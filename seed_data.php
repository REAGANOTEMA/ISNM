<?php
$conn = new mysqli('localhost', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

// Seed leave_types with CORRECT columns (leave_type_name, days_per_year, is_active)
$leaveTypes = [
    [1, 'Annual Leave', 'Annual Leave', 30],
    [2, 'Sick Leave', 'Sick Leave', 14],
    [3, 'Maternity Leave', 'Maternity Leave', 90],
    [4, 'Paternity Leave', 'Paternity Leave', 7],
    [5, 'Compassionate Leave', 'Compassionate Leave', 5],
    [6, 'Study Leave', 'Study Leave', 30],
    [7, 'Casual Leave', 'Casual Leave', 10],
];

echo "=== Seeding leave_types ===\n";
$stmt = $conn->prepare("INSERT IGNORE INTO leave_types (id, type_name, leave_type_name, days_per_year, is_active) VALUES (?, ?, ?, ?, 1)");
foreach ($leaveTypes as $lt) {
    $stmt->bind_param('issi', $lt[0], $lt[1], $lt[2], $lt[3]);
    $stmt->execute();
    echo "  {$lt[1]} - affected: {$stmt->affected_rows}\n";
}
$stmt->close();

// Seed store_categories (status is varchar, not enum)
$storeCats = [
    [1, 'Stationery', 'fas fa-pen'],
    [2, 'Furniture', 'fas fa-chair'],
    [3, 'Electronics', 'fas fa-laptop'],
    [4, 'Cleaning Supplies', 'fas fa-broom'],
    [5, 'Medical Supplies', 'fas fa-heartbeat'],
    [6, 'Laboratory Equipment', 'fas fa-flask'],
    [7, 'Printing Materials', 'fas fa-print'],
    [8, 'General', 'fas fa-boxes'],
];

echo "\n=== Seeding store_categories ===\n";
$stmt = $conn->prepare("INSERT IGNORE INTO store_categories (id, category_name, icon, status) VALUES (?, ?, ?, 'active')");
foreach ($storeCats as $sc) {
    $stmt->bind_param('iss', $sc[0], $sc[1], $sc[2]);
    $stmt->execute();
    echo "  {$sc[1]} - affected: {$stmt->affected_rows}\n";
}
$stmt->close();

echo "\n=== VERIFICATION ===\n";
$r = $conn->query("SELECT COUNT(*) c FROM payroll_allowance_types"); echo "payroll_allowance_types: {$r->fetch_assoc()['c']} rows\n";
$r = $conn->query("SELECT COUNT(*) c FROM payroll_deduction_types"); echo "payroll_deduction_types: {$r->fetch_assoc()['c']} rows\n";
$r = $conn->query("SELECT COUNT(*) c FROM leave_types"); echo "leave_types: {$r->fetch_assoc()['c']} rows\n";
$r = $conn->query("SELECT COUNT(*) c FROM store_categories"); echo "store_categories: {$r->fetch_assoc()['c']} rows\n";
