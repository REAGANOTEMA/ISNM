<?php
/**
 * Payment API — Get available payment providers.
 * GET /payment_gateway/api/providers.php
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

$gateway = GatewayManager::getInstance();
$providers = $gateway->getAvailableProviders();

echo json_encode([
    'success' => true,
    'providers' => array_values($providers),
]);
