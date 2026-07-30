<?php
/**
 * Flutterwave Provider Adapter
 * Supports Cards, Mobile Money, and Bank transfers across Africa
 */
class FlutterwaveProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'flutterwave';
        $this->providerName = 'Flutterwave';
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api.flutterwave.com';

        $payload = [
            'tx_ref' => $reference,
            'amount' => (string)$amount,
            'currency' => $currency,
            'redirect_url' => $this->config['return_url'] ?? ($this->config['callback_url'] ?? ''),
            'customer' => [
                'email' => $payer['email'] ?? '',
                'phonenumber' => $payer['phone'] ?? '',
                'name' => $payer['name'] ?? '',
            ],
            'customizations' => [
                'title' => 'ISNM Fee Payment',
                'description' => $description ?: 'Payment for ISNM',
                'logo' => '',
            ],
        ];

        $result = $this->httpPost($apiUrl . '/v3/payments', $payload, [
            'Authorization: Bearer ' . $this->getApiKey(),
            'Content-Type: application/json',
        ]);

        if ($result['http_code'] === 200 && isset($result['data']['data']['link'])) {
            return [
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $reference,
                'status' => 'pending',
                'payment_url' => $result['data']['data']['link'],
                'message' => 'Redirect to Flutterwave to complete payment.',
            ];
        }

        $this->logError('Initiate payment failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        $errMsg = $result['data']['message'] ?? 'Flutterwave gateway error.';
        return ['success' => false, 'message' => $errMsg];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api.flutterwave.com';
        $result = $this->httpGet($apiUrl . '/v3/transactions/' . $providerTransactionId . '/verify', [
            'Authorization: Bearer ' . $this->getApiKey(),
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $status = $result['data']['data']['status'] ?? 'unknown';
            $mappedStatus = 'processing';
if ($status === 'successful') $mappedStatus = 'successful';
            elseif ($status === 'failed') $mappedStatus = 'failed';
            elseif ($status === 'cancelled') $mappedStatus = 'cancelled';
            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'Transaction status: ' . $status,
                'amount' => $result['data']['data']['amount'] ?? null,
                'currency' => $result['data']['data']['currency'] ?? null,
            ];
        }

        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify Flutterwave transaction.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        $status = $payload['data']['status'] ?? 'unknown';
        $txRef = $payload['data']['tx_ref'] ?? '';
        $providerId = $payload['data']['id'] ?? '';

        $mappedStatus = 'processing';
if ($status === 'successful') $mappedStatus = 'successful';
        elseif ($status === 'failed') $mappedStatus = 'failed';
        elseif ($status === 'cancelled') $mappedStatus = 'cancelled';

        return [
            'success' => true,
            'reference' => $txRef,
            'provider_transaction_id' => (string)$providerId,
            'status' => $mappedStatus,
            'raw_status' => $status,
            'message' => 'Flutterwave callback processed',
        ];
    }
}
