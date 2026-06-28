<?php
include("config.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$input = $_POST["val"] ?? "";
$like = $input . "%";

$stmt = $conn->prepare("SELECT * FROM students WHERE request = 'Accepted' AND fname LIKE ?");
$stmt->bind_param("s", $like);
$result = $stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if(mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<div class="alert alert-primary" role="alert">
                    '.$row["fname"].' '.$row["lname"].' Class '.$row["class"].' section '.$row["section"].'  <br>
                       <b>Bus Request Accepted</b>
                </div>';
        }
    } else {
        echo "No pending requests.";
    }
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
mysqli_close($conn);
?>
