<?php
/**
 * Payment API — Transaction history.
 * GET /payment_gateway/api/transactions.php?status=successful&provider_key=mtn_momo&page=1
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['provider_key'])) $filters['provider_key'] = $_GET['provider_key'];
if (!empty($_GET['student_id'])) $filters['student_id'] = (int)$_GET['student_id'];
if (!empty($_GET['payment_type'])) $filters['payment_type'] = $_GET['payment_type'];
if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$gateway = GatewayManager::getInstance();
$transactions = $gateway->getTransactions($filters, $perPage, $offset);

echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'page' => $page,
    'per_page' => $perPage,
]);
