<?php include('shared/_header.php');?>

  <main>
    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h1 class="page-title">Contact Us</h1>
            <p class="page-subtitle">Get in touch with Iganga School of Nursing and Midwifery</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Information Section -->
    <section class="contact-info-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Our Contact Information</h2>
            <p class="section-subtitle">We're here to help and answer any questions you might have</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <h4>Address</h4>
              <p>P.O. Box 418, Iganga<br>
              Before C.M.S Trading Centre<br>
              Along Jinja-Iganga Highway<br>
              After Nekoli Guest House</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-phone"></i>
              </div>
              <h4>Phone Numbers</h4>
              <p>Principal: 0782 990 403<br>
              Deputy Principal: 0782 633 253<br>
              Director: 0753 393 340<br>
              HRM: 0703 999 796</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <h4>Email</h4>
              <p>iganganursingschool@gmail.com<br>
              admissions@isnm.ac.ug<br>
              info@isnm.ac.ug</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-globe"></i>
              </div>
              <h4>Website</h4>
              <p>www.isnm.ac.ug<br>
              Follow us on social media<br>
              Facebook: @ISNMUganda</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="contact-form-container">
              <h2 class="text-center mb-4">Send Us a Message</h2>
              <p class="text-center mb-4">Fill out the form below and we'll get back to you as soon as possible</p>
              
              <form id="contactForm" method="POST" action="process-contact.php">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="firstName" class="form-label">First Name *</label>
                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                  </div>
                  <div class="col-md-6">
                    <label for="lastName" class="form-label">Last Name *</label>
                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                  </div>
                  <div class="col-md-6">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                  </div>
                  <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number *</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                  </div>
                  <div class="col-md-12">
                    <label for="subject" class="form-label">Subject *</label>
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
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                  </div>
                  <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                      <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                  </div>
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
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Office Hours</h2>
            <p class="section-subtitle">When you can visit us or call</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6">
            <div class="hours-card">
              <h3><i class="fas fa-clock"></i> Administrative Office</h3>
              <div class="hours-list">
                <div class="hour-item">
                  <span class="day">Monday - Friday</span>
                  <span class="time">8:00 AM - 5:00 PM</span>
                </div>
                <div class="hour-item">
                  <span class="day">Saturday</span>
                  <span class="time">9:00 AM - 1:00 PM</span>
                </div>
                <div class="hour-item">
                  <span class="day">Sunday</span>
                  <span class="time">Closed</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="hours-card">
              <h3><i class="fas fa-graduation-cap"></i> Admissions Office</h3>
              <div class="hours-list">
                <div class="hour-item">
                  <span class="day">Monday - Friday</span>
                  <span class="time">9:00 AM - 4:00 PM</span>
                </div>
                <div class="hour-item">
                  <span class="day">Saturday</span>
                  <span class="time">9:00 AM - 1:00 PM</span>
                </div>
                <div class="hour-item">
                  <span class="day">Sunday</span>
                  <span class="time">Closed</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Find Us</h2>
            <p class="section-subtitle">Located in the heart of Iganga Town, Eastern Uganda</p>
          </div>
        </div>
        
        <div class="row">
          <div class="col-lg-12">
            <!-- Map Section -->
        <div class="map-section">
            <h2 class="section-title">Find Us</h2>
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7654321098765!2d33.4516861!3d0.5918431!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!2sIganga+School+of+Nursing+%26+Midwifery!5e0!3m2!1sen!2sug!4v1234567890"
                    width="100%" 
                    height="450" 
                    style="border:0; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Iganga School of Nursing and Midwifery Location">
                </iframe>
            </div>
            <div class="map-info">
                <h3>Visit Iganga School of Nursing and Midwifery</h3>
                <p>Located in the heart of Iganga Town, Eastern Uganda, our campus provides easy access to quality healthcare education. Use the map above to find directions to our institution.</p>
                <div class="directions-btn">
                    <a href="https://www.google.com/maps/place/Iganga+School+of+Nursing+%26+Midwifery/@0.5918431,33.4516861,17z/data=!3m1!4b1!4m6!3m5!1s0x177ef324132c5553:0x86feaa6ce21fc3a1!8m2!3d0.5918377!4d33.454261!16s%2Fg%2F11b5ys19t0?hl=en-GB&entry=ttu&g_ep=EgoyMDI2MDQxNS4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="btn-directions">
                        <i class="fas fa-directions"></i>
                        Get Directions
                    </a>
                </div>
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
        alert('Please fill in all required fields');
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
        alert('Please enter a valid Ugandan phone number');
        return;
      }
      
      // Show loading state
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    });
  </script>

  <style>
    .page-header {
      background: var(--gradient-primary);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }

    .contact-card {
      text-align: center;
      padding: 2rem;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      height: 100%;
      transition: all 0.3s ease;
    }

    .contact-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .contact-icon {
      width: 80px;
      height: 80px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }

    .contact-icon i {
      font-size: 2rem;
      color: white;
    }

    .contact-card h4 {
      color: var(--isnm-blue);
      margin-bottom: 1rem;
    }

    .contact-card p {
      color: var(--secondary-color);
      margin: 0;
      line-height: 1.6;
    }

    .contact-form-container {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .hours-card {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      height: 100%;
    }

    .hours-card h3 {
      color: var(--isnm-blue);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .hours-card h3 i {
      color: var(--primary-color);
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
      padding: 0.75rem 0;
      border-bottom: 1px solid #e9ecef;
    }

    .hour-item:last-child {
      border-bottom: none;
    }

    .hour-item .day {
      font-weight: 500;
      color: var(--dark-color);
    }

    .hour-item .time {
      color: var(--primary-color);
      font-weight: 600;
    }

    .map-container {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .map-container iframe {
      border: none;
    }

    .directions-btn .btn {
      padding: 12px 30px;
      border-radius: 50px;
      transition: all 0.3s ease;
    }

    .directions-btn .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    @media (max-width: 768px) {
      .contact-form-container {
        padding: 2rem;
      }
      
      .hour-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
