<?php
$pageTitle = 'Gallery | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$categories = [];
$images = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $catResult = $websiteDB->query("SELECT id, name, slug, description, cover_image FROM cms_gallery_categories WHERE is_active = 1 ORDER BY sort_order, name");
    if ($catResult) {
      $categories = isnm_fetch_all($catResult);
    }
    $imgResult = $websiteDB->query("SELECT gi.id, gi.title, gi.caption, gi.image_url, gi.thumbnail_url, gi.alt_text, gc.name AS category_name FROM cms_gallery_images gi LEFT JOIN cms_gallery_categories gc ON gc.id = gi.category_id ORDER BY gi.sort_order, gi.id DESC LIMIT 24");
    if ($imgResult) {
      $images = isnm_fetch_all($imgResult);
    }
  }
} catch (Throwable $e) {
  error_log('Gallery page query failed: ' . $e->getMessage());
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/students-hero.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-images"></i> Life at ISNM</div>
          <h1 class="page-title">Gallery</h1>
          <p class="page-subtitle">Moments from our campus, clinical placements, and community activities</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Gallery</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-layer-group"></i> Collections</span>
        <h2 class="section-title">Browse by Collection</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($categories)): ?>
        <div class="row g-4">
          <?php foreach ($categories as $category): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <?php if (!empty($category['cover_image'])): ?>
                  <img src="<?= htmlspecialchars($category['cover_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($category['name']) ?>" loading="lazy">
                <?php endif; ?>
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                  <p class="card-text text-muted"><?= htmlspecialchars($category['description'] ?? 'Photos in this collection will appear here once published.') ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No gallery collections have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-camera"></i> Published Photos</span>
        <h2 class="section-title">Recent Images</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($images)): ?>
        <div class="row g-4">
          <?php foreach ($images as $image): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <img src="<?= htmlspecialchars($image['thumbnail_url'] ?: $image['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($image['alt_text'] ?: $image['title'] ?: 'ISNM gallery image') ?>" loading="lazy">
                <div class="card-body">
                  <?php if (!empty($image['category_name'])): ?>
                    <span class="tag tag-gold mb-2"><?= htmlspecialchars($image['category_name']) ?></span>
                  <?php endif; ?>
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
        <div class="alert alert-info">No gallery images have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
