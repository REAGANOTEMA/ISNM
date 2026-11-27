<?php
/**
 * GatewayManager — Central orchestrator for the ISNM Payment Gateway.
 * Loads providers from DB, routes requests, manages transactions.
 */
require_once __DIR__ . '/../providers/BaseProvider.php';
require_once __DIR__ . '/../providers/MtnMomoProvider.php';
require_once __DIR__ . '/../providers/AirtelMoneyProvider.php';
require_once __DIR__ . '/../providers/FlutterwaveProvider.php';
require_once __DIR__ . '/../providers/PesaPalProvider.php';
require_once __DIR__ . '/../providers/StripeProvider.php';
require_once __DIR__ . '/../providers/PayPalProvider.php';
require_once __DIR__ . '/../providers/BankTransferProvider.php';
require_once __DIR__ . '/../providers/CashProvider.php';

class GatewayManager {

    private static $instance = null;
    private $db;
    private $providers = [];
    private $providerConfigs = [];

    private const PROVIDER_CLASSES = [
        'mtn_momo' => MtnMomoProvider::class,
        'airtel_money' => AirtelMoneyProvider::class,
        'flutterwave' => FlutterwaveProvider::class,
        'pesapal' => PesaPalProvider::class,
        'stripe' => StripeProvider::class,
        'paypal' => PayPalProvider::class,
        'bank_transfer' => BankTransferProvider::class,
        'cash' => CashProvider::class,
    ];

