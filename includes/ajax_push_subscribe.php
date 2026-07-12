<?php
require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id']) || empty($_SESSION['type'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userType = $_SESSION['type'] === 'staff' ? 'staff' : 'student';
$endpoint  = trim($_POST['endpoint'] ?? '');
$authKey   = trim($_POST['auth_key'] ?? '');
$p256dhKey = trim($_POST['p256dh_key'] ?? '');
$deviceType = trim($_POST['device_type'] ?? 'browser');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!$endpoint || !str_starts_with($endpoint, 'https://')) {
    echo json_encode(['success' => false, 'error' => 'Invalid endpoint']);
    exit;
}

$conn = getWebsiteConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    user_type VARCHAR(20) DEFAULT 'staff',
    endpoint TEXT NOT NULL,
    auth_key VARCHAR(255) DEFAULT '',
    p256dh_key VARCHAR(255) DEFAULT '',
    device_type VARCHAR(50) DEFAULT 'browser',
    user_agent TEXT,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id, user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Upsert: if same endpoint exists for this user, update; else insert
$check = $conn->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
if ($check) {
    $check->bind_param('s', $endpoint);
    if (!$check->execute()) { error_log('$check execute failed: ' . ($check->error ?? 'unknown')); };
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        $update = $conn->prepare("UPDATE push_subscriptions SET auth_key=?, p256dh_key=?, device_type=?, user_agent=?, is_active=1, updated_at=NOW() WHERE endpoint=?");
        if ($update) {
            $update->bind_param('sssss', $authKey, $p256dhKey, $deviceType, $userAgent, $endpoint);
            if (!$update->execute()) { error_log('$update execute failed: ' . ($update->error ?? 'unknown')); };
            $update->close();
        }
    } else {
        $check->close();
        $insert = $conn->prepare("INSERT INTO push_subscriptions (user_id, user_type, endpoint, auth_key, p256dh_key, device_type, user_agent, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if ($insert) {
            $insert->bind_param('issssss', $userId, $userType, $endpoint, $authKey, $p256dhKey, $deviceType, $userAgent);
            if (!$insert->execute()) { error_log('$insert execute failed: ' . ($insert->error ?? 'unknown')); };
            $insert->close();
        }
    }
}

$conn->close();

echo json_encode(['success' => true, 'message' => 'Subscription saved']);
