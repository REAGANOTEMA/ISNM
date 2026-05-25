<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard(['storekeeper', 'bursar', 'finance']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

// Enhanced database connections
$students_conn = getStudentsConnection();
$staff_conn = getStaffConnection();

if ($students_conn->connect_error) {
    die("Students DB connection failed: " . $students_conn->connect_error);
}

if ($staff_conn->connect_error) {
    die("Staff DB connection failed: " . $staff_conn->connect_error);
}

// Set charset
$students_conn->set_charset("utf8mb4");
$staff_conn->set_charset("utf8mb4");

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';
$user_role = $_SESSION['role'] ?? '';

// Handle form submissions for inventory management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_quantity':
            handleUpdateQuantity();
            break;
        case 'add_stock':
            handleAddStock();
            break;
        case 'remove_stock':
            handleRemoveStock();
            break;
    }
}

// Function to update quantity directly
function handleUpdateQuantity() {
    global $staff_conn;
    
    $item_id = $_POST['item_id'] ?? 0;
    $new_quantity = $_POST['quantity'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    
    if ($item_id > 0 && is_numeric($new_quantity)) {
        $stmt = $staff_conn->prepare("UPDATE store_inventory SET quantity = ?, unit = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("dsi", $new_quantity, $unit, $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Inventory updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating inventory: " . $stmt->error;
        }
        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Function to add stock
function handleAddStock() {
    global $staff_conn;
    
    $item_id = $_POST['item_id'] ?? 0;
    $quantity_to_add = $_POST['quantity'] ?? 0;
    $reason = $_POST['reason'] ?? 'Stock added';
    
    if ($item_id > 0 && is_numeric($quantity_to_add) && $quantity_to_add > 0) {
        // Get current quantity
        $stmt = $staff_conn->prepare("SELECT quantity FROM store_inventory WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if ($item) {
            $new_quantity = $item['quantity'] + $quantity_to_add;
            
            // Update inventory
            $update_stmt = $staff_conn->prepare("UPDATE store_inventory SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("di", $new_quantity, $item_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Log transaction
            $log_stmt = $staff_conn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by) VALUES (?, 'add', ?, ?, ?)");
            $log_stmt->bind_param("iiss", $item_id, $quantity_to_add, $reason, $user_id);
            $log_stmt->execute();
            $log_stmt->close();
            
            $_SESSION['success_message'] = "Stock added successfully!";
        } else {
            $_SESSION['error_message'] = "Item not found!";
        }
    } else {
        $_SESSION['error_message'] = "Invalid quantity!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Function to remove stock
function handleRemoveStock() {
    global $staff_conn;
    
    $item_id = $_POST['item_id'] ?? 0;
    $quantity_to_remove = $_POST['quantity'] ?? 0;
    $reason = $_POST['reason'] ?? 'Stock removed';
    
    if ($item_id > 0 && is_numeric($quantity_to_remove) && $quantity_to_remove > 0) {
        // Get current quantity
        $stmt = $staff_conn->prepare("SELECT quantity FROM store_inventory WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if ($item && $item['quantity'] >= $quantity_to_remove) {
            $new_quantity = $item['quantity'] - $quantity_to_remove;
            
            // Update inventory
            $update_stmt = $staff_conn->prepare("UPDATE store_inventory SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("di", $new_quantity, $item_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Log transaction
            $log_stmt = $staff_conn->prepare("INSERT INTO store_inventory_transactions (item_id, transaction_type, quantity, reason, created_by) VALUES (?, 'remove', ?, ?, ?)");
            $log_stmt->bind_param("iiss", $item_id, $quantity_to_remove, $reason, $user_id);
            $log_stmt->execute();
            $log_stmt->close();
            
            $_SESSION['success_message'] = "Stock removed successfully!";
        } else {
            $_SESSION['error_message'] = "Insufficient stock or item not found!";
        }
    } else {
        $_SESSION['error_message'] = "Invalid quantity!";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get inventory items
$inventory_items = [];
$result = $staff_conn->query("SELECT * FROM store_inventory ORDER BY category, item_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory_items[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Storekeeper Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <style>
        .inventory-card {
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .inventory-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-bg);
        }
        .item-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-text);
        }
        .item-category {
            background: var(--secondary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        .quantity-display {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin: 15px 0;
            color: var(--primary-color);
        }
        .unit-display {
            display: block;
            text-align: center;
            font-size: 1rem;
            color: var(--dark-text);
            opacity: 0.8;
        }
        .stock-form {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 10px;
            width: 100%;
        }
        .btn-stock {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-add {
            background: var(--success-color);
            color: white;
        }
        .btn-add:hover {
            background: #219653;
            transform: translateY(-2px);
        }
        .btn-remove {
            background: var(--accent-color);
            color: white;
        }
        .btn-remove:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .btn-update {
            background: var(--secondary-color);
            color: white;
        }
        .btn-update:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 8px;
        }
        .category-section {
            margin-bottom: 30px;
        }
        .category-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .category-title i {
            margin-right: 10px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary-color);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .stat-label {
            font-size: 1.1rem;
            color: var(--dark-text);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include("../shared/_header.php"); ?>
    
    <main>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-header text-center mb-4">
                        <h1><i class="fas fa-warehouse"></i> ISNM Storekeeper Dashboard</h1>
                        <p class="text-muted">Manage inventory for General Utilities and Food Store Supplies</p>
                    </div>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Statistics Cards -->
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stat-value"><?php echo count($inventory_items); ?></div>
                                <div class="stat-label">Total Items</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stat-value">
                                    <?php 
                                        $total_quantity = 0;
                                        foreach ($inventory_items as $item) {
                                            $total_quantity += $item['quantity'];
                                        }
                                        echo number_format($total_quantity);
                                    ?>
                                </div>
                                <div class="stat-label">Total Units in Stock</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stat-value"><?php echo $user_name; ?></div>
                                <div class="stat-label">Current User</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stat-value"><?php echo date('M d, Y'); ?></div>
                                <div class="stat-label">Today's Date</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- General Utilities Section -->
                    <div class="col-md-12">
                        <div class="category-section">
                            <h2 class="category-title"><i class="fas fa-tools"></i> General Utilities</h2>
                            <div class="row">
                                <?php 
                                $general_items = array_filter($inventory_items, function($item) {
                                    return strtolower($item['category']) === 'general utilities';
                                });
                                
                                if (empty($general_items)): ?>
                                    <div class="col-md-12">
                                        <p class="text-muted">No general utilities items found.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($general_items as $item): ?>
                                        <div class="col-md-4">
                                            <div class="inventory-card">
                                                <div class="item-header">
                                                    <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                                </div>
                                                <div class="quantity-display"><?php echo number_format($item['quantity']); ?></div>
                                                <div class="unit-display"><?php echo htmlspecialchars($item['unit']); ?></div>
                                                
                                                <form method="POST" action="" class="stock-form">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    
                                                    <div class="form-group">
                                                        <label class="form-label">Quantity:</label>
                                                        <input type="number" name="quantity" class="form-control" value="<?php echo $item['quantity']; ?>" min="0" step="any" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Unit:</label>
                                                        <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($item['unit']); ?>" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-update w-100">Update Quantity</button>
                                                </form>
                                                
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-add me-2" data-bs-toggle="modal" data-bs-target="#addStockModal-<?php echo $item['id']; ?>">
                                                        <i class="fas fa-plus me-1"></i> Add Stock
                                                    </button>
                                                    <button type="button" class="btn btn-remove" data-bs-toggle="modal" data-bs-target="#removeStockModal-<?php echo $item['id']; ?>">
                                                        <i class="fas fa-minus me-1"></i> Remove Stock
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Add Stock Modal -->
                                        <div class="modal fade" id="addStockModal-<?php echo $item['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="add_stock">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Add Stock to <?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Quantity to Add:</label>
                                                                <input type="number" name="quantity" class="form-control" min="1" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason:</label>
                                                                <input type="text" name="reason" class="form-control" placeholder="e.g., New delivery, returned items" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Add Stock</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Remove Stock Modal -->
                                        <div class="modal fade" id="removeStockModal-<?php echo $item['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="remove_stock">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Remove Stock from <?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Quantity to Remove:</label>
                                                                <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $item['quantity']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason:</label>
                                                                <input type="text" name="reason" class="form-control" placeholder="e.g., Usage, damaged, expired" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Remove Stock</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Food Store Supplies Section -->
                    <div class="col-md-12">
                        <div class="category-section">
                            <h2 class="category-title"><i class="fas fa-utensils"></i> Food Store Supplies</h2>
                            <div class="row">
                                <?php 
                                $food_items = array_filter($inventory_items, function($item) {
                                    return strtolower($item['category']) === 'food store supplies';
                                });
                                
                                if (empty($food_items)): ?>
                                    <div class="col-md-12">
                                        <p class="text-muted">No food store supplies items found.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($food_items as $item): ?>
                                        <div class="col-md-4">
                                            <div class="inventory-card">
                                                <div class="item-header">
                                                    <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                                </div>
                                                <div class="quantity-display"><?php echo number_format($item['quantity']); ?></div>
                                                <div class="unit-display"><?php echo htmlspecialchars($item['unit']); ?></div>
                                                
                                                <form method="POST" action="" class="stock-form">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    
                                                    <div class="form-group">
                                                        <label class="form-label">Quantity:</label>
                                                        <input type="number" name="quantity" class="form-control" value="<?php echo $item['quantity']; ?>" min="0" step="any" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Unit:</label>
                                                        <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($item['unit']); ?>" required>
                                                    </div>
                                                    <button type="submit" class="btn btn-update w-100">Update Quantity</button>
                                                </form>
                                                
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-add me-2" data-bs-toggle="modal" data-bs-target="#addFoodStockModal-<?php echo $item['id']; ?>">
                                                        <i class="fas fa-plus me-1"></i> Add Stock
                                                    </button>
                                                    <button type="button" class="btn btn-remove" data-bs-toggle="modal" data-bs-target="#removeFoodStockModal-<?php echo $item['id']; ?>">
                                                        <i class="fas fa-minus me-1"></i> Remove Stock
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Add Stock Modal -->
                                        <div class="modal fade" id="addFoodStockModal-<?php echo $item['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="add_stock">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Add Stock to <?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Quantity to Add:</label>
                                                                <input type="number" name="quantity" class="form-control" min="1" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason:</label>
                                                                <input type="text" name="reason" class="form-control" placeholder="e.g., New delivery, returned items" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Add Stock</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Remove Stock Modal -->
                                        <div class="modal fade" id="removeFoodStockModal-<?php echo $item['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="action" value="remove_stock">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Remove Stock from <?php echo htmlspecialchars($item['item_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Quantity to Remove:</label>
                                                                <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $item['quantity']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason:</label>
                                                                <input type="text" name="reason" class="form-control" placeholder="e.g., Usage, damaged, expired" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Remove Stock</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include("../shared/_footer.php"); ?>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>