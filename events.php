<?php
$pageTitle = 'Events | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$publishedEvents = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT title, short_description, event_date, event_time, location, event_type, registration_url FROM cms_events WHERE is_published = 1 ORDER BY event_date ASC LIMIT 60");
    if ($result) {
      $publishedEvents = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Events page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/graduates-hero3.jpg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Events &amp; Gatherings</span>
      <h1 class="isnm-premium-title">Where our community meets.</h1>
      <p class="isnm-premium-lead">Academic ceremonies, clinical workshops, and community programmes that bring learning to life at ISNM.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Events</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-split">
      <div>
        <span class="isnm-premium-kicker">The rhythm of the year</span>
        <h2 class="isnm-premium-heading">Learning beyond the timetable</h2>
        <p class="isnm-premium-copy">Our calendar follows the academic year and the needs of the communities we serve. Students take part in orientation, skills practice, clinical placement preparation, outreach, and the celebration of graduating cohorts.</p>
        <p class="isnm-premium-copy">Partners and visitors are welcome at public events. Contact the school for invitations, registration details, or to propose a joint activity.</p>
      </div>
      <div>
        <div class="isnm-premium-image-frame">
          <img src="images/certificates-graduates.jpg" alt="ISNM graduates holding certificates" loading="lazy">
        </div>
        <p class="isnm-premium-image-caption">Celebrating another cohort</p>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Recurring programme</span>
      <h2 class="isnm-premium-heading">A year at ISNM</h2>
      <p class="isnm-premium-copy">These gatherings form the steady rhythm of school life. Dates are confirmed through official school announcements.</p>
      <div class="isnm-premium-grid">
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">Orientation</span><h3>Welcome &amp; induction</h3><p>New students are introduced to the campus, programme expectations, student support, and professional conduct.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">Academics</span><h3>Skills &amp; revision weeks</h3><p>Focused practical sessions and guided revision help students prepare for assessments and clinical placement.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">Community</span><h3>Outreach activities</h3><p>Students and staff join health education and service activities with communities around Iganga.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">Ceremony</span><h3>Graduation</h3><p>Families, staff, and partners gather to celebrate graduates as they begin their professional service.</p></div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Official calendar</span>
      <h2 class="isnm-premium-heading">Published events</h2>
      <?php if (!empty($publishedEvents)): ?>
        <div class="isnm-premium-grid">
          <?php foreach ($publishedEvents as $event): ?>
            <article class="isnm-premium-card">
              <span class="isnm-premium-card__index"><?php echo htmlspecialchars(date('M j, Y', strtotime($event['event_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
              <h3><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($event['short_description'])): ?>
                <p><?php echo htmlspecialchars($event['short_description'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if (!empty($event['location'])): ?>
                <p style="margin-top:0.75rem"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($event['location'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if (!empty($event['event_type'])): ?>
                <p><i class="fas fa-tag" aria-hidden="true"></i> <?php echo htmlspecialchars(str_replace('_', ' ', $event['event_type']), ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if (!empty($event['registration_url'])): ?>
                <div class="isnm-premium-actions"><a class="isnm-premium-btn isnm-premium-btn--primary" href="<?php echo htmlspecialchars($event['registration_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Register</a></div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No events are published at the moment. Please check back, or contact the school to ask about upcoming activities.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--navy">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">An open invitation</span>
      <h2 class="isnm-premium-heading">Join us for a public event</h2>
      <p class="isnm-premium-copy isnm-premium-copy--light">Clinical partners, prospective students, guardians, and community organisations are welcome to enquire about attending or partnering with an ISNM event.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i> Contact the School</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="partnerships.php"><i class="fas fa-handshake" aria-hidden="true"></i> Partner With Us</a>
      </div>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Plan your next step</span>
      <h2 class="isnm-premium-heading">Turn interest into a career in care</h2>
      <p class="isnm-premium-copy">Speak to admissions about the next intake and how to join a coming cohort.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="application.php"><i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="admissions.php"><i class="fas fa-door-open" aria-hidden="true"></i> Admissions Guide</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
