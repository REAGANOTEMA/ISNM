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

// Insert the exact 8 document requirements + 20 supply items
$requirements = [
    // 8 Document Requirements
    ['Completed Application Form', 'Document', 1, 1],
    ['A-Level Certificate (UACE)', 'Document', 2, 1],
    ['O-Level Certificate (UCE)', 'Document', 3, 1],
    ['Birth Certificate', 'Document', 4, 1],
    ['Passport Photos (4)', 'Photo', 5, 1],
    ['National ID Copy', 'Document', 6, 1],
    ['Medical Report', 'Document', 7, 1],
    ['Recommendation Letter (LC1)', 'Document', 8, 1],
    
    // 20 Supply Items
    ['Surgical Gloves', 'Other', 9, 0],
    ['Examination Gloves', 'Other', 10, 0],
    ['Photocopying Ream', 'Other', 11, 0],
    ['Ruled Paper Reams', 'Other', 12, 0],
    ['Omo', 'Other', 13, 0],
    ['Toilet Papers', 'Other', 14, 0],
    ['Compound brooms', 'Other', 15, 0],
    ['Soft brooms', 'Other', 16, 0],
    ['Rake', 'Other', 17, 0],
    ['Cobweb brush', 'Other', 18, 0],
    ['Scrubbing Brush', 'Other', 19, 0],
    ['Squeezer', 'Other', 20, 0],
    ['Toilet Brush', 'Other', 21, 0],
    ['JIK', 'Other', 22, 0],
    ['Vim', 'Other', 23, 0],
    ['Mops', 'Other', 24, 0],
    ['Sanitizer', 'Other', 25, 0],
    ['Liquid Soap', 'Other', 26, 0],
    ['Face Masks', 'Other', 27, 0],
    ['Heavy duty Gloves', 'Other', 28, 0]
];

$stmt = $conn->prepare("INSERT INTO admission_requirements (requirement_name, type, display_order, is_mandatory, is_active) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param("ssii", $name, $type, $order, $mandatory);

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
echo "- 20 Supply Items (optional)\n";
echo "\nNote: Proof of Payment and Interview Letter have been removed as requested.\n";
?>