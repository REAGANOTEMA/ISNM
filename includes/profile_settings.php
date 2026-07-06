<?php
if (defined('PROFILE_SETTINGS_LOADED')) return;
define('PROFILE_SETTINGS_LOADED', true);

// ── AJAX: Upload profile image ────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'upload_profile_image') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => ''];
    try {
        require_once __DIR__ . '/../config/database.php';
        $staffDb = null;
        if (function_exists('getDatabaseConnection')) {
            $staffDb = getDatabaseConnection('staffs');
        } elseif (class_exists('DatabaseManager') && method_exists('DatabaseManager', 'getStaffConnection')) {
            $staffDb = DatabaseManager::getStaffConnection();
        } elseif (function_exists('getStaffConnection')) {
            $staffDb = getStaffConnection();
        }
        if (!$staffDb) { throw new Exception('Database connection failed'); }

        $staffId = (int)($_POST['staff_id'] ?? 0);
        if ($staffId <= 0) { throw new Exception('Invalid staff ID'); }

        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $file = $_FILES['profile_image'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) { throw new Exception('File too large. Max 2MB'); }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedTypes)) { throw new Exception('Only JPG, PNG, GIF, WebP allowed'); }

        switch ($mime) {
            case 'image/jpeg': $ext = 'jpg'; break;
            case 'image/png': $ext = 'png'; break;
            case 'image/gif': $ext = 'gif'; break;
            case 'image/webp': $ext = 'webp'; break;
            default: $ext = 'jpg';
        }
        $filename = 'staff_' . $staffId . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new Exception('Failed to save file');
        }

        $relativePath = 'uploads/profiles/' . $filename;

        // Create or update staff_profiles record
        $check = $staffDb->prepare("SELECT id FROM staff_profiles WHERE staff_id = ?");
        $check->bind_param('i', $staffId);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            // Delete old photo
            $oldQuery = $staffDb->prepare("SELECT profile_picture FROM staff_profiles WHERE staff_id = ?");
            $oldQuery->bind_param('i', $staffId);
            $oldQuery->execute();
            $oldRow = $oldQuery->get_result()->fetch_assoc();
            $oldQuery->close();
            if ($oldRow && $oldRow['profile_picture']) {
                $oldFile = __DIR__ . '/../' . $oldRow['profile_picture'];
                if (file_exists($oldFile)) @unlink($oldFile);
            }
            $upd = $staffDb->prepare("UPDATE staff_profiles SET profile_picture = ? WHERE staff_id = ?");
            $upd->bind_param('si', $relativePath, $staffId);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $staffDb->prepare("INSERT INTO staff_profiles (staff_id, profile_picture) VALUES (?, ?)");
            $ins->bind_param('is', $staffId, $relativePath);
            $ins->execute();
            $ins->close();
        }

        // Also update staff.profile_photo for backward compatibility
        $updStaff = $staffDb->prepare("UPDATE staff SET profile_photo = ? WHERE id = ?");
        $updStaff->bind_param('si', $relativePath, $staffId);
        $updStaff->execute();
        $updStaff->close();

        $response = ['success' => true, 'path' => '../' . $relativePath, 'error' => ''];
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

