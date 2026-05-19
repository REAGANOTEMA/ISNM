<?php
// Test password_hash
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";
echo "Hash length: " . strlen($hash) . "<br>";
if ($hash === false) {
    echo "password_hash returned FALSE!<br>";
    // Let's see what the error is
    // password_hash doesn't throw warnings by default, but we can check if the algo is valid
    // PASSWORD_DEFAULT is 1 (bcrypt) as of PHP 7.4.0
} else {
    echo "Hash looks good.<br>";
    // Verify
    $check = password_verify($password, $hash);
    echo "Verification: " . ($check ? 'SUCCESS' : 'FAILED') . "<br>";
}
?>