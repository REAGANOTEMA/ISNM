<?php include('shared/_header.php');?>

  <main>
    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h1 class="page-title">Support ISNM</h1>
            <p class="page-subtitle">Help us train the next generation of healthcare professionals</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Overview -->
    <section class="donation-overview py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Make a Difference</h2>
            <p class="section-subtitle">Your generous support helps us provide quality healthcare education and improve our facilities</p>
          </div>
        </div>
        
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="donation-content">
              <h3>Why Support ISNM?</h3>
              <p>Iganga School of Nursing and Midwifery is committed to producing world-class healthcare professionals who serve communities across Uganda and beyond. Your donation helps us:</p>
              
              <div class="impact-list">
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Quality Education</h4>
                    <p>Provide modern teaching resources and technology for effective learning</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-hospital"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Clinical Training</h4>
                    <p>Support practical training at major hospitals and healthcare facilities</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-laptop"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Technology Infrastructure</h4>
                    <p>Enhance computer labs and digital learning resources</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Student Support</h4>
                    <p>Provide scholarships and financial assistance to deserving students</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="donation-image">
              <img src="images/students-learning.jpg" alt="Students Learning" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Options -->
    <section class="donation-options py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Ways to Give</h2>
            <p class="section-subtitle">Choose how you'd like to support our mission</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-hand-holding-heart"></i>
              </div>
              <h3>One-Time Donation</h3>
              <p>Make a single donation to support our immediate needs and ongoing programs</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 50,000</span> - Supports student learning materials</li>
                <li><span class="amount">UGX 100,000</span> - Funds clinical training equipment</li>
                <li><span class="amount">UGX 500,000</span> - Supports library resources</li>
                <li><span class="amount">UGX 1,000,000</span> - Funds technology upgrades</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('one-time')">Donate Now</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-sync-alt"></i>
              </div>
              <h3>Monthly Giving</h3>
              <p>Provide sustained support through monthly contributions</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 25,000/month</span> - Student meal support</li>
                <li><span class="amount">UGX 50,000/month</span> - Textbook fund</li>
                <li><span class="amount">UGX 100,000/month</span> - Technology maintenance</li>
                <li><span class="amount">UGX 200,000/month</span> - Scholarship fund</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('monthly')">Give Monthly</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-award"></i>
              </div>
              <h3>Scholarship Fund</h3>
              <p>Support deserving students who cannot afford tuition fees</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 500,000</span> - Partial scholarship</li>
                <li><span class="amount">UGX 1,000,000</span> - Half scholarship</li>
                <li><span class="amount">UGX 2,000,000</span> - Full semester</li>
                <li><span class="amount">UGX 4,000,000</span> - Full year scholarship</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('scholarship')">Fund Scholarship</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Specific Projects -->
    <section class="projects-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Support Specific Projects</h2>
            <p class="section-subtitle">Fund our priority development initiatives</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/library.jpg" alt="Library" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Modern Library Development</h3>
                <p>Help us build and equip a modern library with current medical texts, research databases, and study spaces for our students.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 50,000,000</span>
                    <span>Raised: UGX 15,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 30%"></div>
                  </div>
                  <span class="progress-percentage">30% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/computer-lab.jpg" alt="Computer Lab" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Computer Lab Enhancement</h3>
                <p>Upgrade our computer lab with modern systems, high-speed internet, and educational software to enhance digital learning.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 30,000,000</span>
                    <span>Raised: UGX 8,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 27%"></div>
                  </div>
                  <span class="progress-percentage">27% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/skills-lab.jpg" alt="Skills Lab" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Skills Laboratory Equipment</h3>
                <p>Equip our nursing and midwifery skills labs with modern mannequins, simulation equipment, and training supplies.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 40,000,000</span>
                    <span>Raised: UGX 5,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 12.5%"></div>
                  </div>
                  <span class="progress-percentage">12.5% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/student-hostel.jpg" alt="Student Hostel" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Student Hostel Construction</h3>
                <p>Help us complete the construction of a modern girls' hostel to provide safe and comfortable accommodation for our students.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 200,000,000</span>
                    <span>Raised: UGX 50,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 25%"></div>
                  </div>
                  <span class="progress-percentage">25% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Form Modal -->
    <div class="modal fade" id="donationModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Make Your Donation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="donationForm">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="donorName" class="form-label">Full Name *</label>
                  <input type="text" class="form-control" id="donorName" required>
                </div>
                <div class="col-md-6">
                  <label for="donorEmail" class="form-label">Email Address *</label>
                  <input type="email" class="form-control" id="donorEmail" required>
                </div>
                <div class="col-md-6">
                  <label for="donorPhone" class="form-label">Phone Number *</label>
                  <input type="tel" class="form-control" id="donorPhone" required>
                </div>
                <div class="col-md-6">
                  <label for="donationType" class="form-label">Donation Type *</label>
                  <select class="form-control" id="donationType" required>
                    <option value="">Select Type</option>
                    <option value="one-time">One-Time</option>
                    <option value="monthly">Monthly</option>
                    <option value="scholarship">Scholarship</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="donationAmount" class="form-label">Amount (UGX) *</label>
                  <input type="number" class="form-control" id="donationAmount" min="10000" required>
                </div>
                <div class="col-md-6">
                  <label for="paymentMethod" class="form-label">Payment Method *</label>
                  <select class="form-control" id="paymentMethod" required>
                    <option value="">Select Method</option>
                    <option value="mobile-money">Mobile Money</option>
                    <option value="bank-transfer">Bank Transfer</option>
                    <option value="cash">Cash (Visit Campus)</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="donationPurpose" class="form-label">Purpose (Optional)</label>
                  <select class="form-control" id="donationPurpose">
                    <option value="">General Support</option>
                    <option value="library">Library Development</option>
                    <option value="computer-lab">Computer Lab</option>
                    <option value="skills-lab">Skills Laboratory</option>
                    <option value="hostel">Student Hostel</option>
                    <option value="scholarship">Student Scholarship</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="donorMessage" class="form-label">Message (Optional)</label>
                  <textarea class="form-control" id="donorMessage" rows="3" placeholder="Any message or special instructions..."></textarea>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="anonymousDonation">
                    <label class="form-check-label" for="anonymousDonation">
                      Make this donation anonymous
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="processDonation()">Process Donation</button>
          </div>
        </div>
      </div>
    </div>

  </main>

  <script>
    function showDonationForm(type) {
      document.getElementById('donationType').value = type;
      const modal = new bootstrap.Modal(document.getElementById('donationModal'));
      modal.show();
    }

    function processDonation() {
      const form = document.getElementById('donationForm');
      const formData = new FormData(form);
      
      // Basic validation
      const requiredFields = form.querySelectorAll('[required]');
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
        alert('Please fill in all required fields');
        return;
      }
      
      // Show processing message
      const submitBtn = document.querySelector('.modal-footer .btn-primary');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
      
      // Simulate processing (in real implementation, this would send data to server)
      setTimeout(() => {
        alert('Thank you for your donation! We will contact you shortly with payment details.');
        bootstrap.Modal.getInstance(document.getElementById('donationModal')).hide();
        form.reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Process Donation';
      }, 2000);
    }
  </script>

  <style>
    .page-header {
      background: var(--gradient-primary);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }

    .donation-content h3 {
      color: var(--isnm-blue);
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }

    .donation-content p {
      color: var(--secondary-color);
      line-height: 1.8;
      margin-bottom: 2rem;
    }

    .impact-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .impact-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: var(--light-color);
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .impact-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .impact-icon {
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

    .impact-icon i {
      font-size: 1.2rem;
      color: white;
    }

    .impact-text h4 {
      color: var(--isnm-blue);
      margin: 0 0 0.5rem;
      font-size: 1.1rem;
    }

    .impact-text p {
      margin: 0;
      color: var(--secondary-color);
      line-height: 1.5;
    }

    .donation-card {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
      height: 100%;
      transition: all 0.3s ease;
    }

    .donation-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .donation-icon {
      width: 80px;
      height: 80px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }

    .donation-icon i {
      font-size: 2rem;
      color: white;
    }

    .donation-card h3 {
      color: var(--isnm-blue);
      margin-bottom: 1rem;
    }

    .donation-card p {
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .donation-amounts {
      list-style: none;
      padding: 0;
      margin: 0 0 1.5rem;
      text-align: left;
    }

    .donation-amounts li {
      padding: 0.5rem 0;
      color: var(--secondary-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .donation-amounts .amount {
      font-weight: bold;
      color: var(--primary-color);
    }

    .project-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      height: 100%;
    }

    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .project-image {
      height: 200px;
      overflow: hidden;
    }

    .project-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .project-card:hover .project-image img {
      transform: scale(1.05);
    }

    .project-content {
      padding: 1.5rem;
    }

    .project-content h3 {
      color: var(--isnm-blue);
      margin-bottom: 1rem;
    }

    .project-content p {
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .project-progress {
      margin-bottom: 1.5rem;
    }

    .progress-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .progress-info span:first-child {
      color: var(--secondary-color);
    }

    .progress-info span:last-child {
      color: var(--success-color);
      font-weight: bold;
    }

    .progress {
      height: 8px;
      background-color: #e9ecef;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-bar {
      background: var(--gradient-primary);
      transition: width 0.3s ease;
    }

    .progress-percentage {
      font-size: 0.85rem;
      color: var(--secondary-color);
    }

    @media (max-width: 768px) {
      .impact-item {
        flex-direction: column;
        text-align: center;
      }
      
      .impact-icon {
        margin-right: 0;
        margin-bottom: 1rem;
      }
      
      .donation-amounts li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
