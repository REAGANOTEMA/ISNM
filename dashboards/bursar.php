<?php
/**
 * Bursar Dashboard — permanent redirect to unified school-bursar.php
 * All features have been merged into dashboards/school-bursar.php
 */
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['bursar', 'finance', 'school bursar', 'accountant']);
$target = 'school-bursar.php';
$query = $_GET;
unset($query['_']);
if (!empty($query)) {
    $target .= '?' . http_build_query($query);
}
header('Location: ' . $target);
exit;
