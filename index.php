<?php
// Use enhanced configuration with multi-database support
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php';
include_once 'shared/_header.php';

// Fetch live stats from database
$liveStats = ['students' => 315, 'programs' => 4, 'graduates' => 3000, 'practicum' => 6, 'years' => 15];
try {
    $studentsConn = getStudentsConnection();
    if ($studentsConn) {
        $r = @$studentsConn->query("SELECT COUNT(*) as c FROM students WHERE status IN ('Active','active','enrolled','Enrolled')");
        if ($r) { $row = $r->fetch_assoc(); if ((int)$row['c'] > 0) $liveStats['students'] = (int)$row['c']; }
    }
    $staffConn = getStaffConnection();
    if ($staffConn) {
        $r = @$staffConn->query("SELECT COUNT(*) as c FROM academic_programs WHERE is_active = 1");
        if ($r) { $row = $r->fetch_assoc(); if ((int)$row['c'] > 0) $liveStats['programs'] = (int)$row['c']; }
        $r2 = @$staffConn->query("SELECT COUNT(*) as c FROM staff WHERE status = 'Active'");
        if ($r2) { $row = $r2->fetch_assoc(); if ((int)$row['c'] > 0) $liveStats['staff'] = (int)$row['c']; }
    }
} catch (Exception $e) { error_log('index.php live stats: ' . $e->getMessage()); }
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
          <p>"Chosen to Serve, Based on a disciplined mind for health action"</p>
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
          <a href="application.php" class="btn-3d btn-yellow cta-pulse">
            <span class="shine"></span>
            <i class="fas fa-graduation-cap"></i>
            <span>Apply Now</span>
          </a>
          <a href="student-login.php" class="btn-3d btn-blue cta-pulse">
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
      
      <!-- Scroll Indicator -->
      <div class="hero-scroll-indicator" style="position:absolute;bottom:30px;left:50%;transform:translateX(-50%);z-index:4;text-align:center;">
        <a href="#stats" style="color:rgba(255,255,255,0.7);text-decoration:none;display:flex;flex-direction:column;align-items:center;gap:8px;transition:color 0.3s;">
          <span style="font-size:0.75rem;letter-spacing:2px;text-transform:uppercase;font-family:'Montserrat',sans-serif;">Explore</span>
          <div style="width:24px;height:38px;border:2px solid rgba(255,255,255,0.4);border-radius:12px;display:flex;justify-content:center;padding-top:6px;">
            <div style="width:3px;height:8px;background:#FFD700;border-radius:2px;animation:scrollBounce 1.5s ease-in-out infinite;"></div>
          </div>
        </a>
      </div>
      <style>@keyframes scrollBounce{0%,100%{transform:translateY(0);opacity:1;}50%{transform:translateY(8px);opacity:0.5;}}</style>
    </section>

    <!-- Quick Stats Section -->
    <section id="stats" class="stats-section py-5">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-3 col-6 mb-4 animate-on-scroll">
            <div class="stat-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="stat-card-icon">
                <i class="fas fa-users"></i>
              </div>
              <h3 class="stat-number" data-count="<?= $liveStats['students'] ?>">0</h3>
              <p class="stat-label">Students Enrolled</p>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4 animate-on-scroll animate-delay-1">
            <div class="stat-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="stat-card-icon icon-green">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <h3 class="stat-number"><?= $liveStats['programs'] ?>+</h3>
              <p class="stat-label">Academic Programs</p>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4 animate-on-scroll animate-delay-2">
            <div class="stat-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="stat-card-icon icon-blue">
                <i class="fas fa-hospital"></i>
              </div>
              <h3 class="stat-number" data-count="<?= $liveStats['practicum'] ?>">0</h3>
              <p class="stat-label">Practicum Sites</p>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-4 animate-on-scroll animate-delay-3">
            <div class="stat-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="stat-card-icon icon-gold">
                <i class="fas fa-award"></i>
              </div>
              <h3 class="stat-number" data-count="<?= $liveStats['years'] ?>">0</h3>
              <p class="stat-label">Years of Excellence</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Programs Section -->
    <section class="programs-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5 animate-on-scroll">
            <span class="tag tag-primary"><i class="fas fa-graduation-cap"></i> Academic Programs</span>
            <h2 class="section-title mt-3">Our Programs</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">Quality healthcare education for tomorrow's professionals</p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="program-card" onclick="window.location='programs.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='programs.php'">
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
            <div class="program-card" onclick="window.location='programs.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='programs.php'">
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
            <div class="program-card" onclick="window.location='programs.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='programs.php'">
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
            <div class="program-card" onclick="window.location='programs.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='programs.php'">
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
    <section class="about-section py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6 animate-on-scroll">
            <div class="about-content">
              <span class="tag tag-gold"><i class="fas fa-info-circle"></i> About Us</span>
              <h2 class="section-title mt-3">About ISNM</h2>
              <div class="section-divider"></div>
              <p class="about-text">
                Iganga School of Nursing and Midwifery is a private Nursing School registered by the Registrar of Companies as a Limited Liability Company. The school is also registered with the Ministry of Education & Sports (MOES) and Uganda Nurses and Midwives Council (UNMC).
              </p>
              <div class="row g-3 mt-3">
                <div class="col-md-6">
                  <div class="vm-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
                    <div class="vm-card-icon bg-warning">
                      <i class="fas fa-eye"></i>
                    </div>
                    <h4>Vision</h4>
                    <p>"To have a healthy and disease free community"</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="vm-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
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
                <a href="volunteer.php" class="btn-3d btn-chocolate cta-pulse">
                  <span class="shine"></span>
                  <i class="fas fa-hands-helping me-2"></i>Volunteer With Us
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-6 animate-on-scroll animate-delay-2">
            <div class="about-image">
              <img src="images/classroom-photo-certificates-in-nurses-and-diploma.jpeg" alt="ISNM Campus" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Choose ISNM Section — Premium 3D -->
    <section class="why-choose-section py-5">
      <div class="container" style="position:relative;z-index:1;">
        <div class="row">
          <div class="col-lg-12 text-center mb-5 animate-on-scroll">
            <span class="tag tag-gold"><i class="fas fa-star"></i> Why Choose Us</span>
            <h2 class="section-title mt-3" style="color:#fff;">Why Choose ISNM?</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">Discover what makes us the preferred choice for healthcare education in Uganda</p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6 animate-on-scroll">
            <div class="feature-card" onclick="window.location='organogram.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='organogram.php'">
              <div class="feature-icon">
                <i class="fas fa-award"></i>
              </div>
              <h4>Accredited Programs</h4>
              <p>Fully accredited by Uganda Nurses and Midwives Council and Ministry of Education & Sports</p>
              <a href="organogram.php" class="btn-3d btn-yellow btn-3d-sm mt-3" onclick="event.stopPropagation()"><span class="shine"></span><i class="fas fa-sitemap me-1"></i> View Organogram</a>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 animate-on-scroll animate-delay-1">
            <div class="feature-card" onclick="window.location='organogram.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='organogram.php'">
              <div class="feature-icon icon-green">
                <i class="fas fa-user-md"></i>
              </div>
              <h4>Expert Faculty</h4>
              <p>Learn from experienced healthcare professionals and dedicated educators</p>
              <a href="organogram.php" class="btn-3d btn-yellow btn-3d-sm mt-3" onclick="event.stopPropagation()"><span class="shine"></span><i class="fas fa-users me-1"></i> Meet Our Team</a>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 animate-on-scroll animate-delay-2">
            <div class="feature-card" onclick="window.location='organogram.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='organogram.php'">
              <div class="feature-icon icon-blue">
                <i class="fas fa-hospital"></i>
              </div>
              <h4>Clinical Placement</h4>
              <p>Hands-on training at 6+ major hospitals and healthcare facilities across Uganda</p>
              <a href="organogram.php" class="btn-3d btn-yellow btn-3d-sm mt-3" onclick="event.stopPropagation()"><span class="shine"></span><i class="fas fa-sitemap me-1"></i> View Organogram</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Facilities Section -->
    <section class="facilities-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5 animate-on-scroll">
            <span class="tag tag-primary"><i class="fas fa-building"></i> Our Campus</span>
            <h2 class="section-title mt-3">Our Facilities</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">Modern infrastructure for quality learning</p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-md-6 col-lg-3 animate-on-scroll">
            <div class="facility-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="facility-icon">
                <i class="fas fa-school"></i>
              </div>
              <h4>Classrooms</h4>
              <p>Modern spacious classrooms designed for comfortable and interactive learning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 animate-on-scroll animate-delay-1">
            <div class="facility-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="facility-icon icon-green">
                <i class="fas fa-desktop"></i>
              </div>
              <h4>Computer Lab</h4>
              <p>Fully equipped computer laboratory with high-speed internet for digital learning</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 animate-on-scroll animate-delay-2">
            <div class="facility-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="facility-icon icon-blue">
                <i class="fas fa-book"></i>
              </div>
              <h4>Library</h4>
              <p>Well-stocked library with extensive medical reference materials and study areas</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3 animate-on-scroll animate-delay-3">
            <div class="facility-card" onclick="window.location='about.php'" role="link" tabindex="0" onkeydown="if(event.key==='Enter')window.location='about.php'">
              <div class="facility-icon icon-gold">
                <i class="fas fa-utensils"></i>
              </div>
              <h4>Dining Hall</h4>
              <p>Spacious multi-purpose hall accommodating 300 students for dining and events</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5 animate-on-scroll">
            <span class="tag tag-gold"><i class="fas fa-quote-left"></i> Testimonials</span>
            <h2 class="section-title mt-3">What Our Students Say</h2>
            <div class="section-divider section-divider-center"></div>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6 animate-on-scroll">
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"ISNM gave me the foundation to become a confident midwife. The clinical training was exceptional, and the faculty truly cares about every student."</p>
              <div class="testimonial-author">
                <div class="author-avatar">
                  <i class="fas fa-user-nurse"></i>
                </div>
                <div>
                  <h5>Nurse Sarah</h5>
                  <span>Midwifery Graduate 2022</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 animate-on-scroll animate-delay-1">
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"The practical experience I gained at ISNM prepared me for real-world healthcare challenges. I'm now working at a major hospital in Kampala."</p>
              <div class="testimonial-author">
                <div class="author-avatar icon-green">
                  <i class="fas fa-user-md"></i>
                </div>
                <div>
                  <h5>James Okello</h5>
                  <span>Nursing Graduate 2021</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 animate-on-scroll animate-delay-2">
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <p class="testimonial-text">"Choosing ISNM was the best decision of my life. The school's commitment to excellence in healthcare education is unmatched in the region."</p>
              <div class="testimonial-author">
                <div class="author-avatar icon-blue">
                  <i class="fas fa-user"></i>
                </div>
                <div>
                  <h5>Grace Achieng</h5>
                  <span>Diploma Graduate 2023</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Latest News Section -->
    <section class="news-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5 animate-on-scroll">
            <span class="tag tag-primary"><i class="fas fa-newspaper"></i> Latest Updates</span>
            <h2 class="section-title mt-3">Latest News</h2>
            <div class="section-divider section-divider-center"></div>
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
                <h5><a href="news.php?view=single&slug=<?= urlencode($item['slug'] ?? '') ?>"><?= htmlspecialchars($item['title']) ?></a></h5>
                <p class="excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($item['excerpt'] ?? ''), 0, 120, '...')) ?></p>
                <div class="mt-2">
                  <a href="news.php?view=single&slug=<?= urlencode($item['slug'] ?? '') ?>" class="btn-3d btn-blue btn-3d-sm">
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
    <section class="cta-section py-5">
      <div class="cta-bg-shapes">
        <div class="cta-shape cta-shape-1"></div>
        <div class="cta-shape cta-shape-2"></div>
        <div class="cta-shape cta-shape-3"></div>
      </div>
      <div class="container text-center position-relative">
        <div class="animate-on-scroll">
          <span class="tag tag-gold"><i class="fas fa-rocket"></i> Get Started</span>
          <h2 class="mt-3 mb-4" style="color:#fff !important">Ready to Start Your Healthcare Journey?</h2>
          <p class="lead mb-4" style="color:rgba(255,255,255,0.9) !important">Join thousands of successful healthcare professionals who started their careers at ISNM</p>
          <div class="cta-buttons">
            <a href="application.php" class="btn-3d btn-yellow btn-3d-lg me-3 cta-pulse">
              <span class="shine"></span>
              <i class="fas fa-paper-plane"></i> Apply Online
            </a>
            <a href="contact.php" class="btn-3d btn-glass btn-3d-lg">
              <span class="shine"></span>
              <i class="fas fa-phone"></i> Contact Us
            </a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var counters = document.querySelectorAll('.stat-number[data-count]');
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var target = parseInt(el.getAttribute('data-count'), 10);
          var suffix = el.textContent.replace(/[0-9]/g, '') || '';
          var duration = 2000;
          var start = 0;
          var startTime = null;
          function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            el.textContent = current.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(animate);
          }
          el.textContent = '0' + suffix;
          requestAnimationFrame(animate);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.3 });
    counters.forEach(function(c) { observer.observe(c); });
  });
  </script>

  <script>
  document.querySelectorAll('.feature-card').forEach(function(card) {
    card.addEventListener('mousemove', function(e) {
      var rect = card.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      card.style.setProperty('--mouse-x', x + '%');
      card.style.setProperty('--mouse-y', y + '%');
    });
  });
  </script>

  <?php include('shared/_footer.php'); ?>
