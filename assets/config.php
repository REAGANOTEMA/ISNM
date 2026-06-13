<?php
require_once __DIR__ . '/../config/database.php';

$conn = getStudentsConnection();

if (!$conn) {
    header('Location: ../errors/error.html');
    exit();
}
?>
