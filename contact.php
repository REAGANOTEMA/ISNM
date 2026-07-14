<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
include_once 'shared/_header.php';
?>

  <main>
    <!-- Hero Page Header -->
    <section class="hero-header">
      <div class="hero-overlay"></div>
      <div class="hero-particles"></div>
      <div class="container">
        <div class="hero-content">
          <div class="hero-text">
            <h1 class="hero-title animate-fade-in">Contact Us</h1>
            <p class="hero-subtitle animate-slide-up">Get in touch with Iganga School of Nursing and Midwifery</p>
            <div class="hero-decoration animate-scale-in"></div>
          </div>
        </div>
      </div>
    </section>

    <nav aria-label="breadcrumb" class="container mt-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Contact</li>
      </ol>
    </nav>

    <!-- Contact Information Section -->
    <section class="contact-info-section py-5 animate-on-scroll">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-address-book"></i>
          </div>
          <span class="tag tag-gold"><i class="fas fa-envelope"></i> Get in Touch</span>
          <h2 class="section-title">Our Contact Information</h2>
          <div class="section-divider section-divider-center"></div>
          <p class="section-subtitle">We're here to help and answer any questions you might have</p>
        </div>
        
        <div class="contact-grid">
          <div class="contact-card animate-slide-up animate-delay-1" style="animation-delay: 0.1s; cursor:pointer;">
            <div class="contact-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <h4>Address</h4>
            <div class="contact-details">
              <p><i class="fas fa-building"></i> P.O. Box 418, Iganga</p>
              <p><i class="fas fa-store"></i> Before C.M.S Trading Centre</p>
              <p><i class="fas fa-road"></i> Along Jinja-Iganga Highway</p>
              <p><i class="fas fa-home"></i> After Nekoli Guest House</p>
            </div>
          </div>
          
          <div class="contact-card animate-slide-up animate-delay-2" style="animation-delay: 0.2s; cursor:pointer;">
            <div class="contact-icon">
              <i class="fas fa-phone"></i>
            </div>
            <h4>Phone Numbers</h4>
            <div class="contact-details">
              <p><i class="fas fa-user-tie"></i> Principal: +256 782 990 403</p>
              <p><i class="fas fa-user-shield"></i> Deputy Principal: 0782 633 253</p>
              <p><i class="fas fa-user-cog"></i> Director: 0753 393 340</p>
              <p><i class="fas fa-users"></i> HRM: +256 703 999 796</p>
            </div>
          </div>
          
          <div class="contact-card animate-slide-up animate-delay-3" style="animation-delay: 0.3s; cursor:pointer;">
            <div class="contact-icon">
              <i class="fas fa-envelope"></i>
            </div>
            <h4>Email</h4>
            <div class="contact-details">
              <p><i class="fas fa-at"></i> info@igangaschoolofnursingandmidwifery.ac.ug</p>
              <p><i class="fas fa-graduation-cap"></i> admissions@igangaschoolofnursingandmidwifery.ac.ug</p>
              <p><i class="fas fa-info-circle"></i> registrar@igangaschoolofnursingandmidwifery.ac.ug</p>
            </div>
          </div>
          
          <div class="contact-card animate-slide-up animate-delay-4" style="animation-delay: 0.4s; cursor:pointer;">
            <div class="contact-icon">
              <i class="fas fa-globe"></i>
            </div>
            <h4>Website & Social</h4>
            <div class="contact-details">
              <p><i class="fas fa-globe-africa"></i> www.igangaschoolofnursingandmidwifery.ac.ug</p>
              <p><i class="fas fa-share-alt"></i> Follow us on social media</p>
              <p><i class="fab fa-facebook"></i> Facebook: @ISNMUganda</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section py-5 animate-on-scroll">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-paper-plane"></i>
          </div>
          <span class="tag tag-gold"><i class="fas fa-paper-plane"></i> Send a Message</span>
          <h2 class="section-title">Send Us a Message</h2>
          <div class="section-divider section-divider-center"></div>
          <p class="section-subtitle">Fill out the form below and we'll get back to you as soon as possible</p>
        </div>
        
        <div class="row">
          <div class="col-lg-8 col-md-10 col-sm-12 mx-auto">
            <div class="contact-form-container animate-fade-in">
              <form id="contactForm" class="form-premium" method="POST" action="process-contact.php">
                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-user me-2"></i>
                    <h4>Personal Information</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="firstName" class="form-label">
                        <i class="fas fa-user me-1"></i> First Name *
                      </label>
                      <input type="text" class="form-control" id="firstName" name="firstName" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="lastName" class="form-label">
                        <i class="fas fa-user me-1"></i> Last Name *
                      </label>
                      <input type="text" class="form-control" id="lastName" name="lastName" required>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-address-card me-2"></i>
                    <h4>Contact Details</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="email" class="form-label">
                        <i class="fas fa-envelope me-1"></i> Email Address *
                      </label>
                      <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="phone" class="form-label">
                        <i class="fas fa-phone me-1"></i> Phone Number *
                      </label>
                      <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-tag me-2"></i>
                    <h4>Message Details</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="subject" class="form-label">
                        <i class="fas fa-list me-1"></i> Subject *
                      </label>
                      <select class="form-control" id="subject" name="subject" required>
                        <option value="">Select Subject</option>
                        <option value="Admissions">Admissions</option>
                        <option value="Academics">Academics</option>
                        <option value="Finance">Finance/Bursar</option>
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Complaint">Complaint</option>
                        <option value="Partnership">Partnership</option>
                        <option value="Alumni">Alumni</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label for="message" class="form-label">
                        <i class="fas fa-comment-alt me-1"></i> Message *
                      </label>
                      <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>
                  </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-footer text-center">
                  <button type="submit" class="btn-3d btn-yellow btn-3d-block">
                    <span class="shine"></span>
                    <i class="fas fa-paper-plane me-2"></i>
                    <span>Send Message</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Mobile-Friendly Office Hours Section -->
    <section class="office-hours-section py-5 animate-on-scroll">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-clock"></i>
          </div>
          <span class="tag tag-gold"><i class="fas fa-clock"></i> Office Hours</span>
          <h2 class="section-title">Office Hours</h2>
          <div class="section-divider section-divider-center"></div>
          <p class="section-subtitle">When you can visit us or call</p>
        </div>
        
        <div class="row">
          <div class="col-lg-6 col-md-12 mb-4">
            <div class="hours-card animate-slide-up animate-delay-1" style="animation-delay: 0.1s;">
              <div class="card-header">
                <div class="office-icon">
                  <i class="fas fa-building"></i>
                </div>
                <h3>Administrative Office</h3>
              </div>
              <div class="hours-list">
                <div class="hour-item">
                  <div class="day-info">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span class="day">Monday , Friday</span>
                  </div>
                  <span class="time">8:00 AM to 5:00 PM</span>
                </div>
                <div class="hour-item">
                  <div class="day-info">
                    <i class="fas fa-calendar-week me-2"></i>
                    <span class="day">Saturday</span>
                  </div>
                  <span class="time">9:00 AM to 1:00 PM</span>
                </div>
                <div class="hour-item closed">
                  <div class="day-info">
                    <i class="fas fa-calendar-times me-2"></i>
                    <span class="day">Sunday</span>
                  </div>
                  <span class="time">Closed</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6 col-md-12 mb-4">
            <div class="hours-card animate-slide-up animate-delay-2" style="animation-delay: 0.2s;">
              <div class="card-header">
                <div class="office-icon">
                  <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Admissions Office</h3>
              </div>
              <div class="hours-list">
                <div class="hour-item">
                  <div class="day-info">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span class="day">Monday , Friday</span>
                  </div>
                  <span class="time">9:00 AM to 4:00 PM</span>
                </div>
                <div class="hour-item">
                  <div class="day-info">
                    <i class="fas fa-calendar-week me-2"></i>
                    <span class="day">Saturday</span>
                  </div>
                  <span class="time">9:00 AM to 1:00 PM</span>
                </div>
                <div class="hour-item closed">
                  <div class="day-info">
                    <i class="fas fa-calendar-times me-2"></i>
                    <span class="day">Sunday</span>
                  </div>
                  <span class="time">Closed</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Mobile-Friendly Map Section -->
    <section class="map-section py-5 animate-on-scroll">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-map-marked-alt"></i>
          </div>
          <span class="tag tag-gold"><i class="fas fa-map-marked-alt"></i> Find Us</span>
          <h2 class="section-title">Find Us</h2>
          <div class="section-divider section-divider-center"></div>
          <p class="section-subtitle">Located in the heart of Iganga Town, Eastern Uganda</p>
        </div>
        
        <div class="row">
          <div class="col-lg-8 col-md-12 mb-4">
            <div class="map-container animate-fade-in">
              <div class="map-wrapper">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7654321098765!2d33.4516861!3d0.5918431!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!2sIganga+School+of+Nursing+%26+Midwifery!5e0!3m2!1sen!2sug!4v1234567890"
                    width="100%" 
                    height="350" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Iganga School of Nursing and Midwifery Location">
                </iframe>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4 col-md-12">
            <div class="map-info animate-slide-up" style="animation-delay: 0.3s;">
              <div class="info-header">
                <div class="info-icon">
                  <i class="fas fa-location-arrow"></i>
                </div>
                <h3>Visit ISNM</h3>
              </div>
              <p class="info-description">Located in the heart of Iganga Town, Eastern Uganda, our campus provides easy access to quality healthcare education.</p>
              <div class="directions-section">
                <div class="direction-item">
                  <i class="fas fa-car me-2"></i>
                  <span>By Car: 2 hours from Kampala</span>
                </div>
                <div class="direction-item">
                  <i class="fas fa-bus me-2"></i>
                  <span>By Bus: Regular services from major towns</span>
                </div>
                <div class="direction-item">
                  <i class="fas fa-walking me-2"></i>
                  <span>Walking: 10 minutes from Iganga town center</span>
                </div>
              </div>
              <div class="directions-btn">
                <a href="https://www.google.com/maps/place/Iganga+School+of+Nursing+%26+Midwifery/@0.5918431,33.4516861,17z/data=!3m1!4b1!4m6!3m5!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!8m2!3d0.5918377!4d33.454261!16s%2Fg%2F11b5ys19t0?hl=en-GB&entry=ttu&g_ep=EgoyMDI2MDQxNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn-3d btn-green">
                  <span class="shine"></span>
                  <i class="fas fa-directions me-2"></i>
                  <span>Get Directions</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <script>
    // Contact form validation
    document.getElementById('contactForm').addEventListener('submit', function(e) {
      const requiredFields = this.querySelectorAll('[required]');
      let isValid = true;
      
      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('is-invalid');
        } else {
          field.classList.remove('is-invalid');
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
        return;
      }
      
      // Phone number validation
      const phone = document.getElementById('phone').value.replace(/\s/g, '');
      if (phone.startsWith('+256') && phone.length === 13) {
        // Valid
      } else if (phone.startsWith('0') && phone.length === 10) {
        document.getElementById('phone').value = '+256' + phone.substring(1);
      } else {
        e.preventDefault();
        showNotification('Please enter a valid Ugandan phone number', 'error');
        return;
      }
      
      // Show loading state
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    });

    // Notification function
    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
        <div class="notification-content">
          <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
          <span>${message}</span>
        </div>
      `;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.classList.add('show');
      }, 100);
      
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      }, 3000);
    }
  </script>


  <!-- Apply Now & Donate CTA Section -->
  <section class="cta-section py-5 bg-primary text-white animate-on-scroll">
    <div class="container text-center">
      <h2 class="mb-4">Ready to Join ISNM?</h2>
      <p class="lead mb-4">Apply now or support our mission to train the next generation of healthcare professionals</p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="application.php" class="btn-3d btn-blue btn-3d-lg px-5">
          <span class="shine"></span>
          <i class="fas fa-paper-plane me-2"></i> Apply Now
        </a>
        <a href="donation.php" class="btn-3d btn-glass btn-3d-lg px-5">
          <span class="shine"></span>
          <i class="fas fa-hand-holding-heart me-2"></i> Support Us
        </a>
      </div>
    </div>
  </section>

  <?php include('shared/_footer.php'); ?>