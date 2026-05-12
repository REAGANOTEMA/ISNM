<?php
// Enhanced Dashboard Functions - Mind-Blowing Functionality

// Real-time Notifications System
function getRealTimeNotifications($user_id, $role) {
    try {
        $conn = getDatabaseConnection('website');
        
        $sql = "SELECT * FROM notifications 
                 WHERE user_id = ? OR role_target = ? 
                 AND is_read = 0 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                 ORDER BY created_at DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $role);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return [];
    }
}

// Advanced Search System
function performAdvancedSearch($search_term, $filters = []) {
    try {
        $students_conn = getDatabaseConnection('students');
        $staff_conn = getDatabaseConnection('staffs');
        
        $results = [];
        
        // Search across multiple databases
        $student_sql = "SELECT 'student' as type, student_id, CONCAT(first_name, ' ', surname) as name, email, phone, program as category, status 
                       FROM students 
                       WHERE (first_name LIKE ? OR surname LIKE ? OR student_id LIKE ? OR email LIKE ? OR program LIKE ?)
                       AND status = 'Active'";
        $student_stmt = $students_conn->prepare($student_sql);
        $student_stmt->bind_param("sssss", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%");
        $student_stmt->execute();
        $student_results = $student_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $staff_sql = "SELECT 'staff' as type, staff_id, CONCAT(first_name, ' ', surname) as name, email, phone, position, department as category, status 
                      FROM staff 
                      WHERE (first_name LIKE ? OR surname LIKE ? OR staff_id LIKE ? OR email LIKE ? OR position LIKE ? OR department LIKE ?)
                      AND status = 'Active'";
        $staff_stmt = $staff_conn->prepare($staff_sql);
        $staff_stmt->bind_param("sssss", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%");
        $staff_stmt->execute();
        $staff_results = $staff_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Merge results
        $results = array_merge($student_results, $staff_results);
        
        // Apply additional filters
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $results = array_filter($results, function($item) use ($key, $value) {
                    return isset($item[$key]) && stripos($item[$key], $value) !== false;
                });
            }
        }
        
        return array_values($results);
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        return [];
    }
}

// Document Generation System
function generateAdvancedDocument($type, $data, $template) {
    try {
        $timestamp = date('Y-m-d H:i:s');
        $filename = $type . '_' . $data['id'] . '_' . date('Y-m-d') . '.pdf';
        
        // Create professional PDF
        require_once '../vendor/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document properties
        $pdf->SetCreator('ISNM Enhanced Dashboard');
        $pdf->SetAuthor($data['generated_by']);
        $pdf->SetTitle($type . ' - ' . $data['name']);
        
        // Add professional header
        $pdf->SetHeaderData('', array(
            'L' => array(
                'content' => 'Institute of Strategic Nursing and Midwifery',
                'font-size' => 10,
                'color' => array(0, 0, 0),
                'align' => 'L'
            ),
            'R' => array(
                'content' => $filename,
                'font-size' => 8,
                'color' => array(0, 0, 0),
                'align' => 'R'
            ),
            'line' => 0.2
        ));
        
        // Include template content
        ob_start();
        include $template;
        $content = ob_get_clean();
        
        // Add content to PDF
        $pdf->writeHTML($content, true, false, true, '', '');
        
        // Output PDF
        $pdf->Output($filename, 'D');
        
        // Log document generation
        logDocumentGeneration($type, $data['id'], $data['generated_by']);
    } catch (Exception $e) {
        error_log("Document generation error: " . $e->getMessage());
        return false;
    }
}

