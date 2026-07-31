<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
  session_start();

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    include('assets/config.php');
    $response = array();


    $response['status'] = '';
    $response['message'] = '';

    $csrfSent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfSent)) {
        $response['status'] = 'ERROR';
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
        echo json_encode($response);
        exit;
    }

    if(isset($_POST['otp']) && isset($_POST['email'])){

        $email = trim($_POST['email'] ?? '');
        $otp = ($_POST['otp'] ?? '') . '';

        $generatedOtp = $_SESSION['otp'] ?? '';

        if ($generatedOtp !== '' && hash_equals($generatedOtp, $otp)) {
            $response['status'] = 'success';
            $response['message'] = 'otp matched';
            $_SESSION['otp_verified'] = true;
            $_SESSION['otp_email'] = $email;
            unset($_SESSION['otp']);
        }else{
            $response['status'] = 'ERROR';
            $response['message'] = 'Invalid OTP! ';
        }
        

    }else if(isset($_POST['password']) && isset($_POST['email'])){

        if (empty($_SESSION['otp_verified']) || !isset($_SESSION['otp_email']) || !hash_equals($_SESSION['otp_email'], trim($_POST['email'] ?? ''))) {
            $response['status'] = 'ERROR';
            $response['message'] = 'OTP verification required before resetting your password.';
            echo json_encode($response);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (strlen($password) < 8) {
            $response['status'] = 'ERROR';
            $response['message'] = 'Password must be at least 8 characters long.';
            echo json_encode($response);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE `users` SET `password_hash` = ? WHERE `users`.`email` = ?;";
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, "ss",$passwordHash, $email);

        $sql2 = "SELECT `id` FROM `users` WHERE `users`.`email`=?;";
        $stmt3 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt3, "s", $email);
        mysqli_stmt_execute($stmt3);
        $result = mysqli_stmt_get_result($stmt3);


        if(mysqli_stmt_execute($stmt2) && mysqli_num_rows($result) > 0){

            $row = mysqli_fetch_assoc($result);
            $_SESSION['uid'] = $row['id'];
            unset($_SESSION['otp_verified'], $_SESSION['otp_email']);
            $response['status'] = 'update_success';
            $response['message'] = 'Password successfully updated';
        }else{
            $response['status'] = 'Error';
            $response['message'] = 'Unable to update password...!';
        }

        mysqli_stmt_close($stmt2);
    }
    else if(isset($_POST['email'])){

        $email = $_POST['email'] ?? '';

        function domain_exists($email, $record = 'MX'){
            list($user, $domain) = explode('@', $email);
            return checkdnsrr($domain, $record);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && domain_exists($email)) {  

            $query = "SELECT * FROM `users` WHERE `email`=?;";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if(mysqli_num_rows($result) > 0){
                $response['status'] = 'success';

                $OTP = generateOTP();
                $mail = getEmailObject($email, $OTP);

                try {
                    $mail->send();
                    $response['status'] = 'success';
                    $response['email'] = $email . '';
                    $_SESSION['otp'] = $OTP . "";
                    unset($_SESSION['otp_verified'], $_SESSION['otp_email']);
                } catch (Exception $e) {
                    $response['status'] = 'ERROR';
                    $response['message'] = 'Something went wrong!';
                }

              
            }else{
                $response['status'] = 'ERROR';
                $response['message'] = 'Email not found!';
            }
            mysqli_stmt_close($stmt);
          } else {
            $response['status'] = 'ERROR';
            $response['message'] = 'Invalid email!';
          }
    }else{
        $response['status'] = 'ERROR';
        $response['message'] = 'Somehing went wrong!';
    }

    echo json_encode($response);

function generateOTP(): int {
    return random_int(100000, 999999);
}

function getEmailObject($reciever, $otp){
    
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
    
    
    $title = 'OTP Verification email';
    $message = '<h3>OTP Verification email</h3><p>Your one time password is <b>'.$otp.'</b>. Stay connected with Us.</p><p>This email is computer generated so please do not reply this email.</p>';
    
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'erp.schoolmanagementsystem@gmail.com';
    $mail->Password = isnm_env('SMTP_PASSWORD');
    $mail->SMTPSecure = 'tls';  
    $mail->Port = 587;
    
    $mail->setFrom('erp.schoolmanagementsystem@gmail.com');
    $mail->addAddress(''.$reciever);
  
    $mail->isHTML(true);
    
    $mail->Subject = $title;
    $mail->Body = $message;

    return $mail;
}
?>



