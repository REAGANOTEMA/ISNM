<?php
/**
 * Pesapal Payment Provider
 * Supports mobile money, cards, bank transfer (East Africa).
 * Sandbox mode when credentials are not configured.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class PesapalGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'pesapal';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'Pesapal';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && !empty($this->getApiSecret()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    private function getAccessToken(): ?string {
        if (!$this->isLive()) return null;
        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/api/Auth/RequestToken', [
            'Content-Type: application/json',
        ], [
            'consumer_key' => $this->getApiKey(),
            'consumer_secret' => $this->getApiSecret(),
        ]);
        return $result['data']['token'] ?? null;
    }

    public function initiatePayment(array $params): array {
        $amount = (float)($params['amount'] ?? 0);
        $ref = $params['transaction_ref'] ?? $this->generateRef('PSP');
        $description = $params['description'] ?? 'ISNM Payment';
        $email = $params['email'] ?? '';
        $phone = $params['phone'] ?? '';

        if (!$this->isLive()) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => 'PSP-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => '#sandbox-pesapal',
                'message' => 'Sandbox mode: Pesapal payment created.',
                'sandbox' => true,
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) return ['success' => false, 'message' => 'Pesapal authentication failed'];

        $body = [
            'id' => $ref,
            'currency' => 'UGX',
            'amount' => $amount,
            'description' => $description,
            'callback_url' => $this->getCallbackUrl(),
            'notification_id' => '',
            'billing_address' => [
                'email_address' => $email,
                'phone_number' => $phone,
            ],
        ];

        $result = $this->makeHttpRequest('POST', $this->getApiUrl() . '/api/Transactions/SubmitOrderRequest', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ], $body);

        if ($result['success'] && isset($result['data']['redirect_url'])) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $result['data']['order_tracking_id'] ?? '',
                'redirect_url' => $result['data']['redirect_url'],
                'message' => 'Redirecting to Pesapal checkout...',
            ];
        }

        return ['success' => false, 'message' => 'Pesapal payment failed'];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        if (!$this->isLive()) {
            return ['success' => true, 'status' => 'successful', 'message' => 'Sandbox: assumed successful'];
        }
        return ['success' => false, 'message' => 'Pesapal status check via callback only'];
    }

    public function processWebhook(array $payload): array {
        $status = $payload['OrderStatus'] ?? '';
        $orderRef = $payload['OrderTrackingId'] ?? $payload['order_tracking_id'] ?? '';

        $mappedStatus = strtolower($status) === 'completed' ? 'successful' : 'failed';

        return [
            'success' => true,
            'transaction_ref' => $payload['id'] ?? $payload['merchant_reference'] ?? '',
            'provider_ref' => $orderRef,
            'status' => $mappedStatus,
            'message' => "Pesapal callback: $status",
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        return true;
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        return ['success' => false, 'message' => 'Pesapal refund requires manual processing'];
    }
}
