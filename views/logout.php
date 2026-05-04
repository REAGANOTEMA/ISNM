<?php
/**
 * Logout Handler for ISNM Student Management System
 */

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/config.php';

// Handle logout
$authController = new AuthController();
$authController->logout();
?>
