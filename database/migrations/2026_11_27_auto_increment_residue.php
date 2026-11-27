<?php
/**
 * ISNM versioned migration — 2026_11_27_auto_increment_residue
 *
 * Three system tables contain a legacy row with id=0 alongside id=1, which
 * prevents MySQL from adding AUTO_INCREMENT. The id=0 row is re-numbered to
 * max(id)+1; no row is deleted and no other column is changed.
 */
require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = getSystemConnection();
if (!$conn) { echo "FATAL: no system connection\n"; exit(1); }

$tables = ['academic_programs', 'activity_log', 'admission_requirements'];
foreach ($tables as $table) {
    $max = (int) $conn->query("SELECT COALESCE(MAX(id),0) FROM `$table`")->fetch_row()[0];
    $zero = (int) $conn->query("SELECT COUNT(*) FROM `$table` WHERE id=0")->fetch_row()[0];
    if ($zero > 0) {
        $newId = $max + 1;
        if (!$conn->query("UPDATE `$table` SET id=$newId WHERE id=0")) {
            echo "[$table] FAILED renumber: " . $conn->error . PHP_EOL;
            continue;
        }
        echo "[$table] renumbered id 0 -> $newId" . PHP_EOL;
    }
    $col = $conn->query("SELECT column_type FROM information_schema.columns WHERE table_schema='" . SYSTEM_DB_NAME . "' AND table_name='$table' AND column_name='id'")->fetch_row()[0];
    if (!$conn->query("ALTER TABLE `$table` MODIFY COLUMN `id` $col NOT NULL AUTO_INCREMENT")) {
        echo "[$table] FAILED auto_increment: " . $conn->error . PHP_EOL;
    } else {
        echo "[$table] auto_increment enabled" . PHP_EOL;
    }
}
echo 'Complete.' . PHP_EOL;
