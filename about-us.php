<?php include('shared/_header.php');?>

<body>
    <main>
        <!-- Perfect Mobile Hero Header -->
        <section class="hero-header">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title animate-fade-in">About ISNM</h1>
                        <p class="hero-subtitle animate-slide-up">Iganga School of Nursing and Midwifery</p>
                        <div class="hero-decoration animate-scale-in"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Content Section -->
        <section class="about-content-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-md-12">
                        <div class="about-card animate-fade-in">
                            <div class="card-header text-center">
                                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo mb-4" style="height: 120px; width: auto; border-radius: 50%; border: 4px solid var(--accent-color);">
                                <h2 class="about-title">
                                    <i class="fas fa-graduation-cap me-2 text-primary"></i>    
                                    About ISNM
                                </h2>
                            </div>
                            <div class="card-body">
                                <div class="about-content">
                                    <p class="about-text">
                                        Iganga School of Nursing and Midwifery (ISNM) is a premier healthcare education institution located in Eastern Uganda. We are committed to providing quality nursing and midwifery education that prepares competent healthcare professionals to serve communities across Uganda and beyond.
                                    </p>
                                    
                                    <div class="mission-vision-section">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12 mb-4">
                                                <div class="mission-card">
                                                    <div class="card-icon">
                                                        <i class="fas fa-bullseye"></i>
                                                    </div>
                                                    <h4>Our Mission</h4>
                                                    <p>To provide quality nursing and midwifery education that produces competent, compassionate, and ethical healthcare professionals who can meet the healthcare needs of Uganda and the global community.</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12 mb-4">
                                                <div class="vision-card">
                                                    <div class="card-icon">
                                                        <i class="fas fa-eye"></i>
                                                    </div>
                                                    <h4>Our Vision</h4>
                                                    <p>To be a leading center of excellence in nursing and midwifery education, recognized for producing highly skilled healthcare professionals who make a positive impact on global health.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="values-section">
                                        <h3 class="section-title text-center mb-4">
                                            <i class="fas fa-heart me-2"></i>Our Core Values
                                        </h3>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                                <div class="value-item">
                                                    <i class="fas fa-handshake"></i>
                                                    <h5>Integrity</h5>
                                                    <p>Upholding ethical standards in all our practices</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                                <div class="value-item">
                                                    <i class="fas fa-lightbulb"></i>
                                                    <h5>Excellence</h5>
                                                    <p>Striving for the highest quality in education</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                                <div class="value-item">
                                                    <i class="fas fa-users"></i>
                                                    <h5>Compassion</h5>
                                                    <p>Caring for others with empathy and kindness</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                                <div class="value-item">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <h5>Professionalism</h5>
                                                    <p>Maintaining high standards in healthcare</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <style>
        :root {
            --primary-color: #3E2723;
            --secondary-color: #1A237E;
            --accent-color: #FFD700;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            -webkit-box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .hero-header {
            position: relative;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0 60px;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(62, 39, 35, 0.8), rgba(26, 35, 126, 0.6));
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .about-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
        }

        .about-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }

        .about-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
            margin-bottom: 3rem;
            text-align: justify;
        }

        .mission-card, .vision-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .card-icon {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .mission-card h4, .vision-card h4 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .value-item {
            text-align: center;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            height: 100%;
        }

        .value-item i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .value-item h5 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .value-item p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .hero-header {
                padding: 60px 0 40px;
            }

            .hero-title {
                font-size: 2rem !important;
            }

            .hero-subtitle {
                font-size: 1rem !important;
            }

            .about-card {
                padding: 25px;
            }

            .about-title {
                font-size: 2rem !important;
            }

            .about-text {
                font-size: 1rem !important;
            }

            .school-logo {
                height: 100px !important;
            }
        }

        @media (max-width: 480px) {
            .hero-header {
                padding: 40px 0 30px;
            }

            .hero-title {
                font-size: 1.5rem !important;
            }

            .hero-subtitle {
                font-size: 0.9rem !important;
            }

            .about-card {
                padding: 20px;
            }

            .about-title {
                font-size: 1.5rem !important;
            }

            .about-text {
                font-size: 0.9rem !important;
            }

            .school-logo {
                height: 80px !important;
            }

            .mission-card, .vision-card {
                padding: 20px;
            }

            .value-item {
                padding: 15px;
            }
        }
    </style>

    <?php include('shared/_footer.php'); ?>