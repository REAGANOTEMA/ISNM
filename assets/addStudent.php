<?php
include("config.php");
$response = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fname = trim($_POST["fname"] ?? '');
    $lname = trim($_POST["lname"] ?? '');
    $otherName = trim($_POST["other_name"] ?? '');
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

    if (empty($fname) || empty($lname) || empty($course)) {
        echo 'First name, surname and course are required!';
        exit;
    }

    $full_name = trim("$fname $otherName $lname");

    if (!empty($email)) {
        $check = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
        if ($check) {
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo 'Email already exists!';
                $check->close();
                exit;
            }
            $check->close();
        }
    }

    $imageName = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $filename = $_FILES["image"]["name"];
        $tempname = $_FILES["image"]["tmp_name"];
        $fileInfo = pathinfo($filename);
        $fileExtension = strtolower($fileInfo['extension']);
        $allowedExtensions = ['png', 'jpeg', 'jpg'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newName = "S" . time() . rand(1000, 9999) . "." . $fileExtension;
            $folder = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "studentUploads" . DIRECTORY_SEPARATOR . $newName;
            if (move_uploaded_file($tempname, $folder)) {
                $imageName = $newName;
            }
        } else {
            $response = "Invalid image format! (jpg, png, jpeg)";
            echo $response;
            exit;
        }
    }

    $stmtNum = $conn->query("SELECT MAX(CAST(SUBSTRING(student_number, -4) AS UNSIGNED)) as maxNum FROM students WHERE student_number LIKE 'ISNM/%'");
    $row = $stmtNum->fetch_assoc();
    $nextNum = ($row && $row['maxNum']) ? intval($row['maxNum']) + 1 : 1;
    $student_number = sprintf("ISNM/%04d/25", $nextNum);
    $registration_number = $student_number;
    $index_number = "UACE/" . strtoupper(substr(uniqid(), -6)) . "/" . str_pad($nextNum, 4, "0", STR_PAD_LEFT);

    $dob = !empty($_POST["dob"]) ? trim($_POST["dob"]) : null;
    $temp_password = bin2hex(random_bytes(4));
    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
    $intake_year = date('Y');
    $intake_period = date('n') <= 6 ? 'January' : 'July';

    $stmt = $conn->prepare("INSERT INTO students (student_number, registration_number, index_number, first_name, surname, other_name, full_name, email, phone, mobile_number, course, program, current_year, year, level, set_name, gender, date_of_birth, address, nationality, guardian_name, guardian_phone, passport_photo, intake_year, intake_period, status, password, is_first_login, password_changed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, 0, 1)");

    $year = $currentYear;
    if ($stmt) {
        $stmt->bind_param("sssssssssssssiisssssssssss",
            $student_number, $registration_number, $index_number,
            $fname, $lname, $otherName, $full_name,
            $email, $phone, $phone,
            $course, $course, $currentYear, $year, $level, $level,
            $gender, $dob, $address, $nationality,
            $guardian, $gphone, $imageName,
            $intake_year, $intake_period, $password_hash
        );

        if ($stmt->execute()) {
            $response = 'success';
        } else {
            $response = 'Error - Unable to add student: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response = 'Database error: ' . $conn->error;
    }
} else {
    $response = "Invalid request!";
}

echo $response;
?>
