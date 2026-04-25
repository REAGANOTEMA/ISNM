<?php include('shared/_header.php');?>

  <main>
    <!-- Success Header -->
    <section class="success-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <div class="success-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="page-title">Application Submitted Successfully!</h1>
            <p class="page-subtitle">Thank you for applying to Iganga School of Nursing and Midwifery</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Success Content -->
    <section class="success-content py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="success-card">
              <?php
              if (isset($_SESSION['success_message'])) {
                  echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                  unset($_SESSION['success_message']);
              }
              ?>
              
              <div class="next-steps">
                <h3><i class="fas fa-clipboard-list"></i> Next Steps</h3>
                <ol>
                  <li><strong>Application Review:</strong> Your application will be reviewed by our admissions committee.</li>
                  <li><strong>Interview Invitation:</strong> You will be contacted via phone or email for an interview.</li>
                  <li><strong>Interview:</strong> Attend the scheduled interview at the school campus.</li>
                  <li><strong>Application Fee:</strong> Pay the non-refundable application fee of UGX 95,000.</li>
                  <li><strong>Admission Decision:</strong> Successful candidates will receive admission letters.</li>
                </ol>
              </div>

              <div class="important-info">
                <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                <div class="info-grid">
                  <div class="info-item">
                    <h4>Interview Venue</h4>
                    <p>Iganga Campus<br>
                    Before C.M.S Trading Centre<br>
                    Along Jinja-Iganga Highway<br>
                    After Nekoli Guest House</p>
                  </div>
                  <div class="info-item">
                    <h4>Interview Time</h4>
                    <p>9:00 AM - 4:00 PM<br>
                    Monday - Friday</p>
                  </div>
                  <div class="info-item">
                    <h4>Required Documents</h4>
                    <ul>
                      <li>Original academic certificates</li>
                      <li>Birth certificate</li>
                      <li>National ID</li>
                      <li>Passport-sized photos</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="contact-section">
                <h3><i class="fas fa-phone"></i> Contact Information</h3>
                <p>For any inquiries about your application, please contact:</p>
                <div class="contact-list">
                  <div class="contact-item">
                    <strong>Principal:</strong> 0782 990 403
                  </div>
                  <div class="contact-item">
                    <strong>Deputy Principal:</strong> 0782 633 253
                  </div>
                  <div class="contact-item">
                    <strong>Director:</strong> 0753 393 340
                  </div>
                  <div class="contact-item">
                    <strong>HRM:</strong> 0703 999 796
                  </div>
                </div>
              </div>

              <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                  <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="application.php" class="btn btn-outline-primary">
                  <i class="fas fa-plus"></i> Submit Another Application
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <style>
    .success-header {
      background: linear-gradient(135deg, #198754 0%, #10b981 100%);
      color: white;
      padding: 4rem 0;
      margin-bottom: 2rem;
    }

    .success-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      animation: checkmark 0.5s ease-in-out;
    }

    @keyframes checkmark {
      0% {
        transform: scale(0);
        opacity: 0;
      }
      50% {
        transform: scale(1.2);
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    .success-card {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .next-steps, .important-info, .contact-section {
      margin-bottom: 2rem;
      padding: 2rem;
      background: var(--light-color);
      border-radius: 15px;
      border-left: 4px solid var(--success-color);
    }

    .next-steps h3, .important-info h3, .contact-section h3 {
      color: var(--isnm-blue);
      margin-bottom: 1.5rem;
      font-size: 1.3rem;
    }

    .next-steps ol {
      padding-left: 1.5rem;
    }

    .next-steps li {
      margin-bottom: 1rem;
      line-height: 1.6;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-top: 1rem;
    }

    .info-item {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .info-item h4 {
      color: var(--isnm-blue);
      margin-bottom: 0.5rem;
    }

    .info-item ul {
      padding-left: 1.2rem;
      margin: 0;
    }

    .contact-list {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }

    .contact-item {
      background: white;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }

    .action-buttons {
      text-align: center;
      margin-top: 2rem;
    }

    .action-buttons .btn {
      margin: 0.5rem;
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .action-buttons .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    @media (max-width: 768px) {
      .success-card {
        padding: 2rem;
      }
      
      .info-grid {
        grid-template-columns: 1fr;
      }
      
      .contact-list {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <?php include('shared/_footer.php'); ?>
