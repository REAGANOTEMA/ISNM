<?php
/**
 * Approval Integration Hub
 * Bridges the approval_workflow system with application entities:
 *   - store_requests → approval_requests (DG final approval)
 *   - pending_students → approval_requests (DG final approval)
 *   - General approvals
 */

if (!function_exists('submitStoreForApproval')) {
function submitStoreForApproval($storeReqId, $conn = null) {
    if (!$conn) { if (function_exists('getStaffConnection')) $conn = getStaffConnection(); }
    if (!$conn) return false;
    try {
        $sr = $conn->query("SELECT sr.*, s.full_name, s.position FROM store_requests sr LEFT JOIN staff s ON sr.requested_by=s.id WHERE sr.id = " . (int)$storeReqId);
        if (!$sr || !($req = $sr->fetch_assoc())) return false;

        $title = 'Store Requisition: ' . ($req['request_number'] ?? '#' . $storeReqId);
        $desc = ($req['items'] ? substr(strip_tags($req['items']), 0, 200) : 'Items requested') . ' | ' . ($req['department'] ?? 'General');
        $priority = ucfirst(strtolower($req['urgency'] ?? 'Medium'));

        $wfId = 0;
        $wf = $conn->query("SELECT id FROM approval_workflows WHERE workflow_name='Store Requisition' AND is_active=1 LIMIT 1");
        if ($wf && ($w = $wf->fetch_assoc())) $wfId = (int)$w['id'];
        if (!$wfId) return false;

        $result = createApprovalRequest($wfId, $title, $desc, (int)$req['requested_by'], $req['full_name'] ?? 'Unknown', $req['position'] ?? 'Staff', $priority, 'store_requests', (int)$storeReqId, '../dashboards/storekeeper.php', $conn);
        if ($result) {
            $ar = $conn->query("SELECT id FROM approval_requests WHERE reference_type='store_requests' AND reference_id=" . (int)$storeReqId . " AND status='Active' ORDER BY id DESC LIMIT 1");
            if ($ar && ($a = $ar->fetch_assoc())) {
                $conn->query("UPDATE store_requests SET approval_request_id=" . (int)$a['id'] . ", status='pending_approval', forwarded_to=0, forwarded_to_role='Director General' WHERE id=" . (int)$storeReqId);
            }
        }
        return $result;
    } catch (Exception $e) { error_log('submitStoreForApproval: ' . $e->getMessage()); return false; }
}
}

if (!function_exists('submitStudentForApproval')) {
function submitStudentForApproval($pendingId, $conn = null) {
    if (!$conn) { if (function_exists('getStaffConnection')) $conn = getStaffConnection(); }
    if (!$conn) return false;
    try {
        $ps = $conn->query("SELECT * FROM pending_students WHERE id = " . (int)$pendingId);
        if (!$ps || !($s = $ps->fetch_assoc())) return false;

        $title = 'New Student: ' . ($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '');
        $desc = 'Registration for ' . ($s['program'] ?? 'N/A') . ' (' . ($s['student_number'] ?? '') . ')';
        $requesterId = (int)($s['submitted_by'] ?? 0);
        $requesterName = 'Registrar';
        if ($requesterId) {
            $st = $conn->query("SELECT full_name, position FROM staff WHERE id=$requesterId LIMIT 1");
            if ($st && ($r = $st->fetch_assoc())) { $requesterName = $r['full_name']; }
        }

        $wfId = 0;
        $wf = $conn->query("SELECT id FROM approval_workflows WHERE workflow_name='Student Registration' AND is_active=1 LIMIT 1");
        if ($wf && ($w = $wf->fetch_assoc())) $wfId = (int)$w['id'];
        if (!$wfId) return false;

        $result = createApprovalRequest($wfId, $title, $desc, $requesterId, $requesterName, 'Academic Registrar', 'Normal', 'pending_students', (int)$pendingId, '../dashboards/director-general.php', $conn);
        if ($result) {
            $ar = $conn->query("SELECT id FROM approval_requests WHERE reference_type='pending_students' AND reference_id=" . (int)$pendingId . " AND status='Active' ORDER BY id DESC LIMIT 1");
            if ($ar && ($a = $ar->fetch_assoc())) {
                $conn->query("UPDATE pending_students SET approval_request_id=" . (int)$a['id'] . " WHERE id=" . (int)$pendingId);
            }
        }
        return $result;
    } catch (Exception $e) { error_log('submitStudentForApproval: ' . $e->getMessage()); return false; }
}
}

