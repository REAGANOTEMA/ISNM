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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/payment_gateway.php';

$provider = $_GET['provider'] ?? $_POST['provider'] ?? '';
if (empty($provider)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing provider parameter.']);
    exit;
}

$provider = strtolower(trim($provider));

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (!is_array($headers)) $headers = [];

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$gateway = PaymentGateway::getInstance();
$startTime = microtime(true);

$callbackId = logCallbackRequest($provider, $rawBody, $headers, $_SERVER['REMOTE_ADDR'] ?? '');

$providerInstance = $gateway->getProvider($provider);
if (!$providerInstance) {
    logCallbackError($provider, $callbackId, 'Provider not found or inactive.');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Provider "' . $provider . '" not recognized.']);
    exit;
}

$signature = callbackHeaderValue([
    'X-Hub-Signature-256', 'X-Signature', 'X-Webhook-Signature',
    'X-Stripe-Signature', 'X-Flutterwave-Signature', 'X-PayPal-Signature',
]);

$secretName = paymentWebhookSecretName($provider);
$secret = function_exists('isnm_env') ? (string) isnm_env($secretName, '') : (string) getenv($secretName);
if ($secret === '') {
    logCallbackError($provider, $callbackId, 'Webhook secret not configured: ' . $secretName, 401);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Webhook secret is not configured.']);
    exit;
}
if ($signature === '' || !verifyCallbackSignature($provider, $rawBody, $signature, $secret)) {
    logCallbackError($provider, $callbackId, 'Invalid webhook signature.', 401);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature.']);
    exit;
}

$callbackResult = $providerInstance->processCallback($payload, $headers);
if (empty($callbackResult['success'])) {
    $callbackResult = fallbackCallbackResult($provider, $payload);
}

$status = normalizeCallbackStatus($callbackResult['status'] ?? 'unknown');
$providerTxnId = (string) ($callbackResult['provider_transaction_id'] ?? '');
$reference = (string) ($callbackResult['reference'] ?? '') ?: $providerTxnId;

$applied = applyHardenedPaymentStatus($reference, $providerTxnId, $status, grossAmountFromPayload($provider, $payload), $provider);
$elapsed = (int) ((microtime(true) - $startTime) * 1000);

if (!$applied['ok']) {
    logCallbackError($provider, $callbackId, $applied['message'], 422);
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => $applied['message'],
    ]);
    exit;
}

$reference = $applied['reference'] ?: $reference;
$result = $callbackResult;
$result['status'] = $status;
$result['message'] = $applied['message'] !== '' ? $applied['message'] : ($callbackResult['message'] ?? 'Callback processed');

logCallbackSuccess($provider, $callbackId, $reference, $status, $elapsed, (int) ($applied['id'] ?? 0));

if ($status === 'successful' && $reference !== '') {
    $receiptResult = generateReceiptForTransaction($reference);
    if ($receiptResult['success'] ?? false) {
        $result['receipt_number'] = $receiptResult['receipt']['receipt_number'] ?? '';
    }

    $txnId = getTransactionIdByRef($reference);
    if ($txnId) {
        PaymentGateway::getInstance()->sendNotification($txnId, 'email');
    }
}

http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'message' => $result['message'] ?? 'Callback processed',
    'ref'     => $reference,
]);

function paymentWebhookSecretName(string $provider): string {
    $normalized = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '_', $provider) ?: '');
    return 'PAYMENT_' . trim($normalized, '_') . '_WEBHOOK_SECRET';
}

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
                if (in_array(strtolower((string) $key), $wanted, true)) {
                    return is_array($value) ? implode(',', $value) : (string) $value;
                }
            }
        }
    }
    return '';
}

function verifyCallbackSignature(string $provider, string $rawBody, string $signature, string $secret): bool {
    if ($secret === '' || trim($signature) === '') return false;

    if ($provider === 'stripe') {
        $timestamp = '';
        $provided = '';
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') $timestamp = $value;
            if ($key === 'v1') $provided = $value;
        }
        if ($timestamp === '' || $provided === '' || !ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return false;
        }
        return hash_equals(hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret), $provided);
    }

    $provided = trim($signature);
    if (stripos($provided, 'sha256=') === 0) $provided = substr($provided, 7);

    if ($provider === 'paypal') {
        $expected = hash_hmac('sha256', $rawBody, $secret, true);
        return hash_equals(base64_encode($expected), $provided) || hash_equals(bin2hex($expected), $provided);
    }

    return hash_equals(hash_hmac('sha256', $rawBody, $secret), $provided);
}

