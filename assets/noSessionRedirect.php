<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  // Bridge new auth session format to legacy student_panel format
  if (isset($_SESSION['user_id']) && !isset($_SESSION['uid']) && ($_SESSION['type'] ?? '') === 'student') {
      $_SESSION['uid'] = $_SESSION['user_id'];
  }

  $hasOldStudentSession = isset($_SESSION['uid']) || isset($_SESSION['user_id']);
  $isStudent = ($_SESSION['type'] ?? '') === 'student' || ($_SESSION['role'] ?? '') === 'student';

  if (!$hasOldStudentSession) {
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, 'student_panel') !== false || strpos($current_url, 'student') !== false) {
            header("Location: ../student-login.php");
        } elseif ($isStudent) {
            header("Location: ../student-login.php");
        } else {
            header("Location: ../organogram.php");
        }
        exit();
  }
?>