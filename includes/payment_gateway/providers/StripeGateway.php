<?php
/**
 * Stripe Payment Provider
 * Supports card payments and Stripe Checkout sessions.
 * Sandbox mode when API keys are not configured.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class StripeGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'stripe';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'Stripe';
    }

    private function isLive(): bool {
        return !empty($this->getApiKey()) && ($this->config['status'] ?? 'sandbox') === 'active';
    }

    public function initiatePayment(array $params): array {
        $amount = (float)($params['amount'] ?? 0);
        $currency = strtolower($params['currency'] ?? 'usd');
        $ref = $params['transaction_ref'] ?? $this->generateRef('STR');
        $description = $params['description'] ?? 'ISNM School Payment';
        $email = $params['email'] ?? '';
        $successUrl = $params['success_url'] ?? '';
        $cancelUrl = $params['cancel_url'] ?? '';

        if ($amount < 0.5) {
            return ['success' => false, 'message' => 'Amount too small for Stripe'];
        }

        if (!$this->isLive()) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => 'STR-SANDBOX-' . bin2hex(random_bytes(4)),
                'redirect_url' => $successUrl ?: '#sandbox-stripe',
                'message' => 'Sandbox mode: Stripe checkout created.',
                'sandbox' => true,
            ];
        }

        $body = [
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => $successUrl ?: "{$_SERVER['HTTP_HOST']}/payment-complete?ref=$ref&status=success",
            'cancel_url' => $cancelUrl ?: "{$_SERVER['HTTP_HOST']}/payment-complete?ref=$ref&status=cancelled",
            'client_reference_id' => $ref,
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int)($amount * 100),
                    'product_data' => ['name' => $description],
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['transaction_ref' => $ref, 'student_id' => $params['student_id'] ?? ''],
        ];

        if ($email) $body['customer_email'] = $email;

        $result = $this->makeHttpRequest('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query($body, '', '&', PHP_QUERY_RFC3986));

        if ($result['success'] && isset($result['data']['url'])) {
            return [
                'success' => true,
                'transaction_ref' => $ref,
                'provider_ref' => $result['data']['id'] ?? '',
                'redirect_url' => $result['data']['url'],
                'message' => 'Redirecting to Stripe checkout...',
            ];
        }

        return ['success' => false, 'message' => 'Stripe checkout creation failed'];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        if (!$this->isLive()) {
            return ['success' => true, 'status' => 'successful', 'message' => 'Sandbox: assumed successful'];
        }

        $result = $this->makeHttpRequest('GET', "https://api.stripe.com/v1/checkout/sessions/$providerTransactionId", [
            'Authorization: Bearer ' . $this->getApiKey(),
        ]);

        if ($result['success']) {
            $status = $result['data']['payment_status'] ?? '';
            $mapped = $status === 'paid' ? 'successful' : ($status === 'unpaid' ? 'failed' : 'processing');
            return ['success' => true, 'status' => $mapped, 'message' => $status];
        }

        return ['success' => false, 'message' => 'Status check failed'];
    }

    public function processWebhook(array $payload): array {
        $type = $payload['type'] ?? '';
        $data = $payload['data']['object'] ?? [];

        if ($type === 'checkout.session.completed') {
            return [
                'success' => true,
                'transaction_ref' => $data['client_reference_id'] ?? '',
                'provider_ref' => $data['id'] ?? '',
                'status' => 'successful',
                'amount' => ($data['amount_total'] ?? 0) / 100,
                'message' => 'Stripe payment completed',
            ];
        }

        if ($type === 'charge.refunded') {
            return [
                'success' => true,
                'transaction_ref' => $data['metadata']['transaction_ref'] ?? '',
                'provider_ref' => $data['id'] ?? '',
                'status' => 'refunded',
                'message' => 'Stripe refund processed',
            ];
        }

        return ['success' => true, 'status' => 'processing', 'message' => "Unhandled Stripe event: $type"];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        $secret = $this->getWebhookSecret();
        if (empty($secret)) return true;
        $parts = explode(',', $signature);
        $timestamp = '';
        $v1Sig = '';
        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                if ($kv[0] === 't') $timestamp = $kv[1];
                if ($kv[0] === 'v1') $v1Sig = $kv[1];
            }
        }
        $signedPayload = "$timestamp.$rawBody";
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        return hash_equals($expected, $v1Sig);
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        if (!$this->isLive()) {
            return ['success' => true, 'refund_id' => 'STR-RF-SANDBOX-' . bin2hex(random_bytes(4)), 'message' => 'Sandbox refund created'];
        }

        $body = [
            'payment_intent' => $providerTransactionId,
            'amount' => (int)($amount * 100),
            'reason' => 'requested_by_customer',
        ];

        $result = $this->makeHttpRequest('POST', 'https://api.stripe.com/v1/refunds', [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query($body, '', '&', PHP_QUERY_RFC3986));

        if ($result['success']) {
            return [
                'success' => true,
                'refund_id' => $result['data']['id'] ?? '',
                'message' => 'Refund initiated',
            ];
        }

        return ['success' => false, 'message' => 'Refund failed'];
    }

    public function getSupportedPaymentTypes(): array {
        return ['student_fees', 'application', 'admission', 'graduation', 'hostel', 'donation', 'misc'];
    }
}
