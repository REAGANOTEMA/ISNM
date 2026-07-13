<?php
/**
 * Flutterwave Payment Provider
 * Supports cards, mobile money, bank transfers, USSD.
 * Sandbox mode when API keys are not configured.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class FlutterwaveGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'flutterwave';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'Flutterwave';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    public function initiatePayment(array $params): array {
        $amount = (float)($params['amount'] ?? 0);
        $currency = strtoupper($params['currency'] ?? 'UGX');
        $ref = $params['transaction_ref'] ?? $this->generateRef('FLW');
        $email = $params['email'] ?? '';
        $phone = $params['phone'] ?? '';
        $redirectUrl = $params['success_url'] ?? '';
        $title = $params['description'] ?? 'ISNM Payment';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount'];
        }

        if (!$this->isLive()) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => 'FLW-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => $redirectUrl ?: '#sandbox-flw',
                'message' => 'Sandbox mode: Flutterwave payment created.',
                'sandbox' => true,
            ];
        }

        $body = [
            'tx_ref' => $ref,
            'amount' => (string)$amount,
            'currency' => $currency,
            'redirect_url' => $redirectUrl ?: "{$_SERVER['HTTP_HOST']}/payment-complete?ref=$ref",
            'payment_options' => 'card,mobilemoney,ussd,banktransfer',
            'customer' => [
                'email' => $email ?: 'student@isnm.ac.ug',
                'phonenumber' => $phone,
                'name' => $params['payer_name'] ?? 'ISNM Student',
            ],
            'customizations' => [
                'title' => 'ISNM School Payment',
                'description' => $title,
                'logo' => '',
            ],
            'meta' => [['transaction_ref' => $ref]],
        ];

        $result = $this->makeHttpRequest('POST', 'https://api.flutterwave.com/v3/payments', [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/json',
        ], $body);

        if ($result['success'] && isset($result['data']['data']['link'])) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $result['data']['data']['tx_ref'] ?? '',
                'redirect_url' => $result['data']['data']['link'],
                'message' => 'Redirecting to Flutterwave checkout...',
            ];
        }

        return ['success' => false, 'message' => 'Payment initialization failed'];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        if (!$this->isLive()) {
            return ['success' => true, 'status' => 'successful', 'message' => 'Sandbox: assumed successful'];
        }

        $result = $this->makeHttpRequest('GET', "https://api.flutterwave.com/v3/transactions/$providerTransactionId/verify", [
            'Authorization: Bearer ' . $this->getApiKey(),
        ]);

        if ($result['success']) {
            $status = $result['data']['data']['status'] ?? 'UNKNOWN';
            $mapped = strtolower($status) === 'successful' ? 'successful' : (strtolower($status) === 'failed' ? 'failed' : 'processing');
            return ['success' => true, 'status' => $mapped, 'message' => $status];
        }

        return ['success' => false, 'message' => 'Status check failed'];
    }

    public function processWebhook(array $payload): array {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        if ($event === 'charge.completed') {
            $status = $data['status'] ?? '';
            return [
                'success' => true,
                'transaction_ref' => $data['tx_ref'] ?? '',
                'provider_ref' => $data['id'] ?? '',
                'status' => strtolower($status) === 'successful' ? 'successful' : 'failed',
                'amount' => (float)($data['amount'] ?? 0),
                'message' => "Flutterwave: $event ($status)",
            ];
        }

        return ['success' => true, 'status' => 'processing', 'message' => "Unhandled Flutterwave event: $event"];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        $secret = $this->getWebhookSecret();
        if (empty($secret)) return true;
        $expected = hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        if (!$this->isLive()) {
            return ['success' => true, 'refund_id' => 'FLW-RF-SANDBOX-' . bin2hex(random_bytes(4)), 'message' => 'Sandbox refund created'];
        }

        $result = $this->makeHttpRequest('POST', 'https://api.flutterwave.com/v3/transfers', [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/json',
        ], [
            'account_bank' => '',
            'account_number' => '',
            'amount' => $amount,
            'currency' => 'UGX',
            'reference' => $this->generateRef('FLWRF'),
            'reason' => $reason ?: 'Refund',
        ]);

        if ($result['success']) {
            return ['success' => true, 'refund_id' => $result['data']['data']['id'] ?? '', 'message' => 'Refund initiated'];
        }

        return ['success' => false, 'message' => 'Refund failed'];
    }

    public function getSupportedPaymentTypes(): array {
        return ['student_fees', 'application', 'admission', 'graduation', 'hostel', 'library_fine', 'donation', 'misc'];
    }
}
