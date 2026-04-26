<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Student login
    if ($user_type === 'student') {
        $student_role = $_POST['student_role'] ?? '';
        $student_id = $_POST['student_id'] ?? '';
        
        // Validate inputs
        if (empty($student_role) || empty($student_id) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All student fields are required";
            header('Location: student-login.php');
            exit();
        }
        
        // For demo purposes - simulate successful student login
        if ($student_id === 'STU001' && $password === 'student123') {
            $_SESSION['user_id'] = $student_id;
            $_SESSION['user_name'] = 'John Student';
            $_SESSION['user_role'] = $student_role;
            header('Location: dashboards/student.php');
            exit();
        } else {
            $_SESSION['error'] = "Invalid student credentials";
            header('Location: student-login.php');
            exit();
        }
    }
    
    // Staff login
    elseif ($user_type === 'staff') {
        $position = $_POST['position'] ?? '';
        
        // Validate inputs
        if (empty($position) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All staff fields are required";
            header('Location: staff-login.php');
            exit();
        }
        
        // Query database for staff credentials
        $query = "SELECT user_id, first_name, last_name, email, password, role, status FROM users WHERE email = ? AND role = ?";
        $result = executeQuery($query, [$email, $position], 'ss');
        
        if (!empty($result)) {
            $user = $result[0];
            
            // Verify password
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Check if user is active
                if ($user['status'] === 'active') {
                    // Get access level from organizational positions
                    $access_query = "SELECT access_level FROM organizational_positions WHERE position_title = ?";
                    $access_result = executeQuery($access_query, [$position], 's');
                    $access_level = $access_result[0]['access_level'] ?? 1;
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['access_level'] = $access_level;
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    
                    // Log activity
                    logActivity($user['user_id'], $user['role'], 'Login', 'User logged in successfully', 'authentication', $user['user_id']);
                    
                    // Redirect based on position and access level
                    if ($access_level >= 8) {
                        // Top administrators and directors get access to student accounts
                        header('Location: student_accounts_management.php');
                        exit();
                    } else {
                        // Other staff go to their respective dashboards
                        switch ($position) {
                            case "Director General":
                            case "Chief Executive Officer":
                                header('Location: dashboards/director-general.php');
                                break;
                            case "Director Academics":
                                header('Location: dashboards/director-academics.php');
                                break;
                            case "Director ICT":
                                header('Location: dashboards/director-ict.php');
                                break;
                            case "Director Finance":
                                header('Location: dashboards/director-finance.php');
                                break;
                            case "School Principal":
                                header('Location: dashboards/principal.php');
                                break;
                            case "Deputy Principal":
                                header('Location: dashboards/deputy-principal.php');
                                break;
                            case "School Bursar":
                                header('Location: dashboards/bursar.php');
                                break;
                            case "Academic Registrar":
                                header('Location: dashboards/registrar.php');
                                break;
                            case "HR Manager":
                                header('Location: dashboards/hr-manager.php');
                                break;
                            case "School Secretary":
                                header('Location: dashboards/secretary.php');
                                break;
                            case "School Librarian":
                                header('Location: dashboards/librarian.php');
                                break;
                            case "Head of Nursing":
                                header('Location: dashboards/head-nursing.php');
                                break;
                            case "Head of Midwifery":
                                header('Location: dashboards/head-midwifery.php');
                                break;
                            case "Senior Lecturers":
                                header('Location: dashboards/senior-lecturer.php');
                                break;
                            case "Lecturers":
                                header('Location: dashboards/lecturer.php');
                                break;
                            case "Matrons":
                                header('Location: dashboards/matron.php');
                                break;
                            case "Lab Technicians":
                                header('Location: dashboards/lab-technician.php');
                                break;
                            case "Drivers":
                                header('Location: dashboards/driver.php');
                                break;
                            case "Security":
                                header('Location: dashboards/security.php');
                                break;
                            default:
                                header('Location: dashboards/staff-dashboard.php');
                                break;
                        }
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "Your account is not active. Please contact the administrator.";
                    header('Location: staff-login.php');
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid email or password";
                header('Location: staff-login.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "No account found with this email and position";
            header('Location: staff-login.php');
            exit();
        }
    }
    
    // Fallback for old position-based login
    else {
        $position = $_POST['position'] ?? '';
        
        // Validate inputs
        if (empty($position) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All fields are required";
            header('Location: login.php');
            exit();
        }
        
        $_SESSION['error'] = "Please use the new login system";
        header('Location: login.php');
        exit();
    }
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
