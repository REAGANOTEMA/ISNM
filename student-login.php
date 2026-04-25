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
      
      // Redirect to student dashboard
      if ($row['role'] === 'Students' || $row['role'] === 'Guild President' || $row['role'] === 'Class Representatives') {
        header('Location: dashboards/student.php');
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
  <title>ISNM Student Login Portal</title>
  <meta name="description" content="Student Login to ISNM management system">
  <!-- Fontawesome CDN Link -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/isnm-style.css">
  <link rel="stylesheet" href="login-form-style.css">
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  
  <style>
    .student-login-container {
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
    
    .student-login-logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .student-login-logo img {
      width: 100px;
      height: 100px;
      object-fit: contain;
      border-radius: 50%;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      background: white;
      padding: 10px;
      border: 3px solid #28a745;
      transition: all 0.3s ease;
    }
    
    .student-login-logo img:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
    }
    
    .student-login-title {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .student-login-title h2 {
      color: #28a745;
      font-weight: 700;
      margin-bottom: 10px;
    }
    
    .student-login-title p {
      color: #6c757d;
      font-size: 14px;
    }
    
    .student-input-box {
      position: relative;
      margin-bottom: 25px;
    }
    
    .student-input-box i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      font-size: 18px;
      transition: all 0.3s ease;
      z-index: 2;
    }
    
    .student-input-box input,
    .student-input-box select {
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
    
    .student-input-box input:focus,
    .student-input-box select:focus {
      border-color: #28a745;
      background: white;
      box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
      outline: none;
    }
    
    .student-input-box input:focus + i,
    .student-input-box select:focus + i {
      color: #28a745;
    }
    
    .student-login-btn {
      width: 100%;
      padding: 16px 20px;
      border: none;
      border-radius: 30px;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .student-login-btn:hover {
      background: linear-gradient(135deg, #20c997, #17a2b8);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    
    .student-forgot-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .student-forgot-link a {
      color: #6c757d;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    
    .student-forgot-link a:hover {
      color: #28a745;
    }
    
    .student-back-link {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e9ecef;
    }
    
    .student-back-link a {
      color: #6c757d;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    
    .student-back-link a:hover {
      color: #28a745;
    }
    
    .student-action-buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      justify-content: center;
    }
    
    .student-action-buttons .btn-3d {
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      padding: 10px 20px;
      border: none;
      border-radius: 18px;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      position: relative;
      transform-style: preserve-3d;
      transition: all 0.3s ease;
      box-shadow: 
        0 3px 0 #155724,
        0 4px 8px rgba(0,0,0,0.15);
      text-transform: uppercase;
      letter-spacing: 0.2px;
      overflow: hidden;
      font-size: 0.85rem;
    }
    
    .student-action-buttons .btn-3d::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #20c997, #17a2b8);
      border-radius: 50px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .student-action-buttons .btn-3d:hover {
      transform: translateY(2px);
      box-shadow: 
        0 4px 0 #155724,
        0 8px 12px rgba(0,0,0,0.25);
    }
    
    .student-action-buttons .btn-3d:hover::before {
      opacity: 0.3;
    }
    
    .student-action-buttons .btn-3d:active {
      transform: translateY(4px);
      box-shadow: 
        0 2px 0 #155724,
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
  <div class="student-login-container">
    <!-- ISNM Logo -->
    <div class="student-login-logo">
      <img src="images/school-logo.png" alt="ISNM Logo">
    </div>
    
    <!-- Login Title -->
    <div class="student-login-title">
      <h2>Student Login Portal</h2>
      <p>Welcome back! Please login to access your student dashboard</p>
    </div>
    
    <!-- Error Message -->
    <?php if (isset($_SESSION['error'])): ?>
      <div class="error-message">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>
    
    <!-- Student Login Form -->
    <form action="process-login.php" method="post">
      <input type="hidden" name="user_type" value="student">
      
      <!-- Student Role Selection -->
      <div class="student-input-box">
        <i class="fas fa-user-graduate"></i>
        <select name="student_role" required>
          <option value="">Select Your Role</option>
          <option value="Students">Regular Student</option>
          <option value="Guild President">Guild President</option>
          <option value="Class Representatives">Class Representative</option>
        </select>
      </div>
      
      <!-- Student ID Field -->
      <div class="student-input-box">
        <i class="fas fa-id-badge"></i>
        <input type="text" name="student_id" placeholder="Enter your Student ID" required>
      </div>
      
      <!-- Email Field -->
      <div class="student-input-box">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Enter your email" required>
      </div>
      
      <!-- Password Field -->
      <div class="student-input-box">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Enter your password" required>
      </div>
      
      <!-- Login Button -->
      <button type="submit" class="student-login-btn">
        <i class="fas fa-sign-in-alt"></i> Login to Student Dashboard
      </button>
    </form>
    
    <!-- Forgot Password -->
    <div class="student-forgot-link">
      <a href="forgot-password.php">Forgot password?</a>
    </div>
    
    <!-- 3D Action Buttons -->
    <div class="student-action-buttons">
      <button class="btn-3d" onclick="window.location.href='application.php'">
        <i class="fas fa-rocket me-2"></i>Apply Now
      </button>
      <button class="btn-3d" onclick="window.location.href='login.php'">
        <i class="fas fa-home me-2"></i>Main Login
      </button>
    </div>
    
    <!-- Back to Main Login -->
    <div class="student-back-link">
      <a href="login.php">← Back to Main Login</a>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
