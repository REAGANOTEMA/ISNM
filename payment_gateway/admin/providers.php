<?php
/**
 * Payment Provider Admin — Configure and manage payment providers.
 * Access: /payment_gateway/admin/providers.php
 */
session_start();
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/GatewayManager.php';

$conn = getStudentsConnection();
$message = '';
$messageType = '';

// Handle provider status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $providerKey = $_POST['provider_key'] ?? '';
        $newStatus = $_POST['new_status'] ?? 'inactive';
        $stmt = $conn->prepare("UPDATE payment_providers SET status = ? WHERE provider_key = ?");
        $stmt->bind_param('ss', $newStatus, $providerKey);
        $stmt->execute();
        $stmt->close();
        $message = 'Provider "' . $providerKey . '" status updated to ' . $newStatus;
        $messageType = 'success';
    } elseif ($_POST['action'] === 'update_config') {
        $providerKey = $_POST['provider_key'] ?? '';
        $fields = ['api_url', 'api_key', 'api_secret', 'merchant_id', 'callback_url', 'webhook_url'];
        $updates = [];
        $params = [];
        $types = '';
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $updates[] = $f . ' = ?';
                $params[] = $_POST[$f];
                $types .= 's';
            }
        }
        if (!empty($updates)) {
            $sql = "UPDATE payment_providers SET " . implode(', ', $updates) . " WHERE provider_key = ?";
            $params[] = $providerKey;
            $types .= 's';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $message = 'Configuration updated for "' . $providerKey . '"';
                $messageType = 'success';
            }
        }
    }
}

// Fetch providers
$result = $conn->query("SELECT * FROM payment_providers ORDER BY sort_order ASC");
$providers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Providers — ISNM Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><i class="fas fa-credit-card me-2"></i>Payment Providers</h3>
            <p class="text-muted mb-0">Manage payment gateway integrations</p>
        </div>
        <a href="/dashboards/school-bursar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Bursar Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach ($providers as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-<?= match($p['provider_type']) {
                                    'mobile_money' => 'mobile-alt',
                                    'card', 'bank_card', 'card_gateway' => 'credit-card',
                                    'bank', 'bank_transfer' => 'university',
                                    'wallet' => 'wallet',
                                    default => 'money-bill'
                                } ?> me-2 text-primary"></i>
                                <?= htmlspecialchars($p['provider_name']) ?>
                            </h5>
                            <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : ($p['status'] === 'sandbox' ? 'warning' : 'secondary') ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </div>
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars($p['provider_type']) ?> — <?= htmlspecialchars($p['provider_key']) ?>
                        </p>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="provider_key" value="<?= htmlspecialchars($p['provider_key']) ?>">
                                <div class="input-group">
                                    <select name="new_status" class="form-select form-select-sm">
                                        <option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="sandbox" <?= $p['status'] === 'sandbox' ? 'selected' : '' ?>>Sandbox/Testing</option>
                                        <option value="inactive" <?= $p['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                </div>
                            </form>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">API URL</label>
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($p['api_url'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">API Key</label>
                            <input type="password" class="form-control form-control-sm" value="<?= htmlspecialchars($p['api_key'] ?? '') ?>" readonly>
                        </div>

                        <div class="small text-muted">
                            <i class="fas fa-chart-bar me-1"></i>
                            <?= number_format($p['total_transactions'] ?? 0) ?> transactions — 
                            UGX <?= number_format($p['total_volume'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
