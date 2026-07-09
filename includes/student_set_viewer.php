<?php
/**
 * Student Set Viewer – Reusable Component
 * 
 * Drop this include into any dashboard to add:
 * - Set/program/level filter selector
 * - Detailed word-format student cards with full info
 * - Printable layout
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/student_set_viewer.php';
 *   renderStudentSetViewer($studentsConn);
 * 
 * Expects: $studentsConn (mysqli connection to igangaschoolofl_students_db)
 */

if (!function_exists('renderStudentSetViewer')):

function renderStudentSetViewer($conn, array $options = []) {
    $selectedSet     = $_GET['set_name'] ?? '';
    $selectedProgram = $_GET['program']  ?? '';
    $selectedLevel   = $_GET['level']    ?? '';
    $search          = trim($_GET['search'] ?? '');
    $page            = max(1, intval($_GET['page'] ?? 1));

    $title          = $options['title'] ?? 'Student Records by Set';
    $icon           = $options['icon']  ?? 'fa-users';
    $showAllOption  = $options['show_all'] ?? false;
    $isSuperAdmin   = $options['super_admin'] ?? false;
    $perPage        = max(1, intval($options['per_page'] ?? 50));
    $showStatement  = $options['show_statement_link'] ?? false;

    $canShowAll = $showAllOption || $isSuperAdmin;
    $hasFilters = $selectedSet || $selectedProgram || $selectedLevel || $search !== '';

    // ── Fetch distinct filter options ──
    $sets     = [];
    $programs = [];
    $levels   = [];

    if ($conn) {
        try {
            $r = $conn->query("SELECT DISTINCT set_name FROM students WHERE set_name IS NOT NULL AND set_name != '' ORDER BY set_name DESC");
            if ($r) while ($row = $r->fetch_assoc()) $sets[] = $row['set_name'];

            $r = $conn->query("SELECT DISTINCT program FROM students WHERE program IS NOT NULL AND program != '' ORDER BY program");
            if ($r) while ($row = $r->fetch_assoc()) $programs[] = $row['program'];

            $r = $conn->query("SELECT DISTINCT level FROM students WHERE level IS NOT NULL AND level != '' ORDER BY level");
            if ($r) while ($row = $r->fetch_assoc()) $levels[] = $row['level'];
        } catch (Exception $e) { error_log('student_set_viewer load: ' . $e->getMessage()); }
    }

    // ── Build WHERE clause ──
    $conditions = ['1=1'];
    $bindParams = [];
    $bindTypes  = '';

    if ($selectedSet) {
        $conditions[] = 'set_name = ?';
        $bindParams[] = $selectedSet;
        $bindTypes   .= 's';
    }
    if ($selectedProgram) {
        $conditions[] = 'program = ?';
        $bindParams[] = $selectedProgram;
        $bindTypes   .= 's';
    }
    if ($selectedLevel) {
        $conditions[] = 'level = ?';
        $bindParams[] = $selectedLevel;
        $bindTypes   .= 's';
    }
    if ($search !== '') {
        $conditions[] = '(full_name LIKE ? OR first_name LIKE ? OR surname LIKE ? OR other_name LIKE ? OR registration_number LIKE ? OR student_number LIKE ? OR index_number LIKE ? OR phone LIKE ? OR mobile_number LIKE ?)';
        $like = '%' . $search . '%';
        for ($i = 0; $i < 9; $i++) {
            $bindParams[] = $like;
            $bindTypes   .= 's';
        }
    }

    $whereSQL = implode(' AND ', $conditions);

    // ── Query data (count + paginated result) ──
    $students      = [];
    $totalFiltered = 0;

    if ($conn && ($hasFilters || $canShowAll)) {
        try {
            // COUNT
            $countSQL = "SELECT COUNT(*) FROM students WHERE $whereSQL";
            if (!empty($bindParams)) {
                $stmt = $conn->prepare($countSQL);
                if ($stmt) {
                    $stmt->bind_param($bindTypes, ...$bindParams);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) $totalFiltered = intval($res->fetch_row()[0]);
                    $stmt->close();
                }
            } else {
                $r = $conn->query($countSQL);
                if ($r) $totalFiltered = intval($r->fetch_row()[0]);
            }

            // DATA
            $totalPages  = max(1, intval(ceil($totalFiltered / $perPage)));
            $page        = min($page, $totalPages);
            $offset      = ($page - 1) * $perPage;

            $dataSQL = "SELECT * FROM students WHERE $whereSQL ORDER BY set_name DESC, program, level, surname, first_name LIMIT ?, ?";

            if (!empty($bindParams)) {
                $dataParams = array_merge($bindParams, [$offset, $perPage]);
                $dataTypes  = $bindTypes . 'ii';
                $stmt = $conn->prepare($dataSQL);
                if ($stmt) {
                    $stmt->bind_param($dataTypes, ...$dataParams);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        while ($row = $res->fetch_assoc()) $students[] = $row;
                    }
                    $stmt->close();
                }
            } else {
                $stmtLimit = $conn->prepare("SELECT * FROM students WHERE $whereSQL ORDER BY set_name DESC, program, level, surname, first_name LIMIT ?, ?");
                if ($stmtLimit) {
                    $stmtLimit->bind_param('ii', $offset, $perPage);
                    $stmtLimit->execute();
                    $r = $stmtLimit->get_result();
                    if ($r) {
                        while ($row = $r->fetch_assoc()) $students[] = $row;
                    }
                    $stmtLimit->close();
                }
            }
        } catch (Exception $e) { error_log('student_set_viewer query: ' . $e->getMessage()); }
    } else {
        $totalPages = 1;
    }

    // ── Check if a specific student is requested for profile view ──
    $viewStudentId = $_GET['view_student'] ?? '';
    $viewStudent   = null;
    if ($viewStudentId && $conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? OR index_number = ? OR id = ? LIMIT 1");
            if ($stmt) {
                $viewStudentIdInt = intval($viewStudentId);
                $stmt->bind_param("ssi", $viewStudentId, $viewStudentId, $viewStudentIdInt);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) $viewStudent = $r->fetch_assoc();
                $stmt->close();
            }
        } catch (Exception $e) { error_log('student_set_viewer view: ' . $e->getMessage()); }
    }

    $totalPages   = max(1, intval(ceil($totalFiltered / $perPage)));
    $currentPage  = min($page, $totalPages);
    $offset       = max(0, ($currentPage - 1) * $perPage);
    $useTable     = $totalFiltered > 30;

    // ── Print support ──
    $isPrint = isset($_GET['print']) && $_GET['print'] === 'set';

    // ── Build pagination / action query string helper ──
    $listQuery = array_diff_key($_GET, array_flip(['view_student', 'print']));
    ?>
    <div class="student-set-viewer">
        <?php if ($viewStudent): ?>
            <!-- ── SINGLE STUDENT FULL PROFILE ── -->
            <?php renderFullStudentProfile($viewStudent, $conn, $showStatement); ?>
        <?php else: ?>

        <!-- ── HEADER ── -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0" style="color:#0f172a">
                <i class="fas <?= $icon ?> me-2" style="color:var(--isnm-blue, #1e3a8a)"></i><?= $title ?>
                <?php if ($totalFiltered > 0): ?>
                    <span class="badge bg-primary ms-2 fs-6"><?= $totalFiltered ?> student<?= $totalFiltered !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </h4>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($students): ?>
                <button class="btn btn-sm btn-outline-success" onclick="printStudentSet()"><i class="fas fa-print me-1"></i> Print</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── FILTER BAR ── -->
        <form method="GET" class="row g-2 mb-4 p-3 rounded-3" style="background:linear-gradient(135deg,#1e3a8a,#0f4c3a);">
            <?php foreach ($_GET as $gk => $gv): ?>
                <?php if (!in_array($gk, ['set_name','program','level','search','page','print','view_student','sort_by','sort_order'])): ?>
                    <input type="hidden" name="<?= htmlspecialchars($gk) ?>" value="<?= htmlspecialchars($gv) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="page" value="1">

            <div class="col-md-3">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-layer-group me-1"></i>Set / Intake</label>
                <select name="set_name" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Sets</option>
                    <?php foreach ($sets as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $selectedSet === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-graduation-cap me-1"></i>Program</label>
                <select name="program" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selectedProgram === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-layer-group me-1"></i>Level</label>
                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <?php foreach ($levels as $l): ?>
                        <option value="<?= htmlspecialchars($l) ?>" <?= $selectedLevel === $l ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-search me-1"></i>Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, ID, phone..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-light btn-sm flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                <?php if ($hasFilters): ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, array_flip(['set_name','program','level','search','page']))) ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($isPrint): ?>
        <div class="print-header d-none d-print-block text-center mb-4">
            <h2 style="color:#1a237e;">IGANGA SCHOOL OF NURSING & MIDWIFERY</h2>
            <h4>Student Records – <?= htmlspecialchars($selectedSet ?: 'All Sets') ?><?= $search !== '' ? ' (Search: ' . htmlspecialchars($search) . ')' : '' ?></h4>
            <p class="text-muted">Generated: <?= date('l, F j, Y') . ' | Page ' . $currentPage . ' of ' . $totalPages ?></p>
            <hr>
        </div>
        <?php endif; ?>

        <?php if (empty($students) && ($hasFilters || $canShowAll)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search" style="font-size:3rem;color:#cbd5e1"></i>
                <p class="text-muted mt-3 fs-5">No students found matching your criteria</p>
            </div>
        <?php elseif (empty($students)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users" style="font-size:3rem;color:#cbd5e1"></i>
                <p class="text-muted mt-3 fs-5">Select a Set above to view students</p>
            </div>
        <?php else: ?>

            <?php if ($useTable): ?>
            <!-- ── TABLE VIEW (fast, scrollable) ── -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 student-records-table" id="studentRecordsTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="sortable" data-col="0" style="width:40px">#</th>
                            <th class="sortable" data-col="1" style="width:60px">Photo</th>
                            <th class="sortable" data-col="2">Name</th>
                            <th class="sortable" data-col="3">Student ID</th>
                            <th class="sortable" data-col="4">Program</th>
                            <th class="sortable" data-col="5">Level</th>
                            <th class="sortable" data-col="6">Phone / Mobile</th>
                            <th class="sortable" data-col="7">Status</th>
                            <th style="width:80px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rn = $offset + 1; foreach ($students as $stu): ?>
                        <tr>
                            <td><?= $rn++ ?></td>
                            <td>
                                <?php if (!empty($stu['passport_photo']) || !empty($stu['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($stu['passport_photo'] ?: $stu['profile_picture']) ?>" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;font-size:0.85rem;font-weight:700;">
                                        <?= strtoupper(substr($stu['first_name'] ?? 'S', 0, 1) . substr($stu['surname'] ?? 'T', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($stu['full_name'] ?: trim($stu['first_name'] . ' ' . ($stu['other_name'] ?? '') . ' ' . $stu['surname'])) ?></strong>
                                <?php if (!empty($stu['index_number'])): ?>
                                    <br><small class="text-muted">Index: <?= htmlspecialchars($stu['index_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($stu['student_id'] ?? $stu['student_number'] ?? '-') ?></code></td>
                            <td><?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($stu['level'] ?? '-') ?></td>
                            <td class="phone-cell">
                                <?php if (!empty($stu['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($stu['phone']) ?>" class="text-decoration-none"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($stu['phone']) ?></a>
                                <?php endif; ?>
                                <?php if (!empty($stu['mobile_number'])): ?>
                                    <?php if (!empty($stu['phone'])): ?><br><?php endif; ?>
                                    <a href="tel:<?= htmlspecialchars($stu['mobile_number']) ?>" class="badge bg-info text-dark text-decoration-none mt-1"><i class="fas fa-mobile-alt me-1"></i><?= htmlspecialchars($stu['mobile_number']) ?></a>
                                <?php endif; ?>
                                <?php if (empty($stu['phone']) && empty($stu['mobile_number'])): ?>
                                    <span class="text-muted"> , </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= ($stu['status'] ?? 'Active') === 'Active' ? 'success' : (($stu['status'] ?? '') === 'Graduated' ? 'primary' : 'secondary') ?>">
                                    <?= htmlspecialchars($stu['status'] ?? 'Active') ?>
                                </span>
                            </td>
                            <td>
                                <?php $profileLink = '?' . http_build_query(array_merge($listQuery, ['view_student' => $stu['student_id'] ?? $stu['student_number'] ?? $stu['id'], 'page' => $currentPage])); ?>
                                <a href="<?= $profileLink ?>" class="btn btn-sm btn-outline-primary" title="Full Profile"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline-warning" onclick="openEditStudentModal(<?= $stu['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteStudent(<?= $stu['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <!-- ── CARD VIEW (prettier, for small sets) ── -->
            <div class="student-cards-container" id="studentSetCards">
                <?php
                $currentSet = '';
                $cardIndex = 0;
                foreach ($students as $stu):
                    $cardIndex++;
                    $setDisplay = $stu['set_name'] ?? 'Unknown Set';
                    $showSetHeader = ($setDisplay !== $currentSet);
                    if ($showSetHeader) $currentSet = $setDisplay;
                ?>
                    <?php if ($showSetHeader): ?>
                        <div class="set-section-header d-flex align-items-center gap-2 mt-4 mb-3">
                            <span class="badge bg-dark fs-6 px-3 py-2"><?= htmlspecialchars($setDisplay) ?></span>
                            <div class="flex-grow-1" style="height:2px;background:linear-gradient(90deg,#1e3a8a,transparent)"></div>
                        </div>
                    <?php endif; ?>

                    <div class="student-word-card" id="student-card-<?= $cardIndex ?>">
                        <div class="row g-0">
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center p-3 bg-light rounded-start">
                                <div class="student-avatar-lg">
                                    <?php if (!empty($stu['passport_photo']) || !empty($stu['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($stu['passport_photo'] ?: $stu['profile_picture']) ?>" alt="Photo" class="rounded-circle" style="width:90px;height:90px;object-fit:cover;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.15)">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:90px;height:90px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;font-size:2.5rem;font-weight:700;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.15)">
                                            <?= strtoupper(substr($stu['first_name'] ?? 'S', 0, 1) . substr($stu['surname'] ?? 'T', 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-<?= ($stu['status'] ?? 'Active') === 'Active' ? 'success' : (($stu['status'] ?? '') === 'Graduated' ? 'primary' : 'secondary') ?> mt-2">
                                    <?= htmlspecialchars($stu['status'] ?? 'Active') ?>
                                </span>
                            </div>

                            <div class="col-md-10">
                                <div class="card-body p-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="fw-bold mb-0" style="color:#0f172a"><?= htmlspecialchars($stu['full_name'] ?: trim($stu['first_name'] . ' ' . ($stu['other_name'] ?? '') . ' ' . $stu['surname'])) ?></h5>
                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                <?php if (!empty($stu['registration_number'])): ?>
                                                    <span class="badge bg-secondary">Reg: <?= htmlspecialchars($stu['registration_number']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($stu['index_number'])): ?>
                                                    <span class="badge bg-info">Index: <?= htmlspecialchars($stu['index_number']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($stu['national_student_id_number'])): ?>
                                                    <span class="badge bg-secondary">NSIN: <?= htmlspecialchars($stu['national_student_id_number']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($stu['student_number'])): ?>
                                                    <span class="badge bg-dark">#<?= htmlspecialchars($stu['student_number']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">Student ID: <strong><?= htmlspecialchars($stu['student_id'] ?? $stu['student_number']) ?></strong></small>
                                            <div class="mt-1">
                                                <?php $profileLink = '?' . http_build_query(array_merge($listQuery, ['view_student' => $stu['student_id'] ?? $stu['student_number'] ?? $stu['id'], 'page' => $currentPage])); ?>
                                                <a href="<?= $profileLink ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> Full Profile</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-user me-1 text-primary"></i>Personal</div>
                                                <div class="info-value">
                                                    <?php if (!empty($stu['gender'])): ?><span class="me-2"><?= htmlspecialchars($stu['gender']) ?></span><?php endif; ?>
                                                    <?php if (!empty($stu['date_of_birth'])): ?><span><?= date('d M Y', strtotime($stu['date_of_birth'])) ?></span><?php endif; ?>
                                                    <?php if (!empty($stu['nationality'])): ?><br><span><?= htmlspecialchars($stu['nationality']) ?></span><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-graduation-cap me-1 text-success"></i>Academic</div>
                                                <div class="info-value">
                                                    <div><?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '-') ?></div>
                                                    <?php if (!empty($stu['level'])): ?><small>Level: <?= htmlspecialchars($stu['level']) ?></small><?php endif; ?>
                                                    <?php if (!empty($stu['current_semester'])): ?><small> | <?= htmlspecialchars($stu['current_semester']) ?></small><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-phone me-1 text-warning"></i>Contact</div>
                                                <div class="info-value">
                                                    <?php
                                                    $phones = [];
                                                    if (!empty($stu['phone'])) $phones[] = $stu['phone'];
                                                    if (!empty($stu['mobile_number'])) $phones[] = $stu['mobile_number'];
                                                    if ($phones):
                                                    ?>
                                                        <div class="mb-1">
                                                            <?php foreach ($phones as $p): ?>
                                                                <a href="tel:<?= htmlspecialchars($p) ?>" class="badge bg-warning text-dark text-decoration-none me-1 mb-1"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($p) ?></a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($stu['email'])): ?><div><i class="fas fa-envelope me-1 small"></i><?= htmlspecialchars($stu['email']) ?></div><?php endif; ?>
                                                    <?php if (!empty($stu['address'])): ?><div><small><?= htmlspecialchars(substr($stu['address'], 0, 50)) ?></small></div><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-ambulance me-1 text-danger"></i>Emergency Contact</div>
                                                <div class="info-value">
                                                    <?php if (!empty($stu['emergency_contact_name'])): ?>
                                                        <div><?= htmlspecialchars($stu['emergency_contact_name']) ?></div>
                                                        <small>
                                                            <?php if (!empty($stu['emergency_contact_phone'])): ?><?= htmlspecialchars($stu['emergency_contact_phone']) ?><?php endif; ?>
                                                            <?php if (!empty($stu['emergency_contact_email'])): ?> | <?= htmlspecialchars($stu['emergency_contact_email']) ?><?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Not provided</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-user-shield me-1 text-info"></i>Guardian</div>
                                                <div class="info-value">
                                                    <?php if (!empty($stu['guardian_name'])): ?>
                                                        <div><?= htmlspecialchars($stu['guardian_name']) ?></div>
                                                        <?php if (!empty($stu['guardian_phone'])): ?><small><?= htmlspecialchars($stu['guardian_phone']) ?></small><?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Not provided</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>Created: <?= !empty($stu['created_at']) ? date('d M Y', strtotime($stu['created_at'])) : '-' ?>
                                            <?php if (!empty($stu['intake_date'])): ?> | Intake: <?= date('d M Y', strtotime($stu['intake_date'])) ?><?php endif; ?>
                                        </small>
                                        <div class="d-flex gap-1">
                                            <a href="<?= $profileLink ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-file-alt"></i></a>
                                            <button class="btn btn-sm btn-outline-warning" onclick="openEditStudentModal(<?= $stu['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteStudent(<?= $stu['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- ── PAGINATION ── -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-3 d-print-none" aria-label="Student pagination">
                <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($listQuery, ['page' => 1])) ?>" aria-label="First"><i class="fas fa-angle-double-left"></i></a>
                    </li>
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($listQuery, ['page' => $currentPage - 1])) ?>" aria-label="Previous"><i class="fas fa-angle-left"></i></a>
                    </li>
                    <?php
                    $range = 2;
                    $startPage = max(1, $currentPage - $range);
                    $endPage   = min($totalPages, $currentPage + $range);
                    if ($startPage > 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif;
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($listQuery, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor;
                    if ($endPage < $totalPages): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($listQuery, ['page' => $currentPage + 1])) ?>" aria-label="Next"><i class="fas fa-angle-right"></i></a>
                    </li>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($listQuery, ['page' => $totalPages])) ?>" aria-label="Last"><i class="fas fa-angle-double-right"></i></a>
                    </li>
                </ul>
            </nav>
            <div class="text-center text-muted small mt-1 d-print-none">
                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalFiltered) ?> of <?= $totalFiltered ?> students
            </div>
            <?php endif; ?>

            <!-- ── SUMMARY ── -->
            <div class="mt-4 p-3 bg-light rounded d-print-none">
                <div class="row g-2 text-center">
                    <div class="col-md-3">
                        <div class="fw-bold fs-5"><?= $totalFiltered ?></div>
                        <small class="text-muted">Total Students</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold fs-5"><?= count(array_unique(array_column($students, 'set_name'))) ?></div>
                        <small class="text-muted">Sets</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold fs-5"><?= count(array_unique(array_column($students, 'program'))) ?></div>
                        <small class="text-muted">Programs</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold fs-5">
                            <?= count(array_filter($students, fn($s) => ($s['status'] ?? 'Active') === 'Active')) ?>
                        </div>
                        <small class="text-muted">Active</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ── EDIT STUDENT MODAL ── -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;border-radius:10px;width:90%;max-width:800px;max-height:90vh;overflow-y:auto;padding:0;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding:16px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                <h5 style="margin:0;font-size:1.1rem;font-weight:600;"><i class="fas fa-edit"></i> Edit Student</h5>
                <button class="close" onclick="closeEditStudentModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <div id="editStudentFormContainer"><div class="text-center" style="padding:40px"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div></div>
            </div>
            <div class="modal-footer" style="padding:12px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px;">
                <button class="btn btn-secondary" onclick="closeEditStudentModal()" style="padding:8px 20px;border-radius:6px;border:none;cursor:pointer;">Cancel</button>
                <button class="btn btn-primary" onclick="saveEditStudent()" style="padding:8px 20px;border-radius:6px;border:none;cursor:pointer;background:#1e3a8a;color:#fff;"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <style>
        .student-word-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            page-break-inside: avoid;
        }
        .student-word-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            border-color: #93c5fd;
        }
        .info-block {
            padding: 6px 10px;
            background: #f8fafc;
            border-radius: 8px;
            height: 100%;
        }
        .info-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 0.85rem;
            color: #1e293b;
            line-height: 1.4;
        }
        .set-section-header .badge {
            font-size: 1.1rem !important;
            padding: 8px 20px !important;
            border-radius: 30px !important;
        }
        .student-records-table th.sortable {
            cursor: pointer;
            white-space: nowrap;
            user-select: none;
        }
        .student-records-table th.sortable:hover {
            background: #2c3e50;
        }
        .student-records-table th.sortable::after {
            content: '\f0dc';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: 6px;
            font-size: 0.7rem;
            opacity: 0.5;
        }
        .student-records-table th.sortable.sort-asc::after {
            content: '\f0de';
            opacity: 1;
        }
        .student-records-table th.sortable.sort-desc::after {
            content: '\f0dd';
            opacity: 1;
        }
        .student-records-table td.phone-cell {
            white-space: nowrap;
        }
        .pagination {
            gap: 2px;
        }
        .pagination .page-link {
            border-radius: 6px !important;
            margin: 0;
            font-size: 0.85rem;
        }
        .pagination .page-item.active .page-link {
            background: #1e3a8a;
            border-color: #1e3a8a;
        }
        @media print {
            .student-set-viewer .filter-bar,
            .student-set-viewer form,
            .student-set-viewer .btn,
            .student-set-viewer .d-print-none,
            .sidebar, .top-bar, nav, .no-print,
            .dashboard-sidebar, .dashboard-header,
            .mobile-menu-toggle, .sidebar-overlay {
                display: none !important;
            }
            .page-content, .dashboard-main, .main {
                margin-left: 0 !important;
                padding: 10px !important;
            }
            .student-word-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                border-radius: 6px !important;
                margin-bottom: 8px !important;
            }
            .student-word-card .bg-light {
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .badge, .table-dark {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .set-section-header .badge {
                background: #1a237e !important;
                color: white !important;
            }
            .info-block {
                background: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                background: white !important;
                font-size: 10pt;
            }
            .print-header {
                display: block !important;
            }
            .student-cards-container {
                display: block !important;
            }
            .student-avatar-lg img,
            .student-avatar-lg div {
                width: 70px !important;
                height: 70px !important;
                font-size: 1.8rem !important;
            }
            .col-md-2, .col-md-4, .col-md-6, .col-md-10 {
                width: auto !important;
            }
            .student-records-table {
                font-size: 9pt;
            }
            .student-records-table th {
                background: #1a237e !important;
                color: white !important;
            }
            @page {
                margin: 15mm;
                size: A4 portrait;
            }
        }
    </style>

    <script>
    function printStudentSet() {
        const url = new URL(window.location.href);
        url.searchParams.set('print', 'set');
        window.open(url.toString(), '_blank');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('studentRecordsTable');
        if (table) {
            const headers = table.querySelectorAll('th.sortable');
            headers.forEach(function(header) {
                header.addEventListener('click', function() {
                    const col = parseInt(this.dataset.col);
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const isAsc = this.classList.contains('sort-asc');

                    headers.forEach(function(h) {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

                    rows.sort(function(a, b) {
                        let aVal = a.cells[col] ? a.cells[col].innerText.trim() : '';
                        let bVal = b.cells[col] ? b.cells[col].innerText.trim() : '';
                        let aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                        let bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return isAsc ? bNum - aNum : aNum - bNum;
                        }
                        return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
                    });

                    rows.forEach(function(row) {
                        tbody.appendChild(row);
                    });
                });
            });
        }
    });
    </script>
    <script>
    // ── Edit Student Modal Functions ──
    var editStudentData = {};
    function openEditStudentModal(id) {
        var s = null;
        var allData = <?= json_encode($students ?? []) ?>;
        if (allData) { s = allData.find(function(st) { return parseInt(st.id) === parseInt(id) || st.student_id == id || st.student_number == id; }); }
        if (!s) {
            // If student not in current page data, load via AJAX
            fetch('ajax/update_student.php?fetch=1&id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.id) { buildEditForm(data); }
                    else { alert('Student not found'); }
                })
                .catch(function() { alert('Failed to load student data'); });
            return;
        }
        buildEditForm(s);
    }
    function buildEditForm(s) {
        editStudentData = s;
        var html = '<form id="editStudentForm" onsubmit="return false">';
        html += '<input type="hidden" name="id" value="' + (s.id||s.student_id||'') + '">';
        html += '<div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">First Name</label><input type="text" name="first_name" class="form-control" value="' + escHtml(s.first_name||'') + '" required style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Other Name</label><input type="text" name="other_name" class="form-control" value="' + escHtml(s.other_name||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Surname</label><input type="text" name="surname" class="form-control" value="' + escHtml(s.surname||'') + '" required style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Full Name</label><input type="text" name="full_name" class="form-control" value="' + escHtml(s.full_name||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Gender</label><select name="gender" class="form-control" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"><option value="Male"' + (s.gender==='Male'?' selected':'') + '>Male</option><option value="Female"' + (s.gender==='Female'?' selected':'') + '>Female</option><option value="Other"' + (s.gender==='Other'?' selected':'') + '>Other</option></select></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="' + (s.date_of_birth||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Nationality</label><input type="text" name="nationality" class="form-control" value="' + escHtml(s.nationality||'Ugandan') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Address</label><input type="text" name="address" class="form-control" value="' + escHtml(s.address||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">District</label><input type="text" name="district" class="form-control" value="' + escHtml(s.district||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '</div>';
        html += '<h6 style="margin:16px 0 8px;font-weight:600;border-bottom:1px solid #e2e8f0;padding-bottom:4px;"><i class="fas fa-graduation-cap"></i> Academic Info</h6>';
        html += '<div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Student Number</label><input type="text" name="student_number" class="form-control" value="' + escHtml(s.student_number||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Registration Number</label><input type="text" name="registration_number" class="form-control" value="' + escHtml(s.registration_number||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Index Number</label><input type="text" name="index_number" class="form-control" value="' + escHtml(s.index_number||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">National ID Number</label><input type="text" name="national_student_id_number" class="form-control" value="' + escHtml(s.national_student_id_number||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Program</label><input type="text" name="program" class="form-control" value="' + escHtml(s.program||s.course||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Course</label><input type="text" name="course" class="form-control" value="' + escHtml(s.course||s.program||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Level</label><input type="text" name="level" class="form-control" value="' + escHtml(s.level||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Year</label><select name="current_year" class="form-control" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;">';
        for (var y=1;y<=5;y++) { html += '<option value="' + y + '"' + (parseInt(s.current_year||s.year)===y?' selected':'') + '>Year ' + y + '</option>'; }
        html += '</select></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Semester</label><select name="current_semester" class="form-control" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"><option value="Semester 1"' + (s.current_semester==='Semester 1'?' selected':'') + '>Semester 1</option><option value="Semester 2"' + (s.current_semester==='Semester 2'?' selected':'') + '>Semester 2</option><option value="Semester 3"' + (s.current_semester==='Semester 3'?' selected':'') + '>Semester 3</option></select></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Set Name</label><input type="text" name="set_name" class="form-control" value="' + escHtml(s.set_name||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Status</label><select name="status" class="form-control" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"><option value="Active"' + (s.status==='Active'?' selected':'') + '>Active</option><option value="Pending"' + (s.status==='Pending'?' selected':'') + '>Pending</option><option value="Graduated"' + (s.status==='Graduated'?' selected':'') + '>Graduated</option><option value="Suspended"' + (s.status==='Suspended'?' selected':'') + '>Suspended</option><option value="deleted"' + (s.status==='deleted'?' selected':'') + '>Deleted</option></select></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Intake Year</label><input type="number" name="intake_year" class="form-control" value="' + (s.intake_year||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Intake Period</label><input type="text" name="intake_period" class="form-control" value="' + escHtml(s.intake_period||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Category</label><input type="text" name="student_category" class="form-control" value="' + escHtml(s.student_category||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '</div>';
        html += '<h6 style="margin:16px 0 8px;font-weight:600;border-bottom:1px solid #e2e8f0;padding-bottom:4px;"><i class="fas fa-phone"></i> Contact Info</h6>';
        html += '<div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Phone</label><input type="text" name="phone" class="form-control" value="' + escHtml(s.phone||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Mobile Number</label><input type="text" name="mobile_number" class="form-control" value="' + escHtml(s.mobile_number||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Email</label><input type="email" name="email" class="form-control" value="' + escHtml(s.email||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '</div>';
        html += '<h6 style="margin:16px 0 8px;font-weight:600;border-bottom:1px solid #e2e8f0;padding-bottom:4px;"><i class="fas fa-user-shield"></i> Guardian & Emergency</h6>';
        html += '<div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Guardian Name</label><input type="text" name="guardian_name" class="form-control" value="' + escHtml(s.guardian_name||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control" value="' + escHtml(s.guardian_phone||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Guardian Email</label><input type="email" name="guardian_email" class="form-control" value="' + escHtml(s.guardian_email||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Emergency Contact</label><input type="text" name="emergency_contact_name" class="form-control" value="' + escHtml(s.emergency_contact_name||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Emergency Phone</label><input type="text" name="emergency_contact_phone" class="form-control" value="' + escHtml(s.emergency_contact_phone||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Emergency Email</label><input type="email" name="emergency_contact_email" class="form-control" value="' + escHtml(s.emergency_contact_email||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Sponsor</label><input type="text" name="sponsor" class="form-control" value="' + escHtml(s.sponsor||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Marital Status</label><input type="text" name="marital_status" class="form-control" value="' + escHtml(s.marital_status||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '<div class="form-group"><label style="font-weight:600;font-size:0.8rem;">Religion</label><input type="text" name="religion" class="form-control" value="' + escHtml(s.religion||'') + '" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;"></div>';
        html += '</div>';
        html += '</form>';
        document.getElementById('editStudentFormContainer').innerHTML = html;
        document.getElementById('editStudentModal').style.display = 'flex';
    }
    function closeEditStudentModal() {
        document.getElementById('editStudentModal').style.display = 'none';
    }
    function saveEditStudent() {
        var form = document.getElementById('editStudentForm');
        if (!form) return;
        var data = new FormData(form);
        data.set('action', 'update');
        data.append('csrf_token', window.CSRF_TOKEN || '');
        document.querySelector('#editStudentModal .modal-footer .btn-primary').disabled = true;
        document.querySelector('#editStudentModal .modal-footer .btn-primary').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        fetch('ajax/update_student.php', { method: 'POST', body: data })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    alert('Student updated successfully!');
                    closeEditStudentModal();
                    location.reload();
                } else {
                    alert('Error: ' + (res.error || 'Update failed'));
                }
            })
            .catch(function(err) {
                alert('Network error: ' + err.message);
            })
            .finally(function() {
                document.querySelector('#editStudentModal .modal-footer .btn-primary').disabled = false;
                document.querySelector('#editStudentModal .modal-footer .btn-primary').innerHTML = '<i class="fas fa-save"></i> Save Changes';
            });
    }
    function confirmDeleteStudent(id) {
        if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) return;
        var f = new FormData();
        f.set('action','delete'); f.set('id',id);
        f.append('csrf_token', window.CSRF_TOKEN || '');
        fetch('ajax/update_student.php',{method:'POST',body:f})
        .then(function(r){return r.json()})
        .then(function(res){
            if(res.success){alert('Student deleted successfully!');location.reload();}
            else{alert('Error: '+(res.error||'Delete failed'));}
        })
        .catch(function(err){alert('Network error: '+err.message);});
    }
    </script>
    <?php
}

/**
 * Render a single student's full profile (Word-format detail view)
 */
function renderFullStudentProfile($stu, $conn, $showStatementLink = false) {
    ?>
    <div class="student-full-profile">
        <!-- Back button -->
        <div class="mb-3">
            <a href="?" class="btn btn-outline-secondary btn-sm d-print-none"><i class="fas fa-arrow-left me-1"></i> Back to Student List</a>
            <button class="btn btn-outline-success btn-sm d-print-none ms-2" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Profile</button>
            <?php if ($showStatementLink && !empty($stu['student_id'])): ?>
                <a href="school-bursar.php?view=student_statement&student_id=<?= htmlspecialchars($stu['student_id']) ?>" class="btn btn-outline-warning btn-sm d-print-none ms-2"><i class="fas fa-receipt me-1"></i> Print Statement</a>
            <?php endif; ?>
        </div>

        <!-- Print header -->
        <div class="text-center mb-4 d-none d-print-block">
            <h2 style="color:#1a237e;">IGANGA SCHOOL OF NURSING & MIDWIFERY</h2>
            <h4>Full Student Profile</h4>
            <hr>
        </div>

        <!-- Profile Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <?php if (!empty($stu['passport_photo']) || !empty($stu['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($stu['passport_photo'] ?: $stu['profile_picture']) ?>" alt="Photo" style="width:130px;height:130px;object-fit:cover;border-radius:50%;border:4px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.1)">
                        <?php else: ?>
                            <div style="width:130px;height:130px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#3b82f6);display:flex;align-items:center;justify-content:center;color:white;font-size:3.5rem;font-weight:700;margin:0 auto;border:4px solid #e2e8f0;">
                                <?= strtoupper(substr($stu['first_name'] ?? 'S', 0, 1) . substr($stu['surname'] ?? 'T', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h3 class="fw-bold mb-1"><?= htmlspecialchars($stu['full_name'] ?: trim($stu['first_name'] . ' ' . ($stu['other_name'] ?? '') . ' ' . $stu['surname'])) ?></h3>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if (!empty($stu['registration_number'])): ?>
                                <span class="badge bg-secondary fs-6">Reg: <?= htmlspecialchars($stu['registration_number']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($stu['index_number'])): ?>
                                <span class="badge bg-info fs-6">Index: <?= htmlspecialchars($stu['index_number']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($stu['national_student_id_number'])): ?>
                                <span class="badge bg-secondary fs-6">NSIN: <?= htmlspecialchars($stu['national_student_id_number']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($stu['phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($stu['phone']) ?>" class="badge bg-warning text-dark fs-6 text-decoration-none"><i class="fas fa-phone-alt me-1"></i><?= htmlspecialchars($stu['phone']) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($stu['mobile_number'])): ?>
                                <a href="tel:<?= htmlspecialchars($stu['mobile_number']) ?>" class="badge bg-info text-dark fs-6 text-decoration-none"><i class="fas fa-mobile-alt me-1"></i><?= htmlspecialchars($stu['mobile_number']) ?></a>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><strong>Program:</strong> <?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '-') ?></p>
                        <p class="mb-1">
                            <strong>Set:</strong> <?= htmlspecialchars($stu['set_name'] ?? '-') ?> &nbsp;|&nbsp;
                            <strong>Level:</strong> <?= htmlspecialchars($stu['level'] ?? '-') ?> &nbsp;|&nbsp;
                            <strong>Year:</strong> <?= htmlspecialchars($stu['current_year'] ?? $stu['year'] ?? '-') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-<?= ($stu['status'] ?? 'Active') === 'Active' ? 'success' : (($stu['status'] ?? '') === 'Graduated' ? 'primary' : 'secondary') ?> fs-6 px-3 py-2">
                            <?= htmlspecialchars($stu['status'] ?? 'Active') ?>
                        </span>
                        <div class="mt-2">
                            <small class="text-muted">Student ID: <?= htmlspecialchars($stu['student_id'] ?? $stu['student_number'] ?? '-') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="row g-3">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-2"><strong><i class="fas fa-user me-2"></i>Personal Information</strong></div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0 profile-detail-table">
                            <tr><td class="text-muted" style="width:40%">Full Name</td><td><strong><?= htmlspecialchars($stu['full_name'] ?: trim($stu['first_name'] . ' ' . ($stu['other_name'] ?? '') . ' ' . $stu['surname'])) ?></strong></td></tr>
                            <tr><td class="text-muted">First Name</td><td><?= htmlspecialchars($stu['first_name'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Surname</td><td><?= htmlspecialchars($stu['surname'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Other Name</td><td><?= htmlspecialchars($stu['other_name'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Gender</td><td><?= htmlspecialchars($stu['gender'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Date of Birth</td><td><?= !empty($stu['date_of_birth']) ? date('d F Y', strtotime($stu['date_of_birth'])) : '-' ?></td></tr>
                            <tr><td class="text-muted">Nationality</td><td><?= htmlspecialchars($stu['nationality'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Address</td><td><?= nl2br(htmlspecialchars($stu['address'] ?? '-')) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white py-2"><strong><i class="fas fa-graduation-cap me-2"></i>Academic Information</strong></div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0 profile-detail-table">
                            <tr><td class="text-muted" style="width:40%">Program / Course</td><td><strong><?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '-') ?></strong></td></tr>
                            <tr><td class="text-muted">Student Number</td><td><?= htmlspecialchars($stu['student_number'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Registration Number</td><td><?= htmlspecialchars($stu['registration_number'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Index Number</td><td><?= htmlspecialchars($stu['index_number'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">National Student ID</td><td><?= htmlspecialchars($stu['national_student_id_number'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Set / Intake</td><td><?= htmlspecialchars($stu['set_name'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Level / Year</td><td><?= htmlspecialchars($stu['level'] ?? '-') ?> (<?= htmlspecialchars($stu['current_year'] ?? $stu['year'] ?? '-') ?>)</td></tr>
                            <tr><td class="text-muted">Current Semester</td><td><?= htmlspecialchars($stu['current_semester'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Intake Date</td><td><?= !empty($stu['intake_date']) ? date('d F Y', strtotime($stu['intake_date'])) : '-' ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark py-2"><strong><i class="fas fa-address-book me-2"></i>Contact Information</strong></div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0 profile-detail-table">
                            <tr><td class="text-muted" style="width:40%">Phone</td><td><strong><?= htmlspecialchars($stu['phone'] ?? '-') ?></strong></td></tr>
                            <tr><td class="text-muted">Mobile Number</td><td><?= htmlspecialchars($stu['mobile_number'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Email</td><td><?= htmlspecialchars($stu['email'] ?? '-') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Emergency & Guardian -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-danger text-white py-2"><strong><i class="fas fa-shield-alt me-2"></i>Emergency & Guardian</strong></div>
                    <div class="card-body p-3">
                        <table class="table table-sm table-borderless mb-0 profile-detail-table">
                            <tr><td class="text-muted" style="width:40%">Emergency Contact</td><td><strong><?= htmlspecialchars($stu['emergency_contact_name'] ?? '-') ?></strong></td></tr>
                            <tr><td class="text-muted">Emergency Phone</td><td><?= htmlspecialchars($stu['emergency_contact_phone'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Emergency Email</td><td><?= htmlspecialchars($stu['emergency_contact_email'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Guardian Name</td><td><?= htmlspecialchars($stu['guardian_name'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Guardian Phone</td><td><?= htmlspecialchars($stu['guardian_phone'] ?? '-') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white py-2"><strong><i class="fas fa-cogs me-2"></i>System Information</strong></div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-3"><small class="text-muted">Status</small><br><span class="badge bg-<?= ($stu['status'] ?? 'Active') === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($stu['status'] ?? 'Active') ?></span></div>
                            <div class="col-md-3"><small class="text-muted">First Login</small><br><?= $stu['is_first_login'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-success">No</span>' ?></div>
                            <div class="col-md-3"><small class="text-muted">Created</small><br><?= !empty($stu['created_at']) ? date('d M Y H:i', strtotime($stu['created_at'])) : '-' ?></div>
                            <div class="col-md-3"><small class="text-muted">Last Updated</small><br><?= !empty($stu['updated_at']) ? date('d M Y H:i', strtotime($stu['updated_at'])) : '-' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-detail-table td {
            padding: 4px 0 !important;
            font-size: 0.9rem;
        }
        .profile-detail-table td:first-child {
            font-weight: 500;
        }
        @media print {
            .student-full-profile .btn,
            .student-full-profile .d-print-none {
                display: none !important;
            }
            .student-full-profile .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
            .student-full-profile .card-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card-header.bg-primary { background: #1a237e !important; color: white !important; }
            .card-header.bg-success { background: #2e7d32 !important; color: white !important; }
            .card-header.bg-warning { background: #f9a825 !important; color: black !important; }
            .card-header.bg-danger  { background: #c62828 !important; color: white !important; }
            .card-header.bg-secondary { background: #455a64 !important; color: white !important; }
            @page { margin: 12mm; }
        }
    </style>
    <?php
}

endif;