if (!function_exists('getStaffProfileImageUrl')) {
    function getStaffProfileImageUrl($staffId) {
        static $urls = [];
        if (isset($urls[$staffId])) return $urls[$staffId];
        try {
            require_once __DIR__ . '/../config/database.php';
            $staffDb = null;
            if (function_exists('getDatabaseConnection')) {
                $staffDb = getDatabaseConnection('staffs');
            } elseif (class_exists('DatabaseManager') && method_exists('DatabaseManager', 'getStaffConnection')) {
                $staffDb = DatabaseManager::getStaffConnection();
            } elseif (function_exists('getStaffConnection')) {
                $staffDb = getStaffConnection();
            }
            if ($staffDb) {
                $s = $staffDb->prepare("SELECT profile_picture FROM staff_profiles WHERE staff_id = ?");
                if ($s) {
                    $s->bind_param('i', $staffId);
                    $s->execute();
                    $row = $s->get_result()->fetch_assoc();
                    $s->close();
                    if ($row && $row['profile_picture']) {
                        $path = '../' . $row['profile_picture'];
                        $fullPath = __DIR__ . '/../' . $row['profile_picture'];
                        if (file_exists($fullPath)) {
                            $urls[$staffId] = $path;
                            return $path;
                        }
                    }
                }
                // Fallback to staff.profile_photo
                $s2 = $staffDb->prepare("SELECT profile_photo FROM staff WHERE id = ?");
                if ($s2) {
                    $s2->bind_param('i', $staffId);
                    $s2->execute();
                    $row2 = $s2->get_result()->fetch_assoc();
                    $s2->close();
                    if ($row2 && $row2['profile_photo']) {
                        $path = '../' . $row2['profile_photo'];
                        $fullPath = __DIR__ . '/../' . $row2['profile_photo'];
                        if (file_exists($fullPath)) {
                            $urls[$staffId] = $path;
                            return $path;
                        }
                    }
                }
            }
        } catch (Exception $e) { error_log('profile_settings load: ' . $e->getMessage()); }
        $urls[$staffId] = '../images/username.png';
        return '../images/username.png';
    }
}

// ── Fetch staff data for profile form ─────────────────────────
function getStaffProfileData($staffId) {
    $data = [
        'first_name' => '', 'surname' => '', 'other_names' => '',
        'email' => '', 'phone' => '', 'department' => '', 'bio' => ''
    ];
    try {
        $staffDb = null;
        if (function_exists('getDatabaseConnection')) {
            $staffDb = getDatabaseConnection('staffs');
        } elseif (class_exists('DatabaseManager') && method_exists('DatabaseManager', 'getStaffConnection')) {
            $staffDb = DatabaseManager::getStaffConnection();
        } elseif (function_exists('getStaffConnection')) {
            $staffDb = getStaffConnection();
        }
        if ($staffDb) {
            $s = $staffDb->prepare("SELECT full_name, email, phone, department FROM staff WHERE id = ?");
            if ($s) {
                $s->bind_param('i', $staffId);
                $s->execute();
                $row = $s->get_result()->fetch_assoc();
                $s->close();
                if ($row) {
                    // Parse full_name into first_name/surname/other_names
                    $parts = explode(' ', trim($row['full_name'] ?? ''), 3);
                    $data['first_name'] = $parts[0] ?? '';
                    $data['surname'] = count($parts) >= 2 ? $parts[count($parts) === 2 ? 1 : 1] : '';
                    $data['other_names'] = count($parts) === 3 ? $parts[2] : '';
                    $data['email'] = $row['email'] ?? '';
                    $data['phone'] = $row['phone'] ?? '';
                    $data['department'] = $row['department'] ?? '';
                }
            }
            // Also try staff_profiles for bio
            $sp = $staffDb->prepare("SELECT bio FROM staff_profiles WHERE staff_id = ?");
            if ($sp) {
                $sp->bind_param('i', $staffId);
                $sp->execute();
                $row2 = $sp->get_result()->fetch_assoc();
                $sp->close();
                if ($row2 && !empty($row2['bio'])) {
                    $data['bio'] = $row2['bio'];
                }
            }
        }
    } catch (Exception $e) { error_log('profile_settings save: ' . $e->getMessage()); }
    return $data;
}

