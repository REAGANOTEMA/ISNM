<?php
set_time_limit(300);
mysqli_report(MYSQLI_REPORT_OFF);
$c = mysqli_init();
mysqli_real_connect($c, '127.0.0.1', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($c->connect_error) { die('Connect failed: ' . $c->connect_error); }

$files = [
    __DIR__ . '/sql/staffs/igangaschoolofl_staffs_db.sql',
    __DIR__ . '/sql/staffs/staffs_logins.sql',
];

foreach ($files as $file) {
    if (!file_exists($file)) { echo "MISSING: $file\n"; continue; }
    $sql = file_get_contents($file);
    // Strip DELIMITER blocks (CLI-only, not supported by mysqli)
    $sql = preg_replace('/DELIMITER\s+\$\$.*?DELIMITER\s+;/s', '', $sql);
    // Strip SET @var, PREPARE, EXECUTE, DEALLOCATE lines
    $sql = preg_replace('/^\s*(SET\s+@|PREPARE\s+|EXECUTE\s+|DEALLOCATE\s+).*/mi', '', $sql);
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    $ok = $fail = 0;
    foreach ($queries as $q) {
        $q = trim($q);
        if ($q === '' || strncmp($q, '--', 2) === 0 || strncmp($q, '/*', 2) === 0) continue;
        if ($c->query($q)) { $ok++; }
        else {
            // Ignore harmless errors: duplicate table(1050), duplicate column(1060), duplicate entry(1062), unknown table on drop(1051)
            if (!in_array($c->errno, [1050, 1051, 1060, 1062])) {
                echo "[ERR {$c->errno}] " . $c->error . " | " . substr($q, 0, 100) . "\n";
            }
            $fail++;
        }
    }
    echo basename($file) . ": OK=$ok FAIL=$fail\n";
}

// Summary
$r = $c->query("SHOW TABLES");
$tables = [];
while ($row = $r->fetch_row()) $tables[] = $row[0];
echo "\nTables (" . count($tables) . "): " . implode(', ', $tables) . "\n";

$r2 = $c->query("SELECT COUNT(*) FROM staff");
if ($r2) { $row2 = $r2->fetch_row(); echo "Staff records: " . $row2[0] . "\n"; }

$r3 = $c->query("SELECT email, position FROM staff LIMIT 5");
if ($r3) { while ($row3 = $r3->fetch_row()) echo " - " . $row3[0] . " (" . $row3[1] . ")\n"; }

echo "\nDONE - visit http://localhost/ISNM/staff-login.php?position=Director%20General\n";
