<?php
/**
 * Cash Payment Provider
 * For recording cash payments made at the finance office
 */
class CashProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'cash';
        $this->providerName = 'Cash Payment';
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        return [
            'success' => true,
            'transaction_id' => $reference,
            'status' => 'pending',
            'message' => 'Cash payment recorded. Please pay at the finance office and present your reference: ' . $reference,
        ];
    }

    public function verifyPayment(string $providerTransactionId): array {
        return ['success' => false, 'status' => 'pending', 'message' => 'Cash payments require manual verification.'];
    }

    public function processCallback(array $payload, array $headers = []): array {
        return ['success' => false, 'status' => 'pending', 'message' => 'Cash payments do not support callbacks.'];
    }
}
