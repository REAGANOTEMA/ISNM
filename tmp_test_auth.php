<?php
require_once __DIR__ . '/auth-service.php';
$s = new AuthenticationService();
try {
    $res = $s->authenticateStaff('bursar@isnm', 'bursar@isnm');
    var_export($res);
    echo "\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
