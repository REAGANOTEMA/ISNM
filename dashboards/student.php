<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../security-middleware.php';

// Strict dashboard protection - only students allowed
requireRole('Student');

// Get student information
$student_id = $_SESSION['user_id'];
$student_info = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();

// Get fee account information
$fee_account = $conn->query("SELECT * FROM student_fee_accounts WHERE student_id = '$student_id' ORDER BY academic_year DESC, semester DESC")->fetch_all(MYSQLI_ASSOC);

// Get academic records
$academic_records = $conn->query("SELECT * FROM academic_records WHERE student_id = '$student_id' ORDER BY academic_year DESC, semester DESC")->fetch_all(MYSQLI_ASSOC);

// Get recent messages
$messages = $conn->query("SELECT * FROM messages WHERE recipient_id = '$student_id' AND recipient_role = 'Students' ORDER BY sent_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get payment history
$payment_history = $conn->query("SELECT fp.* FROM fee_payments fp JOIN student_fee_accounts sfa ON fp.fee_account_id = sfa.id WHERE sfa.student_id = '$student_id' ORDER BY fp.payment_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - ISNM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/isnm-style.css">
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <style>
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .course-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-left: 4px solid #3b82f6;
        }

        .course-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(59, 130, 246, 0.05), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .course-card:hover::before {
            opacity: 1;
        }

        .course-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-left-color: #1e3a8a;
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .course-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .course-badge {
            background: #f3f4f6;
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .course-card h4 {
            color: #1e3a8a;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .course-card p {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .course-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .course-details span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .course-progress {
            margin-top: 1rem;
        }

        .course-progress .progress {
            height: 8px;
            border-radius: 10px;
            background: #f3f4f6;
            overflow: hidden;
        }

        .course-progress .progress-bar {
            background: linear-gradient(90deg, #3b82f6 0%, #1e3a8a 100%);
            transition: width 0.6s ease;
        }

        .course-progress small {
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: block;
        }

        /* Professional Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .finance-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: slideInFromLeft 0.6s ease-out;
        }

        .finance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(16, 185, 129, 0.05), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .finance-card:hover::before {
            opacity: 1;
        }

        .finance-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }

        .finance-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .finance-details h3 {
            color: #1e3a8a;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .finance-details .amount {
            color: #10b981;
            font-size: 1.8rem;
            font-weight: 700;
        }

        /* Responsive Dashboard Container */
        .dashboard-container {
            min-height: 100vh;
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }
        
        .dashboard-main {
            padding: 20px;
            max-width: 100%;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0 !important;
            }
            
            .dashboard-main {
                padding: 15px;
                padding-top: 80px; /* Space for mobile menu */
            }
        }
        
        @media (min-width: 769px) {
            .dashboard-container.sidebar-collapsed {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Include Responsive Navigation -->
    <?php include_once '../includes/sidebar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Main Content Area -->
        <div class="dashboard-main">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-left">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <?php echo $student_info['first_name']; ?>! Here's your academic overview</p>
                </div>
                <div class="header-right">
                    <div class="date-time">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="user-menu">
                        <img src="../images/default-avatar.png" alt="User" class="user-avatar">
                        <div class="user-dropdown">
                            <span><?php echo $student_info['first_name'] . ' ' . $student_info['surname']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Student Overview -->
                <section id="overview" class="content-section">
                    <h2>Academic Overview</h2>
                    <div class="student-info-cards">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="info-content">
                                <h4>Student Information</h4>
                                <p><strong>ID:</strong> <?php echo $student_info['student_id']; ?></p>
                                <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                <p><strong>Level:</strong> <?php echo $student_info['level']; ?></p>
                                <p><strong>Year:</strong> <?php echo $student_info['current_year']; ?></p>
                                <p><strong>Semester:</strong> <?php echo $student_info['current_semester']; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="info-content">
                                <h4>Academic Performance</h4>
                                <?php if (!empty($academic_records)): ?>
                                <p><strong>Current GPA:</strong> <?php echo $academic_records[0]['gpa'] ?? 'N/A'; ?></p>
                                <p><strong>Last Semester:</strong> <?php echo $academic_records[0]['semester']; ?></p>
                                <p><strong>Class Position:</strong> <?php echo $academic_records[0]['class_position'] ?? 'N/A'; ?>/<?php echo $academic_records[0]['total_students'] ?? 'N/A'; ?></p>
                                <p><strong>Attendance:</strong> <?php echo $academic_records[0]['attendance_percentage'] ?? 'N/A'; ?>%</p>
                                <?php else: ?>
                                <p>No academic records available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="info-content">
                                <h4>Financial Status</h4>
                                <?php if (!empty($fee_account)): ?>
                                <p><strong>Total Fees:</strong> UGX <?php echo number_format($fee_account[0]['total_fees']); ?></p>
                                <p><strong>Paid:</strong> UGX <?php echo number_format($fee_account[0]['amount_paid']); ?></p>
                                <p><strong>Balance:</strong> UGX <?php echo number_format($fee_account[0]['balance']); ?></p>
                                <p><strong>Status:</strong> <span class="status-badge <?php echo $fee_account[0]['status']; ?>"><?php echo ucfirst($fee_account[0]['status']); ?></span></p>
                                <?php else: ?>
                                <p>No fee information available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <button class="action-btn" onclick="openModal('viewTranscript')">
                            <i class="fas fa-file-alt"></i>
                            <span>View Transcript</span>
                        </button>
                        <button class="action-btn" onclick="openModal('payFees')">
                            <i class="fas fa-credit-card"></i>
                            <span>Pay Fees</span>
                        </button>
                        <button class="action-btn" onclick="openModal('downloadDocuments')">
                            <i class="fas fa-download"></i>
                            <span>Download Documents</span>
                        </button>
                        <button class="action-btn" onclick="openModal('sendMessage')">
                            <i class="fas fa-envelope"></i>
                            <span>Send Message</span>
                        </button>
                    </div>
                </section>
                
                <!-- Profile Section -->
                <section id="profile" class="content-section">
                    <h2>My Profile</h2>
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <img src="../images/default-avatar.png" alt="Profile" class="avatar-img" id="studentProfileImage">
                                <button class="btn btn-sm btn-primary" onclick="openModal('uploadPhoto')">
                                    <i class="fas fa-camera"></i> Change Photo
                                </button>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo $student_info['first_name'] . ' ' . $student_info['surname'] . ' ' . $student_info['other_name']; ?></h3>
                                <p><strong>Student ID:</strong> <?php echo $student_info['student_id']; ?></p>
                                <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                <p><strong>Email:</strong> <?php echo $student_info['email']; ?></p>
                                <p><strong>Phone:</strong> <?php echo $student_info['phone']; ?></p>
                            </div>
                            <div class="profile-actions">
                                <button class="btn btn-outline-primary" onclick="openModal('editProfile')">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </button>
                                <button class="btn btn-outline-secondary" onclick="openModal('changePassword')">
                                    <i class="fas fa-lock"></i> Change Password
                                </button>
                                <button class="btn btn-outline-info" onclick="openModal('printProfile')">
                                    <i class="fas fa-print"></i> Print Profile
                                </button>
                                <button class="btn btn-outline-success" onclick="openModal('downloadDocuments')">
                                    <i class="fas fa-download"></i> Download Documents
                                </button>
                            </div>
                        </div>
                        
                        <div class="profile-details">
                            <div class="detail-section">
                                <h4>Personal Information</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Date of Birth:</span>
                                        <strong><?php echo date('F j, Y', strtotime($student_info['date_of_birth'])); ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Gender:</span>
                                        <strong><?php echo $student_info['gender']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Nationality:</span>
                                        <strong><?php echo $student_info['nationality']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Address:</span>
                                        <strong><?php echo $student_info['address']; ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h4>Academic Information</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span>Enrollment Date:</span>
                                        <strong><?php echo date('F j, Y', strtotime($student_info['enrollment_date'])); ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Current Year:</span>
                                        <strong>Year <?php echo $student_info['current_year']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Current Semester:</span>
                                        <strong>Semester <?php echo $student_info['current_semester']; ?></strong>
                                    </div>
                                    <div class="detail-item">
                                        <span>Expected Graduation:</span>
                                        <strong><?php echo $student_info['expected_graduation_date'] ? date('F j, Y', strtotime($student_info['expected_graduation_date'])) : 'TBD'; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Academics Section -->
                <section id="academics" class="content-section">
                    <h2>Academic Records</h2>
                    <div class="academic-tabs">
                        <ul class="nav nav-tabs" id="academicTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab">
                                    <i class="fas fa-chart-bar"></i> Results
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="transcript-tab" data-bs-toggle="tab" data-bs-target="#transcript" type="button" role="tab">
                                    <i class="fas fa-file-alt"></i> Transcript
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
                                    <i class="fas fa-calendar-check"></i> Attendance
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab">
                                    <i class="fas fa-book"></i> Courses
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="academicTabContent">
                            <div class="tab-pane fade show active" id="results" role="tabpanel">
                                <div class="results-table">
                                    <h3>Semester Results</h3>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Academic Year</th>
                                                    <th>Semester</th>
                                                    <th>Year</th>
                                                    <th>GPA</th>
                                                    <th>Class Position</th>
                                                    <th>Total Students</th>
                                                    <th>Attendance</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($academic_records as $record): ?>
                                                <tr>
                                                    <td><?php echo $record['academic_year']; ?></td>
                                                    <td><?php echo $record['semester']; ?></td>
                                                    <td><?php echo $record['year']; ?></td>
                                                    <td><?php echo $record['gpa'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $record['class_position'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $record['total_students'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $record['attendance_percentage'] ?? 'N/A'; ?>%</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary">View Details</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="transcript" role="tabpanel">
                                <div class="transcript-section">
                                    <h3>Academic Transcript</h3>
                                    <div class="transcript-preview">
                                        <div class="transcript-header">
                                            <img src="../images/school-logo.png" alt="ISNM Logo" class="transcript-logo">
                                            <div class="transcript-title">
                                                <h2>Iganga School of Nursing and Midwifery</h2>
                                                <p>Official Academic Transcript</p>
                                            </div>
                                        </div>
                                        <div class="transcript-student-info">
                                            <p><strong>Name:</strong> <?php echo $student_info['first_name'] . ' ' . $student_info['surname']; ?></p>
                                            <p><strong>Student ID:</strong> <?php echo $student_info['student_id']; ?></p>
                                            <p><strong>Program:</strong> <?php echo $student_info['program']; ?></p>
                                        </div>
                                        <div class="transcript-content">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Year</th>
                                                        <th>Semester</th>
                                                        <th>Courses</th>
                                                        <th>Credits</th>
                                                        <th>GPA</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($academic_records as $record): ?>
                                                    <tr>
                                                        <td><?php echo $record['year']; ?></td>
                                                        <td><?php echo $record['semester']; ?></td>
                                                        <td><?php echo $record['courses'] ?? 'N/A'; ?></td>
                                                        <td><?php echo $record['credits'] ?? 'N/A'; ?></td>
                                                        <td><?php echo $record['gpa'] ?? 'N/A'; ?></td>
                                                        <td><?php echo $record['remarks'] ?? 'N/A'; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="transcript-actions">
                                        <button class="btn btn-primary" onclick="downloadTranscript()">
                                            <i class="fas fa-download"></i> Download PDF
                                        </button>
                                        <button class="btn btn-info" onclick="printTranscript()">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="attendance" role="tabpanel">
                                <div class="attendance-section">
                                    <h3>Attendance Records</h3>
                                    <div class="attendance-chart">
                                        <div class="attendance-stat">
                                            <div class="attendance-circle">
                                                <span class="attendance-percentage"><?php echo $academic_records[0]['attendance_percentage'] ?? '0'; ?>%</span>
                                            </div>
                                            <p>Overall Attendance</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="courses" role="tabpanel">
                                <div class="courses-section">
                                    <h3>Current Courses</h3>
                                    <div class="courses-grid">
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.1s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-heartbeat"></i>
                                                </div>
                                                <div class="course-badge">Core</div>
                                            </div>
                                            <h4>Nursing Fundamentals</h4>
                                            <p>Core nursing principles and practices</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 4</span>
                                                <span><i class="fas fa-user-tie"></i> Dr. Sarah Johnson</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 75%"></div>
                                                </div>
                                                <small>75% Complete</small>
                                            </div>
                                        </div>
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-user-md"></i>
                                                </div>
                                                <div class="course-badge">Core</div>
                                            </div>
                                            <h4>Anatomy & Physiology</h4>
                                            <p>Human body structure and functions</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 3</span>
                                                <span><i class="fas fa-user-tie"></i> Prof. Michael Brown</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 60%"></div>
                                                </div>
                                                <small>60% Complete</small>
                                            </div>
                                        </div>
                                        <div class="course-card" style="animation: fadeInUp 0.6s ease-out 0.3s both;">
                                            <div class="course-header">
                                                <div class="course-icon">
                                                    <i class="fas fa-pills"></i>
                                                </div>
                                                <div class="course-badge">Elective</div>
                                            </div>
                                            <h4>Pharmacology</h4>
                                            <p>Medication administration and drug interactions</p>
                                            <div class="course-details">
                                                <span><i class="fas fa-graduation-cap"></i> Credits: 3</span>
                                                <span><i class="fas fa-user-tie"></i> Dr. Emily Chen</span>
                                            </div>
                                            <div class="course-progress">
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 45%"></div>
                                                </div>
                                                <small>45% Complete</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Finances Section -->
                <section id="finances" class="content-section">
                    <h2>Financial Information</h2>
                    <div class="financial-overview">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="finance-card">
                                    <div class="finance-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="finance-details">
                                        <h3>Total Fees</h3>
                                        <p class="amount">UGX <?php echo number_format($fee_account[0]['total_fees'] ?? 0); ?></p>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                        
                        <div class="payment-actions">
                            <button class="btn btn-primary btn-lg" onclick="openModal('makePayment')">
                                <i class="fas fa-credit-card"></i> Make Payment
                            </button>
                            <button class="btn btn-outline-info" onclick="openModal('viewPaymentHistory')">
                                <i class="fas fa-history"></i> Payment History
                            </button>
                            <button class="btn btn-outline-success" onclick="openModal('downloadReceipt')">
                                <i class="fas fa-receipt"></i> Download Receipt
                            </button>
                        </div>
                        
                        <!-- Recent Payments -->
                        <div class="recent-payments">
                            <h3>Recent Payments</h3>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_id']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>UGX <?php echo number_format($payment['amount_paid']); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $payment['status']; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['receipt_generated']): ?>
                                                <button class="btn btn-sm btn-outline-primary">Download</button>
                                                <?php else: ?>
                                                <span class="text-muted">Not Generated</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Messages Section -->
                <section id="messages" class="content-section">
                    <h2>Communication Center</h2>
                    <div class="communication-actions">
                        <button class="btn btn-primary" onclick="openModal('composeMessage')">
                            <i class="fas fa-plus"></i> Compose Message
                        </button>
                        <button class="btn btn-success" onclick="openModal('messageMatron')">
                            <i class="fas fa-female"></i> Message Matron
                        </button>
                        <button class="btn btn-info" onclick="openModal('messageWarden')">
                            <i class="fas fa-male"></i> Message Warden
                        </button>
                        <button class="btn btn-warning" onclick="openModal('messagePrincipal')">
                            <i class="fas fa-university"></i> Message Principal
                        </button>
                        <button class="btn btn-outline-primary" onclick="openModal('messageLecturer')">
                            <i class="fas fa-chalkboard-teacher"></i> Message Lecturer
                        </button>
                    </div>
                    
                    <div class="messages-container">
                        <div class="message-filters">
                            <button class="btn btn-primary active">All Messages</button>
                            <button class="btn btn-outline-primary">Unread</button>
                            <button class="btn btn-outline-primary">Academic</button>
                            <button class="btn btn-outline-primary">Financial</button>
                            <button class="btn btn-outline-primary">Administrative</button>
                            <button class="btn btn-outline-primary">Personal</button>
                            <button class="btn btn-outline-primary">Emergency</button>
                        </div>
                        
                        <div class="messages-list">
                            <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo $message['status'] === 'read' ? 'read' : 'unread'; ?>">
                                <div class="message-header">
                                    <div class="message-sender">
                                        <strong><?php echo $message['sender_role']; ?></strong>
                                        <span class="message-date"><?php echo date('M j, Y H:i', strtotime($message['sent_date'])); ?></span>
                                    </div>
                                    <div class="message-priority">
                                        <span class="priority-badge <?php echo $message['priority']; ?>">
                                            <?php echo ucfirst($message['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <h4><?php echo $message['subject']; ?></h4>
                                </div>
                                <div class="message-preview">
                                    <p><?php echo substr($message['message_content'], 0, 100) . '...'; ?></p>
                                </div>
                                <div class="message-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewMessage('<?php echo $message['message_id']; ?>')">Read More</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="replyMessage('<?php echo $message['message_id']; ?>')">Reply</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Communication Section -->
                <section id="communication" class="content-section">
                    <h2>Communication Center</h2>
                    <div class="communication-grid">
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3>Contact Principal</h3>
                            <p>Send messages to the School Principal</p>
                            <button class="btn btn-primary" onclick="openModal('contactPrincipal')">Send Message</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-female"></i>
                            </div>
                            <h3>Contact Matron</h3>
                            <p>Get in touch with the student matron for welfare issues</p>
                            <button class="btn btn-primary" onclick="openModal('contactMatron')">Send Message</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h3>Contact Classmates</h3>
                            <p>Communicate with fellow students in your class</p>
                            <button class="btn btn-primary" onclick="openModal('contactClassmates')">Find Classmates</button>
                        </div>
                        
                        <div class="comm-card">
                            <div class="comm-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h3>Contact Lecturers</h3>
                            <p>Reach out to your course instructors</p>
                            <button class="btn btn-primary" onclick="openModal('contactLecturers')">View Lecturers</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
        <div class="modal fade" id="actionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modalBody">
                        <!-- Dynamic content -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="modalAction">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalTitle">Make Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="paymentModalBody">
                        <!-- Payment content -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmPayment">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment integration functions
        function openPaymentModal(method) {
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const modalTitle = document.getElementById('paymentModalTitle');
            const modalBody = document.getElementById('paymentModalBody');
            
            let title = '';
            let content = '';
            
            switch(method) {
                case 'mtn':
                    title = 'MTN Mobile Money Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-mobile-alt mtn-icon"></i>
                                <h4>Pay with MTN Mobile Money</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="mtnAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Mobile Money Number</label>
                                    <input type="tel" class="form-control" id="mtnNumber" value="<?php echo $student_info['phone']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="mtnReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>You will receive a prompt on your MTN Mobile Money number to confirm this payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'airtel':
                    title = 'Airtel Money Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-mobile-alt airtel-icon"></i>
                                <h4>Pay with Airtel Money</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="airtelAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Mobile Money Number</label>
                                    <input type="tel" class="form-control" id="airtelNumber" value="<?php echo $student_info['phone']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="airtelReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>You will receive a prompt on your Airtel Money number to confirm this payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'bank':
                    title = 'Bank Deposit Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-university bank-icon"></i>
                                <h4>Bank Deposit</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="bankAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Bank Name</label>
                                    <select class="form-control" id="bankName" required>
                                        <option value="">Select Bank</option>
                                        <option value="centenary">Centenary Bank</option>
                                        <option value="stanbic">Stanbic Bank</option>
                                        <option value="dfcu">DFCU Bank</option>
                                        <option value="absa">Absa Bank</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Deposit Slip Number</label>
                                    <input type="text" class="form-control" id="depositSlip" required>
                                </div>
                                <div class="form-group">
                                    <label>Deposit Date</label>
                                    <input type="date" class="form-control" id="depositDate" required>
                                </div>
                                <div class="form-group">
                                    <label>Upload Deposit Slip</label>
                                    <input type="file" class="form-control" id="depositSlipFile" accept="image/*,.pdf" required>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Upload a clear photo of your deposit slip. Our finance team will verify and approve your payment.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'cash':
                    title = 'Cash Payment';
                    content = `
                        <div class="payment-form">
                            <div class="payment-header">
                                <i class="fas fa-money-bill cash-icon"></i>
                                <h4>Cash Payment at School</h4>
                            </div>
                            <div class="payment-details">
                                <div class="form-group">
                                    <label>Amount (UGX)</label>
                                    <input type="number" class="form-control" id="cashAmount" min="1000" step="1000" required>
                                </div>
                                <div class="form-group">
                                    <label>Payment Reason</label>
                                    <select class="form-control" id="cashReason" required>
                                        <option value="">Select Reason</option>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="accommodation">Accommodation</option>
                                        <option value="clinical">Clinical Fees</option>
                                        <option value="other">Other Fees</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Payment Date</label>
                                    <input type="date" class="form-control" id="cashDate" required>
                                </div>
                            </div>
                            <div class="payment-info">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p>Please visit the bursar's office to make cash payments. Bring this confirmation with you.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            modalTitle.textContent = title;
            modalBody.innerHTML = content;
            modal.show();
        }
        
        function processPayment(method) {
            // Implementation for processing payment
            console.log('Processing payment with method:', method);
            
            // Show processing message
            const modalBody = document.getElementById('paymentModalBody');
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Processing...</span>
                    </div>
                    <h4 class="mt-3">Processing Payment</h4>
                    <p>Please wait while we process your payment...</p>
                </div>
            `;
            
            // Simulate payment processing
            setTimeout(() => {
                modalBody.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Payment Successful!</h4>
                        <p>Your payment has been processed successfully.</p>
                        <div class="payment-receipt">
                            <p><strong>Receipt Number:</strong> ISNM${Date.now()}</p>
                            <p><strong>Amount:</strong> UGX ${document.getElementById(method + 'Amount').value}</p>
                            <p><strong>Method:</strong> ${method.charAt(0).toUpperCase() + method.slice(1)}</p>
                        </div>
                        <button class="btn btn-primary" onclick="downloadReceipt()">Download Receipt</button>
                    </div>
                `;
            }, 3000);
        }
        
        function downloadReceipt() {
            // Implementation for downloading receipt
            console.log('Downloading receipt...');
            window.print();
        }
        
        // Payment confirmation handler
        document.getElementById('confirmPayment').addEventListener('click', function() {
            const modalTitle = document.getElementById('paymentModalTitle').textContent;
            let paymentMethod = '';
            
            if (modalTitle.includes('MTN')) {
                paymentMethod = 'mtn';
            } else if (modalTitle.includes('Airtel')) {
                paymentMethod = 'airtel';
            } else if (modalTitle.includes('Bank')) {
                paymentMethod = 'bank';
            } else if (modalTitle.includes('Cash')) {
                paymentMethod = 'cash';
            }
            
            processPayment(paymentMethod);
        });
        
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Modal functions
        function openModal(action) {
            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            switch(action) {
                case 'viewTranscript':
                    modalTitle.textContent = 'Academic Transcript';
                    modalBody.innerHTML = `
                        <div class="transcript-preview">
                            <h4>Official Academic Transcript</h4>
                            <p>Your complete academic record will be displayed here.</p>
                            <div class="text-center">
                                <button class="btn btn-primary" onclick="downloadTranscript()">
                                    <i class="fas fa-download"></i> Download PDF
                                </button>
                                <button class="btn btn-info" onclick="printTranscript()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                case 'payFees':
                case 'makePayment':
                    modalTitle.textContent = 'Make Payment';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Payment Amount (UGX)</label>
                                <input type="number" class="form-control" min="10000" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" required>
                                    <option value="">Select Method</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_deposit">Bank Deposit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transaction Reference</label>
                                <input type="text" class="form-control" placeholder="Enter transaction reference">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload Proof (Optional)</label>
                                <input type="file" class="form-control" accept="image/*,.pdf">
                            </div>
                        </form>
                    `;
                    break;
                case 'sendMessage':
                    modalTitle.textContent = 'Compose Message';
                    modalBody.innerHTML = `
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Recipient</label>
                                <select class="form-control" required>
                                    <option value="">Select Recipient</option>
                                    <option value="principal">School Principal</option>
                                    <option value="matron">Matron</option>
                                    <option value="lecturer">Lecturer</option>
                                    <option value="classmate">Classmate</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                        </form>
                    `;
                    break;
                default:
                    modalTitle.textContent = 'Action';
                    modalBody.innerHTML = '<p>Action content will be loaded here.</p>';
            }
            
            modal.show();
        }
        
        function downloadTranscript() {
            alert('Transcript download functionality will be implemented here.');
        }
        
        function printTranscript() {
            window.print();
        }
        
        function viewMessage(messageId) {
            console.log('Viewing message:', messageId);
            // Implementation for viewing full message
        }
        
        function replyMessage(messageId) {
            console.log('Replying to message:', messageId);
            // Implementation for replying to message
        }
    </script>
</body>
</html>
