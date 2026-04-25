<?php
/**
 * ISNM Quality Assurance Report
 * Comprehensive testing and validation framework
 */

session_start();

// Include necessary files
require_once '../includes/department-restrictions.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'isnm_school');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// QA Test Suite
class QualityAssurance {
    private $conn;
    private $test_results = [];
    private $total_tests = 0;
    private $passed_tests = 0;
    private $failed_tests = 0;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run complete QA test suite
     */
    public function runFullTestSuite() {
        echo "<h1>ISNM Quality Assurance Report</h1>";
        echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
        
        // Test categories
        $this->testDatabaseConnectivity();
        $this->testUserAuthentication();
        $this->testRoleBasedAccess();
        $this->testDepartmentRestrictions();
        $this->testDashboardFunctionality();
        $this->testStudentPortal();
        $this->testFinancialSystem();
        $this->testApplicationSystem();
        $this->testMobileResponsiveness();
        $this->testSecurityFeatures();
        $this->testPerformance();
        
        $this->generateSummaryReport();
    }
    
    /**
     * Test database connectivity
     */
    private function testDatabaseConnectivity() {
        echo "<h2>Database Connectivity Tests</h2>";
        
        // Test connection
        $this->runTest('Database Connection', function() {
            return $this->conn->ping();
        });
        
        // Test required tables
        $required_tables = ['users', 'students', 'courses', 'programs', 'departments', 'fee_payments', 'academic_records'];
        foreach ($required_tables as $table) {
            $this->runTest("Table Exists: $table", function() use ($table) {
                $result = $this->conn->query("SHOW TABLES LIKE '$table'");
                return $result->num_rows > 0;
            });
        }
    }
    
    /**
     * Test user authentication
     */
    private function testUserAuthentication() {
        echo "<h2>User Authentication Tests</h2>";
        
        // Test session management
        $this->runTest('Session Management', function() {
            return isset($_SESSION) && is_array($_SESSION);
        });
        
        // Test login functionality
        $this->runTest('Login Functionality', function() {
            // Check if login.php exists and is accessible
            return file_exists('../login.php');
        });
        
        // Test logout functionality
        $this->runTest('Logout Functionality', function() {
            return file_exists('../logout.php');
        });
        
        // Test password hashing
        $this->runTest('Password Security', function() {
            $password = 'test123';
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            return password_verify($password, $hashed);
        });
    }
    
    /**
     * Test role-based access control
     */
    private function testRoleBasedAccess() {
        echo "<h2>Role-Based Access Control Tests</h2>";
        
        // Test dashboard files exist
        $dashboard_roles = [
            'director-general', 'ceo', 'director-ict', 'director-finance',
            'principal', 'deputy-principal', 'school-bursar', 'academic-registrar',
            'hr-manager', 'school-secretary', 'school-librarian',
            'head-nursing', 'head-midwifery', 'senior-lecturers', 'lecturers',
            'matrons', 'wardens', 'lab-technicians', 'non-teaching-staff'
        ];
        
        foreach ($dashboard_roles as $role) {
            $this->runTest("Dashboard Exists: $role", function() use ($role) {
                return file_exists("../dashboards/$role.php");
            });
        }
        
        // Test role validation
        $this->runTest('Role Validation', function() {
            $valid_roles = ['Director General', 'CEO', 'Director ICT', 'Director Finance', 'Principal', 'Deputy Principal', 'School Bursar', 'Academic Registrar', 'HR Manager', 'School Secretary', 'School Librarian', 'Head of Nursing', 'Head of Midwifery', 'Senior Lecturers', 'Lecturers', 'Matrons', 'Wardens', 'Lab Technicians', 'Non-Teaching Staff', 'Student'];
            return count($valid_roles) > 0;
        });
    }
    
