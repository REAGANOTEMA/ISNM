<?php
/**
 * ISNM Payment Callback / Webhook Endpoint
 *
 * Receives POST requests from payment providers, verifies signatures,
 * updates transaction status, generates receipts, and dispatches
 * notifications.
 *
 * URL pattern:
 *   POST /api/payment-callback.php?provider=mtn_momo
 *   POST /api/payment-callback.php?provider=airtel_money
 *   POST /api/payment-callback.php?provider=stripe
 *   POST /api/payment-callback.php?provider=flutterwave
 *   POST /api/payment-callback.php?provider=pesapal
 *   POST /api/payment-callback.php?provider=paypal
 *
 * No session required — providers call this directly.
 */

header('Content-Type: application/json; charset=utf-8');

/* ── Bootstrap ─────────────────────────────────────────────── */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

/* ── Determine provider ────────────────────────────────────── */
$provider = $_GET['provider'] ?? $_POST['provider'] ?? '';
if (empty($provider)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing provider parameter.']);
    exit;
}

$provider = strtolower(trim($provider));

/* ── Read raw body and headers ─────────────────────────────── */
$rawBody  = file_get_contents('php://input') ?: '';
$headers  = function_exists('getallheaders') ? getallheaders() : [];

/* ── Parse payload ─────────────────────────────────────────── */
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

/* ── Initialize gateway ────────────────────────────────────── */
$gateway = PaymentGateway::getInstance();
$startTime = microtime(true);

/* ── Log the raw inbound request ───────────────────────────── */
logCallbackRequest($provider, $rawBody, $headers, $_SERVER['REMOTE_ADDR'] ?? '');

/* ── Validate provider exists and is active ────────────────── */
$providerInstance = $gateway->getProvider($provider);
if (!$providerInstance) {
    logCallbackError($provider, 'Provider not found or inactive.');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Provider "' . $provider . '" not recognized.']);
    exit;
}

/* ── Signature verification ────────────────────────────────── */
$signature = $headers['X-Hub-Signature-256']
    ?? $headers['X-Flutterwave-Signature']
    ?? $headers['X-PayPal-Signature']
    ?? $headers['x-hub-signature-256']
    ?? $headers['x-flutterwave-signature']
    ?? $headers['x-paypal-signature']
    ?? '';

if (!empty($signature)) {
    $sigValid = $providerInstance->verifyWebhookSignature($rawBody, $signature);
    if (!$sigValid) {
        logCallbackError($provider, 'Invalid webhook signature.');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature.']);
        exit;
    }
}

/* ── Process the webhook ───────────────────────────────────── */
$result = $gateway->processWebhook($provider, $rawBody, $payload, $headers);

$elapsed = (int) ((microtime(true) - $startTime) * 1000);

if ($result['success']) {
    $reference = $result['reference'] ?? '';
    $status    = $result['status'] ?? 'unknown';

    logCallbackSuccess($provider, $reference, $status, $elapsed);

    /* ── Generate receipt on successful payment ──────────── */
    if ($status === 'successful' && !empty($reference)) {
        $receiptResult = generateReceiptForTransaction($reference);
        if ($receiptResult['success'] ?? false) {
            $result['receipt_number'] = $receiptResult['receipt']['receipt_number'] ?? '';
        }

        /* ── Dispatch notification ───────────────────────── */
        $txnId = getTransactionIdByRef($reference);
        if ($txnId) {
            PaymentGateway::getInstance()->sendNotification($txnId, 'email');
        }
    }

    http_response_code(200);
    echo json_encode([
        'status'  => 'ok',
        'message' => $result['message'] ?? 'Callback processed',
        'ref'     => $reference ?? '',
    ]);
} else {
    logCallbackError($provider, $result['message'] ?? 'Processing failed');

    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $result['message'] ?? 'Callback processing failed',
    ]);
}

/* ═════════════════════════════════════════════════════════════
   LOCAL HELPER FUNCTIONS
   ═════════════════════════════════════════════════════════════ */

/**
 * Generate a receipt for a completed transaction by its reference.
 */
function generateReceiptForTransaction(string $reference): array {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return ['success' => false, 'message' => 'DB unavailable'];

    $stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE transaction_ref = ? AND status = 'successful' LIMIT 1");
    if (!$stmt) return ['success' => false];
    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return ['success' => false, 'message' => 'Transaction not found or not successful'];

    return PaymentGateway::getInstance()->generateReceipt((int) $row['id']);
}

/**
 * Get the internal transaction ID by reference.
 */
function getTransactionIdByRef(string $reference): ?int {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return null;
    $stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE transaction_ref = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : null;
}

/**
 * Log the raw inbound callback request.
 */
function logCallbackRequest(string $provider, string $rawBody, array $headers, string $ip): void {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return;

    $stmt = $conn->prepare("INSERT INTO payment_callbacks
        (provider_key, callback_type, request_method, request_headers, request_body, request_ip)
        VALUES (?, 'callback', 'POST', ?, ?, ?)");
    if (!$stmt) return;

    $hdrs = json_encode($headers);
    $body = mb_substr($rawBody, 0, 65000);
    $stmt->bind_param('ssss', $provider, $hdrs, $body, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Log a successful callback processing.
 */
function logCallbackSuccess(string $provider, string $reference, string $status, int $elapsedMs): void {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return;

    $stmt = $conn->prepare("UPDATE payment_callbacks SET processed = 1, processed_at = NOW(), response_code = 200
        WHERE provider_key = ? AND processed = 0 ORDER BY id DESC LIMIT 1");
    if (!$stmt) return;
    $stmt->bind_param('s', $provider);
    $stmt->execute();
    $stmt->close();

    error_log("[PaymentCallback][{$provider}] OK ref={$reference} status={$status} time={$elapsedMs}ms");
}

/**
 * Log a callback processing error.
 */
function logCallbackError(string $provider, string $error): void {
    $conn = PaymentGatewayDB::getConnection();
    if ($conn) {
        $stmt = $conn->prepare("UPDATE payment_callbacks SET processing_error = ?, response_code = 400
            WHERE provider_key = ? AND processed = 0 ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $error, $provider);
            $stmt->execute();
            $stmt->close();
        }
    }
    error_log("[PaymentCallback][{$provider}] ERROR: {$error}");
}
