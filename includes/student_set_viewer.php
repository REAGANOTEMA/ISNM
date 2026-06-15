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
    $title           = $options['title'] ?? 'Student Records by Set';
    $icon            = $options['icon']  ?? 'fa-users';
    $showAllOption   = $options['show_all'] ?? false;
    $isSuperAdmin    = $options['super_admin'] ?? false;

    // Fetch distinct filter options
    $sets     = [];
    $programs = [];
    $levels   = [];
    $students = [];
    $totalFiltered = 0;

    if ($conn) {
        try {
            $r = $conn->query("SELECT DISTINCT set_name FROM students WHERE set_name IS NOT NULL AND set_name != '' ORDER BY set_name DESC");
            if ($r) while ($row = $r->fetch_assoc()) $sets[] = $row['set_name'];

            $r = $conn->query("SELECT DISTINCT program FROM students WHERE program IS NOT NULL AND program != '' ORDER BY program");
            if ($r) while ($row = $r->fetch_assoc()) $programs[] = $row['program'];

            $r = $conn->query("SELECT DISTINCT level FROM students WHERE level IS NOT NULL AND level != '' ORDER BY level");
            if ($r) while ($row = $r->fetch_assoc()) $levels[] = $row['level'];
        } catch (Exception $e) {}
    }

    // Build query
    $where = ['1=1'];
    $params = [];
    if ($selectedSet) {
        $where[] = 'set_name = ?';
        $params[] = $selectedSet;
    }
    if ($selectedProgram) {
        $where[] = 'program = ?';
        $params[] = $selectedProgram;
    }
    if ($selectedLevel) {
        $where[] = 'level = ?';
        $params[] = $selectedLevel;
    }

    $sql = "SELECT * FROM students WHERE " . implode(' AND ', $where) . " ORDER BY set_name DESC, program, level, surname, first_name";

    if ($conn && $params) {
        try {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) $students[] = $row;
                }
                $totalFiltered = count($students);
                $stmt->close();
            }
        } catch (Exception $e) {}
    } elseif ($conn && empty($params) && $showAllOption) {
        // Only if explicitly allowed (super admin mode)
        try {
            $r = $conn->query("SELECT * FROM students ORDER BY set_name DESC, program, level, surname, first_name LIMIT 200");
            if ($r) {
                while ($row = $r->fetch_assoc()) $students[] = $row;
                $totalFiltered = $r->num_rows;
            }
        } catch (Exception $e) {}
    }

    // Check if a specific student is requested for profile view
    $viewStudentId = $_GET['view_student'] ?? '';
    $viewStudent = null;
    if ($viewStudentId && $conn) {
        try {
            $sid = $conn->real_escape_string($viewStudentId);
            $r = $conn->query("SELECT * FROM students WHERE student_id = '$sid' OR index_number = '$sid' OR id = " . intval($sid) . " LIMIT 1");
            if ($r) $viewStudent = $r->fetch_assoc();
        } catch (Exception $e) {}
    }

    // ─── Print support ──────────────────────────────────────────
    $isPrint = isset($_GET['print']) && $_GET['print'] === 'set';
    ?>
    <div class="student-set-viewer">
        <?php if ($viewStudent): ?>
            <!-- ── SINGLE STUDENT FULL PROFILE ── -->
            <?php renderFullStudentProfile($viewStudent, $conn); ?>
        <?php else: ?>

        <!-- ── HEADER ── -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0" style="color:#0f172a">
                <i class="fas <?= $icon ?> me-2" style="color:var(--isnm-blue, #1e3a8a)"></i><?= $title ?>
                <?php if ($students): ?>
                    <span class="badge bg-primary ms-2 fs-6"><?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?></span>
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
            <?php if (count($_GET) > 0): ?>
                <?php foreach ($_GET as $gk => $gv): ?>
                    <?php if (!in_array($gk, ['set_name','program','level','print','view_student'])): ?>
                        <input type="hidden" name="<?= htmlspecialchars($gk) ?>" value="<?= htmlspecialchars($gv) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="col-md-4">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-layer-group me-1"></i>Set / Intake</label>
                <select name="set_name" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All Sets —</option>
                    <?php foreach ($sets as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $selectedSet === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-graduation-cap me-1"></i>Program</label>
                <select name="program" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All Programs —</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selectedProgram === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-white small fw-semibold mb-1"><i class="fas fa-layer-group me-1"></i>Level</label>
                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All —</option>
                    <?php foreach ($levels as $l): ?>
                        <option value="<?= htmlspecialchars($l) ?>" <?= $selectedLevel === $l ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-light btn-sm flex-grow-1"><i class="fas fa-search me-1"></i> Filter</button>
                <?php if ($selectedSet || $selectedProgram || $selectedLevel): ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, array_flip(['set_name','program','level']))) ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($isPrint): ?>
        <!-- ── PRINT HEADER ── -->
        <div class="print-header d-none d-print-block text-center mb-4">
            <h2 style="color:#1a237e;">IGANGA SCHOOL OF NURSING & MIDWIFERY</h2>
            <h4>Student Records – <?= htmlspecialchars($selectedSet ?: 'All Sets') ?></h4>
            <p class="text-muted">Generated: <?= date('l, F j, Y') ?></p>
            <hr>
        </div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users" style="font-size:3rem;color:#cbd5e1"></i>
                <p class="text-muted mt-3 fs-5">Select a Set above to view students</p>
            </div>
        <?php else: ?>
            <!-- ── STUDENT CARDS ── -->
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
                            <!-- Photo column -->
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

                            <!-- Info columns -->
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
                                            <?php if ($isSuperAdmin || true): ?>
                                                <div class="mt-1">
                                                    <a href="?<?= http_build_query(array_merge($_GET, ['view_student' => $stu['student_id'] ?? $stu['student_number'] ?? $stu['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> Full Profile</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-2">
                                        <!-- Personal Info -->
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
                                        <!-- Academic Info -->
                                        <div class="col-md-4">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-graduation-cap me-1 text-success"></i>Academic</div>
                                                <div class="info-value">
                                                    <div><?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '—') ?></div>
                                                    <?php if (!empty($stu['level'])): ?><small>Level: <?= htmlspecialchars($stu['level']) ?></small><?php endif; ?>
                                                    <?php if (!empty($stu['current_semester'])): ?><small> | <?= htmlspecialchars($stu['current_semester']) ?></small><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Contact Info -->
                                        <div class="col-md-4">
                                            <div class="info-block">
                                                <div class="info-label"><i class="fas fa-phone me-1 text-warning"></i>Contact</div>
                                                <div class="info-value">
                                                    <?php if (!empty($stu['phone'])): ?><div><i class="fas fa-phone-alt me-1 small"></i><?= htmlspecialchars($stu['phone']) ?></div><?php endif; ?>
                                                    <?php if (!empty($stu['email'])): ?><div><i class="fas fa-envelope me-1 small"></i><?= htmlspecialchars($stu['email']) ?></div><?php endif; ?>
                                                    <?php if (!empty($stu['address'])): ?><div><small><?= htmlspecialchars(substr($stu['address'], 0, 50)) ?></small></div><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Emergency / Guardian -->
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
                                            <i class="fas fa-calendar-alt me-1"></i>Created: <?= !empty($stu['created_at']) ? date('d M Y', strtotime($stu['created_at'])) : '—' ?>
                                            <?php if (!empty($stu['intake_date'])): ?> | Intake: <?= date('d M Y', strtotime($stu['intake_date'])) ?><?php endif; ?>
                                        </small>
                                        <div class="d-flex gap-1">
                                            <a href="?<?= http_build_query(array_merge($_GET, ['view_student' => $stu['student_id'] ?? $stu['student_number'] ?? $stu['id']])) ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-file-alt"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ── PRINT SUMMARY ── -->
            <div class="mt-4 p-3 bg-light rounded d-print-none">
                <div class="row g-2 text-center">
                    <div class="col-md-3">
                        <div class="fw-bold fs-5"><?= count($students) ?></div>
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
            .badge {
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
    </script>
    <?php
}