if (!function_exists('processStoreApproval')) {
function processStoreApproval($approvalRequestId, $actionType, $comments = '', $conn = null) {
    if (!$conn) { if (function_exists('getStaffConnection')) $conn = getStaffConnection(); }
    if (!$conn) return false;
    try {
        $ar = $conn->query("SELECT * FROM approval_requests WHERE id=" . (int)$approvalRequestId . " AND reference_type='store_requests'");
        if (!$ar || !($req = $ar->fetch_assoc())) return false;
        $storeReqId = (int)$req['reference_id'];
        if ($actionType === 'approve') {
            $conn->query("UPDATE store_requests SET status='approved', approved_by=" . (int)($req['final_approval_by'] ?? 0) . ", notes=CONCAT(COALESCE(notes,''),'\n[DG Approved: " . $conn->real_escape_string($comments) . "]') WHERE id=$storeReqId");
        } elseif ($actionType === 'reject') {
            $reason = $conn->real_escape_string($comments ?: 'Rejected by Director General');
            $conn->query("UPDATE store_requests SET status='rejected', rejection_reason='$reason' WHERE id=$storeReqId");
        }
        return true;
    } catch (Exception $e) { error_log('processStoreApproval: ' . $e->getMessage()); return false; }
}
}

if (!function_exists('processStudentApproval')) {
function processStudentApproval($approvalRequestId, $actionType, $comments = '', $conn = null, $studentsConn = null) {
    if (!$conn) { if (function_exists('getStaffConnection')) $conn = getStaffConnection(); }
    if (!$studentsConn) { if (function_exists('getStudentsConnection')) $studentsConn = getStudentsConnection(); }
    if (!$conn || !$studentsConn) return false;
    try {
        $ar = $conn->query("SELECT * FROM approval_requests WHERE id=" . (int)$approvalRequestId . " AND reference_type='pending_students'");
        if (!$ar || !($req = $ar->fetch_assoc())) return false;
        $pendingId = (int)$req['reference_id'];
        $ps = $conn->query("SELECT * FROM pending_students WHERE id=$pendingId");
        if (!$ps || !($s = $ps->fetch_assoc())) return false;

        if ($actionType === 'approve') {
            $fullName = trim(($s['first_name']??'') . ' ' . ($s['middle_name']??'') . ' ' . ($s['last_name']??''));
            $intakeDate = ($s['intake_year'] ?? date('Y')) . '-' . (($s['intake_period'] ?? 'January') === 'July' ? '07' : '01') . '-01';
            $stmt = $studentsConn->prepare("INSERT IGNORE INTO students (student_number, first_name, surname, full_name, program, level, intake_date, phone, email, date_of_birth, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            if ($stmt) {
                $fn = $s['first_name']; $ln = $s['last_name']; $sn = $s['student_number'];
                $prog = $s['program']; $lv = $s['level'] ?? '1'; $ph = $s['phone'] ?? ''; $em = $s['email'] ?? ''; $dob = $s['date_of_birth'] ?? '';
                $stmt->bind_param("ssssssssss", $sn, $fn, $ln, $fullName, $prog, $lv, $intakeDate, $ph, $em, $dob);
                $stmt->execute();
                $stmt->close();
            }
            $conn->query("UPDATE pending_students SET status='approved' WHERE id=$pendingId");
        } elseif ($actionType === 'reject') {
            $reason = $conn->real_escape_string($comments ?: 'Rejected by Director General');
            $conn->query("UPDATE pending_students SET status='rejected', rejection_reason='$reason' WHERE id=$pendingId");
        }
        return true;
    } catch (Exception $e) { error_log('processStudentApproval: ' . $e->getMessage()); return false; }
}
}

