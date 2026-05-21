<?php
/**
 * Unified Authentication Handler for ISNM School Management System
 * Centralized authentication processing for both students and staff
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth-service.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global authentication service
$auth_service = new AuthenticationService();

/**
 * Process authentication requests
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'student_login':
            handleStudentLogin();
            break;
            
        case 'staff_login':
            handleStaffLogin();
            break;
            
        case 'create_student':
            handleCreateStudent();
            break;
            
        case 'create_staff':
            handleCreateStaff();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: index.php');
            exit();
    }
}

/**
 * Handle student login
 */
function handleStudentLogin() {
    global $auth_service;
    
    $index_number = sanitizeInput($_POST['index_number'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone_number = sanitizeInput($_POST['phone'] ?? '');
    
    $result = $auth_service->authenticateStudent($index_number, $full_name, $phone_number);
    
    if ($result['success']) {
        $auth_service->createSecureSession($result['user']);
        $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['full_name'];
        header('Location: dashboards/student.php');
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: student-login.php');
        exit();
    }
}

/**
 * Handle staff login
 */
function handleStaffLogin() {
    global $auth_service;
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    $requestedPosition = $_POST['requested_position'] ?? '';
    
    $result = $auth_service->authenticateStaff($email, $password);
    
    if ($result['success']) {
        $auth_service->createSecureSession($result['user']);
        $_SESSION['success'] = "Login successful! Welcome, " . $result['user']['full_name'];
        
        // Determine dashboard route
        $dashboard = null;
        
        // Priority 1: If coming from organogram position, always go to that position's dashboard
        if (!empty($requestedPosition)) {
            $dashboard = $auth_service->getDashboardRoute($requestedPosition);
        }
        
        // Priority 2: Use user's actual role-based dashboard if no position specified
        if (empty($dashboard)) {
            $dashboard = $auth_service->getDashboardRoute($_SESSION['role']);
        }
        
        // Priority 3: Fallback to CEO dashboard
        if (empty($dashboard)) {
            $dashboard = 'dashboards/ceo.php';
        }

        header("Location: $dashboard");
        exit();
    } else {
        $_SESSION['error'] = 'Invalid email or password';
        // Preserve position parameter on failed login
        $redirectUrl = 'staff-login.php';
        if (!empty($requestedPosition)) {
            $redirectUrl .= '?position=' . urlencode($requestedPosition);
        }
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Handle student account creation
 */
function handleCreateStudent() {
    global $auth_service;
    
    // Check if user is authenticated and has permission
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required';
        header('Location: staff-login.php');
        exit();
    }
    
    if (!$auth_service->canCreateStudents($_SESSION['role'])) {
        $_SESSION['error'] = 'You do not have permission to create student accounts';
        header('Location: dashboards/' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    
    $studentData = [
        'index_number' => $_POST['index_number'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];
    
    $result = $auth_service->createStudentAccount($studentData);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

/**
 * Handle staff account creation
 */
function handleCreateStaff() {
    global $auth_service;
    
    // Check if user is authenticated and has permission
    if (!$auth_service->isAuthenticated()) {
        $_SESSION['error'] = 'Authentication required';
        header('Location: staff-login.php');
        exit();
    }
    
    // Only admin or director roles can create staff accounts
    $userRole = strtolower($_SESSION['role']);
    if (!($auth_service->canCreateStudents($userRole) || strpos($userRole, 'admin') !== false)) {
        $_SESSION['error'] = 'You do not have permission to create staff accounts';
        header('Location: dashboards/' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    
    $staffData = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role'] ?? ''
    ];
    
    $result = $auth_service->createStaffAccount($staffData);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

/**
 * Handle logout
 */
function handleLogout() {
    global $auth_service;
    $auth_service->destroySession();
    $_SESSION['success'] = 'You have been logged out successfully';
    header('Location: index.php');
    exit();
}

/**
 * Note: Helper functions requireAuth(), requireRole(), and getCurrentUser() are defined in
 * security-middleware.php and should be used from there for consistency across the application.
 */

// Handle AJAX-style JSON requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Requirement Portal endpoints
    if (strpos($action, 'requirement_') === 0) {
        header('Content-Type: application/json');
        
        require_once 'includes/requirements_functions.php';
        
        // Check authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['type'] !== 'staff') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        $userName = $_SESSION['full_name'] ?? 'Director';
        
        switch ($action) {
            case 'requirement_clear':
                handleRequirementClear();
                break;
                
            case 'requirement_unclear':
                handleRequirementUnclear();
                break;
                
            case 'requirement_clear_all':
                handleRequirementClearAll();
                break;
                
            case 'requirement_unclear_all':
                handleRequirementUnclearAll();
                break;
                
            case 'requirement_search':
                handleRequirementSearch();
                break;
                
            case 'requirement_filter':
                handleRequirementFilter();
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
        exit();
    }
}

// Handle GET requests for exports
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    require_once 'includes/requirements_functions.php';
    
    if ($_GET['export'] === 'csv') {
        $csv = exportRequirementsToCSV();
        
        if ($csv) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="requirements_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $csv;
            exit();
        }
    }
}

/**
 * Handle clearing a single requirement
 */
function handleRequirementClear() {
    require_once 'includes/requirements_functions.php';
    
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $userName = $_SESSION['full_name'] ?? 'Director';
    
    if ($studentId <= 0 || $itemId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }
    
    if (clearRequirement($studentId, $itemId, $userName)) {
        echo json_encode(['success' => true, 'message' => 'Requirement cleared successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error clearing requirement']);
    }
}

/**
 * Handle unclearing a requirement
 */
function handleRequirementUnclear() {
    require_once 'includes/requirements_functions.php';
    
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $itemId = (int) ($_POST['item_id'] ?? 0);
    
    if ($studentId <= 0 || $itemId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        return;
    }
    
    if (unclearRequirement($studentId, $itemId)) {
        echo json_encode(['success' => true, 'message' => 'Requirement uncleared successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error unclearing requirement']);
    }
}

/**
 * Handle clearing all requirements for a student
 */
function handleRequirementClearAll() {
    require_once 'includes/requirements_functions.php';
    
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $userName = $_SESSION['full_name'] ?? 'Director';
    
    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        return;
    }
    
    $cleared = clearAllRequirements($studentId, $userName);
    
    if ($cleared !== false) {
        echo json_encode(['success' => true, 'message' => "Cleared $cleared requirements"]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error clearing requirements']);
    }
}

/**
 * Handle unclearing all requirements for a student
 */
function handleRequirementUnclearAll() {
    require_once 'includes/requirements_functions.php';
    
    $studentId = (int) ($_POST['student_id'] ?? 0);
    
    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        return;
    }
    
    $conn = getStaffConnection();
    
    try {
        $stmt = $conn->prepare("
            UPDATE student_requirements
            SET is_cleared = 0, cleared_by = NULL, cleared_date = NULL, updated_at = NOW()
            WHERE student_id = ? AND is_cleared = 1
        ");
        
        $stmt->bind_param("i", $studentId);
        $result = $stmt->execute();
        $uncleared = $stmt->affected_rows;
        $stmt->close();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => "Reset $uncleared requirements"]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error resetting requirements']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Handle searching for students
 */
function handleRequirementSearch() {
    require_once 'includes/requirements_functions.php';
    
    $searchTerm = sanitizeInput($_POST['search_term'] ?? '');
    
    if (strlen($searchTerm) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Search term too short']);
        return;
    }
    
    $results = searchStudents($searchTerm);
    echo json_encode(['success' => true, 'data' => $results]);
}

/**
 * Handle filtering students
 */
function handleRequirementFilter() {
    require_once 'includes/requirements_functions.php';
    
    $filterBy = sanitizeInput($_POST['filter_by'] ?? 'all');
    $filterValue = sanitizeInput($_POST['filter_value'] ?? '');
    
    $results = filterStudents($filterBy, $filterValue);
    echo json_encode(['success' => true, 'data' => $results]);
}

?>
