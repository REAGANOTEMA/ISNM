<?php
include("../../assets/config.php");

// Assuming form is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data 
    $panel = $_POST['panel'] ?? '';
    $class = $_POST['cla'] ?? '';
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';

    // Insert data into database using prepared statement
    $query = "INSERT INTO notice(title, body, role, class) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $title, $message, $panel, $class);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($result) {
            echo "Notice saved successfully.";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Handle case where form is not submitted via POST
    echo "Form submission method not recognized.";
}
