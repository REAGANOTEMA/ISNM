<?php
/**
 * Global Search Component — ISNM
 * Includes both the search UI (renderGlobalSearchBar) and the AJAX handler.
 * Include in any dashboard head or layout file.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/global_search.php';
 *   renderGlobalSearchBar($conn); // optional $studentsDb parameter
 */
require_once __DIR__ . '/../views/student_data_loader.php';

if (!function_exists('renderGlobalSearchBar')) {
function renderGlobalSearchBar($staffDb = null, $studentsDb = null) {
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;
    $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<!-- Global Search Bar -->
<div id="globalSearchOverlay" class="global-search-overlay" onclick="closeGlobalSearch()"></div>
<div id="globalSearchPanel" class="global-search-panel">
  <div class="global-search-header">
    <i class="fas fa-search"></i>
    <input type="text" id="globalSearchInput" class="global-search-input" placeholder="Search students by name, ID, phone, email, set, program..." autocomplete="off" autofocus>
    <button class="global-search-close" onclick="closeGlobalSearch()"><i class="fas fa-times"></i></button>
  </div>
  <div id="globalSearchResults" class="global-search-results">
    <div class="global-search-hint">Type at least 2 characters to search across all student records (including Excel data).</div>
  </div>
</div>

<style>
.global-search-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9998;opacity:0;transition:opacity 0.25s;pointer-events:none;backdrop-filter:blur(4px)}
.global-search-overlay.active{opacity:1;pointer-events:auto}
.global-search-panel{position:fixed;top:0;left:50%;transform:translateX(-50%) translateY(-100%);width:90%;max-width:800px;z-index:9999;background:#fff;border-radius:0 0 16px 16px;box-shadow:0 8px 40px rgba(0,0,0,0.25);transition:transform 0.35s cubic-bezier(0.16,1,0.3,1);overflow:hidden;max-height:90vh;display:flex;flex-direction:column}
.global-search-panel.active{transform:translateX(-50%) translateY(0)}
.global-search-header{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.global-search-input{flex:1;border:none;outline:none;font-size:16px;background:transparent;color:#0f172a;font-family:inherit}
.global-search-input::placeholder{color:#94a3b8}
.global-search-close{width:32px;height:32px;border-radius:8px;border:none;background:#e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s}
.global-search-close:hover{background:#cbd5e1;color:#0f172a}
.global-search-results{overflow-y:auto;flex:1;padding:8px 0;max-height:70vh}
.global-search-hint{padding:32px 20px;text-align:center;color:#94a3b8;font-size:14px}
.global-search-item{display:flex;align-items:center;gap:12px;padding:10px 20px;cursor:pointer;transition:background 0.1s;border-bottom:1px solid #f1f5f9}
.global-search-item:hover{background:#f1f5f9}
.global-search-item .gs-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;flex-shrink:0}
.global-search-item .gs-info{flex:1;min-width:0}
.global-search-item .gs-name{font-size:14px;font-weight:600;color:#0f172a}
.global-search-item .gs-detail{font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.global-search-item .gs-badge{font-size:10px;padding:2px 8px;border-radius:4px;font-weight:500;flex-shrink:0}
.gs-badge-excel{background:#e0f2fe;color:#0284c7}
.gs-badge-db{background:#ede9fe;color:#7C3AED}
.gs-badge-active{background:#dcfce7;color:#059669}
.global-search-loading{text-align:center;padding:20px;color:#94a3b8}
.global-search-empty{text-align:center;padding:32px 20px;color:#94a3b8}
.global-search-empty i{font-size:36px;margin-bottom:8px;opacity:0.3}
</style>

<script>
  var _gsTimer = null;
var _gsCurrentRequest = null;
var _gsCsrfToken = '<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES) ?>';

function openGlobalSearch() {
  document.getElementById('globalSearchOverlay').classList.add('active');
  document.getElementById('globalSearchPanel').classList.add('active');
  setTimeout(function(){ document.getElementById('globalSearchInput').focus(); }, 350);
}
function closeGlobalSearch() {
  document.getElementById('globalSearchOverlay').classList.remove('active');
  document.getElementById('globalSearchPanel').classList.remove('active');
  document.getElementById('globalSearchInput').value = '';
  document.getElementById('globalSearchResults').innerHTML = '<div class="global-search-hint">Type at least 2 characters to search.</div>';
}
function doGlobalSearch() {
  var q = document.getElementById('globalSearchInput').value.trim();
  var resultsDiv = document.getElementById('globalSearchResults');
  if (q.length < 2) {
    resultsDiv.innerHTML = '<div class="global-search-hint">Type at least 2 characters to search across all student records (including Excel data).</div>';
    return;
  }
  resultsDiv.innerHTML = '<div class="global-search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
  if (_gsCurrentRequest) { _gsCurrentRequest.abort(); }
  _gsCurrentRequest = new XMLHttpRequest();
  _gsCurrentRequest.open('POST', '../includes/ajax_global_student_search.php', true);
  _gsCurrentRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  _gsCurrentRequest.onload = function() {
    if (this.status !== 200) { resultsDiv.innerHTML = '<div class="global-search-empty">Search failed. Try again.</div>'; return; }
    var data;
    try { data = JSON.parse(this.responseText); } catch(e) { data = []; }
    if (!data || data.length === 0) {
      resultsDiv.innerHTML = '<div class="global-search-empty"><i class="fas fa-search"></i><br>No students found matching "<strong>' + q + '</strong>"</div>';
      return;
    }
    var html = '<div class="global-search-hint" style="padding:8px 20px;text-align:left;font-size:12px">Found ' + data.length + ' result(s)</div>';
    for (var i = 0; i < data.length && i < 50; i++) {
      var s = data[i];
      var isExcel = s._source === 'Excel';
      var initial = (s.full_name || '?').charAt(0).toUpperCase();
      var detail = s.student_id || s.index_number || s.student_number || '';
      if (s.program) detail += (detail ? ' &middot; ' : '') + s.program;
      if (s.set_name || s.set) detail += (detail ? ' &middot; ' : '') + (s.set_name || s.set);
      var badge = isExcel ? '<span class="gs-badge gs-badge-excel">Excel</span>' : '<span class="gs-badge gs-badge-db">DB</span>';
      var statusBadge = s.status === 'Active' || !s.status ? '<span class="gs-badge gs-badge-active">Active</span>' : '';
      html += '<div class="global-search-item" onclick="goToStudent(\'' + (s.full_name||'').replace(/'/g,"") + '\',\'' + (s.id||'') + '\',\'' + (s.student_id||s.index_number||'') + '\',\'' + (isExcel ? 'excel' : 'db') + '\')">'
        + '<div class="gs-avatar">' + initial + '</div>'
        + '<div class="gs-info"><div class="gs-name">' + s.full_name + '</div>'
        + '<div class="gs-detail">' + detail + '</div></div>'
        + '<div>' + badge + ' ' + statusBadge + '</div>'
        + '</div>';
    }
    resultsDiv.innerHTML = html;
  };
  _gsCurrentRequest.send('action=global_stu_search&q=' + encodeURIComponent(q) + '&csrf_token=' + encodeURIComponent(_gsCsrfToken));
}
// Global search with debounce
document.addEventListener('DOMContentLoaded', function() {
  var inp = document.getElementById('globalSearchInput');
  if (inp) {
    inp.addEventListener('input', function() {
      clearTimeout(_gsTimer);
      _gsTimer = setTimeout(doGlobalSearch, 300);
    });
    inp.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeGlobalSearch();
    });
  }
  // Keyboard shortcut: Ctrl+K to open search
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      openGlobalSearch();
    }
  });
});
function goToStudent(name, id, sid, source) {
  if (source === 'excel' || !id || id === '0') {
    alert('Excel record: ' + name + ' (' + sid + '). Edit in the student directory.');
    closeGlobalSearch();
    return;
  }
  var baseUrl = window.location.pathname.split('/').slice(0,-1).join('/') + '/';
  // Try to find a students page; fall back to current page with search param
  var possibleTargets = ['director-admissions.php','academic-registrar.php','system-admin.php','director-general.php'];
  var targetPage = 'students';
  for (var t = 0; t < possibleTargets.length; t++) {
    if (baseUrl.indexOf(possibleTargets[t]) >= 0) { targetPage = possibleTargets[t]; break; }
  }
  // If we can't determine, use current page
  var currentFile = window.location.pathname.split('/').pop();
  window.location.href = '?page=' + (targetPage || currentFile) + '&s=' + encodeURIComponent(sid || name);
}
</script>
<?php
}

if (!function_exists('globalStudentSearchHandler')) {
function globalStudentSearchHandler($conn, $studentsDb, $staffDb = null, $websiteDb = null, $ictDb = null) {
    $q = trim($_POST['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); return; }
    header('Content-Type: application/json');
    $results = [];
    $seenByNumber = [];

    // Helper to search a single DB's students table, dedup by student_number
    $searchDb = function($db, $source) use ($q, &$results, &$seenByNumber) {
        if (!$db) return;
        $qq = '%' . $db->real_escape_string($q) . '%';
        $s = $db->prepare("SELECT id, student_id, student_number, index_number, CONCAT(first_name,' ',COALESCE(surname,'')) full_name, email, phone, program, level, set_name, status FROM students WHERE (first_name LIKE ? OR surname LIKE ? OR CONCAT(first_name,' ',COALESCE(surname,'')) LIKE ? OR student_id LIKE ? OR student_number LIKE ? OR registration_number LIKE ? OR phone LIKE ? OR email LIKE ? OR set_name LIKE ?) AND status != 'deleted' LIMIT 100");
        if (!$s) return;
        $s->bind_param('sssssssss', $qq, $qq, $qq, $qq, $qq, $qq, $qq, $qq, $qq);
        $s->execute();
        $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $s->close();
        foreach ($rows as $r) {
            $num = $r['student_number'] ?? $r['index_number'] ?? $r['student_id'] ?? '';
            $key = $num !== '' ? $num : strtolower(trim($r['full_name'] ?? '') . '|' . trim($r['email'] ?? ''));
            if (!isset($seenByNumber[$key])) {
                $r['_source'] = $source;
                $results[] = $r;
                $seenByNumber[$key] = true;
            }
        }
    };

    // Auto-acquire missing connections
    if (!$staffDb && function_exists('getStaffConnection')) {
        $staffDb = getStaffConnection();
    }
    if (!$websiteDb && function_exists('getWebsiteConnection')) {
        $websiteDb = getWebsiteConnection();
    }
    if (!$ictDb && function_exists('getICTConnection')) {
        $ictDb = getICTConnection();
    }

    // Search all 4 databases
    $searchDb($studentsDb, 'StudentsDB');
    $searchDb($staffDb, 'StaffDB');
    $searchDb($websiteDb, 'WebsiteDB');
    $searchDb($ictDb, 'ICTDB');

    // Search Excel files
    try {
        $loader = new StudentDataLoader($studentsDb);
        $excelResults = $loader->searchStudents($q);
        foreach ($excelResults as $er) {
            $num = $er['student_number'] ?? $er['index_number'] ?? '';
            $name = strtolower(trim($er['full_name'] ?? ''));
            $key = $num !== '' ? $num : $name;
            if (!isset($seenByNumber[$key])) {
                $results[] = [
                    'id' => 0,
                    'student_id' => $er['index_number'] ?? $er['student_number'] ?? '',
                    'student_number' => $er['student_number'] ?? '',
                    'index_number' => $er['index_number'] ?? '',
                    'full_name' => $er['full_name'] ?? '',
                    'email' => $er['email'] ?? '',
                    'phone' => $er['phone'] ?? '',
                    'program' => $er['program'] ?? '',
                    'level' => $er['level'] ?? '',
                    'set_name' => $er['set_name'] ?? $er['set'] ?? '',
                    'status' => 'Active',
                    '_source' => 'Excel',
                ];
                $seenByNumber[$key] = true;
            }
        }
    } catch (Exception $e) { error_log('global_search query: ' . $e->getMessage()); }
    echo json_encode($results);
}
}

// ── Close the if (!function_exists('renderGlobalSearchBar')) wrapper ──
}
