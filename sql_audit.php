<?php
/**
 * ISNM ERP Database Audit Script
 * Audits igangaschoolofl_staffs_db (port 3307) for table/column issues.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       ISNM ERP — DATABASE AUDIT REPORT                     ║\n";
echo "║       Database: igangaschoolofl_staffs_db                   ║\n";
echo "║       Server:   localhost:3307  User: root                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ── Connect ──────────────────────────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli('127.0.0.1', 'root', '', 'igangaschoolofl_staffs_db', 3307);
if ($conn->connect_error) {
    die("FATAL: Connection failed — " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');
echo "[OK] Connected to igangaschoolofl_staffs_db on localhost:3307\n\n";

// ── 1. LIST ALL TABLES ───────────────────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════\n";
echo "  SECTION 1: ALL TABLES IN DATABASE\n";
echo "═══════════════════════════════════════════════════════════════\n";

$allTables = [];
$r = $conn->query("SHOW TABLES");
if ($r) {
    while ($row = $r->fetch_row()) {
        $allTables[] = $row[0];
    }
}
sort($allTables);
$tableCount = count($allTables);

echo "Total tables found: $tableCount\n\n";

// Print tables in columns (4 per row)
$cols = 4;
for ($i = 0; $i < $tableCount; $i++) {
    echo str_pad(($i + 1) . ". " . $allTables[$i], 40);
    if (($i + 1) % $cols === 0 || $i === $tableCount - 1) echo "\n";
}

// Build lookup set for fast existence checks
$tableSet = array_flip($allTables);

// Helper: get columns of a table
function getColumns(mysqli $conn, string $table): array {
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cols[$row['Field']] = $row;
        }
    }
    return $cols;
}

// ── 2. DEFINE ALL PHP-CODE TABLE REFERENCES ──────────────────────────────────
// Each entry: table_name => ['db' => 'staffs'|'students'|'ict', 'columns' => [...], 'source' => '...']

$phpRefs = [];

// ═══════════════════════════════════════════════════════════════
// CORE AUTH TABLES (staff DB)
// ═══════════════════════════════════════════════════════════════

$phpRefs['staff'] = [
    'db' => 'staffs', 'source' => 'auth-service.php, auth-handler.php',
    'columns' => [
        'id', 'email', 'full_name', 'phone', 'password', 'role_id',
        'position', 'department', 'status', 'login_attempts', 'locked_until',
        'last_login', 'reset_token', 'reset_expiry', 'updated_at', 'created_at'
    ]
];

$phpRefs['staff_roles'] = [
    'db' => 'staffs', 'source' => 'auth-service.php',
    'columns' => ['id', 'role_name', 'dashboard_path']
];

$phpRefs['staff_login_sessions'] = [
    'db' => 'staffs', 'source' => 'auth-service.php',
    'columns' => ['staff_id', 'session_token', 'ip_address', 'user_agent', 'created_at', 'expires_at']
];

$phpRefs['staff_activity_log'] = [
    'db' => 'staffs', 'source' => 'auth-service.php, dashboards/*.php',
    'columns' => ['staff_id', 'activity_type', 'activity_description', 'module_accessed', 'ip_address', 'user_agent', 'created_at']
];

$phpRefs['users'] = [
    'db' => 'staffs', 'source' => 'config/database.php, includes/auth_functions.php, models/User.php, many legacy files',
    'columns' => [
        'id', 'email', 'username', 'password', 'password_hash', 'role', 'status',
        'first_name', 'last_name', 'surname', 'phone', 'department', 'department_id',
        'index_number', 'user_id', 'login_attempts', 'locked_until', 'last_login', 'theme'
    ]
];

$phpRefs['hr_users'] = [
    'db' => 'staffs', 'source' => 'auth-handler.php, hr_setup.php',
    'columns' => ['id', 'email', 'password_hash', 'full_name', 'role', 'status']
];

// ═══════════════════════════════════════════════════════════════
// PAYROLL TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['payroll_employees'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'staff_id', 'employment_type', 'payment_method', 'bank_name',
        'bank_account_number', 'mobile_money_number', 'tin', 'nssf_number',
        'national_id', 'monthly_salary', 'annual_salary', 'hourly_rate',
        'payroll_status', 'payroll_number', 'created_by', 'created_at'
    ]
];

$phpRefs['payroll_employee_allowances'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_employee_id', 'allowance_type_id', 'amount',
        'is_taxable', 'is_recurring', 'effective_from', 'effective_to',
        'status', 'created_by'
    ]
];

$phpRefs['payroll_employee_deductions'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_employee_id', 'deduction_type_id', 'amount',
        'is_recurring', 'effective_from', 'effective_to', 'status', 'created_by'
    ]
];

$phpRefs['payroll_allowance_types'] = [
    'db' => 'staffs', 'source' => 'includes/payroll_functions.php',
    'columns' => ['id', 'allowance_code', 'allowance_name', 'is_taxable', 'status']
];

$phpRefs['payroll_deduction_types'] = [
    'db' => 'staffs', 'source' => 'includes/payroll_functions.php',
    'columns' => ['id', 'deduction_code', 'deduction_name', 'is_statutory', 'category', 'status']
];

$phpRefs['payroll_overtime'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'payroll_employee_id', 'overtime_type', 'hours_worked', 'rate_multiplier',
        'hourly_rate', 'overtime_date', 'description', 'status', 'created_by',
        'approved_by', 'approved_at', 'total_amount', 'payroll_period_id'
    ]
];

$phpRefs['payroll_bonus'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'payroll_employee_id', 'bonus_type', 'bonus_name', 'amount',
        'is_taxable', 'bonus_date', 'status', 'created_by', 'payroll_period_id'
    ]
];

$phpRefs['payroll_loans'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_employee_id', 'loan_number', 'loan_type',
        'principal_amount', 'interest_rate', 'installments', 'installment_amount',
        'loan_date', 'amount_paid', 'installments_paid', 'status',
        'created_by', 'approved_by', 'approved_at'
    ]
];

$phpRefs['payroll_periods'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'period_code', 'period_name', 'frequency', 'month', 'year',
        'start_date', 'end_date', 'payment_date', 'status', 'is_closed',
        'closed_by', 'closed_at', 'is_locked', 'created_by'
    ]
];

$phpRefs['payroll_runs'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_period_id', 'run_number', 'run_type', 'status',
        'processed_by', 'processed_at', 'approved_by', 'approved_at',
        'paid_by', 'paid_at', 'total_employees', 'total_gross',
        'total_allowances', 'total_deductions', 'total_statutory',
        'total_tax', 'total_nssf', 'total_employer_nssf', 'total_net'
    ]
];

$phpRefs['payroll_items'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_run_id', 'payroll_employee_id', 'staff_id',
        'basic_salary', 'total_allowances', 'total_bonus', 'total_overtime',
        'total_statutory_deductions', 'total_other_deductions', 'paye_tax',
        'nssf_employee', 'nssf_employer', 'net_pay', 'bank_account',
        'mobile_money', 'payment_method', 'status', 'payment_status',
        'payment_date', 'payment_reference'
    ]
];

$phpRefs['payroll_settings'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => ['setting_key', 'setting_value', 'updated_by']
];

$phpRefs['payroll_payslips'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'payroll_item_id', 'payroll_run_id', 'payroll_employee_id',
        'staff_id', 'payslip_number', 'payslip_html', 'pdf_generated',
        'generated_by', 'generated_at'
    ]
];

$phpRefs['payroll_payments'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php',
    'columns' => [
        'id', 'payroll_run_id', 'payment_date', 'payment_method',
        'total_amount', 'employee_count', 'reference_number', 'status', 'processed_by'
    ]
];

$phpRefs['payroll_audit_logs'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => [
        'id', 'staff_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent'
    ]
];

$phpRefs['payroll_approval_history'] = [
    'db' => 'staffs', 'source' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
    'columns' => ['id', 'entity_type', 'entity_id', 'action', 'step', 'comments', 'acted_by', 'acted_at']
];

// ═══════════════════════════════════════════════════════════════
// STORE / INVENTORY TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['store_categories'] = [
    'db' => 'staffs', 'source' => 'store_request.php',
    'columns' => ['id', 'category_name', 'icon', 'status']
];

$phpRefs['store_inventory'] = [
    'db' => 'staffs', 'source' => 'store_request.php, dashboards/storekeeper.php',
    'columns' => ['id', 'category_id', 'item_name', 'unit', 'quantity', 'status']
];

$phpRefs['store_requests'] = [
    'db' => 'staffs', 'source' => 'store_request.php, dashboards/storekeeper.php, dashboards/director-general.php',
    'columns' => [
        'id', 'request_number', 'requested_by', 'department', 'notes',
        'urgency', 'status', 'fulfilled_by', 'fulfilled_at',
        'approval_request_id', 'forwarded_to', 'forwarded_to_role',
        'approved_by', 'approved_at', 'rejection_reason', 'created_at', 'updated_at'
    ]
];

$phpRefs['store_request_items'] = [
    'db' => 'staffs', 'source' => 'store_request.php, dashboards/storekeeper.php',
    'columns' => ['id', 'request_id', 'item_id', 'quantity_requested', 'quantity_fulfilled', 'notes', 'status']
];

$phpRefs['store_inventory_transactions'] = [
    'db' => 'staffs', 'source' => 'dashboards/storekeeper.php',
    'columns' => ['item_id', 'transaction_type', 'quantity', 'quantity_before', 'quantity_after', 'reason', 'created_by', 'reference_type', 'reference_id']
];

// ═══════════════════════════════════════════════════════════════
// SECURITY TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['security_incidents'] = [
    'db' => 'staffs', 'source' => 'handlers/security_handler.php, dashboards/security.php',
    'columns' => ['incident_type', 'location', 'description', 'status', 'reported_by', 'incident_date', 'severity', 'reported_at']
];

$phpRefs['security_patrols'] = [
    'db' => 'staffs', 'source' => 'dashboards/security.php',
    'columns' => ['id', 'guard_id', 'patrol_area', 'patrol_date', 'start_time', 'end_time', 'status', 'notes']
];

$phpRefs['security_equipment'] = [
    'db' => 'staffs', 'source' => 'dashboards/security.php',
    'columns' => ['id', 'equipment_name', 'equipment_type', 'status', 'next_maintenance_date']
];

$phpRefs['security_emergency_contacts'] = [
    'db' => 'staffs', 'source' => 'dashboards/security.php',
    'columns' => ['id', 'contact_name', 'contact_type', 'phone_number', 'email', 'organization', 'is_active']
];

$phpRefs['security_visitors'] = [
    'db' => 'staffs', 'source' => 'dashboards/security.php',
    'columns' => ['id', 'visitor_name', 'visitor_phone', 'visitor_nature', 'person_to_visit_name', 'visit_date', 'expected_arrival', 'actual_arrival', 'expected_departure', 'actual_departure', 'status', 'badge_number', 'notes']
];

$phpRefs['access_control_logs'] = [
    'db' => 'staffs', 'source' => 'dashboards/security.php',
    'columns' => ['id', 'person_name', 'person_type', 'access_point', 'access_time', 'access_type', 'badge_number']
];

// ═══════════════════════════════════════════════════════════════
// STUDENT WELFARE / DISCIPLINE TABLES (staff DB — referenced by handlers/welfare_handler.php)
// ═══════════════════════════════════════════════════════════════

$phpRefs['student_welfare_cases'] = [
    'db' => 'staffs', 'source' => 'handlers/welfare_handler.php, dashboards/*.php',
    'columns' => ['id', 'student_id', 'case_type', 'description', 'case_description', 'immediate_actions', 'status', 'assigned_to', 'reported_by', 'severity', 'created_at']
];

$phpRefs['student_counseling_sessions'] = [
    'db' => 'staffs', 'source' => 'handlers/welfare_handler.php, dashboards/matrons.php, dashboards/wardens.php',
    'columns' => ['id', 'session_id', 'student_id', 'counselor_id', 'session_date', 'session_time', 'session_type', 'issues_discussed', 'location', 'status', 'follow_up_date', 'created_at']
];

$phpRefs['student_discipline'] = [
    'db' => 'staffs', 'source' => 'migration_003_comprehensive_missing_tables.sql',
    'columns' => ['id', 'student_id', 'incident_type', 'offense', 'incident_date', 'action_taken', 'status', 'reported_by', 'created_at']
];

$phpRefs['student_health_incidents'] = [
    'db' => 'staffs', 'source' => 'handlers/welfare_handler.php, dashboards/sickbay.php, dashboards/matrons.php',
    'columns' => ['incident_id', 'student_id', 'incident_date', 'incident_type', 'description', 'actions_taken', 'reported_by', 'resolved', 'created_at']
];

$phpRefs['student_discipline_records'] = [
    'db' => 'staffs', 'source' => 'handlers/welfare_handler.php, dashboards/deputy-principal.php',
    'columns' => ['id', 'student_id', 'incident_date', 'incident_type', 'offense', 'description', 'action_taken', 'reported_by', 'status', 'created_at']
];

// ═══════════════════════════════════════════════════════════════
// HR / LEAVE TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['leave_types'] = [
    'db' => 'staffs', 'source' => 'dashboards/leave-management.php',
    'columns' => ['id', 'type_name', 'description', 'default_days', 'status']
];

$phpRefs['leave_requests'] = [
    'db' => 'staffs', 'source' => 'dashboards/leave-management.php',
    'columns' => ['id', 'staff_id', 'leave_type_id', 'start_date', 'end_date', 'reason', 'status', 'reviewed_by', 'created_at']
];

$phpRefs['leave_balance'] = [
    'db' => 'staffs', 'source' => 'dashboards/leave-management.php',
    'columns' => ['id', 'staff_id', 'leave_type_id', 'year', 'total_days', 'used_days', 'balance_days']
];

$phpRefs['staff_leave_requests'] = [
    'db' => 'staffs', 'source' => 'migration_003_comprehensive_missing_tables.sql',
    'columns' => ['id', 'staff_id', 'leave_type_id', 'start_date', 'end_date', 'reason', 'status', 'approved_by', 'reviewed_by', 'approval_date']
];

$phpRefs['staff_disciplinary'] = [
    'db' => 'staffs', 'source' => 'migration_003_comprehensive_missing_tables.sql',
    'columns' => ['id', 'staff_id', 'incident_date', 'offense_type', 'description', 'action_taken', 'status', 'reported_by']
];

$phpRefs['disciplinary_actions'] = [
    'db' => 'staffs', 'source' => 'migration_003_comprehensive_missing_tables.sql',
    'columns' => ['id', 'staff_id', 'incident_date', 'offense_type', 'description', 'action_taken', 'status', 'reported_by']
];

// ═══════════════════════════════════════════════════════════════
// APPROVAL / WORKFLOW TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['approval_requests'] = [
    'db' => 'staffs', 'source' => 'ajax/approval_action.php, includes/approval_integration.php',
    'columns' => ['id', 'reference_type', 'reference_id', 'status', 'title', 'requester_id', 'requester_name', 'priority']
];

$phpRefs['approval_workflows'] = [
    'db' => 'staffs', 'source' => 'fix_workflows.php',
    'columns' => ['id', 'workflow_name']
];

// ═══════════════════════════════════════════════════════════════
// MISC TABLES FROM migration_003
// ═══════════════════════════════════════════════════════════════

$phpRefs['staff_licenses'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'staff_id', 'license_type', 'license_number', 'issuing_body', 'issue_date', 'expiry_date', 'status', 'document_path']
];

$phpRefs['staff_training'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'staff_id', 'training_name', 'training_type', 'provider', 'start_date', 'end_date', 'status', 'certificate_path', 'cost']
];

$phpRefs['staff_recruitment'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'position_title', 'department', 'description', 'requirements', 'salary_range', 'posted_date', 'closing_date', 'status', 'created_by']
];

$phpRefs['job_applications'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'position_id', 'applicant_name', 'email', 'phone', 'cv_path', 'cover_letter', 'status']
];

$phpRefs['professional_licenses'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'staff_name', 'license_number', 'license_type', 'expiry_date', 'issuing_body', 'created_by']
];

$phpRefs['trainings'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'name', 'description', 'training_type', 'provider', 'start_date', 'end_date', 'max_participants', 'status']
];

$phpRefs['employee_training'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'training_id', 'staff_id', 'status', 'completion_date', 'certificate_path']
];

$phpRefs['onboarding_checklist'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'item_name', 'department', 'created_by', 'status']
];

$phpRefs['access_logs'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'user_id', 'user_type', 'action', 'module', 'ip_address', 'user_agent']
];

$phpRefs['cache_data'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'cache_key', 'cache_value', 'expires_at']
];

$phpRefs['attendance_status'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'staff_id', 'attendance_date', 'check_in_time', 'check_out_time', 'status', 'notes']
];

$phpRefs['student_activities'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'title', 'activity_name', 'activity_type', 'activity_date', 'expected_participants', 'location', 'description', 'status']
];

$phpRefs['skills_laboratory'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'lab_name', 'location', 'capacity', 'status']
];

$phpRefs['examination_results'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'course_id', 'semester', 'academic_year', 'ca_score', 'exam_score', 'total_score', 'grade', 'status', 'entered_by']
];

$phpRefs['result_approvals'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'result_id', 'approved_by', 'approval_date', 'status', 'comments']
];

$phpRefs['teaching_resources'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'title', 'resource_type', 'course_id', 'file_path', 'description', 'uploaded_by', 'status']
];

$phpRefs['grading_approval_workflow_log'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'result_id', 'action', 'acted_by', 'comments']
];

$phpRefs['trip_logs'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'vehicle_id', 'driver_id', 'trip_date', 'destination', 'purpose', 'start_km', 'end_km', 'fuel_cost', 'status']
];

$phpRefs['clinical_training'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'rotation_type', 'department', 'start_date', 'end_date', 'supervisor', 'status', 'evaluation_score', 'notes']
];

$phpRefs['assessments'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'title', 'course_id', 'assessment_type', 'total_marks', 'due_date', 'status', 'created_by']
];

$phpRefs['assessment_scores'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'assessment_id', 'student_id', 'score', 'feedback', 'graded_by']
];

$phpRefs['hostel_inspections'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'hostel_room_id', 'inspection_date', 'inspected_by', 'condition_rating', 'cleanliness_rating', 'findings', 'recommendations', 'status']
];

$phpRefs['hostel_maintenance_requests'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'hostel_room_id', 'requested_by', 'issue_type', 'description', 'priority', 'status', 'assigned_to', 'completed_at']
];

$phpRefs['hostel_clearance'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'hostel_allocation_id', 'cleared_by', 'clearance_date', 'condition_notes', 'key_returned', 'status']
];

$phpRefs['lab_equipment'] = [
    'db' => 'staffs', 'source' => 'migration_003, handlers/lab_handler.php',
    'columns' => [
        'id', 'equipment_code', 'name', 'equipment_name', 'equipment_type', 'description', 'category',
        'quantity', 'available_quantity', 'condition_status', 'location', 'serial_number',
        'brand', 'model', 'lab_room_id', 'purchase_date', 'purchase_cost', 'warranty_expiry',
        'supplier', 'status', 'last_maintenance_date', 'next_maintenance_date', 'notes'
    ]
];

$phpRefs['lab_equipment_checkout'] = [
    'db' => 'staffs', 'source' => 'handlers/lab_handler.php',
    'columns' => [
        'id', 'equipment_id', 'checked_out_to', 'borrower_type', 'borrower_id',
        'checkout_date', 'expected_return', 'actual_return', 'condition_at_return',
        'checked_out_by', 'status'
    ]
];

$phpRefs['lab_consumables'] = [
    'db' => 'staffs', 'source' => 'migration_003, handlers/lab_handler.php',
    'columns' => ['id', 'item_name', 'item_category', 'category', 'quantity', 'reorder_level', 'unit_cost', 'supplier', 'min_stock_level', 'unit', 'notes']
];

$phpRefs['lab_attendance'] = [
    'db' => 'staffs', 'source' => 'migration_003, handlers/lab_handler.php',
    'columns' => ['id', 'student_id', 'lab_room_id', 'session_id', 'attendance_date', 'time_in', 'computer_id', 'status', 'marked_by']
];

$phpRefs['lab_incidents'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'incident_date', 'incident_time', 'reported_by', 'incident_type', 'severity', 'description', 'equipment_involved', 'student_involved', 'action_taken', 'status']
];

$phpRefs['lab_id_card_requests'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'request_type', 'reason', 'status', 'requested_by', 'processed_by']
];

$phpRefs['lab_printing_jobs'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'document_name', 'pages', 'copies', 'cost', 'status', 'requested_by']
];

$phpRefs['lab_practical_sessions'] = [
    'db' => 'staffs', 'source' => 'migration_003, handlers/lab_handler.php',
    'columns' => [
        'id', 'session_code', 'title', 'course_name', 'instructor_name', 'description',
        'instructor', 'program', 'year', 'year_level', 'semester', 'lab_room_id',
        'session_date', 'start_time', 'end_time', 'location', 'max_students',
        'status', 'notes', 'created_by'
    ]
];

$phpRefs['lab_skills_demonstrations'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'student_id', 'skill_name', 'skill_category', 'instructor', 'date_demonstrated', 'competency', 'attempt_number', 'notes', 'next_review_date', 'verified_by']
];

// ═══════════════════════════════════════════════════════════════
// STORE ORDER TABLES
// ═══════════════════════════════════════════════════════════════

$phpRefs['store_order_items'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'order_id', 'item_id', 'quantity_ordered', 'quantity_received', 'unit_price', 'status']
];

// ═══════════════════════════════════════════════════════════════
// VISITOR / ACCESS
// ═══════════════════════════════════════════════════════════════

$phpRefs['visitor_logs'] = [
    'db' => 'staffs', 'source' => 'migration_003',
    'columns' => ['id', 'visitor_name', 'visitor_phone', 'purpose', 'person_to_visit', 'check_in_time', 'check_out_time', 'badge_number']
];

// ── 3. RUN AUDIT ─────────────────────────────────────────────────────────────
echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "  SECTION 2: TABLE & COLUMN AUDIT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$missingTables = [];
$missingColumns = [];
$columnTypeIssues = [];
$okTables = [];

foreach ($phpRefs as $tableName => $info) {
    $expectedCols = $info['columns'];
    $source = $info['source'];

    if (!isset($tableSet[$tableName])) {
        $missingTables[] = [
            'table' => $tableName,
            'expected_columns' => $expectedCols,
            'source' => $source
        ];
        continue;
    }

    $actualCols = getColumns($conn, $tableName);
    $tableMissing = [];

    foreach ($expectedCols as $col) {
        if (!isset($actualCols[$col])) {
            $tableMissing[] = $col;
        }
    }

    if (!empty($tableMissing)) {
        $missingColumns[] = [
            'table' => $tableName,
            'missing' => $tableMissing,
            'actual_cols' => array_keys($actualCols),
            'source' => $source
        ];
    } else {
        $okTables[] = $tableName;
    }
}

// ── Print missing tables ─────────────────────────────────────────────────────
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│  TABLES REFERENCED BY PHP CODE BUT DO NOT EXIST             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

if (empty($missingTables)) {
    echo "  ✅ All referenced tables exist.\n\n";
} else {
    echo "  ❌ " . count($missingTables) . " table(s) MISSING:\n\n";
    foreach ($missingTables as $mt) {
        echo "  ┌─ TABLE: {$mt['table']}\n";
        echo "  │  Source: {$mt['source']}\n";
        echo "  │  Expected columns (" . count($mt['expected_columns']) . "):\n";
        foreach ($mt['expected_columns'] as $col) {
            echo "  │    • $col\n";
        }
        echo "  └──────────────────────────────────────\n\n";
    }
}

// ── Print missing columns ────────────────────────────────────────────────────
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│  TABLES WITH MISSING COLUMNS                                │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

if (empty($missingColumns)) {
    echo "  ✅ All columns exist in referenced tables.\n\n";
} else {
    echo "  ❌ " . count($missingColumns) . " table(s) have missing columns:\n\n";
    foreach ($missingColumns as $mc) {
        echo "  ┌─ TABLE: {$mc['table']}\n";
        echo "  │  Source: {$mc['source']}\n";
        echo "  │  MISSING columns (" . count($mc['missing']) . "):\n";
        foreach ($mc['missing'] as $col) {
            echo "  │    ✗ $col\n";
        }
        echo "  │  Actual columns in DB (" . count($mc['actual_cols']) . "):\n";
        $shown = 0;
        foreach ($mc['actual_cols'] as $col) {
            echo "  │    ✓ $col\n";
            $shown++;
            if ($shown >= 20 && count($mc['actual_cols']) > 25) {
                echo "  │    ... and " . (count($mc['actual_cols']) - 20) . " more\n";
                break;
            }
        }
        echo "  └──────────────────────────────────────\n\n";
    }
}

// ── Print OK tables ──────────────────────────────────────────────────────────
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│  TABLES WITH ALL COLUMNS PRESENT (OK)                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n\n";

echo "  ✅ " . count($okTables) . " table(s) verified OK:\n\n";
foreach ($okTables as $t) {
    echo "    • $t\n";
}
echo "\n";

// ── 4. SPECIFIC CRITICAL TABLE CHECKS ────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════\n";
echo "  SECTION 3: CRITICAL TABLE DEEP DIVE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$criticalTables = [
    'staff' => [
        'used_in' => 'auth-service.php (authentication, session, password reset, staff creation)',
        'expected' => [
            'id' => 'INT PK AUTO_INCREMENT',
            'email' => 'VARCHAR — login identifier',
            'full_name' => 'VARCHAR — display name',
            'phone' => 'VARCHAR',
            'password' => 'VARCHAR — bcrypt hash',
            'role_id' => 'INT FK → staff_roles.id',
            'position' => 'VARCHAR',
            'department' => 'VARCHAR',
            'status' => "ENUM('Active','Inactive') or VARCHAR",
            'login_attempts' => 'INT — brute-force protection',
            'locked_until' => 'DATETIME — lockout timestamp',
            'last_login' => 'DATETIME',
            'reset_token' => 'VARCHAR — password reset',
            'reset_expiry' => 'DATETIME — token expiry',
            'updated_at' => 'TIMESTAMP',
            'created_at' => 'TIMESTAMP',
        ]
    ],
    'users' => [
        'used_in' => 'config/database.php, includes/auth_functions.php, models/User.php, 40+ legacy files',
        'expected' => [
            'id' => 'INT PK',
            'email' => 'VARCHAR',
            'username' => 'VARCHAR',
            'password' => 'VARCHAR',
            'password_hash' => 'VARCHAR',
            'role' => 'VARCHAR',
            'status' => 'VARCHAR',
            'first_name' => 'VARCHAR',
            'last_name' => 'VARCHAR',
            'phone' => 'VARCHAR',
            'department' => 'VARCHAR',
            'department_id' => 'INT',
            'index_number' => 'VARCHAR',
            'user_id' => 'INT',
            'login_attempts' => 'INT',
            'locked_until' => 'DATETIME',
            'last_login' => 'DATETIME',
            'theme' => 'VARCHAR',
        ]
    ],
    'students' => [
        'used_in' => 'auth-service.php (student login, account creation)',
        'note' => 'This table is in students_db, NOT staffs_db. Skipping column check for staffs_db.',
        'expected' => [
            'id', 'index_number', 'first_name', 'surname', 'other_name', 'email',
            'phone', 'program', 'level', 'set_name', 'status', 'is_first_login',
            'password_changed', 'login_attempts', 'locked_until', 'last_login',
            'password', 'student_number', 'created_at', 'updated_at',
            'intake_year', 'intake_period'
        ]
    ],
    'payroll_employees' => [
        'used_in' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
        'expected' => [
            'id', 'staff_id', 'employment_type', 'payment_method', 'bank_name',
            'bank_account_number', 'mobile_money_number', 'tin', 'nssf_number',
            'national_id', 'monthly_salary', 'annual_salary', 'hourly_rate',
            'payroll_status', 'payroll_number', 'created_by'
        ]
    ],
    'payroll_runs' => [
        'used_in' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
        'expected' => [
            'id', 'payroll_period_id', 'run_number', 'run_type', 'status',
            'processed_by', 'processed_at', 'approved_by', 'approved_at',
            'paid_by', 'paid_at', 'total_employees', 'total_gross',
            'total_allowances', 'total_deductions', 'total_statutory',
            'total_tax', 'total_nssf', 'total_employer_nssf', 'total_net'
        ]
    ],
    'payroll_settings' => [
        'used_in' => 'handlers/payroll_handler.php, includes/payroll_functions.php',
        'expected' => ['setting_key', 'setting_value', 'updated_by']
    ],
    'leave_requests' => [
        'used_in' => 'dashboards/leave-management.php',
        'expected' => ['id', 'staff_id', 'leave_type_id', 'start_date', 'end_date', 'reason', 'status', 'reviewed_by']
    ],
    'leave_balance' => [
        'used_in' => 'dashboards/leave-management.php',
        'expected' => ['id', 'staff_id', 'leave_type_id', 'year', 'total_days', 'used_days', 'balance_days']
    ],
    'leave_types' => [
        'used_in' => 'dashboards/leave-management.php',
        'expected' => ['id', 'type_name', 'description', 'default_days', 'status']
    ],
    'store_requests' => [
        'used_in' => 'store_request.php, dashboards/storekeeper.php',
        'expected' => [
            'id', 'request_number', 'requested_by', 'department', 'notes',
            'urgency', 'status', 'fulfilled_by', 'fulfilled_at',
            'approval_request_id', 'forwarded_to', 'forwarded_to_role',
            'approved_by', 'approved_at', 'rejection_reason'
        ]
    ],
    'store_inventory' => [
        'used_in' => 'store_request.php, dashboards/storekeeper.php',
        'expected' => ['id', 'category_id', 'item_name', 'unit', 'quantity', 'status']
    ],
    'security_incidents' => [
        'used_in' => 'handlers/security_handler.php, dashboards/security.php',
        'expected' => ['incident_type', 'location', 'description', 'status', 'reported_by', 'incident_date', 'severity', 'reported_at']
    ],
    'student_welfare_cases' => [
        'used_in' => 'handlers/welfare_handler.php, dashboards/counseling-welfare.php, dashboards/matrons.php, dashboards/wardens.php',
        'expected' => ['id', 'student_id', 'case_type', 'description', 'status', 'assigned_to', 'severity']
    ],
    'student_counseling_sessions' => [
        'used_in' => 'handlers/welfare_handler.php, dashboards/matrons.php, dashboards/wardens.php',
        'expected' => ['id', 'session_id', 'student_id', 'counselor_id', 'session_date', 'session_time', 'session_type', 'issues_discussed', 'status']
    ],
    'student_discipline' => [
        'used_in' => 'migration_003, dashboards/student-discipline.php',
        'expected' => ['id', 'student_id', 'incident_type', 'offense', 'incident_date', 'action_taken', 'status', 'reported_by']
    ],
];

foreach ($criticalTables as $tableName => $info) {
    echo "  ┌─ $tableName\n";
    echo "  │  Used in: {$info['used_in']}\n";

    if (isset($info['note'])) {
        echo "  │  ⚠  {$info['note']}\n";
        echo "  └──────────────────────────────────────\n\n";
        continue;
    }

    if (!isset($tableSet[$tableName])) {
        echo "  │  ❌ TABLE DOES NOT EXIST IN DATABASE!\n";
        echo "  │  Required columns:\n";
        foreach ($info['expected'] as $col => $type) {
            if (is_int($col)) {
                echo "  │    ✗ $type\n";
            } else {
                echo "  │    ✗ $col — $type\n";
            }
        }
        echo "  └──────────────────────────────────────\n\n";
        continue;
    }

    $actualCols = getColumns($conn, $tableName);
    $allPresent = true;
    foreach ($info['expected'] as $col => $type) {
        $colName = is_int($col) ? $type : $col;
        $colType = is_int($col) ? '' : $type;
        if (!isset($actualCols[$colName])) {
            echo "  │  ✗ MISSING: $colName";
            if ($colType) echo " — expected $colType";
            echo "\n";
            $allPresent = false;
        }
    }

    if ($allPresent) {
        echo "  │  ✅ All " . count($info['expected']) . " expected columns present\n";
    }
    echo "  └──────────────────────────────────────\n\n";
}

// ── 5. SUMMARY ───────────────────────────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalChecked = count($phpRefs);
$totalMissingTables = count($missingTables);
$totalMissingCols = 0;
foreach ($missingColumns as $mc) {
    $totalMissingCols += count($mc['missing']);
}

echo "  Tables in database:              $tableCount\n";
echo "  PHP-referenced tables checked:    $totalChecked\n";
echo "  Tables verified OK:               " . count($okTables) . "\n";
echo "  Tables MISSING:                   $totalMissingTables\n";
echo "  Total MISSING columns:            $totalMissingCols\n";

if ($totalMissingTables > 0 || $totalMissingCols > 0) {
    echo "\n  ⚠  ACTION REQUIRED: Missing tables/columns will cause runtime errors.\n";
} else {
    echo "\n  ✅ All referenced tables and columns are present.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  AUDIT COMPLETE — " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

$conn->close();
