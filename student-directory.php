<?php
/**
 * ISNM Student Directory — full listing, search, profile, print.
 * Reads all .xlsx files from students_data/ using the existing data loader.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/views/student_data_loader.php';

// Optional auth — if session exists show who's browsing
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['user_id']);
$userName = $_SESSION['full_name'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? '';

$loader = new StudentDataLoader();
$allStudents = $loader->loadAllStudents();
$stats = $loader->getStatistics();
$filterOptions = $loader->getFilterOptions();

// Serialize to JSON for client-side handling
$studentsJson = json_encode($allStudents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Build filter data
$programsJson = json_encode($filterOptions['programs']);
$levelsJson = json_encode($filterOptions['levels']);
$setsJson = json_encode($filterOptions['sets']);
$gendersJson = json_encode($filterOptions['genders']);
$yearsJson = json_encode(array_map('strval', $filterOptions['years']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Directory — ISNM</title>
<link rel="icon" href="images/school-logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #0f766e;
  --primary-dark: #064e3b;
  --accent: #14b8a6;
  --light: #ccfbf1;
}

* { box-sizing: border-box; }

body {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: #fef9e7;
  margin: 0;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

/* ─── HEADER ─── */
.page-header {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary), #0d9488);
  color: #fff;
  padding: 32px 0 28px;
  position: relative;
  overflow: hidden;
}
.page-header::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0; right: 0;
  height: 6px;
  background: linear-gradient(90deg, #14b8a6, #2dd4bf, #5eead4, #2dd4bf, #14b8a6);
}
.page-header h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 900; font-size: 2rem; margin: 0; }
.page-header p { opacity: .85; margin: 4px 0 0; font-size: .95rem; }

/* ─── STATS BAR ─── */
.stats-bar {
  background: #fff;
  border-bottom: 1px solid #d1fae5;
  padding: 14px 0;
  position: sticky; top: 0; z-index: 102;
  box-shadow: 0 2px 12px rgba(0,0,0,.04);
}
.stat-item {
  text-align: center;
  padding: 2px 10px;
  border-right: 1px solid #e5e7eb;
}
.stat-item:last-child { border-right: none; }
.stat-item .num { font-weight: 800; font-size: 1.25rem; color: var(--primary); line-height: 1.2; }
.stat-item .lbl { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }

/* ─── SEARCH AREA ─── */
.search-area {
  background: #fff;
  border-radius: 16px;
  padding: 24px 28px;
  margin-top: -16px;
  position: relative;
  z-index: 10;
  box-shadow: 0 8px 30px rgba(15,118,110,.12);
}
.search-input-group { position: relative; }
.search-input-group .fa-search {
  position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; font-size: 1rem; z-index: 5;
}
.search-input-group input {
  padding-left: 42px; height: 50px; font-size: 1.05rem;
  border: 2px solid #e5e7eb; border-radius: 12px;
  transition: .2s;
}
.search-input-group input:focus {
  border-color: var(--accent); box-shadow: 0 0 0 4px rgba(20,184,166,.15);
}
.search-input-group .clear-btn {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #9ca3af; font-size: 1.2rem;
  cursor: pointer; display: none; z-index: 5;
}
.search-input-group .clear-btn:hover { color: #ef4444; }

.filter-select {
  border: 2px solid #e5e7eb; border-radius: 10px; padding: 8px 12px;
  font-size: .85rem; transition: .2s; cursor: pointer;
}
.filter-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(20,184,166,.12); }

.result-count {
  font-size: .85rem; color: #6b7280; padding: 8px 0 0;
}

