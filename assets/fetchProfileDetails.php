<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = array();
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['uid']) ? $_SESSION['uid'] : null);
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

    if (isset($uid)) {
        try {
            include("config.php");
            if (isset($conn) && $conn) {
                $query = "SELECT `email`, `role` FROM `users` WHERE `id`=? OR `user_id`=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ss", $uid, $uid);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    $email = $row['email'];
                    $role = $row['role'];

                    $query2 = "";
                    if ($role == "admin") {
                        $query2 = "SELECT * FROM `admins` WHERE `id`=?";
                    } elseif ($role == "teacher" || $role == "Lecturers") {
                        $query2 = "SELECT * FROM `teachers` WHERE `id`=?";
                    } elseif ($role == "student") {
                        $query2 = "SELECT * FROM `students` WHERE `id`=?";
                    }

                    if ($query2) {
                        $stmt2 = mysqli_prepare($conn, $query2);
                        mysqli_stmt_bind_param($stmt2, "s", $uid);
                        mysqli_stmt_execute($stmt2);
                        $result2 = mysqli_stmt_get_result($stmt2);

                        if ($result2 && mysqli_num_rows($result2) > 0) {
                            $row2 = mysqli_fetch_assoc($result2);

                            $response['status'] = "success";
                            $response['id'] = $uid;
                            $response['role'] = $role;

                            $image = "../images/user.png";
                            if ($role == "admin") {
                                $image = "../adminUploads/" . $row2['image'];
                            } elseif ($role == "teacher" || $role == "Lecturers") {
                                $image = "../teacherUploads/" . $row2['image'];
                            } elseif ($role == "student") {
                                $image = "../studentUploads/" . $row2['image'];
                            }

                            $response['image'] = file_exists($image) ? $image : "../images/user.png";
                            $response['fname'] = isset($row2['fname']) ? ucfirst(strtolower($row2['fname'])) : '';
                            $response['lname'] = isset($row2['lname']) ? ucfirst(strtolower($row2['lname'])) : '';
                            $response['dob'] = isset($row2['dob']) ? $row2['dob'] : '';
                            $response['email'] = $email;
                            $response['phone'] = isset($row2['phone']) ? $row2['phone'] : '';
                            $response['class'] = isset($row2['class']) ? $row2['class'] : '';
                            $response['section'] = isset($row2['section']) ? $row2['section'] : '';
                            $response['gender'] = isset($row2['gender']) ? $row2['gender'] : '';
                            $response['address'] = isset($row2['address']) ? $row2['address'] : '';
                        } else {
                            $response['status'] = "Error";
                            $response['message'] = "Profile not found";
                        }
                    } else {
                        $response['status'] = "Error";
                        $response['message'] = "Unknown role";
                    }
                } else {
                    $response['status'] = "Error";
                    $response['message'] = "User not found in database";
                }
            } else {
                $response['status'] = "Error";
                $response['message'] = "Database connection not available";
            }
        } catch (Exception $e) {
            $response['status'] = "Error";
            $response['message'] = "Database error";
        }
    } else {
        $response['status'] = "Error";
        $response['message'] = "User not logged in";
    }
} else {
    $response['status'] = "Error";
    $response['message'] = "Invalid request method";
}

ob_clean();
echo json_encode($response);
?>
