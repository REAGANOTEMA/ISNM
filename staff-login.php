<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'isnm_school';

// Create connection with error handling
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        // If connection fails, continue without database for login display
        $conn = null;
    }
} catch (Exception $e) {
    // If database connection fails, continue without database for login display
    $conn = null;
}

if (isset($_SESSION['user_id']) && $conn !== null) {
  $user_id = $_SESSION['user_id'];
  
  $query = "SELECT `role`, `first_name`, `last_name` FROM `users` WHERE `user_id`=?";
  $stmt = mysqli_prepare($conn, $query);

  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);

    mysqli_stmt_close($stmt);

    if ($row && isset($row['role'])) {
      $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
      $_SESSION['user_role'] = $row['role'];
      
      // Redirect based on role to appropriate dashboard
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
          header('Location: dashboards/principal.php');
          exit();
        case "Deputy Principal":
          header('Location: dashboards/deputy-principal.php');
          exit();
        case "School Bursar":
          header('Location: dashboards/bursar.php');
          exit();
        case "Academic Registrar":
          header('Location: dashboards/registrar.php');
          exit();
        case "HR Manager":
          header('Location: dashboards/hr-manager.php');
          exit();
        case "School Secretary":
          header('Location: dashboards/secretary.php');
          exit();
        case "School Librarian":
          header('Location: dashboards/librarian.php');
          exit();
        case "Head of Nursing":
          header('Location: dashboards/head-nursing.php');
          exit();
        case "Head of Midwifery":
          header('Location: dashboards/head-midwifery.php');
          exit();
        case "Senior Lecturers":
          header('Location: dashboards/senior-lecturer.php');
          exit();
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
        default:
          // Fallback to admin panel for legacy roles
          header('Location: admin_panel/dashboard.php');
          exit();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>ISNM Staff Login Portal</title>
  <meta name="description" content="Staff Login to ISNM management system">
  <!-- Fontawesome CDN Link -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/isnm-style.css">
  <link rel="stylesheet" href="login-form-style.css">
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  
  <style>
    .staff-login-container {
      max-width: 450px;
      margin: 50px auto;
      padding: 40px;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #ffffff 100%);
      border-radius: 30px;
      box-shadow: 
        0 20px 60px rgba(0,0,0,0.1),
        0 10px 30px rgba(0,0,0,0.08),
        0 5px 15px rgba(0,0,0,0.05),
        inset 0 1px 0 rgba(255,255,255,0.9);
      border: 1px solid rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
    }
    
    .staff-login-logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .staff-login-logo img {
      width: 100px;
      height: 100px;
      object-fit: contain;
      border-radius: 50%;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      background: white;
      padding: 10px;
      border: 3px solid #1e3a8a;
      transition: all 0.3s ease;
    }
    
    .staff-login-logo img:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(30, 58, 138, 0.3);
    }
    
    .staff-login-title {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .staff-login-title h2 {
      color: #1e3a8a;
      font-weight: 700;
      margin-bottom: 10px;
    }
    
    .staff-login-title p {
      color: #6c757d;
      font-size: 14px;
    }
    
    .staff-input-box {
      position: relative;
      margin-bottom: 25px;
    }
    
    .staff-input-box i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      font-size: 18px;
      transition: all 0.3s ease;
      z-index: 2;
    }
    
    .staff-input-box input,
    .staff-input-box select {
      width: 100%;
      padding: 16px 20px 16px 55px;
      border: 2px solid #e9ecef;
      border-radius: 30px;
      font-size: 15px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.06),
        0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .staff-input-box input:focus,
    .staff-input-box select:focus {
      border-color: #1e3a8a;
      background: white;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
      outline: none;
    }
    
    .staff-input-box input:focus + i,
    .staff-input-box select:focus + i {
      color: #1e3a8a;
    }
    
    .staff-login-btn {
      width: 100%;
      padding: 16px 20px;
      border: none;
      border-radius: 30px;
      background: linear-gradient(135deg, #007bff, #6f42c1);
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }
    
    .staff-login-btn:hover {
      background: linear-gradient(135deg, #6f42c1, #e83e8c);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }
    
    .staff-forgot-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .staff-forgot-link a {
      color: #6c757d;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    
    .staff-forgot-link a:hover {
      color: #007bff;
    }
    
    .staff-back-link {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e9ecef;
    }
    
    .staff-back-link a {
      color: #6c757d;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    
    .staff-back-link a:hover {
      color: #007bff;
    }
    
    .staff-action-buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      justify-content: center;
    }
    
    .staff-action-buttons .btn-3d {
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      padding: 10px 20px;
      border: none;
      border-radius: 18px;
      background: linear-gradient(135deg, #007bff, #6f42c1);
      color: white;
      position: relative;
      transform-style: preserve-3d;
      transition: all 0.3s ease;
      box-shadow: 
        0 3px 0 #004085,
        0 4px 8px rgba(0,0,0,0.15);
      text-transform: uppercase;
      letter-spacing: 0.2px;
      overflow: hidden;
      font-size: 0.85rem;
    }
    
    .staff-action-buttons .btn-3d::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #6f42c1, #e83e8c);
      border-radius: 50px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .staff-action-buttons .btn-3d:hover {
      transform: translateY(2px);
      box-shadow: 
        0 4px 0 #004085,
        0 8px 12px rgba(0,0,0,0.25);
    }
    
    .staff-action-buttons .btn-3d:hover::before {
      opacity: 0.3;
    }
    
    .staff-action-buttons .btn-3d:active {
      transform: translateY(4px);
      box-shadow: 
        0 2px 0 #004085,
        0 4px 8px rgba(0,0,0,0.25);
    }
    
    .error-message {
      background: #f8d7da;
      color: #721c24;
      padding: 12px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      text-align: center;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>

<body>
  <div class="staff-login-container">
    <!-- ISNM Logo -->
    <div class="staff-login-logo">
      <img src="images/school-logo.png" alt="ISNM Logo">
    </div>
    
    <!-- Login Title -->
    <div class="staff-login-title">
      <h2>Staff Login Portal</h2>
      <p>Welcome back! Please login to access your dashboard</p>
    </div>
    
    <!-- Error Message -->
    <?php if (isset($_SESSION['error'])): ?>
      <div class="error-message">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>
    
    <!-- Staff Login Form -->
    <form action="process-login.php" method="post">
      <input type="hidden" name="user_type" value="staff">
      
      <!-- Position Selection -->
      <div class="staff-input-box">
        <i class="fas fa-user-tie"></i>
        <select name="position" required>
          <option value="">Select Your Position</option>
          <optgroup label="Executive Leadership">
            <option value="Director General">Director General</option>
            <option value="Chief Executive Officer">Chief Executive Officer</option>
          </optgroup>
          <optgroup label="Directors">
            <option value="Director Academics">Director Academics</option>
            <option value="Director ICT">Director ICT</option>
            <option value="Director Finance">Director Finance</option>
          </optgroup>
          <optgroup label="School Management">
            <option value="School Principal">School Principal</option>
            <option value="Deputy Principal">Deputy Principal</option>
            <option value="School Bursar">School Bursar</option>
          </optgroup>
          <optgroup label="Administrative Staff">
            <option value="Academic Registrar">Academic Registrar</option>
            <option value="HR Manager">HR Manager</option>
            <option value="School Secretary">School Secretary</option>
            <option value="School Librarian">School Librarian</option>
          </optgroup>
          <optgroup label="Academic Staff">
            <option value="Head of Nursing">Head of Nursing</option>
            <option value="Head of Midwifery">Head of Midwifery</option>
            <option value="Senior Lecturers">Senior Lecturers</option>
            <option value="Lecturers">Lecturers</option>
          </optgroup>
          <optgroup label="Support Staff">
            <option value="Matrons">Matrons</option>
            <option value="Lab Technicians">Lab Technicians</option>
            <option value="Drivers">Drivers</option>
            <option value="Security">Security</option>
          </optgroup>
        </select>
      </div>
      
      <!-- Email Field -->
      <div class="staff-input-box">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Enter your email" required>
      </div>
      
      <!-- Password Field -->
      <div class="staff-input-box">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      
      <!-- Login Button -->
      <button type="submit" class="staff-login-btn">
        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
      </button>
    </form>
    
    <!-- Forgot Password -->
    <div class="staff-forgot-link">
      <a href="forgot-password.php">Forgot password?</a>
    </div>
    
    <!-- 3D Action Buttons -->
    <div class="staff-action-buttons">
      <button class="btn-3d" onclick="window.location.href='application.php'">
        <i class="fas fa-rocket me-2"></i>Apply Now
      </button>
      <button class="btn-3d" onclick="window.location.href='login.php'">
        <i class="fas fa-home me-2"></i>Main Login
      </button>
    </div>
    
    <!-- Back to Main Login -->
    <div class="staff-back-link">
      <a href="login.php">← Back to Main Login</a>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
