<?php
$pageTitle = 'Clinical Training | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$sites = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT site_name, location, capacity, status FROM clinical_sites WHERE status = 'Active' ORDER BY site_name LIMIT 60");
    if ($result) {
      $sites = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Clinical training page query failed: ' . $e->getMessage());
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/graduates-hero2.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-stethoscope"></i> Practical Experience</div>
          <h1 class="page-title">Clinical Training</h1>
          <p class="page-subtitle">Hands-on learning in active healthcare placements</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Clinical Training</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-7">
          <span class="tag tag-primary"><i class="fas fa-user-nurse"></i> Training Approach</span>
          <h2 class="section-title">From classroom to patient care</h2>
          <div class="section-divider"></div>
          <p class="lead text-muted">
            Clinical training connects classroom learning with supervised practice in healthcare settings.
            The active placement sites listed below come from the school's clinical placement records.
          </p>
          <a href="programs.php" class="btn btn-primary">Explore Programs</a>
          <a href="contact.php" class="btn btn-outline-primary">Contact Us</a>
        </div>
        <div class="col-lg-5">
          <img src="images/classroom-photo-certificates-in-nurses-and-diploma.jpeg" alt="Nursing students in class" class="img-fluid rounded-3 shadow">
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-hospital-user"></i> Placement Sites</span>
        <h2 class="section-title">Active Clinical Sites</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($sites)): ?>
        <div class="table-responsive shadow-sm rounded-3 bg-white">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Site</th>
                <th>Location</th>
                <th>Capacity</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sites as $site): ?>
                <tr>
                  <td><i class="fas fa-hospital me-2 text-primary"></i><?= htmlspecialchars($site['site_name']) ?></td>
                  <td><?= htmlspecialchars($site['location'] ?? '—') ?></td>
                  <td><?= $site['capacity'] !== null ? (int)$site['capacity'] : '—' ?></td>
                  <td><span class="badge bg-success"><?= htmlspecialchars($site['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No active clinical sites are currently published.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
