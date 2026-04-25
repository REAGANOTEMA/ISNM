<?php include('shared/_header.php');?>

  <style>
    /* Enhanced Donation Page Styles */
    :root {
      --isnm-yellow: #FFD700;
      --isnm-cream: #FFF8DC;
      --isnm-chocolate: #3E2723;
      --isnm-dark-blue: #1A237E;
      --isnm-light-blue: #3949AB;
      --isnm-gold: #FFA000;
      --isnm-beige: #F5F5DC;
      --gradient-primary: linear-gradient(135deg, var(--isnm-dark-blue), var(--isnm-light-blue));
      --gradient-secondary: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
    }

    body {
      font-family: 'Poppins', sans-serif;
      line-height: 1.6;
      color: #000000;
    }

    /* Enhanced Page Header */
    .page-header {
      background: var(--gradient-primary);
      color: white;
      padding: 4rem 0;
      position: relative;
      overflow: hidden;
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('images/hero-pattern.png') repeat;
      opacity: 0.1;
      animation: patternMove 20s linear infinite;
    }

    @keyframes patternMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(50px); }
    }

    .page-title {
      font-family: 'Playfair Display', serif;
      font-size: 3.5rem;
      font-weight: 900;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      animation: titleGlow 3s ease-in-out infinite alternate;
    }

    @keyframes titleGlow {
      0% { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 20px rgba(255,215,0,0.3); }
      100% { text-shadow: 2px 2px 4px rgba(0,0,0,0.3), 0 0 30px rgba(255,215,0,0.5); }
    }

    .page-subtitle {
      font-size: 1.3rem;
      opacity: 0.9;
      margin-bottom: 0;
    }

    /* Enhanced Donation Overview */
    .donation-overview {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      position: relative;
    }

    .donation-overview::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('images/subtle-pattern.png') repeat;
      opacity: 0.05;
    }

    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--isnm-dark-blue);
      margin-bottom: 1rem;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--gradient-secondary);
      border-radius: 2px;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #000000;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Enhanced Donation Content */
    .donation-content {
      background: white;
      padding: 3rem;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.1);
      position: relative;
      overflow: hidden;
    }

    .donation-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: var(--gradient-secondary);
    }

    .donation-content h3 {
      color: var(--isnm-dark-blue);
      margin-bottom: 1.5rem;
      font-size: 2rem;
      font-weight: 700;
    }

    /* Enhanced Impact Items */
    .impact-list {
      list-style: none;
      padding: 0;
      margin: 2rem 0 0;
    }

    .impact-item {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
      padding: 1.5rem;
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      border-radius: 15px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      border: 1px solid rgba(0,0,0,0.05);
      position: relative;
      overflow: hidden;
    }

    .impact-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,215,0,0.1), transparent);
      transition: left 0.6s ease;
    }

    .impact-item:hover::before {
      left: 100%;
    }

    .impact-item:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      border-color: var(--isnm-yellow);
    }

    .impact-icon {
      width: 60px;
      height: 60px;
      background: var(--gradient-secondary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1.5rem;
      flex-shrink: 0;
      position: relative;
      box-shadow: 0 5px 15px rgba(255,215,0,0.3);
    }

    .impact-icon::after {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      background: var(--gradient-secondary);
      border-radius: 50%;
      z-index: -1;
      opacity: 0.3;
    }

    .impact-icon i {
      font-size: 1.5rem;
      color: var(--isnm-chocolate);
    }

    .impact-text h4 {
      color: #000000;
      margin: 0 0 0.5rem;
      font-size: 1.2rem;
      font-weight: 600;
    }

    .impact-text p {
      margin: 0;
      color: #000000;
      line-height: 1.6;
    }

    /* Enhanced Donation Image */
    .donation-image {
      position: relative;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      transform: perspective(1000px) rotateY(5deg);
      transition: all 0.5s ease;
      height: 500px;
      margin-top: 1rem;
      margin-bottom: 1rem;
    }

    .donation-image:hover {
      transform: perspective(1000px) rotateY(0deg) scale(1.05);
      box-shadow: 0 30px 80px rgba(0,0,0,0.25);
    }

    .donation-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
      filter: brightness(1.05) contrast(1.1);
    }

    .donation-image:hover img {
      transform: scale(1.08);
      filter: brightness(1.1) contrast(1.15);
    }

    .donation-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,215,0,0.15) 0%, transparent 50%, rgba(255,215,0,0.1) 100%);
      pointer-events: none;
      z-index: 1;
    }

    .donation-image::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(0deg, rgba(0,0,0,0.1) 0%, transparent 20%, transparent 80%, rgba(0,0,0,0.1) 100%);
      pointer-events: none;
      z-index: 2;
    }

    /* Enhanced Donation Options */
    .donation-options {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      position: relative;
    }

    .donation-options::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('images/dots-pattern.png') repeat;
      opacity: 0.03;
    }

    .donation-card {
      background: white;
      padding: 2.5rem;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
      height: 100%;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative;
      overflow: hidden;
      border: 2px solid transparent;
    }

    .donation-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: var(--gradient-secondary);
    }

    .donation-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.2);
      border-color: var(--isnm-yellow);
    }

    .donation-icon {
      width: 90px;
      height: 90px;
      background: var(--gradient-secondary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      position: relative;
      box-shadow: 0 10px 25px rgba(255,215,0,0.3);
      transition: all 0.3s ease;
    }

    .donation-card:hover .donation-icon {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 15px 35px rgba(255,215,0,0.4);
    }

    .donation-icon i {
      font-size: 2.5rem;
      color: var(--isnm-chocolate);
    }

    .donation-card h3 {
      color: #000000;
      margin-bottom: 1.5rem;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .donation-card p {
      color: #000000;
      margin-bottom: 2rem;
      line-height: 1.7;
    }

    .donation-amounts {
      list-style: none;
      padding: 0;
      margin: 0 0 2rem;
      text-align: left;
    }

    .donation-amounts li {
      padding: 0.8rem 1rem;
      margin-bottom: 0.5rem;
      color: #000000;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .donation-amounts li:hover {
      background: linear-gradient(135deg, #fff8dc 0%, #ffffff 100%);
      border-color: var(--isnm-yellow);
      transform: translateX(5px);
    }

    .donation-amounts .amount {
      font-weight: 700;
      color: #000000;
      font-size: 1.1rem;
    }

    /* Enhanced Project Cards */
    .project-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      height: 100%;
      position: relative;
    }

    .project-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    }

    .project-image {
      height: 250px;
      overflow: hidden;
      position: relative;
    }

    .project-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .project-card:hover .project-image img {
      transform: scale(1.1);
    }

    .project-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(26,35,126,0.1) 0%, transparent 50%, rgba(255,215,0,0.05) 100%);
    }

    .project-content {
      padding: 2rem;
    }

    .project-content h3 {
      color: #000000;
      margin-bottom: 1rem;
      font-size: 1.4rem;
      font-weight: 700;
    }

    .project-content p {
      color: #000000;
      margin-bottom: 1.5rem;
      line-height: 1.7;
    }

    .project-progress {
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.05);
    }

    .progress-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.8rem;
      font-size: 0.9rem;
    }

    .progress-info span:first-child {
      color: #000000;
      font-weight: 500;
    }

    .progress-info span:last-child {
      color: #000000;
      font-weight: 700;
    }

    .progress {
      height: 10px;
      background-color: #e9ecef;
      border-radius: 5px;
      overflow: hidden;
      position: relative;
    }

    .progress-bar {
      background: var(--gradient-secondary);
      transition: width 0.8s ease;
      position: relative;
      overflow: hidden;
    }

    .progress-bar::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      animation: progressShine 2s infinite;
    }

    @keyframes progressShine {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    .progress-percentage {
      font-size: 0.85rem;
      color: #000000;
      font-weight: 600;
      margin-top: 0.5rem;
    }

    /* Ensure project buttons are visible */
    .project-card .btn {
      display: inline-block !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 10 !important;
      width: auto !important;
      min-width: 200px !important;
      margin-top: 1rem !important;
    }

    /* Enhanced Buttons */
    .btn {
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      border: none;
      font-size: 0.9rem;
    }

    .btn-primary {
      background: var(--gradient-secondary);
      color: var(--isnm-chocolate);
      box-shadow: 0 5px 15px rgba(255,215,0,0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255,215,0,0.4);
    }

    .btn-outline-primary {
      background: transparent;
      color: var(--isnm-dark-blue);
      border: 2px solid var(--isnm-dark-blue);
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
    }

    .btn-outline-primary:hover {
      background: var(--isnm-dark-blue);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(26,35,126,0.3);
    }

    /* Additional button visibility fixes */
    .project-card {
      position: relative;
      overflow: visible;
      display: flex;
      flex-direction: column;
    }

    .project-card .btn-outline-primary {
      display: inline-flex !important;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 10 !important;
      width: 100% !important;
      max-width: 250px !important;
      margin: 1rem auto 0 !important;
      text-align: center !important;
      align-items: center !important;
      justify-content: center !important;
      align-self: flex-end !important;
    }

    .project-content {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.6s ease;
    }

    .btn:hover::before {
      left: 100%;
    }

    /* Enhanced Modal */
    .modal {
      z-index: 9999 !important;
    }

    .modal-dialog {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }

    .modal-content {
      border-radius: 20px;
      border: none;
      box-shadow: 0 25px 80px rgba(0,0,0,0.4);
      background: white;
      max-width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
      }
      to {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    .modal-header {
      background: var(--gradient-primary);
      color: white;
      border-radius: 20px 20px 0 0;
      border: none;
      padding: 2rem 2.5rem;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .modal-title {
      font-weight: 700;
      font-size: 1.8rem;
      margin: 0;
    }

    .btn-close {
      filter: brightness(0) invert(1);
      font-size: 1.5rem;
      opacity: 0.8;
      transition: opacity 0.3s ease;
    }

    .btn-close:hover {
      opacity: 1;
    }

    .modal-body {
      padding: 2.5rem;
      background: white;
      border-radius: 0 0 20px 20px;
    }

    .modal-footer {
      padding: 1.5rem 2.5rem;
      background: #f8f9fa;
      border-radius: 0 0 20px 20px;
      border-top: 1px solid #e9ecef;
    }

    .modal-backdrop {
      background: rgba(0, 0, 0, 0.7) !important;
      backdrop-filter: blur(5px);
    }

    /* Ensure modal is visible */
    .modal.show {
      display: flex !important;
    }

    .modal.show .modal-dialog {
      display: flex !important;
      align-items: center;
      justify-content: center;
    }

    .form-control {
      border-radius: 10px;
      border: 2px solid #e9ecef;
      padding: 12px 15px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: var(--isnm-yellow);
      box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.25);
    }

    .form-label {
      font-weight: 600;
      color: var(--isnm-dark-blue);
      margin-bottom: 0.5rem;
    }

    /* Payment Method Cards */
    .payment-methods {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }

    .payment-method-card {
      padding: 1.5rem;
      border: 2px solid #e9ecef;
      border-radius: 15px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
    }

    .payment-method-card:hover {
      border-color: var(--isnm-yellow);
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .payment-method-card.selected {
      border-color: var(--isnm-yellow);
      background: linear-gradient(135deg, #fff8dc 0%, #ffffff 100%);
      box-shadow: 0 5px 20px rgba(255,215,0,0.2);
    }

    .payment-method-card i {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      display: block;
    }

    .payment-method-card .visa { color: #1a1f71; }
    .payment-method-card .mastercard { color: #eb001b; }
    .payment-method-card .mobile-money { color: #28a745; }
    .payment-method-card .mtn { color: #ff6600; }
    .payment-method-card .airtel { color: #ed1c24; }

    .payment-method-card span {
      font-size: 0.9rem;
      font-weight: 600;
      color: #333;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .page-title {
        font-size: 2.5rem;
      }
      
      .donation-content {
        padding: 2rem;
      }
      
      .donation-image {
        height: 350px;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
      }
      
      .impact-item {
        flex-direction: column;
        text-align: center;
        padding: 1rem;
      }
      
      .impact-icon {
        margin-right: 0;
        margin-bottom: 1rem;
      }
      
      .donation-amounts li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
      }
      
      .payment-methods {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      .donation-image {
        height: 280px;
      }
    }
  </style>

  <main>
    <!-- Enhanced Page Header -->
    <section class="page-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center">
            <h1 class="page-title">Support ISNM</h1>
            <p class="page-subtitle">Help us train the next generation of healthcare professionals</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Donation Overview -->
    <section class="donation-overview py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Make a Difference</h2>
            <p class="section-subtitle">Your generous support helps us provide quality healthcare education and improve our facilities</p>
          </div>
        </div>
        
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="donation-content">
              <h3>Why Support ISNM?</h3>
              <p>Iganga School of Nursing and Midwifery is committed to producing world-class healthcare professionals who serve communities across Uganda and beyond. Your donation helps us:</p>
              
              <div class="impact-list">
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Quality Education</h4>
                    <p>Provide modern teaching resources and technology for effective learning</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-hospital"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Clinical Training</h4>
                    <p>Support practical training at major hospitals and healthcare facilities</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-laptop"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Technology Infrastructure</h4>
                    <p>Enhance computer labs and digital learning resources</p>
                  </div>
                </div>
                
                <div class="impact-item">
                  <div class="impact-icon">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div class="impact-text">
                    <h4>Student Support</h4>
                    <p>Provide scholarships and financial assistance to deserving students</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="donation-image">
              <img src="images/students-in-class.jpg" alt="Students Learning" class="img-fluid rounded-3">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Options -->
    <section class="donation-options py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Ways to Give</h2>
            <p class="section-subtitle">Choose how you'd like to support our mission</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-hand-holding-heart"></i>
              </div>
              <h3>One-Time Donation</h3>
              <p>Make a single donation to support our immediate needs and ongoing programs</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 50,000</span> - Supports student learning materials</li>
                <li><span class="amount">UGX 100,000</span> - Funds clinical training equipment</li>
                <li><span class="amount">UGX 500,000</span> - Supports library resources</li>
                <li><span class="amount">UGX 1,000,000</span> - Funds technology upgrades</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('one-time')">Donate Now</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-sync-alt"></i>
              </div>
              <h3>Monthly Giving</h3>
              <p>Provide sustained support through monthly contributions</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 25,000/month</span> - Student meal support</li>
                <li><span class="amount">UGX 50,000/month</span> - Textbook fund</li>
                <li><span class="amount">UGX 100,000/month</span> - Technology maintenance</li>
                <li><span class="amount">UGX 200,000/month</span> - Scholarship fund</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('monthly')">Give Monthly</button>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="donation-card">
              <div class="donation-icon">
                <i class="fas fa-award"></i>
              </div>
              <h3>Scholarship Fund</h3>
              <p>Support deserving students who cannot afford tuition fees</p>
              <ul class="donation-amounts">
                <li><span class="amount">UGX 500,000</span> - Partial scholarship</li>
                <li><span class="amount">UGX 1,000,000</span> - Half scholarship</li>
                <li><span class="amount">UGX 2,000,000</span> - Full semester</li>
                <li><span class="amount">UGX 4,000,000</span> - Full year scholarship</li>
              </ul>
              <button class="btn btn-primary" onclick="showDonationForm('scholarship')">Fund Scholarship</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Specific Projects -->
    <section class="projects-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-12 text-center mb-5">
            <h2 class="section-title">Support Specific Projects</h2>
            <p class="section-subtitle">Fund our priority development initiatives</p>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/revision-library.jpg" alt="Library" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Modern Library Development</h3>
                <p>Help us build and equip a modern library with current medical texts, research databases, and study spaces for our students.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 50,000,000</span>
                    <span>Raised: UGX 15,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 30%"></div>
                  </div>
                  <span class="progress-percentage">30% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/computer-lab.jpg" alt="Computer Lab" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Computer Lab Enhancement</h3>
                <p>Upgrade our computer lab with modern systems, high-speed internet, and educational software to enhance digital learning.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 30,000,000</span>
                    <span>Raised: UGX 8,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 27%"></div>
                  </div>
                  <span class="progress-percentage">27% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/skills-lab-nurses.jpeg" alt="Skills Lab" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Skills Laboratory Equipment</h3>
                <p>Equip our nursing and midwifery skills labs with modern mannequins, simulation equipment, and training supplies.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 40,000,000</span>
                    <span>Raised: UGX 5,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 12.5%"></div>
                  </div>
                  <span class="progress-percentage">12.5% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="project-card">
              <div class="project-image">
                <img src="images/diploma-hostel.jpg" alt="Student Hostel" class="img-fluid">
              </div>
              <div class="project-content">
                <h3>Student Hostel Construction</h3>
                <p>Help us complete the construction of a modern girls' hostel to provide safe and comfortable accommodation for our students.</p>
                <div class="project-progress">
                  <div class="progress-info">
                    <span>Goal: UGX 200,000,000</span>
                    <span>Raised: UGX 50,000,000</span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar" style="width: 25%"></div>
                  </div>
                  <span class="progress-percentage">25% Complete</span>
                </div>
                <button class="btn btn-outline-primary">Support This Project</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced Donation Form Modal -->
    <div class="modal fade" id="donationModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Make Your Donation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="donationForm">
              <!-- Personal Information -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-user me-2"></i>Personal Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="donorName" class="form-label">Full Name *</label>
                  <input type="text" class="form-control" id="donorName" placeholder="Enter your full name" required>
                </div>
                <div class="col-md-6">
                  <label for="donorEmail" class="form-label">Email Address *</label>
                  <input type="email" class="form-control" id="donorEmail" placeholder="your.email@example.com" required>
                </div>
                <div class="col-md-6">
                  <label for="donorPhone" class="form-label">Phone Number *</label>
                  <input type="tel" class="form-control" id="donorPhone" placeholder="+256 7XX XXX XXX" required>
                </div>
                <div class="col-md-6">
                  <label for="donorAddress" class="form-label">Address (Optional)</label>
                  <input type="text" class="form-control" id="donorAddress" placeholder="Your address">
                </div>
              </div>

              <!-- Donation Details -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-hand-holding-heart me-2"></i>Donation Details</h4>
                </div>
                <div class="col-md-6">
                  <label for="donationType" class="form-label">Donation Type *</label>
                  <select class="form-control" id="donationType" required>
                    <option value="">Select Type</option>
                    <option value="one-time">One-Time Donation</option>
                    <option value="monthly">Monthly Giving</option>
                    <option value="scholarship">Scholarship Fund</option>
                    <option value="project">Specific Project</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="donationAmount" class="form-label">Amount (UGX) *</label>
                  <input type="number" class="form-control" id="donationAmount" min="10000" placeholder="Enter amount in UGX" required>
                </div>
                <div class="col-12">
                  <label for="donationPurpose" class="form-label">Purpose (Optional)</label>
                  <select class="form-control" id="donationPurpose">
                    <option value="">General Support</option>
                    <option value="library">Library Development</option>
                    <option value="computer-lab">Computer Lab Enhancement</option>
                    <option value="skills-lab">Skills Laboratory Equipment</option>
                    <option value="hostel">Student Hostel Construction</option>
                    <option value="scholarship">Student Scholarship</option>
                    <option value="infrastructure">Infrastructure Development</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="donorMessage" class="form-label">Message (Optional)</label>
                  <textarea class="form-control" id="donorMessage" rows="3" placeholder="Any message or special instructions..."></textarea>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="anonymousDonation">
                    <label class="form-check-label" for="anonymousDonation">
                      Make this donation anonymous
                    </label>
                  </div>
                </div>
              </div>

              <!-- Payment Method Selection -->
              <div class="row mb-4">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method *</h4>
                  <p class="text-muted">Choose your preferred payment method</p>
                </div>
                <div class="col-12">
                  <div class="payment-methods">
                    <div class="payment-method-card" data-method="visa" onclick="selectPaymentMethod('visa')">
                      <i class="fab fa-cc-visa visa"></i>
                      <span>Visa Card</span>
                    </div>
                    <div class="payment-method-card" data-method="mastercard" onclick="selectPaymentMethod('mastercard')">
                      <i class="fab fa-cc-mastercard mastercard"></i>
                      <span>Mastercard</span>
                    </div>
                    <div class="payment-method-card" data-method="mobile-money" onclick="selectPaymentMethod('mobile-money')">
                      <i class="fas fa-mobile-alt mobile-money"></i>
                      <span>Mobile Money</span>
                    </div>
                    <div class="payment-method-card" data-method="mtn" onclick="selectPaymentMethod('mtn')">
                      <i class="fas fa-phone mtn"></i>
                      <span>MTN MoMo</span>
                    </div>
                    <div class="payment-method-card" data-method="airtel" onclick="selectPaymentMethod('airtel')">
                      <i class="fas fa-phone airtel"></i>
                      <span>Airtel Money</span>
                    </div>
                    <div class="payment-method-card" data-method="bank-transfer" onclick="selectPaymentMethod('bank-transfer')">
                      <i class="fas fa-university"></i>
                      <span>Bank Transfer</span>
                    </div>
                  </div>
                  <input type="hidden" id="selectedPaymentMethod" required>
                </div>
              </div>

              <!-- Credit Card Details (Hidden by default) -->
              <div id="creditCardDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-credit-card me-2"></i>Credit Card Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="cardNumber" class="form-label">Card Number *</label>
                  <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="col-md-6">
                  <label for="cardName" class="form-label">Name on Card *</label>
                  <input type="text" class="form-control" id="cardName" placeholder="John Doe">
                </div>
                <div class="col-md-4">
                  <label for="expiryDate" class="form-label">Expiry Date *</label>
                  <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY" maxlength="5">
                </div>
                <div class="col-md-4">
                  <label for="cvv" class="form-label">CVV *</label>
                  <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="4">
                </div>
                <div class="col-md-4">
                  <label for="billingZip" class="form-label">Billing ZIP *</label>
                  <input type="text" class="form-control" id="billingZip" placeholder="12345">
                </div>
              </div>

              <!-- Mobile Money Details (Hidden by default) -->
              <div id="mobileMoneyDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-mobile-alt me-2"></i>Mobile Money Information</h4>
                </div>
                <div class="col-md-6">
                  <label for="mobileProvider" class="form-label">Mobile Provider *</label>
                  <select class="form-control" id="mobileProvider">
                    <option value="">Select Provider</option>
                    <option value="mtn">MTN Mobile Money</option>
                    <option value="airtel">Airtel Money</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="mobileNumber" class="form-label">Mobile Number *</label>
                  <input type="tel" class="form-control" id="mobileNumber" placeholder="+256 7XX XXX XXX">
                </div>
                <div class="col-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You will receive a prompt on your mobile phone to complete the payment.
                  </div>
                </div>
              </div>

              <!-- Bank Transfer Details (Hidden by default) -->
              <div id="bankTransferDetails" class="row mb-4" style="display: none;">
                <div class="col-12">
                  <h4 class="mb-3"><i class="fas fa-university me-2"></i>Bank Transfer Information</h4>
                </div>
                <div class="col-12">
                  <div class="alert alert-info">
                    <h5>Bank Details:</h5>
                    <p><strong>Bank:</strong> Stanbic Bank Uganda<br>
                    <strong>Account Name:</strong> Iganga School of Nursing and Midwifery<br>
                    <strong>Account Number:</strong> 9030001234567<br>
                    <strong>Branch:</strong> Iganga Branch<br>
                    <strong>SWIFT Code:</strong> SBICUGKX</p>
                    <p class="mb-0">Please use your donation reference: <strong id="donationReference"></strong></p>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="processDonation()">
              <i class="fas fa-lock me-2"></i>Process Donation
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>

  <script>
    // Generate unique donation reference
    function generateDonationReference() {
      const timestamp = Date.now();
      const random = Math.floor(Math.random() * 1000);
      return `DON-${timestamp}-${random}`;
    }

    // Show donation form with type
    function showDonationForm(type) {
      console.log('Opening donation form for type:', type);
      
      // Set donation type
      const donationTypeField = document.getElementById('donationType');
      if (donationTypeField) {
        donationTypeField.value = type;
      }
      
      // Generate and set reference
      const referenceField = document.getElementById('donationReference');
      if (referenceField) {
        referenceField.textContent = generateDonationReference();
      }
      
      // Reset payment method selection
      document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
      });
      const selectedMethodField = document.getElementById('selectedPaymentMethod');
      if (selectedMethodField) {
        selectedMethodField.value = '';
      }
      
      // Hide all payment detail sections
      const creditCardSection = document.getElementById('creditCardDetails');
      const mobileMoneySection = document.getElementById('mobileMoneyDetails');
      const bankTransferSection = document.getElementById('bankTransferDetails');
      
      if (creditCardSection) creditCardSection.style.display = 'none';
      if (mobileMoneySection) mobileMoneySection.style.display = 'none';
      if (bankTransferSection) bankTransferSection.style.display = 'none';
      
      // Show modal with multiple methods for compatibility
      const modalElement = document.getElementById('donationModal');
      if (modalElement) {
        // Method 1: Bootstrap Modal
        try {
          const modal = new bootstrap.Modal(modalElement);
          modal.show();
          console.log('Bootstrap modal shown');
        } catch (e) {
          console.log('Bootstrap modal failed:', e);
          
          // Method 2: Manual show
          modalElement.classList.add('show');
          modalElement.style.display = 'block';
          document.body.classList.add('modal-open');
          
          // Create backdrop
          const backdrop = document.createElement('div');
          backdrop.className = 'modal-backdrop show';
          backdrop.style.display = 'block';
          document.body.appendChild(backdrop);
          
          console.log('Manual modal shown');
        }
      } else {
        console.error('Modal element not found');
      }
    }

    // Select payment method
    function selectPaymentMethod(method) {
      // Remove previous selection
      document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
      });
      
      // Add selection to clicked card
      document.querySelector(`[data-method="${method}"]`).classList.add('selected');
      document.getElementById('selectedPaymentMethod').value = method;
      
      // Hide all payment detail sections
      document.getElementById('creditCardDetails').style.display = 'none';
      document.getElementById('mobileMoneyDetails').style.display = 'none';
      document.getElementById('bankTransferDetails').style.display = 'none';
      
      // Show relevant payment details
      if (method === 'visa' || method === 'mastercard') {
        document.getElementById('creditCardDetails').style.display = 'block';
      } else if (method === 'mobile-money' || method === 'mtn' || method === 'airtel') {
        document.getElementById('mobileMoneyDetails').style.display = 'block';
        // Auto-select provider if specific method chosen
        if (method === 'mtn') {
          document.getElementById('mobileProvider').value = 'mtn';
        } else if (method === 'airtel') {
          document.getElementById('mobileProvider').value = 'airtel';
        }
      } else if (method === 'bank-transfer') {
        document.getElementById('bankTransferDetails').style.display = 'block';
      }
    }

    // Format card number
    function formatCardNumber(value) {
      const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      const matches = v.match(/\d{4}/g);
      return matches ? matches.join(' ') : v;
    }

    // Format expiry date
    function formatExpiryDate(value) {
      const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      if (v.length >= 2) {
        return v.slice(0, 2) + '/' + v.slice(2, 4);
      }
      return v;
    }

    // Validate credit card
    function validateCreditCard() {
      const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
      const expiryDate = document.getElementById('expiryDate').value;
      const cvv = document.getElementById('cvv').value;
      const billingZip = document.getElementById('billingZip').value;
      
      // Basic validation
      if (cardNumber.length < 13 || cardNumber.length > 19) {
        alert('Please enter a valid card number');
        return false;
      }
      
      if (!expiryDate.match(/^\d{2}\/\d{2}$/)) {
        alert('Please enter a valid expiry date (MM/YY)');
        return false;
      }
      
      if (cvv.length < 3 || cvv.length > 4) {
        alert('Please enter a valid CVV');
        return false;
      }
      
      if (!billingZip.match(/^\d{5}$/)) {
        alert('Please enter a valid ZIP code');
        return false;
      }
      
      return true;
    }

    // Validate mobile money
    function validateMobileMoney() {
      const provider = document.getElementById('mobileProvider').value;
      const mobileNumber = document.getElementById('mobileNumber').value;
      
      if (!provider) {
        alert('Please select a mobile provider');
        return false;
      }
      
      if (!mobileNumber.match(/^\+256\s?7\d{2}\s?\d{3}\s?\d{3}$/)) {
        alert('Please enter a valid Ugandan mobile number (+256 7XX XXX XXX)');
        return false;
      }
      
      return true;
    }

    // Process donation
    function processDonation() {
      const form = document.getElementById('donationForm');
      const submitBtn = document.querySelector('.modal-footer .btn-primary');
      
      // Basic validation
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;
      
      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('is-invalid');
        } else {
          field.classList.remove('is-invalid');
        }
      });
      
      if (!isValid) {
        alert('Please fill in all required fields');
        return;
      }
      
      // Check if payment method is selected
      const selectedMethod = document.getElementById('selectedPaymentMethod').value;
      if (!selectedMethod) {
        alert('Please select a payment method');
        return;
      }
      
      // Validate payment method specific fields
      if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
        if (!validateCreditCard()) {
          return;
        }
      } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
        if (!validateMobileMoney()) {
          return;
        }
      }
      
      // Show processing message
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
      
      // Collect donation data
      const donationData = {
        reference: document.getElementById('donationReference').textContent,
        name: document.getElementById('donorName').value,
        email: document.getElementById('donorEmail').value,
        phone: document.getElementById('donorPhone').value,
        address: document.getElementById('donorAddress').value,
        type: document.getElementById('donationType').value,
        amount: document.getElementById('donationAmount').value,
        purpose: document.getElementById('donationPurpose').value,
        message: document.getElementById('donorMessage').value,
        anonymous: document.getElementById('anonymousDonation').checked,
        paymentMethod: selectedMethod,
        timestamp: new Date().toISOString()
      };
      
      // Add payment method specific data
      if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
        donationData.cardNumber = document.getElementById('cardNumber').value;
        donationData.cardName = document.getElementById('cardName').value;
        donationData.expiryDate = document.getElementById('expiryDate').value;
        donationData.cvv = document.getElementById('cvv').value;
        donationData.billingZip = document.getElementById('billingZip').value;
      } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
        donationData.mobileProvider = document.getElementById('mobileProvider').value;
        donationData.mobileNumber = document.getElementById('mobileNumber').value;
      }
      
      // Simulate processing (in real implementation, this would send data to server)
      setTimeout(() => {
        // Show success message based on payment method
        let successMessage = 'Thank you for your donation!\n\n';
        successMessage += `Reference: ${donationData.reference}\n`;
        successMessage += `Amount: UGX ${parseInt(donationData.amount).toLocaleString()}\n\n`;
        
        if (selectedMethod === 'visa' || selectedMethod === 'mastercard') {
          successMessage += 'Your credit card payment has been processed successfully.\n';
          successMessage += 'A receipt will be sent to your email.';
        } else if (selectedMethod === 'mobile-money' || selectedMethod === 'mtn' || selectedMethod === 'airtel') {
          successMessage += 'Please check your mobile phone for the payment prompt.\n';
          successMessage += 'Complete the payment to finalize your donation.';
        } else if (selectedMethod === 'bank-transfer') {
          successMessage += 'Please complete the bank transfer using the provided details.\n';
          successMessage += 'Send payment confirmation to accounts@isnm.ac.ug';
        }
        
        successMessage += '\nThank you for supporting ISNM!';
        
        alert(successMessage);
        
        // Reset form and close modal
        closeDonationModal();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Process Donation';
        
        // In a real implementation, you would send this data to your server
        console.log('Donation Data:', donationData);
      }, 2000);
    }

    // Close modal function
    function closeDonationModal() {
      const modalElement = document.getElementById('donationModal');
      if (modalElement) {
        // Remove show class
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Reset form
        document.getElementById('donationForm').reset();
        
        console.log('Modal closed');
      }
    }

    // Add input formatting listeners
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded, setting up donation form');
      
      // Card number formatting
      const cardNumberInput = document.getElementById('cardNumber');
      if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
          e.target.value = formatCardNumber(e.target.value);
        });
      }
      
      // Expiry date formatting
      const expiryDateInput = document.getElementById('expiryDate');
      if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
          e.target.value = formatExpiryDate(e.target.value);
        });
      }
      
      // CVV input (numbers only)
      const cvvInput = document.getElementById('cvv');
      if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
      }
      
      // ZIP code input (numbers only)
      const zipInput = document.getElementById('billingZip');
      if (zipInput) {
        zipInput.addEventListener('input', function(e) {
          e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
      }
      
      // Setup close buttons
      const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
      closeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          closeDonationModal();
        });
      });
      
      // Close on backdrop click
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
          closeDonationModal();
        }
      });
      
      // Close on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const modal = document.getElementById('donationModal');
          if (modal && modal.classList.contains('show')) {
            closeDonationModal();
          }
        }
      });
    });
  </script>

  
  <?php include('shared/_footer.php'); ?>
