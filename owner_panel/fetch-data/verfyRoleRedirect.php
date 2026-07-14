<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
    include('../../config/database.php');
    if(isset($_SESSION['uid'])){

        $userId = $_SESSION['uid'];
        $sql = 'SELECT `role` FROM `users` WHERE `users`.`id`=? ;';

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $row = mysqli_fetch_assoc($result);
        if($row['role'] == 'owner'){

        }else{
            // Destroy session inline (cannot rely on include with POST check during page load)
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_unset();
            session_destroy();
            header("Location: ../../staff-login.php");
            exit();
        }

    }
?>
