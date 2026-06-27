<?php
session_start();
session_unset();
session_destroy();
$response = ['status' => 'success', 'message' => 'Logout successful'];
echo json_encode($response);
