<?php
/**
 * Direct Bank Transfer payment handler
 * Manual bank transfer with reconciliation support.
 */
require_once __DIR__ . '/../AbstractPaymentGateway.php';

class BankTransferGateway extends AbstractPaymentGateway {

    public function __construct(array $config = []) {
        parent::__construct($config);
        if (empty($this->config['provider_key'])) $this->config['provider_key'] = 'bank_transfer';
        if (empty($this->config['provider_name'])) $this->config['provider_name'] = 'Bank Transfer';
    }

    public function initiatePayment(array $params): array {
        $ref = $params['transaction_ref'] ?? $this->generateRef('BTR');
        $bankConfig = json_decode($this->config['config_json'] ?? '{}', true) ?: [];

        return [
            'success' => true,
            'transaction_ref' => $ref,
            'provider_ref' => '',
            'redirect_url' => null,
            'message' => 'Bank transfer details provided. Complete transfer and upload proof.',
            'bank_details' => [
                'bank_name' => $bankConfig['bank_name'] ?? 'Bank of Uganda',
                'account_name' => $bankConfig['account_name'] ?? 'Iganga School of Nursing and Midwifery',
                'account_number' => $bankConfig['account_number'] ?? '',
                'swift_code' => $bankConfig['swift_code'] ?? '',
                'reference' => $ref,
            ],
        ];
    }

    public function checkTransactionStatus(string $providerTransactionId): array {
        return ['success' => true, 'status' => 'pending', 'message' => 'Awaiting manual reconciliation'];
    }

    public function processWebhook(array $payload): array {
        return ['success' => false, 'message' => 'Bank transfers do not support webhooks'];
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool {
        return true;
    }

    public function refundTransaction(string $providerTransactionId, float $amount, string $reason = ''): array {
        return ['success' => false, 'message' => 'Bank transfer refund requires manual processing'];
    }
}
