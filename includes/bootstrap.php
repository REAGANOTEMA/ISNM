<?php
/**
 * ISNM Enterprise Bootstrap
 * Single entry-point initializer for all pages.
 * Include this at the top of every page instead of scattered config/session calls.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/bootstrap.php';
 *   $auth = EnterpriseAuth::getInstance();
 *   $auth->requireStaff();
 *   $auth->requirePermission('fees');
 */
require_once __DIR__ . '/ErrorHandler.php';
ErrorHandler::register();

require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/EnterpriseAuth.php';

// Auto-load database config
require_once __DIR__ . '/../config/database.php';

// Auto-load helper functions
foreach (['functions.php', 'student_helpers.php', 'notification_helper.php', 'navigation_helper.php'] as $helper) {
    $path = __DIR__ . '/' . $helper;
    if (file_exists($path)) require_once $path;
}
