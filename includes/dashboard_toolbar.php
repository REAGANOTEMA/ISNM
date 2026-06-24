<?php
/**
 * Shared Dashboard Toolbar System
 * Consistent top toolbar for ALL dashboards.
 * Drop-in replacement for per-dashboard inline toolbars.
 */
if (isset($GLOBALS['_dashboard_toolbar_loaded'])) return;
$GLOBALS['_dashboard_toolbar_loaded'] = true;

if (!function_exists('renderDashboardToolbar')) {
function renderDashboardToolbar($title, $icon = 'fas fa-tachometer-alt', $options = []) {
    $subtitle = $options['subtitle'] ?? '';
    $badge = $options['badge'] ?? null;
    $search = $options['search'] ?? false;
    $searchPlaceholder = $options['search_placeholder'] ?? 'Search...';
    $searchTarget = $options['search_target'] ?? '';
    $print = $options['print'] ?? false;
    $export = $options['export'] ?? false;
    $actions = $options['actions'] ?? [];

    $ic = '<i class="'.htmlspecialchars($icon).' me-2" style="color:#3b82f6;"></i>';

    $rightHtml = '';
    if ($search) {
        $oninput = $searchTarget ? "filterDataTable('".htmlspecialchars($searchTarget)."',this.value)" : '';
        $rightHtml .= '<div class="input-group" style="max-width:260px;">
            <input type="text" class="form-control form-control-sm" placeholder="'.htmlspecialchars($searchPlaceholder).'" oninput="'.$oninput.'" style="border-radius:8px 0 0 8px;font-size:12px;border:1px solid #d1d5db;">
            <button class="btn btn-sm btn-primary" style="border-radius:0 8px 8px 0;"><i class="fas fa-search"></i></button>
        </div>';
    }
    foreach ($actions as $a) {
        $url = $a['url'] ?? '#';
        $label = $a['label'] ?? '';
        $aic = isset($a['icon']) ? '<i class="'.htmlspecialchars($a['icon']).' me-1"></i>' : '';
        $cls = $a['class'] ?? 'btn-outline-primary';
        $onclick = isset($a['onclick']) ? ' onclick="'.htmlspecialchars($a['onclick']).'"' : '';
        $rightHtml .= '<a href="'.htmlspecialchars($url).'" class="btn btn-sm '.$cls.'"'.$onclick.' style="border-radius:8px;font-size:12px;white-space:nowrap;">'.$aic.htmlspecialchars($label).'</a>';
    }
    if ($print) {
        $rightHtml .= '<button onclick="window.print()" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:12px;" title="Print"><i class="fas fa-print"></i></button>';
    }
    if ($export) {
        $rightHtml .= '<button onclick="exportTableToCSV()" class="btn btn-sm btn-outline-success" style="border-radius:8px;font-size:12px;" title="Export CSV"><i class="fas fa-download"></i></button>';
    }

    $badgeHtml = $badge ? ' <span class="badge rounded-pill px-2 py-1" style="font-size:10px;background:#dc2626;color:#fff;vertical-align:middle;">'.$badge.'</span>' : '';

    return '<div class="dashboard-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-3" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;">'.
        '<div><h5 class="fw-bold mb-0" style="color:#0f172a;font-size:16px;">'.$ic.htmlspecialchars($title).$badgeHtml.'</h5>'.
        ($subtitle ? '<p class="text-muted mb-0" style="font-size:12px;margin-top:2px;">'.htmlspecialchars($subtitle).'</p>' : '').'</div>'.
        ($rightHtml ? '<div class="d-flex gap-2 align-items-center flex-wrap">'.$rightHtml.'</div>' : '').'</div>';
}
}

if (!function_exists('renderDashboardBreadcrumb')) {
function renderDashboardBreadcrumb($crumbs) {
    if (empty($crumbs)) return '';
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-3" style="background:transparent;padding:0;font-size:12px;">';
    $i = 0;
    foreach ($crumbs as $label => $url) {
        $i++;
        if ($i === count($crumbs)) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">'.htmlspecialchars($label).'</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="'.htmlspecialchars($url).'" style="color:#1a237e;text-decoration:none;">'.htmlspecialchars($label).'</a></li>';
        }
    }
    $html .= '</ol></nav>';
    return $html;
}
}

// Expose export function to JS
if (!function_exists('renderExportTableScript')) {
function renderExportTableScript() {
    static $exported = false;
    if ($exported) return '';
    $exported = true;
    ob_start();
    ?>
    <script>
    window.exportTableToCSV = function(tblId, filename) {
        tblId = tblId || null;
        filename = filename || 'export_<?= date('Ymd') ?>.csv';
        var tables = tblId ? [document.getElementById(tblId)] : document.querySelectorAll('table');
        if (!tables.length) { alert('No table found to export.'); return; }
        var csv = [];
        tables.forEach(function(tbl) {
            if (!tbl) return;
            var rows = tbl.querySelectorAll('tr');
            rows.forEach(function(row) {
                var cols = row.querySelectorAll('td, th');
                var vals = [];
                cols.forEach(function(c) { vals.push('"' + (c.textContent || '').trim().replace(/"/g, '""') + '"'); });
                csv.push(vals.join(','));
            });
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a'); link.href = URL.createObjectURL(blob);
        link.download = filename; document.body.appendChild(link); link.click();
        document.body.removeChild(link); URL.revokeObjectURL(link.href);
    };
    </script>
    <?php
    return ob_get_clean();
}
}
