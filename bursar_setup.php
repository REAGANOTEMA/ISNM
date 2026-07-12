<?php
/**
 * Bursar Setup & Initialization Script
 * Creates bursar user, sets up database, and initializes system
 */

require_once 'config/database.php';

$setup_complete = false;
$messages = array();

try {
    $conn = getStudentsConnection();
    
    // 1. Create bursar user if not exists
    $bursar_email = isnm_env('BURSAR_EMAIL', 'bursar@igangaschoolofnursingandmidwifery.ac.ug');
    $bursar_password = isnm_env('BURSAR_PASSWORD');
    $bursar_password_hash = password_hash($bursar_password, PASSWORD_BCRYPT);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM bursar_users WHERE email = ?");
    $stmt->bind_param('s', $bursar_email);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new bursar user
        $stmt = $conn->prepare("
            INSERT INTO bursar_users (email, password_hash, full_name, role, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $name = 'Bursar';
        $role = 'bursar';
        $status = 'active';
        
        $stmt->bind_param('sssss', $bursar_email, $bursar_password_hash, $name, $role, $status);
        if ($stmt->execute()) {
            $messages[] = 'âœ“ Bursar user created successfully';
            $messages[] = 'Email: ' . $bursar_email;
            $messages[] = 'Password: ' . $bursar_password;
        } else {
            $messages[] = 'âœ— Error creating bursar user: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $messages[] = 'âœ“ Bursar user already exists';
    }
    
    // 2. Verify tables exist
    $tables_to_check = array(
        'bursar_users', 'programs', 'fee_structures', 'student_fee_assignments',
        'student_invoices', 'payments', 'payment_receipts', 'scholarships',
        'fee_adjustments', 'penalty_configurations', 'budgets', 'expenditures',
        'general_ledger', 'activity_logs'
    );
    
    $tables_missing = array();
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            $tables_missing[] = $table;
        }
    }
    
    if (empty($tables_missing)) {
        $messages[] = 'âœ“ All required database tables exist';
    } else {
        $messages[] = 'âš  Missing tables: ' . implode(', ', $tables_missing);
        $messages[] = 'Please run: sql/students/bursar_system.sql';
    }
    
    // 3. Check and create default programs if not exist
    $result = $conn->query("SELECT COUNT(*) as count FROM programs");
    $row = $result->fetch_assoc();
    
    if ($row['count'] === 0) {
        // Insert default programs
        $default_programs = array(
            array('cm-bsc', 'Bachelor of Science in Nursing (Comprehensive)', 'degree', 'Nursing'),
            array('cn-bsc', 'Bachelor of Science in Nursing (Conversion)', 'degree', 'Nursing'),
            array('dmordn-dip', 'Diploma in Nursing/Midwifery', 'diploma', 'Academic'),
        );
        
        $stmt = $conn->prepare("
            INSERT INTO programs (program_code, program_name, program_level, department, duration_years) 
            VALUES (?, ?, ?, ?, 3)
        ");
        
        foreach ($default_programs as $prog) {
            $stmt->bind_param('ssss', $prog[0], $prog[1], $prog[2], $prog[3]);
            if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        }
        $stmt->close();
        $messages[] = 'âœ“ Default programs created';
    } else {
        $messages[] = 'âœ“ Programs already configured';
    }
    
    $conn->close();
    $setup_complete = true;
    
} catch (Exception $e) {
    $messages[] = 'âœ— Error: ' . $e->getMessage();
    error_log('Bursar setup error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bursar System Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .messages {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 8px;
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            font-size: 13px;
            font-family: monospace;
            word-break: break-word;
        }
        
        .message:last-child {
            margin-bottom: 0;
        }
        
        .status {
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>ðŸ’¼ Bursar System Setup</h1>
        <p class="subtitle">Financial Management System Initialization</p>
        
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($setup_complete): ?>
            <div class="status success">
                âœ“ Setup Completed Successfully!
            </div>
            <div class="actions">
                <a href="bursar_login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="status error">
                âœ— Setup encountered issues
            </div>
            <div class="actions">
                <a href="#" onclick="location.reload();" class="btn btn-primary">Retry</a>
                <a href="bursar_login.php" class="btn btn-secondary">Skip</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
