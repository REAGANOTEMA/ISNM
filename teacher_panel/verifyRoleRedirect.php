<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

    try {
        include('../assets/config.php');
    } catch (Exception $e) {
        $conn = null;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? null;
    if (!$userId) {
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
        header("Location: ../staff-login.php");
        exit();
    }

    $role = '';
    $staffConn = getStaffConnection();
    if ($staffConn) {
        $sql = 'SELECT LOWER(sr.role_name) AS role_name
                FROM `igangaschool_staffs`.`staff` s
                INNER JOIN `igangaschool_staffs`.`staff_roles` sr ON s.role_id = sr.id
                WHERE s.id = ? LIMIT 1';
        $stmt = mysqli_prepare($staffConn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            $role = strtolower(trim($row['role_name'] ?? ''));
            mysqli_stmt_close($stmt);
        }
    }

    $isTeacher = strpos($role, 'teacher') !== false
        || strpos($role, 'lecturer') !== false;

    if (!$isTeacher) {
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
        header("Location: ../staff-login.php");
        exit();
    }
?>
