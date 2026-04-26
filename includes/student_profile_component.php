<?php
// Universal Student Profile Component
// This component can be included in any dashboard to display student profiles consistently

function displayStudentProfileCard($student_id, $view_mode = 'compact') {
    global $conn;
    
    // Get student data
    $student_sql = "SELECT * FROM students WHERE student_id = ?";
    $student_result = executeQuery($student_sql, [$student_id], 's');
    $student = $student_result[0] ?? null;
    
    if (!$student) {
        return '<div class="alert alert-warning">Student not found</div>';
    }
    
    // Get academic records
    $academic_sql = "SELECT * FROM academic_records WHERE student_id = ? ORDER BY academic_year DESC, semester DESC LIMIT 3";
    $academic_records = executeQuery($academic_sql, [$student_id], 's');
    
    // Get fee information
    $fee_sql = "SELECT * FROM student_fee_accounts WHERE student_id = ? ORDER BY academic_year DESC LIMIT 1";
    $fee_info = executeQuery($fee_sql, [$student_id], 's');
    $current_fees = $fee_info[0] ?? null;
    
    // Calculate age
    $age = calculateAge($student['date_of_birth']);
    
    // Get profile photo URL
    $photo_url = getPassportPhotoUrl($student['profile_image']);
    
    ob_start(); // Start output buffering
    
    if ($view_mode === 'compact') {
        // Compact card view for lists and grids
        ?>
        <div class="student-profile-card compact" data-student-id="<?php echo $student_id; ?>">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start">
                        <div class="profile-avatar me-3">
                            <img src="<?php echo $photo_url; ?>" alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?>" 
                                 class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #1a237e;">
                        </div>
                        <div class="profile-info flex-grow-1">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?></h6>
                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($student['student_id']); ?></p>
                            <div class="badge-container mb-2">
                                <span class="badge bg-primary me-1"><?php echo htmlspecialchars($student['program']); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($student['level']); ?></span>
                                <?php if ($student['status'] !== 'active'): ?>
                                <span class="badge bg-warning"><?php echo ucfirst($student['status']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="contact-info small text-muted">
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($student['phone']); ?>
                                <i class="fas fa-envelope ms-2 me-1"></i> <?php echo htmlspecialchars($student['email']); ?>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="viewFullProfile('<?php echo $student_id; ?>')">
                                        <i class="fas fa-eye me-2"></i>View Full Profile
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editStudent('<?php echo $student_id; ?>')">
                                        <i class="fas fa-edit me-2"></i>Edit Student
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewAcademic('<?php echo $student_id; ?>')">
                                        <i class="fas fa-graduation-cap me-2"></i>Academic Records
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewFees('<?php echo $student_id; ?>')">
                                        <i class="fas fa-money-bill me-2"></i>Fee Information
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="sendMessage('<?php echo $student_id; ?>')">
                                        <i class="fas fa-envelope me-2"></i>Send Message
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } elseif ($view_mode === 'detailed') {
        // Detailed view for individual student pages
        ?>
        <div class="student-profile-detailed" data-student-id="<?php echo $student_id; ?>">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #1a237e, #3949ab);">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <img src="<?php echo $photo_url; ?>" alt="Profile Photo" 
                                 class="rounded-circle border-3 border-white" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                        <div class="col-md-10">
                            <h3 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['surname']); ?></h3>
                            <p class="mb-2 opacity-90"><?php echo htmlspecialchars($student['student_id']); ?></p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($student['program']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($student['level']); ?></span>
                                <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Personal Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td><?php echo formatDate($student['date_of_birth']); ?> (<?php echo $age; ?> years)</td>
                                </tr>
                                <tr>
                                    <td><strong>Gender:</strong></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Nationality:</strong></td>
                                    <td><?php echo htmlspecialchars($student['nationality']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><a href="tel:<?php echo htmlspecialchars($student['phone']); ?>"><?php echo htmlspecialchars($student['phone']); ?></a></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($student['email']); ?>"><?php echo htmlspecialchars($student['email']); ?></a></td>
                                </tr>
                                <tr>
                                    <td><strong>Emergency Contact:</strong></td>
                                    <td>
                                        <?php if ($student['emergency_contact_name']): ?>
                                            <?php echo htmlspecialchars($student['emergency_contact_name']); ?> - 
                                            <a href="tel:<?php echo htmlspecialchars($student['emergency_contact_phone']); ?>">
                                                <?php echo htmlspecialchars($student['emergency_contact_phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            Not provided
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Program:</strong></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Level:</strong></td>
                                    <td><?php echo htmlspecialchars($student['level']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Intake Year:</strong></td>
                                    <td><?php echo htmlspecialchars($student['intake_year']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Intake Period:</strong></td>
                                    <td><?php echo htmlspecialchars($student['intake_period'] ?? 'Not specified'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Current Year:</strong></td>
                                    <td>Year <?php echo htmlspecialchars($student['current_year'] ?? '1'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Current Semester:</strong></td>
                                    <td>Semester <?php echo htmlspecialchars($student['current_semester'] ?? '1'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Enrollment Date:</strong></td>
                                    <td><?php echo formatDate($student['enrollment_date']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Expected Graduation:</strong></td>
                                    <td><?php echo formatDate($student['expected_graduation_date'] ?? 'Not set'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($current_fees): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3"><i class="fas fa-money-bill me-2"></i>Current Fee Status</h5>
                            <div class="alert alert-<?php echo $current_fees['balance'] > 0 ? 'warning' : 'success'; ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Total Fees:</strong><br>
                                        <?php echo formatCurrency($current_fees['total_fees']); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Amount Paid:</strong><br>
                                        <?php echo formatCurrency($current_fees['amount_paid']); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Balance:</strong><br>
                                        <?php echo formatCurrency($current_fees['balance']); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-<?php echo $current_fees['status'] === 'fully_paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $current_fees['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($academic_records)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3"><i class="fas fa-chart-line me-2"></i>Recent Academic Performance</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Semester</th>
                                            <th>GPA</th>
                                            <th>Position</th>
                                            <th>Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($academic_records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($record['semester']); ?></td>
                                            <td>
                                                <?php if ($record['gpa']): ?>
                                                    <span class="badge bg-success"><?php echo number_format($record['gpa'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['class_position']): ?>
                                                    <?php echo htmlspecialchars($record['class_position']); ?>/<?php echo htmlspecialchars($record['total_students']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['attendance_percentage']): ?>
                                                    <?php echo number_format($record['attendance_percentage'], 1); ?>%
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-primary" onclick="editStudent('<?php echo $student_id; ?>')">
                                <i class="fas fa-edit me-2"></i>Edit Student
                            </button>
                            <button class="btn btn-info" onclick="viewAcademic('<?php echo $student_id; ?>')">
                                <i class="fas fa-graduation-cap me-2"></i>Academic Records
                            </button>
                            <button class="btn btn-success" onclick="viewFees('<?php echo $student_id; ?>')">
                                <i class="fas fa-money-bill me-2"></i>Fee Details
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary" onclick="sendMessage('<?php echo $student_id; ?>')">
                                <i class="fas fa-envelope me-2"></i>Send Message
                            </button>
                            <button class="btn btn-outline-primary" onclick="printProfile('<?php echo $student_id; ?>')">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } elseif ($view_mode === 'minimal') {
        // Minimal view for dropdowns and quick references
        ?>
        <div class="student-profile-minimal d-flex align-items-center p-2 border-bottom" data-student-id="<?php echo $student_id; ?>">
            <img src="<?php echo $photo_url; ?>" alt="Profile" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
            <div class="flex-grow-1">
                <div class="fw-bold"><?php echo htmlspecialchars($student['surname'] . ', ' . $student['first_name']); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
            </div>
            <div class="text-end">
                <span class="badge bg-primary small"><?php echo htmlspecialchars($student['program']); ?></span>
            </div>
        </div>
        <?php
    }
    
    return ob_get_clean();
}

function displayStudentSearchBox($placeholder = 'Search students...', $container_id = 'studentSearchResults') {
    ob_start();
    ?>
    <div class="student-search-container">
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" class="form-control" id="studentSearchInput" placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                   onkeyup="searchStudents('<?php echo $container_id; ?>')">
            <button class="btn btn-outline-secondary" type="button" onclick="clearStudentSearch('<?php echo $container_id; ?>')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="<?php echo $container_id; ?>" class="student-search-results"></div>
    </div>
    
    <script>
    function searchStudents(containerId) {
        const searchTerm = document.getElementById('studentSearchInput').value;
        const container = document.getElementById(containerId);
        
        if (searchTerm.length < 2) {
            container.innerHTML = '';
            return;
        }
        
        // Show loading
        container.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        
        // Make AJAX request
        fetch('includes/ajax_student_search.php?term=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchResults(data.students, containerId);
                } else {
                    container.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Search failed. Please try again.</div>';
                console.error('Search error:', error);
            });
    }
    
    function displaySearchResults(students, containerId) {
        const container = document.getElementById(containerId);
        
        if (students.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No students found</div>';
            return;
        }
        
        let html = '<div class="list-group">';
        students.forEach(student => {
            html += `
                <div class="list-group-item list-group-item-action" onclick="selectStudent('${student.student_id}', '${student.first_name} ${student.surname}')">
                    <div class="d-flex align-items-center">
                        <img src="${student.photo_url}" alt="${student.first_name} ${student.surname}" 
                             class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="fw-bold">${student.surname}, ${student.first_name}</div>
                            <small class="text-muted">${student.student_id} - ${student.program}</small>
                        </div>
                        <div>
                            <span class="badge bg-primary">${student.level}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    function clearStudentSearch(containerId) {
        document.getElementById('studentSearchInput').value = '';
        document.getElementById(containerId).innerHTML = '';
    }
    
    function selectStudent(studentId, studentName) {
        // This function can be overridden by the including page
        console.log('Selected student:', studentId, studentName);
    }
    </script>
    <?php
    return ob_get_clean();
}

function displayStudentProfileModal($student_id) {
    $profile_html = displayStudentProfileCard($student_id, 'detailed');
    
    ob_start();
    ?>
    <div class="modal fade" id="studentProfileModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #1a237e, #3949ab);">
                    <h5 class="modal-title">
                        <i class="fas fa-user-graduate me-2"></i>Student Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <?php echo $profile_html; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="editStudent('<?php echo $student_id; ?>')">
                        <i class="fas fa-edit me-2"></i>Edit Student
                    </button>
                    <button type="button" class="btn btn-info" onclick="printProfile('<?php echo $student_id; ?>')">
                        <i class="fas fa-print me-2"></i>Print Profile
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showStudentProfileModal(studentId) {
        // Load the profile content dynamically
        fetch('includes/ajax_student_profile.php?student_id=' + studentId)
            .then(response => response.text())
            .then(html => {
                document.querySelector('#studentProfileModal .modal-body').innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error loading profile:', error);
                alert('Error loading student profile');
            });
    }
    
    function editStudent(studentId) {
        // Close modal and redirect to edit page
        bootstrap.Modal.getInstance(document.getElementById('studentProfileModal')).hide();
        window.location.href = 'student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        bootstrap.Modal.getInstance(document.getElementById('studentProfileModal')).hide();
        window.location.href = 'academic_records.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        bootstrap.Modal.getInstance(document.getElementById('studentProfileModal')).hide();
        window.location.href = 'fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        // Implement message functionality
        alert('Message functionality would be implemented here');
    }
    
    function printProfile(studentId) {
        window.print();
    }
    </script>
    <?php
    return ob_get_clean();
}

// CSS styles for the profile components
function getStudentProfileStyles() {
    return '
    <style>
    .student-profile-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .student-profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .profile-avatar img {
        transition: transform 0.3s ease;
    }
    
    .profile-avatar img:hover {
        transform: scale(1.1);
    }
    
    .badge-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .student-search-results {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        margin-top: 0.5rem;
    }
    
    .student-search-results .list-group-item {
        border-left: none;
        border-right: none;
        border-radius: 0;
    }
    
    .student-search-results .list-group-item:first-child {
        border-top: none;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    
    .student-search-results .list-group-item:last-child {
        border-bottom: none;
        border-radius: 0 0 0.375rem 0.375rem;
    }
    
    .student-profile-minimal {
        transition: background-color 0.3s ease;
    }
    
    .student-profile-minimal:hover {
        background-color: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .student-profile-card .contact-info {
            font-size: 0.75rem;
        }
        
        .badge-container {
            margin-bottom: 0.5rem;
        }
    }
    </style>';
    }
?>
