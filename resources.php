<?php
$pageTitle = 'Resources | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$media = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT id, title, file_name, file_path, file_type, file_size, mime_type, caption FROM cms_media ORDER BY id DESC LIMIT 60");
    if ($result) {
      $media = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Resources page query failed: ' . $e->getMessage());
}

function isnm_format_bytes($bytes): string {
  $bytes = (int)$bytes;
  if ($bytes <= 0) return '';
  if ($bytes < 1024) return $bytes . ' B';
  if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
  return round($bytes / 1048576, 1) . ' MB';
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/hero7.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-download"></i> Downloads</div>
          <h1 class="page-title">Resources</h1>
          <p class="page-subtitle">Documents and media published by the school</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Resources</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-file-download"></i> Published Files</span>
        <h2 class="section-title">Downloads</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($media)): ?>
        <div class="row g-4">
          <?php foreach ($media as $item): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <div class="display-6 text-primary mb-2"><i class="fas fa-file-alt"></i></div>
                  <h5 class="card-title"><?= htmlspecialchars($item['title'] ?: $item['file_name']) ?></h5>
                  <?php if (!empty($item['caption'])): ?>
                    <p class="card-text text-muted"><?= htmlspecialchars($item['caption']) ?></p>
                  <?php endif; ?>
                  <p class="small text-muted mb-3">
                    <?= htmlspecialchars(strtoupper($item['file_type'])) ?>
                    <?php if (!empty($item['file_size'])): ?>
                      &middot; <?= htmlspecialchars(isnm_format_bytes($item['file_size'])) ?>
                    <?php endif; ?>
                  </p>
                  <a href="<?= htmlspecialchars($item['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" download>Download</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No resources have been published for download yet.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-link"></i> Quick Access</span>
        <h2 class="section-title">Useful Pages</h2>
        <div class="section-divider"></div>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <a href="application.php" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 text-center p-4">
              <div class="display-6 text-primary mb-2"><i class="fas fa-edit"></i></div>
              <h5 class="card-title text-dark">Application Form</h5>
              <p class="card-text text-muted mb-0">Start an application to study at ISNM.</p>
            </div>
          </a>
        </div>
        <div class="col-lg-4 col-md-6">
          <a href="programs.php" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 text-center p-4">
              <div class="display-6 text-primary mb-2"><i class="fas fa-graduation-cap"></i></div>
              <h5 class="card-title text-dark">Programs</h5>
              <p class="card-text text-muted mb-0">Review the current programs offered.</p>
            </div>
          </a>
        </div>
        <div class="col-lg-4 col-md-6">
          <a href="student-login.php" class="text-decoration-none">
            <div class="card h-100 shadow-sm border-0 text-center p-4">
              <div class="display-6 text-primary mb-2"><i class="fas fa-user-graduate"></i></div>
              <h5 class="card-title text-dark">Student Portal</h5>
              <p class="card-text text-muted mb-0">Access your student account and records.</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
