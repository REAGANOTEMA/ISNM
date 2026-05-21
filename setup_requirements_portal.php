<?php
/**
 * Setup script for Requirements Portal
 * Access via: http://localhost/ISNM.worktrees/agents-organogram-department-navigation/setup_requirements_portal.php
 */

// Check if already setup (optional safety check)
if (php_sapi_name() !== 'cli' && !isset($_GET['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Requirements Portal Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .info { background: #d1ecf1; border: 1px solid #17a2b8; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            button:hover { background: #218838; }
        </style>
    </head>
    <body>
        <h1>Requirements Portal Setup</h1>
        <div class="warning">
            <strong>⚠️ Important:</strong> This will create database tables and add a new staff role.
        </div>
        <div class="info">
            <strong>ℹ️ This setup will:</strong>
            <ul>
                <li>Create <code>requirement_items</code> table with 20 required items</li>
                <li>Create <code>student_requirements</code> table for tracking clearance</li>
                <li>Add "Director of Requirements" role to staff_roles table</li>
                <li>Insert all 20 requirement items (Medical, Office, Cleaning)</li>
            </ul>
        </div>
        <form method="GET">
            <input type="hidden" name="confirm" value="1">
            <button type="submit">✓ Proceed with Setup</button>
        </form>
    </body>
    </html>
    <?php
    exit();
}

require_once 'config/database.php';

$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    echo '<!DOCTYPE html><html><head><title>Setup Results</title><style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    h1, h2 { color: #333; }
    hr { margin: 20px 0; border: none; border-top: 2px solid #ddd; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .success::before { content: "✓ "; font-weight: bold; }
    .error::before { content: "✗ "; font-weight: bold; }
    .completed { background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; }
    ol { line-height: 1.8; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    a { color: #667eea; text-decoration: none; }
    a:hover { text-decoration: underline; }
    </style></head><body>';
}

// Get connections
$staffConn = getStaffConnection();
$studentsConn = getStudentsConnection();

if (!$staffConn || !$studentsConn) {
    die('Database connection failed');
}

if ($isWeb) echo "<h1>Requirements Portal Setup</h1><hr>";

// 1. Create requirement_items table
if ($isWeb) echo "<h2>1. Creating requirement_items table</h2>";
$sql_items = "
CREATE TABLE IF NOT EXISTS requirement_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($staffConn->query($sql_items)) {
    echo ($isWeb ? "<p class='success'>" : "") . "requirement_items table created successfully" . ($isWeb ? "</p>" : "\n");
} else {
    echo ($isWeb ? "<p class='error'>" : "") . "Error creating requirement_items table: " . $staffConn->error . ($isWeb ? "</p>" : "\n");
}

// 2. Create student_requirements table
if ($isWeb) echo "<h2>2. Creating student_requirements table</h2>";
$sql_reqs = "
CREATE TABLE IF NOT EXISTS student_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_admission_number VARCHAR(50),
    student_name VARCHAR(100),
    student_phone VARCHAR(20),
    requirement_item_id INT NOT NULL,
    is_cleared BOOLEAN DEFAULT FALSE,
    cleared_by VARCHAR(100),
    cleared_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requirement_item_id) REFERENCES requirement_items(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_item (requirement_item_id),
    INDEX idx_cleared (is_cleared)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($staffConn->query($sql_reqs)) {
    echo ($isWeb ? "<p class='success'>" : "") . "student_requirements table created successfully" . ($isWeb ? "</p>" : "\n");
} else {
    echo ($isWeb ? "<p class='error'>" : "") . "Error creating student_requirements table: " . $staffConn->error . ($isWeb ? "</p>" : "\n");
}

// 3. Check and add Director of Requirements role
if ($isWeb) echo "<h2>3. Adding 'Director of Requirements' role</h2>";
$checkRole = $staffConn->prepare("SELECT id FROM staff_roles WHERE role_name = ?");
$checkRole->bind_param("s", $roleName);
$roleName = 'Director of Requirements';
$checkRole->execute();
$result = $checkRole->get_result();

if ($result->num_rows === 0) {
    $insertRole = $staffConn->prepare("
        INSERT INTO staff_roles (role_name, dashboard_path, description) 
        VALUES (?, ?, ?)
    ");
    $dashPath = 'dashboards/requirements-director.php';
    $desc = 'Manages student requirements and clearance tracking';
    $insertRole->bind_param("sss", $roleName, $dashPath, $desc);
    
    if ($insertRole->execute()) {
        echo ($isWeb ? "<p class='success'>" : "") . "Director of Requirements role added successfully" . ($isWeb ? "</p>" : "\n");
    } else {
        echo ($isWeb ? "<p class='error'>" : "") . "Error adding role: " . $staffConn->error . ($isWeb ? "</p>" : "\n");
    }
    $insertRole->close();
} else {
    echo ($isWeb ? "<p class='success'>" : "") . "Director of Requirements role already exists" . ($isWeb ? "</p>" : "\n");
}
$checkRole->close();

// 4. Insert 20 requirement items
if ($isWeb) echo "<h2>4. Inserting requirement items</h2>";

$requirements = [
    // Medical
    ['Surgical Gloves', 'Medical'],
    ['Examination Gloves', 'Medical'],
    ['Face Masks', 'Medical'],
    ['Heavy duty Gloves', 'Medical'],
    // Office
    ['Photocopying Ream', 'Office'],
    ['Ruled Paper Reams', 'Office'],
    // Cleaning
    ['Omo', 'Cleaning'],
    ['Toilet Papers', 'Cleaning'],
    ['Compound brooms', 'Cleaning'],
    ['Soft brooms', 'Cleaning'],
    ['Rake', 'Cleaning'],
    ['Cobweb brush', 'Cleaning'],
    ['Scrubbing Brush', 'Cleaning'],
    ['Squeezer', 'Cleaning'],
    ['Toilet Brush', 'Cleaning'],
    ['JIK', 'Cleaning'],
    ['Vim', 'Cleaning'],
    ['Mops', 'Cleaning'],
    ['Sanitizer', 'Cleaning'],
    ['Liquid Soap', 'Cleaning'],
];

$insertStmt = $staffConn->prepare("
    INSERT IGNORE INTO requirement_items (name, category, status) 
    VALUES (?, ?, 'active')
");

$inserted = 0;
$skipped = 0;

if ($isWeb) echo "<ul>";

foreach ($requirements as $req) {
    $name = $req[0];
    $category = $req[1];
    $insertStmt->bind_param("ss", $name, $category);
    
    if ($insertStmt->execute()) {
        if ($insertStmt->affected_rows > 0) {
            if ($isWeb) echo "<li>$name</li>";
            $inserted++;
        } else {
            $skipped++;
        }
    } else {
        echo ($isWeb ? "<li style='color: red;'>" : "") . "Error inserting {$name}: " . $staffConn->error . ($isWeb ? "</li>" : "\n");
    }
}

if ($isWeb) echo "</ul>";

$insertStmt->close();
echo ($isWeb ? "<p class='success'>" : "") . "Inserted {$inserted} new items, {$skipped} already existed" . ($isWeb ? "</p>" : "\n");

// 5. Summary
if ($isWeb) {
    echo "<hr><h2>Setup Completed Successfully</h2>";
    echo "<div class='completed'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Create a staff account with 'Director of Requirements' role</li>";
    echo "<li>Login and access: <a href='dashboards/requirements-director.php'>Requirements Director Dashboard</a></li>";
    echo "<li>The system will auto-import all active students from students_db</li>";
    echo "</ol>";
    echo "</div>";
    echo "</body></html>";
}

$staffConn->close();
$studentsConn->close();
?>

