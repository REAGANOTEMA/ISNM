<?php
header('Content-Type: text/plain');

// Try different connection methods
echo "Testing database connections...\n\n";

// Method 1: Standard TCP on port 3307
echo "1. Testing TCP connection to localhost:3307 as staff user:\n";
try {
    $conn = new mysqli('localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', '', 3307);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "   Connected successfully!\n";
    
    // Try to select the database
    if ($conn->select_db('igangaschoolofl_staffs_db')) {
        echo "   Selected database 'igangaschoolofl_staffs_db' successfully!\n";
        
        // Check if staff table exists
        $result = $conn->query("SHOW TABLES LIKE 'staff'");
        if ($result && $result->num_rows > 0) {
            echo "   Staff table exists!\n";
        } else {
            echo "   Staff table does NOT exist.\n";
        }
    } else {
        echo "   Failed to select database: " . $conn->error . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "   Connection failed: " . $e->getMessage() . "\n";
}

// Method 2: Try with root user
echo "\n2. Testing TCP connection to localhost:3307 as root user:\n";
try {
    $conn = new mysqli('localhost', 'root', '', '', 3307);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "   Connected successfully!\n";
    
    // Show databases
    $result = $conn->query("SHOW DATABASES");
    if ($result) {
        echo "   Databases:\n";
        while ($row = $result->fetch_assoc()) {
            echo "     - " . $row['Database'] . "\n";
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "   Connection failed: " . $e->getMessage() . "\n";
}

// Method 3: Try via socket
echo "\n3. Testing socket connection:\n";
try {
    $conn = new mysqli(null, 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'igangaschoolofl_staffs_db', null, '/tmp/mysql.sock');
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "   Connected successfully via socket!\n";
    $conn->close();
} catch (Exception $e) {
    echo "   Socket connection failed: " . $e->getMessage() . "\n";
}

// Method 4: Try via socket with root
echo "\n4. Testing socket connection as root:\n";
try {
    $conn = new mysqli(null, 'root', '', '', null, '/tmp/mysql.sock');
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "   Connected successfully via socket as root!\n";
    $conn->close();
} catch (Exception $e) {
    echo "   Socket connection as root failed: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
?>