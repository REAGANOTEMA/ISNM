<?php
require_once __DIR__ . "/config/database.php";
$conn = getStaffConnection();
if (!$conn) { echo "FAIL\n"; exit(1); }
$r = $conn->query("SELECT id, workflow_name, category FROM igangaschoolofl_staffs_db.approval_workflows ORDER BY id");
while ($row = $r->fetch_assoc()) {
    echo "{$row['id']}: {$row['workflow_name']} ({$row['category']})\n";
}
$conn->close();
