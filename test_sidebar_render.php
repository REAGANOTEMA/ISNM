<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Director General';
$_SESSION['type'] = 'staff';
$_SESSION['full_name'] = 'Test User';
$_SERVER['PHP_SELF'] = '/ISNM/dashboards/director-general.php';
$_GET['page'] = 'home';
include 'C:\xampp\htdocs\ISNM\includes\sidebar.php';
