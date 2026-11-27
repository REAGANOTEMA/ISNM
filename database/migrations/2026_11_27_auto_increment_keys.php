<?php
/**
 * ISNM versioned migration — 2026_11_27_auto_increment_keys
 *
 * The imported dumps defined `id` as a PRIMARY KEY without AUTO_INCREMENT on
 * a large number of tables. Inserts that omit `id` therefore fail or collide
 * at id=0. This migration adds AUTO_INCREMENT to single-column integer `id`
 * primary keys only. It is additive and never drops a table or row.
 *
 * Run: php database/migrations/2026_11_27_auto_increment_keys.php
 */
require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_OFF);

function isnm_ai_log($message) {
    echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
}

$connections = [
    SYSTEM_DB_NAME  => getSystemConnection(),
    WEBSITE_DB_NAME => getWebsiteConnection(),
];

foreach ($connections as $dbName => $conn) {
    if (!$conn) {
        isnm_ai_log("FATAL: no connection for $dbName");
        exit(1);
    }

    $sql = "
        SELECT c.table_name, c.column_type
        FROM information_schema.columns c
        WHERE c.table_schema = ?
          AND c.column_name = 'id'
          AND c.column_key = 'PRI'
          AND c.data_type IN ('int','bigint','smallint','tinyint','mediumint')
          AND c.extra NOT LIKE '%auto_increment%'
          AND (SELECT COUNT(*) FROM information_schema.columns p
               WHERE p.table_schema = c.table_schema
                 AND p.table_name = c.table_name
                 AND p.column_key = 'PRI') = 1
        ORDER BY c.table_name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $dbName);
    if (!$stmt->execute()) {
        isnm_ai_log("FATAL query: " . $stmt->error);
        exit(1);
    }
    $tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    isnm_ai_log("$dbName: " . count($tables) . ' table(s) to update');
    $done = 0;
    foreach ($tables as $table) {
        $tableName = $table['table_name'];
        $columnType = $table['column_type'];
        $alter = "ALTER TABLE `$dbName`.`$tableName` MODIFY COLUMN `id` $columnType NOT NULL AUTO_INCREMENT";
        if (!$conn->query($alter)) {
            isnm_ai_log("  FAILED $tableName: " . $conn->error);
        } else {
            $done++;
        }
    }
    isnm_ai_log("$dbName: $done table(s) updated");
}

isnm_ai_log('Complete. No tables or records were deleted.');
