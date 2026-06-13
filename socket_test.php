<?php
$socket = 'C:/xampp/mysql/mysql.sock';
$user = 'root';
$pass = '';
echo 'Connecting via socket...<br>';
$conn = @new mysqli(null, $user, $pass, '', null, $socket);
if ($conn->connect_error) {
    echo 'Failed: ' . $conn->connect_error;
} else {
    echo 'Success';
    $conn->close();
}
?>