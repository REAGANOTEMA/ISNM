<?php
/**
 * ═════════════════════════════════════════════════════════════════════════
 * ISNM ORGANOGRAM STAFF LOGIN — PREMIUM 3D EDITION
 * ═════════════════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

$auth_service = new AuthenticationService();

$requested_position = isset($_GET['position']) ? urldecode($_GET['position']) : '';
if (!$requested_position && !empty($_SESSION['requested_position'])) {
    $requested_position = $_SESSION['requested_position'];
}
$resolved_role      = $requested_position ? $auth_service->resolveOrganogramPosition($requested_position) : '';
$suggested_email    = $resolved_role ? $auth_service->getStaffEmailForRole($resolved_role) : '';
if ($requested_position) {
    $_SESSION['requested_position'] = $requested_position;
}

// Capture redirect URL from query parameter or session
// Note: $_GET values are already URL-decoded by PHP
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if (!$redirect_url && !empty($_SESSION['login_redirect_url'])) {
    $redirect_url = $_SESSION['login_redirect_url'];
}
if ($redirect_url) {
    // Validate: only allow internal paths (starts with / or dashboards/ or known prefix)
    if (strpos($redirect_url, '..') !== false || strpos($redirect_url, '://') !== false) {
        $redirect_url = '';
    }
    if ($redirect_url) {
        $_SESSION['login_redirect_url'] = $redirect_url;
    }
}

if (!$requested_position && !$redirect_url) {
    header('Location: organogram.php');
    exit();
}

$_SESSION['staff_login_allowed']  = true;
if ($requested_position) {
    $_SESSION['staff_login_position'] = $requested_position;
    $_SESSION['requested_position']  = $requested_position;
}

if ($auth_service->isAuthenticated()) {
    if (($_SESSION['type'] ?? '') === 'staff') {
        $sessionRole = $_SESSION['role'] ?? '';
        $requestedPositionFromSession = $_SESSION['requested_position'] ?? '';

        // If the clicked position doesn't match the logged-in role,
        // log out so the user can re-authenticate as the correct position.
        if (!empty($requestedPositionFromSession)
            && !$auth_service->positionMatchesRole($requestedPositionFromSession, $sessionRole)
        ) {
            $auth_service->logout();
            // Fall through to show the login form
        } else {
            // If a redirect URL is stored, check if user can access it first
            if (!empty($_SESSION['login_redirect_url'])) {
                $target = $_SESSION['login_redirect_url'];
                unset($_SESSION['login_redirect_url'], $_SESSION['requested_position']);
                // Avoid redirect loops: if target is same as current or user is already on it, go to dashboard
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $targetPath = parse_url($target, PHP_URL_PATH);
                if ($targetPath && $targetPath !== $currentPath) {
                    header("Location: $target");
                    exit();
                }
            }
            $dashboard = $auth_service->getDashboardRoute($sessionRole);
            if (!empty($requestedPositionFromSession)) {
                $resolvedPosition = $auth_service->resolveOrganogramPosition($requestedPositionFromSession);
                $requestedDashboard = $auth_service->getDashboardRouteFromKey($resolvedPosition);
                if ($requestedDashboard) {
                    $dashboard = $requestedDashboard;
                }
                unset($_SESSION['requested_position']);
            }
            header("Location: $dashboard");
            exit();
        }
    }
    if (($_SESSION['type'] ?? '') === 'student') {
        header('Location: dashboards/student.php');
        exit();
    }
}

$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
if ($login_error)   { unset($_SESSION['error']); }
if ($login_success) { unset($_SESSION['success']); }

$active_staff_tab = 'show active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#1a237e">
  <title>Staff Login | ISNM</title>
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">

  <style>
  :root {
    --primary: #1a237e;
    --primary-dark: #0d47a1;
    --primary-mid: #283593;
    --accent: #ffd600;
    --accent-dark: #f9a825;
    --success: #2e7d32;
    --danger: #c62828;
    --info: #0277bd;
    --text-dark: #212121;
    --text-mid: #616161;
    --text-light: #9e9e9e;
    --bg-light: #f8f8f8;
    --card-bg: #ffffff;
    --border: #e0e0e0;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    background: 
      linear-gradient(135deg, #1a237e 0%, #283593 40%, #0d47a1 100%),
      url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  /* Floating particles */
  .bg-particles {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
  }
  
  .particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    animation: float-particle linear infinite;
  }
  
  @keyframes float-particle {
    0% { transform: translateY(100vh) scale(0); opacity: 0; }
    10% { opacity: 1; }
    50% { transform: translateY(50vh) scale(1); }
    90% { opacity: 1; }
    100% { transform: translateY(-50px) scale(0); opacity: 0; }
  }

  .login-wrapper { 
    width: 100%; 
    max-width: 500px; 
    margin: 0 auto; 
    position: relative;
    z-index: 1;
  }

  /* Premium 3D Card */
  .login-card {
    background: var(--card-bg);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 
      0 2px 4px rgba(0,0,0,0.04),
      0 4px 8px rgba(0,0,0,0.06),
      0 8px 16px rgba(0,0,0,0.08),
      0 16px 32px rgba(0,0,0,0.1),
      0 32px 64px rgba(0,0,0,0.12);
    animation: cardEntrance 0.6s ease-out;
  }
  
  @keyframes cardEntrance {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Header with premium gradient */
  .login-header {
    background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
    color: #fff;
    padding: 48px 30px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  
  .login-header::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: 
      radial-gradient(circle at 30% 40%, rgba(255,255,255,0.06) 0%, transparent 50%),
      radial-gradient(circle at 70% 60%, rgba(255,255,255,0.04) 0%, transparent 50%);
    animation: rotateBg 40s linear infinite;
  }
  
  @keyframes rotateBg {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  
  .header-inner { position: relative; z-index: 2; }

  /* 3D Logo */
  .logo-wrap {
    width: 100px; height: 100px;
    margin: 0 auto 20px;
    background: linear-gradient(145deg, #ffffff, #e6e6e6);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 
      8px 8px 16px rgba(0,0,0,0.2),
      -4px -4px 8px rgba(255,255,255,0.1),
      inset 2px 2px 4px rgba(255,255,255,0.3);
    position: relative;
  }
  
  .logo-wrap::after {
    content: '';
    position: absolute;
    top: -3px; left: -3px; right: -3px; bottom: -3px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    z-index: -1;
    opacity: 0.7;
  }
  
  .logo-wrap img { 
    width: 80px; height: 80px; 
    border-radius: 50%; 
    object-fit: cover;
  }

  .title-3d {
    font-family: 'Playfair Display', serif;
    font-size: 2.4rem;
    font-weight: 900;
    letter-spacing: 3px;
    margin: 0 0 4px;
    color: #ffd700;
    text-shadow:
      0 1px 0 #cc9600,
      0 2px 0 #b88700,
      0 3px 0 #a37700,
      0 4px 0 #8e6800,
      0 5px 0 #795900,
      0 6px 8px rgba(0,0,0,0.2),
      0 8px 16px rgba(0,0,0,0.1);
    position: relative;
    line-height: 1.2;
  }

  .title-3d::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.12) 50%, transparent 60%);
    background-size: 200% 200%;
    pointer-events: none;
    animation: goldShine 6s ease-in-out infinite;
  }

  @keyframes goldShine {
    0% { background-position: 100% 100%; }
    50% { background-position: 0% 0%; }
    100% { background-position: 100% 100%; }
  }
  
  .school-name {
    opacity: 0.92;
    font-size: 0.92rem;
    font-weight: 500;
    margin: 0 0 2px;
    color: #fff;
    letter-spacing: 0.5px;
  }
  
  .sign-in-label {
    opacity: 0.7;
    font-size: 0.82rem;
    font-weight: 400;
    margin: 0;
    color: rgba(255,255,255,0.85);
    letter-spacing: 1px;
    text-transform: uppercase;
  }
  
  .role-badge {
    display: inline-block;
    margin-top: 12px;
    padding: 5px 16px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
  }

  /* Tab bar */
  .tab-bar {
    display: flex;
    background: linear-gradient(180deg, #0d47a1 0%, #1a237e 100%);
  }
  
  .tab-btn {
    flex: 1;
    padding: 16px 8px;
    background: transparent;
    color: rgba(255,255,255,0.55);
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 0.92rem;
    font-weight: 500;
    transition: all 0.25s ease;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  
  .tab-btn:hover { color: rgba(255,255,255,0.8); }
  
  .tab-btn.active {
    color: #fff;
    background: rgba(255,255,255,0.1);
    font-weight: 600;
  }
  
  .tab-btn.active::after {
    content: '';
    position: absolute; bottom: 0; left: 20%; right: 20%;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
  }

  /* Form area */
  .login-body { 
    padding: 32px 30px 28px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
  }

  .form-group { margin-bottom: 20px; }
  
  .form-label {
    font-weight: 600; 
    color: var(--text-dark); 
    margin-bottom: 8px;
    font-size: 0.9rem; 
    display: block;
  }
  
  .input-group { position: relative; }
  
  .input-group i {
    position: absolute; 
    left: 16px; 
    top: 50%; 
    transform: translateY(-50%);
    color: var(--text-light); 
    font-size: 1rem; 
    z-index: 2;
    transition: color 0.2s ease;
  }
  
  .form-control {
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px 14px 46px;
    font-size: 14px;
    transition: all 0.25s ease;
    background: var(--bg-light);
    height: auto;
  }
  
  .form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26,35,126,0.1);
    outline: none;
  }
  
  .input-group:focus-within i { color: var(--primary); }
  
  .form-control::placeholder { color: var(--text-light); }

  /* Premium 3D Button */
  .btn-login {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; 
    border: none;
    border-radius: 12px; 
    padding: 15px 24px;
    font-size: 15px; 
    font-weight: 700;
    width: 100%; 
    cursor: pointer;
    position: relative; 
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 
      0 4px 12px rgba(26,35,126,0.3),
      inset 0 1px 0 rgba(255,255,255,0.15);
  }
  
  .btn-login::before {
    content: '';
    position: absolute; top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
    transition: left 0.5s ease;
  }
  
  .btn-login:hover::before { left: 100%; }
  
  .btn-login:hover {
    box-shadow: 
      0 6px 20px rgba(26,35,126,0.4),
      inset 0 1px 0 rgba(255,255,255,0.2);
    transform: translateY(-1px);
  }
  
  .btn-login:active {
    transform: translateY(0);
    box-shadow: 
      0 2px 8px rgba(26,35,126,0.3),
      inset 0 2px 4px rgba(0,0,0,0.1);
  }

  .tab-panel { display: none; }
  .tab-panel.active { display: block; animation: fadeIn 0.3s ease; }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  .panel-divider { 
    height: 1px; 
    background: linear-gradient(90deg, transparent, var(--border), transparent); 
    margin: 20px 0; 
  }

  .info-block {
    border-radius: 12px; 
    padding: 16px 18px; 
    margin-bottom: 16px;
    border-left: 3px solid var(--info);
    font-size: 0.83rem;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  }
  
  .info-block .block-title { 
    font-weight: 600; 
    margin-bottom: 6px;
    color: var(--text-dark);
  }

  .help-links { text-align: center; }
  .help-links a { 
    color: var(--primary); 
    text-decoration: none; 
    font-size: 0.9rem; 
    display: inline-block;
    margin: 5px 10px;
    transition: color 0.2s ease;
  }
  .help-links a:hover { color: var(--primary-dark); }
  .help-links i { margin-right: 6px; }

  .alert {
    border-radius: 10px; 
    margin-bottom: 18px;
    border: none; 
    padding: 12px 16px; 
    font-size: 0.88rem;
  }
  
  .alert-danger { background: #ffebee; color: #c62828; border-left: 4px solid #ef5350; }
  .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #66bb6a; }

  .login-footer { padding: 0 30px 24px; }

  @media(max-width:768px){
    .login-card { border-radius: 20px; }
    .login-header { padding: 36px 24px 32px; }
    .login-body { padding: 24px 20px; }
    .login-footer { padding: 0 20px 20px; }
    .title-3d { font-size: 1.9rem; }
    .school-name { font-size: 0.85rem; }
    .sign-in-label { font-size: 0.78rem; }
  }
  
  @media(max-width:480px){
    .login-card { border-radius: 16px; }
    .login-header { padding: 30px 18px 26px; }
    .login-body { padding: 20px 16px; }
    .title-3d { font-size: 1.6rem; letter-spacing: 2px; }
    .school-name { font-size: 0.82rem; }
    .sign-in-label { font-size: 0.72rem; }
    .logo-wrap { width: 85px; height: 85px; }
    .logo-wrap img { width: 68px; height: 68px; }
  }

  /* ═══════════════════════════════════════════════════════════════
     PREMIUM MODERN ENHANCEMENTS
     ═══════════════════════════════════════════════════════════════ */

  /* Ambient background glow */
  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse at 15% 50%, rgba(255,214,0,0.05) 0%, transparent 55%),
      radial-gradient(ellipse at 85% 30%, rgba(255,255,255,0.04) 0%, transparent 55%),
      radial-gradient(ellipse at 50% 80%, rgba(26,35,126,0.07) 0%, transparent 55%);
    pointer-events: none;
    z-index: 0;
    animation: ambientGlow 10s ease-in-out infinite alternate;
  }

  @keyframes ambientGlow {
    0% { opacity: 0.5; }
    100% { opacity: 1; }
  }

  /* Decorative rings around logo */
  .logo-wrap .ring-deco {
    position: absolute;
    border-radius: 50%;
    border: 1.5px solid rgba(255,214,0,0.12);
    pointer-events: none;
  }

  .logo-wrap .ring-deco:nth-child(1) {
    top: -8px; left: -8px; right: -8px; bottom: -8px;
    animation: ringPulse 3s ease-in-out infinite;
  }

  .logo-wrap .ring-deco:nth-child(2) {
    top: -15px; left: -15px; right: -15px; bottom: -15px;
    border-color: rgba(255,214,0,0.07);
    animation: ringPulse 3s ease-in-out 1s infinite;
  }

  .logo-wrap .ring-deco:nth-child(3) {
    top: -22px; left: -22px; right: -22px; bottom: -22px;
    border-color: rgba(255,214,0,0.04);
    animation: ringPulse 3s ease-in-out 2s infinite;
  }

  @keyframes ringPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.06); opacity: 1; }
  }

  /* Card corner decorations */
  .login-card::before,
  .login-card::after {
    content: '';
    position: absolute;
    width: 60px; height: 60px;
    border: 2px solid rgba(255,214,0,0.1);
    pointer-events: none;
    z-index: 1;
  }

  .login-card::before {
    top: 10px; left: 10px;
    border-right: none; border-bottom: none;
    border-radius: 4px 0 0 0;
  }

  .login-card::after {
    bottom: 10px; right: 10px;
    border-left: none; border-top: none;
    border-radius: 0 0 4px 0;
  }

  /* Glass info block enhancement */
  .info-block {
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    position: relative;
    overflow: hidden;
  }

  .info-block::before {
    content: '';
    position: absolute;
    top: -50%; right: -50%;
    width: 100%; height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
    pointer-events: none;
  }

  /* Role badge glass enhancement */
  .role-badge {
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    transition: all 0.3s ease;
    position: relative;
  }

  .role-badge:hover {
    background: rgba(255,255,255,0.2) !important;
    transform: scale(1.03);
  }

  /* Enhanced form field on focus */
  .form-group {
    position: relative;
  }

  .form-group .input-highlight {
    position: absolute;
    bottom: 0; left: 50%;
    width: 0; height: 2px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: all 0.3s ease;
    border-radius: 2px;
    z-index: 3;
  }

  .input-group:focus-within ~ .input-highlight,
  .form-control:focus ~ .input-highlight {
    width: 80%;
    left: 10%;
  }

  /* Password toggle */
  .password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 4px;
    font-size: 1.1rem;
    z-index: 3;
    transition: color 0.2s ease;
  }

  .password-toggle:hover { color: var(--primary); }

  /* Loading spinner for submit */
  .btn-login .spinner-layer {
    display: none;
    width: 20px; height: 20px;
    border: 2.5px solid rgba(255,255,255,0.2);
    border-top-color: #fff;
    border-radius: 50%;
    margin-right: 8px;
    animation: spin 0.7s linear infinite;
  }

  .btn-login.loading .spinner-layer { display: inline-block; }
  .btn-login.loading .btn-text { opacity: 0.7; }
  .btn-login.loading { pointer-events: none; }

  @keyframes spin { to { transform: rotate(360deg); } }

  /* Staggered entrance for form elements */
  .form-group {
    animation: slideUpFade 0.5s ease-out backwards;
  }

  .form-group:nth-child(1) { animation-delay: 0.1s; }
  .form-group:nth-child(2) { animation-delay: 0.2s; }
  .btn-login { animation: slideUpFade 0.5s ease-out 0.3s backwards; }
  .help-links { animation: slideUpFade 0.5s ease-out 0.4s backwards; }
  .info-block { animation: slideUpFade 0.5s ease-out 0.5s backwards; }

  @keyframes slideUpFade {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Enhanced divider */
  .panel-divider {
    position: relative;
    overflow: hidden;
  }

  .panel-divider::after {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(26,35,126,0.08), transparent);
    animation: dividerShimmer 4s ease-in-out infinite;
  }

  @keyframes dividerShimmer {
    0% { left: -50%; }
    100% { left: 150%; }
  }

  /* Enhanced particles with multiple shapes */
  .particle {
    mix-blend-mode: overlay;
  }

  .particle.type-star {
    background: transparent;
    border: none;
    width: 0 !important; height: 0 !important;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 7px solid rgba(255,214,0,0.08);
  }

  /* Title shine overlay (applied via JS on .title-3d) */

  @media (min-width: 1200px) {
    .login-wrapper { max-width: 520px; }
  }

  @media(max-width:380px) {
    .login-body { padding: 16px 12px; }
    .form-control { padding: 12px 14px 12px 40px; font-size: 13px; }
    .input-group i { left: 12px; }
  }

  /* ═════════════════════════════════════════════════════════════════════════
     MIND-BLOWING ENHANCEMENTS — next-level advanced design
     ═════════════════════════════════════════════════════════════════════════ */

  /* ── Noise grain overlay ── */
  .noise-overlay {
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    background-size: 256px 256px;
    pointer-events: none;
    z-index: 1;
    opacity: 0.6;
  }

  /* ── Floating geometric shapes ── */
  .floating-shapes {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
  }

  .floating-shape {
    position: absolute;
    border-radius: 50%;
    animation: floatShape 25s linear infinite;
    will-change: transform;
  }

  .floating-shape.type-blob {
    border-radius: 40% 60% 60% 40% / 60% 40% 70% 40%;
    animation: floatShape 25s linear infinite, morphBlob 14s ease-in-out infinite alternate;
  }

  .floating-shape.type-tri {
    width: 0 !important; height: 0 !important;
    border-radius: 0;
    background: transparent !important;
    border-left: 15px solid transparent;
    border-right: 15px solid transparent;
    border-bottom: 26px solid rgba(255,214,0,0.05);
  }

  .floating-shape.type-rect {
    border-radius: 25%;
    animation: floatShape 30s linear infinite, rotateRect 12s linear infinite;
  }

  @keyframes floatShape {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    8% { opacity: 0.5; }
    92% { opacity: 0.5; }
    100% { transform: translateY(-20vh) rotate(720deg); opacity: 0; }
  }

  @keyframes morphBlob {
    0% { border-radius: 40% 60% 60% 40% / 60% 30% 70% 40%; }
    50% { border-radius: 60% 40% 30% 70% / 50% 60% 40% 60%; }
    100% { border-radius: 30% 70% 50% 50% / 60% 40% 60% 50%; }
  }

  @keyframes rotateRect {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* ── 3D card tilt container ── */
  .card-3d-wrap {
    perspective: 1200px;
  }

  .card-3d-wrap .login-card {
    transition: transform 0.12s ease-out, box-shadow 0.5s ease;
    transform-style: preserve-3d;
    will-change: transform;
  }

  .card-3d-wrap .login-card:hover {
    box-shadow:
      0 2px 4px rgba(0,0,0,0.04),
      0 4px 8px rgba(0,0,0,0.06),
      0 8px 16px rgba(0,0,0,0.08),
      0 16px 32px rgba(0,0,0,0.1),
      0 32px 64px rgba(0,0,0,0.12),
      0 0 80px rgba(26,35,126,0.1);
  }

  /* ── Animated gradient border on input focus ── */
  .input-group::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 13px;
    background: linear-gradient(90deg, #1a237e, #ffd600, #0d47a1, #f9a825, #1a237e);
    background-size: 300% 100%;
    animation: gradientBorder 4s linear infinite;
    opacity: 0;
    transition: opacity 0.35s ease;
    z-index: 0;
    pointer-events: none;
  }

  .input-group:focus-within::before {
    opacity: 1;
  }

  .input-group .form-control {
    position: relative;
    z-index: 1;
    background-clip: padding-box;
  }

  .input-group:focus-within .form-control {
    border-color: transparent;
    background: #fff;
  }

  @keyframes gradientBorder {
    0% { background-position: 0% 50%; }
    100% { background-position: 300% 50%; }
  }

  /* ── Magnetic button ── */
  .btn-login {
    transition: transform 0.15s ease, box-shadow 0.3s ease;
    will-change: transform;
  }

  .btn-login.loading {
    transition: none;
    will-change: auto;
  }

  /* ── Ripple effect ── */
  .ripple-effect {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.35);
    transform: scale(0);
    animation: rippleAnim 0.65s ease-out forwards;
    pointer-events: none;
  }

  @keyframes rippleAnim {
    to { transform: scale(4); opacity: 0; }
  }

  /* ── Enhanced particles with glow ── */
  .particle {
    box-shadow: 0 0 6px rgba(255,255,255,0.18);
    will-change: transform, opacity;
  }

  /* ── Typing cursor ── */
  .typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1.1em;
    background: rgba(255,255,255,0.75);
    margin-left: 2px;
    vertical-align: text-bottom;
    animation: blinkCursor 0.75s step-end infinite;
  }

  @keyframes blinkCursor {
    50% { opacity: 0; }
  }

  /* ── Enhanced entrance animation ── */
  .card-3d-wrap .login-card {
    animation: premiumEntrance 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  @keyframes premiumEntrance {
    0% { opacity: 0; transform: translateY(35px) scale(0.97); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* ── Tab bar sliding indicator polish ── */
  .tab-bar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 20%;
    right: 20%;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--accent-dark));
    border-radius: 3px 3px 0 0;
  }

  /* ═════════════════════════════════════════════════════════════════════════
     MEDICAL THEME ENHANCEMENTS — 3D medical precision design
     ═════════════════════════════════════════════════════════════════════════ */

  /* ── 3D Medical Plus watermark (rotates in 3D space) ── */
  .medical-plus {
    position: fixed;
    top: 50%; left: 50%;
    width: 400px; height: 400px;
    pointer-events: none;
    z-index: 0;
    opacity: 0.035;
    transform: translate(-50%, -50%);
    perspective: 800px;
  }

  .medical-plus-inner {
    width: 100%; height: 100%;
    position: relative;
    transform-style: preserve-3d;
    animation: plus3dSpin 40s linear infinite;
  }

  .medical-plus-inner::before,
  .medical-plus-inner::after {
    content: '';
    position: absolute;
    background: linear-gradient(135deg, rgba(255,214,0,0.15), rgba(255,255,255,0.08));
    border-radius: 18px;
    box-shadow:
      0 0 30px rgba(255,214,0,0.06),
      0 0 60px rgba(26,35,126,0.04),
      inset 0 0 20px rgba(255,255,255,0.03);
  }

  .medical-plus-inner::before {
    width: 68%; height: 20%;
    top: 40%; left: 16%;
  }

  .medical-plus-inner::after {
    width: 20%; height: 68%;
    top: 16%; left: 40%;
  }

  @keyframes plus3dSpin {
    0% { transform: rotateX(25deg) rotateY(0deg) rotateZ(0deg); }
    33% { transform: rotateX(25deg) rotateY(120deg) rotateZ(120deg); }
    66% { transform: rotateX(25deg) rotateY(240deg) rotateZ(240deg); }
    100% { transform: rotateX(25deg) rotateY(360deg) rotateZ(360deg); }
  }

  /* ── Pulse / Sonar Rings ── */
  .pulse-container {
    position: fixed;
    top: 50%; left: 50%;
    width: 0; height: 0;
    pointer-events: none;
    z-index: 0;
  }

  .pulse-ring {
    position: absolute;
    top: 0; left: 0;
    border: 1.5px solid rgba(255,214,0,0.07);
    border-radius: 50%;
    animation: pulseWave 5s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
    transform: translate(-50%, -50%);
  }

  .pulse-ring:nth-child(1) { width: 40px; height: 40px; animation-delay: 0s; }
  .pulse-ring:nth-child(2) { width: 100px; height: 100px; animation-delay: 1s; }
  .pulse-ring:nth-child(3) { width: 200px; height: 200px; animation-delay: 2s; border-width: 1px; }
  .pulse-ring:nth-child(4) { width: 350px; height: 350px; animation-delay: 3s; border-width: 1px; opacity: 0.6; }
  .pulse-ring:nth-child(5) { width: 550px; height: 550px; animation-delay: 4s; border-width: 0.8px; opacity: 0.4; }

  @keyframes pulseWave {
    0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
    20% { opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
  }

  /* ── Heartbeat / EKG Line ── */
  .ekg-line {
    position: fixed;
    bottom: 50px; left: 8%; right: 8%;
    height: 32px;
    pointer-events: none;
    z-index: 0;
    opacity: 0.045;
  }

  .ekg-line svg {
    width: 100%; height: 100%;
    display: block;
  }

  .ekg-line path {
    fill: none;
    stroke: #ffd700;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 3000;
    stroke-dashoffset: 3000;
    animation: drawEKG 4s linear infinite;
    filter: drop-shadow(0 0 4px rgba(255,214,0,0.15));
  }

  @keyframes drawEKG {
    0% { stroke-dashoffset: 3000; }
    80% { stroke-dashoffset: 0; }
    100% { stroke-dashoffset: 0; }
  }

  /* ── Hexagonal Medical Grid (subtle background pattern) ── */
  .hex-grid {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    opacity: 0.025;
    background-image:
      radial-gradient(circle at 25% 25%, rgba(255,255,255,0.15) 1px, transparent 1px),
      radial-gradient(circle at 75% 75%, rgba(255,255,255,0.15) 1px, transparent 1px);
    background-size: 60px 60px;
  }

  /* ── Enhanced floating medical shapes ── */
  .floating-shape.type-pill {
    border-radius: 50px;
    animation: floatShape 28s linear infinite, pillGlow 4s ease-in-out infinite alternate;
  }

  @keyframes pillGlow {
    0% { box-shadow: 0 0 4px rgba(255,214,0,0.03); }
    100% { box-shadow: 0 0 12px rgba(255,214,0,0.08); }
  }

  .floating-shape.type-plus {
    background: transparent !important;
    position: relative;
  }

  .floating-shape.type-plus::before,
  .floating-shape.type-plus::after {
    content: '';
    position: absolute;
    background: rgba(255,214,0,0.06);
    border-radius: 2px;
  }

  .floating-shape.type-plus::before {
    width: 60%; height: 20%;
    top: 40%; left: 20%;
  }

  .floating-shape.type-plus::after {
    width: 20%; height: 60%;
    top: 20%; left: 40%;
  }
  </style>
</head>
<body>

<div class="hex-grid"></div>
<div class="bg-particles" id="particles"></div>
<div class="floating-shapes" id="floatingShapes"></div>
<div class="noise-overlay"></div>
<div class="pulse-container">
  <div class="pulse-ring"></div>
  <div class="pulse-ring"></div>
  <div class="pulse-ring"></div>
  <div class="pulse-ring"></div>
  <div class="pulse-ring"></div>
</div>
<div class="medical-plus"><div class="medical-plus-inner"></div></div>
<div class="ekg-line">
  <svg viewBox="0 0 1000 32" preserveAspectRatio="none">
    <path d="M0,20 L120,20 L140,20 L155,18 L165,22 L180,20 L200,20 L300,20 L320,20 L335,12 L345,28 L360,20 L380,20 L500,20 L520,20 L535,18 L545,22 L560,20 L580,20 L700,20 L715,20 L730,8 L740,32 L755,20 L770,20 L900,20 L920,20 L935,18 L945,22 L960,20 L1000,20"/>
  </svg>
</div>

<div class="login-wrapper">
  <div class="card-3d-wrap">
    <div class="login-card">
    <div class="login-header">
      <div class="header-inner">
        <div class="logo-wrap">
          <div class="ring-deco"></div>
          <div class="ring-deco"></div>
          <div class="ring-deco"></div>
          <img src="images/school-logo.png" alt="ISNM Logo">
        </div>
        <div class="title-3d" data-text="ISNM Portal">ISNM Portal</div>
        <p class="school-name">Iganga School of Nursing and Midwifery</p>
        <p class="sign-in-label">Staff Sign In</p>

        <?php if ($requested_position): ?>
          <div>
            <span class="role-badge">
              <i class="fas fa-sitemap"></i> <?php echo htmlspecialchars($requested_position); ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="tab-bar" role="tablist">
      <button type="button" class="tab-btn active" id="tab-staff" role="tab">
        <i class="fas fa-user-tie"></i> Staff Login
      </button>
    </div>

    <div class="login-body">
      <?php if ($login_error): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>

      <?php if ($login_success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($login_success); ?>
        </div>
      <?php endif; ?>

      <div class="tab-panel active" id="panel-staff">
        <form method="POST" action="auth-handler.php">
          <input type="hidden" name="action" value="staff_login">
          <?php if ($requested_position): ?>
            <input type="hidden" name="requested_position" value="<?php echo htmlspecialchars($requested_position); ?>">
          <?php endif; ?>
          <?php if ($redirect_url): ?>
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="s-email" class="form-label">
              <i class="fas fa-envelope" style="margin-right: 6px; color: var(--primary);"></i>Email Address
            </label>
            <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" class="form-control" id="s-email" name="email"
                     placeholder="you@isnm.ug" required autocomplete="email"
                     value="<?php echo $suggested_email ? htmlspecialchars($suggested_email) : ''; ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="s-password" class="form-label">
              <i class="fas fa-lock" style="margin-right: 6px; color: var(--primary);"></i>Password
            </label>
            <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" class="form-control" id="s-password" name="password"
                     placeholder="Enter your password" required autocomplete="current-password">
              <button type="button" class="password-toggle" id="toggle-s-password" tabindex="-1" aria-label="Toggle password visibility">
                <i class="fas fa-eye"></i>
              </button>
              <div class="input-highlight"></div>
            </div>
          </div>

          <button type="submit" class="btn-login" style="margin-top:4px;" id="btn-submit">
            <span class="spinner-layer"></span>
            <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login to Staff Portal</span>
          </button>
        </form>
      </div>
    </div>

    <div class="login-footer">
      <div class="panel-divider"></div>

      <div class="help-links">
        <a href="staff-password-reset.php"><i class="fas fa-key"></i>Forgot Password?</a>
        <a href="organogram.php"><i class="fas fa-arrow-left"></i>Back to Organogram</a>
      </div>

      <div class="info-block">
        <div class="block-title"><i class="fas fa-university"></i> About ISNM</div>
        <div style="font-size: 0.8rem; line-height: 1.6;">
          <strong>Iganga School of Nursing and Midwifery</strong> , GOVERNMENT OF UGANDA<br>
          P.O. Box 416, Iganga District , Tel: +256 703 204722
        </div>
      </div>
    </div>
  </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // ════════════════════════════════════════════════════════════════
  // 1. FLOATING GEOMETRIC SHAPES
  // ════════════════════════════════════════════════════════════════
  const shapesContainer = document.getElementById('floatingShapes');
  if (shapesContainer) {
    const shapeTypes = ['circle', 'blob', 'rect', 'pill', 'plus'];
    const colors = [
      'rgba(255,214,0,0.08)', 'rgba(255,255,255,0.04)',
      'rgba(26,35,126,0.06)', 'rgba(13,71,161,0.05)',
      'rgba(249,168,37,0.07)', 'rgba(255,255,255,0.03)'
    ];
    for (let i = 0; i < 18; i++) {
      const shape = document.createElement('div');
      const type = shapeTypes[Math.floor(Math.random() * shapeTypes.length)];
      shape.className = 'floating-shape type-' + type;
      const size = 18 + Math.random() * 60;
      if (type !== 'tri' && type !== 'plus') {
        shape.style.width = type === 'pill' ? (size * 2.2) + 'px' : size + 'px';
        shape.style.height = size + 'px';
        shape.style.background = colors[Math.floor(Math.random() * colors.length)];
        if (type === 'blob') {
          shape.style.background = 'linear-gradient(135deg, rgba(255,214,0,0.08), rgba(26,35,126,0.04))';
        }
      }
      if (type === 'plus') {
        shape.style.width = (size * 0.8) + 'px';
        shape.style.height = (size * 0.8) + 'px';
      }
      shape.style.left = Math.random() * 100 + '%';
      shape.style.animationDuration = (22 + Math.random() * 35) + 's';
      shape.style.animationDelay = Math.random() * 15 + 's';
      shapesContainer.appendChild(shape);
    }
  }

  // ════════════════════════════════════════════════════════════════
  // 2. FLOATING PARTICLES (enhanced)
  // ════════════════════════════════════════════════════════════════
  const container = document.getElementById('particles');
  const particleCount = 20;
  const pShapes = ['circle', 'star'];
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    const shape = pShapes[Math.floor(Math.random() * pShapes.length)];
    particle.className = 'particle' + (shape === 'star' ? ' type-star' : '');
    const size = 2 + Math.random() * 5;
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDuration = (12 + Math.random() * 20) + 's';
    particle.style.animationDelay = Math.random() * 10 + 's';
    if (shape === 'star') {
      particle.style.borderBottomWidth = (size * 1.5) + 'px';
      particle.style.borderLeftWidth = (size * 0.9) + 'px';
      particle.style.borderRightWidth = (size * 0.9) + 'px';
    }
    container.appendChild(particle);
  }

  // ════════════════════════════════════════════════════════════════
  // 3. 3D TILT ON CARD
  // ════════════════════════════════════════════════════════════════
  const card3dWrap = document.querySelector('.card-3d-wrap');
  const loginCard = document.querySelector('.card-3d-wrap .login-card');
  if (card3dWrap && loginCard) {
    let isTilting = false;
    card3dWrap.addEventListener('mousemove', function(e) {
      const rect = this.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      const rotateX = ((y - centerY) / centerY) * -5;
      const rotateY = ((x - centerX) / centerX) * 5;
      loginCard.style.transform = 'rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg)';
    });
    card3dWrap.addEventListener('mouseleave', function() {
      loginCard.style.transform = 'rotateX(0deg) rotateY(0deg)';
    });
  }

  // ════════════════════════════════════════════════════════════════
  // 4. MAGNETIC BUTTON
  // ════════════════════════════════════════════════════════════════
  const loginBtn = document.querySelector('.btn-login');
  if (loginBtn && !loginBtn.classList.contains('loading')) {
    loginBtn.addEventListener('mousemove', function(e) {
      if (this.classList.contains('loading')) return;
      const rect = this.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      this.style.setProperty('--mx', (x * 0.25) + 'px');
      this.style.setProperty('--my', (y * 0.25) + 'px');
      this.style.transform = 'translate(var(--mx), var(--my))';
    });
    loginBtn.addEventListener('mouseleave', function() {
      this.style.transform = '';
    });
  }

  // ════════════════════════════════════════════════════════════════
  // 5. RIPPLE EFFECT ON BUTTONS
  // ════════════════════════════════════════════════════════════════
  document.querySelectorAll('.btn-login').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.className = 'ripple-effect';
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height) * 1.2;
      ripple.style.width = size + 'px';
      ripple.style.height = size + 'px';
      ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
      ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
      this.appendChild(ripple);
      setTimeout(function() { ripple.remove(); }, 700);
    });
  });

  // ════════════════════════════════════════════════════════════════
  // 6. TYPING EFFECT ON SUBTITLE
  // ════════════════════════════════════════════════════════════════
  const subtitle = document.querySelector('.school-name');
  if (subtitle) {
    const originalText = subtitle.textContent;
    subtitle.textContent = '';
    subtitle.style.display = 'inline';
    let charIndex = 0;
    function typeChar() {
      if (charIndex < originalText.length) {
        subtitle.textContent += originalText.charAt(charIndex);
        charIndex++;
        setTimeout(typeChar, 18 + Math.random() * 25);
      } else {
        const cursor = document.createElement('span');
        cursor.className = 'typing-cursor';
        subtitle.parentNode.insertBefore(cursor, subtitle.nextSibling);
      }
    }
    setTimeout(typeChar, 600);
  }

  // ════════════════════════════════════════════════════════════════
  // 7. PASSWORD VISIBILITY TOGGLE
  // ════════════════════════════════════════════════════════════════
  const toggleBtn = document.getElementById('toggle-s-password');
  const pwdInput = document.getElementById('s-password');
  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', function() {
      const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
      pwdInput.setAttribute('type', type);
      this.querySelector('i').className = 'fas fa-' + (type === 'password' ? 'eye' : 'eye-slash');
    });
  }

  // ════════════════════════════════════════════════════════════════
  // 8. LOADING STATE ON SUBMIT
  // ════════════════════════════════════════════════════════════════
  const form = document.querySelector('form');
  const btn = document.getElementById('btn-submit');
  if (form && btn) {
    form.addEventListener('submit', function() {
      btn.classList.add('loading');
      // Disable magnetic when loading
      btn.style.transform = '';
    });
  }

  // ════════════════════════════════════════════════════════════════
  // 9. iOS VIEWPORT GUARD
  // ════════════════════════════════════════════════════════════════
  const m = 'width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no';
  document.querySelectorAll('input[type="email"],input[type="password"],input[type="text"]').forEach(function(el) {
    el.addEventListener('focus', function() { 
      document.querySelector('meta[name=viewport]').setAttribute('content', m); 
    });
    el.addEventListener('blur', function() { 
      document.querySelector('meta[name=viewport]').setAttribute('content', m + ',shrink-to-fit=no'); 
    });
  });
});
</script>
</body>
</html>