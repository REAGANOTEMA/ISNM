<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_path', SESSION_COOKIE_PATH);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    $https = false;
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        $https = true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $https = in_array(strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']), ['https', 'wss'], true);
    }
    if ($https) {
        ini_set('session.cookie_secure', 1);
    } else {
        ini_set('session.cookie_secure', 0);
    }
    session_start();
}

// Generate CSRF token for login form BEFORE rendering
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$student_role = isset($_GET['student_role']) ? trim($_GET['student_role']) : '';
$allowed_student_roles = ['student', 'nursing', 'midwifery'];
if ($student_role !== '' && in_array(strtolower($student_role), $allowed_student_roles)) {
    $_SESSION['student_role'] = urldecode($student_role);
}

$_SESSION['student_login_allowed'] = true;

$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
$student_hint  = $_SESSION['student_role'] ?? '';

if ($login_error) { unset($_SESSION['error']); }
if ($login_success) { unset($_SESSION['success']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Login | ISNM</title>
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link rel="stylesheet" href="css/3d-buttons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <style>
  :root {
    --primary: #2e7d32;
    --primary-dark: #1b5e20;
    --primary-mid: #388e3c;
    --accent: #ffd600;
    --accent-dark: #f9a825;
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
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    position: relative;
    background: #1a2e1a;
    background-image: url('images/loginimage.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: 
      linear-gradient(135deg,
        rgba(27,94,32,0.85) 0%,
        rgba(20,65,24,0.78) 40%,
        rgba(10,30,12,0.82) 100%
      );
    z-index: 0;
    pointer-events: none;
  }

  .bg-particles {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    pointer-events: none; overflow: hidden; z-index: 0;
  }
  
  .particle {
    position: absolute; border-radius: 50%;
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
    width: 100%; max-width: 520px; margin: 0 auto; position: relative; z-index: 1;
  }

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

  .login-header {
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    color: #fff;
    padding: 48px 30px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  
  .login-header::before {
    content: '';
    position: absolute; top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: 
      radial-gradient(circle at 30% 40%, rgba(255,255,255,0.06) 0%, transparent 50%),
      radial-gradient(circle at 70% 60%, rgba(255,255,255,0.04) 0%, transparent 50%);
    animation: rotateBg 40s linear infinite;
  }
  
  @keyframes rotateBg {
    from { transform: rotate(0deg); } to { transform: rotate(360deg); }
  }
  
  .header-inner { position: relative; z-index: 2; }

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
    z-index: -1; opacity: 0.7;
  }
  
  .logo-wrap img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }

  .login-header h1 {
    font-size: 1.85rem; font-weight: 800; margin: 0 0 6px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.85) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .login-header p { opacity: 0.88; font-size: 0.92rem; font-weight: 400; }
  
  .hint-pill {
    display: inline-block;
    margin-top: 12px;
    padding: 5px 16px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 20px;
    font-size: 0.8rem; font-weight: 500;
  }

  .login-body { 
    padding: 32px 30px 28px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
  }

  .form-group { margin-bottom: 18px; }
  
  .form-label {
    font-weight: 600; color: var(--text-dark);
    margin-bottom: 8px; font-size: 0.9rem; display: block;
  }
  
  .input-group { position: relative; }
  
  .input-group-text {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 46px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid var(--border);
    border-right: none;
    border-radius: 12px 0 0 12px;
    color: var(--text-light);
    font-size: 1rem;
    z-index: 2;
    transition: all 0.2s ease;
  }
  
  .input-group:focus-within .input-group-text {
    border-color: var(--primary);
    color: var(--primary);
    background: linear-gradient(180deg, #e8f5e9 0%, #c8e6c9 100%);
  }
  
  .form-control {
    border: 2px solid var(--border);
    border-radius: 0 12px 12px 0;
    padding: 13px 14px 13px 46px;
    font-size: 14px;
    transition: all 0.25s ease;
    background: var(--bg-light);
    height: auto;
  }
  
  .form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(46,125,50,0.1);
    outline: none;
  }
  
  .form-control::placeholder { color: var(--text-light); }
  
  .form-text {
    font-size: 0.8rem; color: var(--text-mid);
    margin-top: 4px; font-weight: 400;
  }

  .first-login-section {
    background: linear-gradient(135deg, #fff8e1, #fff3cd);
    border: 1px solid #ffe082;
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 18px;
    transition: all 0.3s ease;
  }

  .first-login-section .section-divider {
    display: flex; align-items: center;
    margin-bottom: 10px;
  }

  .first-login-section .section-divider span {
    font-size: 0.9rem; font-weight: 600;
    color: #e65100;
  }

  .first-login-section .section-divider span i {
    margin-right: 6px;
  }

  .first-login-section .section-help {
    font-size: 0.8rem; color: #bf360c;
    margin-bottom: 14px;
  }

  .first-login-section .form-group { margin-bottom: 12px; }

  .first-login-section .form-control {
    background: #fff;
  }

  .btn-login {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; border: none;
    border-radius: 12px; padding: 15px 24px;
    font-size: 15px; font-weight: 700;
    width: 100%; cursor: pointer;
    position: relative; overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(46,125,50,0.3), inset 0 1px 0 rgba(255,255,255,0.15);
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
    box-shadow: 0 6px 20px rgba(46,125,50,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
    transform: translateY(-1px);
  }
  
  .btn-login:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(46,125,50,0.3), inset 0 2px 4px rgba(0,0,0,0.1);
  }

  .info-block {
    border-radius: 12px; padding: 18px 20px;
    margin-top: 20px; font-size: 0.83rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border-left: 3px solid var(--primary);
    color: var(--text-dark);
  }
  
  .info-block .block-title {
    font-weight: 600; margin-bottom: 8px;
    color: var(--primary-dark);
    display: flex; align-items: center; gap: 6px;
  }

  .link-row {
    margin-top: 18px;
    display: flex; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
  }
  
  .link-row a:not(.btn-3d) {
    color: var(--primary); text-decoration: none;
    font-size: 0.88rem; transition: color 0.2s ease;
  }
  
  .link-row a:not(.btn-3d):hover { color: var(--primary-dark); }
  .link-row a:not(.btn-3d) i { margin-right: 5px; }

  .alert {
    border-radius: 10px; margin-bottom: 18px;
    border: none; padding: 12px 16px;
    font-size: 0.88rem;
  }
  
  .alert-danger { background: #ffebee; color: #c62828; border-left: 4px solid #ef5350; }
  .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #66bb6a; }

  @media(max-width:768px){
    .login-card { border-radius: 20px; }
    .login-header { padding: 36px 24px 32px; }
    .login-body { padding: 24px 20px; }
    .login-header h1 { font-size: 1.5rem; }
  }
  
  @media(max-width:480px){
    .login-card { border-radius: 16px; }
    .login-header { padding: 30px 18px 26px; }
    .login-body { padding: 20px 16px; }
    .logo-wrap { width: 85px; height: 85px; }
    .logo-wrap img { width: 68px; height: 68px; }
    .link-row { flex-direction: column; gap: 8px; }
  }

  /* ═══════════════════════════════════════════════════════════════
     PREMIUM MODERN ENHANCEMENTS
     ═══════════════════════════════════════════════════════════════ */

  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse at 15% 50%, rgba(255,214,0,0.05) 0%, transparent 55%),
      radial-gradient(ellipse at 85% 30%, rgba(255,255,255,0.04) 0%, transparent 55%),
      radial-gradient(ellipse at 50% 80%, rgba(46,125,50,0.07) 0%, transparent 55%);
    pointer-events: none;
    z-index: 0;
    animation: ambientGlow 10s ease-in-out infinite alternate;
  }

  @keyframes ambientGlow {
    0% { opacity: 0.5; }
    100% { opacity: 1; }
  }

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
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 60%);
    pointer-events: none;
  }

  .hint-pill {
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    transition: all 0.3s ease;
    position: relative;
  }

  .hint-pill:hover {
    background: rgba(255,255,255,0.2) !important;
    transform: scale(1.03);
  }

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

  .form-group {
    animation: slideUpFade 0.5s ease-out backwards;
  }

  .form-group:nth-child(1) { animation-delay: 0.1s; }
  .form-group:nth-child(2) { animation-delay: 0.15s; }
  .form-group:nth-child(3) { animation-delay: 0.2s; }
  .form-group:nth-child(4) { animation-delay: 0.25s; }
  .btn-login { animation: slideUpFade 0.5s ease-out 0.3s backwards; }
  .info-block { animation: slideUpFade 0.5s ease-out 0.4s backwards; }
  .link-row { animation: slideUpFade 0.5s ease-out 0.45s backwards; }

  @keyframes slideUpFade {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }

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

  .login-header h1 {
    position: relative;
    display: inline-block;
  }

  .login-header h1::after {
    content: '';
    position: absolute;
    bottom: -2px; left: 10%; right: 10%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  }

  @media (min-width: 1200px) {
    .login-wrapper { max-width: 540px; }
  }

  @media(max-width:380px) {
    .login-body { padding: 16px 12px; }
    .form-control { padding: 12px 14px 12px 46px; font-size: 13px; }
  }
  /* ── Ultra-Compact Mobile (480px and below) ── */
  @media(max-width:480px){
    body{min-height:100dvh;padding:12px}
    .login-wrapper{padding:0}
    .login-card{border-radius:18px}
    .login-header{padding:28px 18px 24px}
    .login-header h1{font-size:1.35rem}
    .login-header p{font-size:0.82rem}
    .logo-wrap{width:80px;height:80px;margin-bottom:14px}
    .logo-wrap img{width:64px;height:64px}
    .logo-wrap .ring-deco:nth-child(1){top:-6px;left:-6px;right:-6px;bottom:-6px}
    .logo-wrap .ring-deco:nth-child(2){top:-12px;left:-12px;right:-12px;bottom:-12px}
    .logo-wrap .ring-deco:nth-child(3){top:-18px;left:-18px;right:-18px;bottom:-18px}
    .hint-pill{font-size:0.72rem;padding:4px 14px}
    .login-body{padding:22px 16px 22px}
    .form-group{margin-bottom:14px}
    .form-label{font-size:0.82rem;margin-bottom:6px}
    .input-group-text{width:42px;font-size:0.9rem}
    .form-control{padding:13px 12px 13px 42px;font-size:16px;min-height:48px;border-radius:0 10px 10px 0}
    .input-group-text{border-radius:10px 0 0 10px}
    .first-login-section{padding:12px 14px;margin-bottom:14px}
    .first-login-section .section-divider span{font-size:0.82rem}
    .first-login-section .section-help{font-size:0.76rem;margin-bottom:10px}
    .btn-login{padding:14px 20px;font-size:14px;border-radius:10px;min-height:48px}
    .info-block{padding:14px 16px;margin-top:14px;font-size:0.78rem}
    .info-block .block-title{font-size:0.85rem}
    .link-row{margin-top:14px;gap:10px}
    .link-row a:not(.btn-3d){font-size:0.82rem}
    .btn-3d-sm{padding:10px 14px;font-size:11px;border-radius:10px;min-height:44px}
    .alert{padding:10px 14px;font-size:0.82rem;border-radius:10px;margin-bottom:14px}
    .back-home-link{font-size:0.8rem}
    .form-text{font-size:0.75rem}
    .password-toggle{width:44px;height:44px;display:flex;align-items:center;justify-content:center;padding:0;font-size:1.1rem;right:6px;top:50%;transform:translateY(-50%)}
    .login-card::before,
    .login-card::after{width:40px;height:40px}
  }
  /* ── Tiny screens (400px and below) ── */
  @media(max-width:400px){
    body{padding:8px}
    .login-card{border-radius:14px}
    .login-header{padding:22px 14px 20px}
    .login-header h1{font-size:1.15rem}
    .login-header p{font-size:0.76rem}
    .logo-wrap{width:70px;height:70px;margin-bottom:10px}
    .logo-wrap img{width:56px;height:56px}
    .hint-pill{font-size:0.68rem;padding:3px 12px}
    .login-body{padding:16px 12px 18px}
    .form-group{margin-bottom:12px}
    .form-label{font-size:0.78rem}
    .input-group-text{width:38px;font-size:0.82rem}
    .form-control{font-size:16px;padding:12px 10px 12px 38px;min-height:46px}
    .first-login-section{padding:10px 12px}
    .first-login-section .section-divider span{font-size:0.78rem}
    .first-login-section .section-help{font-size:0.72rem}
    .btn-login{padding:12px 16px;font-size:13px;min-height:46px;border-radius:10px}
    .info-block{padding:12px 14px;font-size:0.74rem}
    .link-row{gap:8px}
    .btn-3d-sm{padding:8px 12px;font-size:10px;min-height:40px;border-radius:8px}
    .alert{font-size:0.78rem;padding:8px 12px}
    .login-card::before,
    .login-card::after{width:30px;height:30px}
  }
  /* ── Extra tiny screens (360px and below) ── */
  @media(max-width:360px){
    body{padding:6px}
    .login-card{border-radius:12px}
    .login-header{padding:18px 10px 16px}
    .login-header h1{font-size:1rem}
    .login-header p{font-size:0.7rem}
    .logo-wrap{width:60px;height:60px;margin-bottom:8px}
    .logo-wrap img{width:48px;height:48px}
    .hint-pill{font-size:0.62rem;padding:2px 10px}
    .login-body{padding:12px 8px 14px}
    .form-label{font-size:0.74rem}
    .input-group-text{width:34px;font-size:0.78rem}
    .form-control{font-size:16px;padding:10px 8px 10px 34px;min-height:44px}
    .btn-login{padding:10px 14px;font-size:12px;min-height:44px}
    .btn-3d-sm{padding:7px 10px;font-size:9px;min-height:36px}
    .info-block{padding:10px 12px;font-size:0.7rem}
    .link-row{gap:6px}
    .form-group{margin-bottom:10px}
  }
  </style>
