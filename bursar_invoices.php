<?php
/** Redirect to unified bursar dashboard */
require_once __DIR__ . '/includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'school bursar', 'accountant']);
header('Location: dashboards/school-bursar.php?view=generate_invoice');
exit;
