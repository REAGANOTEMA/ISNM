<?php
/**
 * Bank Transfer Provider Adapter
 * Handles manual bank transfers with verification
 */
class BankTransferProvider extends BaseProvider {

    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->providerKey = 'bank_transfer';
        $this->providerName = 'Bank Transfer';
    }

    public function initiatePayment(string $reference, float $amount, string $currency, array $payer, string $description = ''): array {
        $bankDetails = [
            'bank_name' => $this->config['bank_name'] ?? 'Stanbic Bank Uganda',
            'account_name' => $this->config['account_name'] ?? 'Iganga School of Nursing and Midwifery',
            'account_number' => $this->config['account_number'] ?? '',
            'swift_code' => $this->config['swift_code'] ?? '',
            'branch' => $this->config['branch'] ?? 'Iganga',
            'reference' => $reference,
        ];

        return [
            'success' => true,
            'transaction_id' => $reference,
            'status' => 'pending',
            'bank_details' => $bankDetails,
            'message' => 'Transfer the exact amount to the bank account below. Use the reference number: ' . $reference,
            'instructions' => 'After making the transfer, upload your payment slip or proof of payment for verification.',
        ];
    }

    public function verifyPayment(string $providerTransactionId): array {
        return [
            'success' => false,
            'status' => 'pending',
            'message' => 'Bank transfers require manual verification by the finance team.',
        ];
    }

    public function processCallback(array $payload, array $headers = []): array {
        return [
            'success' => false,
            'reference' => '',
            'provider_transaction_id' => '',
            'status' => 'pending',
            'message' => 'Bank transfers do not support automatic callbacks.',
        ];
    }
}
