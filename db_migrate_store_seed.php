<?php
/**
 * Store Management - Database Migration & Item Seeding
 * Creates tables and seeds all General Utilities Store + Food Store Supplies
 */
require_once __DIR__ . '/config/database.php';

$conn = getStaffConnection();
if (!$conn) { die("ERROR: Cannot connect to staff database.\n"); }

echo "=== ISNM Store Management Migration ===\n\n";

// ── Step 1: Create/Verify Tables ──
$tables = [
    "CREATE TABLE IF NOT EXISTS store_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-box',
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_cat_name (category_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS store_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_code VARCHAR(30) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        category_id INT DEFAULT 0,
        unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
        quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
        reorder_level INT NOT NULL DEFAULT 10,
        unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
        location VARCHAR(100) DEFAULT NULL,
        batch_number VARCHAR(50) DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        supplier VARCHAR(200) DEFAULT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_inv_category (category_id),
        INDEX idx_inv_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS store_inventory_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        transaction_type VARCHAR(50) NOT NULL,
        quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
        quantity_before DECIMAL(12,2) DEFAULT 0,
        quantity_after DECIMAL(12,2) DEFAULT 0,
        reason TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sit_item (item_id),
        INDEX idx_sit_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS store_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(30) NOT NULL UNIQUE,
        requested_by INT DEFAULT NULL,
        requester_name VARCHAR(200) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        urgency ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
        status ENUM('pending','pending_approval','approved','fulfilled','rejected') NOT NULL DEFAULT 'pending',
        rejection_reason TEXT DEFAULT NULL,
        fulfilled_by INT DEFAULT NULL,
        fulfilled_at TIMESTAMP NULL DEFAULT NULL,
        approved_by INT DEFAULT NULL,
        approved_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sr_status (status),
        INDEX idx_sr_dept (department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS store_request_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity_requested DECIMAL(12,2) NOT NULL DEFAULT 0,
        quantity_fulfilled DECIMAL(12,2) NOT NULL DEFAULT 0,
        notes TEXT DEFAULT NULL,
        status ENUM('pending','partial','fulfilled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sri_request (request_id),
        INDEX idx_sri_item (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS store_requisitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        requisition_number VARCHAR(30) NOT NULL UNIQUE,
        requested_by INT DEFAULT NULL,
        requester_name VARCHAR(200) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity_requested DECIMAL(12,2) NOT NULL DEFAULT 0,
        quantity_approved DECIMAL(12,2) DEFAULT 0,
        unit VARCHAR(30) DEFAULT 'pcs',
        purpose TEXT DEFAULT NULL,
        urgency ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
        status ENUM('pending','approved','fulfilled','rejected') NOT NULL DEFAULT 'pending',
        approved_by INT DEFAULT NULL,
        approved_at TIMESTAMP NULL DEFAULT NULL,
        fulfilled_by INT DEFAULT NULL,
        fulfilled_at TIMESTAMP NULL DEFAULT NULL,
        rejection_reason TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sreq_status (status),
        INDEX idx_sreq_dept (department)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS student_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        student_name VARCHAR(200) DEFAULT NULL,
        registration_number VARCHAR(50) DEFAULT NULL,
        requirement_type VARCHAR(100) NOT NULL,
        document_name VARCHAR(200) DEFAULT NULL,
        file_path VARCHAR(500) DEFAULT NULL,
        status ENUM('pending','submitted','verified','rejected','received','not_received') DEFAULT 'pending',
        submitted_date DATE DEFAULT NULL,
        verified_date DATE DEFAULT NULL,
        verified_by INT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

echo "Step 1: Creating tables...\n";
foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        echo "  ✓ Table created/verified\n";
    } else {
        echo "  ✗ Error: " . $conn->error . "\n";
    }
}

// ── Step 2: Seed Categories ──
echo "\nStep 2: Seeding categories...\n";

$categories = [
    ['General Utilities Store', 'All general stationery, cleaning, electrical, and office supplies', 'fas fa-boxes-stacked'],
    ['Food Store Supplies', 'Kitchen and food service supplies', 'fas fa-utensils'],
];

foreach ($categories as $cat) {
    $stmt = $conn->prepare("INSERT IGNORE INTO store_categories (category_name, description, icon, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("sss", $cat[0], $cat[1], $cat[2]);
    $stmt->execute();
    echo "  ✓ Category: {$cat[0]}\n";
    $stmt->close();
}

// Get category IDs
$r = $conn->query("SELECT id, category_name FROM store_categories WHERE status='active'");
$catMap = [];
while ($row = $r->fetch_assoc()) {
    $catMap[$row['category_name']] = (int)$row['id'];
}
$generalCatId = $catMap['General Utilities Store'] ?? 1;
$foodCatId = $catMap['Food Store Supplies'] ?? 2;

// ── Step 3: Seed General Utilities Store Items ──
echo "\nStep 3: Seeding General Utilities Store items...\n";

$generalItems = [
    ['GU-001', 'Surgical Gloves', 'box', 0, 500, 'Cleaning & Safety'],
    ['GU-002', 'Binding Tape', 'roll', 0, 200, 'Stationery'],
    ['GU-003', 'Examination Gloves', 'box', 0, 500, 'Cleaning & Safety'],
    ['GU-004', 'Masking Tape', 'roll', 0, 200, 'Stationery'],
    ['GU-005', 'Sink Pumps', 'pcs', 0, 50, 'Plumbing'],
    ['GU-006', 'Ruled Reams', 'ream', 0, 1000, 'Stationery'],
    ['GU-007', 'Requirements Clearance Books', 'pcs', 0, 100, 'Stationery'],
    ['GU-008', 'Receipt Books', 'pcs', 0, 100, 'Stationery'],
    ['GU-009', 'Photocopying Reams', 'ream', 0, 1000, 'Stationery'],
    ['GU-010', 'Payment Voucher Books', 'pcs', 0, 100, 'Stationery'],
    ['GU-011', 'Omo', 'kg', 0, 500, 'Cleaning'],
    ['GU-012', 'Binding Rings', 'pack', 0, 200, 'Stationery'],
    ['GU-013', 'Vim', 'kg', 0, 500, 'Cleaning'],
    ['GU-014', 'Ring Binder Files', 'pcs', 0, 200, 'Stationery'],
    ['GU-015', 'Jik', 'litre', 0, 500, 'Cleaning'],
    ['GU-016', 'Envelops', 'pcs', 0, 500, 'Stationery'],
    ['GU-017', 'A3 Paper', 'ream', 0, 500, 'Stationery'],
    ['GU-018', 'A4 Paper', 'ream', 0, 2000, 'Stationery'],
    ['GU-019', 'A5 Paper', 'ream', 0, 500, 'Stationery'],
    ['GU-020', 'Box Files', 'pcs', 0, 200, 'Stationery'],
    ['GU-021', 'Double Gang Switches', 'pcs', 0, 50, 'Electrical'],
    ['GU-022', 'Single Gang Switches', 'pcs', 0, 50, 'Electrical'],
    ['GU-023', 'Counter Books', 'pcs', 0, 200, 'Stationery'],
    ['GU-024', 'Lamp Holders', 'pcs', 0, 50, 'Electrical'],
    ['GU-025', 'Scrubbing Brushes', 'pcs', 0, 100, 'Cleaning'],
    ['GU-026', 'Single Sockets', 'pcs', 0, 50, 'Electrical'],
    ['GU-027', 'Double Sockets', 'pcs', 0, 50, 'Electrical'],
    ['GU-028', 'Squeezers', 'pcs', 0, 50, 'Cleaning'],
    ['GU-029', 'Bulbs', 'pcs', 0, 100, 'Electrical'],
    ['GU-030', 'Mops', 'pcs', 0, 50, 'Cleaning'],
    ['GU-031', 'Mounding Boxes', 'pcs', 0, 50, 'Electrical'],
    ['GU-032', 'Toilet Brushes', 'pcs', 0, 100, 'Cleaning'],
    ['GU-033', 'Markers', 'pcs', 0, 200, 'Stationery'],
    ['GU-034', 'Color Papers', 'pack', 0, 200, 'Stationery'],
    ['GU-035', 'Layer File Trays', 'pcs', 0, 100, 'Stationery'],
    ['GU-036', 'Cobweb Brushes', 'pcs', 0, 50, 'Cleaning'],
    ['GU-037', 'Laminating Paper', 'pack', 0, 200, 'Stationery'],
    ['GU-038', 'Soft Brooms', 'pcs', 0, 100, 'Cleaning'],
    ['GU-039', 'Staple Wires', 'box', 0, 500, 'Stationery'],
    ['GU-040', 'Compound Brooms', 'pcs', 0, 100, 'Cleaning'],
    ['GU-041', 'Paper Clips', 'box', 0, 500, 'Stationery'],
    ['GU-042', 'Rakes', 'pcs', 0, 20, 'Cleaning'],
    ['GU-043', 'PVC Covers', 'pack', 0, 200, 'Stationery'],
    ['GU-044', 'Chalk', 'box', 0, 200, 'Stationery'],
    ['GU-045', 'Atlas Files', 'pcs', 0, 100, 'Stationery'],
    ['GU-046', 'Dormeciliary Kit Bags', 'pcs', 0, 50, 'Medical'],
    ['GU-047', 'Carbon Papers', 'pack', 0, 200, 'Stationery'],
    ['GU-048', 'Blackboard Dusters', 'pcs', 0, 50, 'Stationery'],
    ['GU-049', 'Highlighter Markers', 'pcs', 0, 200, 'Stationery'],
    ['GU-050', 'Liquid Soap', 'litre', 0, 500, 'Cleaning'],
    ['GU-051', 'Pens', 'pcs', 0, 1000, 'Stationery'],
    ['GU-052', 'Rubbers', 'pcs', 0, 200, 'Stationery'],
    ['GU-053', 'Office Glue', 'pcs', 0, 200, 'Stationery'],
    ['GU-054', 'Sticker Notes', 'pack', 0, 200, 'Stationery'],
    ['GU-055', 'Stick Glue', 'pcs', 0, 200, 'Stationery'],
    ['GU-056', 'Toilet Papers', 'pack', 0, 500, 'Cleaning'],
    ['GU-057', 'Insulation Tape', 'roll', 0, 100, 'Electrical'],
];

$inserted = 0;
foreach ($generalItems as $item) {
    $stmt = $conn->prepare("INSERT IGNORE INTO store_inventory (item_code, item_name, category_id, unit, quantity, reorder_level, supplier, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssiiis", $item[0], $item[1], $generalCatId, $item[2], $item[3], $item[4], $item[5]);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted++;
    $stmt->close();
}
echo "  ✓ $inserted General Utilities items inserted\n";

// ── Step 4: Seed Food Store Supplies ──
echo "\nStep 4: Seeding Food Store Supplies...\n";

$foodItems = [
    ['FS-001', 'Posho', 'kg', 0, 1000, 'Food Staples'],
    ['FS-002', 'Rice', 'kg', 0, 500, 'Food Staples'],
    ['FS-003', 'Beans', 'kg', 0, 500, 'Food Staples'],
    ['FS-004', 'Salt', 'kg', 0, 200, 'Condiments'],
    ['FS-005', 'Cooking Oil', 'litre', 0, 500, 'Condiments'],
    ['FS-006', 'Sugar', 'kg', 0, 500, 'Condiments'],
    ['FS-007', 'Plates', 'pcs', 0, 200, 'Kitchenware'],
    ['FS-008', 'Charcoal', 'bag', 0, 100, 'Fuel'],
];

$inserted = 0;
foreach ($foodItems as $item) {
    $stmt = $conn->prepare("INSERT IGNORE INTO store_inventory (item_code, item_name, category_id, unit, quantity, reorder_level, supplier, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssiiis", $item[0], $item[1], $foodCatId, $item[2], $item[3], $item[4], $item[5]);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted++;
    $stmt->close();
}
echo "  ✓ $inserted Food Store items inserted\n";

// ── Step 5: Seed Admission Requirements ──
echo "\nStep 5: Seeding admission requirements for clearance portal...\n";

$admissionReqs = [
    'Surgical Gloves',
    'Examination Gloves',
    'Photocopying Ream',
    'Ruled Paper Reams',
    'Omo',
    'Toilet Papers',
    'Compound Brooms',
    'Soft Brooms',
    'Rake',
    'Cobweb Brush',
    'Scrubbing Brush',
    'Squeezer',
    'Toilet Brush',
    'JIK',
    'Vim',
    'Mops',
    'Sanitizer',
    'Liquid Soap',
    'Face Masks',
    'Heavy Duty Gloves',
];

foreach ($admissionReqs as $i => $reqName) {
    $stmt = $conn->prepare("INSERT IGNORE INTO admission_requirements (requirement_name, type, display_order, is_mandatory, is_active) VALUES (?, 'Other', ?, 1, 1)");
    $order = $i + 1;
    $stmt->bind_param("si", $reqName, $order);
    $stmt->execute();
    $stmt->close();
}
echo "  ✓ " . count($admissionReqs) . " admission requirements seeded\n";

// ── Step 6: Verify ──
echo "\n=== Verification ===\n";
$r = $conn->query("SELECT COUNT(*) c FROM store_categories WHERE status='active'");
echo "  Categories: " . ($r ? $r->fetch_assoc()['c'] : 0) . "\n";
$r = $conn->query("SELECT COUNT(*) c FROM store_inventory WHERE status='active'");
echo "  Inventory Items: " . ($r ? $r->fetch_assoc()['c'] : 0) . "\n";
$r = $conn->query("SELECT c.category_name, COUNT(i.id) cnt FROM store_categories c LEFT JOIN store_inventory i ON c.id=i.category_id AND i.status='active' WHERE c.status='active' GROUP BY c.id");
if ($r) while ($row = $r->fetch_assoc()) {
    echo "  - {$row['category_name']}: {$row['cnt']} items\n";
}

$conn->close();
echo "\n=== Migration Complete ===\n";
