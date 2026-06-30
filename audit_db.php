<?php
/**
 * COMPREHENSIVE DATABASE AUDIT — ISNM ERP
 * Scans all 3 databases, lists every table and its columns
 */

$host = '127.0.0.1';
$port = 3307;
$user = 'igangaschoolofl_staffs_db';
$pass = 'AgKzJjZZnT5q58jCahs8';

$databases = [
    'igangaschoolofl_staffs_db' => 'staffs',
    'igangaschoolofl_students_db' => 'students',
    'igangaschoolofl_website_db' => 'website',
];

foreach ($databases as $dbName => $label) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "DATABASE: {$dbName} ({$label})\n";
    echo str_repeat('=', 70) . "\n";
    
    $conn = @new mysqli($host, $user, $pass, $dbName, $port);
    if ($conn->connect_error) {
        $conn = @new mysqli($host, $user, $pass, $dbName);
    }
    if ($conn->connect_error) {
        echo "  CONNECTION FAILED: " . $conn->connect_error . "\n";
        continue;
    }
    $conn->set_charset('utf8mb4');
    
    $r = $conn->query("SHOW TABLES");
    if (!$r || $r->num_rows === 0) {
        echo "  No tables found.\n";
        $conn->close();
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
    
    $conn->close();
}
