<?php
try {
    $conn = new mysqli('localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db', 3307);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo 'Connection successful';
    $conn->close();
} catch (Exception $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>