</head>
<body>

<div class="bg-particles" id="particles"></div>

<div class="text-center mb-3">
  <a href="organogram.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;font-size:0.85rem;color:#fff;text-decoration:none;border-radius:8px;background:rgba(255,255,255,0.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);transition:all 0.3s ease;">
    <i class="fas fa-sitemap"></i> Back to Organogram
  </a>
</div>
<div class="login-wrapper">
  <div class="login-card animate-on-scroll">
    <div class="login-header">
      <div class="header-inner">
        <div class="logo-wrap">
          <div class="ring-deco"></div>
          <div class="ring-deco"></div>
          <div class="ring-deco"></div>
          <img src="images/school-logo.png" alt="ISNM Logo">
        </div>
        <h1>Student Portal</h1>
        <p>Secure student access , ISNM Learning Management System</p>
        <?php if ($student_hint): ?>
          <div class="hint-pill">
            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($student_hint); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="login-body">
      <?php if ($login_error): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($login_error); ?>
        </div>
      <?php endif; ?>
      <?php if ($login_success): ?>
        <div class="alert alert-success" role="alert">
          <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($login_success); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="auth-handler.php" id="login-form">
        <input type="hidden" name="action" value="student_login">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <?php if ($student_hint): ?>
          <input type="hidden" name="student_role" value="<?php echo htmlspecialchars($student_hint); ?>">
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="stu-index">
            <i class="fas fa-id-card" style="margin-right: 6px; color: var(--primary);"></i>Student / Index Number
          </label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
            <input id="stu-index" name="index_number" class="form-control" type="text" required 
                   placeholder="e.g. STU202600001 or U001/CM/056/16" autocomplete="username">
          </div>
          <div class="form-text">Enter your student number or UNMEB index number</div>
        </div>

        <div id="first-login-fields" class="first-login-section" style="display: none;">
          <div class="section-divider">
            <span><i class="fas fa-user-plus"></i> First-Time Verification</span>
          </div>
          <p class="section-help">Enter your registered full name and phone number to verify your identity.</p>

          <div class="form-group">
            <label class="form-label" for="stu-name">
              <i class="fas fa-user" style="margin-right: 6px; color: var(--primary);"></i>Full Name
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input id="stu-name" name="full_name" class="form-control" type="text"
                     placeholder="Enter your full name as registered" autocomplete="name">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="stu-phone">
              <i class="fas fa-phone" style="margin-right: 6px; color: var(--primary);"></i>Phone Number
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-phone"></i></span>
              <input id="stu-phone" name="phone_number" class="form-control" type="tel"
                     placeholder="0771234567" autocomplete="tel">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="stu-password">
            <i class="fas fa-lock" style="margin-right: 6px; color: var(--primary);"></i>Password
          </label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="stu-password" name="password" class="form-control" type="password" 
                   placeholder="Enter your password" autocomplete="current-password">
            <button type="button" class="password-toggle" id="toggle-stu-password" tabindex="-1" aria-label="Toggle password visibility">
              <i class="fas fa-eye"></i>
            </button>
            <div class="input-highlight"></div>
          </div>
          <div class="form-text" id="password-hint">First time? Leave blank and enter your name + phone to verify.</div>
        </div>

        <button type="submit" class="btn-3d btn-yellow btn-3d-block" id="btn-submit">
          <span class="shine"></span>
          <span class="spinner-layer"></span>
          <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login to Student Portal</span>
        </button>
      </form>

      <div class="info-block">
        <div class="block-title"><i class="fas fa-graduation-cap"></i> Student Portal</div>
        <p style="margin-bottom: 6px;">Access your academic records, course materials, and student services.</p>
        <p style="margin-bottom: 0; font-size: 0.78rem;">Your session is secure and encrypted.</p>
      </div>

    <div class="link-row">
        <a href="student-forgot-password.php" class="btn-3d btn-blue btn-3d-sm"><i class="fas fa-key"></i> Forgot Password</a>
        <a href="organogram.php" class="btn-3d btn-green btn-3d-sm"><i class="fas fa-arrow-left"></i> Staff login</a>
        <a href="index.php" class="btn-3d btn-chocolate btn-3d-sm"><i class="fas fa-home"></i> Homepage</a>
    </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // ── Floating particles (enhanced) ──
  const container = document.getElementById('particles');
  const particleCount = 18;
  const shapes = ['circle', 'star'];
  
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    const shape = shapes[Math.floor(Math.random() * shapes.length)];
    particle.className = 'particle' + (shape === 'star' ? ' type-star' : '');
    const size = 2 + Math.random() * 5;
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDuration = (12 + Math.random() * 18) + 's';
    particle.style.animationDelay = Math.random() * 10 + 's';
    if (shape === 'star') {
      particle.style.borderBottomWidth = (size * 1.5) + 'px';
      particle.style.borderLeftWidth = (size * 0.9) + 'px';
      particle.style.borderRightWidth = (size * 0.9) + 'px';
    }
    container.appendChild(particle);
  }

  // ── Password visibility toggle ──
  const toggleBtn = document.getElementById('toggle-stu-password');
  const pwdInput = document.getElementById('stu-password');
  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', function() {
      const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
      pwdInput.setAttribute('type', type);
      this.querySelector('i').className = 'fas fa-' + (type === 'password' ? 'eye' : 'eye-slash');
    });
  }

  // ── Auto-detect student account type ──
  const indexInput = document.getElementById('stu-index');
  const firstLoginFields = document.getElementById('first-login-fields');
  const nameInput = document.getElementById('stu-name');
  const phoneInput = document.getElementById('stu-phone');
  const passwordHint = document.getElementById('password-hint');
  let checkTimeout = null;

  if (indexInput && firstLoginFields) {
    indexInput.addEventListener('blur', function() {
      const val = this.value.trim();
      if (val.length < 5) { firstLoginFields.style.display = 'none'; return; }

      if (checkTimeout) clearTimeout(checkTimeout);
      checkTimeout = setTimeout(function() {
        fetch('auth-handler.php?action=check_student&index_number=' + encodeURIComponent(val))
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (data.exists && data.has_password) {
              firstLoginFields.style.display = 'none';
              if (nameInput) nameInput.required = false;
              if (phoneInput) phoneInput.required = false;
              if (passwordHint) passwordHint.textContent = 'Enter your password to sign in.';
              if (pwdInput) pwdInput.placeholder = 'Enter your password';
              if (pwdInput) pwdInput.required = true;
            } else {
              firstLoginFields.style.display = 'block';
              if (nameInput) nameInput.required = true;
              if (phoneInput) phoneInput.required = true;
              if (passwordHint) passwordHint.textContent = 'First time? Leave blank and we\'ll verify your details.';
              if (pwdInput) pwdInput.placeholder = 'Leave blank for first login';
              if (pwdInput) pwdInput.required = false;
            }
          })
          .catch(function() {
            // fallback: show all fields
            firstLoginFields.style.display = 'block';
          });
      }, 300);
    });

    indexInput.addEventListener('input', function() {
      firstLoginFields.style.display = 'none';
    });
  }

  // ── Ensure first-login fields are correctly toggled on submit ──
  const form = document.getElementById('login-form');
  const btn = document.getElementById('btn-submit');
  if (form && btn) {
    form.addEventListener('submit', function() {
      // If first-login fields are visible, ensure they're required
      if (firstLoginFields && firstLoginFields.style.display !== 'none') {
        if (nameInput) nameInput.required = true;
        if (phoneInput) phoneInput.required = true;
        if (pwdInput) pwdInput.required = false;
      } else {
        // Hidden -> not needed, but password is required
        if (nameInput) nameInput.required = false;
        if (phoneInput) phoneInput.required = false;
      }
      btn.classList.add('loading');
    });
  }

  // ── iOS viewport guard ──
  const m = 'width=device-width,initial-scale=1.0';
  document.querySelectorAll('input[type="email"],input[type="password"],input[type="text"],input[type="tel"]').forEach(function(el) {
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