function normalizeCallbackStatus($raw): string {
    $raw = strtolower(trim((string) $raw));
    if (in_array($raw, ['successful', 'success', 'completed', 'paid'], true)) return 'successful';
    if (in_array($raw, ['failed', 'failure', 'rejected'], true)) return 'failed';
    if (in_array($raw, ['cancelled', 'canceled', 'voided'], true)) return 'cancelled';
    if (in_array($raw, ['expired', 'timeout'], true)) return 'expired';
    if ($raw === 'refunded') return 'refunded';
    if ($raw === 'pending') return 'pending';
    return 'processing';
}

function callbackStatusRank(string $status): int {
    $ranks = [
        'pending' => 0, 'processing' => 1, 'failed' => 2, 'cancelled' => 2,
        'expired' => 2, 'successful' => 3, 'refunded' => 4,
    ];
    return $ranks[$status] ?? 0;
}

function normalizeCallbackMoney($value): ?float {
    if ($value === null || $value === '' || is_array($value) || !is_numeric($value)) return null;
    return round((float) $value, 2);
}

function grossAmountFromPayload(string $provider, array $payload): ?array {
    $raw = null;
    $currency = null;

    if ($provider === 'stripe') {
        $object = $payload['data']['object'] ?? [];
        if (!isset($object['amount_total'])) return null;
        $raw = $object['amount_total'];
        $currency = $object['currency'] ?? null;
        return ['amount' => round((float) $raw / 100, 2), 'currency' => $currency !== null ? strtoupper((string) $currency) : null];
    }

    if ($provider === 'paypal') {
        $resource = $payload['resource'] ?? [];
        $raw = $resource['purchase_units'][0]['amount']['value'] ?? $resource['amount']['value'] ?? null;
        $currency = $resource['purchase_units'][0]['amount']['currency_code'] ?? $resource['amount']['currency_code'] ?? null;
    } elseif ($provider === 'flutterwave') {
        $raw = $payload['data']['amount'] ?? null;
        $currency = $payload['data']['currency'] ?? null;
    } elseif ($provider === 'mtn_momo') {
        $raw = $payload['amount']['value'] ?? null;
        $currency = $payload['amount']['currency'] ?? null;
    } elseif ($provider === 'airtel_money') {
        $raw = $payload['transaction']['amount'] ?? $payload['amount'] ?? null;
        $currency = $payload['transaction']['currency'] ?? $payload['currency'] ?? null;
    } elseif ($provider === 'pesapal') {
        $raw = $payload['amount'] ?? null;
        $currency = $payload['currency'] ?? null;
    } else {
        $raw = $payload['amount'] ?? null;
        $currency = $payload['currency'] ?? null;
    }

    if (is_array($raw)) {
        $currency = $raw['currency'] ?? $currency;
        $raw = $raw['value'] ?? null;
    }
    $amount = normalizeCallbackMoney($raw);
    if ($amount === null) return null;
    return ['amount' => $amount, 'currency' => $currency !== null ? strtoupper((string) $currency) : null];
}

