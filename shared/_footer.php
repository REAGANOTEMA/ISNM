<!-- Enhanced ISNM Footer with Stripes -->
<footer class="isnm-footer">
  <!-- Top Stripes -->
  <div class="footer-stripes-top"></div>
  
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="footer-logo">
          <img src="images/school-logo.png" alt="ISNM Logo" class="footer-logo-img">
          <h4>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h4>
          <p><i class="fas fa-map-marker-alt"></i> P.O. Box 418, Iganga, Uganda</p>
          <p><i class="fas fa-phone"></i> Tel: 0782 990 403 | 0782 633 253 | 0753 393 340</p>
          <p><i class="fas fa-envelope"></i> Email: iganganursingschool@gmail.com</p>
          <p><i class="fas fa-globe"></i> Website: <a href="https://igangaschoolofnursingandmidwifery.ac.ug" target="_blank" rel="noopener">igangaschoolofnursingandmidwifery.ac.ug</a></p>
          
          <!-- Social Media Links -->
          <div class="social-links">
            <a href="#" class="social-btn"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-btn"><i class="fab fa-linkedin"></i></a>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 col-md-6">
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
      
      <div class="col-lg-4 col-md-12">
        <div class="footer-developer">
          <h5><i class="fas fa-code me-2"></i>Designed and Developed by</h5>
          <div class="developer-info">
            <h6><i class="fas fa-user me-2"></i>Reagan Otema</h6>
            <div class="contact-info">
              <p><i class="fab fa-whatsapp me-2"></i> MTN WhatsApp: <a href="https://wa.me/256772514889" target="_blank">+256772514889</a></p>
              <p><i class="fab fa-whatsapp me-2"></i> Airtel WhatsApp: <a href="https://wa.me/256730314979" target="_blank">+256730314979</a></p>
              <p><i class="fas fa-envelope me-2"></i> Email: <a href="mailto:softwareengineer@igangaschoolofnursingandmidwifery.ac.ug">softwareengineer@igangaschoolofnursingandmidwifery.ac.ug</a></p>
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
          <button id="pwaInstallBtn" onclick="installPWA()" style="display:none;margin-top:8px;padding:4px 14px;font-size:0.8rem;background:var(--primary);color:#fff;border:none;border-radius:4px;cursor:pointer"><i class="fas fa-download me-1"></i>Install App</button>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Enhanced Footer CSS -->
