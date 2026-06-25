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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1E88E5">
<title>Staff Portal Sign-In | ISNM</title>
<link rel="icon" type="image/x-icon" href="images/school-logo.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --blue:#1E88E5;
  --blue-dark:#0D47A1;
  --blue-deeper:#072A5E;
  --teal:#0FA3B1;
  --teal-light:#3BC9D9;
  --teal-glow:rgba(15,163,177,0.25);
  --cream:#F5E6C8;
  --cream-light:#FFF8E7;
  --cream-glow:rgba(245,230,200,0.20);
  --white:#FFFFFF;
  --glass-bg:rgba(255,255,255,0.09);
  --glass-border:rgba(255,255,255,0.12);
  --glass-shadow:rgba(0,0,0,0.18);
}
body{
  font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
  min-height:100vh;display:flex;overflow:hidden;
  background:linear-gradient(135deg,var(--blue-deeper) 0%,var(--blue-dark) 35%,var(--blue) 65%,var(--blue-dark) 100%);
  -webkit-font-smoothing:antialiased;
  perspective:1600px;
}
/* ── FULL BLEED BACKGROUND ── */
.login-bg{
  position:fixed;inset:0;z-index:0;
  background:url('images/classroom-photo-certificates-in-nurses-and-diploma.jpeg') center/cover no-repeat;
  transform:scale(1.02);
  animation:bgParallax 25s ease-in-out infinite alternate;
  opacity:0.65
}
@keyframes bgParallax{
  0%{transform:scale(1.02) translateX(0)}
  100%{transform:scale(1.06) translateX(-12px)}
}
.login-bg-overlay{
  position:fixed;inset:0;z-index:1;
  background:
    linear-gradient(135deg, rgba(7,42,94,0.30) 0%, rgba(13,71,161,0.20) 40%, rgba(30,136,229,0.15) 70%, rgba(13,71,161,0.25) 100%);
  pointer-events:none
}
.login-bg-vignette{
  position:fixed;inset:0;z-index:1;
  box-shadow:inset 0 0 150px rgba(0,0,0,0.20), inset 0 0 300px rgba(0,0,0,0.05);
  pointer-events:none
}
/* ── VOLUMETRIC LIGHT RAY ── */
.volumetric-ray{
  position:fixed;top:0;left:0;width:100%;height:100%;z-index:1;
  pointer-events:none;
  background:linear-gradient(100deg,
    rgba(255,255,255,0.03) 0%,transparent 12%,transparent 70%,
    rgba(15,163,177,0.01) 85%,transparent 100%);
  animation:raySweep 20s ease-in-out infinite alternate;
  will-change:transform
}
@keyframes raySweep{
  0%{transform:rotate(-4deg) scale(1.4) translateX(-3%)}
  100%{transform:rotate(4deg) scale(1.4) translateX(3%)}
}
/* ── VOLUMETRIC BLOOM BEHIND TITLE ── */
.title-bloom{
  position:absolute;top:18%;left:30%;z-index:0;
  width:500px;height:500px;
  background:radial-gradient(circle,rgba(245,230,200,0.12) 0%,rgba(30,136,229,0.04) 30%,transparent 70%);
  filter:blur(40px);pointer-events:none;animation:bloomPulse 6s ease-in-out infinite alternate;
  will-change:transform,opacity
}
@keyframes bloomPulse{
  0%{opacity:0.5;transform:scale(0.95)}
  100%{opacity:1;transform:scale(1.1)}
}
/* ── SPLIT LAYOUT ── */
.login-left{
  width:55%;min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  position:relative;z-index:3;padding:40px
}
.login-right{
  width:45%;min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  position:relative;z-index:3;padding:40px
}
/* ── CARD AMBIENT GLOW ── */
.card-glow{
  position:absolute;top:50%;right:8%;z-index:0;
  width:520px;height:520px;
  background:radial-gradient(circle,rgba(30,136,229,0.10) 0%,rgba(15,163,177,0.04) 40%,transparent 70%);
  filter:blur(30px);pointer-events:none;
  animation:glowPulse 5s ease-in-out infinite alternate;
  will-change:transform,opacity
}
@keyframes glowPulse{
  0%{opacity:0.5;transform:scale(0.92)}
  100%{opacity:1;transform:scale(1.08)}
}
/* ── HERO CONTENT (left) ── */
.hero-content{
  position:relative;z-index:2;max-width:560px;
  transform:perspective(1200px) rotateX(1.2deg);
  transform-origin:center bottom
}
.hero-content>*{
  animation:textReveal 1s cubic-bezier(0.16,1,0.3,1) forwards;
  opacity:0
}
.hero-badge{
  display:inline-flex;align-items:center;gap:10px;
  padding:7px 18px;border-radius:20px;
  background:rgba(255,255,255,0.06);
  border:1px solid rgba(255,255,255,0.08);
  font-size:11px;font-weight:500;color:rgba(255,255,255,0.75);
  margin-bottom:24px;text-transform:uppercase;letter-spacing:2.2px;
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  animation-delay:0.1s
}
.hero-badge i{color:var(--teal-light);font-size:11px}
.hero-title{
  position:relative;font-size:3.4rem;font-weight:800;
  font-family:'Playfair Display',Georgia,serif;
  color:var(--cream);line-height:1.08;margin-bottom:18px;
  letter-spacing:-0.8px;
  animation-delay:0.2s;
  text-shadow:
    0 1px 0 var(--cream-light),
    0 2px 4px rgba(0,0,0,0.15),
    0 4px 8px rgba(0,0,0,0.12),
    0 8px 16px rgba(0,0,0,0.08),
    0 12px 24px rgba(0,0,0,0.05),
    0 0 40px var(--cream-glow);
  -webkit-font-smoothing:antialiased;
  overflow:hidden
}
.hero-title::after{
  content:'';position:absolute;top:-15%;left:-60%;width:50%;height:130%;
  background:linear-gradient(90deg,transparent 0%,rgba(255,248,231,0.06) 40%,rgba(255,248,231,0.12) 50%,rgba(255,248,231,0.06) 60%,transparent 100%);
  transform:skewX(-18deg);pointer-events:none;
  animation:shimmerSweep 5s ease-in-out infinite
}
@keyframes shimmerSweep{
  0%{left:-60%}
  50%{left:150%}
  100%{left:-60%}
}
.hero-title span{
  background:linear-gradient(135deg,var(--cream) 0%,var(--cream-light) 20%,#F5D6A8 40%,var(--cream-light) 60%,var(--cream) 80%,var(--cream-light) 100%);
  background-size:300% 300%;
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
  animation:creamShimmer 5s ease-in-out infinite;
  filter:drop-shadow(0 0 30px var(--cream-glow)) drop-shadow(0 2px 8px rgba(0,0,0,0.2))
}
@keyframes creamShimmer{
  0%{background-position:0% 50%}
  50%{background-position:100% 50%}
  100%{background-position:0% 50%}
}
.hero-sub{
  font-size:0.95rem;color:rgba(255,255,255,0.70);
  line-height:1.7;margin-bottom:28px;font-weight:300;
  letter-spacing:0.15px;animation-delay:0.35s
}
.hero-motto{
  padding:14px 20px;border-radius:12px;
  background:rgba(255,255,255,0.04);
  border-left:3px solid var(--teal);
  font-size:13px;color:rgba(255,255,255,0.60);
  font-weight:400;font-style:italic;line-height:1.7;
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  letter-spacing:0.3px;animation-delay:0.5s;
  text-shadow:0 0 20px rgba(15,163,177,0.06)
}
.hero-motto strong{color:var(--teal-light);font-weight:600}
.hero-stats{
  display:flex;gap:28px;margin-top:32px;
  animation-delay:0.65s
}
.hero-stat{text-align:center}
.hero-stat-num{
  font-size:1.5rem;font-weight:700;color:var(--white);
  line-height:1;text-shadow:
    0 2px 12px rgba(0,0,0,0.25),
    0 0 30px rgba(15,163,177,0.08);
  letter-spacing:-0.5px
}
.hero-stat-label{
  font-size:10px;color:rgba(255,255,255,0.50);
  text-transform:uppercase;letter-spacing:1.2px;margin-top:5px;
  font-weight:500
}
@keyframes textReveal{
  0%{opacity:0;transform:translateY(18px)}
  100%{opacity:1;transform:translateY(0)}
}
/* ── GLASSMORPHISM CARD (right) ── */
.login-card-wrap{
  width:100%;max-width:420px;position:relative;z-index:1;
  animation:cardIn 1s cubic-bezier(0.16,1,0.3,1) forwards,
             cardFloat 7s ease-in-out 1s infinite;
  transform-origin:center center;
  will-change:transform
}
@keyframes cardIn{
  0%{opacity:0;transform:translateY(40px) scale(0.95) perspective(1200px) rotateX(3deg)}
  100%{opacity:1;transform:translateY(0) scale(1) perspective(1200px) rotateX(1.5deg)}
}
@keyframes cardFloat{
  0%,100%{transform:perspective(1200px) rotateX(1.5deg) translateY(0)}
  50%{transform:perspective(1200px) rotateX(1.5deg) translateY(-7px)}
}
.login-card{
  background:var(--glass-bg);
  backdrop-filter:blur(28px) saturate(1.1);
  -webkit-backdrop-filter:blur(28px) saturate(1.1);
  border:1px solid var(--glass-border);
  border-radius:22px;
  padding:38px 34px 32px;
  position:relative;
  box-shadow:
    0 2px 4px rgba(0,0,0,0.02),
    0 8px 24px rgba(0,0,0,0.04),
    0 20px 48px rgba(0,0,0,0.06),
    0 40px 80px rgba(0,0,0,0.04),
    inset 0 1px 0 rgba(255,255,255,0.12);
  transition:box-shadow 0.4s
}
.login-card::before{
  content:'';position:absolute;top:0;left:18px;right:18px;height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent)
}
.login-card::after{
  content:'';position:absolute;bottom:0;left:18px;right:18px;height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent)
}
.card-logo-wrap{
  display:flex;align-items:center;gap:14px;margin-bottom:22px
}
.card-logo{
  width:46px;height:46px;border-radius:50%;object-fit:cover;
  border:2px solid rgba(255,255,255,0.15);flex-shrink:0
}
.card-brand h2{
  font-size:1.05rem;font-weight:700;color:var(--white);
  margin:0;line-height:1.2;letter-spacing:-0.1px;
  text-shadow:0 1px 4px rgba(0,0,0,0.15)
}
.card-brand p{
  font-size:10px;color:rgba(255,255,255,0.45);margin:2px 0 0;
  text-transform:uppercase;letter-spacing:0.8px;font-weight:400
}
.card-title{
  font-size:1.25rem;font-weight:700;color:var(--white);
  margin-bottom:4px;letter-spacing:-0.3px;
  text-shadow:0 1px 4px rgba(0,0,0,0.10)
}
.card-subtitle{
  font-size:13px;color:rgba(255,255,255,0.50);
  margin-bottom:24px;line-height:1.5;font-weight:400
}
.form-group{margin-bottom:16px}
.form-label{
  display:block;font-size:10px;font-weight:600;color:rgba(255,255,255,0.60);
  margin-bottom:5px;text-transform:uppercase;letter-spacing:0.8px
}
.input-wrap{
  position:relative;display:flex;align-items:center
}
.input-wrap .input-icon{
  position:absolute;left:14px;color:rgba(255,255,255,0.30);
  font-size:13px;z-index:2;transition:color 0.3s
}
.input-wrap .form-control{
  width:100%;padding:10px 14px 10px 42px;
  border:1px solid rgba(255,255,255,0.08);
  border-radius:11px;font-size:14px;font-family:'Inter',sans-serif;
  background:rgba(0,0,0,0.12);
  transition:all 0.3s;color:var(--white);
  outline:none;
  box-shadow:
    inset 0 2px 4px rgba(0,0,0,0.15),
    inset 0 1px 1px rgba(0,0,0,0.10),
    0 1px 0 rgba(255,255,255,0.04)
}
.input-wrap .form-control:focus{
  border-color:rgba(255,255,255,0.20);
  background:rgba(0,0,0,0.18);
  box-shadow:
    inset 0 2px 4px rgba(0,0,0,0.15),
    0 0 0 3px rgba(30,136,229,0.10),
    0 0 20px rgba(15,163,177,0.04)
}
.input-wrap:focus-within .input-icon{color:var(--teal-light)}
.form-control::placeholder{color:rgba(255,255,255,0.22);font-weight:300}
.password-toggle{
  position:absolute;right:12px;background:none;border:none;
  color:rgba(255,255,255,0.30);cursor:pointer;padding:4px;font-size:13px;z-index:2;
  transition:color 0.3s
}
.password-toggle:hover{color:rgba(255,255,255,0.60)}
.form-options{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:20px
}
.remember-me{
  display:flex;align-items:center;gap:6px;
  font-size:12px;color:rgba(255,255,255,0.50);cursor:pointer;
  font-weight:400;letter-spacing:0.2px
}
.remember-me input[type="checkbox"]{
  width:15px;height:15px;accent-color:var(--teal);border-radius:3px;
  background:rgba(255,255,255,0.05)
}
.forgot-link{
  font-size:12px;color:rgba(255,255,255,0.50);text-decoration:none;
  font-weight:400;transition:color 0.3s;letter-spacing:0.1px
}
.forgot-link:hover{color:var(--teal-light)}
.btn-login{
  width:100%;padding:11px 20px;
  background:linear-gradient(165deg,var(--blue) 0%,var(--blue-dark) 100%);
  color:#fff;border:none;border-radius:11px;
  font-size:14px;font-weight:600;font-family:'Inter',sans-serif;
  cursor:pointer;position:relative;overflow:hidden;
  transition:all 0.3s cubic-bezier(0.16,1,0.3,1);
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:
    0 4px 14px rgba(30,136,229,0.25),
    0 2px 4px rgba(0,0,0,0.10),
    0 8px 20px rgba(0,0,0,0.06),
    inset 0 1px 0 rgba(255,255,255,0.18);
  letter-spacing:0.2px;
  will-change:transform
}
.btn-login:hover{
  transform:translateY(-2px);
  box-shadow:
    0 6px 24px rgba(30,136,229,0.35),
    0 4px 8px rgba(0,0,0,0.12),
    0 12px 30px rgba(0,0,0,0.08),
    inset 0 1px 0 rgba(255,255,255,0.25)
}
.btn-login:active{
  transform:translateY(0);
  box-shadow:
    0 1px 4px rgba(30,136,229,0.15),
    inset 0 2px 4px rgba(0,0,0,0.12)
}
.btn-login .spinner{
  display:none;width:18px;height:18px;
  border:2px solid rgba(255,255,255,0.2);border-top-color:#fff;
  border-radius:50%;animation:spin 0.6s linear infinite
}
.btn-login.loading .spinner{display:inline-block}
.btn-login.loading .btn-text{opacity:0.7}
.btn-login.loading{pointer-events:none}
@keyframes spin{to{transform:rotate(360deg)}}
.divider{
  height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);
  margin:18px 0 16px
}
.footer-links{
  display:flex;justify-content:center;gap:20px;
  font-size:12px
}
.footer-links a{
  color:rgba(255,255,255,0.40);text-decoration:none;
  display:inline-flex;align-items:center;gap:6px;
  transition:color 0.3s;font-weight:400;letter-spacing:0.1px
}
.footer-links a:hover{color:rgba(255,255,255,0.70)}
.alert{
  border-radius:10px;padding:10px 14px;margin-bottom:18px;
  font-size:12px;display:flex;align-items:center;gap:8px;
  border:none;font-weight:400
}
.alert-danger{
  background:rgba(239,68,68,0.08);color:#fca5a5;
  border-left:3px solid rgba(239,68,68,0.4);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)
}
.alert-success{
  background:rgba(34,197,94,0.08);color:#86efac;
  border-left:3px solid rgba(34,197,94,0.4);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)
}
.role-badge{
  display:inline-flex;align-items:center;gap:6px;
  padding:4px 12px;border-radius:16px;
  background:rgba(15,163,177,0.08);border:1px solid rgba(15,163,177,0.12);
  font-size:11px;font-weight:400;color:var(--teal-light);
  margin-top:8px;letter-spacing:0.3px
}
/* ── SCAN LINES (live broadcast texture) ── */
.scan-lines{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.025) 2px,rgba(0,0,0,0.025) 4px)
}
/* ── PARTICLES ── */
.particles{
  position:fixed;inset:0;z-index:1;pointer-events:none;overflow:hidden
}
.particle{
  position:absolute;border-radius:50%;opacity:0;
  background:rgba(255,255,255,0.08);
  animation:floatParticle linear infinite
}
.particle:nth-child(1){left:8%;width:2px;height:2px;animation-duration:22s;animation-delay:0s}
.particle:nth-child(2){left:20%;width:3px;height:3px;animation-duration:26s;animation-delay:3s}
.particle:nth-child(3){left:40%;width:1.5px;height:1.5px;animation-duration:20s;animation-delay:5s}
.particle:nth-child(4){left:55%;width:2.5px;height:2.5px;animation-duration:24s;animation-delay:2s}
.particle:nth-child(5){left:72%;width:2px;height:2px;animation-duration:28s;animation-delay:6s}
.particle:nth-child(6){left:85%;width:3px;height:3px;animation-duration:22s;animation-delay:4s}
.particle:nth-child(7){left:30%;width:2px;height:2px;animation-duration:25s;animation-delay:7s}
.particle:nth-child(8){left:65%;width:1.5px;height:1.5px;animation-duration:21s;animation-delay:1s}
@keyframes floatParticle{
  0%{transform:translateY(0) translateX(0);opacity:0}
  12%{opacity:0.8}
  88%{opacity:0.8}
  100%{transform:translateY(100vh) translateX(40px);opacity:0}
}
/* ── RESPONSIVE ── */
@media(max-width:1024px){
  .hero-title{font-size:2.6rem}
  .login-left{width:50%;padding:30px}
  .login-right{width:50%;padding:30px}
}
@media(max-width:820px){
  body{flex-direction:column;overflow-y:auto}
  .login-bg{transform:none;animation:none;opacity:0.4}
  .volumetric-ray{display:none}.title-bloom{display:none}.scan-lines{display:none}
  .login-left{width:100%;min-height:auto;padding:32px 24px 16px}
  .login-right{width:100%;min-height:auto;padding:16px 24px 32px}
  .hero-content{transform:none}
  .hero-title{font-size:2.2rem}
  .hero-sub{font-size:0.9rem}
  .hero-stats{display:none}
  .hero-motto{display:none}
  .card-glow{display:none}
  .login-card-wrap{max-width:440px;margin:0 auto;animation:cardIn 1s cubic-bezier(0.16,1,0.3,1) forwards}
  .login-card{padding:28px 24px 24px}
}
@media(max-width:480px){
  .login-left{padding:24px 16px 12px}
  .login-right{padding:12px 16px 24px}
  .hero-title{font-size:1.6rem}
  .hero-badge{font-size:10px;margin-bottom:16px;letter-spacing:1.5px}
  .hero-sub{font-size:0.82rem;margin-bottom:20px}
  .login-card{padding:22px 16px 20px;border-radius:16px}
  .card-title{font-size:1.1rem}
  .card-subtitle{font-size:12px;margin-bottom:18px}
  .form-group{margin-bottom:14px}
  .login-card-wrap{max-width:100%}
  .form-options{flex-direction:column;gap:8px;align-items:flex-start}
}
</style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-bg-overlay"></div>
<div class="login-bg-vignette"></div>
<div class="volumetric-ray"></div>
<div class="scan-lines"></div>
<div class="particles">
  <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
  <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
