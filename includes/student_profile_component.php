<?php
/**
 * Professional Student Profile Component
 * Complete with:
 * - Full student details display
 * - Perfect print functionality
 * - No external dependencies
 * - Fallbacks for missing data
 */

if (!function_exists('displayStudentProfileCard')) {
function displayStudentProfileCard($student_id = null, $view_mode = 'compact') {
    // Load connections (fallback if not already loaded)
    if (!function_exists('getStudentsConnection')) {
        require_once __DIR__ . '/../config/database.php';
    }
    
    // If no student ID, return compact placeholder
    if (empty($student_id)) {
        if ($view_mode === 'modal') {
            return '<div class="alert alert-info">Select a student to view their profile</div>';
        }
        return '<div class="card p-3 text-center text-muted small"><i class="fas fa-user-graduate fa-2x mb-2"></i><div>Select Student</div></div>';
    }
    
    $conn = getStudentsConnection();
    $student = null;
    
    // Try to find student by various IDs
    if ($conn) {
        // Build search query for multiple ID types
        $stmt = $conn->prepare("
            SELECT s.* 
            FROM students s 
            WHERE s.student_id = ? 
               OR s.index_number = ? 
               OR s.registration_number = ?
               OR s.national_student_id_number = ?
               OR s.student_number = ?
            LIMIT 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("sssss", $student_id, $student_id, $student_id, $student_id, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $student = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
    
    // Fallback to StudentDataLoader if DB doesn't have it
    if (!$student && class_exists('StudentDataLoader')) {
        $loader = new StudentDataLoader();
        $allStudents = $loader->loadAllStudents();
        
        foreach ($allStudents as $s) {
            if (
                ($s['index_number'] ?? '') == $student_id ||
                ($s['student_number'] ?? '') == $student_id ||
                ($s['national_id'] ?? '') == $student_id ||
                ($s['student_id'] ?? '') == $student_id
            ) {
                $student = $s;
                break;
            }
        }
    }
    
    // Still no student? Show not found
    if (!$student) {
        return '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Student not found (ID: ' . htmlspecialchars($student_id) . ')</div>';
    }
    
    // Normalize student data fields (handle both DB and Excel formats)
    $student = normalizeStudentData($student);
    
    // Build the UI
    ob_start();
    
    if ($view_mode === 'compact') {
        ?>
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="text-center">
                        <div class="rounded-circle bg-primary bg-gradient d-flex align-items-center justify-content-center text-white" style="width:60px;height:60px;font-size:24px;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($student['full_name'] ?? $student['name'] ?? 'Unknown Name') ?></h6>
                        <div class="text-muted small">
                            <i class="fas fa-id-card me-1"></i><?= htmlspecialchars($student['student_id'] ?? $student['index_number'] ?? 'N/A') ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <span class="badge bg-primary"><?= htmlspecialchars($student['program'] ?? $student['course'] ?? 'N/A') ?></span>
                            <span class="badge bg-secondary"><?= htmlspecialchars($student['level'] ?? 'N/A') ?></span>
                            <?php if (isset($student['status']) && $student['status'] !== 'Active'): ?>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($student['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small mt-1 text-muted">
                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($student['phone'] ?? $student['mobile_number'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else if ($view_mode === 'detailed' || $view_mode === 'modal') {
        ?>
        <div class="student-profile-detailed">
            <div class="card border-0 shadow">
                <!-- Header -->
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
                    <div class="row align-items-center g-3">
                        <div class="col-auto text-center">
                            <div class="rounded-circle border-3 border-white bg-white bg-opacity-20 d-flex align-items-center justify-content-center text-white" style="width:100px;height:100px;font-size:42px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h3 class="mb-1 fw-bold"><?= htmlspecialchars($student['full_name'] ?? $student['name'] ?? 'Unknown Name') ?></h3>
                            <p class="mb-0 opacity-90"><?= htmlspecialchars($student['student_id'] ?? $student['index_number'] ?? $student['national_student_id_number'] ?? $student['nsin'] ?? 'N/A') ?></p>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($student['program'] ?? $student['course'] ?? 'N/A') ?></span>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($student['level'] ?? 'N/A') ?></span>
                                <?php if (isset($student['set'])): ?>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($student['set']) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-<?= (isset($student['status']) && $student['status'] === 'Active') ? 'success' : 'warning text-dark' ?>">
                                    <?= ucwords(htmlspecialchars($student['status'] ?? 'Active')) ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($view_mode === 'detailed'): ?>
                            <div class="col-auto">
                                <button class="btn btn-light btn-sm text-primary no-print" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>Print Profile
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Personal Information -->
                        <div class="col-lg-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">First Name</td><td class="fw-semibold"><?= htmlspecialchars($student['first_name'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Middle Name</td><td class="fw-semibold"><?= htmlspecialchars($student['middle_name'] ?? $student['other_name'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Last Name</td><td class="fw-semibold"><?= htmlspecialchars($student['surname'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Date of Birth</td><td class="fw-semibold"><?= formatDate($student['date_of_birth'] ?? $student['dob'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Gender</td><td class="fw-semibold"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Nationality</td><td class="fw-semibold"><?= htmlspecialchars($student['nationality'] ?? 'Uganda') ?></td></tr>
                                        <tr><td class="text-muted">District</td><td class="fw-semibold"><?= htmlspecialchars($student['district'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Address</td><td class="fw-semibold"><?= htmlspecialchars($student['address'] ?? 'N/A') ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="col-lg-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-phone-volume me-2"></i>Contact Information</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">Phone Number</td><td class="fw-semibold"><?= htmlspecialchars($student['phone'] ?? $student['mobile_number'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Email</td><td class="fw-semibold"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Emergency Contact</td><td class="fw-semibold"><?= htmlspecialchars($student['emergency_contact_name'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Emergency Phone</td><td class="fw-semibold"><?= htmlspecialchars($student['emergency_contact_phone'] ?? 'N/A') ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Academic Information -->
                        <div class="col-lg-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">Index Number</td><td class="fw-semibold"><code><?= htmlspecialchars($student['index_number'] ?? $student['registration_number'] ?? $student['student_number'] ?? 'N/A') ?></code></td></tr>
                                        <tr><td class="text-muted">Student ID</td><td class="fw-semibold"><code><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></code></td></tr>
                                        <tr><td class="text-muted">Program</td><td class="fw-semibold"><?= htmlspecialchars($student['program'] ?? $student['course'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Level</td><td class="fw-semibold"><?= htmlspecialchars($student['level'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Set</td><td class="fw-semibold"><?= htmlspecialchars($student['set'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Intake Year</td><td class="fw-semibold"><?= htmlspecialchars($student['intake_year'] ?? $student['year'] ?? 'N/A') ?></td></tr>
                                        <tr><td class="text-muted">Intake Period</td><td class="fw-semibold"><?= htmlspecialchars($student['intake_period'] ?? 'N/A') ?></td></tr>
                                        <?php if (isset($student['enrollment_date'])): ?>
                                            <tr><td class="text-muted">Enrollment Date</td><td class="fw-semibold"><?= formatDate($student['enrollment_date'] ?? 'N/A') ?></td></tr>
                                        <?php endif; ?>
                                        <?php if (isset($student['expected_graduation_date'])): ?>
                                            <tr><td class="text-muted">Expected Graduation</td><td class="fw-semibold"><?= formatDate($student['expected_graduation_date'] ?? 'N/A') ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Other Info -->
                        <div class="col-lg-6">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr><td class="text-muted w-50">NSIN</td><td class="fw-semibold"><code><?= htmlspecialchars($student['national_student_id_number'] ?? $student['nsin'] ?? $student['national_id'] ?? 'N/A') ?></code></td></tr>
                                        <tr><td class="text-muted">Source File</td><td class="fw-semibold"><small class="text-muted"><?= htmlspecialchars($student['source_file'] ?? 'Database') ?></small></td></tr>
                                        <tr><td class="text-muted">Status</td><td class="fw-semibold">
                                            <span class="badge bg-<?= (isset($student['status']) && $student['status'] === 'Active') ? 'success' : 'warning text-dark' ?>">
                                                <?= ucwords(htmlspecialchars($student['status'] ?? 'Active')) ?>
                                            </span>
                                        </td></tr>
                                        <?php if (isset($student['course_codes'])): ?>
                                            <tr><td class="text-muted">Course Codes</td><td class="fw-semibold"><small><?= htmlspecialchars($student['course_codes'] ?? 'N/A') ?></small></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="card-footer bg-light border-0 d-flex flex-wrap justify-content-between gap-2">
                    <div class="no-print">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.close()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print Profile
                        </button>
                    </div>
                    <div class="text-muted small">
                        ISNM Student Profile • Generated: <?= date('d M Y H:i') ?>
                    </div>
                </div>
            </div>
            
            <!-- Print Styles -->
            <style>
            @media print {
                body { background: white; }
                .no-print { display: none !important; }
                .student-profile-detailed .card { box-shadow: none; border: 1px solid #ddd; }
                .student-profile-detailed .card-header { background: #e9ecef !important; color: #212529 !important; }
                .bg-light { background: #f8f9fa !important; }
                .border { border: 1px solid #dee2e6 !important; }
                .badge { border: 1px solid #dee2e6; }
            }
            </style>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
}

// Helper: Normalize student data from various sources
if (!function_exists('normalizeStudentData')) {
function normalizeStudentData($student) {
    $normalized = [];
    
    // Map common field names
    $field_map = [
        'full_name' => ['full_name', 'name', 'student_name'],
        'first_name' => ['first_name', 'firstname', 'first'],
        'middle_name' => ['middle_name', 'other_name', 'middlename', 'middle'],
        'surname' => ['surname', 'last_name', 'lastname', 'last'],
        'student_id' => ['student_id', 'id'],
        'index_number' => ['index_number', 'index_no', 'index'],
        'registration_number' => ['registration_number', 'reg_no', 'reg'],
        'student_number' => ['student_number', 'student_no'],
        'national_id' => ['national_id', 'national_student_id_number', 'nsin'],
        'phone' => ['phone', 'phone_number', 'phone_no', 'mobile_number', 'mobile'],
        'email' => ['email', 'e_mail'],
        'program' => ['program', 'course', 'programme'],
        'level' => ['level', 'award'],
        'set' => ['set', 'class_set', 'intake_set'],
        'intake_year' => ['intake_year', 'year'],
        'intake_period' => ['intake_period', 'semester', 'trial'],
        'date_of_birth' => ['date_of_birth', 'dob'],
        'district' => ['district', 'location'],
        'nationality' => ['nationality', 'country'],
        'address' => ['address', 'physical_address'],
        'gender' => ['gender', 'sex'],
        'status' => ['status', 'student_status'],
        'enrollment_date' => ['enrollment_date', 'registration_date'],
        'source_file' => ['source_file', 'source'],
    ];
    
    foreach ($field_map as $target => $sources) {
        foreach ($sources as $source) {
            if (isset($student[$source]) && trim($student[$source]) !== '') {
                $normalized[$target] = $student[$source];
                break;
            }
        }
        if (!isset($normalized[$target])) {
            $normalized[$target] = null;
        }
    }
    
    // Also keep all original fields
    return array_merge($student, $normalized);
}
}

// Helper: Format dates (with safety check)
if (!function_exists('formatDate')) {
    function formatDate($date_str) {
        if (empty($date_str) || $date_str === 'N/A') return 'N/A';
        $timestamp = strtotime($date_str);
        if (!$timestamp) return htmlspecialchars($date_str);
        return date('d M Y', $timestamp);
    }
}

// Display student search box with results
if (!function_exists('displayStudentSearchBox')) {
function displayStudentSearchBox($placeholder = 'Search students...', $container_id = 'student_search_container') {
    ob_start();
    ?>
    <div class="student-search-container" id="<?= $container_id ?>">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="student_search_input_<?= $container_id ?>" 
                   placeholder="<?= htmlspecialchars($placeholder) ?>"
                   onkeyup="searchStudents_<?= $container_id ?>()">
            <button class="btn btn-outline-secondary" type="button" onclick="clearStudentSearch_<?= $container_id ?>()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="student_search_results_<?= $container_id ?>" class="mt-2"></div>
    </div>
    
    <script>
    // Universal search using StudentDataLoader data embedded in page
    function searchStudents_<?= $container_id ?>() {
        const input = document.getElementById('student_search_input_<?= $container_id ?>');
        const resultsDiv = document.getElementById('student_search_results_<?= $container_id ?>');
        const searchTerm = input.value.toLowerCase().trim();
        
        if (searchTerm.length < 2) {
            resultsDiv.innerHTML = '';
            return;
        }
        
        // Get all students (data should be defined in page context)
        const allStudents = window.allStudents || [];
        const filtered = allStudents.filter(student => {
            const searchableFields = [
                student.full_name || '', student.first_name || '', student.surname || '',
                student.index_number || '', student.student_number || '', student.nsin || '',
                student.national_id || '', student.phone || '', student.email || '',
                student.program || '', student.course || '', student.department || '',
                student.level || '', student.set || '', student.source_file || ''
            ].join(' ').toLowerCase();
            return searchableFields.includes(searchTerm);
        }).slice(0, 20);
        
        if (filtered.length === 0) {
            resultsDiv.innerHTML = '<div class="alert alert-info small"><i class="fas fa-info-circle me-2"></i>No students found</div>';
            return;
        }
        
        let html = '<div class="list-group">';
        filtered.forEach(student => {
            const name = student.full_name || (student.first_name + ' ' + student.surname) || 'Unknown';
            const id = student.index_number || student.student_number || student.national_id || 'N/A';
            const program = student.program || student.course || 'N/A';
            
            html += `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                     onclick="selectStudent_<?= $container_id ?>('${escapeHtml(id)}')">
                    <div>
                        <div class="fw-bold">${escapeHtml(name)}</div>
                        <small class="text-muted">${escapeHtml(id)} • ${escapeHtml(program)}</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            `;
        });
        html += '</div>';
        resultsDiv.innerHTML = html;
    }
    
    function clearStudentSearch_<?= $container_id ?>() {
        const input = document.getElementById('student_search_input_<?= $container_id ?>');
        const resultsDiv = document.getElementById('student_search_results_<?= $container_id ?>');
        input.value = '';
        resultsDiv.innerHTML = '';
    }
    
    function selectStudent_<?= $container_id ?>(studentId) {
        if (typeof showStudentProfileModal === 'function') {
            showStudentProfileModal(studentId);
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
    <?php
    return ob_get_clean();
}
}

// Display student profile modal
if (!function_exists('displayStudentProfileModal')) {
function displayStudentProfileModal($modal_id = 'student_profile_modal') {
    ob_start();
    ?>
    <div class="modal fade" id="<?= $modal_id ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" id="<?= $modal_id ?>_content">
                <div class="modal-body p-4 text-center text-muted">
                    <i class="fas fa-user-graduate fa-2x mb-2"></i>
                    <div>Select a student to view profile</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showStudentProfileModal(studentId) {
        const modal = document.getElementById('<?= $modal_id ?>');
        const contentDiv = document.getElementById('<?= $modal_id ?>_content');

        contentDiv.innerHTML = '<div class="modal-body p-4 text-center"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><div>Loading student profile...</div></div>';

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        if (window.allStudents && studentId) {
            const student = window.allStudents.find(s => 
                String(s.index_number || '').toLowerCase() === String(studentId).toLowerCase() || 
                String(s.student_number || '').toLowerCase() === String(studentId).toLowerCase() || 
                String(s.national_id || '').toLowerCase() === String(studentId).toLowerCase() ||
                String(s.student_id || '').toLowerCase() === String(studentId).toLowerCase()
            );

            if (student) {
                renderStudentProfile(student, contentDiv, bsModal);
                return;
            }
        }

        contentDiv.innerHTML = '<div class="modal-body p-4"><div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Student profile not found (ID: ' + escapeHtml(studentId) + ')</div></div>';
    }

    function renderStudentProfile(student, container, modal) {
        const name = student.full_name || (student.first_name + ' ' + student.surname) || 'Unknown';
        const id = student.index_number || student.student_number || student.national_id || student.student_id || 'N/A';
        const program = student.program || student.course || 'N/A';
        const level = student.level || 'N/A';
        const set = student.set || 'N/A';
        const status = student.status || 'Active';
        const statusBadge = status.toLowerCase() === 'active' ? 'bg-success' : 'bg-warning text-dark';
        
        const html = `
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #0f766e, #14b8a6)">
                <h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-user me-2"></i>Personal Information</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted w-50">First Name</td><td class="fw-semibold">${escapeHtml(student.first_name || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Middle Name</td><td class="fw-semibold">${escapeHtml(student.middle_name || student.other_name || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Last Name</td><td class="fw-semibold">${escapeHtml(student.surname || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Date of Birth</td><td class="fw-semibold">${escapeHtml(student.date_of_birth || student.dob || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Gender</td><td class="fw-semibold">${escapeHtml(student.gender || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Nationality</td><td class="fw-semibold">${escapeHtml(student.nationality || 'Uganda')}</td></tr>
                                <tr><td class="text-muted">District</td><td class="fw-semibold">${escapeHtml(student.district || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Address</td><td class="fw-semibold">${escapeHtml(student.address || 'N/A')}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-phone-volume me-2"></i>Contact Information</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted w-50">Phone Number</td><td class="fw-semibold">${escapeHtml(student.phone || student.mobile_number || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Email</td><td class="fw-semibold">${escapeHtml(student.email || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Emergency Contact</td><td class="fw-semibold">${escapeHtml(student.emergency_contact_name || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Emergency Phone</td><td class="fw-semibold">${escapeHtml(student.emergency_contact_phone || 'N/A')}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted w-50">Index Number</td><td class="fw-semibold"><code>${escapeHtml(student.index_number || student.registration_number || student.student_number || 'N/A')}</code></td></tr>
                                <tr><td class="text-muted">Student ID</td><td class="fw-semibold"><code>${escapeHtml(student.student_id || 'N/A')}</code></td></tr>
                                <tr><td class="text-muted">Program</td><td class="fw-semibold">${escapeHtml(program)}</td></tr>
                                <tr><td class="text-muted">Level</td><td class="fw-semibold">${escapeHtml(level)}</td></tr>
                                <tr><td class="text-muted">Set</td><td class="fw-semibold">${escapeHtml(set)}</td></tr>
                                <tr><td class="text-muted">Intake Year</td><td class="fw-semibold">${escapeHtml(student.intake_year || student.year || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Intake Period</td><td class="fw-semibold">${escapeHtml(student.intake_period || 'N/A')}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3 text-primary fw-bold"><i class="fas fa-info-circle me-2"></i>Additional Information</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td class="text-muted w-50">NSIN</td><td class="fw-semibold"><code>${escapeHtml(student.national_student_id_number || student.nsin || student.national_id || 'N/A')}</code></td></tr>
                                <tr><td class="text-muted">Source File</td><td class="fw-semibold"><small class="text-muted">${escapeHtml(student.source_file || 'Database')}</small></td></tr>
                                <tr><td class="text-muted">Status</td><td class="fw-semibold"><span class="badge ${statusBadge}">${escapeHtml(status)}</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 d-flex flex-wrap justify-content-between gap-2">
                <div class="no-print">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print Profile
                    </button>
                </div>
                <div class="text-muted small">
                    ISNM Student Profile • Generated: ${new Date().toLocaleString()}
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
    <?php
    return ob_get_clean();
}
}
?>
