<?php
/**
 * Shared Enterprise Dashboard Component Library
 * Drop-in reusable components for ALL dashboards.
 * Include once via dashboard_footer.php — cascades to every page.
 */
if (isset($GLOBALS['_dashboard_components_loaded'])) return;
$GLOBALS['_dashboard_components_loaded'] = true;

// ─── KPI / Stat Card ───
if (!function_exists('renderKpiCard')) {
function renderKpiCard($label, $value, $icon = 'fas fa-chart-line', $color = 'primary', $link = null, $trend = null) {
    $colors = [
        'primary' => ['bg'=>'#eef2ff','icon'=>'#4f46e5','text'=>'#1e1b4b'],
        'success' => ['bg'=>'#f0fdf4','icon'=>'#16a34a','text'=>'#052e16'],
        'warning' => ['bg'=>'#fffbeb','icon'=>'#d97706','text'=>'#451a03'],
        'danger'  => ['bg'=>'#fef2f2','icon'=>'#dc2626','text'=>'#450a0a'],
        'info'    => ['bg'=>'#ecfeff','icon'=>'#0891b2','text'=>'#083344'],
        'purple'  => ['bg'=>'#f5f3ff','icon'=>'#7c3aed','text'=>'#2e1065'],
        'pink'    => ['bg'=>'#fdf2f8','icon'=>'#db2777','text'=>'#4a051c'],
        'teal'    => ['bg'=>'#f0fdfa','icon'=>'#0d9488','text'=>'#022c22'],
    ];
    $c = $colors[$color] ?? $colors['primary'];
    $trendHtml = '';
    if ($trend !== null) {
        $up = $trend >= 0;
        $trendHtml = '<span style="font-size:11px;color:'.($up?'#16a34a':'#dc2626').';font-weight:600;"><i class="fas fa-'.($up?'arrow-up':'arrow-down').' me-1"></i>'.abs($trend).'%</span>';
    }
    $linkStart = $link ? '<a href="'.htmlspecialchars($link).'" style="text-decoration:none;color:inherit;display:block;">' : '';
    $linkEnd = $link ? '</a>' : '';
    return $linkStart.'<div class="kpi-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;transition:all 0.25s;cursor:'.($link?'pointer':'default').';">'.
        '<div class="d-flex align-items-center gap-3">'.
        '<div style="width:46px;height:46px;border-radius:12px;background:'.$c['bg'].';display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="'.$icon.'" style="color:'.$c['icon'].';font-size:20px;"></i></div>'.
        '<div style="flex:1;min-width:0;"><div style="font-size:22px;font-weight:800;color:'.$c['text'].';line-height:1.2;">'.$value.'</div>'.
        '<div style="font-size:12px;color:#64748b;font-weight:500;margin-top:2px;">'.$label.'</div></div>'.
        ($trendHtml ? '<div style="flex-shrink:0;">'.$trendHtml.'</div>' : '').
        '</div></div>'.$linkEnd;
}
}

// ─── Status Badge ───
if (!function_exists('renderStatusBadge')) {
function renderStatusBadge($status, $size = 'sm') {
    $map = [
        'active' => ['bg'=>'#16a34a','label'=>'Active'],
        'inactive' => ['bg'=>'#94a3b8','label'=>'Inactive'],
        'pending' => ['bg'=>'#d97706','label'=>'Pending'],
        'approved' => ['bg'=>'#16a34a','label'=>'Approved'],
        'rejected' => ['bg'=>'#dc2626','label'=>'Rejected'],
        'returned' => ['bg'=>'#7c3aed','label'=>'Returned'],
        'fulfilled' => ['bg'=>'#059669','label'=>'Fulfilled'],
        'cancelled' => ['bg'=>'#64748b','label'=>'Cancelled'],
        'draft' => ['bg'=>'#94a3b8','label'=>'Draft'],
        'pending_approval' => ['bg'=>'#d97706','label'=>'Pending DG'],
        'completed' => ['bg'=>'#059669','label'=>'Completed'],
        'paid' => ['bg'=>'#16a34a','label'=>'Paid'],
        'unpaid' => ['bg'=>'#dc2626','label'=>'Unpaid'],
        'overdue' => ['bg'=>'#dc2626','label'=>'Overdue'],
        'partial' => ['bg'=>'#d97706','label'=>'Partial'],
        'yes' => ['bg'=>'#16a34a','label'=>'Yes'],
        'no' => ['bg'=>'#dc2626','label'=>'No'],
        'true' => ['bg'=>'#16a34a','label'=>'Yes'],
        'false' => ['bg'=>'#dc2626','label'=>'No'],
        'urgent' => ['bg'=>'#dc2626','label'=>'Urgent'],
        'high' => ['bg'=>'#f97316','label'=>'High'],
        'medium' => ['bg'=>'#3b82f6','label'=>'Medium'],
        'low' => ['bg'=>'#94a3b8','label'=>'Low'],
        'normal' => ['bg'=>'#3b82f6','label'=>'Normal'],
        'critical' => ['bg'=>'#dc2626','label'=>'Critical'],
    ];
    $s = strtolower(trim($status));
    $m = $map[$s] ?? ['bg'=>'#64748b','label'=>ucfirst($status)];
    $font = $size === 'sm' ? '9px' : '11px';
    $pad = $size === 'sm' ? '2px 8px' : '4px 12px';
    return '<span class="status-badge" style="display:inline-block;background:'.$m['bg'].'20;color:'.$m['bg'].';border:1px solid '.$m['bg'].'40;border-radius:20px;padding:'.$pad.';font-size:'.$font.';font-weight:600;white-space:nowrap;">'.$m['label'].'</span>';
}
}

