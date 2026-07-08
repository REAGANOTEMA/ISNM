<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['bursar', 'school bursar', 'director finance', 'registrar']);
header('Location: school-bursar.php');
exit;
