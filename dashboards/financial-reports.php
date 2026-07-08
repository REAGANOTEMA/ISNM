<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['bursar', 'school bursar', 'director finance', 'director general']);
require_once __DIR__ . '/../includes/redirect_to_bursar.php';
bursarRedirect('reports');
