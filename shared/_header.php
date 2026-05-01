<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Iganga School of Nursing and Midwifery'; ?></title>
  <meta name="description" content="Iganga School of Nursing and Midwifery - Quality Healthcare Education in Uganda">
  <meta name="keywords" content="nursing school, midwifery, healthcare education, ISNM, Uganda">
  <meta name="author" content="Iganga School of Nursing and Midwifery">
  
  <!-- Favicon Implementation -->
  <link rel="icon" type="image/x-icon" href="images/school-logo.png">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Rockwell:wght@700;800;900&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="shared/style.css" />
  <link rel="stylesheet" href="css/isnm-style.css" />
  <link rel="stylesheet" href="css/responsive.css" />
  <link rel="stylesheet" href="css/animations.css" />
  
  <!-- Enhanced Header CSS -->
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
    
    /* Header Stripes Design */
    .isnm-header {
      background: var(--isnm-chocolate);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
    }
    
    .isnm-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 8px;
      background: repeating-linear-gradient(
        90deg,
        var(--isnm-yellow) 0px,
        var(--isnm-yellow) 20px,
        var(--isnm-cream) 20px,
        var(--isnm-cream) 40px,
        var(--isnm-chocolate) 40px,
        var(--isnm-chocolate) 60px
      );
      animation: stripeMove 3s linear infinite;
    }
    
    .isnm-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: repeating-linear-gradient(
        90deg,
        var(--isnm-chocolate) 0px,
        var(--isnm-chocolate) 15px,
        var(--isnm-yellow) 15px,
        var(--isnm-yellow) 30px,
        var(--isnm-cream) 30px,
        var(--isnm-cream) 45px
      );
      animation: stripeMoveReverse 4s linear infinite;
    }
    
    @keyframes stripeMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(60px); }
    }
    
    @keyframes stripeMoveReverse {
      0% { transform: translateX(60px); }
      100% { transform: translateX(0); }
    }
    
    /* Animated School Title - Live TV Ticker */
    .school-title-container {
      padding: 15px 0;
      position: relative;
      z-index: 10;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .ticker-wrapper {
      overflow: hidden;
      width: 100%;
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .ticker-track {
      display: flex;
      white-space: nowrap;
      animation: tickerScroll 15s linear infinite;
      will-change: transform;
    }
    
    .school-title {
      font-family: 'Rockwell Extra Bold', 'Rockwell', serif;
      font-weight: 800;
      font-size: 2rem;
      background: linear-gradient(45deg, var(--isnm-yellow), var(--isnm-cream), var(--isnm-gold));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: titleGlow 3s ease-in-out infinite alternate;
      letter-spacing: 2px;
      text-transform: uppercase;
      white-space: nowrap;
      text-shadow: 
        0 0 20px rgba(255, 215, 0, 0.3),
        0 0 40px rgba(255, 215, 0, 0.2),
        0 0 60px rgba(255, 215, 0, 0.1);
      display: inline-block;
      padding-right: 100px;
    }
    
    .school-motto {
      font-family: 'Montserrat', sans-serif;
      font-weight: 300;
      font-size: 1rem;
      color: var(--isnm-cream);
      margin-top: 8px;
      font-style: italic;
      animation: fadeInOut 4s ease-in-out infinite;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      text-align: center;
    }
    
    /* Live TV Ticker Animation */
    @keyframes tickerScroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-50%);
      }
    }
    
    @keyframes titleGlow {
      0% { 
        text-shadow: 
          0 0 20px rgba(255, 215, 0, 0.3),
          0 0 40px rgba(255, 215, 0, 0.2),
          0 0 60px rgba(255, 215, 0, 0.1);
      }
      50% { 
        text-shadow: 
          0 0 30px rgba(255, 215, 0, 0.5),
          0 0 60px rgba(255, 215, 0, 0.3),
          0 0 90px rgba(255, 215, 0, 0.2);
      }
      100% { 
        text-shadow: 
          0 0 25px rgba(255, 215, 0, 0.4),
          0 0 50px rgba(255, 215, 0, 0.25),
          0 0 75px rgba(255, 215, 0, 0.15);
      }
    }
    
    @keyframes fadeInOut {
      0%, 100% { opacity: 0.7; }
      50% { opacity: 1; }
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
    
    /* Navigation Menu */
    .isnm-navigation {
      background: var(--isnm-cream);
      border-top: 3px solid var(--isnm-yellow);
      border-bottom: 3px solid var(--isnm-chocolate);
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 6px 30px rgba(0,0,0,0.25);
      height: 85px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      overflow: hidden;
    }
    
    .nav-links {
      display: flex;
      gap: 30px;
      align-items: center;
      margin-right: 40px;
    }
    
    .position-relative {
      position: relative;
    }
    
    .navbar-collapse {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }
    
    .navbar-nav {
      display: flex;
      gap: 25px;
      align-items: center;
      height: 100%;
      margin: 0;
    }
    
    .navbar-nav .nav-link {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      color: var(--isnm-chocolate) !important;
      padding: 12px 24px !important;
      margin: 0;
      border-radius: 25px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      border: 2px solid var(--isnm-chocolate);
      box-shadow: 
        0 4px 0 var(--isnm-chocolate),
        0 6px 12px rgba(0,0,0,0.2);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .navbar-nav .nav-link:hover {
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate) !important;
      transform: translateY(-3px);
      box-shadow: 
        0 7px 0 var(--isnm-chocolate),
        0 8px 16px rgba(0,0,0,0.25);
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
      border: 2px solid var(--isnm-chocolate);
    }
    
    .navbar-nav .nav-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, var(--isnm-yellow), transparent);
      transition: left 0.5s ease;
    }
    
    .navbar-nav .nav-link:hover::before {
      left: 100%;
    }
    
    .navbar-nav .nav-link:hover {
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate) !important;
      transform: perspective(1000px) rotateX(-3deg) rotateY(3deg) translateY(-5px);
      box-shadow: 
        0 15px 30px rgba(255, 215, 0, 0.4),
        0 8px 18px rgba(0,0,0,0.25),
        0 4px 8px rgba(255, 255, 255, 0.25),
        inset 0 3px 6px rgba(255, 255, 255, 0.3);
      text-shadow: none;
      border-color: var(--isnm-chocolate);
    }
    
    .navbar-nav .nav-link:active {
      transform: translateY(2px);
      box-shadow: 
        0 2px 0 var(--isnm-chocolate),
        0 4px 8px rgba(0,0,0,0.2);
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    /* 4D Depth Effect */
    .navbar-nav .nav-link::after {
      content: '';
      position: absolute;
      top: 3px;
      left: 3px;
      right: -3px;
      bottom: -3px;
      background: linear-gradient(135deg, rgba(0,0,0,0.25), rgba(0,0,0,0.08));
      border-radius: 25px;
      z-index: -1;
      transition: all 0.4s ease;
    }
    
    .navbar-nav .nav-link:hover::after {
      top: 6px;
      left: 6px;
      right: -6px;
      bottom: -6px;
      background: linear-gradient(135deg, rgba(0,0,0,0.35), rgba(0,0,0,0.15));
    }
    
    /* 3D Buttons */
    .btn-3d {
      font-family: 'Poppins', sans-serif;
      font-weight: 400;
      padding: 6px 14px;
      border: none;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
      color: var(--isnm-chocolate);
      position: relative;
      transform-style: preserve-3d;
      transition: all 0.3s ease;
      box-shadow: 
        0 3px 0 var(--isnm-chocolate),
        0 4px 8px rgba(0,0,0,0.15);
      text-transform: uppercase;
      letter-spacing: 0.2px;
      overflow: hidden;
      font-size: 0.75rem;
    }
    
    .btn-3d::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, var(--isnm-cream), var(--isnm-yellow));
      border-radius: 50px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .btn-3d:hover {
      transform: translateY(2px);
      box-shadow: 
        0 4px 0 var(--isnm-chocolate),
        0 8px 12px rgba(0,0,0,0.25);
    }
    
    .btn-3d:hover::before {
      opacity: 0.3;
    }
    
    .btn-3d:active {
      transform: translateY(4px);
      box-shadow: 
        0 2px 0 var(--isnm-chocolate),
        0 4px 8px rgba(0,0,0,0.25);
    }
    
    /* School Logo Integration */
    .school-logo-header-before-home {
      max-height: 65px;
      width: auto;
      border: 2px solid var(--isnm-yellow);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      border-radius: 50%;
      object-fit: contain;
      flex-shrink: 0;
      margin-right: 30px;
    }
    
    .school-logo-header-before-home img {
      max-height: 65px;
      width: auto;
      object-fit: contain;
    }
    
    .school-logo-header {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      border: 3px solid var(--isnm-yellow);
      box-shadow: 0 6px 20px rgba(0,0,0,0.25);
      transition: all 0.3s ease;
      position: relative;
      z-index: 10;
      flex-shrink: 0;
    }
    
    .school-logo-header:hover {
      border-color: var(--isnm-gold);
      box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
      transform: scale(1.05);
    }
    
    /* Standalone Logo at Far Right */
    .school-logo-header-standalone {
      width: 74.2px;
      height: 74.2px;
      border-radius: 50%;
      border: 4px solid var(--isnm-yellow);
      box-shadow: 0 8px 30px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
      position: relative;
      z-index: 15;
      background: rgba(255, 255, 255, 0.1);
      padding: 5px;
      image-rendering: auto;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
      flex-shrink: 0;
    }
    
    .school-logo-header-standalone:hover {
      border-color: var(--isnm-gold);
      box-shadow: 0 12px 40px rgba(255, 215, 0, 0.4);
      transform: scale(1.05);
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
      /* Enhanced Mobile Header */
      .isnm-header {
        flex-direction: column;
        padding: 15px 20px;
        gap: 15px;
      }
      
      .school-title-container {
        padding: 10px 0;
        text-align: center;
        width: 100%;
      }
      
      .school-title {
        font-family: 'Rockwell Extra Bold', 'Rockwell', serif;
        font-weight: 800;
        font-size: 1.3rem;
        letter-spacing: 0.5px;
        padding-right: 60px;
        line-height: 1.2;
      }
      
      .school-motto {
        font-size: 0.8rem;
        margin-top: 5px;
        text-align: center;
        padding: 0 10px;
      }
      
      .ticker-track {
        animation: tickerScrollMobile 10s linear infinite;
      }
      
      @keyframes tickerScrollMobile {
        0% {
          transform: translateX(0);
        }
        100% {
          transform: translateX(-50%);
        }
      }
      
      /* Enhanced Mobile Logo */
      .school-logo-header-standalone {
        width: 48px;
        height: 48px;
        border: 3px solid var(--isnm-yellow);
        margin: 0 auto;
        display: block;
        image-rendering: auto;
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
      }
      
      .school-logo-header-standalone:hover {
        transform: scale(1.08);
      }
      
      /* Enhanced Mobile Navigation */
      .isnm-navigation {
        height: auto;
        min-height: 60px;
        padding: 10px 15px;
        position: sticky;
        top: 0;
      }
      
      .navbar-toggler {
        border-color: var(--isnm-chocolate);
        background: var(--isnm-cream);
        padding: 8px 12px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      
      .navbar-toggler:hover {
        background: var(--isnm-yellow);
        transform: scale(1.05);
      }
      
      .navbar-toggler-icon {
        filter: invert(0.3);
      }
      
      .navbar-collapse {
        flex-direction: column;
        height: auto;
        background: var(--isnm-cream);
        border-radius: 12px;
        padding: 15px;
        margin-top: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 2px solid var(--isnm-chocolate);
      }
      
      .nav-links {
        flex-direction: column;
        gap: 8px;
        align-items: center;
        width: 100%;
      }
      
      .navbar-nav .nav-link {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 14px;
        color: var(--isnm-chocolate) !important;
        padding: 10px 16px !important;
        margin: 2px 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #ffffff 100%);
        border: 2px solid var(--isnm-chocolate);
        box-shadow: 
          0 3px 12px rgba(0,0,0,0.1),
          0 1px 4px rgba(0,0,0,0.05),
          inset 0 2px 4px rgba(255, 255, 255, 0.8);
        transform-style: preserve-3d;
        transform: perspective(600px) rotateX(0deg) rotateY(0deg);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        width: 100%;
        text-align: center;
        transition: all 0.3s ease;
      }
      
      .navbar-nav .nav-link:hover {
        background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
        color: var(--isnm-chocolate) !important;
        transform: perspective(800px) rotateX(-2deg) rotateY(2deg) translateY(-3px);
        box-shadow: 
          0 8px 20px rgba(255, 215, 0, 0.3),
          0 4px 12px rgba(0,0,0,0.15),
          0 2px 6px rgba(255, 255, 255, 0.3),
          inset 0 3px 6px rgba(255, 255, 255, 0.9);
        border: 2px solid var(--isnm-chocolate);
      }
      
      .navbar-nav .nav-link:active {
        transform: perspective(800px) rotateX(-1deg) rotateY(1deg) translateY(-1px);
        box-shadow: 
          0 3px 8px rgba(255, 215, 0, 0.2),
          0 2px 4px rgba(0,0,0,0.1),
          inset 0 2px 4px rgba(255, 255, 255, 0.7);
      }
      
      /* Enhanced Mobile 3D Buttons */
      .btn-3d {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        padding: 10px 18px;
        font-size: 13px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--isnm-yellow), var(--isnm-gold));
        color: var(--isnm-chocolate);
        border: 2px solid var(--isnm-chocolate);
        box-shadow: 
          0 4px 0 var(--isnm-chocolate),
          0 6px 12px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 5px 0;
        width: 100%;
        transition: all 0.3s ease;
      }
      
      .btn-3d:hover {
        transform: translateY(2px);
        box-shadow: 
          0 6px 0 var(--isnm-chocolate),
          0 8px 16px rgba(0,0,0,0.25);
      }
      
      .btn-3d:active {
        transform: translateY(4px);
        box-shadow: 
          0 2px 0 var(--isnm-chocolate),
          0 4px 8px rgba(0,0,0,0.2);
      }
      
      /* Mobile Action Buttons Container */
      .d-flex.gap-3 {
        flex-direction: column;
        width: 100%;
        gap: 10px !important;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid var(--isnm-chocolate);
      }
    }
    
    /* Extra Small Mobile */
    @media (max-width: 480px) {
      .school-title {
        font-size: 1.1rem;
        letter-spacing: 0.3px;
        padding-right: 40px;
      }
      
      .school-motto {
        font-size: 0.7rem;
        padding: 0 5px;
      }
      
      .school-logo-header-standalone {
        width: 42px;
        height: 42px;
        border: 2px solid var(--isnm-yellow);
      }
      
      .navbar-nav .nav-link {
        font-size: 13px;
        padding: 8px 12px !important;
      }
      
      .btn-3d {
        font-size: 12px;
        padding: 8px 14px;
      }
    }
  </style>
