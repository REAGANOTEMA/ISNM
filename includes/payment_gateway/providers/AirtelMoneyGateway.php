<?php
/**
 * Airtel Money Payment Provider
 * Supports collection via Airtel Money API.
 * Sandbox mode when credentials are not configured.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class AirtelMoneyGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'airtel_money';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'Airtel Money';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && !empty($this->getApiSecret()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    private function getAccessToken(): ?string {
        if (!$this->isLive()) return null;
        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/auth/oauth2/token', [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], http_build_query([
            'client_id' => $this->getApiKey(),
            'client_secret' => $this->getApiSecret(),
            'grant_type' => 'client_credentials',
        ]));
        return $result['data']['access_token'] ?? null;
    }

    public function initiatePayment(array $params): array {
        $phone = preg_replace('/[^0-9+]/', '', $params['phone'] ?? '');
        $amount = (float)($params['amount'] ?? 0);
        $ref = $params['transaction_ref'] ?? $this->generateRef('AIR');

        if ($amount < ($this->config['min_amount'] ?? 100)) {
            return ['success' => false, 'message' => 'Amount below minimum'];
        }
        if (empty($phone)) {
            return ['success' => false, 'message' => 'Phone number required'];
        }

        if (!$this->isLive()) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => 'AIR-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => null,
                'message' => 'Sandbox mode: payment request created.',
                'sandbox' => true,
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to authenticate with Airtel API'];
        }

        $body = [
            'transaction' => [
                'amount' => $amount,
                'country' => 'UG',
                'currency' => 'UGX',
                'externalId' => $ref,
            ],
            'party' => [
                'msisdn' => $phone,
            ],
            'payee' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
            'paymentOption' => ['type' => 'MOBILE_MONEY'],
            'reference' => $params['description'] ?? 'ISNM Payment',
        ];

        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/pay/v1/platform', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ], $body);

        if ($result['success']) {
            $providerRef = $result['data']['data']['transaction']['id'] ?? '';
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $providerRef,
                'redirect_url' => null,
                'message' => 'Payment request sent. Approve on your phone.',
            ];
        }

        return ['success' => false, 'message' => 'Payment request failed. Please try again.'];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        if (!$this->isLive()) {
            return ['success' => true, 'status' => 'successful', 'message' => 'Sandbox: assumed successful'];
        }

        $token = $this->getAccessToken();
        if (!$token) return ['success' => false, 'message' => 'Authentication failed'];

        $result = $this->makeHttpRequest('GET', $this->getApiUrl() . "/transaction/v3/transaction/$providerTransactionId", [
            'Authorization: Bearer ' . $token,
        ]);

        if ($result['success']) {
            $status = $result['data']['data']['transaction']['status'] ?? 'UNKNOWN';
            $mapped = strtolower($status) === 'successful' ? 'successful' : (strtolower($status) === 'failed' ? 'failed' : 'processing');
            return ['success' => true, 'status' => $mapped, 'message' => $status];
        }

        return ['success' => false, 'message' => 'Status check failed'];
    }

    public function processWebhook(array $payload): array {
        $status = $payload['transaction']['status'] ?? $payload['status'] ?? '';
        $externalId = $payload['transaction']['externalId'] ?? $payload['externalId'] ?? '';
        $providerRef = $payload['transaction']['id'] ?? $payload['transactionId'] ?? '';

        $mappedStatus = strtolower($status) === 'successful' ? 'successful' : 'failed';

        return [
            'success' => true,
            'transaction_ref' => $externalId,
            'provider_ref' => $providerRef,
            'status' => $mappedStatus,
            'message' => "Airtel callback: $status",
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        $secret = $this->getWebhookSecret();
        if (empty($secret)) return true;
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        if (!$this->isLive()) {
            return ['success' => true, 'refund_id' => 'AIR-RF-SANDBOX-' . bin2hex(random_bytes(4)), 'message' => 'Sandbox refund created'];
        }
        return ['success' => false, 'message' => 'Airtel refund requires manual provider integration'];
    }
}
