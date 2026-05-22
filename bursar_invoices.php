<?php
session_start();
if (!isset($_SESSION['bursar_id'])) { header('Location: staff-login.php'); exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoices - Bursar Portal</title>
</head>
<body style="padding: 40px; text-align: center; font-family: Arial; color: #666;">
    <h1>📄 Invoice Management</h1>
    <p style="margin-top: 30px; color: #999;">Under development</p>
</body>
</html>
