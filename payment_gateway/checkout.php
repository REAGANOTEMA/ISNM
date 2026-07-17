<?php
/**
 * Payment Checkout Page
 * Renders a unified payment form for any provider.
 * Access: /payment_gateway/checkout.php?ref=PAY-20260714-ABC123&amount=500000&provider=mtn_momo
 */
session_start();
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core/GatewayManager.php';

$reference = $_GET['ref'] ?? $_GET['reference'] ?? '';
$amount = (float)($_GET['amount'] ?? 0);
$provider = $_GET['provider'] ?? '';
$paymentFor = $_GET['for'] ?? 'tuition';
$studentId = (int)($_GET['student_id'] ?? $_SESSION['student_id'] ?? 0);
$payerName = $_GET['name'] ?? '';
$payerPhone = $_GET['phone'] ?? '';
$payerEmail = $_GET['email'] ?? '';

$gateway = GatewayManager::getInstance();
$providers = $gateway->getAvailableProviders();

// Process payment if submitted
$paymentResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['pay_now'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) { die('Invalid CSRF token'); }
    $selectedProvider = $_POST['selected_provider'] ?? $provider;
    $paymentResult = $gateway->initiatePayment($selectedProvider, [
        'reference' => $_POST['reference'] ?? $reference,
        'amount' => (float)($_POST['amount'] ?? $amount),
        'currency' => $_POST['currency'] ?? 'UGX',
        'payment_for' => $_POST['payment_for'] ?? $paymentFor,
        'student_id' => $_POST['student_id'] ?? $studentId,
        'payer_name' => $_POST['payer_name'] ?? $payerName,
        'payer_phone' => $_POST['payer_phone'] ?? $payerPhone,
        'payer_email' => $_POST['payer_email'] ?? $payerEmail,
        'description' => $_POST['description'] ?? '',
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM — Make a Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .checkout-card { max-width: 600px; margin: 40px auto; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
        .provider-option { border: 2px solid #e0e0e0; border-radius: 12px; padding: 12px 16px; cursor: pointer; transition: all 0.2s; }
        .provider-option:hover { border-color: #4CAF50; background: #f8fff8; }
        .provider-option.selected { border-color: #4CAF50; background: #e8f5e9; }
        .provider-option input { display: none; }
        .pay-btn { background: #4CAF50; color: white; border: none; padding: 14px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; }
        .pay-btn:hover { background: #388E3C; }
        .school-header { background: linear-gradient(135deg, #1a5276, #2e86c1); color: white; padding: 20px; border-radius: 16px 16px 0 0; }
        .amount-display { font-size: 28px; font-weight: 700; color: #2e7d32; }
    </style>
</head>
<body>
<div class="checkout-card card">
    <div class="school-header text-center">
        <h4 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Iganga School of Nursing and Midwifery</h4>
        <p class="mb-0 mt-1">Secure Payment Portal</p>
    </div>

    <div class="card-body p-4">
        <?php if ($paymentResult && !$paymentResult['success']): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($paymentResult['message']) ?></div>
        <?php endif; ?>

        <?php if ($paymentResult && $paymentResult['success'] && !empty($paymentResult['payment_url'])): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>Payment Initiated Successfully</h5>
                <p class="text-muted"><?= htmlspecialchars($paymentResult['message']) ?></p>
                <p><strong>Reference:</strong> <?= htmlspecialchars($paymentResult['reference'] ?? '') ?></p>
                <a href="<?= htmlspecialchars($paymentResult['payment_url']) ?>" class="pay-btn mt-3 d-inline-block">
                    <i class="fas fa-external-link-alt me-2"></i>Proceed to Payment
                </a>
            </div>
        <?php elseif ($paymentResult && $paymentResult['success']): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>Payment Request Sent</h5>
                <p class="text-muted"><?= htmlspecialchars($paymentResult['message']) ?></p>
                <p><strong>Reference:</strong> <?= htmlspecialchars($paymentResult['reference'] ?? '') ?></p>
                <button class="btn btn-outline-primary mt-3" onclick="checkStatus()">
                    <i class="fas fa-sync-alt me-2"></i>Check Payment Status
                </button>
            </div>
        <?php else: ?>
            <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Complete Your Payment</h5>

            <div class="text-center mb-4">
                <div class="text-muted">Amount to Pay</div>
                <div class="amount-display" id="amountDisplay"><?= $amount > 0 ? 'UGX ' . number_format($amount) : '' ?></div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="reference" value="<?= htmlspecialchars($reference) ?>">
                <input type="hidden" name="amount" value="<?= $amount ?>">
                <input type="hidden" name="payment_for" value="<?= htmlspecialchars($paymentFor) ?>">
                <input type="hidden" name="student_id" value="<?= $studentId ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Name</label>
                    <input type="text" name="payer_name" class="form-control" value="<?= htmlspecialchars($payerName) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="tel" name="payer_phone" class="form-control" placeholder="07XX XXX XXX" value="<?= htmlspecialchars($payerPhone) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email (optional)</label>
                    <input type="email" name="payer_email" class="form-control" value="<?= htmlspecialchars($payerEmail) ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Select Payment Method</label>
                    <div class="row g-2">
                        <?php foreach ($providers as $p): ?>
                            <div class="col-6">
                                <label class="provider-option <?= $p['key'] === $provider ? 'selected' : '' ?>" onclick="selectProvider(this, '<?= $p['key'] ?>')">
                                    <input type="radio" name="selected_provider" value="<?= $p['key'] ?>" <?= $p['key'] === $provider ? 'checked' : '' ?>>
                                    <i class="fas fa-<?= match($p['type']) {
                                        'mobile_money' => 'mobile-alt',
                                        'card', 'bank_card', 'card_gateway' => 'credit-card',
                                        'bank', 'bank_transfer' => 'university',
                                        'wallet' => 'wallet',
                                        default => 'money-bill'
                                    } ?> me-2"></i>
                                    <?= htmlspecialchars($p['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" name="pay_now" value="1" class="pay-btn">
                        <i class="fas fa-lock me-2"></i>Pay Now
                    </button>
                </div>

                <p class="text-center text-muted mt-3 small">
                    <i class="fas fa-shield-alt me-1"></i>Your payment is secured with 256-bit SSL encryption
                </p>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function selectProvider(el, key) {
    document.querySelectorAll('.provider-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
}

function checkStatus() {
    const ref = '<?= htmlspecialchars($paymentResult["reference"] ?? "") ?>';
    const provider = '<?= htmlspecialchars($paymentResult["provider"] ?? $provider) ?>';
    fetch('/payment_gateway/api/verify.php?provider=' + provider + '&transaction_ref=' + ref)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'successful') {
                alert('Payment confirmed! Receipt will be generated shortly.');
                window.location.reload();
            } else {
                alert('Status: ' + (data.message || 'Still pending. Please wait a moment and check again.'));
            }
        })
        .catch(() => alert('Unable to check status. Please try again.'));
}
</script>
</body>
</html>
