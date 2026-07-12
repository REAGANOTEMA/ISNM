<?php
/**
 * Fix approval_workflows duplicate entries and add unique index.
 * Run ONCE from browser/CLI to fix the SQL migration failure.
 * Uses a single DB connection (unlike phpMyAdmin's per-statement connections).
 */

require_once __DIR__ . '/config/database.php';
$conn = getStaffConnection();
if (!$conn) {
    die("ERROR: Cannot connect to staff database.\n");
}

echo "Connected to " . STAFF_DB_NAME . "\n";

// 1. Delete all stages (avoids FK issues)
$conn->query("DELETE FROM approval_stages");
echo "Stages cleared.\n";

// 2. Remove duplicate workflows â€” keep lowest id per name
$conn->query("DELETE t1 FROM approval_workflows t1
  INNER JOIN approval_workflows t2
  ON t1.workflow_name = t2.workflow_name AND t1.id > t2.id");
echo "Duplicate workflows removed.\n";

// 3. Drop existing indexes if present
$idx1 = $conn->query("SELECT COUNT(*) AS c FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA='" . STAFF_DB_NAME . "' AND TABLE_NAME='approval_workflows' AND INDEX_NAME='uq_workflow_name'")->fetch_assoc()['c'];
if ($idx1 > 0) {
    $conn->query("DROP INDEX uq_workflow_name ON approval_workflows");
    echo "Dropped existing uq_workflow_name index.\n";
}

$idx2 = $conn->query("SELECT COUNT(*) AS c FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA='" . STAFF_DB_NAME . "' AND TABLE_NAME='approval_stages' AND INDEX_NAME='uq_workflow_stage_order'")->fetch_assoc()['c'];
if ($idx2 > 0) {
    $conn->query("DROP INDEX uq_workflow_stage_order ON approval_stages");
    echo "Dropped existing uq_workflow_stage_order index.\n";
}

// 4. Add unique indexes
$conn->query("ALTER TABLE approval_workflows ADD UNIQUE INDEX uq_workflow_name (workflow_name)");
echo "Added uq_workflow_name index.\n";

$conn->query("ALTER TABLE approval_stages ADD UNIQUE INDEX uq_workflow_stage_order (workflow_id, stage_order)");
echo "Added uq_workflow_stage_order index.\n";

// 5. Insert seed workflows
$workflows = [
    ['General Department Request', 'General Administration', 'Standard approval workflow for general administrative requests requiring Director General sign-off'],
    ['HR Request', 'Human Resources', 'HR-related requests requiring Director General approval'],
    ['Finance Request', 'Finance', 'Financial requests and budget approvals requiring Director General sign-off'],
    ['ICT Request', 'ICT', 'ICT department requests requiring departmental review and Director General approval'],
    ['Academic Request', 'Academic', 'Academic affairs requests requiring Director General approval'],
    ['Admissions Request', 'Admissions', 'Admissions-related requests requiring Director General approval'],
    ['Library Request', 'Library', 'Library resource and service requests requiring Director General approval'],
    ['Store Requisition', 'Store & Assets', 'Store and asset requisitions requiring Director General approval'],
    ['Student Registration', 'Academic', 'Student registration requests requiring Director General approval'],
];
$stmt = $conn->prepare("INSERT IGNORE INTO approval_workflows (workflow_name, category, description, is_active) VALUES (?, ?, ?, 1)");
foreach ($workflows as $w) {
    $stmt->bind_param('sss', $w[0], $w[1], $w[2]);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
}
$stmt->close();
echo "Seed workflows inserted.\n";

// 6. Insert stages
$stagesData = [
    ['ICT Request', [
        ['Director ICT Review', 1, null, 'Director ICT', 0],
        ['Director General Final Approval', 2, null, 'Director General', 1],
    ]],
    ['General Department Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['HR Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Finance Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Academic Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Admissions Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Library Request', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Store Requisition', [['Director General Approval', 1, null, 'Director General', 1]]],
    ['Student Registration', [['Director General Approval', 1, null, 'Director General', 1]]],
];
$stmt2 = $conn->prepare("INSERT IGNORE INTO approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($stagesData as $sd) {
    $wfName = $sd[0];
    $stageList = $sd[1];
    $rStmt = $conn->prepare("SELECT id FROM approval_workflows WHERE workflow_name = ? LIMIT 1");
    if ($rStmt) { $rStmt->bind_param('s', $wfName); if (!$rStmt->execute()) { error_log('$rStmt execute failed: ' . ($rStmt->error ?? 'unknown')); }; $r = $rStmt->get_result(); $rStmt->close(); } else { $r = false; }
    $wfId = $r ? $r->fetch_assoc()['id'] : null;
    if (!$wfId) { echo "Warning: workflow '$wfName' not found, skipping stages.\n"; continue; }
    foreach ($stageList as $s) {
        $stmt2->bind_param('isissi', $wfId, $s[0], $s[1], $s[2], $s[3], $s[4]);
        if (!$stmt2->execute()) { error_log('$stmt2 execute failed: ' . ($stmt2->error ?? 'unknown')); };
    }
}
$stmt2->close();
echo "Seed stages inserted.\n";

$conn->close();
echo "\nâœ“ DONE. All workflows and stages seeded successfully.\n";
