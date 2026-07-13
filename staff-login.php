<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_path', SESSION_COOKIE_PATH);
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
            if (session_status() === PHP_SESSION_NONE) {
                ini_set('session.cookie_path', SESSION_COOKIE_PATH);
                session_start();
            }
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            if (!empty($_SESSION['login_redirect_url'])) {
                $target = $_SESSION['login_redirect_url'];
                unset($_SESSION['login_redirect_url'], $_SESSION['requested_position']);
                $currentFile = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '');
                $targetFile = basename(parse_url($target, PHP_URL_PATH) ?: '');
                if ($targetFile && $targetFile !== 'staff-login.php' && $targetFile !== $currentFile) {
                    session_write_close();
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
            session_write_close();
            header("Location: $dashboard");
            exit();
        }
    }
    if (($_SESSION['type'] ?? '') === 'student') {
        session_write_close();
        header('Location: dashboards/student.php');
        exit();
    }
}
$login_error   = $_SESSION['error']   ?? '';
$login_success = $_SESSION['success'] ?? '';
if ($login_error)   { unset($_SESSION['error']); }
if ($login_success) { unset($_SESSION['success']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Portal Sign-In | ISNM</title>
<link rel="icon" type="image/x-icon" href="images/school-logo.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800;900&family=Orbitron:wght@400;500;600;700;800;900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --yellow:#FFD700;--yellow-dark:#B8960F;--yellow-light:#FFF3A1;--yellow-glow:rgba(255,215,0,0.35);
  --blue:#1E88E5;--blue-dark:#0D47A1;--blue-mid:#1565C0;--blue-light:#64B5F6;--blue-glow:rgba(30,136,229,0.3);
  --green:#2ECC71;--green-dark:#1A8C4A;--green-light:#6BE89B;--green-glow:rgba(46,204,113,0.3);
  --chocolate:#D2691E;--chocolate-dark:#8B4513;--chocolate-mid:#A0522D;--chocolate-light:#F4A460;--chocolate-glow:rgba(210,105,30,0.3);
  --red:#E74C3C;--red-dark:#B71C1C;--red-mid:#C62828;--red-light:#EF9A9A;--red-glow:rgba(231,76,60,0.3);
  --bg-dark:#0A0E1A;--bg-mid:#111827;--bg-card:rgba(255,255,255,0.04);
  --text-primary:#FFFFFF;--text-secondary:rgba(255,255,255,0.7);--text-muted:rgba(255,255,255,0.4);
  --border-light:rgba(255,255,255,0.06);--border-mid:rgba(255,255,255,0.1);
  --font-display:'Cinzel',Georgia,serif;
  --font-heading:'Playfair Display',Georgia,serif;
  --font-body:'Poppins',-apple-system,sans-serif;
  --font-accent:'Rajdhani','Poppins',sans-serif;
  --font-futuristic:'Orbitron',monospace;
}
body{
  font-family:var(--font-body);min-height:100vh;display:flex;overflow:hidden;
  background:var(--bg-dark);
  -webkit-font-smoothing:antialiased;perspective:2000px;
}
/* ── Animated Particle Background ── */
.particle-field{position:fixed;inset:0;z-index:0;overflow:hidden}
.particle{
  position:absolute;width:var(--size,4px);height:var(--size,4px);
  background:var(--color,var(--yellow));border-radius:50%;
  box-shadow:0 0 var(--glow,6px) var(--color,var(--yellow)),0 0 var(--glow2,20px) rgba(255,215,0,0.05);
  animation:particleFloat var(--duration,12s) ease-in-out var(--delay,0s) infinite;
  opacity:0;pointer-events:none;
}
@keyframes particleFloat{
  0%{opacity:0;transform:translateY(110vh) translateX(0) scale(0.5) rotate(0deg)}
  10%{opacity:var(--opacity,0.8)}
  90%{opacity:var(--opacity,0.6)}
  100%{opacity:0;transform:translateY(-10vh) translateX(var(--drift,50px)) scale(1) rotate(360deg)}
}
/* ── Gradient Mesh Background ── */
.mesh-bg{
  position:fixed;inset:0;z-index:1;overflow:hidden;
  background:
    radial-gradient(ellipse 45% 35% at 10% 85%,var(--yellow-glow) 0%,transparent 70%),
    radial-gradient(ellipse 40% 30% at 90% 15%,var(--blue-glow) 0%,transparent 70%),
    radial-gradient(ellipse 30% 25% at 50% 50%,var(--green-glow) 0%,transparent 60%),
    radial-gradient(ellipse 25% 20% at 75% 70%,var(--chocolate-glow) 0%,transparent 60%),
    radial-gradient(ellipse 20% 15% at 25% 30%,var(--red-glow) 0%,transparent 55%);
}
.glass-tint{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:linear-gradient(160deg,rgba(10,14,26,0.3) 0%,rgba(17,24,39,0.5) 50%,rgba(10,14,26,0.4) 100%);
}
/* ── Grid Lines Overlay ── */
.grid-overlay{
  position:fixed;inset:0;z-index:1;pointer-events:none;overflow:hidden;
}
.grid-overlay::before{
  content:'';position:absolute;inset:-100%;
  background-image:
    linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px),
    linear-gradient(0deg,rgba(255,255,255,0.015) 1px,transparent 1px);
  background-size:60px 60px;
  animation:gridShift 40s linear infinite;
}
@keyframes gridShift{0%{transform:translate(0,0)}100%{transform:translate(60px,60px)}}
/* ── Light Sweep ── */
.light-sweep{
  position:fixed;top:0;left:0;width:100%;height:100%;z-index:1;pointer-events:none;
  background:linear-gradient(115deg,rgba(255,255,255,0.015) 0%,transparent 18%,transparent 72%,rgba(255,215,0,0.012) 85%,transparent 100%);
  animation:sweepMove 14s ease-in-out infinite alternate;
}
@keyframes sweepMove{0%{transform:rotate(-4deg) scale(1.4) translateX(-5%)}100%{transform:rotate(4deg) scale(1.4) translateX(5%)}}
/* ── Floating Orbs ── */
.orb{
  position:fixed;border-radius:50%;z-index:0;pointer-events:none;
  filter:blur(80px);animation:orbFloat 25s ease-in-out infinite;
}
.orb-1{width:700px;height:700px;top:-20%;left:-12%;background:radial-gradient(circle,rgba(255,215,0,0.08),transparent 70%);animation-delay:0s}
.orb-2{width:550px;height:550px;bottom:-25%;right:-15%;background:radial-gradient(circle,rgba(30,136,229,0.06),transparent 70%);animation-delay:-8s}
.orb-3{width:450px;height:450px;top:35%;left:45%;background:radial-gradient(circle,rgba(46,204,113,0.04),transparent 70%);animation-delay:-16s}
@keyframes orbFloat{
  0%,100%{transform:translate(0,0) scale(1)}
  25%{transform:translate(40px,-25px) scale(1.06)}
  50%{transform:translate(-30px,35px) scale(0.94)}
  75%{transform:translate(25px,-15px) scale(1.03)}
}
/* ── Background Image ── */
.login-bg{
  position:fixed;inset:0;z-index:0;
  background:url('images/classroom-photo-certificates-in-nurses-and-diploma.jpeg') center/cover no-repeat;
  opacity:0.55;transform:scale(1.05);
  animation:bgParallax 35s ease-in-out infinite alternate;
}
.bg-overlay{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background:linear-gradient(135deg,rgba(10,14,26,0.75) 0%,rgba(10,14,26,0.45) 40%,rgba(10,14,26,0.5) 100%);
}
.glass-tint{background:linear-gradient(160deg,rgba(10,14,26,0.25) 0%,rgba(17,24,39,0.35) 50%,rgba(10,14,26,0.3) 100%)}
@keyframes bgParallax{0%{transform:scale(1.05) translateX(0)}100%{transform:scale(1.1) translateX(-20px)}}
/* ── LEFT PANEL ── */
.login-left{
  width:55%;min-height:100vh;display:flex;align-items:center;justify-content:center;
  position:relative;z-index:3;padding:50px 45px;
}
.hero-content{position:relative;z-index:2;max-width:620px;width:100%}
.hero-content>*{animation:textReveal 1.2s cubic-bezier(0.16,1,0.3,1) forwards;opacity:0}
.hero-badge{
  display:inline-flex;align-items:center;gap:10px;padding:10px 24px;border-radius:30px;
  background:var(--bg-card);border:1px solid var(--border-mid);
  font-size:11px;font-weight:600;font-family:var(--font-accent);
  color:var(--text-secondary);margin-bottom:30px;letter-spacing:3px;text-transform:uppercase;
  backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);animation-delay:0.1s;
  box-shadow:0 4px 20px rgba(0,0,0,0.1);
}
.hero-badge i{font-size:10px;background:linear-gradient(135deg,var(--yellow),var(--yellow-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-title{
  font-family:var(--font-display);font-size:3.8rem;font-weight:900;
  color:var(--text-primary);line-height:1.08;margin-bottom:22px;
  letter-spacing:1px;animation-delay:0.2s;
  text-shadow:0 2px 4px rgba(0,0,0,0.3),0 4px 12px rgba(0,0,0,0.2),0 8px 30px rgba(0,0,0,0.1);
}
.hero-title .highlight{
  background:linear-gradient(135deg,var(--yellow) 0%,var(--yellow-light) 25%,var(--yellow) 50%,var(--blue-light) 75%,var(--green-light) 100%);
  background-size:300% 300%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  animation:titleShimmer 6s ease-in-out infinite;
  filter:drop-shadow(0 0 40px var(--yellow-glow)) drop-shadow(0 2px 12px rgba(0,0,0,0.3));
  display:inline-block;
}
@keyframes titleShimmer{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.hero-sub{margin-bottom:32px;animation-delay:0.35s}
.hero-institution{
  display:block;font-family:var(--font-heading);font-size:1.15rem;font-weight:600;
  color:var(--text-primary);line-height:1.5;margin-bottom:12px;letter-spacing:0.5px;
}
.hero-divider{
  display:block;width:80px;height:3px;
  background:linear-gradient(90deg,var(--yellow),var(--blue),var(--green),var(--chocolate));
  border-radius:3px;margin-bottom:16px;box-shadow:0 0 20px var(--yellow-glow);
}
.hero-description{
  display:block;font-size:0.92rem;color:var(--text-secondary);
  line-height:1.9;font-weight:300;
}
.hero-motto{
  display:flex;align-items:flex-start;gap:16px;padding:20px 26px;border-radius:16px;
  background:var(--bg-card);border:1px solid var(--border-light);
  font-size:13px;font-weight:400;line-height:1.6;
  backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);animation-delay:0.5s;
  box-shadow:0 4px 24px rgba(0,0,0,0.06);
}
.motto-icon{
  flex-shrink:0;width:40px;height:40px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,rgba(255,215,0,0.15),rgba(255,215,0,0.05));
  border:1px solid rgba(255,215,0,0.12);font-size:16px;
}
.motto-icon i{background:linear-gradient(135deg,var(--yellow),var(--yellow-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-motto strong{
  display:block;color:var(--text-primary);font-weight:600;font-family:var(--font-heading);
  font-size:15px;margin-bottom:3px;letter-spacing:0.3px;
}
.motto-sub{
  display:block;color:var(--text-muted);font-style:italic;font-weight:300;
  font-size:12.5px;letter-spacing:0.3px;
}
.hero-stats{
  display:flex;gap:36px;margin-top:38px;animation-delay:0.65s;
}
.hero-stat{text-align:center;position:relative}
.hero-stat:not(:last-child)::after{
  content:'';position:absolute;right:-18px;top:50%;transform:translateY(-50%);
  width:1px;height:35px;background:linear-gradient(0deg,transparent,rgba(255,255,255,0.08),transparent);
}
.hero-stat-num{
  font-family:var(--font-futuristic);font-size:1.8rem;font-weight:700;
  color:var(--text-primary);line-height:1;
  text-shadow:0 2px 15px rgba(0,0,0,0.3),0 0 30px var(--yellow-glow);
  letter-spacing:1px;
}
.hero-stat-label{
  font-size:10px;color:var(--text-muted);text-transform:uppercase;
  letter-spacing:1.5px;margin-top:7px;font-weight:500;font-family:var(--font-accent);
}
@keyframes textReveal{0%{opacity:0;transform:translateY(25px)}100%{opacity:1;transform:translateY(0)}}
/* ── RIGHT PANEL - LOGIN CARD ── */
.login-right{
  width:45%;min-height:100vh;display:flex;align-items:center;justify-content:center;
  position:relative;z-index:3;padding:50px 40px;
}
.login-card-wrap{
  width:100%;max-width:500px;position:relative;
  animation:cardIn 1.4s cubic-bezier(0.16,1,0.3,1) forwards;
  transform-origin:center center;transform-style:preserve-3d;
}
@keyframes cardIn{
  0%{opacity:0;transform:translateY(60px) scale(0.9) perspective(1400px) rotateX(5deg) rotateY(-3deg)}
  100%{opacity:1;transform:translateY(0) scale(1) perspective(1400px) rotateX(1.5deg) rotateY(0deg)}
}
.login-card{
  background:var(--bg-card);backdrop-filter:blur(40px) saturate(1.4);
  -webkit-backdrop-filter:blur(40px) saturate(1.4);
  border:1px solid var(--border-light);border-radius:30px;
  padding:50px 44px 44px;position:relative;overflow:hidden;
  box-shadow:
    0 2px 4px rgba(0,0,0,0.03),0 8px 24px rgba(0,0,0,0.05),
    0 24px 56px rgba(0,0,0,0.07),0 48px 100px rgba(0,0,0,0.05),
    0 80px 150px rgba(0,0,0,0.03),
    inset 0 1px 0 rgba(255,255,255,0.12);
  transition:box-shadow 0.5s;
}
.login-card:hover{
  box-shadow:
    0 4px 8px rgba(0,0,0,0.04),0 12px 32px rgba(0,0,0,0.06),
    0 32px 72px rgba(0,0,0,0.08),0 64px 128px rgba(0,0,0,0.06),
    0 100px 180px rgba(0,0,0,0.04),
    inset 0 1px 0 rgba(255,255,255,0.15);
}
.login-card .card-accent-top{
  position:absolute;top:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--yellow),var(--blue),var(--green),var(--chocolate),var(--red));
  border-radius:30px 30px 0 0;
}
.login-card .card-glow{
  position:absolute;top:-50%;right:-50%;width:100%;height:100%;
  background:radial-gradient(circle,rgba(255,215,0,0.03),transparent 70%);
  pointer-events:none;animation:cardGlow 8s ease-in-out infinite;
}
@keyframes cardGlow{0%,100%{opacity:0.5}50%{opacity:1}}
/* ── Card Header ── */
.card-header-wrap{
  display:flex;align-items:center;gap:20px;margin-bottom:30px;position:relative;z-index:1;
}
.card-logo{
  width:64px;height:64px;border-radius:50%;object-fit:cover;flex-shrink:0;
  border:2px solid rgba(255,215,0,0.2);
  box-shadow:0 0 35px rgba(255,215,0,0.08),0 6px 18px rgba(0,0,0,0.2);
  transition:transform 0.4s,box-shadow 0.4s;
}
.card-logo:hover{transform:scale(1.06) rotate(-3deg);box-shadow:0 0 50px rgba(255,215,0,0.15),0 8px 24px rgba(0,0,0,0.25)}
.card-brand{flex:1}
.card-brand h2{
  font-family:var(--font-display);font-size:1.15rem;font-weight:700;
  color:var(--text-primary);line-height:1.25;letter-spacing:0.5px;
  text-shadow:0 2px 10px rgba(0,0,0,0.2);
}
.card-brand p{
  font-size:10.5px;color:var(--text-muted);margin:5px 0 0;
  letter-spacing:1px;font-weight:400;
}
.card-title{
  font-family:var(--font-heading);font-size:1.7rem;font-weight:700;
  color:var(--text-primary);margin-bottom:4px;letter-spacing:0px;
  text-shadow:0 2px 15px rgba(0,0,0,0.2);
  position:relative;z-index:1;
}
.card-title span{color:var(--yellow)}
.card-subtitle{
  font-size:14px;color:var(--text-secondary);margin-bottom:26px;
  line-height:1.6;font-weight:300;position:relative;z-index:1;
}
/* ── Role Badge ── */
.role-badge{
  display:inline-flex;align-items:center;gap:8px;padding:8px 20px;border-radius:30px;
  background:linear-gradient(135deg,rgba(210,105,30,0.12),rgba(210,105,30,0.04));
  border:1px solid rgba(210,105,30,0.15);margin-top:8px;margin-bottom:18px;position:relative;z-index:1;
  font-family:var(--font-accent);font-size:13px;font-weight:600;color:var(--chocolate-light);
  letter-spacing:0.5px;
  box-shadow:0 4px 20px rgba(210,105,30,0.06),inset 0 1px 0 rgba(255,255,255,0.06);
}
.role-badge i{color:var(--chocolate)}
/* ── Alert Messages ── */
.alert{
  border-radius:14px;padding:14px 20px;margin-bottom:22px;font-size:13px;
  display:flex;align-items:center;gap:12px;border:none;font-weight:400;
  position:relative;z-index:1;line-height:1.5;
}
.alert-danger{
  background:linear-gradient(135deg,rgba(231,76,60,0.1),rgba(231,76,60,0.04));
  color:var(--red-light);border-left:3px solid rgba(231,76,60,0.4);
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
}
.alert-success{
  background:linear-gradient(135deg,rgba(46,204,113,0.1),rgba(46,204,113,0.04));
  color:var(--green-light);border-left:3px solid rgba(46,204,113,0.4);
  backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
}
.alert i{font-size:16px;flex-shrink:0}
/* ── Form ── */
.form-group{margin-bottom:24px;position:relative;z-index:1}
.form-label{
  display:block;font-size:11px;font-weight:600;color:var(--text-secondary);
  margin-bottom:8px;letter-spacing:1px;text-transform:uppercase;
  font-family:var(--font-accent);
}
.input-wrap{position:relative;display:flex;align-items:center}
.input-wrap .input-icon{
  position:absolute;left:18px;color:var(--text-muted);font-size:14px;z-index:2;
  transition:color 0.3s;
}
.input-wrap .form-control{
  width:100%;padding:15px 20px 15px 52px;
  border:1px solid var(--border-light);border-radius:14px;
  font-size:14px;font-family:var(--font-body);
  background:rgba(0,0,0,0.2);
  transition:all 0.35s;color:var(--text-primary);outline:none;
  box-shadow:inset 0 2px 8px rgba(0,0,0,0.2),0 1px 0 rgba(255,255,255,0.03);
}
.input-wrap .form-control:focus{
  border-color:rgba(255,215,0,0.15);
  background:rgba(0,0,0,0.28);
  box-shadow:inset 0 2px 8px rgba(0,0,0,0.2),0 0 0 4px rgba(255,215,0,0.04),0 0 40px rgba(255,215,0,0.03);
}
.input-wrap:focus-within .input-icon{color:var(--yellow)}
.form-control::placeholder{color:var(--text-muted);font-weight:300;font-size:13px}
.form-control:-webkit-autofill,
.form-control:-webkit-autofill:hover,
.form-control:-webkit-autofill:focus{
  -webkit-text-fill-color:var(--text-primary) !important;
  -webkit-box-shadow:0 0 0 100px rgba(0,0,0,0.4) inset !important;
  border-color:rgba(255,215,0,0.1) !important;
  caret-color:var(--text-primary);
}
/* ── Password Toggle ── */
.password-toggle{
  position:absolute;right:15px;background:none;border:none;
  color:var(--text-muted);cursor:pointer;padding:10px;font-size:15px;z-index:2;
  transition:color 0.3s;
}
.password-toggle:hover{color:var(--text-secondary)}
/* ── Form Options ── */
.form-options{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:26px;position:relative;z-index:1;
}
.remember-me{
  display:flex;align-items:center;gap:10px;font-size:13px;
  color:var(--text-muted);cursor:pointer;font-weight:400;letter-spacing:0.2px;
}
.remember-me input[type="checkbox"]{
  width:18px;height:18px;accent-color:var(--yellow);border-radius:4px;
  background:rgba(255,255,255,0.05);cursor:pointer;
}
.forgot-link{
  font-size:13px;color:var(--text-muted);text-decoration:none;
  font-weight:500;transition:all 0.3s;letter-spacing:0.2px;
  display:inline-flex;align-items:center;gap:6px;
}
.forgot-link:hover{color:var(--yellow)}
/* ── 3D BUTTONS ── */
.btn-3d{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  padding:16px 28px;border:none;border-radius:14px;
  font-family:var(--font-accent);font-size:14px;font-weight:700;
  cursor:pointer;position:relative;overflow:hidden;
  transition:all 0.2s cubic-bezier(0.16,1,0.3,1);
  letter-spacing:0.5px;text-transform:uppercase;text-decoration:none;
  transform-style:preserve-3d;will-change:transform;
}
.btn-3d::before{
  content:'';position:absolute;top:0;left:0;right:0;bottom:0;
  border-radius:14px;z-index:0;pointer-events:none;
}
.btn-3d span,.btn-3d i{position:relative;z-index:1}

/* Yellow 3D Button */
.btn-yellow{
  background:linear-gradient(180deg,#FFD700 0%,#F0C000 40%,#DAA520 100%);
  color:#1a1a2e;
  box-shadow:
    0 6px 0 #B8960F,0 8px 20px rgba(255,215,0,0.3),
    0 12px 30px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.4);
}
.btn-yellow:hover{transform:translateY(-3px);box-shadow:0 9px 0 #B8960F,0 12px 30px rgba(255,215,0,0.4),0 18px 45px rgba(0,0,0,0.2),inset 0 1px 0 rgba(255,255,255,0.5)}
.btn-yellow:active{transform:translateY(3px);box-shadow:0 3px 0 #B8960F,0 4px 12px rgba(255,215,0,0.2),inset 0 2px 8px rgba(0,0,0,0.1)}

/* Blue 3D Button */
.btn-blue{
  background:linear-gradient(180deg,#1E88E5 0%,#1565C0 40%,#0D47A1 100%);
  color:#fff;
  box-shadow:
    0 6px 0 #0D47A1,0 8px 20px rgba(30,136,229,0.3),
    0 12px 30px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.25);
}
.btn-blue:hover{transform:translateY(-3px);box-shadow:0 9px 0 #0D47A1,0 12px 30px rgba(30,136,229,0.4),0 18px 45px rgba(0,0,0,0.2),inset 0 1px 0 rgba(255,255,255,0.35)}
.btn-blue:active{transform:translateY(3px);box-shadow:0 3px 0 #0D47A1,0 4px 12px rgba(30,136,229,0.2),inset 0 2px 8px rgba(0,0,0,0.1)}

/* Green 3D Button */
.btn-green{
  background:linear-gradient(180deg,#2ECC71 0%,#27AE60 40%,#1A8C4A 100%);
  color:#fff;
  box-shadow:
    0 6px 0 #1A8C4A,0 8px 20px rgba(46,204,113,0.3),
    0 12px 30px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.25);
}
.btn-green:hover{transform:translateY(-3px);box-shadow:0 9px 0 #1A8C4A,0 12px 30px rgba(46,204,113,0.4),0 18px 45px rgba(0,0,0,0.2),inset 0 1px 0 rgba(255,255,255,0.35)}
.btn-green:active{transform:translateY(3px);box-shadow:0 3px 0 #1A8C4A,0 4px 12px rgba(46,204,113,0.2),inset 0 2px 8px rgba(0,0,0,0.1)}

/* Chocolate 3D Button */
.btn-chocolate{
  background:linear-gradient(180deg,#D2691E 0%,#A0522D 40%,#8B4513 100%);
  color:#fff;
  box-shadow:
    0 6px 0 #8B4513,0 8px 20px rgba(210,105,30,0.3),
    0 12px 30px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.2);
}
.btn-chocolate:hover{transform:translateY(-3px);box-shadow:0 9px 0 #8B4513,0 12px 30px rgba(210,105,30,0.4),0 18px 45px rgba(0,0,0,0.2),inset 0 1px 0 rgba(255,255,255,0.3)}
.btn-chocolate:active{transform:translateY(3px);box-shadow:0 3px 0 #8B4513,0 4px 12px rgba(210,105,30,0.2),inset 0 2px 8px rgba(0,0,0,0.1)}

/* Red 3D Button */
.btn-red{
  background:linear-gradient(180deg,#E74C3C 0%,#C62828 40%,#B71C1C 100%);
  color:#fff;
  box-shadow:
    0 6px 0 #B71C1C,0 8px 20px rgba(231,76,60,0.3),
    0 12px 30px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.2);
}
.btn-red:hover{transform:translateY(-3px);box-shadow:0 9px 0 #B71C1C,0 12px 30px rgba(231,76,60,0.4),0 18px 45px rgba(0,0,0,0.2),inset 0 1px 0 rgba(255,255,255,0.3)}
.btn-red:active{transform:translateY(3px);box-shadow:0 3px 0 #B71C1C,0 4px 12px rgba(231,76,60,0.2),inset 0 2px 8px rgba(0,0,0,0.1)}

/* Small 3D Button variant */
.btn-3d-sm{padding:12px 20px;font-size:12px;border-radius:12px;gap:8px}

/* Full width */
.btn-3d-block{width:100%}

.btn-3d .spinner{
  display:none;width:20px;height:20px;
  border:2px solid rgba(255,255,255,0.2);border-top-color:#fff;
  border-radius:50%;animation:spin 0.6s linear infinite;
}
.btn-3d.loading .spinner{display:inline-block}
.btn-3d.loading .btn-text{opacity:0.7}
.btn-3d.loading{pointer-events:none;transform:translateY(3px);box-shadow:0 3px 0 var(--shadow-color,#000),0 4px 10px rgba(0,0,0,0.1)}
@keyframes spin{to{transform:rotate(360deg)}}

.btn-3d .shine{
  position:absolute;top:0;left:-80%;width:60%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),rgba(255,255,255,0.12),rgba(255,255,255,0.08),transparent);
  transform:skewX(-22deg);pointer-events:none;animation:shineSweep 4s ease-in-out infinite;
  z-index:0;
}
@keyframes shineSweep{0%{left:-80%}50%{left:150%}100%{left:-80%}}

/* ── Quick Access ── */
.quick-access{
  display:flex;justify-content:center;gap:14px;margin-top:10px;position:relative;z-index:1;
}
.quick-access a{
  width:48px;height:48px;border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  background:var(--bg-card);border:1px solid var(--border-light);
  color:var(--text-muted);font-size:18px;text-decoration:none;
  transition:all 0.3s;backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
  box-shadow:0 4px 12px rgba(0,0,0,0.1),inset 0 1px 0 rgba(255,255,255,0.06);
}
.quick-access a:hover{
  transform:translateY(-4px);
  box-shadow:0 8px 25px rgba(0,0,0,0.15),inset 0 1px 0 rgba(255,255,255,0.1);
}
.quick-access a:nth-child(1):hover{background:linear-gradient(135deg,rgba(255,215,0,0.12),rgba(255,215,0,0.04));border-color:rgba(255,215,0,0.15);color:var(--yellow)}
.quick-access a:nth-child(2):hover{background:linear-gradient(135deg,rgba(30,136,229,0.12),rgba(30,136,229,0.04));border-color:rgba(30,136,229,0.15);color:var(--blue)}
.quick-access a:nth-child(3):hover{background:linear-gradient(135deg,rgba(46,204,113,0.12),rgba(46,204,113,0.04));border-color:rgba(46,204,113,0.15);color:var(--green)}
/* ── Divider ── */
.divider{
  height:1px;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);
  margin:26px 0 22px;position:relative;z-index:1;
}
/* ── Footer Links (as 3D buttons) ── */
.footer-links{
  display:flex;justify-content:center;gap:16px;position:relative;z-index:1;
  flex-wrap:wrap;
}
.footer-links a{
  font-family:var(--font-accent);font-size:12px;font-weight:600;
  color:var(--text-muted);text-decoration:none;letter-spacing:0.5px;
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 18px;border-radius:12px;
  background:var(--bg-card);border:1px solid var(--border-light);
  transition:all 0.3s;text-transform:uppercase;
  box-shadow:0 3px 10px rgba(0,0,0,0.08),inset 0 1px 0 rgba(255,255,255,0.04);
}
.footer-links a:hover{
  transform:translateY(-3px);
  box-shadow:0 6px 20px rgba(0,0,0,0.12),inset 0 1px 0 rgba(255,255,255,0.08);
}
.footer-links a:nth-child(1):hover{color:var(--yellow);border-color:rgba(255,215,0,0.12)}
.footer-links a:nth-child(2):hover{color:var(--blue);border-color:rgba(30,136,229,0.12)}
/* ── Responsive ── */
@media(max-width:1200px){
  .hero-title{font-size:3rem}
  .login-card{padding:42px 36px 38px}
}
@media(max-width:1024px){
  .hero-title{font-size:2.5rem}
  .hero-stats{gap:24px}
  .login-left{padding:40px 30px}
  .login-right{padding:40px 30px}
}
@media(max-width:900px){
  body{flex-direction:column;overflow-y:auto}
  .login-bg{transform:none;animation:none;opacity:0.4}
  .login-left{width:100%;min-height:auto;padding:28px 20px 8px}
  .login-right{width:100%;min-height:auto;padding:8px 20px 28px}
  .hero-content{max-width:100%;text-align:center}
  .hero-title{font-size:2rem}
  .hero-title .highlight{display:inline}
  .hero-badge{margin-left:auto;margin-right:auto}
  .hero-stats{display:none}
  .hero-motto{display:none}
  .hero-divider{margin-left:auto;margin-right:auto}
  .login-card-wrap{max-width:460px;margin:0 auto}
  .login-card{padding:34px 28px 30px}
  .orb{display:none}
  .particle-field{opacity:0.3}
}
@media(max-width:600px){
  .login-left{padding:24px 16px 6px}
  .login-right{padding:6px 16px 24px}
  .hero-title{font-size:1.5rem;margin-bottom:12px}
  .hero-badge{font-size:10px;padding:6px 14px;margin-bottom:14px;letter-spacing:1.5px}
  .hero-institution{font-size:0.82rem;margin-bottom:6px}
  .hero-description{font-size:0.78rem;line-height:1.6}
  .hero-sub{margin-bottom:4px}
  .login-card{padding:24px 18px 20px;border-radius:18px}
  .card-header-wrap{gap:12px;margin-bottom:18px}
  .card-logo{width:46px;height:46px}
  .card-brand h2{font-size:0.92rem}
  .card-brand p{font-size:9px}
  .card-title{font-size:1.25rem}
  .card-subtitle{font-size:13px;margin-bottom:16px}
  .form-group{margin-bottom:16px}
  .form-label{font-size:10px}
  .input-wrap .form-control{font-size:16px;padding:14px 16px 14px 44px;border-radius:12px;min-height:48px}
  .input-wrap .input-icon{left:14px;font-size:13px}
  .form-options{flex-direction:column;gap:10px;align-items:flex-start}
  .btn-3d{padding:14px 22px;font-size:14px;border-radius:12px;min-height:50px}
  .btn-3d-sm{padding:12px 18px;font-size:12px;border-radius:10px;min-height:44px}
  .role-badge{padding:6px 16px;font-size:12px}
  .quick-access{gap:12px;margin-top:4px}
  .quick-access a{width:46px;height:46px;font-size:18px;border-radius:14px}
  .footer-links{gap:10px}
  .footer-links a{font-size:11px;padding:8px 16px;border-radius:10px;min-height:44px}
  .alert{padding:10px 14px;font-size:12px;border-radius:10px}
  .light-sweep{animation:none;opacity:0.3}
  .grid-overlay::before{animation:none}
}
/* ── Ultra-Compact Mobile (480px and below) ── */
@media(max-width:480px){
  body{overflow-y:auto;min-height:100dvh}
  .login-left{padding:18px 12px 4px}
  .login-right{padding:4px 12px 18px}
  .hero-title{font-size:1.25rem;line-height:1.2;margin-bottom:10px}
  .hero-badge{font-size:9px;padding:5px 12px;margin-bottom:12px;gap:5px;letter-spacing:1px}
  .hero-badge i{font-size:9px}
  .hero-institution{font-size:0.76rem;margin-bottom:5px}
  .hero-description{font-size:0.72rem;line-height:1.5}
  .login-card{border-radius:16px;padding:20px 14px 18px}
  .card-header-wrap{gap:10px;margin-bottom:14px}
  .card-logo{width:40px;height:40px}
  .card-brand h2{font-size:0.82rem}
  .card-brand p{font-size:8px;letter-spacing:0.3px}
  .card-title{font-size:1.1rem}
  .card-subtitle{font-size:12px;margin-bottom:14px;line-height:1.4}
  .role-badge{font-size:11px;padding:5px 12px;margin-top:2px;margin-bottom:12px}
  .form-group{margin-bottom:14px}
  .form-label{font-size:9px;margin-bottom:5px;letter-spacing:0.5px}
  .input-wrap .form-control{font-size:16px;padding:12px 14px 12px 40px;border-radius:10px;min-height:48px}
  .input-wrap .input-icon{left:12px;font-size:12px}
  .password-toggle{width:44px;height:44px;display:flex;align-items:center;justify-content:center;padding:0;font-size:16px;right:4px;top:50%;transform:translateY(-50%)}
  .form-options{gap:8px;margin-bottom:16px}
  .remember-me{font-size:12px}
  .remember-me input[type="checkbox"]{width:20px;height:20px}
  .forgot-link{font-size:12px}
  .btn-3d{padding:14px 18px;font-size:13px;border-radius:12px;min-height:48px}
  .btn-3d-sm{padding:10px 14px;font-size:11px;border-radius:10px;min-height:42px}
  .quick-access{gap:10px;margin-top:2px}
  .quick-access a{width:42px;height:42px;font-size:16px;border-radius:12px}
  .footer-links{gap:8px}
  .footer-links a{font-size:10px;padding:8px 12px;gap:5px;border-radius:10px;min-height:42px}
  .alert{padding:10px 12px;font-size:11px;border-radius:10px;gap:7px;margin-bottom:12px}
  .divider{margin:14px 0 12px}
  .back-home-link{font-size:0.8rem}
  .login-card:hover{box-shadow:0 2px 4px rgba(0,0,0,0.03),0 8px 24px rgba(0,0,0,0.05),0 24px 56px rgba(0,0,0,0.07)}
  #togglePass{width:48px;height:48px;right:2px}
}
/* ── Tiny screens (400px and below) ── */
@media(max-width:400px){
  .login-left{padding:14px 10px 2px}
  .login-right{padding:2px 10px 14px}
  .hero-title{font-size:1.1rem;margin-bottom:8px}
  .hero-badge{font-size:8px;padding:4px 10px;margin-bottom:10px}
  .hero-institution{font-size:0.7rem}
  .hero-description{font-size:0.67rem}
  .login-card{padding:16px 10px 14px;border-radius:14px}
  .card-header-wrap{gap:8px;margin-bottom:12px}
  .card-logo{width:36px;height:36px}
  .card-brand h2{font-size:0.76rem}
  .card-title{font-size:1rem}
  .card-subtitle{font-size:11px;margin-bottom:12px}
  .form-group{margin-bottom:12px}
  .form-label{font-size:8.5px}
  .input-wrap .form-control{padding:11px 12px 11px 36px;border-radius:10px;font-size:16px;min-height:46px}
  .input-wrap .input-icon{left:10px;font-size:11px}
  .btn-3d{padding:12px 16px;font-size:12px;min-height:44px}
  .btn-3d-sm{padding:8px 12px;font-size:10px;min-height:40px}
  .quick-access a{width:38px;height:38px;font-size:14px}
  .footer-links a{font-size:9px;padding:6px 10px;min-height:38px}
  #togglePass{width:44px;height:44px}
}
/* ── Extra tiny screens (360px and below) ── */
@media(max-width:360px){
  .hero-title{font-size:0.95rem}
  .login-card{border-radius:12px;padding:14px 8px 12px}
  .card-header-wrap{gap:6px}
  .card-logo{width:32px;height:32px}
  .card-brand h2{font-size:0.7rem}
  .card-title{font-size:0.9rem}
  .card-subtitle{font-size:10px}
  .input-wrap .form-control{font-size:16px;padding:10px 10px 10px 32px;min-height:44px}
  .form-options{flex-direction:column;gap:6px;align-items:flex-start}
  .quick-access{gap:6px}
  .quick-access a{width:34px;height:34px;font-size:12px;border-radius:10px}
  .footer-links a{font-size:8px;padding:6px 8px;min-height:34px}
  #togglePass{width:40px;height:40px}
}
</style>
</head>
<body>
<div class="particle-field">
  <div class="particle" style="--size:5px;--color:var(--yellow);--glow:10px;--glow2:30px;--duration:14s;--delay:0s;--opacity:0.7;--drift:40px;left:10%"></div>
  <div class="particle" style="--size:3px;--color:var(--blue);--glow:8px;--glow2:20px;--duration:18s;--delay:2s;--opacity:0.6;--drift:-30px;left:25%"></div>
  <div class="particle" style="--size:4px;--color:var(--green);--glow:9px;--glow2:25px;--duration:16s;--delay:4s;--opacity:0.5;--drift:50px;left:40%"></div>
  <div class="particle" style="--size:6px;--color:var(--chocolate);--glow:12px;--glow2:35px;--duration:20s;--delay:1s;--opacity:0.6;--drift:-45px;left:55%"></div>
  <div class="particle" style="--size:3px;--color:var(--red);--glow:8px;--glow2:20px;--duration:15s;--delay:3s;--opacity:0.7;--drift:35px;left:70%"></div>
  <div class="particle" style="--size:5px;--color:var(--yellow);--glow:10px;--glow2:30px;--duration:17s;--delay:5s;--opacity:0.5;--drift:-20px;left:85%"></div>
  <div class="particle" style="--size:4px;--color:var(--blue);--glow:8px;--glow2:22px;--duration:13s;--delay:7s;--opacity:0.6;--drift:60px;left:15%"></div>
  <div class="particle" style="--size:3px;--color:var(--green);--glow:7px;--glow2:18px;--duration:19s;--delay:6s;--opacity:0.5;--drift:-35px;left:60%"></div>
</div>
<div class="mesh-bg"></div>
<div class="glass-tint"></div>
<div class="grid-overlay"></div>
<div class="light-sweep"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="login-bg"></div>
<div class="bg-overlay"></div>
<div class="login-left">
  <div class="hero-content">
    <div class="hero-badge"><i class="fas fa-crown"></i> &nbsp; Staff Portal</div>
    <h1 class="hero-title">Welcome to<br><span class="highlight">ISNM</span></h1>
    <div class="hero-sub">
      <span class="hero-institution">Iganga School of Nursing &amp; Midwifery</span>
      <span class="hero-divider"></span>
      <span class="hero-description">Uganda's premier institution for nursing and midwifery education, empowering healthcare professionals since establishment.</span>
    </div>
    <div class="hero-motto">
      <div class="motto-icon"><i class="fas fa-star"></i></div>
      <div>
        <strong>Chosen to Serve</strong>
        <span class="motto-sub">Based on a disciplined mind for health action.</span>
      </div>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-num">06</div><div class="hero-stat-label">Programs</div></div>
      <div class="hero-stat"><div class="hero-stat-num">1000+</div><div class="hero-stat-label">Students</div></div>
      <div class="hero-stat"><div class="hero-stat-num">50+</div><div class="hero-stat-label">Staff</div></div>
      <div class="hero-stat"><div class="hero-stat-num">15+</div><div class="hero-stat-label">Years</div></div>
    </div>
  </div>
</div>
<div class="login-right">
  <div class="text-center mb-3">
  <a href="index.php" class="text-decoration-none back-home-link" style="color:var(--yellow);font-size:0.9rem">
    <i class="fas fa-arrow-left me-1"></i> Back to Home
  </a>
</div>
<div class="login-card-wrap">
    <div class="login-card">
      <div class="card-accent-top"></div>
      <div class="card-glow"></div>
      <div class="card-header-wrap">
        <img src="images/school-logo.png" alt="ISNM" class="card-logo">
        <div class="card-brand">
          <h2>Iganga School of Nursing</h2>
          <p>and Midwifery &bull; MOES &amp; UNMC Registered</p>
        </div>
      </div>
      <div class="card-title">Staff <span>Portal</span></div>
      <div class="card-subtitle">Welcome back. Sign in to access your dashboard.</div>
      <?php if ($requested_position): ?>
        <div class="role-badge"><i class="fas fa-briefcase"></i> <?=htmlspecialchars($requested_position, ENT_QUOTES, 'UTF-8')?></div>
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
        <button type="submit" class="btn-3d btn-blue btn-3d-block" id="btnLogin">
          <span class="shine"></span>
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
        <a href="index.php"><i class="fas fa-globe"></i> Main Website</a>
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
  if(c && !('ontouchstart' in window)){
    document.addEventListener('mousemove',function(m){
      var r=c.getBoundingClientRect();
      var x=(m.clientX-r.left)/r.width-0.5;
      var y=(m.clientY-r.top)/r.height-0.5;
      c.style.transform='perspective(1400px) rotateX('+(1.5-y*3.5)+'deg) rotateY('+(x*3.5)+'deg) translateY(0)';
    })
  } else if (c) {
    c.style.transform='perspective(1400px) rotateX(1.5deg)';
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
