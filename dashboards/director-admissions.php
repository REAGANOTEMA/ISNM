<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['admissions', 'director']);
$conn = $ctx['staff'];
$students_conn = $ctx['students'] ?? null;
$website_conn = $ctx['website'];
$user = $ctx['user'];
$user_id = (int)($user['id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$user_name = $user['full_name'] ?? 'Director Admissions';

function sc($c, $s) { $r=$c->query($s); if(!$r)return 0; $w=$r->fetch_assoc(); return intval($w['c']??0); }
function logAdmission($conn, $uid, $action, $module, $rid, $desc) {
    $a = $conn->real_escape_string($action);
    $m = $conn->real_escape_string($module);
    $d = $conn->real_escape_string($desc);
    $conn->query("INSERT INTO admission_activity_logs (user_id,action,module,record_id,description) VALUES ($uid,'$a','$m',".intval($rid).",'$d')");
}

// Stats
$total_applicants    = sc($conn, "SELECT COUNT(*)c FROM applicants");
$new_applicants      = sc($conn, "SELECT COUNT(*)c FROM applicants WHERE status='New Applicant'");
$approved_applicants = sc($conn, "SELECT COUNT(*)c FROM applicants WHERE status='Approved'");
$pending_verify      = sc($conn, "SELECT COUNT(*)c FROM applicants WHERE status='Under Review'");
$cleared_count       = sc($conn, "SELECT COUNT(*)c FROM applicants WHERE status='Registered'");
$missing_req_count   = 0;
$total_reqs = sc($conn, "SELECT COUNT(*)c FROM admission_requirements WHERE is_active=1");
$total_apps_ws       = $website_conn ? sc($website_conn, "SELECT COUNT(*)c FROM student_applications") : 0;

// Count applicants with missing requirements
$mr = $conn->query("SELECT COUNT(DISTINCT a.id)c FROM applicants a LEFT JOIN applicant_requirement_status ars ON a.id=ars.applicant_id AND ars.status='Verified' GROUP BY a.id HAVING COUNT(ars.id) < $total_reqs");
if ($mr) $missing_req_count = $mr->num_rows;

// Load data
$applicants_list = []; $r=$conn->query("SELECT a.*,COALESCE((SELECT COUNT(*) FROM applicant_requirement_status WHERE applicant_id=a.id AND status='Verified'),0) verified_count,$total_reqs total_reqs FROM applicants a ORDER BY a.created_at DESC LIMIT 100"); if($r) while($row=$r->fetch_assoc()) $applicants_list[]=$row;
$programs_list = []; $r=$conn->query("SELECT program_code,program_name,program_type,department,duration_years,status FROM academic_programs WHERE status='Active' ORDER BY program_name"); if($r) while($row=$r->fetch_assoc()) $programs_list[]=$row;
$req_items = []; $r=$conn->query("SELECT * FROM admission_requirements WHERE is_active=1 ORDER BY display_order"); if($r) while($row=$r->fetch_assoc()) $req_items[]=$row;
$total_req_items = count($req_items);
$recent_activity = []; $r=$conn->query("SELECT al.*,s.full_name performer_name FROM admission_activity_logs al LEFT JOIN staff s ON al.user_id=s.id ORDER BY al.created_at DESC LIMIT 20"); if($r) while($row=$r->fetch_assoc()) $recent_activity[]=$row;

// Intake stats for chart
$intake_stats = []; $r=$conn->query("SELECT intake,COUNT(*)c FROM applicants WHERE intake IS NOT NULL AND intake!='' GROUP BY intake ORDER BY intake"); if($r) while($row=$r->fetch_assoc()) $intake_stats[]=$row;

// Clearance progress for chart
$clearance_levels = ['Not Submitted'=>0,'Submitted'=>0,'Verified'=>0,'Rejected'=>0,'Missing'=>0];
$r=$conn->query("SELECT status,COUNT(*)c FROM applicant_requirement_status GROUP BY status"); if($r) while($row=$r->fetch_assoc()) $clearance_levels[$row['status']] = intval($row['c']);

// Reports
$report = $_GET['report'] ?? '';
if ($report) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ddd;padding:6px 8px}th{background:#f3f4f6}h2{color:#1f2937}@media print{body{print-color-adjust:exact}.no-print{display:none}}</style></head><body>';
    echo '<div class="no-print"><button onclick="window.print()" style="padding:6px 16px;margin-bottom:12px">Print</button> <button onclick="window.close()" style="padding:6px 16px">Close</button></div>';
    if ($report === 'applications') {
        echo '<h2>Applicants Report</h2>';
        $r=$conn->query("SELECT application_number,full_name,COALESCE((SELECT program_name FROM academic_programs WHERE id=a.program_id),'N/A') program,intake,status,created_at FROM applicants a ORDER BY created_at DESC");
        echo '<table><thead><tr><th>App No</th><th>Applicant</th><th>Program</th><th>Intake</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['application_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['program']).'</td><td>'.htmlspecialchars($row['intake']??'-').'</td><td>'.$row['created_at'].'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'cleared') {
        echo '<h2>Fully Cleared Applicants</h2>';
        $r=$conn->query("SELECT a.*,COALESCE((SELECT COUNT(*) FROM applicant_requirement_status WHERE applicant_id=a.id AND status='Verified'),0) vc FROM applicants a HAVING vc>=$total_req_items ORDER BY a.full_name");
        echo '<table><thead><tr><th>App No</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['application_number']).'</td><td>'.htmlspecialchars($row['full_name']).'</td><td>'.htmlspecialchars($row['phone']??'-').'</td><td>'.$row['status'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'clearance') {
        echo '<h2>Requirements Clearance Report</h2>';
        $r=$conn->query("SELECT ars.*,a.full_name applicant_name,adr.requirement_name FROM applicant_requirement_status ars LEFT JOIN applicants a ON ars.applicant_id=a.id LEFT JOIN admission_requirements adr ON ars.requirement_id=adr.id ORDER BY ars.applicant_id,adr.display_order");
        echo '<table><thead><tr><th>Applicant</th><th>Requirement</th><th>Status</th><th>Verified By</th><th>Date</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['applicant_name']??$row['applicant_id']).'</td><td>'.htmlspecialchars($row['requirement_name']??'-').'</td><td>'.$row['status'].'</td><td>'.$row['verified_by'].'</td><td>'.$row['verified_at'].'</td></tr>'; }
        echo '</tbody></table>';
    } elseif ($report === 'intake') {
        echo '<h2>Intake Report</h2>';
        $r=$conn->query("SELECT intake,COUNT(*) total FROM applicants WHERE intake IS NOT NULL AND intake!='' GROUP BY intake ORDER BY intake");
        echo '<table><thead><tr><th>Intake</th><th>Applicants</th></tr></thead><tbody>';
        if($r) while($row=$r->fetch_assoc()){ echo '<tr><td>'.htmlspecialchars($row['intake']).'</td><td>'.$row['total'].'</td></tr>'; }
        echo '</tbody></table>';
    }
    echo '</body></html>'; exit;
}

