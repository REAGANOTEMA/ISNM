<?php
/**
 * Stripe Provider Adapter
 * Supports international card payments (Visa, Mastercard, AMEX)
 */
class StripeProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'stripe';
        $this->providerName = 'Stripe';
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api.stripe.com/v1';

        $payload = [
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][product_data][name]' => 'ISNM Payment - ' . $reference,
            'line_items[0][price_data][unit_amount]' => (int)($amount * 100),
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => $this->config['return_url'] ?? '',
            'cancel_url' => $this->config['return_url'] ?? '',
            'client_reference_id' => $reference,
            'customer_email' => $payer['email'] ?? '',
            'metadata[reference]' => $reference,
            'metadata[school]' => 'ISNM',
        ];

        $result = $this->httpPost($apiUrl . '/checkout/sessions', $payload, [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            return [
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $result['data']['id'] ?? $reference,
                'status' => 'pending',
                'payment_url' => $result['data']['url'] ?? '',
                'message' => 'Redirect to Stripe to complete payment.',
            ];
        }

        $this->logError('Initiate payment failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        $errMsg = $result['data']['error']['message'] ?? 'Stripe gateway error.';
        return ['success' => false, 'message' => $errMsg];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api.stripe.com/v1';
        $result = $this->httpGet($apiUrl . '/checkout/sessions/' . $providerTransactionId, [
            'Authorization: Bearer ' . $this->getApiKey(),
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $status = $result['data']['payment_status'] ?? 'unknown';
            $mappedStatus = match($status) {
                'paid' => 'successful',
                'unpaid' => 'failed',
                'no_payment_required' => 'successful',
                default => 'processing',
            };
            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'Transaction status: ' . $status,
                'amount' => ($result['data']['amount_total'] ?? 0) / 100,
                'currency' => strtoupper($result['data']['currency'] ?? ''),
            ];
        }

        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify Stripe transaction.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        $type = $payload['type'] ?? '';
        $status = $payload['data']['object']['payment_status'] ?? 'unknown';
        $sessionId = $payload['data']['object']['id'] ?? '';
        $reference = $payload['data']['object']['client_reference_id'] ?? $sessionId;

        if ($type === 'checkout.session.completed') {
            $mappedStatus = 'successful';
        } elseif ($type === 'checkout.session.expired') {
            $mappedStatus = 'expired';
        } else {
            $mappedStatus = 'processing';
        }

        return [
            'success' => true,
            'reference' => $reference,
            'provider_transaction_id' => $sessionId,
            'status' => $mappedStatus,
            'raw_status' => $status,
            'message' => 'Stripe webhook processed: ' . $type,
        ];
    }
}
