<?php
// Redirect to consolidated Director Admissions dashboard
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['admissions', 'director']);
header('Location: director-admissions.php?page=admission_letters');
exit;
