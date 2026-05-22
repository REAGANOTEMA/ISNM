<?php
/**
 * Bursar Payments Page
 */
session_start();
if (!isset($_SESSION['bursar_id'])) { header('Location: staff-login.php'); exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Bursar Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="padding: 40px; text-align: center; color: #666; font-family: Arial;">
        <h1>💳 Payment Management</h1>
        <p>Record payments from students</p>
        <p style="margin-top: 30px; color: #999;">
            <i class="fas fa-tools"></i> This page is under development
        </p>
    </div>
</body>
</html>
