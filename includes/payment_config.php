<?php
/**
 * ISNM Payment Provider Configuration Management
 *
 * Provides functions to list, read, update, and check the status
 * of payment providers. All configuration is backed by the
 * payment_providers and payment_gateway_settings tables.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/payment_config.php';
 *   $all    = getPaymentProviders();
 *   $mtn    = getProviderConfig('mtn_momo');
 *   $active = isProviderEnabled('stripe');
 *   updateProviderConfig('mtn_momo', ['api_key' => 'new_key']);
 */

if (!function_exists('getPaymentGatewayDB')) {
    function getPaymentGatewayDB(): ?mysqli {
        if (function_exists('getStudentsConnection')) {
            return getStudentsConnection();
        }
        $configPath = __DIR__ . '/../config/database.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        }
        return function_exists('getStudentsConnection') ? getStudentsConnection() : null;
    }
}

/**
 * List all configured payment providers, sorted by sort_order.
 *
 * @param bool $includeInactive  If true, returns all providers regardless of status
 * @return array  key-indexed array of provider config rows
 */
function getPaymentProviders(bool $includeInactive = true): array {
    $conn = getPaymentGatewayDB();
    if (!$conn) return [];

    $sql = $includeInactive
        ? "SELECT * FROM payment_providers ORDER BY sort_order ASC, provider_name ASC"
        : "SELECT * FROM payment_providers WHERE status IN ('active','testing') ORDER BY sort_order ASC, provider_name ASC";

    $result = $conn->query($sql);
    if (!$result) return [];

    $providers = [];
    while ($row = $result->fetch_assoc()) {
        $providers[$row['provider_key']] = $row;
    }
    return $providers;
}

/**
 * Get full configuration for a specific provider.
 *
 * @param string $providerKey  e.g. 'mtn_momo', 'stripe'
 * @return array|null  provider config row or null if not found
 */
function getProviderConfig(string $providerKey): ?array {
    $conn = getPaymentGatewayDB();
    if (!$conn) return null;

    $stmt = $conn->prepare("SELECT * FROM payment_providers WHERE provider_key = ? LIMIT 1");
    if (!$stmt) return null;

    $stmt->bind_param('s', $providerKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Update configuration fields for a provider.
 *
 * @param string $providerKey  e.g. 'mtn_momo'
 * @param array  $config       Associative array of field => value to update
 * @return array  ['success' => bool, 'message' => string]
 */
function updateProviderConfig(string $providerKey, array $config): array {
    $conn = getPaymentGatewayDB();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection unavailable.'];
    }

    $allowed = [
        'provider_name', 'provider_type', 'provider_category', 'description',
        'api_base_url', 'api_key', 'api_secret', 'merchant_id',
        'public_key', 'private_key', 'callback_url', 'webhook_url', 'return_url',
        'currency', 'supported_currencies',
        'fee_type', 'fee_fixed', 'fee_percentage',
        'min_amount', 'max_amount',
        'status', 'is_test_mode',
        'test_api_base_url', 'test_api_key', 'test_api_secret', 'test_merchant_id',
        'hmac_secret', 'certificate_path', 'config_data',
        'sort_order', 'logo_url',
    ];

    $updates = [];
    $params  = [];
    $types   = '';

    foreach ($config as $field => $value) {
        if (!in_array($field, $allowed, true)) continue;
        $updates[] = $field . ' = ?';
        $params[]  = $value;
        $types    .= 's';
    }

    if (empty($updates)) {
        return ['success' => false, 'message' => 'No valid fields to update.'];
    }

    $params[] = $providerKey;
    $types   .= 's';

    $sql = "UPDATE payment_providers SET " . implode(', ', $updates) . " WHERE provider_key = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Failed to prepare query: ' . $conn->error];
    }

    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    $affected = $conn->affected_rows;
    $stmt->close();

    if (!$success) {
        return ['success' => false, 'message' => 'Update failed.'];
    }

    /* ── Audit log ────────────────────────────────────── */
    if (function_exists('PaymentGateway') && class_exists('PaymentGateway')) {
        $gw = PaymentGateway::getInstance();
        $gw->auditLog('provider_config_updated', 'provider', 0, null, [
            'provider' => $providerKey,
            'fields'   => array_keys($config),
        ]);
    }

    return [
        'success' => true,
        'message' => $affected > 0
            ? 'Provider "' . $providerKey . '" configuration updated.'
            : 'No changes applied (same values or provider not found).',
    ];
}

