<?php
/**
 * Institutional Framework: Hierarchy, Ownership, Alerts, Performance, Audit
 * Central include for director management enhancements.
 * Does not modify existing data or functionality.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// ──────────────────────────────────────────────────────────────────────────────
// 1. DIRECTOR HIERARCHY
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('getDirectorHierarchy')) {
function getDirectorHierarchy($conn) {
    $hierarchy = [];
    if (!$conn) return $hierarchy;
    try {
        $result = $conn->query("
            SELECT sr.id, sr.role_name, sr.role_level, sr.hierarchy_level, 
                   sr.reporting_to_role_id, sr.can_approve_level, sr.is_executive,
                   sr.dashboard_path, sr.role_description,
                   (SELECT COUNT(*) FROM staff s WHERE s.role_id = sr.id AND s.status = 'Active') as staff_count
            FROM staff_roles sr
            WHERE sr.hierarchy_level <= 10
            ORDER BY sr.hierarchy_level ASC, sr.role_name ASC
        ");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $hierarchy[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log('getDirectorHierarchy error: ' . $e->getMessage());
    }
    return $hierarchy;
}
}

if (!function_exists('getRoleHierarchyLevel')) {
function getRoleHierarchyLevel($roleName, $conn) {
    if (!$conn) return 99;
    try {
        $stmt = $conn->prepare("SELECT hierarchy_level FROM staff_roles WHERE role_name = ? LIMIT 1");
        if (!$stmt) return 99;
        $stmt->bind_param('s', $roleName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['hierarchy_level'] : 99;
        } catch (Exception $e) {
        error_log('institutional_framework getHierarchyLevel: ' . $e->getMessage());
        return 99;
    }


}
}

if (!function_exists('canManageRole')) {
function canManageRole($managerRoleLevel, $targetRoleLevel) {
    return $managerRoleLevel < $targetRoleLevel;
}
}

if (!function_exists('renderHierarchyChart')) {
function renderHierarchyChart($conn) {
    $hierarchy = getDirectorHierarchy($conn);
    if (empty($hierarchy)) return '<div class="text-muted small py-3">Hierarchy data not available.</div>';
    $html = '<div class="hierarchy-chart">';
    $currentLevel = 0;
    foreach ($hierarchy as $role) {
        $level = (int)$role['hierarchy_level'];
        if ($level > $currentLevel) {
            if ($currentLevel > 0) $html .= '</div>';
            $html .= '<div class="hierarchy-level">';
            $currentLevel = $level;
        }
        $label = $level === 1 ? 'Highest Authority' : ($level === 2 ? 'Executive' : 'Director');
        $badge = $role['is_executive'] ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:8px">EXEC</span>' : '';
        $html .= '<div class="hierarchy-node">';
        $html .= '  <div class="hierarchy-node-card">';
        $html .= '    <div class="hierarchy-node-icon"><i class="fas fa-user-tie"></i></div>';
        $html .= '    <div class="hierarchy-node-info">';
        $html .= '      <div class="hierarchy-node-name">' . htmlspecialchars($role['role_name']) . $badge . '</div>';
        $html .= '      <div class="hierarchy-node-meta">Level ' . $level . ' &middot; ' . $label . '</div>';
        $html .= '      <div class="hierarchy-node-staff">' . $role['staff_count'] . ' staff</div>';
        $html .= '    </div>';
        $html .= '  </div>';
        if ($level > 1) {
            $html .= '  <div class="hierarchy-connector"><i class="fas fa-arrow-up"></i> Reports to Level ' . ($level - 1) . '</div>';
        }
        $html .= '</div>';
    }
    $html .= '</div></div>';
    return $html;
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 2. DATA OWNERSHIP & ACCESS CONTROL
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('getDataOwnership')) {
function getDataOwnership($roleId, $conn) {
    $rules = [];
    if (!$conn) return $rules;
    try {
        $stmt = $conn->prepare("SELECT * FROM data_ownership_rules WHERE role_id = ?");
        if (!$stmt) return $rules;
        $stmt->bind_param('i', $roleId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework getInstitution: ' . $e->getMessage()); }
    return $rules;
}
}

if (!function_exists('canAccessData')) {
function canAccessData($roleId, $departmentCode, $dataCategory, $conn) {
    if (!$conn) return true;
    try {
        $stmt = $conn->prepare("SELECT access_level FROM data_ownership_rules WHERE role_id = ? AND department_code = ? AND data_category = ? LIMIT 1");
        if (!$stmt) return true;
        $stmt->bind_param('iss', $roleId, $departmentCode, $dataCategory);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            $stmt2 = $conn->prepare("SELECT access_level FROM data_ownership_rules WHERE role_id = ? AND data_category = 'all' AND access_level = 'full' LIMIT 1");
            if ($stmt2) {
                $stmt2->bind_param('i', $roleId);
                $stmt2->execute();
                $row2 = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                return $row2 ? $row2['access_level'] : 'none';
            }
            return 'none';
        }
        return $row['access_level'];
    } catch (Exception $e) {
        error_log('institutional_framework getAccessLevel: ' . $e->getMessage());
        return 'full';
    }
}
}

if (!function_exists('isDataOwner')) {
function isDataOwner($roleId, $departmentCode, $dataCategory, $conn) {
    if (!$conn) return false;
    try {
        $stmt = $conn->prepare("SELECT is_owner FROM data_ownership_rules WHERE role_id = ? AND department_code = ? AND data_category = ? AND is_owner = 1 LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('iss', $roleId, $departmentCode, $dataCategory);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return true;
        $stmt2 = $conn->prepare("SELECT is_owner FROM data_ownership_rules WHERE role_id = ? AND data_category = 'all' AND is_owner = 1 LIMIT 1");
        if ($stmt2) {
            $stmt2->bind_param('i', $roleId);
            $stmt2->execute();
            $row2 = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            return $row2 ? true : false;
        }
        return false;
        } catch (Exception $e) {
        error_log('institutional_framework checkAccess: ' . $e->getMessage());
        return false;
    }


}
}

if (!function_exists('renderOwnershipBadge')) {
function renderOwnershipBadge($roleId, $conn) {
    $rules = getDataOwnership($roleId, $conn);
    if (empty($rules)) return '';
    $html = '<div class="ownership-badges">';
    foreach ($rules as $rule) {
        $cls = $rule['is_owner'] ? 'badge bg-primary' : 'badge bg-secondary';
        $label = $rule['is_owner'] ? 'Owner' : 'Access';
        $html .= "<span class=\"{$cls} me-1\" style=\"font-size:9px\">{$rule['department_code']}: {$rule['access_level']} ({$label})</span>";
    }
    $html .= '</div>';
    return $html;
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 3. ENHANCED AUDIT TRAIL
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('recordAuditTrail')) {
function recordAuditTrail($staffId, $action, $category, $description, $tableAffected = null, $recordId = null, $recordIdentifier = null, $previousValues = null, $newValues = null, $conn = null) {
    if (!$conn) {
        if (function_exists('getStaffConnection')) $conn = getStaffConnection();
    }
    if (!$conn) return false;
    try {
        $staffName = $_SESSION['full_name'] ?? 'Unknown';
        $roleName = $_SESSION['role'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionId = session_id();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $prevJson = $previousValues ? json_encode($previousValues) : null;
        $newJson = $newValues ? json_encode($newValues) : null;
        $stmt = $conn->prepare("INSERT INTO audit_trail (staff_id, staff_name, role_name, action, category, description, table_affected, record_id, record_identifier, previous_values, new_values, ip_address, user_agent, session_id, request_method, request_uri, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param('issssssissssssss', $staffId, $staffName, $roleName, $action, $category, $description, $tableAffected, $recordId, $recordIdentifier, $prevJson, $newJson, $ip, $ua, $sessionId, $method, $uri);
        $r = $stmt->execute();
        $stmt->close();
        return $r;
    } catch (Exception $e) {
        error_log('recordAuditTrail error: ' . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('getAuditTrail')) {
function getAuditTrail($conn, $filters = [], $limit = 50, $offset = 0) {
    $rows = [];
    if (!$conn) return $rows;
    try {
        $sql = "SELECT * FROM audit_trail WHERE 1=1";
        $params = [];
        $types = '';
        if (!empty($filters['staff_id'])) { $sql .= " AND staff_id = ?"; $params[] = $filters['staff_id']; $types .= 'i'; }
        if (!empty($filters['action'])) { $sql .= " AND action = ?"; $params[] = $filters['action']; $types .= 's'; }
        if (!empty($filters['category'])) { $sql .= " AND category = ?"; $params[] = $filters['category']; $types .= 's'; }
        if (!empty($filters['table'])) { $sql .= " AND table_affected = ?"; $params[] = $filters['table']; $types .= 's'; }
        if (!empty($filters['record_id'])) { $sql .= " AND record_id = ?"; $params[] = $filters['record_id']; $types .= 'i'; }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $types .= 'i';
        $params[] = $offset; $types .= 'i';
        $stmt = $conn->prepare($sql);
        if (!$stmt) return $rows;
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework update: ' . $e->getMessage()); }
    return $rows;
}
}

if (!function_exists('renderAuditTrailTable')) {
function renderAuditTrailTable($conn, $filters = [], $limit = 30) {
    $logs = getAuditTrail($conn, $filters, $limit);
    if (empty($logs)) {
        return '<div class="text-center text-muted py-4"><i class="fas fa-history fa-2x mb-2"></i><div>No audit records found.</div></div>';
    }
    $html = '<div class="table-responsive"><table class="table table-sm table-hover audit-table"><thead><tr>
        <th>Date/Time</th><th>Staff</th><th>Action</th><th>Category</th><th>Description</th><th>Details</th>
    </tr></thead><tbody>';
    foreach ($logs as $log) {
        $actionBadge = 'bg-secondary';
        if ($log['action'] === 'CREATE' || $log['action'] === 'APPROVE') $actionBadge = 'bg-success';
        elseif ($log['action'] === 'UPDATE' || $log['action'] === 'REVIEW') $actionBadge = 'bg-info';
        elseif ($log['action'] === 'DELETE' || $log['action'] === 'REJECT') $actionBadge = 'bg-danger';
        elseif ($log['action'] === 'LOGIN') $actionBadge = 'bg-primary';
        $hasChanges = !empty($log['previous_values']) || !empty($log['new_values']);
        $html .= '<tr>';
        $html .= '<td class="small">' . date('d M Y H:i', strtotime($log['created_at'])) . '</td>';
        $html .= '<td><span class="fw-semibold small">' . htmlspecialchars($log['staff_name']) . '</span><br><span class="text-muted" style="font-size:10px">' . htmlspecialchars($log['role_name']) . '</span></td>';
        $html .= '<td><span class="badge ' . $actionBadge . '" style="font-size:9px">' . htmlspecialchars($log['action']) . '</span></td>';
        $html .= '<td><span class="badge bg-secondary" style="font-size:9px">' . htmlspecialchars($log['category']) . '</span></td>';
        $html .= '<td class="small">' . htmlspecialchars($log['description']) . '</td>';
        $html .= '<td>';
        if ($log['table_affected']) $html .= '<span class="text-muted" style="font-size:10px">' . htmlspecialchars($log['table_affected']) . ($log['record_id'] ? ' #' . $log['record_id'] : '') . '</span>';
        if ($hasChanges) {
            $html .= '<button class="btn btn-sm btn-link p-0 ms-1" onclick="toggleAuditDetails(' . $log['id'] . ')" style="font-size:10px"><i class="fas fa-eye"></i></button>';
            $html .= '<div id="auditDetails' . $log['id'] . '" style="display:none" class="mt-1 p-2 bg-light rounded small">';
            if ($log['previous_values']) $html .= '<div><strong>Before:</strong><pre class="mb-0" style="font-size:9px">' . htmlspecialchars(json_encode(json_decode($log['previous_values'], true), JSON_PRETTY_PRINT)) . '</pre></div>';
            if ($log['new_values']) $html .= '<div><strong>After:</strong><pre class="mb-0" style="font-size:9px">' . htmlspecialchars(json_encode(json_decode($log['new_values'], true), JSON_PRETTY_PRINT)) . '</pre></div>';
            $html .= '</div>';
        }
        $html .= '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    $html .= '<script>function toggleAuditDetails(id){var el=document.getElementById("auditDetails"+id);if(el)el.style.display=el.style.display==="none"?"":"none";}</script>';
    return $html;
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 4. DIRECTOR PERFORMANCE MONITORING
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('getDirectorPerformance')) {
function getDirectorPerformance($staffId, $conn) {
    if (!$conn) return null;
    try {
        $stmt = $conn->prepare("SELECT * FROM director_performance_reviews WHERE staff_id = ? ORDER BY id DESC LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    } catch (Exception $e) {
        error_log('institutional_framework getPriority: ' . $e->getMessage());
        return null;
    }
}
}

if (!function_exists('getDepartmentTargets')) {
function getDepartmentTargets($departmentCode, $fiscalYear, $conn) {
    $targets = [];
    if (!$conn) return $targets;
    try {
        $stmt = $conn->prepare("SELECT * FROM department_targets WHERE department_code = ? AND (fiscal_year = ? OR fiscal_year = '') ORDER BY target_category, target_name");
        if (!$stmt) return $targets;
        $stmt->bind_param('ss', $departmentCode, $fiscalYear);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $targets[] = $row;
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework getDepartmentTargets: ' . $e->getMessage()); }
    return $targets;
}
}

if (!function_exists('calculatePerformanceScore')) {
function calculatePerformanceScore($targets) {
    if (empty($targets)) return ['score' => 0, 'total' => 0, 'achieved' => 0, 'missed' => 0, 'inProgress' => 0];
    $total = 0; $achieved = 0; $missed = 0; $inProgress = 0; $weightedSum = 0; $totalWeight = 0;
    foreach ($targets as $t) {
        $w = (int)($t['weight'] ?? 1);
        $totalWeight += $w;
        $tv = (float)($t['target_value'] ?? 0);
        $av = (float)($t['achieved_value'] ?? 0);
        $status = $t['status'] ?? 'Not Started';
        if ($status === 'Achieved' || $status === 'Exceeded') { $achieved++; $weightedSum += $w; }
        elseif ($status === 'Missed') { $missed++; }
        else { $inProgress++; $weightedSum += $w * 0.5; }
        $total++;
    }
    $score = $totalWeight > 0 ? round(($weightedSum / $totalWeight) * 100) : 0;
    return ['score' => $score, 'total' => $total, 'achieved' => $achieved, 'missed' => $missed, 'inProgress' => $inProgress];
}
}

if (!function_exists('renderPerformanceGauge')) {
function renderPerformanceGauge($score, $size = 120) {
    $score = max(0, min(100, (int)$score));
    $color = $score >= 80 ? '#22c55e' : ($score >= 60 ? '#eab308' : ($score >= 40 ? '#f97316' : '#ef4444'));
    $label = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Good' : ($score >= 40 ? 'Fair' : 'Poor'));
    $radius = ($size / 2) - 10;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - ($score / 100) * $circumference;
    $cx = $size / 2; $cy = $size / 2;
    return '<div class="performance-gauge-wrap text-center" style="width:' . $size . 'px;height:' . $size . 'px;margin:0 auto">'
        . '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">'
        . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" fill="none" stroke="#e5e7eb" stroke-width="8"/>'
        . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" fill="none" stroke="' . $color . '" stroke-width="8" stroke-dasharray="' . $circumference . '" stroke-dashoffset="' . $offset . '" stroke-linecap="round" transform="rotate(-90 ' . $cx . ' ' . $cy . ')" style="transition: stroke-dashoffset 1s ease"/>'
        . '<text x="' . $cx . '" y="' . ($cy - 2) . '" text-anchor="middle" font-size="' . ($size * 0.18) . '" font-weight="700" fill="' . $color . '">' . $score . '%</text>'
        . '<text x="' . $cx . '" y="' . ($cy + 14) . '" text-anchor="middle" font-size="' . ($size * 0.09) . '" fill="#6b7280">' . $label . '</text>'
        . '</svg></div>';
}
}

if (!function_exists('renderDirectorPerformanceCard')) {
function renderDirectorPerformanceCard($staffId, $roleId, $roleName, $conn) {
    $perf = getDirectorPerformance($staffId, $conn);
    $deptCode = '';
    try {
        $dq = $conn->prepare("SELECT department_code FROM director_departments WHERE role_id = ? AND is_primary = 1 LIMIT 1");
        if ($dq) { $dq->bind_param('i', $roleId); $dq->execute(); $dr = $dq->get_result()->fetch_assoc(); $dq->close(); if ($dr) $deptCode = $dr['department_code']; }
    } catch (Exception $e) { error_log('inst_framework getDirectorPerformance: ' . $e->getMessage()); }
    $targets = $deptCode ? getDepartmentTargets($deptCode, date('Y'), $conn) : [];
    $ps = calculatePerformanceScore($targets);
    $html = '<div class="director-perf-card">';
    $html .= '  <div class="d-flex align-items-center gap-3">';
    $html .= '    <div class="perf-avatar"><i class="fas fa-user-tie fa-2x text-primary"></i></div>';
    $html .= '    <div class="flex-grow-1">';
    $html .= '      <h6 class="mb-0 fw-semibold">' . htmlspecialchars($roleName) . '</h6>';
    $html .= '      <span class="text-muted small">' . ($deptCode ? htmlspecialchars($deptCode) : 'No department assigned') . '</span>';
    $html .= '    </div>';
    $score = $ps['score'];
    $html .= '    <div class="perf-score-circle ' . ($score >= 80 ? 'text-success' : ($score >= 60 ? 'text-warning' : 'text-danger')) . ' fw-bold">' . $score . '%</div>';
    $html .= '  </div>';
    $html .= '  <div class="row g-2 mt-2 text-center small">';
    $html .= '    <div class="col-3"><div class="perf-stat"><span class="fw-bold text-success">' . $ps['achieved'] . '</span><br><span class="text-muted" style="font-size:9px">Done</span></div></div>';
    $html .= '    <div class="col-3"><div class="perf-stat"><span class="fw-bold text-warning">' . $ps['inProgress'] . '</span><br><span class="text-muted" style="font-size:9px">Active</span></div></div>';
    $html .= '    <div class="col-3"><div class="perf-stat"><span class="fw-bold text-danger">' . $ps['missed'] . '</span><br><span class="text-muted" style="font-size:9px">Missed</span></div></div>';
    $html .= '    <div class="col-3"><div class="perf-stat"><span class="fw-bold text-info">' . $ps['total'] . '</span><br><span class="text-muted" style="font-size:9px">Total</span></div></div>';
    $html .= '  </div>';
    if ($perf) {
        $html .= '  <div class="mt-2 pt-2 border-top small text-muted">';
        $html .= '    Rating: <strong>' . htmlspecialchars($perf['performance_rating']) . '</strong> &middot; Period: ' . htmlspecialchars($perf['review_period']);
        $html .= '  </div>';
    }
    $html .= '</div>';
    return $html;
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 5. INTELLIGENT MANAGEMENT ALERTS
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('createAlert')) {
function createAlert($title, $message, $type = 'info', $priority = 'Medium', $category = 'other', $departmentCode = null, $sourceUrl = null, $isAuto = false, $conn = null) {
    if (!$conn) {
        if (function_exists('getStaffConnection')) $conn = getStaffConnection();
    }
    if (!$conn) return false;
    try {
        $typeMap = ['warning' => 'warning', 'danger' => 'danger', 'info' => 'info', 'success' => 'success', 'critical' => 'critical'];
        $aType = $typeMap[$type] ?? 'info';
        $stmt = $conn->prepare("INSERT INTO institutional_alerts (alert_title, alert_message, alert_type, priority, category, department_code, source_url, is_auto_generated, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) return false;
        $auto = $isAuto ? 1 : 0;
        $stmt->bind_param('sssssssi', $title, $message, $aType, $priority, $category, $departmentCode, $sourceUrl, $auto);
        $r = $stmt->execute();
        $stmt->close();
        if ($r) recordAuditTrail($_SESSION['user_id'] ?? 0, 'CREATE', 'Alert', 'Alert created: ' . $title, 'institutional_alerts', null, $title, null, null, $conn);
        return $r;
    } catch (Exception $e) {
        error_log('createAlert error: ' . $e->getMessage());
        return false;
    }
}
}

if (!function_exists('getAlerts')) {
function getAlerts($conn, $departmentCode = null, $limit = 20, $unresolvedOnly = true) {
    $alerts = [];
    if (!$conn) return $alerts;
    try {
        $sql = "SELECT * FROM institutional_alerts WHERE 1=1";
        if ($unresolvedOnly) $sql .= " AND is_resolved = 0";
        if ($departmentCode) $sql .= " AND (department_code = ? OR department_code IS NULL)";
        $sql .= " ORDER BY FIELD(priority,'Critical','High','Medium','Low') ASC, created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return $alerts;
        if ($departmentCode) $stmt->bind_param('si', $departmentCode, $limit);
        else $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $alerts[] = $row;
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework getAlerts: ' . $e->getMessage()); }
    return $alerts;
}
}

if (!function_exists('renderAlertsPanel')) {
function renderAlertsPanel($conn, $departmentCode = null, $limit = 10) {
    $alerts = getAlerts($conn, $departmentCode, $limit);
    if (empty($alerts)) {
        return '<div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><div>No active alerts. All clear!</div></div>';
    }
    $html = '<div class="alerts-list">';
    foreach ($alerts as $alert) {
        $icon = 'fa-info-circle'; $bg = 'alert-info';
        if ($alert['alert_type'] === 'danger' || $alert['alert_type'] === 'critical') { $icon = 'fa-exclamation-triangle'; $bg = 'alert-danger'; }
        elseif ($alert['alert_type'] === 'warning') { $icon = 'fa-exclamation-circle'; $bg = 'alert-warning'; }
        elseif ($alert['alert_type'] === 'success') { $icon = 'fa-check-circle'; $bg = 'alert-success'; }
        $pClass = 'badge bg-' . ($alert['priority'] === 'Critical' ? 'danger' : ($alert['priority'] === 'High' ? 'warning' : ($alert['priority'] === 'Medium' ? 'info' : 'secondary')));
        $html .= '<div class="alert-item ' . $bg . '">';
        $html .= '  <div class="d-flex align-items-start gap-3">';
        $html .= '    <i class="fas ' . $icon . ' mt-1" style="font-size:1.1rem"></i>';
        $html .= '    <div class="flex-grow-1">';
        $html .= '      <div class="fw-semibold small">' . htmlspecialchars($alert['title']) . '</div>';
        $html .= '      <div class="small text-muted">' . htmlspecialchars($alert['description']) . '</div>';
        $html .= '      <div class="mt-1 d-flex gap-2 align-items-center">';
        $html .= '        <span class="' . $pClass . '" style="font-size:8px">' . htmlspecialchars($alert['priority']) . '</span>';
        $html .= '        <span class="text-muted" style="font-size:9px">' . date('d M H:i', strtotime($alert['created_at'])) . '</span>';
        if ($alert['alert_type']) $html .= '        <span class="badge bg-secondary" style="font-size:8px">' . htmlspecialchars($alert['alert_type']) . '</span>';
        $html .= '      </div>';
        $html .= '    </div>';
        if ($alert['source']) $html .= '    <a href="' . htmlspecialchars($alert['source']) . '" class="btn btn-sm btn-outline-secondary" style="font-size:10px"><i class="fas fa-external-link-alt"></i></a>';
        $html .= '  </div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
}

if (!function_exists('getAlertCounts')) {
function getAlertCounts($conn, $departmentCode = null) {
    $counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'total' => 0];
    if (!$conn) return $counts;
    try {
        $sql = "SELECT priority, COUNT(*) as cnt FROM institutional_alerts WHERE is_resolved = 0";
        if ($departmentCode) $sql .= " AND (department_code = ? OR department_code IS NULL)";
        $sql .= " GROUP BY priority";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return $counts;
        if ($departmentCode) $stmt->bind_param('s', $departmentCode);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $k = strtolower($row['priority']);
            if (isset($counts[$k])) $counts[$k] = (int)$row['cnt'];
            $counts['total'] += (int)$row['cnt'];
        }
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework getAlertCounts: ' . $e->getMessage()); }
    return $counts;
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 6. COMPLIANCE & RISK
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('getComplianceStatus')) {
function getComplianceStatus($conn) {
    $status = ['compliant' => 0, 'non_compliant' => 0, 'overdue' => 0, 'in_progress' => 0, 'not_started' => 0, 'total' => 0];
    if (!$conn) return $status;
    try {
        $result = $conn->query("SELECT status, COUNT(*) as cnt FROM compliance_requirements GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $s = strtolower(str_replace(' ', '_', $row['status']));
                $map = ['compliant' => 'compliant', 'non-compliant' => 'non_compliant', 'non_compliant' => 'non_compliant', 'overdue' => 'overdue', 'in_progress' => 'in_progress', 'not_started' => 'not_started'];
                $k = $map[$s] ?? 'not_started';
                if (isset($status[$k])) $status[$k] = (int)$row['cnt'];
                $status['total'] += (int)$row['cnt'];
            }
        }
    } catch (Exception $e) { error_log('inst_framework getStatusCounts: ' . $e->getMessage()); }
    return $status;
}
}

if (!function_exists('renderComplianceBadge')) {
function renderComplianceBadge($status) {
    $map = [
        'compliant' => ['class' => 'bg-success', 'icon' => 'fa-check-circle'],
        'non_compliant' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle'],
        'overdue' => ['class' => 'bg-warning', 'icon' => 'fa-exclamation-circle'],
        'in_progress' => ['class' => 'bg-info', 'icon' => 'fa-spinner fa-spin'],
        'not_started' => ['class' => 'bg-secondary', 'icon' => 'fa-circle'],
    ];
    $s = strtolower(str_replace(' ', '_', $status));
    $m = $map[$s] ?? $map['not_started'];
    return '<span class="badge ' . $m['class'] . '" style="font-size:9px"><i class="fas ' . $m['icon'] . ' me-1"></i>' . htmlspecialchars($status) . '</span>';
}
}

if (!function_exists('getTopRisks')) {
function getTopRisks($conn, $limit = 5) {
    $risks = [];
    if (!$conn) return $risks;
    try {
        $result = $conn->query("SELECT * FROM institutional_risks WHERE status NOT IN ('Closed','Mitigated') ORDER BY risk_score DESC LIMIT " . (int)$limit);
        if ($result) {
            while ($row = $result->fetch_assoc()) $risks[] = $row;
        }
    } catch (Exception $e) { error_log('inst_framework getRiskHeatmap: ' . $e->getMessage()); }
    return $risks;
}
}

if (!function_exists('renderRiskBadge')) {
function renderRiskBadge($score) {
    $score = (int)$score;
    if ($score >= 20) return '<span class="badge bg-danger" style="font-size:9px">Critical (' . $score . ')</span>';
    if ($score >= 12) return '<span class="badge bg-warning text-dark" style="font-size:9px">High (' . $score . ')</span>';
    if ($score >= 6) return '<span class="badge bg-info" style="font-size:9px">Medium (' . $score . ')</span>';
    return '<span class="badge bg-success" style="font-size:9px">Low (' . $score . ')</span>';
}
}

// ──────────────────────────────────────────────────────────────────────────────
// 7. PENDING APPROVALS
// ──────────────────────────────────────────────────────────────────────────────

if (!function_exists('getPendingApprovals')) {
function getPendingApprovals($conn, $roleId = null, $limit = 20) {
    $approvals = [];
    if (!$conn) return $approvals;
    try {
        $sql = "SELECT ar.*, aw.workflow_name, aw.category as workflow_category, 
                       ast.stage_name as current_stage_name
                FROM approval_requests ar
                JOIN approval_workflows aw ON ar.workflow_id = aw.id
                LEFT JOIN approval_stages ast ON ar.current_stage_id = ast.id
                WHERE ar.status = 'Active'";
        if ($roleId) $sql .= " AND ast.assigned_role_id = ?";
        $sql .= " ORDER BY FIELD(ar.priority,'Critical','High','Medium','Low') ASC, ar.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return $approvals;
        if ($roleId) $stmt->bind_param('ii', $roleId, $limit);
        else $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $approvals[] = $row;
        $stmt->close();
    } catch (Exception $e) { error_log('inst_framework getPendingApprovals: ' . $e->getMessage()); }
    return $approvals;
}
}
