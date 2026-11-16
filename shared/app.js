/**
 * ISNM Main Application JavaScript
 * Optimized: deferred loading, lazy initialization, IntersectionObserver for performance
 */
(function() {
  'use strict';

  // Hero Carousel (lazy-init, respects prefers-reduced-motion)
  function initHeroCarousel() {
    var slides = document.querySelectorAll('.hero-slide');
    if (!slides.length) return;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var currentSlide = 0, slideInterval;
    function showSlide(i) { slides.forEach(function(s){s.classList.remove('active');}); slides[i].classList.add('active'); }
    function nextSlide() { currentSlide = (currentSlide + 1) % slides.length; showSlide(currentSlide); }
    function startSlideshow() {
      if (reduceMotion || slides.length < 2) return;
      stopSlideshow();
      slideInterval = setInterval(nextSlide, 5000);
    }
    function stopSlideshow() { if (slideInterval) clearInterval(slideInterval); }
    if (!reduceMotion) startSlideshow();
    var hero = document.querySelector('.hero-section');
    if (hero && !reduceMotion) { hero.addEventListener('mouseenter', stopSlideshow); hero.addEventListener('mouseleave', startSlideshow); }
    if (window.matchMedia) {
      var mq = window.matchMedia('(prefers-reduced-motion: reduce)');
      var onChange = function(e) {
        reduceMotion = e.matches;
        if (reduceMotion) { stopSlideshow(); showSlide(0); } else { startSlideshow(); }
      };
      if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange);
    }
  }

  // Smooth scroll â”€â”€
  function initSmoothScroll() {
    var arrow = document.querySelector('.hero-scroll');
    if (!arrow) return;
    arrow.addEventListener('click', function() {
      var target = document.querySelector('.stats-section');
      if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
  }

  // â”€â”€ Scroll animations (IntersectionObserver â€” no jank) â”€â”€
  function initScrollAnimations() {
    if (!('IntersectionObserver' in window)) return;
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -100px 0px' });
    document.querySelectorAll('.feature-card, .stat-card, .program-card').forEach(function(el) {
      el.style.opacity = '0';
      observer.observe(el);
    });
  }

  // â”€â”€ Hamburger toggle (only if hero-section exists) â”€â”€
  function initHamburgerToggle() {
    var btn = document.querySelector('.btn-primary');
    var hero = document.querySelector('.hero-section');
    if (btn && hero) btn.addEventListener('click', function() { hero.classList.toggle('active'); });
  }

  // â”€â”€ Inject fadeInUp keyframes â”€â”€
  var style = document.createElement('style');
  style.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}';
  document.head.appendChild(style);

  // â”€â”€ Initialize on DOM ready â”€â”€
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  function init() {
    initHamburgerToggle();
    initHeroCarousel();
    initSmoothScroll();
    initScrollAnimations();
  }
})();