if (!function_exists('overrideApprovalActionHandler')) {
/**
 * Enhanced approval action handler that also processes entity-specific side effects.
 * Drop-in replacement for registerApprovalActionHandler().
 */
function overrideApprovalActionHandler() {
    if (isset($GLOBALS['_approval_override_registered'])) return;
    $GLOBALS['_approval_override_registered'] = true;
    ?>
    <script>
    window.submitApprovalAction = function(requestId, action) {
        var comments = prompt("Enter comments for " + action + ":");
        if (comments === null) return;
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../ajax/approval_action.php?override=1", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) { location.reload(); }
                    else { alert("Error: " + (resp.error || "Unknown error")); }
                } catch(e) { location.reload(); }
            }
        };
        xhr.onerror = function() { alert("Network error submitting approval action."); };
        xhr.send("request_id=" + requestId + "&action=" + action + "&comments=" + encodeURIComponent(comments));
    };
    </script>
    <?php
}
}

if (!function_exists('renderApprovalTabs')) {
function renderApprovalTabs($conn, $studentsConn) {
    $pa = getPendingApprovals($conn, null, 50);
    $storeApprovals = []; $studentApprovals = []; $generalApprovals = [];
    foreach ($pa as $a) {
        $cat = $a['workflow_category'] ?? '';
        if ($cat === 'Store') $storeApprovals[] = $a;
        elseif ($cat === 'Academic') $studentApprovals[] = $a;
        else $generalApprovals[] = $a;
    }
    $allCount = count($pa);
    $storeCount = count($storeApprovals);
    $studentCount = count($studentApprovals);
    $genCount = count($generalApprovals);

    ob_start();
    ?>
    <div class="approval-hub">
      <ul class="nav nav-tabs nav-fill mb-3" style="border-bottom:2px solid #e2e8f0;gap:0;" id="approvalTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" style="font-size:12px;font-weight:600;padding:8px 12px;border:none;border-bottom:3px solid #2563eb;color:#2563eb;background:transparent;" data-bs-toggle="tab" data-bs-target="#tabAll" type="button">
            All <span class="badge bg-primary ms-1" style="font-size:10px;"><?= $allCount ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" style="font-size:12px;font-weight:600;padding:8px 12px;border:none;border-bottom:3px solid transparent;color:#64748b;background:transparent;" data-bs-toggle="tab" data-bs-target="#tabStore" type="button">
            <i class="fas fa-shopping-cart me-1" style="color:#d97706;"></i> Store <span class="badge bg-warning text-dark ms-1" style="font-size:10px;"><?= $storeCount ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" style="font-size:12px;font-weight:600;padding:8px 12px;border:none;border-bottom:3px solid transparent;color:#64748b;background:transparent;" data-bs-toggle="tab" data-bs-target="#tabStudents" type="button">
            <i class="fas fa-user-graduate me-1" style="color:#059669;"></i> Students <span class="badge bg-success ms-1" style="font-size:10px;"><?= $studentCount ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" style="font-size:12px;font-weight:600;padding:8px 12px;border:none;border-bottom:3px solid transparent;color:#64748b;background:transparent;" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
            <i class="fas fa-file-contract me-1" style="color:#3b82f6;"></i> General <span class="badge bg-info ms-1" style="font-size:10px;"><?= $genCount ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="tabAll">
          <?= renderApprovalList($pa, $conn) ?>
        </div>
        <div class="tab-pane fade" id="tabStore">
          <?= renderApprovalList($storeApprovals, $conn) ?>
        </div>
        <div class="tab-pane fade" id="tabStudents">
          <?= renderApprovalList($studentApprovals, $conn, 'student') ?>
        </div>
        <div class="tab-pane fade" id="tabGeneral">
          <?= renderApprovalList($generalApprovals, $conn) ?>
        </div>
      </div>
    </div>
    <style>
    .approval-hub .nav-tabs .nav-link:hover { color: #1e293b; border-bottom-color: #94a3b8; }
    .approval-hub .tab-pane { animation: fadeInUp 0.3s ease; }
    </style>
    <?php
    return ob_get_clean();
}
}

if (!function_exists('renderApprovalList')) {
function renderApprovalList($approvals, $conn, $context = '') {
    if (empty($approvals)) {
        return '<div class="text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-2" style="color:#10b981;"></i><div class="fw-semibold">All clear!</div><div style="font-size:13px;">No pending items in this category.</div></div>';
    }
    $html = '<div class="row g-2">';
    foreach ($approvals as $a) {
        $pc = 'bg-info'; $ic = 'info';
        if ($a['priority'] === 'Critical' || $a['priority'] === 'Urgent') { $pc = 'bg-danger'; $ic = 'danger'; }
        elseif ($a['priority'] === 'High') { $pc = 'bg-warning text-dark'; $ic = 'warning'; }
        elseif ($a['priority'] === 'Low') { $pc = 'bg-secondary'; $ic = 'secondary'; }

        $catIcon = 'fa-file-alt';
        $catColor = '#6b7280';
        $cat = $a['workflow_category'] ?? '';
        if ($cat === 'Store') { $catIcon = 'fa-shopping-cart'; $catColor = '#d97706'; }
        elseif ($cat === 'Academic') { $catIcon = 'fa-user-graduate'; $catColor = '#059669'; }

        $refUrl = $a['reference_url'] ? htmlspecialchars($a['reference_url']) : '#';

        $html .= '<div class="col-md-6 col-lg-4">';
        $html .= '<div class="approval-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px;transition:all 0.2s;height:100%;position:relative;">';
        $html .= '  <div class="d-flex justify-content-between align-items-start mb-2">';
        $html .= '    <div class="d-flex align-items-center gap-2">';
        $html .= '      <div style="width:32px;height:32px;border-radius:8px;background:' . $catColor . '15;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas ' . $catIcon . '" style="color:' . $catColor . ';font-size:13px;"></i></div>';
        $html .= '      <div><span class="badge ' . $pc . '" style="font-size:9px;font-weight:500;">' . htmlspecialchars($a['priority']) . '</span> <span class="badge bg-light text-dark" style="font-size:9px;font-weight:500;">' . htmlspecialchars($a['workflow_category'] ?? 'General') . '</span></div>';
        $html .= '    </div>';
        $html .= '    <code style="font-size:9px;color:#94a3b8;">#' . htmlspecialchars($a['request_number']) . '</code>';
        $html .= '  </div>';
        $html .= '  <h6 style="font-size:13px;font-weight:700;margin-bottom:4px;">' . htmlspecialchars($a['title']) . '</h6>';
        if ($a['description']) $html .= '  <p style="font-size:11px;color:#64748b;margin-bottom:8px;">' . htmlspecialchars(substr($a['description'], 0, 120)) . '</p>';
        $html .= '  <div class="d-flex justify-content-between align-items-center" style="font-size:11px;">';
        $html .= '    <span style="color:#64748b;"><i class="fas fa-user me-1"></i>' . htmlspecialchars($a['requester_name']) . '</span>';
        $html .= '    <span class="badge bg-primary" style="font-size:9px;font-weight:500;">' . htmlspecialchars($a['current_stage_name'] ?? 'Pending') . '</span>';
        $html .= '  </div>';
        $html .= '  <div style="font-size:10px;color:#94a3b8;margin-top:6px;">' . date('d M Y H:i', strtotime($a['created_at'])) . '</div>';
        $html .= '  <div class="mt-2 d-flex gap-1">';
        $html .= '    <button class="btn btn-sm btn-success" onclick="window.submitApprovalAction(' . (int)$a['id'] . ',\'approve\')" style="font-size:10px;padding:3px 10px;border-radius:6px;"><i class="fas fa-check me-1"></i>Approve</button>';
        $html .= '    <button class="btn btn-sm btn-danger" onclick="window.submitApprovalAction(' . (int)$a['id'] . ',\'reject\')" style="font-size:10px;padding:3px 10px;border-radius:6px;"><i class="fas fa-times me-1"></i>Reject</button>';
        if ($refUrl !== '#') $html .= '    <a href="' . $refUrl . '" class="btn btn-sm btn-outline-secondary" style="font-size:10px;padding:3px 8px;border-radius:6px;" target="_blank"><i class="fas fa-external-link-alt"></i></a>';
        $html .= '  </div>';
        $html .= '</div></div>';
    }
    $html .= '</div>';
    return $html;
}
}
