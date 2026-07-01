<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['director','secretary','ict','it','principal']);
$pageTitle = 'Communications';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['full_name'] ?? 'Staff';

$conn = getStaffConnection();

$inboxCount = 0;
$sentCount = 0;
$unreadCount = 0;

if ($conn && $user_id) {
    $r = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE recipient_id = ? AND is_deleted_recipient = 0");
    if ($r) { $r->bind_param("i", $user_id); $r->execute(); $inboxCount = (int)$r->get_result()->fetch_assoc()['c']; $r->close(); }

    $r = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE sender_id = ? AND is_deleted_sender = 0");
    if ($r) { $r->bind_param("i", $user_id); $r->execute(); $sentCount = (int)$r->get_result()->fetch_assoc()['c']; $r->close(); }

    $r = $conn->prepare("SELECT COUNT(*) as c FROM staff_inbox WHERE recipient_id = ? AND is_read = 0 AND is_deleted_recipient = 0");
    if ($r) { $r->bind_param("i", $user_id); $r->execute(); $unreadCount = (int)$r->get_result()->fetch_assoc()['c']; $r->close(); }
}

$activeTab = $_GET['tab'] ?? 'inbox';

$messages = [];
if ($conn && $user_id) {
    if ($activeTab === 'sent') {
        $r = $conn->prepare("SELECT * FROM staff_inbox WHERE sender_id = ? AND is_deleted_sender = 0 ORDER BY created_at DESC LIMIT 50");
        if ($r) { $r->bind_param("i", $user_id); $r->execute(); $res = $r->get_result(); while ($row = $res->fetch_assoc()) $messages[] = $row; $r->close(); }
    } else {
        $r = $conn->prepare("SELECT * FROM staff_inbox WHERE recipient_id = ? AND is_deleted_recipient = 0 ORDER BY created_at DESC LIMIT 50");
        if ($r) { $r->bind_param("i", $user_id); $r->execute(); $res = $r->get_result(); while ($row = $res->fetch_assoc()) $messages[] = $row; $r->close(); }
    }
}

