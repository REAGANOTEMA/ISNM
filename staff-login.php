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
  --gold:#C9A84C;
  --gold-light:#E8D48B;
  --gold-glow:rgba(201,168,76,0.20);
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
  background:radial-gradient(circle,rgba(245,230,200,0.15) 0%,rgba(201,168,76,0.06) 30%,transparent 70%);
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
  width:600px;height:600px;
  background:radial-gradient(circle,rgba(201,168,76,0.08) 0%,rgba(30,136,229,0.10) 30%,transparent 70%);
  filter:blur(40px);pointer-events:none;
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
.hero-badge i{color:var(--gold-light);font-size:11px}
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
  margin-bottom:28px;animation-delay:0.35s
}
.hero-institution{
  display:block;font-size:1.05rem;font-weight:600;
  color:var(--cream);line-height:1.5;letter-spacing:0.2px;
  margin-bottom:10px;font-family:'Playfair Display',Georgia,serif
}
.hero-divider{
  display:block;width:60px;height:1px;
  background:linear-gradient(90deg,var(--gold),rgba(201,168,76,0.15),transparent);
  margin-bottom:12px
}
.hero-description{
  display:block;font-size:0.9rem;color:rgba(255,255,255,0.65);
  line-height:1.7;font-weight:300;letter-spacing:0.15px
}
.hero-motto{
  display:flex;align-items:flex-start;gap:14px;
  padding:16px 20px;border-radius:12px;
  background:rgba(201,168,76,0.04);
  border:1px solid rgba(201,168,76,0.08);
  font-size:13px;font-weight:400;line-height:1.6;
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
  letter-spacing:0.2px;animation-delay:0.5s
}
.motto-emblem{
  flex-shrink:0;width:8px;height:8px;border-radius:50%;
  margin-top:7px;background:var(--gold);
  box-shadow:0 0 12px var(--gold-glow),0 0 24px rgba(201,168,76,0.08)
}
.hero-motto strong{
  display:block;color:var(--cream);font-weight:600;
  font-family:'Playfair Display',Georgia,serif;
  font-size:14px;margin-bottom:2px;letter-spacing:0.5px
}
.motto-sub{
  display:block;color:rgba(255,255,255,0.55);
  font-style:italic;font-weight:300;letter-spacing:0.3px
}
.hero-stats{
  display:flex;gap:28px;margin-top:32px;
  animation-delay:0.65s
}
.hero-stat{text-align:center}
.hero-stat-num{
  font-size:1.5rem;font-weight:700;color:var(--white);
  line-height:1;  text-shadow:
    0 2px 12px rgba(0,0,0,0.25),
    0 0 30px var(--gold-glow);
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
  width:100%;max-width:480px;position:relative;z-index:1;
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
  background:rgba(255,255,255,0.10);
  backdrop-filter:blur(32px) saturate(1.2);
  -webkit-backdrop-filter:blur(32px) saturate(1.2);
  border:1px solid rgba(255,255,255,0.14);
  border-radius:24px;
  padding:44px 40px 38px;
  position:relative;
  box-shadow:
    0 2px 4px rgba(0,0,0,0.04),
    0 8px 24px rgba(0,0,0,0.06),
    0 24px 56px rgba(0,0,0,0.08),
    0 48px 96px rgba(0,0,0,0.06),
    0 80px 140px rgba(0,0,0,0.04),
    inset 0 1px 0 rgba(255,255,255,0.15);
  transition:box-shadow 0.4s
}
.login-card::before{
  content:'';position:absolute;top:0;left:20px;right:20px;height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.30),transparent)
}
.login-card::after{
  content:'';position:absolute;bottom:0;left:20px;right:20px;height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent)
}
.card-logo-wrap{
  display:flex;align-items:center;gap:16px;margin-bottom:26px
}
.card-logo{
  width:54px;height:54px;border-radius:50%;object-fit:cover;
  border:2px solid rgba(201,168,76,0.30);flex-shrink:0;
  box-shadow:0 0 28px rgba(201,168,76,0.10),0 4px 12px rgba(0,0,0,0.12)
}
.card-brand h2{
  font-size:1.2rem;font-weight:700;color:var(--cream);
  margin:0;line-height:1.25;letter-spacing:-0.2px;
  text-shadow:0 2px 8px rgba(0,0,0,0.20)
}
.card-brand p{
  font-size:11px;color:var(--gold-light);margin:3px 0 0;
  text-transform:uppercase;letter-spacing:0.9px;font-weight:500;
  opacity:0.85;text-shadow:0 1px 4px rgba(0,0,0,0.15)
}
.card-title{
  font-size:1.5rem;font-weight:800;color:var(--cream);
  margin-bottom:6px;letter-spacing:-0.3px;
  text-shadow:0 2px 12px rgba(0,0,0,0.18),0 0 30px var(--cream-glow)
}
.card-subtitle{
  font-size:14px;color:rgba(255,255,255,0.52);
  margin-bottom:26px;line-height:1.6;font-weight:300;
  letter-spacing:0.25px;text-shadow:0 1px 6px rgba(0,0,0,0.10)
}
.form-group{margin-bottom:20px}
.form-label{
  display:block;font-size:11px;font-weight:700;color:var(--gold-light);
  margin-bottom:6px;text-transform:uppercase;letter-spacing:1px;
  opacity:0.9;text-shadow:0 1px 4px rgba(0,0,0,0.10)
}
.input-wrap{
  position:relative;display:flex;align-items:center
}
.input-wrap .input-icon{
  position:absolute;left:16px;color:rgba(255,255,255,0.35);
  font-size:15px;z-index:2;transition:color 0.3s
}
.input-wrap .form-control{
  width:100%;padding:13px 16px 13px 48px;
  border:1px solid rgba(255,255,255,0.10);
  border-radius:12px;font-size:15px;font-family:'Inter',sans-serif;
  background:rgba(0,0,0,0.15);
  transition:all 0.3s;color:var(--white);
  outline:none;
  box-shadow:
    inset 0 2px 6px rgba(0,0,0,0.18),
    inset 0 1px 2px rgba(0,0,0,0.12),
    0 1px 0 rgba(255,255,255,0.05)
}
.input-wrap .form-control:focus{
  border-color:rgba(201,168,76,0.25);
  background:rgba(0,0,0,0.22);
  box-shadow:
    inset 0 2px 6px rgba(0,0,0,0.18),
    0 0 0 4px rgba(30,136,229,0.10),
    0 0 32px rgba(201,168,76,0.06)
}
.input-wrap:focus-within .input-icon{color:var(--gold-light)}
.form-control::placeholder{color:rgba(255,255,255,0.20);font-weight:300;font-size:14px}
.password-toggle{
  position:absolute;right:14px;background:none;border:none;
  color:rgba(255,255,255,0.30);cursor:pointer;padding:6px;font-size:15px;z-index:2;
  transition:color 0.3s
}
.password-toggle:hover{color:rgba(255,255,255,0.60)}
.form-options{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:22px
}
.remember-me{
  display:flex;align-items:center;gap:8px;
  font-size:13px;color:rgba(255,255,255,0.52);cursor:pointer;
  font-weight:400;letter-spacing:0.2px;text-shadow:0 1px 4px rgba(0,0,0,0.08)
}
.remember-me input[type="checkbox"]{
  width:16px;height:16px;accent-color:var(--gold);border-radius:4px;
  background:rgba(255,255,255,0.05);cursor:pointer
}
.forgot-link{
  font-size:13px;color:rgba(255,255,255,0.50);text-decoration:none;
  font-weight:500;transition:color 0.3s;letter-spacing:0.2px;
  text-shadow:0 1px 4px rgba(0,0,0,0.08)
}
.forgot-link:hover{color:var(--gold-light)}
.btn-login{
  width:100%;padding:14px 24px;
  background:linear-gradient(165deg,var(--blue) 0%,#0B3D91 50%,var(--blue-dark) 100%);
  color:#fff;border:none;border-radius:12px;
  font-size:15px;font-weight:700;font-family:'Inter',sans-serif;
  cursor:pointer;position:relative;overflow:hidden;
  transition:all 0.3s cubic-bezier(0.16,1,0.3,1);
  display:flex;align-items:center;justify-content:center;gap:10px;
  box-shadow:
    0 6px 20px rgba(30,136,229,0.30),
    0 3px 6px rgba(0,0,0,0.12),
    0 12px 28px rgba(0,0,0,0.08),
    inset 0 1px 0 rgba(255,255,255,0.18);
  letter-spacing:0.3px;
  will-change:transform
}
.btn-login::before{
  content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(201,168,76,0.07),rgba(201,168,76,0.12),rgba(201,168,76,0.07),transparent);
  transform:skewX(-20deg);pointer-events:none;
  animation:btnGoldSweep 5s ease-in-out infinite
}
@keyframes btnGoldSweep{
  0%{left:-80%}
  50%{left:150%}
  100%{left:-80%}
}
.btn-login:hover{
  transform:translateY(-2px);
  box-shadow:
    0 8px 28px rgba(30,136,229,0.40),
    0 6px 12px rgba(0,0,0,0.15),
    0 16px 40px rgba(0,0,0,0.10),
    inset 0 1px 0 rgba(255,255,255,0.22)
}
.btn-login:active{
  transform:translateY(0);
  box-shadow:
    0 2px 6px rgba(30,136,229,0.15),
    inset 0 2px 6px rgba(0,0,0,0.12)
}
.btn-login .spinner{
  display:none;width:20px;height:20px;
  border:2px solid rgba(255,255,255,0.2);border-top-color:#fff;
  border-radius:50%;animation:spin 0.6s linear infinite
}
.btn-login.loading .spinner{display:inline-block}
.btn-login.loading .btn-text{opacity:0.7}
.btn-login.loading{pointer-events:none}
@keyframes spin{to{transform:rotate(360deg)}}
.divider{
  height:1px;background:linear-gradient(90deg,transparent,rgba(201,168,76,0.15),transparent);
  margin:22px 0 18px
}
.footer-links{
  display:flex;justify-content:center;gap:24px;
  font-size:13px
}
.footer-links a{
  color:rgba(255,255,255,0.42);text-decoration:none;
  display:inline-flex;align-items:center;gap:7px;
  transition:color 0.3s;font-weight:500;letter-spacing:0.15px;
  text-shadow:0 1px 4px rgba(0,0,0,0.08)
}
.footer-links a:hover{color:rgba(255,255,255,0.75)}
.alert{
  border-radius:11px;padding:12px 16px;margin-bottom:20px;
  font-size:13px;display:flex;align-items:center;gap:10px;
  border:none;font-weight:400
}
.alert-danger{
  background:rgba(239,68,68,0.10);color:#fca5a5;
  border-left:3px solid rgba(239,68,68,0.5);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)
}
.alert-success{
  background:rgba(34,197,94,0.10);color:#86efac;
  border-left:3px solid rgba(34,197,94,0.5);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)
}
.role-badge{
  display:inline-flex;align-items:center;gap:8px;
  padding:6px 16px;border-radius:20px;
  background:rgba(201,168,76,0.10);border:1px solid rgba(201,168,76,0.15);
  font-size:12px;font-weight:500;color:var(--gold-light);
  margin-top:10px;margin-bottom:4px;letter-spacing:0.4px;
  text-shadow:0 1px 4px rgba(0,0,0,0.10);
  box-shadow:0 2px 8px rgba(0,0,0,0.06)
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
  .login-card{padding:32px 28px 28px}
}
@media(max-width:480px){
  .login-left{padding:24px 16px 12px}
  .login-right{padding:12px 16px 24px}
  .hero-title{font-size:1.6rem}
  .hero-badge{font-size:10px;margin-bottom:16px;letter-spacing:1.5px}
  .hero-institution{font-size:0.9rem}
  .hero-description{font-size:0.78rem;margin-bottom:20px}
  .login-card{padding:24px 18px 22px;border-radius:18px}
  .card-title{font-size:1.25rem}
  .card-subtitle{font-size:13px;margin-bottom:18px}
  .card-brand h2{font-size:1rem}
  .form-group{margin-bottom:16px}
  .form-label{font-size:10px}
  .input-wrap .form-control{font-size:14px;padding:11px 14px 11px 42px}
  .login-card-wrap{max-width:100%}
  .form-options{flex-direction:column;gap:8px;align-items:flex-start}
  .btn-login{padding:12px 20px;font-size:14px}
  .role-badge{padding:4px 12px;font-size:11px}
  .footer-links{font-size:12px;gap:16px}
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
    <div class="hero-badge"><i class="fas fa-gem"></i> School Staff Portal</div>
    <h1 class="hero-title">Welcome to<br><span>ISNM</span> Staff Portal</h1>
    <div class="hero-sub">
      <span class="hero-institution">Iganga School of Nursing and Midwifery</span>
      <span class="hero-divider"></span>
      <span class="hero-description">Uganda's premier institution for nursing and midwifery education, empowering healthcare professionals since establishment.</span>
    </div>
    <div class="hero-motto">
      <span class="motto-emblem"></span>
      <strong>Chosen to Serve</strong>
      <span class="motto-sub">Based on a disciplined mind for health action.</span>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-num">6</div><div class="hero-stat-label">Programs</div></div>
      <div class="hero-stat"><div class="hero-stat-num">1000+</div><div class="hero-stat-label">Students</div></div>
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
          <p>and Midwifery &bull; Registered with MOES &amp; UNMC</p>
        </div>
      </div>
      <div class="card-title">Staff Portal Login</div>
      <div class="card-subtitle">Welcome back. Please sign in to continue.</div>
      <?php if ($requested_position): ?>
        <div class="role-badge"><i class="fas fa-star"></i> <?=htmlspecialchars($requested_position, ENT_QUOTES, 'UTF-8')?></div>
      <?php endif; ?>
      <?php if ($login_error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($login_error)?></div>
      <?php endif; ?>
      <?php if ($login_success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($login_success)?></div>
      <?php endif; ?>
      <form method="POST" action="auth-handler.php">
        <input type="hidden" name="action" value="staff_login">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
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