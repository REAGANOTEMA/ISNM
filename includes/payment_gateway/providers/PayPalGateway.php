<?php
/**
 * PayPal Payment Provider (Orders API v2)
 * Sandbox mode when credentials are not configured.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class PayPalGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'paypal';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'PayPal';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && !empty($this->getApiSecret()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    private function getAccessToken(): ?string {
        if (!$this->isLive()) return null;
        $url = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $result = $this->makeHttpRequest('POST', $url . '/v1/oauth2/token', [
            'Authorization: Basic ' . base64_encode($this->getApiKey() . ':' . $this->getApiSecret()),
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query(['grant_type' => 'client_credentials']));
        return $result['data']['access_token'] ?? null;
    }

    public function initiatePayment(array $params): array {
        $ref = $params['transaction_ref'] ?? $this->generateRef('PAY');
        $amount = (float)($params['amount'] ?? 0);
        $currency = strtoupper($params['currency'] ?? 'USD');
        $description = $params['description'] ?? 'ISNM Payment';

        if (!$this->isLive()) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => 'PAY-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => '#sandbox-paypal',
                'message' => 'Sandbox mode: PayPal payment created.',
                'sandbox' => true,
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) return ['success' => false, 'message' => 'PayPal authentication failed'];

        $returnUrl = $params['return_url'] ?? $this->getCallbackUrl();
        $cancelUrl = $params['cancel_url'] ?? $returnUrl;

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => substr($ref, 0, 127),
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => $description,
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'Iganga School of Nursing and Midwifery',
                'locale' => 'en-US',
            ],
        ];

        $url = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $result = $this->makeHttpRequest('POST', $url . '/v2/checkout/orders', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ], $payload);

        if (($result['http_code'] === 200 || $result['http_code'] === 201) && $result['data']) {
            $approveUrl = '';
            foreach ($result['data']['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approveUrl = $link['href'] ?? '';
                    break;
                }
            }
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $result['data']['id'] ?? '',
                'redirect_url' => $approveUrl,
                'message' => 'Redirect to PayPal to complete payment.',
            ];
        }

        return ['success' => false, 'message' => 'PayPal gateway error.'];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        if (!$this->isLive()) {
            return ['success' => true, 'status' => 'successful', 'message' => 'Sandbox: status check simulated.'];
        }

        $token = $this->getAccessToken();
        if (!$token) return ['success' => false, 'status' => 'unknown', 'message' => 'Auth failed.'];

        $url = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $result = $this->makeHttpRequest('GET', $url . '/v2/checkout/orders/' . $providerTransactionId, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $raw = $result['data']['status'] ?? 'unknown';
            $mapped = match ($raw) {
                'COMPLETED' => 'successful',
                'VOIDED' => 'cancelled',
                'PENDING', 'APPROVED' => 'processing',
                default => 'processing',
            };
            return [
                'success' => $mapped === 'successful',
                'status' => $mapped,
                'provider_status' => $raw,
                'message' => 'PayPal status: ' . $raw,
            ];
        }
        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify PayPal transaction.'];
    }

    public function processWebhook(array $payload): array {
        $txnId = $payload['resource']['id'] ?? $payload['id'] ?? '';
        $eventType = $payload['event_type'] ?? '';
        $status = 'processing';

        if (str_contains($eventType, 'CAPTURE.COMPLETED')) {
            $status = 'successful';
        } elseif (str_contains($eventType, 'CAPTURE.DENIED') || str_contains($eventType, 'VOIDED')) {
            $status = 'failed';
        }

        return [
            'success' => true,
            'transaction_ref' => $payload['resource']['custom_id'] ?? $txnId,
            'provider_transaction_id' => $txnId,
            'status' => $status,
            'message' => 'PayPal webhook processed: ' . $eventType,
        ];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        if (!$this->isLive()) return true;
        $webhookSecret = $this->getWebhookSecret();
        if (empty($webhookSecret)) return true;
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $signature);
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        if (!$this->isLive()) {
            return ['success' => true, 'refund_id' => 'REF-SANDBOX-' . bin2hex(random_bytes(4)), 'message' => 'Sandbox: refund simulated.'];
        }
        return ['success' => false, 'message' => 'PayPal refunds require manual processing via PayPal dashboard.'];
    }
}
