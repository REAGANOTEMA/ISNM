<?php
/**
 * Abstract base class for all payment providers.
 * Every provider adapter must extend this and implement its methods.
 */
abstract class BaseProvider {

    protected $providerKey;
    protected $providerName;
    protected $config = [];
    protected $isTestMode = true;

    public function __construct(array $config = []) {
        $this->config = $config;
        $this->isTestMode = !empty($config['is_test_mode']) || ($config['status'] ?? '') === 'sandbox';
        $this->providerKey = $config['provider_key'] ?? 'unknown';
        $this->providerName = $config['provider_name'] ?? 'Unknown Provider';
    }

    abstract public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array;
    abstract public function verifyPayment(string $providerTransactionId): array;
    abstract public function processCallback(array $payload, array $headers = []): array;

    public function getProviderKey(): string { return $this->providerKey; }
    public function getProviderName(): string { return $this->providerName; }
    public function isTestMode(): bool { return $this->isTestMode; }
    public function getConfig(): array { return $this->config; }

    protected function getApiUrl(): string {
        if ($this->isTestMode && !empty($this->config['test_api_base_url'])) {
            return rtrim($this->config['test_api_base_url'], '/');
        }
        return rtrim($this->config['api_url'] ?? $this->config['api_base_url'] ?? '', '/');
    }

    protected function getApiKey(): string {
        return $this->isTestMode
            ? ($this->config['test_api_key'] ?? $this->config['api_key'] ?? '')
            : ($this->config['api_key'] ?? '');
    }

    protected function getApiSecret(): string {
        return $this->isTestMode
            ? ($this->config['test_api_secret'] ?? $this->config['api_secret'] ?? '')
            : ($this->config['api_secret'] ?? '');
    }

    protected function calculateFee(float $amount): float {
        $feeType = $this->config['fee_type'] ?? ($this->config['transaction_fee_percent'] ?? 'none');
        $totalFee = 0.0;

        if (!empty($this->config['transaction_fee_fixed'])) {
            $totalFee += (float)$this->config['transaction_fee_fixed'];
        }
        $pct = (float)($this->config['transaction_fee_percent'] ?? 0);
        if ($pct > 0) {
            $totalFee += ($amount * $pct / 100);
        }
        return round($totalFee, 2);
    }

    protected function formatPhone(string $phone, string $defaultCountryCode = '256'): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) $phone = $defaultCountryCode . $phone;
        return $phone;
    }

    protected function httpPost(string $url, array $data, array $headers = [], int $timeout = 30): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_string($data) ? $data : json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => !$this->isTestMode,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'http_code' => $httpCode,
            'response' => $response,
            'data' => json_decode($response, true),
            'error' => $error,
        ];
    }

    protected function httpGet(string $url, array $headers = [], int $timeout = 15): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => !$this->isTestMode,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'http_code' => $httpCode,
            'response' => $response,
            'data' => json_decode($response, true),
            'error' => $error,
        ];
    }

    protected function logError(string $message, array $context = []): void {
        $context['provider'] = $this->providerKey;
        $context['test_mode'] = $this->isTestMode;
        error_log('[PaymentGateway][' . $this->providerKey . '] ' . $message . ' ' . json_encode($context));
    }

    /**
     * Check if this provider is properly configured and active.
     */
    public function isActive(): bool {
        return ($this->config['status'] ?? 'inactive') === 'active'
            || ($this->config['status'] ?? '') === 'sandbox';
    }
}
