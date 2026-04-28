<?php
/**
 * Test Database Connection
 * This file tests the database connection and displays status
 */

// Include database connection
require_once 'db.php';

// Test connection
try {
    $conn = getDatabaseConnection();
    
    // Test basic query
    $result = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'isnm_db'");
    $row = $result->fetch_assoc();
    
    echo "<h2>Database Connection Test Results</h2>";
    echo "<p><strong>Status:</strong> <span style='color: green;'>✅ Connected Successfully</span></p>";
    echo "<p><strong>Database:</strong> isnm_db</p>";
    echo "<p><strong>Tables Found:</strong> " . $row['table_count'] . "</p>";
    
    // Test users table
    $user_result = $conn->query("SELECT COUNT(*) as user_count FROM users");
    $user_row = $user_result->fetch_assoc();
    echo "<p><strong>Users in Database:</strong> " . $user_row['user_count'] . "</p>";
    
    // Test sample users
    $sample_result = $conn->query("SELECT full_name, type, role FROM users LIMIT 5");
    echo "<h3>Sample Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Type</th><th>Role</th></tr>";
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Connection Details:</h3>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Character Set:</strong> " . $conn->character_set_name() . "</p>";
    
} catch (Exception $e) {
    echo "<h2>Database Connection Test Results</h2>";
    echo "<p><strong>Status:</strong> <span style='color: red;'>❌ Connection Failed</span></p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Check if XAMPP/MySQL is running</li>";
    echo "<li>Verify database name 'isnm_db' exists</li>";
    echo "<li>Check MySQL credentials (root/empty password)</li>";
    echo "<li>Run the SQL setup script if database doesn't exist</li>";
    echo "</ol>";
}
?>
