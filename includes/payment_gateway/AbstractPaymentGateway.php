<?php
/**
 * Abstract base payment gateway with common functionality.
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface {

    protected $config;
    protected $providerKey;
    protected $providerName;
    protected $conn;

    public function __construct(array $config = []) {
        $this->config = $config;
        $this->providerKey = $config['provider_key'] ?? '';
        $this->providerName = $config['provider_name'] ?? '';
    }

    protected function getMerchantId(): string {
        return $this->config['merchant_id'] ?? '';
    }

    protected function getApiKey(): string {
        return $this->config['api_key'] ?? '';
    }

    protected function getApiSecret(): string {
        return $this->config['api_secret'] ?? '';
    }

    protected function getApiUrl(): string {
        return $this->config['api_url'] ?? '';
    }

    protected function getCallbackUrl(): string {
        $base = rtrim($_SERVER['HTTP_HOST'] ?? 'localhost', '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $this->config['callback_url'] ?? "$scheme://$base{$path}/includes/payment_gateway/handlers/webhook_handler.php?provider={$this->providerKey}";
    }

    protected function getWebhookSecret(): string {
        return $this->config['webhook_secret'] ?? '';
    }

    protected function calculateFee(float $amount): float {
        $percentFee = (float)($this->config['transaction_fee_percent'] ?? 0);
        $fixedFee = (float)($this->config['transaction_fee_fixed'] ?? 0);
        return round(($amount * $percentFee / 100) + $fixedFee, 2);
    }

    protected function makeHttpRequest(string $method, string $url, array $headers = [], $body = null): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
            }
        } elseif (strtoupper($method) === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
            }
        } elseif (strtoupper($method) === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => $error ?: 'HTTP request failed', 'http_code' => 0];
        }

        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'response' => $response,
            'data' => json_decode($response, true),
        ];
    }

    protected function generateRef(string $prefix = 'TXN'): string {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }

    public function getProviderKey(): string {
        return $this->providerKey;
    }

    public function getProviderName(): string {
        return $this->providerName;
    }

    public function getSupportedPaymentTypes(): array {
        return ['student_fees', 'application', 'admission', 'graduation', 'hostel', 'library_fine', 'donation', 'misc'];
    }
}