</div>
<div class="login-left">
  <div class="hero-content">
    <div class="title-bloom"></div>
    <div class="hero-badge"><i class="fas fa-shield-alt"></i> Healthcare Staff Portal</div>
    <h1 class="hero-title">Welcome to<br><span>ISNM</span> Staff Portal</h1>
    <p class="hero-sub">Iganga School of Nursing and Midwifery — Uganda's premier institution for nursing and midwifery education, empowering healthcare professionals since establishment.</p>
    <div class="hero-motto">
      <strong>Chosen to Serve</strong> — Based on a disciplined mind for health action.
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-num">20+</div><div class="hero-stat-label">Programs</div></div>
      <div class="hero-stat"><div class="hero-stat-num">1500+</div><div class="hero-stat-label">Students</div></div>
      <div class="hero-stat"><div class="hero-stat-num">50+</div><div class="hero-stat-label">Staff</div></div>
      <div class="hero-stat"><div class="hero-stat-num">15+</div><div class="hero-stat-label">Years</div></div>
    </div>
  </div>
</div>
<div class="login-right">
  <div class="card-glow"></div>
  <div class="login-card-wrap">
    <div class="login-card">
      <div class="card-logo-wrap">
        <img src="images/school-logo.png" alt="ISNM" class="card-logo">
        <div class="card-brand">
          <h2>Iganga School of Nursing</h2>
          <p>and Midwifery &bull; Government of Uganda</p>
        </div>
      </div>
      <div class="card-title">Staff Portal Login</div>
      <div class="card-subtitle">Welcome back. Please sign in to continue.</div>
      <?php if ($requested_position): ?>
        <div class="role-badge"><i class="fas fa-sitemap"></i> <?=htmlspecialchars($requested_position)?></div>
      <?php endif; ?>
      <?php if ($login_error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($login_error)?></div>
      <?php endif; ?>
      <?php if ($login_success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($login_success)?></div>
      <?php endif; ?>
      <form method="POST" action="auth-handler.php">
        <input type="hidden" name="action" value="staff_login">
        <?php if ($requested_position): ?>
          <input type="hidden" name="requested_position" value="<?=htmlspecialchars($requested_position)?>">
        <?php endif; ?>
        <?php if ($redirect_url): ?>
          <input type="hidden" name="redirect_url" value="<?=htmlspecialchars($redirect_url)?>">
        <?php endif; ?>
        <div class="form-group">
          <label class="form-label" for="s-email">Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-user input-icon"></i>
            <input type="email" class="form-control" id="s-email" name="email"
                   placeholder="you@isnm.ug" required autocomplete="email"
                   value="<?=$suggested_email ? htmlspecialchars($suggested_email) : ''?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="s-password">Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" class="form-control" id="s-password" name="password"
                   placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="password-toggle" id="togglePass" tabindex="-1" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="staff-password-reset.php" class="forgot-link"><i class="fas fa-key me-1"></i>Forgot Password?</a>
        </div>
        <button type="submit" class="btn-login" id="btnLogin">
          <span class="spinner"></span>
          <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
        </button>
      </form>
      <div class="divider"></div>
      <div class="footer-links">
        <a href="organogram.php"><i class="fas fa-arrow-left"></i> Back to Organogram</a>
        <a href="index.php"><i class="fas fa-globe"></i> Website</a>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  var t=document.getElementById('togglePass'),p=document.getElementById('s-password');
  if(t&&p){t.addEventListener('click',function(){
    var ty=p.getAttribute('type')==='password'?'text':'password';
    p.setAttribute('type',ty);this.querySelector('i').className='fas fa-'+(ty==='password'?'eye':'eye-slash');
  })}
  var f=document.querySelector('form'),b=document.getElementById('btnLogin');
  if(f&&b){f.addEventListener('submit',function(){b.classList.add('loading')})}
  var e=document.getElementById('s-email');if(e)e.focus();
});
</script>
</body>
</html>