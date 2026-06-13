<?php
function testConn($name, $host, $user, $pass, $db, $port) {
    echo "$name ($host:$port): ";
    try {
        $conn = new mysqli($host, $user, $pass, $db, $port);
        if ($conn->connect_error) {
            echo "failed: " . $conn->connect_error . "\n";
        } else {
            echo "OK (" . $conn->host_info . ")\n";
            $conn->close();
        }
    } catch (Throwable $e) {
        echo "exception: " . $e->getMessage() . "\n";
    }
}

testConn('staff_3306', 'localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db', 3306);
testConn('staff_3307', 'localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db', 3307);
testConn('root_3306', 'localhost', 'root', '', 'mysql', 3306);
testConn('root_3307', 'localhost', 'root', '', 'mysql', 3307);
