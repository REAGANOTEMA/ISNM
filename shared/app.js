/**
 * ISNM Main Application JavaScript
 * Optimized: deferred loading, lazy initialization, IntersectionObserver for performance
 */
(function() {
  'use strict';

  // ── Hero Carousel (lazy-init only on pages with .hero-slide) ──
  function initHeroCarousel() {
    var slides = document.querySelectorAll('.hero-slide');
    if (!slides.length) return;
    var currentSlide = 0, slideInterval;
    function showSlide(i) { slides.forEach(function(s){s.classList.remove('active');}); slides[i].classList.add('active'); }
    function nextSlide() { currentSlide = (currentSlide + 1) % slides.length; showSlide(currentSlide); }
    function startSlideshow() { slideInterval = setInterval(nextSlide, 4000); }
    function stopSlideshow() { clearInterval(slideInterval); }
    startSlideshow();
    var hero = document.querySelector('.hero-section');
    if (hero) { hero.addEventListener('mouseenter', stopSlideshow); hero.addEventListener('mouseleave', startSlideshow); }
  }

  // ── Smooth scroll ──
  function initSmoothScroll() {
    var arrow = document.querySelector('.hero-scroll');
    if (!arrow) return;
    arrow.addEventListener('click', function() {
      var target = document.querySelector('.stats-section');
      if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
  }

  // ── Scroll animations (IntersectionObserver — no jank) ──
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

  // ── Hamburger toggle (only if hero-section exists) ──
  function initHamburgerToggle() {
    var btn = document.querySelector('.btn-primary');
    var hero = document.querySelector('.hero-section');
    if (btn && hero) btn.addEventListener('click', function() { hero.classList.toggle('active'); });
  }

  // ── Inject fadeInUp keyframes ──
  var style = document.createElement('style');
  style.textContent = '@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}';
  document.head.appendChild(style);

  // ── Initialize on DOM ready ──
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
