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
            <a href="https://www.facebook.com/igangaschoolofnursing" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
            <a href="https://twitter.com/iganganursing" target="_blank" rel="noopener" class="social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/igangaschoolofnursing" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://www.linkedin.com/company/igangaschoolofnursing" target="_blank" rel="noopener" class="social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 col-md-6">
        <div class="footer-links">
          <h5><i class="fas fa-link me-2"></i>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li><a href="programs.php"><i class="fas fa-graduation-cap"></i> Programs</a></li>
            <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
            <li><a href="news.php"><i class="fas fa-newspaper"></i> News</a></li>
            <li><a href="application.php"><i class="fas fa-user-plus"></i> Apply</a></li>
            <li><a href="donation.php"><i class="fas fa-hand-holding-heart"></i> Donate</a></li>
            <li><a href="volunteer.php"><i class="fas fa-hands-helping"></i> Volunteer</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
            <li><a href="organogram.php"><i class="fas fa-sitemap"></i> Organogram</a></li>
            <li><a href="staff-login.php"><i class="fas fa-sign-in-alt"></i> Staff Login</a></li>
            <li><a href="student-login.php"><i class="fas fa-user-graduate"></i> Student Login</a></li>
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
  
  /* ════════════════════════════════════════════════════════════
     TABLET (< 992px) — 2-column grid for all sections
     ════════════════════════════════════════════════════════════ */
  @media (max-width: 991px) {
    .isnm-footer .container {
      padding: 28px 16px 16px;
    }
    .isnm-footer .row.g-4 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .isnm-footer .row.g-4 > .col-lg-4:last-child {
      grid-column: 1 / -1;
    }
    .isnm-footer .row > div {
      padding-left: 8px;
      padding-right: 8px;
    }
    .footer-logo {
      text-align: left;
    }
    .footer-logo-img {
      width: 56px;
      height: 56px;
    }
    .footer-logo h4 {
      font-size: 0.88rem;
      line-height: 1.3;
    }
    .footer-logo p {
      font-size: 0.78rem;
      word-break: break-word;
      line-height: 1.5;
      margin-bottom: 3px;
    }
    .footer-logo p i {
      width: 16px;
      text-align: center;
      margin-right: 6px !important;
    }
    .social-links {
      justify-content: flex-start;
      gap: 8px;
      margin-top: 10px !important;
    }
    .social-btn {
      width: 36px;
      height: 36px;
      font-size: 0.9rem;
      min-width: 36px;
      min-height: 36px;
    }
    .isnm-footer h5 {
      font-size: 0.85rem;
      margin-bottom: 10px;
    }
    .footer-links ul {
      columns: 2;
      column-gap: 10px;
    }
    .footer-links ul li {
      break-inside: avoid;
    }
    .footer-links ul li a {
      font-size: 0.74rem;
      padding: 4px 0;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      line-height: 1.4;
    }
    .footer-links ul li a i {
      font-size: 0.5rem;
      width: 12px;
      text-align: center;
      margin-right: 3px !important;
      flex-shrink: 0;
    }
    .footer-developer h5 {
      font-size: 0.82rem;
      margin-bottom: 8px;
    }
    .footer-developer h6 {
      font-size: 0.82rem;
      margin-bottom: 6px;
    }
    .developer-info .contact-info p {
      font-size: 0.76rem;
      line-height: 1.4;
      word-break: break-word;
      margin-bottom: 3px;
    }
    .developer-info .contact-info a {
      font-size: 0.76rem;
      word-break: break-all;
      display: inline-block;
    }
    .developer-note {
      margin-top: 6px;
      padding: 6px 10px;
    }
    .developer-note p {
      font-size: 0.74rem;
      margin-bottom: 0;
    }
    .footer-developer .btn-3d.btn-sm {
      width: 100%;
      text-align: center;
      padding: 10px 14px;
      font-size: 0.8rem;
      min-height: 40px;
    }
    .isnm-footer p,
    .isnm-footer a {
      font-size: 0.78rem;
    }
    .footer-bottom {
      padding: 12px 0;
    }
    .footer-bottom p {
      font-size: 0.76rem;
      padding: 0 8px;
      line-height: 1.4;
      margin: 2px 0;
    }
    .footer-bottom .motto {
      font-size: 0.72rem;
      display: block;
      padding: 3px 8px 0;
    }
    .footer-stripes-top {
      height: 4px;
    }
    .footer-stripes-bottom {
      height: 3px;
    }
  }

  /* ════════════════════════════════════════════════════════════
     MOBILE (< 576px) — Clean card-based layout
     ════════════════════════════════════════════════════════════ */
  @media (max-width: 576px) {
    .isnm-footer .container {
      padding: 0 10px 4px;
    }
    .isnm-footer .row.g-4 {
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .isnm-footer .row > div {
      padding-left: 0;
      padding-right: 0;
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }
    .isnm-footer .row > div:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
    }

    /* ── Logo Section ── */
    .footer-logo {
      text-align: center;
      padding: 14px 12px;
      background: rgba(255,255,255,0.04);
      border-radius: 8px;
      margin-bottom: 8px;
    }
    .footer-logo-img {
      width: 44px;
      height: 44px;
      margin-bottom: 6px;
    }
    .footer-logo h4 {
      font-size: 0.74rem;
      margin-bottom: 6px;
      line-height: 1.3;
      letter-spacing: 0.4px;
    }
    .footer-logo p {
      font-size: 0.7rem;
      line-height: 1.5;
      margin-bottom: 2px;
      opacity: 0.9;
    }
    .footer-logo p i {
      display: none;
    }
    .social-links {
      justify-content: center;
      gap: 8px;
      margin-top: 8px !important;
    }
    .social-btn {
      width: 30px;
      height: 30px;
      font-size: 0.8rem;
      min-width: 30px;
      min-height: 30px;
    }

    /* ── Quick Links Section ── */
    .footer-links {
      padding: 12px;
      background: rgba(255,255,255,0.04);
      border-radius: 8px;
      margin-bottom: 8px;
    }
    .isnm-footer h5 {
      font-size: 0.76rem;
      text-align: center;
      margin-bottom: 10px;
      padding-bottom: 6px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .isnm-footer h5 i {
      display: none;
    }
    .footer-links ul {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
      columns: unset;
      column-gap: unset;
      text-align: left;
    }
    .footer-links ul li {
      break-inside: unset;
      margin-bottom: 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .footer-links ul li:last-child,
    .footer-links ul li:nth-last-child(2):nth-child(odd) {
      border-bottom: none;
    }
    .footer-links ul li a {
      font-size: 0.74rem;
      padding: 6px 4px;
      display: flex;
      align-items: center;
      gap: 6px;
      line-height: 1.35;
      border-radius: 4px;
      transition: background 0.2s;
    }
    .footer-links ul li a:active {
      background: rgba(255,255,255,0.08);
    }
    .footer-links ul li a i {
      display: inline-flex;
      font-size: 0.55rem;
      width: 14px;
      min-width: 14px;
      text-align: center;
      margin-right: 0 !important;
      color: var(--isnm-yellow);
      flex-shrink: 0;
    }

    /* ── Developer Section ── */
    .footer-developer {
      text-align: center;
      padding: 12px;
      background: rgba(255,255,255,0.04);
      border-radius: 8px;
    }
    .footer-developer h5 {
      font-size: 0.72rem;
      text-align: center;
      border-bottom: none;
      padding-bottom: 0;
      margin-bottom: 6px;
    }
    .footer-developer h5 i {
      display: none;
    }
    .footer-developer h6 {
      font-size: 0.74rem;
      text-align: center;
      margin-bottom: 6px;
    }
    .footer-developer h6 i {
      display: none;
    }
    .developer-info .contact-info p {
      font-size: 0.7rem;
      text-align: center;
      line-height: 1.4;
      margin-bottom: 3px;
    }
    .developer-info .contact-info p i {
      display: none;
    }
    .developer-info .contact-info a {
      font-size: 0.7rem;
      word-break: break-all;
    }
    .developer-note {
      margin-top: 6px;
      padding: 6px 8px;
    }
    .developer-note p {
      font-size: 0.68rem;
      text-align: center;
    }
    .developer-note p i {
      display: none;
    }
    .footer-developer .btn-3d.btn-sm {
      width: 100%;
      padding: 8px 12px;
      font-size: 0.72rem;
      min-height: 34px;
    }
    .footer-developer .btn-3d.btn-sm i {
      display: inline;
    }
    .isnm-footer p,
    .isnm-footer a {
      font-size: 0.72rem;
    }
    .footer-bottom {
      padding: 8px 0;
    }
    .footer-bottom p {
      font-size: 0.66rem;
      margin: 2px 0;
    }
    .footer-bottom .motto {
      font-size: 0.6rem;
      display: block;
      padding: 3px 0 0;
    }
    .footer-stripes-top {
      height: 4px;
    }
    .footer-stripes-bottom {
      height: 3px;
    }
  }

  /* ════════════════════════════════════════════════════════════
     EXTRA SMALL (< 360px) — Tighter spacing
     ════════════════════════════════════════════════════════════ */
  @media (max-width: 360px) {
    .isnm-footer .container {
      padding: 0 6px 2px;
    }
    .footer-logo {
      padding: 10px 8px;
      margin-bottom: 6px;
    }
    .footer-logo-img {
      width: 36px;
      height: 36px;
    }
    .footer-logo h4 {
      font-size: 0.68rem;
    }
    .footer-logo p {
      font-size: 0.64rem;
    }
    .social-btn {
      width: 26px;
      height: 26px;
      font-size: 0.72rem;
      min-width: 26px;
      min-height: 26px;
    }
    .footer-links {
      padding: 10px 8px;
      margin-bottom: 6px;
    }
    .footer-links ul li a {
      font-size: 0.68rem;
      padding: 5px 3px;
      gap: 4px;
    }
    .footer-links ul li a i {
      font-size: 0.5rem;
      width: 12px;
      min-width: 12px;
    }
    .footer-developer {
      padding: 10px 8px;
    }
    .developer-info .contact-info p {
      font-size: 0.64rem;
    }
    .developer-info .contact-info a {
      font-size: 0.64rem;
    }
    .isnm-footer p,
    .isnm-footer a {
      font-size: 0.64rem;
    }
    .footer-bottom p {
      font-size: 0.6rem;
    }
    .footer-bottom .motto {
      font-size: 0.56rem;
    }
  }

  /* Back to Top Button */
  #backToTop{position:fixed;bottom:90px;right:24px;z-index:9998;width:48px;height:48px;border-radius:50%;background:var(--isnm-primary,#1A237E);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 15px rgba(26,35,126,0.4);opacity:0;transform:translateY(20px);transition:all 0.3s ease;pointer-events:none}#backToTop.visible{opacity:1;transform:translateY(0);pointer-events:auto}#backToTop:hover{background:var(--isnm-secondary,#FFD700);color:var(--isnm-primary,#1A237E);transform:translateY(-3px);box-shadow:0 6px 20px rgba(255,215,0,0.4)}@media(max-width:576px){#backToTop{bottom:80px;right:16px;width:42px;height:42px;font-size:16px}}
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

    if ('serviceWorker' in navigator && !navigator.serviceWorker.controller) {
      window.addEventListener('load', function () {
        var swPath = (window.location.pathname.includes('/ISNM/') ? '/ISNM/' : '/') + 'sw.js';
        var scope = window.location.pathname.includes('/ISNM/') ? '/ISNM/' : '/';
        navigator.serviceWorker.register(swPath, { scope: scope }).catch(function (e) { console.warn('[ISNM] SW reg failed:', e); });
      });
    }

    // PWA Install Prompt Handler
    var deferredPrompt;
    window.addEventListener('beforeinstallprompt', function (e) {
      deferredPrompt = e;
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
    // Back to Top button visibility
    var backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
      window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
          backToTopBtn.classList.add('visible');
        } else {
          backToTopBtn.classList.remove('visible');
        }
      }, {passive: true});
    }
  </script>

