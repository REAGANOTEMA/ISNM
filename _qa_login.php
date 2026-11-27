<?php
require __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_GET['role'] ?? 'Director General';
$c = getSystemConnection();
$s = $c->prepare("SELECT s.id,s.full_name,s.email,s.phone,s.position,s.department,sr.role_name FROM staff s LEFT JOIN staff_roles sr ON sr.id=s.role_id WHERE sr.role_name=? LIMIT 1");
$s->bind_param('s', $role);
$s->execute();
$u = $s->get_result()->fetch_assoc();
if (!$u) { http_response_code(404); echo 'role not found'; exit; }
$_SESSION['logged_in'] = true;
$_SESSION['type'] = 'staff';
$_SESSION['user_id'] = (int)$u['id'];
$_SESSION['role'] = $u['role_name'];
$_SESSION['full_name'] = $u['full_name'];
$_SESSION['email'] = $u['email'];
$_SESSION['phone'] = $u['phone'] ?? '';
$_SESSION['position'] = $u['position'] ?? '';
$_SESSION['department'] = $u['department'] ?? '';
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo 'OK ' . $u['role_name'] . ' TOKEN=' . $_SESSION['csrf_token'];
