<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
$ctx = bootstrapStaffDashboard([]);
$staffDb = $ctx['staff'];
$studentsDb = $ctx['students'];
$websiteDb = $ctx['website'];
$user = $ctx['user'];
$userRole = $user['role'] ?? '';
$userName = $user['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/_favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Statements - ISNM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
.card-section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.page-header { border-bottom: 2px solid #e9ecef; padding-bottom: 12px; margin-bottom: 20px; }
.coming-soon-icon { font-size: 48px; color: #0d6efd; opacity: .6; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:270px;padding:20px;background:#f0f2f5;min-height:100vh;">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Student Statements</h4>
            <span class="text-muted small"><?= date('l, d M Y') ?></span>
        </div>

        <div class="card-section text-center py-5 mb-4">
            <div class="coming-soon-icon mb-3"><i class="fas fa-file-alt"></i></div>
            <h5>Student Statements</h5>
            <p class="text-muted">This module is under development. Individual student fee statements, balance summaries, payment history, and printable statements coming soon.</p>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Coming Soon</span>
        </div>

        <div class="card-section">
            <h5 class="fw-bold mb-3"><i class="fas fa-search me-2"></i>Search Student Statement</h5>
            <form class="row g-3" onsubmit="event.preventDefault();">
                <div class="col-md-4">
                    <label class="form-label">Student Name / ID</label>
                    <input type="text" class="form-control" placeholder="Enter student name or ID...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Program</label>
                    <select class="form-select">
                        <option value="">All Programs</option>
                        <option>Diploma in Nursing</option>
                        <option>Diploma in Midwifery</option>
                        <option>Certificate in Nursing</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <select class="form-select">
                        <option value="">All Years</option>
                        <option>2025</option>
                        <option>2026</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" disabled><i class="fas fa-search me-1"></i>Search</button>
                </div>
            </form>
            <p class="text-muted small mt-3 mb-0"><i class="fas fa-info-circle me-1"></i>Search functionality will be enabled once the module is fully developed.</p>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
