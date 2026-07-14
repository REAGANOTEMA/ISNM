<?php
/**
 * MTN Mobile Money Provider Adapter
 * Handles MTN MoMo API integration for Uganda
 */
class MtnMomoProvider extends BaseProvider {

    private $subscriptionKey;

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'mtn_momo';
        $this->providerName = 'MTN Mobile Money';
        $this->subscriptionKey = $this->isTestMode
            ? ($config['test_api_key'] ?? $config['api_key'] ?? '')
            : ($config['api_key'] ?? '');
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $phone = $this->formatPhone($payer['phone'] ?? '');
        if (strlen($phone) < 12) {
            return ['success' => false, 'message' => 'Invalid phone number. Use 2567XXXXXXXX format.'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to authenticate with MTN MoMo API.'];
        }

        $apiUrl = $this->getApiUrl();
        $payload = [
            'amount' => ['currency' => $currency, 'value' => (string)$amount],
            'externalId' => substr($reference, 0, 30),
            'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
            'payerMessage' => substr($description ?: 'Payment ' . $reference, 0, 50),
            'payeeNote' => 'ISNM Payment',
        ];

        $url = $apiUrl . '/v1_0/requesttopay';
        $result = $this->httpPost($url, $payload, [
            'Authorization: Bearer ' . $token,
            'X-Reference-Id: ' . $reference,
            'X-Target-Environment: ' . ($this->isTestMode ? 'sandbox' : 'production'),
            'Content-Type: application/json',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        ]);

        if ($result['http_code'] === 202 || $result['http_code'] === 200) {
            return [
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $reference,
                'status' => 'pending',
                'message' => 'MTN MoMo payment request sent. Please approve on your phone.',
            ];
        }

        $this->logError('Initiate payment failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        return ['success' => false, 'message' => 'MTN MoMo gateway unavailable. Please try again or use another method.'];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'status' => 'unknown', 'message' => 'Failed to authenticate.'];
        }

        $url = $this->getApiUrl() . '/v1_0/requesttopay/' . $providerTransactionId;
        $result = $this->httpGet($url, [
            'Authorization: Bearer ' . $token,
            'X-Target-Environment: ' . ($this->isTestMode ? 'sandbox' : 'production'),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        ]);

        if ($result['http_code'] === 200 && $result['data']) {
            $status = $result['data']['status'] ?? 'unknown';
            $mappedStatus = match($status) {
                'SUCCESSFUL' => 'successful',
                'FAILED' => 'failed',
                'REJECTED' => 'failed',
                'TIMEOUT' => 'expired',
                default => 'processing',
            };
            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'Transaction status: ' . strtolower($status),
                'amount' => $result['data']['amount']['value'] ?? null,
                'currency' => $result['data']['amount']['currency'] ?? null,
            ];
        }

        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify transaction.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        $externalId = $payload['externalId'] ?? $payload['external_id'] ?? '';
        $status = $payload['status'] ?? 'unknown';
        $txnId = $payload['transactionId'] ?? $payload['transaction_id'] ?? '';

        $mappedStatus = match(strtoupper($status)) {
            'SUCCESSFUL', 'COMPLETED' => 'successful',
            'FAILED', 'REJECTED' => 'failed',
            'TIMEOUT' => 'expired',
            default => 'processing',
        };

        return [
            'success' => true,
            'reference' => $externalId,
            'provider_transaction_id' => $txnId,
            'status' => $mappedStatus,
            'raw_status' => $status,
            'message' => 'MTN callback processed',
        ];
    }

    private function getAccessToken(): ?string {
        $url = $this->getApiUrl() . '/v1_0/apiuser/' . $this->getApiKey() . '/apikey';
        $result = $this->httpPost($url, '', [
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        ], 15);

        if ($result['http_code'] === 201 && $result['data']) {
            return $result['data']['apiKey'] ?? null;
        }
        return null;
    }
}
