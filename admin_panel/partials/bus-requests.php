<?php
include("config.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM students WHERE status != 'deleted' LIMIT 50";
$result = mysqli_query($conn, $sql);

if ($result) {
    if(mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $displayName = $row['full_name'] ?: trim($row['first_name'] . ' ' . $row['other_name'] . ' ' . $row['surname']);
            echo '<div class="alert alert-primary" role="alert">
                    '.htmlspecialchars($displayName).' - '.htmlspecialchars($row['course'] ?? '').' Year '.htmlspecialchars($row['year'] ?? '').'  <br>
                    <a href="partials/Accept-request.php?id='.$row['id'].'" class="alert-link">
                        <button type="button" class="btn btn-success">
                            Accept Request
                        </button>
                    </a>
                </div>';
        }
    } else {
        echo "No pending requests.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
