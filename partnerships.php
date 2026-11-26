<?php
$pageTitle = 'Partnerships | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$partners = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT name, logo_url, website_url, description, partner_type FROM cms_partners WHERE is_active = 1 ORDER BY sort_order, name LIMIT 60");
    if ($result) {
      $partners = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Partnerships page query failed: ' . $e->getMessage());
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/hero6.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-handshake"></i> Working Together</div>
          <h1 class="page-title">Partnerships</h1>
          <p class="page-subtitle">Collaborators supporting healthcare education and community service</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Partnerships</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-7">
          <span class="tag tag-primary"><i class="fas fa-network-wired"></i> Collaboration</span>
          <h2 class="section-title">Partners in health education</h2>
          <div class="section-divider"></div>
          <p class="lead text-muted">
            ISNM works with healthcare facilities, academic institutions, and community organisations to
            strengthen practical training and service delivery. The partners listed below are maintained
            in the school's records.
          </p>
          <a href="contact.php" class="btn btn-primary">Become a Partner</a>
        </div>
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="display-4 text-primary mb-2"><i class="fas fa-hands-helping"></i></div>
              <h5 class="card-title">Work with us</h5>
              <p class="card-text text-muted mb-0">Use the contact page to reach the school administration about partnership opportunities.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-users"></i> Our Partners</span>
        <h2 class="section-title">Published Partners</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($partners)): ?>
        <div class="row g-4">
          <?php foreach ($partners as $partner): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                  <?php if (!empty($partner['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($partner['logo_url']) ?>" alt="<?= htmlspecialchars($partner['name']) ?> logo" class="img-fluid mb-3" style="max-height:80px;" loading="lazy">
                  <?php else: ?>
                    <div class="display-6 text-primary mb-3"><i class="fas fa-handshake"></i></div>
                  <?php endif; ?>
                  <h5 class="card-title"><?= htmlspecialchars($partner['name']) ?></h5>
                  <span class="badge bg-light text-dark mb-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $partner['partner_type']))) ?></span>
                  <?php if (!empty($partner['description'])): ?>
                    <p class="card-text text-muted"><?= htmlspecialchars($partner['description']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($partner['website_url'])): ?>
                    <a href="<?= htmlspecialchars($partner['website_url']) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Visit Website</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No partners have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
