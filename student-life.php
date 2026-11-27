<?php
$pageTitle = 'Student Life | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$studentActivities = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT title, short_description, event_date, location FROM cms_events WHERE is_published = 1 AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 6");
    if ($result) {
      $studentActivities = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Student life page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/diploma-hostel.jpg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Campus Experience</span>
      <h1 class="isnm-premium-title">Life together, learning together.</h1>
      <p class="isnm-premium-lead">A disciplined but welcoming community where students grow in skill, friendship, faith, and service.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Student Life</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-intro">
      <div>
        <span class="isnm-premium-kicker">Belonging at ISNM</span>
        <h2 class="isnm-premium-heading">More than a place to study</h2>
        <p class="isnm-premium-copy">Student life is shaped by early mornings in class, long hours in skills practice, shared meals, and the encouragement of a community that knows your name. The school supports students academically and personally so they can stay focused on the work ahead.</p>
        <p class="isnm-premium-copy">Hostel information, welfare support, and student expectations are shared during orientation and through the student portal.</p>
      </div>
      <blockquote class="isnm-premium-quote">
        A disciplined mind for health action is formed in daily habits, not in a single lesson.
        <cite>ISNM Community</cite>
      </blockquote>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Support &amp; community</span>
      <h2 class="isnm-premium-heading">What student life includes</h2>
      <div class="isnm-premium-grid">
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-bed" aria-hidden="true"></i></span><h3>Hostel &amp; welfare</h3><p>Arrangements for accommodation and student welfare are communicated by the school, with guidance on conduct, safety, and reporting concerns.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-book-open" aria-hidden="true"></i></span><h3>Academic support</h3><p>Revision sessions, skills practice, and access to learning spaces help students keep pace with demanding programme requirements.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-praying-hands" aria-hidden="true"></i></span><h3>Faith &amp; reflection</h3><p>The school's faith-informed values shape a culture of service, respect, and care for every patient and colleague.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-users" aria-hidden="true"></i></span><h3>Clubs &amp; service</h3><p>Group activities, community outreach, and shared celebrations connect classroom learning with the wider community.</p></div>
      </div>
      <div class="isnm-premium-split" style="margin-top:3.5rem">
        <div>
          <div class="isnm-premium-image-frame"><img src="images/revision-library.jpg" alt="Students working together in the ISNM library" loading="lazy"></div>
          <p class="isnm-premium-image-caption">Shared study in the library</p>
        </div>
        <div>
          <div class="isnm-premium-image-frame"><img src="images/students-in-class.jpg" alt="Students in class at ISNM" loading="lazy"></div>
          <p class="isnm-premium-image-caption">Learning as a cohort</p>
        </div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Student voice</span>
      <h2 class="isnm-premium-heading">Where students grow</h2>
      <div class="isnm-premium-stats">
        <div class="isnm-premium-stats__item"><span class="isnm-premium-stats__value">6</span><span class="isnm-premium-stats__label">Practicum sites</span></div>
        <div class="isnm-premium-stats__item"><span class="isnm-premium-stats__value">4</span><span class="isnm-premium-stats__label">Programmes</span></div>
        <div class="isnm-premium-stats__item"><span class="isnm-premium-stats__value">15+</span><span class="isnm-premium-stats__label">Years of service</span></div>
        <div class="isnm-premium-stats__item"><span class="isnm-premium-stats__value">3000+</span><span class="isnm-premium-stats__label">Alumni</span></div>
      </div>
      <?php if (!empty($studentActivities)): ?>
        <div class="isnm-premium-grid">
          <?php foreach ($studentActivities as $activity): ?>
            <article class="isnm-premium-card">
              <span class="isnm-premium-card__index"><?php echo htmlspecialchars(date('M j, Y', strtotime($activity['event_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
              <h3><?php echo htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($activity['short_description'])): ?><p><?php echo htmlspecialchars($activity['short_description'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
              <?php if (!empty($activity['location'])): ?><p style="margin-top:0.75rem"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($activity['location'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No upcoming student activities are published yet. Browse the events page for the full calendar.</div>
      <?php endif; ?>
      <div class="isnm-premium-actions"><a class="isnm-premium-btn isnm-premium-btn--primary" href="events.php"><i class="fas fa-calendar-day" aria-hidden="true"></i> See All Events</a></div>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Join the community</span>
      <h2 class="isnm-premium-heading">Start your ISNM journey</h2>
      <p class="isnm-premium-copy">Find out how to apply, what life on campus looks like, and how we support students through to graduation.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="application.php"><i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="admissions.php"><i class="fas fa-door-open" aria-hidden="true"></i> Admissions</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
