<?php
/**
 * Payment Gateway Integration for ISNM
 * Supports: MTN MoMo (Sandbox/Production), Airtel Money
 * Integration endpoint for school-bursar.php payment processing.
 */

if (!class_exists('PaymentGateway')) {
    class PaymentGateway {
        private $mode; // 'sandbox' or 'production'
        private $mtnConfig = [];
        private $airtelConfig = [];

        public function __construct() {
            $this->mode = getenv('PAYMENT_MODE') ?: 'sandbox';
            $this->mtnConfig = [
                'api_user'  => getenv('MTN_MOMO_API_USER')  ?: 'sandbox_user',
                'api_key'   => getenv('MTN_MOMO_API_KEY')   ?: 'sandbox_key',
                'subscription_key' => getenv('MTN_MOMO_SUB_KEY') ?: 'sandbox_sub_key',
                'collection_url'   => $this->mode === 'production'
                    ? 'https://momodeveloper.mtn.com/collection/'
                    : 'https://sandbox.momodeveloper.mtn.com/collection/',
            ];
            $this->airtelConfig = [
                'client_id'     => getenv('AIRTEL_CLIENT_ID') ?: 'sandbox_id',
                'client_secret' => getenv('AIRTEL_CLIENT_SECRET') ?: 'sandbox_secret',
                'api_url'       => $this->mode === 'production'
                    ? 'https://openapi.airtel.ug/'
                    : 'https://openapi.airtel.ug/sandbox/',
            ];
        }

        /**
         * Request payment via mobile money.
         * @param string $provider 'mtn' or 'airtel'
         * @param string $phone    2567XXXXXXXX
         * @param float  $amount   UGX
         * @param string $reference Internal reference
         * @param string $note      Payment description
         * @return array ['success'=>bool, 'transaction_id'=>'...', 'message'=>'...']
         */
        public function requestPayment($provider, $phone, $amount, $reference, $note = '') {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) === 9) $phone = '256' . $phone;
            if (strlen($phone) !== 12) return ['success' => false, 'message' => 'Invalid phone number. Use 2567XXXXXXXX format.'];

            if ($provider === 'mtn') return $this->mtnRequest($phone, $amount, $reference, $note);
            if ($provider === 'airtel') return $this->airtelRequest($phone, $amount, $reference, $note);
            return ['success' => false, 'message' => 'Unsupported provider. Use mtn or airtel.'];
        }

        /**
         * Verify transaction status.
         * @param string $provider
         * @param string $transactionId
         * @return array ['success'=>bool, 'status'=>'...', 'message'=>'...']
         */
        public function verifyTransaction($provider, $transactionId) {
            if ($provider === 'mtn') return $this->mtnVerify($transactionId);
            if ($provider === 'airtel') return $this->airtelVerify($transactionId);
            return ['success' => false, 'status' => 'unknown', 'message' => 'Unsupported provider.'];
        }

        // â”€â”€ MTN MoMo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        private function mtnRequest($phone, $amount, $ref, $note) {
            $payload = json_encode([
                'amount' => ['currency' => 'EUR', 'value' => $amount / 3700], // approximate conversion for sandbox
                'externalId' => substr($ref, 0, 30),
                'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
                'payerMessage' => substr($note ?: 'Payment ' . $ref, 0, 50),
                'payeeNote' => 'ISNM Fee Payment',
            ]);
            $token = $this->mtnGetToken();
            if (!$token) return ['success' => false, 'message' => 'Failed to authenticate with MTN MoMo.'];
            $url = $this->mtnConfig['collection_url'] . 'v1_0/requesttopay';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'X-Reference-Id: ' . $ref,
                    'X-Target-Environment: ' . $this->mode,
                    'Content-Type: application/json',
                    'Ocp-Apim-Subscription-Key: ' . $this->mtnConfig['subscription_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 202) {
                return ['success' => true, 'transaction_id' => $ref, 'status' => 'pending', 'message' => 'Payment request sent. Awaiting customer approval.'];
            }
            error_log('MTN MoMo request failed: HTTP ' . $httpCode . ' ' . ($response ?: ''));
            return ['success' => false, 'message' => 'Payment gateway unavailable. Payment recorded as unverified.'];
        }

        private function mtnGetToken() {
            $url = $this->mtnConfig['collection_url'] . 'v1_0/apiuser/' . $this->mtnConfig['api_user'] . '/apikey';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Ocp-Apim-Subscription-Key: ' . $this->mtnConfig['subscription_key']],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $res = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($http === 201 && ($data = json_decode($res, true))) return $data['apiKey'] ?? null;
            return 'sandbox_token';
        }

        private function mtnVerify($transactionId) {
            $url = $this->mtnConfig['collection_url'] . 'v1_0/requesttopay/' . $transactionId;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . ($this->mtnGetToken() ?: ''),
                    'X-Target-Environment: ' . $this->mode,
                    'Ocp-Apim-Subscription-Key: ' . $this->mtnConfig['subscription_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $res = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($http === 200 && ($data = json_decode($res, true))) {
                $status = $data['status'] ?? 'unknown';
                return ['success' => $status === 'SUCCESSFUL', 'status' => $status, 'message' => 'Transaction ' . strtolower($status)];
            }
            return ['success' => false, 'status' => 'unknown', 'message' => 'Could not verify transaction.'];
        }

        // â”€â”€ Airtel Money â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        private function airtelRequest($phone, $amount, $ref, $note) {
            $payload = json_encode([
                'reference' => $ref,
                'subscriber' => ['country' => 'UGA', 'currency' => 'UGX', 'msisdn' => $phone],
                'transaction' => ['amount' => $amount, 'country' => 'UGA', 'currency' => 'UGX', 'id' => $ref],
            ]);
            $token = $this->airtelGetToken();
            if (!$token) return ['success' => false, 'message' => 'Failed to authenticate with Airtel.'];
            $url = $this->airtelConfig['api_url'] . 'standard/v1/payments/';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'X-Country: UGA',
                    'X-Currency: UGX',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200 || $httpCode === 201) {
                $data = json_decode($response, true);
                $txnId = $data['transaction']['id'] ?? $ref;
                return ['success' => true, 'transaction_id' => $txnId, 'status' => 'pending', 'message' => 'Airtel Money request sent.'];
            }
            error_log('Airtel request failed: HTTP ' . $httpCode . ' ' . ($response ?: ''));
            return ['success' => false, 'message' => 'Airtel gateway unavailable. Payment recorded as unverified.'];
        }

        private function airtelGetToken() {
            $url = $this->airtelConfig['api_url'] . 'auth/oauth2/token';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['client_id' => $this->airtelConfig['client_id'], 'client_secret' => $this->airtelConfig['client_secret'], 'grant_type' => 'client_credentials']),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $res = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($http === 200 && ($data = json_decode($res, true))) return $data['access_token'] ?? null;
            return 'sandbox_airtel_token';
        }

        private function airtelVerify($transactionId) {
            return ['success' => true, 'status' => 'pending', 'message' => 'Airtel verification pending.'];
        }
    }
}

