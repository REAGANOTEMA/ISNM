<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['hr', 'manager', 'director']);
$staffDb = $ctx['staff'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';

$search = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$total = 0; $active = 0; $expiring = 0; $expired = 0;
$contracts = []; $staffList = [];

if ($staffDb) {
    $total = (int)(($r=$staffDb->query("SELECT COUNT(*) c FROM staff_contracts"))&&$r?$r->fetch_assoc()['c']:0);
    $active = (int)(($r=$staffDb->query("SELECT COUNT(*) c FROM staff_contracts WHERE status='Active'"))&&$r?$r->fetch_assoc()['c']:0);
    $expiring = (int)(($r=$staffDb->query("SELECT COUNT(*) c FROM staff_contracts WHERE status='Active' AND end_date IS NOT NULL AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)"))&&$r?$r->fetch_assoc()['c']:0);
    $expired = (int)(($r=$staffDb->query("SELECT COUNT(*) c FROM staff_contracts WHERE status='Expired'"))&&$r?$r->fetch_assoc()['c']:0);

    $where = [];
    if ($search) $where[] = "(s.full_name LIKE '%".$staffDb->real_escape_string($search)."%' OR c.contract_number LIKE '%".$staffDb->real_escape_string($search)."%')";
    if ($filterType) $where[] = "c.contract_type='".$staffDb->real_escape_string($filterType)."'";
    if ($filterStatus) $where[] = "c.status='".$staffDb->real_escape_string($filterStatus)."'";
    $ws = $where ? 'WHERE '.implode(' AND ', $where) : '';
    $r = $staffDb->query("SELECT c.*, s.full_name, s.staff_id, s.department sd, s.position FROM staff_contracts c LEFT JOIN staff s ON c.staff_id=s.id $ws ORDER BY c.created_at DESC LIMIT 100");
    if ($r) while ($row = $r->fetch_assoc()) $contracts[] = $row;

    $sl = $staffDb->query("SELECT id, staff_id, full_name, department, position FROM staff WHERE status='Active' ORDER BY full_name");
    if ($sl) while ($row = $sl->fetch_assoc()) $staffList[] = $row;
}

// POST handlers
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($staffDb && $action==='add_contract') {
        $staff_id = (int)($_POST['staff_id']??0);
        $type = $staffDb->real_escape_string($_POST['contract_type']??'');
        $start = $staffDb->real_escape_string($_POST['start_date']??'');
        $end = $staffDb->real_escape_string($_POST['end_date']??'');
        $job = $staffDb->real_escape_string($_POST['job_title']??'');
        $dept = $staffDb->real_escape_string($_POST['department']??'');
        $salary = floatval($_POST['salary']??0);
        $probation = (int)($_POST['probation_period']??6);
        $notice = (int)($_POST['notice_period']??30);
        $terms = $staffDb->real_escape_string($_POST['contract_terms']??'');
        $benefits = $staffDb->real_escape_string($_POST['benefits']??'');
        $signed = $staffDb->real_escape_string($_POST['signed_date']??'');
        $cnum = 'CTR-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
        if ($staff_id && $type && $start && $job) {
            $stmt = $staffDb->prepare("INSERT INTO staff_contracts (contract_number,staff_id,contract_type,start_date,end_date,job_title,department,salary,probation_period,notice_period,contract_terms,benefits,signed_date,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Active')");
            if ($stmt) {
                $stmt->bind_param('sisssssdiisss',$cnum,$staff_id,$type,$start,$end,$job,$dept,$salary,$probation,$notice,$terms,$benefits,$signed);
                $stmt->execute();
                $eid = $stmt->insert_id;
                $stmt->close();
                if (function_exists('logActivity')) logActivity($staffDb, $user['id']??0, "Added contract $cnum for staff ID $staff_id");
                $_SESSION['success'] = "Contract $cnum created successfully.";
            }
        }
        header('Location: contracts-management.php'); exit;
    }
    if ($staffDb && $action==='terminate_contract') {
        $cid = (int)($_POST['contract_id']??0);
        if ($cid) {
            $staffDb->query("UPDATE staff_contracts SET status='Terminated', updated_at=NOW() WHERE id=$cid");
            if (function_exists('logActivity')) logActivity($staffDb, $user['id']??0, "Terminated contract #$cid");
            $_SESSION['success'] = 'Contract terminated.';
        }
        header('Location: contracts-management.php'); exit;
    }
    if ($staffDb && $action==='renew_contract') {
        $cid = (int)($_POST['contract_id']??0);
        $newEnd = $staffDb->real_escape_string($_POST['new_end_date']??'');
        if ($cid && $newEnd) {
            $staffDb->query("UPDATE staff_contracts SET status='Renewed', updated_at=NOW() WHERE id=$cid");
            $r = $staffDb->query("SELECT * FROM staff_contracts WHERE id=$cid");
            $old = $r ? $r->fetch_assoc() : null;
            if ($old) {
                $stmt = $staffDb->prepare("INSERT INTO staff_contracts (contract_number,staff_id,contract_type,start_date,end_date,job_title,department,salary,probation_period,notice_period,contract_terms,benefits,signed_date,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'Active')");
                if ($stmt) {
                    $newNum = 'CTR-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
                    $stmt->bind_param('sisssssdiisss',$newNum,$old['staff_id'],$old['contract_type'],date('Y-m-d'),$newEnd,$old['job_title'],$old['department'],$old['salary'],$old['probation_period'],$old['notice_period'],$old['contract_terms'],$old['benefits'],date('Y-m-d'));
                    $stmt->execute();
                    $stmt->close();
                }
            }
            if (function_exists('logActivity')) logActivity($staffDb, $user['id']??0, "Renewed contract #$cid");
            $_SESSION['success'] = 'Contract renewed with new end date.';
        }
        header('Location: contracts-management.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.contract-card { border-left:4px solid #6c5ce7;transition:transform .15s; }
.contract-card:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08); }
.status-active { border-left-color:#00b894; }
.status-expiring { border-left-color:#fdcb6e; }
.status-expired { border-left-color:#d63031; }
.status-renewed { border-left-color:#0984e3; }
.status-terminated { border-left-color:#636e72; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-file-contract me-2"></i>Contract Management</h4>
            <div>
                <span class="text-muted small me-3"><?= date('l, d M Y') ?></span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContractModal"><i class="fas fa-plus me-1"></i>New Contract</button>
            </div>
        </div>

        <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show mt-3"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>
        <?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger alert-dismissible fade show mt-3"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']); endif; ?>

        <div class="row g-3 mt-2">
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-primary mb-2"><i class="fas fa-file-contract"></i></div><h3 class="fw-bold mb-0"><?= $total ?></h3><small class="text-muted">Total Contracts</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div><h3 class="fw-bold mb-0"><?= $active ?></h3><small class="text-muted">Active</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-warning mb-2"><i class="fas fa-exclamation-triangle"></i></div><h3 class="fw-bold mb-0"><?= $expiring ?></h3><small class="text-muted">Expiring Soon (60d)</small></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><div class="fs-1 text-danger mb-2"><i class="fas fa-clock"></i></div><h3 class="fw-bold mb-0"><?= $expired ?></h3><small class="text-muted">Expired</small></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Contracts Register</h5>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Search contract # or staff..." value="<?= htmlspecialchars($search) ?>">
                    <select name="type" class="form-select form-select-sm" style="width:auto">
                        <option value="">All Types</option>
                        <?php foreach(['Permanent','Probation','Fixed Term','Contract','Consultancy','Internship'] as $t): ?>
                        <option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width:auto">
                        <option value="">All Status</option>
                        <?php foreach(['Active','Expired','Terminated','Suspended','Renewed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
                    <a href="contracts-management.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if (empty($contracts)): ?>
                <div class="text-center py-5"><i class="fas fa-file-contract fa-3x mb-3 text-muted" style="opacity:.3;"></i><p class="text-muted">No contracts found. Create your first contract.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Contract #</th><th>Staff</th><th>Type</th><th>Job Title</th><th>Start</th><th>End</th><th>Salary</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($contracts as $c):
                            $sc = strtolower($c['status']??'Active');
                            $badge = match($sc) {
                                'active' => 'bg-success', 'expired' => 'bg-secondary',
                                'terminated' => 'bg-danger', 'suspended' => 'bg-warning text-dark',
                                'renewed' => 'bg-info', default => 'bg-primary'
                            };
                            $isExpiring = $sc==='active' && $c['end_date'] && strtotime($c['end_date']) <= strtotime('+60 days');
                        ?>
                            <tr class="contract-card status-<?= $isExpiring ? 'expiring' : $sc ?>">
                                <td><code><?= htmlspecialchars($c['contract_number']) ?></code></td>
                                <td><strong><?= htmlspecialchars($c['full_name']??'Unknown') ?></strong><br><small class="text-muted"><?= htmlspecialchars($c['staff_id']??'') ?></small></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($c['contract_type']) ?></span></td>
                                <td><?= htmlspecialchars($c['job_title']) ?></td>
                                <td><?= $c['start_date'] ? date('d M Y',strtotime($c['start_date'])) : '-' ?></td>
                                <td><?= $c['end_date'] ? date('d M Y',strtotime($c['end_date'])).($isExpiring ? ' <i class="fas fa-exclamation-circle text-warning" title="Expiring soon"></i>' : '') : 'Open-ended' ?></td>
                                <td><?= htmlspecialchars($c['currency']??'UGX') ?> <?= number_format((float)($c['salary']??0),0) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="viewContract(<?= $c['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                    <?php if ($sc==='Active'): ?>
                                    <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="renewContract(<?= $c['id'] ?>,'<?= htmlspecialchars($c['contract_number']) ?>')" title="Renew"><i class="fas fa-sync-alt"></i></button>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="terminateContract(<?= $c['id'] ?>,'<?= htmlspecialchars($c['contract_number']) ?>')" title="Terminate"><i class="fas fa-ban"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="fw-bold mb-0"><i class="fas fa-clock me-2"></i>Expiring Within 60 Days</h6></div>
                <div class="card-body">
                    <?php $expR = $staffDb ? $staffDb->query("SELECT c.*,s.full_name,s.staff_id,s.phone FROM staff_contracts c LEFT JOIN staff s ON c.staff_id=s.id WHERE c.status='Active' AND c.end_date IS NOT NULL AND c.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) ORDER BY c.end_date LIMIT 10") : null; ?>
                    <?php if ($expR && $expR->num_rows): $first=true; while ($e = $expR->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 <?= $first?'':'mt-1' ?>">
                        <div><strong><?= htmlspecialchars($e['full_name']??'') ?></strong><br><small class="text-muted"><?= htmlspecialchars($e['contract_number']) ?> — <?= htmlspecialchars($e['contract_type']) ?></small></div>
                        <div class="text-end"><span class="badge bg-warning text-dark"><?= date('d M Y',strtotime($e['end_date'])) ?></span><br><small class="text-muted"><?= ceil((strtotime($e['end_date'])-time())/86400) ?> days left</small></div>
                    </div>
                    <?php $first=false; endwhile; else: ?>
                    <p class="text-muted small text-center py-3"><i class="fas fa-check-circle text-success me-2"></i>No contracts expiring within 60 days.</p>
                    <?php endif; ?>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2"></i>Contract Type Distribution</h6></div>
                <div class="card-body">
                    <?php $typeR = $staffDb ? $staffDb->query("SELECT contract_type, COUNT(*) cnt FROM staff_contracts GROUP BY contract_type ORDER BY cnt DESC") : null; ?>
                    <?php if ($typeR && $typeR->num_rows): ?>
                    <div class="d-flex flex-wrap gap-3 justify-content-center py-3">
                        <?php $colors=['#6c5ce7','#00b894','#fdcb6e','#d63031','#0984e3','#636e72']; $i=0; while ($t = $typeR->fetch_assoc()): ?>
                        <div class="text-center"><div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px;background:<?= $colors[$i%count($colors)] ?>20;color:<?= $colors[$i%count($colors)] ?>;font-size:20px;font-weight:700;"><?= (int)$t['cnt'] ?></div><br><small class="text-muted"><?= htmlspecialchars($t['contract_type']) ?></small></div>
                        <?php $i++; endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small text-center py-3">No contracts recorded.</p>
                    <?php endif; ?>
                </div></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Contract Modal -->
<div class="modal fade" id="addContractModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content"><input type="hidden" name="action" value="add_contract">
<div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>New Contract</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="row g-3">
    <div class="col-md-6"><label class="form-label fw-semibold">Staff *</label><select name="staff_id" class="form-select" required><option value="">Select Staff</option><?php foreach($staffList as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name'].' ('.$s['staff_id'].')') ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Contract Type *</label><select name="contract_type" class="form-select" required><?php foreach(['Permanent','Probation','Fixed Term','Contract','Consultancy','Internship'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label fw-semibold">Job Title *</label><input type="text" name="job_title" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Start Date *</label><input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
    <div class="col-md-4"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Salary (UGX)</label><input type="number" name="salary" class="form-control" step="0.01"></div>
    <div class="col-md-4"><label class="form-label">Probation (months)</label><input type="number" name="probation_period" class="form-control" value="6"></div>
    <div class="col-md-4"><label class="form-label">Notice Period (days)</label><input type="number" name="notice_period" class="form-control" value="30"></div>
    <div class="col-md-4"><label class="form-label">Signed Date</label><input type="date" name="signed_date" class="form-control"></div>
    <div class="col-12"><label class="form-label">Contract Terms</label><textarea name="contract_terms" class="form-control" rows="3"></textarea></div>
    <div class="col-12"><label class="form-label">Benefits</label><textarea name="benefits" class="form-control" rows="2" placeholder="Housing, transport, medical..."></textarea></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Contract</button></div>
</form></div></div>

<!-- Renew Modal -->
<div class="modal fade" id="renewModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="renew_contract"><input type="hidden" name="contract_id" id="renewId">
<div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Renew Contract <span id="renewNum"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><label class="form-label fw-semibold">New End Date *</label><input type="date" name="new_end_date" id="renewEndDate" class="form-control" required></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Renew</button></div>
</form></div></div>

<!-- Terminate Modal -->
<div class="modal fade" id="terminateModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content"><input type="hidden" name="action" value="terminate_contract"><input type="hidden" name="contract_id" id="termId">
<div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-ban me-2"></i>Terminate Contract <span id="termNum"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Are you sure you want to terminate this contract? This action cannot be undone.</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="fas fa-check me-1"></i>Terminate</button></div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewContract(id) { alert('View contract #' + id + ' — full detail view coming with document generation.'); }
function renewContract(id, num) { document.getElementById('renewId').value = id; document.getElementById('renewNum').textContent = num; new bootstrap.Modal(document.getElementById('renewModal')).show(); }
function terminateContract(id, num) { document.getElementById('termId').value = id; document.getElementById('termNum').textContent = num; new bootstrap.Modal(document.getElementById('terminateModal')).show(); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
