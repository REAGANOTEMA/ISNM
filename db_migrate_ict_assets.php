<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ISNM";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
CREATE TABLE IF NOT EXISTS ict_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_number VARCHAR(100) UNIQUE NOT NULL,
    barcode VARCHAR(255) UNIQUE,
    qr_code VARCHAR(255) UNIQUE,
    serial_number VARCHAR(255) UNIQUE NOT NULL,
    brand VARCHAR(255),
    model VARCHAR(255),
    category_id INT,
    purchase_date DATE,
    warranty_expiry DATE,
    current_status ENUM('Active', 'In Maintenance', 'Retired', 'Transferred') DEFAULT 'Active',
    assigned_staff_id INT,
    assigned_department_id INT,
    current_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES ict_asset_categories(id),
    FOREIGN KEY (assigned_staff_id) REFERENCES staff(id), -- Assuming a 'staff' table exists
    FOREIGN KEY (assigned_department_id) REFERENCES departments(id) -- Assuming a 'departments' table exists
);
";

if ($conn->query($sql) === TRUE) {
    echo "Table ict_assets created successfully or already exists\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();

?>