// Advanced Analytics System
function getAdvancedAnalytics($timeframe = '30days') {
    try {
        $students_conn = getDatabaseConnection('students');
        $staff_conn = getDatabaseConnection('staffs');
        
        $analytics = [];
        
        // Student analytics
        $student_analytics_sql = "SELECT 
            COUNT(*) as total_students,
            AVG(gpa) as avg_gpa,
            COUNT(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_students,
            COUNT(CASE WHEN status = 'Graduated' THEN 1 ELSE 0 END) as graduated_students,
            COUNT(DISTINCT program) as total_programs
            FROM students 
            WHERE enrollment_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $student_result = $students_conn->query($student_analytics_sql);
        $analytics['students'] = $student_result->fetch_assoc();
        
        // Staff analytics
        $staff_analytics_sql = "SELECT 
            COUNT(*) as total_staff,
            COUNT(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_staff,
            COUNT(DISTINCT department) as total_departments,
            AVG(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) * 100 as retention_rate
            FROM staff 
            WHERE hire_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $staff_result = $staff_conn->query($staff_analytics_sql);
        $analytics['staff'] = $staff_result->fetch_assoc();
        
        // Financial analytics
        $financial_analytics_sql = "SELECT 
            SUM(CASE WHEN fee_status = 'Paid' THEN amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN fee_status = 'Unpaid' THEN amount ELSE 0 END) as outstanding_fees,
            COUNT(*) as total_transactions
            FROM fee_accounts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $financial_result = $students_conn->query($financial_analytics_sql);
        $analytics['financial'] = $financial_result->fetch_assoc();
        
        return $analytics;
    } catch (Exception $e) {
        error_log("Analytics error: " . $e->getMessage());
        return [];
    }
}

// Activity Logging System
function logActivity($activity, $user_id, $details = '') {
    try {
        $conn = getDatabaseConnection('website');
        
        $sql = "INSERT INTO activity_log (activity, user_id, details, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $activity, $user_id, $details);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

// Document Generation Logging
function logDocumentGeneration($document_type, $document_id, $generated_by) {
    try {
        $conn = getDatabaseConnection('website');
        
        $sql = "INSERT INTO document_generation_log (document_type, document_id, generated_by, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $document_type, $document_id, $generated_by);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Document logging error: " . $e->getMessage());
    }
}

// Real-time Dashboard Updates
function getDashboardUpdates($last_update) {
    try {
        $conn = getDatabaseConnection('website');
        
        $sql = "SELECT * FROM dashboard_updates 
                 WHERE created_at > ? 
                 ORDER BY created_at DESC LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $last_update);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Dashboard updates error: " . $e->getMessage());
        return [];
    }
}

// Advanced Filtering System
function applyAdvancedFilters($base_query, $filters) {
    try {
        $filter_clauses = [];
        $params = [];
        $types = '';
        
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case 'date_range':
                        $dates = explode(' - ', $value);
                        $filter_clauses[] = "DATE(created_at) BETWEEN ? AND ?";
                        $params[] = $dates[0];
                        $params[] = $dates[1];
                        $types .= 'ss';
                        break;
                        
                    case 'status':
                        $filter_clauses[] = "status = ?";
                        $params[] = $value;
                        $types .= 's';
                        break;
                        
                    case 'category':
                        $filter_clauses[] = "category = ?";
                        $params[] = $value;
                        $types .= 's';
                        break;
                        
                    case 'amount_range':
                        $amounts = explode(' - ', $value);
                        $filter_clauses[] = "amount BETWEEN ? AND ?";
                        $params[] = $amounts[0];
                        $params[] = $amounts[1];
                        $types .= 'dd';
                        break;
                }
            }
        }
        
        if (!empty($filter_clauses)) {
            $base_query .= ' WHERE ' . implode(' AND ', $filter_clauses);
        }
        
        return [
            'query' => $base_query,
            'params' => $params,
            'types' => $types
        ];
    } catch (Exception $e) {
        error_log("Filter error: " . $e->getMessage());
        return [
            'query' => $base_query,
            'params' => [],
            'types' => ''
        ];
    }
}

// Export System
function exportData($data, $format = 'excel', $filename = '') {
    try {
        $filename = $filename ?: 'export_' . date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'excel':
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
                
                // Create Excel file
                require_once '../vendor/phpexcel/PHPExcel.php';
                $objPHPExcel = new PHPExcel();
                
                // Add headers
                $objPHPExcel->getActiveSheet()->fromArray($data['headers'])->freezePane('A2');
                
                // Add data
                $objPHPExcel->getActiveSheet()->fromArray($data['rows'], null, 'A2');
                
                // Save file
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                $objWriter->save('php://output');
                break;
                
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, $data['headers']);
                foreach ($data['rows'] as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
                break;
                
            case 'pdf':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
                
                // Create PDF
                require_once '../vendor/tcpdf/tcpdf.php';
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->writeHTML($data['content']);
                $pdf->Output($filename, 'D');
                break;
        }
        
        exit();
    } catch (Exception $e) {
        error_log("Export error: " . $e->getMessage());
        return false;
    }
}

