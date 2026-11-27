<?php
/**
 * ISNM versioned migration — 2026_11_27_critical_integrity
 *
 * Additive only: no DROP, no TRUNCATE, no deletion of records.
 * The only data correction renames duplicate student index numbers by
 * appending a numeric suffix; every student record is preserved.
 *
 * Run: php database/migrations/2026_11_27_critical_integrity.php
 */
require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_OFF);

function isnm_migrate_log($message) {
    echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
}

function isnm_column_exists($conn, $db, $table, $column) {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?');
    if (!$stmt) return false;
    $stmt->bind_param('sss', $db, $table, $column);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return (int)($row[0] ?? 0) > 0;
}

function isnm_index_exists($conn, $db, $table, $index) {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?');
    if (!$stmt) return false;
    $stmt->bind_param('sss', $db, $table, $index);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return (int)($row[0] ?? 0) > 0;
}

function isnm_add_column($conn, $db, $table, $column, $definition, $after = '') {
    if (isnm_column_exists($conn, $db, $table, $column)) {
        isnm_migrate_log("  exists: $db.$table.$column");
        return true;
    }
    $sql = "ALTER TABLE `$db`.`$table` ADD COLUMN `$column` $definition" . ($after !== '' ? " AFTER `$after`" : '');
    if (!$conn->query($sql)) {
        isnm_migrate_log('  FAILED: ' . $sql . ' -> ' . $conn->error);
        return false;
    }
    isnm_migrate_log("  added: $db.$table.$column");
    return true;
}

$system = getSystemConnection();
$website = getWebsiteConnection();
if (!$system || !$website) {
    isnm_migrate_log('FATAL: database connection unavailable');
    exit(1);
}

isnm_migrate_log('1. Remediating duplicate student index numbers (records are preserved)');
$dupes = $system->query("SELECT index_number, GROUP_CONCAT(id ORDER BY id) ids FROM students WHERE index_number IS NOT NULL AND index_number<>'' GROUP BY index_number HAVING COUNT(*)>1");
if ($dupes) {
    $rows = $dupes->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $ids = array_values(array_filter(array_map('intval', explode(',', $row['ids']))));
        $base = $row['index_number'];
        array_shift($ids);
        $suffix = 2;
        foreach ($ids as $id) {
            $new = $base . '-' . $suffix++;
            $stmt = $system->prepare('UPDATE students SET index_number=? WHERE id=?');
            $stmt->bind_param('si', $new, $id);
            if ($stmt->execute()) {
                isnm_migrate_log("  student $id: $base -> $new");
            } else {
                isnm_migrate_log("  FAILED student $id: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

isnm_migrate_log('2. Enforcing unique student index numbers');
if (!isnm_index_exists($system, SYSTEM_DB_NAME, 'students', 'uniq_students_index_number')) {
    $remaining = $system->query('SELECT COUNT(*) c FROM (SELECT index_number FROM students WHERE index_number IS NOT NULL AND index_number<>\'\' GROUP BY index_number HAVING COUNT(*)>1) d');
    $count = (int)($remaining ? $remaining->fetch_assoc()['c'] : 0);
    if ($count > 0) {
        isnm_migrate_log('  SKIPPED: ' . $count . ' duplicate index group(s) remain');
    } elseif (!$system->query('ALTER TABLE `students` ADD UNIQUE INDEX `uniq_students_index_number` (`index_number`)')) {
        isnm_migrate_log('  FAILED: ' . $system->error);
    } else {
        isnm_migrate_log('  added unique index uniq_students_index_number');
    }
} else {
    isnm_migrate_log('  exists: uniq_students_index_number');
}

isnm_migrate_log('3. Website application form columns');
$appColumns = [
    'application_number' => ['varchar(60) NULL', 'id'],
    'other_name' => ['varchar(120) NULL', 'surname'],
    'date_of_birth' => ['date NULL', 'other_name'],
    'gender' => ['varchar(30) NULL', 'date_of_birth'],
    'nationality' => ['varchar(100) NULL', 'gender'],
    'address' => ['text NULL', 'phone'],
    'previous_school' => ['varchar(200) NULL', 'program_applied'],
    'uce_results' => ['varchar(150) NULL', 'previous_school'],
    'uace_results' => ['varchar(150) NULL', 'uce_results'],
    'academic_document' => ['varchar(500) NULL', 'uace_results'],
    'photo' => ['varchar(500) NULL', 'academic_document'],
    'uce_certificate_doc' => ['varchar(500) NULL', 'photo'],
    'uace_certificate_doc' => ['varchar(500) NULL', 'uce_certificate_doc'],
    'unmeb_result_slip' => ['varchar(500) NULL', 'uace_certificate_doc'],
    'unmeb_certificate' => ['varchar(500) NULL', 'unmeb_result_slip'],
    'enrolment_certificate' => ['varchar(500) NULL', 'unmeb_certificate'],
    'practicing_license_doc' => ['varchar(500) NULL', 'enrolment_certificate'],
    'academic_transcript' => ['varchar(500) NULL', 'practicing_license_doc'],
];
foreach ($appColumns as $name => $def) {
    isnm_add_column($website, WEBSITE_DB_NAME, 'student_applications', $name, $def[0], $def[1]);
}

isnm_migrate_log('4. Website volunteer form columns');
foreach ([
    'last_name' => ['varchar(120) NULL', 'first_name'],
    'profession' => ['varchar(150) NULL', 'phone'],
    'opportunity' => ['varchar(200) NULL', 'profession'],
    'duration' => ['varchar(100) NULL', 'availability'],
    'comments' => ['text NULL', 'motivation'],
] as $name => $def) {
    isnm_add_column($website, WEBSITE_DB_NAME, 'volunteer_applications', $name, $def[0], $def[1]);
}

isnm_migrate_log('5. Notification compatibility columns');
foreach (['igangaschool_system' => $system, 'igangaschool_website' => $website] as $dbName => $conn) {
    isnm_add_column($conn, $dbName, 'notifications', 'title', "varchar(255) NOT NULL DEFAULT ''");
    isnm_add_column($conn, $dbName, 'notifications', 'type', "varchar(100) NOT NULL DEFAULT 'general'");
    isnm_add_column($conn, $dbName, 'notifications', 'priority', "varchar(50) NOT NULL DEFAULT 'normal'");
    isnm_add_column($conn, $dbName, 'notifications', 'audience', "varchar(100) NOT NULL DEFAULT 'all'");
    isnm_add_column($conn, $dbName, 'notifications', 'created_by', 'int(11) NOT NULL DEFAULT 0');
}

isnm_migrate_log('6. Staff notification compatibility column');
isnm_add_column($system, SYSTEM_DB_NAME, 'staff_notifications', 'staff_id', 'int(10) unsigned NULL', 'id');
$system->query('UPDATE `staff_notifications` SET `staff_id`=`target_user_id` WHERE `staff_id` IS NULL AND `target_user_id` IS NOT NULL');

isnm_migrate_log('Migration complete. No tables or records were deleted.');
