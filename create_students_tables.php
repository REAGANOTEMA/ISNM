<?php
require_once __DIR__ . '/config/database.php';

$conn = getStudentsConnection();
if (!$conn) {
    die('Connection failed');
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