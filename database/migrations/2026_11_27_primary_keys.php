<?php
/**
 * ISNM versioned migration — 2026_11_27_primary_keys
 *
 * The imported dumps left many tables without a PRIMARY KEY. Duplicate
 * `id` values (often 0) make CRUD operations target the wrong row or fail.
 * This migration adds PRIMARY KEY + AUTO_INCREMENT to tables that already
 * have an integer `id` column. Duplicate/NULL ids are re-numbered to new
 * high values; no row or column is deleted.
 *
 * Run: php database/migrations/2026_11_27_primary_keys.php
 */
require_once __DIR__ . '/../../config/database.php';

mysqli_report(MYSQLI_REPORT_OFF);

function isnm_pk_log($message) {
    echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
}

foreach ([SYSTEM_DB_NAME => getSystemConnection(), WEBSITE_DB_NAME => getWebsiteConnection()] as $dbName => $conn) {
    if (!$conn) { isnm_pk_log("FATAL: no connection for $dbName"); exit(1); }

    $query = "
        SELECT t.table_name, c.column_type
        FROM information_schema.tables t
        JOIN information_schema.columns c
          ON c.table_schema=t.table_schema AND c.table_name=t.table_name
        WHERE t.table_schema=? AND t.table_type='BASE TABLE'
          AND c.column_name='id'
          AND c.data_type IN ('int','bigint','smallint','tinyint','mediumint')
          AND NOT EXISTS (
              SELECT 1 FROM information_schema.statistics s
              WHERE s.table_schema=t.table_schema AND s.table_name=t.table_name AND s.index_name='PRIMARY'
          )
        ORDER BY t.table_name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $dbName);
    $stmt->execute();
    $tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    isnm_pk_log("$dbName: " . count($tables) . ' keyless table(s) with an id column');
    $fixed = 0; $failed = 0;
    foreach ($tables as $table) {
        $name = $table['table_name'];
        $type = $table['column_type'];

        $stats = $conn->query("SELECT COUNT(*) c, COUNT(DISTINCT id) d, COALESCE(MAX(id),0) m FROM `$name`");
        if (!$stats) { isnm_pk_log("  FAILED stats $name: " . $conn->error); $failed++; continue; }
        $s = $stats->fetch_assoc();
        $maxId = (int) $s['m'];
        $needsResequence = ((int) $s['c'] !== (int) $s['d']) || (int) $conn->query("SELECT COUNT(*) FROM `$name` WHERE id IS NULL")->fetch_row()[0] > 0;

        if ($needsResequence) {
            $dupes = $conn->query("SELECT id, COUNT(*) n FROM `$name` GROUP BY id HAVING COUNT(*)>1");
            if ($dupes) {
                $dupeRows = $dupes->fetch_all(MYSQLI_ASSOC);
                foreach ($dupeRows as $dr) {
                    $oldId = $dr['id'] === null ? null : (int) $dr['id'];
                    $extras = (int) $dr['n'] - 1;
                    for ($i = 0; $i < $extras; $i++) {
                        $maxId++;
                        if ($oldId === null) {
                            $ok = $conn->query("UPDATE `$name` SET id=$maxId WHERE id IS NULL LIMIT 1");
                        } else {
                            $ok = $conn->query("UPDATE `$name` SET id=$maxId WHERE id=$oldId LIMIT 1");
                        }
                        if (!$ok) { isnm_pk_log("  FAILED resequence $name: " . $conn->error); break; }
                    }
                }
            }
            while ((int) $conn->query("SELECT COUNT(*) FROM `$name` WHERE id IS NULL")->fetch_row()[0] > 0) {
                $maxId++;
                if (!$conn->query("UPDATE `$name` SET id=$maxId WHERE id IS NULL LIMIT 1")) break;
            }
        }

        $alter = "ALTER TABLE `$name` MODIFY COLUMN `id` $type NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)";
        if (!$conn->query($alter)) {
            isnm_pk_log("  FAILED $name: " . $conn->error);
            $failed++;
        } else {
            $fixed++;
        }
    }
    isnm_pk_log("$dbName: $fixed fixed, $failed failed");
}

isnm_pk_log('Complete. No tables or records were deleted.');
