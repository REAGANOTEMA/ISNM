<?php include('shared/_header.php');?>

  <main>
    <!-- Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h1 class="page-title">ISNM Organizational Structure</h1>
            <p class="page-subtitle">Click on your position to access your personalized dashboard</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Organizational Structure -->
    <section class="org-structure-section py-5">
      <div class="container">
        <div class="row">
          <section class="org-content">
        <div class="container">
          <h2 class="text-center mb-5">ISNM Organizational Structure</h2>
          <p class="text-center mb-5">Click on your position to access your personalized dashboard</p>
          
          <div class="org-chart">
            <!-- Executive Leadership -->
            <div class="org-level">
              <h3>Executive Leadership</h3>
              <div class="org-positions">
                <div class="org-position" onclick="redirectToLogin('Director General')">
                  <div class="position-icon">
                    <i class="fas fa-crown"></i>
                  </div>
                  <div class="position-info">
                    <h4>Director General</h4>
                    <p>Overall Institution Leadership</p>
                  </div>
                  <div class="position-action">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                    <div class="org-position management" onclick="redirectToLogin('Director Academics')">
                      <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                      <h4>Director Academics</h4>
                      <p>Academic Affairs Director</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position management" onclick="redirectToLogin('Director ICT')">
                      <i class="fas fa-laptop fa-2x mb-2"></i>
                      <h4>Director ICT</h4>
                      <p>Technology Director</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position management" onclick="redirectToLogin('Director Finance')">
                      <i class="fas fa-coins fa-2x mb-2"></i>
                      <h4>Director Finance</h4>
                      <p>Financial Affairs Director</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- School Management -->
              <div class="org-level mb-4">
                <h3 class="level-title">School Management</h3>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="org-position management" onclick="redirectToLogin('School Principal')">
                      <i class="fas fa-school fa-2x mb-2"></i>
                      <h4>School Principal</h4>
                      <p>Chief Academic Officer</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="org-position management" onclick="redirectToLogin('Deputy Principal')">
                      <i class="fas fa-user-graduate fa-2x mb-2"></i>
                      <h4>Deputy Principal</h4>
                      <p>Assistant Academic Officer</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="org-position management" onclick="redirectToLogin('School Bursar')">
                      <i class="fas fa-calculator fa-2x mb-2"></i>
                      <h4>School Bursar</h4>
                      <p>Chief Financial Officer</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Administrative Staff -->
              <div class="org-level mb-4">
                <h3 class="level-title">Administrative Staff</h3>
                <div class="row g-3">
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('Academic Registrar')">
                      <i class="fas fa-book fa-2x mb-2"></i>
                      <h4>Academic Registrar</h4>
                      <p>Student Records</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('HR Manager')">
                      <i class="fas fa-users fa-2x mb-2"></i>
                      <h4>HR Manager</h4>
                      <p>Human Resources</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('School Secretary')">
                      <i class="fas fa-file-alt fa-2x mb-2"></i>
                      <h4>School Secretary</h4>
                      <p>Administrative Support</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('School Librarian')">
                      <i class="fas fa-book-open fa-2x mb-2"></i>
                      <h4>School Librarian</h4>
                      <p>Library Management</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Academic Staff -->
              <div class="org-level mb-4">
                <h3 class="level-title">Academic Staff</h3>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="org-position academic" onclick="redirectToLogin('Head of Nursing')">
                      <i class="fas fa-user-nurse fa-2x mb-2"></i>
                      <h4>Head of Nursing</h4>
                      <p>Nursing Department</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="org-position academic" onclick="redirectToLogin('Head of Midwifery')">
                      <i class="fas fa-baby fa-2x mb-2"></i>
                      <h4>Head of Midwifery</h4>
                      <p>Midwifery Department</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('Senior Lecturers')">
                      <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                      <h4>Senior Lecturers</h4>
                      <p>Teaching Staff</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position academic" onclick="redirectToLogin('Lecturers')">
                      <i class="fas fa-chalkboard fa-2x mb-2"></i>
                      <h4>Lecturers</h4>
                      <p>Teaching Staff</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Support Staff -->
              <div class="org-level mb-4">
                <h3 class="level-title">Support Staff</h3>
                <div class="row g-3">
                  <div class="col-md-4">
                    <div class="org-position support" onclick="redirectToLogin('Matrons')">
                      <i class="fas fa-female fa-2x mb-2"></i>
                      <h4>Matrons</h4>
                      <p>Student Welfare</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position support" onclick="redirectToLogin('Lab Technicians')">
                      <i class="fas fa-flask fa-2x mb-2"></i>
                      <h4>Lab Technicians</h4>
                      <p>Laboratory Services</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position support" onclick="redirectToLogin('Drivers')">
                      <i class="fas fa-bus fa-2x mb-2"></i>
                      <h4>Drivers</h4>
                      <p>Transport Services</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="org-position support" onclick="redirectToLogin('Security')">
                      <i class="fas fa-shield-alt fa-2x mb-2"></i>
                      <h4>Security</h4>
                      <p>Campus Security</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Student Leadership -->
              <div class="org-level mb-4">
                <h3 class="level-title">Student Leadership</h3>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="org-position student" onclick="redirectToLogin('Students')">
                      <i class="fas fa-user-graduate fa-2x mb-2"></i>
                      <h4>Students</h4>
                      <p>Student Body</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="org-position student" onclick="redirectToLogin('Guild President')">
                      <i class="fas fa-crown fa-2x mb-2"></i>
                      <h4>Guild President</h4>
                      <p>Student Leadership</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="org-position student" onclick="redirectToLogin('Class Representatives')">
                      <i class="fas fa-users fa-2x mb-2"></i>
                      <h4>Class Representatives</h4>
                      <p>Class Leadership</p>
                      <small><i class="fas fa-sign-in-alt"></i> Login</small>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <div class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-12 text-center">
          <p class="designer-info">
            Designed and Developed by Reagan Otema<br>
            For system errors, contact via WhatsApp<br>
            MTN WhatsApp: <a href="https://wa.me/256772514889" class="text-white">+256772514889</a> | 
            Airtel WhatsApp: <a href="https://wa.me/256730314979" class="text-white">+256730314979</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <script>
    function redirectToLogin(position) {
      // Store the selected position in sessionStorage
      sessionStorage.setItem('selectedPosition', position);
      
      // Redirect to login page
      window.location.href = 'staff-login.php?position=' + encodeURIComponent(position);
    }

    // Add hover effects and animations
    document.addEventListener('DOMContentLoaded', function() {
      const positions = document.querySelectorAll('.org-position');
      
      positions.forEach(position => {
        position.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        position.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    });
  </script>

  <style>
    .page-header {
      background: var(--gradient-primary);
      color: white;
      padding: 3rem 0;
      margin-bottom: 2rem;
    }

    .page-title {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .page-subtitle {
      font-size: 1.1rem;
      opacity: 0.9;
    }

    .org-structure-section {
      background: var(--light-color);
    }

    .level-title {
      color: var(--isnm-blue);
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 3px solid var(--primary-color);
    }

    .org-position {
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .org-position::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s ease;
    }

    .org-position:hover::before {
      left: 100%;
    }

    .org-position:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .org-position i {
      transition: transform 0.3s ease;
    }

    .org-position:hover i {
      transform: scale(1.1);
    }

    @media (max-width: 768px) {
      .page-title {
        font-size: 2rem;
      }
      
      .org-position {
        margin-bottom: 1rem;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