function fallbackCallbackResult(string $provider, array $payload): array {
    $raw = '';
    $reference = '';
    $providerTxnId = '';

    if ($provider === 'mtn_momo') {
        $raw = $payload['status'] ?? '';
        $reference = $payload['externalId'] ?? $payload['external_id'] ?? '';
        $providerTxnId = $payload['transactionId'] ?? $payload['financialTransactionId'] ?? '';
    } elseif ($provider === 'airtel_money') {
        $raw = $payload['transaction']['status'] ?? $payload['status'] ?? '';
        $reference = $payload['transaction']['externalId'] ?? $payload['reference'] ?? '';
        $providerTxnId = $payload['transaction']['id'] ?? $payload['transactionId'] ?? '';
    } elseif ($provider === 'pesapal') {
        $raw = $payload['payment_status'] ?? $payload['OrderStatus'] ?? '';
        $reference = $payload['order_merchant_reference'] ?? $payload['id'] ?? '';
        $providerTxnId = $payload['order_tracking_id'] ?? $payload['OrderTrackingId'] ?? '';
    } else {
        $reference = $payload['reference'] ?? $payload['transaction_ref'] ?? '';
    }

    return [
        'success' => true,
        'reference' => $reference,
        'provider_transaction_id' => $providerTxnId,
        'status' => normalizeCallbackStatus($raw),
        'raw_status' => $raw,
        'message' => 'Callback processed',
    ];
}