// ── AJAX: Save profile fields ────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'save_profile_fields') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => ''];
    try {
        require_once __DIR__ . '/../config/database.php';
        $staffDb = null;
        if (function_exists('getDatabaseConnection')) {
            $staffDb = getDatabaseConnection('staffs');
        } elseif (class_exists('DatabaseManager') && method_exists('DatabaseManager', 'getStaffConnection')) {
            $staffDb = DatabaseManager::getStaffConnection();
        } elseif (function_exists('getStaffConnection')) {
            $staffDb = getStaffConnection();
        }
        if (!$staffDb) { throw new Exception('Database connection failed'); }

        $staffId = (int)($_POST['staff_id'] ?? 0);
        if ($staffId <= 0) { throw new Exception('Invalid staff ID'); }

        $first_name = trim($_POST['first_name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $other_names = trim($_POST['other_names'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        // Build full_name from components
        $full_name = trim($first_name . ' ' . $surname . ($other_names ? ' ' . $other_names : ''));

        // Update staff table
        $upd = $staffDb->prepare("UPDATE staff SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        if (!$upd) { throw new Exception('Prepare failed: ' . $staffDb->error); }
        $upd->bind_param('sssi', $full_name, $email, $phone, $staffId);
        if (!$upd->execute()) { throw new Exception('Update failed: ' . $upd->error); }
        $upd->close();

        // Update staff_profiles (bio)
        $check = $staffDb->prepare("SELECT id FROM staff_profiles WHERE staff_id = ?");
        $check->bind_param('i', $staffId);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();
        if ($exists) {
            $upd2 = $staffDb->prepare("UPDATE staff_profiles SET bio = ? WHERE staff_id = ?");
            $upd2->bind_param('si', $bio, $staffId);
            $upd2->execute();
            $upd2->close();
        } else {
            $ins = $staffDb->prepare("INSERT INTO staff_profiles (staff_id, bio) VALUES (?, ?)");
            $ins->bind_param('is', $staffId, $bio);
            $ins->execute();
            $ins->close();
        }

        // Update session
        $_SESSION['first_name'] = $first_name;
        $_SESSION['surname'] = $surname;
        $_SESSION['full_name'] = $first_name . ' ' . $surname . ($other_names ? ' ' . $other_names : '');
        if ($email) $_SESSION['email'] = $email;

        $response = ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

// ── Render profile settings modal ─────────────────────────────
function renderProfileModal() {
    $staffId = (int)($_SESSION['user_id'] ?? 0);
    $imgUrl = $staffId ? getStaffProfileImageUrl($staffId) : '../images/username.png';
    $name = $_SESSION['full_name'] ?? $_SESSION['first_name'] ?? 'Staff';
    $role = $_SESSION['role'] ?? '';
    $email = $_SESSION['email'] ?? $_SESSION['staff_email'] ?? '';
    $profile = getStaffProfileData($staffId);
    ?>
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content profile-modal-content">
                <div class="modal-header profile-modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-cog me-2"></i>Profile Settings</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-4">
                        <!-- Photo Column -->
                        <div class="col-md-4 text-center">
                            <div class="profile-avatar-section mb-3">
                                <div class="profile-avatar-wrap position-relative d-inline-block">
                                    <img src="<?= $imgUrl ?>" alt="" class="profile-modal-avatar rounded-circle" id="profileModalAvatar">
                                    <label for="profileImageInput" class="profile-avatar-overlay" title="Change Photo">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <input type="file" id="profileImageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                                <div id="profileUploadPreview" class="mt-2" style="display:none">
                                    <small class="text-muted">Preview:</small>
                                    <img id="profilePreviewImg" class="rounded-circle mt-1" style="width:70px;height:70px;object-fit:cover;border:2px solid #e2e8f0">
                                </div>
                                <div id="profileUploadStatus" class="mt-2 small"></div>
                            </div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($name) ?></h5>
                            <p class="text-muted mb-2"><i class="fas fa-briefcase me-1"></i><?= htmlspecialchars($role) ?></p>
                            <div class="text-start small text-muted mt-3">
                                <p class="mb-1"><i class="fas fa-info-circle me-1"></i>Upload a professional photo.</p>
                                <p class="mb-0"><i class="fas fa-image me-1"></i>JPG, PNG, GIF, WebP (max 2MB)</p>
                            </div>
                        </div>
                        <!-- Form Column -->
                        <div class="col-md-8">
                            <div class="profile-form-section">
                                <h6 class="fw-bold mb-3" style="color:#1a237e"><i class="fas fa-address-card me-2"></i>Personal Information</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">First Name</label>
                                        <input type="text" class="form-control form-control-sm" id="pf_first_name" value="<?= htmlspecialchars($profile['first_name']) ?>" placeholder="First name">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Surname</label>
                                        <input type="text" class="form-control form-control-sm" id="pf_surname" value="<?= htmlspecialchars($profile['surname']) ?>" placeholder="Surname">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Other Names</label>
                                        <input type="text" class="form-control form-control-sm" id="pf_other_names" value="<?= htmlspecialchars($profile['other_names']) ?>" placeholder="Other names">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Phone Number</label>
                                        <input type="text" class="form-control form-control-sm" id="pf_phone" value="<?= htmlspecialchars($profile['phone']) ?>" placeholder="+256 XXX XXX XXX">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Email Address</label>
                                        <input type="email" class="form-control form-control-sm" id="pf_email" value="<?= htmlspecialchars($profile['email']) ?>" placeholder="email@example.com">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Department</label>
                                        <input type="text" class="form-control form-control-sm" id="pf_department" value="<?= htmlspecialchars($profile['department']) ?>" placeholder="Department" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-medium">Bio / About</label>
                                        <textarea class="form-control form-control-sm" id="pf_bio" rows="3" placeholder="Brief description about yourself..."><?= htmlspecialchars($profile['bio']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between border-0 pt-0 px-4 pb-4">
                    <div id="profileSaveStatus" class="small"></div>
                    <div>
                        <button type="button" class="btn btn-secondary px-3 me-2" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary px-4" id="profilePhotoSaveBtn" onclick="uploadProfileImage()" disabled>
                            <i class="fas fa-upload me-1"></i>Save Photo
                        </button>
                        <button type="button" class="btn btn-primary px-4" id="profileInfoSaveBtn" onclick="saveProfileFields()">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }

// ── Styles ────────────────────────────────────────────────────
function renderProfileStyles() { ?>
<style>
.profile-modal-content { border: none; border-radius: 20px; overflow: hidden; }
.profile-modal-header {
    background: linear-gradient(135deg, #1a237e, #283593); color: #fff;
    border-bottom: none; padding: 1.25rem 1.5rem;
}
.profile-modal-avatar { width: 120px; height: 120px; object-fit: cover; border: 4px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.profile-avatar-wrap:hover .profile-avatar-overlay { opacity: 1; }
.profile-avatar-overlay {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(0,0,0,.5); color: #fff; display: flex;
    align-items: center; justify-content: center; font-size: 1.5rem;
    cursor: pointer; opacity: 0; transition: opacity .25s ease;
    border: 4px solid transparent;
}
#profileUploadStatus .spinner-border, #profileSaveStatus .spinner-border { width: 1rem; height: 1rem; }
.profile-form-section {
    background: #f8fafc;
    border-radius: 14px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
}
.profile-form-section .form-label {
    color: #475569;
    margin-bottom: 4px;
}
.profile-form-section .form-control-sm {
    border-radius: 8px;
    border-color: #d1d5db;
}
.profile-form-section .form-control-sm:focus {
    border-color: #1a237e;
    box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
}
</style>
<?php }

// ── Scripts ───────────────────────────────────────────────────
function renderProfileScripts() { ?>
<script>
(function(){
    var staffId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

    // Image input change → preview
    var input = document.getElementById('profileImageInput');
    var preview = document.getElementById('profileUploadPreview');
    var previewImg = document.getElementById('profilePreviewImg');
    var saveBtn = document.getElementById('profilePhotoSaveBtn');
    var status = document.getElementById('profileUploadStatus');
    var modalAvatar = document.getElementById('profileModalAvatar');

    if (input) {
        input.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) { preview.style.display = 'none'; saveBtn.disabled = true; return; }
            if (file.size > 2 * 1024 * 1024) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>File too large (max 2MB)</span>';
                preview.style.display = 'none'; saveBtn.disabled = true; return;
            }
            var reader = new FileReader();
            reader.onload = function(ev) {
                previewImg.src = ev.target.result;
                preview.style.display = 'block';
                status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Ready to upload</span>';
                saveBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        });
    }

    window.uploadProfileImage = function() {
        var file = input ? input.files[0] : null;
        if (!file || !staffId) { return; }
        var formData = new FormData();
        formData.append('action', 'upload_profile_image');
        formData.append('staff_id', staffId);
        formData.append('profile_image', file);

        saveBtn.disabled = true;
        status.innerHTML = '<span><span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading...</span>';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../includes/profile_settings.php', true);
        xhr.onload = function() {
            if (xhr.status !== 200) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Upload failed</span>';
                saveBtn.disabled = false;
                return;
            }
            try {
                var d = JSON.parse(xhr.responseText);
                if (d.success) {
                    status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Uploaded successfully!</span>';
                    modalAvatar.src = d.path + '?v=' + Date.now();
                    // Also update sidebar avatar if it exists
                    var sidebarAvatar = document.querySelector('.user-avatar');
                    if (sidebarAvatar) sidebarAvatar.src = d.path.replace('../', '') + '?v=' + Date.now();
                    input.value = '';
                    preview.style.display = 'none';
                    setTimeout(function() {
                        var modalEl = document.getElementById('profileModal');
                        if (modalEl) { var bsModal = bootstrap.Modal.getInstance(modalEl); if (bsModal) bsModal.hide(); }
                        showProfileToast('Profile photo updated', 'success');
                    }, 800);
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + escHtml(d.error || 'Error') + '</span>';
                    saveBtn.disabled = false;
                }
            } catch(e) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Invalid response</span>';
                saveBtn.disabled = false;
            }
        };
        xhr.onerror = function() {
            status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Network error</span>';
            saveBtn.disabled = false;
        };
        xhr.send(formData);
    };

    function showProfileToast(msg, type) {
        var c = document.createElement('div');
        c.className = 'position-fixed bottom-0 end-0 p-3'; c.style.zIndex = '9999';
        c.innerHTML = '<div class="toast align-items-center text-bg-' + type + ' border-0" role="alert"><div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
        document.body.appendChild(c);
        var t = new bootstrap.Toast(c.querySelector('.toast')); t.show();
        setTimeout(function() { c.remove(); }, 4000);
    }

    function escHtml(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // ── Save profile fields ──
    window.saveProfileFields = function() {
        var saveBtn = document.getElementById('profileInfoSaveBtn');
        var status = document.getElementById('profileSaveStatus');
        if (!saveBtn) return;
        saveBtn.disabled = true;
        status.innerHTML = '<span class="text-info"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>';

        var data = new URLSearchParams();
        data.append('action', 'save_profile_fields');
        data.append('staff_id', staffId);
        data.append('first_name', (document.getElementById('pf_first_name') || {}).value || '');
        data.append('surname', (document.getElementById('pf_surname') || {}).value || '');
        data.append('other_names', (document.getElementById('pf_other_names') || {}).value || '');
        data.append('phone', (document.getElementById('pf_phone') || {}).value || '');
        data.append('email', (document.getElementById('pf_email') || {}).value || '');
        data.append('bio', (document.getElementById('pf_bio') || {}).value || '');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../includes/profile_settings.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            saveBtn.disabled = false;
            if (xhr.status !== 200) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Save failed</span>';
                return;
            }
            try {
                var d = JSON.parse(xhr.responseText);
                if (d.success) {
                    status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Saved successfully!</span>';
                    // Update sidebar username
                    var sidebarName = document.querySelector('.user-fullname');
                    if (sidebarName) {
                        var fn = data.get('first_name');
                        var sn = data.get('surname');
                        var on = data.get('other_names');
                        sidebarName.textContent = fn + ' ' + sn + (on ? ' ' + on : '');
                    }
                    setTimeout(function() {
                        status.innerHTML = '';
                    }, 3000);
                } else {
                    status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + escHtml(d.error || 'Error') + '</span>';
                }
            } catch(e) {
                status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Invalid response</span>';
            }
        };
        xhr.onerror = function() {
            saveBtn.disabled = false;
            status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Network error</span>';
        };
        xhr.send(data);
    };
})();

window.openProfileModal = function() {
    var el = document.getElementById('profileModal');
    if (el) { var modal = new bootstrap.Modal(el); modal.show(); }
};
</script>
<?php }
