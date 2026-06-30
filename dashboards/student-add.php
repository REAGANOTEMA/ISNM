<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard(['registrar', 'admissions', 'admin']);
$staffDb = $ctx['staff'];
$user_role = $_SESSION['role'] ?? '';
$pageTitle = 'Add Student';

require_once __DIR__ . '/../config/database.php';
$conn = getStudentsConnection();

$totalStudents = $activeStudents = $newThisYear = $graduated = 0;
$students = [];
if ($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) as c FROM students");
        if ($r) $totalStudents = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status='Active'");
        if ($r) $activeStudents = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE YEAR(created_at)=YEAR(CURDATE())");
        if ($r) $newThisYear = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM students WHERE status='Graduated'");
        if ($r) $graduated = (int)$r->fetch_assoc()['c'];

        $search = trim($_GET['search'] ?? '');
        $program = trim($_GET['program'] ?? '');
        $level = trim($_GET['level'] ?? '');
        $hasSearch = $search !== '' || $program !== '' || $level !== '';
        if ($hasSearch) {
            $where = "WHERE status != 'Deleted'";
            $types = '';
            $params = [];
            if ($search !== '') {
                $like = '%' . $search . '%';
                $where .= " AND (first_name LIKE ? OR surname LIKE ? OR other_name LIKE ? OR full_name LIKE ? OR index_number LIKE ? OR registration_number LIKE ? OR student_number LIKE ? OR national_student_id_number LIKE ? OR phone LIKE ? OR email LIKE ?)";
                $types .= str_repeat('s', 10);
                $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like, $like, $like]);
            }
            if ($program !== '') {
                $where .= " AND program=?";
                $types .= 's';
                $params[] = $program;
            }
            if ($level !== '') {
                $where .= " AND level=?";
                $types .= 's';
                $params[] = $level;
            }
            $stmt = $conn->prepare("SELECT id, first_name, surname, other_name, full_name, gender, index_number, registration_number, student_number, national_student_id_number, phone, email, program, level, set_name, year, status FROM students $where ORDER BY id DESC LIMIT 200");
            if ($stmt) {
                if (!empty($params)) $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $r = $stmt->get_result();
                if ($r) while ($row = $r->fetch_assoc()) $students[] = $row;
                $stmt->close();
            }
        }

        $progR = $conn->query("SELECT DISTINCT program FROM students WHERE status != 'Deleted' ORDER BY program");
        $programs = [];
        if ($progR) while ($p = $progR->fetch_assoc()) $programs[] = $p['program'];
        $levelR = $conn->query("SELECT DISTINCT level FROM students WHERE status != 'Deleted' ORDER BY level");
        $levels = [];
        if ($levelR) while ($l = $levelR->fetch_assoc()) $levels[] = $l['level'];
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$conn) {
        $_SESSION['error'] = 'Database connection failed';
        header('Location: student-add.php'); exit;
    }

    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $other_name = trim($_POST['other_name'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        if (empty($full_name)) $full_name = trim("$first_name $other_name $surname");
        $gender = $_POST['gender'] ?? 'Other';
        $index_number = trim($_POST['index_number'] ?? '');
        $registration_number = trim($_POST['registration_number'] ?? '');
        $student_number = trim($_POST['student_number'] ?? '');
        $national_id = trim($_POST['national_student_id_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        if (empty($mobile_number)) $mobile_number = $phone;
        if (empty($phone)) $phone = $mobile_number;
        $email = trim($_POST['email'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $set_name = trim($_POST['set_name'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        if (empty($first_name) || empty($surname)) {
            $_SESSION['error'] = 'First name and surname are required';
            header('Location: student-add.php'); exit;
        }
        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $fname = uniqid('stu_') . '.' . $ext;
                $dest = __DIR__ . '/../uploads/student_photos/' . $fname;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $photo_path = 'uploads/student_photos/' . $fname;
                }
            }
        }
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO students (first_name,surname,other_name,full_name,gender,index_number,registration_number,student_number,national_student_id_number,phone,mobile_number,email,program,level,set_name,year,current_year,passport_photo,profile_picture,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active')");
            $stmt->bind_param('sssssssssssssssssss', $first_name,$surname,$other_name,$full_name,$gender,$index_number,$registration_number,$student_number,$national_id,$phone,$mobile_number,$email,$program,$level,$set_name,$year,$year,$photo_path,$photo_path);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Student '$full_name' added successfully.";
            } else {
                $_SESSION['error'] = 'Add failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            if ($photo_path !== '') {
                $stmt = $conn->prepare("UPDATE students SET first_name=?,surname=?,other_name=?,full_name=?,gender=?,index_number=?,registration_number=?,student_number=?,national_student_id_number=?,phone=?,mobile_number=?,email=?,program=?,level=?,set_name=?,year=?,current_year=?,passport_photo=?,profile_picture=? WHERE id=?");
                $stmt->bind_param('sssssssssssssssssssi', $first_name,$surname,$other_name,$full_name,$gender,$index_number,$registration_number,$student_number,$national_id,$phone,$mobile_number,$email,$program,$level,$set_name,$year,$year,$photo_path,$photo_path,$id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET first_name=?,surname=?,other_name=?,full_name=?,gender=?,index_number=?,registration_number=?,student_number=?,national_student_id_number=?,phone=?,mobile_number=?,email=?,program=?,level=?,set_name=?,year=?,current_year=? WHERE id=?");
                $stmt->bind_param('ssssssssssssssssii', $first_name,$surname,$other_name,$full_name,$gender,$index_number,$registration_number,$student_number,$national_id,$phone,$mobile_number,$email,$program,$level,$set_name,$year,$year,$id);
            }
            if ($stmt->execute()) {
                $_SESSION['success'] = "Student '$full_name' updated successfully.";
            } else {
                $_SESSION['error'] = 'Update failed: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: student-add.php'); exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE students SET status='Deleted' WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Student deactivated successfully.';
            } else {
                $_SESSION['error'] = 'Delete failed: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: student-add.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
<style>
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    .main { margin-left: 0 !important; padding: 20px !important; }
}
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<?php include_once __DIR__ . '/../includes/dashboard_topbar.php'; ?>
<section class="content-section dashboard-section active" data-section="overview">
<div class="main" style="margin-left:270px;padding:32px">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Add Student</h4>
        <span class="text-muted small"><?= date('l, d M Y') ?></span>
    </div>

    <?php if(!empty($_SESSION['success'])): ?><div class="alert alert-success py-2 no-print"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if(!empty($_SESSION['error'])): ?><div class="alert alert-danger py-2 no-print"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <div class="stats-grid no-print">
        <div class="stat-card primary"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-content"><h3><?= $totalStudents ?></h3><p>Total Students</p></div></div>
        <div class="stat-card success"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-content"><h3><?= $activeStudents ?></h3><p>Active</p></div></div>
        <div class="stat-card info"><div class="stat-icon"><i class="fas fa-calendar-plus"></i></div><div class="stat-content"><h3><?= $newThisYear ?></h3><p>New This Year</p></div></div>
        <div class="stat-card warning"><div class="stat-icon"><i class="fas fa-graduation-cap"></i></div><div class="stat-content"><h3><?= $graduated ?></h3><p>Graduated</p></div></div>
    </div>

    <div class="no-print d-flex justify-content-between align-items-center mb-3 mt-3">
        <h5 class="fw-bold mb-0"><i class="fas fa-list me-2"></i>Student Records</h5>
        <div>
            <button class="btn btn-sm btn-outline-primary me-1" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="resetForm()"><i class="fas fa-plus me-1"></i>Add Student</button>
        </div>
    </div>

    <form method="GET" class="row g-2 mb-3 no-print">
        <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, index, phone, email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"></div>
        <div class="col-md-2"><select name="program" class="form-select form-select-sm"><option value="">All Programs</option><?php foreach($programs as $p): ?><option <?= ($_GET['program']??'')===$p?'selected':'' ?>><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select name="level" class="form-select form-select-sm"><option value="">All Levels</option><?php foreach($levels as $l): ?><option <?= ($_GET['level']??'')===$l?'selected':'' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button></div>
        <div class="col-md-2"><a href="student-add.php" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times"></i> Clear</a></div>
    </form>

    <div class="print-area table-responsive">
        <table class="table table-striped table-hover">
            <thead><tr><th>Student #</th><th>Full Name</th><th>Program</th><th>Level</th><th>Year</th><th>Phone</th><th>Status</th><th class="no-print">Actions</th></tr></thead>
            <tbody>
                <?php if (empty($students) && $hasSearch): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No student records match your search.</td></tr>
                <?php elseif (empty($students) && !$hasSearch): ?>
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-search me-1"></i>Use the search fields above to find existing students, or click "Add Student" to create a new one.</td></tr>
                <?php else: ?>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['student_number'] ?: $s['index_number'] ?: $s['id']) ?></code></td>
                    <td><strong><?= htmlspecialchars($s['full_name'] ?: trim(($s['first_name']??'').' '.($s['surname']??''))) ?></strong></td>
                    <td><?= htmlspecialchars($s['program'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['level'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['year'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                    <td><span class="badge bg-<?= ($s['status']??'')==='Active'?'success':(($s['status']??'')==='Graduated'?'info':(($s['status']??'')==='Deleted'?'danger':'secondary')) ?>"><?= htmlspecialchars(ucfirst($s['status'] ?? 'Unknown')) ?></span></td>
                    <td class="no-print">
                        <button class="btn btn-sm btn-outline-primary" onclick="editStudent(<?= $s['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-info" onclick="printStudent(<?= $s['id'] ?>)" title="Print"><i class="fas fa-print"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $s['id'] ?>,'<?= htmlspecialchars($s['full_name'] ?: trim(($s['first_name']??'').' '.($s['surname']??'')), ENT_QUOTES) ?>')" title="Deactivate"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</section>

<!-- Add/Edit Modal -->
<div class="modal fade" id="studentModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form method="POST" enctype="multipart/form-data" class="modal-content"><input type="hidden" name="action" id="formAction" value="add"><input type="hidden" name="id" id="studentId" value="0"><div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle">Add Student</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">First Name <span class="text-danger">*</span></label><input type="text" name="first_name" id="f_first_name" class="form-control" required></div><div class="col-md-4"><label class="form-label">Surname <span class="text-danger">*</span></label><input type="text" name="surname" id="f_surname" class="form-control" required></div><div class="col-md-4"><label class="form-label">Other Name</label><input type="text" name="other_name" id="f_other_name" class="form-control"></div><div class="col-md-4"><label class="form-label">Gender</label><select name="gender" id="f_gender" class="form-select"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div><div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" id="f_phone" class="form-control"></div><div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" id="f_email" class="form-control"></div><div class="col-md-3"><label class="form-label">Index Number</label><input type="text" name="index_number" id="f_index_number" class="form-control"></div><div class="col-md-3"><label class="form-label">Registration Number</label><input type="text" name="registration_number" id="f_registration_number" class="form-control"></div><div class="col-md-3"><label class="form-label">Student Number</label><input type="text" name="student_number" id="f_student_number" class="form-control"></div><div class="col-md-3"><label class="form-label">National ID (NSIN)</label><input type="text" name="national_student_id_number" id="f_national_id" class="form-control"></div><div class="col-md-4"><label class="form-label">Program</label><input type="text" name="program" id="f_program" class="form-control" list="progList"><datalist id="progList"><?php foreach($programs as $p): ?><option value="<?= htmlspecialchars($p) ?>"><?php endforeach; ?></datalist></div><div class="col-md-2"><label class="form-label">Level</label><input type="text" name="level" id="f_level" class="form-control" list="levelList"><datalist id="levelList"><?php foreach($levels as $l): ?><option value="<?= htmlspecialchars($l) ?>"><?php endforeach; ?></datalist></div><div class="col-md-3"><label class="form-label">Set Name</label><input type="text" name="set_name" id="f_set_name" class="form-control"></div><div class="col-md-3"><label class="form-label">Year</label><input type="number" name="year" id="f_year" class="form-control" value="<?= date('Y') ?>"></div><div class="col-md-6"><label class="form-label">Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Student</button></div></form></div></div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-header bg-danger text-white"><h6 class="modal-title">Confirm Deactivate</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="deleteBody">Deactivate this student?</div><div class="modal-footer"><form method="POST" id="deleteForm"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="deleteId" value="0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Deactivate</button></form></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const allStudents = <?= json_encode($students) ?>;
function resetForm() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Student';
    document.getElementById('studentId').value = 0;
    document.querySelectorAll('#studentModal input, #studentModal select').forEach(el => {
        if (el.type !== 'file' && el.type !== 'hidden') el.value = '';
    });
    document.getElementById('f_year').value = new Date().getFullYear();
}
function editStudent(id) {
    const s = allStudents.find(st => parseInt(st.id) === parseInt(id));
    if (!s) return;
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Student';
    document.getElementById('studentId').value = s.id;
    document.getElementById('f_first_name').value = s.first_name || '';
    document.getElementById('f_surname').value = s.surname || '';
    document.getElementById('f_other_name').value = s.other_name || '';
    document.getElementById('f_gender').value = s.gender || '';
    document.getElementById('f_phone').value = s.phone || '';
    document.getElementById('f_email').value = s.email || '';
    document.getElementById('f_index_number').value = s.index_number || '';
    document.getElementById('f_registration_number').value = s.registration_number || '';
    document.getElementById('f_student_number').value = s.student_number || '';
    document.getElementById('f_national_id').value = s.national_student_id_number || '';
    document.getElementById('f_program').value = s.program || '';
    document.getElementById('f_level').value = s.level || '';
    document.getElementById('f_set_name').value = s.set_name || '';
    document.getElementById('f_year').value = s.year || new Date().getFullYear();
    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteBody').textContent = 'Deactivate student "' + name + '"? This will set their status to Deleted.';
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
function printStudent(id) {
    const s = allStudents.find(st => parseInt(st.id) === parseInt(id));
    if (!s) return;
    const w = window.open('', '_blank');
    w.document.write('<html><head><title>Student Profile</title>');
    w.document.write('<style>body{font-family:Arial,sans-serif;padding:40px;}h2{color:#1a237e;border-bottom:2px solid #1a237e;padding-bottom:8px;}.row{display:flex;margin:6px 0;}.label{font-weight:700;width:180px;color:#555;}.value{flex:1;}</style></head>');
    w.document.write('<body><h2>Student Profile</h2>');
    w.document.write('<div class="row"><span class="label">Name:</span><span class="value">' + htmlEsc(s.full_name || (s.first_name + ' ' + s.surname)) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Gender:</span><span class="value">' + htmlEsc(s.gender) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Index Number:</span><span class="value">' + htmlEsc(s.index_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Registration:</span><span class="value">' + htmlEsc(s.registration_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Student Number:</span><span class="value">' + htmlEsc(s.student_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">National ID:</span><span class="value">' + htmlEsc(s.national_student_id_number) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Program:</span><span class="value">' + htmlEsc(s.program) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Level:</span><span class="value">' + htmlEsc(s.level) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Set:</span><span class="value">' + htmlEsc(s.set_name) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Year:</span><span class="value">' + htmlEsc(s.year) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Phone:</span><span class="value">' + htmlEsc(s.phone) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Email:</span><span class="value">' + htmlEsc(s.email) + '</span></div>');
    w.document.write('<div class="row"><span class="label">Status:</span><span class="value">' + htmlEsc(s.status) + '</span></div>');
    w.document.write('<p style="margin-top:30px;color:#999;font-size:12px;">Generated on ' + new Date().toLocaleDateString() + '</p>');
    w.document.write('</body></html>');
    w.document.close();
    setTimeout(function() { w.print(); }, 500);
}
function htmlEsc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