<style>
  /* Footer Design */
  .isnm-footer {
    background: linear-gradient(135deg, var(--isnm-chocolate) 0%, #2E1A17 100%);
    position: relative;
    overflow: hidden;
    color: var(--isnm-cream);
    padding: 0;
    border-top: 3px solid var(--isnm-yellow);
  }
  
  .footer-stripes-top {
    height: 6px;
    background: repeating-linear-gradient(
      90deg,
      var(--isnm-yellow) 0px,
      var(--isnm-yellow) 20px,
      var(--isnm-cream) 20px,
      var(--isnm-cream) 40px,
      var(--isnm-dark-blue) 40px,
      var(--isnm-dark-blue) 60px
    );
  }
  
  .footer-stripes-bottom {
    height: 4px;
    background: repeating-linear-gradient(
      90deg,
      var(--isnm-dark-blue) 0px,
      var(--isnm-dark-blue) 15px,
      var(--isnm-yellow) 15px,
      var(--isnm-yellow) 30px,
      var(--isnm-cream) 30px,
      var(--isnm-cream) 45px
    );
  }
  
  .isnm-footer .container {
    padding: 40px 20px 20px;
    text-align: left;
  }
  
  .isnm-footer h4,
  .isnm-footer h5,
  .isnm-footer h6 {
    color: var(--isnm-yellow);
    font-family: 'Inter', 'Segoe UI', sans-serif;
    font-weight: 600;
  }
  
  .isnm-footer h4 {
    font-size: 1rem;
    margin-bottom: 14px;
  }
  
  .isnm-footer h5 {
    font-size: 0.92rem;
    margin-bottom: 12px;
  }
  
  .isnm-footer h6 {
    font-size: 0.88rem;
    margin-bottom: 8px;
  }
  
  .isnm-footer p,
  .isnm-footer a {
    color: var(--isnm-cream);
    font-family: 'Inter', 'Segoe UI', sans-serif;
    font-size: 0.85rem;
    line-height: 1.7;
  }
  
  .isnm-footer a:hover {
    color: var(--isnm-yellow);
    text-decoration: none;
    transition: color 0.3s ease;
  }
  
  .isnm-footer .footer-links a:hover {
    padding-left: 4px;
  }
  
  .footer-logo-img {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    border: 3px solid var(--isnm-yellow);
    margin-bottom: 12px;
    transition: all 0.3s ease;
  }
  
  .footer-logo-img:hover {
    border-color: var(--isnm-gold);
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
  }
  
  .footer-logo h4 {
    font-size: 0.92rem;
    margin-bottom: 12px;
    line-height: 1.4;
  }
  
  .footer-logo p {
    margin-bottom: 5px;
    font-size: 0.82rem;
  }
  
  .social-links {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    margin-top: 14px !important;
  }
  
  .social-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    color: var(--isnm-cream);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
  }
  
  .social-btn:hover {
    background: var(--isnm-yellow);
    color: var(--isnm-chocolate);
    transform: translateY(-3px);
  }
  
  .footer-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
    columns: 2;
    column-gap: 16px;
  }
  
  .footer-links ul li {
    margin-bottom: 4px;
    break-inside: avoid;
    display: block;
  }
  
  .footer-links ul li a {
    font-size: 0.73rem;
    padding: 2px 0;
    display: inline-block;
    text-decoration: none;
  }
  
  .footer-links ul li a i {
    font-size: 0.5rem;
    margin-right: 4px !important;
    width: 10px;
    text-align: center;
  }
  
  .footer-developer h5 {
    font-size: 0.88rem;
    margin-bottom: 10px;
  }
  
  .developer-info .contact-info p {
    margin-bottom: 4px;
    font-size: 0.8rem;
  }
  
  .developer-info .contact-info a {
    font-size: 0.8rem;
  }
  
  .developer-note {
    margin-top: 8px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.06);
    border-radius: 6px;
  }
  
  .developer-note p {
    margin-bottom: 0;
    font-size: 0.8rem;
  }
  
  .footer-developer .btn-3d.btn-sm {
    padding: 6px 16px;
    font-size: 0.78rem;
  }
  
  .footer-bottom {
    background: rgba(0, 0, 0, 0.25);
    padding: 16px 0;
    text-align: center;
  }
  
  .footer-bottom p {
    margin: 3px 0;
    font-size: 0.8rem;
    opacity: 0.9;
  }
  
  .footer-bottom .motto {
    font-style: italic;
    color: var(--isnm-yellow);
    font-weight: 500;
    opacity: 1;
  }
  
  @media (max-width: 991px) {
    .isnm-footer .container {
      padding: 32px 20px 16px;
    }
    
    .footer-links ul {
      columns: 1;
    }
  }
  
  @media (max-width: 768px) {
    .isnm-footer .container {
      padding: 28px 16px 12px;
    }
    
    .isnm-footer .row > div {
      margin-bottom: 24px;
    }
    
    .isnm-footer .row > div:last-child {
      margin-bottom: 0;
    }
    
    .footer-logo-img {
      width: 60px;
      height: 60px;
    }
    
    .social-links {
      gap: 10px;
      justify-content: center;
    }
    
    .social-btn {
      width: 30px;
      height: 30px;
      font-size: 0.85rem;
    }
    
    .footer-links ul {
      columns: 2;
      column-gap: 16px;
    }
    
    .footer-links ul li a {
      font-size: 0.72rem;
    }
    
    .developer-info .contact-info p {
      font-size: 0.78rem;
    }
  }
  
  @media (max-width: 576px) {
    .isnm-footer .container {
      padding: 24px 14px 10px;
    }
    .footer-links ul {
      columns: 1;
    }
    .footer-logo h4 {
      font-size: 0.88rem;
    }
    .footer-logo p {
      font-size: 0.78rem;
    }
    .footer-bottom p {
      font-size: 0.75rem;
    }
  }
  
  @media (max-width: 360px) {
    .isnm-footer .container {
      padding: 18px 10px 8px;
    }
  
    .footer-logo-img {
      width: 50px;
      height: 50px;
    }
  
    .footer-links ul li a {
      font-size: 0.68rem;
      padding: 3px 0;
    }
  
    .developer-info .contact-info p {
      font-size: 0.7rem;
    }
  
    .developer-info .contact-info a {
      font-size: 0.7rem;
    }
  
    .isnm-footer p,
    .isnm-footer a {
      font-size: 0.75rem;
    }
  
    .footer-bottom p {
      font-size: 0.7rem;
    }
  
    .social-btn {
      width: 28px;
      height: 28px;
      font-size: 0.75rem;
    }
  }