// AJAX handlers
$ajax = $_GET['ajax'] ?? '';
if ($ajax === 'search_applicants') {
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit; }
    $sq = $conn->real_escape_string($q);
    $r = $conn->query("SELECT id,full_name,application_number,phone,COALESCE((SELECT program_name FROM academic_programs WHERE id=a.program_id),'N/A') program,status FROM applicants a WHERE full_name LIKE '%$sq%' OR application_number LIKE '%$sq%' OR phone LIKE '%$sq%' ORDER BY full_name LIMIT 30");
    $out = [];
    if ($r) { while($row = $r->fetch_assoc()) $out[] = $row; }
    echo json_encode($out); exit;
}
if ($ajax === 'get_requirements') {
    header('Content-Type: application/json');
    $aid = intval($_GET['applicant_id'] ?? 0);
    if (!$aid) { echo json_encode([]); exit; }
    $info = []; $rn = $conn->query("SELECT * FROM applicants WHERE id=$aid"); if ($rn) $info = $rn->fetch_assoc() ?: [];
    $req = []; $rr = $conn->query("SELECT ars.*, adr.requirement_name, adr.display_order FROM applicant_requirement_status ars RIGHT JOIN admission_requirements adr ON ars.requirement_id=adr.id AND ars.applicant_id=$aid WHERE adr.is_active=1 ORDER BY adr.display_order"); if ($rr) { while($row=$rr->fetch_assoc()) $req[] = $row; }
    $hist = []; $rh = $conn->query("SELECT rh.*, s.full_name performed_by_name FROM requirement_history rh LEFT JOIN staff s ON rh.performed_by=s.id WHERE rh.applicant_id=$aid ORDER BY rh.created_at DESC LIMIT 50"); if ($rh) { while($row=$rh->fetch_assoc()) $hist[] = $row; }
    echo json_encode(['info'=>$info,'requirements'=>$req,'history'=>$hist]); exit;
}
if ($ajax === 'toggle_requirement') {
    header('Content-Type: application/json');
    $aid = intval($_POST['applicant_id'] ?? 0);
    $rid = intval($_POST['requirement_id'] ?? 0);
    $new_status = $conn->real_escape_string(trim($_POST['status'] ?? 'Not Submitted'));
    $remarks = $conn->real_escape_string(trim($_POST['remarks'] ?? ''));
    $valid_statuses = ['Not Submitted','Submitted','Verified','Rejected','Missing'];
    if (!in_array($new_status, $valid_statuses)) { echo json_encode(['success'=>false,'error'=>'Invalid status']); exit; }
    if ($aid && $rid) {
        // Check current status for history
        $cr = $conn->query("SELECT status FROM applicant_requirement_status WHERE applicant_id=$aid AND requirement_id=$rid");
        $old_status = ($cr && $cr->num_rows) ? $cr->fetch_assoc()['status'] : 'Not Submitted';
        // Map actions
        $action_map = ['Submitted'=>'Submitted','Verified'=>'Verified','Rejected'=>'Rejected'];
        $action = $action_map[$new_status] ?? 'Updated';
        if ($old_status === $new_status && $new_status === 'Not Submitted') $action = 'Updated';
        // Upsert
        $sub_by = ($new_status === 'Submitted') ? ",submitted_by=$user_id,submitted_at=NOW()" : '';
        $ver_by = ($new_status === 'Verified') ? ",verified_by=$user_id,verified_at=NOW()" : '';
        $rej_by = ($new_status === 'Rejected') ? ",rejected_by=$user_id" : '';
        $remarks_sql = $remarks ? ",remarks='$remarks'" : '';
        $conn->query("INSERT INTO applicant_requirement_status (applicant_id,requirement_id,status,submitted_by,submitted_at,verified_by,verified_at,rejected_by,remarks) VALUES ($aid,$rid,'$new_status',$user_id,NOW(),NULL,NULL,NULL,'$remarks') ON DUPLICATE KEY UPDATE status='$new_status'$sub_by$ver_by$rej_by$remarks_sql");
        // Log history
        $hist_remarks = $conn->real_escape_string($remarks ? "$action: $remarks" : "$action");
        $conn->query("INSERT INTO requirement_history (applicant_id,requirement_id,action,performed_by,remarks) VALUES ($aid,$rid,'$action',$user_id,'$hist_remarks')");
        // Log activity
        $rn = $conn->query("SELECT requirement_name FROM admission_requirements WHERE id=$rid");
        $req_name = ($rn && $rn->num_rows) ? $rn->fetch_assoc()['requirement_name'] : "requirement #$rid";
        logAdmission($conn, $user_id, "Requirement $new_status", 'requirements', $rid, "$req_name $new_status for applicant #$aid");
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false,'error'=>'Invalid IDs']); exit;
}
if ($ajax === 'mark_all_submitted') {
    header('Content-Type: application/json');
    $aid = intval($_POST['applicant_id'] ?? 0);
    if ($aid) {
        foreach ($req_items as $ri) {
            $iid = $ri['id'];
            $conn->query("INSERT INTO applicant_requirement_status (applicant_id,requirement_id,status,submitted_by,submitted_at) VALUES ($aid,$iid,'Submitted',$user_id,NOW()) ON DUPLICATE KEY UPDATE status='Submitted',submitted_by=$user_id,submitted_at=NOW()");
            $conn->query("INSERT INTO requirement_history (applicant_id,requirement_id,action,performed_by,remarks) VALUES ($aid,$iid,'Submitted',$user_id,'Bulk submitted')");
        }
        logAdmission($conn, $user_id, 'Bulk Submitted', 'requirements', $aid, "All requirements submitted for applicant #$aid");
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
if ($ajax === 'reset_requirements') {
    header('Content-Type: application/json');
    $aid = intval($_POST['applicant_id'] ?? 0);
    if ($aid) {
        $conn->query("UPDATE applicant_requirement_status SET status='Not Submitted',submitted_by=NULL,verified_by=NULL,rejected_by=NULL,submitted_at=NULL,verified_at=NULL,remarks='' WHERE applicant_id=$aid");
        $conn->query("INSERT INTO requirement_history (applicant_id,action,performed_by,remarks) VALUES ($aid,'Reset',$user_id,'All requirements reset')");
        logAdmission($conn, $user_id, 'Reset All', 'requirements', $aid, "All requirements reset for applicant #$aid");
        echo json_encode(['success'=>true]); exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_applicant') {
        $fn  = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
        $dob = $_POST['date_of_birth'] ?? null;
        $gen = $conn->real_escape_string(trim($_POST['gender'] ?? 'Other'));
        $ph  = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $em  = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $addr = $conn->real_escape_string(trim($_POST['address'] ?? ''));
        $gn  = $conn->real_escape_string(trim($_POST['guardian_name'] ?? ''));
        $gp  = $conn->real_escape_string(trim($_POST['guardian_phone'] ?? ''));
        $gr  = $conn->real_escape_string(trim($_POST['guardian_relationship'] ?? ''));
        $prog_id = intval($_POST['program_id'] ?? 0);
        $intake = $conn->real_escape_string(trim($_POST['intake'] ?? ''));
        $app_num = 'APP-'.date('Y').str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
        if ($fn) {
            $conn->query("INSERT INTO applicants (full_name,date_of_birth,gender,phone,email,address,guardian_name,guardian_phone,guardian_relationship,application_number,program_id,intake,admission_date,status) VALUES ('$fn','$dob','$gen','$ph','$em','$addr','$gn','$gp','$gr','$app_num',$prog_id,'$intake',CURDATE(),'New Applicant')");
            if ($conn->affected_rows > 0) {
                $aid = $conn->insert_id;
                // Auto-create requirement entries
                foreach ($req_items as $ri) {
                    $conn->query("INSERT INTO applicant_requirement_status (applicant_id,requirement_id,status) VALUES ($aid,{$ri['id']},'Not Submitted')");
                }
                logAdmission($conn, $user_id, 'Add Applicant', 'applicants', $aid, "Added applicant: $fn ($app_num)");
                $_SESSION['success'] = "Applicant '$fn' added. App No: $app_num";
            } else { $_SESSION['error'] = 'Failed: '.$conn->error; }
        } else { $_SESSION['error'] = 'Full name required.'; }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'edit_applicant') {
        $aid = intval($_POST['id'] ?? 0);
        $fn  = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
        $dob = $_POST['date_of_birth'] ?? null;
        $gen = $conn->real_escape_string(trim($_POST['gender'] ?? 'Other'));
        $ph  = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $em  = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $addr = $conn->real_escape_string(trim($_POST['address'] ?? ''));
        $gn  = $conn->real_escape_string(trim($_POST['guardian_name'] ?? ''));
        $gp  = $conn->real_escape_string(trim($_POST['guardian_phone'] ?? ''));
        $gr  = $conn->real_escape_string(trim($_POST['guardian_relationship'] ?? ''));
        $prog_id = intval($_POST['program_id'] ?? 0);
        $intake = $conn->real_escape_string(trim($_POST['intake'] ?? ''));
        $status = $conn->real_escape_string(trim($_POST['status'] ?? 'New Applicant'));
        if ($aid && $fn) {
            $conn->query("UPDATE applicants SET full_name='$fn',date_of_birth='$dob',gender='$gen',phone='$ph',email='$em',address='$addr',guardian_name='$gn',guardian_phone='$gp',guardian_relationship='$gr',program_id=$prog_id,intake='$intake',status='$status' WHERE id=$aid");
            logAdmission($conn, $user_id, 'Edit Applicant', 'applicants', $aid, "Edited applicant: $fn");
            $_SESSION['success'] = "Applicant updated.";
        }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'approve_applicant') {
        $aid = intval($_POST['applicant_id'] ?? 0);
        if ($aid) {
            $conn->query("UPDATE applicants SET status='Approved' WHERE id=$aid");
            logAdmission($conn, $user_id, 'Approve Applicant', 'applicants', $aid, "Applicant approved");
            $_SESSION['success'] = 'Applicant approved.';
        }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'reject_applicant') {
        $aid = intval($_POST['applicant_id'] ?? 0);
        if ($aid) {
            $conn->query("UPDATE applicants SET status='Rejected' WHERE id=$aid");
            logAdmission($conn, $user_id, 'Reject Applicant', 'applicants', $aid, "Applicant rejected");
            $_SESSION['success'] = 'Applicant rejected.';
        }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'register_applicant') {
        $aid = intval($_POST['applicant_id'] ?? 0);
        if ($aid) {
            $conn->query("UPDATE applicants SET status='Registered' WHERE id=$aid");
            logAdmission($conn, $user_id, 'Register Applicant', 'applicants', $aid, "Applicant registered");
            $_SESSION['success'] = 'Applicant registered as student.';
        }
        header("Location: director-admissions.php"); exit;
    }

    if ($action === 'delete_applicant') {
        $aid = intval($_POST['applicant_id'] ?? 0);
        if ($aid) {
            $conn->query("DELETE FROM requirement_history WHERE applicant_id=$aid");
            $conn->query("DELETE FROM applicant_requirement_status WHERE applicant_id=$aid");
            $conn->query("DELETE FROM applicants WHERE id=$aid");
            logAdmission($conn, $user_id, 'Delete Applicant', 'applicants', $aid, "Applicant deleted");
            $_SESSION['success'] = 'Applicant deleted.';
        }
        header("Location: director-admissions.php"); exit;
    }

    header("Location: director-admissions.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
:root{--adm-prim:#7c3aed;--adm-sec:#6d28d9;--adm-bg:#f8fafc;--adm-card: #ffffff;--adm-border:#e2e8f0;}
.da-header{background:linear-gradient(135deg,#1e1b4b,#312e81);padding:16px 24px;border-radius:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.da-header h1{font-size:1.35rem;font-weight:700;color:#fff;margin:0;letter-spacing:-0.3px}
.da-header p{font-size:0.78rem;color:rgba(255,255,255,0.7);margin:2px 0 0 0}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;margin-bottom:16px}
.stat-card{background:var(--adm-card);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 2px rgba(0,0,0,0.04);border:1px solid var(--adm-border);transition:all 0.15s}
.stat-card:hover{box-shadow:0 3px 10px rgba(0,0,0,0.06)}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;flex-shrink:0}
.stat-content h3{font-size:1.4rem;font-weight:700;margin:0;color:#1e293b;line-height:1.1}
.stat-content p{font-size:0.7rem;margin:2px 0 0;color:#64748b}
.sec-card{background:var(--adm-card);border-radius:10px;padding:18px 20px;border:1px solid var(--adm-border);box-shadow:0 1px 2px rgba(0,0,0,0.03);margin-bottom:14px}
.sec-card h2{font-size:1rem;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:8px;color:#1e293b}
.sec-nav{display:flex;gap:3px;flex-wrap:wrap;margin-bottom:12px;padding:0 2px}
.sec-nav a{padding:5px 12px;border-radius:6px;font-size:0.78rem;color:#64748b;background:#f1f5f9;text-decoration:none;font-weight:500;transition:all 0.12s;white-space:nowrap}
.sec-nav a:hover,.sec-nav a.active{background:var(--adm-prim);color:#fff}
.sec-nav a i{margin-right:4px;font-size:0.75rem}
.cs{display:none}.cs.active{display:block}
.table th{font-size:0.72rem;text-transform:uppercase;letter-spacing:0.4px;color:#64748b;border-bottom:2px solid var(--adm-border);padding:8px 10px}
.table td{font-size:0.82rem;vertical-align:middle;color:#334155;padding:8px 10px}
.btn-sm{border-radius:6px;font-size:0.75rem;padding:3px 9px}
.form-control,.form-select{border-radius:7px;font-size:0.82rem;border-color:var(--adm-border)}
.form-control:focus,.form-select:focus{border-color:var(--adm-prim);box-shadow:0 0 0 2px rgba(124,58,237,0.12)}
.badge{font-size:0.68rem;padding:2px 8px;border-radius:5px;font-weight:500}
.modal-content{border-radius:12px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.12)}
.search-dd{position:absolute;z-index:1000;width:100%;max-height:260px;overflow-y:auto;background:#fff;border:1px solid var(--adm-border);border-radius:7px;box-shadow:0 6px 20px rgba(0,0,0,0.1)}
.search-dd-item{display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background 0.08s}
.search-dd-item:hover{background:#f5f3ff}.search-dd-item:last-child{border-bottom:none}
.sr-name{font-weight:600;color:#1e293b;font-size:0.82rem}
.sr-meta{font-size:0.72rem;color:#64748b}
.app-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--adm-prim),#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.9rem}
.app-info-card{background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #c4b5fd;border-radius:10px;padding:14px 16px}
.req-card{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--adm-border);border-radius:8px;transition:all 0.12s;position:relative}
.req-card:hover{border-color:#c4b5fd;background:#fafaff}
.req-card .req-name{flex:1;font-size:0.82rem;font-weight:500;color:#1e293b;min-width:0}
.req-card select.form-select{border-radius:5px;font-size:0.72rem;padding:2px 20px 2px 6px;width:auto;min-width:95px;border:1px solid #d1d5db}
.req-card .req-remarks-input{font-size:0.72rem;border:1px solid var(--adm-border);border-radius:5px;padding:2px 6px;width:130px}
.req-card .req-remarks-input:focus{border-color:var(--adm-prim);outline:none}
.req-hist-item{font-size:0.75rem;padding:6px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;align-items:flex-start}
.req-hist-item:last-child{border-bottom:none}
.toast-fixed{position:fixed;top:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:8px;font-size:0.82rem;font-weight:500;color:#fff;box-shadow:0 6px 20px rgba(0,0,0,0.12);transform:translateX(120%);transition:transform 0.25s ease}
.toast-fixed.show{transform:translateX(0)}
.toast-fixed.success{background:#059669}.toast-fixed.error{background:#dc2626}.toast-fixed.info{background:#0284c7}
.chart-container{position:relative;height:200px;width:100%}
.loading-dots{text-align:center;padding:30px;color:#94a3b8}
.empty-state{text-align:center;padding:40px 20px;color:#94a3b8}
.empty-state i{font-size:2.5rem;margin-bottom:12px;opacity:0.3}
@media(max-width:768px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .stat-card{padding:10px 12px}
  .stat-content h3{font-size:1.1rem}
  .sec-nav{overflow-x:auto;flex-wrap:nowrap;padding-bottom:4px}
  .sec-nav a{font-size:0.72rem;padding:4px 10px}
  .da-header{flex-direction:column;align-items:flex-start}
  .req-card{flex-wrap:wrap}
  .req-card select.form-select{width:100%}
  .req-card .req-remarks-input{width:100%}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr 1fr;gap:6px}
  .stat-card{padding:8px 10px;gap:8px}
  .stat-icon{width:32px;height:32px;font-size:0.9rem}
  .stat-content h3{font-size:1rem}
}
</style>
</head>
<body>
<div class="dashboard-container">
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
<header class="da-header">
<div>
<h1><i class="fas fa-file-signature me-2"></i>Director Admissions & Requirements</h1>
<p>Applications &middot; Requirements Clearance &middot; Admission Reports</p>
</div>
<div class="d-flex align-items-center gap-2">
<span style="font-size:0.76rem;color:rgba(255,255,255,0.7)"><i class="fas fa-calendar me-1"></i><?= date('l, F j, Y') ?></span>
<a href="../index.php" class="btn btn-sm btn-outline-light"><i class="fas fa-home"></i></a>
</div>
</header>

<?php if(!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-3 mb-0 py-2 small"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if(!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0 py-2 small"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['error']); endif; ?>

<div class="p-3">

<div class="sec-nav">
<a href="#" class="active" data-sec="overview"><i class="fas fa-chart-pie"></i>Overview</a>
<a href="#" data-sec="applications"><i class="fas fa-file-alt"></i>Applications</a>
<a href="#" data-sec="requirements"><i class="fas fa-clipboard-check"></i>Requirements</a>
<a href="#" data-sec="clearance"><i class="fas fa-check-double"></i>Clearance</a>
<a href="#" data-sec="reports"><i class="fas fa-chart-bar"></i>Reports</a>
</div>

<!-- ═══ OVERVIEW ═══ -->
<section id="sec-overview" class="cs active">
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon" style="background:var(--adm-prim)"><i class="fas fa-file-alt"></i></div><div class="stat-content"><h3><?= $total_applicants ?></h3><p>Total Applicants</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#f59e0b"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= $new_applicants ?></h3><p>New Applications</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#059669"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $approved_applicants ?></h3><p>Approved</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#0284c7"><i class="fas fa-search"></i></div><div class="stat-content"><h3><?= $pending_verify ?></h3><p>Pending Verification</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#8b5cf6"><i class="fas fa-user-check"></i></div><div class="stat-content"><h3><?= $cleared_count ?></h3><p>Fully Cleared</p></div></div>
<div class="stat-card"><div class="stat-icon" style="background:#dc2626"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-content"><h3><?= $missing_req_count ?></h3><p>Missing Requirements</p></div></div>
</div>

<div class="row g-3">
<div class="col-md-8">
<div class="sec-card">
<h2><i class="fas fa-chart-bar" style="color:var(--adm-prim)"></i>Applications by Intake</h2>
<?php if (!empty($intake_stats)): ?>
<canvas id="intakeChart" height="160"></canvas>
<?php else: ?>
<div class="empty-state"><i class="fas fa-chart-bar"></i><p>No intake data yet.</p></div>
<?php endif; ?>
</div>
</div>
<div class="col-md-4">
<div class="sec-card">
<h2><i class="fas fa-clipboard-check" style="color:#059669"></i>Clearance Progress</h2>
<?php if (array_sum($clearance_levels) > 0): ?>
<canvas id="clearanceChart" height="160"></canvas>
<?php else: ?>
<div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No clearance data yet.</p></div>
<?php endif; ?>
</div>
</div>
</div>

<div class="sec-card">
<h2><i class="fas fa-bolt" style="color:#f59e0b"></i>Quick Actions</h2>
<div class="d-flex flex-wrap gap-2">
<button class="btn btn-sm btn-primary" onclick="openAddModal()"><i class="fas fa-user-plus me-1"></i>Add Applicant</button>
<button class="btn btn-sm btn-outline-info" onclick="switchSec('applications')"><i class="fas fa-list me-1"></i>View All</button>
<button class="btn btn-sm btn-outline-success" onclick="switchSec('requirements')"><i class="fas fa-clipboard-check me-1"></i>Requirements</button>
<a href="admission-letters.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-envelope me-1"></i>Letters</a>
<a href="intake-planning.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-calendar me-1"></i>Intake Planning</a>
</div>
</div>

<div class="sec-card">
<h2><i class="fas fa-history" style="color:#64748b"></i>Recent Activity</h2>
<?php if (empty($recent_activity)): ?>
<div class="empty-state" style="padding:20px"><i class="fas fa-history" style="font-size:1.5rem"></i><p class="small mb-0">No activity yet.</p></div>
<?php else: ?>
<div style="max-height:300px;overflow-y:auto">
<?php foreach($recent_activity as $act): ?>
<div class="d-flex align-items-center gap-2 px-1 py-1" style="border-bottom:1px solid #f1f5f9;font-size:0.78rem">
<span class="badge bg-<?= strpos($act['action'],'Add')!==false?'primary':(strpos($act['action'],'Approve')!==false?'success':(strpos($act['action'],'Reject')!==false?'danger':(strpos($act['action'],'Register')!==false?'info':'secondary'))) ?> me-1"><?= htmlspecialchars($act['action']) ?></span>
<span class="text-muted"><?= htmlspecialchars($act['description'] ?? '') ?></span>
<span class="ms-auto text-muted" style="font-size:0.7rem;white-space:nowrap"><?= date('M j, g:ia', strtotime($act['created_at'])) ?></span>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>

<!-- ═══ APPLICATIONS ═══ -->
<section id="sec-applications" class="cs">
<div class="sec-card">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<h2 style="margin:0"><i class="fas fa-file-alt" style="color:var(--adm-prim)"></i>Applicant Records</h2>
<button class="btn btn-sm btn-primary" onclick="openAddModal()"><i class="fas fa-plus me-1"></i>Add Applicant</button>
</div>

<div class="search-box mb-3" style="position:relative">
<div class="input-group input-group-sm">
<span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted" style="font-size:0.8rem"></i></span>
<input type="text" id="appSearch" class="form-control border-start-0" placeholder="Search by name, application number, or phone..." autocomplete="off">
<button class="btn btn-primary" onclick="searchApps()"><i class="fas fa-search me-1"></i>Search</button>
</div>
<div id="appSearchResults" class="search-dd mt-1" style="display:none"></div>
<div class="mt-1 d-flex gap-2 flex-wrap">
<small class="text-muted">Search by:</small>
<span class="badge bg-light text-dark border">Full Name</span>
<span class="badge bg-light text-dark border">Application Number</span>
<span class="badge bg-light text-dark border">Phone Number</span>
</div>
</div>

<div id="appListContainer">
<?php if (empty($applicants_list)): ?>
<div class="empty-state"><i class="fas fa-users"></i><p>No applicants yet. Click "Add Applicant" to create one.</p></div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-hover" id="appsTable">
<thead><tr><th>App No</th><th>Applicant</th><th>Program</th><th>Intake</th><th>Status</th><th>Date</th><th style="width:120px">Actions</th></tr></thead>
<tbody>
<?php foreach($applicants_list as $a):
$sts = $a['status'];
$badge = $sts==='Approved'?'success':($sts==='Rejected'?'danger':($sts==='Registered'?'info':($sts==='Under Review'?'warning':'secondary')));
$prog_name = '';
$rpn = $conn->query("SELECT program_name FROM academic_programs WHERE id={$a['program_id']}");
if ($rpn && $rpn->num_rows) $prog_name = $rpn->fetch_assoc()['program_name'];
?>
<tr>
<td><code><?= htmlspecialchars($a['application_number']) ?></code></td>
<td><strong><?= htmlspecialchars($a['full_name']) ?></strong></td>
<td><?= htmlspecialchars($prog_name ?: 'N/A') ?></td>
<td><?= htmlspecialchars($a['intake'] ?? '-') ?></td>
<td><span class="badge bg-<?= $badge ?>"><?= $sts ?></span></td>
<td><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
<td>
<div class="d-flex gap-1">
<button class="btn btn-sm btn-outline-info py-0 px-1" onclick="viewApplicant(<?= $a['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
<button class="btn btn-sm btn-outline-primary py-0 px-1" onclick="editApplicant(<?= $a['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
<?php if ($sts==='New Applicant'||$sts==='Under Review'): ?>
<form method="POST" class="d-inline" onsubmit="return confirm('Approve this applicant?')"><input type="hidden" name="action" value="approve_applicant"><input type="hidden" name="applicant_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-success py-0 px-1"><i class="fas fa-check"></i></button></form>
<form method="POST" class="d-inline" onsubmit="return confirm('Reject this applicant?')"><input type="hidden" name="action" value="reject_applicant"><input type="hidden" name="applicant_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fas fa-times"></i></button></form>
<?php elseif ($sts==='Approved'): ?>
<form method="POST" class="d-inline" onsubmit="return confirm('Register as student?')"><input type="hidden" name="action" value="register_applicant"><input type="hidden" name="applicant_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-outline-success py-0 px-1" title="Register"><i class="fas fa-user-graduate"></i></button></form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
</div>
</section>

<!-- ═══ REQUIREMENTS PORTAL ═══ -->
<section id="sec-requirements" class="cs">
<div class="sec-card">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<h2 style="margin:0"><i class="fas fa-clipboard-check" style="color:var(--adm-prim)"></i>Requirements Portal</h2>
</div>
<div class="stats-grid small mb-2">
<div class="stat-card p-2"><div class="stat-icon" style="font-size:0.9rem;width:32px;height:32px;background:var(--adm-prim)"><i class="fas fa-list"></i></div><div class="stat-content"><h4 style="font-size:1rem;margin:0"><?= $total_req_items ?></h4><p style="font-size:0.65rem;margin:0;color:#64748b">Requirement Items</p></div></div>
<div class="stat-card p-2"><div class="stat-icon" style="font-size:0.9rem;width:32px;height:32px;background:#0284c7"><i class="fas fa-users"></i></div><div class="stat-content"><h4 style="font-size:1rem;margin:0"><?= $total_applicants ?></h4><p style="font-size:0.65rem;margin:0;color:#64748b">Total Applicants</p></div></div>
<div class="stat-card p-2"><div class="stat-icon" style="font-size:0.9rem;width:32px;height:32px;background:#059669"><i class="fas fa-check-double"></i></div><div class="stat-content"><h4 style="font-size:1rem;margin:0"><?= $clearance_levels['Verified'] ?></h4><p style="font-size:0.65rem;margin:0;color:#64748b">Verified Items</p></div></div>
<div class="stat-card p-2"><div class="stat-icon" style="font-size:0.9rem;width:32px;height:32px;background:#8b5cf6"><i class="fas fa-user-check"></i></div><div class="stat-content"><h4 style="font-size:1rem;margin:0"><?= $cleared_count ?> / <?= $total_applicants ?></h4><p style="font-size:0.65rem;margin:0;color:#64748b">Fully Cleared</p></div></div>
</div>

<div class="mb-3" style="position:relative">
<div class="input-group input-group-sm">
<span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
<input type="text" id="reqAppSearch" class="form-control border-start-0" placeholder="Search applicant by name, app number, or phone..." autocomplete="off">
<button class="btn btn-primary" onclick="searchReqApps()"><i class="fas fa-search me-1"></i>Search</button>
</div>
<div id="reqSearchResults" class="search-dd mt-1" style="display:none"></div>
</div>

<div id="reqAppInfoCard" class="app-info-card mb-3" style="display:none">
<div class="d-flex align-items-center gap-3 flex-wrap">
<div class="app-avatar"><i class="fas fa-user-graduate"></i></div>
<div class="flex-grow-1">
<h5 class="mb-0" id="reqAppName" style="font-size:0.95rem">-</h5>
<div class="d-flex gap-3 small text-muted flex-wrap">
<span><i class="fas fa-id-card me-1"></i><span id="reqAppNo">-</span></span>
<span><i class="fas fa-phone me-1"></i><span id="reqAppPhone">-</span></span>
<span><i class="fas fa-tag me-1"></i><span id="reqAppStatus">-</span></span>
</div>
</div>
<div class="text-end">
<div id="reqProgressCircle">
<svg width="52" height="52" viewBox="0 0 60 60"><circle cx="30" cy="30" r="26" fill="none" stroke="#e5e7eb" stroke-width="4"/><circle id="reqArc" cx="30" cy="30" r="26" fill="none" stroke="#7c3aed" stroke-width="4" stroke-dasharray="163.36" stroke-dashoffset="163.36" transform="rotate(-90 30 30)"/><text x="30" y="30" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="bold" fill="#1e293b" id="reqPct">0%</text></svg>
</div>
</div>
</div>
<div class="progress mt-2" style="height:5px">
<div id="reqBar" class="progress-bar bg-purple" role="progressbar" style="width:0%"></div>
</div>
<div class="d-flex gap-2 mt-2 flex-wrap">
<span class="badge bg-success" id="reqBadgeVerified">0 Verified</span>
<span class="badge bg-warning text-dark" id="reqBadgePending">0 Pending</span>
<span class="badge bg-secondary" id="reqBadgeMissing">0 Missing</span>
</div>
</div>

<div id="reqListContainer">
<div class="empty-state"><i class="fas fa-search"></i><p>Search for an applicant above to manage their requirements.</p></div>
</div>

<ul class="nav nav-tabs mt-3" id="reqDetailTabs" style="display:none">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#reqChecklistTab">Checklist</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#reqHistoryTab">History</a></li>
</ul>
<div class="tab-content mt-2" id="reqDetailContent" style="display:none">
<div class="tab-pane fade show active" id="reqChecklistTab"></div>
<div class="tab-pane fade" id="reqHistoryTab"></div>
</div>
</div>
</section>

<!-- ═══ CLEARANCE ═══ -->
<section id="sec-clearance" class="cs">
<div class="sec-card">
<h2><i class="fas fa-check-double" style="color:#059669"></i>Requirement Clearance</h2>
<p class="text-muted small mb-3">Overview of requirement completion per item.</p>
<div class="table-responsive">
<table class="table table-sm">
<thead><tr><th>#</th><th>Requirement</th><th>Not Submitted</th><th>Submitted</th><th>Verified</th><th>Rejected</th><th>Missing</th><th>Progress</th></tr></thead>
<tbody>
<?php $idx=1; foreach($req_items as $ri):
$n = sc($conn,"SELECT COUNT(*)c FROM applicant_requirement_status WHERE requirement_id={$ri['id']} AND status='Not Submitted'");
$s = sc($conn,"SELECT COUNT(*)c FROM applicant_requirement_status WHERE requirement_id={$ri['id']} AND status='Submitted'");
$v = sc($conn,"SELECT COUNT(*)c FROM applicant_requirement_status WHERE requirement_id={$ri['id']} AND status='Verified'");
$rj = sc($conn,"SELECT COUNT(*)c FROM applicant_requirement_status WHERE requirement_id={$ri['id']} AND status='Rejected'");
$m = sc($conn,"SELECT COUNT(*)c FROM applicant_requirement_status WHERE requirement_id={$ri['id']} AND status='Missing'");
$total_s = $n+$s+$v+$rj+$m;
$pct = $total_s > 0 ? round(($v/$total_s)*100) : 0;
$bar = $pct>=80?'bg-success':($pct>=50?'bg-warning':'bg-danger');
?>
<tr>
<td><?= $idx++ ?></td>
<td><?= htmlspecialchars($ri['requirement_name']) ?></td>
<td><?= $n ?></td>
<td><?= $s ?></td>
<td><strong><?= $v ?></strong></td>
<td><?= $rj ?></td>
<td><?= $m ?></td>
<td style="min-width:140px"><div class="progress" style="height:16px"><div class="progress-bar <?= $bar ?>" style="width:<?= $pct ?>%"><?= $pct ?>%</div></div></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</section>

<!-- ═══ REPORTS ═══ -->
<section id="sec-reports" class="cs">
<div class="sec-card">
<h2><i class="fas fa-chart-bar" style="color:var(--adm-prim)"></i>Admission Reports</h2>
<div class="row g-3">
<div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:8px;border:1px solid var(--adm-border);transition:all 0.15s" onclick="window.open('director-admissions.php?report=applications','_blank')"><i class="fas fa-file-alt fa-2x mb-2" style="color:var(--adm-prim)"></i><strong class="small">Applications</strong></div></div>
<div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:8px;border:1px solid var(--adm-border);transition:all 0.15s" onclick="window.open('director-admissions.php?report=cleared','_blank')"><i class="fas fa-user-check fa-2x mb-2" style="color:#059669"></i><strong class="small">Cleared</strong></div></div>
<div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:8px;border:1px solid var(--adm-border);transition:all 0.15s" onclick="window.open('director-admissions.php?report=clearance','_blank')"><i class="fas fa-clipboard-check fa-2x mb-2" style="color:#8b5cf6"></i><strong class="small">Clearance</strong></div></div>
<div class="col-md-3 col-6"><div class="card card-body text-center py-3" style="cursor:pointer;border-radius:8px;border:1px solid var(--adm-border);transition:all 0.15s" onclick="window.open('director-admissions.php?report=intake','_blank')"><i class="fas fa-calendar-alt fa-2x mb-2" style="color:#0284c7"></i><strong class="small">Intake</strong></div></div>
</div>
</div>
</section>

</div><!-- /p-3 -->
</div><!-- /main-content -->
</div><!-- /dashboard-container -->

<!-- Add/Edit Modal -->
<div class="modal fade" id="appModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content" id="appForm">
<input type="hidden" name="action" id="appFormAction" value="add_applicant">
<input type="hidden" name="id" id="appFormId" value="">
<div class="modal-header" style="background:linear-gradient(135deg,var(--adm-prim),#6d28d9);color:#fff">
<h5 class="modal-title" id="appModalTitle"><i class="fas fa-user-plus me-2"></i>Add Applicant</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="row g-3">
<div class="col-md-12"><label class="form-label small fw-medium">Full Name *</label><input type="text" name="full_name" id="afn" class="form-control form-control-sm" required></div>
<div class="col-md-4"><label class="form-label small fw-medium">Gender</label><select name="gender" id="agen" class="form-select form-select-sm"><option>Male</option><option>Female</option><option>Other</option></select></div>
<div class="col-md-4"><label class="form-label small fw-medium">Date of Birth</label><input type="date" name="date_of_birth" id="adob" class="form-control form-control-sm"></div>
<div class="col-md-4"><label class="form-label small fw-medium">Phone</label><input type="text" name="phone" id="aph" class="form-control form-control-sm"></div>
<div class="col-md-6"><label class="form-label small fw-medium">Email</label><input type="email" name="email" id="aem" class="form-control form-control-sm"></div>
<div class="col-md-6"><label class="form-label small fw-medium">Address</label><input type="text" name="address" id="aaddr" class="form-control form-control-sm"></div>
<hr class="my-1">
<div class="col-md-4"><label class="form-label small fw-medium">Guardian Name</label><input type="text" name="guardian_name" id="agn" class="form-control form-control-sm"></div>
<div class="col-md-4"><label class="form-label small fw-medium">Guardian Phone</label><input type="text" name="guardian_phone" id="agp" class="form-control form-control-sm"></div>
<div class="col-md-4"><label class="form-label small fw-medium">Relationship</label><input type="text" name="guardian_relationship" id="agr" class="form-control form-control-sm"></div>
<hr class="my-1">
<div class="col-md-6"><label class="form-label small fw-medium">Program</label>
<select name="program_id" id="aprog" class="form-select form-select-sm"><option value="">Select Program</option>
<?php foreach($programs_list as $p): ?><option value="<?= $p['id'] ?? $p['program_code'] ?>"><?= htmlspecialchars($p['program_name']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-3"><label class="form-label small fw-medium">Intake</label><input type="text" name="intake" id="aint" class="form-control form-control-sm" placeholder="e.g. 2025A"></div>
<div class="col-md-3" id="editStatusWrap" style="display:none"><label class="form-label small fw-medium">Status</label><select name="status" id="asts" class="form-select form-select-sm"><option>New Applicant</option><option>Under Review</option><option>Approved</option><option>Rejected</option><option>Registered</option></select></div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i><span id="appSaveLabel">Add Applicant</span></button>
</div>
</form>
</div>
</div>

<!-- View Applicant Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header" style="background:linear-gradient(135deg,#1e1b4b,#312e81);color:#fff">
<h5 class="modal-title"><i class="fas fa-user me-2"></i>Applicant Profile</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body p-4" id="viewModalBody">
<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--adm-prim)"></i><p class="mt-2 text-muted small">Loading...</p></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// ── Prevent unhandled promise rejections ──
window.addEventListener('unhandledrejection', function(e){ e.promise.catch(function(){}); });

// ── Section switching ──
var navLinks = document.querySelectorAll('.sec-nav a');
function switchSec(sec){
    navLinks.forEach(function(l){ l.classList.toggle('active', l.getAttribute('data-sec') === sec); });
    document.querySelectorAll('.cs').forEach(function(s){ s.classList.toggle('active', s.id === 'sec-' + sec); });
    history.replaceState(null, '', '#' + sec);
}
navLinks.forEach(function(l){
    l.addEventListener('click', function(e){
        e.preventDefault();
        switchSec(this.getAttribute('data-sec'));
    });
});
(function(){
    var hash = location.hash.replace('#', '');
    if (['overview','applications','requirements','clearance','reports'].indexOf(hash) !== -1) switchSec(hash);
})();
window.addEventListener('hashchange', function(){
    var h = location.hash.replace('#', '');
    if (['overview','applications','requirements','clearance','reports'].indexOf(h) !== -1) switchSec(h);
});

// ── Charts ──
<?php if (!empty($intake_stats)): ?>
new Chart(document.getElementById('intakeChart'), {
    type: 'bar',
    data: {
        labels: [<?php foreach($intake_stats as $is){ echo "'".htmlspecialchars($is['intake'])."',"; } ?>],
        datasets: [{
            label: 'Applicants',
            data: [<?php foreach($intake_stats as $is){ echo $is['c'].","; } ?>],
            backgroundColor: '#7c3aed',
            borderRadius: 4,
            barThickness: 28
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {legend:{display:false}},
        scales: {y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}
    }
});
<?php endif; ?>

<?php if (array_sum($clearance_levels) > 0): ?>
new Chart(document.getElementById('clearanceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Not Submitted','Submitted','Verified','Rejected','Missing'],
        datasets: [{
            data: [<?= $clearance_levels['Not Submitted'] ?>,<?= $clearance_levels['Submitted'] ?>,<?= $clearance_levels['Verified'] ?>,<?= $clearance_levels['Rejected'] ?>,<?= $clearance_levels['Missing'] ?>],
            backgroundColor: ['#94a3b8','#f59e0b','#059669','#dc2626','#f97316'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {legend:{position:'bottom',labels:{boxWidth:10,font:{size:10}}}}
    }
});
<?php endif; ?>

// ── Toast ──
function showToast(msg, type){
    type = type || 'success';
    var t = document.createElement('div');
    t.className = 'toast-fixed ' + type;
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(function(){ t.classList.add('show'); }, 30);
    setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); }, 250); }, 3000);
}

// ── Applicant Modal ──
function openAddModal(){
    document.getElementById('appFormAction').value = 'add_applicant';
    document.getElementById('appModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add Applicant';
    document.getElementById('appSaveLabel').textContent = 'Add Applicant';
    document.getElementById('appFormId').value = '';
    document.getElementById('editStatusWrap').style.display = 'none';
    document.getElementById('appForm').reset();
    new bootstrap.Modal(document.getElementById('appModal')).show();
}

function editApplicant(id){
    document.getElementById('appFormAction').value = 'edit_applicant';
    document.getElementById('appModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Applicant';
    document.getElementById('appSaveLabel').textContent = 'Update Applicant';
    document.getElementById('editStatusWrap').style.display = 'block';
    document.getElementById('appFormId').value = id;
    fetch('director-admissions.php?ajax=get_requirements&applicant_id=' + id)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var info = d.info || {};
            document.getElementById('afn').value = info.full_name || '';
            document.getElementById('agen').value = info.gender || 'Male';
            document.getElementById('adob').value = info.date_of_birth || '';
            document.getElementById('aph').value = info.phone || '';
            document.getElementById('aem').value = info.email || '';
            document.getElementById('aaddr').value = info.address || '';
            document.getElementById('agn').value = info.guardian_name || '';
            document.getElementById('agp').value = info.guardian_phone || '';
            document.getElementById('agr').value = info.guardian_relationship || '';
            document.getElementById('aprog').value = info.program_id || '';
            document.getElementById('aint').value = info.intake || '';
            document.getElementById('asts').value = info.status || 'New Applicant';
            new bootstrap.Modal(document.getElementById('appModal')).show();
        })
        .catch(function(){ showToast('Failed to load applicant', 'error'); });
}

// ── Search Applicants ──
function searchApps(){
    var q = document.getElementById('appSearch').value.trim();
    var results = document.getElementById('appSearchResults');
    var container = document.getElementById('appListContainer');
    if (q.length < 2) {
        results.style.display = 'none';
        container.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>Enter at least 2 characters to search.</p></div>';
        return;
    }
    results.style.display = '';
    results.innerHTML = '<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    fetch('director-admissions.php?ajax=search_applicants&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data || !data.length) {
                results.innerHTML = '<div class="p-2 text-center text-muted small">No applicants found.</div>';
                return;
            }
            var h = '';
            data.forEach(function(s){
                h += '<div class="search-dd-item" onclick="viewApplicant('+s.id+')">'+
                    '<div class="app-avatar" style="width:32px;height:32px;font-size:0.75rem"><i class="fas fa-user"></i></div>'+
                    '<div style="flex:1;min-width:0"><div class="sr-name">'+htmlEsc(s.full_name)+'</div><div class="sr-meta">'+htmlEsc(s.application_number)+' | '+htmlEsc(s.program)+' | '+htmlEsc(s.phone)+'</div></div>'+
                    '<span class="badge bg-'+ (s.status==='Approved'?'success':(s.status==='Rejected'?'danger':'secondary')) +'">'+s.status+'</span>'+
                    '</div>';
            });
            results.innerHTML = h;
            results.style.display = '';
        })
        .catch(function(){ results.innerHTML = '<div class="p-2 text-center text-danger small">Search failed.</div>'; });
}

document.addEventListener('DOMContentLoaded', function(){
    var inp = document.getElementById('appSearch');
    if (inp) {
        inp.addEventListener('keyup', function(e){
            if (e.key === 'Enter') searchApps();
            else { var v = this.value; setTimeout(function(){ if (document.getElementById('appSearch').value === v) searchApps(); }, 300); }
        });
        inp.addEventListener('blur', function(){ setTimeout(function(){ var d=document.getElementById('appSearchResults'); if(d)d.style.display='none'; }, 200); });
        inp.addEventListener('focus', function(){ if(this.value.length>=2){ var d=document.getElementById('appSearchResults'); if(d)d.style.display=''; } });
    }
});

// ── View Applicant (loads full profile with requirements) ──
function viewApplicant(id){
    var modal = new bootstrap.Modal(document.getElementById('viewModal'));
    document.getElementById('viewModalBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#7c3aed"></i><p class="mt-2 text-muted small">Loading profile...</p></div>';
    modal.show();
    fetch('director-admissions.php?ajax=get_requirements&applicant_id='+id)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var info = d.info || {};
            var reqData = d.requirements || [];
            var history = d.history || [];
            var totalItems = reqData.length;
            var verified = reqData.filter(function(r){ return r.status === 'Verified'; }).length;
            var pct = totalItems > 0 ? Math.round((verified/totalItems)*100) : 0;
            var h = '<div class="row g-3">'+
                '<div class="col-md-4">'+
                '<div class="text-center p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">'+
                '<div class="app-avatar" style="width:64px;height:64px;font-size:1.5rem;margin:0 auto 8px"><i class="fas fa-user-graduate"></i></div>'+
                '<h5 class="mb-0" style="font-size:1rem">'+htmlEsc(info.full_name||'')+'</h5>'+
                '<small class="text-muted">'+htmlEsc(info.application_number||'')+'</small>'+
                '<div class="mt-2"><span class="badge bg-'+ (info.status==='Approved'?'success':(info.status==='Rejected'?'danger':(info.status==='Registered'?'info':'secondary'))) +'">'+(info.status||'-')+'</span></div>'+
                '</div>'+
                '<div class="mt-3 p-3 rounded" style="background:#f5f3ff;border:1px solid #e0e7ff">'+
                '<div class="small fw-bold" style="color:#7c3aed"><i class="fas fa-clipboard-check me-1"></i>Progress</div>'+
                '<div class="d-flex align-items-center gap-3 mt-2">'+
                '<svg width="56" height="56" viewBox="0 0 60 60"><circle cx="30" cy="30" r="26" fill="none" stroke="#e5e7eb" stroke-width="4"/>'+
                '<circle cx="30" cy="30" r="26" fill="none" stroke="#7c3aed" stroke-width="4" stroke-dasharray="163.36" stroke-dashoffset="'+((100-pct)/100*163.36)+'" transform="rotate(-90 30 30)"/>'+
                '<text x="30" y="30" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="bold" fill="#1e293b">'+pct+'%</text></svg>'+
                '<div><div class="fw-bold" style="font-size:1.1rem">'+verified+' / '+totalItems+'</div><div class="small text-muted">verified</div></div>'+
                '</div><div class="progress mt-2" style="height:4px"><div class="progress-bar" style="background:#7c3aed;width:'+pct+'%"></div></div>'+
                '</div>'+
                '</div>'+
                '<div class="col-md-8">'+
                '<ul class="nav nav-tabs mb-2 small">'+
                '<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#vpInfo">Info</a></li>'+
                '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vpReqs">Requirements</a></li>'+
                '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vpH">History</a></li>'+
                '</ul>'+
                '<div class="tab-content">'+
                '<div class="tab-pane fade show active" id="vpInfo">'+
                '<div class="row g-1 small">';
            var fields = [
                ['Full Name', info.full_name],
                ['App Number', info.application_number],
                ['Gender', info.gender],
                ['DOB', info.date_of_birth],
                ['Phone', info.phone],
                ['Email', info.email],
                ['Address', info.address],
                ['Guardian', info.guardian_name],
                ['Guardian Phone', info.guardian_phone],
                ['Relationship', info.guardian_relationship],
                ['Intake', info.intake],
                ['Admission Date', info.admission_date],
                ['Status', info.status]
            ];
            fields.forEach(function(f){
                if (f[1]) h += '<div class="col-6"><span class="text-muted">'+f[0]+':</span> <strong>'+htmlEsc(f[1])+'</strong></div>';
            });
            h += '</div></div>'+
                '<div class="tab-pane fade" id="vpReqs">'+
                '<div class="row g-1">';
            reqData.forEach(function(r){
                var sts = r.status || 'Not Submitted';
                var badge = sts==='Verified'?'bg-success':(sts==='Rejected'?'bg-danger':(sts==='Submitted'?'bg-warning text-dark':(sts==='Missing'?'bg-orange text-dark':'bg-light text-muted')));
                h += '<div class="col-md-6"><div class="req-card py-1 px-2" style="border-color:'+(sts==='Verified'?'#86efac':(sts==='Rejected'?'#fecaca':'#e2e8f0'))+'">'+
                    '<span style="font-size:0.8rem;color:'+(sts==='Verified'?'#059669':(sts==='Rejected'?'#dc2626':'#94a3b8'))+'">'+
                    '<i class="fas '+(sts==='Verified'?'fa-check-circle':(sts==='Rejected'?'fa-times-circle':'fa-circle'))+'"></i></span>'+
                    '<span class="req-name">'+htmlEsc(r.requirement_name||'')+'</span>'+
                    '<span class="badge '+badge+'">'+sts+'</span>'+
                    (r.remarks ? '<small class="text-muted ms-1" title="'+htmlEsc(r.remarks)+'"><i class="fas fa-comment"></i></small>' : '')+
                    '</div></div>';
            });
            h += '</div></div>'+
                '<div class="tab-pane fade" id="vpH"><div style="max-height:300px;overflow-y:auto">';
            if (history.length) {
                history.forEach(function(ev){
                    h += '<div class="req-hist-item"><span class="badge bg-'+ (ev.action==='Verified'?'success':(ev.action==='Rejected'?'danger':(ev.action==='Submitted'?'warning text-dark':'secondary'))) +'">'+ev.action+'</span>'+
                        '<span style="flex:1">'+htmlEsc(ev.remarks||'')+'</span>'+
                        '<small class="text-muted" style="white-space:nowrap">'+(ev.performed_by_name ? htmlEsc(ev.performed_by_name)+' &middot; ' : '')+ (ev.created_at ? new Date(ev.created_at).toLocaleString() : '')+'</small></div>';
                });
            } else {
                h += '<p class="text-muted small">No history yet.</p>';
            }
            h += '</div></div></div></div></div></div>';
            document.getElementById('viewModalBody').innerHTML = h;
        })
        .catch(function(){
            document.getElementById('viewModalBody').innerHTML = '<div class="alert alert-danger text-center m-3 py-4">Failed to load profile.</div>';
        });
}

// ── REQUIREMENTS PORTAL ──
var _selectedReqAppId = 0;

function searchReqApps(){
    var q = document.getElementById('reqAppSearch').value.trim();
    var results = document.getElementById('reqSearchResults');
    if (q.length < 2) { results.style.display = 'none'; return; }
    results.style.display = '';
    results.innerHTML = '<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    fetch('director-admissions.php?ajax=search_applicants&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data || !data.length) {
                results.innerHTML = '<div class="p-2 text-center text-muted small">No applicants found.</div>';
                return;
            }
            var h = '';
            data.forEach(function(s){
                h += '<div class="search-dd-item" onclick="selectReqApp('+s.id+')">'+
                    '<div class="app-avatar" style="width:32px;height:32px;font-size:0.75rem"><i class="fas fa-user"></i></div>'+
                    '<div style="flex:1;min-width:0"><div class="sr-name">'+htmlEsc(s.full_name)+'</div><div class="sr-meta">'+htmlEsc(s.application_number)+' | '+htmlEsc(s.phone)+'</div></div>'+
                    '<span class="badge bg-'+ (s.status==='Approved'?'success':(s.status==='Rejected'?'danger':'secondary')) +'">'+s.status+'</span>'+
                    '</div>';
            });
            results.innerHTML = h;
        })
        .catch(function(){ results.innerHTML = '<div class="p-2 text-center text-danger small">Search failed.</div>'; });
}

document.addEventListener('DOMContentLoaded', function(){
    var inp = document.getElementById('reqAppSearch');
    if (inp) {
        inp.addEventListener('keyup', function(e){
            if (e.key === 'Enter') searchReqApps();
            else { var v = this.value; setTimeout(function(){ if (document.getElementById('reqAppSearch').value === v) searchReqApps(); }, 300); }
        });
        inp.addEventListener('blur', function(){ setTimeout(function(){ var d=document.getElementById('reqSearchResults'); if(d)d.style.display='none'; }, 200); });
        inp.addEventListener('focus', function(){ if(this.value.length>=2){ var d=document.getElementById('reqSearchResults'); if(d)d.style.display=''; } });
    }
});

function selectReqApp(id){
    _selectedReqAppId = id;
    document.getElementById('reqSearchResults').style.display = 'none';
    document.getElementById('reqAppSearch').value = '';
    loadReq(id);
}

function loadReq(aid){
    var list = document.getElementById('reqListContainer');
    list.innerHTML = '<div class="loading-dots"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading requirements...</p></div>';
    document.getElementById('reqAppInfoCard').style.display = 'none';
    document.getElementById('reqDetailTabs').style.display = 'none';
    document.getElementById('reqDetailContent').style.display = 'none';
    fetch('director-admissions.php?ajax=get_requirements&applicant_id=' + aid)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var info = d.info || {};
            var reqData = d.requirements || [];
            var history = d.history || [];
            var totalItems = reqData.length;
            var verifiedCount = 0;
            var pendingCount = 0;
            var missingCount = 0;
            var statusMap = {};
            var remarksMap = {};
            reqData.forEach(function(r){
                statusMap[r.requirement_id] = r.status || 'Not Submitted';
                remarksMap[r.requirement_id] = r.remarks || '';
                if (r.status === 'Verified') verifiedCount++;
                else if (r.status === 'Missing') missingCount++;
                else if (r.status === 'Not Submitted') pendingCount++;
                else pendingCount++;
            });
            var pct = totalItems > 0 ? Math.round((verifiedCount/totalItems)*100) : 0;

            document.getElementById('reqAppName').textContent = info.full_name || '-';
            document.getElementById('reqAppNo').textContent = info.application_number || '-';
            document.getElementById('reqAppPhone').textContent = info.phone || '-';
            document.getElementById('reqAppStatus').textContent = info.status || '-';
            document.getElementById('reqAppStatus').className = 'badge bg-'+(info.status==='Approved'?'success':(info.status==='Rejected'?'danger':(info.status==='Registered'?'info':'secondary')));
            document.getElementById('reqAppInfoCard').style.display = '';

            var arc = document.getElementById('reqArc');
            var circumference = 2 * Math.PI * 26;
            arc.style.strokeDasharray = circumference;
            arc.style.strokeDashoffset = circumference - (pct/100) * circumference;
            document.getElementById('reqPct').textContent = pct + '%';
            document.getElementById('reqBar').style.width = pct + '%';
            document.getElementById('reqBadgeVerified').textContent = verifiedCount + ' Verified';
            document.getElementById('reqBadgePending').textContent = (totalItems - verifiedCount - missingCount) + ' Pending';
            document.getElementById('reqBadgeMissing').textContent = missingCount + ' Missing';

            document.getElementById('reqDetailTabs').style.display = '';
            document.getElementById('reqDetailContent').style.display = '';

            // Checklist tab
            var ch = '<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">'+
                '<span class="fw-bold small">CHECKLIST ('+totalItems+' items)</span>'+
                '<div class="d-flex gap-1">'+
                '<button class="btn btn-sm btn-success py-0" onclick="markAllSub('+aid+')"><i class="fas fa-check-double me-1"></i>All Submitted</button>'+
                '<button class="btn btn-sm btn-outline-secondary py-0" onclick="resetReq('+aid+')"><i class="fas fa-undo me-1"></i>Reset</button>'+
                '</div></div>'+
                '<div class="row g-1">';
            reqData.forEach(function(r){
                var sts = statusMap[r.requirement_id] || 'Not Submitted';
                var bg = sts==='Verified'?'bg-success':(sts==='Rejected'?'bg-danger':(sts==='Submitted'?'bg-warning text-dark':(sts==='Missing'?'bg-orange text-dark':'bg-light text-muted border')));
                ch += '<div class="col-md-6 col-lg-4">'+
                    '<div class="req-card py-1 px-2" data-rid="'+r.requirement_id+'">'+
                    '<span class="req-name">'+htmlEsc(r.requirement_name||'')+'</span>'+
                    '<select class="form-select form-select-sm" onchange="toggleReq('+aid+','+r.requirement_id+',this.value)">'+
                    '<option value="Not Submitted"'+(sts==='Not Submitted'?' selected':'')+'>Not Submitted</option>'+
                    '<option value="Submitted"'+(sts==='Submitted'?' selected':'')+'>Submitted</option>'+
                    '<option value="Verified"'+(sts==='Verified'?' selected':'')+'>Verified</option>'+
                    '<option value="Rejected"'+(sts==='Rejected'?' selected':'')+'>Rejected</option>'+
                    '<option value="Missing"'+(sts==='Missing'?' selected':'')+'>Missing</option>'+
                    '</select>'+
                    '<input type="text" class="req-remarks-input" placeholder="Remarks..." value="'+htmlEsc(remarksMap[r.requirement_id]||'')+'" onblur="setRemarks('+aid+','+r.requirement_id+',this.value)">'+
                    '</div></div>';
            });
            ch += '</div>';
            document.getElementById('reqChecklistTab').innerHTML = ch;

            // History tab
            var hh = '<div style="max-height:250px;overflow-y:auto">';
            if (history.length) {
                history.forEach(function(ev){
                    hh += '<div class="req-hist-item"><span class="badge bg-'+ (ev.action==='Verified'?'success':(ev.action==='Rejected'?'danger':(ev.action==='Submitted'?'warning text-dark':'secondary'))) +'">'+ev.action+'</span>'+
                        '<span style="flex:1">'+htmlEsc(ev.remarks||'')+'</span>'+
                        '<small class="text-muted" style="white-space:nowrap">'+(ev.performed_by_name ? htmlEsc(ev.performed_by_name)+' &middot; ' : '')+(ev.created_at ? new Date(ev.created_at).toLocaleString() : '')+'</small></div>';
                });
            } else {
                hh += '<p class="text-muted small">No history yet.</p>';
            }
            hh += '</div>';
            document.getElementById('reqHistoryTab').innerHTML = hh;
            list.innerHTML = '';
        })
        .catch(function(){
            list.innerHTML = '<div class="alert alert-danger text-center m-3">Failed to load applicant data.</div>';
        });
}

function toggleReq(aid, rid, status){
    var fd = new FormData();
    fd.append('applicant_id', aid);
    fd.append('requirement_id', rid);
    fd.append('status', status);
    fetch('director-admissions.php?ajax=toggle_requirement', { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) {
                showToast('Requirement updated to: ' + status, 'info');
                loadReq(aid);
            } else { showToast('Failed to update', 'error'); }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

function setRemarks(aid, rid, remarks){
    var fd = new FormData();
    fd.append('applicant_id', aid);
    fd.append('requirement_id', rid);
    fd.append('status', document.querySelector('.req-card[data-rid="'+rid+'"] select')?.value || 'Not Submitted');
    fd.append('remarks', remarks);
    fetch('director-admissions.php?ajax=toggle_requirement', { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .catch(function(){});
}

function markAllSub(aid){
    if (!confirm('Mark all requirements as Submitted?')) return;
    var fd = new FormData();
    fd.append('applicant_id', aid);
    fetch('director-admissions.php?ajax=mark_all_submitted', { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) { showToast('All marked as submitted', 'success'); loadReq(aid); }
            else { showToast('Failed', 'error'); }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

function resetReq(aid){
    if (!confirm('Reset all requirements for this applicant?')) return;
    var fd = new FormData();
    fd.append('applicant_id', aid);
    fetch('director-admissions.php?ajax=reset_requirements', { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) { showToast('All requirements reset', 'info'); loadReq(aid); }
            else { showToast('Failed', 'error'); }
        })
        .catch(function(){ showToast('Network error', 'error'); });
}

// ── Utility ──
function htmlEsc(s){
    if (typeof s !== 'string') return String(s || '');
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
