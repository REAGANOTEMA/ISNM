<?php
try {
    $conn = new mysqli('localhost', 'root', '', '', 3307);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    
    // Check if staff database exists
    $result = $conn->query("SHOW DATABASES LIKE 'igangaschoolofl_staffs_db'");
    if ($result->num_rows > 0) {
        echo "Staff database exists\n";
        
        // Try to connect to the staff database
        $conn->select_db('igangaschoolofl_staffs_db');
        echo "Connected to staff database successfully\n";
        
        // Check if staff table exists
        $result = $conn->query("SHOW TABLES LIKE 'staff'");
        if ($result->num_rows > 0) {
            echo "Staff table exists\n";
        } else {
            echo "Staff table does not exist\n";
        }
    } else {
        echo "Staff database does not exist\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>