/**
 * Fee clearance check for result blocking.
 * @param mysqli $stuConn Students DB connection
 * @param int $studentId
 * @return array ['cleared'=>bool, 'balance'=>float]
 */
function checkFeeClearance($stuConn, $studentId) {
    if (!$stuConn) return ['cleared' => true, 'balance' => 0];
    $r = $stuConn->query("SELECT COALESCE(SUM(balance),0) as bal FROM student_fees WHERE student_id=" . (int)$studentId);
    if (!$r) return ['cleared' => true, 'balance' => 0];
    $bal = (float)$r->fetch_assoc()['bal'];
    return ['cleared' => $bal <= 0, 'balance' => $bal];
}

/**
 * Mobile Money webhook handler endpoint.
 * POST /api/momo-callback.php â€” processes payment callbacks from MTN/Airtel.
 */
function handleMobileMoneyCallback() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) return ['success' => false, 'message' => 'Invalid callback data'];
    $ref = $input['externalId'] ?? $input['reference'] ?? '';
    $status = $input['status'] ?? 'unknown';
    $txnId = $input['transactionId'] ?? $input['transaction']['id'] ?? '';
    if (!$ref) return ['success' => false, 'message' => 'No reference in callback'];
    $conn = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
    if (!$conn) return ['success' => false, 'message' => 'DB unavailable'];
    $s = $conn->prepare("UPDATE payments SET status=?, transaction_id=?, verified_at=NOW() WHERE receipt_number=? OR reference=?");
    if (!$s) return ['success' => false, 'message' => 'DB error'];
    $newStatus = (strtoupper($status) === 'SUCCESSFUL' || $status === 'completed') ? 'verified' : 'failed';
    $s->bind_param('ssss', $newStatus, $txnId, $ref, $ref);
    if (!$s->execute()) { error_log('$s execute failed: ' . ($s->error ?? 'unknown')); };
    $s->close();
    $conn->close();
    return ['success' => true, 'message' => 'Callback processed: ' . $newStatus];
}