<?php
// CEO dashboard has been merged into Director General dashboard.
// Redirect to the unified Director General dashboard.
session_start();
$_SESSION['info'] = 'Welcome to the Director General Dashboard , your unified command center.';
header('Location: director-general.php');
exit;
