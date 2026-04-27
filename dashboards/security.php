<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../staff-login.php");
    exit();
}

// Verify role
if ($_SESSION['role'] !== 'Security') {
    header("Location: ../staff-login.php");
    exit();
}

include '../includes/config.php';
include '../includes/functions.php';
include '../includes/auth_functions.php';

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

// Get security statistics (using fallback data only)
$total_incidents_today = 3; // Fallback value
$security_patrols = 12; // Fallback value
$access_control_checks = 45; // Fallback value
$emergency_alerts = 1; // Fallback value
$cctv_cameras_active = 24; // Fallback value

// Get recent security incidents (using fallback data)
$recent_incidents = [
    ['type' => 'Visitor Check-in', 'location' => 'Main Gate', 'time' => '08:30 AM', 'status' => 'resolved'],
    ['type' => 'Vehicle Entry', 'location' => 'Parking Lot', 'time' => '09:15 AM', 'status' => 'resolved'],
    ['type' => 'Access Control', 'location' => 'Library', 'time' => '10:45 AM', 'status' => 'resolved']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - ISNM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../images/school-logo.png">
    <link href="dashboard-style.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid #dc3545;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #dc3545;
        }
        .security-alert {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .alert-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
        }
        .alert-item.high {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .alert-item.medium {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .alert-item.low {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .patrol-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-break {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-shield-alt"></i> Security Dashboard</h1>
                    <p class="mb-0">Campus Security Management</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="user-info">
                        <span class="me-3">Welcome, <?php echo $_SESSION['first_name']; ?></span>
                        <a href="../logout.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> Guards</h3>
                    <div class="stat-number">12</div>
                    <p class="text-muted mb-0">On Duty Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-exclamation-triangle"></i> Incidents</h3>
                    <div class="stat-number">3</div>
                    <p class="text-muted mb-0">Reported Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-video"></i> Cameras</h3>
                    <div class="stat-number">24</div>
                    <p class="text-muted mb-0">Active Monitoring</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fas fa-door-open"></i> Access</h3>
                    <div class="stat-number">847</div>
                    <p class="text-muted mb-0">Entries Today</p>
                </div>
            </div>
        </div>

        <!-- Security Alerts -->
        <div class="security-alert">
            <h3><i class="fas fa-bell"></i> Recent Security Alerts</h3>
            <div class="alert-item high">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-exclamation-circle"></i> Unauthorized Access Attempt</h6>
                        <small class="text-muted">Main Gate - 2:30 PM | Unknown individual attempted entry</small>
                    </div>
                    <span class="badge bg-danger">High Priority</span>
                </div>
            </div>
            <div class="alert-item medium">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-car"></i> Parking Violation</h6>
                        <small class="text-muted">Staff Parking - 1:15 PM | Vehicle parked in restricted area</small>
                    </div>
                    <span class="badge bg-warning">Medium Priority</span>
                </div>
            </div>
            <div class="alert-item low">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-lightbulb"></i> Light Malfunction</h6>
                        <small class="text-muted">Block B - 12:45 PM | Corridor light not working</small>
                    </div>
                    <span class="badge bg-success">Low Priority</span>
                </div>
            </div>
        </div>

        <!-- Patrol Schedule -->
        <div class="row">
            <div class="col-md-6">
                <div class="security-alert">
                    <h3><i class="fas fa-walking"></i> Current Patrol Status</h3>
                    <div class="alert-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Guard James Mukiibi</h6>
                                <small class="text-muted">Patrolling: Academic Block | Started: 2:00 PM</small>
                            </div>
                            <span class="patrol-status status-active">Active</span>
                        </div>
                    </div>
                    <div class="alert-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Guard Sarah Namulondo</h6>
                                <small class="text-muted">Patrolling: Hostel Area | Started: 1:30 PM</small>
                            </div>
                            <span class="patrol-status status-active">Active</span>
                        </div>
                    </div>
                    <div class="alert-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Guard David Katumba</h6>
                                <small class="text-muted">Break: Main Gate | Break until 3:00 PM</small>
                            </div>
                            <span class="patrol-status status-break">On Break</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="security-alert">
                    <h3><i class="fas fa-tasks"></i> Security Tasks</h3>
                    <div class="alert-item">
                        <h6>Gate Access Control</h6>
                        <small class="text-muted">Monitor and verify all campus entries</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="alert-item">
                        <h6>Vehicle Inspection</h6>
                        <small class="text-muted">Check all incoming and outgoing vehicles</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-warning" style="width: 60%"></div>
                        </div>
                    </div>
                    <div class="alert-item">
                        <h6>Campus Patrol</h6>
                        <small class="text-muted">Regular patrol of all campus areas</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-info" style="width: 40%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="security-alert">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-danger w-100">
                                <i class="fas fa-phone"></i> Emergency Contact
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning w-100">
                                <i class="fas fa-exclamation-triangle"></i> Report Incident
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info w-100">
                                <i class="fas fa-video"></i> View Cameras
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success w-100">
                                <i class="fas fa-clipboard"></i> Daily Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
