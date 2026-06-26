<?php
require 'C:\xampp\htdocs\ISNM\config\database.php';
$conn = getStaffConnection();

// Seed `roles` table (id, name, description, created_at) from `staff_roles` (id, role_name, role_description, created_at)
$result = $conn->query("SELECT id, role_name, role_description, created_at FROM staff_roles ORDER BY id");
if ($result && $result->num_rows > 0) {
    $count = 0;
    $stmt = $conn->prepare("INSERT IGNORE INTO roles (id, name, description, created_at) VALUES (?, ?, ?, ?)");
    if (!$stmt) { echo "Prepare error: " . $conn->error . PHP_EOL; exit; }
    $stmt->bind_param("isss", $id, $name, $desc, $created);
    
    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['id'];
        $name = $row['role_name'];
        $desc = $row['role_description'];
        $created = $row['created_at'];
        if ($stmt->execute()) {
            $count += $stmt->affected_rows;
        } else {
            echo "Error inserting role $name: " . $stmt->error . PHP_EOL;
        }
    }
    $stmt->close();
    echo "Seeded $count roles into `roles` table." . PHP_EOL;
} else {
    echo "No rows from staff_roles or error: " . ($result ? 'Empty' : $conn->error) . PHP_EOL;
}

// Verify
$r2 = $conn->query("SELECT COUNT(*) as cnt FROM roles");
echo "Roles table now has: " . ($r2 ? $r2->fetch_assoc()['cnt'] : 'error') . " rows" . PHP_EOL;

echo PHP_EOL . "Roles:" . PHP_EOL;
$r3 = $conn->query("SELECT id, name FROM roles ORDER BY id");
if ($r3) { while ($row = $r3->fetch_assoc()) { echo "  {$row['id']}: {$row['name']}" . PHP_EOL; } }

$conn->close();
