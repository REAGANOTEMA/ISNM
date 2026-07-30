<?php
/**
 * PesaPal Provider Adapter
 * Supports M-Pesa, Airtel Money, and Card payments in East Africa
 */
class PesaPalProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'pesapal';
        $this->providerName = 'PesaPal';
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';

        $payload = [
            'id' => $reference,
            'currency' => $currency,
            'amount' => $amount,
            'description' => $description ?: 'ISNM Fee Payment - ' . $reference,
            'callback_url' => $this->config['callback_url'] ?? '',
            'billing_address' => [
                'email_address' => $payer['email'] ?? '',
                'phone_number' => $payer['phone'] ?? '',
                'first_name' => $payer['first_name'] ?? $payer['name'] ?? '',
                'last_name' => $payer['last_name'] ?? '',
                'country_code' => 'UG',
            ],
        ];

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to authenticate with PesaPal.'];
        }

        $result = $this->httpPost($apiUrl . '/Orders', $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $redirectUrl = $result['data']['redirect_url'] ?? $result['data']['order_tracking_id'] ?? '';
            return [
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $result['data']['order_tracking_id'] ?? $reference,
                'status' => 'pending',
                'payment_url' => $redirectUrl,
                'message' => 'Redirect to PesaPal to complete payment.',
            ];
        }

        $this->logError('Initiate payment failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        return ['success' => false, 'message' => 'PesaPal gateway unavailable.'];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'status' => 'unknown', 'message' => 'Failed to authenticate.'];
        }

        $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';
        $result = $this->httpGet($apiUrl . '/Transactions/' . $providerTransactionId, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $status = $result['data']['payment_status'] ?? 'unknown';
$mappedStatus = 'processing';
if (in_array($status, ['Completed', 'SUCCESS'])) $mappedStatus = 'successful';
            elseif (in_array($status, ['Failed', 'FAILED'])) $mappedStatus = 'failed';
            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'Transaction status: ' . $status,
            ];
        }
        
        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify PesaPal transaction.'];
    }
    
    public function processCallback(array $payload, array $headers = []): array {
        $status = $payload['payment_status'] ?? $payload['status'] ?? 'unknown';
        $orderRef = $payload['order_tracking_id'] ?? $payload['order_merchant_reference'] ?? '';
        
        $mappedStatus = 'processing';
if (in_array($status, ['Completed', 'SUCCESS'])) $mappedStatus = 'successful';
            elseif (in_array($status, ['Failed', 'FAILED'])) $mappedStatus = 'failed';

        return [
            'success' => true,
            'reference' => $orderRef,
            'provider_transaction_id' => $orderRef,
            'status' => $mappedStatus,
            'raw_status' => $status,
            'message' => 'PesaPal callback processed',
        ];
    }

    private function getAccessToken(): ?string {
        $apiUrl = $this->getApiUrl() ?: 'https://www.pesapal.com/api';
        $result = $this->httpPost($apiUrl . '/Auth/RequestToken', [
            'consumer_key' => $this->getApiKey(),
            'consumer_secret' => $this->getApiSecret(),
        ], ['Content-Type: application/json'], 15);

        if ($result['http_code'] === 200 && $result['data']) {
            return $result['data']['token'] ?? null;
        }
        return null;
    }
}
