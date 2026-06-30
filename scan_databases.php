<?php
require_once __DIR__ . '/config/database.php';

function scanDb($label, $connFunc) {
    $conn = $connFunc();
    if (!$conn) { echo "=== $label: CONNECTION FAILED ===\n"; return; }
    echo "=== $label ===\n";
    $r = $conn->query('SHOW TABLES');
    $tables = [];
    if ($r) while ($row = $r->fetch_row()) $tables[] = $row[0];
    echo 'Tables: ' . count($tables) . "\n";
    foreach ($tables as $t) {
        $escaped = $conn->real_escape_string($t);
        $cols = $conn->query("DESCRIBE `$escaped`");
        echo "  $t: ";
        if ($cols) {
            $parts = [];
            while ($c = $cols->fetch_assoc()) {
                $part = $c['Field'] . '(' . $c['Type'] . ')';
                if ($c['Key'] === 'PRI') $part .= ' [PK]';
                if ($c['Key'] === 'MUL') $part .= ' [FK]';
                if ($c['Key'] === 'UNI') $part .= ' [UQ]';
                $parts[] = $part;
            }
            echo implode(', ', $parts);
        }
        echo "\n";
    }
    // FK query
    $dbName = $conn->db;
    $fks = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($dbName) . "' AND REFERENCED_TABLE_NAME IS NOT NULL");
    if ($fks && $fks->num_rows > 0) {
        echo "  FOREIGN KEYS:\n";
        while ($fk = $fks->fetch_assoc()) {
            echo "    {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
    }
    $conn->close();
    echo "\n";
}

scanDb('STAFFS_DB (igangaschoolofl_staffs_db)', 'getStaffConnection');
scanDb('STUDENTS_DB (igangaschoolofl_students_db)', 'getStudentsConnection');
scanDb('WEBSITE_DB (igangaschoolofl_website_db)', 'getWebsiteConnection');
scanDb('ICT_DB (igangaschoolofl_ict)', 'getICTConnection');
