<?php
/**
 * Test Database Connection and Debug Login
 * This file tests the database connection and displays user data for debugging
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

    // Debug: Show all users with their details
    echo "<h3>All Users (for debugging):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Password Hash</th></tr>";
    $all_users = $conn->query("SELECT id, full_name, email, role, status, LEFT(password, 20) as password_hash FROM users ORDER BY role");
    while ($row = $all_users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td><code>" . htmlspecialchars($row['password_hash']) . "...</code></td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test password verification with sample data
    echo "<h3>Password Verification Test:</h3>";
    $test_users = $conn->query("SELECT email, password FROM users WHERE role != 'student' LIMIT 3");
    while ($row = $test_users->fetch_assoc()) {
        $test_password = 'password123';
        $verify_result = password_verify($test_password, $row['password']);
        echo "<p><strong>" . htmlspecialchars($row['email']) . "</strong> with password 'password123': ";
        echo $verify_result ? "<span style='color: green;'>✅ Valid</span>" : "<span style='color: red;'>❌ Invalid</span>";
        echo "</p>";
    }

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
    echo "<li>Check MySQL credentials (root/ReagaN23#)</li>";
    echo "<li>Run the SQL setup script if database doesn't exist</li>";
    echo "</ol>";
}
?>
