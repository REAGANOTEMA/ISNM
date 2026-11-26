<?php
$pageTitle = 'Events | Iganga School of Nursing and Midwifery';
include('shared/_header.php');

$upcoming = [];
$past = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT id, title, slug, short_description, event_date, end_date, event_time, location, event_type, image_url, registration_url FROM cms_events WHERE is_published = 1 ORDER BY event_date ASC LIMIT 60");
    if ($result) {
      $today = date('Y-m-d');
      foreach (isnm_fetch_all($result) as $event) {
        if ($event['event_date'] >= $today) {
          $upcoming[] = $event;
        } else {
          $past[] = $event;
        }
      }
      $past = array_reverse($past);
    }
  }
} catch (Throwable $e) {
  error_log('Events page query failed: ' . $e->getMessage());
}

function isnm_event_card(array $event): void {
  $date = date('M j, Y', strtotime($event['event_date']));
  ?>
  <div class="col-lg-4 col-md-6">
    <div class="card h-100 shadow-sm border-0">
      <?php if (!empty($event['image_url'])): ?>
        <img src="<?= htmlspecialchars($event['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($event['title']) ?>" loading="lazy">
      <?php endif; ?>
      <div class="card-body">
        <span class="tag tag-gold mb-2"><i class="fas fa-calendar-day me-1"></i><?= htmlspecialchars($date) ?></span>
        <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
        <?php if (!empty($event['short_description'])): ?>
          <p class="card-text text-muted"><?= htmlspecialchars($event['short_description']) ?></p>
        <?php endif; ?>
        <ul class="list-unstyled small text-muted mb-3">
          <?php if (!empty($event['location'])): ?>
            <li><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($event['location']) ?></li>
          <?php endif; ?>
          <?php if (!empty($event['event_time'])): ?>
            <li><i class="fas fa-clock me-2"></i><?= htmlspecialchars(date('g:i A', strtotime($event['event_time']))) ?></li>
          <?php endif; ?>
          <li><i class="fas fa-tag me-2"></i><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $event['event_type']))) ?></li>
        </ul>
        <?php if (!empty($event['registration_url'])): ?>
          <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($event['registration_url']) ?>" target="_blank" rel="noopener noreferrer">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php
}
?>

<main>
  <section class="page-header page-header-bg animate-on-scroll" style="background-image: url('images/hero3.jpg');">
    <div class="decor-shape decor-shape-1"></div>
    <div class="decor-shape decor-shape-2"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <div class="tag tag-gold mb-3"><i class="fas fa-calendar-day"></i> Stay Connected</div>
          <h1 class="page-title">Events</h1>
          <p class="page-subtitle">Academic ceremonies, workshops, and community activities at ISNM</p>
        </div>
      </div>
    </div>
  </section>

  <nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Events</li>
    </ol>
  </nav>

  <section class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-calendar-check"></i> Coming Up</span>
        <h2 class="section-title">Upcoming Events</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($upcoming)): ?>
        <div class="row g-4">
          <?php foreach ($upcoming as $event) { isnm_event_card($event); } ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">There are no published upcoming events at the moment.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <span class="tag tag-primary"><i class="fas fa-history"></i> Archive</span>
        <h2 class="section-title">Past Events</h2>
        <div class="section-divider"></div>
      </div>
      <?php if (!empty($past)): ?>
        <div class="row g-4">
          <?php foreach ($past as $event) { isnm_event_card($event); } ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">No past events have been published yet.</div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include('shared/_footer.php'); ?>
