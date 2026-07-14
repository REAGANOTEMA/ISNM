<?php
/**
 * Airtel Money Provider Adapter
 * Handles Airtel Money API integration for Uganda
 */
class AirtelMoneyProvider extends BaseProvider {

    private $clientId;
    private $clientSecret;

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'airtel_money';
        $this->providerName = 'Airtel Money';
        $this->clientId = $this->getApiKey();
        $this->clientSecret = $this->getApiSecret();
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $phone = $this->formatPhone($payer['phone'] ?? '');
        if (strlen($phone) < 12) {
            return ['success' => false, 'message' => 'Invalid phone number. Use 2567XXXXXXXX format.'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to authenticate with Airtel Money API.'];
        }

        $payload = [
            'reference' => $reference,
            'subscriber' => [
                'country' => 'UGA',
                'currency' => $currency,
                'msisdn' => $phone,
            ],
            'transaction' => [
                'amount' => $amount,
                'country' => 'UGA',
                'currency' => $currency,
                'id' => $reference,
            ],
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
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $txnId,
                'status' => 'pending',
                'message' => 'Airtel Money payment request sent. Please approve on your phone.',
            ];
        }

        $this->logError('Initiate payment failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        return ['success' => false, 'message' => 'Airtel Money gateway unavailable. Please try again or use another method.'];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'status' => 'unknown', 'message' => 'Failed to authenticate.'];
        }

        $url = $this->getApiUrl() . '/standard/v1/payments/' . $providerTransactionId;
        $result = $this->httpGet($url, [
            'Authorization: Bearer ' . $token,
            'X-Country: UGA',
            'X-Currency: UGX',
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $status = $result['data']['status'] ?? 'unknown';
            $mappedStatus = match($status) {
                'SUCCESSFUL', 'TS' => 'successful',
                'FAILED', 'TF' => 'failed',
                'INITIATED', 'TI' => 'pending',
                default => 'processing',
            };
            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'Transaction status: ' . $status,
            ];
        }

        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify transaction.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        $txnId = $payload['transaction']['id'] ?? $payload['transactionId'] ?? '';
        $status = $payload['status'] ?? $payload['transaction']['status'] ?? 'unknown';
        $reference = $payload['reference'] ?? $txnId;

        $mappedStatus = match($status) {
            'TS', 'SUCCESSFUL', 'COMPLETED' => 'successful',
            'TF', 'FAILED' => 'failed',
            'TI', 'INITIATED' => 'processing',
            default => 'processing',
        };

        return [
            'success' => true,
            'reference' => $reference,
            'provider_transaction_id' => $txnId,
            'status' => $mappedStatus,
            'raw_status' => $status,
            'message' => 'Airtel callback processed',
        ];
    }

    private function getAccessToken(): ?string {
        $url = $this->getApiUrl() . '/auth/oauth2/token';
        $result = $this->httpPost($url, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ], ['Content-Type: application/json'], 15);

        if ($result['http_code'] === 200 && $result['data']) {
            return $result['data']['access_token'] ?? null;
        }
        return null;
    }
}
