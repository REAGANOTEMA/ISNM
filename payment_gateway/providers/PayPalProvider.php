<?php
/**
 * PayPal Provider Adapter
 * Supports PayPal wallet payments and international card payments
 * Uses PayPal REST API v2
 */
class PayPalProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'paypal';
        $this->providerName = 'PayPal';
    }

    private function getAccessToken(): ?string {
        $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $clientId = $this->getApiKey();
        $clientSecret = $this->getApiSecret();

        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }

        $result = $this->httpPost($apiUrl . '/v1/oauth2/token', 'grant_type=client_credentials', [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        if ($result['http_code'] === 200 && isset($result['data']['access_token'])) {
            return $result['data']['access_token'];
        }

        $this->logError('PayPal token fetch failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        return null;
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'message' => 'PayPal authentication failed. Please check API credentials.'];
        }

        $returnUrl = $this->config['return_url'] ?? '';
        $cancelUrl = $this->config['return_url'] ?? '';

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $reference,
                    'description' => $description ?: 'ISNM Payment - ' . $reference,
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => 'Iganga School of Nursing and Midwifery',
                'locale' => 'en-US',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $returnUrl . '?ref=' . $reference,
                'cancel_url' => $cancelUrl . '?ref=' . $reference . '&cancelled=1',
            ],
        ];

        $result = $this->httpPost($apiUrl . '/v2/checkout/orders', $payload, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ]);

        if ($result['http_code'] === 201 && isset($result['data']['id'])) {
            $approveUrl = '';
            foreach ($result['data']['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approveUrl = $link['href'] ?? '';
                    break;
                }
            }

            return [
                'success' => true,
                'transaction_id' => $reference,
                'provider_ref' => $result['data']['id'],
                'status' => 'pending',
                'payment_url' => $approveUrl,
                'message' => 'Redirect to PayPal to complete payment.',
            ];
        }

        $this->logError('PayPal initiate failed', ['http_code' => $result['http_code'], 'response' => $result['response']]);
        $errMsg = $result['data']['message'] ?? 'PayPal gateway error.';
        if (isset($result['data']['details'])) {
            foreach ($result['data']['details'] as $detail) {
                $errMsg .= ' ' . ($detail['description'] ?? '');
            }
        }
        return ['success' => false, 'message' => trim($errMsg)];
    }

    public function verifyPayment(string $providerTransactionId): array {
        $apiUrl = $this->getApiUrl() ?: 'https://api-m.paypal.com';
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'status' => 'unknown', 'message' => 'PayPal authentication failed.'];
        }

        $result = $this->httpGet($apiUrl . '/v2/checkout/orders/' . $providerTransactionId, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        if ($result['http_code'] === 200 && isset($result['data']['status'])) {
            $status = $result['data']['status'];
            $mappedStatus = match($status) {
                'COMPLETED' => 'successful',
                'APPROVED' => 'processing',
                'CREATED' => 'pending',
                'VOIDED' => 'cancelled',
                'PAYER_ACTION_REQUIRED' => 'pending',
                default => 'processing',
            };

            $amount = null;
            $currency = null;
            if (isset($result['data']['purchase_units'][0]['amount'])) {
                $amount = (float)($result['data']['purchase_units'][0]['amount']['value'] ?? 0);
                $currency = $result['data']['purchase_units'][0]['amount']['currency_code'] ?? null;
            }

            return [
                'success' => $mappedStatus === 'successful',
                'status' => $mappedStatus,
                'provider_status' => $status,
                'message' => 'PayPal order status: ' . $status,
                'amount' => $amount,
                'currency' => $currency,
            ];
        }

        return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify PayPal transaction.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];
        $orderId = $resource['id'] ?? $payload['id'] ?? '';
        $customId = $resource['custom_id'] ?? '';

        // Map PayPal event types to our statuses
        $mappedStatus = match($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => 'successful',
            'PAYMENT.CAPTURE.DENIED' => 'failed',
            'PAYMENT.CAPTURE.PENDING' => 'processing',
            'PAYMENT.CAPTURE.REFUNDED' => 'refunded',
            'CHECKOUT.ORDER.APPROVED' => 'processing',
            'CHECKOUT.ORDER.COMPLETED' => 'successful',
            'CHECKOUT.ORDER.CANCELLED' => 'cancelled',
            default => 'processing',
        };

        $reference = $customId ?: $orderId;

        return [
            'success' => true,
            'reference' => $reference,
            'provider_transaction_id' => $orderId,
            'status' => $mappedStatus,
            'raw_status' => $eventType,
            'message' => 'PayPal webhook processed: ' . $eventType,
        ];
    }
}