// ─── Empty State ───
if (!function_exists('renderEmptyState')) {
function renderEmptyState($message, $icon = 'fas fa-inbox', $extra = '') {
    return '<div class="empty-state" style="text-align:center;padding:40px 20px;">'.
        '<div style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"><i class="' . htmlspecialchars($icon) . '"></i></div>'.
        '<h6 style="color:#475569;font-weight:600;margin-bottom:4px;">' . htmlspecialchars($message) . '</h6>'.
        ($extra ? '<p style="color:#94a3b8;font-size:13px;margin-bottom:0;">' . $extra . '</p>' : '').
        '</div>';
}
}

// ─── Loading Spinner ───
if (!function_exists('renderLoadingSpinner')) {
function renderLoadingSpinner($text = 'Loading...') {
    return '<div class="text-center py-5"><div class="spinner-border" style="width:2.5rem;height:2.5rem;color:#1a237e;" role="status"><span class="visually-hidden">'.$text.'</span></div>'.
        '<p class="mt-2 text-muted" style="font-size:13px;">'.$text.'</p></div>';
}
}

// ─── Action Button ───
if (!function_exists('renderActionButton')) {
function renderActionButton($label, $icon = null, $url = '#', $style = 'primary', $onclick = null) {
    $styles = [
        'primary' => 'background:#1a237e;color:#fff;',
        'success' => 'background:#16a34a;color:#fff;',
        'danger'  => 'background:#dc2626;color:#fff;',
        'warning' => 'background:#d97706;color:#fff;',
        'info'    => 'background:#0891b2;color:#fff;',
        'outline' => 'background:transparent;color:#1a237e;border:1px solid #1a237e;',
        'ghost'   => 'background:transparent;color:#64748b;border:none;',
    ];
    $s = $styles[$style] ?? $styles['primary'];
    $ic = $icon ? '<i class="'.htmlspecialchars($icon).' me-1"></i>' : '';
    $oc = $onclick ? ' onclick="'.htmlspecialchars($onclick).'"' : '';
    $tag = $url && $url !== '#' ? 'a href="'.htmlspecialchars($url).'"' : 'button type="button"';
    return '<'.$tag.$oc.' style="display:inline-flex;align-items:center;'.$s.'border:none;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:600;text-decoration:none;cursor:pointer;transition:all 0.15s;">'.$ic.htmlspecialchars($label).'</'.($url && $url !== '#' ? 'a' : 'button').'>';
}
}

