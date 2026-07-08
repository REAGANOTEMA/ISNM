<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['registrar', 'academic registrar', 'academics', 'secretary']);
header('Location: course-registration.php');
exit;
