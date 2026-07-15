<?php
/**
 * Centralized Global Student Search — ISNM
 *
 * Single AJAX endpoint that ALL dashboards can use to search for students
 * across multiple databases and tables.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/global_student_search.php';
 *   handleGlobalStudentSearch(); // JSON AJAX endpoint
 *   renderGlobalSearchBar();     // Reusable HTML search bar
 *
 * Accepts: GET
 *   q     — search term (min 2 chars)
 *   limit — max results (default 20, max 100)
 *
 * Returns JSON:
 *   {success: bool, results: [...], count: int}
 */

if (!function_exists('handleGlobalStudentSearch')) {
    function handleGlobalStudentSearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        /* ── Ensure database connections are available ── */
        if (!function_exists('getStudentsConnection')) {
            require_once __DIR__ . '/../config/database.php';
        }

        /* ── Parse GET parameters ── */
        $q     = trim($_GET['q'] ?? '');
        $limit = min(max((int) ($_GET['limit'] ?? 20), 1), 100);

        if (strlen($q) < 2) {
            echo json_encode([
                'success' => false,
                'message' => 'Search query must be at least 2 characters',
                'results' => [],
                'count'   => 0,
            ]);
            return;
        }

        $results = [];
        $seen    = [];

        /* ── Dedup key helper ── */
        $dedupKey = static function (string $studentNumber, string $fullName, string $email): string {
            if ($studentNumber !== '') {
                return 'sn:' . strtolower($studentNumber);
            }
            return 'ne:' . strtolower(trim($fullName)) . '|' . strtolower(trim($email));
        };

        /* ── Normalise result row ── */
        $normalise = static function (array $row, string $source): array {
            $fn   = trim($row['first_name'] ?? '');
            $sn   = trim($row['surname'] ?? $row['last_name'] ?? '');
            $full = trim($row['full_name'] ?? '');
            if ($full === '' && ($fn !== '' || $sn !== '')) {
                $full = trim($fn . ' ' . $sn);
            }
            return [
                'id'             => (int) ($row['id'] ?? 0),
                'student_number' => $row['student_number'] ?? $row['application_number'] ?? '',
                'full_name'      => $full,
                'email'          => $row['email'] ?? '',
                'phone'          => $row['phone'] ?? '',
                'program'        => $row['program'] ?? $row['program_name'] ?? '',
                'status'         => $row['status'] ?? $row['admission_status'] ?? '',
                'source'         => $source,
            ];
        };

        /* ═══════════════════════════════════════════════════
         * 1. Search: igangaschool_students.students
         * ═══════════════════════════════════════════════════ */
        $stuConn = function_exists('getStudentsConnection') ? getStudentsConnection() : null;
        if ($stuConn) {
            $like = '%' . $q . '%';
            $sql  = "SELECT id, student_number, registration_number,
                            first_name, surname,
                            CONCAT(first_name, ' ', COALESCE(surname, '')) AS full_name,
                            email, phone, program, status
                     FROM students
                     WHERE (student_number LIKE ?
                         OR registration_number LIKE ?
                         OR first_name LIKE ?
                         OR surname LIKE ?
                         OR CONCAT(first_name, ' ', COALESCE(surname, '')) LIKE ?
                         OR email LIKE ?
                         OR phone LIKE ?)
                       AND status != 'deleted'
                     LIMIT ?";
            $stmt = $stuConn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sssssssi', $like, $like, $like, $like, $like, $like, $like, $limit);
                if ($stmt->execute()) {
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    foreach ($rows as $r) {
                        $num = trim($r['student_number'] ?? '');
                        $norm = $normalise($r, 'students_db');
                        $key = $dedupKey($num, $norm['full_name'], $norm['email']);
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $results[]  = $norm;
                        }
                    }
                } else {
                    error_log('global_student_search: students query failed — ' . ($stmt->error ?? ''));
                }
                $stmt->close();
            }
        }

        /* ═══════════════════════════════════════════════════
         * 2. Search: igangaschool_staffs.student_admission_tracking
         * ═══════════════════════════════════════════════════ */
        $staffConn = function_exists('getStaffConnection') ? getStaffConnection() : null;
        if ($staffConn) {
            $like = '%' . $q . '%';
            $sql  = "SELECT id, student_number, full_name, program, intake,
                            admission_status, phone
                     FROM student_admission_tracking
                     WHERE (student_number LIKE ?
                         OR full_name LIKE ?
                         OR phone LIKE ?
                         OR program LIKE ?)
                     LIMIT ?";
            $stmt = $staffConn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssssi', $like, $like, $like, $like, $limit);
                if ($stmt->execute()) {
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    foreach ($rows as $r) {
                        $num  = trim($r['student_number'] ?? '');
                        $norm = $normalise($r, 'student_admission_tracking');
                        $key  = $dedupKey($num, $norm['full_name'], $norm['email']);
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $results[]  = $norm;
                        }
                    }
                } else {
                    error_log('global_student_search: admission_tracking query failed — ' . ($stmt->error ?? ''));
                }
                $stmt->close();
            }

            /* ═══════════════════════════════════════════════════
             * 3. Search: igangaschool_staffs.applicants
             * ═══════════════════════════════════════════════════ */
            $sql = "SELECT id, application_number, full_name, email, phone,
                           program_id, status
                    FROM applicants
                    WHERE (application_number LIKE ?
                        OR full_name LIKE ?
                        OR email LIKE ?
                        OR phone LIKE ?)
                    LIMIT ?";
            $stmt = $staffConn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssssi', $like, $like, $like, $like, $limit);
                if ($stmt->execute()) {
                    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    foreach ($rows as $r) {
                        $num  = trim($r['application_number'] ?? '');
                        $norm = $normalise($r, 'applicants');
                        $key  = $dedupKey($num, $norm['full_name'], $norm['email']);
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $results[]  = $norm;
                        }
                    }
                } else {
                    error_log('global_student_search: applicants query failed — ' . ($stmt->error ?? ''));
                }
                $stmt->close();
            }
        }

        /* ── Trim to limit and respond ── */
        $results = array_slice($results, 0, $limit);

        echo json_encode([
            'success' => true,
            'results' => $results,
            'count'   => count($results),
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('renderGlobalSearchBar')) {
    function renderGlobalSearchBar(): void
    {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
        $searchUrl = htmlspecialchars(
            (str_contains(($_SERVER['SCRIPT_NAME'] ?? ''), '/dashboards/')
                ? '../includes/global_student_search.php'
                : 'includes/global_student_search.php'),
            ENT_QUOTES, 'UTF-8'
        );
?>
<!-- Global Student Search Bar -->
<div id="gSearchOverlay" class="gsearch-overlay" onclick="gSearchClose()"></div>
<div id="gSearchPanel" class="gsearch-panel">
  <div class="gsearch-header">
    <i class="fas fa-search gsearch-icon"></i>
    <input type="text" id="gSearchInput" class="gsearch-input"
           placeholder="Search by name, student number, phone, email..."
           autocomplete="off" autofocus>
    <kbd class="gsearch-kbd">ESC</kbd>
    <button class="gsearch-close" onclick="gSearchClose()" title="Close">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div id="gSearchResults" class="gsearch-results">
    <div class="gsearch-hint">
      <i class="fas fa-info-circle"></i>
      Type at least 2 characters to search across all student records.
    </div>
  </div>
</div>

<style>
.gsearch-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9998;opacity:0;transition:opacity .2s;pointer-events:none;backdrop-filter:blur(3px)}
.gsearch-overlay.active{opacity:1;pointer-events:auto}
.gsearch-panel{position:fixed;top:0;left:50%;transform:translateX(-50%) translateY(-100%);width:92%;max-width:720px;z-index:9999;background:#fff;border-radius:0 0 14px 14px;box-shadow:0 12px 48px rgba(0,0,0,.22);transition:transform .3s cubic-bezier(.16,1,.3,1);display:flex;flex-direction:column;max-height:85vh;overflow:hidden}
.gsearch-panel.active{transform:translateX(-50%) translateY(0)}
.gsearch-header{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.gsearch-icon{color:#94a3b8;font-size:15px;flex-shrink:0}
.gsearch-input{flex:1;border:none;outline:none;font-size:15px;background:transparent;color:#0f172a;font-family:inherit}
.gsearch-input::placeholder{color:#94a3b8}
.gsearch-kbd{font-size:11px;padding:2px 7px;border-radius:4px;background:#e2e8f0;color:#64748b;border:1px solid #cbd5e1;font-family:inherit;line-height:1.4;flex-shrink:0}
.gsearch-close{width:30px;height:30px;border-radius:7px;border:none;background:transparent;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0}
.gsearch-close:hover{background:#f1f5f9;color:#0f172a}
.gsearch-results{overflow-y:auto;flex:1;padding:6px 0;max-height:68vh}
.gsearch-hint{padding:28px 18px;text-align:center;color:#94a3b8;font-size:13px}
.gsearch-hint i{margin-right:4px}
.gsearch-loading{text-align:center;padding:20px;color:#94a3b8;font-size:13px}
.gsearch-item{display:flex;align-items:center;gap:12px;padding:10px 18px;cursor:pointer;transition:background .1s;border-bottom:1px solid #f1f5f9;text-decoration:none}
.gsearch-item:hover{background:#f1f5f9}
.gsearch-item:last-child{border-bottom:none}
.gsearch-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0}
.gsearch-info{flex:1;min-width:0}
.gsearch-name{font-size:13px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gsearch-detail{font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.gsearch-badge{font-size:10px;padding:2px 8px;border-radius:4px;font-weight:500;flex-shrink:0;text-transform:capitalize}
.gsearch-badge-students_db{background:#ede9fe;color:#6d28d9}
.gsearch-badge-student_admission_tracking{background:#fef3c7;color:#92400e}
.gsearch-badge-applicants{background:#dbeafe;color:#1d4ed8}
.gsearch-count{padding:6px 18px;font-size:11px;color:#94a3b8;border-bottom:1px solid #f1f5f9}
.gsearch-empty{text-align:center;padding:28px 18px;color:#94a3b8}
.gsearch-empty i{font-size:28px;margin-bottom:6px;opacity:.35;display:block}
</style>

<script>
(function(){
  var _gTimer = null, _gReq = null;
  var _gSearchUrl = '<?= $searchUrl ?>';
  var _gCsrf = '<?= $csrf ?>';

  window.gSearchOpen = function(){
    var o = document.getElementById('gSearchOverlay');
    var p = document.getElementById('gSearchPanel');
    if(o) o.classList.add('active');
    if(p) p.classList.add('active');
    setTimeout(function(){ var i=document.getElementById('gSearchInput'); if(i) i.focus(); },320);
  };

  window.gSearchClose = function(){
    var o = document.getElementById('gSearchOverlay');
    var p = document.getElementById('gSearchPanel');
    var i = document.getElementById('gSearchInput');
    var r = document.getElementById('gSearchResults');
    if(o) o.classList.remove('active');
    if(p) p.classList.remove('active');
    if(i) i.value = '';
    if(r) r.innerHTML = '<div class="gsearch-hint"><i class="fas fa-info-circle"></i> Type at least 2 characters to search across all student records.</div>';
    if(_gReq){ _gReq.abort(); _gReq=null; }
  };

  function gDoSearch(){
    var inp = document.getElementById('gSearchInput');
    var res = document.getElementById('gSearchResults');
    if(!inp || !res) return;
    var q = inp.value.trim();
    if(q.length < 2){
      res.innerHTML = '<div class="gsearch-hint"><i class="fas fa-info-circle"></i> Type at least 2 characters to search across all student records.</div>';
      return;
    }
    res.innerHTML = '<div class="gsearch-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    if(_gReq){ _gReq.abort(); }
    _gReq = new XMLHttpRequest();
    _gReq.open('GET', _gSearchUrl + '?q=' + encodeURIComponent(q) + '&limit=20', true);
    _gReq.onload = function(){
      if(this.status === 403 || this.status === 401){
        res.innerHTML = '<div class="gsearch-empty"><i class="fas fa-lock"></i><div>Authentication required.</div></div>';
        return;
      }
      if(this.status !== 200){
        res.innerHTML = '<div class="gsearch-empty"><i class="fas fa-exclamation-triangle"></i><div>Search failed. Try again.</div></div>';
        return;
      }
      var data;
      try{ data = JSON.parse(this.responseText); }catch(e){ data = {success:false,results:[]}; }
      if(!data.success || !data.results || data.results.length === 0){
        res.innerHTML = '<div class="gsearch-empty"><i class="fas fa-search"></i><div>No results for &ldquo;<strong>'+_esc(q)+'</strong>&rdquo;</div></div>';
        return;
      }
      var items = data.results;
      var html = '<div class="gsearch-count">Found '+data.count+' result(s)</div>';
      for(var i=0; i<items.length; i++){
        var s = items[i];
        var init = (s.full_name||'?').charAt(0).toUpperCase();
        var detail = s.student_number || '';
        if(s.program) detail += (detail?' &middot; ':'') + s.program;
        if(s.email)   detail += (detail?' &middot; ':'') + s.email;
        if(s.phone)   detail += (detail?' &middot; ':'') + s.phone;
        var badgeCls = 'gsearch-badge gsearch-badge-' + (s.source||'students_db').replace(/\s+/g,'_');
        var statusStr = s.status ? ' &middot; '+_esc(s.status) : '';
        html += '<div class="gsearch-item" data-src="'+_esc(s.source||'')+'" data-sn="'+_esc(s.student_number||'')+'" data-name="'+_esc(s.full_name||'')+'">'
          + '<div class="gsearch-avatar">'+init+'</div>'
          + '<div class="gsearch-info"><div class="gsearch-name">'+_esc(s.full_name||'')+'</div>'
          + '<div class="gsearch-detail">'+detail+statusStr+'</div></div>'
          + '<span class="'+badgeCls+'">'+_esc(s.source||'db')+'</span>'
          + '</div>';
      }
      res.innerHTML = html;
      var clickables = res.querySelectorAll('.gsearch-item');
      for(var j=0; j<clickables.length; j++){
        clickables[j].addEventListener('click', function(){
          var sn = this.getAttribute('data-sn') || '';
          var nm = this.getAttribute('data-name') || '';
          gSearchClose();
          var target = 'student-management.php?student_search=' + encodeURIComponent(sn || nm);
          window.location.href = target;
        });
      }
    };
    _gReq.onerror = function(){
      res.innerHTML = '<div class="gsearch-empty"><i class="fas fa-wifi"></i><div>Network error.</div></div>';
    };
    _gReq.send();
  }

  function _esc(s){ var d=document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }

  document.addEventListener('DOMContentLoaded', function(){
    var inp = document.getElementById('gSearchInput');
    if(inp){
      inp.addEventListener('input', function(){
        clearTimeout(_gTimer);
        _gTimer = setTimeout(gDoSearch, 280);
      });
      inp.addEventListener('keydown', function(e){
        if(e.key === 'Escape') gSearchClose();
      });
    }
    document.addEventListener('keydown', function(e){
      if((e.ctrlKey || e.metaKey) && e.key === 'k'){
        e.preventDefault();
        gSearchOpen();
      }
    });
  });
})();
</script>
<?php
    }
}
