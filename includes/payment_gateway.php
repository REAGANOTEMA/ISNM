<?php
/**
 * ISNM Payment Gateway — Complete Modular Architecture
 *
 * Contains:
 *   - Abstract class PaymentProvider (the interface all adapters implement)
 *   - Concrete provider adapters: MTNMoMo, AirtelMoney, Stripe, Flutterwave,
 *     PesaPal, PayPal, BankTransfer
 *   - PaymentGateway — unified service (routing, records, callbacks, receipts,
 *     notifications, audit logging)
 *
 * Usage:
 *   require_once __DIR__ . '/includes/payment_gateway.php';
 *   $gw = PaymentGateway::getInstance();
 *   $result = $gw->initiatePayment('mtn_momo', [ ... ]);
 *
 * Credentials are loaded from the payment_providers table (DB_PAYMENT_*
 * pattern) and .env. All providers ship with sandbox placeholders so the
 * system is ready to activate the moment real credentials arrive.
 */

/* ─────────────────────────────────────────────────────────────
   DATABASE HELPER (self-contained — no external dependency)
   ───────────────────────────────────────────────────────────── */

if (!class_exists('PaymentGatewayDB')) {
    final class PaymentGatewayDB {

        private static ?mysqli $conn = null;

        public static function getConnection(): ?mysqli {
            if (self::$conn instanceof mysqli && self::$conn->ping()) {
                return self::$conn;
            }

            if (function_exists('getStudentsConnection')) {
                self::$conn = getStudentsConnection();
                return self::$conn;
            }

            require_once __DIR__ . '/../config/database.php';
            if (function_exists('getStudentsConnection')) {
                self::$conn = getStudentsConnection();
            }
            return self::$conn;
        }

        public static function prepare(string $sql): ?mysqli_stmt {
            $conn = self::getConnection();
            if (!$conn) return null;
            return $conn->prepare($sql);
        }

        public static function lastInsertId(): int {
            $conn = self::getConnection();
            return $conn ? (int) $conn->insert_id : 0;
        }

        public static function escape(string $val): string {
            $conn = self::getConnection();
            return $conn ? $conn->real_escape_string($val) : htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
        }
    }
}

/* ─────────────────────────────────────────────────────────────
   ABSTRACT CLASS — PaymentProvider
   Every adapter must extend this and implement all methods.
   ───────────────────────────────────────────────────────────── */

if (!class_exists('PaymentProvider', false)) {
    abstract class PaymentProvider {

        protected string $providerKey   = 'unknown';
        protected string $providerName  = 'Unknown';
        protected array  $config        = [];
        protected bool   $isTestMode    = true;

        public function __construct(array $config = []) {
            $this->config     = $config;
            $this->isTestMode = !empty($config['is_test_mode']) || ($config['status'] ?? '') === 'testing';
            $this->providerKey  = $config['provider_key']  ?? $this->providerKey;
            $this->providerName = $config['provider_name'] ?? $this->providerName;
        }

        /* ── abstract contract ─────────────────────────────── */

        abstract public function initialize(array $params): array;
        abstract public function verify(string $providerTransactionId): array;
        abstract public function refund(string $providerTransactionId, float $amount, string $reason = ''): array;
        abstract public function getStatus(string $providerTransactionId): array;

        /* ── optional hooks (provider overrides as needed) ── */

        public function processCallback(array $payload, array $headers = []): array {
            return ['success' => false, 'message' => 'Callback handling not implemented.'];
        }

        public function verifyWebhookSignature(string $rawBody, string $signature): bool {
            return false;
        }

        /* ── accessors ────────────────────────────────────── */

        public function getKey(): string  { return $this->providerKey; }
        public function getName(): string { return $this->providerName; }
        public function isTestMode(): bool { return $this->isTestMode; }
        public function isActive(): bool {
            return in_array(($this->config['status'] ?? 'inactive'), ['active', 'testing'], true);
        }

        /* ── config helpers ────────────────────────────────── */

        protected function getApiUrl(): string {
            if ($this->isTestMode && !empty($this->config['test_api_base_url'])) {
                return rtrim($this->config['test_api_base_url'], '/');
            }
            return rtrim($this->config['api_base_url'] ?? $this->config['api_url'] ?? '', '/');
        }

        protected function getApiKey(): string {
            return $this->isTestMode
                ? ($this->config['test_api_key'] ?? $this->config['api_key'] ?? '')
                : ($this->config['api_key'] ?? '');
        }

        protected function getApiSecret(): string {
            return $this->isTestMode
                ? ($this->config['test_api_secret'] ?? $this->config['api_secret'] ?? '')
                : ($this->config['api_secret'] ?? '');
        }

        protected function getMerchantId(): string {
            return $this->isTestMode
                ? ($this->config['test_merchant_id'] ?? $this->config['merchant_id'] ?? '')
                : ($this->config['merchant_id'] ?? '');
        }

        protected function getHmacSecret(): string {
            return $this->config['hmac_secret'] ?? '';
        }

        /* ── fee calculation ──────────────────────────────── */

        protected function calculateFee(float $amount): float {
            $total = 0.0;
            $fixed = (float)($this->config['fee_fixed'] ?? $this->config['transaction_fee_fixed'] ?? 0);
            $pct   = (float)($this->config['fee_percentage'] ?? $this->config['transaction_fee_percent'] ?? 0);
            $total += $fixed;
            if ($pct > 0) $total += $amount * $pct / 100;
            return round($total, 2);
        }

        /* ── HTTP helpers ──────────────────────────────────── */

        protected function httpPost(string $url, $data, array $headers = [], int $timeout = 30): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => is_string($data) ? $data : json_encode($data),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => !$this->isTestMode,
            ]);
            $response = curl_exec($ch);
            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);
            return [
                'http_code' => $code,
                'response'  => $response,
                'data'      => json_decode($response, true),
                'error'     => $error,
            ];
        }

        protected function httpGet(string $url, array $headers = [], int $timeout = 15): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET        => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => !$this->isTestMode,
            ]);
            $response = curl_exec($ch);
            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);
            return [
                'http_code' => $code,
                'response'  => $response,
                'data'      => json_decode($response, true),
                'error'     => $error,
            ];
        }

        /* ── phone formatting ──────────────────────────────── */

        protected function formatPhone(string $phone, string $cc = '256'): string {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) === 9) $phone = $cc . $phone;
            return $phone;
        }

        /* ── logging ──────────────────────────────────────── */

        protected function logError(string $msg, array $ctx = []): void {
            $ctx['provider']  = $this->providerKey;
            $ctx['test_mode'] = $this->isTestMode;
            error_log('[PaymentGateway][' . $this->providerKey . '] ' . $msg . ' ' . json_encode($ctx));
        }
    }
}

/* ═════════════════════════════════════════════════════════════
   PROVIDER ADAPTERS
   ═════════════════════════════════════════════════════════════ */

