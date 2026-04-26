<?php
// Universal navigation helper for all ISNM pages
// Ensures perfect connectivity across all pages

function getNavigationMenu($current_page = '') {
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? '';
    
    // Get unread message count for students
    $unread_count = 0;
    if ($user_role === 'Student' && $user_id) {
        global $conn;
        $unread_sql = "SELECT COUNT(*) as count FROM messages WHERE student_id = ? AND status = 'sent'";
        $unread_result = executeQuery($unread_sql, [$user_id], 's');
        $unread_count = $unread_result[0]['count'] ?? 0;
    }
    
    ob_start();
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> ISNM Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if ($user_role !== 'Student'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['students', 'academic_records']) ? 'active' : ''; ?>" href="#" id="studentsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-users"></i> Students
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="student_accounts_management.php">
                                    <i class="fas fa-user-plus"></i> Student Accounts
                                </a></li>
                                <li><a class="dropdown-item" href="academic_records_management.php">
                                    <i class="fas fa-graduation-cap"></i> Academic Records
                                </a></li>
                                <li><a class="dropdown-item" href="student_communication_system.php">
                                    <i class="fas fa-envelope"></i> Messages
                                </a></li>
                                <li><a class="dropdown-item" href="import_student_data.php">
                                    <i class="fas fa-upload"></i> Import Students
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($user_role, ['Director General', 'Chief Executive Officer', 'Director Finance', 'School Bursar'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo $current_page === 'finance' ? 'active' : ''; ?>" href="#" id="financeDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-money-bill"></i> Finance
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="fee_management.php">
                                    <i class="fas fa-credit-card"></i> Fee Management
                                </a></li>
                                <li><a class="dropdown-item" href="bulk_photo_upload.php">
                                    <i class="fas fa-camera"></i> Photo Upload
                                </a></li>
                                <li><a class="dropdown-item" href="financial_reports.php">
                                    <i class="fas fa-chart-line"></i> Financial Reports
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($user_role, ['Director General', 'Chief Executive Officer', 'Director Academics', 'School Principal', 'Academic Registrar'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['applications', 'transcripts']) ? 'active' : ''; ?>" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-graduation-cap"></i> Academics
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="applications.php">
                                    <i class="fas fa-file-alt"></i> Applications
                                </a></li>
                                <li><a class="dropdown-item" href="transcript_management.php">
                                    <i class="fas fa-file-alt"></i> Transcripts
                                </a></li>
                                <li><a class="dropdown-item" href="course_management.php">
                                    <i class="fas fa-book"></i> Course Management
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($user_role, ['Director General', 'Chief Executive Officer', 'HR Manager'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo $current_page === 'hr' ? 'active' : ''; ?>" href="#" id="hrDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-users-cog"></i> HR
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="staff_management.php">
                                    <i class="fas fa-user-tie"></i> Staff Management
                                </a></li>
                                <li><a class="dropdown-item" href="staff_applications.php">
                                    <i class="fas fa-user-plus"></i> Staff Applications
                                </a></li>
                                <li><a class="dropdown-item" href="leave_management.php">
                                    <i class="fas fa-calendar-alt"></i> Leave Management
                                </a></li>
                                <li><a class="dropdown-item" href="payroll.php">
                                    <i class="fas fa-money-check-alt"></i> Payroll
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'messages' ? 'active' : ''; ?>" href="student_communication_system.php">
                            <i class="fas fa-envelope"></i> Messages
                            <?php if ($user_role === 'Student' && $unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="student_reports.php">
                                <i class="fas fa-users"></i> Student Reports
                            </a></li>
                            <li><a class="dropdown-item" href="academic_reports.php">
                                <i class="fas fa-graduation-cap"></i> Academic Reports
                            </a></li>
                            <li><a class="dropdown-item" href="financial_reports.php">
                                <i class="fas fa-money-bill"></i> Financial Reports
                            </a></li>
                            <li><a class="dropdown-item" href="system_reports.php">
                                <i class="fas fa-cog"></i> System Reports
                            </a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="system_settings.php">
                                <i class="fas fa-cogs"></i> System Settings
                            </a></li>
                            <li><a class="dropdown-item" href="user_profile.php">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <style>
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .navbar-brand {
        font-weight: bold;
        color: #ffd700 !important;
    }
    
    .navbar {
        background: linear-gradient(135deg, #1a237e, #3949ab);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .nav-link {
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        background-color: rgba(255,255,255,0.1);
    }
    
    .nav-link.active {
        background-color: rgba(255,255,255,0.2);
    }
    </style>
    <?php
    return ob_get_clean();
}

function getSidebarNavigation($current_page = '') {
    $user_role = $_SESSION['role'] ?? '';
    
    ob_start();
    ?>
    <nav class="sidebar-nav">
        <a href="#overview" class="nav-link <?php echo $current_page === 'overview' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Overview
        </a>
        
        <?php if ($user_role !== 'Student'): ?>
            <a href="#students" class="nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Student Management
            </a>
        <?php endif; ?>
        
        <?php if (in_array($user_role, ['Academic Registrar', 'School Principal', 'Director Academics'])): ?>
            <a href="#academics" class="nav-link <?php echo $current_page === 'academics' ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap"></i> Academics
            </a>
        <?php endif; ?>
        
        <?php if (in_array($user_role, ['Director General', 'Chief Executive Officer', 'Director Finance', 'School Bursar'])): ?>
            <a href="#finance" class="nav-link <?php echo $current_page === 'finance' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill"></i> Finance
            </a>
        <?php endif; ?>
        
        <?php if (in_array($user_role, ['Director General', 'Chief Executive Officer', 'HR Manager'])): ?>
            <a href="#staff" class="nav-link <?php echo $current_page === 'staff' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i> Staff
            </a>
        <?php endif; ?>
        
        <a href="#reports" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        
        <a href="#messages" class="nav-link <?php echo $current_page === 'messages' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Messages
        </a>
        
        <a href="#settings" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
    </nav>
    <?php
    return ob_get_clean();
}

function getFooter() {
    ob_start();
    ?>
    <footer class="main-footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <strong>Copyright &copy; <?php echo date('Y'); ?> Iganga School of Nursing and Midwifery</strong>
                    All rights reserved.
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">Version 2.0 | ISNM Management System</span>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
    .main-footer {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        padding: 1rem 0;
        margin-top: 2rem;
    }
    </style>
    <?php
    return ob_get_clean();
}

function includeCommonStyles() {
    ob_start();
    ?>
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary-color);
        }

        .content-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-content p {
            color: #666;
            margin: 0.5rem 0 0 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                padding: 1rem;
            }
            
            .content-section {
                padding: 1rem;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

function includeCommonScripts() {
    ob_start();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/js/all.min.js"></script>
    
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.style.display !== 'none') {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        // Common functions
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            alertDiv.innerHTML = `
                <i class="fas ${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }, 5000);
        }

        // Format date helper
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
        }

        // Format currency helper
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-UG', {
                style: 'currency',
                currency: 'UGX',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Confirm action helper
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Loading helper
        function showLoading(element) {
            element.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        }

        // AJAX helper
        function ajaxRequest(url, data, callback) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => callback(data))
            .catch(error => {
                console.error('AJAX Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        }
    </script>
    <?php
    return ob_get_clean();
}

function getDashboardTemplate($page_title, $current_page = '') {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page_title); ?> - ISNM</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <?php echo includeCommonStyles(); ?>
    </head>
    <body>
        <?php echo getNavigationMenu($current_page); ?>
        
        <div class="main-container" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
    <?php
    return ob_get_clean();
}

function closeDashboardTemplate() {
    ob_start();
    ?>
        </div>
        <?php echo getFooter(); ?>
        <?php echo includeCommonScripts(); ?>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
