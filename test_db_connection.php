<?php
try {
    $conn = new mysqli('localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db');
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo 'Staff DB connection successful: ' . $conn->host_info . "\n";
    $conn->close();
} catch (Exception $e) {
    echo 'Staff DB connection failed: ' . $e->getMessage() . "\n";
}
?>