if (!class_exists('MTNMoMoProvider', false)) {

    /**
     * MTN Mobile Money — Collection API (Request to Pay)
     * Docs: https://momodeveloper.mtn.com/api-documentation
     */
    class MTNMoMoProvider extends PaymentProvider {

        protected string $providerKey  = 'mtn_momo';
        protected string $providerName = 'MTN Mobile Money';
        private string $subscriptionKey;

        public function __construct(array $config = []) {
            parent::__construct($config);
            $this->subscriptionKey = $this->isTestMode
                ? ($config['test_api_key'] ?? $config['api_key'] ?? '')
                : ($config['api_key'] ?? '');
        }

        public function initialize(array $params): array {
            $phone = $this->formatPhone($params['phone'] ?? $params['payer_phone'] ?? '');
            if (strlen($phone) < 12) {
                return ['success' => false, 'message' => 'Invalid phone number. Use 2567XXXXXXXX format.'];
            }

            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to authenticate with MTN MoMo API. Check credentials.'];
            }

            $reference = $params['reference'] ?? '';
            $amount    = (float)($params['amount'] ?? 0);
            $currency  = $params['currency'] ?? 'UGX';

            $payload = [
                'amount'       => ['currency' => $currency, 'value' => (string) $amount],
                'externalId'   => substr($reference, 0, 30),
                'payer'        => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
                'payerMessage' => substr($params['description'] ?? 'ISNM Payment', 0, 50),
                'payeeNote'    => 'ISNM Fee Payment',
            ];

            $url = $this->getApiUrl() . '/v1_0/requesttopay';
            $result = $this->httpPost($url, $payload, [
                'Authorization: Bearer ' . $token,
                'X-Reference-Id: ' . $reference,
                'X-Target-Environment: ' . ($this->isTestMode ? 'sandbox' : 'production'),
                'Content-Type: application/json',
                'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            ]);

            if ($result['http_code'] === 202 || $result['http_code'] === 200) {
                return [
                    'success'      => true,
                    'provider_ref' => $reference,
                    'status'       => 'pending',
                    'message'      => 'MTN MoMo payment request sent. Please approve on your phone.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => 'MTN MoMo gateway unavailable. Please try again later.'];
        }

        public function verify(string $providerTransactionId): array {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'status' => 'unknown', 'message' => 'Auth failed.'];
            }

            $url = $this->getApiUrl() . '/v1_0/requesttopay/' . $providerTransactionId;
            $result = $this->httpGet($url, [
                'Authorization: Bearer ' . $token,
                'X-Target-Environment: ' . ($this->isTestMode ? 'sandbox' : 'production'),
                'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['status'] ?? 'UNKNOWN';
                $mapped = match (strtoupper($raw)) {
                    'SUCCESSFUL'      => 'successful',
                    'FAILED','REJECTED' => 'failed',
                    'TIMEOUT'         => 'expired',
                    default           => 'processing',
                };
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'amount'          => $result['data']['amount']['value'] ?? null,
                    'currency'        => $result['data']['amount']['currency'] ?? null,
                    'message'         => 'Transaction: ' . strtolower($raw),
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            return ['success' => false, 'message' => 'MTN MoMo refunds are not supported via API. Use the MTN merchant portal.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $extId = $payload['externalId'] ?? $payload['external_id'] ?? '';
            $raw   = $payload['status'] ?? 'unknown';
            $txnId = $payload['transactionId'] ?? $payload['transaction_id'] ?? '';

            $mapped = match (strtoupper($raw)) {
                'SUCCESSFUL','COMPLETED' => 'successful',
                'FAILED','REJECTED'     => 'failed',
                'TIMEOUT'               => 'expired',
                default                 => 'processing',
            };

            return [
                'success'                => true,
                'reference'              => $extId,
                'provider_transaction_id' => $txnId,
                'status'                 => $mapped,
                'message'                => 'MTN callback processed',
            ];
        }

        public function verifyWebhookSignature(string $rawBody, string $signature): bool {
            $secret = $this->getHmacSecret();
            if (empty($secret)) return true;
            $expected = hash_hmac('sha256', $rawBody, $secret);
            return hash_equals($expected, $signature);
        }

        private function getAccessToken(): ?string {
            $url = $this->getApiUrl() . '/v1_0/apiuser/' . $this->getApiKey() . '/apikey';
            $result = $this->httpPost($url, '', [
                'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            ], 15);
            if ($result['http_code'] === 201 && $result['data']) {
                return $result['data']['apiKey'] ?? null;
            }
            $this->logError('Token fetch failed', ['http' => $result['http_code']]);
            return null;
        }
    }
}

if (!class_exists('AirtelMoneyProvider', false)) {

    /**
     * Airtel Money — Collection API (Uganda)
     * Docs: https://developer.airtel.africa/docs
     */
    class AirtelMoneyProvider extends PaymentProvider {

        protected string $providerKey  = 'airtel_money';
        protected string $providerName = 'Airtel Money';

        public function initialize(array $params): array {
            $phone = $this->formatPhone($params['phone'] ?? $params['payer_phone'] ?? '');
            if (strlen($phone) < 12) {
                return ['success' => false, 'message' => 'Invalid phone number. Use 2567XXXXXXXX format.'];
            }

            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to authenticate with Airtel Money API.'];
            }

            $reference = $params['reference'] ?? '';
            $amount    = (float)($params['amount'] ?? 0);
            $currency  = $params['currency'] ?? 'UGX';

            $payload = [
                'reference' => $reference,
                'subscriber' => ['country' => 'UGA', 'currency' => $currency, 'msisdn' => $phone],
                'transaction' => ['amount' => $amount, 'country' => 'UGA', 'currency' => $currency, 'id' => $reference],
            ];

            $url = $this->getApiUrl() . '/standard/v1/payments/';
            $result = $this->httpPost($url, $payload, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'X-Country: UGA',
                'X-Currency: ' . $currency,
            ]);

            if ($result['http_code'] === 200 || $result['http_code'] === 201) {
                $txnId = $result['data']['transaction']['id'] ?? $reference;
                return [
                    'success'      => true,
                    'provider_ref' => $txnId,
                    'status'       => 'pending',
                    'message'      => 'Airtel Money request sent. Please approve on your phone.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => 'Airtel Money gateway unavailable.'];
        }

        public function verify(string $providerTransactionId): array {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'status' => 'unknown', 'message' => 'Auth failed.'];
            }

            $url = $this->getApiUrl() . '/standard/v1/payments/' . $providerTransactionId;
            $result = $this->httpGet($url, [
                'Authorization: Bearer ' . $token,
                'X-Country: UGA',
                'X-Currency: UGX',
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['status'] ?? 'unknown';
                $mapped = match ($raw) {
                    'TS','SUCCESSFUL' => 'successful',
                    'TF','FAILED'     => 'failed',
                    'TI','INITIATED'  => 'pending',
                    default           => 'processing',
                };
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'message'         => 'Transaction status: ' . $raw,
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            return ['success' => false, 'message' => 'Airtel Money refunds are not supported via API. Contact Airtel support.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $txnId = $payload['transaction']['id'] ?? $payload['transactionId'] ?? '';
            $raw   = $payload['status'] ?? $payload['transaction']['status'] ?? 'unknown';
            $ref   = $payload['reference'] ?? $txnId;

            $mapped = match ($raw) {
                'TS','SUCCESSFUL','COMPLETED' => 'successful',
                'TF','FAILED'                => 'failed',
                'TI','INITIATED'             => 'processing',
                default                      => 'processing',
            };

            return [
                'success'                => true,
                'reference'              => $ref,
                'provider_transaction_id' => $txnId,
                'status'                 => $mapped,
                'message'                => 'Airtel callback processed',
            ];
        }

        private function getAccessToken(): ?string {
            $url = $this->getApiUrl() . '/auth/oauth2/token';
            $result = $this->httpPost($url, [
                'client_id'     => $this->getApiKey(),
                'client_secret' => $this->getApiSecret(),
                'grant_type'    => 'client_credentials',
            ], ['Content-Type: application/json'], 15);
            if ($result['http_code'] === 200 && $result['data']) {
                return $result['data']['access_token'] ?? null;
            }
            $this->logError('Token fetch failed', ['http' => $result['http_code']]);
            return null;
        }
    }
}

if (!class_exists('StripeProvider', false)) {

    /**
     * Stripe — Checkout Sessions API
     * Docs: https://stripe.com/docs/payments/checkout
     */
    class StripeProvider extends PaymentProvider {

        protected string $providerKey  = 'stripe';
        protected string $providerName = 'Stripe';

        public function initialize(array $params): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.stripe.com/v1';
            $currency = strtolower($params['currency'] ?? 'usd');
            $amountCents = (int)(($params['amount'] ?? 0) * 100);

            $payload = [
                'payment_method_types[]'                        => 'card',
                'line_items[0][price_data][currency]'           => $currency,
                'line_items[0][price_data][product_data][name]' => 'ISNM Payment - ' . ($params['reference'] ?? ''),
                'line_items[0][price_data][unit_amount]'        => $amountCents,
                'line_items[0][quantity]'                       => 1,
                'mode'                                          => 'payment',
                'success_url'                                   => $params['return_url'] ?? ($this->config['return_url'] ?? ''),
                'cancel_url'                                    => $params['cancel_url'] ?? ($this->config['return_url'] ?? ''),
                'client_reference_id'                           => $params['reference'] ?? '',
                'customer_email'                                => $params['payer_email'] ?? ($params['email'] ?? ''),
                'metadata[reference]'                           => $params['reference'] ?? '',
                'metadata[school]'                              => 'ISNM',
            ];

            $result = $this->httpPost($apiUrl . '/checkout/sessions', $payload, [
                'Authorization: Bearer ' . $this->getApiKey(),
                'Content-Type: application/x-www-form-urlencoded',
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                return [
                    'success'      => true,
                    'provider_ref' => $result['data']['id'] ?? ($params['reference'] ?? ''),
                    'status'       => 'pending',
                    'payment_url'  => $result['data']['url'] ?? '',
                    'message'      => 'Redirect to Stripe to complete payment.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            $msg = $result['data']['error']['message'] ?? 'Stripe gateway error.';
            return ['success' => false, 'message' => $msg];
        }

        public function verify(string $providerTransactionId): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.stripe.com/v1';
            $result = $this->httpGet($apiUrl . '/checkout/sessions/' . $providerTransactionId, [
                'Authorization: Bearer ' . $this->getApiKey(),
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['payment_status'] ?? 'unknown';
                $mapped = match ($raw) {
                    'paid','no_payment_required' => 'successful',
                    'unpaid'                     => 'failed',
                    default                      => 'processing',
                };
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'amount'          => ($result['data']['amount_total'] ?? 0) / 100,
                    'currency'        => strtoupper($result['data']['currency'] ?? ''),
                    'message'         => 'Stripe status: ' . $raw,
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify Stripe transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.stripe.com/v1';

            $payload = [
                'payment_intent' => $providerTransactionId,
                'amount'         => (int)($amount * 100),
                'reason'         => $reason ?: 'requested_by_customer',
            ];

            $result = $this->httpPost($apiUrl . '/refunds', $payload, [
                'Authorization: Bearer ' . $this->getApiKey(),
                'Content-Type: application/x-www-form-urlencoded',
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                return [
                    'success'  => true,
                    'refund_id' => $result['data']['id'] ?? '',
                    'status'   => $result['data']['status'] ?? 'pending',
                    'message'  => 'Refund initiated.',
                ];
            }
            $this->logError('refund failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => 'Stripe refund failed.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $type = $payload['type'] ?? '';
            $obj  = $payload['data']['object'] ?? [];
            $sessionId = $obj['id'] ?? '';
            $ref       = $obj['client_reference_id'] ?? $sessionId;

            $mapped = match ($type) {
                'checkout.session.completed' => 'successful',
                'checkout.session.expired'   => 'expired',
                default                      => 'processing',
            };

            return [
                'success'                => true,
                'reference'              => $ref,
                'provider_transaction_id' => $sessionId,
                'status'                 => $mapped,
                'message'                => 'Stripe webhook: ' . $type,
            ];
        }

        public function verifyWebhookSignature(string $rawBody, string $signature): bool {
            $secret = $this->getHmacSecret();
            if (empty($secret)) return true;
            $parts  = explode(',', $signature);
            $ts     = null;
            $sig    = null;
            foreach ($parts as $part) {
                [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
                if ($k === 't')  $ts  = $v;
                if ($k === 'v1') $sig = $v;
            }
            if (!$ts || !$sig) return false;
            $payload = $ts . '.' . $rawBody;
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $sig);
        }
    }
}

if (!class_exists('FlutterwaveProvider', false)) {

    /**
     * Flutterwave — Standard API (v3)
     * Docs: https://developer.flutterwave.com/docs
     */
    class FlutterwaveProvider extends PaymentProvider {

        protected string $providerKey  = 'flutterwave';
        protected string $providerName = 'Flutterwave';

        public function initialize(array $params): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.flutterwave.com';

            $payload = [
                'tx_ref'       => $params['reference'] ?? '',
                'amount'       => (string) ($params['amount'] ?? 0),
                'currency'     => $params['currency'] ?? 'UGX',
                'redirect_url' => $params['return_url'] ?? ($this->config['return_url'] ?? $this->config['callback_url'] ?? ''),
                'customer'     => [
                    'email'       => $params['payer_email'] ?? $params['email'] ?? '',
                    'phonenumber' => $params['phone'] ?? $params['payer_phone'] ?? '',
                    'name'        => $params['payer_name'] ?? $params['name'] ?? '',
                ],
                'customizations' => [
                    'title'       => 'ISNM Fee Payment',
                    'description' => $params['description'] ?? 'Payment for ISNM',
                ],
            ];

            $result = $this->httpPost($apiUrl . '/v3/payments', $payload, [
                'Authorization: Bearer ' . $this->getApiKey(),
                'Content-Type: application/json',
            ]);

            if ($result['http_code'] === 200 && isset($result['data']['data']['link'])) {
                return [
                    'success'      => true,
                    'provider_ref' => $params['reference'] ?? '',
                    'status'       => 'pending',
                    'payment_url'  => $result['data']['data']['link'],
                    'message'      => 'Redirect to Flutterwave to complete payment.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => $result['data']['message'] ?? 'Flutterwave gateway error.'];
        }

        public function verify(string $providerTransactionId): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.flutterwave.com';
            $result = $this->httpGet($apiUrl . '/v3/transactions/' . $providerTransactionId . '/verify', [
                'Authorization: Bearer ' . $this->getApiKey(),
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['data']['status'] ?? 'unknown';
                $mapped = match ($raw) {
                    'successful' => 'successful',
                    'failed'     => 'failed',
                    'cancelled'  => 'cancelled',
                    default      => 'processing',
                };
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'amount'          => $result['data']['data']['amount'] ?? null,
                    'currency'        => $result['data']['data']['currency'] ?? null,
                    'message'         => 'Flutterwave status: ' . $raw,
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify Flutterwave transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api.flutterwave.com';
            $result = $this->httpPost($apiUrl . '/v3/transactions/' . $providerTransactionId . '/refund', [
                'amount' => $amount,
            ], [
                'Authorization: Bearer ' . $this->getApiKey(),
                'Content-Type: application/json',
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                return [
                    'success'  => true,
                    'refund_id' => $result['data']['data']['id'] ?? '',
                    'status'   => 'pending',
                    'message'  => 'Flutterwave refund initiated.',
                ];
            }
            $this->logError('refund failed', ['http' => $result['http_code']]);
            return ['success' => false, 'message' => 'Flutterwave refund failed.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $raw   = $payload['data']['status'] ?? 'unknown';
            $txRef = $payload['data']['tx_ref'] ?? '';
            $provId = $payload['data']['id'] ?? '';

            $mapped = match ($raw) {
                'successful' => 'successful',
                'failed'     => 'failed',
                'cancelled'  => 'cancelled',
                default      => 'processing',
            };

            return [
                'success'                => true,
                'reference'              => $txRef,
                'provider_transaction_id' => (string) $provId,
                'status'                 => $mapped,
                'message'                => 'Flutterwave callback processed',
            ];
        }

        public function verifyWebhookSignature(string $rawBody, string $signature): bool {
            $secret = $this->getHmacSecret();
            if (empty($secret)) return true;
            $expected = hash_hmac('sha256', $rawBody, $secret);
            return hash_equals($expected, $signature);
        }
    }
}

if (!class_exists('PesapalProvider', false)) {

    /**
     * PesaPal — Order API v3
     * Docs: https://pesapal.github.io/pesapal-api-docs/
     */
    class PesapalProvider extends PaymentProvider {

        protected string $providerKey  = 'pesapal';
        protected string $providerName = 'PesaPal';

        public function initialize(array $params): array {
            $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';
            $token  = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to authenticate with PesaPal.'];
            }

            $payload = [
                'id'          => $params['reference'] ?? '',
                'currency'    => $params['currency'] ?? 'UGX',
                'amount'      => (float) ($params['amount'] ?? 0),
                'description' => ($params['description'] ?? 'ISNM Fee Payment') . ' - ' . ($params['reference'] ?? ''),
                'callback_url' => $params['callback_url'] ?? ($this->config['callback_url'] ?? ''),
                'billing_address' => [
                    'email_address' => $params['payer_email'] ?? $params['email'] ?? '',
                    'phone_number'  => $params['phone'] ?? $params['payer_phone'] ?? '',
                    'first_name'    => $params['payer_first_name'] ?? $params['payer_name'] ?? '',
                    'last_name'     => $params['payer_last_name'] ?? '',
                    'country_code'  => 'UG',
                ],
            ];

            $result = $this->httpPost($apiUrl . '/Orders', $payload, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                return [
                    'success'      => true,
                    'provider_ref' => $result['data']['order_tracking_id'] ?? ($params['reference'] ?? ''),
                    'status'       => 'pending',
                    'payment_url'  => $result['data']['redirect_url'] ?? '',
                    'message'      => 'Redirect to PesaPal to complete payment.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => 'PesaPal gateway unavailable.'];
        }

        public function verify(string $providerTransactionId): array {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'status' => 'unknown', 'message' => 'Auth failed.'];
            }

            $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';
            $result = $this->httpGet($apiUrl . '/Transactions/' . $providerTransactionId, [
                'Authorization: Bearer ' . $token,
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['payment_status'] ?? 'unknown';
                $mapped = match ($raw) {
                    'Completed','SUCCESS'  => 'successful',
                    'Failed','FAILED'      => 'failed',
                    'Pending','PENDING'    => 'processing',
                    default                => 'processing',
                };
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'message'         => 'PesaPal status: ' . $raw,
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify PesaPal transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            return ['success' => false, 'message' => 'PesaPal refunds must be processed through the merchant portal.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $raw     = $payload['payment_status'] ?? $payload['status'] ?? 'unknown';
            $orderRef = $payload['order_tracking_id'] ?? $payload['order_merchant_reference'] ?? '';

            $mapped = match ($raw) {
                'Completed','SUCCESS' => 'successful',
                'Failed','FAILED'     => 'failed',
                'Pending','PENDING'   => 'processing',
                default               => 'processing',
            };

            return [
                'success'                => true,
                'reference'              => $orderRef,
                'provider_transaction_id' => $orderRef,
                'status'                 => $mapped,
                'message'                => 'PesaPal callback processed',
            ];
        }

        private function getAccessToken(): ?string {
            $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';
            $result = $this->httpPost($apiUrl . '/Auth/RequestToken', [
                'consumer_key'    => $this->getApiKey(),
                'consumer_secret' => $this->getApiSecret(),
            ], ['Content-Type: application/json'], 15);

            if ($result['http_code'] === 200 && $result['data']) {
                return $result['data']['token'] ?? null;
            }
            $this->logError('Token fetch failed', ['http' => $result['http_code']]);
            return null;
        }
    }
}

if (!class_exists('PayPalProvider', false)) {

    /**
     * PayPal — Orders API v2
     * Docs: https://developer.paypal.com/docs/api/orders/v2/
     */
    class PayPalProvider extends PaymentProvider {

        protected string $providerKey  = 'paypal';
        protected string $providerName = 'PayPal';

        public function initialize(array $params): array {
            $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
            $token  = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to authenticate with PayPal.'];
            }

            $currency = strtoupper($params['currency'] ?? 'USD');
            $amount   = number_format((float) ($params['amount'] ?? 0), 2, '.', '');
            $returnUrl = $params['return_url'] ?? ($this->config['return_url'] ?? '');
            $cancelUrl = $params['cancel_url'] ?? $returnUrl;

            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => substr($params['reference'] ?? '', 0, 127),
                    'amount' => [
                        'currency_code' => $currency,
                        'value'         => $amount,
                    ],
                    'description' => $params['description'] ?? 'ISNM Payment',
                ]],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'brand_name' => 'Iganga School of Nursing and Midwifery',
                    'locale'     => 'en-US',
                ],
            ];

            $result = $this->httpPost($apiUrl . '/v2/checkout/orders', $payload, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]);

            if ($result['http_code'] === 201 && $result['data']) {
                $approveUrl = '';
                foreach ($result['data']['links'] ?? [] as $link) {
                    if (($link['rel'] ?? '') === 'approve') {
                        $approveUrl = $link['href'] ?? '';
                        break;
                    }
                }
                return [
                    'success'      => true,
                    'provider_ref' => $result['data']['id'] ?? '',
                    'status'       => 'pending',
                    'payment_url'  => $approveUrl,
                    'message'      => 'Redirect to PayPal to complete payment.',
                ];
            }

            $this->logError('initialize failed', ['http' => $result['http_code'], 'resp' => $result['response']]);
            return ['success' => false, 'message' => 'PayPal gateway error.'];
        }

        public function verify(string $providerTransactionId): array {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'status' => 'unknown', 'message' => 'Auth failed.'];
            }

            $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
            $result = $this->httpGet($apiUrl . '/v2/checkout/orders/' . $providerTransactionId, [
                'Authorization: Bearer ' . $token,
            ]);

            if ($result['http_code'] === 200 && $result['data']) {
                $raw    = $result['data']['status'] ?? 'unknown';
                $mapped = match ($raw) {
                    'COMPLETED' => 'successful',
                    'VOIDED'    => 'cancelled',
                    'PENDING'   => 'processing',
                    'APPROVED'  => 'processing',
                    default     => 'processing',
                };
                $amountVal = $result['data']['purchase_units'][0]['amount']['value'] ?? null;
                return [
                    'success'         => $mapped === 'successful',
                    'status'          => $mapped,
                    'provider_status' => $raw,
                    'amount'          => $amountVal ? (float) $amountVal : null,
                    'currency'        => $result['data']['purchase_units'][0]['amount']['currency_code'] ?? null,
                    'message'         => 'PayPal status: ' . $raw,
                ];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify PayPal transaction.'];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            $token = $this->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Auth failed.'];
            }

            $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';

            $captureId = '';
            $verifyResult = $this->verify($providerTransactionId);
            if ($verifyResult['success']) {
                $captureId = $providerTransactionId;
            }

            $payload = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value'         => number_format($amount, 2, '.', ''),
                ],
            ];
            if ($reason) {
                $payload['note_to_payer'] = $reason;
            }

            $result = $this->httpPost($apiUrl . '/v2/payments/captures/' . $captureId . '/refund', $payload, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]);

            if ($result['http_code'] === 201 && $result['data']) {
                return [
                    'success'  => true,
                    'refund_id' => $result['data']['id'] ?? '',
                    'status'   => 'pending',
                    'message'  => 'PayPal refund initiated.',
                ];
            }
            $this->logError('refund failed', ['http' => $result['http_code']]);
            return ['success' => false, 'message' => 'PayPal refund failed.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }

        public function processCallback(array $payload, array $headers = []): array {
            $resourceType = $payload['resource_type'] ?? '';
            $eventType    = $payload['event_type'] ?? '';
            $resource     = $payload['resource'] ?? [];
            $orderId      = $resource['id'] ?? '';
            $raw          = $resource['status'] ?? 'unknown';

            $mapped = match ($raw) {
                'COMPLETED' => 'successful',
                'VOIDED'    => 'cancelled',
                default     => 'processing',
            };

            $customId = $resource['custom_id'] ?? $resource['id'] ?? '';

            return [
                'success'                => true,
                'reference'              => $customId,
                'provider_transaction_id' => $orderId,
                'status'                 => $mapped,
                'message'                => 'PayPal webhook: ' . $eventType,
            ];
        }

        public function verifyWebhookSignature(string $rawBody, string $signature): bool {
            $algo     = 'sha256';
            $authAlgo = $this->config['webhook_auth_algo'] ?? 'sha256';
            $secret   = $this->getHmacSecret();
            if (empty($secret)) return true;
            $expected = base64_encode(hash_hmac($algo, $rawBody, $secret, true));
            return hash_equals($expected, $signature);
        }

        private function getAccessToken(): ?string {
            $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
            $cred   = base64_encode($this->getApiKey() . ':' . $this->getApiSecret());
            $result = $this->httpPost($apiUrl . '/v1/oauth2/token', 'grant_type=client_credentials', [
                'Authorization: Basic ' . $cred,
                'Content-Type: application/x-www-form-urlencoded',
            ], 15);

            if ($result['http_code'] === 200 && $result['data']) {
                return $result['data']['access_token'] ?? null;
            }
            $this->logError('Token fetch failed', ['http' => $result['http_code']]);
            return null;
        }
    }
}

if (!class_exists('BankTransferProvider', false)) {

    /**
     * Bank Transfer — manual with proof-of-payment upload
     * No API integration; bank details are returned for the user.
     */
    class BankTransferProvider extends PaymentProvider {

        protected string $providerKey  = 'direct_bank';
        protected string $providerName = 'Direct Bank Transfer';

        public function initialize(array $params): array {
            $bankDetails = [
                'bank_name'     => $this->config['bank_name'] ?? 'Stanbic Bank Uganda',
                'account_name'  => $this->config['bank_account_name'] ?? 'Iganga School of Nursing and Midwifery',
                'account_number' => $this->config['bank_account_number'] ?? '',
                'swift_code'    => $this->config['bank_swift_code'] ?? '',
                'branch'        => $this->config['bank_branch'] ?? 'Iganga',
                'reference'     => $params['reference'] ?? '',
                'amount'        => (float) ($params['amount'] ?? 0),
                'currency'      => $params['currency'] ?? 'UGX',
            ];

            return [
                'success'      => true,
                'provider_ref' => $params['reference'] ?? '',
                'status'       => 'pending',
                'bank_details' => $bankDetails,
                'message'      => 'Transfer the exact amount. Use reference: ' . ($params['reference'] ?? ''),
                'instructions' => 'After transferring, upload your payment slip for verification by the finance team.',
            ];
        }

        public function verify(string $providerTransactionId): array {
            return [
                'success' => false,
                'status'  => 'pending',
                'message' => 'Bank transfers require manual verification by the finance team.',
            ];
        }

        public function refund(string $providerTransactionId, float $amount, string $reason = ''): array {
            return ['success' => false, 'message' => 'Bank transfer refunds must be processed manually by finance.'];
        }

        public function getStatus(string $providerTransactionId): array {
            return $this->verify($providerTransactionId);
        }
    }
}

/* ═════════════════════════════════════════════════════════════
   UNIFIED PAYMENT GATEWAY SERVICE
   ═════════════════════════════════════════════════════════════ */

if (!class_exists('PaymentGateway', false)) {

    class PaymentGateway {

        private static ?self $instance = null;

        /** @var PaymentProvider[] key => provider */
        private array $providers = [];

        /** @var array key => config row */
        private array $providerConfigs = [];

        private function __construct() {
            $this->loadProviders();
        }

        public static function getInstance(): self {
            if (self::$instance === null) self::$instance = new self();
            return self::$instance;
        }

        /* ── Provider class map ───────────────────────────── */

        private const PROVIDER_CLASSES = [
            'mtn_momo'      => MTNMoMoProvider::class,
            'airtel_money'  => AirtelMoneyProvider::class,
            'stripe'        => StripeProvider::class,
            'flutterwave'   => FlutterwaveProvider::class,
            'pesapal'       => PesapalProvider::class,
            'paypal'        => PayPalProvider::class,
            'direct_bank'   => BankTransferProvider::class,
        ];

        /* ── Load active providers from DB ────────────────── */

        private function loadProviders(): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $result = $conn->query(
                "SELECT * FROM payment_providers WHERE status IN ('active','testing') ORDER BY sort_order ASC"
            );
            if (!$result) return;

            while ($row = $result->fetch_assoc()) {
                $key = $row['provider_key'];
                $this->providerConfigs[$key] = $row;

                $className = self::PROVIDER_CLASSES[$key] ?? null;
                if ($className && class_exists($className)) {
                    $this->providers[$key] = new $className($row);
                }
            }
        }

        /* ── Public API ───────────────────────────────────── */

        public function getAvailableProviders(): array {
            $out = [];
            foreach ($this->providers as $key => $p) {
                if (!$p->isActive()) continue;
                $cfg = $this->providerConfigs[$key] ?? [];
                $out[$key] = [
                    'key'        => $key,
                    'name'       => $p->getName(),
                    'type'       => $cfg['provider_type'] ?? 'custom',
                    'category'   => $cfg['provider_category'] ?? 'local',
                    'currency'   => $cfg['currency'] ?? 'UGX',
                    'min_amount' => (float) ($cfg['min_amount'] ?? 0),
                    'max_amount' => (float) ($cfg['max_amount'] ?? 999999),
                ];
            }
            return $out;
        }

        public function getProvider(string $key): ?PaymentProvider {
            return $this->providers[$key] ?? null;
        }

        public function getProviderConfig(string $key): ?array {
            return $this->providerConfigs[$key] ?? null;
        }

        /**
         * Generate a unique transaction reference.
         */
        public function generateReference(string $type = 'payment'): string {
            $prefix = strtoupper(substr($type, 0, 3));
            return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        }

        /**
         * Initiate a payment through the specified provider.
         *
         * @param string $providerKey  e.g. 'mtn_momo'
         * @param array  $params       amount, currency, phone, email, reference, student_id, etc.
         * @return array
         */
        public function initiatePayment(string $providerKey, array $params): array {
            $provider = $this->getProvider($providerKey);
            if (!$provider) {
                return ['success' => false, 'message' => 'Provider "' . $providerKey . '" is not available.'];
            }
            if (!$provider->isActive()) {
                return ['success' => false, 'message' => 'Provider "' . $providerKey . '" is currently inactive.'];
            }

            $cfg = $this->providerConfigs[$providerKey] ?? [];
            $amount = (float) ($params['amount'] ?? 0);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'Invalid payment amount.'];
            }

            $min = (float) ($cfg['min_amount'] ?? 0);
            $max = (float) ($cfg['max_amount'] ?? 999999999);
            if ($amount < $min || $amount > $max) {
                return ['success' => false, 'message' => 'Amount must be between ' . number_format($min) . ' and ' . number_format($max) . '.'];
            }

            $reference = $params['reference'] ?? $this->generateReference($params['payment_for'] ?? 'payment');
            $params['reference'] = $reference;

            $result = $provider->initialize($params);

            if ($result['success']) {
                $this->recordTransaction($reference, $providerKey, $params, $result);
                $this->auditLog('payment_initiated', 'transaction', 0, null, [
                    'reference'  => $reference,
                    'provider'   => $providerKey,
                    'amount'     => $amount,
                    'currency'   => $params['currency'] ?? 'UGX',
                ]);
            }

            $result['reference'] = $reference;
            return $result;
        }

        /**
         * Verify a payment via the provider API.
         */
        public function verifyPayment(string $providerKey, string $providerTxnId): array {
            $provider = $this->getProvider($providerKey);
            if (!$provider) {
                return ['success' => false, 'status' => 'unknown', 'message' => 'Unknown provider.'];
            }
            $result = $provider->verify($providerTxnId);

            if (!empty($result['status'])) {
                $this->updateTransactionStatusByProviderRef($providerTxnId, $result['status']);
            }

            return $result;
        }

        /**
         * Process a callback / webhook from a provider.
         */
        public function processCallback(string $providerKey, array $payload, array $headers = []): array {
            $provider = $this->getProvider($providerKey);
            if (!$provider) {
                return ['success' => false, 'message' => 'Unknown provider.'];
            }

            $this->logCallbackRaw($providerKey, 'callback', $payload, $headers);
            $result = $provider->processCallback($payload, $headers);

            if (!empty($result['reference']) && !empty($result['status'])) {
                $this->updateTransactionStatus($result['reference'], $result['status'], $result['provider_transaction_id'] ?? null);
            }

            return $result;
        }

        /**
         * Process a webhook (signature verification included).
         */
        public function processWebhook(string $providerKey, string $rawBody, array $payload, array $headers = []): array {
            $provider = $this->getProvider($providerKey);
            if (!$provider) {
                return ['success' => false, 'message' => 'Unknown provider.'];
            }

            $signature   = $headers['X-Hub-Signature-256'] ?? $headers['X-Flutterwave-Signature'] ?? $headers['X-PayPal-Signature'] ?? '';
            $sigValid    = $provider->verifyWebhookSignature($rawBody, $signature);
            $startTime   = microtime(true);

            $this->logWebhook($providerKey, $payload, $headers, $signature, $sigValid);

            if (!$sigValid && !empty($signature)) {
                $this->updateWebhookLogStatus($providerKey, 'failed', 'Invalid signature');
                return ['success' => false, 'message' => 'Invalid webhook signature.'];
            }

            $result = $provider->processCallback($payload, $headers);
            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            if (!empty($result['reference']) && !empty($result['status'])) {
                $this->updateTransactionStatus($result['reference'], $result['status'], $result['provider_transaction_id'] ?? null);
            }

            $this->updateWebhookLogStatus($providerKey, 'processed', null, $elapsed);

            return $result;
        }

        /**
         * Process a refund.
         */
        public function refundPayment(string $providerKey, string $providerTxnId, float $amount, string $reason = '', int $approvedBy = 0): array {
            $provider = $this->getProvider($providerKey);
            if (!$provider) {
                return ['success' => false, 'message' => 'Unknown provider.'];
            }

            $refundRef = $this->generateReference('ref');
            $result = $provider->refund($providerTxnId, $amount, $reason);

            $this->recordRefund($refundRef, $providerKey, $providerTxnId, $amount, $reason, $result, $approvedBy);
            $this->auditLog('refund_initiated', 'refund', 0, null, [
                'refund_ref'   => $refundRef,
                'provider'     => $providerKey,
                'original_txn' => $providerTxnId,
                'amount'       => $amount,
            ]);

            $result['refund_ref'] = $refundRef;
            return $result;
        }

        /**
         * Get transactions with filters.
         */
        public function getTransactions(array $filters = [], int $limit = 50, int $offset = 0): array {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return [];

            $where  = ['1=1'];
            $params = [];
            $types  = '';

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
                $params[] = (int) $filters['student_id'];
                $types .= 'i';
            }
            if (!empty($filters['payment_for'])) {
                $where[] = 'payment_for = ?';
                $params[] = $filters['payment_for'];
                $types .= 's';
            }
            if (!empty($filters['date_from'])) {
                $where[] = 'created_at >= ?';
                $params[] = $filters['date_from'];
                $types .= 's';
            }
            if (!empty($filters['date_to'])) {
                $where[] = 'created_at <= ?';
                $params[] = $filters['date_to'] . ' 23:59:59';
                $types .= 's';
            }

            $sql = "SELECT * FROM payment_transactions WHERE " . implode(' AND ', $where)
                 . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';

            $stmt = $conn->prepare($sql);
            if (!$stmt) return [];
            if ($types) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            $stmt->close();
            return $rows;
        }

        /**
         * Dashboard statistics.
         */
        public function getStats(?string $dateFrom = null, ?string $dateTo = null): array {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return [];

            $where  = ['1=1'];
            $params = [];
            $types  = '';
            if ($dateFrom) { $where[] = 'created_at >= ?'; $params[] = $dateFrom; $types .= 's'; }
            if ($dateTo)   { $where[] = 'created_at <= ?'; $params[] = $dateTo . ' 23:59:59'; $types .= 's'; }
            $wc = implode(' AND ', $where);

            $sql = "SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status='successful' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status='successful' THEN amount ELSE 0 END), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status='successful' THEN fee_amount ELSE 0 END), 0) as total_fees
            FROM payment_transactions WHERE " . $wc;

            $stmt = $conn->prepare($sql);
            if ($stmt && $types) $stmt->bind_param($types, ...$params);
            if ($stmt) {
                $stmt->execute();
                $stats = $stmt->get_result()->fetch_assoc() ?: [];
                $stmt->close();
            } else {
                $stats = [];
            }

            $stats['by_provider'] = [];
            $sql2 = "SELECT provider_key, COUNT(*) as count,
                COALESCE(SUM(CASE WHEN status='successful' THEN amount ELSE 0 END), 0) as volume
            FROM payment_transactions WHERE " . $wc . " GROUP BY provider_key ORDER BY volume DESC";
            $stmt2 = $conn->prepare($sql2);
            if ($stmt2 && $types) $stmt2->bind_param($types, ...$params);
            if ($stmt2) {
                $stmt2->execute();
                $res = $stmt2->get_result();
                while ($row = $res->fetch_assoc()) $stats['by_provider'][] = $row;
                $stmt2->close();
            }

            return $stats;
        }

        /* ────────────────────────────────────────────────────
           RECEIPT GENERATION
           ──────────────────────────────────────────────────── */

        /**
         * Generate a receipt for a successful transaction.
         */
        public function generateReceipt(int $transactionId): array {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return ['success' => false, 'message' => 'Database unavailable.'];

            $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE id = ? AND status = 'successful' LIMIT 1");
            if (!$stmt) return ['success' => false, 'message' => 'DB error.'];
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            $txn = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$txn) {
                return ['success' => false, 'message' => 'No successful transaction found.'];
            }

            $receiptNumber = $this->generateReceiptNumber();
            $receiptData   = [
                'receipt_number'  => $receiptNumber,
                'transaction_id'  => $txn['id'],
                'receipt_type'    => 'payment',
                'student_name'    => $txn['payer_name'] ?? '',
                'student_number'  => $txn['student_id'] ? (string) $txn['student_id'] : '',
                'amount'          => $txn['amount'],
                'currency'        => $txn['currency'],
                'payment_method'  => $txn['provider_key'],
                'payment_date'    => $txn['completed_at'] ?? $txn['created_at'],
                'description'     => $txn['description'] ?? 'Fee Payment',
            ];

            $ins = $conn->prepare("INSERT INTO payment_receipts
                (receipt_number, transaction_id, receipt_type, student_name, student_number,
                 amount, currency, payment_method, payment_date, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$ins) return ['success' => false, 'message' => 'DB error.'];
            $ins->bind_param('sissssdsss',
                $receiptData['receipt_number'], $receiptData['transaction_id'],
                $receiptData['receipt_type'], $receiptData['student_name'],
                $receiptData['student_number'], $receiptData['amount'],
                $receiptData['currency'], $receiptData['payment_method'],
                $receiptData['payment_date'], $receiptData['description']
            );
            $ins->execute();
            $receiptData['id'] = $conn->insert_id;
            $ins->close();

            $this->auditLog('receipt_generated', 'receipt', $receiptData['id'], null, $receiptData);

            return ['success' => true, 'receipt' => $receiptData];
        }

        /**
         * Get a receipt by number.
         */
        public function getReceipt(string $receiptNumber): ?array {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return null;
            $stmt = $conn->prepare("SELECT r.*, t.transaction_ref, t.provider_key, t.status as txn_status
                FROM payment_receipts r
                JOIN payment_transactions t ON t.id = r.transaction_id
                WHERE r.receipt_number = ? LIMIT 1");
            if (!$stmt) return null;
            $stmt->bind_param('s', $receiptNumber);
            $stmt->execute();
            $receipt = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $receipt ?: null;
        }

        /* ────────────────────────────────────────────────────
           NOTIFICATION DISPATCH
           ──────────────────────────────────────────────────── */

        /**
         * Send payment notification (email / SMS placeholder).
         * In production, hook into your mailer / SMS service here.
         */
        public function sendNotification(int $transactionId, string $type = 'email'): bool {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return false;

            $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE id = ? LIMIT 1");
            if (!$stmt) return false;
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            $txn = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$txn) return false;

            $toEmail = $txn['payer_email'] ?? '';
            $toPhone = $txn['payer_phone'] ?? '';
            $amount  = number_format((float) $txn['amount']);
            $status  = $txn['status'];
            $ref     = $txn['transaction_ref'];

            if ($type === 'email' && !empty($toEmail)) {
                $subject = "ISNM Payment {$status} — Ref: {$ref}";
                $body    = "Dear {$txn['payer_name']},\n\n"
                         . "Your payment of UGX {$amount} (Ref: {$ref}) has been {$status}.\n"
                         . "Provider: {$txn['provider_key']}\n"
                         . "Date: {$txn['created_at']}\n\n"
                         . "Thank you.\nIganga School of Nursing and Midwifery";

                $headers = "From: finance@isnm.ac.ug\r\nContent-Type: text/plain; charset=UTF-8";

                $mailResult = @mail($toEmail, $subject, $body, $headers);
                $this->auditLog('notification_sent', 'transaction', $transactionId, null, [
                    'type' => 'email', 'to' => $toEmail, 'result' => $mailResult,
                ]);
                return $mailResult;
            }

            if ($type === 'sms' && !empty($toPhone)) {
                // Placeholder: Integrate with your SMS gateway here
                $this->auditLog('notification_sms_placeholder', 'transaction', $transactionId, null, [
                    'type' => 'sms', 'to' => $toPhone,
                ]);
                return true;
            }

            return false;
        }

        /* ────────────────────────────────────────────────────
           INTERNAL HELPERS
           ──────────────────────────────────────────────────── */

        private function recordTransaction(string $reference, string $providerKey, array $data, array $result): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("INSERT INTO payment_transactions
                (transaction_ref, provider_key, provider_transaction_id, transaction_type, payment_for,
                 student_id, staff_id, applicant_id, payer_name, payer_phone, payer_email,
                 amount, currency, fee_amount, status, status_reason, description, metadata,
                 ip_address, user_agent, idempotency_key)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) return;

            $txnType    = $data['transaction_type'] ?? 'payment';
            $payFor     = $data['payment_for'] ?? 'tuition';
            $stuId      = (int) ($data['student_id'] ?? 0);
            $staffId    = (int) ($data['staff_id'] ?? 0);
            $appId      = (int) ($data['applicant_id'] ?? 0);
            $payerName  = $data['payer_name'] ?? $data['name'] ?? '';
            $payerPhone = $data['phone'] ?? $data['payer_phone'] ?? '';
            $payerEmail = $data['payer_email'] ?? $data['email'] ?? '';
            $amount     = (float) ($data['amount'] ?? 0);
            $currency   = $data['currency'] ?? 'UGX';
            $fee        = (float) ($data['fee_amount'] ?? 0);
            $status     = $result['status'] ?? 'pending';
            $reason     = $result['message'] ?? '';
            $desc       = $data['description'] ?? '';
            $meta       = json_encode($data['metadata'] ?? $data);
            $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $idempKey   = $data['idempotency_key'] ?? null;

            $provTxn = $result['provider_ref'] ?? null;

            $stmt->bind_param('ssssiiissssddssssssss',
                $reference, $providerKey, $provTxn, $txnType, $payFor,
                $stuId, $staffId, $appId, $payerName, $payerPhone, $payerEmail,
                $amount, $currency, $fee, $status, $reason, $desc, $meta,
                $ip, $ua, $idempKey
            );
            $stmt->execute();
            $stmt->close();
        }

        private function updateTransactionStatus(string $reference, string $status, ?string $providerTxnId = null): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn || empty($reference)) return;

            $completedAt = ($status === 'successful') ? date('Y-m-d H:i:s') : null;

            if ($providerTxnId) {
                $stmt = $conn->prepare("UPDATE payment_transactions
                    SET status = ?,
                        provider_transaction_id = IFNULL(provider_transaction_id, ?),
                        completed_at = IF(? = 'successful', NOW(), completed_at),
                        verified_at = IF(? = 'successful', NOW(), verified_at),
                        updated_at = NOW()
                    WHERE transaction_ref = ?");
                $stmt->bind_param('sssss', $status, $providerTxnId, $status, $status, $reference);
            } else {
                $stmt = $conn->prepare("UPDATE payment_transactions
                    SET status = ?,
                        completed_at = IF(? = 'successful', NOW(), completed_at),
                        verified_at = IF(? = 'successful', NOW(), verified_at),
                        updated_at = NOW()
                    WHERE transaction_ref = ?");
                $stmt->bind_param('ssss', $status, $status, $status, $reference);
            }
            $stmt->execute();
            $stmt->close();

            if ($status === 'successful') {
                $this->updateProviderStats($reference);
            }
        }

        private function updateTransactionStatusByProviderRef(string $providerTxnId, string $status): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("UPDATE payment_transactions
                SET status = ?,
                    completed_at = IF(? = 'successful', NOW(), completed_at),
                    verified_at = IF(? = 'successful', NOW(), verified_at),
                    verification_attempts = verification_attempts + 1,
                    last_verification_at = NOW(),
                    updated_at = NOW()
                WHERE provider_transaction_id = ? AND status NOT IN ('successful','refunded')");
            $stmt->bind_param('ssss', $status, $status, $status, $providerTxnId);
            $stmt->execute();
            $stmt->close();
        }

        private function updateProviderStats(string $reference): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;
            $stmt = $conn->prepare("UPDATE payment_providers p
                INNER JOIN payment_transactions t ON t.provider_key = p.provider_key
                SET p.total_transactions = p.total_transactions + 1,
                    p.total_volume = p.total_volume + t.amount,
                    p.last_transaction_at = NOW()
                WHERE t.transaction_ref = ?");
            if (!$stmt) return;
            $stmt->bind_param('s', $reference);
            $stmt->execute();
            $stmt->close();
        }

        private function recordRefund(string $refundRef, string $providerKey, string $origTxnRef, float $amount, string $reason, array $result, int $approvedBy): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $origId = 0;
            $stmt0 = $conn->prepare("SELECT id FROM payment_transactions WHERE transaction_ref = ? OR provider_transaction_id = ? LIMIT 1");
            if ($stmt0) {
                $stmt0->bind_param('ss', $origTxnRef, $origTxnRef);
                $stmt0->execute();
                $r0 = $stmt0->get_result()->fetch_assoc();
                if ($r0) $origId = (int) $r0['id'];
                $stmt0->close();
            }

            $status = $result['success'] ? 'pending' : 'failed';

            $stmt = $conn->prepare("INSERT INTO payment_refunds
                (refund_ref, original_transaction_id, provider_key, provider_refund_id,
                 amount, reason, status, approved_by, approved_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) return;

            $provRefundId = $result['refund_id'] ?? null;
            $approvedAt   = $approvedBy > 0 ? date('Y-m-d H:i:s') : null;

            $stmt->bind_param('sissssss',
                $refundRef, $origId, $providerKey, $provRefundId,
                $amount, $reason, $status, $approvedBy, $approvedAt
            );
            $stmt->execute();
            $stmt->close();
        }

        /* ── Logging helpers ──────────────────────────────── */

        private function logCallbackRaw(string $providerKey, string $type, array $payload, array $headers): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("INSERT INTO payment_callbacks
                (provider_key, callback_type, request_method, request_headers, request_body, request_ip)
                VALUES (?, ?, 'POST', ?, ?, ?)");
            if (!$stmt) return;

            $hdrs = json_encode($headers);
            $body = json_encode($payload);
            $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt->bind_param('ssss', $providerKey, $type, $hdrs, $body, $ip);
            $stmt->execute();
            $stmt->close();
        }

        private function logWebhook(string $providerKey, array $payload, array $headers, string $signature, bool $sigValid): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("INSERT INTO payment_webhook_logs
                (provider_key, event_type, payload, headers, signature, signature_valid, processing_status)
                VALUES (?, ?, ?, ?, ?, ?, 'received')");
            if (!$stmt) return;

            $eventType = $payload['event_type'] ?? $payload['type'] ?? 'unknown';
            $pl    = json_encode($payload);
            $hdrs  = json_encode($headers);
            $sigV = $sigValid ? 1 : 0;

            $stmt->bind_param('sssssi', $providerKey, $eventType, $pl, $hdrs, $signature, $sigV);
            $stmt->execute();
            $stmt->close();
        }

        private function updateWebhookLogStatus(string $providerKey, string $status, ?string $error = null, ?int $elapsedMs = null): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("UPDATE payment_webhook_logs
                SET processing_status = ?, error_message = ?, processing_time_ms = ?
                WHERE id = (SELECT id FROM (SELECT id FROM payment_webhook_logs WHERE provider_key = ? ORDER BY id DESC LIMIT 1) as tmp)");
            if (!$stmt) return;
            $stmt->bind_param('ssis', $status, $error, $elapsedMs, $providerKey);
            $stmt->execute();
            $stmt->close();
        }

        private function generateReceiptNumber(): string {
            $prefix = 'ISNM';
            $conn   = PaymentGatewayDB::getConnection();
            $next   = 10001;
            if ($conn) {
                $r = $conn->query("SELECT setting_value FROM payment_gateway_settings WHERE setting_key = 'receipt_starting_number' LIMIT 1");
                if ($r && $row = $r->fetch_assoc()) $next = (int) $row['setting_value'];
                $r2 = $conn->query("SELECT MAX(CAST(SUBSTRING(receipt_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)) as mx FROM payment_receipts WHERE receipt_number LIKE '" . $prefix . "-%'");
                $row2 = $r2->fetch_assoc();
                if ($r2 && !empty($row2['mx'])) {
                    $next = (int) $row2['mx'] + 1;
                }
            }
            return $prefix . '-' . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
        }

        public function auditLog(string $action, ?string $entityType = null, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): void {
            $conn = PaymentGatewayDB::getConnection();
            if (!$conn) return;

            $stmt = $conn->prepare("INSERT INTO payment_audit_log
                (user_id, user_type, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) return;

            $userId   = (int) ($_SESSION['user_id'] ?? 0);
            $userType = !empty($_SESSION['user_id']) ? 'staff' : 'system';
            $oldJson  = $oldValues ? json_encode($oldValues) : null;
            $newJson  = $newValues ? json_encode($newValues) : null;
            $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt->bind_param('isssissss',
                $userId, $userType, $action, $entityType, $entityId,
                $oldJson, $newJson, $ip, $ua
            );
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* ─────────────────────────────────────────────────────────────
   GLOBAL HELPER FUNCTIONS
   ───────────────────────────────────────────────────────────── */

if (!function_exists('initiateGatewayPayment')) {
    function initiateGatewayPayment(string $provider, array $data): array {
        return PaymentGateway::getInstance()->initiatePayment($provider, $data);
    }
}

if (!function_exists('verifyGatewayPayment')) {
    function verifyGatewayPayment(string $provider, string $txnRef): array {
        return PaymentGateway::getInstance()->verifyPayment($provider, $txnRef);
    }
}

if (!function_exists('getAvailablePaymentProviders')) {
    function getAvailablePaymentProviders(): array {
        return PaymentGateway::getInstance()->getAvailableProviders();
    }
}

if (!function_exists('generatePaymentReference')) {
    function generatePaymentReference(string $type = 'payment'): string {
        return PaymentGateway::getInstance()->generateReference($type);
    }
}

if (!function_exists('checkFeeClearance')) {
    function checkFeeClearance($stuConn, int $studentId): array {
        if (!$stuConn) return ['cleared' => true, 'balance' => 0];
        $r = $stuConn->query("SELECT COALESCE(SUM(balance),0) as bal FROM student_fees WHERE student_id=" . (int) $studentId);
        if (!$r) return ['cleared' => true, 'balance' => 0];
        $bal = (float) $r->fetch_assoc()['bal'];
        return ['cleared' => $bal <= 0, 'balance' => $bal];
    }
}

if (!function_exists('handleMobileMoneyCallback')) {
    function handleMobileMoneyCallback(): array {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) return ['success' => false, 'message' => 'Invalid callback data'];
        $ref = $input['externalId'] ?? $input['reference'] ?? '';
        if (!$ref) return ['success' => false, 'message' => 'No reference in callback'];

        $gw = PaymentGateway::getInstance();
        $provider = 'unknown';
        if (isset($input['transactionId']))    $provider = 'mtn_momo';
        if (isset($input['transaction']['id'])) $provider = 'airtel_money';

        return $gw->processCallback($provider, $input);
    }
}
