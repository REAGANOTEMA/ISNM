<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
include('shared/_header.php');
?>

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

    <nav aria-label="breadcrumb" class="container mt-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Volunteer</li>
      </ol>
    </nav>

    <!-- Volunteer Overview -->
    <section class="volunteer-overview py-5 animate-on-scroll">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <span class="tag tag-primary"><i class="fas fa-hands-helping"></i> Get Involved</span>
            <h2 class="section-title">Why Volunteer at ISNM?</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">Join our community of passionate healthcare educators and professionals</p>
          </div>
        </div>
        
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="volunteer-content">
              <h3>Make a Meaningful Impact</h3>
              <p>Volunteering at Iganga School of Nursing and Midwifery offers a unique opportunity to contribute to the development of future healthcare professionals while gaining valuable experience in healthcare education.</p>
              
              <div class="benefits-list">
                <div class="benefit-item animate-on-scroll animate-delay-1">
                  <div class="benefit-icon">
                    <i class="fas fa-hands-helping"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Share Your Expertise</h4>
                    <p>Contribute your professional knowledge and skills to train the next generation of healthcare workers</p>
                  </div>
                </div>
                
                <div class="benefit-item animate-on-scroll animate-delay-2">
                  <div class="benefit-icon">
                    <i class="fas fa-users"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Mentor Future Leaders</h4>
                    <p>Guide and inspire students as they prepare for careers in nursing and midwifery</p>
                  </div>
                </div>
                
                <div class="benefit-item animate-on-scroll animate-delay-3">
                  <div class="benefit-icon">
                    <i class="fas fa-certificate"></i>
                  </div>
                  <div class="benefit-text">
                    <h4>Gain Experience</h4>
                    <p>Enhance your teaching and leadership skills in a dynamic educational environment</p>
                  </div>
                </div>
                
                <div class="benefit-item animate-on-scroll animate-delay-4">
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
            <div class="volunteer-image animate-on-scroll">
              <img src="images/students.jpg" alt="Volunteers Teaching" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Opportunities -->
    <section class="volunteer-opportunities py-5 bg-light animate-on-scroll">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <span class="tag tag-primary"><i class="fas fa-briefcase"></i> Opportunities</span>
            <h2 class="section-title">Volunteer Opportunities</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">Find the perfect role that matches your skills and interests</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-1">
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
                <p>Flexible , 4-8 hours per week during clinical placements</p>
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('Clinical Instructor')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-2">
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
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('Guest Lecturer')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-3">
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
                <p>Flexible , 4-6 hours per week</p>
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('IT Support')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-4">
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
                <p>Flexible , 8-12 hours per week</p>
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('Administrative Assistant')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-5">
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
                <p>Event-based , 4-8 hours per screening event</p>
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('Health Screening')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="opportunity-card animate-on-scroll animate-delay-1">
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
                <p>Flexible , 6-10 hours per week</p>
                
                <button class="btn-3d btn-green" onclick="applyVolunteer('Library Assistant')"><span class="shine"></span>Apply Now</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Volunteer Benefits -->
    <section class="volunteer-benefits py-5 animate-on-scroll">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <span class="tag tag-primary"><i class="fas fa-gift"></i> Benefits</span>
            <h2 class="section-title">Volunteer Benefits</h2>
            <div class="section-divider section-divider-center"></div>
            <p class="section-subtitle">What you gain as an ISNM volunteer</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card animate-on-scroll animate-delay-1">
              <div class="benefit-card-icon">
                <i class="fas fa-certificate"></i>
              </div>
              <h4>Certificate of Appreciation</h4>
              <p>Receive official recognition for your valuable contribution to our institution</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card animate-on-scroll animate-delay-2">
              <div class="benefit-card-icon">
                <i class="fas fa-user-tie"></i>
              </div>
              <h4>Professional Development</h4>
              <p>Enhance your skills and gain experience in healthcare education and training</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card animate-on-scroll animate-delay-3">
              <div class="benefit-card-icon">
                <i class="fas fa-network-wired"></i>
              </div>
              <h4>Networking Opportunities</h4>
              <p>Connect with healthcare professionals and educators from various backgrounds</p>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="benefit-card animate-on-scroll animate-delay-4">
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
    <section class="volunteer-application py-5 bg-light animate-on-scroll">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="application-form-container">
              <span class="tag tag-primary"><i class="fas fa-paper-plane"></i> Apply Now</span>
              <h2 class="text-center mb-4">Apply to Volunteer</h2>
              <div class="section-divider section-divider-center"></div>
              <p class="text-center mb-4">Fill out the form below and we'll contact you about available opportunities</p>
              
              <form id="volunteerForm" method="POST" action="process-volunteer.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="volunteerFirstName" class="form-label">First Name *</label>
                    <input type="text" class="form-control" id="volunteerFirstName" name="firstName" 
required maxlength="100">
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerLastName" class="form-label">Last Name *</label>
                    <input type="text" class="form-control" id="volunteerLastName" name="lastName" 
required maxlength="100">
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerEmail" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="volunteerEmail" name="email" 
required maxlength="254">
                  </div>
                  <div class="col-md-6">
                    <label for="volunteerPhone" class="form-label">Phone Number *</label>
                    <input type="tel" class="form-control" id="volunteerPhone" name="phone" required maxlength="20">
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
                      <option value="Event-based">Event based Only</option>
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
                    <textarea class="form-control" id="volunteerSkills" name="skills" rows="3" 
required maxlength="1000" placeholder="Describe your relevant skills, qualifications, and experience..."></textarea>
                  </div>
                  <div class="col-12">
                    <label for="volunteerMotivation" class="form-label">Motivation for Volunteering *</label>
                    <textarea class="form-control" id="volunteerMotivation" name="motivation" 
rows="3" required maxlength="1000" placeholder="Why do you want to volunteer at ISNM?"></textarea>
                  </div>
                  <div class="col-12">
                    <label for="volunteerComments" class="form-label">Additional Comments</label>
                    <textarea class="form-control" id="volunteerComments" name="comments" rows="2" 
maxlength="500" placeholder="Any additional information or special requirements..."></textarea>
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
                    <button type="submit" class="btn-3d btn-yellow btn-3d-lg">
                      <span class="shine"></span><i class="fas fa-paper-plane"></i> Submit Application
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


  <!-- Support CTA Section -->
  <section class="cta-section py-5 bg-primary text-white animate-on-scroll">
    <div class="container text-center">
      <h2 class="mb-4">Support Our Mission</h2>
      <p class="lead mb-4">Your contribution helps us train the next generation of healthcare professionals</p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="donation.php" class="btn-3d btn-blue btn-3d-lg px-5">
          <span class="shine"></span><i class="fas fa-hand-holding-heart me-2"></i> Make a Donation
        </a>
        <a href="application.php" class="btn-3d btn-glass btn-3d-lg px-5">
          <span class="shine"></span><i class="fas fa-paper-plane me-2"></i> Apply Now
        </a>
      </div>
    </div>
  </section>

  <?php include('shared/_footer.php'); ?>