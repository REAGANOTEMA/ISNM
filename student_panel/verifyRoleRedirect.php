<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
    include('../assets/config.php');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
    if ($userId) {
        $sql = 'SELECT `role` FROM `igangaschool_staffs`.`users` WHERE `users`.`id`=? ;';
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $row = mysqli_fetch_assoc($result);
        $role = strtolower($row['role'] ?? '');
        if ($role !== 'student') {
            include('../assets/logout.php');
            header("Location: ../student-login.php");
            exit();
        }
    } else {
        header("Location: ../student-login.php");
        exit();
    }
?>