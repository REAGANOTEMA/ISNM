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

    $userId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
    $isStudentSession = ($_SESSION['type'] ?? '') === 'student' || ($_SESSION['role'] ?? '') === 'student';

    if (!$userId) {
        header("Location: ../student-login.php");
        exit();
    }

    // Modern sessions already carry type=student; verify against the students table.
    $isStudent = $isStudentSession;
    $studentsConn = getStudentsConnection();
    if ($studentsConn) {
        $sql = 'SELECT id FROM `igangaschool_students`.`students` WHERE id = ? LIMIT 1';
        $stmt = mysqli_prepare($studentsConn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $isStudent = true;
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
