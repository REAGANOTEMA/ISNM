<?php
include("config.php");
session_start();
$response = array();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $uid = $_SESSION['uid'];

    if (
        isset($_POST['fname']) &&
        isset($_POST['lname']) &&
        isset($_POST['email']) &&
        isset($_POST['dob']) &&
        isset($_POST['gender']) &&
        isset($_POST['phone']) &&
        isset($_POST['address'])
    ) {
        // Determine user role
        $roleQuery = "SELECT `role` FROM `users` WHERE `id`=?";
        $roleStmt = mysqli_prepare($conn, $roleQuery);
        mysqli_stmt_bind_param($roleStmt, "s", $uid);
        mysqli_stmt_execute($roleStmt);
        $roleResult = mysqli_stmt_get_result($roleStmt);
        $roleRow = mysqli_fetch_assoc($roleResult);
        $role = $roleRow ? $roleRow['role'] : '';
        mysqli_stmt_close($roleStmt);

        $profileUpdated = false;

        if ($role == "admin") {
            $query = "UPDATE `admins` SET `fname` = ?, `lname` = ?, `dob` = ?, `phone` = ?, `gender` = ?, `address` = ? WHERE `admins`.`id` = ?;";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $_POST['fname'], $_POST['lname'], $_POST['dob'], $_POST['phone'], $_POST['gender'], $_POST['address'], $uid);
            $profileUpdated = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } elseif ($role == "teacher" || $role == "Lecturers") {
            $query = "UPDATE `teachers` SET `fname` = ?, `lname` = ?, `dob` = ?, `phone` = ?, `gender` = ?, `address` = ? WHERE `teachers`.`id` = ?;";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssss", $_POST['fname'], $_POST['lname'], $_POST['dob'], $_POST['phone'], $_POST['gender'], $_POST['address'], $uid);
            $profileUpdated = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $sql = 'UPDATE `users` SET `email` = ? WHERE `users`.`id` = ?;';
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, "ss", $_POST['email'], $uid);
        $userUpdated = mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        if ($profileUpdated && $userUpdated) {
            $response['status'] = "success";
            $response['message'] = "Profile Edited Successfully.";
        } else {
            $response['status'] = "Error";
            $response['message'] = "Something went wrong!";
        }
    } else {
        $response['status'] = "Error";
        $response['message'] = "Something went wrong!";
    }
} else {
    $response['status'] = "Error";
    $response['message'] = "Something went wrong!";
}
echo json_encode($response);
?>