// ─── Data Table Wrapper ───
if (!function_exists('renderDataTable')) {
function renderDataTable($headers, $rows, $options = []) {
    $id = $options['id'] ?? 'dt_' . uniqid();
    $searchable = $options['searchable'] ?? true;
    $responsive = $options['responsive'] ?? true;
    $emptyMsg = $options['empty_message'] ?? 'No records found.';
    $class = $options['class'] ?? '';

    $html = '<div class="data-table-wrapper" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">';
    if ($searchable) {
        $html .= '<div class="p-3 border-bottom" style="background:#f8fafc;"><input type="text" class="form-control form-control-sm" oninput="filterDataTable(\''.$id.'\',this.value)" placeholder="Search '.($options['search_placeholder'] ?? '...').'" style="max-width:300px;border-radius:8px;font-size:13px;"></div>';
    }
    $html .= '<div style="overflow-x:'.($responsive?'auto':'visible').';">';
    $html .= '<table id="'.$id.'" class="table table-hover mb-0 '.$class.'" style="font-size:13px;width:100%;">';
    $html .= '<thead style="background:#f1f5f9;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;"><tr>';
    foreach ($headers as $h) {
        $w = isset($h['width']) ? ' style="width:'.$h['width'].';"' : '';
        $html .= '<th'.$w.'>'.htmlspecialchars($h['label'] ?? $h).'</th>';
    }
    $html .= '</tr></thead><tbody>';
    if (empty($rows)) {
        $colspan = count($headers);
        $html .= '<tr><td colspan="'.$colspan.'" class="text-center text-muted py-4" style="font-size:13px;">'.$emptyMsg.'</td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr style="transition:background 0.15s;">';
            foreach ($row as $cell) {
                $html .= '<td style="padding:10px 12px;vertical-align:middle;">'.$cell.'</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table></div></div>';
    $html .= '<script>window.filterDataTable=window.filterDataTable||function(tblId,q){var t=document.getElementById(tblId);if(!t)return;var r=t.querySelectorAll(\'tbody tr\');q=q.toLowerCase();r.forEach(function(row){var txt=row.textContent.toLowerCase();row.style.display=txt.indexOf(q)>-1?\'\':\'none\';});};</script>';
    return $html;
}
}

// ─── Confirm Action Modal ───
if (!function_exists('renderConfirmActionModal')) {
function renderConfirmActionModal($id = 'confirmActionModal') {
    static $rendered = false;
    if ($rendered) return '';
    $rendered = true;
    ob_start();
    ?>
    <div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border:none;border-radius:14px;">
                <div class="modal-body text-center p-4">
                    <div style="font-size:44px;color:#f59e0b;margin-bottom:12px;"><i class="fas fa-exclamation-triangle"></i></div>
                    <h6 class="fw-bold mb-2" id="<?= $id ?>Title">Confirm Action</h6>
                    <p style="font-size:13px;color:#64748b;margin-bottom:16px;" id="<?= $id ?>Message">Are you sure?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px;font-size:13px;">Cancel</button>
                        <button type="button" class="btn btn-danger" id="<?= $id ?>ConfirmBtn" style="border-radius:8px;font-size:13px;">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
}

// ─── Flash Message Renderer ───
if (!function_exists('renderFlashMessages')) {
function renderFlashMessages() {
    $html = '';
    if (!empty($_SESSION['success'])) {
        $html .= '<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:13px;border-left:4px solid #16a34a;"><i class="fas fa-check-circle me-1"></i> '.$_SESSION['success'].'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success']);
    }
    if (!empty($_SESSION['error'])) {
        $html .= '<div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;font-size:13px;border-left:4px solid #dc2626;"><i class="fas fa-times-circle me-1"></i> '.$_SESSION['error'].'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error']);
    }
    if (!empty($_SESSION['info'])) {
        $html .= '<div class="alert alert-info alert-dismissible fade show" style="border-radius:10px;font-size:13px;border-left:4px solid #0891b2;"><i class="fas fa-info-circle me-1"></i> '.$_SESSION['info'].'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['info']);
    }
    if (!empty($_SESSION['warning'])) {
        $html .= '<div class="alert alert-warning alert-dismissible fade show" style="border-radius:10px;font-size:13px;border-left:4px solid #d97706;"><i class="fas fa-exclamation-triangle me-1"></i> '.$_SESSION['warning'].'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['warning']);
    }
    return $html;
}
}

// ─── Section Header ───
if (!function_exists('renderSectionHeader')) {
function renderSectionHeader($title, $subtitle = '', $icon = null, $actions = []) {
    $ic = $icon ? '<i class="'.htmlspecialchars($icon).' me-2" style="color:#3b82f6;"></i>' : '';
    $actionsHtml = '';
    if (!empty($actions)) {
        foreach ($actions as $a) {
            $url = $a['url'] ?? '#';
            $label = $a['label'] ?? '';
            $icon2 = $a['icon'] ?? null;
            $class = $a['class'] ?? 'btn-outline-primary';
            $ic2 = $icon2 ? '<i class="'.htmlspecialchars($icon2).' me-1"></i>' : '';
            $onclick = isset($a['onclick']) ? ' onclick="'.htmlspecialchars($a['onclick']).'"' : '';
            $actionsHtml .= '<a href="'.htmlspecialchars($url).'" class="btn btn-sm '.$class.'"'.$onclick.' style="border-radius:8px;font-size:12px;">'.$ic2.htmlspecialchars($label).'</a>';
        }
    }
    return '<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">'.
        '<div><h5 class="fw-bold mb-0" style="color:#0f172a;font-size:16px;">'.$ic.htmlspecialchars($title).'</h5>'.
        ($subtitle ? '<p class="text-muted mb-0" style="font-size:12px;">'.htmlspecialchars($subtitle).'</p>' : '').'</div>'.
        ($actionsHtml ? '<div class="d-flex gap-2">'.$actionsHtml.'</div>' : '').'</div>';
}
}
