<?php
require_once __DIR__ . '/../includes/auto_deduction_processor.php';

$action = $_GET['action'] ?? 'run';
$limit = min((int)($_GET['limit'] ?? 50), 200);
$key = $_GET['key'] ?? '';

$secretKey = defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : 'isnm-auto-deduction-key-2026';

if ($key !== $secretKey && php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing cron key. Use ?key=your-secret-key or run via CLI.']);
    exit;
}

header('Content-Type: application/json');

$startTime = microtime(true);

switch ($action) {
    case 'run':
        $results = processAutoDeductions($limit);
        $elapsed = round(microtime(true) - $startTime, 2);
        echo json_encode([
            'success' => true,
            'processed' => $results['processed'],
            'successful' => $results['success'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped'],
            'errors' => $results['errors'],
            'execution_time' => $elapsed . 's',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        break;

    case 'stats':
        $stats = getSubscriptionStats();
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        break;

    case 'list':
        $status = $_GET['status'] ?? null;
        $subscriptions = getAllSubscriptions($status, $limit);
        echo json_encode([
            'success' => true,
            'count' => count($subscriptions),
            'subscriptions' => $subscriptions,
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action. Use: run, stats, list']);
}
