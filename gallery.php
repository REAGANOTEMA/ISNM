<?php
$pageTitle = 'Gallery | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$galleryImages = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT gi.title, gi.caption, gi.image_url, gi.thumbnail_url, gi.alt_text, gc.name AS category_name FROM cms_gallery_images gi LEFT JOIN cms_gallery_categories gc ON gc.id = gi.category_id ORDER BY gi.sort_order, gi.id DESC LIMIT 24");
    if ($result) {
      $galleryImages = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Gallery page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/students-hero.jpg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Life at ISNM</span>
      <h1 class="isnm-premium-title">The story, in pictures.</h1>
      <p class="isnm-premium-lead">Classrooms, clinical practice, ceremony, and community: a visual record of the people who make ISNM what it is.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Gallery</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-intro">
      <div>
        <span class="isnm-premium-kicker">Our community</span>
        <h2 class="isnm-premium-heading">Moments worth remembering</h2>
        <p class="isnm-premium-copy">Every photograph here comes from school life in Iganga: students learning together, staff preparing the next generation of clinicians, and graduates stepping into service.</p>
        <p class="isnm-premium-copy">Use the gallery to explore our learning environment before you visit. For a closer look, arrange a visit through the contact page.</p>
      </div>
      <blockquote class="isnm-premium-quote">
        Learning is seen in the daily discipline of practice, not only in the final ceremony.
        <cite>Student Life at ISNM</cite>
      </blockquote>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Campus &amp; ceremony</span>
      <h2 class="isnm-premium-heading">Through the year</h2>
      <p class="isnm-premium-copy">A selection of scenes from the school, its facilities, and its celebrations.</p>
      <div class="isnm-premium-gallery">
        <figure><img src="images/students-in-class.jpg" alt="Nursing students attending class at ISNM" loading="lazy"><figcaption>Students in class</figcaption></figure>
        <figure><img src="images/skills-lab-s.jpeg" alt="Practical work in the ISNM skills laboratory" loading="lazy"><figcaption>Skills laboratory practice</figcaption></figure>
        <figure><img src="images/revision-library.jpg" alt="Students revising in the ISNM library" loading="lazy"><figcaption>Revision in the library</figcaption></figure>
        <figure><img src="images/diploma-hostel.jpg" alt="Student hostel accommodation at ISNM" loading="lazy"><figcaption>Student hostel</figcaption></figure>
        <figure><img src="images/school.JPG" alt="Iganga School of Nursing and Midwifery campus" loading="lazy"><figcaption>Our campus in Iganga</figcaption></figure>
        <figure><img src="images/staffs-meeting1.JPG" alt="ISNM staff in a meeting" loading="lazy"><figcaption>Staff collaboration</figcaption></figure>
        <figure><img src="images/certificates-graduates.jpg" alt="Graduates holding their certificates" loading="lazy"><figcaption>Graduation day</figcaption></figure>
        <figure><img src="images/graduates1.jpg" alt="ISNM graduates together after ceremony" loading="lazy"><figcaption>New healthcare professionals</figcaption></figure>
        <figure><img src="images/computer-students.jpeg" alt="Students using the computer laboratory" loading="lazy"><figcaption>Computer laboratory</figcaption></figure>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">From our records</span>
      <h2 class="isnm-premium-heading">Recently published photos</h2>
      <?php if (!empty($galleryImages)): ?>
        <div class="isnm-premium-gallery">
          <?php foreach ($galleryImages as $image): ?>
            <figure>
              <img src="<?php echo htmlspecialchars($image['thumbnail_url'] ?: $image['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?: $image['title'] ?: 'ISNM gallery image', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              <figcaption><?php echo htmlspecialchars($image['caption'] ?: $image['title'] ?: $image['category_name'] ?: 'ISNM community', ENT_QUOTES, 'UTF-8'); ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No additional photos have been published yet. Please check back as new collections are added.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Explore further</span>
      <h2 class="isnm-premium-heading">See the school in context</h2>
      <div class="isnm-premium-grid">
        <a class="isnm-premium-card" href="facilities.php"><span class="isnm-premium-card__icon"><i class="fas fa-building" aria-hidden="true"></i></span><h3>Facilities</h3><p>Tour the spaces where theory becomes practical skill.</p></a>
        <a class="isnm-premium-card" href="student-life.php"><span class="isnm-premium-card__icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span><h3>Student Life</h3><p>Discover hostel, welfare, clubs, and community life.</p></a>
        <a class="isnm-premium-card" href="events.php"><span class="isnm-premium-card__icon"><i class="fas fa-calendar-day" aria-hidden="true"></i></span><h3>Events</h3><p>Find the ceremonies and gatherings that bring us together.</p></a>
      </div>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Come and see for yourself</span>
      <h2 class="isnm-premium-heading">Visit Iganga School of Nursing and Midwifery</h2>
      <p class="isnm-premium-copy">A campus visit is the best way to meet our team and see our learning spaces. Arrange a time that works for you.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="contact.php"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Plan a Visit</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="admissions.php"><i class="fas fa-door-open" aria-hidden="true"></i> Admissions</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
