<?php include('shared/_header.php');?>

  <main>
      <!-- Page Header -->
      <section class="page-header">
        <div class="container">
          <div class="row">
             <h1 class="page-title">APPLICATION FORM</h1>
              <p class="page-subtitle">IGANGA SCHOOL OF NURSING AND MIDWIFERY</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Application Introduction -->
      <section class="application-intro py-5">
        <div class="container">
          <div class="row">
            <div class="col-lg-12">
              <div class="application-header">
                <div class="school-info">
                  <h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
                  <p>P.O. Box 418, Iganga</p>
                  <p>Tel: 0782 990 403 | 0782 633 253 | 0753 393 340 | 0703 999 796</p>
                  <p>Email: iganganursingschool@gmail.com</p>
                  <p>Website: www.isnm.ac.ug</p>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-lg-12 text-center mb-5">
              <h3 class="section-title">SPECIAL ANNOUNCEMENT</h3>
              <p class="section-subtitle">The Principal of Iganga School of Nursing & Midwifery, Iganga Campus cordially invites suitable applicants for Certificate Courses in Nursing & Midwifery and Diploma Extension programs in Nursing and Midwifery for JUNE/JULY, 2026 INTAKE (PICKING OF APPLICATIONS IS IN PROGRESS)</p>
            </div>
          </div>
          
          <div class="row">
            <div class="col-lg-6">
              <div class="requirements-card">
                <h4>ENTRY REQUIREMENTS</h4>
                
                <div class="requirement-section">
                  <h5>CERTIFICATE LEVEL</h5>
                  <ol>
                    <li>You must have passed "O" Level in English, Mathematics, Biology, Chemistry and Physics at least with a pass or D for candidates of the New Lower Secondary curriculum</li>
                    <li>This MUST be obtained at the same sitting</li>
                    <li>Filled application form (picked from school with attachment of all relevant documents)</li>
                    <li>"A" Level is an added advantage</li>
                  </ol>
                </div>
                
                <div class="requirement-section">
                  <h5>DIPLOMA EXTENSION PROGRAM</h5>
                  <ol>
                    <li>Must have qualified as an Enrolled Nurse, Enrolled Midwife and or Enrolled Comprehensive Nurse from a recognized Institution</li>
                    <li>Must have a pass slip/Transcript and a Certificate of completion from the Uganda Nurses and Midwives Examinations Board (UNMEB)</li>
                    <li>Must have a Certificate of Enrolment and a Valid practicing license from the Uganda Nurses and Midwives Council (UNMC)</li>
                    <li>Must have an experience of two (2) years in the field</li>
                  </ol>
                </div>
              </div>
            </div>
            
            <div class="col-lg-6">
              <div class="interview-info">
                <h4>INTERVIEWS & ADMISSIONS IN PROGRESS FOR JUNE/JULY, 2026 INTAKE</h4>
                <div class="interview-details">
                  <div class="detail-item">
                    <span class="label">VENUE:</span>
                    <span class="value">IGANGA CAMPUS</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">TIME:</span>
                    <span class="value">9:00AM-4:00PM (MONDAY-FRIDAY)</span>
                  </div>
                  <div class="detail-item">
                    <span class="label">FEE:</span>
                    <span class="value">UGX 95,000 (Ninety Five Thousand Shillings Only) NON REFUNDABLE APPLICATION FEE</span>
                  </div>
                </div>
                
                <div class="location-info">
                  <p><strong>The School is located before C.M.S Trading Centre along Jinja-Iganga Highway after Nekoli Guest House</strong></p>
                </div>
                
                <div class="contact-details">
                  <h4>FOR MORE INFORMATION PLEASE CONTACT:</h4>
                  <div class="contact-list">
                    <div class="contact-item">
                      <span class="role">PRINCIPAL:</span>
                      <span class="number">0782 990 403</span>
                    </div>
                    <div class="contact-item">
                      <span class="role">DEPUTY PRINCIPAL:</span>
                      <span class="number">0782 633 253</span>
                    </div>
                    <div class="contact-item">
                      <span class="role">DIRECTOR:</span>
                      <span class="number">0753 393 340</span>
                    </div>
                    <div class="contact-item">
                      <span class="role">HRM:</span>
                      <span class="number">0703 999 796</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Application Form -->
      <section class="application-form py-5">
        <div class="container">
          <div class="row">
            <div class="col-lg-12">
              <div class="form-container">
                <!-- ISNM Header -->
                <div class="isnm-header text-center mb-4">
                  <h2 class="isnm-title">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2>
                  <p class="isnm-subtitle">P.O. Box 418, Iganga | Tel: 0782 990 403 | Email: iganganursingschool@gmail.com</p>
                  <p class="isnm-website">Website: www.isnm.ac.ug</p>
                </div>
                
                <h3 class="text-center mb-4">APPLICATION FORM</h3>
                <p class="text-center mb-4">PLEASE FILL THIS FORM IN CAPITAL LETTERS</p>
                
                <form id="applicationForm" method="POST" action="process-application.php" enctype="multipart/form-data">
                  <!-- Personal Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-user"></i> APPLICANT'S PERSONAL DETAILS</h4>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label for="surname" class="form-label">SURNAME *</label>
                        <input type="text" class="form-control" id="surname" name="surname" required>
                      </div>
                      <div class="col-md-4">
                        <label for="firstName" class="form-label">FIRST NAME *</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                      </div>
                      <div class="col-md-4">
                        <label for="otherName" class="form-label">OTHER NAME</label>
                        <input type="text" class="form-control" id="otherName" name="otherName">
                      </div>
                      <div class="col-md-3">
                        <label for="gender" class="form-label">GENDER *</label>
                        <select class="form-control" id="gender" name="gender" required>
                          <option value="">Select Gender</option>
                          <option value="Male">MALE</option>
                          <option value="Female">FEMALE</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label for="dateOfBirth" class="form-label">DATE OF BIRTH *</label>
                        <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                      </div>
                      <div class="col-md-3">
                        <label for="nationality" class="form-label">NATIONALITY *</label>
                        <input type="text" class="form-control" id="nationality" name="nationality" value="UGANDAN" required>
                      </div>
                      <div class="col-md-3">
                        <label for="countryOfResidence" class="form-label">COUNTRY OF RESIDENCE *</label>
                        <input type="text" class="form-control" id="countryOfResidence" name="countryOfResidence" value="UGANDA" required>
                      </div>
                      <div class="col-md-4">
                        <label for="homeDistrict" class="form-label">HOME DISTRICT</label>
                        <input type="text" class="form-control" id="homeDistrict" name="homeDistrict">
                      </div>
                      <div class="col-md-4">
                        <label for="village" class="form-label">VILLAGE</label>
                        <input type="text" class="form-control" id="village" name="village">
                      </div>
                      <div class="col-md-4">
                        <label for="religion" class="form-label">RELIGIOUS AFFILIATION</label>
                        <input type="text" class="form-control" id="religion" name="religion" placeholder="Specify denomination">
                      </div>
                      <div class="col-md-6">
                        <label for="email" class="form-label">EMAIL ADDRESS *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                      </div>
                      <div class="col-md-6">
                        <label for="contactNumber" class="form-label">TELEPHONE CONTACT *</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contactNumber" placeholder="+256..." required>
                      </div>
                      <div class="col-md-6">
                        <label for="maritalStatus" class="form-label">MARITAL STATUS *</label>
                        <select class="form-control" id="maritalStatus" name="maritalStatus" required>
                          <option value="">Select Status</option>
                          <option value="Single">Single</option>
                          <option value="Married">Married</option>
                          <option value="Other">Other (Specify)</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="spouseName" class="form-label">NAME OF SPOUSE</label>
                        <input type="text" class="form-control" id="spouseName" name="spouseName">
                      </div>
                      <div class="col-md-6">
                        <label for="numberOfChildren" class="form-label">NUMBER OF CHILDREN</label>
                        <input type="number" class="form-control" id="numberOfChildren" name="numberOfChildren" min="0">
                      </div>
                    </div>
                  </div>

                  <!-- Disability Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-accessibility"></i> DISABILITY</h4>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="disability" class="form-label">Do you have any disability? *</label>
                        <select class="form-control" id="disability" name="disability" required>
                          <option value="">Select Option</option>
                          <option value="No">No</option>
                          <option value="Yes">Yes</option>
                        </select>
                      </div>
                      <div class="col-md-6">
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

                  <!-- Fee Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-money-bill"></i> FEES INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="feePayer" class="form-label">Who is expected to pay your fees/tuition? *</label>
                        <select class="form-control" id="feePayer" name="feePayer" required>
                          <option value="">Select Option</option>
                          <option value="Yourself">Yourself</option>
                          <option value="Parent/Guardian">Parent/Guardian</option>
                          <option value="Sponsors">Sponsors</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="parentName" class="form-label">Details of the person responsible for fees payment - Name *</label>
                        <input type="text" class="form-control" id="parentName" name="parentName" required>
                      </div>
                      <div class="col-md-6">
                        <label for="parentNationality" class="form-label">Nationality *</label>
                        <input type="text" class="form-control" id="parentNationality" name="parentNationality" required>
                      </div>
                      <div class="col-md-6">
                        <label for="parentAddress" class="form-label">Address *</label>
                        <input type="text" class="form-control" id="parentAddress" name="parentAddress" required>
                      </div>
                      <div class="col-md-6">
                        <label for="parentPhone" class="form-label">Telephone contact *</label>
                        <input type="tel" class="form-control" id="parentPhone" name="parentPhone" required>
                      </div>
                      <div class="col-md-6">
                        <label for="parentEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="parentEmail" name="parentEmail" required>
                      </div>
                    </div>
                  </div>

                  <!-- Emergency Contact -->
                  <div class="form-section">
                    <h4><i class="fas fa-phone-alt"></i> DETAILS OF EMERGENCY CONTACT INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="emergencyContactName" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="emergencyContactName" name="emergencyContactName" required>
                      </div>
                      <div class="col-md-6">
                        <label for="emergencyContactPhone" class="form-label">Telephone contact *</label>
                        <input type="tel" class="form-control" id="emergencyContactPhone" name="emergencyContactPhone" required>
                      </div>
                      <div class="col-md-6">
                        <label for="emergencyContactEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="emergencyContactEmail" name="emergencyContactEmail" required>
                      </div>
                    </div>
                  </div>

                  <!-- Academic Information -->
                  <div class="form-section">
                    <h4><i class="fas fa-graduation-cap"></i> ACADEMIC INFORMATION</h4>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="levelApplying" class="form-label">CHOICE OF PROGRAMME (Tick one (1) program of your choice) *</label>
                        <select class="form-control" id="levelApplying" name="levelApplying" required onchange="updateProgramOptions()">
                          <option value="">Select Level</option>
                          <option value="Certificate">Certificate Program</option>
                          <option value="Diploma Extension">Diploma Extension Program</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="course" class="form-label">Course *</label>
                        <select class="form-control" id="course" name="course" required>
                          <option value="">Select Course</option>
                          <option value="Nursing">Nursing</option>
                          <option value="Midwifery">Midwifery</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label for="intakePeriod" class="form-label">CHOICE OF INTAKE (Indicate January/July) *</label>
                        <select class="form-control" id="intakePeriod" name="intakePeriod" required>
                          <option value="">Select Intake</option>
                          <option value="January">January</option>
                          <option value="July">July</option>
                        </select>
                      </div>
                    </div>
                  </div>

                <!-- UCE Results (For Certificate Applicants) -->
                <div class="form-section" id="uceSection" style="display: none;">
                  <h4><i class="fas fa-book"></i> UGANDA CERTIFICATE OF EDUCATION (UCE)</h4>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="uceIndexNumber" class="form-label">INDEX NUMBER</label>
                      <input type="text" class="form-control" id="uceIndexNumber" name="uceIndexNumber" required>
                    </div>
                    <div class="col-md-6">
                      <label for="uceYear" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="uceYear" name="uceYear" min="2010" max="2026" required>
                    </div>
                  </div>
                  
                  <div class="row g-3">
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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

                <!-- UACE Results (Optional) -->
                <div class="form-section">
                  <h4><i class="fas fa-book"></i> UGANDA ADVANCED CERTIFICATE OF EDUCATION (UACE) - OPTIONAL</h4>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="uaceIndexNumber" class="form-label">INDEX NUMBER</label>
                      <input type="text" class="form-control" id="uaceIndexNumber" name="uaceIndexNumber">
                    </div>
                    <div class="col-md-6">
                      <label for="uaceYear" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="uaceYear" name="uaceYear" min="2010" max="2026">
                    </div>
                  </div>
                  
                  <div class="alert alert-info">
                    <strong>Note:</strong> Attach a photocopy of the UACE Certificate or equivalent. This is optional but recommended.
                  </div>
                </div>
                        <option value="P8">P8</option>
                        <option value="F9">F9</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label for="ucePhysics" class="form-label">Physics Grade</label>
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
                  </div>
                </div>

                <!-- Diploma Extension Information -->
                <div class="form-section" id="diplomaSection" style="display: none;">
                  <h3><i class="fas fa-certificate"></i> Diploma Extension Information</h3>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="diplomaExamNumber" class="form-label">Diploma Exam Number</label>
                      <input type="text" class="form-control" id="diplomaExamNumber" name="diplomaExamNumber">
                    </div>
                    <div class="col-md-6">
                      <label for="diplomaYearCompletion" class="form-label">Year of Completion</label>
                      <input type="number" class="form-control" id="diplomaYearCompletion" name="diplomaYearCompletion" min="2000" max="2026">
                    </div>
                    <div class="col-md-6">
                      <label for="diplomaYearEntry" class="form-label">Year of Entry</label>
                      <input type="number" class="form-control" id="diplomaYearEntry" name="diplomaYearEntry" min="2000" max="2026">
                    </div>
                    <div class="col-md-6">
                      <label for="practicingLicense" class="form-label">Practicing License Number</label>
                      <input type="text" class="form-control" id="practicingLicense" name="practicingLicense">
                    </div>
                  </div>
                </div>

                <!-- Diploma Extension Results (For Diploma Applicants) -->
                <div class="form-section" id="diplomaSection" style="display: none;">
                  <h4><i class="fas fa-graduation-cap"></i> DIPLOMA EXTENSION QUALIFICATIONS</h4>
                  <div class="alert alert-warning">
                    <strong>FOR ONLY STUDENTS APPLYING FOR DIPLOMA EXTENSION PROGRAM</strong>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="diplomaExamNumber" class="form-label">EXAM NUMBER (NSIN)</label>
                      <input type="text" class="form-control" id="diplomaExamNumber" name="diplomaExamNumber" required>
                    </div>
                    <div class="col-md-6">
                      <label for="diplomaYearCompletion" class="form-label">YEAR OF COMPLETION</label>
                      <input type="number" class="form-control" id="diplomaYearCompletion" name="diplomaYearCompletion" min="2000" max="2026" required>
                    </div>
                    <div class="col-md-6">
                      <label for="diplomaYearEntry" class="form-label">YEAR OF ENTRY</label>
                      <input type="number" class="form-control" id="diplomaYearEntry" name="diplomaYearEntry" min="2000" max="2026" required>
                    </div>
                    <div class="col-md-6">
                      <label for="practicingLicense" class="form-label">PRACTICING LICENSE NUMBER</label>
                      <input type="text" class="form-control" id="practicingLicense" name="practicingLicense" required>
                    </div>
                  </div>
                  
                  <div class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="academicDocument" class="form-label">Upload Academic Document (PDF, JPEG, PNG, DOC) *</label>
                      <input type="file" class="form-control" id="academicDocument" name="academicDocument" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                      <small class="text-muted">Maximum file size: 5MB</small>
                    </div>
                    <div class="col-md-6">
                      <label for="photo" class="form-label">Upload Your Photo *</label>
                      <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                      <small class="text-muted">Passport-sized photo, maximum file size: 2MB</small>
                    </div>
                  </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                  <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                  <div class="row g-3">
                    <div class="col-12">
                      <label for="motivation" class="form-label">Why do you want to undertake this course? (Maximum 100 words)</label>
                      <textarea class="form-control" id="motivation" name="motivation" rows="4" maxlength="500" required placeholder="State your motivation, relevant experience, skills, attributes, and long-term goals..."></textarea>
                      <small class="text-muted"><span id="charCount">0</span>/500 characters</small>
                    </div>
                    <div class="col-12">
                      <label for="disability" class="form-label">Do you have any disability?</label>
                      <select class="form-control" id="disability" name="disability" onchange="toggleDisabilityDetails()">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </div>
                    <div id="disabilityDetails" style="display: none;">
                      <div class="col-md-6">
                        <label for="disabilityType" class="form-label">Type of Disability</label>
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
                        <label for="disabilityDescription" class="form-label">Brief description of disability</label>
                        <textarea class="form-control" id="disabilityDescription" name="disabilityDescription" rows="3"></textarea>
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
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Application
                  </button>
                  <div class="mt-3">
                    <small class="text-muted">
                      Application Fee: UGX 95,000 (Non-refundable)<br>
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

    <!-- Contact Information -->
    <section class="contact-info-section py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h2 class="section-title">Contact Application Office</h2>
            <div class="contact-grid">
              <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h4>Principal</h4>
                <p>0782 990 403</p>
              </div>
              <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h4>Deputy Principal</h4>
                <p>0782 633 253</p>
              </div>
              <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h4>Director</h4>
                <p>0753 393 340</p>
              </div>
              <div class="contact-item">
                <i class="fas fa-phone"></i>
                <h4>HRM</h4>
                <p>0703 999 796</p>
              </div>
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
      
      if (level === 'Certificate') {
        uceSection.style.display = 'block';
        diplomaSection.style.display = 'none';
      } else if (level === 'Diploma Extension') {
        uceSection.style.display = 'none';
        diplomaSection.style.display = 'block';
      } else {
        uceSection.style.display = 'none';
        diplomaSection.style.display = 'none';
      }
    }

    // Form validation
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
      // Basic validation
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
      
      // Check file sizes
      const academicDoc = document.getElementById('academicDocument').files[0];
      const photo = document.getElementById('photo').files[0];
      
      if (academicDoc && academicDoc.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('Academic document must be less than 5MB');
        return;
      }
      
      if (photo && photo.size > 2 * 1024 * 1024) {
        e.preventDefault();
        alert('Photo must be less than 2MB');
        return;
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

  <style>
    .application-header {
      background: var(--gradient-primary);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }

    .application-form-container {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .form-header {
      text-align: center;
      margin-bottom: 3rem;
    }

    .form-header h2 {
      color: var(--isnm-blue);
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    .form-section {
      margin-bottom: 3rem;
      padding: 2rem;
      background: var(--light-color);
      border-radius: 15px;
      border-left: 4px solid var(--primary-color);
    }

    .form-section h3 {
      color: var(--isnm-blue);
      margin-bottom: 1.5rem;
      font-size: 1.3rem;
    }

    .form-section h3 i {
      margin-right: 0.5rem;
    }

    .declaration-box {
      background: #fff3cd;
      border: 1px solid #ffc107;
      border-radius: 10px;
      padding: 1.5rem;
    }

    .declaration-box h4 {
      color: #856404;
      margin-bottom: 1rem;
    }

    .declaration-text {
      color: #856404;
      font-style: italic;
      margin-bottom: 1rem;
    }

    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .contact-item {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
    }

    .contact-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }

    .contact-item i {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 1rem;
    }

    .contact-item h4 {
      color: var(--isnm-blue);
      margin-bottom: 0.5rem;
    }

    .is-invalid {
      border-color: var(--danger-color) !important;
    }

    @media (max-width: 768px) {
      .application-form-container {
        padding: 2rem;
      }
      
      .form-section {
        padding: 1.5rem;
      }
      
      .contact-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
