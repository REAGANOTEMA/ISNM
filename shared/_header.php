<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="ISNM">
  <meta name="application-name" content="ISNM">
  <meta name="theme-color" content="#3E2723">
  <meta name="msapplication-TileColor" content="#3E2723">
  <meta name="msapplication-config" content="/browserconfig.xml">
  <link rel="manifest" href="manifest.json">
  
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Iganga School of Nursing and Midwifery'; ?></title>
  <meta name="description" content="Iganga School of Nursing and Midwifery - Quality Healthcare Education in Uganda">
  <meta name="keywords" content="nursing school, midwifery, healthcare education, ISNM, Uganda">
  <meta name="author" content="Iganga School of Nursing and Midwifery">
  
  <!-- Favicon and Apple Touch Icons -->
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  <link rel="apple-touch-icon" href="images/school-logo.png">
  <link rel="apple-touch-icon" sizes="152x152" href="images/school-logo.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/school-logo.png">
  <link rel="icon" type="image/png" sizes="32x32" href="images/school-logo.png">
  <link rel="icon" type="image/png" sizes="16x16" href="images/school-logo.png">
    
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Rockwell:wght@700;800;900&family=Haettenschweiler&family=Elephant&family=Cooper+Black&family=Bodoni+MT+Poster+Compressed&family=Colonna+MT&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="shared/style.css" />
  <link rel="stylesheet" href="css/isnm-style.css" />
  <link rel="stylesheet" href="css/responsive.css" />
  <link rel="stylesheet" href="css/animations.css" />
  
  <!-- Perfect Mobile Responsive CSS -->
  <style>
    /* ISNM Color Scheme Variables */
    :root {
      --isnm-yellow: #FFD700;
      --isnm-cream: #FFF8DC;
      --isnm-chocolate: #3E2723;
      --isnm-dark-blue: #1A237E;
      --isnm-light-blue: #3949AB;
      --isnm-gold: #FFA000;
      --isnm-beige: #F5F5DC;
    }
    
    /* Perfect Mobile Base Styles */
    * {
      box-sizing: border-box;
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
    }
    
    html {
      font-size: 16px;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
      scroll-behavior: smooth;
    }
    
    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      line-height: 1.6;
      color: #333;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    
    /* Perfect Mobile Images */
    img {
      max-width: 100%;
      height: auto;
      border: 0;
      -webkit-touch-callout: none;
    }
    
    /* Touch-friendly buttons */
    button, input, select, textarea {
      font-family: inherit;
      font-size: inherit;
      line-height: inherit;
      margin: 0;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }
    
    button, .btn {
      cursor: pointer;
      touch-action: manipulation;
      min-height: 44px;
      min-width: 44px;
      padding: 12px 24px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    
    /* Perfect Mobile Navigation */
    .navbar {
      background: var(--isnm-chocolate);
      padding: 0;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
      width: 100%;
    }
    
    .navbar-brand {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--isnm-yellow) !important;
    }
    
    .navbar-brand img {
      height: 40px;
      width: auto;
      margin-right: 10px;
    }
    
    .navbar-toggler {
      border: none;
      padding: 8px 12px;
      font-size: 1.5rem;
      color: var(--isnm-yellow);
      background: rgba(255,255,255,0.1);
      border-radius: 6px;
    }
    
    .navbar-toggler:focus {
      box-shadow: 0 0 0 3px rgba(255,215,0,0.3);
      outline: none;
    }
    
    .navbar-nav {
      padding: 10px 0;
    }
    
    .nav-link {
      color: #fff !important;
      padding: 12px 20px !important;
      font-weight: 500;
      border-radius: 6px;
      margin: 2px 10px;
      transition: all 0.3s ease;
      text-align: center;
    }
    
    .nav-link:hover, .nav-link:focus {
      background: rgba(255,215,0,0.2);
      color: var(--isnm-yellow) !important;
      transform: translateY(-1px);
    }
    
    .nav-link.active {
      background: var(--isnm-yellow);
      color: var(--isnm-chocolate) !important;
    }
    
    /* Mobile Specific Styles */
    @media (max-width: 768px) {
      .navbar-brand {
        font-size: 1rem;
      }
      
      .navbar-brand img {
        height: 35px;
      }
      
      .navbar-nav {
        background: var(--isnm-chocolate);
        padding: 15px;
        margin: 0;
        border-top: 2px solid var(--isnm-yellow);
      }
      
      .nav-link {
        margin: 5px 0;
        padding: 15px 20px !important;
        font-size: 1rem;
      }
      
      .dropdown-menu {
        background: var(--isnm-dark-blue);
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      }
      
      .dropdown-item {
        color: #fff;
        padding: 12px 20px;
        border-radius: 4px;
        margin: 2px 0;
      }
      
      .dropdown-item:hover {
        background: var(--isnm-yellow);
        color: var(--isnm-chocolate);
      }
    }
    
    /* Small Mobile Devices */
    @media (max-width: 480px) {
      html {
        font-size: 14px;
      }
      
      .navbar-brand {
        font-size: 0.9rem;
      }
      
      .navbar-brand img {
        height: 30px;
      }
      
      .container, .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
      }
      
      .btn {
        padding: 10px 20px;
        font-size: 0.9rem;
        min-height: 40px;
      }
      
      .form-control {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 12px 15px;
        border-radius: 8px;
      }
    }
    
    /* iPhone and iOS Specific */
    @supports (-webkit-touch-callout: none) {
      .navbar {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      }
      
      .modal {
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
      }
    }
    
    /* Landscape Mobile */
    @media (max-height: 500px) and (orientation: landscape) {
      .navbar {
        padding: 5px 0;
      }
      
      .navbar-brand img {
        height: 25px;
      }
      
      .nav-link {
        padding: 8px 15px !important;
        font-size: 0.9rem;
      }
    }
    
    /* Tablet Styles */
    @media (min-width: 769px) and (max-width: 1024px) {
      .navbar-brand {
        font-size: 1.1rem;
      }
      
      .nav-link {
        padding: 10px 15px !important;
        font-size: 0.95rem;
      }
    }
    
    /* Perfectly Aligned ISNM Header */
    .isnm-header {
      background: linear-gradient(135deg, var(--isnm-chocolate), var(--isnm-dark-blue));
      padding: 20px 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      position: relative;
      z-index: 1000;
      border-bottom: 3px solid var(--isnm-yellow);
    }
    
    .isnm-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: repeating-linear-gradient(
        90deg,
        var(--isnm-yellow) 0px,
        var(--isnm-yellow) 15px,
        var(--isnm-cream) 15px,
        var(--isnm-cream) 30px,
        var(--isnm-gold) 30px,
        var(--isnm-gold) 45px
      );
      animation: stripeMove 4s linear infinite;
    }
    
    @keyframes stripeMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(45px); }
    }
    
    .header-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 1400px;
      margin: 0 auto;
      height: 80px;
      position: relative;
    }
    
    .header-logo {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
    }
    
    .logo-img {
      height: 60px;
      width: auto;
      border-radius: 50%;
      border: 3px solid var(--isnm-yellow);
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
      background: var(--isnm-cream);
      padding: 3px;
    }
    
    .logo-img:hover {
      transform: scale(1.08) rotate(2deg);
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
      border-color: var(--isnm-gold);
    }
    
    .header-logo-right {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
    }
    
    .logo-img-right {
      height: 60px;
      width: auto;
      border-radius: 50%;
      border: 3px solid var(--isnm-yellow);
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
      background: var(--isnm-cream);
      padding: 3px;
    }
    
    .logo-img-right:hover {
      transform: scale(1.08) rotate(-2deg);
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
      border-color: var(--isnm-gold);
    }
    
    .logo-link {
      display: block;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .logo-link:hover {
      transform: scale(1.05);
    }
    
    .logo-link:hover .logo-img,
    .logo-link:hover .logo-img-right {
      transform: scale(1.08) rotate(2deg);
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
      border-color: var(--isnm-gold);
    }
    
    .header-title {
      flex: 1;
      text-align: center;
      padding: 0 30px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-width: 0;
      max-width: calc(100% - 160px);
    }
    
    .title-wrapper {
      overflow: hidden;
      position: relative;
      width: 100%;
      margin-bottom: 8px;
      height: 50px;
      display: flex;
      align-items: center;
    }
    
    .ticker-wrapper {
      overflow: hidden;
      position: relative;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
    }
    
    .ticker-track {
      display: flex;
      white-space: nowrap;
      animation: tickerScroll 20s linear infinite;
      will-change: transform;
    }
    
    .school-title {
      font-family: 'Rockwell Extra Bold', 'Rockwell', serif;
      font-weight: 900;
      font-size: 2.2rem;
      margin: 0;
      text-transform: uppercase;
      letter-spacing: 3px;
      white-space: nowrap;
      display: inline-block;
      padding-right: 80px;
      
      /* Clean 3D Text Effect - Embossed Look */
      text-shadow: 
        /* Main depth shadows */
        0px 1px 0px rgba(0, 0, 0, 0.8),
        0px 2px 0px rgba(0, 0, 0, 0.7),
        0px 3px 0px rgba(0, 0, 0, 0.6),
        0px 4px 0px rgba(0, 0, 0, 0.5),
        0px 5px 0px rgba(0, 0, 0, 0.4),
        /* Subtle highlight */
        0px -1px 0px rgba(255, 255, 255, 0.1),
        0px -2px 0px rgba(255, 255, 255, 0.05);
      
      /* Color Cycling Animation - 5 Second Intervals */
      animation: 
        tickerScroll 20s linear infinite,
        colorCycle 25s ease-in-out infinite;
      
      /* Initial color */
      color: var(--isnm-yellow);
      
      filter: contrast(1.1) brightness(1.1);
    }
    
    /* Color Cycling Keyframes - 5 seconds each color */
    @keyframes colorCycle {
      0%, 20% {
        color: var(--isnm-yellow);
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.7),
          0px 3px 0px rgba(0, 0, 0, 0.6),
          0px 4px 0px rgba(0, 0, 0, 0.5),
          0px 5px 0px rgba(0, 0, 0, 0.4),
          0px -1px 0px rgba(255, 255, 255, 0.1),
          0px -2px 0px rgba(255, 255, 255, 0.05);
      }
      25%, 45% {
        color: var(--isnm-cream);
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.7),
          0px 3px 0px rgba(0, 0, 0, 0.6),
          0px 4px 0px rgba(0, 0, 0, 0.5),
          0px 5px 0px rgba(0, 0, 0, 0.4),
          0px -1px 0px rgba(0, 0, 0, 0.1),
          0px -2px 0px rgba(0, 0, 0, 0.05);
      }
      50%, 70% {
        color: #90EE90; /* Light Green */
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.7),
          0px 3px 0px rgba(0, 0, 0, 0.6),
          0px 4px 0px rgba(0, 0, 0, 0.5),
          0px 5px 0px rgba(0, 0, 0, 0.4),
          0px -1px 0px rgba(255, 255, 255, 0.15),
          0px -2px 0px rgba(255, 255, 255, 0.08);
      }
      75%, 95% {
        color: var(--isnm-light-blue);
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.7),
          0px 3px 0px rgba(0, 0, 0, 0.6),
          0px 4px 0px rgba(0, 0, 0, 0.5),
          0px 5px 0px rgba(0, 0, 0, 0.4),
          0px -1px 0px rgba(255, 255, 255, 0.12),
          0px -2px 0px rgba(255, 255, 255, 0.06);
      }
      100% {
        color: var(--isnm-chocolate);
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.7),
          0px 3px 0px rgba(0, 0, 0, 0.6),
          0px 4px 0px rgba(0, 0, 0, 0.5),
          0px 5px 0px rgba(0, 0, 0, 0.4),
          0px -1px 0px rgba(255, 255, 255, 0.08),
          0px -2px 0px rgba(255, 255, 255, 0.04);
      }
    }
    
    @keyframes tickerScroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-50%);
      }
    }
    
    .school-motto {
      font-family: 'Montserrat', sans-serif;
      font-weight: 300;
      font-size: 0.95rem;
      color: var(--isnm-cream);
      margin: 0;
      font-style: italic;
      opacity: 0.95;
      text-shadow: 0 2px 4px rgba(0,0,0,0.4);
      letter-spacing: 0.5px;
    }
    
    /* Cinematic Hero Section */
    .hero-section {
      position: relative;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .hero-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }
    
    .hero-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      transition: opacity 2s ease-in-out;
      transform: scale(1.1);
    }
    
    .hero-slide.active {
      opacity: 1;
      transform: scale(1);
    }
    
    .hero-bg {
      width: 100%;
      height: 100%;
      object-fit: cover;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
      image-rendering: pixelated;
      backface-visibility: hidden;
      transform: translateZ(0);
      -webkit-transform: translateZ(0);
      -webkit-font-smoothing: antialiased;
    }
    
    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        180deg,
        rgba(0, 0, 0, 0.4) 0%,
        rgba(0, 0, 0, 0.6) 50%,
        rgba(0, 0, 0, 0.8) 100%
      );
      z-index: 2;
    }
    
    .hero-content {
      position: relative;
      z-index: 3;
      text-align: center;
      width: 100%;
      max-width: 1200px;
      padding: 0 20px;
    }
    
    .cinematic-title-wrapper {
      position: relative;
      overflow: hidden;
      width: 100%;
      margin-bottom: 40px;
    }
    
    .cinematic-title-track {
      display: flex;
      white-space: nowrap;
      animation: cinematicTitleScroll 25s linear infinite;
      will-change: transform;
    }
    
    .cinematic-title {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      font-size: 4.2rem;
      line-height: 1.1;
      color: #ffffff;
      text-shadow: 
        2px 2px 8px rgba(0, 0, 0, 0.9),
        0 0 20px rgba(0, 0, 0, 0.7),
        0 0 40px rgba(0, 0, 0, 0.5);
      display: inline-block;
      padding-right: 120px;
      letter-spacing: 4px;
      text-transform: uppercase;
      opacity: 1;
      filter: contrast(1.1) brightness(1.1);
    }
    
    @keyframes cinematicTitleScroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-50%);
      }
    }
    
    .hero-subtitle {
      margin-bottom: 40px;
    }
    
    .hero-subtitle p {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.3rem;
      font-style: italic;
      color: #ffffff;
      text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.9);
      opacity: 1;
    }
    
    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 60px;
      margin-bottom: 50px;
    }
    
    .stat-item {
      text-align: center;
      position: relative;
      padding: 25px 20px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #f8f9fa 100%);
      border-radius: 15px;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 
        0 5px 15px rgba(0, 0, 0, 0.1),
        0 2px 8px rgba(0, 0, 0, 0.05),
        inset 0 1px 3px rgba(255, 255, 255, 0.8),
        inset 0 -1px 3px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .stat-item:hover {
      transform: translateY(-3px);
      box-shadow: 
        0 8px 25px rgba(0, 0, 0, 0.15),
        0 4px 12px rgba(0, 0, 0, 0.08),
        inset 0 1px 3px rgba(255, 255, 255, 0.9),
        inset 0 -1px 3px rgba(0, 0, 0, 0.15);
    }
    
    .stat-item::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, var(--isnm-yellow), var(--isnm-gold));
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
    }
    
    @keyframes floatButton {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-3px); }
    }
    
    .stat-number {
      display: block;
      font-family: 'Playfair Display', serif;
      font-size: 3rem;
      font-weight: 900;
      color: #000000;
      line-height: 1;
      position: relative;
      z-index: 3;
    }
    
    .stat-label {
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      color: #000000;
      position: relative;
      z-index: 3;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 5px;
    }
    
    .cta-buttons {
      display: flex;
      justify-content: center;
      gap: 25px;
      flex-wrap: wrap;
    }
    
    .btn-cinematic {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 18px 35px;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 1.1rem;
      text-decoration: none;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      position: relative;
      overflow: hidden;
    }
    
    .btn-cinematic.btn-primary {
      background: linear-gradient(135deg, #FFD700, #FFA500);
      color: #1A237E;
      border: none;
      box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
    }
    
    .btn-cinematic.btn-secondary {
      background: transparent;
      color: #ffffff;
      border: 2px solid #FFD700;
      box-shadow: 0 8px 25px rgba(255, 215, 0, 0.2);
    }
    
    .btn-cinematic.btn-outline {
      background: transparent;
      color: #ffffff;
      border: 2px solid #ffffff;
      box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
    }
    
    .btn-cinematic:hover {
      transform: translateY(-5px) scale(1.05);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }
    
    .btn-cinematic.btn-primary:hover {
      box-shadow: 0 15px 40px rgba(255, 215, 0, 0.5);
    }
    
    .btn-cinematic::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      transition: left 0.6s ease;
    }
    
    .btn-cinematic:hover::before {
      left: 100%;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
      .cinematic-title {
        font-size: 2.8rem;
        padding-right: 100px;
        letter-spacing: 3px;
      }
      
      .hero-subtitle p {
        font-size: 1.1rem;
      }
      
      .hero-stats {
        gap: 30px;
        margin-bottom: 40px;
      }
      
      .stat-number {
        font-size: 2.2rem;
      }
      
      .stat-label {
        font-size: 0.9rem;
      }
      
      .cta-buttons {
        gap: 15px;
      }
      
      .btn-cinematic {
        padding: 15px 25px;
        font-size: 0.95rem;
      }
      
      .about-image {
        transform: scale(1.02);
      }
      
      .about-image img {
        min-height: 350px;
      }
      
      .stat-item {
        padding: 20px 15px;
      }
      
      .stat-number {
        font-size: 2.2rem;
      }
      
      .stat-label {
        font-size: 0.9rem;
        letter-spacing: 0.3px;
      }
    }
    
    .hero-content-container {
      position: relative;
      z-index: 3;
      height: 100%;
    }
    
    .hero-text {
      animation: slideInLeft 1s ease-out;
    }
    
    @keyframes slideInLeft {
      0% {
        transform: translateX(-100px);
        opacity: 0;
      }
      100% {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    .hero-badge {
      display: inline-block;
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate);
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      font-size: 3.5rem;
      line-height: 1.2;
      margin-bottom: 20px;
      color: var(--isnm-cream);
    }
    
    .title-line {
      display: block;
    }
    
    .title-line.highlight {
      background: linear-gradient(45deg, var(--isnm-yellow), var(--isnm-gold));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      position: relative;
    }
    
    .hero-subtitle {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.2rem;
      font-style: italic;
      color: var(--isnm-cream);
      margin-bottom: 25px;
      opacity: 0.9;
    }
    
    .hero-description {
      font-family: 'Poppins', sans-serif;
      font-size: 1.1rem;
      line-height: 1.6;
      color: var(--isnm-cream);
      margin-bottom: 30px;
      opacity: 0.95;
    }
    
    .hero-stats {
      display: flex;
      gap: 40px;
      margin-bottom: 40px;
    }
    
    .stat-item {
      text-align: center;
    }
    
    .stat-number {
      display: block;
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 900;
      color: var(--isnm-yellow);
      line-height: 1;
    }
    
    .stat-label {
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      color: var(--isnm-cream);
      opacity: 0.8;
    }
    
    .cta-buttons {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }
    
    .btn-hero {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 15px 30px;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .btn-hero.btn-primary {
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate);
      border: none;
    }
    
    .btn-hero.btn-secondary {
      background: transparent;
      color: var(--isnm-cream);
      border: 2px solid var(--isnm-yellow);
    }
    
    .btn-hero.btn-outline {
      background: transparent;
      color: var(--isnm-cream);
      border: 2px solid var(--isnm-cream);
    }
    
    .btn-hero:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .btn-shine {
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s ease;
    }
    
    .btn-hero:hover .btn-shine {
      left: 100%;
    }
    
    .hero-features {
      animation: slideInRight 1s ease-out;
    }
    
    @keyframes slideInRight {
      0% {
        transform: translateX(100px);
        opacity: 0;
      }
      100% {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    .feature-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 215, 0, 0.2);
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 20px;
      transition: all 0.3s ease;
    }
    
    .feature-card:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.15);
      border-color: var(--isnm-yellow);
    }
    
    .feature-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--isnm-chocolate);
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    
    .feature-content h4 {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      color: var(--isnm-cream);
      margin-bottom: 8px;
    }
    
    .feature-content p {
      font-family: 'Poppins', sans-serif;
      color: var(--isnm-cream);
      opacity: 0.8;
      font-size: 0.9rem;
      margin: 0;
    }
    
    .hero-indicators {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 15px;
      z-index: 4;
    }
    
    .indicator {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      border: 2px solid var(--isnm-yellow);
      background: transparent;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .indicator.active {
      background: var(--isnm-yellow);
    }
    
    .hero-scroll {
      position: absolute;
      bottom: 30px;
      left: 30px;
      z-index: 4;
      animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-10px); }
      60% { transform: translateY(-5px); }
    }
    
    .scroll-text {
      font-family: 'Poppins', sans-serif;
      font-size: 0.8rem;
      color: var(--isnm-cream);
      opacity: 0.7;
      margin-bottom: 5px;
    }
    
    .scroll-arrow {
      color: var(--isnm-yellow);
      font-size: 1.2rem;
    }
    
    /* About Section Enhanced Image */
    .about-image {
      position: relative;
      overflow: hidden;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      transform: scale(1.05);
      animation: slideInRight 1s ease-out;
    }
    
    .about-image img {
      width: 100%;
      height: auto;
      min-height: 450px;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    
    .about-image:hover img {
      transform: scale(1.08);
    }
    
    .about-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, 
        rgba(255, 215, 0, 0.1) 0%, 
        transparent 50%, 
        rgba(255, 215, 0, 0.05) 100%);
      border-radius: 20px;
      pointer-events: none;
    }
    
    .about-image::after {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, var(--isnm-yellow), transparent, var(--isnm-gold));
      border-radius: 20px;
      z-index: -1;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .about-image:hover::after {
      opacity: 0.6;
    }
    
    @keyframes slideInRight {
      0% {
        transform: translateX(100px) scale(0.95);
        opacity: 0;
      }
      100% {
        transform: translateX(0) scale(1.05);
        opacity: 1;
      }
    }
    
    /* Hero Section Mobile Responsive */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.2rem;
      }
      
      .hero-subtitle {
        font-size: 1rem;
      }
      
      .hero-description {
        font-size: 1rem;
      }
      
      .hero-stats {
        gap: 20px;
        margin-bottom: 30px;
      }
      
      .stat-number {
        font-size: 2rem;
      }
      
      .stat-label {
        font-size: 0.8rem;
      }
      
      .cta-buttons {
        gap: 15px;
      }
      
      .btn-hero {
        padding: 12px 20px;
        font-size: 0.9rem;
      }
      
      .feature-card {
        padding: 20px;
        margin-bottom: 15px;
      }
      
      .feature-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
      }
      
      .hero-indicators {
        bottom: 20px;
        gap: 10px;
      }
      
      .indicator {
        width: 10px;
        height: 10px;
      }
      
      .hero-scroll {
        bottom: 20px;
        left: 20px;
      }
    }
    
    /* Clean Bootstrap 5 Navbar with Perfect Alignment */
    .isnm-navbar {
      background: linear-gradient(135deg, var(--isnm-cream), #fafafa);
      border-bottom: 3px solid var(--isnm-chocolate);
      box-shadow: 0 4px 20px rgba(0,0,0,0.12);
      padding: 0;
      position: sticky;
      top: 0;
      z-index: 999;
      min-height: 70px;
    }
    
    .isnm-navbar .navbar-brand {
      display: flex;
      align-items: center;
      color: var(--isnm-chocolate) !important;
      font-weight: 700;
      font-size: 1.3rem;
      font-family: 'Rockwell Extra Bold', 'Rockwell', serif;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: all 0.3s ease;
      height: 70px;
    }
    
    .isnm-navbar .navbar-brand:hover {
      color: var(--isnm-dark-blue) !important;
      transform: scale(1.02);
    }
    
    .brand-logo {
      height: 40px;
      width: auto;
      margin-right: 12px;
      border-radius: 50%;
      border: 3px solid var(--isnm-yellow);
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }
    
    .brand-name {
      font-family: 'Rockwell Extra Bold', 'Rockwell', serif;
      text-transform: uppercase;
      letter-spacing: 1px;
      background: linear-gradient(45deg, var(--isnm-chocolate), var(--isnm-dark-blue));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .isnm-navbar .navbar-toggler {
      border: 3px solid var(--isnm-chocolate);
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate);
      padding: 10px 14px;
      border-radius: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      height: 45px;
      width: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .isnm-navbar .navbar-toggler:hover {
      background: linear-gradient(135deg, var(--isnm-gold), var(--isnm-yellow));
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
    }
    
    .isnm-navbar .navbar-toggler:focus {
      box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.3);
    }
    
    .isnm-navbar .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%233E2723' stroke-linecap='round' stroke-miterlimit='10' stroke-width='3' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
      width: 24px;
      height: 24px;
    }
    
    /* Desktop Navigation - Perfect Alignment */
    @media (min-width: 992px) {
      .isnm-navbar .navbar-collapse {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 70px;
      }
      
      .isnm-navbar .navbar-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 4px;
        height: 100%;
      }
      
      .isnm-navbar .nav-item {
        display: flex;
        align-items: center;
        height: 100%;
      }
      
      .isnm-navbar .nav-link {
        color: var(--isnm-chocolate) !important;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        padding: 18px 20px !important;
        margin: 0 2px;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        background: transparent;
        border: 2px solid transparent;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
      }
      
      .isnm-navbar .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
        transition: left 0.5s ease;
      }
      
      .isnm-navbar .nav-link:hover::before {
        left: 100%;
      }
      
      .isnm-navbar .nav-link:hover,
      .isnm-navbar .nav-link:focus {
        background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
        color: var(--isnm-chocolate) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        border: 2px solid var(--isnm-chocolate);
      }
      
      .isnm-navbar .nav-link.active {
        background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
        color: var(--isnm-chocolate) !important;
        box-shadow: 0 2px 10px rgba(255, 215, 0, 0.2);
        border: 2px solid var(--isnm-chocolate);
      }
    }
    
    /* Mobile Navigation - Clean Collapse */
    @media (max-width: 991px) {
      .isnm-navbar .navbar-collapse {
        background: linear-gradient(135deg, var(--isnm-cream), #fafafa);
        border: 3px solid var(--isnm-chocolate);
        border-radius: 12px;
        padding: 20px;
        margin-top: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        max-height: 80vh;
        overflow-y: auto;
      }
      
      .isnm-navbar .navbar-nav {
        text-align: center;
        gap: 6px;
        width: 100%;
      }
      
      .isnm-navbar .nav-item {
        width: 100%;
      }
      
      .isnm-navbar .nav-link {
        color: var(--isnm-chocolate) !important;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        padding: 16px 20px !important;
        margin: 2px 0;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #ffffff, #f8f9fa);
        border: 2px solid var(--isnm-chocolate);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        width: 100%;
        text-align: center;
      }
      
      .isnm-navbar .nav-link:hover,
      .isnm-navbar .nav-link:focus {
        background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
        color: var(--isnm-chocolate) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
      }
    }
    
    /* Dropdown Menu - Perfect Alignment */
    .isnm-navbar .dropdown-menu {
      background: linear-gradient(135deg, var(--isnm-cream), #fafafa);
      border: 2px solid var(--isnm-chocolate);
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.15);
      margin-top: 8px;
      padding: 8px;
      min-width: 220px;
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }
    
    .isnm-navbar .dropdown-item {
      color: var(--isnm-chocolate);
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      padding: 12px 16px;
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: 2px 0;
      background: transparent;
      border: 1px solid transparent;
      display: flex;
      align-items: center;
    }
    
    .isnm-navbar .dropdown-item:hover {
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate) !important;
      transform: translateX(3px);
      box-shadow: 0 2px 8px rgba(255, 215, 0, 0.2);
    }
    
    .isnm-navbar .dropdown-item i {
      width: 20px;
      text-align: center;
      margin-right: 10px;
      font-size: 1rem;
      flex-shrink: 0;
    }
    
    .isnm-navbar .dropdown-toggle::after {
      border-top-color: var(--isnm-chocolate);
      border-width: 6px 6px 0;
      margin-left: 8px;
    }
    
    /* Enhanced Mobile Responsiveness - Perfect Stack */
    @media (max-width: 991px) {
      /* Mobile Header - Clean Stack Layout */
      .isnm-header {
        padding: 15px 20px;
        min-height: auto;
        position: relative;
      }
      
      .header-container {
        flex-direction: column;
        gap: 15px;
        align-items: center;
        justify-content: center;
        height: auto;
        min-height: auto;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
      }
      
      .header-logo {
        order: 1;
        width: auto;
        height: auto;
      }
      
      .header-title {
        order: 2;
        padding: 0 10px;
        width: 100%;
        max-width: 100%;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      
      .header-logo-right {
        order: 3;
        width: auto;
        height: auto;
      }
      
      .logo-img,
      .logo-img-right {
        height: 45px;
        width: auto;
        max-width: 45px;
      }
      
      .title-wrapper {
        overflow: hidden;
        position: relative;
        width: 100%;
        margin-bottom: 8px;
        max-width: 100%;
      }
      
      .ticker-wrapper {
        overflow: hidden;
        position: relative;
        width: 100%;
        height: 40px;
        display: flex;
        align-items: center;
        max-width: 100%;
      }
      
      .school-title {
        font-size: 1.5rem;
        letter-spacing: 1px;
        padding-right: 60px;
        /* Adjusted 3D effect for mobile */
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.8),
          0px 2px 0px rgba(0, 0, 0, 0.6),
          0px 3px 0px rgba(0, 0, 0, 0.4),
          0px 0px 15px rgba(255, 215, 0, 0.2);
        /* Color cycling continues on mobile */
        animation: 
          tickerScrollMobile 15s linear infinite,
          colorCycle 25s ease-in-out infinite;
      }
      
      .school-motto {
        font-size: 0.85rem;
        padding: 0 5px;
        text-align: center;
        line-height: 1.3;
        max-width: 100%;
        word-wrap: break-word;
      }
      
      /* Prevent horizontal overflow */
      .ticker-track {
        animation: tickerScrollMobile 15s linear infinite;
        will-change: transform;
      }
      
      @keyframes tickerScrollMobile {
        0% {
          transform: translateX(0);
        }
        100% {
          transform: translateX(-50%);
        }
      }
    }
    
    @media (max-width: 768px) {
      .isnm-header {
        padding: 12px 15px;
      }
      
      .header-container {
        gap: 12px;
      }
      
      .logo-img,
      .logo-img-right {
        height: 40px;
        max-width: 40px;
      }
      
      .school-title {
        font-size: 1.3rem;
        letter-spacing: 0.8px;
        padding-right: 50px;
      }
      
      .school-motto {
        font-size: 0.8rem;
      }
      
      .ticker-wrapper {
        height: 35px;
      }
      
      .ticker-track {
        animation: tickerScrollTablet 13s linear infinite;
      }
      
      @keyframes tickerScrollTablet {
        0% {
          transform: translateX(0);
        }
        100% {
          transform: translateX(-50%);
        }
      }
    }
    
    @media (max-width: 480px) {
      .isnm-header {
        padding: 10px 12px;
      }
      
      .header-container {
        gap: 10px;
        padding: 0 5px;
      }
      
      .logo-img,
      .logo-img-right {
        height: 35px;
        max-width: 35px;
      }
      
      .school-title {
        font-size: 1.1rem;
        letter-spacing: 0.5px;
        padding-right: 40px;
        /* Simplified 3D for very small screens */
        text-shadow: 
          0px 1px 0px rgba(0, 0, 0, 0.7),
          0px 2px 0px rgba(0, 0, 0, 0.5),
          0px 0px 10px rgba(255, 215, 0, 0.15);
        /* Color cycling continues */
        animation: 
          tickerScrollSmall 12s linear infinite,
          colorCycle 25s ease-in-out infinite;
      }
      
      .school-motto {
        font-size: 0.75rem;
        padding: 0 3px;
        line-height: 1.2;
      }
      
      .ticker-wrapper {
        height: 30px;
      }
      
      .ticker-track {
        animation: tickerScrollSmall 12s linear infinite;
      }
      
      @keyframes tickerScrollSmall {
        0% {
          transform: translateX(0);
        }
        100% {
          transform: translateX(-50%);
        }
      }
    }
    
    /* Prevent horizontal scroll on all devices */
    body {
      overflow-x: hidden;
      width: 100%;
      max-width: 100vw;
    }
    
    .isnm-header,
    .isnm-navbar {
      width: 100%;
      max-width: 100%;
      overflow-x: hidden;
      position: relative;
    }
    
    .header-container {
      max-width: 100%;
      overflow-x: hidden;
      position: relative;
    }
    
    .ticker-wrapper {
      overflow: hidden;
      width: 100%;
      position: relative;
    }
    
    /* Final Visual Polish and Performance Optimizations */
    
    /* Smooth scrolling for entire page */
    html {
      scroll-behavior: smooth;
    }
    
    /* Prevent horizontal scroll on all devices */
    body {
      overflow-x: hidden;
      width: 100%;
      max-width: 100vw;
    }
    
    .isnm-header,
    .isnm-navbar {
      width: 100%;
      max-width: 100%;
      overflow-x: hidden;
      position: relative;
    }
    
    .header-container {
      max-width: 100%;
      overflow-x: hidden;
      position: relative;
    }
    
    .ticker-wrapper {
      overflow: hidden;
      width: 100%;
      position: relative;
    }
    
    /* Enhanced visual depth and premium feel */
    .isnm-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, 
        transparent 0%, 
        var(--isnm-yellow) 25%, 
        var(--isnm-gold) 50%, 
        var(--isnm-yellow) 75%, 
        transparent 100%);
      animation: shimmer 3s ease-in-out infinite;
    }
    
    @keyframes shimmer {
      0%, 100% { opacity: 0.3; }
      50% { opacity: 0.8; }
    }
    
    /* Premium navbar enhancements */
    .isnm-navbar {
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    
    /* Enhanced logo effects */
    .logo-img::before,
    .logo-img-right::before {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      background: linear-gradient(45deg, 
        var(--isnm-yellow), 
        var(--isnm-gold), 
        var(--isnm-yellow));
      border-radius: 50%;
      z-index: -1;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .logo-img:hover::before,
    .logo-img-right:hover::before {
      opacity: 1;
      animation: logoGlow 2s ease-in-out infinite;
    }
    
    @keyframes logoGlow {
      0%, 100% { 
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
      }
      50% { 
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
      }
    }
    
    /* Enhanced ticker text performance */
    .ticker-track {
      transform: translateZ(0);
      -webkit-transform: translateZ(0);
      backface-visibility: hidden;
      -webkit-backface-visibility: hidden;
      perspective: 1000px;
      -webkit-perspective: 1000px;
    }
    
    .school-title {
      transform: translateZ(0);
      -webkit-transform: translateZ(0);
      will-change: transform;
    }
    
    <?php
// Use enhanced configuration with multi-database support
require_once 'includes/config_enhanced.php';
include_once 'includes/functions.php'; ?>
    
    /* Enhanced navigation interactions */
    .isnm-navbar .nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: var(--isnm-chocolate);
      transition: all 0.3s ease;
      transform: translateX(-50%);
      z-index: -1;
    }
    
    .isnm-navbar .nav-link:hover::after {
      width: 80%;
    }
    
    /* Enhanced dropdown animations */
    .isnm-navbar .dropdown-menu {
      transform-origin: top;
      animation: dropdownSlide 0.3s ease-out;
    }
    
    @keyframes dropdownSlide {
      0% {
        opacity: 0;
        transform: translateY(-10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Performance optimizations */
    .isnm-header,
    .isnm-navbar,
    .logo-img,
    .logo-img-right,
    .school-title {
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    
    /* Enhanced focus states for accessibility */
    .isnm-navbar .nav-link:focus,
    .isnm-navbar .navbar-toggler:focus,
    .isnm-navbar .dropdown-item:focus {
      outline: 3px solid rgba(255, 215, 0, 0.5);
      outline-offset: 2px;
    }
    
    /* Smooth transitions for all interactive elements */
    .logo-img,
    .logo-img-right,
    .isnm-navbar .nav-link,
    .isnm-navbar .dropdown-item,
    .isnm-navbar .navbar-toggler {
      transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
  </style>
</head>

<body>

<!-- Perfectly Aligned ISNM Header -->
<header class="isnm-header">
  <div class="header-container">
    <!-- Left: Logo -->
    <div class="header-logo">
      <a href="index.php" class="logo-link">
        <img src="images/school-logo.png" alt="ISNM Logo" class="logo-img">
      </a>
    </div>
    
    <!-- Center: Animated School Title and Motto -->
    <div class="header-title">
      <div class="title-wrapper">
        <div class="ticker-wrapper">
          <div class="ticker-track">
            <h1 class="school-title">Iganga School of Nursing and Midwifery</h1>
            <h1 class="school-title">Iganga School of Nursing and Midwifery</h1>
          </div>
        </div>
      </div>
      <p class="school-motto">"Chosen to Serve - Based on a disciplined mind for health action"</p>
    </div>
    
    <!-- Right: Logo -->
    <div class="header-logo-right">
      <a href="index.php" class="logo-link">
        <img src="images/school-logo.png" alt="ISNM Logo" class="logo-img-right">
      </a>
    </div>
  </div>
</header>

<!-- Clean Bootstrap 5 Navigation -->
<nav class="navbar navbar-expand-lg isnm-navbar sticky-top">
  <div class="container">
    <!-- Mobile Brand (Logo + Name) -->
    <a class="navbar-brand d-lg-none" href="index.php">
      <img src="images/school-logo.png" alt="ISNM Logo" class="brand-logo">
      <span class="brand-name">ISNM</span>
    </a>
    
    <!-- Mobile Toggle Button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Navigation Menu -->
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="fas fa-home"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about.php">
            <i class="fas fa-info-circle"></i> About
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="history.php">
            <i class="fas fa-history"></i> History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="programs.php">
            <i class="fas fa-graduation-cap"></i> Programs
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="donation.php">
            <i class="fas fa-hand-holding-heart"></i> Donate
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="volunteer.php">
            <i class="fas fa-hands-helping"></i> Volunteer
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="contact.php">
            <i class="fas fa-envelope"></i> Contact
          </a>
        </li>
        
        <!-- Portal Link -->
        <li class="nav-item">
          <a class="nav-link" href="organogram.php">
            <i class="fas fa-sitemap"></i> Portal
          </a>
        </li>
        
        <!-- Login Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sign-in-alt"></i> Login
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
            <li><a class="dropdown-item" href="student-login.php">
              <i class="fas fa-user-graduate"></i> Student Login
            </a></li>
            <li><a class="dropdown-item" href="staff-login.php">
              <i class="fas fa-user-tie"></i> Staff Login
            </a></li>
            <li><a class="dropdown-item" href="application.php">
              <i class="fas fa-edit"></i> Apply Now
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>