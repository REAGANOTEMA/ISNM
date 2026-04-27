<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/photo_upload.php';
include_once '../includes/student_profile_component.php';

// Check if user is logged in and has Academic Registrar role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Academic Registrar') {
    header('Location: ../staff-login.php');
    exit();
}

// Database connection is already established in config.php
global $conn;

// Get user information
$username = $_SESSION['username'] ?? $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$user_id = $user['id'] ?? 0;

// Get registrar statistics (using fallback data only)
$total_applications = 12; // Fallback value
$registered_students = 150; // Fallback value
$pending_registrations = 8; // Fallback value
$total_courses = 15; // Fallback value
$active_programs = 2; // Fallback value
$graduates_this_year = 25; // Fallback value

// Get recent students for profile display (using fallback data)
$recent_students = [
    ['first_name' => 'Alice', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'Bob', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active'],
    ['first_name' => 'Carol', 'surname' => 'Student', 'program' => 'Nursing', 'status' => 'active'],
    ['first_name' => 'David', 'surname' => 'Student', 'program' => 'Midwifery', 'status' => 'active']
];

// Get activity logs (using fallback data)
$recent_activities = [
    ['activity' => 'New student application received', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['activity' => 'Student registration processed', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['activity' => 'Academic records updated', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['activity' => 'Graduation certificates issued', 'created_at' => date('Y-m-d H:i:s', strtotime('-7 hours'))]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Registrar Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="dashboard-style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo">
                <h4>Academic Registrar Dashboard</h4>
                <p><?php echo ($user['first_name'] ?? 'User') . ' ' . ($user['surname'] ?? $user['last_name'] ?? ''); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#overview">
                            <i class="fas fa-tachometer-alt"></i> Registration Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#applications">
                            <i class="fas fa-file-alt"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registration">
                            <i class="fas fa-user-plus"></i> Student Registration
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#records">
                            <i class="fas fa-folder"></i> Student Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#transcripts">
                            <i class="fas fa-graduation-cap"></i> Transcripts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#certificates">
                            <i class="fas fa-certificate"></i> Certificates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <h1>Academic Registrar Dashboard</h1>
                    <p>Student Records & Registration Management</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span id="currentDate"></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $user['first_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Registration Overview -->
                <section id="overview" class="content-section">
                    <h2>Registration Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $total_applications; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $registered_students; ?></h3>
                                <p>Registered Students</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $pending_registrations; ?></h3>
                                <p>Pending Registrations</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <h3><?php echo $transcripts_issued; ?></h3>
                                <p>Transcripts Issued (This Month)</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Student Profiles -->
                <section id="student-profiles" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Recent Student Profiles</h2>
                        <div>
                            <button class="btn btn-primary" onclick="openModal('viewAllStudents')">
                                <i class="fas fa-users"></i> View All Students
                            </button>
                            <button class="btn btn-success" onclick="openModal('addStudent')">
                                <i class="fas fa-user-plus"></i> Add New Student
                            </button>
                        </div>
                    </div>
                    
                    <!-- Student Search -->
                    <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'registrarSearchResults'); ?>
                    
                    <!-- Student Profile Cards -->
                    <div class="row mt-4">
                        <?php foreach ($recent_students as $student): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <?php echo displayStudentProfileCard($student['student_id'], 'compact'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Applications -->
                <section id="applications" class="content-section">
                    <h2>Application Management</h2>
                    <div class="application-actions">
                        <button class="btn btn-primary" onclick="openModal('reviewApplication')">
                            <i class="fas fa-eye"></i> Review Applications
                        </button>
                        <button class="btn btn-success" onclick="openModal('approveApplication')">
                            <i class="fas fa-check"></i> Approve Applications
                        </button>
                        <button class="btn btn-info" onclick="openModal('rejectApplication')">
                            <i class="fas fa-times"></i> Reject Applications
                        </button>
                        <button class="btn btn-warning" onclick="openModal('interviewSchedule')">
                            <i class="fas fa-calendar"></i> Schedule Interviews
                        </button>
                    </div>
                    
                    <div class="applications-table">
                        <h3>Recent Applications</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Application ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>APP-2026-001</td>
                                        <td>John Doe</td>
                                        <td>Certificate Nursing</td>
                                        <td>Apr 20, 2026</td>
                                        <td><span class="status-badge pending">Pending Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-002</td>
                                        <td>Jane Smith</td>
                                        <td>Certificate Midwifery</td>
                                        <td>Apr 19, 2026</td>
                                        <td><span class="status-badge in-progress">Under Review</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>APP-2026-003</td>
                                        <td>Michael Johnson</td>
                                        <td>Diploma Nursing</td>
                                        <td>Apr 18, 2026</td>
                                        <td><span class="status-badge approved">Approved</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                            <button class="btn btn-sm btn-outline-info">Register</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Student Registration -->
                <section id="registration" class="content-section">
                    <h2>Student Registration</h2>
                    <div class="registration-actions">
                        <button class="btn btn-primary" onclick="openModal('newRegistration')">
                            <i class="fas fa-user-plus"></i> New Registration
                        </button>
                        <button class="btn btn-success" onclick="openModal('bulkRegistration')">
                            <i class="fas fa-users"></i> Bulk Registration
                        </button>
                        <button class="btn btn-info" onclick="openModal('registrationReport')">
                            <i class="fas fa-chart-bar"></i> Registration Report
                        </button>
                        <button class="btn btn-warning" onclick="openModal('registrationAudit')">
                            <i class="fas fa-audit"></i> Registration Audit
                        </button>
                    </div>
                    
                    <div class="registration-overview">
                        <h3>Registration Statistics by Program</h3>
                        <div class="registration-stats">
                            <div class="stat-card">
                                <h4>Certificate Nursing</h4>
                                <div class="stat-number">120</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Certificate Midwifery</h4>
                                <div class="stat-number">95</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Nursing</h4>
                                <div class="stat-number">60</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                            <div class="stat-card">
                                <h4>Diploma Midwifery</h4>
                                <div class="stat-number">40</div>
                                <div class="stat-detail">Registered Students</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Student Records -->
                <section id="records" class="content-section">
                    <h2>Student Records Management</h2>
                    <div class="records-actions">
                        <button class="btn btn-primary" onclick="openModal('searchStudent')">
                            <i class="fas fa-search"></i> Search Student
                        </button>
                        <button class="btn btn-success" onclick="openModal('updateRecord')">
                            <i class="fas fa-edit"></i> Update Record
                        </button>
                        <button class="btn btn-info" onclick="openModal('transferStudent')">
                            <i class="fas fa-exchange-alt"></i> Transfer Student
                        </button>
                        <button class="btn btn-warning" onclick="openModal('deactivateStudent')">
                            <i class="fas fa-user-times"></i> Deactivate Student
                        </button>
                    </div>
                    
                    <div class="records-search">
                        <h3>Quick Student Search</h3>
                        <div class="search-form">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter Student ID, Name, or Email">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Transcripts -->
                <section id="transcripts" class="content-section">
                    <h2>Academic Transcripts</h2>
                    <div class="transcript-actions">
                        <button class="btn btn-primary" onclick="openModal('generateTranscript')">
                            <i class="fas fa-file-alt"></i> Generate Transcript
                        </button>
                        <button class="btn btn-success" onclick="openModal('verifyTranscript')">
                            <i class="fas fa-check-circle"></i> Verify Transcript
                        </button>
                        <button class="btn btn-info" onclick="openModal('transcriptTemplate')">
                            <i class="fas fa-file-code"></i> Transcript Template
                        </button>
                        <button class="btn btn-warning" onclick="openModal('transcriptLog')">
                            <i class="fas fa-list-alt"></i> Transcript Log
                        </button>
                    </div>
                    
                    <div class="transcript-overview">
                        <h3>Recent Transcript Requests</h3>
                        <div class="transcript-list">
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-001 - John Doe</h4>
                                    <span class="status-badge completed">Completed</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Nursing</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 15, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Job Application</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                    <button class="btn btn-sm btn-outline-info">Reprint</button>
                                </div>
                            </div>
                            
                            <div class="transcript-item">
                                <div class="transcript-header">
                                    <h4>STU-2023-045 - Jane Smith</h4>
                                    <span class="status-badge in-progress">Processing</span>
                                </div>
                                <div class="transcript-details">
                                    <div class="detail">
                                        <span>Program:</span>
                                        <strong>Certificate Midwifery</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Requested:</span>
                                        <strong>Apr 18, 2026</strong>
                                    </div>
                                    <div class="detail">
                                        <span>Purpose:</span>
                                        <strong>Further Studies</strong>
                                    </div>
                                </div>
                                <div class="transcript-actions">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-warning">Process</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activities -->
                <section class="activities-section">
                    <h2>Recent Registrar Activities</h2>
                    <div class="activities-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon'] ?? 'check-circle'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo $activity['user_name'] ?? 'Academic Registrar'; ?></strong> <?php echo $activity['action'] ?? $activity['activity'] ?? 'Activity'; ?></p>
                                <small><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current date/time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Navigation
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                document.querySelectorAll('.content-section').forEach(section => {
                    section.style.display = 'none';
                });
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });

        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'reviewApplication':
                    modalTitle.textContent = 'Review Application';
                    modalBody.innerHTML = `
                        <div class="application-review">
                            <h5>Application Details</h5>
                            <div class="applicant-info">
                                <div class="info-row">
                                    <strong>Application ID:</strong> APP-2026-001
                                </div>
                                <div class="info-row">
                                    <strong>Name:</strong> John Doe
                                </div>
                                <div class="info-row">
                                    <strong>Program:</strong> Certificate Nursing
                                </div>
                                <div class="info-row">
                                    <strong>Applied Date:</strong> April 20, 2026
                                </div>
                                <div class="info-row">
                                    <strong>Qualifications:</strong> UCE - English (C4), Math (C3), Biology (C3), Chemistry (C4), Physics (P7)
                                </div>
                            </div>
                            <div class="review-actions">
                                <h6>Review Comments</h6>
                                <textarea class="form-control mb-3" rows="3" placeholder="Add review comments..."></textarea>
                                <div class="decision-buttons">
                                    <button class="btn btn-success">Approve Application</button>
                                    <button class="btn btn-warning">Request Interview</button>
                                    <button class="btn btn-danger">Reject Application</button>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'newRegistration':
                    modalTitle.textContent = 'New Student Registration';
                    modalBody.innerHTML = `
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Student ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Application ID</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Surname</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Other Names</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Program</label>
                                        <select class="form-control" required>
                                            <option value="">Select Program</option>
                                            <option value="cert-nursing">Certificate Nursing</option>
                                            <option value="cert-midwifery">Certificate Midwifery</option>
                                            <option value="diploma-nursing">Diploma Nursing</option>
                                            <option value="diploma-midwifery">Diploma Midwifery</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Intake</label>
                                        <select class="form-control" required>
                                            <option value="">Select Intake</option>
                                            <option value="january">January 2026</option>
                                            <option value="july">July 2026</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                case 'generateTranscript':
                    modalTitle.textContent = 'Generate Academic Transcript';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transcript Type</label>
                                <select class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="official">Official Transcript</option>
                                    <option value="unofficial">Unofficial Transcript</option>
                                    <option value="provisional">Provisional Transcript</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purpose</label>
                                <select class="form-control" required>
                                    <option value="">Select Purpose</option>
                                    <option value="job">Job Application</option>
                                    <option value="further-studies">Further Studies</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="personal">Personal Use</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Include</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeGrades" checked>
                                    <label class="form-check-label" for="includeGrades">Grades and GPA</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeAttendance">
                                    <label class="form-check-label" for="includeAttendance">Attendance Records</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeConduct">
                                    <label class="form-check-label" for="includeConduct">Conduct Report</label>
                                </div>
                            </div>
                        </form>
                    `;
                    break;
                // Add more cases as needed
            }
            
            modal.show();
        }
    </script>
    
    <!-- Student Profile Modal -->
    <?php echo displayStudentProfileModal(''); ?>
    
    <!-- Student Profile Styles -->
    <?php echo getStudentProfileStyles(); ?>
    
    <script>
    // Override modal functions for registrar dashboard
    function viewFullProfile(studentId) {
        showStudentProfileModal(studentId);
    }
    
    function editStudent(studentId) {
        window.location.href = '../student_accounts_management.php?action=edit&student_id=' + studentId;
    }
    
    function viewAcademic(studentId) {
        window.location.href = '../academic_records.php?student_id=' + studentId;
    }
    
    function viewFees(studentId) {
        window.location.href = '../fee_management.php?student_id=' + studentId;
    }
    
    function sendMessage(studentId) {
        // Implement messaging functionality
        alert('Messaging functionality would be implemented here for student: ' + studentId);
    }
    
    function printProfile(studentId) {
        window.print();
    }
    </script>
</body>
</html>
