<!-- Enhanced ISNM Footer with Stripes -->
<footer class="isnm-footer">
  <!-- Top Stripes -->
  <div class="footer-stripes-top"></div>
  
  <div class="container">
    <div class="row">
      <div class="col-md-4">
        <div class="footer-logo">
          <img src="images/school-logo.png" alt="ISNM Logo" class="footer-logo-img">
          <h4>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h4>
          <p><i class="fas fa-map-marker-alt"></i> P.O. Box 418, Iganga, Uganda</p>
          <p><i class="fas fa-phone"></i> Tel: 0782 990 403 | 0782 633 253 | 0753 393 340</p>
          <p><i class="fas fa-envelope"></i> Email: iganganursingschool@gmail.com</p>
          <p><i class="fas fa-globe"></i> Website: www.isnm.ac.ug</p>
          
          <!-- Social Media Links -->
          <div class="social-links mt-3">
            <a href="#" class="social-btn"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-linkedin"></i></a>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="footer-links">
          <h5><i class="fas fa-link me-2"></i>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="index.php"><i class="fas fa-home me-2"></i>Home</a></li>
            <li><a href="about.php"><i class="fas fa-info-circle me-2"></i>About Us</a></li>
            <li><a href="history.php"><i class="fas fa-history me-2"></i>School History</a></li>
            <li><a href="programs.php"><i class="fas fa-graduation-cap me-2"></i>Programs</a></li>
            <li><a href="application.php"><i class="fas fa-user-plus me-2"></i>Application</a></li>
            <li><a href="donation.php"><i class="fas fa-hand-holding-heart me-2"></i>Donate</a></li>
            <li><a href="volunteer.php"><i class="fas fa-hands-helping me-2"></i>Volunteer</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope me-2"></i>Contact</a></li>
            <li><a href="organogram.php"><i class="fas fa-sitemap me-2"></i>Organogram</a></li>
            <li><a href="staff-login.php"><i class="fas fa-sign-in-alt me-2"></i>Staff Login</a></li>
          <li><a href="student-login.php"><i class="fas fa-graduation-cap me-2"></i>Student Login</a></li>
          </ul>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="footer-developer">
          <h5><i class="fas fa-code me-2"></i>Designed and Developed by</h5>
          <div class="developer-info">
            <h6><i class="fas fa-user me-2"></i>Reagan Otema</h6>
            <div class="contact-info">
              <p><i class="fab fa-whatsapp me-2"></i> MTN WhatsApp: <a href="https://wa.me/256772514889" target="_blank">+256772514889</a></p>
              <p><i class="fab fa-whatsapp me-2"></i> Airtel WhatsApp: <a href="https://wa.me/256730314979" target="_blank">+256730314979</a></p>
              <p><i class="fas fa-envelope me-2"></i> Email: <a href="mailto:rotema@byupathway.edu">rotema@byupathway.edu</a></p>
            </div>
            <div class="developer-note">
              <p><i class="fas fa-tools me-2"></i> For system errors, contact via WhatsApp</p>
            </div>
            
            <!-- Developer 3D Button -->
            <button class="btn-3d btn-sm mt-3" onclick="window.open('https://wa.me/256772514889', '_blank')">
              <i class="fas fa-comment me-2"></i>Contact Developer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Bottom Stripes -->
  <div class="footer-stripes-bottom"></div>
  
  <div class="footer-bottom">
    <div class="container">
      <div class="row">
        <div class="col-md-12 text-center">
          <p>&copy; <?php echo date('Y'); ?> Iganga School of Nursing and Midwifery. All rights reserved.</p>
          <p class="motto"><i class="fas fa-quote-left me-2"></i>"Chosen to Serve" - Disciplined Mind for Health Action<i class="fas fa-quote-right ms-2"></i></p>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Enhanced Footer CSS -->
<style>
  /* Footer Stripes Design */
  .isnm-footer {
    background: linear-gradient(135deg, var(--isnm-chocolate) 0%, #2E1A17 100%);
    position: relative;
    overflow: hidden;
    color: var(--isnm-cream);
    padding: 0;
  }
  
  .footer-stripes-top {
    height: 8px;
    background: repeating-linear-gradient(
      90deg,
      var(--isnm-yellow) 0px,
      var(--isnm-yellow) 20px,
      var(--isnm-cream) 20px,
      var(--isnm-cream) 40px,
      var(--isnm-dark-blue) 40px,
      var(--isnm-dark-blue) 60px
    );
    animation: stripeMove 3s linear infinite;
  }
  
  .footer-stripes-bottom {
    height: 6px;
    background: repeating-linear-gradient(
      90deg,
      var(--isnm-dark-blue) 0px,
      var(--isnm-dark-blue) 15px,
      var(--isnm-yellow) 15px,
      var(--isnm-yellow) 30px,
      var(--isnm-cream) 30px,
      var(--isnm-cream) 45px
    );
    animation: stripeMoveReverse 4s linear infinite;
  }
  
  .isnm-footer .container {
    padding: 40px 20px 20px;
  }
  
  .isnm-footer h4,
  .isnm-footer h5,
  .isnm-footer h6 {
    color: var(--isnm-yellow);
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
  }
  
  .isnm-footer p,
  .isnm-footer a {
    color: var(--isnm-cream);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.9rem;
  }
  
  .isnm-footer a:hover {
    color: var(--isnm-yellow);
    text-decoration: none;
    transform: translateX(5px);
    transition: all 0.3s ease;
  }
  
  .footer-logo-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid var(--isnm-yellow);
    margin-bottom: 15px;
    animation: logoRotate 20s linear infinite;
    transition: all 0.3s ease;
  }
  
  .footer-logo-img:hover {
    transform: scale(1.1);
    border-color: var(--isnm-gold);
    box-shadow: 0 12px 35px rgba(255, 215, 0, 0.4);
  }
  
  .social-links {
    display: flex;
    gap: 15px;
    justify-content: center;
  }
  
  .social-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--isnm-yellow);
    color: var(--isnm-chocolate);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.2rem;
  }
  
  .social-btn:hover {
    background: var(--isnm-gold);
    color: var(--isnm-cream);
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
  }
  
  .footer-bottom {
    background: rgba(0, 0, 0, 0.3);
    padding: 20px 0;
    text-align: center;
  }
  
  .footer-bottom p {
    margin: 5px 0;
    font-size: 0.85rem;
  }
  
  .footer-bottom .motto {
    font-style: italic;
    color: var(--isnm-yellow);
    font-weight: 500;
  }
  
  .btn-sm {
    padding: 8px 20px;
    font-size: 0.85rem;
  }
  
  @media (max-width: 768px) {
    .isnm-footer .container {
      padding: 30px 15px 15px;
    }
    
    .footer-logo-img {
      width: 60px;
      height: 60px;
    }
    
    .social-links {
      gap: 10px;
    }
    
    .social-btn {
      width: 35px;
      height: 35px;
      font-size: 1rem;
    }
  }
</style>



  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
  <script src="js/bootstrap.bundle.js"></script>
  <script src="./shared/app.js"></script>
</body>

</html>
