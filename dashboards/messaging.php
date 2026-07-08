<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['director', 'secretary', 'registrar', 'ict', 'admin']);
header('Location: communications.php');
exit;
