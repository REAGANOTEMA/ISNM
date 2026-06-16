<?php
require_once __DIR__ . '/../includes/staff_dashboard_access.php';
require_once __DIR__ . '/../includes/module_coming_soon.php';
$ctx = bootstrapStaffDashboard(['librarian', 'library', 'admin']);
$user = $ctx['user'];
?><!DOCTYPE html>
<html lang="en">
<head>
<?php include_once __DIR__ . '/../includes/dashboard_head.php'; ?>
</head>
<body>
<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main" style="margin-left:270px;padding:40px 32px">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card dev-card">
        <div class="card-header text-white">
          <div class="d-flex align-items-center gap-3 mb-1">
            <i class="fas fa-book fa-3x"></i>
            <div>
              <h2 class="mb-1">Student Library</h2>
              <p class="mb-0 opacity-75">Manage library resources, book loans, returns, and student borrowing history</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <?php renderComingSoon('Student Library Access', 'fas fa-book-reader', [
    ['icon'=>'fas fa-book', 'label'=>'Book Catalog', 'note'=>'Browse available books'],
    ['icon'=>'fas fa-search', 'label'=>'Search', 'note'=>'Find resources'],
    ['icon'=>'fas fa-shopping-cart', 'label'=>'Borrow Requests', 'note'=>'Request books'],
    ['icon'=>'fas fa-history', 'label'=>'Borrow History', 'note'=>'Past borrowings'],
    ['icon'=>'fas fa-clock', 'label'=>'Due Dates', 'note'=>'Return reminders'],
    ['icon'=>'fas fa-download', 'label'=>'Digital Resources', 'note'=>'E-books & PDFs'],
], 'Under Development'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/dashboard_footer.php'; ?>
</body>
</html>