/* ─── STUDENT CARDS ─── */
.student-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 16px;
  padding: 0;
}
.student-card {
  background: #fff;
  border-radius: 14px;
  padding: 18px 20px;
  cursor: pointer;
  transition: all .2s ease;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  position: relative;
}
.student-card:hover {
  border-color: var(--accent);
  box-shadow: 0 8px 24px rgba(15,118,110,.12);
  transform: translateY(-2px);
}
.student-card .avatar {
  width: 48px; height: 48px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.student-card .info { flex: 1; min-width: 0; }
.student-card .name {
  font-weight: 700; font-size: .95rem; color: #111827;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.student-card .meta {
  font-size: .8rem; color: #6b7280; margin-top: 2px;
  display: flex; flex-wrap: wrap; gap: 4px 12px;
}
.student-card .meta span { white-space: nowrap; }
.student-card .badge-program {
  display: inline-block;
  font-size: .68rem; padding: 2px 10px; border-radius: 20px;
  background: var(--light); color: var(--primary-dark);
  font-weight: 600; margin-top: 6px;
}
.student-card .click-hint {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  color: #d1d5db; font-size: .85rem; opacity: 0; transition: .2s;
}
.student-card:hover .click-hint { opacity: 1; color: var(--accent); }

/* ─── EMPTY STATE ─── */
.empty-state {
  text-align: center; padding: 60px 20px; color: #9ca3af;
}
.empty-state .icon { font-size: 3.5rem; margin-bottom: 16px; }
.empty-state h5 { font-weight: 600; color: #6b7280; }

/* ─── PAGINATION ─── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 0; flex-wrap: wrap; gap: 10px;
}
.pagination-bar .page-info { font-size: .85rem; color: #6b7280; }
.pagination-bar .btn-group .btn {
  border-radius: 8px; padding: 6px 14px; font-size: .82rem;
  border: 1px solid #d1d5db;
}
.pagination-bar .btn-group .btn.active {
  background: var(--primary); color: #fff; border-color: var(--primary);
}

/* ─── PROFILE MODAL ─── */
.profile-header {
  display: flex; gap: 20px; align-items: center;
  padding: 8px 0 16px;
  border-bottom: 2px solid #f3f4f6;
  margin-bottom: 20px;
}
.profile-header .big-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.profile-header .big-info h3 { font-weight: 800; margin: 0; font-size: 1.3rem; }
.profile-header .big-info .sub { color: #6b7280; font-size: .85rem; }
.profile-detail-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px;
}
.profile-detail-grid .field { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
.profile-detail-grid .field .label {
  font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
  color: #9ca3af; font-weight: 600;
}
.profile-detail-grid .field .value {
  font-size: .92rem; color: #111827; font-weight: 500;
}
.profile-detail-grid .field.full-width { grid-column: 1 / -1; }
.modal-print-actions { display: flex; gap: 8px; }

/* ─── PRINT STYLES ─── */
@media print {
  body * { visibility: hidden; }
  #profilePrintArea, #profilePrintArea * { visibility: visible; }
  #profilePrintArea {
    position: fixed; top: 0; left: 0; right: 0;
    background: #fff; padding: 30px;
    z-index: 99999;
  }
  .modal-print-actions, .btn-close, .modal-header, .page-header, .stats-bar,
  .search-area, .pagination-bar, footer { display: none !important; }
  .profile-detail-grid { break-inside: avoid; }
  .profile-header { border-bottom: 2px solid #000; }
  @page { margin: 15mm; }
}

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
  .page-header { padding: 20px 0 24px; }
  .page-header h1 { font-size: 1.4rem; }
  .search-area { padding: 16px; margin-top: -10px; }
  .student-grid { grid-template-columns: 1fr; }
  .profile-detail-grid { grid-template-columns: 1fr; }
  .stat-item .num { font-size: 1rem; }
  .stat-item .lbl { font-size: .6rem; }
}
@media (max-width: 576px) {
  .student-card { padding: 14px; }
  .student-card .avatar { width: 40px; height: 40px; font-size: 1rem; }
  .profile-header { flex-direction: column; text-align: center; }
  .filter-row .col-6 { margin-bottom: 6px; }
}

/* ─── LOADING ─── */
.loading-overlay {
  position: fixed; inset: 0; background: rgba(255,255,255,.85);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; flex-direction: column; gap: 12px;
}
.loading-overlay .spinner { width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top-color: var(--primary); border-radius: 50%; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<!-- Loading Screen -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div style="color:var(--primary);font-weight:600;font-size:.95rem">Loading student directory...</div>
</div>

<!-- Header -->
<header class="page-header">
  <div class="container">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h1><i class="fas fa-graduation-cap me-3"></i>Student Directory</h1>
        <p><i class="fas fa-database me-1"></i><?= number_format($stats['total_students']) ?> records across <?= $stats['data_files'] ?> data files</p>
      </div>
      <div class="text-end">
        <?php if ($loggedIn): ?>
        <span class="d-inline-block bg-white bg-opacity-20 rounded-pill px-3 py-1" style="font-size:.82rem;background:rgba(255,255,255,.15)">
          <i class="fas fa-user me-1"></i><?= htmlspecialchars($userName) ?>
        </span>
        <?php endif; ?>
        <div class="mt-1">
          <a href="index.php" class="text-white text-decoration-none small me-3"><i class="fas fa-home me-1"></i>Home</a>
          <?php if ($loggedIn): ?>
          <a href="dashboards/" class="text-white text-decoration-none small"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Stats Bar -->
<div class="stats-bar" id="statsBar">
  <div class="container">
    <div class="row g-0">
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statTotal">0</div><div class="lbl">Students</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statMale">0</div><div class="lbl">Male</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statFemale">0</div><div class="lbl">Female</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statPrograms">0</div><div class="lbl">Programs</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statSets">0</div><div class="lbl">Sets</div></div>
      <div class="col-4 col-md-2 stat-item"><div class="num" id="statSources">0</div><div class="lbl">Files</div></div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="container py-3">

  <!-- Search & Filters -->
  <div class="search-area mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-12">
        <div class="search-input-group">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" class="form-control" placeholder="Search by name, index number, NSIN, phone, course, district…" autofocus>
          <button class="clear-btn" id="clearBtn" onclick="clearSearch()">&times;</button>
        </div>
      </div>
    </div>
    <div class="row g-2 mt-2 filter-row" id="filterRow">
      <div class="col-6 col-md-3">
        <select id="filterProgram" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Programs</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select id="filterLevel" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Levels</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select id="filterSet" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Sets</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select id="filterGender" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Genders</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select id="filterYear" class="form-select filter-select" onchange="applyFilters()">
          <option value="">All Years</option>
        </select>
      </div>
      <div class="col-6 col-md-1">
        <button class="btn btn-outline-secondary w-100" style="border-radius:10px;height:42px" onclick="resetFilters()" title="Reset filters">
          <i class="fas fa-undo"></i>
        </button>
      </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="result-count" id="resultCount"></div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" onclick="toggleView()" id="viewToggle" style="border-radius:8px">
          <i class="fas fa-list me-1"></i><span>List</span>
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" style="border-radius:8px">
          <i class="fas fa-print me-1"></i>Print
        </button>
      </div>
    </div>
  </div>

  <!-- Student Grid / List -->
  <div id="studentContainer"></div>

  <!-- Pagination -->
  <div class="pagination-bar" id="paginationBar">
    <div class="page-info" id="pageInfo"></div>
    <div class="btn-group" id="pageButtons"></div>
  </div>

</div>

<!-- Footer -->
<div style="background:#fff;border-top:1px solid #e5e7eb;padding:16px 0;margin-top:20px">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">&copy; <?= date('Y') ?> Iganga School of Nursing &amp; Midwifery</small>
    <small class="text-muted"><i class="fas fa-file-excel me-1"></i>Data from students_data/ &middot; Last sync: <?= date('d M Y H:i') ?></small>
  </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden">
      <div class="modal-header" style="background:var(--primary);color:#fff;padding:16px 24px;border:none">
        <h5 class="modal-title fw-bold"><i class="fas fa-id-card me-2"></i>Student Profile</h5>
        <div class="modal-print-actions">
          <button class="btn btn-sm btn-light me-2" onclick="printProfile()" style="border-radius:8px">
            <i class="fas fa-print me-1"></i>Print
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body p-4" id="profileBody">
        <div id="profilePrintArea">
          <div class="profile-header">
            <div class="big-avatar" id="pAvatar"></div>
            <div class="big-info">
              <h3 id="pName"></h3>
              <div class="sub">
                <span id="pIndex"></span>
                <span class="mx-2">&middot;</span>
                <span id="pSource"></span>
              </div>
            </div>
          </div>
          <div class="profile-detail-grid" id="pDetails"></div>
        </div>
      </div>
      <div class="modal-footer" style="border:none;padding:12px 24px;background:#f9fafb">
        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Click Print for a formatted hard copy</small>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── DATA ───
const ALL_STUDENTS = <?= $studentsJson ?>;
const PROGRAMS = <?= $programsJson ?>;
const LEVELS = <?= $levelsJson ?>;
const SETS = <?= $setsJson ?>;
const GENDERS = <?= $gendersJson ?>;
const YEARS = <?= $yearsJson ?>;

// ─── STATE ───
const PER_PAGE = 48;
let currentPage = 1;
let filtered = [];
let isGridView = true;
let currentProfileIndex = -1;

// ─── INIT ───
document.addEventListener('DOMContentLoaded', function() {
  populateFilters();
  filtered = ALL_STUDENTS;
  updateStats();
  render();
  document.getElementById('loadingOverlay').style.display = 'none';

  // Search
  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 200);
    const btn = document.querySelector('.clear-btn');
    btn.style.display = this.value.length > 0 ? 'block' : 'none';
  });
});

