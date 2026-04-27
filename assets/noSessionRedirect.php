<?php
  // Check if session is already started
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  
  // Check for both old session format (uid) and new format (user_id)
  if(!isset($_SESSION['uid']) && !isset($_SESSION['user_id'])){
        // Redirect to appropriate login page based on URL
        $current_url = $_SERVER['REQUEST_URI'];
        if (strpos($current_url, 'student') !== false || strpos($current_url, 'student_profile') !== false) {
            header("Location: ../student-login.php");
        } else {
            header("Location: ../staff-login.php");
        }
        exit();
  }

?>