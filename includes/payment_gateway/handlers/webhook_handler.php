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

require_once __DIR__ . '/../../../config/database.php';
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

$signatureHeader = callbackHeaderValue(['X-Signature', 'X-Webhook-Signature', 'X-Stripe-Signature', 'X-Hub-Signature-256', 'X-Flutterwave-Signature', 'X-PayPal-Signature']);

$eventType = $payload['type'] ?? $payload['event'] ?? $payload['event_type'] ?? 'unknown';

if ($signatureHeader === '') {
    $service->logWebhookEvent($providerKey, $eventType, $rawBody, '', false, 'Missing webhook signature');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing webhook signature']);
    exit();
}

$signatureValid = $gateway->verifyWebhookSignature($rawBody, $signatureHeader);

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
$currency = $webhookResult['currency'] ?? null;

if (empty($transactionRef) && empty($providerRef)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No transaction reference in webhook payload']);
    exit();
}

$updated = $service->processWebhookResult($transactionRef, $providerRef, $status, $message, $amount, $currency);

if (!$updated) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update transaction status']);
    exit();
}

$conn = getStudentsConnection();
if ($conn && !empty($transactionRef)) {
    $safeServer = [];
    foreach ($_SERVER as $key => $value) {
        $lower = strtolower((string) $key);
        $safeServer[$key] = (strpos($lower, 'signature') !== false || strpos($lower, 'secret') !== false
            || strpos($lower, 'token') !== false || strpos($lower, 'authorization') !== false)
            ? '[redacted]' : $value;
    }
    $service->recordCallback($transactionRef, $providerKey, 'webhook', $_SERVER['REQUEST_METHOD'] ?? 'POST', json_encode($safeServer), $rawBody, 200, json_encode(['processed' => true]));
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
exit();

function callbackHeaderValue(array $names): string {
    $wanted = array_map('strtolower', $names);
    foreach ($_SERVER as $key => $value) {
        if (strpos((string) $key, 'HTTP_') !== 0) continue;
        $header = strtolower(str_replace('_', '-', substr((string) $key, 5)));
        if (in_array($header, $wanted, true)) return (string) $value;
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (in_array(strtolower((string) $key), $wanted, true)) return (string) $value;
            }
        }
    }
    return '';
}