/**
 * Render a single student's full profile (Word-format detail view)
 */
function renderFullStudentProfile($stu, $conn) {
    ?>
    <div class="student-full-profile">
        <!-- Back button -->
        <div class="mb-3">
            <a href="?" class="btn btn-outline-secondary btn-sm d-print-none"><i class="fas fa-arrow-left me-1"></i> Back to Student List</a>
            <button class="btn btn-outline-success btn-sm d-print-none ms-2" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Profile</button>
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
                        </div>
                        <p class="mb-1"><strong>Program:</strong> <?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '—') ?></p>
                        <p class="mb-1">
                            <strong>Set:</strong> <?= htmlspecialchars($stu['set_name'] ?? '—') ?> &nbsp;|&nbsp;
                            <strong>Level:</strong> <?= htmlspecialchars($stu['level'] ?? '—') ?> &nbsp;|&nbsp;
                            <strong>Year:</strong> <?= htmlspecialchars($stu['current_year'] ?? $stu['year'] ?? '—') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-<?= ($stu['status'] ?? 'Active') === 'Active' ? 'success' : (($stu['status'] ?? '') === 'Graduated' ? 'primary' : 'secondary') ?> fs-6 px-3 py-2">
                            <?= htmlspecialchars($stu['status'] ?? 'Active') ?>
                        </span>
                        <div class="mt-2">
                            <small class="text-muted">Student ID: <?= htmlspecialchars($stu['student_id'] ?? $stu['student_number'] ?? '—') ?></small>
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
                            <tr><td class="text-muted">First Name</td><td><?= htmlspecialchars($stu['first_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Surname</td><td><?= htmlspecialchars($stu['surname'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Other Name</td><td><?= htmlspecialchars($stu['other_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Gender</td><td><?= htmlspecialchars($stu['gender'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Date of Birth</td><td><?= !empty($stu['date_of_birth']) ? date('d F Y', strtotime($stu['date_of_birth'])) : '—' ?></td></tr>
                            <tr><td class="text-muted">Nationality</td><td><?= htmlspecialchars($stu['nationality'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Address</td><td><?= nl2br(htmlspecialchars($stu['address'] ?? '—')) ?></td></tr>
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
                            <tr><td class="text-muted" style="width:40%">Program / Course</td><td><strong><?= htmlspecialchars($stu['program'] ?: $stu['course'] ?: '—') ?></strong></td></tr>
                            <tr><td class="text-muted">Student Number</td><td><?= htmlspecialchars($stu['student_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Registration Number</td><td><?= htmlspecialchars($stu['registration_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Index Number</td><td><?= htmlspecialchars($stu['index_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">National Student ID</td><td><?= htmlspecialchars($stu['national_student_id_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Set / Intake</td><td><?= htmlspecialchars($stu['set_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Level / Year</td><td><?= htmlspecialchars($stu['level'] ?? '—') ?> (<?= htmlspecialchars($stu['current_year'] ?? $stu['year'] ?? '—') ?>)</td></tr>
                            <tr><td class="text-muted">Current Semester</td><td><?= htmlspecialchars($stu['current_semester'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Intake Date</td><td><?= !empty($stu['intake_date']) ? date('d F Y', strtotime($stu['intake_date'])) : '—' ?></td></tr>
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
                            <tr><td class="text-muted" style="width:40%">Phone</td><td><strong><?= htmlspecialchars($stu['phone'] ?? '—') ?></strong></td></tr>
                            <tr><td class="text-muted">Mobile Number</td><td><?= htmlspecialchars($stu['mobile_number'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Email</td><td><?= htmlspecialchars($stu['email'] ?? '—') ?></td></tr>
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
                            <tr><td class="text-muted" style="width:40%">Emergency Contact</td><td><strong><?= htmlspecialchars($stu['emergency_contact_name'] ?? '—') ?></strong></td></tr>
                            <tr><td class="text-muted">Emergency Phone</td><td><?= htmlspecialchars($stu['emergency_contact_phone'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Emergency Email</td><td><?= htmlspecialchars($stu['emergency_contact_email'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Guardian Name</td><td><?= htmlspecialchars($stu['guardian_name'] ?? '—') ?></td></tr>
                            <tr><td class="text-muted">Guardian Phone</td><td><?= htmlspecialchars($stu['guardian_phone'] ?? '—') ?></td></tr>
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
                            <div class="col-md-3"><small class="text-muted">Created</small><br><?= !empty($stu['created_at']) ? date('d M Y H:i', strtotime($stu['created_at'])) : '—' ?></div>
                            <div class="col-md-3"><small class="text-muted">Last Updated</small><br><?= !empty($stu['updated_at']) ? date('d M Y H:i', strtotime($stu['updated_at'])) : '—' ?></div>
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
