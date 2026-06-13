<?php
require_once __DIR__ . '/config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$connections = [
    'staff' => getStaffConnection(),
    'students' => getStudentsConnection(),
    'website' => getWebsiteConnection(),
    'ict' => getICTConnection(),
];

echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ISNM Database Status</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">';
echo '<style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px}.setup-container{background:white;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:30px;max-width:800px;margin:0 auto}.status-card{border-radius:10px;padding:20px;margin-bottom:20px;border-left:4px solid}.status-success{background:#d4edda;border-color:#28a745;color:#155724}.status-error{background:#f8d7da;border-color:#dc3545;color:#721c24}.status-warning{background:#fff3cd;border-color:#ffc107;color:#856404}</style></head><body>';
echo '<div class="setup-container"><div class="text-center mb-4"><h1><i class="fas fa-database"></i> ISNM Database Status</h1><p>Current hosting database connection status.</p></div>';

foreach ($connections as $name => $conn) {
    if (!$conn) {
        echo '<div class="status-card status-error"><h4><i class="fas fa-times-circle"></i> ' . htmlspecialchars(strtoupper($name)) . ' DB</h4><p>Connection failed. Check .env values in the hosting environment.</p></div>';
        continue;
    }

    $tables = $conn->query('SHOW TABLES');
    $count = $tables ? $tables->num_rows : 0;
    echo '<div class="status-card status-success"><h4><i class="fas fa-check-circle"></i> ' . htmlspecialchars(strtoupper($name)) . ' DB</h4><p>Connected successfully. Tables found: ' . (int) $count . '</p></div>';
    $conn->close();
}

echo '<p class="text-muted small">Database creation and user provisioning should be handled through the hosting control panel. Do not keep setup scripts in production.</p>';
echo '</div></body></html>';
?>