/**
 * Check if a provider is enabled (active or testing).
 *
 * @param string $providerKey  e.g. 'mtn_momo'
 * @return bool
 */
function isProviderEnabled(string $providerKey): bool {
    $config = getProviderConfig($providerKey);
    return $config && in_array($config['status'] ?? '', ['active', 'testing'], true);
}

/**
 * Get all enabled providers as a simple key => name list.
 *
 * @return array  e.g. ['mtn_momo' => 'MTN Mobile Money', ...]
 */
function getEnabledProviderKeys(): array {
    $conn = getPaymentGatewayDB();
    if (!$conn) return [];

    $result = $conn->query(
        "SELECT provider_key, provider_name FROM payment_providers
         WHERE status IN ('active','testing') ORDER BY sort_order ASC"
    );
    if (!$result) return [];

    $out = [];
    while ($row = $result->fetch_assoc()) {
        $out[$row['provider_key']] = $row['provider_name'];
    }
    return $out;
}

/* ─────────────────────────────────────────────────────────────
   GATEWAY SETTINGS (key-value store)
   ───────────────────────────────────────────────────────────── */

/**
 * Get a gateway setting value.
 *
 * @param string $key      Setting key
 * @param mixed  $default  Fallback value
 * @return mixed
 */
function getGatewaySetting(string $key, $default = null) {
    $conn = getPaymentGatewayDB();
    if (!$conn) return $default;

    $stmt = $conn->prepare("SELECT setting_value FROM payment_gateway_settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) return $default;

    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || $row['setting_value'] === null) return $default;
    $val = $row['setting_value'];
    if (is_numeric($val)) return $val + 0;
    if ($val === '1') return true;
    if ($val === '0') return false;
    return $val;
}

/**
 * Set a gateway setting value (upsert).
 *
 * @param string $key    Setting key
 * @param mixed  $value  Value to store
 * @param string $group  Setting group (e.g. 'general', 'webhooks')
 * @return bool
 */
function setGatewaySetting(string $key, $value, string $group = 'general'): bool {
    $conn = getPaymentGatewayDB();
    if (!$conn) return false;

    $stmt = $conn->prepare("INSERT INTO payment_gateway_settings (setting_key, setting_value, setting_group)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
    if (!$stmt) return false;

    $stmt->bind_param('sss', $key, $value, $group);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Get all gateway settings, optionally filtered by group.
 *
 * @param string|null $group  Filter by group, or null for all
 * @return array  key => value pairs
 */
function getAllGatewaySettings(?string $group = null): array {
    $conn = getPaymentGatewayDB();
    if (!$conn) return [];

    if ($group) {
        $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_group, description FROM payment_gateway_settings WHERE setting_group = ? ORDER BY setting_key");
        if (!$stmt) return [];
        $stmt->bind_param('s', $group);
    } else {
        $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_group, description FROM payment_gateway_settings ORDER BY setting_group, setting_key");
    }
    if (!$stmt) return [];

    $stmt->execute();
    $result = $stmt->get_result();
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = [
            'value'       => $row['setting_value'],
            'group'       => $row['setting_group'],
            'description' => $row['description'] ?? '',
        ];
    }
    $stmt->close();
    return $settings;
}

/**
 * Check if the entire gateway is enabled.
 *
 * @return bool
 */
function isPaymentGatewayEnabled(): bool {
    return (bool) getGatewaySetting('gateway_enabled', true);
}

/**
 * Get the base callback URL for webhooks.
 *
 * @return string  e.g. 'https://isnm.ac.ug'
 */
function getCallbackBaseUrl(): string {
    $url = getGatewaySetting('callback_base_url', '');
    if (!empty($url)) return rtrim($url, '/');
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    return $proto . '://' . $host;
}

/**
 * Build the full callback URL for a given provider.
 *
 * @param string $providerKey  e.g. 'mtn_momo'
 * @return string
 */
function getProviderCallbackUrl(string $providerKey): string {
    return getCallbackBaseUrl() . '/api/payment-callback.php?provider=' . urlencode($providerKey);
}

/**
 * Get the default currency.
 *
 * @return string
 */
function getDefaultCurrency(): string {
    return (string) getGatewaySetting('default_currency', 'UGX');
}

/**
 * Get payment timeout in minutes.
 *
 * @return int
 */
function getPaymentTimeoutMinutes(): int {
    return (int) getGatewaySetting('payment_timeout_minutes', 30);
}