// Advanced Security Functions
function validateEnhancedInput($input, $type = 'string', $required = true) {
    try {
        $input = trim($input);
        
        if ($required && empty($input)) {
            return ['valid' => false, 'error' => 'This field is required'];
        }
        
        switch ($type) {
            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    return ['valid' => false, 'error' => 'Invalid email format'];
                }
                break;
                
            case 'phone':
                if (!preg_match('/^[0-9]{10,15}$/', $input)) {
                    return ['valid' => false, 'error' => 'Invalid phone number format'];
                }
                break;
                
            case 'date':
                if (!DateTime::createFromFormat('Y-m-d', $input)) {
                    return ['valid' => false, 'error' => 'Invalid date format'];
                }
                break;
                
            case 'amount':
                if (!is_numeric($input) || $input < 0) {
                    return ['valid' => false, 'error' => 'Invalid amount'];
                }
                break;
        }
        
        return ['valid' => true, 'value' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')];
    } catch (Exception $e) {
        error_log("Validation error: " . $e->getMessage());
        return ['valid' => false, 'error' => 'Validation system error'];
    }
}

// Performance Monitoring
function getPerformanceMetrics($user_id) {
    try {
        $conn = getDatabaseConnection('website');
        
        $sql = "SELECT 
            COUNT(*) as total_actions,
            AVG(TIMESTAMPDIFF(SECOND, created_at, NOW())) as avg_response_time,
            COUNT(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_actions,
            MAX(created_at) as last_action
            FROM user_activity_log 
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Performance metrics error: " . $e->getMessage());
        return [];
    }
}

// Smart Suggestions System
function getSmartSuggestions($user_id, $context = '') {
    try {
        $conn = getDatabaseConnection('website');
        
        $suggestions = [];
        
        // Get user's recent activities
        $activity_sql = "SELECT activity, COUNT(*) as frequency 
                       FROM user_activity_log 
                       WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       GROUP BY activity 
                       ORDER BY frequency DESC LIMIT 5";
        $activity_stmt = $conn->prepare($activity_sql);
        $activity_stmt->bind_param("i", $user_id);
        $activity_stmt->execute();
        $recent_activities = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Generate suggestions based on context
        foreach ($recent_activities as $activity) {
            if (stripos($activity['activity'], 'student') !== false) {
                $suggestions[] = [
                    'type' => 'action',
                    'suggestion' => 'View student statistics',
                    'icon' => 'fa-chart-line',
                    'priority' => 'high'
                ];
            }
            
            if (stripos($activity['activity'], 'report') !== false) {
                $suggestions[] = [
                    'type' => 'action',
                    'suggestion' => 'Generate monthly report',
                    'icon' => 'fa-file-pdf',
                    'priority' => 'medium'
                ];
            }
        }
        
        return array_unique($suggestions, SORT_REGULAR);
    } catch (Exception $e) {
        error_log("Smart suggestions error: " . $e->getMessage());
        return [];
    }
}

// Advanced Error Handling
function handleEnhancedError($error_message, $error_code = 500, $redirect_url = null) {
    try {
        $conn = getDatabaseConnection('website');
        
        // Log error
        $error_sql = "INSERT INTO error_log (error_message, error_code, user_id, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($error_sql);
        $stmt->bind_param("sis", $error_message, $error_code, $_SESSION['user_id'] ?? 0);
        $stmt->execute();
        
        // Set session error
        $_SESSION['error'] = $error_message;
        
        // Redirect if specified
        if ($redirect_url) {
            header("Location: $redirect_url");
            exit();
        }
        
        // Return JSON for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error_message]);
            exit();
        }
    } catch (Exception $e) {
        error_log("Error handling error: " . $e->getMessage());
        return false;
    }
}

// Cache Management
function getCachedData($key, $expiry = 300) {
    try {
        $cache_file = '../cache/' . $key . '.cache';
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $expiry) {
            return json_decode(file_get_contents($cache_file), true);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Cache get error: " . $e->getMessage());
        return null;
    }
}

function setCachedData($key, $data, $expiry = 300) {
    try {
        $cache_file = '../cache/' . $key . '.cache';
        $cache_data = [
            'data' => $data,
            'timestamp' => time(),
            'expiry' => time() + $expiry
        ];
        
        file_put_contents($cache_file, json_encode($cache_data));
    } catch (Exception $e) {
        error_log("Cache set error: " . $e->getMessage());
    }
}