function applyHardenedPaymentStatus(string $reference, string $providerTxnId, string $status, ?array $amountInfo, string $provider): array {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) {
        return ['ok' => false, 'reference' => '', 'message' => 'Database unavailable.'];
    }
    if ($reference === '' && $providerTxnId === '') {
        return ['ok' => false, 'reference' => '', 'message' => 'No transaction reference in callback payload.'];
    }

    $stmt = $conn->prepare("SELECT id, transaction_ref, status, amount, currency, provider_transaction_id
        FROM payment_transactions WHERE transaction_ref = ? AND provider_key = ? LIMIT 1");
    if (!$stmt) return ['ok' => false, 'reference' => '', 'message' => 'Database error.'];
    $stmt->bind_param('ss', $reference, $provider);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['ok' => false, 'reference' => '', 'message' => 'Database error.'];
    }
    $txn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$txn && $providerTxnId !== '') {
        $stmt = $conn->prepare("SELECT id, transaction_ref, status, amount, currency, provider_transaction_id
            FROM payment_transactions WHERE provider_transaction_id = ? AND provider_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $providerTxnId, $provider);
            if ($stmt->execute()) $txn = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
    if (!$txn) {
        return ['ok' => false, 'reference' => '', 'message' => 'Transaction not found for this provider.'];
    }

    $transactionRef = (string) $txn['transaction_ref'];
    $transactionId = (int) $txn['id'];
    $current = normalizeCallbackStatus($txn['status'] ?? '');
    if (callbackStatusRank($status) < callbackStatusRank($current)) {
        return ['ok' => true, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Callback ignored: current status ' . $current . ' is final.'];
    }
    if ($status === $current && callbackStatusRank($status) >= 3) {
        return ['ok' => true, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Payment already recorded.'];
    }

    $gross = normalizeCallbackMoney($txn['amount'] ?? null);
    $amountReceived = null;
    if ($status === 'successful') {
        $paid = $amountInfo['amount'] ?? null;
        $paid = normalizeCallbackMoney($paid);
        $expectedCurrency = strtoupper(trim((string) ($txn['currency'] ?? 'UGX')));
        $paidCurrency = $amountInfo['currency'] ?? null;
        if ($gross === null || $paid === null || $paid <= 0) {
            return ['ok' => false, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Paid amount missing from callback payload.'];
        }
        if ($paidCurrency !== null && strtoupper(trim((string) $paidCurrency)) !== $expectedCurrency) {
            return ['ok' => false, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Paid currency does not match the expected currency.'];
        }
        if (abs($gross - $paid) > 0.01) {
            return ['ok' => false, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Paid amount does not match the expected amount.'];
        }
        $amountReceived = $gross;
    }

    $completedAt = $status === 'successful' ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare("UPDATE payment_transactions
        SET status = ?,
            provider_transaction_id = IFNULL(provider_transaction_id, ?),
            amount_received = IFNULL(?, amount_received),
            completed_at = IFNULL(?, completed_at),
            verified_at = IFNULL(?, verified_at),
            callback_received_at = NOW(),
            updated_at = NOW()
        WHERE id = ? AND CASE status
            WHEN 'refunded' THEN 4
            WHEN 'successful' THEN 3
            WHEN 'failed' THEN 2
            WHEN 'cancelled' THEN 2
            WHEN 'expired' THEN 2
            WHEN 'processing' THEN 1
            ELSE 0
        END < ?");
    if (!$stmt) return ['ok' => false, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Database error.'];
    $stmt->bind_param('sssssii', $status, $providerTxnId, $amountReceived, $completedAt, $completedAt, $transactionId, callbackStatusRank($status));
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        error_log('[PaymentCallback] status update failed for ' . $transactionRef . ': ' . $error);
        return ['ok' => false, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Failed to update transaction status.'];
    }
    $stmt->close();

    return ['ok' => true, 'reference' => $transactionRef, 'id' => $transactionId, 'message' => 'Callback processed.'];
}

function generateReceiptForTransaction(string $reference): array {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return ['success' => false, 'message' => 'DB unavailable'];

    $stmt = $conn->prepare("SELECT t.id, r.receipt_number
        FROM payment_transactions t
        LEFT JOIN payment_receipts r ON r.transaction_id = t.id
        WHERE t.transaction_ref = ? AND t.status = 'successful' LIMIT 1");
    if (!$stmt) return ['success' => false];
    $stmt->bind_param('s', $reference);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false];
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return ['success' => false, 'message' => 'Transaction not found or not successful'];
    if (!empty($row['receipt_number'])) {
        return ['success' => true, 'receipt' => ['receipt_number' => $row['receipt_number']]];
    }

    return PaymentGateway::getInstance()->generateReceipt((int) $row['id']);
}

function getTransactionIdByRef(string $reference): ?int {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return null;
    $stmt = $conn->prepare("SELECT id FROM payment_transactions WHERE transaction_ref = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $reference);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : null;
}

function logCallbackRequest(string $provider, string $rawBody, array $headers, string $ip): ?int {
    $conn = PaymentGatewayDB::getConnection();
    if (!$conn) return null;

    $stmt = $conn->prepare("INSERT INTO payment_callbacks
        (transaction_id, provider_key, callback_type, request_method, request_headers, request_body, request_ip)
        VALUES (0, ?, 'webhook', 'POST', ?, ?, ?)");
    if (!$stmt) return null;

    $safeHeaders = [];
    foreach ($headers as $name => $value) {
        $lower = strtolower((string) $name);
        $safeHeaders[$name] = (strpos($lower, 'signature') !== false || strpos($lower, 'secret') !== false
            || strpos($lower, 'token') !== false || strpos($lower, 'authorization') !== false)
            ? '[redacted]' : $value;
    }
    $hdrs = json_encode($safeHeaders);
    $body = substr($rawBody, 0, 65000);
    $ok = $stmt->bind_param('ssss', $provider, $hdrs, $body, $ip) && $stmt->execute();
    $id = $ok ? (int) $conn->insert_id : 0;
    if (!$ok) error_log('[PaymentCallback] failed to log callback request for ' . $provider);
    $stmt->close();
    return $id ?: null;
}

function logCallbackSuccess(string $provider, ?int $callbackId, string $reference, string $status, int $elapsedMs, int $transactionId): void {
    $conn = PaymentGatewayDB::getConnection();

    if ($conn && $callbackId) {
        $stmt = $conn->prepare("UPDATE payment_callbacks SET transaction_id = ?, processed = 1, processed_at = NOW(), response_code = 200 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $transactionId, $callbackId);
            if (!$stmt->execute()) error_log('[PaymentCallback] failed to mark callback ' . $callbackId . ' processed');
            $stmt->close();
        }
    }

    error_log("[PaymentCallback][{$provider}] OK ref={$reference} status={$status} time={$elapsedMs}ms");
}

function logCallbackError(string $provider, ?int $callbackId, string $error, int $responseCode = 400): void {
    $conn = PaymentGatewayDB::getConnection();
    if ($conn && $callbackId) {
        $stmt = $conn->prepare("UPDATE payment_callbacks SET processing_error = ?, response_code = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('sii', $error, $responseCode, $callbackId);
            if (!$stmt->execute()) error_log('[PaymentCallback] failed to log callback error for ' . $provider);
            $stmt->close();
        }
    }
    error_log("[PaymentCallback][{$provider}] ERROR: {$error}");
}
