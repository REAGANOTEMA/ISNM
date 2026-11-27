<?php
/**
 * Provider-specific callback endpoints.
 * These are the URLs configured in each provider's dashboard.
 * e.g., /payment_gateway/callbacks/mtn.php
 *       /payment_gateway/callbacks/airtel.php
 *       /payment_gateway/callbacks/flutterwave.php
 *       /payment_gateway/callbacks/pesapal.php
 *       /payment_gateway/callbacks/stripe.php
 */

// Determine provider from filename
$scriptName = basename($_SERVER['SCRIPT_NAME'], '.php');
$providerMap = [
    'mtn' => 'mtn_momo',
    'airtel' => 'airtel_money',
    'flutterwave' => 'flutterwave',
    'pesapal' => 'pesapal',
    'stripe' => 'stripe',
];

$provider = $providerMap[$scriptName] ?? $scriptName;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents('php://input') ?: '';
$payload = json_decode($rawBody, true) ?: $_POST;
$headers = function_exists('getallheaders') ? getallheaders() : [];

$gateway = GatewayManager::getInstance();
$result = $gateway->processCallback($provider, $payload, $headers, $rawBody);

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
