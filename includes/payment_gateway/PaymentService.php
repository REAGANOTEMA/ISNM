<?php
/**
 * PaymentService — Unified payment routing for ISNM.
 * Loads provider configs from DB, instantiates correct gateway,
 * records transactions, and coordinates accounting/notification.
 */

require_once __DIR__ . '/PaymentGatewayInterface.php';
require_once __DIR__ . '/AbstractPaymentGateway.php';
require_once __DIR__ . '/providers/MtnMomoGateway.php';
require_once __DIR__ . '/providers/AirtelMoneyGateway.php';
require_once __DIR__ . '/providers/StripeGateway.php';
require_once __DIR__ . '/providers/FlutterwaveGateway.php';
require_once __DIR__ . '/providers/PesapalGateway.php';
require_once __DIR__ . '/providers/PayPalGateway.php';
require_once __DIR__ . '/providers/BankTransferGateway.php';

class PaymentService {

    private $conn;
    private $providers = [];
    private $gateways = [];

    public function __construct(?mysqli $conn = null) {
        $this->conn = $conn;
        $this->loadProviders();
    }

    private const STATUS_RANKS = [
        'pending' => 0, 'processing' => 1, 'failed' => 2, 'cancelled' => 2,
        'expired' => 2, 'successful' => 3, 'refunded' => 4,
    ];

    private static function statusRank(string $status): int {
        return self::STATUS_RANKS[strtolower($status)] ?? 0;
    }

    private static function normalizeMoney($value): ?float {
        if ($value === null || $value === '' || is_array($value) || !is_numeric($value)) return null;
        return round((float) $value, 2);
    }

    private static function amountMatches(float $expected, ?float $paid, string $expectedCurrency, $paidCurrency): bool {
        if ($paid === null || $paid <= 0) return false;
        if ($paidCurrency !== null && strtoupper(trim((string) $paidCurrency)) !== strtoupper(trim($expectedCurrency))) return false;
        return abs($expected - $paid) <= 0.01;
    }

    private function getConnection(): ?mysqli {
        if ($this->conn !== null && $this->conn->ping()) {
            return $this->conn;
        }
        if (function_exists('getStudentsConnection')) {
            $this->conn = getStudentsConnection();
            return $this->conn;
        }
        return null;
    }

    private function loadProviders(): void {
        $conn = $this->getConnection();
        if (!$conn) return;

        $result = $conn->query("SELECT * FROM payment_providers ORDER BY provider_name ASC");
        if (!$result) return;

        while ($row = $result->fetch_assoc()) {
            $row['config_json'] = json_decode($row['config_json'] ?? '{}', true) ?: [];
            $this->providers[$row['provider_key']] = $row;
        }
    }

    public function getEnabledProviders(): array {
        return array_filter($this->providers, fn($p) => $p['is_enabled'] == 1 && $p['status'] !== 'inactive');
    }

    public function getProviderConfig(string $providerKey): ?array {
        return $this->providers[$providerKey] ?? null;
    }

    public function getGateway(string $providerKey): ?PaymentGatewayInterface {
        if (isset($this->gateways[$providerKey])) {
            return $this->gateways[$providerKey];
        }

        $config = $this->providers[$providerKey] ?? null;
        if (!$config) return null;

        if ($providerKey === 'mtn_momo') {
            $gateway = new MtnMomoGateway($config);
        } elseif ($providerKey === 'airtel_money') {
            $gateway = new AirtelMoneyGateway($config);
        } elseif ($providerKey === 'stripe') {
            $gateway = new StripeGateway($config);
        } elseif ($providerKey === 'flutterwave') {
            $gateway = new FlutterwaveGateway($config);
        } elseif ($providerKey === 'pesapal') {
            $gateway = new PesapalGateway($config);
        } elseif ($providerKey === 'paypal') {
            $gateway = new PayPalGateway($config);
        } elseif ($providerKey === 'bank_transfer') {
            $gateway = new BankTransferGateway($config);
        } else {
            $gateway = null;
        }

        if ($gateway) {
            $this->gateways[$providerKey] = $gateway;
        }
        return $gateway;
    }

