<?php
$pageTitle = 'Facilities | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$facilityImages = [];
$clinicalSites = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $imgResult = $websiteDB->query("SELECT gi.id, gi.title, gi.caption, gi.image_url, gi.thumbnail_url, gi.alt_text FROM cms_gallery_images gi INNER JOIN cms_gallery_categories gc ON gc.id = gi.category_id WHERE gc.slug = 'facilities' ORDER BY gi.sort_order, gi.id DESC LIMIT 12");
    if ($imgResult) {
      $facilityImages = isnm_fetch_all($imgResult);
    }
    $siteResult = $websiteDB->query("SELECT site_name, location, capacity, status FROM clinical_sites WHERE status = 'Active' ORDER BY site_name LIMIT 30");
    if ($siteResult) {
      $clinicalSites = isnm_fetch_all($siteResult);
    }
  }
} catch (Throwable $e) {
  error_log('Facilities page query failed: ' . $e->getMessage());
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/hero2.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-building"></i> Learning Environment</div>
          <h1 class="page-title">Facilities</h1>
          <p class="page-subtitle">Spaces and clinical placements that support nursing and midwifery training</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Facilities</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-camera"></i> Campus Facilities</span>
        <h2 class="section-title">Published Photos</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($facilityImages)): ?>
        <div class="row g-4">
          <?php foreach ($facilityImages as $image): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <img src="<?= htmlspecialchars($image['thumbnail_url'] ?: $image['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($image['alt_text'] ?: $image['title'] ?: 'ISNM facility') ?>" loading="lazy">
                <div class="card-body">
                  <?php if (!empty($image['title'])): ?>
                    <h5 class="card-title"><?= htmlspecialchars($image['title']) ?></h5>
                  <?php endif; ?>
                  <?php if (!empty($image['caption'])): ?>
                    <p class="card-text text-muted mb-0"><?= htmlspecialchars($image['caption']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No facility photos have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-hospital"></i> Clinical Placements</span>
        <h2 class="section-title">Active Clinical Sites</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($clinicalSites)): ?>
        <div class="row g-4">
          <?php foreach ($clinicalSites as $site): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <h5 class="card-title"><i class="fas fa-hospital me-2 text-primary"></i><?= htmlspecialchars($site['site_name']) ?></h5>
                  <?php if (!empty($site['location'])): ?>
                    <p class="card-text text-muted mb-2"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($site['location']) ?></p>
                  <?php endif; ?>
                  <span class="badge bg-success">Active</span>
                  <?php if (!empty($site['capacity'])): ?>
                    <span class="badge bg-light text-dark">Capacity: <?= (int)$site['capacity'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No active clinical sites are currently published.</div>
      <?php endif; ?>
      <div class="text-center mt-4">
        <a href="clinical-training.php" class="btn btn-outline-primary">Explore Clinical Training</a>
      </div>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
