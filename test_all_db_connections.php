<?php
// Test students database
try {
    $conn = new mysqli('localhost', 'igangaschoolofl_students_db', 'hbkKdmMHUfHTHuxWKPRf', 'igangaschoolofl_students_db');
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo 'Students DB connection successful: ' . $conn->host_info . "\n";
    $conn->close();
} catch (Exception $e) {
    echo 'Students DB connection failed: ' . $e->getMessage() . "\n";
}

// Test website database
try {
    $conn = new mysqli('localhost', 'igangaschoolofl_website_db', 'AaCH75gXpekcFQj5wPZn', 'igangaschoolofl_website_db');
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo 'Website DB connection successful: ' . $conn->host_info . "\n";
    $conn->close();
} catch (Exception $e) {
    echo 'Website DB connection failed: ' . $e->getMessage() . "\n";
}
?>