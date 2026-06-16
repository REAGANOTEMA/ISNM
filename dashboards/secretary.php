<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/photo_upload.php';
require_once __DIR__ . '/../includes/student_profile_component.php';

$ctx = bootstrapStaffDashboard(['school secretary', 'secretary']);
$auth_service = $ctx['auth'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';

$conn = getStaffConnection();

$user_id    = $_SESSION['user_id'] ?? 0;
$user_role  = $_SESSION['role']    ?? '';
$user_email = $_SESSION['email']   ?? '';
$user_name  = $_SESSION['full_name'] ?? '';

$students_conn = getStudentsConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_student': handleAddStudent(); break;
            case 'update_student': handleUpdateStudent(); break;
            case 'delete_student': handleDeleteStudent(); break;
            case 'send_message': handleSendMessage(); break;
            case 'schedule_appointment': handleScheduleAppointment(); break;
        }
    }
}

function handleAddStudent() {
    global $conn;
    $student_id = generateStudentId();
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $nationality = sanitizeInput($_POST['nationality']);
    $address = sanitizeInput($_POST['address']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $program = sanitizeInput($_POST['program']);
    $level = sanitizeInput($_POST['level']);
    $intake_year = sanitizeInput($_POST['intake_year']);
    $intake_period = sanitizeInput($_POST['intake_period']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    $guardian_email = sanitizeInput($_POST['guardian_email']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    $sql = "INSERT INTO students (student_id, first_name, surname, other_name, date_of_birth, gender, nationality, address, phone, email, program, level, intake_year, intake_period, enrollment_date, guardian_name, guardian_phone, guardian_email, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssssss", $student_id, $first_name, $surname, $other_name, $date_of_birth, $gender, $nationality, $address, $phone, $email, $program, $level, $intake_year, $intake_period, $guardian_name, $guardian_phone, $guardian_email, $emergency_contact_name, $emergency_contact_phone);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Added', "Added new student: $student_id - $first_name $surname", 'students', $student_id);
        $_SESSION['success'] = "Student added successfully!";
    } else {
        $_SESSION['error'] = "Error adding student: " . $conn->error;
    }
    header("Location: secretary.php"); exit();
}

function handleUpdateStudent() {
    global $conn;
    $student_id = sanitizeInput($_POST['student_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $surname = sanitizeInput($_POST['surname']);
    $other_name = sanitizeInput($_POST['other_name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_phone = sanitizeInput($_POST['guardian_phone']);
    $guardian_email = sanitizeInput($_POST['guardian_email']);
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone']);
    $status = sanitizeInput($_POST['status']);
    $sql = "UPDATE students SET first_name = ?, surname = ?, other_name = ?, phone = ?, email = ?, address = ?, guardian_name = ?, guardian_phone = ?, guardian_email = ?, emergency_contact_name = ?, emergency_contact_phone = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", $first_name, $surname, $other_name, $phone, $email, $address, $guardian_name, $guardian_phone, $guardian_email, $emergency_contact_name, $emergency_contact_phone, $status, $student_id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Updated', "Updated student: $student_id - $first_name $surname", 'students', $student_id);
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating student: " . $conn->error;
    }
    header("Location: secretary.php"); exit();
}

function handleDeleteStudent() {
    global $conn;
    $student_id = sanitizeInput($_POST['student_id']);
    $check_sql = "SELECT COUNT(*) as count FROM fee_payments WHERE student_id = ?";
    $check_result = executeQuery($check_sql, [$student_id], 's');
    if ($check_result[0]['count'] > 0) {
        $_SESSION['error'] = "Cannot delete student with payment records. Please archive instead.";
    } else {
        $sql = "DELETE FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $student_id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], $_SESSION['role'], 'Student Deleted', "Deleted student: $student_id", 'students', $student_id);
            $_SESSION['success'] = "Student deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting student: " . $conn->error;
        }
    }
    header("Location: secretary.php"); exit();
}

function handleSendMessage() {
    global $conn;
    $student_id = sanitizeInput($_POST['student_id']);
    $message = sanitizeInput($_POST['message']);
    $message_type = sanitizeInput($_POST['message_type']);
    $sql = "INSERT INTO messages (student_id, sender_id, message_type, message_content, sent_date, status) VALUES (?, ?, ?, ?, CURDATE(), 'sent')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $student_id, $_SESSION['user_id'], $message_type, $message);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Message Sent', "Sent $message_type message to: $student_id", 'messages', $student_id);
        $_SESSION['success'] = "Message sent successfully!";
    } else {
        $_SESSION['error'] = "Error sending message: " . $conn->error;
    }
    header("Location: secretary.php"); exit();
}

function handleScheduleAppointment() {
    global $conn;
    $student_id = sanitizeInput($_POST['student_id']);
    $appointment_date = sanitizeInput($_POST['appointment_date']);
    $appointment_time = sanitizeInput($_POST['appointment_time']);
    $purpose = sanitizeInput($_POST['purpose']);
    $notes = sanitizeInput($_POST['notes']);
    $sql = "INSERT INTO appointments (student_id, staff_id, appointment_date, appointment_time, purpose, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'scheduled', CURDATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $student_id, $_SESSION['user_id'], $appointment_date, $appointment_time, $purpose, $notes);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], $_SESSION['role'], 'Appointment Scheduled', "Scheduled appointment for: $student_id", 'appointments', $student_id);
        $_SESSION['success'] = "Appointment scheduled successfully!";
    } else {
        $_SESSION['error'] = "Error scheduling appointment: " . $conn->error;
    }
    header("Location: secretary.php"); exit();
}

function generateStudentId() {
    global $conn;
    do {
        $year = date('Y');
        $random = mt_rand(1000, 9999);
        $student_id = "ISNM/$year/$random";
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE student_id = ?";
        $check_result = executeQuery($check_sql, [$student_id], 's');
    } while ($check_result[0]['count'] > 0);
    return $student_id;
}

$total_students = 0;
$total_stmt = $students_conn ? $students_conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'") : null;
if ($total_stmt) { $row = $total_stmt->fetch_assoc(); $total_students = (int)($row['count'] ?? 0); }

$appointments_today = 0;
$apt_stmt = $students_conn ? $students_conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()") : null;
if ($apt_stmt) { $row = $apt_stmt->fetch_assoc(); $appointments_today = (int)($row['count'] ?? 0); }

$recent_students = [];
$rs_stmt = $students_conn ? $students_conn->query("SELECT student_id, first_name, surname, program, status FROM students WHERE status = 'Active' ORDER BY created_at DESC LIMIT 6") : null;
if ($rs_stmt) { while ($row = $rs_stmt->fetch_assoc()) { $recent_students[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secretary Dashboard – ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;margin:0}
.page-content{margin-left:280px;flex:1;min-height:100vh}
@media(max-width:768px){.page-content{margin-left:0}}
.top-bar{background:#fff;padding:14px 22px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.content-area{padding:22px}
.stat-card{background:linear-gradient(to bottom,#ffe082 0%,#ffe082 5px,#fef9e7 5px,#fef9e7 100%);border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;transition:transform .25s}
.stat-card:hover{transform:translateY(-4px)}
.si{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0}
.si-blue{background:linear-gradient(135deg,#1a237e,#3949ab)}
.si-green{background:linear-gradient(135deg,#2e7d32,#43a047)}
.si-cyan{background:linear-gradient(135deg,#0277bd,#039be5)}
.si-orange{background:linear-gradient(135deg,#e65100,#fb8c00)}
.si-purple{background:linear-gradient(135deg,#4a148c,#8e24aa)}
.si-red{background:linear-gradient(135deg,#b71c1c,#ef5350)}
.stat-content h3{font-size:1.6rem;font-weight:700;margin:0;line-height:1}
.stat-content p{font-size:.77rem;color:#666;margin:2px 0 0}
.section-card{background:linear-gradient(to bottom,#d7ccc8 0%,#d7ccc8 5px,#f0dcc8 5px,#f0dcc8 100%);border-radius:14px;padding:20px;margin-bottom:22px}
.section-card h2{font-size:1rem;font-weight:700;margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f0f2f5}
.student-item{border:1px solid #e8e8e8;border-radius:10px;padding:12px 14px;margin-bottom:10px;transition:box-shadow .2s}
.student-item:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}
@media print{.sidebar,.top-bar,.no-print{display:none!important}.page-content{margin-left:0!important;padding:20px!important}.section-card{box-shadow:none!important;border:1px solid #ddd;page-break-inside:avoid}body{background:#fff!important}}
</style>
<link href="../dashboards/dashboard-mobile.css" rel="stylesheet">
</head>
<body>

<?php include_once '../includes/sidebar.php'; ?>

<div class="page-content">
  <div class="top-bar">
    <div>
      <strong><i class="fas fa-user-tie me-2 text-primary"></i>Secretary Dashboard</strong>
      <div class="text-muted small">Administrative Support &amp; Student Services | <?php echo htmlspecialchars($user_name); ?></div>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-block" id="currentDate"></span>
      <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
    </div>
  </div>

  <div class="content-area">
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?php echo htmlspecialchars($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['success']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?php echo htmlspecialchars($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="section-card">
      <h2><i class="fas fa-tachometer-alt me-2"></i>Office Overview</h2>
      <div class="row g-3">
        <div class="col-md-3 col-6">
          <div class="stat-card">
            <div class="si si-blue"><i class="fas fa-users"></i></div>
            <div class="stat-content"><h3><?php echo $total_students; ?></h3><p>Active Students</p></div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card">
            <div class="si si-green"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content"><h3>8</h3><p>Pending Applications</p></div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card">
            <div class="si si-orange"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content"><h3><?php echo $appointments_today; ?></h3><p>Today's Appointments</p></div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-card">
            <div class="si si-purple"><i class="fas fa-user-graduate"></i></div>
            <div class="stat-content"><h3><?php echo $total_students; ?></h3><p>Total Enrolled</p></div>
          </div>
        </div>
      </div>
    </div>

    <div class="section-card">
      <h2><i class="fas fa-users me-2"></i>Student Management</h2>
      <div class="mb-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus me-1"></i>Add Student</button>
        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#sendMessageModal"><i class="fas fa-envelope me-1"></i>Send Message</button>
        <a href="student-records.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users-gear me-1"></i>Student Records</a>
        <a href="../student-directory.php" class="btn btn-outline-info btn-sm"><i class="fas fa-address-book me-1"></i>Directory</a>
      </div>
      <?php echo displayStudentSearchBox('Search students by name, ID, or phone...', 'secretarySearchResults'); ?>
      <div class="row mt-3 g-2">
        <?php if (empty($recent_students)): ?>
          <div class="col-12 text-center py-4"><i class="fas fa-users fa-3x text-muted mb-3"></i><p class="text-muted">No students found</p></div>
        <?php else: ?>
          <?php foreach ($recent_students as $s): ?>
          <div class="col-md-4">
            <div class="student-item">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['surname']); ?></strong>
                <span class="badge bg-success" style="font-size:.7rem"><?php echo htmlspecialchars($s['status'] ?? 'Active'); ?></span>
              </div>
              <small class="text-muted d-block"><?php echo htmlspecialchars($s['student_id'] ?? ''); ?></small>
              <small class="text-muted d-block"><?php echo htmlspecialchars($s['program'] ?? ''); ?></small>
              <div class="mt-2 d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary" onclick="viewFullProfile('<?php echo $s['student_id']; ?>')"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-outline-success" onclick="editStudent('<?php echo $s['student_id']; ?>')"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-info" onclick="viewFees('<?php echo $s['student_id']; ?>')"><i class="fas fa-money-bill"></i></button>
                <button class="btn btn-sm btn-outline-warning" onclick="sendMessage('<?php echo $s['student_id']; ?>')"><i class="fas fa-envelope"></i></button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="section-card">
      <h2><i class="fas fa-calendar-alt me-2"></i>Today's Appointments</h2>
      <div class="mb-2">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal"><i class="fas fa-plus me-1"></i>Schedule Appointment</button>
      </div>
      <?php if ($appointments_today === 0): ?>
        <div class="text-center py-4"><i class="fas fa-calendar fa-3x text-muted mb-3"></i><p class="text-muted">No appointments scheduled for today</p></div>
      <?php else: ?>
        <p class="text-muted"><?php echo $appointments_today; ?> appointment(s) scheduled today</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#1a237e,#3949ab)">
        <h5 class="modal-title text-white"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="secretary.php">
        <input type="hidden" name="action" value="add_student">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">First Name *</label><input type="text" class="form-control" name="first_name" required></div>
            <div class="col-md-4"><label class="form-label">Surname *</label><input type="text" class="form-control" name="surname" required></div>
            <div class="col-md-4"><label class="form-label">Other Name</label><input type="text" class="form-control" name="other_name"></div>
            <div class="col-md-3"><label class="form-label">Date of Birth *</label><input type="date" class="form-control" name="date_of_birth" required></div>
            <div class="col-md-3"><label class="form-label">Gender *</label>
              <select class="form-select" name="gender" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Program *</label>
              <select class="form-select" name="program" required>
                <option value="">Select</option>
                <option value="Certificate Midwifery">Certificate Midwifery</option>
                <option value="Diploma Midwifery">Diploma Midwifery</option>
                <option value="Diploma Midwifery Extension">Diploma Midwifery Extension</option>
                <option value="Diploma Nursing Extension">Diploma Nursing Extension</option>
                <option value="Certificate Nursing">Certificate Nursing</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Level *</label>
              <select class="form-select" name="level" required>
                <option value="">Select</option>
                <option value="Certificate">Certificate</option>
                <option value="Diploma">Diploma</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Phone *</label><input type="tel" class="form-control" name="phone" required></div>
            <div class="col-md-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required></div>
            <div class="col-md-3"><label class="form-label">Intake Year *</label><input type="text" class="form-control" name="intake_year" value="<?php echo date('Y'); ?>" required></div>
            <div class="col-md-3"><label class="form-label">Intake Period</label>
              <select class="form-select" name="intake_period">
                <option value="January">January</option>
                <option value="May">May</option>
                <option value="July">July</option>
                <option value="September">September</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#17a2b8,#20c997)">
        <h5 class="modal-title text-white"><i class="fas fa-envelope me-2"></i>Send Message</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="secretary.php">
        <input type="hidden" name="action" value="send_message">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Student ID *</label><input type="text" class="form-control" name="student_id" required></div>
            <div class="col-md-6"><label class="form-label">Message Type *</label>
              <select class="form-select" name="message_type" required>
                <option value="">Select Type</option>
                <option value="general">General</option>
                <option value="academic">Academic</option>
                <option value="financial">Financial</option>
                <option value="administrative">Administrative</option>
                <option value="emergency">Emergency</option>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Message *</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info"><i class="fas fa-paper-plane me-1"></i>Send Message</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Schedule Appointment Modal -->
<div class="modal fade" id="scheduleAppointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#d97706,#f59e0b)">
        <h5 class="modal-title text-white"><i class="fas fa-calendar-plus me-2"></i>Schedule Appointment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="secretary.php">
        <input type="hidden" name="action" value="schedule_appointment">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Student ID *</label><input type="text" class="form-control" name="student_id" required></div>
            <div class="col-md-6"><label class="form-label">Appointment Date *</label><input type="date" class="form-control" name="appointment_date" required></div>
            <div class="col-md-6"><label class="form-label">Appointment Time *</label><input type="time" class="form-control" name="appointment_time" required></div>
            <div class="col-md-6"><label class="form-label">Purpose *</label>
              <select class="form-select" name="purpose" required>
                <option value="">Select</option>
                <option value="Registration">Registration</option>
                <option value="Academic Counseling">Academic Counseling</option>
                <option value="Financial Assistance">Financial Assistance</option>
                <option value="Administrative Support">Administrative Support</option>
                <option value="Document Collection">Document Collection</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-check me-1"></i>Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php echo displayStudentProfileModal(''); ?>
<?php echo getStudentProfileStyles(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateDateTime(){document.getElementById('currentDate').textContent=new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}
updateDateTime();setInterval(updateDateTime,60000);
function viewFullProfile(s){showStudentProfileModal(s)}
function editStudent(s){window.location.href='../student_accounts_management.php?action=edit&student_id='+s}
function viewFees(s){window.location.href='../fee_management.php?student_id='+s}
function sendMessage(s){document.querySelector('#sendMessageModal input[name="student_id"]').value=s;new bootstrap.Modal(document.getElementById('sendMessageModal')).show()}
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