    private function __construct() {
        $this->loadProviders();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getDbConnection(): ?mysqli {
        if ($this->db && $this->db->ping()) return $this->db;
        if (function_exists('getStudentsConnection')) {
            $this->db = getStudentsConnection();
        } else {
            require_once __DIR__ . '/../../config/database.php';
            $this->db = getStudentsConnection();
        }
        return $this->db;
    }

    private function loadProviders(): void {
        $conn = $this->getDbConnection();
        if (!$conn) return;

        $result = $conn->query("SELECT * FROM payment_providers WHERE status IN ('active','sandbox') ORDER BY sort_order ASC");
        if (!$result) return;

        while ($row = $result->fetch_assoc()) {
            $key = $row['provider_key'];
            $this->providerConfigs[$key] = $row;
            $class = self::PROVIDER_CLASSES[$key] ?? null;
            if ($class && class_exists($class)) {
                $this->providers[$key] = new $class($row);
            }
        }
    }

    public function getAvailableProviders(): array {
        $available = [];
        foreach ($this->providers as $key => $provider) {
            if ($provider->isActive()) {
                $config = $this->providerConfigs[$key] ?? [];
                $available[$key] = [
                    'key' => $key,
                    'name' => $provider->getProviderName(),
                    'type' => $config['provider_type'] ?? 'custom',
                    'category' => $config['provider_category'] ?? 'local',
                    'status' => $config['status'] ?? 'inactive',
                    'min_amount' => (float)($config['min_amount'] ?? 0),
                    'max_amount' => (float)($config['max_amount'] ?? 999999),
                ];
            }
        }
        return $available;
    }

    public function getProvider(string $providerKey): ?BaseProvider {
        return $this->providers[$providerKey] ?? null;
    }

    /**
     * Generate a unique transaction reference.
     */
    public function generateReference(string $paymentFor = 'payment'): string {
        $prefix = strtoupper(substr($paymentFor, 0, 3));
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Initiate a payment through the specified provider.
     */
    public function initiatePayment(string $providerKey, array $paymentData): array {
        $provider = $this->getProvider($providerKey);
        if (!$provider) {
            return ['success' => false, 'message' => 'Payment provider "' . $providerKey . '" is not available.'];
        }
        if (!$provider->isActive()) {
            return ['success' => false, 'message' => 'Payment provider "' . $providerKey . '" is currently inactive.'];
        }

        $reference = $paymentData['reference'] ?? $this->generateReference($paymentData['payment_for'] ?? 'payment');
        $amount = (float)($paymentData['amount'] ?? 0);
        $currency = $paymentData['currency'] ?? 'UGX';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid payment amount.'];
        }

        $config = $this->providerConfigs[$providerKey] ?? [];
        $minAmount = (float)($config['min_amount'] ?? 0);
        $maxAmount = (float)($config['max_amount'] ?? 999999999);
        if ($amount < $minAmount || $amount > $maxAmount) {
            return ['success' => false, 'message' => 'Amount must be between ' . number_format($minAmount) . ' and ' . number_format($maxAmount) . ' UGX.'];
        }

        $payer = [
            'name' => $paymentData['payer_name'] ?? '',
            'phone' => $paymentData['payer_phone'] ?? '',
            'email' => $paymentData['payer_email'] ?? '',
            'first_name' => $paymentData['payer_first_name'] ?? '',
            'last_name' => $paymentData['payer_last_name'] ?? '',
        ];

        $description = $paymentData['description'] ?? 'ISNM Payment';

        $result = $provider->initiatePayment($reference, $amount, $currency, $payer, $description);

        if ($result['success'] && !$this->recordTransaction($reference, $providerKey, $paymentData, $result)) {
            return ['success' => false, 'message' => 'Failed to record transaction.'];
        }

        $result['reference'] = $reference;
        return $result;
    }

    /**
     * Verify a payment with the provider.
     */
    public function verifyPayment(string $providerKey, string $transactionId): array {
        $provider = $this->getProvider($providerKey);
        if (!$provider) {
            return ['success' => false, 'status' => 'unknown', 'message' => 'Unknown provider.'];
        }
        return $provider->verifyPayment($transactionId);
    }

    /**
     * Process an incoming callback from a provider.
     */
    public function processCallback(string $providerKey, array $payload, array $headers = [], ?string $rawBody = null): array {
        $provider = $this->getProvider($providerKey);
        if (!$provider) {
            return ['success' => false, 'message' => 'Unknown provider.'];
        }

        $body = $rawBody ?? json_encode($payload);
        $signature = method_exists($provider, 'signatureFromHeaders') ? $provider->signatureFromHeaders($headers) : '';
        if (!$provider->verifyWebhookSignature($body, $signature)) {
            error_log('[GatewayManager][' . $providerKey . '] callback rejected: signature verification failed');
            return ['success' => false, 'message' => 'Invalid webhook signature.'];
        }

        $this->logCallback($providerKey, $payload, $headers);
        $result = $provider->processCallback($payload, $headers);

        if (!empty($result['reference']) && !empty($result['status'])) {
            $this->updateTransactionStatus(
                (string) $result['reference'],
                (string) $result['status'],
                $result['provider_transaction_id'] ?? null,
                $result['amount'] ?? null,
                $result['currency'] ?? null
            );
        }

        return $result;
    }

    /**
     * Record a new transaction in the database.
     */
    private function recordTransaction(string $reference, string $providerKey, array $data, array $result): bool {
        $conn = $this->getDbConnection();
        if (!$conn) return false;

        $stmt = $conn->prepare("INSERT INTO payment_transactions
            (transaction_ref, provider_key, provider_transaction_id, payment_type, student_id, staff_id,
             payer_name, payer_phone, payer_email, amount, currency, fee_amount, status, metadata_json,
             initiated_by, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) return false;

        $txnRef = $reference;
        $pKey = $providerKey;
        $provTxn = $result['provider_ref'] ?? null;
        $payType = $data['payment_for'] ?? 'misc';
        $stuId = (int)($data['student_id'] ?? 0);
        $staffId = (int)($data['staff_id'] ?? 0);
        $payerName = $data['payer_name'] ?? '';
        $payerPhone = $data['payer_phone'] ?? '';
        $payerEmail = $data['payer_email'] ?? '';
        $amount = (float)($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'UGX';
        $fee = (float)($data['fee_amount'] ?? 0);
        $status = $result['status'] ?? 'pending';
        $meta = json_encode($data['metadata'] ?? []);
        $initiatedBy = (int)($data['initiated_by'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $ok = $stmt->bind_param('sssiissssddssisss',
            $txnRef, $pKey, $provTxn, $payType, $stuId, $staffId,
            $payerName, $payerPhone, $payerEmail, $amount, $currency, $fee,
            $status, $meta, $initiatedBy, $ip, $ua
        ) && $stmt->execute();
        if (!$ok) {
            error_log('[GatewayManager] failed to record transaction ' . $reference . ': ' . $stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    private const STATUS_RANKS = [
        'pending' => 0, 'processing' => 1, 'failed' => 2, 'cancelled' => 2,
        'expired' => 2, 'successful' => 3, 'refunded' => 4,
    ];

    private static function statusRank(string $status): int {
        return self::STATUS_RANKS[strtolower($status)] ?? 0;
    }

    private static function normalizeMoney($value): ?float {
        if ($value === null || $value === '' || !is_numeric($value)) return null;
        return round((float) $value, 2);
    }

    private static function amountMatches(float $expected, ?float $paid, string $expectedCurrency, $paidCurrency): bool {
        if ($paid === null || $paid <= 0) return false;
        if ($paidCurrency !== null && strtoupper(trim((string) $paidCurrency)) !== strtoupper(trim($expectedCurrency))) return false;
        return abs($expected - $paid) <= 0.01;
    }

    /**
     * Update transaction status after callback/verification.
     */
    public function updateTransactionStatus(string $reference, string $status, ?string $providerTxnId = null, $paidAmount = null, $paidCurrency = null): bool {
        $conn = $this->getDbConnection();
        if (!$conn || $reference === '') return false;

        $status = strtolower(trim($status));
        if (!array_key_exists($status, self::STATUS_RANKS)) {
            error_log('[GatewayManager] unsupported status ' . $status . ' for ' . $reference);
            return false;
        }

        $stmt = $conn->prepare("SELECT id, status, amount, currency FROM payment_transactions
            WHERE transaction_ref = ? OR provider_transaction_id = ? LIMIT 1");
        if (!$stmt) {
            error_log('[GatewayManager] failed to load transaction ' . $reference . ': ' . $conn->error);
            return false;
        }
        $stmt->bind_param('ss', $reference, $reference);
        if (!$stmt->execute()) {
            error_log('[GatewayManager] failed to load transaction ' . $reference . ': ' . $stmt->error);
            $stmt->close();
            return false;
        }
        $txn = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$txn) {
            error_log('[GatewayManager] transaction not found for status update: ' . $reference);
            return false;
        }

        $current = strtolower((string) $txn['status']);
        if (self::statusRank($status) < self::statusRank($current)) {
            error_log('[GatewayManager] ignored non-monotonic transition ' . $current . ' -> ' . $status . ' for ' . $reference);
            return true;
        }
        if ($status === $current && self::statusRank($status) >= 3) {
            return true;
        }

        $gross = self::normalizeMoney($txn['amount'] ?? null);
        if ($status === 'successful') {
            $paid = self::normalizeMoney($paidAmount);
            if ($gross === null || !self::amountMatches($gross, $paid, (string) ($txn['currency'] ?? 'UGX'), $paidCurrency)) {
                error_log('[GatewayManager] amount mismatch for ' . $reference . '; rejecting successful status');
                return false;
            }
        }

        $completedAt = ($status === 'successful') ? date('Y-m-d H:i:s') : null;
        $amountReceived = ($status === 'successful') ? $gross : null;
        $id = (int) $txn['id'];
        $rank = self::statusRank($status);

        if ($providerTxnId) {
            $stmt = $conn->prepare("UPDATE payment_transactions
                SET status = ?, provider_transaction_id = IFNULL(provider_transaction_id, ?),
                    amount_received = IFNULL(?, amount_received),
                    completed_at = IFNULL(?, completed_at), verified_at = IFNULL(?, verified_at),
                    callback_received_at = NOW(), updated_at = NOW()
                WHERE id = ? AND CASE status
                    WHEN 'refunded' THEN 4
                    WHEN 'successful' THEN 3
                    WHEN 'failed' THEN 2
                    WHEN 'cancelled' THEN 2
                    WHEN 'expired' THEN 2
                    WHEN 'processing' THEN 1
                    ELSE 0
                END < ?");
            if ($stmt) {
                $stmt->bind_param('sssssii', $status, $providerTxnId, $amountReceived, $completedAt, $completedAt, $id, $rank);
            }
        } else {
            $stmt = $conn->prepare("UPDATE payment_transactions
                SET status = ?, amount_received = IFNULL(?, amount_received),
                    completed_at = IFNULL(?, completed_at), verified_at = IFNULL(?, verified_at),
                    callback_received_at = NOW(), updated_at = NOW()
                WHERE id = ? AND CASE status
                    WHEN 'refunded' THEN 4
                    WHEN 'successful' THEN 3
                    WHEN 'failed' THEN 2
                    WHEN 'cancelled' THEN 2
                    WHEN 'expired' THEN 2
                    WHEN 'processing' THEN 1
                    ELSE 0
                END < ?");
            if ($stmt) {
                $stmt->bind_param('ssssii', $status, $amountReceived, $completedAt, $completedAt, $id, $rank);
            }
        }
        if (!$stmt) {
            error_log('[GatewayManager] failed to prepare status update for ' . $reference . ': ' . $conn->error);
            return false;
        }
        $ok = $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        if (!$ok) {
            error_log('[GatewayManager] failed to update status for ' . $reference . ': ' . $stmt->error);
        }
        $stmt->close();

        if ($ok && $changed && $status === 'successful') {
            $this->updateProviderStats($reference);
        }
        return $ok;
    }

    /**
     * Update provider totals after successful payment.
     */
    private function updateProviderStats(string $reference): void {
        $conn = $this->getDbConnection();
        if (!$conn) return;

        $stmt = $conn->prepare("UPDATE payment_providers p
            INNER JOIN payment_transactions t ON t.provider_key = p.provider_key
            SET p.total_transactions = p.total_transactions + 1,
                p.total_volume = p.total_volume + t.amount,
                p.last_transaction_at = NOW()
            WHERE t.transaction_ref = ?");
        if (!$stmt || !$stmt->bind_param('s', $reference) || !$stmt->execute()) {
            error_log('[GatewayManager] failed to update provider stats for ' . $reference);
        }
        if ($stmt) $stmt->close();
    }

    /**
     * Log callback/webhook.
     */
    private function logCallback(string $providerKey, array $payload, array $headers): void {
        $conn = $this->getDbConnection();
        if (!$conn) return;

        $stmt = $conn->prepare("INSERT INTO payment_callbacks
            (provider_key, callback_type, request_method, request_headers, request_body, request_ip)
            VALUES (?, 'webhook', 'POST', ?, ?, ?)");
        $hdrs = json_encode($headers);
        $body = json_encode($payload);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$stmt || !$stmt->bind_param('ssss', $providerKey, $hdrs, $body, $ip) || !$stmt->execute()) {
            error_log('[GatewayManager] failed to log callback for ' . $providerKey);
        }
        if ($stmt) $stmt->close();
    }

    /**
     * Get transaction history with filters.
     */
    public function getTransactions(array $filters = [], int $limit = 50, int $offset = 0): array {
        $conn = $this->getDbConnection();
        if (!$conn) return [];

        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['provider_key'])) {
            $where[] = 'provider_key = ?';
            $params[] = $filters['provider_key'];
            $types .= 's';
        }
        if (!empty($filters['student_id'])) {
            $where[] = 'student_id = ?';
            $params[] = (int)$filters['student_id'];
            $types .= 'i';
        }
        if (!empty($filters['payment_type'])) {
            $where[] = 'payment_type = ?';
            $params[] = $filters['payment_type'];
            $types .= 's';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $sql = "SELECT * FROM payment_transactions WHERE " . implode(' AND ', $where) .
               " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $stmt->close();
        return $transactions;
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(string $dateFrom = null, string $dateTo = null): array {
        $conn = $this->getDbConnection();
        if (!$conn) return [];

        $where = ['1=1'];
        $params = [];
        $types = '';

        if ($dateFrom) { $where[] = 'created_at >= ?'; $params[] = $dateFrom; $types .= 's'; }
        if ($dateTo) { $where[] = 'created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; $types .= 's'; }

        $whereClause = implode(' AND ', $where);

        $stats = [];

        $sql = "SELECT
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status='successful' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN status='successful' THEN amount ELSE 0 END), 0) as total_volume,
            COALESCE(SUM(CASE WHEN status='successful' THEN fee_amount ELSE 0 END), 0) as total_fees
        FROM payment_transactions WHERE " . $whereClause;

        $stmt = $conn->prepare($sql);
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt) {
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc() ?: [];
            $stmt->close();
        }

        $stats['by_provider'] = [];
        $sql2 = "SELECT provider_key, COUNT(*) as count,
            COALESCE(SUM(CASE WHEN status='successful' THEN amount ELSE 0 END), 0) as volume
        FROM payment_transactions WHERE " . $whereClause . " GROUP BY provider_key ORDER BY volume DESC";
        $stmt2 = $conn->prepare($sql2);
        if ($stmt2 && !empty($params)) {
            $stmt2->bind_param($types, ...$params);
        }
        if ($stmt2) {
            $stmt2->execute();
            $res = $stmt2->get_result();
            while ($row = $res->fetch_assoc()) {
                $stats['by_provider'][] = $row;
            }
            $stmt2->close();
        }

        return $stats;
    }
}
