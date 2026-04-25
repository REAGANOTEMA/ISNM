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
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Iganga School of Nursing and Midwifery - Login</title>
  <meta name="description" content="Login to ISNM management system">
  <!-- Fontawesome CDN Link -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/isnm-style.css">
  <link rel="stylesheet" href="login-form-style.css">
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
</head>

<body>
  <div class="container">
    <input type="checkbox" id="flip">
    <div class="cover">
      <div class="front">
        <img src="images/loginimage.jpg" alt="">
        <div class="text">
          <span class="text-1">IGANGA SCHOOL OF NURSING<br>& MIDWIFERY</span>
          <span class="text-2">"Chosen to Serve"</span>
        </div>
      </div>
    </div>
    <div class="forms">
      <div class="form-content">
        <div class="login-form">
          <!-- ISNM Logo -->
          <div class="login-logo text-center mb-3">
            <img src="images/school-logo.png" alt="ISNM Logo" class="login-logo-img">
          </div>

          <!-- Login Type Selection -->
          <div class="login-tabs">
            <a href="student-login.php" class="tab-btn">
              <i class="fas fa-graduation-cap"></i> Student Login
            </a>
            <a href="staff-login.php" class="tab-btn">
              <i class="fas fa-user-tie"></i> Staff Login
            </a>
          </div>

          <div class="alert-box">
            <div class="alert alert-info text-center mt-3" role="alert">
              <?php 
                if (isset($_SESSION['error'])) {
                  echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                  unset($_SESSION['error']);
                } else {
                  echo "Select your login type to continue";
                }
              ?>
            </div>
          </div>

          <!-- Quick Login Options -->
          <div class="quick-login-options">
            <div class="option-card">
              <div class="option-icon">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <div class="option-content">
                <h3>Student Portal</h3>
                <p>Access your student dashboard, grades, and academic resources</p>
                <a href="student-login.php" class="option-btn">
                  <i class="fas fa-arrow-right"></i> Continue to Student Login
                </a>
              </div>
            </div>
            
            <div class="option-card">
              <div class="option-icon">
                <i class="fas fa-user-tie"></i>
              </div>
              <div class="option-content">
                <h3>Staff Portal</h3>
                <p>Access your staff dashboard, administrative tools, and resources</p>
                <a href="staff-login.php" class="option-btn">
                  <i class="fas fa-arrow-right"></i> Continue to Staff Login
                </a>
              </div>
            </div>
          </div>


          <!-- forgot password gui -->
          <form action="index.php" id="forgotPassword-form" method="post" style="display:none;">

            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="forgotEmail" placeholder="Enter your email" required>
              </div>

              <div class="text" style="margin-bottom: 20px;display:flex">
                <a id="backToLogin">back to login?</a>
              </div>

              <div class="button input-box">
                <button type="submit" id='sendCodeBtn'>
                  Send Code
                </button>
              </div>

            </div>
          </form>

          <form id="otpVarification-form" method="post" style="display:none;">

            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="text" name="email" value="some value" id="otpDisabledEmail">
              </div>

              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="text" name="otp" placeholder="Enter code" id="otpCode" required>
              </div>

              <div class="text" style="margin-bottom: 20px;display:flex">
                <a id="backToforgotPasswordForm">back</a>
                <a id="resendOTP" style='margin-left: auto;'>resend OTP?</a>
              </div>

              <div class="button input-box">
                <button type="submit" id='verifyCodeBtn'>
                  Verify Code
                </button>
              </div>

            </div>
          </form>


          <form id="createNewPassword-form" method="post" style="display:none;">

            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="newpassword" id='newpassword' placeholder='Enter new password' required>
              </div>

              <div class="invalid-feedback" id='weakPasswordFeedback'></div>

              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirmpassword" id='confirmpassword' placeholder='Confirm password' required>
              </div>

              <div class="invalid-feedback" id='passwordNotSame'>
                New password and confirm password are not same!
              </div>

              <div class="form-check mt-3 ">
                <input class="form-check-input" type="checkbox" value="" id="showPasswords">
                <label class="form-check-label" for="showPasswords" id='showPasswordLabel'>
                  Show password
                </label>
              </div>

              <div class="button input-box">
                <button type="submit" id='changePasswordBtn'>
                  Change password
                </button>
              </div>

            </div>
          </form>

          <!-- end of forgot password gui -->


        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="index.js"></script>
  
  <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
      const tabButtons = document.querySelectorAll('.tab-btn');
      const formContents = document.querySelectorAll('.login-form-content');
      
      tabButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetTab = this.getAttribute('data-tab');
          
          // Remove active class from all buttons and forms
          tabButtons.forEach(btn => btn.classList.remove('active'));
          formContents.forEach(form => form.classList.remove('active'));
          
          // Add active class to clicked button and corresponding form
          this.classList.add('active');
          document.getElementById(targetTab + '-login-form').classList.add('active');
        });
      });
      
      // Password toggle functionality
      const toggleStudentPassword = document.getElementById('toggleStudentPassword');
      const studentPassword = document.getElementById('studentPassword');
      const toggleStaffPassword = document.getElementById('toggleStaffPassword');
      const staffPassword = document.getElementById('staffPassword');
      
      if (toggleStudentPassword && studentPassword) {
        toggleStudentPassword.addEventListener('click', function() {
          if (studentPassword.type === 'password') {
            studentPassword.type = 'text';
            this.classList.remove('bi-eye-fill');
            this.classList.add('bi-eye-slash-fill');
          } else {
            studentPassword.type = 'password';
            this.classList.remove('bi-eye-slash-fill');
            this.classList.add('bi-eye-fill');
          }
        });
      }
      
      if (toggleStaffPassword && staffPassword) {
        toggleStaffPassword.addEventListener('click', function() {
          if (staffPassword.type === 'password') {
            staffPassword.type = 'text';
            this.classList.remove('bi-eye-fill');
            this.classList.add('bi-eye-slash-fill');
          } else {
            staffPassword.type = 'password';
            this.classList.remove('bi-eye-slash-fill');
            this.classList.add('bi-eye-fill');
          }
        });
      }
    });
  </script>


</body>

</html>