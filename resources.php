<?php
$pageTitle = 'Resources | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$publishedFiles = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT title, file_name, file_path, file_type, caption FROM cms_media ORDER BY id DESC LIMIT 60");
    if ($result) {
      $publishedFiles = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Resources page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/revision-library.jpg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Resources</span>
      <h1 class="isnm-premium-title">Everything you need, in one place.</h1>
      <p class="isnm-premium-lead">A practical starting point for applicants, students, and partners looking for forms, checklists, and official information.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Resources</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-intro">
      <div>
        <span class="isnm-premium-kicker">Before you begin</span>
        <h2 class="isnm-premium-heading">Prepare with confidence</h2>
        <p class="isnm-premium-copy">The checklists below are here to help you organise your application and student records. Requirements can change between intakes, so confirm the current version with the admissions office before you submit documents.</p>
        <p class="isnm-premium-copy">Where a downloadable form is not yet published, the school can provide the correct document on request.</p>
      </div>
      <blockquote class="isnm-premium-quote">
        Good preparation is the first act of disciplined practice.
        <cite>ISNM Admissions Team</cite>
      </blockquote>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Checklists</span>
      <h2 class="isnm-premium-heading">Application &amp; registration</h2>
      <div class="isnm-premium-resources">
        <div class="isnm-premium-resource">
          <span class="isnm-premium-resource__icon"><i class="fas fa-edit" aria-hidden="true"></i></span>
          <div><h3>Application form</h3><p>Complete the online application for your chosen nursing or midwifery programme.</p></div>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="application.php">Open Form</a>
        </div>
        <div class="isnm-premium-resource">
          <span class="isnm-premium-resource__icon"><i class="fas fa-file-alt" aria-hidden="true"></i></span>
          <div><h3>Document checklist</h3><p>Confirm the academic records, identification, and additional documents required for the current intake.</p></div>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="contact.php">Request Checklist</a>
        </div>
        <div class="isnm-premium-resource">
          <span class="isnm-premium-resource__icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
          <div><h3>Programme information</h3><p>Compare pathways and speak to the right person about entry requirements and career direction.</p></div>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="programs.php">View Programs</a>
        </div>
        <div class="isnm-premium-resource">
          <span class="isnm-premium-resource__icon"><i class="fas fa-clipboard-list" aria-hidden="true"></i></span>
          <div><h3>Registration requirements</h3><p>Successful applicants receive a registration guide; contact the school if you need yours again.</p></div>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="contact.php">Ask Admissions</a>
        </div>
        <div class="isnm-premium-resource">
          <span class="isnm-premium-resource__icon"><i class="fas fa-stethoscope" aria-hidden="true"></i></span>
          <div><h3>Clinical placement guide</h3><p>Expectations, supervision, and conduct for students preparing for placement rotations.</p></div>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="clinical-training.php">View Guide</a>
        </div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Student access</span>
      <h2 class="isnm-premium-heading">Useful pages</h2>
      <div class="isnm-premium-grid">
        <a class="isnm-premium-card" href="student-login.php"><span class="isnm-premium-card__icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span><h3>Student portal</h3><p>Access your student account, records, and school communications.</p></a>
        <a class="isnm-premium-card" href="facilities.php"><span class="isnm-premium-card__icon"><i class="fas fa-building" aria-hidden="true"></i></span><h3>Facilities</h3><p>See the classrooms, skills lab, library, and clinical settings that support your studies.</p></a>
        <a class="isnm-premium-card" href="events.php"><span class="isnm-premium-card__icon"><i class="fas fa-calendar-day" aria-hidden="true"></i></span><h3>Events</h3><p>Check the calendar for workshops, ceremonies, and community activities.</p></a>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Published downloads</span>
      <h2 class="isnm-premium-heading">From the school library</h2>
      <?php if (!empty($publishedFiles)): ?>
        <div class="isnm-premium-resources">
          <?php foreach ($publishedFiles as $file): ?>
            <div class="isnm-premium-resource">
              <span class="isnm-premium-resource__icon"><i class="fas fa-file-download" aria-hidden="true"></i></span>
              <div>
                <h3><?php echo htmlspecialchars($file['title'] ?: $file['file_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if (!empty($file['caption'])): ?><p><?php echo htmlspecialchars($file['caption'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                <?php if (!empty($file['file_type'])): ?><p style="margin-top:0.3rem">Format: <?php echo htmlspecialchars(strtoupper($file['file_type']), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
              </div>
              <a class="isnm-premium-btn isnm-premium-btn--primary" href="<?php echo htmlspecialchars($file['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" download>Download</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No downloadable files are published at the moment. Contact the school and we will provide the document you need.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Need a document?</span>
      <h2 class="isnm-premium-heading">Ask the school directly</h2>
      <p class="isnm-premium-copy">If a form or checklist is not listed here, send us a message and the admissions team will point you to the current version.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i> Contact Admissions</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="application.php"><i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
