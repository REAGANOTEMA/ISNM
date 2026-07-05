<?php
// Use enhanced configuration with multi-database support
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';
?>

  <main>
    <!-- Cinematic Hero Section -->
    <section class="hero-section">
      <div class="hero-background">
        <div class="hero-slide active">
          <img src="images/hero1.jpg" alt="ISNM Hero" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero2.jpg" alt="ISNM Campus" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero3.jpg" alt="Learning Facilities" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero4.jpg" alt="Medical Training" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero5.jpg" alt="Nursing Students" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero6.jpg" alt="Healthcare Education" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/hero7.jpg" alt="Medical Excellence" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/graduates-hero2.jpg" alt="Graduates" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/graduates-hero3.jpg" alt="Graduation Ceremony" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/graduates-hero4.jpg" alt="Hero Graduates" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/students-hero.jpg" alt="Students" class="hero-bg">
        </div>
        <div class="hero-slide">
          <img src="images/diploma-graduates-on-gown-use-it-for-hero.jpg" alt="Diploma Graduates" class="hero-bg">
        </div>
      </div>
      
      <div class="hero-overlay"></div>
      
      <div class="hero-content">
        <div class="cinematic-title-wrapper">
          <div class="cinematic-title-track">
           <h1 class="cinematic-title">Training Healers. Saving Lives.</h1>
           <h1 class="cinematic-title">Training Healers. Saving Lives.</h1>
       </div>
        </div>
        
        <div class="hero-subtitle">
          <p>"Chosen to Serve , Based on a disciplined mind for health action"</p>
        </div>
        
        <div class="hero-stats">
          <div class="stat-item stat-green">
            <span class="stat-number">15+</span>
            <span class="stat-label">Years Excellence</span>
          </div>
          <div class="stat-item stat-blue">
            <span class="stat-number">3000+</span>
            <span class="stat-label">Graduates</span>
          </div>
          <div class="stat-item stat-chocolate">
            <span class="stat-number">100%</span>
            <span class="stat-label">Pass Rate</span>
          </div>
        </div>
        
        <div class="cta-buttons">
          <a href="application.php" class="btn-3d btn-yellow">
            <span class="shine"></span>
            <i class="fas fa-graduation-cap"></i>
            <span>Apply Now</span>
          </a>
          <a href="student-login.php" class="btn-3d btn-blue">
            <span class="shine"></span>
            <i class="fas fa-user-graduate"></i>
            <span>Student Portal</span>
          </a>
          <a href="about.php" class="btn-3d btn-glass">
            <span class="shine"></span>
            <i class="fas fa-info-circle"></i>
            <span>Learn More</span>
          </a>
        </div>
      </div>
    </section>

    <!-- Quick Stats Section -->
    <section class="stats-section py-5 bg-light">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-3 mb-4">
            <div class="stat-card">
              <i class="fas fa-users fa-3x text-primary mb-3"></i>
              <h3>315+</h3>
              <p>Students Enrolled</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-card">
              <i class="fas fa-graduation-cap fa-3x text-success mb-3"></i>
              <h3>100%</h3>
              <p>Midwifery Pass Rate</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-card">
              <i class="fas fa-hospital fa-3x text-info mb-3"></i>
              <h3>6</h3>
              <p>Practicum Sites</p>
            </div>
          </div>
          <div class="col-md-3 mb-4">
            <div class="stat-card">
              <i class="fas fa-award fa-3x text-warning mb-3"></i>
              <h3>15+</h3>
              <p>Years of Excellence</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Programs Section -->
    <section class="programs-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Our Programs</h2>
            <p class="section-subtitle">Quality healthcare education for tomorrow's professionals</p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="program-card">
              <div class="program-icon">
                <i class="fas fa-user-nurse"></i>
              </div>
              <h3>Certificate in Nursing</h3>
              <p>2½ years comprehensive nursing program with theoretical and practical training</p>
              <ul class="program-features">
                <li>Clinical practice at major hospitals</li>
                <li>Skills laboratory training</li>
                <li>Community health exposure</li>
              </ul>
              <a href="programs.php" class="btn-3d btn-blue btn-3d-sm">
                <span class="shine"></span>
                <i class="fas fa-arrow-right"></i> Learn More
              </a>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="program-card">
              <div class="program-icon">
                <i class="fas fa-baby"></i>
              </div>
              <h3>Certificate in Midwifery</h3>
              <p>2½ years specialized midwifery program with hands-on delivery experience</p>
              <ul class="program-features">
                <li>Maternal health training</li>
                <li>Delivery room practice</li>
                <li>Postnatal care expertise</li>
              </ul>
              <a href="programs.php" class="btn-3d btn-blue btn-3d-sm">
                <span class="shine"></span>
                <i class="fas fa-arrow-right"></i> Learn More
              </a>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="program-card">
              <div class="program-icon">
                <i class="fas fa-user-md"></i>
              </div>
              <h3>Diploma in Nursing - Extension</h3>
              <p>1½ years program for enrolled nurses seeking diploma qualification</p>
              <ul class="program-features">
                <li>Advanced nursing concepts</li>
                <li>Leadership training</li>
                <li>Research methodology</li>
              </ul>
              <a href="programs.php" class="btn-3d btn-blue btn-3d-sm">
                <span class="shine"></span>
                <i class="fas fa-arrow-right"></i> Learn More
              </a>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="program-card">
              <div class="program-icon">
                <i class="fas fa-stethoscope"></i>
              </div>
              <h3>Diploma in Midwifery - Extension</h3>
              <p>1½ years advanced program for enrolled midwives</p>
              <ul class="program-features">
                <li>Advanced midwifery skills</li>
                <li>High-risk pregnancy management</li>
                <li>Neonatal care specialization</li>
              </ul>
              <a href="programs.php" class="btn-3d btn-blue btn-3d-sm">
                <span class="shine"></span>
                <i class="fas fa-arrow-right"></i> Learn More
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5 bg-light">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="about-content">
              <h2 class="section-title">About ISNM</h2>
              <p class="about-text">
                Iganga School of Nursing and Midwifery is a private Nursing School registered by the Registrar of Companies as a Limited Liability Company. The school is also registered with the Ministry of Education & Sports (MOES) and Uganda Nurses and Midwives Council (UNMC).
              </p>
              <div class="row g-3 mt-3">
                <div class="col-md-6">
                  <div class="vm-card">
                    <div class="vm-card-icon bg-warning">
                      <i class="fas fa-eye"></i>
                    </div>
                    <h4>Vision</h4>
                    <p>"To have a healthy and disease free community"</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="vm-card">
                    <div class="vm-card-icon bg-success">
                      <i class="fas fa-bullseye"></i>
                    </div>
                    <h4>Mission</h4>
                    <p>"To produce world class and competitive health workers through the use of modern teaching methods, technology and research"</p>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-3 mt-4 flex-wrap">
                <a href="about.php" class="btn-3d btn-green">
                  <span class="shine"></span>
                  <i class="fas fa-info-circle me-2"></i>Learn More About Us
                </a>
                <a href="volunteer.php" class="btn-3d btn-chocolate">
                  <span class="shine"></span>
                  <i class="fas fa-hands-helping me-2"></i>Volunteer With Us
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="about-image">
              <img src="images/classroom-photo-certificates-in-nurses-and-diploma.jpeg" alt="ISNM Campus" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Facilities Section -->
    <section class="facilities-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Our Facilities</h2>
            <p class="section-subtitle">Modern infrastructure for quality learning</p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="facility-card">
              <i class="fas fa-school fa-3x text-primary mb-3"></i>
              <h4>Classrooms</h4>
              <p>Modern spacious classrooms designed for comfortable and interactive learning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="facility-card">
              <i class="fas fa-desktop fa-3x text-success mb-3"></i>
              <h4>Computer Lab</h4>
              <p>Fully equipped computer laboratory with high-speed internet for digital learning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="facility-card">
              <i class="fas fa-book fa-3x text-info mb-3"></i>
              <h4>Library</h4>
              <p>Well-stocked library with extensive medical reference materials and study areas</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="facility-card">
              <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
              <h4>Dining Hall</h4>
              <p>Spacious multi-purpose hall accommodating 300 students for dining and events</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Latest News Section -->
    <section class="news-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Latest News</h2>
            <p class="section-subtitle">Stay updated with the latest happenings at ISNM</p>
          </div>
        </div>
        <div class="row g-4">
          <?php
          $newsItems = [];
          try {
            $websiteDB = getDatabaseConnection('website');
            if ($websiteDB) {
              $newsResult = $websiteDB->query("SELECT id, title, slug, excerpt, featured_image, author_name, published_at FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 3");
              if ($newsResult) {
                $newsItems = $newsResult->fetch_all(MYSQLI_ASSOC);
              }
            }
          } catch (Exception $e) {
            error_log("Error fetching news: " . $e->getMessage());
          }
          if (!empty($newsItems)):
            foreach ($newsItems as $item):
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="news-card">
              <?php if (!empty($item['featured_image'])): ?>
              <img src="<?= htmlspecialchars($item['featured_image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="news-card-img" loading="lazy">
              <?php else: ?>
              <div class="news-card-img-placeholder">
                <i class="fas fa-newspaper"></i>
              </div>
              <?php endif; ?>
              <div class="news-card-body">
                <span class="date"><i class="far fa-calendar-alt me-1"></i><?= date('F j, Y', strtotime($item['published_at'])) ?></span>
                <h5><a href="news.php?id=<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a></h5>
                <p class="excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($item['excerpt'] ?? ''), 0, 120, '...')) ?></p>
                <div class="mt-2">
                  <a href="news.php?id=<?= (int)$item['id'] ?>" class="btn-3d btn-blue btn-3d-sm">
                    <span class="shine"></span>
                    <i class="fas fa-arrow-right"></i> Read More
                  </a>
                </div>
              </div>
            </div>
          </div>
          <?php
            endforeach;
          else:
          ?>
          <div class="col-12">
            <div class="empty-state">
              <i class="fas fa-newspaper"></i>
              <h4>No News Yet</h4>
              <p>Check back soon for updates and announcements from ISNM.</p>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="text-center mt-4">
          <a href="news.php" class="btn-3d btn-green btn-3d-lg">
            <span class="shine"></span>
            <i class="fas fa-newspaper me-2"></i>View All News
          </a>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-5 bg-primary text-white">
      <div class="container text-center">
        <h2 class="mb-4">Ready to Start Your Healthcare Journey?</h2>
        <p class="lead mb-4">Join thousands of successful healthcare professionals who started their careers at ISNM</p>
        <div class="cta-buttons">
          <a href="application.php" class="btn-3d btn-yellow btn-3d-lg me-3">
            <span class="shine"></span>
            <i class="fas fa-paper-plane"></i> Apply Online
          </a>
          <a href="contact.php" class="btn-3d btn-glass btn-3d-lg">
            <span class="shine"></span>
            <i class="fas fa-phone"></i> Contact Us
          </a>
        </div>
      </div>
    </section>

  </main>

  <?php include('shared/_footer.php'); ?>
