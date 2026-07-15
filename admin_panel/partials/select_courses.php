<?php
require_once __DIR__ . '/../../config/database.php';
$conn = getStudentsConnection();
if ($conn) {
    $result = $conn->query("SELECT DISTINCT program_name FROM programs WHERE is_active = 1 ORDER BY program_name");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $val = htmlspecialchars($row['program_name']);
            echo "<option value=\"$val\">$val</option>";
        }
    }
}
?>
