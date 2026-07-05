<?php
/**
 * ═════════════════════════════════════════════════════════════════════════
 * ISNM ORGANOGRAM STAFF LOGIN — PREMIUM 3D EDITION
 * ═════════════════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth-service.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if (!$redirect_url && !empty($_SESSION['login_redirect_url'])) {
    $redirect_url = $_SESSION['login_redirect_url'];
}
if ($redirect_url) {
    if (strpos($redirect_url, '..') !== false || strpos($redirect_url, '://') !== false) {
        $redirect_url = '';
    }
    if ($redirect_url) {
        $_SESSION['login_redirect_url'] = $redirect_url;
    }
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
        if (!empty($requestedPositionFromSession)
            && !$auth_service->positionMatchesRole($requestedPositionFromSession, $sessionRole)
        ) {
            $auth_service->logout();
        } else {
            if (!empty($_SESSION['login_redirect_url'])) {
                $target = $_SESSION['login_redirect_url'];
                unset($_SESSION['login_redirect_url'], $_SESSION['requested_position']);
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
<meta name="theme-color" content="#072A5E">
<title>Staff Portal Sign-In | ISNM</title>
<link rel="icon" type="image/x-icon" href="images/school-logo.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#072A5E;--navy-dark:#041A3A;--navy-mid:#0D3B7A;
  --blue:#1E88E5;--blue-dark:#0D47A1;--blue-glow:rgba(30,136,229,0.35);
  --teal:#0FA3B1;--teal-light:#3BC9D9;--teal-glow:rgba(15,163,177,0.3);
  --cream:#F5E6C8;--cream-light:#FFF8E7;--cream-glow:rgba(245,230,200,0.2);
  --gold:#C9A84C;--gold-light:#E8D48B;--gold-glow:rgba(201,168,76,0.3);
  --white:#FFFFFF;--glass-bg:rgba(255,255,255,0.07);
}
body{
  font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
  min-height:100vh;display:flex;overflow:hidden;
  background:linear-gradient(160deg,var(--navy-dark) 0%,var(--navy) 30%,var(--navy-mid) 55%,var(--blue-dark) 80%,var(--navy) 100%);
  -webkit-font-smoothing:antialiased;perspective:1800px;
}
.mesh-bg{
  position:fixed;inset:0;z-index:0;overflow:hidden;
  background:radial-gradient(ellipse 40% 50% at 15% 80%,rgba(201,168,76,0.06) 0%,transparent 70%),
    radial-gradient(ellipse 50% 40% at 85% 20%,rgba(30,136,229,0.08) 0%,transparent 70%),
    radial-gradient(ellipse 60% 50% at 50% 50%,rgba(15,163,177,0.04) 0%,transparent 70%);
}
.mesh-bg::before{
  content:'';position:absolute;inset:-50%;z-index:0;
  background-image:repeating-linear-gradient(0deg,transparent,transparent 80px,rgba(255,255,255,0.008) 80px,rgba(255,255,255,0.008) 81px),
    repeating-linear-gradient(90deg,transparent,transparent 80px,rgba(255,255,255,0.008) 80px,rgba(255,255,255,0.008) 81px);
  animation:meshShift 60s linear infinite;
}
@keyframes meshShift{0%{transform:translate(0,0) rotate(0deg)}100%{transform:translate(80px,40px) rotate(0.5deg)}}
.orb{
  position:fixed;border-radius:50%;z-index:0;pointer-events:none;
  filter:blur(70px);animation:orbFloat 20s ease-in-out infinite;
}
.orb-1{width:600px;height:600px;top:-15%;left:-10%;background:radial-gradient(circle,rgba(30,136,229,0.12),transparent 70%);animation-delay:0s}
.orb-2{width:500px;height:500px;bottom:-20%;right:-10%;background:radial-gradient(circle,rgba(201,168,76,0.08),transparent 70%);animation-delay:-7s}
.orb-3{width:400px;height:400px;top:40%;left:50%;background:radial-gradient(circle,rgba(15,163,177,0.06),transparent 70%);animation-delay:-14s}
@keyframes orbFloat{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(30px,-20px) scale(1.05)}50%{transform:translate(-20px,30px) scale(0.95)}75%{transform:translate(20px,-10px) scale(1.02)}}
.login-bg{
  position:fixed;inset:0;z-index:0;
  background:url('images/classroom-photo-certificates-in-nurses-and-diploma.jpeg') center/cover no-repeat;
  opacity:0.25;transform:scale(1.03);animation:bgParallax 30s ease-in-out infinite alternate;
}
@keyframes bgParallax{0%{transform:scale(1.03) translateX(0)}100%{transform:scale(1.07) translateX(-15px)}}
.login-bg-overlay{
  position:fixed;inset:0;z-index:1;
  background:linear-gradient(135deg,rgba(7,42,94,0.5) 0%,rgba(13,71,161,0.3) 50%,rgba(7,42,94,0.4) 100%);
  pointer-events:none
}
.scan-lines{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.018) 2px,rgba(0,0,0,0.018) 4px)
}
.light-ray{
  position:fixed;top:0;left:0;width:100%;height:100%;z-index:1;pointer-events:none;
  background:linear-gradient(105deg,rgba(255,255,255,0.02) 0%,transparent 15%,transparent 75%,rgba(201,168,76,0.015) 88%,transparent 100%);
  animation:raySweep 18s ease-in-out infinite alternate;
}
@keyframes raySweep{0%{transform:rotate(-3deg) scale(1.3) translateX(-4%)}100%{transform:rotate(3deg) scale(1.3) translateX(4%)}}
.login-left{width:55%;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;z-index:3;padding:40px}
.login-right{width:45%;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;z-index:3;padding:40px}
.hero-content{position:relative;z-index:2;max-width:560px;transform:perspective(1200px) rotateX(1deg)}
.hero-content>*{animation:textReveal 1s cubic-bezier(0.16,1,0.3,1) forwards;opacity:0}
.hero-badge{
  display:inline-flex;align-items:center;gap:10px;padding:8px 20px;border-radius:30px;
  background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.07);
  font-size:11px;font-weight:600;color:rgba(255,255,255,0.7);margin-bottom:28px;
  text-transform:uppercase;letter-spacing:2.5px;
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);animation-delay:0.1s
}
.hero-badge i{color:var(--gold-light);font-size:10px}
.hero-title{
  position:relative;font-size:3.6rem;font-weight:800;
  font-family:'Playfair Display',Georgia,serif;color:var(--cream);line-height:1.06;
  margin-bottom:20px;letter-spacing:-1px;animation-delay:0.2s;
  text-shadow:0 1px 0 var(--cream-light),0 2px 4px rgba(0,0,0,0.18),0 4px 8px rgba(0,0,0,0.14),
    0 8px 20px rgba(0,0,0,0.1),0 16px 30px rgba(0,0,0,0.06),0 0 50px var(--cream-glow);
  overflow:hidden
}
.hero-title::after{
  content:'';position:absolute;top:-15%;left:-60%;width:50%;height:130%;
  background:linear-gradient(90deg,transparent,rgba(255,248,231,0.06),rgba(255,248,231,0.12),rgba(255,248,231,0.06),transparent);
  transform:skewX(-18deg);pointer-events:none;animation:shimmerSweep 6s ease-in-out infinite
}
@keyframes shimmerSweep{0%{left:-60%}50%{left:150%}100%{left:-60%}}
.hero-title span{
  background:linear-gradient(135deg,var(--cream) 0%,var(--cream-light) 20%,#F5D6A8 40%,var(--cream-light) 60%,var(--cream) 80%,var(--cream-light) 100%);
  background-size:300% 300%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
  animation:creamShimmer 5s ease-in-out infinite;
  filter:drop-shadow(0 0 30px var(--cream-glow)) drop-shadow(0 2px 8px rgba(0,0,0,0.2))
}
@keyframes creamShimmer{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.hero-sub{margin-bottom:30px;animation-delay:0.35s}
.hero-institution{display:block;font-size:1.1rem;font-weight:600;color:var(--cream);line-height:1.5;letter-spacing:0.3px;margin-bottom:10px;font-family:'Playfair Display',Georgia,serif}
.hero-divider{display:block;width:70px;height:2px;background:linear-gradient(90deg,var(--gold),rgba(201,168,76,0.1),transparent);border-radius:2px;margin-bottom:14px}
.hero-description{display:block;font-size:0.92rem;color:rgba(255,255,255,0.6);line-height:1.8;font-weight:300;letter-spacing:0.2px}
.hero-motto{
  display:flex;align-items:flex-start;gap:14px;padding:18px 22px;border-radius:14px;
  background:rgba(201,168,76,0.04);border:1px solid rgba(201,168,76,0.07);
  font-size:13px;font-weight:400;line-height:1.6;
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
  letter-spacing:0.2px;animation-delay:0.5s;box-shadow:0 4px 20px rgba(0,0,0,0.06)
}
.motto-emblem{flex-shrink:0;width:10px;height:10px;border-radius:50%;margin-top:7px;background:var(--gold);box-shadow:0 0 15px var(--gold-glow),0 0 30px rgba(201,168,76,0.06)}
.hero-motto strong{display:block;color:var(--cream);font-weight:600;font-family:'Playfair Display',Georgia,serif;font-size:14px;margin-bottom:2px;letter-spacing:0.5px}
.motto-sub{display:block;color:rgba(255,255,255,0.5);font-style:italic;font-weight:300;letter-spacing:0.3px}
.hero-stats{display:flex;gap:32px;margin-top:36px;animation-delay:0.65s}
.hero-stat{text-align:center}
.hero-stat-num{font-size:1.6rem;font-weight:800;color:var(--white);line-height:1;text-shadow:0 2px 12px rgba(0,0,0,0.25),0 0 30px var(--gold-glow);letter-spacing:-0.5px}
.hero-stat-label{font-size:10px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:1.3px;margin-top:6px;font-weight:500}
@keyframes textReveal{0%{opacity:0;transform:translateY(20px)}100%{opacity:1;transform:translateY(0)}}
.login-card-wrap{
  width:100%;max-width:480px;position:relative;z-index:1;
  animation:cardIn 1.2s cubic-bezier(0.16,1,0.3,1) forwards,cardFloat 8s ease-in-out 1.5s infinite;
  transform-origin:center center;will-change:transform;transform-style:preserve-3d;
}
@keyframes cardIn{0%{opacity:0;transform:translateY(50px) scale(0.92) perspective(1200px) rotateX(4deg) rotateY(-2deg)}100%{opacity:1;transform:translateY(0) scale(1) perspective(1200px) rotateX(1.8deg) rotateY(0deg)}}
@keyframes cardFloat{0%,100%{transform:perspective(1200px) rotateX(1.8deg) translateY(0)}50%{transform:perspective(1200px) rotateX(1.8deg) translateY(-8px)}}
.login-card{
  background:rgba(255,255,255,0.08);backdrop-filter:blur(35px) saturate(1.3);
  -webkit-backdrop-filter:blur(35px) saturate(1.3);
  border:1px solid rgba(255,255,255,0.12);border-radius:28px;
  padding:46px 42px 40px;position:relative;overflow:hidden;
  box-shadow:0 2px 4px rgba(0,0,0,0.03),0 8px 24px rgba(0,0,0,0.05),0 24px 56px rgba(0,0,0,0.07),
    0 48px 96px rgba(0,0,0,0.05),0 80px 140px rgba(0,0,0,0.03),inset 0 1px 0 rgba(255,255,255,0.18);
  transition:box-shadow 0.5s;
}
.login-card::before{content:'';position:absolute;top:0;left:25px;right:25px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.35),transparent)}
.login-card::after{content:'';position:absolute;bottom:0;left:25px;right:25px;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent)}
.login-card:hover{box-shadow:0 4px 8px rgba(0,0,0,0.04),0 12px 32px rgba(0,0,0,0.06),0 32px 72px rgba(0,0,0,0.08),0 64px 120px rgba(0,0,0,0.06),0 100px 160px rgba(0,0,0,0.04),inset 0 1px 0 rgba(255,255,255,0.2)}
.card-logo-wrap{display:flex;align-items:center;gap:18px;margin-bottom:28px}
.card-logo{width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid rgba(201,168,76,0.25);flex-shrink:0;box-shadow:0 0 32px rgba(201,168,76,0.08),0 4px 14px rgba(0,0,0,0.15);transition:transform 0.4s,box-shadow 0.4s}
.card-logo:hover{transform:scale(1.05);box-shadow:0 0 40px rgba(201,168,76,0.15),0 6px 20px rgba(0,0,0,0.2)}
.card-brand h2{font-size:1.25rem;font-weight:700;color:var(--cream);margin:0;line-height:1.25;letter-spacing:-0.2px;text-shadow:0 2px 8px rgba(0,0,0,0.2)}
.card-brand p{font-size:11px;color:var(--gold-light);margin:4px 0 0;text-transform:uppercase;letter-spacing:1px;font-weight:500;opacity:0.8;text-shadow:0 1px 4px rgba(0,0,0,0.15)}
.card-title{font-size:1.55rem;font-weight:800;color:var(--cream);margin-bottom:6px;letter-spacing:-0.4px;text-shadow:0 2px 12px rgba(0,0,0,0.18),0 0 40px var(--cream-glow)}
.card-subtitle{font-size:14px;color:rgba(255,255,255,0.48);margin-bottom:28px;line-height:1.6;font-weight:300;letter-spacing:0.25px;text-shadow:0 1px 6px rgba(0,0,0,0.08)}
.form-group{margin-bottom:22px}
.form-label{display:block;font-size:11px;font-weight:700;color:var(--gold-light);margin-bottom:7px;text-transform:uppercase;letter-spacing:1.2px;opacity:0.9;text-shadow:0 1px 4px rgba(0,0,0,0.08)}
.input-wrap{position:relative;display:flex;align-items:center}
.input-wrap .input-icon{position:absolute;left:17px;color:rgba(255,255,255,0.3);font-size:15px;z-index:2;transition:color 0.3s}
.input-wrap .form-control{width:100%;padding:14px 18px 14px 50px;border:1px solid rgba(255,255,255,0.08);border-radius:14px;font-size:15px;font-family:'Inter',sans-serif;background:rgba(0,0,0,0.18);transition:all 0.35s;color:var(--white);outline:none;box-shadow:inset 0 2px 6px rgba(0,0,0,0.2),inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.04)}
.input-wrap .form-control:focus{border-color:rgba(201,168,76,0.2);background:rgba(0,0,0,0.25);box-shadow:inset 0 2px 6px rgba(0,0,0,0.2),0 0 0 4px rgba(30,136,229,0.08),0 0 35px rgba(201,168,76,0.04)}
.input-wrap:focus-within .input-icon{color:var(--gold-light)}
.form-control::placeholder{color:rgba(255,255,255,0.18);font-weight:300;font-size:14px}
.password-toggle{position:absolute;right:15px;background:none;border:none;color:rgba(255,255,255,0.25);cursor:pointer;padding:8px;font-size:15px;z-index:2;transition:color 0.3s}
.password-toggle:hover{color:rgba(255,255,255,0.55)}
.form-options{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.remember-me{display:flex;align-items:center;gap:9px;font-size:13px;color:rgba(255,255,255,0.48);cursor:pointer;font-weight:400;letter-spacing:0.2px;text-shadow:0 1px 4px rgba(0,0,0,0.06)}
.remember-me input[type="checkbox"]{width:17px;height:17px;accent-color:var(--gold);border-radius:4px;background:rgba(255,255,255,0.05);cursor:pointer}
.forgot-link{font-size:13px;color:rgba(255,255,255,0.45);text-decoration:none;font-weight:500;transition:color 0.3s;letter-spacing:0.2px;text-shadow:0 1px 4px rgba(0,0,0,0.06)}
.forgot-link:hover{color:var(--gold-light)}
.btn-login{
  width:100%;padding:15px 28px;
  background:linear-gradient(170deg,var(--blue) 0%,#0B3D91 50%,var(--blue-dark) 100%);
  color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:700;
  font-family:'Inter',sans-serif;cursor:pointer;position:relative;overflow:hidden;
  transition:all 0.35s cubic-bezier(0.16,1,0.3,1);
  display:flex;align-items:center;justify-content:center;gap:12px;
  box-shadow:0 6px 24px var(--blue-glow),0 3px 8px rgba(0,0,0,0.15),0 14px 32px rgba(0,0,0,0.08),inset 0 1px 0 rgba(255,255,255,0.2);
  letter-spacing:0.4px;will-change:transform
}
.btn-login::before{content:'';position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(201,168,76,0.06),rgba(201,168,76,0.1),rgba(201,168,76,0.06),transparent);transform:skewX(-22deg);pointer-events:none;animation:btnGoldSweep 5s ease-in-out infinite}
@keyframes btnGoldSweep{0%{left:-80%}50%{left:150%}100%{left:-80%}}
.btn-login:hover{transform:translateY(-3px) scale(1.01);box-shadow:0 10px 32px var(--blue-glow),0 6px 14px rgba(0,0,0,0.18),0 20px 48px rgba(0,0,0,0.1),inset 0 1px 0 rgba(255,255,255,0.25)}
.btn-login:active{transform:translateY(0) scale(0.99);box-shadow:0 2px 8px rgba(30,136,229,0.15),inset 0 3px 8px rgba(0,0,0,0.15)}
.btn-login .spinner{display:none;width:20px;height:20px;border:2px solid rgba(255,255,255,0.2);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite}
.btn-login.loading .spinner{display:inline-block}
.btn-login.loading .btn-text{opacity:0.7}
.btn-login.loading{pointer-events:none}
@keyframes spin{to{transform:rotate(360deg)}}
.quick-access{display:flex;justify-content:center;gap:14px;margin-top:8px}
.quick-access a{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);color:rgba(255,255,255,0.35);font-size:17px;text-decoration:none;transition:all 0.3s;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.quick-access a:hover{background:rgba(255,255,255,0.08);color:var(--gold-light);transform:translateY(-3px);border-color:rgba(201,168,76,0.15);box-shadow:0 6px 20px rgba(0,0,0,0.1)}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(201,168,76,0.12),transparent);margin:24px 0 20px}
.footer-links{display:flex;justify-content:center;gap:28px;font-size:13px}
.footer-links a{color:rgba(255,255,255,0.38);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:color 0.3s;font-weight:500;letter-spacing:0.15px;text-shadow:0 1px 4px rgba(0,0,0,0.06)}
.footer-links a:hover{color:rgba(255,255,255,0.7)}
.alert{border-radius:12px;padding:13px 18px;margin-bottom:22px;font-size:13px;display:flex;align-items:center;gap:10px;border:none;font-weight:400}
.alert-danger{background:rgba(239,68,68,0.08);color:#fca5a5;border-left:3px solid rgba(239,68,68,0.4);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.alert-success{background:rgba(34,197,94,0.08);color:#86efac;border-left:3px solid rgba(34,197,94,0.4);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.role-badge{display:inline-flex;align-items:center;gap:8px;padding:7px 18px;border-radius:30px;background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.12);font-size:12px;font-weight:500;color:var(--gold-light);margin-top:10px;margin-bottom:6px;letter-spacing:0.4px;text-shadow:0 1px 4px rgba(0,0,0,0.08);box-shadow:0 2px 10px rgba(0,0,0,0.04)}
@media(max-width:1100px){.hero-title{font-size:2.8rem}.login-left{padding:30px}.login-right{padding:30px}}
@media(max-width:900px){
  body{flex-direction:column;overflow-y:auto}.login-bg{transform:none;animation:none;opacity:0.2}
  .login-left{width:100%;min-height:auto;padding:36px 24px 12px}
  .login-right{width:100%;min-height:auto;padding:12px 24px 36px}
  .hero-content{transform:none}.hero-title{font-size:2.4rem}.hero-sub{font-size:0.9rem}
  .hero-stats{display:none}.hero-motto{display:none}
  .login-card-wrap{max-width:440px;margin:0 auto;animation:cardIn 1s cubic-bezier(0.16,1,0.3,1) forwards}
  .login-card{padding:34px 30px 30px}
}
@media(max-width:480px){
  .login-left{padding:28px 16px 8px}.login-right{padding:8px 16px 28px}
  .hero-title{font-size:1.7rem}.hero-badge{font-size:10px;margin-bottom:18px;letter-spacing:1.5px}
  .hero-institution{font-size:0.9rem}.hero-description{font-size:0.78rem;margin-bottom:22px}
  .login-card{padding:26px 20px 24px;border-radius:20px}
  .card-title{font-size:1.3rem}.card-subtitle{font-size:13px;margin-bottom:20px}
  .card-brand h2{font-size:1rem}.card-brand p{font-size:10px}
  .form-group{margin-bottom:18px}.form-label{font-size:10px}
  .input-wrap .form-control{font-size:14px;padding:12px 14px 12px 44px;border-radius:12px}
  .login-card-wrap{max-width:100%}.form-options{flex-direction:column;gap:10px;align-items:flex-start}
  .btn-login{padding:13px 20px;font-size:14px;border-radius:12px}
  .role-badge{padding:5px 14px;font-size:11px}
  .footer-links{font-size:12px;gap:18px;flex-wrap:wrap}.quick-access a{width:40px;height:40px;font-size:15px}
}
</style>
</head>
<body>
<div class="mesh-bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="login-bg"></div>
<div class="login-bg-overlay"></div>
<div class="scan-lines"></div>
<div class="light-ray"></div>
<div class="login-left">
  <div class="hero-content">
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
            <i class="fas fa-envelope input-icon"></i>
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
          <a href="staff-password-reset.php" class="forgot-link"><i class="fas fa-key"></i> Forgot Password?</a>
        </div>
        <button type="submit" class="btn-login" id="btnLogin">
          <span class="spinner"></span>
          <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
        </button>
      </form>
      <div class="divider"></div>
      <div class="quick-access">
        <a href="organogram.php" title="Organogram"><i class="fas fa-sitemap"></i></a>
        <a href="index.php" title="Website"><i class="fas fa-globe-africa"></i></a>
        <a href="contact.php" title="Contact"><i class="fas fa-headset"></i></a>
      </div>
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
  var c=document.querySelector('.login-card-wrap');
  if(c){document.addEventListener('mousemove',function(m){
    var r=c.getBoundingClientRect();
    var x=(m.clientX-r.left)/r.width-0.5;
    var y=(m.clientY-r.top)/r.height-0.5;
    c.style.transform='perspective(1200px) rotateX('+(1.8-y*4)+'deg) rotateY('+(x*4)+'deg) translateY(0)';
  })}
});
</script>
</body>
</html>