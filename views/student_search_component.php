<?php
/**
 * Reusable Student Search Component — ISNM
 *
 * Drop-in include for any staff dashboard:
 *   <?php include_once __DIR__ . '/../views/student_search_component.php'; ?>
 *
 * Features:
 *   - AJAX search against the centralized endpoint (includes/ajax_student_search.php)
 *   - Debounced typeahead input
 *   - Bootstrap 5 modal for search results
 *   - Click result → student profile detail modal
 *   - CSRF-protected
 *   - Role-gated (checks canSearchStudentProfiles)
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', SESSION_COOKIE_PATH);
    session_start();
}

require_once __DIR__ . '/../auth-service.php';
$_auth = new AuthenticationService();
$_canSearch = $_auth->isAuthenticated()
    && ($_SESSION['type'] ?? '') === 'staff'
    && $_auth->canSearchStudentProfiles($_SESSION['role'] ?? '');

if (!$_canSearch) {
    return;
}

$_csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES);
$_searchEndpoint = '../includes/ajax_student_search.php';
?>
<!-- ════════════════════════════════════════════════════════════════════
     Student Search Component
     ════════════════════════════════════════════════════════════════════ -->

<!-- Inline search bar (appears where this file is included) -->
<div id="stuSearchWrap" class="card mb-4 border-0 shadow-sm">
  <div class="card-body py-2 px-3">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-search text-muted"></i>
      <input type="text" id="stuSearchInput" class="form-control form-control-sm border-0"
             placeholder="Search students — name, index number, phone, email, program…"
             autocomplete="off" style="font-size:14px">
      <select id="stuSearchLevel" class="form-select form-select-sm border-0 text-muted" style="max-width:140px;font-size:13px">
        <option value="">All Levels</option>
        <option value="Certificate">Certificate</option>
        <option value="Diploma">Diploma</option>
        <option value="Degree">Degree</option>
      </select>
      <select id="stuSearchGender" class="form-select form-select-sm border-0 text-muted" style="max-width:120px;font-size:13px">
        <option value="">All Genders</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
      <span id="stuSearchCount" class="text-muted small text-nowrap"></span>
    </div>
  </div>
</div>

<!-- ═══════════════ Results Modal ═══════════════ -->
<div class="modal fade" id="stuSearchModal" tabindex="-1" aria-labelledby="stuSearchModalLabel" data-bs-backdrop="false">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header py-2" style="background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff">
        <h6 class="modal-title" id="stuSearchModalLabel"><i class="fas fa-user-graduate me-2"></i>Student Search Results</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="stuSearchResults">
        <div class="text-center text-muted py-5">
          <i class="fas fa-search fa-2x mb-2 opacity-25"></i>
          <p>Type at least 2 characters to search.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ Student Profile Modal ═══════════════ -->
<div class="modal fade" id="stuProfileModal" tabindex="-1" aria-labelledby="stuProfileModalLabel" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header py-2" style="background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff">
        <h6 class="modal-title" id="stuProfileModalLabel"><i class="fas fa-user me-2"></i><span id="stuProfileName">Student Profile</span></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="stuProfileBody">
        <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ Component JS ═══════════════ -->
<script>
(function() {
  'use strict';

  var _csrf   = '<?= $_csrfToken ?>';
  var _endpoint = '<?= $_searchEndpoint ?>';
  var _timer  = null;
  var _xhr    = null;
  var _modal  = null;
  var _profileModal = null;
  var _currentResults = [];
  var _currentPage = 1;
  var _lastQuery = '';
  var _lastLevel = '';
  var _lastGender = '';

  /* ── Open search modal (triggered by typing) ── */
  function openSearchModal() {
    if (!_modal) _modal = new bootstrap.Modal(document.getElementById('stuSearchModal'));
    _modal.show();
  }

  /* ── Debounced search ── */
  function doSearch(page) {
    page = page || 1;
    var q = document.getElementById('stuSearchInput').value.trim();
    var level = document.getElementById('stuSearchLevel').value;
    var gender = document.getElementById('stuSearchGender').value;
    var resultsDiv = document.getElementById('stuSearchResults');
    var countSpan = document.getElementById('stuSearchCount');

    if (q.length < 2 && !level && !gender) {
      resultsDiv.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-search fa-2x mb-2 opacity-25"></i><p>Type at least 2 characters to search.</p></div>';
      countSpan.textContent = '';
      return;
    }

    openSearchModal();
    resultsDiv.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Searching…</p></div>';

    if (_xhr) _xhr.abort();

    var params = new URLSearchParams();
    params.append('q', q);
    params.append('limit', '50');
    if (level)  params.append('level', level);
    if (gender) params.append('gender', gender);

    _xhr = new XMLHttpRequest();
    _xhr.open('GET', _endpoint + '?' + params.toString(), true);
    _xhr.onload = function() {
      if (this.status !== 200) {
        resultsDiv.innerHTML = '<div class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Search failed. Please try again.</p></div>';
        return;
      }
      var data;
      try { data = JSON.parse(this.responseText); } catch(e) { data = {students:[]}; }

      var students = data.students || [];
      _currentResults = students;
      countSpan.textContent = students.length + ' result' + (students.length !== 1 ? 's' : '');

      if (students.length === 0) {
        resultsDiv.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-search fa-2x mb-2 opacity-25"></i><p>No students found matching <strong>"' + escHtml(q) + '"</strong>.</p></div>';
        return;
      }

      var html = '<div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0">'
        + '<thead class="table-light"><tr>'
        + '<th style="width:40px"></th>'
        + '<th>ID / Index</th>'
        + '<th>Name</th>'
        + '<th>Program</th>'
        + '<th>Level</th>'
        + '<th>Set</th>'
        + '<th>Contact</th>'
        + '<th>Status</th>'
        + '<th>Source</th>'
        + '<th></th>'
        + '</tr></thead><tbody>';

      for (var i = 0; i < students.length; i++) {
        var s = students[i];
        var initials = ((s.surname||'?')[0]||'?').toUpperCase() + ((s.first_name||'?')[0]||'?').toUpperCase();
        var statusCls = (s.status||'Active').toLowerCase() === 'active' ? 'bg-success' : 'bg-secondary';
        var srcBadge  = s.source === 'Excel'
          ? '<span class="badge bg-info">Excel</span>'
          : '<span class="badge bg-dark">DB</span>';
        var photo = s.passport_photo || '';
        var photoHtml = photo
          ? '<img src="../' + escAttr(photo) + '" style="width:36px;height:36px;border-radius:50%;object-fit:cover">'
          : '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600">' + initials + '</div>';

        html += '<tr style="cursor:pointer" onclick="window._stuSearchOpenProfile(' + i + ')">'
          + '<td>' + photoHtml + '</td>'
          + '<td><code class="small">' + escHtml(s.student_id || s.index_number || s.student_number || '-') + '</code></td>'
          + '<td><strong>' + escHtml(s.full_name || (s.surname + ' ' + s.first_name)) + '</strong></td>'
          + '<td class="small">' + escHtml(s.program || '-') + '</td>'
          + '<td>' + escHtml(s.level || '-') + '</td>'
          + '<td class="small">' + escHtml(s.set_name || '-') + '</td>'
          + '<td class="small">' + escHtml(s.phone || s.email || '-') + '</td>'
          + '<td><span class="badge ' + statusCls + '">' + escHtml(s.status || 'Active') + '</span></td>'
          + '<td>' + srcBadge + '</td>'
          + '<td><button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();window._stuSearchOpenProfile(' + i + ')" title="View profile"><i class="fas fa-eye"></i></button></td>'
          + '</tr>';
      }
      html += '</tbody></table></div>';
      resultsDiv.innerHTML = html;
    };
    _xhr.onerror = function() {
      resultsDiv.innerHTML = '<div class="text-center text-danger py-4"><p>Network error. Please try again.</p></div>';
    };
    _xhr.send();
  }

  /* ── Open student profile ── */
  window._stuSearchOpenProfile = function(idx) {
    var s = _currentResults[idx];
    if (!s) return;

    if (!_profileModal) _profileModal = new bootstrap.Modal(document.getElementById('stuProfileModal'));
    document.getElementById('stuProfileName').textContent = s.full_name || 'Student Profile';

    var photo = s.passport_photo || '';
    var photoHtml = photo
      ? '<img src="../' + escAttr(photo) + '" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;border:3px solid #7C3AED">'
      : '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;font-size:28px;font-weight:700">' + (((s.surname||'?')[0]||'?') + ((s.first_name||'?')[0]||'?')).toUpperCase() + '</div>';

    var html = '<div class="text-center p-4">' + photoHtml
      + '<h5 class="mb-1">' + escHtml(s.full_name) + '</h5>'
      + '<span class="badge bg-' + ((s.status||'Active').toLowerCase()==='active'?'success':'secondary') + ' mb-2">' + escHtml(s.status || 'Active') + '</span>'
      + (s.source === 'Excel' ? ' <span class="badge bg-info">Excel record</span>' : '')
      + '</div><hr class="my-0">';

    html += '<div class="p-4"><div class="row g-3">';
    html += fieldRow('Student ID', s.student_id || s.index_number || s.student_number || '-');
    html += fieldRow('Full Name', s.full_name || '-');
    html += fieldRow('First Name', s.first_name || '-');
    html += fieldRow('Surname', s.surname || '-');
    if (s.other_name) html += fieldRow('Other Name', s.other_name);
    html += fieldRow('Program', s.program || '-');
    html += fieldRow('Level', s.level || '-');
    html += fieldRow('Set', s.set_name || '-');
    html += fieldRow('Gender', s.gender || '-');
    html += fieldRow('Phone', s.phone || '-');
    html += fieldRow('Email', s.email || '-');
    if (s.date_of_birth) html += fieldRow('Date of Birth', s.date_of_birth);
    html += '</div></div>';

    document.getElementById('stuProfileBody').innerHTML = html;
    _profileModal.show();
  };

  /* ── Helpers ── */
  function fieldRow(label, value) {
    return '<div class="col-md-6"><div class="border rounded p-2">'
      + '<small class="text-muted d-block">' + escHtml(label) + '</small>'
      + '<strong class="small">' + escHtml(value) + '</strong>'
      + '</div></div>';
  }
  function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
  function escAttr(s) { return (s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  /* ── Bind events ── */
  document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('stuSearchInput');
    if (!input) return;

    input.addEventListener('input', function() {
      clearTimeout(_timer);
      _timer = setTimeout(doSearch, 350);
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); clearTimeout(_timer); doSearch(); }
    });

    document.getElementById('stuSearchLevel').addEventListener('change', function() { doSearch(); });
    document.getElementById('stuSearchGender').addEventListener('change', function() { doSearch(); });
  });
})();
</script>
