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
            include('../../logout.php');
            header("Location: ../../organogram.php");
            exit();
        }

    }
?>