// Advanced Email Notifications
function sendEnhancedNotification($to, $subject, $message, $priority = 'normal') {
    try {
        $headers = [
            'From: ' . 'noreply@isnm.edu',
            'Reply-To: ' . $to,
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: ' . $priority,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $html_message = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0;">
                <h2 style="margin: 0; font-size: 24px;">' . $subject . '</h2>
            </div>
            <div style="padding: 20px; background: #f9f9f9;">
                <p style="font-size: 16px; line-height: 1.5; color: #333;">' . $message . '</p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
                <p style="font-size: 12px; color: #666; text-align: center;">
                    <em>This is an automated message from ISNM Enhanced Dashboard System</em><br>
                    <small>Generated at: ' . date('Y-m-d H:i:s') . '</small>
                </p>
            </div>
        </div>';
        
        return mail($to, $subject, $html_message, $headers);
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        return false;
    }
}

// Real-time Data Sync
function syncRealTimeData($tables = []) {
    try {
        $sync_results = [];
        
        foreach ($tables as $table) {
            $table_config = getTableSyncConfig($table);
            
            if ($table_config) {
                $last_sync = getLastSyncTime($table);
                $current_time = time();
                
                if (($current_time - $last_sync) > $table_config['sync_interval']) {
                    $sync_results[$table] = performTableSync($table, $table_config);
                    updateLastSyncTime($table, $current_time);
                } else {
                    $sync_results[$table] = ['status' => 'up_to_date', 'last_sync' => $last_sync];
                }
            }
        }
        
        return $sync_results;
    } catch (Exception $e) {
        error_log("Sync error: " . $e->getMessage());
        return [];
    }
}

function getTableSyncConfig($table) {
    try {
        $configs = [
            'students' => [
                'sync_interval' => 300, // 5 minutes
                'fields' => ['first_name', 'surname', 'email', 'phone', 'status'],
                'sync_method' => 'incremental'
            ],
            'staff' => [
                'sync_interval' => 600, // 10 minutes
                'fields' => ['first_name', 'surname', 'email', 'phone', 'position', 'department', 'status'],
                'sync_method' => 'incremental'
            ],
            'fee_accounts' => [
                'sync_interval' => 1800, // 30 minutes
                'fields' => ['student_id', 'amount', 'fee_status', 'paid_amount'],
                'sync_method' => 'full'
            ]
        ];
        
        return $configs[$table] ?? null;
    } catch (Exception $e) {
        error_log("Table sync config error: " . $e->getMessage());
        return null;
    }
}

function getLastSyncTime($table) {
    try {
        $sync_file = '../sync/' . $table . '_last_sync.json';
        
        if (file_exists($sync_file)) {
            $sync_data = json_decode(file_get_contents($sync_file), true);
            return $sync_data['last_sync'] ?? 0;
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Get last sync time error: " . $e->getMessage());
        return 0;
    }
}

function updateLastSyncTime($table, $timestamp) {
    try {
        $sync_file = '../sync/' . $table . '_last_sync.json';
        $sync_data = [
            'last_sync' => $timestamp,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($sync_file, json_encode($sync_data));
    } catch (Exception $e) {
        error_log("Update last sync time error: " . $e->getMessage());
    }
}

function performTableSync($table, $config) {
    try {
        $conn = getDatabaseConnection('website');
        
        $sync_result = [
            'status' => 'success',
            'records_synced' => 0,
            'errors' => []
        ];
        
        $last_id = getLastSyncedId($table);
        
        $sql = "SELECT * FROM $table WHERE id > ? ORDER BY id ASC LIMIT 1000";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $last_id);
        $stmt->execute();
        $new_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($new_records as $record) {
            $sync_result['records_synced']++;
            updateLastSyncedId($table, $record['id']);
        }
        
        return $sync_result;
    } catch (Exception $e) {
        error_log("Table sync error: " . $e->getMessage());
        return [
            'status' => 'error',
            'records_synced' => 0,
            'errors' => [$e->getMessage()]
        ];
    }
}

function getLastSyncedId($table) {
    try {
        $sync_file = '../sync/' . $table . '_last_id.json';
        
        if (file_exists($sync_file)) {
            $sync_data = json_decode(file_get_contents($sync_file), true);
            return $sync_data['last_id'] ?? 0;
        }
        
        return 0;
    } catch (Exception $e) {
        error_log("Get last synced ID error: " . $e->getMessage());
        return 0;
    }
}

function updateLastSyncedId($table, $id) {
    try {
        $sync_file = '../sync/' . $table . '_last_id.json';
        $sync_data = [
            'last_id' => $id,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($sync_file, json_encode($sync_data));
    } catch (Exception $e) {
        error_log("Update last synced ID error: " . $e->getMessage());
    }
}

?>