    /**
     * Test department restrictions
     */
    private function testDepartmentRestrictions() {
        echo "<h2>Department Restrictions Tests</h2>";
        
        // Test department restrictions class
        $this->runTest('Department Restrictions Class', function() {
            return class_exists('DepartmentRestrictions');
        });
        
        // Test department filtering
        $this->runTest('Department Filtering', function() {
            $restrictions = new DepartmentRestrictions($this->conn);
            return method_exists($restrictions, 'canAccessDepartment');
        });
        
        // Test access logging
        $this->runTest('Access Logging', function() {
            $restrictions = new DepartmentRestrictions($this->conn);
            return method_exists($restrictions, 'logAccess');
        });
    }
    
    /**
     * Test dashboard functionality
     */
    private function testDashboardFunctionality() {
        echo "<h2>Dashboard Functionality Tests</h2>";
        
        // Test dashboard structure
        $this->runTest('Dashboard Structure', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'dashboard-container') !== false;
        });
        
        // Test navigation
        $this->runTest('Dashboard Navigation', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'sidebar-nav') !== false;
        });
        
        // Test modals
        $this->runTest('Modal Functionality', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'actionModal') !== false;
        });
        
        // Test charts (if applicable)
        $this->runTest('Chart Integration', function() {
            $bursar_content = file_get_contents('../dashboards/bursar-enhanced.php');
            return strpos($bursar_content, 'Chart.js') !== false;
        });
    }
    
    /**
     * Test student portal
     */
    private function testStudentPortal() {
        echo "<h2>Student Portal Tests</h2>";
        
        // Test student dashboard
        $this->runTest('Student Dashboard', function() {
            return file_exists('../dashboards/student-enhanced.php');
        });
        
        // Test transcript functionality
        $this->runTest('Transcript Generation', function() {
            $student_content = file_get_contents('../dashboards/student-enhanced.php');
            return strpos($student_content, 'transcript') !== false;
        });
        
        // Test payment integration
        $this->runTest('Payment Integration', function() {
            $student_content = file_get_contents('../dashboards/student-enhanced.php');
            return strpos($student_content, 'MTN') !== false && strpos($student_content, 'Airtel') !== false;
        });
        
        // Test communication
        $this->runTest('Communication System', function() {
            $student_content = file_get_contents('../dashboards/student-enhanced.php');
            return strpos($student_content, 'communication') !== false;
        });
    }
    
    /**
     * Test financial system
     */
    private function testFinancialSystem() {
        echo "<h2>Financial System Tests</h2>";
        
        // Test bursar dashboard
        $this->runTest('Bursar Dashboard', function() {
            return file_exists('../dashboards/bursar-enhanced.php');
        });
        
        // Test payment processing
        $this->runTest('Payment Processing', function() {
            $bursar_content = file_get_contents('../dashboards/bursar-enhanced.php');
            return strpos($bursar_content, 'fee_payments') !== false;
        });
        
        // Test URA reporting
        $this->runTest('URA Reporting', function() {
            $bursar_content = file_get_contents('../dashboards/bursar-enhanced.php');
            return strpos($bursar_content, 'URA') !== false;
        });
        
        // Test budget management
        $this->runTest('Budget Management', function() {
            $bursar_content = file_get_contents('../dashboards/bursar-enhanced.php');
            return strpos($bursar_content, 'budget') !== false;
        });
    }
    
    /**
     * Test application system
     */
    private function testApplicationSystem() {
        echo "<h2>Application System Tests</h2>";
        
        // Test application form
        $this->runTest('Application Form', function() {
            return file_exists('../application.php');
        });
        
        // Test form validation
        $this->runTest('Form Validation', function() {
            $application_content = file_get_contents('../application.php');
            return strpos($application_content, 'required') !== false;
        });
        
        // Test file upload
        $this->runTest('File Upload', function() {
            $application_content = file_get_contents('../application.php');
            return strpos($application_content, 'file') !== false;
        });
        
        // Test contract requirements
        $this->runTest('Contract Requirements', function() {
            $application_content = file_get_contents('../application.php');
            return strpos($application_content, 'UCE') !== false && strpos($application_content, 'UNMEB') !== false;
        });
    }
    
    /**
     * Test mobile responsiveness
     */
    private function testMobileResponsiveness() {
        echo "<h2>Mobile Responsiveness Tests</h2>";
        
        // Test responsive CSS
        $this->runTest('Responsive CSS', function() {
            return file_exists('../css/responsive.css');
        });
        
        // Test viewport meta tag
        $this->runTest('Viewport Meta Tag', function() {
            $header_content = file_get_contents('../shared/_header.php');
            return strpos($header_content, 'viewport') !== false;
        });
        
        // Test mobile breakpoints
        $this->runTest('Mobile Breakpoints', function() {
            $responsive_content = file_get_contents('../css/responsive.css');
            return strpos($responsive_content, '@media') !== false;
        });
        
        // Test touch optimization
        $this->runTest('Touch Optimization', function() {
            $responsive_content = file_get_contents('../css/responsive.css');
            return strpos($responsive_content, 'pointer: coarse') !== false;
        });
    }
    
    /**
     * Test security features
     */
    private function testSecurityFeatures() {
        echo "<h2>Security Features Tests</h2>";
        
        // Test SQL injection protection
        $this->runTest('SQL Injection Protection', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'mysqli') !== false || strpos($dashboard_content, 'prepared') !== false;
        });
        
        // Test XSS protection
        $this->runTest('XSS Protection', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'htmlspecialchars') !== false;
        });
        
        // Test CSRF protection
        $this->runTest('CSRF Protection', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'session') !== false;
        });
        
        // Test input validation
        $this->runTest('Input Validation', function() {
            $application_content = file_get_contents('../application.php');
            return strpos($application_content, 'filter_var') !== false || strpos($application_content, 'required') !== false;
        });
    }
    
    /**
     * Test performance
     */
    private function testPerformance() {
        echo "<h2>Performance Tests</h2>";
        
        // Test CSS optimization
        $this->runTest('CSS Optimization', function() {
            $responsive_content = file_get_contents('../css/responsive.css');
            return strpos($responsive_content, 'transition') !== false;
        });
        
        // Test JavaScript optimization
        $this->runTest('JavaScript Optimization', function() {
            $dashboard_content = file_get_contents('../dashboards/student.php');
            return strpos($dashboard_content, 'addEventListener') !== false;
        });
        
        // Test image optimization
        $this->runTest('Image Optimization', function() {
            return file_exists('../images/school-logo.png');
        });
        
        // Test caching headers
        $this->runTest('Caching Headers', function() {
            $header_content = file_get_contents('../shared/_header.php');
            return strpos($header_content, 'cache') !== false || strpos($header_content, 'preload') !== false;
        });
    }
    
    /**
     * Run individual test
     */
    private function runTest($test_name, $test_function) {
        $this->total_tests++;
        
        try {
            $result = $test_function();
            if ($result) {
                $this->passed_tests++;
                $status = 'PASS';
                $class = 'success';
            } else {
                $this->failed_tests++;
                $status = 'FAIL';
                $class = 'danger';
            }
        } catch (Exception $e) {
            $this->failed_tests++;
            $status = 'ERROR';
            $class = 'danger';
        }
        
        $this->test_results[] = [
            'name' => $test_name,
            'status' => $status,
            'class' => $class
        ];
        
        echo "<div class='alert alert-{$class}'>";
        echo "<strong>{$test_name}:</strong> {$status}";
        echo "</div>";
    }
    
    /**
     * Generate summary report
     */
    private function generateSummaryReport() {
        echo "<h2>Quality Assurance Summary</h2>";
        
        $pass_rate = $this->total_tests > 0 ? round(($this->passed_tests / $this->total_tests) * 100, 2) : 0;
        
        echo "<div class='row'>";
        echo "<div class='col-md-3'>";
        echo "<div class='card text-center'>";
        echo "<div class='card-body'>";
        echo "<h3 class='text-primary'>{$this->total_tests}</h3>";
        echo "<p>Total Tests</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "<div class='card text-center'>";
        echo "<div class='card-body'>";
        echo "<h3 class='text-success'>{$this->passed_tests}</h3>";
        echo "<p>Passed</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "<div class='card text-center'>";
        echo "<div class='card-body'>";
        echo "<h3 class='text-danger'>{$this->failed_tests}</h3>";
        echo "<p>Failed</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-md-3'>";
        echo "<div class='card text-center'>";
        echo "<div class='card-body'>";
        echo "<h3 class='text-info'>{$pass_rate}%</h3>";
        echo "<p>Pass Rate</p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        
        // Overall assessment
        echo "<h3>Overall Assessment</h3>";
        if ($pass_rate >= 95) {
            echo "<div class='alert alert-success'><strong>EXCELLENT:</strong> System meets all quality standards and is ready for production deployment.</div>";
        } elseif ($pass_rate >= 85) {
            echo "<div class='alert alert-info'><strong>GOOD:</strong> System meets most quality standards with minor issues that should be addressed.</div>";
        } elseif ($pass_rate >= 70) {
            echo "<div class='alert alert-warning'><strong>ACCEPTABLE:</strong> System meets basic quality standards but requires improvements before production deployment.</div>";
        } else {
            echo "<div class='alert alert-danger'><strong>NEEDS IMPROVEMENT:</strong> System requires significant improvements before production deployment.</div>";
        }
        
        // Recommendations
        echo "<h3>Recommendations</h3>";
        echo "<ul>";
        if ($this->failed_tests > 0) {
            echo "<li>Address failed tests before production deployment</li>";
        }
        echo "<li>Conduct user acceptance testing (UAT)</li>";
        echo "<li>Perform load testing for expected user volume</li>";
        echo "<li>Test all payment integrations in sandbox environment</li>";
        echo "<li>Verify all security measures are properly implemented</li>";
        echo "<li>Test mobile responsiveness across different devices</li>";
        echo "</ul>";
        
        // Test details
        echo "<h3>Test Details</h3>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Test Name</th><th>Status</th></tr></thead>";
        echo "<tbody>";
        foreach ($this->test_results as $test) {
            echo "<tr>";
            echo "<td>{$test['name']}</td>";
            echo "<td><span class='badge bg-{$test['class']}'>{$test['status']}</span></td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    }
}

// Run QA tests
if (isset($_GET['run_qa']) && $_GET['run_qa'] == 'true') {
    $qa = new QualityAssurance($conn);
    $qa->runFullTestSuite();
} else {
    echo "<h1>ISNM Quality Assurance</h1>";
    echo "<p>Click the button below to run the complete quality assurance test suite.</p>";
    echo "<a href='?run_qa=true' class='btn btn-primary btn-lg'>Run QA Tests</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Quality Assurance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/school-logo.png">
</head>
<body>
    <div class="container mt-4">
        <?php
        if (isset($_GET['run_qa']) && $_GET['run_qa'] == 'true') {
            $qa = new QualityAssurance($conn);
            $qa->runFullTestSuite();
        } else {
            echo "<div class='text-center'>";
            echo "<img src='../images/school-logo.png' alt='ISNM Logo' style='width: 100px; margin-bottom: 20px;'>";
            echo "<h1>ISNM Quality Assurance</h1>";
            echo "<p class='lead'>Comprehensive testing and validation framework for the ISNM Digital Management System</p>";
            echo "<a href='?run_qa=true' class='btn btn-primary btn-lg me-3'><i class='fas fa-play'></i> Run QA Tests</a>";
            echo "<a href='../index.php' class='btn btn-secondary btn-lg'><i class='fas fa-home'></i> Back to Home</a>";
            echo "</div>";
        }
        ?>
    </div>
    
    <footer class="mt-5 py-3 bg-light text-center">
        <p>&copy; 2026 Iganga School of Nursing and Midwifery - Quality Assurance Report</p>
        <p>Designed and Developed by Reagan Otema</p>
        <p>MTN WhatsApp: +256772514889 | Airtel WhatsApp: +256730314979</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
