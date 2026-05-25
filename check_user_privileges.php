<?php
echo "Testing connection as staff user to mysql database to check user privileges...\n\n";

try {
    $conn = new mysqli('localhost', 'igangaschoolofl_staffs_db', 'AgKzJjZZnT5q58jCahs8', 'mysql', 3306);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    echo "✓ Connected to mysql database as staff user!\n";
    
    // Check what privileges this user has
    $result = $conn->query("SELECT Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, Grant_priv FROM mysql.User WHERE User = 'igangaschoolofl_staffs_db'");
    if ($result && $result->num_rows > 0) {
        echo "✓ User privileges:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  Host: " . $row['Host'] . ", User: " . $row['User'] . "\n";
            echo "  Select_priv: " . $row['Select_priv'] . ", Insert_priv: " . $row['Insert_priv'] . "\n";
            echo "  Update_priv: " . $row['Update_priv'] . ", Delete_priv: " . $row['Delete_priv'] . "\n";
            echo "  Create_priv: " . $row['Create_priv'] . ", Drop_priv: " . $row['Drop_priv'] . ", Grant_priv: " . $row['Grant_priv'] . "\n";
        }
        $result->free();
    } else {
        echo "✗ No privileges found for user 'igangaschoolofl_staffs_db' in mysql database\n";
        
        // Let's see what users DO exist
        $result = $conn->query("SELECT Host, User FROM mysql.User");
        if ($result) {
            echo "Existing users:\n";
            while ($row = $result->fetch_assoc()) {
                echo "  Host: " . $row['Host'] . ", User: " . $row['User'] . "\n";
            }
            $result->free();
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
}
?>