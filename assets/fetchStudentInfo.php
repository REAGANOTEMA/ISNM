<?php

include('config.php');

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $data = array('id' => $id);

    $sql = "SELECT * FROM students WHERE id = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { echo json_encode($data); exit; }
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows) {
        $row = $result->fetch_assoc();
        $data["fname"] = $row["first_name"] ?? '';
        $data["lname"] = $row["surname"] ?? '';
        $data["other_name"] = $row["other_name"] ?? '';
        $data["gender"] = $row["gender"] ?? '';
        $data["image"] = $row["profile_picture"] ?? $row["passport_photo"] ?? '';

        $data["dob"] = $row["date_of_birth"] ?? '';
        if (!empty($data["dob"])) {
            $timestamp = strtotime($data["dob"]);
            $data["dob"] = date('Y-m-d', $timestamp);
        }

        $data["phone"] = $row["phone"] ?? $row["mobile_number"] ?? '';
        $data["email"] = $row["email"] ?? '';
        $data["address"] = $row["address"] ?? '';
        $data["nationality"] = $row["nationality"] ?? '';

        $data["course"] = $row["course"] ?? $row["program"] ?? '';
        $data["current_year"] = $row["current_year"] ?? $row["year"] ?? 1;
        $data["level"] = $row["level"] ?? '';
        $data["set_name"] = $row["set_name"] ?? '';
        $data["student_number"] = $row["student_number"] ?? '';
        $data["registration_number"] = $row["registration_number"] ?? '';
        $data["index_number"] = $row["index_number"] ?? '';
        $data["national_student_id_number"] = $row["national_student_id_number"] ?? '';
        $data["status"] = $row["status"] ?? 'Active';

        $data["guardian"] = $row["guardian_name"] ?? '';
        $data["gphone"] = $row["guardian_phone"] ?? '';
    }

    header('Content-Type: application/json');
    echo json_encode($data);

    $stmt->close();
}
?>
