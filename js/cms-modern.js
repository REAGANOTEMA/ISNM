/**
 * ISNM Modern CMS — Interactive JavaScript
 * Scroll animations, counters, lightbox, FAQ, ripple effects
 */
(function() {
  'use strict';

  // ─── Scroll Animations ───────────────────────────
  function initScrollAnimations() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const delay = entry.target.dataset.delay || 0;
          setTimeout(() => {
            entry.target.classList.add('animated');
          }, parseInt(delay));
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

    elements.forEach(el => observer.observe(el));
  }

  // ─── Animated Counters ───────────────────────────
  function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.count);
    if (isNaN(target)) return;
    const duration = 2000;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  }

  // ─── Button Ripple Effect ────────────────────────
  function initRipple() {
    document.querySelectorAll('.cms-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        ripple.style.left = (e.clientX - rect.left) + 'px';
        ripple.style.top = (e.clientY - rect.top) + 'px';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
      });
    });
  }

  // ─── Simple Lightbox ─────────────────────────────
  function initLightbox() {
    document.querySelectorAll('[data-lightbox]').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const src = this.getAttribute('href');
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:pointer;animation:fadeIn 0.3s ease';
        const img = document.createElement('img');
        img.src = src;
        img.style.cssText = 'max-width:90%;max-height:90vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:zoomIn 0.3s ease';
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'position:absolute;top:20px;right:30px;color:white;font-size:2rem;background:none;border:none;cursor:pointer;z-index:10000;width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:all 0.3s';
        closeBtn.onmouseover = () => closeBtn.style.background = 'rgba(255,255,255,0.2)';
        closeBtn.onmouseout = () => closeBtn.style.background = 'none';

        overlay.appendChild(img);
        overlay.appendChild(closeBtn);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        function close() {
          overlay.remove();
          document.body.style.overflow = '';
        }
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
        closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', function handler(e) {
          if (e.key === 'Escape') { close(); document.removeEventListener('keydown', handler); }
        });
      });
    });
  }

  // ─── Hero Parallax ───────────────────────────────
  function initParallax() {
    const hero = document.querySelector('.cms-hero');
    if (!hero || window.innerWidth < 768) return;

    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      hero.style.backgroundPositionY = (scrolled * 0.3) + 'px';
    }, { passive: true });
  }

  // ─── Navbar Scroll Effect ────────────────────────
  function initNavbarScroll() {
    const navbar = document.querySelector('.isnm-navbar, .navbar, nav');
    if (!navbar) return;

    let lastScroll = 0;
    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;
      if (currentScroll > 100) {
        navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
        navbar.style.background = 'rgba(255,255,255,0.98)';
        navbar.style.backdropFilter = 'blur(20px)';
      } else {
        navbar.style.boxShadow = '';
        navbar.style.background = '';
        navbar.style.backdropFilter = '';
      }
      lastScroll = currentScroll;
    }, { passive: true });
  }

  // ─── Back to Top Button ──────────────────────────
  function initBackToTop() {
    const existing = document.querySelector('.cms-back-to-top');
    if (existing) return;

    const btn = document.createElement('button');
    btn.className = 'cms-back-to-top';
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    btn.setAttribute('aria-label', 'Back to top');
    btn.style.cssText = 'position:fixed;bottom:30px;right:30px;width:50px;height:50px;border-radius:50%;background:var(--isnm-primary);color:white;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(26,35,126,0.3);opacity:0;visibility:hidden;transition:all 0.3s ease;z-index:999;font-size:1.1rem';
    document.body.appendChild(btn);

    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 400) {
        btn.style.opacity = '1';
        btn.style.visibility = 'visible';
      } else {
        btn.style.opacity = '0';
        btn.style.visibility = 'hidden';
      }
    }, { passive: true });

    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ─── Loading State for Forms ─────────────────────
  function initFormLoading() {
    document.querySelectorAll('form[data-loading]').forEach(form => {
      form.addEventListener('submit', function() {
        const btn = this.querySelector('[type="submit"]');
        if (btn) {
          btn.dataset.originalHtml = btn.innerHTML;
          btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
          btn.disabled = true;
        }
      });
    });
  }

  // ─── Smooth Scroll for Anchor Links ──────────────
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  // ─── Dynamic Copyright Year ──────────────────────
  function initCopyrightYear() {
    document.querySelectorAll('[data-year]').forEach(el => {
      el.textContent = new Date().getFullYear();
    });
  }

  // ─── Initialize Everything ───────────────────────
  function init() {
    initScrollAnimations();
    initCounters();
    initRipple();
    initLightbox();
    initParallax();
    initNavbarScroll();
    initBackToTop();
    initFormLoading();
    initSmoothScroll();
    initCopyrightYear();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Add required CSS for animations
  const style = document.createElement('style');
  style.textContent = '@keyframes fadeIn{from{opacity:0}to{opacity:1}}@keyframes zoomIn{from{transform:scale(0.9);opacity:0}to{transform:scale(1);opacity:1}}';
  document.head.appendChild(style);
})();
