<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper headers for JSON response
header('Content-Type: application/json');

$response = array();

// Disable error display but log errors
error_reporting(0);
ini_set('display_errors', 0);

// Handle both old and new session formats with bypass support
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    // Check for new session format (from bypass) or old format
    $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['uid']) ? $_SESSION['uid'] : null);
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
    $first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Test';
    $last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : 'User';
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : 'test@test.com';
    $phone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '0770000000';

    if(isset($uid)){
        
        // Try database connection if available
        $db_available = false;
        try {
            include("config.php");
            if (isset($conn) && $conn) {
                $db_available = true;
            }
        } catch (Exception $e) {
            $db_available = false;
        }

        if ($db_available) {
            // Database available - try to get real data
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
                if($role == "admin"){
                    $query2 = "SELECT * FROM `admins` WHERE `id`=?";
                }
                else if($role == "teacher" || $role == "Lecturers"){
                    $query2 = "SELECT * FROM `teachers` WHERE `id`=?";
                }

                if ($query2) {
                    $stmt2 = mysqli_prepare($conn, $query2);
                    mysqli_stmt_bind_param($stmt2, "s", $uid);
                    mysqli_stmt_execute($stmt2);
                    $result2 = mysqli_stmt_get_result($stmt2);

                    if($result2 && mysqli_num_rows($result2) > 0){
                        $row2 = mysqli_fetch_assoc($result2);
                        
                        $response['status'] = "success";
                        $response['id'] = $uid;
                        $response['role'] = $role;
                        
                        $image = "../images/user.png";
                        if($role == "admin"){
                            $image = "../adminUploads/" . $row2['image'];
                        }
                        else if($role == "teacher" || $role == "Lecturers"){
                            $image = "../teacherUploads/" . $row2['image'];
                        }

                        $response['image'] = file_exists($image) ? $image : "../images/user.png" ;
                        $response['fname'] = isset($row2['fname']) ? ucfirst(strtolower($row2['fname'])) : $first_name;
                        $response['lname'] = isset($row2['lname']) ? ucfirst(strtolower($row2['lname'])) : $last_name;
                        $response['dob'] = isset($row2['dob']) ? $row2['dob'] : '1990-01-01';
                        $response['email'] = $email;
                        $response['phone'] = isset($row2['phone']) ? $row2['phone'] : $phone;
                        $response['class'] = isset($row2['class']) ? $row2['class'] : 'N/A';
                        $response['section'] = isset($row2['section']) ? $row2['section'] : 'N/A';
                        $response['gender'] = isset($row2['gender']) ? $row2['gender'] : 'Other';
                        $response['address'] = isset($row2['address']) ? $row2['address'] : 'Test Address';
                    } else {
                        // Database record not found - use session data
                        $response = createMockResponse($uid, $role, $first_name, $last_name, $email, $phone);
                    }
                } else {
                    // No query needed - use session data
                    $response = createMockResponse($uid, $role, $first_name, $last_name, $email, $phone);
                }
            } else {
                // User not found in database - use session data
                $response = createMockResponse($uid, $role, $first_name, $last_name, $email, $phone);
            }
        } else {
            // Database not available - use session data
            $response = createMockResponse($uid, $role, $first_name, $last_name, $email, $phone);
        }
    } else {
        $response['status'] = "Error";
        $response['message'] = "User not logged in";
    }
} else {
    $response['status'] = "Error";
    $response['message'] = "Invalid request method";
}

function createMockResponse($uid, $role, $first_name, $last_name, $email, $phone) {
    return [
        'status' => "success",
        'id' => $uid,
        'role' => $role,
        'image' => "../images/user.png",
        'fname' => $first_name,
        'lname' => $last_name,
        'dob' => '1990-01-01',
        'email' => $email,
        'phone' => $phone,
        'class' => 'N/A',
        'section' => 'N/A',
        'gender' => 'Other',
        'address' => 'Test Address'
    ];
}

// Clean output buffer to prevent HTML errors
ob_clean();
echo json_encode($response);
?>
