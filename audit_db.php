<?php
/**
 * COMPREHENSIVE DATABASE AUDIT â€” ISNM ERP
 * Scans all 4 databases, lists every table and its columns
 */
require_once __DIR__ . '/config/database.php';

$databases = [
    'igangaschool_staffs' => ['label' => 'staffs',     'func' => 'getStaffConnection'],
    'igangaschool_students' => ['label' => 'students',  'func' => 'getStudentsConnection'],
    'igangaschool_website' => ['label' => 'website',   'func' => 'getWebsiteConnection'],
    'igangaschool_ict' => ['label' => 'ict',         'func' => 'getICTConnection'],
];

foreach ($databases as $dbName => $info) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "DATABASE: {$dbName} ({$info['label']})\n";
    echo str_repeat('=', 70) . "\n";
    
    $conn = $info['func']();
    if (!$conn || $conn->connect_error) {
        echo "  CONNECTION FAILED: " . ($conn ? $conn->connect_error : 'Function returned null') . "\n";
        continue;
    }
    $conn->set_charset('utf8mb4');
    
    $r = $conn->query("SHOW TABLES");
    if (!$r || $r->num_rows === 0) {
        echo "  No tables found.\n";
        continue;
    }
    
    $tables = [];
    while ($row = $r->fetch_row()) {
        $tables[] = $row[0];
    }
    sort($tables);
    
    echo "  Total tables: " . count($tables) . "\n\n";
    
    foreach ($tables as $tbl) {
        $cols = $conn->query("SHOW COLUMNS FROM `{$tbl}`");
        $colList = [];
        if ($cols) {
            while ($c = $cols->fetch_assoc()) {
                $colList[] = $c['Field'];
            }
        }
        echo "  {$tbl} (" . count($colList) . " cols): " . implode(', ', $colList) . "\n";
    }
}
