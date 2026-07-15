<?php
include("config.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$input = $_POST["val"] ?? "";
$like = $input . "%";

$stmt = $conn->prepare("SELECT * FROM students WHERE status != 'deleted' AND (first_name LIKE ? OR surname LIKE ? OR full_name LIKE ? OR student_number LIKE ?) LIMIT 50");
$searchPattern = "%" . $input . "%";
$stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if(mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
            echo '<div class="alert alert-primary" role="alert">
                    '.htmlspecialchars($displayName).' - '.htmlspecialchars($row['course'] ?? '').' Year '.htmlspecialchars($row['year'] ?? '').'  <br>
                       <b>Student Found</b>
                </div>';
        }
    } else {
        echo "No students found.";
    }
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
?>
