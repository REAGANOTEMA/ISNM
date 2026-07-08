<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
bootstrapStaffDashboard(['academics', 'registrar', 'academic registrar', 'lecturer', 'head nursing', 'head midwifery', 'director']);
header('Location: exams-results.php');
exit;
