<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';

$ctx = bootstrapStaffDashboard([]);
$staffDb   = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user      = $ctx['user'];

$staff_id    = (int)($user['id'] ?? $_SESSION['user_id'] ?? 0);
$staff_email = $user['email'] ?? $_SESSION['email'] ?? '';
$staff_role  = $user['role'] ?? $_SESSION['role'] ?? '';

// ── Fetch staff record ──────────────────────────────────────────
$staff = null;
$stmt = $staffDb->prepare("SELECT * FROM staff WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Fetch staff profile (extended) ──────────────────────────────
$profile = null;
$stmt = $staffDb->prepare("SELECT * FROM staff_profiles WHERE staff_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Handle Profile Photo Upload ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = __DIR__ . '/../uploads/staff_profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file      = $_FILES['profile_picture'];
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp  = $file['tmp_name'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed    = ['jpg', 'jpeg', 'png'];
    $max_size   = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file_ext, $allowed)) {
        $_SESSION['error'] = "Invalid file type. Only JPG, JPEG & PNG allowed.";
    } elseif ($file_size > $max_size) {
        $_SESSION['error'] = "File too large. Maximum size is 5 MB.";
    } elseif ($file_tmp && is_uploaded_file($file_tmp)) {
        // Delete old photo if exists
        $old_photo = null;
        if ($profile && !empty($profile['profile_picture'])) {
            $old_photo = $profile['profile_picture'];
        }

        $new_name = 'staff_' . $staff_id . '_' . time() . '.' . $file_ext;
        $dest     = $upload_dir . $new_name;

        if (move_uploaded_file($file_tmp, $dest)) {
            $relative_path = 'uploads/staff_profiles/' . $new_name;

            // Upsert into staff_profiles
            if ($profile) {
                $u = $staffDb->prepare("UPDATE staff_profiles SET profile_picture = ?, updated_at = NOW() WHERE staff_id = ?");
                $u->bind_param("si", $relative_path, $staff_id);
                $u->execute();
                $u->close();
            } else {
                $i = $staffDb->prepare("INSERT INTO staff_profiles (staff_id, profile_picture, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $i->bind_param("is", $staff_id, $relative_path);
                $i->execute();
                $i->close();
            }

            // Delete old photo file
            if ($old_photo && file_exists(__DIR__ . '/../' . $old_photo) && $old_photo !== $relative_path) {
                @unlink(__DIR__ . '/../' . $old_photo);
            }

            // Refresh profile data
            $stmt = $staffDb->prepare("SELECT * FROM staff_profiles WHERE staff_id = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $_SESSION['success'] = "Profile picture updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to upload profile picture.";
        }
    } else {
        $_SESSION['error'] = "Upload failed. Please try again.";
    }

    header("Location: staff_profile_management.php");
    exit;
}

// ── Handle Profile Update ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position   = trim($_POST['position'] ?? '');

    if (empty($full_name)) {
        $_SESSION['error'] = "Full name is required.";
    } else {
        $u = $staffDb->prepare("UPDATE staff SET full_name=?, email=?, phone=?, address=?, department=?, position=? WHERE id=?");
        $u->bind_param("ssssssi", $full_name, $email, $phone, $address, $department, $position, $staff_id);
        if ($u->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";

            // Refresh staff data
            $stmt = $staffDb->prepare("SELECT * FROM staff WHERE id = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
        $u->close();
    }

    header("Location: staff_profile_management.php");
    exit;
}

// ── Derived values ──────────────────────────────────────────────
$photo_path = ($profile && !empty($profile['profile_picture']))
    ? '../' . htmlspecialchars($profile['profile_picture'])
    : null;
$full_name_val  = htmlspecialchars($staff['full_name'] ?? '');
$email_val      = htmlspecialchars($staff['email'] ?? '');
$phone_val      = htmlspecialchars($staff['phone'] ?? '');
$address_val    = htmlspecialchars($staff['address'] ?? '');
$department_val = htmlspecialchars($staff['department'] ?? '');
$position_val   = htmlspecialchars($staff['position'] ?? '');
$status_val     = htmlspecialchars($staff['status'] ?? '');
$staff_id_disp  = htmlspecialchars($staff['staff_id'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ISNM Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="dashboard-mobile.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a237e;
            --primary-light: #3949ab;
            --accent: #ffd600;
            --bg: #f0f2f5;
            --card-shadow: 0 2px 12px rgba(0,0,0,.08);
            --radius: 14px;
        }
        body {
            background: var(--bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .profile-page {
            margin-left: 270px;
            padding: 28px;
            min-height: 100vh;
        }
        .profile-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(148,163,184,.18);
            overflow: hidden;
        }
        .profile-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0d47a1 100%);
            color: #fff;
            padding: 20px 28px;
        }
        .profile-card-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.15rem;
        }
        .profile-card-body {
            padding: 28px;
        }
        .avatar-wrap {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
            margin: 0 auto 12px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-wrap .no-photo {
            font-size: 3.5rem;
            color: #adb5bd;
        }
        .photo-section {
            text-align: center;
            padding: 20px;
        }
        .photo-section .staff-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a237e;
            margin: 8px 0 2px;
        }
        .photo-section .staff-role {
            color: #6c757d;
            font-size: .9rem;
        }
        .photo-section .staff-dept {
            color: #6c757d;
            font-size: .85rem;
        }
        .photo-section .staff-status {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            margin-top: 6px;
        }
        .staff-status.active { background: #d4edda; color: #155724; }
        .staff-status.inactive { background: #f8d7da; color: #721c24; }
        .staff-status.on-leave { background: #fff3cd; color: #856404; }
        .staff-status.suspended { background: #f8d7da; color: #721c24; }
        .detail-label {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .detail-value {
            font-size: .95rem;
            color: #212529;
            margin-bottom: 14px;
        }
        .upload-area {
            border: 2px dashed #d0d5dd;
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: all .25s;
            background: #fafbfc;
        }
        .upload-area:hover {
            border-color: var(--primary-light);
            background: #f0f2ff;
        }
        .upload-area i {
            font-size: 2rem;
            color: var(--primary-light);
            margin-bottom: 8px;
        }
        .upload-area p {
            margin: 0;
            font-size: .85rem;
            color: #6c757d;
        }
        .upload-area .small {
            font-size: .75rem;
            color: #adb5bd;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 10px 14px;
            font-size: .9rem;
            transition: border-color .2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(57,73,171,.12);
        }
        .btn-save {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            border: none;
            padding: 10px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: .9rem;
            transition: transform .2s, box-shadow .2s;
        }
        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(26,35,126,.3);
            color: #fff;
        }
        .btn-outline-pdf {
            border: 2px solid #dc3545;
            color: #dc3545;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: .88rem;
            transition: all .2s;
        }
        .btn-outline-pdf:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn-outline-secondary-custom {
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: .88rem;
            transition: all .2s;
        }
        .btn-outline-secondary-custom:hover {
            background: #6c757d;
            color: #fff;
        }
        .alert-floating {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 320px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
            animation: slideInRight .4s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        @media (max-width: 992px) {
            .profile-page { margin-left: 0; padding: 20px 16px; }
            .avatar-wrap { width: 140px; height: 140px; }
        }
        @media print {
            body { background: #fff !important; }
            .profile-page { margin-left: 0 !important; padding: 20px !important; }
            .sidebar, .isnm-sidebar, .sidebar-collapse-btn, .btn-outline-pdf,
            .upload-area, .btn-save, .btn-outline-secondary-custom, .alert-floating,
            form, #sidebarCollapse, .sidebar-toggle, .sidebar-overlay { display: none !important; }
            .profile-card { box-shadow: none !important; border: 1px solid #ddd !important; }
            .profile-card-header { background: #1a237e !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color: #fff !important; }
            .avatar-wrap { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .staff-status.active { background: #d4edda !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .profile-page { position: static !important; }
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-floating">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show alert-floating">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="profile-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--primary);"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
            <p class="text-muted mb-0" style="font-size:.9rem;">Manage your personal information and profile picture</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-pdf">
                <i class="fas fa-file-pdf me-1"></i> Download My Info (PDF)
            </button>
            <a href="../index.php" class="btn btn-outline-secondary-custom">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- ═══ LEFT COLUMN: PHOTO ═══ -->
        <div class="col-lg-4">
            <div class="profile-card">
                <div class="profile-card-header">
                    <h4><i class="fas fa-camera me-2"></i>Profile Photo</h4>
                </div>
                <div class="profile-card-body">
                    <div class="photo-section">
                        <div class="avatar-wrap">
                            <?php if ($photo_path): ?>
                                <img src="<?= $photo_path ?>" alt="Profile Photo">
                            <?php else: ?>
                                <i class="fas fa-user no-photo"></i>
                            <?php endif; ?>
                        </div>
                        <div class="staff-name"><?= $full_name_val ?: 'Staff Member' ?></div>
                        <div class="staff-role"><i class="fas fa-briefcase me-1"></i><?= $position_val ?: 'N/A' ?></div>
                        <?php if ($department_val): ?>
                            <div class="staff-dept"><i class="fas fa-building me-1"></i><?= $department_val ?></div>
                        <?php endif; ?>
                        <div>
                            <span class="staff-status <?= strtolower(str_replace(' ', '-', $status_val)) ?>">
                                <?= $status_val ?: 'Active' ?>
                            </span>
                        </div>
                    </div>

                    <hr>

                    <form method="POST" enctype="multipart/form-data" id="photoForm">
                        <label class="upload-area" for="profile_picture">
                            <div>
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p><strong>Click to upload</strong> or drag and drop</p>
                                <p class="small">JPEG, PNG &bull; Max 5 MB</p>
                            </div>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png" class="d-none" onchange="document.getElementById('photoForm').submit();">
                        <button type="submit" class="btn btn-save w-100 mt-3">
                            <i class="fas fa-upload me-1"></i> Upload New Photo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══ RIGHT COLUMN: EDIT FORM ═══ -->
        <div class="col-lg-8">
            <div class="profile-card">
                <div class="profile-card-header">
                    <h4><i class="fas fa-edit me-2"></i>Personal Information</h4>
                </div>
                <div class="profile-card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Staff ID</label>
                                <input type="text" class="form-control" value="<?= $staff_id_disp ?: $staff_id ?>" readonly disabled style="background:#f8f9fa;cursor:not-allowed;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <input type="text" class="form-control" value="<?= $status_val ?: 'Active' ?>" readonly disabled style="background:#f8f9fa;cursor:not-allowed;">
                            </div>
                            <div class="col-md-6">
                                <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $full_name_val ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= $email_val ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= $phone_val ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control" id="department" name="department" value="<?= $department_val ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label fw-semibold">Position / Job Title</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?= $position_val ?>">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label fw-semibold">Physical Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= $address_val ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="reset" class="btn btn-outline-secondary-custom">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="submit" name="update_profile" class="btn btn-save">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Info Summary -->
            <div class="profile-card mt-4">
                <div class="profile-card-header">
                    <h4><i class="fas fa-info-circle me-2"></i>Account Summary</h4>
                </div>
                <div class="profile-card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?= $email_val ?: '<span class="text-muted fst-italic">Not set</span>' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value"><?= $phone_val ?: '<span class="text-muted fst-italic">Not set</span>' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Department</div>
                            <div class="detail-value"><?= $department_val ?: '<span class="text-muted fst-italic">Not set</span>' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Position</div>
                            <div class="detail-value"><?= $position_val ?: '<span class="text-muted fst-italic">Not set</span>' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?= $address_val ?: '<span class="text-muted fst-italic">Not set</span>' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Role</div>
                            <div class="detail-value"><?= htmlspecialchars($staff_role) ?: 'Staff' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
