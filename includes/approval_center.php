<?php
/**
 * Enterprise Director General Approval Center
 * Centralized approval authority for all institution requests.
 * Integrates with existing approval_workflow, approval_requests, approval_actions tables.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Get counts for summary cards ───
if (!function_exists('getApprovalCenterCounts')) {
function getApprovalCenterCounts($conn) {
    $counts = [
        'pending' => 0, 'approved_today' => 0, 'rejected_today' => 0,
        'critical' => 0, 'returned' => 0, 'total' => 0
    ];
    if (!$conn) return $counts;
    try {
        $r = $conn->query("SELECT 
            SUM(status='Active') as pending,
            SUM(status='Approved' AND DATE(final_approval_at)=CURDATE()) as approved_today,
            SUM(status='Rejected' AND DATE(updated_at)=CURDATE()) as rejected_today,
            SUM(status='Active' AND (priority='Critical' OR priority='Urgent')) as critical
            FROM igangaschoolofl_staffs_db.approval_requests");
        if ($r && $row = $r->fetch_assoc()) {
            $counts['pending'] = (int)$row['pending'];
            $counts['approved_today'] = (int)$row['approved_today'];
            $counts['rejected_today'] = (int)$row['rejected_today'];
            $counts['critical'] = (int)$row['critical'];
        }
        $r2 = $conn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.approval_requests");
        if ($r2) $counts['total'] = (int)$r2->fetch_assoc()['c'];
        $r3 = $conn->query("SELECT COUNT(*) as c FROM igangaschoolofl_staffs_db.approval_requests WHERE status='Active' AND (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_actions WHERE request_id=approval_requests.id AND action_type='return')>0");
        if ($r3) $counts['returned'] = (int)$r3->fetch_assoc()['c'];
    } catch (Exception $e) {}
    return $counts;
}
}

// ─── Summary Cards ───
if (!function_exists('renderApprovalSummaryCards')) {
function renderApprovalSummaryCards($conn) {
    $c = getApprovalCenterCounts($conn);
    $cards = [
        ['label'=>'Pending Approvals', 'count'=>$c['pending'], 'icon'=>'fas fa-clock', 'color'=>'#3b82f6', 'bg'=>'#eff6ff', 'filter'=>'pending'],
        ['label'=>'Approved Today', 'count'=>$c['approved_today'], 'icon'=>'fas fa-check-circle', 'color'=>'#10b981', 'bg'=>'#ecfdf5', 'filter'=>'approved_today'],
        ['label'=>'Rejected Today', 'count'=>$c['rejected_today'], 'icon'=>'fas fa-times-circle', 'color'=>'#ef4444', 'bg'=>'#fef2f2', 'filter'=>'rejected_today'],
        ['label'=>'Critical', 'count'=>$c['critical'], 'icon'=>'fas fa-exclamation-triangle', 'color'=>'#f59e0b', 'bg'=>'#fffbeb', 'filter'=>'critical'],
        ['label'=>'Returned', 'count'=>$c['returned'], 'icon'=>'fas fa-undo', 'color'=>'#8b5cf6', 'bg'=>'#f5f3ff', 'filter'=>'returned'],
        ['label'=>'All Requests', 'count'=>$c['total'], 'icon'=>'fas fa-file-alt', 'color'=>'#64748b', 'bg'=>'#f8fafc', 'filter'=>'all'],
    ];
    $html = '<div class="row g-3 mb-4" id="approvalSummaryCards">';
    foreach ($cards as $card) {
        $html .= '<div class="col-6 col-md-4 col-lg-2">';
        $html .= '<div class="approval-summary-card" data-filter="'.$card['filter'].'" style="background:'.$card['bg'].';border-radius:12px;padding:16px;cursor:pointer;border:1px solid transparent;transition:all 0.2s;" onclick="filterApprovals(\''.$card['filter'].'\')">';
        $html .= '  <div class="d-flex align-items-center gap-3">';
        $html .= '    <div style="width:40px;height:40px;border-radius:10px;background:'.$card['color'].'20;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="'.$card['icon'].'" style="color:'.$card['color'].';font-size:18px;"></i></div>';
        $html .= '    <div><div style="font-size:22px;font-weight:800;color:#0f172a;line-height:1;">'.$card['count'].'</div><div style="font-size:11px;color:#64748b;font-weight:500;">'.$card['label'].'</div></div>';
        $html .= '  </div></div></div>';
    }
    $html .= '</div>';
    return $html;
}
}

// ─── Fetch approvals with filters ───
if (!function_exists('getApprovalCenterRequests')) {
function getApprovalCenterRequests($conn, $filters = []) {
    $requests = [];
    if (!$conn) return $requests;
    try {
        $sql = "SELECT ar.*, ws.workflow_name, ws.category as workflow_category, 
                (SELECT stage_name FROM igangaschoolofl_staffs_db.approval_stages WHERE id = ar.current_stage_id) as current_stage_name,
                (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.approval_stages WHERE workflow_id = ar.workflow_id) as total_stages
                FROM igangaschoolofl_staffs_db.approval_requests ar
                LEFT JOIN igangaschoolofl_staffs_db.approval_workflows ws ON ar.workflow_id = ws.id
                WHERE 1=1";
        $params = []; $types = '';

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $sql .= " AND ar.status = 'Active' AND ar.current_stage_order = (SELECT MAX(stage_order) FROM igangaschoolofl_staffs_db.approval_stages WHERE workflow_id = ar.workflow_id)";
            } elseif ($filters['status'] === 'active') {
                $sql .= " AND ar.status = 'Active'";
            } elseif ($filters['status'] === 'approved_today') {
                $sql .= " AND ar.status = 'Approved' AND DATE(ar.final_approval_at) = CURDATE()";
            } elseif ($filters['status'] === 'rejected_today') {
                $sql .= " AND ar.status = 'Rejected' AND DATE(ar.updated_at) = CURDATE()";
            } elseif ($filters['status'] === 'critical') {
                $sql .= " AND ar.status = 'Active' AND (ar.priority = 'Critical' OR ar.priority = 'Urgent')";
            } elseif ($filters['status'] === 'returned') {
                $sql .= " AND ar.status = 'Active' AND EXISTS (SELECT 1 FROM igangaschoolofl_staffs_db.approval_actions WHERE request_id = ar.id AND action_type = 'return')";
            } else {
                $sql .= " AND ar.status = ?";
                $params[] = $filters['status']; $types .= 's';
            }
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND ar.priority = ?";
            $params[] = $filters['priority']; $types .= 's';
        }
        if (!empty($filters['category'])) {
            $sql .= " AND ws.category = ?";
            $params[] = $filters['category']; $types .= 's';
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (ar.title LIKE ? OR ar.request_number LIKE ? OR ar.requester_name LIKE ? OR ar.description LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
            $types .= 'ssss';
        }
        $sql .= " ORDER BY FIELD(ar.priority,'Critical','Urgent','High','Medium','Normal','Low'), ar.created_at DESC LIMIT 100";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) $requests[] = $row;
                $stmt->close();
            }
        } else {
            $result = $conn->query($sql);
            if ($result) while ($row = $result->fetch_assoc()) $requests[] = $row;
        }
    } catch (Exception $e) {}
    return $requests;
}
}

// ─── Request Card ───
if (!function_exists('renderApprovalRequestCard')) {
function renderApprovalRequestCard($req) {
    $pc = 'bg-secondary'; $pcolor = '#64748b';
    $priority = $req['priority'] ?? 'Normal';
    if ($priority === 'Critical') { $pc = 'bg-danger'; $pcolor = '#ef4444'; }
    elseif ($priority === 'Urgent') { $pc = 'bg-warning text-dark'; $pcolor = '#f59e0b'; }
    elseif ($priority === 'High') { $pc = 'bg-orange'; $pcolor = '#f97316'; }
    elseif ($priority === 'Medium') { $pc = 'bg-info'; $pcolor = '#3b82f6'; }
    elseif ($priority === 'Low') { $pc = 'bg-secondary'; $pcolor = '#94a3b8'; }

    $status = $req['status'] ?? 'Active';
    $sc = 'bg-secondary'; $slabel = $status;
    if ($status === 'Active') { $sc = 'bg-primary'; $slabel = 'Pending'; }
    elseif ($status === 'Approved') { $sc = 'bg-success'; }
    elseif ($status === 'Rejected') { $sc = 'bg-danger'; }
    elseif ($status === 'Returned') { $sc = 'bg-purple'; }

    $cat = $req['workflow_category'] ?? 'General';
    $catIcon = 'fa-file-alt'; $catColor = '#6b7280';
    if ($cat === 'Store' || $cat === 'Store & Assets') { $catIcon = 'fa-shopping-cart'; $catColor = '#d97706'; }
    elseif ($cat === 'Academic' || $cat === 'Academics') { $catIcon = 'fa-graduation-cap'; $catColor = '#059669'; }
    elseif ($cat === 'Finance') { $catIcon = 'fa-chart-line'; $catColor = '#10b981'; }
    elseif ($cat === 'HR' || $cat === 'Human Resources') { $catIcon = 'fa-users'; $catColor = '#8b5cf6'; }
    elseif ($cat === 'ICT') { $catIcon = 'fa-laptop-code'; $catColor = '#3b82f6'; }
    elseif ($cat === 'Admissions') { $catIcon = 'fa-file-signature'; $catColor = '#7c3aed'; }
    elseif ($cat === 'Library') { $catIcon = 'fa-book'; $catColor = '#06b6d4'; }
    elseif ($cat === 'General Administration') { $catIcon = 'fa-building'; $catColor = '#64748b'; }

    $html = '<div class="approval-card" data-request-id="'.$req['id'].'" data-priority="'.$priority.'" data-status="'.$status.'" data-category="'.$cat.'">';
    $html .= '  <div class="approval-card-header" onclick="openRequestDetail('.$req['id'].')">';
    $html .= '    <div class="d-flex justify-content-between align-items-start mb-2">';
    $html .= '      <div class="d-flex align-items-center gap-2">';
    $html .= '        <div class="approval-cat-icon"><i class="fas '.$catIcon.'"></i></div>';
    $html .= '        <span class="badge priority-badge" style="background:'.$pcolor.'20;color:'.$pcolor.';border:1px solid '.$pcolor.'40;font-size:9px;font-weight:600;">'.$priority.'</span>';
    $html .= '        <span class="badge category-badge" style="background:'.$catColor.'12;color:'.$catColor.';font-size:9px;">'.$cat.'</span>';
    $html .= '      </div>';
    $html .= '      <code class="request-number">#'.htmlspecialchars($req['request_number']).'</code>';
    $html .= '    </div>';
    $html .= '    <h6 class="approval-title">'.htmlspecialchars($req['title']).'</h6>';
    if (!empty($req['description'])) {
        $html .= '    <p class="approval-desc">'.htmlspecialchars(substr($req['description'], 0, 150)).'</p>';
    }
    $html .= '  </div>';
    $html .= '  <div class="approval-card-body">';
    $html .= '    <div class="approval-meta">';
    $html .= '      <span><i class="fas fa-user me-1"></i>'.htmlspecialchars($req['requester_name']).'</span>';
    $html .= '      <span class="badge '.$sc.' stage-badge">'.$slabel.'</span>';
    $html .= '    </div>';
    $html .= '    <div class="approval-time">'.date('d M Y H:i', strtotime($req['created_at'])).'</div>';
    $html .= '  </div>';
    $html .= '  <div class="approval-card-actions">';
    if ($status === 'Active') {
        $html .= '    <button class="btn-approve" onclick="event.stopPropagation();showConfirmModal('.$req['id'].',\'approve\')"><i class="fas fa-check me-1"></i>Approve</button>';
        $html .= '    <button class="btn-reject" onclick="event.stopPropagation();showConfirmModal('.$req['id'].',\'reject\')"><i class="fas fa-times me-1"></i>Reject</button>';
        $html .= '    <button class="btn-return" onclick="event.stopPropagation();showConfirmModal('.$req['id'].',\'return\')"><i class="fas fa-undo me-1"></i>Return</button>';
    }
    $html .= '    <button class="btn-view" onclick="event.stopPropagation();openRequestDetail('.$req['id'].')"><i class="fas fa-eye me-1"></i>View</button>';
    $html .= '  </div>';
    $html .= '</div>';
    return $html;
}
}

// ─── Request List ───
if (!function_exists('renderApprovalRequestList')) {
function renderApprovalRequestList($requests) {
    if (empty($requests)) {
        return '<div class="text-center py-5"><i class="fas fa-inbox fa-3x mb-3" style="color:#cbd5e1;"></i><h6 style="color:#64748b;">No requests found</h6><p style="color:#94a3b8;font-size:13px;">All approvals are up to date.</p></div>';
    }
    $html = '<div class="approval-grid">';
    foreach ($requests as $req) {
        $html .= renderApprovalRequestCard($req);
    }
    $html .= '</div>';
    return $html;
}
}

// ─── Request Detail Modal ───
if (!function_exists('renderRequestDetailModal')) {
function renderRequestDetailModal() {
    ?>
    <div class="modal fade" id="requestDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#1a237e,#283593);color:#fff;border:none;padding:20px 24px;">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="fas fa-file-contract me-2"></i><span id="detailTitle"></span></h5>
                        <div style="font-size:12px;opacity:0.8;">Request <code id="detailRequestNumber" style="color:#fff;background:rgba(255,255,255,0.15);padding:2px 8px;border-radius:4px;"></code></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-7 border-end">
                            <div class="p-4">
                                <!-- Priority + Status badges -->
                                <div class="d-flex gap-2 mb-3" id="detailBadges"></div>
                                <!-- Description -->
                                <h6 style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px;">Description</h6>
                                <p id="detailDescription" style="font-size:14px;color:#475569;line-height:1.6;"></p>
                                <!-- Attachments -->
                                <div id="detailAttachments" style="display:none;" class="mb-3">
                                    <h6 style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px;">Attachments</h6>
                                    <div id="detailAttachmentsList"></div>
                                </div>
                                <!-- Approval Timeline -->
                                <h6 style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:12px;margin-top:24px;">
                                    <i class="fas fa-history me-1" style="color:#3b82f6;"></i>Activity Timeline
                                </h6>
                                <div id="detailTimeline" class="approval-timeline"></div>
                            </div>
                        </div>
                        <div class="col-lg-5" style="background:#f8fafc;">
                            <div class="p-4">
                                <!-- Request Info -->
                                <h6 style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:12px;"><i class="fas fa-info-circle me-1" style="color:#3b82f6;"></i>Request Information</h6>
                                <div class="detail-info-grid" id="detailInfo"></div>
                                <!-- Actions -->
                                <div id="detailActions" class="mt-4 pt-3" style="border-top:1px solid #e2e8f0;"></div>
                                <!-- Comments -->
                                <div id="detailComments" class="mt-3">
                                    <h6 style="font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px;"><i class="fas fa-comment me-1" style="color:#3b82f6;"></i>Comments</h6>
                                    <div id="detailCommentsList" class="approval-comments"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
}

// ─── Confirmation Modal ───
if (!function_exists('renderApprovalConfirmModal')) {
function renderApprovalConfirmModal() {
    ?>
    <div class="modal fade" id="approvalConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:16px;">
                <div class="modal-header" style="border:none;padding:20px 24px 0;">
                    <h5 class="modal-title fw-bold" id="confirmModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p id="confirmModalMessage" style="font-size:14px;color:#475569;margin-bottom:16px;"></p>
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" style="font-size:13px;">Comment <span id="commentRequired" class="text-danger">*</span></label>
                        <textarea id="confirmComment" class="form-control" rows="3" placeholder="Enter your comment..." style="border-radius:8px;border-color:#d1d5db;font-size:13px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border:none;padding:0 24px 20px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn" style="border-radius:8px;padding:8px 24px;">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
}

// ─── JavaScript ───
if (!function_exists('renderApprovalCenterScripts')) {
function renderApprovalCenterScripts() {
    ?>
    <style>
    .approval-summary-card:hover { border-color: #3b82f6 !important; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .approval-summary-card.active { border-color: #3b82f6 !important; box-shadow: 0 4px 12px rgba(59,130,246,0.15); }
    .approval-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
    .approval-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:all 0.2s; }
    .approval-card:hover { border-color:#cbd5e1; box-shadow:0 4px 16px rgba(0,0,0,0.06); }
    .approval-card-header { padding:16px 16px 8px; cursor:pointer; }
    .approval-cat-icon { width:32px;height:32px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:13px;color:#64748b;flex-shrink:0; }
    .approval-title { font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px;line-height:1.3; }
    .approval-desc { font-size:12px;color:#64748b;margin-bottom:0;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical; }
    .approval-card-body { padding:8px 16px 12px; }
    .approval-meta { display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#64748b; }
    .stage-badge { font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px; }
    .approval-time { font-size:11px;color:#94a3b8;margin-top:4px; }
    .approval-card-actions { display:flex;gap:6px;padding:10px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;flex-wrap:wrap; }
    .approval-card-actions button { font-size:11px;font-weight:600;padding:5px 12px;border-radius:6px;border:none;cursor:pointer;transition:all 0.15s; }
    .btn-approve { background:#10b98120;color:#059669; }
    .btn-approve:hover { background:#10b98130; }
    .btn-reject { background:#ef444420;color:#dc2626; }
    .btn-reject:hover { background:#ef444430; }
    .btn-return { background:#8b5cf620;color:#7c3aed; }
    .btn-return:hover { background:#8b5cf630; }
    .btn-view { background:#3b82f610;color:#2563eb; }
    .btn-view:hover { background:#3b82f620; }
    .priority-badge { border-radius:20px;padding:2px 10px; }
    .category-badge { border-radius:20px;padding:2px 10px;background:#f1f5f9;color:#64748b; }
    .request-number { font-size:10px;color:#94a3b8; }
    .approval-timeline { position:relative;padding-left:24px; }
    .approval-timeline::before { content:'';position:absolute;left:8px;top:4px;bottom:4px;width:2px;background:#e2e8f0;border-radius:2px; }
    .timeline-item { position:relative;padding-bottom:16px; }
    .timeline-item:last-child { padding-bottom:0; }
    .timeline-dot { position:absolute;left:-20px;top:4px;width:14px;height:14px;border-radius:50%;border:2px solid #3b82f6;background:#fff;z-index:1; }
    .timeline-dot.approved { border-color:#10b981;background:#ecfdf5; }
    .timeline-dot.rejected { border-color:#ef4444;background:#fef2f2; }
    .timeline-dot.returned { border-color:#8b5cf6;background:#f5f3ff; }
    .timeline-content { font-size:13px; }
    .timeline-content .tl-action { font-weight:600;color:#0f172a; }
    .timeline-content .tl-by { color:#64748b; }
    .timeline-content .tl-comment { font-size:12px;color:#64748b;margin-top:2px;padding:6px 10px;background:#f8fafc;border-radius:6px; }
    .timeline-content .tl-time { font-size:11px;color:#94a3b8;margin-top:2px; }
    .detail-info-grid { display:grid;grid-template-columns:1fr 1fr;gap:8px; }
    .detail-info-item { padding:8px 10px;background:#fff;border-radius:8px;border:1px solid #e2e8f0; }
    .detail-info-item .label { font-size:11px;color:#94a3b8;margin-bottom:2px; }
    .detail-info-item .value { font-size:13px;font-weight:600;color:#0f172a; }
    .approval-comments .comment-item { padding:8px 12px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:8px; }
    .approval-comments .comment-item .c-author { font-size:12px;font-weight:600;color:#0f172a; }
    .approval-comments .comment-item .c-text { font-size:13px;color:#475569;margin-top:2px; }
    .approval-comments .comment-item .c-time { font-size:11px;color:#94a3b8;margin-top:2px; }
    @media (max-width:768px) {
        .approval-grid { grid-template-columns:1fr; }
        .detail-info-grid { grid-template-columns:1fr; }
        .approval-card-actions { flex-direction:column; }
        .approval-card-actions button { width:100%; }
    }
    </style>

    <script>
    var currentFilter = 'all';
    var currentActionRequestId = 0;
    var currentActionType = '';

    window.filterApprovals = function(filter) {
        currentFilter = filter;
        document.querySelectorAll('.approval-summary-card').forEach(function(c) { c.classList.toggle('active', c.dataset.filter === filter); });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ajax/approval_center_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success) { document.getElementById('approvalRequestList').innerHTML = d.html; }
                } catch(e) {}
            }
        };
        xhr.send('action=get_requests&filter=' + filter);
    };

    window.searchApprovals = function() {
        var q = document.getElementById('approvalSearchInput') ? document.getElementById('approvalSearchInput').value : '';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ajax/approval_center_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success) { document.getElementById('approvalRequestList').innerHTML = d.html; }
                } catch(e) {}
            }
        };
        xhr.send('action=get_requests&filter=' + currentFilter + '&search=' + encodeURIComponent(q));
    };

    window.showConfirmModal = function(requestId, action) {
        currentActionRequestId = requestId;
        currentActionType = action;
        var labels = {approve:'Approve',reject:'Reject',return:'Return for Correction'};
        var messages = {
            approve:'Are you sure you want to APPROVE this request?',
            reject:'Are you sure you want to REJECT this request? A reason is required.',
            return:'Are you sure you want to RETURN this request for correction? A comment is required.'
        };
        var el = document.getElementById('confirmModalTitle');
        if (!el) return;
        el.textContent = labels[action] || 'Confirm Action';
        document.getElementById('confirmModalMessage').textContent = messages[action] || '';
        var requiresComment = (action === 'reject' || action === 'return');
        document.getElementById('commentRequired').style.display = requiresComment ? 'inline' : 'none';
        document.getElementById('confirmComment').value = '';
        document.getElementById('confirmActionBtn').textContent = labels[action] || 'Confirm';
        var btn = document.getElementById('confirmActionBtn');
        btn.className = 'btn px-4';
        if (action === 'approve') btn.className += ' btn-success';
        else if (action === 'reject') btn.className += ' btn-danger';
        else btn.className += ' btn-primary';
        try {
            var modal = new bootstrap.Modal(document.getElementById('approvalConfirmModal'));
            modal.show();
        } catch(e) {
            if (confirm(messages[action] || 'Confirm ' + action + '?')) {
                currentActionRequestId = requestId;
                currentActionType = action;
                document.getElementById('confirmActionBtn').click();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        var confirmBtn = document.getElementById('confirmActionBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
                var comment = document.getElementById('confirmComment').value.trim();
                if ((currentActionType === 'reject' || currentActionType === 'return') && !comment) {
                    alert('Comment is required for this action.');
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = document.getElementById('confirmModalTitle').textContent;
                    return;
                }
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../ajax/approval_action.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.success) { 
                                var modalEl = document.getElementById('approvalConfirmModal');
                                var bsModal = bootstrap.Modal.getInstance(modalEl);
                                if (bsModal) bsModal.hide();
                                window.filterApprovals(currentFilter);
                            } else { alert('Error: ' + (resp.error || 'Failed')); confirmBtn.disabled = false; confirmBtn.textContent = document.getElementById('confirmModalTitle').textContent; }
                        } catch(e) { location.reload(); }
                    } else {
                        alert('Server error: ' + xhr.status);
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = document.getElementById('confirmModalTitle').textContent;
                    }
                };
                xhr.onerror = function() { alert('Network error. Please try again.'); confirmBtn.disabled = false; confirmBtn.textContent = document.getElementById('confirmModalTitle').textContent; };
                xhr.send('request_id=' + currentActionRequestId + '&action=' + currentActionType + '&comments=' + encodeURIComponent(comment));
            });
        }
    });

    window.openRequestDetail = function(requestId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ajax/approval_center_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var d = JSON.parse(xhr.responseText);
                    if (d.success) { populateDetailModal(d.data); }
                } catch(e) { alert('Error loading request details.'); }
            }
        };
        xhr.send('action=get_detail&id=' + requestId);
    };

    function populateDetailModal(data) {
        document.getElementById('detailTitle').textContent = data.title;
        document.getElementById('detailRequestNumber').textContent = '#' + data.request_number;
        document.getElementById('detailDescription').textContent = data.description || 'No description provided.';
        var badges = document.getElementById('detailBadges');
        badges.innerHTML = '<span class="badge" style="background:' + data.priority_color + '20;color:' + data.priority_color + ';border:1px solid ' + data.priority_color + '40;font-size:10px;font-weight:600;">' + data.priority + '</span>' +
            '<span class="badge ' + (data.status === 'Approved' ? 'bg-success' : data.status === 'Rejected' ? 'bg-danger' : 'bg-primary') + '" style="font-size:10px;">' + data.status + '</span>' +
            '<span class="badge bg-light text-dark" style="font-size:10px;">' + data.category + '</span>';
        var info = document.getElementById('detailInfo');
        info.innerHTML = '';
        var fields = [
            {label:'Department', value:data.category},
            {label:'Request Type', value:data.workflow_name},
            {label:'Submitted By', value:data.requester_name},
            {label:'Submitted On', value:data.created_at_formatted},
            {label:'Current Stage', value:data.current_stage_name || 'Pending'},
            {label:'Final Decision', value:data.final_approval_by_name || 'Pending'},
            {label:'Reference', value:data.reference_type || 'N/A'},
        ];
        fields.forEach(function(f) {
            if (f.value) info.innerHTML += '<div class="detail-info-item"><div class="label">' + f.label + '</div><div class="value">' + f.value + '</div></div>';
        });
        var actions = document.getElementById('detailActions');
        if (data.status === 'Active') {
            actions.innerHTML = '<div class="d-flex gap-2 flex-wrap">' +
                '<button class="btn btn-success btn-sm" onclick="showConfirmModal(' + data.id + ',\'approve\')"><i class="fas fa-check me-1"></i>Approve</button>' +
                '<button class="btn btn-danger btn-sm" onclick="showConfirmModal(' + data.id + ',\'reject\')"><i class="fas fa-times me-1"></i>Reject</button>' +
                '<button class="btn" style="background:#8b5cf620;color:#7c3aed;" onclick="showConfirmModal(' + data.id + ',\'return\')"><i class="fas fa-undo me-1"></i>Return</button>' +
                '</div>';
        } else { actions.innerHTML = '<div class="text-muted" style="font-size:13px;">This request has been <strong>' + data.status + '</strong>.</div>'; }
        var timeline = document.getElementById('detailTimeline');
        timeline.innerHTML = '';
        if (data.timeline && data.timeline.length) {
            data.timeline.forEach(function(t) {
                var dotClass = 'timeline-dot';
                if (t.action_type === 'approve') dotClass += ' approved';
                else if (t.action_type === 'reject') dotClass += ' rejected';
                else if (t.action_type === 'return') dotClass += ' returned';
                var icon = t.action_type === 'create' ? 'fa-plus-circle' : t.action_type === 'approve' ? 'fa-check-circle' : t.action_type === 'reject' ? 'fa-times-circle' : t.action_type === 'return' ? 'fa-undo' : 'fa-arrow-right';
                timeline.innerHTML += '<div class="timeline-item"><div class="' + dotClass + '"></div><div class="timeline-content">' +
                    '<div class="tl-action"><i class="fas ' + icon + ' me-1" style="font-size:11px;"></i>' + t.action_label + '</div>' +
                    '<div class="tl-by">by ' + t.action_by_name + '</div>' +
                    (t.comments ? '<div class="tl-comment">' + t.comments + '</div>' : '') +
                    '<div class="tl-time">' + t.created_at_formatted + '</div></div></div>';
            });
        } else {
            timeline.innerHTML = '<div class="text-muted" style="font-size:13px;">No activity recorded.</div>';
        }
        var comments = document.getElementById('detailCommentsList');
        comments.innerHTML = '';
        if (data.comments && data.comments.length) {
            data.comments.forEach(function(c) {
                comments.innerHTML += '<div class="comment-item"><div class="c-author">' + c.action_by_name + ' <span class="text-muted" style="font-weight:400;font-size:11px;">(' + c.action_type + ')</span></div>' +
                    '<div class="c-text">' + c.comments + '</div><div class="c-time">' + c.created_at_formatted + '</div></div>';
            });
        } else { comments.innerHTML = '<div class="text-muted" style="font-size:12px;">No comments.</div>'; }
        var modal = new bootstrap.Modal(document.getElementById('requestDetailModal'));
        modal.show();
    }
    </script>
    <?php
}
}

// ─── Render Full Approval Center (UI only) ───
if (!function_exists('renderApprovalCenter')) {
function renderApprovalCenter($conn) {
    $counts = getApprovalCenterCounts($conn);
    $requests = getApprovalCenterRequests($conn, ['status' => 'active']);
    ob_start();
    ?>
    <div class="approval-center">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h4 class="fw-bold mb-1" style="color:#0f172a;"><i class="fas fa-check-double me-2" style="color:#3b82f6;"></i>Approval Center</h4>
                <p class="text-muted mb-0" style="font-size:13px;">Director General — Final approval authority for all institution requests.</p>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <div class="input-group" style="max-width:300px;">
                    <input type="text" class="form-control form-control-sm" id="approvalSearchInput" placeholder="Search requests..." style="border-radius:8px 0 0 8px;font-size:13px;" onkeyup="if(event.key==='Enter')searchApprovals()">
                    <button class="btn btn-sm btn-primary" onclick="searchApprovals()" style="border-radius:0 8px 8px 0;"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>

        <?= renderApprovalSummaryCards($conn) ?>

        <div id="approvalRequestList">
            <?= renderApprovalRequestList($requests) ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
}

// ─── Render Approval Modals & Scripts (call OUTSIDE section-card) ───
if (!function_exists('renderApprovalModalsAndScripts')) {
function renderApprovalModalsAndScripts() {
    renderRequestDetailModal();
    renderApprovalConfirmModal();
    renderApprovalCenterScripts();
}
}