function populateFilters() {
  // Programs
  const progSel = document.getElementById('filterProgram');
  PROGRAMS.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p; opt.textContent = p;
    if (p) progSel.appendChild(opt);
  });
  // Levels
  const lvlSel = document.getElementById('filterLevel');
  LEVELS.forEach(l => {
    if (!l) return;
    const opt = document.createElement('option');
    opt.value = l; opt.textContent = l;
    lvlSel.appendChild(opt);
  });
  // Sets
  const setSel = document.getElementById('filterSet');
  SETS.forEach(s => {
    if (!s) return;
    const opt = document.createElement('option');
    opt.value = s; opt.textContent = s;
    setSel.appendChild(opt);
  });
  // Genders
  const genSel = document.getElementById('filterGender');
  GENDERS.forEach(g => {
    if (!g) return;
    const opt = document.createElement('option');
    opt.value = g; opt.textContent = g.charAt(0).toUpperCase() + g.slice(1);
    genSel.appendChild(opt);
  });
  // Years
  const yrSel = document.getElementById('filterYear');
  YEARS.forEach(y => {
    if (!y) return;
    const opt = document.createElement('option');
    opt.value = y; opt.textContent = y;
    yrSel.appendChild(opt);
  });
}

function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  const prog = document.getElementById('filterProgram').value;
  const lvl = document.getElementById('filterLevel').value;
  const set = document.getElementById('filterSet').value;
  const gen = document.getElementById('filterGender').value;
  const yr = document.getElementById('filterYear').value;

  filtered = ALL_STUDENTS.filter(s => {
    // Text search
    if (q) {
      const haystack = [
        s.full_name, s.surname, s.first_name, s.other_name,
        s.index_number, s.registration_number, s.student_number,
        s.national_id, s.phone, s.email, s.program, s.level,
        s.set, s.intake_year, s.intake_period, s.district,
        s.source_file, s.course_codes
      ].filter(Boolean).join(' | ').toLowerCase();
      if (haystack.indexOf(q) === -1) return false;
    }
    // Filters
    if (prog && s.program !== prog) return false;
    if (lvl && s.level !== lvl) return false;
    if (set && s.set !== set) return false;
    if (gen && s.gender && s.gender.toLowerCase() !== gen.toLowerCase()) return false;
    if (yr && String(s.intake_year) !== yr) return false;
    return true;
  });

  currentPage = 1;
  updateStats();
  render();
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.querySelector('.clear-btn').style.display = 'none';
  document.getElementById('filterProgram').value = '';
  document.getElementById('filterLevel').value = '';
  document.getElementById('filterSet').value = '';
  document.getElementById('filterGender').value = '';
  document.getElementById('filterYear').value = '';
  applyFilters();
}

