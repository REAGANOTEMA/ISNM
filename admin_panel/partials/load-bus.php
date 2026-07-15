<?php
include("config.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM students WHERE status != 'deleted' ORDER BY first_name, surname ASC LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result) {
    if(mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
            echo '<div class="alert alert-primary" role="alert">
                    '.htmlspecialchars($displayName).' - '.htmlspecialchars($row['course'] ?? '').' Year '.htmlspecialchars($row['year'] ?? '').'  <br>
                       <b>Active Student</b>
                </div>';
        }
    } else {
        echo "No students found.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
