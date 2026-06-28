<?php include('shared/_header.php');?>


  <main>
    <!-- Enhanced Page Header -->
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

    <!-- Enhanced Donation Overview -->
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
            <div class="donation-content content-section">
              <h3>Why Support ISNM?</h3>
              <p>Iganga School of Nursing and Midwifery is committed to producing world class healthcare professionals who serve communities across Uganda and beyond. Your donation helps us:</p>
              
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
              <img src="images/students-in-class.jpg" alt="Students Learning" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Options -->
    <section class="donation-options py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Ways to Give</h2>
            <p class="section-subtitle">Choose how you'd like to support our mission</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="donation-card" data-card="one-time">
              <div class="donation-icon">
                <i class="fas fa-hand-holding-heart"></i>
              </div>
              <h3>One Time Donation</h3>
              <p>Make a single donation to support our immediate needs and ongoing programs</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 50,000</span><span class="amount-desc">Supports student learning materials</span></li>
                <li><span class="amount">UGX 100,000</span><span class="amount-desc">Funds clinical training equipment</span></li>
                <li><span class="amount">UGX 500,000</span><span class="amount-desc">Supports library resources</span></li>
                <li><span class="amount">UGX 1,000,000</span><span class="amount-desc">Funds technology upgrades</span></li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('one-time')">Donate Now</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card" data-card="monthly">
              <div class="donation-icon">
                <i class="fas fa-sync-alt"></i>
              </div>
              <h3>Monthly Giving</h3>
              <p>Provide sustained support through monthly contributions</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 25,000<span class="period">/month</span></span><span class="amount-desc">Student meal support</span></li>
                <li><span class="amount">UGX 50,000<span class="period">/month</span></span><span class="amount-desc">Textbook fund</span></li>
                <li><span class="amount">UGX 100,000<span class="period">/month</span></span><span class="amount-desc">Technology maintenance</span></li>
                <li><span class="amount">UGX 200,000<span class="period">/month</span></span><span class="amount-desc">Scholarship fund</span></li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('monthly')">Give Monthly</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card" data-card="scholarship">
              <div class="donation-icon">
                <i class="fas fa-award"></i>
              </div>
              <h3>Scholarship Fund</h3>
              <p>Support deserving students who cannot afford tuition fees</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 500,000</span><span class="amount-desc">Partial scholarship</span></li>
                <li><span class="amount">UGX 1,000,000</span><span class="amount-desc">Half scholarship</span></li>
                <li><span class="amount">UGX 2,000,000</span><span class="amount-desc">Full semester</span></li>
                <li><span class="amount highlight">UGX 4,000,000</span><span class="amount-desc">Full year scholarship</span></li>
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
                <img src="images/revision-library.jpg" alt="Library" class="img-fluid">
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
                <button class="btn btn-outline-primary" onclick="showDonationForm('project', 'library')">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/computer-students.jpeg" alt="Computer Lab" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Computer Lab Enhancement</h3>
                <p>Upgrade our computer lab with modern systems, high speed internet, and educational software to enhance digital learning.</p>
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
                <button class="btn btn-outline-primary" onclick="showDonationForm('project', 'computer-lab')">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/skills-lab-nurses.jpeg" alt="Skills Lab" class="img-fluid">
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
                <button class="btn btn-outline-primary" onclick="showDonationForm('project', 'skills-lab')">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/diploma-hostel.jpg" alt="Student Hostel" class="img-fluid">
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
                <button class="btn btn-outline-primary" onclick="showDonationForm('project', 'hostel')">Support This Project</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Donation Form Modal -->
    <div class="modal fade" id="donationModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Make Your Donation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="donationForm" method="POST" action="process-donation.php">
              <!-- Personal Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-user me-2"></i>Personal Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="donorName" class="form-label">Full Name *</label>
                  <input type="text" class="form-control" id="donorName" name="donorName" placeholder="Enter your full name" required>
                </div>
                <div class="col-md-6">
                  <label for="donorEmail" class="form-label">Email Address *</label>
                  <input type="email" class="form-control" id="donorEmail" name="donorEmail" placeholder="your.email@example.com" required>
                </div>
                <div class="col-md-6">
                  <label for="donorPhone" class="form-label">Phone Number *</label>
                  <input type="tel" class="form-control" id="donorPhone" name="donorPhone" placeholder="+256 7XX XXX XXX" required>
                </div>
                <div class="col-md-6">
                  <label for="donorAddress" class="form-label">Address (Optional)</label>
                  <input type="text" class="form-control" id="donorAddress" name="donorAddress" placeholder="Your address">
                </div>
              </div>

              <!-- Donation Details -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-hand-holding-heart me-2"></i>Donation Details</h4>
                </div>
                <div class="col-md-6">
                  <label for="donationType" class="form-label">Donation Type *</label>
                  <select class="form-control" id="donationType" name="donationType" required>
                    <option value="">Select Type</option>
                    <option value="one-time">One Time Donation</option>
                    <option value="monthly">Monthly Giving</option>
                    <option value="scholarship">Scholarship Fund</option>
                    <option value="project">Specific Project</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="donationAmount" class="form-label">Amount (UGX) *</label>
                  <input type="number" class="form-control" id="donationAmount" name="amount" min="10000" placeholder="Enter amount in UGX" required>
                </div>
                <div class="col-12">
                  <label for="donationPurpose" class="form-label">Purpose (Optional)</label>
                  <select class="form-control" id="donationPurpose" name="purpose">
                    <option value="">General Support</option>
                    <option value="library">Library Development</option>
                    <option value="computer-lab">Computer Lab Enhancement</option>
                    <option value="skills-lab">Skills Laboratory Equipment</option>
                    <option value="hostel">Student Hostel Construction</option>
                    <option value="scholarship">Student Scholarship</option>
                    <option value="infrastructure">Infrastructure Development</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="donorMessage" class="form-label">Message (Optional)</label>
                  <textarea class="form-control" id="donorMessage" name="notes" rows="3" placeholder="Any message or special instructions..."></textarea>
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

              <!-- Payment Method Selection -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method *</h4>
                  <p class="text-muted">Choose your preferred payment method</p>
                </div>
                <div class="col-12">
                  <div class="payment-methods">
                    <div class="payment-method-card" data-method="visa" onclick="selectPaymentMethod('visa')">
                      <i class="fab fa-cc-visa visa"></i>
                      <span>Visa Card</span>
                    </div>
                    <div class="payment-method-card" data-method="mastercard" onclick="selectPaymentMethod('mastercard')">
                      <i class="fab fa-cc-mastercard mastercard"></i>
                      <span>Mastercard</span>
                    </div>
                    <div class="payment-method-card" data-method="mobile-money" onclick="selectPaymentMethod('mobile-money')">
                      <i class="fas fa-mobile-alt mobile-money"></i>
                      <span>Mobile Money</span>
                    </div>
                    <div class="payment-method-card" data-method="mtn" onclick="selectPaymentMethod('mtn')">
                      <i class="fas fa-phone mtn"></i>
                      <span>MTN MoMo</span>
                    </div>
                    <div class="payment-method-card" data-method="airtel" onclick="selectPaymentMethod('airtel')">
                      <i class="fas fa-phone airtel"></i>
                      <span>Airtel Money</span>
                    </div>
                    <div class="payment-method-card" data-method="bank-transfer" onclick="selectPaymentMethod('bank-transfer')">
                      <i class="fas fa-university"></i>
                      <span>Bank Transfer</span>
                    </div>
                  </div>
                  <input type="hidden" id="selectedPaymentMethod" name="paymentMethod" required>
                </div>
              </div>

              <!-- Credit Card Details (Hidden by default) -->
              <div id="creditCardDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-credit-card me-2"></i>Credit Card Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="cardNumber" class="form-label">Card Number *</label>
                  <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="col-md-6">
                  <label for="cardName" class="form-label">Name on Card *</label>
                  <input type="text" class="form-control" id="cardName" placeholder="John Doe">
                </div>
                <div class="col-md-4">
                  <label for="expiryDate" class="form-label">Expiry Date *</label>
                  <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY" maxlength="5">
                </div>
                <div class="col-md-4">
                  <label for="cvv" class="form-label">CVV *</label>
                  <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="4">
                </div>
                <div class="col-md-4">
                  <label for="billingZip" class="form-label">Billing ZIP *</label>
                  <input type="text" class="form-control" id="billingZip" placeholder="12345">
                </div>
              </div>

              <!-- Mobile Money Details (Hidden by default) -->
              <div id="mobileMoneyDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-mobile-alt me-2"></i>Mobile Money Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="mobileProvider" class="form-label">Mobile Provider *</label>
                  <select class="form-control" id="mobileProvider">
                    <option value="">Select Provider</option>
                    <option value="mtn">MTN Mobile Money</option>
                    <option value="airtel">Airtel Money</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="mobileNumber" class="form-label">Mobile Number *</label>
                  <input type="tel" class="form-control" id="mobileNumber" placeholder="+256 7XX XXX XXX">
                </div>
                <div class="col-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You will receive a prompt on your mobile phone to complete the payment.
                  </div>
                </div>
              </div>

              <!-- Bank Transfer Details (Hidden by default) -->
              <div id="bankTransferDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-university me-2"></i>Bank Transfer Information</h4>
                </div>
                <?php
                $bankAccounts = [
                    'stanbic' => ['name' => 'Stanbic Bank Uganda', 'logo' => 'images/stanbic-logo.jpg', 'account' => '9030001234567', 'swift' => 'SBICUGKX'],
                    'centenary' => ['name' => 'Centenary Bank', 'logo' => 'images/centenary-logo.jpg', 'account' => '3210009876', 'swift' => 'CERBUGKA'],
                    'equity' => ['name' => 'Equity Bank', 'logo' => 'images/equity_logo.png', 'account' => '5010004567', 'swift' => 'EQBLUGKA'],
                    'pearl' => ['name' => 'Pearl Bank', 'logo' => 'images/pearl-logo.png', 'account' => '7120003456', 'swift' => 'PRBLUGKA'],
                    'uba' => ['name' => 'UBA Bank', 'logo' => 'images/uba-bank-logo.png', 'account' => '6010002345', 'swift' => 'UBAFUGKA'],
                ];
                foreach ($bankAccounts as $key => $bank): ?>
                <div class="col-md-6 mb-3">
                  <div class="card border h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                      <img src="<?= htmlspecialchars($bank['logo']) ?>" alt="<?= htmlspecialchars($bank['name']) ?>" style="height: 40px; width: auto; object-fit: contain; border-radius: 4px;">
                      <div>
                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($bank['name']) ?></h6>
                        <small class="text-muted d-block">Account: <?= htmlspecialchars($bank['account']) ?></small>
                        <small class="text-muted d-block">SWIFT: <?= htmlspecialchars($bank['swift']) ?></small>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
                <div class="col-12">
                  <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-1"></i> Please use your donation reference as the payment narrative. After transfer, visit <a href="https://igangaschoolofnursingandmidwifery.ac.ug">our portal</a> to confirm.
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="processDonation()">
              <i class="fas fa-lock me-2"></i>Process Donation
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>

  <script>
    // Generate unique donation reference
    function generateDonationReference() {
      const timestamp = Date.now();
      const random = Math.floor(Math.random() * 1000);
      return `DON-${timestamp}-${random}`;
    }

    // Show donation form with type
    function showDonationForm(type, purpose) {
      console.log('Opening donation form for type:', type, 'purpose:', purpose);
      
      // Set donation type
      const donationTypeField = document.getElementById('donationType');
      if (donationTypeField) {
        donationTypeField.value = type;
      }
      
      // Set donation purpose
      const purposeField = document.getElementById('donationPurpose');
      if (purposeField && purpose) {
        purposeField.value = purpose;
      } else if (purposeField) {
        purposeField.value = '';
      }
      
      // Generate and set reference
      const referenceField = document.getElementById('donationReference');
      if (referenceField) {
        referenceField.textContent = generateDonationReference();
      }
      
      // Reset payment method selection
      document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
      });
      const selectedMethodField = document.getElementById('selectedPaymentMethod');
      if (selectedMethodField) {
        selectedMethodField.value = '';
      }
      
      // Hide all payment detail sections
      const creditCardSection = document.getElementById('creditCardDetails');
      const mobileMoneySection = document.getElementById('mobileMoneyDetails');
      const bankTransferSection = document.getElementById('bankTransferDetails');
      
      if (creditCardSection) creditCardSection.style.display = 'none';
      if (mobileMoneySection) mobileMoneySection.style.display = 'none';
      if (bankTransferSection) bankTransferSection.style.display = 'none';
      
      // Show modal with multiple methods for compatibility
      const modalElement = document.getElementById('donationModal');
      if (modalElement) {
        // Method 1: Bootstrap Modal
        try {
          const modal = new bootstrap.Modal(modalElement);
          modal.show();
          console.log('Bootstrap modal shown');
        } catch (e) {
          console.log('Bootstrap modal failed:', e);
          
          // Method 2: Manual show
          modalElement.classList.add('show');
          modalElement.style.display = 'block';
          document.body.classList.add('modal-open');
          
          // Create backdrop
          const backdrop = document.createElement('div');
          backdrop.className = 'modal-backdrop show';
          backdrop.style.display = 'block';
          document.body.appendChild(backdrop);
          
          console.log('Manual modal shown');
        }
      } else {
        console.error('Modal element not found');
      }
    }

    // Select payment method
    function selectPaymentMethod(method) {
      // Remove previous selection
      document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
      });
      
      // Add selection to clicked card
      document.querySelector(`[data-method="${method}"]`).classList.add('selected');
      document.getElementById('selectedPaymentMethod').value = method;
      
      // Hide all payment detail sections
      document.getElementById('creditCardDetails').style.display = 'none';
      document.getElementById('mobileMoneyDetails').style.display = 'none';
      document.getElementById('bankTransferDetails').style.display = 'none';
      
      // Show relevant payment details
      if (method === 'visa' || method === 'mastercard') {
        document.getElementById('creditCardDetails').style.display = 'block';
      } else if (method === 'mobile-money' || method === 'mtn' || method === 'airtel') {
        document.getElementById('mobileMoneyDetails').style.display = 'block';
        // Auto-select provider if specific method chosen
        if (method === 'mtn') {
          document.getElementById('mobileProvider').value = 'mtn';
        } else if (method === 'airtel') {
          document.getElementById('mobileProvider').value = 'airtel';
        }
      } else if (method === 'bank-transfer') {
        document.getElementById('bankTransferDetails').style.display = 'block';
      }
    }

    // Format card number
    function formatCardNumber(value) {
      const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      const matches = v.match(/\d{4}/g);
      return matches ? matches.join(' ') : v;
    }

    // Format expiry date
    function formatExpiryDate(value) {
      const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      if (v.length >= 2) {
        return v.slice(0, 2) + '/' + v.slice(2, 4);
      }
      return v;
    }

    // Validate credit card
    function validateCreditCard() {
      const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
      const expiryDate = document.getElementById('expiryDate').value;
      const cvv = document.getElementById('cvv').value;
      const billingZip = document.getElementById('billingZip').value;
      
      // Basic validation
      if (cardNumber.length < 13 || cardNumber.length > 19) {
        alert('Please enter a valid card number');
        return false;
      }
      
      if (!expiryDate.match(/^\d{2}\/\d{2}$/)) {
        alert('Please enter a valid expiry date (MM/YY)');
        return false;
      }
      
      if (cvv.length < 3 || cvv.length > 4) {
        alert('Please enter a valid CVV');
        return false;
      }
      
      if (!billingZip.match(/^\d{5}$/)) {
        alert('Please enter a valid ZIP code');
        return false;
      }
      
      return true;
    }

    // Validate mobile money
    function validateMobileMoney() {
      const provider = document.getElementById('mobileProvider').value;
      const mobileNumber = document.getElementById('mobileNumber').value;
      
      if (!provider) {
        alert('Please select a mobile provider');
        return false;
      }
      
      if (!mobileNumber.match(/^\+256\s?7\d{2}\s?\d{3}\s?\d{3}$/)) {
        alert('Please enter a valid Ugandan mobile number (+256 7XX XXX XXX)');
        return false;
      }
      
      return true;
    }

    // Process donation
    function processDonation() {
      const form = document.getElementById('donationForm');
      const submitBtn = document.querySelector('.modal-footer .btn-primary');
      
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
      
      // Check if payment method is selected
      const selectedMethod = document.getElementById('selectedPaymentMethod').value;
      if (!selectedMethod) {
        alert('Please select a payment method');
        return;
      }
      
      // Validate payment method specific fields
      if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
        if (!validateCreditCard()) {
          return;
        }
      } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
        if (!validateMobileMoney()) {
          return;
        }
      }
      
      // Show processing message
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
      
      // Collect donation data
      const donationData = {
        reference: document.getElementById('donationReference').textContent,
        name: document.getElementById('donorName').value,
        email: document.getElementById('donorEmail').value,
        phone: document.getElementById('donorPhone').value,
        address: document.getElementById('donorAddress').value,
        type: document.getElementById('donationType').value,
        amount: document.getElementById('donationAmount').value,
        purpose: document.getElementById('donationPurpose').value,
        message: document.getElementById('donorMessage').value,
        anonymous: document.getElementById('anonymousDonation').checked,
        paymentMethod: selectedMethod,
        timestamp: new Date().toISOString()
      };
      
      // Add payment method specific data
      if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
        donationData.cardNumber = document.getElementById('cardNumber').value;
        donationData.cardName = document.getElementById('cardName').value;
        donationData.expiryDate = document.getElementById('expiryDate').value;
        donationData.cvv = document.getElementById('cvv').value;
        donationData.billingZip = document.getElementById('billingZip').value;
      } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
        donationData.mobileProvider = document.getElementById('mobileProvider').value;
        donationData.mobileNumber = document.getElementById('mobileNumber').value;
      }
      
      // Simulate processing (in real implementation, this would send data to server)
      setTimeout(() => {
        // Show success message based on payment method
        let successMessage = 'Thank you for your donation!\n\n';
        successMessage += `Reference: ${donationData.reference}\n`;
        successMessage += `Amount: UGX ${parseInt(donationData.amount).toLocaleString()}\n\n`;
        
        if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
          successMessage += 'Your credit card payment has been processed successfully.\n';
          successMessage += 'A receipt will be sent to your email.';
        } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
          successMessage += 'Please check your mobile phone for the payment prompt.\n';
          successMessage += 'Complete the payment to finalize your donation.';
        } else if (selectedMethod === 'bank-transfer') {
          successMessage += 'Please complete the bank transfer using the provided details.\n';
          successMessage += 'Send payment confirmation to accounts@isnm.ac.ug';
        }
        
        successMessage += '\nThank you for supporting ISNM!';
        
        alert(successMessage);
        
        // Reset form and close modal
        closeDonationModal();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Process Donation';
        
        // In a real implementation, you would send this data to your server
        console.log('Donation Data:', donationData);
      }, 2000);
    }

    // Close modal function
    function closeDonationModal() {
      const modalElement = document.getElementById('donationModal');
      if (modalElement) {
        // Remove show class
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Reset form
        document.getElementById('donationForm').reset();
        
        console.log('Modal closed');
      }
    }

    // Add input formatting listeners
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded, setting up donation form');
      
      // Card number formatting
      const cardNumberInput = document.getElementById('cardNumber');
      if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
          e.target.value = formatCardNumber(e.target.value);
        });
      }
      
      // Expiry date formatting
      const expiryDateInput = document.getElementById('expiryDate');
      if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
          e.target.value = formatExpiryDate(e.target.value);
        });
      }
      
      // CVV input (numbers only)
      const cvvInput = document.getElementById('cvv');
      if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
      }
      
      // ZIP code input (numbers only)
      const zipInput = document.getElementById('billingZip');
      if (zipInput) {
        zipInput.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
      }
      
      // Setup close buttons
      const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
      closeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          closeDonationModal();
        });
      });
      
      // Close on backdrop click
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
          closeDonationModal();
        }
      });
      
      // Close on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const modal = document.getElementById('donationModal');
          if (modal && modal.classList.contains('show')) {
            closeDonationModal();
          }
        }
      });
    });
  </script>

  
  <?php include('shared/_footer.php'); ?>