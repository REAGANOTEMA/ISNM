<?php include('shared/_header.php');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

  <main>

      <section class="application-intro py-5">
        <div class="container">
          <div class="row">
            <div class="col-lg-6 col-md-12 mb-4">
              <div class="requirements-card animate-fade-in">
                <div class="card-header">
                  <i class="fas fa-graduation-cap"></i>
                  <h4>ENTRY REQUIREMENTS</h4>
                </div>
                
                <div class="requirement-section">
                  <div class="section-header">
                    <i class="fas fa-certificate"></i>
                    <h5>CERTIFICATE LEVEL</h5>
                  </div>
                  <ol class="requirements-list">
                    <li>You must have passed "O" Level in English, Mathematics, Biology, Chemistry and Physics at least with a pass or D for candidates of the New Lower Secondary curriculum</li>
                    <li>This MUST be obtained at the same sitting</li>
                    <li>Filled application form (picked from school with attachment of all relevant documents)</li>
                    <li>"A" Level is an added advantage</li>
                  </ol>
                </div>
                
                <div class="requirement-section">
                  <div class="section-header">
                    <i class="fas fa-medal"></i>
                    <h5>DIPLOMA EXTENSION PROGRAM</h5>
                  </div>
                  <ol class="requirements-list">
                    <li>Must have qualified as an Enrolled Nurse, Enrolled Midwife and or Enrolled Comprehensive Nurse from a recognized Institution</li>
                    <li>Must have a pass slip/Transcript and a Certificate of completion from the Uganda Nurses and Midwives Examinations Board (UNMEB)</li>
                    <li>Must have a Certificate of Enrolment and a Valid practicing license from the Uganda Nurses and Midwives Council (UNMC)</li>
                    <li>Must have an experience of two (2) years in the field</li>
                  </ol>
                </div>
              </div>
            </div>
            
            <div class="col-lg-6 col-md-12 mb-4">
              <div class="interview-card animate-slide-up">
                <div class="card-header">
                  <i class="fas fa-calendar-check"></i>
                  <h4>INTERVIEWS & ADMISSIONS IN PROGRESS</h4>
                  <span class="intake-badge">JUNE/JULY, 2026 INTAKE</span>
                </div>
                
                <div class="interview-details">
                  <div class="detail-item">
                    <div class="detail-icon">
                      <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="detail-content">
                      <span class="label">VENUE:</span>
                      <span class="value">IGANGA CAMPUS</span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-icon">
                      <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-content">
                      <span class="label">TIME:</span>
                      <span class="value">9:00AM to 4:00PM (MONDAY , FRIDAY)</span>
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-icon">
                      <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="detail-content">
                      <span class="label">FEE:</span>
                      <span class="value">UGX 95,000 (NON REFUNDABLE)</span>
                    </div>
                  </div>
                </div>
                
                <div class="location-info">
                  <div class="location-header">
                    <i class="fas fa-location-arrow"></i>
                    <h5>LOCATION</h5>
                  </div>
                  <p>The School is located before C.M.S Trading Centre along Jinja-Iganga Highway after Nekoli Guest House</p>
                </div>
                
                <div class="contact-details">
                  <div class="contact-header">
                    <i class="fas fa-phone-alt"></i>
                    <h5>CONTACT INFORMATION</h5>
                  </div>
                  <div class="contact-grid">
                    <div class="contact-item">
                      <div class="contact-avatar">
                        <i class="fas fa-user-tie"></i>
                      </div>
                      <div class="contact-info">
                        <span class="role">PRINCIPAL</span>
                        <span class="number">0782 990 403</span>
                      </div>
                    </div>
                    <div class="contact-item">
                      <div class="contact-avatar">
                        <i class="fas fa-user-shield"></i>
                      </div>
                      <div class="contact-info">
                        <span class="role">DEPUTY PRINCIPAL</span>
                        <span class="number">0782 633 253</span>
                      </div>
                    </div>
                    <div class="contact-item">
                      <div class="contact-avatar">
                        <i class="fas fa-user-cog"></i>
                      </div>
                      <div class="contact-info">
                        <span class="role">DIRECTOR</span>
                        <span class="number">0753 393 340</span>
                      </div>
                    </div>
                    <div class="contact-item">
                      <div class="contact-avatar">
                        <i class="fas fa-users"></i>
                      </div>
                      <div class="contact-info">
                        <span class="role">HRM</span>
                        <span class="number">0703 999 796</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Perfect Mobile Application Form -->
      <section class="application-form-section py-5">
        <div class="container">
          <div class="row">
            <div class="col-12">
              <div class="form-container animate-fade-in">
                <!-- Mobile-Friendly Form Header -->
                <div class="form-header">
                  <div class="form-logo">
                    <img src="images/school-logo.png" alt="ISNM Logo" style="height: 60px; width: auto; border-radius: 50%; border: 3px solid var(--accent-color);">
                  </div>
                  <h2 class="form-title">APPLICATION FORM</h2>
                  <p class="form-subtitle">IGANGA SCHOOL OF NURSING AND MIDWIFERY</p>
                  <div class="form-instructions">
                    <i class="fas fa-info-circle"></i>
                    <span>PLEASE FILL THIS FORM IN CAPITAL LETTERS</span>
                  </div>
                </div>
                
                <form id="applicationForm" method="POST" action="process-application.php" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <!-- Personal Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-user me-2"></i> APPLICANT'S PERSONAL DETAILS</h4>
                    <div class="row g-3">
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="surname" class="form-label">SURNAME *</label>
                        <input type="text" class="form-control" id="surname" name="surname" required>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="firstName" class="form-label">FIRST NAME *</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="otherName" class="form-label">OTHER NAME</label>
                        <input type="text" class="form-control" id="otherName" name="otherName">
                      </div>
                      <div class="col-lg-3 col-md-6 col-sm-12">
                        <label for="gender" class="form-label">GENDER *</label>
                        <select class="form-control" id="gender" name="gender" required>
                          <option value="">Select Gender</option>
                          <option value="Male">MALE</option>
                          <option value="Female">FEMALE</option>
                        </select>
                      </div>
                      <div class="col-lg-3 col-md-6 col-sm-12">
                        <label for="dateOfBirth" class="form-label">DATE OF BIRTH *</label>
                        <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                      </div>
                      <div class="col-lg-3 col-md-6 col-sm-12">
                        <label for="nationality" class="form-label">NATIONALITY *</label>
                        <input type="text" class="form-control" id="nationality" name="nationality" value="UGANDAN" required>
                      </div>
                      <div class="col-lg-3 col-md-6 col-sm-12">
                        <label for="countryOfResidence" class="form-label">COUNTRY OF RESIDENCE *</label>
                        <input type="text" class="form-control" id="countryOfResidence" name="countryOfResidence" value="UGANDA" required>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="homeDistrict" class="form-label">HOME DISTRICT</label>
                        <input type="text" class="form-control" id="homeDistrict" name="homeDistrict">
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="village" class="form-label">VILLAGE</label>
                        <input type="text" class="form-control" id="village" name="village">
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="religion" class="form-label">RELIGIOUS AFFILIATION</label>
                        <input type="text" class="form-control" id="religion" name="religion" placeholder="Specify denomination">
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="email" class="form-label">EMAIL ADDRESS *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="contactNumber" class="form-label">TELEPHONE CONTACT *</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contactNumber" placeholder="+256..." required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="maritalStatus" class="form-label">MARITAL STATUS *</label>
                        <select class="form-control" id="maritalStatus" name="maritalStatus" required>
                          <option value="">Select Status</option>
                          <option value="Single">Single</option>
                          <option value="Married">Married</option>
                          <option value="Other">Other (Specify)</option>
                        </select>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="spouseName" class="form-label">NAME OF SPOUSE</label>
                        <input type="text" class="form-control" id="spouseName" name="spouseName">
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="numberOfChildren" class="form-label">NUMBER OF CHILDREN</label>
                        <input type="number" class="form-control" id="numberOfChildren" name="numberOfChildren" min="0">
                      </div>
                    </div>
                  </div>

                  <!-- Mobile-Friendly Disability Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-accessibility me-2"></i> DISABILITY</h4>
                    <div class="row g-3">
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="disability" class="form-label">Do you have any disability? *</label>
                        <select class="form-control" id="disability" name="disability" required onchange="toggleDisabilityDetails()">
                          <option value="">Select Option</option>
                          <option value="No">No</option>
                          <option value="Yes">Yes</option>
                        </select>
                      </div>
                    </div>
                    <div id="disabilityDetails" class="row g-3 mt-3" style="display: none;">
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="disabilityType" class="form-label">If yes, state the type of disability</label>
                        <select class="form-control" id="disabilityType" name="disabilityType">
                          <option value="">Select Type</option>
                          <option value="Physical disability">Physical disability</option>
                          <option value="Chronic illness">Chronic illness</option>
                          <option value="Hearing impairment">Hearing impairment</option>
                          <option value="Visual impairment">Visual impairment</option>
                          <option value="Speech impairment">Speech impairment</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-12">
                        <label for="disabilityDescription" class="form-label">Briefly state nature of disability</label>
                        <textarea class="form-control" id="disabilityDescription" name="disabilityDescription" rows="2"></textarea>
                      </div>
                    </div>
                  </div>

                  <!-- Mobile-Friendly Fee Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-money-bill me-2"></i> FEES INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="feePayer" class="form-label">Who is expected to pay your fees/tuition? *</label>
                        <select class="form-control" id="feePayer" name="feePayer" required>
                          <option value="">Select Option</option>
                          <option value="Yourself">Yourself</option>
                          <option value="Parent/Guardian">Parent/Guardian</option>
                          <option value="Sponsors">Sponsors</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="parentName" class="form-label">Details of the person responsible for fees payment , Name *</label>
                        <input type="text" class="form-control" id="parentName" name="parentName" required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="parentNationality" class="form-label">Nationality *</label>
                        <input type="text" class="form-control" id="parentNationality" name="parentNationality" required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="parentAddress" class="form-label">Address *</label>
                        <input type="text" class="form-control" id="parentAddress" name="parentAddress" required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="parentPhone" class="form-label">Telephone contact *</label>
                        <input type="tel" class="form-control" id="parentPhone" name="parentPhone" required>
                      </div>
                      <div class="col-lg-6 col-md-6 col-sm-12">
                        <label for="parentEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="parentEmail" name="parentEmail" required>
                      </div>
                    </div>
                  </div>

                  <!-- Mobile-Friendly Emergency Contact -->
                  <div class="form-section">
                    <h4><i class="fas fa-phone-alt me-2"></i> DETAILS OF EMERGENCY CONTACT INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="emergencyContactName" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="emergencyContactName" name="emergencyContactName" required>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="emergencyContactPhone" class="form-label">Telephone contact *</label>
                        <input type="tel" class="form-control" id="emergencyContactPhone" name="emergencyContactPhone" required>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="emergencyContactEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="emergencyContactEmail" name="emergencyContactEmail" required>
                      </div>
                    </div>
                  </div>

                  <!-- Mobile-Friendly Academic Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-graduation-cap me-2"></i> ACADEMIC INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="levelApplying" class="form-label">CHOICE OF PROGRAMME (Tick one (1) program of your choice) *</label>
                        <select class="form-control" id="levelApplying" name="levelApplying" required onchange="updateProgramOptions()">
                          <option value="">Select Level</option>
                          <option value="Certificate">Certificate Program</option>
                          <option value="Diploma Extension">Diploma Extension Program</option>
                        </select>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="course" class="form-label">Course *</label>
                        <select class="form-control" id="course" name="course" required>
                          <option value="">Select Course</option>
                          <option value="Nursing">Nursing</option>
                          <option value="Midwifery">Midwifery</option>
                        </select>
                      </div>
                      <div class="col-lg-4 col-md-6 col-sm-12">
                        <label for="intakePeriod" class="form-label">CHOICE OF INTAKE (Indicate January/July) *</label>
                        <select class="form-control" id="intakePeriod" name="intakePeriod" required>
                          <option value="">Select Intake</option>
                          <option value="January">January</option>
                          <option value="July">July</option>
                        </select>
                      </div>
                    </div>
                    <div class="row g-3 mt-3">
                      <div class="col-lg-12">
                        <label for="previousSchool" class="form-label">PREVIOUS SCHOOL ATTENDED</label>
                        <input type="text" class="form-control" id="previousSchool" name="previousSchool" placeholder="Name of your most recent school">
                      </div>
                    </div>
                  </div>

                <!-- Mobile-Friendly UCE Results (For Certificate Applicants) -->
                <div class="form-section" id="uceSection" style="display: none;">
                  <h4><i class="fas fa-book me-2"></i> UGANDA CERTIFICATE OF EDUCATION (UCE)</h4>
                  <div class="row g-3">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="uceIndexNumber" class="form-label">INDEX NUMBER</label>
                      <input type="text" class="form-control" id="uceIndexNumber" name="uceIndexNumber" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="uceYear" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="uceYear" name="uceYear" min="2010" max="2026" required>
                    </div>
                  </div>
                  
                  <div class="row g-3">
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="uceEnglish" class="form-label">ENG</label>
                      <select class="form-control" id="uceEnglish" name="uceEnglish" required>
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="uceMath" class="form-label">MATH</label>
                      <select class="form-control" id="uceMath" name="uceMath" required>
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="uceBiology" class="form-label">CHEM</label>
                      <select class="form-control" id="uceBiology" name="uceBiology" required>
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="uceChemistry" class="form-label">PHY</label>
                      <select class="form-control" id="uceChemistry" name="uceChemistry" required>
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="ucePhysics" class="form-label">BEST</label>
                      <select class="form-control" id="ucePhysics" name="ucePhysics">
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 col-6">
                      <label for="uceOther" class="form-label">OTHER</label>
                      <select class="form-control" id="uceOther" name="uceOther">
                        <option value="">Grade</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="C3">C3</option>
                        <option value="C4">C4</option>
                        <option value="C5">C5</option>
                        <option value="C6">C6</option>
                        <option value="P7">P7</option>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="alert alert-info">
                    <strong>Note:</strong> Attach a photo of the UCE Certificate or its equivalent. Strictly photocopy of the Certificate must be attached.
                  </div>
                </div>

                <!-- UACE Results (Optional, for Certificate Applicants) -->
                <div class="form-section" id="uaceSection">
                  <h4><i class="fas fa-book me-2"></i> UGANDA ADVANCED CERTIFICATE OF EDUCATION (UACE) , OPTIONAL</h4>
                  <div class="row g-3">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceIndexNumber" class="form-label">INDEX NUMBER</label>
                      <input type="text" class="form-control" id="uaceIndexNumber" name="uaceIndexNumber">
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSchoolName" class="form-label">SCHOOL</label>
                      <input type="text" class="form-control" id="uaceSchoolName" name="uaceSchoolName">
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceYear" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="uaceYear" name="uaceYear" min="2010" max="2026">
                    </div>
                  </div>
                  <div class="row g-3 mt-3">
                    <h5 class="fw-bold">PRINCIPAL SUBJECTS</h5>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSubject1" class="form-label">SUBJECT 1</label>
                      <input type="text" class="form-control" id="uaceSubject1" name="uaceSubject1" placeholder="e.g. Biology">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                      <label for="uaceGrade1" class="form-label">GRADE</label>
                      <select class="form-control" id="uaceGrade1" name="uaceGrade1">
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="O">O</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSubject2" class="form-label">SUBJECT 2</label>
                      <input type="text" class="form-control" id="uaceSubject2" name="uaceSubject2" placeholder="e.g. Chemistry">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                      <label for="uaceGrade2" class="form-label">GRADE</label>
                      <select class="form-control" id="uaceGrade2" name="uaceGrade2">
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="O">O</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSubject3" class="form-label">SUBJECT 3</label>
                      <input type="text" class="form-control" id="uaceSubject3" name="uaceSubject3" placeholder="e.g. Physics">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                      <label for="uaceGrade3" class="form-label">GRADE</label>
                      <select class="form-control" id="uaceGrade3" name="uaceGrade3">
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="O">O</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-3 mt-3">
                    <h5 class="fw-bold">SUBSIDIARY SUBJECTS</h5>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSubsidiary1" class="form-label">SUBJECT 1</label>
                      <input type="text" class="form-control" id="uaceSubsidiary1" name="uaceSubsidiary1" placeholder="e.g. Subsidiary Math">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                      <label for="uaceSubGrade1" class="form-label">GRADE</label>
                      <select class="form-control" id="uaceSubGrade1" name="uaceSubGrade1">
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="O">O</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                      <label for="uaceSubsidiary2" class="form-label">SUBJECT 2</label>
                      <input type="text" class="form-control" id="uaceSubsidiary2" name="uaceSubsidiary2" placeholder="e.g. General Paper">
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-12">
                      <label for="uaceSubGrade2" class="form-label">GRADE</label>
                      <select class="form-control" id="uaceSubGrade2" name="uaceSubGrade2">
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="O">O</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                  </div>
                  <div class="alert alert-info mt-3">
                    <strong>Note:</strong> Attach a photocopy of the UACE Certificate or equivalent. This is optional but recommended.
                  </div>
                </div>

                <!-- Mobile-Friendly Diploma Extension Results (For Diploma Applicants) -->
                <div class="form-section" id="diplomaSection" style="display: none;">
                  <h4><i class="fas fa-graduation-cap me-2"></i> DIPLOMA EXTENSION QUALIFICATIONS</h4>
                  <div class="alert alert-warning">
                    <strong>FOR ONLY STUDENTS APPLYING FOR DIPLOMA EXTENSION PROGRAM</strong>
                  </div>
                  <div class="row g-3">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="diplomaExamNumber" class="form-label">EXAM NUMBER (NSIN)</label>
                      <input type="text" class="form-control" id="diplomaExamNumber" name="diplomaExamNumber" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="diplomaYearCompletion" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="diplomaYearCompletion" name="diplomaYearCompletion" min="2000" max="2026" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="diplomaYearEntry" class="form-label">YEAR OF ENTRY</label>
                      <input type="number" class="form-control" id="diplomaYearEntry" name="diplomaYearEntry" min="2000" max="2026" required>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                      <label for="practicingLicense" class="form-label">PRACTICING LICENSE NUMBER</label>
                      <input type="text" class="form-control" id="practicingLicense" name="practicingLicense" required>
                    </div>
                  </div>
                  
                  <div class="row g-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                      <label for="diplomaPaper1" class="form-label">PAPER I</label>
                      <select class="form-control" id="diplomaPaper1" name="diplomaPaper1" required>
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                      <label for="diplomaPaper2" class="form-label">PAPER II</label>
                      <select class="form-control" id="diplomaPaper2" name="diplomaPaper2" required>
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="diplomaPaper3" class="form-label">PAPER III</label>
                      <select class="form-control" id="diplomaPaper3" name="diplomaPaper3" required>
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="diplomaOsce" class="form-label">OSPE/USCE</label>
                      <select class="form-control" id="diplomaOsce" name="diplomaOsce" required>
                        <option value="">Grade</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label for="diplomaDistinctions" class="form-label">DISTINCTIONS</label>
                      <input type="number" class="form-control" id="diplomaDistinctions" name="diplomaDistinctions" min="0" required>
                    </div>
                    <div class="col-md-4">
                      <label for="diplomaCredits" class="form-label">CREDITS</label>
                      <input type="number" class="form-control" id="diplomaCredits" name="diplomaCredits" min="0" required>
                    </div>
                    <div class="col-md-4">
                      <label for="diplomaPasses" class="form-label">PASSES</label>
                      <input type="number" class="form-control" id="diplomaPasses" name="diplomaPasses" min="0" required>
                    </div>
                    <div class="col-md-12">
                      <label for="diplomaCgpa" class="form-label">CGPA</label>
                      <input type="text" class="form-control" id="diplomaCgpa" name="diplomaCgpa" required>
                    </div>
                  </div>
                  
                  <div class="alert alert-info">
                    <strong>Please attach a photocopy of the result slip from UNMEB, UNMEB certificate, certificate of enrolment and practicing license from UNMEB, and academic transcript.</strong>
                  </div>
                </div>

                <!-- Sports and Leadership -->
                <div class="form-section">
                  <h4><i class="fas fa-trophy"></i> SPORTS AND LEADERSHIP</h4>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="sportsActivities" class="form-label">Have you taken part in any sports activities? (List and attach sports certificates)</label>
                      <textarea class="form-control" id="sportsActivities" name="sportsActivities" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                      <label for="leadershipPositions" class="form-label">State positions of responsibility held (e.g., Prefect, Sports Captain, Counselor, Minister)</label>
                      <textarea class="form-control" id="leadershipPositions" name="leadershipPositions" rows="3"></textarea>
                    </div>
                  </div>
                </div>

                <!-- Motivation Statement -->
                <div class="form-section">
                  <h4><i class="fas fa-edit"></i> MOTIVATION STATEMENT</h4>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="motivation" class="form-label">State why you want to undertake this course, any relevant experience, skills and attributes and your long-term goals (In not more than 100 words)</label>
                      <textarea class="form-control" id="motivation" name="motivation" rows="4" maxlength="500" required placeholder="State your motivation, relevant experience, skills, attributes, and long-term goals..."></textarea>
                      <small class="text-muted"><span id="charCount">0</span>/500 characters</small>
                    </div>
                  </div>
                </div>

                <!-- Document Uploads -->
                <div class="form-section">
                  <h4><i class="fas fa-upload"></i> DOCUMENT UPLOADS</h4>
                  <div class="alert alert-info">
                    <strong>Ensure all uploaded documents are clear scanned copies (PDF, JPEG, or PNG format).</strong>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="photo" class="form-label">Passport Photo *</label>
                      <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                      <small class="text-muted">Passport-sized photo, max 2MB (JPEG/PNG/GIF)</small>
                    </div>
                    <div class="col-md-6">
                      <label for="academicDocument" class="form-label">Academic Document (Certificates/Transcripts) *</label>
                      <input type="file" class="form-control" id="academicDocument" name="academicDocument" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                      <small class="text-muted">Max 5MB (PDF, JPEG, PNG, DOC, DOCX)</small>
                    </div>
                  </div>
                  <!-- Certificate-level specific documents -->
                  <div id="certDocuments" style="display: none;">
                    <hr>
                    <h5 class="fw-bold mb-3">CERTIFICATE APPLICANTS DOCUMENTS</h5>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="uceCertificateDoc" class="form-label">UCE Certificate (Photocopy)</label>
                        <input type="file" class="form-control" id="uceCertificateDoc" name="uceCertificateDoc" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Scanned copy of UCE certificate</small>
                      </div>
                      <div class="col-md-6">
                        <label for="uaceCertificateDoc" class="form-label">UACE Certificate (if applicable)</label>
                        <input type="file" class="form-control" id="uaceCertificateDoc" name="uaceCertificateDoc" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Optional but recommended</small>
                      </div>
                    </div>
                  </div>
                  <!-- Diploma-level specific documents -->
                  <div id="diplomaDocuments" style="display: none;">
                    <hr>
                    <h5 class="fw-bold mb-3">DIPLOMA EXTENSION APPLICANTS DOCUMENTS</h5>
                    <div class="alert alert-warning">
                      <strong>You must attach ALL of the following documents for your application to be considered:</strong>
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="unmebResultSlip" class="form-label">UNMEB Result Slip *</label>
                        <input type="file" class="form-control" id="unmebResultSlip" name="unmebResultSlip" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Result slip from Uganda Nurses and Midwives Examinations Board</small>
                      </div>
                      <div class="col-md-6">
                        <label for="unmebCertificate" class="form-label">UNMEB Certificate *</label>
                        <input type="file" class="form-control" id="unmebCertificate" name="unmebCertificate" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Certificate of completion from UNMEB</small>
                      </div>
                      <div class="col-md-6">
                        <label for="enrolmentCertificate" class="form-label">Certificate of Enrolment (UNMC) *</label>
                        <input type="file" class="form-control" id="enrolmentCertificate" name="enrolmentCertificate" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Certificate of Enrolment from Uganda Nurses and Midwives Council</small>
                      </div>
                      <div class="col-md-6">
                        <label for="practicingLicenseDoc" class="form-label">Valid Practicing License *</label>
                        <input type="file" class="form-control" id="practicingLicenseDoc" name="practicingLicenseDoc" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Valid practicing license from UNMC</small>
                      </div>
                      <div class="col-md-6">
                        <label for="academicTranscript" class="form-label">Academic Transcript *</label>
                        <input type="file" class="form-control" id="academicTranscript" name="academicTranscript" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Transcript from your training institution</small>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Declaration -->
                <div class="form-section">
                  <div class="declaration-box">
                    <h4><i class="fas fa-exclamation-triangle"></i> Important Notice</h4>
                    <p class="declaration-text">
                      Cases of impersonation, falsification of documents, or giving false/incomplete information whenever discovered, either at registration or afterwards, will lead to automatic cancellation of admission and prosecution in the Uganda Courts of Law.
                    </p>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="declaration" name="declaration" required>
                      <label class="form-check-label" for="declaration">
                        I have read and understood the above declaration and confirm that all information provided is true and accurate
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="form-section text-center">
                  <button type="submit" class="btn-3d btn-yellow btn-3d-lg">
                    <i class="fas fa-paper-plane"></i> Submit Application<span class="shine"></span>
                  </button>
                  <div class="mt-3">
                    <small class="text-muted">
                      Application Fee: UGX 95,000 (Non refundable)<br>
                      You will be contacted for interview and fee payment after submission
                    </small>
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
    // Character counter for motivation field
    document.getElementById('motivation').addEventListener('input', function() {
      const charCount = this.value.length;
      document.getElementById('charCount').textContent = charCount;
    });

    // Toggle disability details
    function toggleDisabilityDetails() {
      const disability = document.getElementById('disability').value;
      const details = document.getElementById('disabilityDetails');
      details.style.display = disability === 'Yes' ? 'block' : 'none';
    }

    // Update program options based on level
    function updateProgramOptions() {
      const level = document.getElementById('levelApplying').value;
      const uceSection = document.getElementById('uceSection');
      const diplomaSection = document.getElementById('diplomaSection');
      const certDocuments = document.getElementById('certDocuments');
      const diplomaDocuments = document.getElementById('diplomaDocuments');
      
      if (level === 'Certificate') {
        uceSection.style.display = 'block';
        diplomaSection.style.display = 'none';
        if (certDocuments) certDocuments.style.display = 'block';
        if (diplomaDocuments) diplomaDocuments.style.display = 'none';
      } else if (level === 'Diploma Extension') {
        uceSection.style.display = 'none';
        diplomaSection.style.display = 'block';
        if (certDocuments) certDocuments.style.display = 'none';
        if (diplomaDocuments) diplomaDocuments.style.display = 'block';
      } else {
        uceSection.style.display = 'none';
        diplomaSection.style.display = 'none';
        if (certDocuments) certDocuments.style.display = 'none';
        if (diplomaDocuments) diplomaDocuments.style.display = 'none';
      }
    }

    // Form validation
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
      const level = document.getElementById('levelApplying').value;
      
      // Basic validation
      const requiredFields = this.querySelectorAll('[required]');
      let isValid = true;
      
      requiredFields.forEach(field => {
        if (field.offsetParent === null || field.closest('[style*="display: none"]')) return;
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('is-invalid');
        } else {
          field.classList.remove('is-invalid');
        }
      });
      
      // Validate diploma document uploads if Diploma Extension is selected
      if (level === 'Diploma Extension') {
        const diplomaUploads = ['unmebResultSlip', 'unmebCertificate', 'enrolmentCertificate', 'practicingLicenseDoc', 'academicTranscript'];
        for (const id of diplomaUploads) {
          const input = document.getElementById(id);
          if (input && !input.files.length) {
            isValid = false;
            input.classList.add('is-invalid');
          } else if (input) {
            input.classList.remove('is-invalid');
          }
        }
      }
      
      if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields and upload all required documents');
        return;
      }
      
      // Check file sizes
      const maxSize = 5 * 1024 * 1024;
      const fileInputs = this.querySelectorAll('input[type="file"]');
      for (const input of fileInputs) {
        if (input.files.length && input.files[0].size > maxSize) {
          e.preventDefault();
          alert('File ' + input.files[0].name + ' exceeds the 5MB limit');
          return;
        }
      }
      
      // Show loading state
      const submitBtn = this.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    });

    // Phone number validation
    document.getElementById('contactNumber').addEventListener('input', function() {
      const value = this.value.replace(/\s/g, '');
      if (value.startsWith('+256') && value.length === 13) {
        this.setCustomValidity('');
      } else if (value.startsWith('0') && value.length === 10) {
        this.value = '+256' + value.substring(1);
        this.setCustomValidity('');
      } else {
        this.setCustomValidity('Please enter a valid Ugandan phone number');
      }
    });
  </script>


  <?php include('shared/_footer.php'); ?>