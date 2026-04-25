<?php
error_reporting(0);
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

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  
  $query = "SELECT `role`, `first_name`, `last_name` FROM `users` WHERE `user_id`=?";
  $stmt = mysqli_prepare($conn, $query);

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

          <div class="title" id='board-title'>Staff Login Portal</div>

          <div class="position-info" id="selectedPositionInfo" style="display: none;">
            <div class="position-badge">
              <i class="fas fa-user-tie"></i>
              <span id="selectedPositionText">Director General</span>
            </div>
          </div>

          <div class="alert-box">
            <div class="alert alert-danger text-center mt-3" role="alert" id="error-msg">

            </div>
          </div>

          <form action="process-login.php" id="login-form" method="post">
            <div class="input-boxes">
              <div class="input-box">
                <i class="fas fa-user-tie"></i>
                <select name="position" id="positionSelect" required>
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
                  <optgroup label="Student Leadership">
                    <option value="Guild President">Guild President</option>
                    <option value="Class Representatives">Class Representatives</option>
                    <option value="Students">Students</option>
                  </optgroup>
                </select>
              </div>
              <div class="input-box">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your email" id='loginEmail' required>
              </div>
              <div class="input-box">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your password" id="password" required>
                <i class="bi bi-eye-fill" style="margin-left:auto;margin-right: 6px;" id="togglePassword"></i>
              </div>
              <div class="text"><a id="forgotpassword">Forgot password?</a></div>
              <div class="button input-box">
                <button type="submit" class="btn">
                  <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
              </div>
            </div>
          </form>


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


</body>

</html>