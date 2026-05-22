<?php
/**
 * HR System Setup & Initialization Script
 * Creates HR user and initializes database
 */

require_once 'config/database.php';

$setup_complete = false;
$messages = array();

try {
    $conn = getStaffConnection();
    
    // 1. Create HR user if not exists
    $hr_email = 'hr@igangaschoolofnursingandmidwifery.ac.ug';
    $hr_password = 'Lovely2God';
    $hr_password_hash = password_hash($hr_password, PASSWORD_BCRYPT);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM hr_users WHERE email = ?");
    $stmt->bind_param('s', $hr_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new HR user
        $stmt = $conn->prepare("
            INSERT INTO hr_users (email, password_hash, full_name, role, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $name = 'HR Manager';
        $role = 'hr_manager';
        $status = 'active';
        
        $stmt->bind_param('sssss', $hr_email, $hr_password_hash, $name, $role, $status);
        if ($stmt->execute()) {
            $messages[] = '✓ HR user created successfully';
            $messages[] = 'Email: ' . $hr_email;
            $messages[] = 'Password: ' . $hr_password;
        } else {
            $messages[] = '✗ Error creating HR user: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $messages[] = '✓ HR user already exists';
    }
    
    // 2. Verify tables exist
    $tables_to_check = array(
        'hr_users', 'staff_records', 'employment_details', 'job_vacancies',
        'job_applications', 'attendance', 'leave_requests', 'staff_appraisals',
        'training_programs', 'staff_training', 'incident_reports', 'employment_contracts',
        'salary_structures', 'payslips', 'hr_activity_logs'
    );
    
    $tables_missing = array();
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            $tables_missing[] = $table;
        }
    }
    
    if (empty($tables_missing)) {
        $messages[] = '✓ All required database tables exist';
    } else {
        $messages[] = '⚠ Missing tables: ' . implode(', ', $tables_missing);
        $messages[] = 'Please run: sql/hr_system.sql';
    }
    
    $conn->close();
    $setup_complete = true;
    
} catch (Exception $e) {
    $messages[] = '✗ Error: ' . $e->getMessage();
    error_log('HR setup error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR System Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            border-left: 4px solid #e74c3c;
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
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
        <h1>👥 HR System Setup</h1>
        <p class="subtitle">Human Resources Management System Initialization</p>
        
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($setup_complete): ?>
            <div class="status success">
                ✓ Setup Completed Successfully!
            </div>
            <div class="actions">
                <a href="staff-login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <div class="status error">
                ✗ Setup encountered issues
            </div>
            <div class="actions">
                <a href="#" onclick="location.reload();" class="btn btn-primary">Retry</a>
                <a href="staff-login.php" class="btn btn-secondary">Skip</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