</style>



<div class="isnm-loader" id="isnmLoader">
  <div class="loader-spinner"></div>
  <div class="loader-text">Please wait<span class="loader-dots"></span></div>
</div>

<style>
.isnm-loader {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15,23,42,0.7);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
}
.isnm-loader.active { display: flex; }
.isnm-loader .loader-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(255,255,255,0.12);
    border-top-color: #fff;
    border-radius: 50%;
    animation: isnmSpin 0.5s linear infinite;
}
.isnm-loader .loader-text {
    color: #fff;
    font-size: 15px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    letter-spacing: 0.3px;
}
@keyframes isnmSpin { to { transform: rotate(360deg); } }
</style>

<!-- Font Awesome loaded as CSS/webfont in _header.php → no fetch rejections -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="shared/app.js"></script>
  <script>
    // ── Global Link Loading Animation ──
    (function(){
      var loader = document.getElementById('isnmLoader');
      if (loader) {
        var shown = false;
        function showLoader() { if (!shown) { shown = true; loader.classList.add('active'); } }
        function hideLoader() { shown = false; loader.classList.remove('active'); }
        document.addEventListener('click', function(e) {
          var link = e.target.closest('a');
          if (!link) return;
          var href = link.getAttribute('href') || '';
          if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('http')) return;
          if (link.getAttribute('target') === '_blank') return;
          if (link.hasAttribute('data-no-loader')) return;
          if (link.closest('form')) return;
          if (e.button !== 0) return;
          showLoader();
        });
        window.addEventListener('pageshow', hideLoader);
        window.addEventListener('load', hideLoader);
        window.addEventListener('popstate', hideLoader);
        hideLoader();
      }
    })();

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        var swPath = (window.location.pathname.includes('/ISNM/') ? '/ISNM/' : '/') + 'sw.js';
        var scope = window.location.pathname.includes('/ISNM/') ? '/ISNM/' : '/';
        navigator.serviceWorker.register(swPath, { scope: scope }).catch(function (e) { console.warn('[ISNM] SW reg failed:', e); });
      });
    }

    // PWA Install Prompt Handler
    var deferredPrompt;
    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferredPrompt = e;
      // Show a small install button in the footer
      var btn = document.getElementById('pwaInstallBtn');
      if (btn) btn.style.display = 'inline-block';
    });
    window.addEventListener('appinstalled', function () {
      deferredPrompt = null;
      var btn = document.getElementById('pwaInstallBtn');
      if (btn) { btn.style.display = 'none'; btn.textContent = 'Installed'; }
    });
    function installPWA() {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function(choiceResult) {
        deferredPrompt = null;
        var btn = document.getElementById('pwaInstallBtn');
        if (btn) btn.style.display = 'none';
      });
    }
  </script>
</body>

</html>
