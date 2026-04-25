<?php
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'isnm_school';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = $_POST['position'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($position) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        header('Location: login.php');
        exit();
    }
    
    // Prepare and execute query
    $query = "SELECT `user_id`, `first_name`, `last_name`, `email`, `password`, `role`, `status` FROM `users` WHERE `email` = ? AND `role` = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    mysqli_stmt_bind_param($stmt, "ss", $email, $position);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_array($result)) {
        // Verify password (assuming password is hashed, use password_verify)
        if (password_verify($password, $row['password']) || $password === $row['password']) { // For demo, allow plain text too
            // Check if user is active
            if ($row['status'] === 'active') {
                // Set session variables
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role'];
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                
                // Log activity
                $activity_query = "INSERT INTO `activity_logs` (`user_id`, `user_role`, `activity_type`, `activity_description`, `ip_address`, `activity_date`) VALUES (?, ?, 'login', 'User logged in', ?, NOW())";
                $activity_stmt = mysqli_prepare($conn, $activity_query);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($activity_stmt, "sss", $row['user_id'], $row['role'], $ip_address);
                mysqli_stmt_execute($activity_stmt);
                mysqli_stmt_close($activity_stmt);
                
                // Redirect based on role
                switch ($row['role']) {
                    case "Director General":
                    case "Chief Executive Officer":
                        header('Location: dashboards/director-general.php');
                        exit();
                    case "Director Academics":
                        header('Location: dashboards/director-academics.php');
                        exit();
                    case "Director ICT":
                        header('Location: dashboards/director-ict.php');
                        exit();
                    case "Director Finance":
                        header('Location: dashboards/director-finance.php');
                        exit();
                    case "School Principal":
                        header('Location: dashboards/school-principal.php');
                        exit();
                    case "Deputy Principal":
                        header('Location: dashboards/deputy-principal.php');
                        exit();
                    case "School Bursar":
                        header('Location: dashboards/school-bursar.php');
                        exit();
                    case "Academic Registrar":
                        header('Location: dashboards/academic-registrar.php');
                        exit();
                    case "HR Manager":
                        header('Location: dashboards/hr-manager.php');
                        exit();
                    case "School Secretary":
                        header('Location: dashboards/school-secretary.php');
                        exit();
                    case "School Librarian":
                        header('Location: dashboards/school-librarian.php');
                        exit();
                    case "Head of Nursing":
                        header('Location: dashboards/head-nursing.php');
                        exit();
                    case "Head of Midwifery":
                        header('Location: dashboards/head-midwifery.php');
                        exit();
                    case "Senior Lecturers":
                    case "Lecturers":
                        header('Location: dashboards/lecturer.php');
                        exit();
                    case "Matrons":
                        header('Location: dashboards/matron.php');
                        exit();
                    case "Lab Technicians":
                        header('Location: dashboards/lab-technician.php');
                        exit();
                    case "Drivers":
                        header('Location: dashboards/driver.php');
                        exit();
                    case "Security":
                        header('Location: dashboards/security.php');
                        exit();
                    case "Guild President":
                        header('Location: dashboards/guild-president.php');
                        exit();
                    case "Class Representatives":
                        header('Location: dashboards/class-representative.php');
                        exit();
                    case "Students":
                        header('Location: dashboards/student.php');
                        exit();
                    default:
                        // Fallback to admin panel for legacy roles
                        header('Location: admin_panel/dashboard.php');
                        exit();
                }
            } else {
                $_SESSION['error'] = "Your account is not active. Please contact the administrator.";
                header('Location: login.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "No account found with this email and position";
        header('Location: login.php');
        exit();
    }
    
    mysqli_stmt_close($stmt);
}

$conn->close();
?>
