// Main application JavaScript
let toggle_btn, big_wrapper, hamburger_menu;

function declare() {
  // Look for elements that exist in index.php
  toggle_btn = document.querySelector(".btn-primary");
  big_wrapper = document.querySelector(".hero-section");
  hamburger_menu = document.querySelector(".navbar-toggler");
}

function events() {
  if (toggle_btn) {
    toggle_btn.addEventListener("click", toggleAnimation);
  }
  if (hamburger_menu) {
    hamburger_menu.addEventListener("click", () => {
      if (big_wrapper) {
        big_wrapper.classList.toggle("active");
      }
    });
  }
}

function toggleAnimation() {
  if (big_wrapper) {
    big_wrapper.classList.toggle("active");
  }
}

// Cinematic Hero Carousel functionality
function initHeroCarousel() {
  const slides = document.querySelectorAll('.hero-slide');
  let currentSlide = 0;
  let slideInterval;

  function showSlide(index) {
    // Hide all slides
    slides.forEach(slide => slide.classList.remove('active'));
    
    // Show current slide
    slides[index].classList.add('active');
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }

  function startSlideshow() {
    slideInterval = setInterval(nextSlide, 4000); // Change slide every 4 seconds
  }

  function stopSlideshow() {
    clearInterval(slideInterval);
  }

  // Start slideshow
  if (slides.length > 0) {
    startSlideshow();
  }

  // Pause on hover
  const heroSection = document.querySelector('.hero-section');
  if (heroSection) {
    heroSection.addEventListener('mouseenter', stopSlideshow);
    heroSection.addEventListener('mouseleave', startSlideshow);
  }
}

// Smooth scroll functionality
function initSmoothScroll() {
  const scrollArrow = document.querySelector('.hero-scroll');
  if (scrollArrow) {
    scrollArrow.addEventListener('click', () => {
      const nextSection = document.querySelector('.stats-section');
      if (nextSection) {
        nextSection.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }
}

// Animate elements on scroll
function initScrollAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
      }
    });
  }, observerOptions);

  // Observe feature cards and other elements
  const animateElements = document.querySelectorAll('.feature-card, .stat-card, .program-card');
  animateElements.forEach(el => observer.observe(el));
}

// Add fadeInUp animation
const style = document.createElement('style');
style.textContent = `
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;
document.head.appendChild(style);

// Initialize all features when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  declare();
  events();
  initHeroCarousel();
  initSmoothScroll();
  initScrollAnimations();
});
