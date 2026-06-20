<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['principal', 'deputy']);
header("Location: school-principal.php");
exit;
