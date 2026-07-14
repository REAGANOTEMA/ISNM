<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/financial_functions.php';

if (empty($_SESSION['user_id']) || ($_SESSION['type'] ?? '') !== 'student') {
    header('Location: ../student-login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$studentsDb = getStudentsConnection();
$staffDb = getStaffConnection();

$student_id = (int)$_SESSION['user_id'];
$student = null;
if ($studentsDb) {
    $stmt = $studentsDb->prepare("SELECT * FROM students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
if (!$student) { header('Location: ../student-login.php'); exit(); }

$student_number = $student['student_number'] ?? $student['registration_number'] ?? '';
$full_name = $student['full_name'] ?? trim(($student['first_name'] ?? '').' '.($student['surname'] ?? ''));
$program = $student['program'] ?? '';
$current_year = (int)($student['current_year'] ?? $student['year'] ?? 1);
$level = $student['level'] ?? '';
$set_name = $student['set_name'] ?? '';
$gender = $student['gender'] ?? '';
$email = $student['email'] ?? '';
$phone = $student['phone'] ?? $student['mobile_number'] ?? '';
$dob = $student['date_of_birth'] ?? '';
$guardian_name = $student['guardian_name'] ?? '';
$guardian_phone = $student['guardian_phone'] ?? '';
$profile_picture = $student['profile_picture'] ?? '';
$student_status = $student['status'] ?? 'Active';

function tableExists($db, $table) {
    if (!$db) return false;
    $r = $db->query("SHOW TABLES LIKE '$table'");
    return $r && $r->num_rows > 0;
}

function safeQuery($db, $sql) {
    if (!$db) return [];
    $r = $db->query($sql);
    if (!$r) return [];
    $data = [];
    while ($row = $r->fetch_assoc()) $data[] = $row;
    return $data;
}

function safeQueryPrepared($db, $sql, $types, $params) {
    if (!$db) return [];
    $stmt = $db->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); };
    $result = $stmt->get_result();
    $data = [];
    if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
    $stmt->close();
    return $data;
}

$page = $_GET['page'] ?? 'dashboard';
$msg = null;
if (isset($_SESSION['success'])) { $msg = ['type'=>'success','text'=>$_SESSION['success']]; unset($_SESSION['success']); }
if (isset($_SESSION['error'])) { $msg = ['type'=>'danger','text'=>$_SESSION['error']]; unset($_SESSION['error']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $studentsDb) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header("Location: student-portal.php?page=" . urlencode($page));
        exit();
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $file['size'] <= 5*1024*1024) {
                $dir = __DIR__ . '/../studentUploads/profile_images/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'student_' . $student_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                    $stmt = $studentsDb->prepare("UPDATE students SET profile_picture = ? WHERE id = ?");
                    if ($stmt) { $stmt->bind_param("si", $filename, $student_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
                    $_SESSION['success'] = 'Profile photo updated.';
                } else { $_SESSION['error'] = 'Failed to upload file.'; }
            } else { $_SESSION['error'] = 'Invalid file type or size (max 5MB).' ; }
        }
        header("Location: student-portal.php?page=profile");
        exit();
    }
    if ($action === 'remove_photo') {
        $stmt = $studentsDb->prepare("UPDATE students SET profile_picture = NULL WHERE id = ?");
        if ($stmt) { $stmt->bind_param("i", $student_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Profile photo removed.';
        header("Location: student-portal.php?page=profile");
        exit();
    }
    if ($action === 'submit_request') {
        $req_type = trim($_POST['request_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        if ($req_type && $reason) {
            $stmt = $studentsDb->prepare("INSERT INTO student_requests (student_id, request_type, reason, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
            if ($stmt) { $sid = $student_id; $stmt->bind_param("iss", $sid, $req_type, $reason); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
            $_SESSION['success'] = 'Request submitted successfully.';
        } else { $_SESSION['error'] = 'Please fill all required fields.'; }
        header("Location: student-portal.php?page=requests");
        exit();
    }
    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_phone = trim($_POST['guardian_phone'] ?? '');
        $stmt = $studentsDb->prepare("UPDATE students SET phone=?, email=?, guardian_name=?, guardian_phone=? WHERE id=?");
        if ($stmt) { $stmt->bind_param("ssssi", $phone, $email, $guardian_name, $guardian_phone, $student_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $stmt->close(); }
        $_SESSION['success'] = 'Profile updated.';
        header("Location: student-portal.php?page=profile");
        exit();
    }
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) { $_SESSION['error'] = 'Passwords do not match.'; header("Location: student-portal.php?page=password"); exit(); }
        if (strlen($new) < 6) { $_SESSION['error'] = 'Password must be at least 6 characters.'; header("Location: student-portal.php?page=password"); exit(); }
        $stmt = $studentsDb->prepare("SELECT password FROM students WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $student_id); if (!$stmt->execute()) { error_log('$stmt execute failed: ' . ($stmt->error ?? 'unknown')); }; $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $storedHash = $row['password'] ?? '';
            if (password_verify($current, $storedHash)) {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $studentsDb->prepare("UPDATE students SET password=?, password_changed=1 WHERE id=?");
                if ($upd) { $upd->bind_param("si", $newHash, $student_id); if (!$upd->execute()) { error_log('$upd execute failed: ' . ($upd->error ?? 'unknown')); }; $upd->close(); }
                $_SESSION['success'] = 'Password changed successfully.';
            } else { $_SESSION['error'] = 'Current password is incorrect.'; }
        }
        header("Location: student-portal.php?page=password");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ISNM Student Portal â€” <?= ucfirst(htmlspecialchars($page)) ?></title>
<link rel="icon" href="../images/school-logo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/mobile-fixes.css?v=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f0f2f5;color:#1a1d29}
.sp-sidebar{position:fixed;top:0;left:0;width:260px;height:100vh;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);color:#fff;z-index:1000;overflow-y:auto;transition:transform .3s}
.sp-sidebar .brand{padding:20px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
.sp-sidebar .brand img{width:50px;height:50px;border-radius:50%;margin-bottom:8px}
.sp-sidebar .brand h5{font-size:.95rem;font-weight:600;margin:0}
.sp-sidebar .brand small{font-size:.75rem;color:#94a3b8}
.sp-sidebar .nav-menu{padding:12px 0}
.sp-sidebar .nav-item{display:block;padding:10px 20px;color:#cbd5e1;text-decoration:none;font-size:.85rem;transition:all .2s;border-left:3px solid transparent}
.sp-sidebar .nav-item:hover{background:rgba(255,255,255,.08);color:#fff}
.sp-sidebar .nav-item.active{background:rgba(59,130,246,.15);color:#60a5fa;border-left-color:#3b82f6;font-weight:600}
.sp-sidebar .nav-item i{width:22px;text-align:center;margin-right:10px;font-size:.9rem}
.sp-sidebar .nav-section{padding:8px 20px;font-size:.7rem;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-top:8px}
.sp-topbar{position:fixed;top:0;left:260px;right:0;height:60px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;padding:0 24px;z-index:999}
.sp-topbar .page-title{font-size:1.1rem;font-weight:600}
.sp-topbar .user-info{display:flex;align-items:center;gap:12px}
.sp-topbar .user-info img{width:36px;height:36px;border-radius:50%;object-fit:cover}
.sp-topbar .user-info .name{font-size:.85rem;font-weight:500}
.sp-topbar .user-info .role{font-size:.7rem;color:#64748b}
.sp-content{margin-left:260px;margin-top:60px;padding:24px;min-height:calc(100vh - 60px)}
.sp-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:20px}
.sp-card h4{font-size:1.1rem;font-weight:600;margin-bottom:16px;color:#1e293b}
.sp-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.sp-stat{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);text-align:center}
.sp-stat .icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.2rem}
.sp-stat h3{font-size:1.5rem;font-weight:700;margin-bottom:4px}
.sp-stat p{font-size:.8rem;color:#64748b}
.sp-table{width:100%;border-collapse:collapse}
.sp-table th,.sp-table td{padding:10px 12px;text-align:left;border-bottom:1px solid #f1f5f9;font-size:.85rem}
.sp-table th{background:#f8fafc;font-weight:600;color:#475569;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px}
.sp-table tr:hover{background:#f8fafc}
.sp-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:600}
.sp-badge-success{background:#d1fae5;color:#065f46}
.sp-badge-warning{background:#fef3c7;color:#92400e}
.sp-badge-danger{background:#fee2e2;color:#991b1b}
.sp-badge-info{background:#dbeafe;color:#1e40af}
.sp-badge-secondary{background:#f1f5f9;color:#475569}
.sp-btn{padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:500;border:none;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.sp-btn-primary{background:#3b82f6;color:#fff}.sp-btn-primary:hover{background:#2563eb}
.sp-btn-success{background:#10b981;color:#fff}.sp-btn-success:hover{background:#059669}
.sp-btn-danger{background:#ef4444;color:#fff}.sp-btn-danger:hover{background:#dc2626}
.sp-btn-outline{background:transparent;border:1px solid #e2e8f0;color:#475569}.sp-btn-outline:hover{background:#f8fafc}
.sp-form-group{margin-bottom:16px}
.sp-form-group label{display:block;font-size:.82rem;font-weight:500;color:#374151;margin-bottom:6px}
.sp-form-group input,.sp-form-group select,.sp-form-group textarea{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;transition:border-color .2s}
.sp-form-group input:focus,.sp-form-group select:focus,.sp-form-group textarea:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.sp-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.sp-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.sp-alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem;display:flex;align-items:center;gap:8px}
.sp-alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.sp-alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.sp-alert-warning{background:#fef3c7;color:#92400e;border:1px solid #fcd34d}
.sp-tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap}
.sp-tab{padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:500;color:#64748b;background:#fff;border:1px solid #e2e8f0;cursor:pointer;text-decoration:none}
.sp-tab:hover{background:#f1f5f9;color:#1e293b}
.sp-tab.active{background:#3b82f6;color:#fff;border-color:#3b82f6}
.sp-empty{text-align:center;padding:40px;color:#94a3b8}
.sp-empty i{font-size:2.5rem;margin-bottom:12px;display:block}
.sp-profile-card{text-align:center;padding:24px;position:relative}
.sp-profile-card .sp-photo-wrapper{position:relative;display:inline-block}
.sp-profile-card .sp-photo-wrapper img{width:110px;height:110px;border-radius:50%;object-fit:cover;margin-bottom:12px;border:3px solid #e2e8f0;transition:border-color .2s}
.sp-profile-card h4{font-size:1.1rem;margin-bottom:4px}
.sp-profile-card p{font-size:.82rem;color:#64748b}
.sp-remove-photo-form{position:absolute;top:-2px;right:-4px;margin:0}
.sp-btn-remove-photo{width:28px;height:28px;border-radius:50%;border:2px solid #fecaca;background:#fef2f2;color:#dc2626;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .15s}
.sp-btn-remove-photo:hover{background:#fee2e2;border-color:#fca5a5}
.sp-upload-zone{position:relative;border:2px dashed #cbd5e1;border-radius:10px;padding:16px 12px;text-align:center;transition:all .2s;background:#f8fafc}
.sp-upload-zone.dragover{border-color:#3b82f6;background:#eff6ff}
.sp-upload-zone.has-file{border-color:#059669;background:#f0fdf4}
.sp-timeline{position:relative;padding-left:24px}
.sp-timeline::before{content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:#e2e8f0}
.sp-timeline-item{position:relative;padding-bottom:20px}
.sp-timeline-item::before{content:'';position:absolute;left:-20px;top:4px;width:12px;height:12px;border-radius:50%;background:#3b82f6;border:2px solid #fff}
.sp-timeline-item h6{font-size:.85rem;font-weight:600;margin-bottom:2px}
.sp-timeline-item p{font-size:.78rem;color:#64748b}
.sp-progress{height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden}
.sp-progress-bar{height:100%;background:#3b82f6;border-radius:4px;transition:width .3s}
@media(max-width:768px){
.sp-sidebar{transform:translateX(-100%);width:280px;max-width:85vw;z-index:1100}
.sp-sidebar.open{transform:translateX(0)}
.sp-topbar{left:0;padding:0 14px 0 56px}
.sp-content{margin-left:0;padding:16px}
.sp-grid-2,.sp-grid-3{grid-template-columns:1fr}
.sp-stats{grid-template-columns:repeat(2,1fr)}
.sp-tabs{overflow-x:auto;flex-wrap:nowrap;-webkit-overflow-scrolling:touch}
.mobile-toggle{display:flex!important}
.sp-card{padding:16px}
.sp-stat{padding:14px}
.sp-stat h3{font-size:1.2rem}
.sp-stat p{font-size:.75rem}
.sp-table th,.sp-table td{padding:8px 6px;font-size:.78rem}
.sp-btn{min-height:40px;padding:8px 14px}
.sp-form-group input,.sp-form-group select,.sp-form-group textarea{font-size:16px!important;padding:10px 12px!important;min-height:44px!important}
}
.mobile-toggle{
  display:none;
  position:fixed;
  top:14px;
  left:14px;
  z-index:1100;
  background:linear-gradient(135deg,#FFD700,#FFA000);
  color:#3E2723;
  border:none;
  border-radius:10px;
  width:44px;
  height:44px;
  min-width:44px;
  min-height:44px;
  font-size:1rem;
  cursor:pointer;
  align-items:center;
  justify-content:center;
  box-shadow:0 2px 10px rgba(255,215,0,0.3);
  transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
  -webkit-tap-highlight-color:transparent;
}
.mobile-toggle:hover{transform:scale(1.05);box-shadow:0 4px 16px rgba(255,215,0,0.4)}
.mobile-toggle:active{transform:scale(0.95)}
.mobile-toggle i{transition:transform 0.3s ease}
.sidebar-overlay{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.5);
  z-index:1050;
  backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);
}
.sidebar-overlay.show{display:block}
</style>
</head>
<body>
<button class="mobile-toggle" id="spMobileToggle" aria-label="Toggle navigation">
  <i class="fas fa-bars" id="spToggleIcon"></i>
</button>
<div class="sidebar-overlay" id="spOverlay"></div>

<script>
(function(){
  var toggle = document.getElementById('spMobileToggle');
  var sidebar = document.querySelector('.sp-sidebar');
  var overlay = document.getElementById('spOverlay');
  var icon = document.getElementById('spToggleIcon');
  if(!toggle || !sidebar) return;

  function openMenu(){
    sidebar.classList.add('open');
    if(overlay) overlay.classList.add('show');
    icon.className = 'fas fa-times';
    document.body.style.overflow = 'hidden';
  }
  function closeMenu(){
    sidebar.classList.remove('open');
    if(overlay) overlay.classList.remove('show');
    icon.className = 'fas fa-bars';
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function(){
    if(sidebar.classList.contains('open')) closeMenu();
    else openMenu();
  });

  if(overlay){
    overlay.addEventListener('click', closeMenu);
  }

  // Close on nav link click
  sidebar.querySelectorAll('.nav-item').forEach(function(link){
    link.addEventListener('click', function(){
      if(window.innerWidth <= 768) closeMenu();
    });
  });

  // Close on resize to desktop
  window.addEventListener('resize', function(){
    if(window.innerWidth > 768) closeMenu();
  });
})();
</script>

<aside class="sp-sidebar">
<div class="brand">
<img src="../images/school-logo.png" alt="ISNM">
<h5><?= htmlspecialchars($full_name) ?></h5>
<small><?= htmlspecialchars($student_number) ?></small>
</div>
<nav class="nav-menu">
<div class="nav-section">Main</div>
<a class="nav-item <?= $page==='dashboard'?'active':'' ?>" href="?page=dashboard"><i class="fas fa-th-large"></i>Dashboard</a>
<a class="nav-item <?= $page==='profile'?'active':'' ?>" href="?page=profile"><i class="fas fa-user-circle"></i>My Profile</a>
<div class="nav-section">Academics</div>
<a class="nav-item <?= in_array($page,['academics','courses','results','attendance'])?'active':'' ?>" href="?page=academics"><i class="fas fa-graduation-cap"></i>Academic Record</a>
<a class="nav-item <?= $page==='courses'?'active':'' ?>" href="?page=courses"><i class="fas fa-book"></i>Course Registration</a>
<a class="nav-item <?= $page==='results'?'active':'' ?>" href="?page=results"><i class="fas fa-file-alt"></i>Results & Transcripts</a>
<a class="nav-item <?= $page==='attendance'?'active':'' ?>" href="?page=attendance"><i class="fas fa-clipboard-check"></i>Attendance</a>
<div class="nav-section">Clinical</div>
<a class="nav-item <?= in_array($page,['clinical','logbook','competency'])?'active':'' ?>" href="?page=clinical"><i class="fas fa-hospital"></i>Clinical Placement</a>
<a class="nav-item <?= $page==='logbook'?'active':'' ?>" href="?page=logbook"><i class="fas fa-book-open"></i>Logbook</a>
<a class="nav-item <?= $page==='competency'?'active':'' ?>" href="?page=competency"><i class="fas fa-star"></i>Competencies</a>
<div class="nav-section">Admission</div>
<a class="nav-item <?= $page==='requirements'?'active':'' ?>" href="?page=requirements"><i class="fas fa-check-double"></i>Requirements</a>
<div class="nav-section">Student Life</div>
<a class="nav-item <?= in_array($page,['discipline','finances','hostel','library'])?'active':'' ?>" href="?page=discipline"><i class="fas fa-gavel"></i>Discipline</a>
<a class="nav-item <?= $page==='finances'?'active':'' ?>" href="?page=finances"><i class="fas fa-money-bill"></i>Finances</a>
<a class="nav-item <?= $page==='hostel'?'active':'' ?>" href="?page=hostel"><i class="fas fa-home"></i>Hostel</a>
<a class="nav-item <?= $page==='library'?'active':'' ?>" href="?page=library"><i class="fas fa-book-reader"></i>Library</a>
<div class="nav-section">Communication</div>
<a class="nav-item <?= in_array($page,['notices','messages','requests'])?'active':'' ?>" href="?page=notices"><i class="fas fa-bullhorn"></i>Announcements</a>
<a class="nav-item <?= $page==='messages'?'active':'' ?>" href="?page=messages"><i class="fas fa-envelope"></i>Messages</a>
<a class="nav-item <?= $page==='requests'?'active':'' ?>" href="?page=requests"><i class="fas fa-paper-plane"></i>My Requests</a>
<div class="nav-section">Schedule</div>
<a class="nav-item <?= $page==='timetable'?'active':'' ?>" href="?page=timetable"><i class="fas fa-calendar-alt"></i>Timetable</a>
<div class="nav-section">Account</div>
<a class="nav-item <?= $page==='password'?'active':'' ?>" href="?page=password"><i class="fas fa-key"></i>Change Password</a>
<a class="nav-item" href="#" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../logout.php';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt"></i>Logout</a>
</nav>
</aside>

<header class="sp-topbar">
<div class="page-title"><i class="fas fa-graduation-cap me-2"></i><?= ucfirst(htmlspecialchars($page)) ?></div>
<div class="user-info">
<span><span class="name"><?= htmlspecialchars($full_name) ?></span><br><span class="role"><?= htmlspecialchars($program) ?> Â· Year <?= $current_year ?></span></span>
<a href="#" class="sp-btn sp-btn-outline" title="Logout" onclick="event.preventDefault();var f=document.createElement('form');f.method='POST';f.action='../logout.php';document.body.appendChild(f);f.submit();"><i class="fas fa-sign-out-alt"></i></a>
</div>
</header>

<main class="sp-content">
<?php if ($msg): ?>
<div class="sp-alert sp-alert-<?= $msg['type'] ?>"><i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i><?= $msg['text'] ?></div>
<?php endif; ?>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 1. DASHBOARD
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
if ($page === 'dashboard'):
    $fee_balance = 0;
    if (tableExists($studentsDb, 'student_fee_tracking')) {
        $fr = $studentsDb->query("SELECT COALESCE(SUM(balance),0) as bal FROM student_fee_tracking WHERE student_id=$student_id AND status != 'Paid'");
        if ($fr && $fr->num_rows) $fee_balance = (float)$fr->fetch_assoc()['bal'];
    } elseif (tableExists($studentsDb, 'student_fee_accounts')) {
        $fr = $studentsDb->query("SELECT COALESCE(SUM(balance),0) as bal FROM student_fee_accounts WHERE student_id=$student_id AND status != 'paid'");
        if ($fr && $fr->num_rows) $fee_balance = (float)$fr->fetch_assoc()['bal'];
    }
    $attendance_pct = 0;
    if (tableExists($studentsDb, 'student_attendance')) {
        $ar = $studentsDb->query("SELECT ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) as pct FROM student_attendance WHERE student_id=$student_id");
        if ($ar && $ar->num_rows) $attendance_pct = (float)$ar->fetch_assoc()['pct'];
    }
    $active_warnings = 0;
    if (tableExists($studentsDb, 'student_warnings')) {
        $wr = $studentsDb->query("SELECT COUNT(*) as cnt FROM student_warnings WHERE student_id=$student_id AND status='Active'");
        if ($wr && $wr->num_rows) $active_warnings = (int)$wr->fetch_assoc()['cnt'];
    }
    $gpa = 0;
    if (tableExists($studentsDb, 'student_semester_gpa')) {
        $gr = $studentsDb->query("SELECT semester_gpa FROM student_semester_gpa WHERE student_id=$student_id ORDER BY id DESC LIMIT 1");
        if ($gr && $gr->num_rows) $gpa = (float)$gr->fetch_assoc()['semester_gpa'];
    } elseif (tableExists($studentsDb, 'student_academic_records')) {
        $gr = $studentsDb->query("SELECT gpa FROM student_academic_records WHERE student_id=$student_id ORDER BY id DESC LIMIT 1");
        if ($gr && $gr->num_rows) $gpa = (float)$gr->fetch_assoc()['gpa'];
    }
    // Admission requirements status from staff DB
    $admissionStatus = 'Not Set';
    $requirements = [];
    $directorNotes = [];
    $reqTotal = 0; $reqCompleted = 0;
    if ($staffDb && tableExists($staffDb, 'applicant_requirement_status') && tableExists($staffDb, 'admission_requirements')) {
        $studentNum = $student['student_number'] ?? $student['registration_number'] ?? '';
        // Find applicant record by student_number or registration_number
        $appQ = $staffDb->query("SELECT id FROM applicants WHERE student_number='".$staffDb->real_escape_string($studentNum)."' OR registration_number='".$staffDb->real_escape_string($studentNum)."' LIMIT 1");
        if ($appQ && $appQ->num_rows > 0) {
            $appRow = $appQ->fetch_assoc();
            $appId = (int)$appRow['id'];
            // Get admission status
            $trackQ = $staffDb->query("SELECT admission_status FROM student_admission_tracking WHERE applicant_id=$appId LIMIT 1");
            if ($trackQ && $trackQ->num_rows > 0) { $admissionStatus = $trackQ->fetch_assoc()['admission_status']; }
            // Get requirements with director notes
            $reqQ = $staffDb->query("SELECT ar.requirement_name, ars.status, ars.director_notes, ars.updated_at FROM applicant_requirement_status ars JOIN admission_requirements ar ON ars.requirement_id=ar.id WHERE ars.applicant_id=$appId AND ar.is_active=1 ORDER BY ar.display_order");
            if ($reqQ) { while ($r = $reqQ->fetch_assoc()) { $requirements[] = $r; if (!empty($r['director_notes'])) $directorNotes[] = ['req'=>$r['requirement_name'], 'note'=>$r['director_notes']]; } }
            $reqTotal = count($requirements);
            $reqCompleted = count(array_filter($requirements, fn($r) => in_array($r['status'], ['Submitted','Verified','Received'])));
        }
    }
    $first_name_greeting = explode(' ', trim($full_name))[0] ?? 'Student';
?>
<div class="sp-card" style="background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;margin-bottom:20px">
<h4 style="color:#fff;margin-bottom:4px"><i class="fas fa-hand-wave me-2"></i>Welcome <?= htmlspecialchars($first_name_greeting) ?></h4>
<p style="opacity:.85;font-size:.85rem;margin:0"><?= htmlspecialchars($program) ?> &middot; Year <?= $current_year ?> &middot; <?= htmlspecialchars($student_number) ?></p>
</div>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-graduation-cap"></i></div><h3><?= $current_year ?></h3><p>Year of Study</p></div>
<div class="sp-stat"><div class="icon" style="background:#d1fae5;color:#059669"><i class="fas fa-chart-line"></i></div><h3><?= number_format($gpa, 2) ?></h3><p>GPA</p></div>
<div class="sp-stat"><div class="icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-clipboard-check"></i></div><h3><?= number_format($attendance_pct, 1) ?>%</h3><p>Attendance</p></div>
<div class="sp-stat"><div class="icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-money-bill"></i></div><h3>UGX <?= number_format($fee_balance) ?></h3><p>Fee Balance</p></div>
<?php if ($reqTotal > 0): ?>
<div class="sp-stat"><div class="icon" style="background:#ede9fe;color:#7C3AED"><i class="fas fa-check-double"></i></div><h3><?= $reqCompleted ?>/<?= $reqTotal ?></h3><p>Requirements Met</p></div>
<div class="sp-stat"><div class="icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-clipboard-list"></i></div><h3><?= htmlspecialchars($admissionStatus) ?></h3><p>Admission Status</p></div>
<?php endif; ?>
</div>
<?php if ($reqTotal > 0 && $reqCompleted < $reqTotal): ?>
<div class="sp-alert sp-alert-warning" style="margin-bottom:16px">
<i class="fas fa-exclamation-triangle"></i>
<div><strong>Admission Hold:</strong> You have <?= $reqTotal - $reqCompleted ?> incomplete requirement(s) (<?= $reqCompleted ?>/<?= $reqTotal ?> completed). Please contact the Admissions Office to resolve missing items before your admission can be finalized.
<?php if (!empty($directorNotes)): ?><br><small style="margin-top:4px;display:block">Director's remarks are available on the <a href="?page=requirements" style="color:#92400e;font-weight:600">Requirements page</a>.</small><?php endif; ?>
</div>
</div>
<?php endif; ?>
<div class="sp-grid-2">
<div class="sp-card">
<h4><i class="fas fa-user-circle me-2"></i>Quick Info</h4>
<table class="sp-table">
<tr><td style="width:140px;color:#64748b">Name</td><td><strong><?= htmlspecialchars($full_name) ?></strong></td></tr>
<tr><td style="color:#64748b">Reg No</td><td><?= htmlspecialchars($student_number) ?></td></tr>
<tr><td style="color:#64748b">Program</td><td><?= htmlspecialchars($program) ?></td></tr>
<tr><td style="color:#64748b">Year / Level</td><td>Year <?= $current_year ?> Â· <?= htmlspecialchars($level) ?></td></tr>
<tr><td style="color:#64748b">Set</td><td><?= htmlspecialchars($set_name) ?></td></tr>
<tr><td style="color:#64748b">Status</td><td><span class="sp-badge sp-badge-<?= $student_status==='Active'?'success':'warning' ?>"><?= htmlspecialchars($student_status) ?></span></td></tr>
<?php if ($admissionStatus !== 'Not Set'): ?><tr><td style="color:#64748b">Admission</td><td><span class="sp-badge sp-badge-<?= $admissionStatus==='Registered'?'success':'warning' ?>"><?= htmlspecialchars($admissionStatus) ?></span></td></tr><?php endif; ?>
</table>
</div>
<div class="sp-card">
<h4><i class="fas fa-exclamation-triangle me-2"></i>Recent Activity</h4>
<?php
$notifs = [];
if (tableExists($studentsDb, 'student_notifications')) {
    $notifs = safeQuery($studentsDb, "SELECT * FROM student_notifications WHERE student_id=$student_id ORDER BY created_at DESC LIMIT 5");
}
if (empty($notifs)) {
    echo '<div class="sp-empty"><i class="fas fa-bell"></i><p>No recent notifications</p></div>';
} else {
    foreach ($notifs as $n): ?>
    <div style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:.85rem">
    <strong><?= htmlspecialchars($n['title'] ?? '') ?></strong>
    <p style="color:#64748b;font-size:.78rem;margin:2px 0"><?= htmlspecialchars($n['message'] ?? '') ?></p>
    <small style="color:#94a3b8"><?= date('M j, Y', strtotime($n['created_at'])) ?></small>
    </div>
    <?php endforeach;
}
?>
</div>
</div>
<div class="sp-card">
<h4><i class="fas fa-link me-2"></i>Quick Actions</h4>
<div style="display:flex;gap:12px;flex-wrap:wrap">
<a href="?page=courses" class="sp-btn sp-btn-primary"><i class="fas fa-book"></i>Register Courses</a>
<a href="?page=results" class="sp-btn sp-btn-success"><i class="fas fa-file-alt"></i>View Results</a>
<a href="?page=finances" class="sp-btn sp-btn-outline"><i class="fas fa-money-bill"></i>Fee Status</a>
<a href="?page=timetable" class="sp-btn sp-btn-outline"><i class="fas fa-calendar"></i>Timetable</a>
<a href="?page=requests" class="sp-btn sp-btn-outline"><i class="fas fa-paper-plane"></i>Submit Request</a>
<a href="?page=logbook" class="sp-btn sp-btn-outline"><i class="fas fa-book-open"></i>Logbook</a>
</div>
</div>

<?php if (!empty($requirements)): ?>
<div class="sp-card">
<h4><i class="fas fa-check-double me-2"></i>Admission Requirements Status</h4>
<div class="sp-progress" style="margin-bottom:16px"><div class="sp-progress-bar" style="width:<?= $reqTotal>0 ? round($reqCompleted/$reqTotal*100) : 0 ?>%"></div></div>
<p style="font-size:.82rem;color:#64748b;margin-bottom:12px"><?= $reqCompleted ?> of <?= $reqTotal ?> requirements completed &middot; Admission: <strong><?= htmlspecialchars($admissionStatus) ?></strong></p>
<table class="sp-table">
<thead><tr><th>Requirement</th><th>Status</th><th>Updated</th></tr></thead>
<tbody>
<?php foreach ($requirements as $req): $s = $req['status'] ?? 'Not Submitted'; ?>
<tr>
<td><?= htmlspecialchars($req['requirement_name']) ?></td>
<td><span class="sp-badge sp-badge-<?= in_array($s,['Verified','Received'])?'success':(in_array($s,['Submitted'])?'info':(in_array($s,['Rejected','Missing'])?'danger':($s==='Not Yet Given'?'warning':'secondary'))) ?>"><?= htmlspecialchars($s) ?></span></td>
<td><?= $req['updated_at'] ? date('M j, Y', strtotime($req['updated_at'])) : '-' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php if (!empty($directorNotes)): ?>
<div class="sp-card">
<h4><i class="fas fa-sticky-note me-2"></i>Director's Remarks</h4>
<?php foreach ($directorNotes as $dn): ?>
<div style="padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;border-left:3px solid #7C3AED">
<p style="font-size:.78rem;color:#64748b;font-weight:600;margin-bottom:4px"><?= htmlspecialchars($dn['req']) ?></p>
<p style="font-size:.85rem;color:#1e293b"><?= nl2br(htmlspecialchars($dn['note'])) ?></p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// REQUIREMENTS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'requirements'):
?>
<div class="sp-card">
<h4><i class="fas fa-check-double me-2"></i>Admission Requirements</h4>
<p style="color:#64748b;margin-bottom:16px">Track your admission requirements status. Director remarks are shown for each requirement.</p>
<?php if (empty($requirements)): ?>
<div class="sp-empty"><i class="fas fa-file-alt"></i><p>No requirement records found for your account.</p></div>
<?php else: ?>
<div class="sp-progress" style="margin-bottom:20px"><div class="sp-progress-bar" style="width:<?= $reqTotal>0 ? round($reqCompleted/$reqTotal*100) : 0 ?>%"></div></div>
<p style="font-size:.85rem;color:#64748b;margin-bottom:16px"><?= $reqCompleted ?> of <?= $reqTotal ?> requirements completed (<?= $reqTotal>0 ? round($reqCompleted/$reqTotal*100) : 0 ?>%)</p>
<table class="sp-table">
<thead><tr><th>#</th><th>Requirement</th><th>Status</th><th>Last Updated</th><th>Director's Note</th></tr></thead>
<tbody>
<?php $i = 0; foreach ($requirements as $req): $i++; $s = $req['status'] ?? 'Not Submitted'; ?>
<tr>
<td><?= $i ?></td>
<td><?= htmlspecialchars($req['requirement_name']) ?></td>
<td><span class="sp-badge sp-badge-<?= in_array($s,['Verified','Received'])?'success':(in_array($s,['Submitted'])?'info':(in_array($s,['Rejected','Missing'])?'danger':($s==='Not Yet Given'?'warning':'secondary'))) ?>"><?= htmlspecialchars($s) ?></span></td>
<td><?= $req['updated_at'] ? date('M j, Y', strtotime($req['updated_at'])) : '-' ?></td>
<td><?= !empty($req['director_notes']) ? nl2br(htmlspecialchars(substr($req['director_notes'],0,200))) : '<span style="color:#94a3b8">â€”</span>' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="sp-card">
<h4><i class="fas fa-info-circle me-2"></i>Admission Status</h4>
<p><strong>Current Status:</strong> <span class="sp-badge sp-badge-<?= $admissionStatus==='Registered'?'success':'warning' ?>"><?= htmlspecialchars($admissionStatus) ?></span></p>
<p style="color:#64748b;margin-top:8px;font-size:.85rem">Your admission status reflects the current stage of your application process. Contact the Admissions Office for any questions.</p>
</div>
<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 2. PROFILE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'profile'):
    $profile = null;
    if (tableExists($studentsDb, 'student_profiles')) {
        $pr = $studentsDb->prepare("SELECT * FROM student_profiles WHERE student_id = ?");
        if ($pr) { $pr->bind_param("i", $student_id); if (!$pr->execute()) { error_log('$pr execute failed: ' . ($pr->error ?? 'unknown')); }; $profile = $pr->get_result()->fetch_assoc(); $pr->close(); }
    }
?>
<div class="sp-grid-2">
<div class="sp-card sp-profile-card">
<div class="sp-photo-wrapper">
  <img id="profilePhotoPreview" src="<?= $profile_picture ? '../'.$profile_picture : 'https://ui-avatars.com/api/?name='.urlencode($full_name).'&size=100&background=3b82f6&color=fff' ?>" alt="Photo">
  <?php if ($profile_picture): ?>
  <form method="POST" class="sp-remove-photo-form" onsubmit="return confirm('Remove your profile photo?')">
    <input type="hidden" name="action" value="remove_photo">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <button type="submit" class="sp-btn-remove-photo" title="Remove photo"><i class="fas fa-times"></i></button>
  </form>
  <?php endif; ?>
</div>
<h4><?= htmlspecialchars($full_name) ?></h4>
<p><?= htmlspecialchars($student_number) ?></p>
<p style="margin-top:4px"><span class="sp-badge sp-badge-<?= $student_status==='Active'?'success':'warning' ?>"><?= htmlspecialchars($student_status) ?></span></p>
<form method="POST" enctype="multipart/form-data" style="margin-top:16px">
<input type="hidden" name="action" value="upload_photo">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<div class="sp-upload-zone" id="uploadZone">
  <i class="fas fa-cloud-upload-alt" style="font-size:28px;color:#94a3b8"></i>
  <p style="font-size:13px;color:#64748b;margin:4px 0">Drag & drop or click to upload</p>
  <p style="font-size:11px;color:#94a3b8">JPG, PNG, GIF, WebP &bull; Max 5MB</p>
  <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
</div>
<div id="uploadPreview" style="display:none;margin-top:8px;text-align:center">
  <img id="candidateImg" style="max-width:100%;max-height:120px;border-radius:8px;border:2px solid #e2e8f0">
  <p style="font-size:11px;color:#64748b;margin-top:4px">Click "Save Photo" to confirm</p>
</div>
<button type="submit" id="uploadBtn" class="sp-btn sp-btn-primary" style="width:100%;margin-top:8px" disabled><i class="fas fa-upload"></i>Save Photo</button>
</form>
</div>
<div class="sp-card">
<h4><i class="fas fa-user-edit me-2"></i>Edit Profile</h4>
<form method="POST">
<input type="hidden" name="action" value="update_profile">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<div class="sp-grid-2">
<div class="sp-form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>"></div>
<div class="sp-form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($email) ?>"></div>
</div>
<div class="sp-grid-2">
<div class="sp-form-group"><label>Guardian Name</label><input type="text" name="guardian_name" value="<?= htmlspecialchars($guardian_name) ?>"></div>
<div class="sp-form-group"><label>Guardian Phone</label><input type="text" name="guardian_phone" value="<?= htmlspecialchars($guardian_phone) ?>"></div>
</div>
<button type="submit" class="sp-btn sp-btn-primary"><i class="fas fa-save"></i>Save Changes</button>
</form>
</div>
</div>
<div class="sp-card">
<h4><i class="fas fa-info-circle me-2"></i>Personal Details</h4>
<div class="sp-grid-3">
<div class="sp-form-group"><label>Date of Birth</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($dob ?: 'Not set') ?></p></div>
<div class="sp-form-group"><label>Gender</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($gender) ?></p></div>
<div class="sp-form-group"><label>Nationality</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($student['nationality'] ?? '') ?></p></div>
<div class="sp-form-group"><label>District</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($student['district'] ?? '') ?></p></div>
<div class="sp-form-group"><label>Category</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($student['student_category'] ?? '') ?></p></div>
<div class="sp-form-group"><label>Intake Year</label><p style="padding:8px;background:#f8fafc;border-radius:6px"><?= htmlspecialchars($student['intake_year'] ?? '') ?></p></div>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 3. ACADEMIC RECORD
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'academics'):
    $records = safeQueryPrepared($studentsDb, "SELECT * FROM student_academic_records WHERE student_id=? ORDER BY academic_year DESC, semester DESC, subject ASC", "i", [$student_id]);
    $sem_gpa = safeQueryPrepared($studentsDb, "SELECT * FROM student_semester_gpa WHERE student_id=? ORDER BY academic_year DESC, semester DESC", "i", [$student_id]);
    $standing = null;
    if (!empty($sem_gpa)) $standing = $sem_gpa[0]['academic_standing'] ?? null;
?>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-book"></i></div><h3><?= count($records) ?></h3><p>Total Records</p></div>
<div class="sp-stat"><div class="icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check-circle"></i></div><h3><?= count(array_filter($records, fn($r) => (float)($r['marks']??0) >= 50)) ?></h3><p>Passed</p></div>
<div class="sp-stat"><div class="icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times-circle"></i></div><h3><?= count(array_filter($records, fn($r) => (float)($r['marks']??0) < 50 && (float)($r['marks']??0) > 0)) ?></h3><p>Failed</p></div>
<div class="sp-stat"><div class="icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-medal"></i></div><h3><?= $standing ?: 'N/A' ?></h3><p>Standing</p></div>
</div>
<?php if (!empty($sem_gpa)): ?>
<div class="sp-card">
<h4><i class="fas fa-chart-bar me-2"></i>Semester Performance</h4>
<table class="sp-table">
<thead><tr><th>Year</th><th>Semester</th><th>Credits</th><th>Sem GPA</th><th>Cum GPA</th><th>Standing</th></tr></thead>
<tbody>
<?php foreach ($sem_gpa as $sg): ?>
<tr>
<td><?= htmlspecialchars($sg['academic_year']) ?></td>
<td><?= htmlspecialchars($sg['semester']) ?></td>
<td><?= $sg['earned_credits'] ?? $sg['total_credits'] ?? 0 ?></td>
<td><strong><?= number_format($sg['semester_gpa'] ?? 0, 2) ?></strong></td>
<td><?= number_format($sg['cumulative_gpa'] ?? 0, 2) ?></td>
<td><span class="sp-badge sp-badge-<?= ($sg['academic_standing'] ?? '') === 'Good Standing' ? 'success' : 'warning' ?>"><?= htmlspecialchars($sg['academic_standing'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
<div class="sp-card">
<h4><i class="fas fa-list me-2"></i>Course Results</h4>
<?php if (empty($records)): ?>
<div class="sp-empty"><i class="fas fa-file-alt"></i><p>No academic records found</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Year</th><th>Semester</th><th>Subject</th><th>Code</th><th>Marks</th><th>Grade</th><th>Credits</th><th>Remarks</th></tr></thead>
<tbody>
<?php foreach ($records as $r): ?>
<tr>
<td><?= htmlspecialchars($r['academic_year'] ?? '') ?></td>
<td><?= htmlspecialchars($r['semester'] ?? '') ?></td>
<td><?= htmlspecialchars($r['subject'] ?? '') ?></td>
<td><?= htmlspecialchars($r['course_code'] ?? '') ?></td>
<td><strong><?= $r['marks'] ?? '' ?></strong></td>
<td><span class="sp-badge sp-badge-<?= (float)($r['marks']??0)>=50?'success':'danger' ?>"><?= htmlspecialchars($r['grade'] ?? '') ?></span></td>
<td><?= $r['credits'] ?? '' ?></td>
<td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 4. COURSE REGISTRATION
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'courses'):
    $registered = safeQueryPrepared($studentsDb, "SELECT scr.*, cc.course_code, cc.course_name, cc.credit_hours, cc.is_compulsory FROM student_course_registrations scr LEFT JOIN course_catalog cc ON scr.course_id=cc.id WHERE scr.student_id=? ORDER BY scr.academic_year DESC, scr.semester DESC", "i", [$student_id]);
    $available = safeQueryPrepared($studentsDb, "SELECT * FROM course_catalog WHERE program=? AND level=? AND status='Active' ORDER BY course_code", "ss", [$program, $level]);
    $prereqs = safeQuery($studentsDb, "SELECT * FROM course_prerequisites");
?>
<div class="sp-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h4 style="margin:0"><i class="fas fa-book me-2"></i>My Course Registrations</h4>
<button class="sp-btn sp-btn-primary" onclick="document.getElementById('regModal').style.display='flex'"><i class="fas fa-plus"></i>Register Courses</button>
</div>
<?php if (empty($registered)): ?>
<div class="sp-empty"><i class="fas fa-book"></i><p>No courses registered yet</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Code</th><th>Course Name</th><th>Credits</th><th>Semester</th><th>Year</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($registered as $cr): ?>
<tr>
<td><strong><?= htmlspecialchars($cr['course_code'] ?? '') ?></strong></td>
<td><?= htmlspecialchars($cr['course_name'] ?? '') ?></td>
<td><?= $cr['credit_hours'] ?? '' ?></td>
<td><?= htmlspecialchars($cr['semester'] ?? '') ?></td>
<td><?= htmlspecialchars($cr['academic_year'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($cr['status'] ?? '')==='Registered'?'info':(($cr['status'] ?? '')==='Completed'?'success':'secondary') ?>"><?= htmlspecialchars($cr['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div id="regModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
<div style="background:#fff;border-radius:12px;width:90%;max-width:600px;max-height:80vh;overflow-y:auto;padding:24px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h4 style="margin:0">Register Courses</h4>
<button class="btn-close" onclick="document.getElementById('regModal').style.display='none'" aria-label="Close"></button>
</div>
<?php if (empty($available)): ?>
<p class="text-muted">No courses available for your program/level.</p>
<?php else: ?>
<?php
$current_semester = $student['current_semester'] ?? 'Semester 1';
$current_academic_year = date('Y');
?>
<form method="POST" action="../includes/ajax_student_portal.php">
<input type="hidden" name="action" value="register_courses">
<input type="hidden" name="academic_year" value="<?= $current_academic_year ?>">
<input type="hidden" name="semester" value="<?= htmlspecialchars($current_semester) ?>">
<table class="sp-table">
<thead><tr><th><input type="checkbox" id="checkAll" onclick="document.querySelectorAll('.reg-check').forEach(c=>c.checked=this.checked)"></th><th>Code</th><th>Course</th><th>Credits</th><th>Type</th></tr></thead>
<tbody>
<?php foreach ($available as $c): ?>
<tr>
<td><input type="checkbox" name="course_ids[]" value="<?= $c['id'] ?>" class="reg-check"></td>
<td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
<td><?= htmlspecialchars($c['course_name']) ?></td>
<td><?= $c['credit_hours'] ?></td>
<td><span class="sp-badge sp-badge-<?= $c['is_compulsory']?'info':'secondary' ?>"><?= $c['is_compulsory']?'Compulsory':'Elective' ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div style="margin-top:16px;text-align:right">
<button type="submit" class="sp-btn sp-btn-primary"><i class="fas fa-check"></i>Register Selected</button>
</div>
</form>
<?php endif; ?>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 5. RESULTS & TRANSCRIPTS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'results'):
    $results = safeQueryPrepared($studentsDb, "SELECT * FROM student_academic_records WHERE student_id=? ORDER BY academic_year DESC, semester DESC", "i", [$student_id]);
    $transcripts = safeQueryPrepared($studentsDb, "SELECT * FROM registrar_transcript_requests WHERE student_id=? ORDER BY created_at DESC", "i", [$student_id]);
    $approvals = safeQueryPrepared($studentsDb, "SELECT ra.*, cc.course_code, cc.course_name FROM result_approvals ra LEFT JOIN course_catalog cc ON ra.course_id=cc.id WHERE ra.final_status='Published' ORDER BY ra.published_at DESC LIMIT 10", "", []);
?>
<div class="sp-tabs">
<a href="?page=results&tab=results" class="sp-tab <?= ($_GET['tab'] ?? 'results') === 'results' ? 'active' : '' ?>">Results</a>
<a href="?page=results&tab=transcripts" class="sp-tab <?= ($_GET['tab'] ?? '') === 'transcripts' ? 'active' : '' ?>">Transcripts</a>
<a href="?page=results&tab=published" class="sp-tab <?= ($_GET['tab'] ?? '') === 'published' ? 'active' : '' ?>">Published Results</a>
</div>
<?php $tab = $_GET['tab'] ?? 'results'; ?>
<?php if ($tab === 'results'): ?>
<div class="sp-card">
<h4><i class="fas fa-file-alt me-2"></i>My Results</h4>
<?php if (empty($results)): ?>
<div class="sp-empty"><i class="fas fa-file-alt"></i><p>No results available yet</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Year</th><th>Semester</th><th>Subject</th><th>Code</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Credits</th></tr></thead>
<tbody>
<?php foreach ($results as $r): ?>
<tr>
<td><?= htmlspecialchars($r['academic_year'] ?? '') ?></td>
<td><?= htmlspecialchars($r['semester'] ?? '') ?></td>
<td><?= htmlspecialchars($r['subject'] ?? '') ?></td>
<td><?= htmlspecialchars($r['course_code'] ?? '') ?></td>
<td><?= $r['ca_marks'] ?? $r['marks'] ?? '' ?></td>
<td><?= $r['exam_marks'] ?? '' ?></td>
<td><strong><?= $r['total_marks'] ?? $r['marks'] ?? '' ?></strong></td>
<td><span class="sp-badge sp-badge-<?= (float)($r['total_marks'] ?? $r['marks'] ?? 0)>=50?'success':'danger' ?>"><?= htmlspecialchars($r['grade'] ?? '') ?></span></td>
<td><?= $r['credits'] ?? '' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php elseif ($tab === 'transcripts'): ?>
<div class="sp-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h4 style="margin:0"><i class="fas fa-scroll me-2"></i>Transcript Requests</h4>
<a href="../includes/ajax_student_portal.php?action=request_transcript" class="sp-btn sp-btn-primary" onclick="return confirm('Request official transcript?')"><i class="fas fa-plus"></i>Request Transcript</a>
</div>
<?php if (empty($transcripts)): ?>
<div class="sp-empty"><i class="fas fa-scroll"></i><p>No transcript requests yet</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Number</th><th>Purpose</th><th>Status</th><th>Requested</th></tr></thead>
<tbody>
<?php foreach ($transcripts as $t): ?>
<tr>
<td><strong><?= htmlspecialchars($t['request_number'] ?? '') ?></strong></td>
<td><?= htmlspecialchars($t['purpose'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($t['status'] ?? '')==='Issued'?'success':(($t['status'] ?? '')==='Processing'?'warning':'info') ?>"><?= htmlspecialchars($t['status'] ?? '') ?></span></td>
<td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php else: ?>
<div class="sp-card">
<h4><i class="fas fa-check-double me-2"></i>Published Results</h4>
<?php if (empty($approvals)): ?>
<div class="sp-empty"><i class="fas fa-check-double"></i><p>No published results yet</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Course</th><th>Year</th><th>Semester</th><th>Published</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($approvals as $a): ?>
<tr>
<td><?= htmlspecialchars(($a['course_code'] ?? '').' - '.($a['course_name'] ?? '')) ?></td>
<td><?= htmlspecialchars($a['academic_year'] ?? '') ?></td>
<td><?= htmlspecialchars($a['semester'] ?? '') ?></td>
<td><?= $a['published_at'] ? date('M j, Y', strtotime($a['published_at'])) : '' ?></td>
<td><span class="sp-badge sp-badge-success">Published</span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php endif; ?>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 6. ATTENDANCE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'attendance'):
    $attendance = safeQueryPrepared($studentsDb, "SELECT * FROM student_attendance WHERE student_id=? ORDER BY date DESC, attendance_date DESC LIMIT 50", "i", [$student_id]);
    $clinical_att = safeQueryPrepared($studentsDb, "SELECT * FROM attendance_records ar JOIN class_sessions cs ON ar.session_id=cs.id WHERE ar.student_id=? AND cs.session_type='Clinical' ORDER BY cs.session_date DESC LIMIT 20", "i", [$student_id]);
    $total_classes = count($attendance);
    $present = count(array_filter($attendance, fn($a) => ($a['status'] ?? '') === 'Present'));
    $pct = $total_classes > 0 ? round($present * 100 / $total_classes, 1) : 0;
?>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-calendar-check"></i></div><h3><?= $total_classes ?></h3><p>Total Sessions</p></div>
<div class="sp-stat"><div class="icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check"></i></div><h3><?= $present ?></h3><p>Present</p></div>
<div class="sp-stat"><div class="icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times"></i></div><h3><?= $total_classes - $present ?></h3><p>Absent/Late</p></div>
<div class="sp-stat"><div class="icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-percentage"></i></div><h3><?= $pct ?>%</h3><p>Attendance Rate</p></div>
</div>
<div class="sp-grid-2">
<div class="sp-card">
<h4><i class="fas fa-calendar me-2"></i>Class Attendance</h4>
<?php if (empty($attendance)): ?>
<div class="sp-empty"><i class="fas fa-clipboard"></i><p>No attendance records</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Date</th><th>Subject</th><th>Status</th><th>Time</th></tr></thead>
<tbody>
<?php foreach ($attendance as $a): ?>
<tr>
<td><?= date('M j, Y', strtotime($a['date'] ?? $a['attendance_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($a['subject'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($a['status']==='Present')?'success':(($a['status']==='Late')?'warning':'danger') ?>"><?= htmlspecialchars($a['status'] ?? '') ?></span></td>
<td><?= htmlspecialchars($a['time_in'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="sp-card">
<h4><i class="fas fa-hospital me-2"></i>Clinical Attendance</h4>
<?php if (empty($clinical_att)): ?>
<div class="sp-empty"><i class="fas fa-hospital"></i><p>No clinical attendance records</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Date</th><th>Type</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($clinical_att as $ca): ?>
<tr>
<td><?= date('M j, Y', strtotime($ca['session_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($ca['session_type'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($ca['status']==='Present')?'success':'danger' ?>"><?= htmlspecialchars($ca['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 7. CLINICAL PLACEMENT
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'clinical'):
    $placements = safeQueryPrepared($studentsDb, "SELECT * FROM clinical_placements_students WHERE student_id=? ORDER BY start_date DESC", "i", [$student_id]);
    if (empty($placements)) $placements = safeQueryPrepared($studentsDb, "SELECT * FROM clinical_placements WHERE student_id=? ORDER BY start_date DESC", "i", [$student_id]);
    $evaluations = safeQueryPrepared($studentsDb, "SELECT * FROM clinical_evaluations WHERE student_id=? ORDER BY evaluation_date DESC", "i", [$student_id]);
?>
<div class="sp-card">
<h4><i class="fas fa-hospital me-2"></i>My Clinical Placements</h4>
<?php if (empty($placements)): ?>
<div class="sp-empty"><i class="fas fa-hospital"></i><p>No clinical placements assigned yet</p></div>
<?php else: ?>
<?php foreach ($placements as $p): ?>
<div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:12px">
<div style="display:flex;justify-content:space-between;align-items:start">
<div>
<h5 style="margin-bottom:4px"><?= htmlspecialchars($p['placement_site'] ?? $p['facility_name'] ?? '') ?></h5>
<p style="color:#64748b;font-size:.85rem"><?= htmlspecialchars($p['facility_location'] ?? '') ?></p>
</div>
<span class="sp-badge sp-badge-<?= ($p['status']==='Active')?'success':(($p['status']==='Completed')?'info':'warning') ?>"><?= htmlspecialchars($p['status'] ?? '') ?></span>
</div>
<div class="sp-grid-3" style="margin-top:12px;font-size:.85rem">
<div><strong>Supervisor:</strong><br><?= htmlspecialchars($p['supervisor_name'] ?? '') ?></div>
<div><strong>Period:</strong><br><?= date('M j', strtotime($p['start_date'] ?? '')) ?> - <?= date('M j, Y', strtotime($p['end_date'] ?? '')) ?></div>
<div><strong>Logbook:</strong><br><?= ($p['logbook_submitted'] ?? 0) ? '<span class="sp-badge sp-badge-success">Submitted</span>' : '<span class="sp-badge sp-badge-warning">Pending</span>' ?></div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<?php if (!empty($evaluations)): ?>
<div class="sp-card">
<h4><i class="fas fa-star me-2"></i>Supervisor Evaluations</h4>
<table class="sp-table">
<thead><tr><th>Date</th><th>Evaluator</th><th>Conduct</th><th>Skills</th><th>Communication</th><th>Overall</th></tr></thead>
<tbody>
<?php foreach ($evaluations as $e): ?>
<tr>
<td><?= date('M j, Y', strtotime($e['evaluation_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($e['evaluator_name'] ?? '') ?></td>
<td><?= $e['professional_conduct'] ?? '' ?></td>
<td><?= $e['clinical_skills'] ?? '' ?></td>
<td><?= $e['communication'] ?? '' ?></td>
<td><strong><?= $e['overall_rating'] ?? '' ?></strong></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 8. LOGBOOK
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'logbook'):
    $logbook = safeQueryPrepared($studentsDb, "SELECT * FROM student_logbook WHERE student_id=? ORDER BY entry_date DESC", "i", [$student_id]);
    $total_entries = count($logbook);
    $verified = count(array_filter($logbook, fn($l) => (int)($l['verified'] ?? 0) === 1));
    $total_patients = array_sum(array_map(fn($l) => (int)($l['patients_seen'] ?? 0), $logbook));
?>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-book-open"></i></div><h3><?= $total_entries ?></h3><p>Total Entries</p></div>
<div class="sp-stat"><div class="icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check-circle"></i></div><h3><?= $verified ?></h3><p>Verified</p></div>
<div class="sp-stat"><div class="icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-users"></i></div><h3><?= $total_patients ?></h3><p>Patients Seen</p></div>
</div>
<div class="sp-card">
<h4><i class="fas fa-book-open me-2"></i>Clinical Logbook</h4>
<?php if (empty($logbook)): ?>
<div class="sp-empty"><i class="fas fa-book-open"></i><p>No logbook entries yet</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Date</th><th>Ward/Unit</th><th>Shift</th><th>Procedures</th><th>Patients</th><th>Supervisor</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($logbook as $l): ?>
<tr>
<td><?= date('M j, Y', strtotime($l['entry_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($l['ward_unit'] ?? '') ?></td>
<td><?= htmlspecialchars($l['shift'] ?? '') ?></td>
<td style="max-width:200px;font-size:.8rem"><?= htmlspecialchars($l['procedures_performed'] ?? '') ?></td>
<td><?= $l['patients_seen'] ?? 0 ?></td>
<td><?= htmlspecialchars($l['supervisor_name'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($l['verified'] ?? 0) ? 'success' : 'warning' ?>"><?= ($l['verified'] ?? 0) ? 'Verified' : 'Pending' ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 9. COMPETENCIES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'competency'):
    $competencies = safeQueryPrepared($studentsDb, "SELECT * FROM student_competencies WHERE student_id=? ORDER BY skill_category, skill_name", "i", [$student_id]);
    $grouped = [];
    foreach ($competencies as $c) { $grouped[$c['skill_category'] ?? 'Other'][] = $c; }
?>
<div class="sp-card">
<h4><i class="fas fa-star me-2"></i>Skills Competency Tracker</h4>
<?php if (empty($competencies)): ?>
<div class="sp-empty"><i class="fas fa-star"></i><p>No competency records yet</p></div>
<?php else: ?>
<?php foreach ($grouped as $cat => $skills): ?>
<h5 style="margin:16px 0 8px;color:#3b82f6"><?= htmlspecialchars($cat) ?></h5>
<?php foreach ($skills as $s): ?>
<div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f1f5f9">
<div style="flex:1">
<strong style="font-size:.85rem"><?= htmlspecialchars($s['skill_name']) ?></strong>
<p style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($s['competency_level'] ?? '') ?></p>
</div>
<div style="width:120px">
<div class="sp-progress"><div class="sp-progress-bar" style="width:<?= $s['score'] ?? 0 ?>%"></div></div>
<small><?= $s['score'] ?? 0 ?>/<?= $s['max_score'] ?? 100 ?></small>
</div>
<span class="sp-badge sp-badge-<?= ($s['score'] ?? 0) >= 80 ? 'success' : (($s['score'] ?? 0) >= 60 ? 'warning' : 'danger') ?>"><?= $s['competency_level'] ?? '' ?></span>
</div>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 10. DISCIPLINE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'discipline'):
    $cases = safeQueryPrepared($studentsDb, "SELECT * FROM student_discipline WHERE student_id=? ORDER BY incident_date DESC", "s", [$student_number]);
    if (empty($cases)) $cases = safeQueryPrepared($studentsDb, "SELECT * FROM student_discipline WHERE CAST(student_id AS UNSIGNED)=? ORDER BY incident_date DESC", "i", [$student_id]);
    $warnings = safeQueryPrepared($studentsDb, "SELECT * FROM student_warnings WHERE student_id=? ORDER BY warning_date DESC", "i", [$student_id]);
?>
<div class="sp-grid-2">
<div class="sp-card">
<h4><i class="fas fa-gavel me-2"></i>Disciplinary Records</h4>
<?php if (empty($cases)): ?>
<div class="sp-empty"><i class="fas fa-gavel"></i><p>No disciplinary records</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Action</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($cases as $c): ?>
<tr>
<td><?= date('M j, Y', strtotime($c['incident_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($c['incident_type'] ?? '') ?></td>
<td style="max-width:200px;font-size:.8rem"><?= htmlspecialchars($c['description'] ?? '') ?></td>
<td><?= htmlspecialchars($c['action_taken'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($c['status'] ?? '')==='Resolved'?'success':'warning' ?>"><?= htmlspecialchars($c['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="sp-card">
<h4><i class="fas fa-exclamation-triangle me-2"></i>Warnings</h4>
<?php if (empty($warnings)): ?>
<div class="sp-empty"><i class="fas fa-check-circle"></i><p>No warnings on record</p></div>
<?php else: ?>
<?php foreach ($warnings as $w): ?>
<div style="border-left:3px solid <?= ($w['severity'] ?? '')==='Expulsion'?'#dc2626':(($w['severity'] ?? '')==='Suspension'?'#f59e0b':(($w['severity'] ?? '')==='Final'?'#f97316':'#3b82f6')) ?>;padding:12px;margin-bottom:12px;background:#f8fafc;border-radius:0 8px 8px 0">
<div style="display:flex;justify-content:space-between">
<strong style="font-size:.85rem"><?= htmlspecialchars($w['title'] ?? '') ?></strong>
<span class="sp-badge sp-badge-<?= ($w['status'] ?? '')==='Active'?'danger':'secondary' ?>"><?= htmlspecialchars($w['status'] ?? '') ?></span>
</div>
<p style="font-size:.8rem;color:#64748b;margin:4px 0"><?= htmlspecialchars($w['description'] ?? '') ?></p>
<small style="color:#94a3b8"><?= htmlspecialchars($w['severity'] ?? '') ?> Â· <?= date('M j, Y', strtotime($w['warning_date'] ?? '')) ?> Â· By: <?= htmlspecialchars($w['issued_by_name'] ?? '') ?></small>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 11. FINANCES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'finances'):
    $fees = safeQueryPrepared($studentsDb, "SELECT * FROM student_fee_tracking WHERE student_id=? ORDER BY due_date ASC", "i", [$student_id]);
    if (empty($fees)) $fees = safeQueryPrepared($studentsDb, "SELECT * FROM student_fees WHERE student_id=? ORDER BY due_date ASC", "i", [$student_id]);
    $payments = safeQueryPrepared($studentsDb, "SELECT * FROM payments WHERE student_id=? ORDER BY payment_date DESC LIMIT 20", "i", [$student_id]);
    if (empty($payments)) $payments = safeQueryPrepared($studentsDb, "SELECT * FROM fee_payments WHERE student_id=? ORDER BY payment_date DESC LIMIT 20", "i", [$student_id]);
    $total_fees = array_sum(array_map(fn($f) => (float)($f['amount'] ?? 0), $fees));
    $total_paid = array_sum(array_map(fn($f) => (float)($f['amount_paid'] ?? 0), $fees));
    $balance = $total_fees - $total_paid;
?>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-file-invoice-dollar"></i></div><h3>UGX <?= number_format($total_fees) ?></h3><p>Total Fees</p></div>
<div class="sp-stat"><div class="icon" style="background:#d1fae5;color:#059669"><i class="fas fa-check-circle"></i></div><h3>UGX <?= number_format($total_paid) ?></h3><p>Amount Paid</p></div>
<div class="sp-stat"><div class="icon" style="background:<?= $balance > 0 ? '#fee2e2' : '#d1fae5' ?>;color:<?= $balance > 0 ? '#dc2626' : '#059669' ?>"><i class="fas fa-balance-scale"></i></div><h3>UGX <?= number_format($balance) ?></h3><p>Balance</p></div>
</div>
<div class="sp-grid-2">
<div class="sp-card">
<h4><i class="fas fa-file-invoice me-2"></i>Fee Details</h4>
<?php if (empty($fees)): ?>
<div class="sp-empty"><i class="fas fa-file-invoice"></i><p>No fee records</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Type</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($fees as $f): ?>
<tr>
<td><?= htmlspecialchars($f['fee_type'] ?? '') ?></td>
<td>UGX <?= number_format($f['amount'] ?? 0) ?></td>
<td>UGX <?= number_format($f['amount_paid'] ?? 0) ?></td>
<td><strong>UGX <?= number_format($f['balance'] ?? ($f['amount'] ?? 0) - ($f['amount_paid'] ?? 0)) ?></strong></td>
<td><?= $f['due_date'] ? date('M j', strtotime($f['due_date'])) : '' ?></td>
<td><span class="sp-badge sp-badge-<?= ($f['status'] ?? '') === 'Paid' ? 'success' : (($f['status'] ?? '') === 'Pending' ? 'warning' : 'info') ?>"><?= htmlspecialchars($f['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="sp-card">
<h4><i class="fas fa-receipt me-2"></i>Payment History</h4>
<?php if (empty($payments)): ?>
<div class="sp-empty"><i class="fas fa-receipt"></i><p>No payments recorded</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Date</th><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($payments as $p): ?>
<tr>
<td><?= date('M j, Y', strtotime($p['payment_date'] ?? '')) ?></td>
<td><?= htmlspecialchars($p['payment_reference'] ?? $p['transaction_ref'] ?? '') ?></td>
<td><strong>UGX <?= number_format($p['amount'] ?? $p['amount_received'] ?? 0) ?></strong></td>
<td><?= htmlspecialchars($p['payment_method'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($p['status'] ?? '')==='Completed'?'success':'warning' ?>"><?= htmlspecialchars($p['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 12. HOSTEL
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'hostel'):
    $allocations = safeQueryPrepared($studentsDb, "SELECT ha.*, hr.room_number, hr.hostel_name FROM hostel_allocations ha LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id WHERE ha.student_id=? ORDER BY ha.check_in_date DESC", "s", [$student_number]);
    if (empty($allocations)) $allocations = safeQueryPrepared($studentsDb, "SELECT ha.*, hr.room_number, hr.hostel_name FROM hostel_allocations ha LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id WHERE ha.student_id=? ORDER BY ha.check_in_date DESC", "i", [$student_id]);
?>
<div class="sp-card">
<h4><i class="fas fa-home me-2"></i>Hostel Allocation</h4>
<?php if (empty($allocations)): ?>
<div class="sp-empty"><i class="fas fa-home"></i><p>No hostel allocation records</p></div>
<?php else: ?>
<?php foreach ($allocations as $h): ?>
<div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:12px">
<div style="display:flex;justify-content:space-between">
<div>
<h5><?= htmlspecialchars($h['hostel_name'] ?? '') ?></h5>
<p style="color:#64748b;font-size:.85rem">Room <?= htmlspecialchars($h['room_number'] ?? '') ?></p>
</div>
<span class="sp-badge sp-badge-<?= ($h['status'] ?? '')==='Active'?'success':'secondary' ?>"><?= htmlspecialchars($h['status'] ?? '') ?></span>
</div>
<div style="margin-top:8px;font-size:.85rem">
<span>Check-in: <?= $h['check_in_date'] ?? $h['allocation_date'] ? date('M j, Y', strtotime($h['check_in_date'] ?? $h['allocation_date'])) : '' ?></span>
<?php if (!empty($h['check_out_date'])): ?>
<span> Â· Check-out: <?= date('M j, Y', strtotime($h['check_out_date'])) ?></span>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 13. LIBRARY
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'library'):
    $borrowing = safeQueryPrepared($studentsDb, "SELECT lb.*, lbb.book_title, lbb.author FROM library_borrowing lb LEFT JOIN library_books lbb ON lb.book_id=lbb.id WHERE lb.student_id=? ORDER BY lb.borrow_date DESC", "s", [$student_number]);
    if (empty($borrowing)) {
        $borrowing = safeQueryPrepared($studentsDb, "SELECT lb.*, lbb.book_title, lbb.author FROM library_borrowing lb LEFT JOIN library_books lbb ON lb.book_id=lbb.id WHERE CAST(lb.student_id AS UNSIGNED)=? ORDER BY lb.borrow_date DESC", "i", [$student_id]);
    }
    $fines = safeQueryPrepared($studentsDb, "SELECT * FROM library_fines WHERE student_id = ?", "s", [$student_number]);
    if (empty($fines)) {
        $fines = safeQueryPrepared($studentsDb, "SELECT * FROM library_fines WHERE CAST(student_id AS UNSIGNED) = ?", "i", [$student_id]);
    }
    $total_fines = array_sum(array_map(fn($f) => (float)($f['amount'] ?? 0), $fines));
    $unpaid_fines = array_sum(array_filter(array_map(fn($f) => (float)($f['amount'] ?? 0), $fines), fn($f) => $f > 0));
?>
<div class="sp-stats">
<div class="sp-stat"><div class="icon" style="background:#dbeafe;color:#3b82f6"><i class="fas fa-book"></i></div><h3><?= count($borrowing) ?></h3><p>Books Borrowed</p></div>
<div class="sp-stat"><div class="icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-exclamation-circle"></i></div><h3>UGX <?= number_format($total_fines) ?></h3><p>Total Fines</p></div>
</div>
<div class="sp-grid-2">
<div class="sp-card">
<h4><i class="fas fa-book-reader me-2"></i>Borrowing History</h4>
<?php if (empty($borrowing)): ?>
<div class="sp-empty"><i class="fas fa-book"></i><p>No books borrowed</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($borrowing as $b): ?>
<tr>
<td><strong><?= htmlspecialchars($b['book_title'] ?? '') ?></strong><br><small style="color:#64748b"><?= htmlspecialchars($b['author'] ?? '') ?></small></td>
<td><?= date('M j', strtotime($b['borrow_date'] ?? '')) ?></td>
<td><?= date('M j, Y', strtotime($b['due_date'] ?? '')) ?></td>
<td><?= $b['return_date'] ? date('M j', strtotime($b['return_date'])) : '-' ?></td>
<td><span class="sp-badge sp-badge-<?= ($b['status'] ?? '')==='Returned'?'success':(($b['status'] ?? '')==='Overdue'?'danger':'info') ?>"><?= htmlspecialchars($b['status'] ?? '') ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div class="sp-card">
<h4><i class="fas fa-coins me-2"></i>Fines</h4>
<?php if (empty($fines)): ?>
<div class="sp-empty"><i class="fas fa-check-circle"></i><p>No fines on record</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Amount</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($fines as $f): ?>
<tr>
<td><strong>UGX <?= number_format($f['amount'] ?? 0) ?></strong></td>
<td><?= htmlspecialchars($f['reason'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($f['status'] ?? '')==='Paid'?'success':'danger' ?>"><?= htmlspecialchars($f['status'] ?? '') ?></span></td>
<td><?= date('M j, Y', strtotime($f['created_at'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 14. ANNOUNCEMENTS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'notices'):
    $notices = safeQuery($studentsDb, "SELECT * FROM announcements WHERE is_active=1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY FIELD(priority,'Urgent','High','Normal','Low'), created_at DESC LIMIT 20");
    if (empty($notices)) $notices = safeQueryPrepared($studentsDb, "SELECT * FROM student_notifications WHERE student_id=? ORDER BY created_at DESC LIMIT 20", "i", [$student_id]);
?>
<div class="sp-card">
<h4><i class="fas fa-bullhorn me-2"></i>Announcements & Notices</h4>
<?php if (empty($notices)): ?>
<div class="sp-empty"><i class="fas fa-bullhorn"></i><p>No announcements at this time</p></div>
<?php else: ?>
<?php foreach ($notices as $n): ?>
<div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:12px;border-left:4px solid <?= ($n['priority'] ?? '')==='Urgent'?'#dc2626':(($n['priority'] ?? '')==='High'?'#f59e0b':'#3b82f6') ?>">
<div style="display:flex;justify-content:space-between;align-items:start">
<h5 style="margin-bottom:4px"><?= htmlspecialchars($n['title'] ?? $n['subject'] ?? '') ?></h5>
<div>
<?php if (!empty($n['type'])): ?>
<span class="sp-badge sp-badge-info"><?= htmlspecialchars($n['type']) ?></span>
<?php endif; ?>
<?php if (!empty($n['priority'])): ?>
<span class="sp-badge sp-badge-<?= ($n['priority']==='Urgent')?'danger':(($n['priority']==='High')?'warning':'secondary') ?>"><?= htmlspecialchars($n['priority']) ?></span>
<?php endif; ?>
</div>
</div>
<p style="font-size:.85rem;color:#475569;margin:8px 0"><?= nl2br(htmlspecialchars($n['content'] ?? $n['message'] ?? '')) ?></p>
<small style="color:#94a3b8"><?= date('M j, Y', strtotime($n['created_at'] ?? '')) ?></small>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 15. MESSAGES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'messages'):
    $messages = safeQueryPrepared($studentsDb, "SELECT * FROM messages WHERE student_id=? ORDER BY created_at DESC LIMIT 30", "s", [$student_number]);
    if (empty($messages)) $messages = safeQueryPrepared($studentsDb, "SELECT * FROM student_messages WHERE student_id=? ORDER BY created_at DESC LIMIT 30", "i", [$student_id]);
?>
<div class="sp-card">
<h4><i class="fas fa-envelope me-2"></i>Messages</h4>
<?php if (empty($messages)): ?>
<div class="sp-empty"><i class="fas fa-envelope"></i><p>No messages</p></div>
<?php else: ?>
<?php foreach ($messages as $m): ?>
<div style="border-bottom:1px solid #f1f5f9;padding:12px 0;<?= ($m['is_read'] ?? 1) ? '' : 'background:#f0f9ff;margin:0 -24px;padding:12px 24px;border-left:3px solid #3b82f6' ?>">
<div style="display:flex;justify-content:space-between">
<strong style="font-size:.85rem"><?= htmlspecialchars($m['subject'] ?? '') ?></strong>
<small style="color:#94a3b8"><?= date('M j', strtotime($m['created_at'] ?? $m['sent_date'] ?? '')) ?></small>
</div>
<p style="font-size:.8rem;color:#64748b;margin:4px 0"><?= htmlspecialchars(substr($m['message'] ?? $m['message_content'] ?? '', 0, 150)) ?>...</p>
<small style="color:#94a3b8">From: <?= htmlspecialchars($m['sender_role'] ?? 'System') ?></small>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 16. REQUESTS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'requests'):
    $requests = safeQueryPrepared($studentsDb, "SELECT * FROM student_requests WHERE student_id=? ORDER BY created_at DESC", "i", [$student_id]);
?>
<div class="sp-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h4 style="margin:0"><i class="fas fa-paper-plane me-2"></i>My Requests</h4>
<button class="sp-btn sp-btn-primary" onclick="document.getElementById('reqModal').style.display='flex'"><i class="fas fa-plus"></i>New Request</button>
</div>
<?php if (empty($requests)): ?>
<div class="sp-empty"><i class="fas fa-paper-plane"></i><p>No requests submitted</p></div>
<?php else: ?>
<table class="sp-table">
<thead><tr><th>Type</th><th>Reason</th><th>Status</th><th>Review Notes</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($requests as $r): ?>
<tr>
<td><strong><?= htmlspecialchars($r['request_type'] ?? '') ?></strong></td>
<td style="max-width:200px;font-size:.8rem"><?= htmlspecialchars($r['reason'] ?? '') ?></td>
<td><span class="sp-badge sp-badge-<?= ($r['status'] ?? '')==='Approved'?'success':(($r['status'] ?? '')==='Rejected'?'danger':'warning') ?>"><?= htmlspecialchars($r['status'] ?? '') ?></span></td>
<td style="font-size:.8rem"><?= htmlspecialchars($r['review_notes'] ?? '') ?></td>
<td><?= date('M j, Y', strtotime($r['created_at'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<div id="reqModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
<div style="background:#fff;border-radius:12px;width:90%;max-width:500px;padding:24px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
<h4 style="margin:0">Submit Request</h4>
<button class="btn-close" onclick="document.getElementById('reqModal').style.display='none'" aria-label="Close"></button>
</div>
<form method="POST">
<input type="hidden" name="action" value="submit_request">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<div class="sp-form-group"><label>Request Type</label>
<select name="request_type" required>
<option value="">Select type...</option>
<option value="Leave of Absence">Leave of Absence</option>
<option value="Deferral">Deferral</option>
<option value="Transfer">Transfer</option>
<option value="Withdrawal">Withdrawal</option>
<option value="Other">Other</option>
</select></div>
<div class="sp-form-group"><label>Reason / Details</label><textarea name="reason" rows="4" required placeholder="Describe your request..."></textarea></div>
<div style="text-align:right"><button type="submit" class="sp-btn sp-btn-primary"><i class="fas fa-paper-plane"></i>Submit</button></div>
</form>
</div>
</div>

<?php
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// 17. TIMETABLE
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
elseif ($page === 'timetable'):
    $timetable = safeQueryPrepared($studentsDb, "SELECT * FROM student_timetables WHERE student_id=? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time", "i", [$student_id]);
    if (empty($timetable)) $timetable = safeQueryPrepared($studentsDb, "SELECT * FROM timetable WHERE program=? AND year_of_study=? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), time_slot", "si", [$program, $current_year]);
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $byDay = [];
    foreach ($timetable as $t) { $byDay[$t['day_of_week']][] = $t; }
?>
<div class="sp-card">
<h4><i class="fas fa-calendar-alt me-2"></i>Weekly Timetable</h4>
<?php if (empty($timetable)): ?>
<div class="sp-empty"><i class="fas fa-calendar-alt"></i><p>No timetable available</p></div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="sp-table">
<thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Lecturer</th><th>Room</th></tr></thead>
<tbody>
<?php foreach ($days as $day):
    if (empty($byDay[$day])) continue;
    foreach ($byDay[$day] as $i => $t): ?>
<tr>
<?php if ($i === 0): ?>
<td rowspan="<?= count($byDay[$day]) ?>" style="vertical-align:middle;font-weight:600;color:#3b82f6;"><?= htmlspecialchars($day) ?></td>
<?php endif; ?>
<td><?= htmlspecialchars($t['start_time'] ?? $t['time_slot'] ?? '') ?> - <?= htmlspecialchars($t['end_time'] ?? '') ?></td>
<td><strong><?= htmlspecialchars($t['course_name'] ?? $t['subject'] ?? '') ?></strong><br><small style="color:#64748b"><?= htmlspecialchars($t['course_code'] ?? '') ?></small></td>
<td><?= htmlspecialchars($t['lecturer'] ?? '') ?></td>
<td><?= htmlspecialchars($t['location'] ?? $t['room'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

<?php elseif ($page === 'password'): ?>
<div class="sp-card" style="max-width:500px;margin:0 auto">
<h4><i class="fas fa-key me-2"></i>Change Password</h4>
<form method="POST" action="?page=password">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? $_SESSION['full_name'] ?? '') ?>" autocomplete="username" style="display:none;">
<div class="sp-form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
<div class="sp-form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" autocomplete="new-password" required minlength="6"></div>
<div class="sp-form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control" autocomplete="new-password" required minlength="6"></div>
<button type="submit" name="action" value="change_password" class="sp-btn sp-btn-primary"><i class="fas fa-save me-1"></i>Update Password</button>
</form>
</div>
<?php endif; ?>
</main>
<script>
// Profile photo upload preview + drag-drop
(function() {
  var zone = document.getElementById('uploadZone');
  var input = document.getElementById('profilePhotoInput');
  var preview = document.getElementById('uploadPreview');
  var candidateImg = document.getElementById('candidateImg');
  var uploadBtn = document.getElementById('uploadBtn');
  var currentImg = document.querySelector('.sp-photo-wrapper img');

  if (!zone || !input) return;

  // Drag events
  zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); });
  zone.addEventListener('drop', function(e) {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
      input.files = e.dataTransfer.files;
      handleFile(e.dataTransfer.files[0]);
    }
  });

  // Click to select
  input.addEventListener('change', function() {
    if (this.files.length > 0) handleFile(this.files[0]);
  });

  function handleFile(file) {
    if (!file.type.match('image.*')) {
      alert('Please select an image file (JPG, PNG, GIF, or WebP).');
      input.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert('File too large. Maximum size is 5MB.');
      input.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
      candidateImg.src = e.target.result;
      preview.style.display = 'block';
      zone.classList.add('has-file');
      uploadBtn.disabled = false;
    };
    reader.readAsDataURL(file);
  }
})();
</script>
</body>
</html>
