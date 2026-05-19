<?php
// Test the complete login flow with position parameter using our test director
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth-service.php';

// Create auth service instance
$auth_service = new AuthenticationService();

// Use the test director credentials we just created
$email = 'director.test@isnm.ac.ug';
$password = 'password123';
$requestedPosition = 'Director General';

// Set the requested position in session (as staff-login.php does)
$_SESSION['requested_position'] = $requestedPosition;

// Set up POST data
$_POST['email'] = $email;
$_POST['password'] = $password;
$_POST['requested_position'] = $requestedPosition;
$_POST['action'] = 'staff_login';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Start output buffering to capture any headers
ob_start();

// Include the auth handler (this will process the login)
require_once 'auth-handler.php';

// Get headers and any output
$headers = ob_get_clean();

// Check if redirect header was set
if (strpos($headers, 'Location:') !== false) {
    // Extract redirect URL
    preg_match('/Location: (.*)/', $headers, $matches);
    $redirect_url = trim($matches[1]);
    echo "Redirecting to: " . $redirect_url . "<br>";
    
    // Check if it's going to the correct dashboard
    if (strpos($redirect_url, 'dashboards/director-general.php') !== false) {
        echo "SUCCESS: Correctly redirected to Director General dashboard!";
    } else {
        echo "ERROR: Redirected to wrong dashboard.";
    }
} else {
    echo "No redirect header found.<br>";
    echo "Output: " . $headers;
    
    // Show session data for debugging
    echo "<br><br>Session data:<br>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Show any errors that might have occurred during authentication
    if (!empty($_SESSION['error'])) {
        echo "<br>Error in session: " . $_SESSION['error'];
    }
}
?>