// ISNM School Management System - Enhanced JavaScript
// This file handles UI interactions for the new login system
// No more login.php references - uses staff-login.php and student-login.php

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle password toggle functionality for login forms
    initializePasswordToggles();
    
    // Handle form validations
    initializeFormValidations();
    
    // Handle navigation enhancements
    initializeNavigationEnhancements();
    
    // Initialize animations
    initializeAnimations();
    
    // Initialize phone number formatting
    initializePhoneFormatting();
    
    // Initialize NSIN validation
    initializeNSINValidation();
});

// Password toggle functionality
function initializePasswordToggles() {
    // Staff login password toggle
    const staffTogglePassword = document.querySelector("#staff-togglePassword");
    const staffPassword = document.querySelector("#staff-password");
    
    if (staffTogglePassword && staffPassword) {
        staffTogglePassword.addEventListener("click", function () {
            const type = staffPassword.getAttribute("type") === "password" ? "text" : "password";
            staffPassword.setAttribute("type", type);
            this.classList.toggle("bi-eye-slash-fill");
        });
    }
    
    // Student login password toggle (if exists)
    const studentTogglePassword = document.querySelector("#student-togglePassword");
    const studentPassword = document.querySelector("#student-password");
    
    if (studentTogglePassword && studentPassword) {
        studentTogglePassword.addEventListener("click", function () {
            const type = studentPassword.getAttribute("type") === "password" ? "text" : "password";
            studentPassword.setAttribute("type", type);
            this.classList.toggle("bi-eye-slash-fill");
        });
    }
}

// Form validation enhancements
function initializeFormValidations() {
    // Staff login form validation
    const staffLoginForm = document.querySelector('#staff-login-form');
    if (staffLoginForm) {
        staffLoginForm.addEventListener('submit', function(e) {
            const username = document.querySelector('#staff-username');
            const password = document.querySelector('#staff-password');
            
            if (!username.value.trim() || !password.value.trim()) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'warning');
                return false;
            }
            
            // Show loading state
            const submitBtn = staffLoginForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            }
        });
    }
    
    // Student login form validation
    const studentLoginForm = document.querySelector('#student-login-form');
    if (studentLoginForm) {
        studentLoginForm.addEventListener('submit', function(e) {
            const nsinNumber = document.querySelector('#nsin_number');
            const firstName = document.querySelector('#first_name');
            const phone = document.querySelector('#phone');
            
            if (!nsinNumber.value.trim() || !firstName.value.trim() || !phone.value.trim()) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'warning');
                return false;
            }
            
            // Validate NSIN format
            if (!validateNSIN(nsinNumber.value)) {
                e.preventDefault();
                showNotification('Invalid NSIN number format. Use CM followed by 13 digits', 'warning');
                return false;
            }
            
            // Validate phone number
            if (!validatePhone(phone.value)) {
                e.preventDefault();
                showNotification('Invalid phone number format. Use Uganda format (256...)', 'warning');
                return false;
            }
            
            // Show loading state
            const submitBtn = studentLoginForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            }
        });
    }
}

// NSIN number validation
function validateNSIN(nsin) {
    return /^CM\d{13}$/.test(nsin);
}

// Phone number validation
function validatePhone(phone) {
    // Remove all non-digit characters
    const cleaned = phone.replace(/\D/g, '');
    
    // Validate Uganda phone numbers (256 followed by 9 digits)
    return /^256\d{9}$/.test(cleaned);
}

// Phone number formatting
function formatPhoneNumber(input) {
    // Remove all non-digit characters
    let cleaned = input.replace(/\D/g, '');
    
    // Add 256 prefix if not present and number starts with 7
    if (cleaned.length === 10 && cleaned.startsWith('7')) {
        cleaned = '256' + cleaned.substring(1);
    }
    
    return cleaned;
}

// NSIN number formatting
function formatNSIN(input) {
    // Remove all non-alphanumeric characters
    let cleaned = input.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
    
    // Ensure it starts with CM
    if (!cleaned.startsWith('CM')) {
        cleaned = 'CM' + cleaned;
    }
    
    // Limit to 15 characters (CM + 13 digits)
    return cleaned.substring(0, 15);
}

// Initialize phone number formatting
function initializePhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[name="phone"], input[id*="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = formatPhoneNumber(e.target.value);
        });
        
        input.addEventListener('blur', function(e) {
            if (!validatePhone(e.target.value)) {
                e.target.classList.add('is-invalid');
            } else {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            }
        });
    });
}

// Initialize NSIN validation
function initializeNSINValidation() {
    const nsinInputs = document.querySelectorAll('input[name="nsin_number"], input[id*="nsin"]');
    nsinInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = formatNSIN(e.target.value);
        });
        
        input.addEventListener('blur', function(e) {
            if (!validateNSIN(e.target.value)) {
                e.target.classList.add('is-invalid');
            } else {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            }
        });
    });
}

// Show notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notif => notif.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${type} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Navigation enhancements
function initializeNavigationEnhancements() {
    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add loading states to buttons
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                this.disabled = true;
                
                // Reset after 5 seconds if form hasn't submitted
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 5000);
            }
        });
    });
    
    // Handle login page redirects
    const loginButtons = document.querySelectorAll('.login-redirect-btn');
    loginButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const userType = this.getAttribute('data-user-type');
            if (userType === 'staff') {
                window.location.href = 'staff-login.php';
            } else if (userType === 'student') {
                window.location.href = 'student-login.php';
            } else {
                showNotification('Please select Staff Login or Student Login', 'info');
            }
        });
    });
}

// Initialize animations
function initializeAnimations() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements that should animate
    document.querySelectorAll('.card, .dashboard-card, .student-card, .bus-card').forEach(el => {
        observer.observe(el);
    });
    
    // Add fade-in animation to login containers
    const loginContainers = document.querySelectorAll('.login-container, .student-header');
    loginContainers.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Utility functions for form handling
function clearFormErrors(formId) {
    const form = document.querySelector(formId);
    if (form) {
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
        });
        form.querySelectorAll('.is-valid').forEach(input => {
            input.classList.remove('is-valid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.style.display = 'none';
        });
    }
}

// Handle form field validation feedback
function handleFieldValidation(fieldId, isValid, message = '') {
    const field = document.querySelector(fieldId);
    const feedback = field.parentElement.querySelector('.invalid-feedback');
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        if (feedback) feedback.style.display = 'none';
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }
}

// Auto-hide alerts
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.style.display !== 'none') {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });
}

// Initialize auto-hide alerts
document.addEventListener('DOMContentLoaded', autoHideAlerts);

// Export functions for use in other files
window.ISNM = {
    showNotification,
    validateNSIN,
    validatePhone,
    formatPhoneNumber,
    formatNSIN,
    clearFormErrors,
    handleFieldValidation
};

// Console log for debugging
console.log('ISNM School Management System - JavaScript loaded successfully');
console.log('Using staff-login.php and student-login.php - no more login.php references');
