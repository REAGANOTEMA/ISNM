<?php
/**
 * Sync admission requirements between director admissions and secretary databases
 * This ensures both systems have the same 8 document requirements + 20 supply items
 */

require __DIR__ . '/../config/database.php';

$conn = getStaffConnection();
if (!$conn) {
    die("FATAL: Could not connect to staff DB.\n");
}

echo "Syncing admission requirements...\n";

// First, clear existing requirements
$conn->query("DELETE FROM admission_requirements");
echo "Cleared existing requirements\n";

/**
 * SECRETARY ADMISSION CHECKLIST SYNC
 * Required checklist items only (8 documents).
 * Proof of Payment + Interview have been intentionally omitted.
 */
$requirements = [
    ['Completed Application Form', 'Document', 1, 1],
    ['A-Level Certificate (UACE)', 'Document', 2, 1],
    ['O-Level Certificate (UCE)', 'Document', 3, 1],
    ['Birth Certificate', 'Document', 4, 1],
    ['Passport Photos (4)', 'Photo', 5, 1],
    ['National ID Copy', 'Document', 6, 1],
    ['Medical Report', 'Document', 7, 1],
    ['Recommendation Letter (LC1)', 'Document', 8, 1],
];

$stmt = $conn->prepare(
    "INSERT INTO admission_requirements (requirement_name, type, display_order, is_mandatory, is_active)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssiii", $name, $type, $order, $mandatory, $isActive);
$isActive = 1;

$inserted = 0;
foreach ($requirements as $req) {
    list($name, $type, $order, $mandatory) = $req;
    if ($stmt->execute()) {
        $inserted++;
        echo "  Added: $name\n";
    } else {
        echo "  ERROR: $name - " . $conn->error . "\n";
    }
}

$stmt->close();

echo "\nDone. Inserted $inserted requirements.\n";
echo "\nRequirements have been synced with:\n";
echo "- 8 Document Requirements (mandatory)\n";
echo "- (No supply items included)\n";
echo "\nNote: Proof of Payment and Interview Letter have been removed as requested.\n";
?>

