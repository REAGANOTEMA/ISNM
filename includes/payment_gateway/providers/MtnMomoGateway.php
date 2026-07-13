<?php
/**
 * MTN Mobile Money (MoMo) Payment Provider
 * Supports collection (customer pays merchant) via MTN API.
 * When credentials are not yet available, operates in sandbox/mock mode.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class MtnMomoGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'mtn_momo';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'MTN Mobile Money';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && !empty($this->getApiSecret()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    private function getAccessToken(): ?string {
        if (!$this->isLive()) return null;
        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/collection/token/', [
            'Authorization: Basic ' . base64_encode($this->getApiKey() . ':' . $this->getApiSecret()),
            'Ocp-Apim-Subscription-Key: ' . $this->getApiKey(),
        ]);
        return $result['data']['access_token'] ?? null;
    }

    public function initiatePayment(array $params): array {
        $phone = preg_replace('/[^0-9+]/', '', $params['phone'] ?? '');
        $amount = (float)($params['amount'] ?? 0);
        $currency = $params['currency'] ?? 'UGX';
        $ref = $params['transaction_ref'] ?? $this->generateRef('MTN');

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
                'provider_ref' => 'MTN-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => null,
                'message' => 'Sandbox mode: payment request created. Configure API credentials for live mode.',
                'sandbox' => true,
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to authenticate with MTN API'];
        }

        $body = [
            'amount' => (string)$amount,
            'currency' => $currency,
            'externalId' => $ref,
            'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
            'payerMessage' => $params['description'] ?? 'School payment',
            'payeeNote' => 'ISNM Payment',
        ];

        $apiRef = bin2hex(random_bytes(16));
        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/collection/v1_0/requesttopay', [
            'Authorization: Bearer ' . $token,
            'X-Reference-Id: ' . $apiRef,
            'X-Target-Environment: ' . ($this->config['sandbox'] ? 'sandbox' : 'production'),
            'Ocp-Apim-Subscription-Key: ' . $this->getApiKey(),
            'Content-Type: application/json',
        ], $body);

        if ($result['success']) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $apiRef,
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

        $result = $this->makeHttpRequest('GET', $this->getApiUrl() . "/collection/v1_0/requesttopay/$providerTransactionId", [
            'Authorization: Bearer ' . $token,
            'X-Target-Environment: ' . ($this->config['sandbox'] ? 'sandbox' : 'production'),
            'Ocp-Apim-Subscription-Key: ' . $this->getApiKey(),
        ]);

        if ($result['success']) {
            $status = $result['data']['status'] ?? 'UNKNOWN';
            $mapped = strtoupper($status) === 'SUCCESSFUL' ? 'successful' : (strtoupper($status) === 'FAILED' ? 'failed' : 'processing');
            return ['success' => true, 'status' => $mapped, 'message' => $status];
        }

        return ['success' => false, 'message' => 'Status check failed'];
    }

    public function processWebhook(array $payload): array {
        $status = $payload['status'] ?? '';
        $externalId = $payload['externalId'] ?? '';
        $providerRef = $payload['financialTransactionId'] ?? $payload['transactionId'] ?? '';
        $amount = (float)($payload['amount'] ?? 0);

        $mappedStatus = strtolower($status) === 'successful' ? 'successful' : 'failed';

        return [
            'success' => true,
            'transaction_ref' => $externalId,
            'provider_ref' => $providerRef,
            'status' => $mappedStatus,
            'amount' => $amount,
            'message' => "MTN callback: $status",
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
            return ['success' => true, 'refund_id' => 'MTN-RF-SANDBOX-' . bin2hex(random_bytes(4)), 'message' => 'Sandbox refund created'];
        }
        return ['success' => false, 'message' => 'MTN MoMo refund requires manual provider integration'];
    }
}
