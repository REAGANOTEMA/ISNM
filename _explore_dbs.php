<?php
require_once __DIR__ . "/config/database.php";

function exploreDatabase($conn, $dbName) {
    if (!$conn) { echo "=== ERROR: Could not connect to {$dbName} ===

"; return; }
    echo "=== DATABASE: {$dbName} ===
";
    $tables = $conn->query("SHOW TABLES");
    if (!$tables) { echo "  ERROR listing tables: " . $conn->error . "
"; return; }
    $allTables = [];
    while ($row = $tables->fetch_array()) { $allTables[] = $row[0]; }
    echo "  Total tables: " . count($allTables) . "

";
    $keywords = ["student", "academic", "exam", "result", "course", "program", "semester", "transcript", "certificate", "graduat", "grade", "curriculum", "intake", "admission", "enroll", "regist", "assess", "mark", "record", "calendar", "placement", "clinical", "approval", "audit", "download"];
    $foundTables = [];
    foreach ($allTables as $table) {
        $lower = strtolower($table);
        foreach ($keywords as $kw) {
            if (strpos($lower, $kw) !== false) { $foundTables[] = $table; break; }
        }
    }
    echo "  --- Academic/Student-related tables found: " . count($foundTables) . " ---
";
    sort($foundTables);
    foreach ($foundTables as $table) {
        echo "
  === TABLE: {$table} ===
";
        $cols = $conn->query("SHOW COLUMNS FROM `{$table}`");
        if ($cols) {
            echo "    COLUMNS:
";
            while ($col = $cols->fetch_assoc()) {
                echo "      {$col['Field']} ({$col['Type']})";
                if ($col['Key'] === 'PRI') echo " [PK]";
                if ($col['Key'] === 'MUL') echo " [INDEX]";
                if ($col['Key'] === 'UNI') echo " [UNIQUE]";
                if ($col['Null'] === 'NO') echo " NOT NULL";
                if ($col['Default'] !== null) echo " DEFAULT: " . $col['Default'];
                if ($col['Extra']) echo " " . $col['Extra'];
                echo "
";
            }
        }
        $cnt = $conn->query("SELECT COUNT(*) as c FROM `{$table}`");
        if ($cnt) { $r = $cnt->fetch_assoc(); echo "    ROWS: {$r['c']}
"; }
    }
    echo "

";
}

$staffConn = getStaffConnection();
exploreDatabase($staffConn, STAFF_DB_NAME);
if ($staffConn) $staffConn->close();

$studentsConn = getStudentsConnection();
exploreDatabase($studentsConn, STUDENTS_DB_NAME);
if ($studentsConn) $studentsConn->close();

$websiteConn = getWebsiteConnection();
exploreDatabase($websiteConn, WEBSITE_DB_NAME);
if ($websiteConn) $websiteConn->close();

$ictConn = getICTConnection();
exploreDatabase($ictConn, ICT_DB_NAME);
if ($ictConn) $ictConn->close();

echo "DONE.
";