<!-- Back to Top Button -->
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Back to top">
  <i class="fas fa-chevron-up"></i>
</button>

<!-- WhatsApp Floating Button -->
<div id="whatsappFloat" onclick="window.open('https://wa.me/256700451998','_blank')">
  <i class="fab fa-whatsapp"></i>
  <span>Chat with us</span>
</div>

<!-- Scroll Animation Observer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Intersection Observer for scroll animations
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        // Animate counter numbers
        var counter = entry.target.querySelector('[data-count]');
        if (counter && !counter.dataset.animated) {
          counter.dataset.animated = 'true';
          var target = parseInt(counter.dataset.count);
          var current = 0;
          var increment = Math.ceil(target / 60);
          var timer = setInterval(function() {
            current += increment;
            if (current >= target) {
              current = target;
              clearInterval(timer);
            }
            counter.textContent = current + (target > 100 ? '+' : '+');
          }, 30);
        }
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.animate-on-scroll').forEach(function(el) {
    observer.observe(el);
  });
});
</script>
<style>
#whatsappFloat{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;align-items:center;gap:10px;background:#25D366;color:#fff;border:none;border-radius:50px;padding:14px 18px;cursor:pointer;box-shadow:0 4px 20px rgba(37,211,102,0.4);transition:all 0.3s ease;font-family:'Inter','Segoe UI',sans-serif}#whatsappFloat i{font-size:26px}#whatsappFloat span{font-size:14px;font-weight:600;max-width:0;overflow:hidden;white-space:nowrap;transition:max-width 0.3s ease,opacity 0.3s ease;opacity:0}#whatsappFloat:hover{padding:14px 24px;box-shadow:0 6px 28px rgba(37,211,102,0.55)}#whatsappFloat:hover span{max-width:130px;opacity:1;margin-left:4px}#whatsappFloat::before{content:'';position:absolute;inset:0;border-radius:50px;animation:whatsappPulse 2s infinite}@keyframes whatsappPulse{0%{box-shadow:0 0 0 0 rgba(37,211,102,0.5)}70%{box-shadow:0 0 0 14px rgba(37,211,102,0)}100%{box-shadow:0 0 0 0 rgba(37,211,102,0)}}@media(max-width:576px){#whatsappFloat{bottom:16px;right:12px;padding:12px;border-radius:50%;width:50px;height:50px;display:flex;align-items:center;justify-content:center}#whatsappFloat i{font-size:24px}#whatsappFloat span{display:none}#whatsappFloat::before{animation:whatsappPulse 2.5s infinite}}@media(max-width:360px){#whatsappFloat{bottom:12px;right:8px;width:44px;height:44px;padding:10px}#whatsappFloat i{font-size:20px}}</style>
</body>

</html>
