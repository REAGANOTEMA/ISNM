<?php include('shared/_header.php');?>

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

    <!-- Contact Information Section -->
    <section class="contact-info-section py-5">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-address-book"></i>
          </div>
          <h2 class="section-title">Our Contact Information</h2>
          <p class="section-subtitle">We're here to help and answer any questions you might have</p>
        </div>
        
        <div class="contact-grid">
          <div class="contact-card animate-slide-up" style="animation-delay: 0.1s;">
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
          
          <div class="contact-card animate-slide-up" style="animation-delay: 0.2s;">
            <div class="contact-icon">
              <i class="fas fa-phone"></i>
            </div>
            <h4>Phone Numbers</h4>
            <div class="contact-details">
              <p><i class="fas fa-user-tie"></i> Principal: 0782 990 403</p>
              <p><i class="fas fa-user-shield"></i> Deputy Principal: 0782 633 253</p>
              <p><i class="fas fa-user-cog"></i> Director: 0753 393 340</p>
              <p><i class="fas fa-users"></i> HRM: 0703 999 796</p>
            </div>
          </div>
          
          <div class="contact-card animate-slide-up" style="animation-delay: 0.3s;">
            <div class="contact-icon">
              <i class="fas fa-envelope"></i>
            </div>
            <h4>Email</h4>
            <div class="contact-details">
              <p><i class="fas fa-at"></i> iganganursingschool@gmail.com</p>
              <p><i class="fas fa-graduation-cap"></i> admissions@isnm.ac.ug</p>
              <p><i class="fas fa-info-circle"></i> info@isnm.ac.ug</p>
            </div>
          </div>
          
          <div class="contact-card animate-slide-up" style="animation-delay: 0.4s;">
            <div class="contact-icon">
              <i class="fas fa-globe"></i>
            </div>
            <h4>Website & Social</h4>
            <div class="contact-details">
              <p><i class="fas fa-globe-africa"></i> www.isnm.ac.ug</p>
              <p><i class="fas fa-share-alt"></i> Follow us on social media</p>
              <p><i class="fab fa-facebook"></i> Facebook: @ISNMUganda</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section py-5">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-paper-plane"></i>
          </div>
          <h2 class="section-title">Send Us a Message</h2>
          <p class="section-subtitle">Fill out the form below and we'll get back to you as soon as possible</p>
        </div>
        
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="contact-form-container animate-fade-in">
              <form id="contactForm" method="POST" action="process-contact.php">
                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-user"></i>
                    <h4>Personal Information</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="firstName" class="form-label">
                        <i class="fas fa-user"></i> First Name *
                      </label>
                      <input type="text" class="form-control" id="firstName" name="firstName" required>
                    </div>
                    <div class="col-md-6">
                      <label for="lastName" class="form-label">
                        <i class="fas fa-user"></i> Last Name *
                      </label>
                      <input type="text" class="form-control" id="lastName" name="lastName" required>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-address-card"></i>
                    <h4>Contact Details</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address *
                      </label>
                      <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-md-6">
                      <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i> Phone Number *
                      </label>
                      <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="section-title">
                    <i class="fas fa-tag"></i>
                    <h4>Message Details</h4>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-12">
                      <label for="subject" class="form-label">
                        <i class="fas fa-list"></i> Subject *
                      </label>
                      <select class="form-control" id="subject" name="subject" required>
                        <option value="">Select Subject</option>
                        <option value="Admissions">🎓 Admissions</option>
                        <option value="Academics">📚 Academics</option>
                        <option value="Finance">💰 Finance/Bursar</option>
                        <option value="General Inquiry">❓ General Inquiry</option>
                        <option value="Complaint">📝 Complaint</option>
                        <option value="Partnership">🤝 Partnership</option>
                        <option value="Alumni">👥 Alumni</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label for="message" class="form-label">
                        <i class="fas fa-comment-alt"></i> Message *
                      </label>
                      <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>
                  </div>
                </div>

                <div class="form-footer text-center">
                  <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Message</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Office Hours Section -->
    <section class="office-hours-section py-5">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-clock"></i>
          </div>
          <h2 class="section-title">Office Hours</h2>
          <p class="section-subtitle">When you can visit us or call</p>
        </div>
        
        <div class="hours-grid">
          <div class="hours-card animate-slide-up" style="animation-delay: 0.1s;">
            <div class="card-header">
              <div class="office-icon">
                <i class="fas fa-building"></i>
              </div>
              <h3>Administrative Office</h3>
            </div>
            <div class="hours-list">
              <div class="hour-item">
                <div class="day-info">
                  <i class="fas fa-calendar-day"></i>
                  <span class="day">Monday - Friday</span>
                </div>
                <span class="time">8:00 AM - 5:00 PM</span>
              </div>
              <div class="hour-item">
                <div class="day-info">
                  <i class="fas fa-calendar-week"></i>
                  <span class="day">Saturday</span>
                </div>
                <span class="time">9:00 AM - 1:00 PM</span>
              </div>
              <div class="hour-item closed">
                <div class="day-info">
                  <i class="fas fa-calendar-times"></i>
                  <span class="day">Sunday</span>
                </div>
                <span class="time">Closed</span>
              </div>
            </div>
          </div>
          
          <div class="hours-card animate-slide-up" style="animation-delay: 0.2s;">
            <div class="card-header">
              <div class="office-icon">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <h3>Admissions Office</h3>
            </div>
            <div class="hours-list">
              <div class="hour-item">
                <div class="day-info">
                  <i class="fas fa-calendar-day"></i>
                  <span class="day">Monday - Friday</span>
                </div>
                <span class="time">9:00 AM - 4:00 PM</span>
              </div>
              <div class="hour-item">
                <div class="day-info">
                  <i class="fas fa-calendar-week"></i>
                  <span class="day">Saturday</span>
                </div>
                <span class="time">9:00 AM - 1:00 PM</span>
              </div>
              <div class="hour-item closed">
                <div class="day-info">
                  <i class="fas fa-calendar-times"></i>
                  <span class="day">Sunday</span>
                </div>
                <span class="time">Closed</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5">
      <div class="container">
        <div class="section-header text-center">
          <div class="header-icon">
            <i class="fas fa-map-marked-alt"></i>
          </div>
          <h2 class="section-title">Find Us</h2>
          <p class="section-subtitle">Located in the heart of Iganga Town, Eastern Uganda</p>
        </div>
        
        <div class="map-content">
          <div class="map-container animate-fade-in">
            <div class="map-wrapper">
              <iframe 
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7654321098765!2d33.4516861!3d0.5918431!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!2sIganga+School+of+Nursing+%26+Midwifery!5e0!3m2!1sen!2sug!4v1234567890"
                  width="100%" 
                  height="450" 
                  allowfullscreen="" 
                  loading="lazy" 
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Iganga School of Nursing and Midwifery Location">
              </iframe>
            </div>
          </div>
          
          <div class="map-info animate-slide-up" style="animation-delay: 0.3s;">
            <div class="info-header">
              <div class="info-icon">
                <i class="fas fa-location-arrow"></i>
              </div>
              <h3>Visit Iganga School of Nursing and Midwifery</h3>
            </div>
            <p class="info-description">Located in the heart of Iganga Town, Eastern Uganda, our campus provides easy access to quality healthcare education. Use the map above to find directions to our institution.</p>
            <div class="directions-section">
              <div class="direction-item">
                <i class="fas fa-car"></i>
                <span>By Car: 2 hours from Kampala</span>
              </div>
              <div class="direction-item">
                <i class="fas fa-bus"></i>
                <span>By Bus: Regular services from major towns</span>
              </div>
              <div class="direction-item">
                <i class="fas fa-walking"></i>
                <span>Walking: 10 minutes from Iganga town center</span>
              </div>
            </div>
            <div class="directions-btn">
              <a href="https://www.google.com/maps/place/Iganga+School+of+Nursing+%26+Midwifery/@0.5918431,33.4516861,17z/data=!3m1!4b1!4m6!3m5!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!8m2!3d0.5918377!4d33.454261!16s%2Fg%2F11b5ys19t0?hl=en-GB&entry=ttu&g_ep=EgoyMDI2MDQxNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn-directions">
                <i class="fas fa-directions"></i>
                <span>Get Directions</span>
              </a>
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

