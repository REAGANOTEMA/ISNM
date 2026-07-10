<?php
require_once __DIR__ . '/config/database.php';
$conn = getICTConnection();
if (!$conn) { die("Connection failed: no ICT database connection"); }

$sql = "
CREATE TABLE IF NOT EXISTS ict_asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
";

if ($conn->query($sql) === TRUE) {
    echo "Table ict_asset_categories created successfully or already exists\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();

?>
