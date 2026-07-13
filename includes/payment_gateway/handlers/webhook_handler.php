<?php
/**
 * Unified Webhook/Callback Handler for ISNM Payment Gateway.
 * Receives callbacks from all providers, validates signatures,
 * routes to correct adapter, and updates transaction status.
 *
 * Usage: POST /includes/payment_gateway/handlers/webhook_handler.php?provider=mtn_momo
 *        POST /includes/payment_gateway/handlers/webhook_handler.php?provider=stripe
 *        POST /includes/payment_gateway/handlers/webhook_handler.php?provider=flutterwave
 *        etc.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../PaymentService.php';

$providerKey = $_GET['provider'] ?? '';
if (empty($providerKey)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing provider parameter']);
    exit();
}

$rawBody = file_get_contents('php://input');
if (empty($rawBody)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty request body']);
    exit();
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit();
}

$service = new PaymentService();
$gateway = $service->getGateway($providerKey);

if (!$gateway) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Unknown provider: ' . $providerKey]);
    exit();
}

$signatureHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$signatureValid = $gateway->verifyWebhookSignature($rawBody, $signatureHeader);

$eventType = $payload['type'] ?? $payload['event'] ?? $payload['event_type'] ?? 'unknown';

if (!$signatureValid) {
    $service->logWebhookEvent($providerKey, $eventType, $rawBody, $signatureHeader, false, 'Invalid signature');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid webhook signature']);
    exit();
}

$service->logWebhookEvent($providerKey, $eventType, $rawBody, $signatureHeader, true, null);

$webhookResult = $gateway->processWebhook($payload);

if (!$webhookResult['success']) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $webhookResult['message'] ?? 'Webhook processing failed']);
    exit();
}

$transactionRef = $webhookResult['transaction_ref'] ?? '';
$providerRef = $webhookResult['provider_ref'] ?? '';
$status = $webhookResult['status'] ?? 'pending';
$message = $webhookResult['message'] ?? '';
$amount = (float)($webhookResult['amount'] ?? 0);

if (empty($transactionRef) && empty($providerRef)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No transaction reference in webhook payload']);
    exit();
}

$updated = $service->processWebhookResult($transactionRef, $providerRef, $status, $message, $amount);

if (!$updated) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update transaction status']);
    exit();
}

$conn = getStudentsConnection();
if ($conn && !empty($transactionRef)) {
    $service->recordCallback($transactionRef, $providerKey, 'webhook', $_SERVER['REQUEST_METHOD'] ?? 'POST', json_encode($_SERVER), $rawBody, 200, json_encode(['processed' => true]));
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
exit();
