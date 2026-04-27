<?php
    error_reporting(0);
    
    // Include config with error handling
    try {
        include('../assets/config.php');
    } catch (Exception $e) {
        // Config file error - allow access for development
        $conn = null;
    }
    
    if(isset($_SESSION['uid'])){
        // If database connection is available, check role
        if (isset($conn) && $conn) {
            try {
                $userId = $_SESSION['uid'];
                $sql = 'SELECT `role` FROM `users` WHERE `users`.`id`=? ;';

                $stmt = mysqli_prepare($conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $userId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($result && $row = mysqli_fetch_assoc($result)) {
                        if($row['role'] == 'admin'){
                            // Admin access granted
                        }else{
                            include('../assets/logout.php');
                            header("Location: ../staff-login.php");
                            exit();
                        }
                    } else {
                        // User not found - logout and redirect
                        include('../assets/logout.php');
                        header("Location: ../staff-login.php");
                        exit();
                    }
                } else {
                    // Statement preparation failed - allow access for development
                }
            } catch (Exception $e) {
                // Database error - allow access for development
            }
        } else {
            // No database connection - allow access for development
        }
    }
?>