<?php
/**
 * Test multiple MySQL connection methods
 */

echo "Testing MySQL connection methods...<br><br>";

// Method 1: TCP/IP with localhost and empty password
echo "<strong>Method 1: TCP/IP localhost, empty password</strong><br>";
$conn1 = @new mysqli('localhost', 'root', '', '');
if ($conn1->connect_error) {
    echo "Failed: " . $conn1->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn1->close();
}
echo "<br>";

// Method 2: TCP/IP with 127.0.0.1 and empty password
echo "<strong>Method 2: TCP/IP 127.0.0.1, empty password</strong><br>";
$conn2 = @new mysqli('127.0.0.1', 'root', '', '');
if ($conn2->connect_error) {
    echo "Failed: " . $conn2->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn2->close();
}
echo "<br>";

// Method 3: TCP/IP with localhost and empty password, using MYSQLI_CLIENT_FOUND_ROWS
echo "<strong>Method 3: TCP/IP localhost, empty password, MYSQLI_CLIENT_FOUND_ROWS</strong><br>";
$conn3 = @new mysqli('localhost', 'root', '', '', 3306, null, MYSQLI_CLIENT_FOUND_ROWS);
if ($conn3->connect_error) {
    echo "Failed: " . $conn3->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn3->close();
}
echo "<br>";

// Method 4: Using named pipes (Windows)
echo "<strong>Method 4: Named pipes (host: ., socket: MySQL)</strong><br>";
$conn4 = @new mysqli('.', 'root', '', '', 0, 'MySQL');
if ($conn4->connect_error) {
    echo "Failed: " . $conn4->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn4->close();
}
echo "<br>";

// Method 5: Using named pipes with empty socket
echo "<strong>Method 5: Named pipes (host: ., socket: empty)</strong><br>";
$conn5 = @new mysqli('.', 'root', '', '', 0, '');
if ($conn5->connect_error) {
    echo "Failed: " . $conn5->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn5->close();
}
echo "<br>";

// Method 6: Try to connect without specifying a database (for CREATE DATABASE)
echo "<strong>Method 6: TCP/IP localhost, empty password, no database</strong><br>";
$conn6 = @new mysqli('localhost', 'root', '');
if ($conn6->connect_error) {
    echo "Failed: " . $conn6->connect_error . "<br>";
} else {
    echo "Success!<br>";
    $conn6->close();
}
echo "<br>";

// Method 7: Try to get more error details by using mysqli_report
echo "<strong>Method 7: TCP/IP localhost, empty password with mysqli_report</strong><br>";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn7 = new mysqli('localhost', 'root', '');
    echo "Success!<br>";
    $conn7->close();
} catch (mysqli_sql_exception $e) {
    echo "Failed: " . $e->getMessage() . "<br>";
}
echo "<br>";

echo "<em>Done testing.</em>";
?>