function clearSearch() {
  document.getElementById('searchInput').value = '';
  document.querySelector('.clear-btn').style.display = 'none';
  applyFilters();
  document.getElementById('searchInput').focus();
}

function updateStats() {
  document.getElementById('statTotal').textContent = filtered.length;
  document.getElementById('statMale').textContent = filtered.filter(s => (s.gender||'').toLowerCase() === 'male').length;
  document.getElementById('statFemale').textContent = filtered.filter(s => (s.gender||'').toLowerCase() === 'female').length;
  const progs = new Set(filtered.map(s => s.program).filter(Boolean));
  const setSet = new Set(filtered.map(s => s.set).filter(Boolean));
  document.getElementById('statPrograms').textContent = progs.size;
  document.getElementById('statSets').textContent = setSet.size;
  document.getElementById('statSources').textContent = <?= $stats['data_files'] ?>;
}

function render() {
  const container = document.getElementById('studentContainer');
  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / PER_PAGE));
  if (currentPage > pages) currentPage = pages;

  const start = (currentPage - 1) * PER_PAGE;
  const end = Math.min(start + PER_PAGE, total);
  const pageStudents = filtered.slice(start, end);

  if (total === 0) {
    container.innerHTML = `<div class="empty-state">
      <div class="icon"><i class="fas fa-search"></i></div>
      <h5>No students match your search</h5>
      <p class="text-muted">Try a different name, index number, or adjust the filters above.</p>
      <button class="btn btn-sm btn-outline-primary mt-2" onclick="resetFilters()">Clear All Filters</button>
    </div>`;
  } else if (isGridView) {
    let html = '<div class="student-grid">';
    const colors = ['#0f766e','#0891b2','#7c3aed','#dc2626','#ea580c','#ca8a04','#16a34a','#db2777','#4f46e5','#059669'];
    pageStudents.forEach((s, i) => {
      const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
      const initial = (s.first_name || s.full_name || 'S').charAt(0).toUpperCase();
      const color = colors[Math.floor(Math.random() * colors.length)];
      const srcFile = s.source_file || '';
      const indexNum = s.index_number || s.national_id || '—';
      html += `<div class="student-card" onclick="showProfile(${ALL_STUDENTS.indexOf(s)})">
        <div class="avatar" style="background:${color}">${initial}</div>
        <div class="info">
          <div class="name">${escHtml(name)}</div>
          <div class="meta">
            <span><i class="fas fa-hashtag me-1" style="font-size:.7rem"></i>${escHtml(indexNum)}</span>
            <span><i class="fas fa-phone me-1" style="font-size:.7rem"></i>${escHtml(s.phone || '—')}</span>
          </div>
          <div class="badge-program">${escHtml(s.program || 'General')}</div>
        </div>
        <div class="click-hint"><i class="fas fa-chevron-right"></i></div>
      </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
  } else {
    // List view
    let html = `<div class="table-responsive"><table class="table table-hover align-middle" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb">
      <thead class="table-light"><tr>
        <th>Name</th><th>Index / NSIN</th><th>Program</th><th>Set</th><th>Gender</th><th>Phone</th><th>File</th>
      </tr></thead><tbody>`;
    pageStudents.forEach((s, i) => {
      const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
      const indexNum = s.index_number || s.national_id || '—';
      const genBadge = (s.gender||'').toLowerCase() === 'male' ? 'bg-primary' : (s.gender||'').toLowerCase() === 'female' ? 'bg-danger' : 'bg-secondary';
      html += `<tr onclick="showProfile(${ALL_STUDENTS.indexOf(s)})" style="cursor:pointer">
        <td><strong>${escHtml(name)}</strong></td>
        <td><code>${escHtml(indexNum)}</code></td>
        <td>${escHtml(s.program || '—')}</td>
        <td>${escHtml(s.set || '—')}</td>
        <td>${s.gender ? `<span class="badge ${genBadge}">${escHtml(s.gender)}</span>` : '—'}</td>
        <td>${escHtml(s.phone || '—')}</td>
        <td><small class="text-muted">${escHtml(s.source_file || '')}</small></td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
  }

  // Pagination
  document.getElementById('resultCount').textContent = `Showing ${start + 1}–${end} of ${total} student${total !== 1 ? 's' : ''}`;
  document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${pages}`;
  let btns = '';
  if (pages > 1) {
    if (currentPage > 1) btns += `<button class="btn btn-sm btn-outline-secondary" onclick="goPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
    for (let p = Math.max(1, currentPage - 2); p <= Math.min(pages, currentPage + 2); p++) {
      btns += `<button class="btn btn-sm ${p === currentPage ? 'active' : 'btn-outline-secondary'}" onclick="goPage(${p})">${p}</button>`;
    }
    if (currentPage < pages) btns += `<button class="btn btn-sm btn-outline-secondary" onclick="goPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }
  document.getElementById('pageButtons').innerHTML = btns;
}

function goPage(p) {
  const pages = Math.ceil(filtered.length / PER_PAGE);
  if (p < 1) p = 1;
  if (p > pages) p = pages;
  currentPage = p;
  render();
  document.getElementById('studentContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function toggleView() {
  isGridView = !isGridView;
  const btn = document.getElementById('viewToggle');
  btn.innerHTML = isGridView
    ? '<i class="fas fa-list me-1"></i><span>List</span>'
    : '<i class="fas fa-th me-1"></i><span>Grid</span>';
  render();
}

// ─── PROFILE MODAL ───
let modalInstance = null;

function showProfile(index) {
  currentProfileIndex = index;
  const s = ALL_STUDENTS[index];
  if (!s) return;

  const name = s.full_name || (s.surname + ' ' + s.first_name).trim() || 'Unknown';
  const initial = (s.first_name || s.full_name || 'S').charAt(0).toUpperCase();
  const colors = ['#0f766e','#0891b2','#7c3aed','#dc2626','#ea580c','#ca8a04','#16a34a','#db2777','#4f46e5','#059669'];
  const color = colors[index % colors.length];

  document.getElementById('pAvatar').style.background = color;
  document.getElementById('pAvatar').textContent = initial;
  document.getElementById('pName').textContent = name;
  document.getElementById('pIndex').textContent = s.index_number || s.national_id || s.registration_number || '—';
  document.getElementById('pSource').textContent = s.source_file || 'Excel file';

  const fields = [
    { label: 'Full Name', val: name },
    { label: 'Surname', val: s.surname },
    { label: 'First Name', val: s.first_name },
    { label: 'Other Name', val: s.other_name },
    { label: 'Gender', val: s.gender },
    { label: 'Date of Birth', val: s.date_of_birth },
    { label: 'Index Number', val: s.index_number },
    { label: 'Registration Number', val: s.registration_number },
    { label: 'Student Number', val: s.student_number },
    { label: 'National ID (NSIN)', val: s.national_id },
    { label: 'Phone', val: s.phone },
    { label: 'Email', val: s.email },
    { label: 'Program / Course', val: s.program },
    { label: 'Level', val: s.level },
    { label: 'Set', val: s.set },
    { label: 'Intake Year', val: s.intake_year },
    { label: 'Intake Period', val: s.intake_period },
    { label: 'District', val: s.district },
    { label: 'Nationality', val: s.nationality },
    { label: 'Course Codes', val: s.course_codes, full: true },
    { label: 'No. of Papers', val: s.no_of_papers },
    { label: 'Source File', val: s.source_file, full: true },
  ];

  let html = '';
  fields.forEach(f => {
    if (!f.val) return;
    html += `<div class="field${f.full ? ' full-width' : ''}">
      <div class="label">${escHtml(f.label)}</div>
      <div class="value">${escHtml(f.val)}</div>
    </div>`;
  });
  document.getElementById('pDetails').innerHTML = html;

  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(document.getElementById('profileModal'));
  }
  modalInstance.show();
}

function printProfile() {
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  if (currentProfileIndex < 0) return;
  printWindow.document.write(`<!DOCTYPE html><html><head>
    <title>Student Profile - ISNM</title>
    <style>
      body{font-family:'Segoe UI',sans-serif;padding:30px;color:#111;max-width:700px;margin:0 auto}
      .header{display:flex;gap:20px;align-items:center;border-bottom:2px solid #0f766e;padding-bottom:16px;margin-bottom:20px}
      .avatar{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;flex-shrink:0}
      .info h2{margin:0;font-size:1.4rem}
      .info .sub{color:#6b7280;font-size:.85rem}
      .grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px}
      .field{padding:6px 0;border-bottom:1px solid #e5e7eb;break-inside:avoid}
      .field .lbl{font-size:.72rem;text-transform:uppercase;color:#9ca3af;font-weight:600}
      .field .val{font-size:.92rem;font-weight:500}
      .field.full{grid-column:1/-1}
      .footer{text-align:center;margin-top:30px;font-size:.8rem;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:16px}
      @page{margin:12mm}
      @media print{body{padding:0}}
    </style>
  </head><body>
    <div class="header">
      <div class="avatar" style="background:${document.getElementById('pAvatar').style.background}">${document.getElementById('pAvatar').textContent}</div>
      <div class="info">
        <h2>${document.getElementById('pName').textContent}</h2>
        <div class="sub">${document.getElementById('pIndex').textContent} &middot; ${document.getElementById('pSource').textContent}</div>
      </div>
    </div>
    <div class="grid">`);

  document.querySelectorAll('#pDetails .field').forEach(f => {
    const label = f.querySelector('.label')?.textContent || '';
    const value = f.querySelector('.value')?.textContent || '';
    const isFull = f.classList.contains('full-width');
    printWindow.document.write(`<div class="field${isFull?' full':''}"><div class="lbl">${escHtml(label)}</div><div class="val">${escHtml(value)}</div></div>`);
  });

  printWindow.document.write(`</div>
    <div class="footer">
      Iganga School of Nursing &amp; Midwifery &middot; Student Directory &middot; ${new Date().toLocaleDateString()}
    </div>
  </body></html>`);
  printWindow.document.close();
  setTimeout(() => { printWindow.focus(); printWindow.print(); }, 300);
}

// ─── HELPERS ───
function escHtml(str) {
  if (!str) return '';
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
</script>
</body>
</html>