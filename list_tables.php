<?php
require_once __DIR__ . '/config/database.php';
$conn = getICTConnection();
if (!$conn) { die("Connection failed: no ICT database connection"); }

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        foreach ($row as $table_name) {
            echo $table_name . "\n";
        }
    }
} else {
    echo "0 results";
}
$conn->close();
?>
