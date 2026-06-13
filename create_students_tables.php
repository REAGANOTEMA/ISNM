<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$port = 3307;
$db = 'igangaschoolofl_students_db';

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
echo "Connected to students database<br>";

// Read and execute the students database creation SQL
$sql = file_get_contents('sql/students/01_create_students_database.sql');
if ($conn->multi_query($sql)) {
    echo 'Students database tables created successfully<br>';
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    echo 'Error creating students database tables: ' . $conn->error . '<br>';
}
$conn->close();
?>