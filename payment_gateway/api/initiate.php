<?php
/**
 * Payment API — Initiate a payment.
 * POST /payment_gateway/api/initiate.php
 * JSON body: { provider, amount, currency, payment_for, student_id, payer_name, payer_phone, payer_email, description }
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

$required = ['provider', 'amount', 'payment_for'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

$gateway = GatewayManager::getInstance();
$result = $gateway->initiatePayment($input['provider'], [
    'reference' => $input['reference'] ?? null,
    'amount' => (float)$input['amount'],
    'currency' => $input['currency'] ?? 'UGX',
    'payment_for' => $input['payment_for'],
    'student_id' => $input['student_id'] ?? null,
    'staff_id' => $input['staff_id'] ?? null,
    'payer_name' => $input['payer_name'] ?? '',
    'payer_phone' => $input['payer_phone'] ?? '',
    'payer_email' => $input['payer_email'] ?? '',
    'description' => $input['description'] ?? '',
    'initiated_by' => $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 0,
    'metadata' => $input['metadata'] ?? [],
]);

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
