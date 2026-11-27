<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../assets/config.php';

$userId = (int) ($_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ../student-login.php');
    exit();
}

$isStudent = false;
$studentsConn = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
if ($studentsConn) {
    $stmt = mysqli_prepare($studentsConn, 'SELECT id FROM students WHERE id = ? AND (status IS NULL OR status <> \'deleted\') LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $isStudent = $result && mysqli_num_rows($result) > 0;
        }
        mysqli_stmt_close($stmt);
    }
}

if (!$isStudent) {
    unset($_SESSION['uid'], $_SESSION['user_id'], $_SESSION['student_id'], $_SESSION['type'], $_SESSION['role']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_unset();
    session_destroy();
    header('Location: ../student-login.php');
    exit();
}
?>
