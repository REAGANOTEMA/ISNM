<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
header("Location: school-secretary.php");
exit;
