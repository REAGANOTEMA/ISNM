<?php
$pageTitle = 'Clinical Training | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$placementSites = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT site_name, location, capacity, status FROM clinical_sites WHERE status = 'Active' ORDER BY site_name LIMIT 60");
    if ($result) {
      $placementSites = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Clinical training page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/hero4.jpg');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Practical Experience</span>
      <h1 class="isnm-premium-title">From classroom to patient care.</h1>
      <p class="isnm-premium-lead">Supervised clinical placements where nursing and midwifery students learn to practise with competence, dignity, and discipline.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Clinical Training</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-split">
      <div>
        <span class="isnm-premium-kicker">Our approach</span>
        <h2 class="isnm-premium-heading">Practice with purpose</h2>
        <p class="isnm-premium-copy">Clinical training connects what students learn in class with the realities of healthcare delivery. Placements are structured around programme objectives, supervision, reflection, and the welfare of patients and families.</p>
        <p class="isnm-premium-copy">Students are prepared before placement, supported while on site, and assessed on professional behaviour as well as technical skill.</p>
        <div class="isnm-premium-actions"><a class="isnm-premium-btn isnm-premium-btn--primary" href="programs.php"><i class="fas fa-graduation-cap" aria-hidden="true"></i> View Programs</a></div>
      </div>
      <div>
        <div class="isnm-premium-image-frame"><img src="images/skill-nurses.jpeg" alt="Nursing students practising clinical skills" loading="lazy"></div>
        <p class="isnm-premium-image-caption">Preparation before placement</p>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">How placements work</span>
      <h2 class="isnm-premium-heading">Four commitments</h2>
      <div class="isnm-premium-grid">
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">01 / Preparation</span><h3>Ready before arrival</h3><p>Students complete skills practice and safety preparation before entering a clinical area.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">02 / Supervision</span><h3>Guided by professionals</h3><p>Clinical staff and school staff work together to guide learning and protect patient safety.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">03 / Reflection</span><h3>Learning made deliberate</h3><p>Students review experiences, link them to theory, and identify what they need to improve.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__index">04 / Conduct</span><h3>Professional standards</h3><p>Respect, confidentiality, punctuality, and accountability are expected in every placement.</p></div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Questions answered</span>
      <h2 class="isnm-premium-heading">Clinical placement guide</h2>
      <div class="isnm-premium-split">
        <div class="isnm-premium-accordion">
          <details open>
            <summary>How are placement sites chosen?</summary>
            <p>Sites are selected for the learning opportunities they offer and their ability to support supervised nursing and midwifery practice. Active placements are reviewed as programme needs change.</p>
          </details>
          <details>
            <summary>What support do students receive on placement?</summary>
            <p>Students receive guidance from placement supervisors and the school, with channels for raising concerns about workload, safety, or wellbeing during the rotation.</p>
          </details>
          <details>
            <summary>What is expected of students?</summary>
            <p>Students follow the policies of the placement site and the school, maintain patient confidentiality, arrive prepared, and ask for help when a task is beyond their current competence.</p>
          </details>
          <details>
            <summary>Can a facility become a placement partner?</summary>
            <p>Yes. Healthcare facilities interested in clinical placement partnership can contact the school to discuss expectations, capacity, and the support required for a successful rotation.</p>
          </details>
        </div>
        <div>
          <div class="isnm-premium-image-frame"><img src="images/class.JPG" alt="ISNM teaching session" loading="lazy"></div>
          <p class="isnm-premium-image-caption">Learning before service</p>
        </div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Current partners</span>
      <h2 class="isnm-premium-heading">Active placement sites</h2>
      <?php if (!empty($placementSites)): ?>
        <div class="isnm-premium-grid">
          <?php foreach ($placementSites as $site): ?>
            <article class="isnm-premium-card">
              <span class="isnm-premium-card__icon"><i class="fas fa-hospital" aria-hidden="true"></i></span>
              <h3><?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($site['location'])): ?><p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($site['location'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
              <?php if (!empty($site['capacity'])): ?><p style="margin-top:0.6rem">Capacity: <?php echo (int)$site['capacity']; ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No active placement sites are currently published. Contact the school for the latest placement information.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Ready to begin</span>
      <h2 class="isnm-premium-heading">Train where your care will matter</h2>
      <p class="isnm-premium-copy">Apply to join a programme that combines classroom learning with supervised clinical experience.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="application.php"><i class="fas fa-paper-plane" aria-hidden="true"></i> Apply Now</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i> Contact Us</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