$staffList = [];
if ($conn) {
    $sr = $conn->query("SELECT id, full_name, position, department FROM staff WHERE status = 'Active' ORDER BY full_name");
    if ($sr) while ($row = $sr->fetch_assoc()) $staffList[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
.msg-tabs { display:flex; gap:0; margin-bottom:20px; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0; }
.msg-tab { flex:1; padding:10px 16px; text-align:center; font-size:13px; font-weight:600; cursor:pointer; background:#f8fafc; color:#64748b; transition:all .2s; border:none; }
.msg-tab.active { background:#1e3a8a; color:#fff; }
.msg-tab:hover:not(.active) { background:#e2e8f0; }
.msg-tab .badge { margin-left:6px; }
.msg-item { display:flex; align-items:center; gap:12px; padding:12px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .15s; border-radius:8px; margin-bottom:2px; }
.msg-item:hover { background:#f1f5f9; }
.msg-item.unread { background:#eff6ff; border-left:3px solid #3b82f6; }
.msg-item.unread .msg-subject { font-weight:700; color:#0f172a; }
.msg-avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:600; color:#fff; flex-shrink:0; }
.msg-content { flex:1; min-width:0; }
.msg-subject { font-size:13px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.msg-preview { font-size:11px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.msg-meta { text-align:right; flex-shrink:0; }
.msg-time { font-size:10px; color:#94a3b8; }
.msg-priority { font-size:9px; padding:2px 6px; border-radius:8px; font-weight:600; margin-top:4px; display:inline-block; }
.priority-Urgent { background:#fef2f2; color:#dc2626; }
.priority-High { background:#fff7ed; color:#ea580c; }
.priority-Normal { background:#f0fdf4; color:#16a34a; }
.priority-Low { background:#f8fafc; color:#64748b; }
.msg-detail-header { padding:16px; border-bottom:1px solid #e2e8f0; }
.msg-detail-body { padding:20px; line-height:1.7; font-size:13px; color:#334155; }
.compose-form label { font-size:12px; font-weight:600; color:#475569; margin-bottom:4px; }
.compose-form .form-control, .compose-form .form-select { font-size:13px; border-radius:8px; }
.empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
.empty-state i { font-size:40px; margin-bottom:12px; display:block; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<main class="main" style="margin-left:270px;padding:32px;">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-comments me-2"></i>Communications</h4>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
            <button class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" onclick="openComposeModal()"><i class="fas fa-pen me-1"></i>Compose</button>
        </div>
    </div>

    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
        <div class="stat-card primary" style="background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;">
            <div class="stat-content"><h3 style="font-size:22px;font-weight:700;margin:0;"><?= $inboxCount ?></h3><p style="margin:0;font-size:12px;color:#64748b;">Inbox</p></div>
        </div>
        <div class="stat-card success" style="background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;">
            <div class="stat-content"><h3 style="font-size:22px;font-weight:700;margin:0;"><?= $sentCount ?></h3><p style="margin:0;font-size:12px;color:#64748b;">Sent</p></div>
        </div>
        <div class="stat-card warning" style="background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;">
            <div class="stat-content"><h3 style="font-size:22px;font-weight:700;margin:0;"><?= $unreadCount ?></h3><p style="margin:0;font-size:12px;color:#64748b;">Unread</p></div>
        </div>
        <div class="stat-card info" style="background:#fff;border-radius:10px;padding:16px;border:1px solid #e2e8f0;">
            <div class="stat-content"><h3 style="font-size:22px;font-weight:700;margin:0;"><?= $inboxCount + $sentCount ?></h3><p style="margin:0;font-size:12px;color:#64748b;">Total</p></div>
        </div>
    </div>

    <div style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
        <div class="msg-tabs">
            <a href="?tab=inbox" class="msg-tab <?= $activeTab === 'inbox' ? 'active' : '' ?>"><i class="fas fa-inbox me-1"></i>Inbox<?php if ($unreadCount > 0): ?><span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span><?php endif; ?></a>
            <a href="?tab=sent" class="msg-tab <?= $activeTab === 'sent' ? 'active' : '' ?>"><i class="fas fa-paper-plane me-1"></i>Sent</a>
        </div>

        <div id="msgListContainer" style="max-height:500px;overflow-y:auto;">
            <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-<?= $activeTab === 'sent' ? 'paper-plane' : 'inbox' ?>"></i>
                <p>No <?= $activeTab === 'sent' ? 'sent' : '' ?> messages yet.</p>
            </div>
            <?php else: ?>
                <?php foreach ($messages as $m):
                    $isUnread = !$m['is_read'] && $activeTab === 'inbox';
                    $avatarColors = ['#3b82f6','#059669','#d97706','#dc2626','#7c3aed','#0891b2'];
                    $colorIdx = ($m['sender_id'] ?? 0) % count($avatarColors);
                    $avatarColor = $avatarColors[$colorIdx];
                    $initial = strtoupper(substr($activeTab === 'sent' ? $m['recipient_name'] : $m['sender_name'], 0, 1));
                ?>
                <div class="msg-item <?= $isUnread ? 'unread' : '' ?>" onclick="viewMessage(<?= (int)$m['id'] ?>, '<?= $activeTab ?>')" data-id="<?= (int)$m['id'] ?>">
                    <div class="msg-avatar" style="background:<?= $avatarColor ?>"><?= $initial ?></div>
                    <div class="msg-content">
                        <div class="msg-subject"><?= htmlspecialchars($m['subject']) ?></div>
                        <div class="msg-preview"><?= $activeTab === 'sent' ? 'To: ' . htmlspecialchars($m['recipient_name']) : 'From: ' . htmlspecialchars($m['sender_name']) ?> &mdash; <?= htmlspecialchars(mb_substr($m['message'], 0, 60)) ?></div>
                    </div>
                    <div class="msg-meta">
                        <div class="msg-time"><?= date('d M Y', strtotime($m['created_at'])) ?></div>
                        <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                        <?php if ($m['priority'] !== 'Normal'): ?>
                        <span class="msg-priority priority-<?= $m['priority'] ?>"><?= $m['priority'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Detail Panel (hidden by default) -->
    <div id="msgDetailPanel" style="display:none;background:#fff;border-radius:10px;border:1px solid #e2e8f0;margin-top:16px;overflow:hidden;">
        <div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
            <button class="btn btn-sm" style="background:none;border:none;color:#64748b;" onclick="closeDetail()"><i class="fas fa-arrow-left me-1"></i>Back</button>
            <div id="detailActions"></div>
        </div>
        <div id="msgDetailContent"></div>
    </div>
</div>
</main>

<!-- Compose Modal -->
<div class="modal fade" id="composeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;padding:14px 18px;">
                <h5 class="modal-title" style="font-size:15px;"><i class="fas fa-pen me-2"></i>New Message</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body compose-form" style="padding:18px;">
                <div class="mb-3">
                    <label class="form-label">To *</label>
                    <select id="composeRecipient" class="form-select" required>
                        <option value="">Select staff member...</option>
                        <?php foreach ($staffList as $s): ?>
                        <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['full_name']) ?>"><?= htmlspecialchars($s['full_name']) ?> &mdash; <?= htmlspecialchars($s['position'] ?? $s['department'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject *</label>
                    <input type="text" id="composeSubject" class="form-control" placeholder="Enter subject..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message *</label>
                    <textarea id="composeBody" class="form-control" rows="6" placeholder="Write your message..." required style="resize:vertical;"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select id="composePriority" class="form-select">
                        <option value="Normal">Normal</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <input type="hidden" id="composeParentId" value="">
            </div>
            <div class="modal-footer" style="border-top:1px solid #e2e8f0;padding:12px 18px;">
                <span id="composeResult" class="small me-auto"></span>
                <button type="button" class="btn btn-sm" style="background:#e2e8f0;color:#475569;border:none;border-radius:8px;" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" onclick="sendMessage()"><i class="fas fa-paper-plane me-1"></i>Send</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
<script>
var CURRENT_USER_ID = <?= $user_id ?>;

function openComposeModal() {
    document.getElementById('composeRecipient').value = '';
    document.getElementById('composeSubject').value = '';
    document.getElementById('composeBody').value = '';
    document.getElementById('composePriority').value = 'Normal';
    document.getElementById('composeParentId').value = '';
    document.getElementById('composeResult').innerHTML = '';
    new bootstrap.Modal(document.getElementById('composeModal')).show();
}

function sendMessage() {
    var recipient = document.getElementById('composeRecipient').value;
    var subject = document.getElementById('composeSubject').value.trim();
    var body = document.getElementById('composeBody').value.trim();
    var priority = document.getElementById('composePriority').value;
    var result = document.getElementById('composeResult');

    if (!recipient) { result.innerHTML = '<span class="text-danger">Please select a recipient.</span>'; return; }
    if (!subject) { result.innerHTML = '<span class="text-danger">Subject is required.</span>'; return; }
    if (!body) { result.innerHTML = '<span class="text-danger">Message is required.</span>'; return; }

    result.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Sending...</span>';

    var fd = new FormData();
    fd.append('action', 'send');
    fd.append('sender_id', CURRENT_USER_ID);
    fd.append('recipient_id', recipient);
    fd.append('subject', subject);
    fd.append('message', body);
    fd.append('priority', priority);

    var parentId = document.getElementById('composeParentId').value;
    if (parentId) fd.append('parent_id', parentId);

    fetch('../includes/ajax_messaging.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                result.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Sent!</span>';
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                result.innerHTML = '<span class="text-danger">' + (d.error || 'Failed') + '</span>';
            }
        })
        .catch(function() {
            result.innerHTML = '<span class="text-danger">Network error.</span>';
        });
}

function viewMessage(id, tab) {
    var currentTab = tab || 'inbox';
    var fd = new FormData();
    fd.append('action', 'read');
    fd.append('message_id', id);
    fd.append('user_id', CURRENT_USER_ID);
    fetch('../includes/ajax_messaging.php', { method: 'POST', body: fd });

    var container = document.getElementById('msgListContainer');
    var detailPanel = document.getElementById('msgDetailPanel');
    var detailContent = document.getElementById('msgDetailContent');
    var detailActions = document.getElementById('detailActions');

    container.style.display = 'none';
    detailPanel.style.display = 'block';

    var item = document.querySelector('.msg-item[data-id="' + id + '"]');
    if (item) item.classList.remove('unread');

    var rows = container.querySelectorAll('.msg-item');
    var msgData = null;
    rows.forEach(function(r) {
        if (r.dataset.id == id) {
            var subjectEl = r.querySelector('.msg-subject');
            var previewEl = r.querySelector('.msg-preview');
            var timeEls = r.querySelectorAll('.msg-time');
            msgData = {
                subject: subjectEl ? subjectEl.textContent : '',
                preview: previewEl ? previewEl.textContent : '',
                time: timeEls.length ? timeEls[0].textContent + ' ' + (timeEls[1] ? timeEls[1].textContent : '') : ''
            };
        }
    });

    var html = '<div class="msg-detail-header">';
    html += '<h5 style="font-size:16px;font-weight:700;margin-bottom:6px;">' + (msgData ? msgData.subject : 'Message') + '</h5>';
    html += '<div style="font-size:12px;color:#64748b;">' + (msgData ? msgData.preview : '') + '</div>';
    html += '<div style="font-size:11px;color:#94a3b8;margin-top:4px;">' + (msgData ? msgData.time : '') + '</div>';
    html += '</div>';
    html += '<div class="msg-detail-body">Loading full message...</div>';
    detailContent.innerHTML = html;

    detailActions.innerHTML = '<button class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:none;border-radius:8px;" onclick="deleteMessage(' + id + ', \'' + currentTab + '\')"><i class="fas fa-trash me-1"></i>Delete</button>';

    var fd2 = new FormData();
    if (currentTab === 'sent') {
        fd2.append('action', 'sent');
        fd2.append('sender_id', CURRENT_USER_ID);
    } else {
        fd2.append('action', 'inbox');
        fd2.append('recipient_id', CURRENT_USER_ID);
    }
    fd2.append('limit', '50');
    fetch('../includes/ajax_messaging.php', { method: 'POST', body: fd2 })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success && d.data) {
                var msg = d.data.find(function(m) { return m.id == id; });
                if (msg) {
                    var fullHtml = '<div class="msg-detail-header">';
                    fullHtml += '<h5 style="font-size:16px;font-weight:700;margin-bottom:6px;">' + escapeHtml(msg.subject) + '</h5>';
                    fullHtml += '<div style="display:flex;gap:8px;align-items:center;margin-top:8px;">';
                    fullHtml += '<div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;">' + (msg.sender_name ? msg.sender_name.charAt(0).toUpperCase() : '?') + '</div>';
                    fullHtml += '<div><div style="font-size:13px;font-weight:600;">' + escapeHtml(msg.sender_name) + '</div>';
                    fullHtml += '<div style="font-size:11px;color:#94a3b8;">' + escapeHtml(msg.sender_role || '') + ' &middot; ' + msg.created_at + '</div></div>';
                    fullHtml += '</div>';
                    if (msg.priority !== 'Normal') {
                        fullHtml += '<span class="msg-priority priority-' + msg.priority + '" style="margin-top:8px;">' + msg.priority + '</span>';
                    }
                    fullHtml += '</div>';
                    fullHtml += '<div class="msg-detail-body">' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>';
                    fullHtml += '<div style="padding:12px 20px;border-top:1px solid #e2e8f0;">';
                    fullHtml += '<button class="btn btn-sm" style="background:#2563eb;color:#fff;border:none;border-radius:8px;" onclick="replyTo(' + msg.sender_id + ', \'' + escapeHtml(msg.sender_name).replace(/'/g, "\\'") + '\', \'' + escapeHtml(msg.subject).replace(/'/g, "\\'") + '\', ' + (msg.parent_id || msg.id) + ')"><i class="fas fa-reply me-1"></i>Reply</button>';
                    fullHtml += '</div>';
                    detailContent.innerHTML = fullHtml;
                }
            }
        });
}

function closeDetail() {
    document.getElementById('msgListContainer').style.display = '';
    document.getElementById('msgDetailPanel').style.display = 'none';
}

function deleteMessage(id, box) {
    if (!confirm('Delete this message?')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('message_id', id);
    fd.append('user_id', CURRENT_USER_ID);
    fd.append('box', box);
    fetch('../includes/ajax_messaging.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) location.reload(); else alert(d.error || 'Delete failed'); })
        .catch(function() { alert('Network error.'); });
}

function replyTo(senderId, senderName, subject, parentId) {
    document.getElementById('composeRecipient').value = senderId;
    document.getElementById('composeSubject').value = 'Re: ' + subject;
    document.getElementById('composeBody').value = '';
    document.getElementById('composePriority').value = 'Normal';
    document.getElementById('composeResult').innerHTML = '';
    document.getElementById('composeParentId').value = parentId || '';
    closeDetail();
    new bootstrap.Modal(document.getElementById('composeModal')).show();
}

function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
</script>
</body>
</html>