</head>

<body>

<!-- Enhanced ISNM Header with Animated Title -->
<header class="isnm-header">
  <div class="school-title-container">
    <div class="ticker-wrapper">
      <div class="ticker-track">
        <h1 class="school-title">Iganga School of Nursing and Midwifery</h1>
        <h1 class="school-title">Iganga School of Nursing and Midwifery</h1>
      </div>
    </div>
    <p class="school-motto">"Chosen to Serve - Based on a disciplined mind for health action"</p>
  </div>
  <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo-header-standalone">
</header>

<!-- Enhanced Navigation Menu -->
<nav class="navbar navbar-expand-lg isnm-navigation">
  <div class="container position-relative">
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="nav-links">
        <a class="nav-link" href="index.php">
          <i class="fas fa-home me-2"></i>Home
        </a>
        <a class="nav-link" href="about.php">
          <i class="fas fa-info-circle me-2"></i>About
        </a>
        <a class="nav-link" href="history.php">
          <i class="fas fa-history me-2"></i>History
        </a>
        <a class="nav-link" href="programs.php">
          <i class="fas fa-graduation-cap me-2"></i>Programs
        </a>
        <a class="nav-link" href="donation.php">
          <i class="fas fa-hand-holding-heart me-2"></i>Donate
        </a>
        <a class="nav-link" href="volunteer.php">
          <i class="fas fa-hands-helping me-2"></i>Volunteer
        </a>
        <a class="nav-link" href="contact.php">
          <i class="fas fa-envelope me-2"></i>Contact
        </a>
      </div>
      <!-- 3D Action Buttons -->
      <div class="d-flex gap-3">
        <button class="btn-3d" onclick="window.location.href='application.php'">
          <i class="fas fa-rocket me-2"></i>Apply Now
        </button>
        <button class="btn-3d" onclick="window.location.href='organogram.php'">
          <i class="fas fa-user-shield me-2"></i>Staff Login
        </button>
      </div>
    </div>
  </div>
</nav>