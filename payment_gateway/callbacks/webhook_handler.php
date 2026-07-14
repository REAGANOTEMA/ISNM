<?php
/**
 * Central Webhook/Callback Handler for all payment providers.
 * POST /payment_gateway/callbacks/webhook_handler.php?provider=mtn_momo
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

$provider = $_GET['provider'] ?? $_POST['provider'] ?? '';

if (!$provider) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing provider parameter']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$headers = getallheaders();

$gateway = GatewayManager::getInstance();
$result = $gateway->processCallback($provider, $payload, $headers);

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => $result['message'] ?? 'Callback processed']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Callback processing failed']);
}
