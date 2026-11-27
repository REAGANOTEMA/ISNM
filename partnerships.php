<?php
$pageTitle = 'Partnerships | Iganga School of Nursing and Midwifery';
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

$publishedPartners = [];
try {
  $websiteDB = getDatabaseConnection('website');
  if ($websiteDB) {
    $result = $websiteDB->query("SELECT name, logo_url, website_url, description, partner_type FROM cms_partners WHERE is_active = 1 ORDER BY sort_order, name LIMIT 60");
    if ($result) {
      $publishedPartners = isnm_fetch_all($result);
    }
  }
} catch (Throwable $e) {
  error_log('Partnerships page query failed: ' . $e->getMessage());
}
?>
<main>
  <section class="isnm-premium-hero" style="background-image: url('images/staffs-meeting1.JPG');">
    <div class="isnm-premium-hero-inner">
      <span class="isnm-premium-eyebrow">Working Together</span>
      <h1 class="isnm-premium-title">Better health, built together.</h1>
      <p class="isnm-premium-lead">ISNM partners with facilities, institutions, and communities to strengthen healthcare education and service in Uganda.</p>
    </div>
  </section>

  <nav class="isnm-premium-crumbbar" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Partnerships</li>
      </ol>
    </div>
  </nav>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-intro">
      <div>
        <span class="isnm-premium-kicker">A shared purpose</span>
        <h2 class="isnm-premium-heading">Collaboration with consequence</h2>
        <p class="isnm-premium-copy">No school trains healthcare professionals alone. Our work depends on the trust and expertise of clinical facilities, academic colleagues, community organisations, and supporters who share our commitment to service.</p>
        <p class="isnm-premium-copy">We seek relationships that benefit students, improve care, and remain accountable to the communities we serve.</p>
      </div>
      <blockquote class="isnm-premium-quote">
        Strong partnerships turn learning into service, and service into lasting impact.
        <cite>ISNM Partnership Ethos</cite>
      </blockquote>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Pathways</span>
      <h2 class="isnm-premium-heading">How you can work with us</h2>
      <div class="isnm-premium-grid">
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-hospital" aria-hidden="true"></i></span><h3>Clinical placements</h3><p>Healthcare facilities can support supervised student rotations, mentoring, and shared assessment of clinical competence.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-hand-holding-heart" aria-hidden="true"></i></span><h3>Community programmes</h3><p>Organisations can work with students and staff on health education, outreach, and service activities around Iganga.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></span><h3>Academic exchange</h3><p>Institutions can collaborate on teaching, curriculum development, quality assurance, and professional development.</p></div>
        <div class="isnm-premium-card"><span class="isnm-premium-card__icon"><i class="fas fa-hands-helping" aria-hidden="true"></i></span><h3>Donors &amp; volunteers</h3><p>Supporters can contribute to learning resources, facilities, scholarships, and the day-to-day work of the school.</p></div>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section">
    <div class="isnm-premium-container isnm-premium-split">
      <div>
        <span class="isnm-premium-kicker">What we ask</span>
        <h2 class="isnm-premium-heading">A clear, mutual commitment</h2>
        <p class="isnm-premium-copy">We value partnerships with clear expectations: defined roles, safe learning environments, respect for patients, and shared responsibility for outcomes. Every collaboration is discussed with school leadership before it begins.</p>
        <div class="isnm-premium-actions">
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i> Start a Conversation</a>
          <a class="isnm-premium-btn isnm-premium-btn--primary" href="volunteer.php"><i class="fas fa-hands-helping" aria-hidden="true"></i> Volunteer</a>
        </div>
      </div>
      <div>
        <div class="isnm-premium-image-frame"><img src="images/school.JPG" alt="Iganga School of Nursing and Midwifery campus" loading="lazy"></div>
        <p class="isnm-premium-image-caption">Our home in Iganga</p>
      </div>
    </div>
  </section>

  <section class="isnm-premium-section isnm-premium-section--tint">
    <div class="isnm-premium-container">
      <span class="isnm-premium-kicker">Published partners</span>
      <h2 class="isnm-premium-heading">Organisations in our records</h2>
      <?php if (!empty($publishedPartners)): ?>
        <div class="isnm-premium-grid">
          <?php foreach ($publishedPartners as $partner): ?>
            <article class="isnm-premium-card">
              <?php if (!empty($partner['logo_url'])): ?>
                <div class="isnm-premium-logos"><div class="isnm-premium-logos__item"><img src="<?php echo htmlspecialchars($partner['logo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($partner['name'], ENT_QUOTES, 'UTF-8'); ?> logo" loading="lazy"></div></div>
              <?php else: ?>
                <span class="isnm-premium-card__icon"><i class="fas fa-handshake" aria-hidden="true"></i></span>
              <?php endif; ?>
              <h3><?php echo htmlspecialchars($partner['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <?php if (!empty($partner['partner_type'])): ?><span class="isnm-premium-card__index"><?php echo htmlspecialchars(str_replace('_', ' ', $partner['partner_type']), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
              <?php if (!empty($partner['description'])): ?><p><?php echo htmlspecialchars($partner['description'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
              <?php if (!empty($partner['website_url'])): ?><div class="isnm-premium-actions"><a class="isnm-premium-btn isnm-premium-btn--primary" href="<?php echo htmlspecialchars($partner['website_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Visit Website</a></div><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="isnm-premium-empty">No partners are published at the moment. Contact the school to learn about current collaborations.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="isnm-premium-cta">
    <div class="isnm-premium-container">
      <span class="isnm-premium-eyebrow">Build with us</span>
      <h2 class="isnm-premium-heading">Partner for stronger healthcare education</h2>
      <p class="isnm-premium-copy">Tell us what you can offer and what you hope to achieve. We will connect you with the right team at ISNM.</p>
      <div class="isnm-premium-actions">
        <a class="isnm-premium-btn isnm-premium-btn--gold" href="contact.php"><i class="fas fa-handshake" aria-hidden="true"></i> Become a Partner</a>
        <a class="isnm-premium-btn isnm-premium-btn--outline" href="donation.php"><i class="fas fa-hand-holding-heart" aria-hidden="true"></i> Support the School</a>
      </div>
    </div>
  </section>
</main>

<?php include_once 'shared/_footer.php'; ?>
