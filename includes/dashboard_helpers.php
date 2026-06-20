<?php
/**
 * Shared dashboard helpers — print, flash messages, filtering.
 * Include after bootstrapStaffDashboard() in any dashboard.
 */

if (!function_exists('renderFlashMessages')) {
    function renderFlashMessages(): void {
        if (!empty($_SESSION['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show py-2">'
                . htmlspecialchars($_SESSION['success'])
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['success']);
        }
        if (!empty($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show py-2">'
                . htmlspecialchars($_SESSION['error'])
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['error']);
        }
    }
}

if (!function_exists('printStyleTag')) {
    function printStyleTag(): string {
        return '<style>
@media print {
  .no-print, .sidebar, .section-tabs, .top-bar, footer, .btn { display:none !important; }
  body { background:#fff !important; font-size:10pt; }
  .page-content, .content-area { margin:0 !important; padding:0 !important; max-width:100% !important; }
  .card, .health-card, .section-card, .stat-card { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; }
  .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>';
    }
}

if (!function_exists('printHeaderBlock')) {
    function printHeaderBlock(string $title, string $subtitle = ''): string {
        return '<div class="top-bar no-print"><div><strong><i class="fas fa-chart-line me-2 text-primary"></i>'
            . htmlspecialchars($title)
            . '</strong>'
            . ($subtitle ? '<div class="text-muted small">' . htmlspecialchars($subtitle) . '</div>' : '')
            . '</div><div class="d-flex align-items-center gap-3">'
            . '<span class="text-muted small d-none d-md-block">' . date('D, d M Y') . '</span>'
            . '<button class="btn btn-sm btn-outline-success no-print" onclick="window.print()"><i class="fas fa-print me-1"></i></button>'
            . '<a href="../logout.php" class="btn btn-sm btn-outline-danger no-print"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>'
            . '</div></div>';
    }
}

if (!function_exists('quickSearchBox')) {
    function quickSearchBox(string $action, string $placeholder = 'Search...', string $inputName = 'q', string $hiddenSection = ''): string {
        $h = $hiddenSection ? '<input type="hidden" name="section" value="' . htmlspecialchars($hiddenSection) . '">' : '';
        return '<form method="GET" action="' . htmlspecialchars($action) . '" class="mb-3 no-print">'
            . $h
            . '<div class="input-group">'
            . '<input type="text" name="' . htmlspecialchars($inputName) . '" class="form-control" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($_GET[$inputName] ?? '') . '">'
            . '<button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Search</button>'
            . '</div></form>';
    }
}

if (!function_exists('filterTableJS')) {
    function filterTableJS(): string {
        return '
function filterTable(tblId,col,val){val=val.toLowerCase();const tbl=document.getElementById(tblId);if(!tbl||!tbl.tBodies[0])return;const rows=tbl.tBodies[0].rows;for(let i=0;i<rows.length;i++){const cells=rows[i].cells;if(!cells[col])continue;rows[i].style.display=cells[col].textContent.toLowerCase().indexOf(val)>-1?"":"none";}}
function filterTableByDate(tblId,col,val){const tbl=document.getElementById(tblId);if(!tbl||!tbl.tBodies[0])return;const rows=tbl.tBodies[0].rows;const months={Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};for(let i=0;i<rows.length;i++){const cells=rows[i].cells;if(!cells[col])continue;if(!val){rows[i].style.display="";continue;}const parts=cells[col].textContent.trim().split(" ");const cd=new Date(parseInt(parts[2]),months[parts[1]],parseInt(parts[0]));const sd=new Date(val);rows[i].style.display=cd.toDateString()===sd.toDateString()?"":"none";}}
';
    }
}