    public function initiatePayment(string $providerKey, array $params): array {
        $gateway = $this->getGateway($providerKey);
        if (!$gateway) {
            return ['success' => false, 'message' => 'Invalid or disabled payment provider'];
        }

        $conn = $this->getConnection();
        if (!$conn) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }

        $config = $this->providers[$providerKey];
        $amount = (float)($params['amount'] ?? 0);

        if ($amount < (float)($config['min_amount'] ?? 0)) {
            return ['success' => false, 'message' => 'Amount below minimum'];
        }
        if ($amount > (float)($config['max_amount'] ?? 10000000)) {
            return ['success' => false, 'message' => 'Amount exceeds maximum'];
        }

        $ref = $params['transaction_ref'] ?? $this->generateTransactionRef($providerKey);
        $fee = $this->calculateFee($providerKey, $amount);
        $netAmount = round($amount - $fee, 2);

        $params['transaction_ref'] = $ref;

        $result = $gateway->initiatePayment($params);

        if ($result['success']) {
            if (!$this->recordTransaction($conn, [
                'transaction_ref'     => $ref,
                'provider_key'        => $providerKey,
                'provider_txn_id'     => $result['provider_ref'] ?? '',
                'payment_type'        => $params['payment_type'] ?? 'student_fees',
                'reference_type'      => $params['reference_type'] ?? '',
                'reference_id'        => $params['reference_id'] ?? 0,
                'student_id'          => $params['student_id'] ?? 0,
                'staff_id'            => $params['staff_id'] ?? 0,
                'payer_name'          => $params['payer_name'] ?? '',
                'payer_phone'         => $params['phone'] ?? $params['payer_phone'] ?? '',
                'payer_email'         => $params['email'] ?? $params['payer_email'] ?? '',
                'amount'              => $amount,
                'currency'            => $params['currency'] ?? 'UGX',
                'fee_amount'          => $fee,
                'net_amount'          => $netAmount,
                'status'              => 'processing',
                'status_message'      => $result['message'] ?? '',
                'metadata'            => $params['metadata'] ?? [],
                'initiated_by'        => $params['initiated_by'] ?? ($_SESSION['user_id'] ?? 0),
                'ip_address'          => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'          => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ])) {
                return ['success' => false, 'message' => 'Failed to record transaction.'];
            }

            $result['transaction_ref'] = $ref;
            $result['fee_amount'] = $fee;
            $result['net_amount'] = $netAmount;
        }

        return $result;
    }

    public function checkStatus(string $transactionRef): array {
        $conn = $this->getConnection();
        if (!$conn) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }

        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE transaction_ref = ?");
        $stmt->bind_param("s", $transactionRef);
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Database error'];
        }
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$txn) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        if ($txn['status'] !== 'processing' && $txn['status'] !== 'pending') {
            return [
                'success' => true,
                'transaction_ref' => $transactionRef,
                'status' => $txn['status'],
                'message' => 'Transaction already ' . $txn['status'],
            ];
        }

        $gateway = $this->getGateway($txn['provider_key']);
        if (!$gateway) {
            return ['success' => false, 'message' => 'Provider unavailable'];
        }

        $providerTxnId = $txn['provider_transaction_id'] ?: $transactionRef;
        $gatewayResult = $gateway->checkTransactionStatus($providerTxnId);

        if ($gatewayResult['success'] && isset($gatewayResult['status'])) {
            $newStatus = $gatewayResult['status'];
            if (in_array($newStatus, ['successful', 'failed', 'cancelled'])) {
                $gross = self::normalizeMoney($txn['amount'] ?? null);
                $paid = self::normalizeMoney($gatewayResult['amount'] ?? null);
                if ($newStatus === 'successful' && ($gross === null || !self::amountMatches($gross, $paid, (string) ($txn['currency'] ?? 'UGX'), $gatewayResult['currency'] ?? null))) {
                    error_log('[PaymentService] amount mismatch for ' . $transactionRef . '; rejecting successful status');
                    $gatewayResult['success'] = false;
                    $gatewayResult['message'] = 'Paid amount does not match the expected amount.';
                } elseif ($this->updateTransactionStatus($conn, $transactionRef, $newStatus, $gatewayResult['message'] ?? '')) {
                    if ($newStatus === 'successful') {
                        $this->handleSuccessfulPayment($conn, $txn);
                    }
                }
            }
        }

        return array_merge($gatewayResult, ['transaction_ref' => $transactionRef]);
    }

    public function processWebhookResult(string $transactionRef, string $providerRef, string $status, string $message = '', float $amount = 0, ?string $currency = null): bool {
        $conn = $this->getConnection();
        if (!$conn) return false;

        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE transaction_ref = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param("s", $transactionRef);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$txn && $providerRef !== '') {
            $stmt2 = $conn->prepare("SELECT * FROM payment_transactions WHERE provider_transaction_id = ? LIMIT 1");
            if (!$stmt2) return false;
            $stmt2->bind_param("s", $providerRef);
            if (!$stmt2->execute()) {
                $stmt2->close();
                return false;
            }
            $txn = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        }
        if (!$txn) return false;
        $transactionRef = (string) $txn['transaction_ref'];

        $currentStatus = strtolower((string) $txn['status']);
        if (in_array($currentStatus, ['successful', 'refunded'], true)) return true;

        $status = strtolower(trim($status));
        if (!in_array($status, ['successful', 'failed', 'cancelled', 'pending'], true)) {
            error_log('[PaymentService] unsupported webhook status ' . $status . ' for ' . $transactionRef);
            return false;
        }
        if (self::statusRank($status) < self::statusRank($currentStatus)) {
            error_log('[PaymentService] ignored non-monotonic webhook transition ' . $currentStatus . ' -> ' . $status . ' for ' . $transactionRef);
            return true;
        }

        if ($status === 'successful') {
            $gross = self::normalizeMoney($txn['amount'] ?? null);
            $paid = self::normalizeMoney($amount);
            if ($gross === null || !self::amountMatches($gross, $paid, (string) ($txn['currency'] ?? 'UGX'), $currency)) {
                error_log('[PaymentService] webhook amount mismatch for ' . $transactionRef . '; rejecting successful status');
                return false;
            }
        }

        if (!empty($providerRef) && empty($txn['provider_transaction_id'])) {
            $stmt3 = $conn->prepare("UPDATE payment_transactions SET provider_transaction_id = ? WHERE transaction_ref = ?");
            if (!$stmt3) return false;
            $stmt3->bind_param("ss", $providerRef, $transactionRef);
            $ok = $stmt3->execute();
            if (!$ok) {
                error_log('[PaymentService] failed to store provider reference for ' . $transactionRef);
            }
            $stmt3->close();
            if (!$ok) return false;
        }

        if (!$this->updateTransactionStatus($conn, $transactionRef, $status, $message)) return false;

        if ($status === 'successful') {
            $this->handleSuccessfulPayment($conn, $txn);
        }

        return true;
    }

    private function handleSuccessfulPayment(mysqli $conn, array $txn): void {
        $referenceType = $txn['reference_type'] ?? '';
        $referenceId = (int)($txn['reference_id'] ?? 0);

        if ($referenceType === 'student_invoice' && $referenceId > 0) {
            $this->recordStudentPayment($conn, $txn);
        }

        $this->logActivity($conn, 'payment_success', "Payment {$txn['transaction_ref']} of {$txn['currency']} " . number_format($txn['amount'], 2) . " via {$txn['provider_key']}", $txn['student_id'] ?? 0, $txn['staff_id'] ?? 0);
    }

    private function recordStudentPayment(mysqli $conn, array $txn): void {
        $invoiceId = (int)($txn['reference_id'] ?? 0);
        if ($invoiceId <= 0) return;

        $check = $conn->prepare("SELECT id FROM payments WHERE transaction_ref = ? LIMIT 1");
        if ($check) {
            $check->bind_param('s', $txn['transaction_ref']);
            if (!$check->execute()) {
                error_log('[PaymentService] failed to check existing payment for ' . $txn['transaction_ref']);
                $check->close();
                return;
            }
            $exists = $check->get_result()->fetch_row();
            $check->close();
            if ($exists) return;
        }

        $stmt = $conn->prepare("
            INSERT INTO payments (
                payment_reference, student_id, amount_received, invoice_id,
                payment_method, transaction_ref, payment_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Completed')
        ");
        $method = $this->getProviderDisplayName($txn['provider_key']);
        $received = self::normalizeMoney($txn['amount'] ?? null);
        if ($received === null || !$stmt) {
            if ($stmt) $stmt->close();
            return;
        }
        $stmt->bind_param("sidsss",
            $txn['transaction_ref'],
            $txn['student_id'],
            $received,
            $invoiceId,
            $method,
            $txn['provider_transaction_id']
        );
        if ($stmt->execute() && $conn->affected_rows > 0) {
            if (function_exists('updateStudentInvoiceBalance')) {
                updateStudentInvoiceBalance($invoiceId);
            }
        }
        $stmt->close();
    }

    private function recordTransaction(mysqli $conn, array $data): bool {
        $stmt = $conn->prepare("
            INSERT INTO payment_transactions (
                transaction_ref, provider_key, provider_transaction_id,
                payment_type, reference_type, reference_id,
                student_id, staff_id,
                payer_name, payer_phone, payer_email,
                amount, currency, fee_amount, net_amount,
                status, status_message, metadata_json,
                initiated_by, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) return false;
        $metadataJson = json_encode($data['metadata'] ?? []);
        $gross = self::normalizeMoney($data['amount'] ?? null);
        $fee = self::normalizeMoney($data['fee_amount'] ?? 0) ?? 0.0;
        $net = self::normalizeMoney($data['net_amount'] ?? null) ?? round(($gross ?? 0) - $fee, 2);
        if ($gross === null) return false;

        $stmt->bind_param("sssssiissssdsddssssss",
            $data['transaction_ref'],
            $data['provider_key'],
            $data['provider_txn_id'],
            $data['payment_type'],
            $data['reference_type'],
            $data['reference_id'],
            $data['student_id'],
            $data['staff_id'],
            $data['payer_name'],
            $data['payer_phone'],
            $data['payer_email'],
            $gross,
            $data['currency'],
            $fee,
            $net,
            $data['status'],
            $data['status_message'],
            $metadataJson,
            $data['initiated_by'],
            $data['ip_address'],
            $data['user_agent']
        );

        $ok = $stmt->execute();
        if (!$ok) {
            error_log('[PaymentService] failed to record transaction ' . $data['transaction_ref'] . ': ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    private function updateTransactionStatus(mysqli $conn, string $transactionRef, string $status, string $message = ''): bool {
        $status = strtolower(trim($status));
        $rank = self::statusRank($status);
        if ($rank === 0 && $status !== 'pending') {
            error_log('[PaymentService] unsupported status ' . $status . ' for ' . $transactionRef);
            return false;
        }

        $stmt = $conn->prepare("SELECT id, status FROM payment_transactions WHERE transaction_ref = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('s', $transactionRef);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$current) return false;

        $currentStatus = strtolower((string) $current['status']);
        if ($rank < self::statusRank($currentStatus)) {
            error_log('[PaymentService] ignored non-monotonic transition ' . $currentStatus . ' -> ' . $status . ' for ' . $transactionRef);
            return true;
        }
        if ($status === $currentStatus && $rank >= 3) return true;

        $callbackAt = ($status === 'successful' || $status === 'failed') ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare("
            UPDATE payment_transactions
            SET status = ?, status_message = ?, callback_received_at = COALESCE(?, callback_received_at),
                completed_at = IF(? = 'successful', COALESCE(completed_at, NOW()), completed_at),
                verified_at = IF(? = 'successful', COALESCE(verified_at, NOW()), verified_at),
                updated_at = NOW()
            WHERE id = ? AND CASE status
                WHEN 'refunded' THEN 4
                WHEN 'successful' THEN 3
                WHEN 'failed' THEN 2
                WHEN 'cancelled' THEN 2
                WHEN 'expired' THEN 2
                WHEN 'processing' THEN 1
                ELSE 0
            END < ?
        ");
        if (!$stmt) return false;
        $stmt->bind_param("sssssii", $status, $message, $callbackAt, $status, $status, (int) $current['id'], $rank);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('[PaymentService] failed to update status for ' . $transactionRef . ': ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    public function recordCallback(string $transactionRef, string $providerKey, string $callbackType, string $requestMethod, string $headers, string $body, int $responseCode = 0, string $responseBody = ''): void {
        $conn = $this->getConnection();
        if (!$conn) return;

        $stmt = $conn->prepare("
            INSERT INTO payment_callbacks (
                transaction_id, provider_key, callback_type,
                request_method, request_headers, request_body,
                response_code, response_body
            )
            SELECT id, ?, ?, ?, ?, ?, ?, ?
            FROM payment_transactions WHERE transaction_ref = ?
        ");
        if (!$stmt || !$stmt->bind_param("ssssssis", $providerKey, $callbackType, $requestMethod, $headers, $body, $responseCode, $responseBody, $transactionRef) || !$stmt->execute()) {
            error_log('[PaymentService] failed to record callback for ' . $transactionRef);
        }
        if ($stmt) $stmt->close();
    }

    public function logWebhookEvent(string $providerKey, string $eventType, string $payload, string $signature, ?bool $signatureValid, ?string $error): void {
        $conn = $this->getConnection();
        if (!$conn) return;

        $stmt = $conn->prepare("
            INSERT INTO payment_webhook_logs (provider_key, event_type, payload, signature, signature_valid, error_message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $sigValid = $signatureValid === null ? null : ($signatureValid ? 1 : 0);
        if (!$stmt || !$stmt->bind_param("ssssis", $providerKey, $eventType, $payload, $signature, $sigValid, $error) || !$stmt->execute()) {
            error_log('[PaymentService] failed to log webhook event for ' . $providerKey);
        }
        if ($stmt) $stmt->close();
    }

    public function recordRefund(string $transactionRef, float $amount, string $reason, int $initiatedBy = 0): array {
        $conn = $this->getConnection();
        if (!$conn) {
            return ['success' => false, 'message' => 'Database unavailable'];
        }

        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE transaction_ref = ? AND status = 'successful'");
        $stmt->bind_param("s", $transactionRef);
        if (!$stmt->execute()) return ['success' => false, 'message' => 'Database error'];
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$txn) {
            return ['success' => false, 'message' => 'Transaction not found or not eligible for refund'];
        }

        if ($amount > (float)$txn['net_amount']) {
            return ['success' => false, 'message' => 'Refund amount exceeds transaction net amount'];
        }

        $gateway = $this->getGateway($txn['provider_key']);
        if (!$gateway) {
            return ['success' => false, 'message' => 'Provider unavailable'];
        }

        $gatewayResult = $gateway->refundTransaction($txn['provider_transaction_id'], $amount, $reason);

        $refundRef = 'RF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt2 = $conn->prepare("
            INSERT INTO payment_refunds (refund_ref, original_transaction_id, provider_key, provider_refund_id, amount, reason, status, initiated_by)
            SELECT ?, id, provider_key, ?, ?, ?, ?, ?
            FROM payment_transactions WHERE transaction_ref = ?
        ");
        $status = $gatewayResult['success'] ? 'processing' : 'failed';
        $providerRefundId = $gatewayResult['refund_id'] ?? '';
        $recorded = $stmt2 && $stmt2->bind_param("ssdssis", $refundRef, $providerRefundId, $amount, $reason, $status, $initiatedBy, $transactionRef) && $stmt2->execute();
        if (!$recorded) {
            error_log('[PaymentService] failed to record refund ' . $refundRef);
        }
        if ($stmt2) $stmt2->close();

        if ($gatewayResult['success'] && $recorded) {
            $this->updateTransactionStatus($conn, $transactionRef, 'refunded', 'Refund initiated: ' . $refundRef);
        }

        return array_merge($gatewayResult, ['refund_ref' => $refundRef]);
    }

    private function calculateFee(string $providerKey, float $amount): float {
        $config = $this->providers[$providerKey] ?? [];
        $percent = (float)($config['transaction_fee_percent'] ?? 0);
        $fixed = (float)($config['transaction_fee_fixed'] ?? 0);
        return round(($amount * $percent / 100) + $fixed, 2);
    }

    private function generateTransactionRef(string $providerKey): string {
        $prefixMap = [
            'mtn_momo' => 'MTN',
            'airtel_money' => 'ATL',
            'stripe' => 'STR',
            'flutterwave' => 'FLW',
            'pesapal' => 'PSP',
            'bank_transfer' => 'BNK',
        ];
        $prefix = $prefixMap[$providerKey] ?? 'TXN';
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }

    private function getProviderDisplayName(string $providerKey): string {
        return $this->providers[$providerKey]['provider_name'] ?? ucfirst(str_replace('_', ' ', $providerKey));
    }

    private function logActivity(mysqli $conn, string $activityType, string $description, int $studentId = 0, int $staffId = 0): void {
        if (function_exists('logFinancialActivity')) {
            logFinancialActivity($activityType, 'payment_transactions', 0, null, ['description' => $description]);
        }
    }

    public function updateProviderConfig(string $providerKey, array $updates): bool {
        $conn = $this->getConnection();
        if (!$conn) return false;

        $allowed = ['is_enabled', 'merchant_id', 'api_key', 'api_secret', 'api_url', 'callback_url', 'webhook_secret', 'config_json', 'supported_currencies', 'transaction_fee_percent', 'transaction_fee_fixed', 'min_amount', 'max_amount', 'status'];
        $sets = [];
        $types = '';
        $values = [];

        foreach ($updates as $key => $value) {
            if (!in_array($key, $allowed)) continue;
            if ($key === 'config_json') $value = json_encode($value);
            $sets[] = "$key = ?";
            $types .= 's';
            $values[] = (string)$value;
        }

        if (empty($sets)) return false;

        $values[] = $providerKey;
        $sql = "UPDATE payment_providers SET " . implode(', ', $sets) . " WHERE provider_key = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types . 's', ...$values);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->loadProviders();
            $this->gateways = [];
        }

        return $result;
    }

    public function getTransactionStats(string $period = 'today'): array {
        $conn = $this->getConnection();
        if (!$conn) return [];

        if ($period === 'today') {
            $where = "AND DATE(created_at) = CURDATE()";
        } elseif ($period === 'week') {
            $where = "AND YEARWEEK(created_at) = YEARWEEK(NOW())";
        } elseif ($period === 'month') {
            $where = "AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
        } else {
            $where = '';
        }

        $sql = "
            SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'successful' THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN status IN ('pending','processing') THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'successful' THEN amount ELSE 0 END), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status = 'successful' THEN fee_amount ELSE 0 END), 0) as total_fees,
                COALESCE(SUM(CASE WHEN status = 'successful' THEN net_amount ELSE 0 END), 0) as total_net
            FROM payment_transactions
            WHERE 1=1 $where
        ";

        $result = $conn->query($sql);
        return $result ? $result->fetch_assoc() : [];
    }

    public function getTransactions(array $filters = [], int $limit = 50, int $offset = 0): array {
        $conn = $this->getConnection();
        if (!$conn) return [];

        $wheres = [];
        $types = '';
        $values = [];

        if (!empty($filters['status'])) {
            $wheres[] = "status = ?";
            $types .= 's';
            $values[] = $filters['status'];
        }
        if (!empty($filters['provider_key'])) {
            $wheres[] = "provider_key = ?";
            $types .= 's';
            $values[] = $filters['provider_key'];
        }
        if (!empty($filters['payment_type'])) {
            $wheres[] = "payment_type = ?";
            $types .= 's';
            $values[] = $filters['payment_type'];
        }
        if (!empty($filters['student_id'])) {
            $wheres[] = "student_id = ?";
            $types .= 'i';
            $values[] = (int)$filters['student_id'];
        }
        if (!empty($filters['date_from'])) {
            $wheres[] = "DATE(created_at) >= ?";
            $types .= 's';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = "DATE(created_at) <= ?";
            $types .= 's';
            $values[] = $filters['date_to'];
        }

        $where = '';
        if (!empty($wheres)) {
            $where = 'WHERE ' . implode(' AND ', $wheres);
        }

        $sql = "SELECT * FROM payment_transactions $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $values[] = $limit;
        $values[] = $offset;

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) return [];
        $results = isnm_fetch_all($stmt->get_result());
        $stmt->close();

        return $results;
    }
}
