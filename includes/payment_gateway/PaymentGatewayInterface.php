<?php
/**
 * Payment Gateway Interface
 * All payment providers must implement this interface.
 */
interface PaymentGatewayInterface {

    /**
     * Initialize a payment request with the provider
     * @param array $params Amount, phone, email, reference, metadata, etc.
     * @return array ['success' => bool, 'transaction_ref' => string, 'provider_ref' => string, 'redirect_url' => string|null, 'message' => string]
     */
    public function initiatePayment(array $params): array;

    /**
     * Check the status of a pending payment
     * @param string $providerTransactionId
     * @return array ['success' => bool, 'status' => string, 'message' => string]
     */
    public function checkTransactionStatus(string $providerTransactionId): array;

    /**
     * Process an incoming webhook/callback from the provider
     * @param array $payload Raw callback data
     * @return array ['success' => bool, 'transaction_ref' => string, 'status' => string, 'message' => string]
     */
    public function processWebhook(array $payload): array;

    /**
     * Verify a webhook signature
     * @param string $rawBody Raw request body
     * @param string $signature Signature from provider
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool;

    /**
     * Initiate a refund (if supported)
     * @param string $providerTransactionId
     * @param float $amount
     * @param string $reason
     * @return array ['success' => bool, 'refund_id' => string, 'message' => string]
     */
    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array;

    /**
     * Get provider key identifier
     */
    public function getProviderKey(): string;

    /**
     * Get provider display name
     */
    public function getProviderName(): string;

    /**
     * Get supported payment types
     */
    public function getSupportedPaymentTypes(): array;
}
