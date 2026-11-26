<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        include('../assets/config.php');
    } catch (Exception $e) {
        // fall through; authentication via session guards
    }

    $userId = (int)($_SESSION['uid'] ?? $_SESSION['user_id'] ?? 0);

    if (!$userId) {
        header("Location: ../student-login.php");
        exit();
    }

    // The database is the only authority for student identity. Session role
    // flags are never sufficient: a forged session must not grant access.
    $isStudent = false;
    $studentsConn = getStudentsConnection();
    if ($studentsConn) {
        $sql = 'SELECT id FROM `igangaschool_system`.`students` WHERE id = ? AND status <> \'deleted\' LIMIT 1';
        $stmt = mysqli_prepare($studentsConn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($result && mysqli_num_rows($result) > 0) {
                    $isStudent = true;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (!$isStudent) {
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
        header("Location: ../student-login.php");
        exit();
    }
?>
