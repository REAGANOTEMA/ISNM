<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['registrar', 'academic registrar', 'lecturer', 'academics']);
header('Location: timetable.php');
exit;
