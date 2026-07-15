<?php
/**
 * Payment API — Verify a payment status.
 * GET /payment_gateway/api/verify.php?provider=mtn_momo&transaction_ref=XXX
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

$provider = $_GET['provider'] ?? '';
$txnRef = $_GET['transaction_ref'] ?? $_GET['ref'] ?? '';

if (!$provider || !$txnRef) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing provider or transaction_ref']);
    exit;
}

$gateway = GatewayManager::getInstance();

// First try to find the provider_transaction_id from our DB
$conn = getStudentsConnection();
$stmt = $conn->prepare("SELECT provider_transaction_id FROM payment_transactions WHERE transaction_ref = ?");
$stmt->bind_param('s', $txnRef);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$verifyRef = $result['provider_transaction_id'] ?? $txnRef;
$verifyResult = $gateway->verifyPayment($provider, $verifyRef);

// Update our DB status if we got a definitive answer
if ($verifyResult['success'] && ($verifyResult['status'] === 'successful' || $verifyResult['status'] === 'failed')) {
    $gateway->updateTransactionStatus($txnRef, $verifyResult['status'], $verifyRef);
    $verifyResult['reference'] = $txnRef;
}

echo json_encode($verifyResult);
