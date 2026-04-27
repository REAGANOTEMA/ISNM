<?php
session_start();

session_unset();
header('Location: ../staff-login.php');
?>