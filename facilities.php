<?php
$pageTitle = 'Facilities | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$facilityImages = [];
$clinicalSites = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $imageResult = $websiteDB->query("SELECT gi.title, gi.caption, gi.image_url, gi.thumbnail_url, gi.alt_text FROM cms_gallery_images gi INNER JOIN cms_gallery_categories gc ON gc.id = gi.category_id WHERE gc.slug = 'facilities' ORDER BY gi.sort_order, gi.id DESC LIMIT 12");
    if ($imageResult) {
      $facilityImages = isnm_fetch_all($imageResult);
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
  <section class="isnm-premium-hero" style="background-image: url('images/skills-lab-s.jpeg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Learning Environment</span>
      <h1 class="isnm-premium-title">Spaces built for practice.</h1>
      <p class="isnm-premium-lead">Classrooms, laboratories, library, and clinical settings that turn nursing and midwifery theory into confident care.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Facilities</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-split">
      <div>
        <span class="isnm-premium-kicker">Purposeful spaces</span>
        <h2 class="isnm-premium-heading">Where theory becomes skill</h2>
        <p class="isnm-premium-copy">Our facilities support the whole learning cycle: listening and discussion in class, guided practice in the skills laboratory, independent study in the library, and supervised care in clinical placements.</p>
        <p class="isnm-premium-copy">Equipment and spaces are used as part of structured teaching, with students expected to follow safety, hygiene, and professional conduct standards at all times.</p>
        <div class="isnm-premium-actions"><a class="isnm-premium-btn isnm-premium-btn--primary" href="clinical-training.php"><i class="fas fa-stethoscope" aria-hidden="true"></i> Explore Clinical Training</a></div>
      </div>
      <div>
        <div class="isnm-premium-image-frame"><img src="images/classroom-photo-certificates-in-nurses-and-diploma.jpeg" alt="Students learning in an ISNM classroom" loading="lazy"></div>
        <p class="isnm-premium-image-caption">Classroom learning</p>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">On campus</span>
      <h2 class="isnm-premium-heading">Our learning facilities</h2>
      <div class="isnm-premium-grid">
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></span><h3>Classrooms</h3><p>Teaching spaces for nursing and midwifery theory, group discussion, case review, and assessment preparation.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-stethoscope" aria-hidden="true"></i></span><h3>Skills laboratory</h3><p>Practical sessions allow students to rehearse clinical procedures before supervised work with patients.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-book" aria-hidden="true"></i></span><h3>Library &amp; revision</h3><p>A quiet place for reading, revision, and research that supports both coursework and professional development.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-desktop" aria-hidden="true"></i></span><h3>Computer lab</h3><p>Access to digital learning and record-keeping practice, supporting documentation skills used in modern healthcare.</p></div>
      </div>
      <div class="isnm-premium-gallery" style="margin-top:3rem">
        <figure><img src="images/skills-lab-nurses.jpeg" alt="Nursing students practising in the skills lab" loading="lazy"><figcaption>Skills practice</figcaption></figure>
        <figure><img src="images/revision-library.jpg" alt="Students revising in the library" loading="lazy"><figcaption>Library revision</figcaption></figure>
        <figure><img src="images/computer-students.jpeg" alt="Students using computers at ISNM" loading="lazy"><figcaption>Digital learning</figcaption></figure>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Approved placements</span>
      <h2 class="isnm-premium-heading">Active clinical sites</h2>
      <p class="isnm-premium-copy">The following sites are currently recorded as active placement partners. Placement availability changes with each cohort and programme.</p>
      <?php if (!empty($clinicalSites)): ?>
        <div class="isnm-premium-grid">
          <?php foreach ($clinicalSites as $site): ?>
            <article class="isnm-premium-card">
              <span class="isnm-premium-card__icon"><i class="fas fa-hospital" aria-hidden="true"></i></span>
              <h3><?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($site['location'])): ?><p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($site['location'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
              <?php if (!empty($site['capacity'])): ?><p style="margin-top:0.6rem">Placement capacity: <?php echo (int)$site['capacity']; ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No active clinical sites are currently published. Contact the clinical placement office for current information.</div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($facilityImages)): ?>
    <section class="isnm-premium-section isnm-premium-section--tint">
      <div class="isnm-premium-container">
        <span class="isnm-premium-kicker">Photo record</span>
        <h2 class="isnm-premium-heading">Published facility photos</h2>
        <div class="isnm-premium-gallery">
          <?php foreach ($facilityImages as $image): ?>
            <figure>
              <img src="<?php echo htmlspecialchars($image['thumbnail_url'] ?: $image['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?: $image['title'] ?: 'ISNM facility', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              <figcaption><?php echo htmlspecialchars($image['caption'] ?: $image['title'] ?: 'ISNM facility', ENT_QUOTES, 'UTF-8'); ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">See the campus</span>
      <h2 class="isnm-premium-heading">Visit our learning spaces</h2>
      <p class="isnm-premium-copy">Meet the team, view the skills laboratory, and ask how our facilities support your chosen programme.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i> Arrange a Visit</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="application.php"><i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
