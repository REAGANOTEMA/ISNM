<?php include('shared/_header.php');?>

  <main>
    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h1 class="page-title">Volunteer with ISNM</h1>
            <p class="page-subtitle">Share your skills and make a difference in healthcare education</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Overview -->
    <section class="volunteer-overview py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Why Volunteer at ISNM?</h2>
            <p class="section-subtitle">Join our community of passionate healthcare educators and professionals</p>
          </div>
        </div>
        
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="volunteer-content">
              <h3>Make a Meaningful Impact</h3>
              <p>Volunteering at Iganga School of Nursing and Midwifery offers a unique opportunity to contribute to the development of future healthcare professionals while gaining valuable experience in healthcare education.</p>
              
              <div class="benefits-list">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <i class="fas fa-hands-helping"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Share Your Expertise</h4>
                    <p>Contribute your professional knowledge and skills to train the next generation of healthcare workers</p>
                  </div>
                </div>
                
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <i class="fas fa-users"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Mentor Future Leaders</h4>
                    <p>Guide and inspire students as they prepare for careers in nursing and midwifery</p>
                  </div>
                </div>
                
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <i class="fas fa-certificate"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Gain Experience</h4>
                    <p>Enhance your teaching and leadership skills in a dynamic educational environment</p>
                  </div>
                </div>
                
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <i class="fas fa-heart"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Give Back to Community</h4>
                    <p>Contribute to improving healthcare outcomes in Uganda through education</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="volunteer-image">
              <img src="images/volunteers-teaching.jpg" alt="Volunteers Teaching" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Opportunities -->
    <section class="volunteer-opportunities py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Volunteer Opportunities</h2>
            <p class="section-subtitle">Find the perfect role that matches your skills and interests</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="opportunity-title">
                  <h3>Clinical Instructor</h3>
                  <span class="opportunity-type">Teaching</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Provide clinical instruction and supervision to nursing and midwifery students during their practical training sessions at partner hospitals.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Registered Nurse or Midwife with valid practicing license</li>
                  <li>Minimum 3 years clinical experience</li>
                  <li>Passion for teaching and mentorship</li>
                  <li>Available for clinical supervision sessions</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>Flexible - 4-8 hours per week during clinical placements</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('Clinical Instructor')">Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-book-medical"></i>
                </div>
                <div class="opportunity-title">
                  <h3>Guest Lecturer</h3>
                  <span class="opportunity-type">Teaching</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Deliver specialized lectures on topics related to nursing, midwifery, healthcare management, or related fields.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Expertise in specific healthcare topics</li>
                  <li>Strong presentation and communication skills</li>
                  <li>Ability to simplify complex concepts</li>
                  <li>Professional healthcare background</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>1-2 lectures per month (2-4 hours each)</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('Guest Lecturer')">Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-laptop-code"></i>
                </div>
                <div class="opportunity-title">
                  <h3>IT Support Volunteer</h3>
                  <span class="opportunity-type">Technical</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Assist with maintaining computer labs, troubleshooting technical issues, and training students on digital literacy skills.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Strong IT and computer skills</li>
                  <li>Experience with educational technology</li>
                  <li>Patience in teaching technical concepts</li>
                  <li>Problem-solving abilities</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>Flexible - 4-6 hours per week</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('IT Support')">Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-users-cog"></i>
                </div>
                <div class="opportunity-title">
                  <h3>Administrative Assistant</h3>
                  <span class="opportunity-type">Administrative</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Support administrative operations including student records management, event coordination, and office tasks.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Strong organizational skills</li>
                  <li>Computer literacy (MS Office)</li>
                  <li>Attention to detail</li>
                  <li>Good communication skills</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>Flexible - 8-12 hours per week</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('Administrative Assistant')">Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-heartbeat"></i>
                </div>
                <div class="opportunity-title">
                  <h3>Health Screening Volunteer</h3>
                  <span class="opportunity-type">Healthcare</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Assist with health screening programs for students and community outreach activities.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Healthcare background preferred</li>
                  <li>Basic vital signs measurement skills</li>
                  <li>Good interpersonal skills</li>
                  <li>First aid knowledge (advantage)</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>Event-based - 4-8 hours per screening event</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('Health Screening')">Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card">
              <div class="opportunity-header">
                <div class="opportunity-icon">
                  <i class="fas fa-book"></i>
                </div>
                <div class="opportunity-title">
                  <h3>Library Assistant</h3>
                  <span class="opportunity-type">Educational</span>
                </div>
              </div>
              <div class="opportunity-content">
                <h4>Role Description</h4>
                <p>Help organize library resources, assist students with research, and maintain the study environment.</p>
                
                <h4>Requirements</h4>
                <ul class="requirements-list">
                  <li>Love for books and learning</li>
                  <li>Organizational skills</li>
                  <li>Basic computer skills</li>
                  <li>Student-friendly attitude</li>
                </ul>
                
                <h4>Time Commitment</h4>
                <p>Flexible - 6-10 hours per week</p>
                
                <button class="btn btn-primary" onclick="applyVolunteer('Library Assistant')">Apply Now</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Benefits -->
    <section class="volunteer-benefits py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Volunteer Benefits</h2>
            <p class="section-subtitle">What you gain as an ISNM volunteer</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card">
              <div class="benefit-card-icon">
                <i class="fas fa-certificate"></i>
              </div>
              <h4>Certificate of Appreciation</h4>
              <p>Receive official recognition for your valuable contribution to our institution</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card">
              <div class="benefit-card-icon">
                <i class="fas fa-user-tie"></i>
              </div>
              <h4>Professional Development</h4>
              <p>Enhance your skills and gain experience in healthcare education and training</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card">
              <div class="benefit-card-icon">
                <i class="fas fa-network-wired"></i>
              </div>
              <h4>Networking Opportunities</h4>
              <p>Connect with healthcare professionals and educators from various backgrounds</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card">
              <div class="benefit-card-icon">
                <i class="fas fa-hands-helping"></i>
              </div>
              <h4>Make a Difference</h4>
              <p>Contribute meaningfully to improving healthcare education in Uganda</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Application Form -->
    <section class="volunteer-application py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="application-form-container">
              <h2 class="text-center mb-4">Apply to Volunteer</h2>
              <p class="text-center mb-4">Fill out the form below and we'll contact you about available opportunities</p>
              
              <form id="volunteerForm" method="POST" action="process-volunteer.php">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="volunteerFirstName" class="form-label">First Name *</label>
                    <input type="text" class="form-control" id="volunteerFirstName" name="firstName" required>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerLastName" class="form-label">Last Name *</label>
                    <input type="text" class="form-control" id="volunteerLastName" name="lastName" required>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerEmail" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="volunteerEmail" name="email" required>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerPhone" class="form-label">Phone Number *</label>
                    <input type="tel" class="form-control" id="volunteerPhone" name="phone" required>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerProfession" class="form-label">Profession *</label>
                    <input type="text" class="form-control" id="volunteerProfession" name="profession" required placeholder="e.g., Nurse, Doctor, Teacher, IT Professional">
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerExperience" class="form-label">Years of Experience *</label>
                    <input type="number" class="form-control" id="volunteerExperience" name="experience" min="0" required>
                  </div>
                  <div class="col-12">
                    <label for="volunteerOpportunity" class="form-label">Interested Opportunity *</label>
                    <select class="form-control" id="volunteerOpportunity" name="opportunity" required>
                      <option value="">Select Opportunity</option>
                      <option value="Clinical Instructor">Clinical Instructor</option>
                      <option value="Guest Lecturer">Guest Lecturer</option>
                      <option value="IT Support">IT Support Volunteer</option>
                      <option value="Administrative Assistant">Administrative Assistant</option>
                      <option value="Health Screening">Health Screening Volunteer</option>
                      <option value="Library Assistant">Library Assistant</option>
                      <option value="Other">Other (Specify in comments)</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerAvailability" class="form-label">Availability *</label>
                    <select class="form-control" id="volunteerAvailability" name="availability" required>
                      <option value="">Select Availability</option>
                      <option value="Weekdays">Weekdays Only</option>
                      <option value="Weekends">Weekends Only</option>
                      <option value="Flexible">Flexible</option>
                      <option value="Evenings">Evenings Only</option>
                      <option value="Event-based">Event-based Only</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerDuration" class="form-label">Preferred Duration *</label>
                    <select class="form-control" id="volunteerDuration" name="duration" required>
                      <option value="">Select Duration</option>
                      <option value="1-3 months">1-3 months</option>
                      <option value="3-6 months">3-6 months</option>
                      <option value="6-12 months">6-12 months</option>
                      <option value="1+ year">1+ year</option>
                      <option value="Ongoing">Ongoing</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label for="volunteerSkills" class="form-label">Relevant Skills & Qualifications *</label>
                    <textarea class="form-control" id="volunteerSkills" name="skills" rows="3" required placeholder="Describe your relevant skills, qualifications, and experience..."></textarea>
                  </div>
                  <div class="col-12">
                    <label for="volunteerMotivation" class="form-label">Motivation for Volunteering *</label>
                    <textarea class="form-control" id="volunteerMotivation" name="motivation" rows="3" required placeholder="Why do you want to volunteer at ISNM?"></textarea>
                  </div>
                  <div class="col-12">
                    <label for="volunteerComments" class="form-label">Additional Comments</label>
                    <textarea class="form-control" id="volunteerComments" name="comments" rows="2" placeholder="Any additional information or special requirements..."></textarea>
                  </div>
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="volunteerAgreement" name="agreement" required>
                      <label class="form-check-label" for="volunteerAgreement">
                        I agree to commit my time and skills as described above and understand that this is a voluntary position without monetary compensation
                      </label>
                    </div>
                  </div>
                  <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                      <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <script>
    function applyVolunteer(opportunity) {
      document.getElementById('volunteerOpportunity').value = opportunity;
      document.getElementById('volunteerOpportunity').scrollIntoView({ behavior: 'smooth' });
    }

    // Volunteer form validation
    document.getElementById('volunteerForm').addEventListener('submit', function(e) {
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
      const phone = document.getElementById('volunteerPhone').value.replace(/\s/g, '');
      if (phone.startsWith('+256') && phone.length === 13) {
        // Valid
      } else if (phone.startsWith('0') && phone.length === 10) {
        document.getElementById('volunteerPhone').value = '+256' + phone.substring(1);
      } else {
        e.preventDefault();
        alert('Please enter a valid Ugandan phone number');
        return;
      }
      
      // Show loading state
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    });
  </script>

  <style>
    .page-header {
      background: var(--gradient-primary);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }

    .volunteer-content h3 {
      color: var(--isnm-blue);
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }

    .volunteer-content p {
      color: var(--secondary-color);
      line-height: 1.8;
      margin-bottom: 2rem;
    }

    .benefits-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .benefit-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: var(--light-color);
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .benefit-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .benefit-icon {
      width: 50px;
      height: 50px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      flex-shrink: 0;
    }

    .benefit-icon i {
      font-size: 1.2rem;
      color: white;
    }

    .benefit-text h4 {
      color: var(--isnm-blue);
      margin: 0 0 0.5rem;
      font-size: 1.1rem;
    }

    .benefit-text p {
      margin: 0;
      color: var(--secondary-color);
      line-height: 1.5;
    }

    .opportunity-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: all 0.3s ease;
      height: 100%;
    }

    .opportunity-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .opportunity-header {
      background: var(--gradient-primary);
      color: white;
      padding: 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .opportunity-icon {
      width: 60px;
      height: 60px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .opportunity-icon i {
      font-size: 1.5rem;
      color: white;
    }

    .opportunity-title h3 {
      margin: 0;
      font-size: 1.3rem;
      font-weight: 600;
    }

    .opportunity-type {
      display: inline-block;
      background: rgba(255,255,255,0.2);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      margin-top: 0.5rem;
    }

    .opportunity-content {
      padding: 2rem;
    }

    .opportunity-content h4 {
      color: var(--isnm-blue);
      margin: 1.5rem 0 1rem;
      font-size: 1.1rem;
    }

    .opportunity-content h4:first-child {
      margin-top: 0;
    }

    .opportunity-content p {
      color: var(--secondary-color);
      line-height: 1.6;
      margin-bottom: 1rem;
    }

    .requirements-list {
      list-style: none;
      padding: 0;
      margin: 1rem 0;
    }

    .requirements-list li {
      padding: 0.5rem 0;
      color: var(--secondary-color);
      position: relative;
      padding-left: 1.5rem;
    }

    .requirements-list li:before {
      content: "✓";
      color: var(--success-color);
      position: absolute;
      left: 0;
      font-weight: bold;
    }

    .benefit-card {
      text-align: center;
      padding: 2rem;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      height: 100%;
      transition: all 0.3s ease;
    }

    .benefit-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .benefit-card-icon {
      width: 80px;
      height: 80px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }

    .benefit-card-icon i {
      font-size: 2rem;
      color: white;
    }

    .benefit-card h4 {
      color: var(--isnm-blue);
      margin-bottom: 1rem;
    }

    .benefit-card p {
      color: var(--secondary-color);
      line-height: 1.6;
      margin: 0;
    }

    .application-form-container {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
      .benefit-item {
        flex-direction: column;
        text-align: center;
      }
      
      .benefit-icon {
        margin-right: 0;
        margin-bottom: 1rem;
      }
      
      .opportunity-header {
        flex-direction: column;
        text-align: center;
      }
      
      .opportunity-icon {
        margin-right: 0;
        margin-bottom: 1rem;
      }
      
      .application-form-container {
        padding: 2rem;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
