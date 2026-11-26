<?php
$pageTitle = 'Student Life | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$lifeCategories = [];
$events = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $catResult = $websiteDB->query("SELECT id, name, slug, description, cover_image FROM cms_gallery_categories WHERE is_active = 1 AND slug IN ('campus-life','sports-activities','events') ORDER BY sort_order, name");
    if ($catResult) {
      $lifeCategories = isnm_fetch_all($catResult);
    }
    $eventResult = $websiteDB->query("SELECT id, title, short_description, event_date, location, event_type FROM cms_events WHERE is_published = 1 AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 6");
    if ($eventResult) {
      $events = isnm_fetch_all($eventResult);
    }
  }
} catch (Throwable $e) {
  error_log('Student life page query failed: ' . $e->getMessage());
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/hero5.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-user-graduate"></i> Campus Experience</div>
          <h1 class="page-title">Student Life</h1>
          <p class="page-subtitle">Learning, service, and community beyond the classroom</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Student Life</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <span class="tag tag-primary"><i class="fas fa-heart"></i> Our Community</span>
          <h2 class="section-title">A place to learn and serve</h2>
          <div class="section-divider"></div>
          <p class="lead text-muted">
            Student life at ISNM is shaped by clinical practice, academic events, and service to the community.
            The collections and upcoming activities below are published directly from the school's records.
          </p>
          <a href="gallery.php" class="btn btn-primary">View the Gallery</a>
          <a href="events.php" class="btn btn-outline-primary">See All Events</a>
        </div>
        <div class="col-lg-6">
          <img src="images/students-hero.jpg" alt="ISNM students" class="img-fluid rounded-3 shadow">
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-images"></i> Published Collections</span>
        <h2 class="section-title">Campus &amp; Activities</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($lifeCategories)): ?>
        <div class="row g-4">
          <?php foreach ($lifeCategories as $category): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <?php if (!empty($category['cover_image'])): ?>
                  <img src="<?= htmlspecialchars($category['cover_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($category['name']) ?>" loading="lazy">
                <?php endif; ?>
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                  <p class="card-text text-muted"><?= htmlspecialchars($category['description'] ?? 'Browse the published images in this collection.') ?></p>
                  <a href="gallery.php" class="btn btn-sm btn-outline-primary">Open Gallery</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No student life collections have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-calendar-day"></i> On the Calendar</span>
        <h2 class="section-title">Upcoming Activities</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($events)): ?>
        <div class="row g-4">
          <?php foreach ($events as $event): ?>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                  <span class="tag tag-gold mb-2"><?= htmlspecialchars(date('M j, Y', strtotime($event['event_date']))) ?></span>
                  <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                  <p class="card-text text-muted"><?= htmlspecialchars($event['short_description'] ?? '') ?></p>
                  <?php if (!empty($event['location'])): ?>
                    <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($event['location']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No upcoming student activities have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
