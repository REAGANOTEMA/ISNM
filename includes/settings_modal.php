<?php
/**
 * Universal Settings Modal
 * Single gear-icon entry point for all settings across every dashboard.
 * Roles determine which settings cards are shown.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$s_role = $_SESSION['role'] ?? '';
$s_type = $_SESSION['type'] ?? '';
$is_student = $s_type === 'student';
$is_admin = in_array(strtolower($s_role), [
    'director general', 'director', 'ceo', 'school principal', 'principal',
    'academic registrar', 'admin', 'administrator', 'hr manager', 'hr'
]);
$is_bursar = in_array(strtolower($s_role), [
    'school bursar', 'bursar', 'finance', 'accountant',
    'director finance', 'payroll officer'
]);
$is_lecturer = in_array(strtolower($s_role), ['lecturers', 'lecturer', 'senior lecturers', 'senior lecturer', 'teacher']);
$is_head = in_array(strtolower($s_role), [
    'head nursing', 'head of nursing', 'head midwifery', 'head of midwifery',
    'deputy principal', 'director academics', 'director ict'
]);
?>
<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content settings-modal-content">
      <div class="modal-header settings-modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="settings-icon-wrap">
            <i class="fas fa-cog fa-spin"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white">Settings</h5>
            <small class="text-white-50">Manage your account and system preferences</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body settings-modal-body">
        <div class="settings-grid">

          <!-- Profile Settings – everyone -->
          <a href="#" class="settings-card" onclick="event.preventDefault();bootstrap.Modal.getInstance(document.getElementById('settingsModal'))?.hide();setTimeout(function(){if(typeof openProfileModal==='function')openProfileModal()},300)">
            <div class="sc-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Profile Settings</span>
              <span class="sc-desc">Update your name, photo, contact info</span>
            </div>
          </a>

          <!-- Change Password – everyone -->
          <a href="#" class="settings-card" onclick="event.preventDefault();bootstrap.Modal.getInstance(document.getElementById('settingsModal'))?.hide();if(typeof openChangePasswordModal==='function')openChangePasswordModal();">
            <div class="sc-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
              <i class="fas fa-key"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Change Password</span>
              <span class="sc-desc">Update your login password</span>
            </div>
          </a>

          <?php if (!$is_student): ?>
          <!-- System Settings – admin roles -->
          <?php if ($is_admin || $is_head): ?>
          <a href="../admin_panel/settings.php" class="settings-card">
            <div class="sc-icon" style="background:linear-gradient(135deg,#0ea5e9,#0369a1)">
              <i class="fas fa-sliders-h"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">System Settings</span>
              <span class="sc-desc">Configure system preferences and defaults</span>
            </div>
          </a>
          <?php endif; ?>

          <!-- HR Settings -->
          <?php if ($is_admin || strtolower($s_role) === 'hr manager'): ?>
          <a href="../dashboards/hr-manager.php" class="settings-card">
            <div class="sc-icon" style="background:linear-gradient(135deg,#f59e0b,#b45309)">
              <i class="fas fa-users-cog"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">HR Settings</span>
              <span class="sc-desc">Staff roles, departments, payroll config</span>
            </div>
          </a>
          <?php endif; ?>

          <!-- Bursar / Finance Settings -->
          <?php if ($is_bursar || $is_admin): ?>
          <a href="../dashboards/fee-structure.php" class="settings-card">
            <div class="sc-icon" style="background:linear-gradient(135deg,#14b8a6,#0d9488)">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Billing Config</span>
              <span class="sc-desc">Payment plans, discounts, deadlines</span>
            </div>
          </a>
          <?php endif; ?>

          <!-- Theme Settings – everyone -->
          <a href="#" class="settings-card" onclick="event.preventDefault();bootstrap.Modal.getInstance(document.getElementById('settingsModal'))?.hide();if(typeof openThemeModal==='function')openThemeModal();">
            <div class="sc-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
              <i class="fas fa-palette"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Theme Settings</span>
              <span class="sc-desc">Choose your dashboard color theme</span>
            </div>
          </a>

          <!-- Directory – everyone (staff) -->
          <a href="../student-directory.php" class="settings-card">
            <div class="sc-icon" style="background:linear-gradient(135deg,#6366f1,#4338ca)">
              <i class="fas fa-address-book"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Student Directory</span>
              <span class="sc-desc">Browse and manage all student records</span>
            </div>
          </a>

          <!-- Lecturer settings -->
          <?php if ($is_lecturer): ?>
          <a href="../teacher_panel/settings.php" class="settings-card">
            <div class="sc-icon" style="background:linear-gradient(135deg,#ec4899,#be185d)">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="sc-body">
              <span class="sc-title">Teaching Profile</span>
              <span class="sc-desc">Course assignments, class schedules</span>
            </div>
          </a>
          <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>
      <div class="modal-footer settings-modal-footer">
        <small class="text-muted">
          <i class="fas fa-shield-alt me-1"></i>Settings are role-specific
        </small>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
.settings-modal-content {
  border: none;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 25px 60px rgba(0,0,0,0.3);
}
.settings-modal-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border: none;
  padding: 24px 28px;
}
.settings-icon-wrap {
  width: 48px; height: 48px;
  background: rgba(255,255,255,0.1);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #94a3b8;
  border: 1px solid rgba(255,255,255,0.08);
}
.settings-modal-body {
  padding: 24px 28px 16px;
  background: #f8fafc;
}
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
}
.settings-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.2s ease;
  cursor: pointer;
}
.settings-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  transform: translateY(-1px);
}
.settings-card:active {
  transform: scale(0.98);
}
.sc-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: #fff;
  flex-shrink: 0;
}
.sc-body {
  display: flex; flex-direction: column;
  min-width: 0;
}
.sc-title {
  font-size: 14px; font-weight: 600;
  color: #0f172a;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sc-desc {
  font-size: 11.5px; color: #64748b;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.settings-modal-footer {
  border-top: 1px solid #e2e8f0;
  background: #fff;
  padding: 12px 28px;
}
@media (max-width: 576px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
  .settings-modal-body {
    padding: 16px;
  }
}
</style>