<style>
    /* Hero Header Styles */
    .hero-header {
      position: relative;
      background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e22ce 100%);
      color: white;
      padding: 6rem 0 4rem;
      overflow: hidden;
    }

    .hero-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.15"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.15"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
      opacity: 0.3;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(30, 60, 114, 0.8), rgba(126, 34, 206, 0.6));
    }

    .hero-particles {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
      animation: float 6s ease-in-out infinite;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
    }

    .hero-title {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      letter-spacing: 2px;
    }

    .hero-subtitle {
      font-size: 1.5rem;
      font-weight: 300;
      margin-bottom: 2rem;
      opacity: 0.9;
    }

    .hero-decoration {
      width: 100px;
      height: 4px;
      background: linear-gradient(90deg, #ffd700, #ffed4e);
      margin: 0 auto;
      border-radius: 2px;
    }

    /* Section Headers */
    .section-header {
      margin-bottom: 4rem;
      padding: 2rem 0;
    }

    .section-header.text-center {
      text-align: center;
    }

    .header-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #7e22ce, #ec4899);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(126, 34, 206, 0.3);
      position: relative;
    }

    .header-icon::before {
      content: '';
      position: absolute;
      top: -5px;
      left: -5px;
      right: -5px;
      bottom: -5px;
      background: linear-gradient(135deg, #7e22ce, #ec4899);
      border-radius: 25px;
      z-index: -1;
      opacity: 0.3;
      animation: pulse 2s ease-in-out infinite;
    }

    .header-icon i {
      font-size: 2.5rem;
      color: white;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1e3c72;
      margin-bottom: 0.5rem;
    }

    .section-subtitle {
      font-size: 1.2rem;
      color: #6c757d;
      margin-top: 0.5rem;
    }

    /* Contact Information Section */
    .contact-info-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 5rem 0;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2.5rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    .contact-card {
      background: white;
      border-radius: 25px;
      padding: 2.5rem;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,0.1);
      border: 1px solid #e9ecef;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .contact-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #7e22ce, #ec4899, #f59e0b, #10b981, #3b82f6, #7e22ce);
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    .contact-card:hover {
      transform: translateY(-15px);
      box-shadow: 0 30px 60px rgba(0,0,0,0.15);
    }

    .contact-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #7e22ce, #6b21a8);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(126, 34, 206, 0.3);
    }

    .contact-icon i {
      font-size: 2.5rem;
      color: white;
    }

    .contact-card h4 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #1e3c72;
      margin-bottom: 1.5rem;
    }

    .contact-details {
      text-align: left;
    }

    .contact-details p {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      color: #495057;
      margin: 0.8rem 0;
      line-height: 1.6;
    }

    .contact-details i {
      color: #7e22ce;
      font-size: 1rem;
      width: 20px;
      flex-shrink: 0;
    }

    /* Contact Form Section */
    .contact-form-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 5rem 0;
    }

    .contact-form-container {
      background: white;
      border-radius: 30px;
      padding: 4rem;
      box-shadow: 0 25px 80px rgba(0,0,0,0.12);
      border: 2px solid #e9ecef;
      position: relative;
      overflow: hidden;
      max-width: 900px;
      margin: 0 auto;
    }

    .contact-form-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #7e22ce, #ec4899, #f59e0b, #10b981, #3b82f6, #7e22ce);
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    .form-section {
      margin-bottom: 2.5rem;
      padding: 2rem;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 20px;
      border: 1px solid #dee2e6;
      transition: all 0.3s ease;
    }

    .form-section:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    .section-title {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #dee2e6;
    }

    .section-title i {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #7e22ce, #6b21a8);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
    }

    .section-title h4 {
      font-size: 1.3rem;
      font-weight: 700;
      color: #1e3c72;
      margin: 0;
    }

    .form-label {
      font-weight: 600;
      color: #495057;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-label i {
      color: #7e22ce;
      font-size: 0.9rem;
    }

    .form-control {
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 0.8rem 1rem;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
    }

    .form-control:focus {
      border-color: #7e22ce;
      box-shadow: 0 0 0 0.2rem rgba(126, 34, 206, 0.15);
      outline: none;
    }

    .form-control:hover {
      border-color: #dee2e6;
    }

    .submit-btn {
      background: linear-gradient(135deg, #7e22ce, #6b21a8);
      color: white;
      border: none;
      padding: 1rem 3rem;
      border-radius: 50px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.8rem;
      box-shadow: 0 10px 30px rgba(126, 34, 206, 0.3);
    }

    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(126, 34, 206, 0.4);
      background: linear-gradient(135deg, #6b21a8, #581c87);
    }

    .submit-btn i {
      font-size: 1.2rem;
    }

    /* Office Hours Section */
    .office-hours-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .hours-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 2rem;
    }

    .hours-card {
      background: white;
      border-radius: 25px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0,0,0,0.1);
      border: 1px solid #e9ecef;
      transition: all 0.3s ease;
    }

    .hours-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #f8f9fa;
    }

    .office-icon {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }

    .office-icon i {
      font-size: 1.5rem;
      color: white;
    }

    .card-header h3 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #1e3c72;
      margin: 0;
    }

    .hours-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .hour-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 0;
      border-bottom: 1px solid #e9ecef;
      transition: all 0.3s ease;
    }

    .hour-item:last-child {
      border-bottom: none;
    }

    .hour-item:hover {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      margin: 0 -1rem;
      padding: 1rem;
      border-radius: 10px;
    }

    .day-info {
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .day-info i {
      color: #7e22ce;
      font-size: 1rem;
      width: 20px;
    }

    .day {
      font-weight: 600;
      color: #495057;
    }

    .time {
      color: #10b981;
      font-weight: 700;
      font-size: 1.1rem;
    }

    .hour-item.closed .time {
      color: #dc3545;
    }

    /* Map Section */
    .map-section {
      background: white;
    }

    .map-content {
      display: grid;
      grid-template-columns: 1.5fr 1fr;
      gap: 3rem;
      align-items: start;
      margin-top: 2rem;
    }

    .map-container {
      border-radius: 25px;
      overflow: hidden;
      box-shadow: 0 30px 80px rgba(0,0,0,0.15);
      border: 2px solid #e9ecef;
      position: relative;
      background: white;
      transition: all 0.3s ease;
    }

    .map-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 40px 100px rgba(0,0,0,0.2);
    }

    .map-wrapper iframe {
      border: none;
      border-radius: 25px;
      width: 100%;
      height: 500px;
    }

    .map-info {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 30px;
      padding: 3rem;
      border: 2px solid #dee2e6;
      position: relative;
      overflow: hidden;
      box-shadow: 0 25px 70px rgba(0,0,0,0.12);
    }

    .map-info::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #f59e0b, #d97706, #10b981, #3b82f6, #7e22ce, #f59e0b);
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    .info-header {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      margin-bottom: 2.5rem;
      padding-bottom: 1.5rem;
      border-bottom: 2px solid #f8f9fa;
    }

    .info-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 12px 35px rgba(245, 158, 11, 0.4);
      position: relative;
    }

    .info-icon::before {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border-radius: 20px;
      z-index: -1;
      opacity: 0.3;
      animation: pulse 2s ease-in-out infinite;
    }

    .info-icon i {
      font-size: 1.8rem;
      color: white;
    }

    .info-header h3 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #1e3c72;
      margin: 0;
      letter-spacing: 0.5px;
    }

    .info-description {
      color: #495057;
      line-height: 1.8;
      margin-bottom: 2.5rem;
      font-size: 1.1rem;
    }

    .directions-section {
      margin-bottom: 2.5rem;
    }

    .direction-item {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      padding: 1.2rem;
      margin-bottom: 1.2rem;
      background: white;
      border-radius: 15px;
      border: 2px solid #e9ecef;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .direction-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(180deg, #f59e0b, #d97706);
      transition: width 0.3s ease;
    }

    .direction-item:hover::before {
      width: 100%;
    }

    .direction-item:hover {
      transform: translateX(8px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      border-color: #f59e0b;
    }

    .direction-item i {
      color: #f59e0b;
      font-size: 1.4rem;
      width: 30px;
      flex-shrink: 0;
    }

    .direction-item span {
      color: #495057;
      font-weight: 600;
      font-size: 1.05rem;
    }

    .directions-btn {
      text-align: center;
      margin-top: 2rem;
    }

    .btn-directions {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      text-decoration: none;
      padding: 1.2rem 3rem;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1.1rem;
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 15px 40px rgba(245, 158, 11, 0.4);
      position: relative;
      overflow: hidden;
    }

    .btn-directions::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s ease;
    }

    .btn-directions:hover::before {
      left: 100%;
    }

    .btn-directions:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 50px rgba(245, 158, 11, 0.5);
      background: linear-gradient(135deg, #d97706, #b45309);
    }

    /* Notification Styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      transform: translateX(400px);
      transition: transform 0.3s ease;
    }

    .notification.show {
      transform: translateX(0);
    }

    .notification-content {
      background: white;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      gap: 1rem;
      border-left: 4px solid #7e22ce;
    }

    .notification.error .notification-content {
      border-left-color: #dc3545;
    }

    .notification i {
      font-size: 1.5rem;
    }

    .notification.error i {
      color: #dc3545;
    }

    .notification:not(.error) i {
      color: #10b981;
    }

    /* Animations */
    @keyframes shimmer {
      0% { background-position: -200% 0; }
      100% { background-position: 200% 0; }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.3; }
      50% { transform: scale(1.05); opacity: 0.5; }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
      0% { opacity: 0; transform: translateY(30px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in {
      animation: fadeIn 0.8s ease-out;
    }

    .animate-slide-up {
      animation: slideUp 0.8s ease-out;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .hero-subtitle {
        font-size: 1.2rem;
      }
      
      .section-title {
        font-size: 2rem;
      }
      
      .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
      
      .hours-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
      
      .map-content {
        grid-template-columns: 1fr;
        gap: 2rem;
      }
      
      .contact-form-container {
        padding: 2.5rem;
      }
      
      .form-section {
        padding: 2rem;
      }
      
      .section-title {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
      }
      
      .hour-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
      
      .direction-item {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
      }
      
      .map-wrapper iframe {
        height: 400px;
      }
    }

    @media (max-width: 480px) {
      .contact-card,
      .hours-card,
      .map-info {
        padding: 2rem;
      }
      
      .contact-form-container {
        padding: 2rem;
      }
      
      .map-wrapper iframe {
        height: 350px;
      }
      
      .info-header {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
      }
      
      .directions-btn {
        margin-top: 1.5rem;
      }
    }

    /* Invalid Form Styles */
    .is-invalid {
      border-color: #dc3545 !important;
      background-color: #fff5f5;
    }

    .is-invalid:focus {
      border-color: #dc3545;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
    }
  </style>

  <?php include('shared/_footer.php'); ?>
