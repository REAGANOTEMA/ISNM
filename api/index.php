<?php
/**
 * ISNM Centralized API Router
 * Single entry point for all AJAX requests.
 * Route: /api/{module}/{action}
 *
 * Every endpoint returns standardized JSON via Response class.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? ''));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$auth = EnterpriseAuth::getInstance();

// Parse route: /api/{module}/{action}
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$route = trim($route, '/');

$parts = explode('/', $route, 2);
$module = $parts[0] ?? '';
$action = $parts[1] ?? 'index';

if (!$module) {
    Response::error('No module specified', 400);
}

$handlerFile = __DIR__ . '/modules/' . $module . '.php';
if (!file_exists($handlerFile)) {
    Response::notFound("API module '$module' not found");
}

require_once $handlerFile;

$handlerClass = 'Api_' . str_replace('-', '_', ucwords($module, '-'));
if (!class_exists($handlerClass)) {
    Response::notFound("Handler class '$handlerClass' not found in module '$module'");
}

$handler = new $handlerClass($auth);

if (!method_exists($handler, $action)) {
    Response::notFound("Action '$action' not found in module '$module'");
}

try {
    $handler->$action();
} catch (Throwable $e) {
    error_log("[API] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
