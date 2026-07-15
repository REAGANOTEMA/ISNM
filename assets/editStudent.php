<?php

include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = intval($_POST["id"] ?? 0);
    if ($id < 1) { echo 'Invalid student ID'; exit; }

    $fname = trim($_POST["fname"] ?? '');
    $lname = trim($_POST["lname"] ?? '');
    $otherName = trim($_POST["other_name"] ?? '');
    $full_name = trim("$fname $otherName $lname");
    $gender = trim($_POST["gender"] ?? '');
    $course = trim($_POST["course"] ?? '');
    $currentYear = intval($_POST["current_year"] ?? 1);
    $level = trim($_POST["level"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $address = trim($_POST["address"] ?? '');
    $nationality = trim($_POST["nationality"] ?? 'Ugandan');
    $guardian = trim($_POST["guardian"] ?? '');
    $gphone = trim($_POST["gphone"] ?? '');

    $sql = "SELECT * FROM students WHERE id=? AND status != 'deleted'";
    $stmt_check = $conn->prepare($sql);
    if (!$stmt_check) { echo 'Database error'; exit; }
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $query = "UPDATE students SET first_name=?, surname=?, other_name=?, full_name=?, gender=?, course=?, program=?, current_year=?, year=?, level=?, set_name=?, phone=?, mobile_number=?, email=?, address=?, nationality=?, guardian_name=?, guardian_phone=?, updated_at=NOW() WHERE id=?";

        $stmt = $conn->prepare($query);
        if (!$stmt) { echo 'Database error: ' . $conn->error; exit; }

        $year = $currentYear;
        $stmt->bind_param("ssssssiiissssssssi",
            $fname, $lname, $otherName, $full_name,
            $gender, $course, $course, $currentYear, $year, $level, $level,
            $phone, $phone, $email, $address, $nationality,
            $guardian, $gphone, $id
        );

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Something went wrong: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'Student not found';
    }

    $stmt_check->close();

} else {
    echo "Invalid request method";
}
?>
