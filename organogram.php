<?php 
$pageTitle = 'ISNM Organizational Structure';
include('shared/_header.php');
?>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #ecf0f1;
            --dark-text: #2c3e50;
            --border-color: #bdc3c7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-text);
        }

        .organogram-container {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .organogram-tree {
            position: relative;
            padding: 20px;
            perspective: 1200px;
        }

        /* ── Universal card size (all cards identical) ── */
        .org-node {
            background: white;
            border-radius: 16px;
            padding: 22px 18px 18px;
            margin: 12px;
            box-shadow:
                0 2px 6px rgba(0,0,0,0.05),
                0 6px 18px rgba(0,0,0,0.09),
                0 14px 36px rgba(0,0,0,0.07);
            text-align: center;
            transition: transform 0.4s cubic-bezier(.25,.8,.25,1), box-shadow 0.4s ease;
            border: 2.5px solid rgba(0,0,0,0.05);
            position: relative;
            width: 238px;
            height: 290px;
            min-height: 290px;
            max-height: 290px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            transform-style: preserve-3d;
            will-change: transform;
        }

        /* Subtle glass overlay */
        .org-node::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255,255,255,0.22) 0%, transparent 40%, rgba(0,0,0,0.03) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .org-node > * {
            position: relative;
            z-index: 2;
        }

        /* Hover lift — steady, no shake */
        .org-node:hover {
            transform: translateY(-5px);
            box-shadow:
                0 6px 14px rgba(0,0,0,0.07),
                0 14px 28px rgba(0,0,0,0.11),
                0 22px 52px rgba(0,0,0,0.09);
            border-color: rgba(255,255,255,0.3);
        }

        /* Shine sweep on hover */
        .card-shine {
            position: absolute;
            top: 0; left: -110%;
            width: 60%; height: 100%;
            background: linear-gradient(105deg, transparent 25%, rgba(255,255,255,0.16) 50%, transparent 75%);
            transform: skewX(-15deg);
            transition: left 0.65s ease;
            pointer-events: none;
            z-index: 3;
        }

        .org-node:hover .card-shine {
            left: 130%;
        }

        /* Button press — real 3D feel */
        .org-node .btn-3d:active {
            transform: translateY(3px);
            box-shadow: 0 1px 2px rgba(0,0,0,0.18) !important;
        }

        /* Icon */
        .org-icon {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.12));
            transition: transform 0.4s cubic-bezier(.25,.8,.25,1);
        }

        .org-node:hover .org-icon {
            transform: scale(1.1) translateY(-2px);
        }

        /* Typography */
        .org-title {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            max-height: 2.4em;
            overflow: hidden;
            letter-spacing: 0.1px;
        }

        .org-subtitle {
            font-size: 0.80rem;
            color: rgba(255,255,255,0.88);
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
            flex-grow: 1;
            margin-bottom: 6px;
            overflow: hidden;
            text-shadow: 0 1px 2px rgba(0,0,0,0.12);
        }

        /* Link pill */
        .org-link {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(255,255,255,0.13);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.80rem;
            transition: all 0.3s ease;
            border: 1.5px solid rgba(255,255,255,0.22);
            margin-bottom: 4px;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .org-link:hover {
            background: rgba(255,255,255,0.26);
            transform: scale(1.03);
            color: white;
            border-color: rgba(255,255,255,0.45);
        }

        /* Actions (button stack) */
        .org-actions {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .org-actions .btn-3d {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            padding: 7px 12px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(155deg, rgba(255,255,255,0.22), rgba(255,255,255,0.07));
            color: white;
            position: relative;
            transform-style: preserve-3d;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            box-shadow:
                0 2px 0 rgba(0,0,0,0.16),
                0 4px 8px rgba(0,0,0,0.10);
            text-transform: uppercase;
            letter-spacing: 0.25px;
            overflow: hidden;
            font-size: 0.68rem;
            width: 100%;
            text-align: center;
            border: 1.5px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        /* Frosted inner shine */
        .org-actions .btn-3d::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 60%);
            border-radius: 14px;
            pointer-events: none;
        }

        .org-actions .btn-3d:hover {
            transform: translateY(-2px);
            box-shadow:
                0 4px 0 rgba(0,0,0,0.18),
                0 8px 16px rgba(0,0,0,0.14);
            border-color: rgba(255,255,255,0.38);
        }

        /* Color themes — identical layout, different hues */
        .org-node.executive {
            background: linear-gradient(160deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .org-node.management {
            background: linear-gradient(160deg, #c471f5 0%, #b03070 100%);
            color: white;
        }
        .org-node.administrative {
            background: linear-gradient(160deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        .org-node.academic {
            background: linear-gradient(160deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }
        .org-node.support {
            background: linear-gradient(160deg, #f673a8 0%, #d4a017 100%);
            color: white;
        }
        .org-node.student {
            background: linear-gradient(160deg, #30cfd0 0%, #330867 100%);
            color: white;
        }
        .org-node.ict {
            background: linear-gradient(160deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: 2.5px solid rgba(144,224,239,0.45);
        }

        .btn-ict-primary {
            background: linear-gradient(155deg, #0077b6 0%, #005f92 100%) !important;
            box-shadow: 0 2px 0 #003d66, 0 4px 8px rgba(0,0,0,0.14) !important;
        }
        .btn-ict-primary:hover {
            box-shadow: 0 4px 0 #003d66, 0 8px 16px rgba(0,0,0,0.16) !important;
        }

        .btn-ict-secondary {
            background: linear-gradient(155deg, #00b4d8 0%, #0288d1 100%) !important;
            box-shadow: 0 2px 0 #01579b, 0 4px 8px rgba(0,0,0,0.14) !important;
        }
        .btn-ict-secondary:hover {
            box-shadow: 0 4px 0 #01579b, 0 8px 16px rgba(0,0,0,0.16) !important;
        }

        /* Gentle float — very subtle, no shaking */
        .floating {
            animation: subtleFloat 6s ease-in-out infinite;
        }

        /* Stagger floating so cards don't move in sync (prevents any perceived shaking) */
        .org-level .org-branch:nth-child(1) .floating { animation-delay: 0s; }
        .org-level .org-branch:nth-child(2) .floating { animation-delay: -1.2s; }
        .org-level .org-branch:nth-child(3) .floating { animation-delay: -2.4s; }
        .org-level .org-branch:nth-child(4) .floating { animation-delay: -3.6s; }
        .org-level .org-branch:nth-child(5) .floating { animation-delay: -4.8s; }
        .org-level .org-branch:nth-child(6) .floating { animation-delay: -6s; }
        .org-level .org-branch:nth-child(7) .floating { animation-delay: -0.8s; }
        .org-level .org-branch:nth-child(8) .floating { animation-delay: -2.0s; }

        @keyframes subtleFloat {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-2px); }
        }

        .org-level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 28px 0;
            position: relative;
            gap: 18px;
            flex-wrap: wrap;
        }

        .org-level::before {
            content: '';
            position: absolute;
            top: -28px;
            left: 50%;
            width: 2px;
            height: 28px;
            background: rgba(255,255,255,0.28);
        }

        .org-branch {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .org-branch::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 28px;
            background: rgba(255,255,255,0.28);
        }

        .org-branch:first-child::before  { left: 50%;  width: 50%; }
        .org-branch:last-child::before   { left: 0;    width: 50%; }
        .org-branch:not(:first-child):not(:last-child)::before { left: 0; width: 100%; }

        .org-horizontal {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: rgba(255,255,255,0.28);
        }

        @media (max-width: 1200px) {
            .org-level {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            .org-branch { width: 100%; max-width: none; margin: 0; }
            .org-branch::before,
            .org-horizontal { display: none; }
        }

        @media (max-width: 768px) {
            .organogram-container { padding: 20px 10px; }
            .page-header h1 { font-size: 1.8rem; }
            .page-header p { font-size: 1rem; }
            .org-node {
                width: 175px;
                height: 260px;
                min-height: 260px;
                max-height: 260px;
                padding: 14px 10px;
                margin: 8px;
            }
            .org-icon { font-size: 1.7rem; }
            .org-title { font-size: 0.82rem; }
            .org-subtitle { font-size: 0.72rem; }
            .org-link { padding: 5px 12px; font-size: 0.75rem; }
            .org-actions .btn-3d { padding: 5px 10px; font-size: 0.66rem; }
            .org-level {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
                margin: 18px 0;
            }
            .org-branch { width: 100%; max-width: none; margin: 0; }
            .org-branch::before,
            .org-horizontal { display: none; }
        }

        @media (max-width: 480px) {
            .organogram-container { padding: 12px 4px; }
            .page-header h1 { font-size: 1.4rem; }
            .org-level {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
            }
            .org-node {
                width: 130px;
                height: 230px;
                min-height: 230px;
                max-height: 230px;
                padding: 10px 6px;
                margin: 5px;
            }
            .org-icon { font-size: 1.4rem; }
            .org-title { font-size: 0.78rem; }
            .org-subtitle { font-size: 0.68rem; }
            .org-link { padding: 4px 10px; font-size: 0.70rem; }
            .org-actions .btn-3d { padding: 4px 8px; font-size: 0.62rem; }
        }

        /* ── School header ── */
        .school-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 28px 24px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            margin-bottom: 28px;
            box-shadow: 0 6px 28px rgba(0,0,0,0.08);
        }

        .school-logo {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 3px 12px rgba(0,0,0,0.18);
        }

        .school-info {
            text-align: center;
            margin: 0 18px;
            color: white;
        }

        .school-info h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 4px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.25);
        }

        .school-info p {
            font-size: 0.95rem;
            font-style: italic;
            opacity: 0.9;
            margin: 0;
        }

        @media (max-width: 768px) {
            .school-header { flex-direction: column; text-align: center; }
            .school-info { margin: 14px 0; }
            .school-logo { width: 58px; height: 58px; }
            .school-info h2 { font-size: 1.4rem; }
        }

        @media (max-width: 480px) {
            .school-logo { width: 48px; height: 48px; }
            .school-info h2 { font-size: 1.1rem; }
            .school-info p { font-size: 0.85rem; }
        }
    </style>

    <main>
        <div class="school-header">
            <div class="logo-container">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            </div>
            <div class="school-info">
                <h2>Iganga School of Nursing and Midwifery</h2>
                <p>"Chosen to Serve - Based on a disciplined mind for health action"</p>
            </div>
            <div class="logo-container">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            </div>
        </div>
        
        <div class="organogram-container">
            <div class="page-header">
                <h1><i class="fas fa-sitemap"></i> ISNM Organizational Structure</h1>
                <p>Click on your position to access your personalized dashboard</p>
            </div>

        <div class="organogram-tree">
            <!-- Executive Leadership Level -->
            <div class="org-level executive">
                <div class="org-branch">
                    <div class="org-node executive pulse-animation">
                        <i class="fas fa-crown org-icon"></i>
                        <div class="org-title">Director General</div>
                        <div class="org-subtitle">Overall Institution Leadership</div>
                        <a href="staff-login.php?position=Director%20General" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button type="button" class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20General'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Level -->
            <div class="org-level management">
                <div class="org-branch">
                    <div class="org-node management floating">
                        <i class="fas fa-user-tie org-icon"></i>
                        <div class="org-title">Chief Executive Officer</div>
                        <div class="org-subtitle">Executive Leadership</div>
                        <a href="staff-login.php?position=Chief%20Executive%20Officer" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Chief%20Executive%20Officer'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node management floating">
                        <i class="fas fa-graduation-cap org-icon"></i>
                        <div class="org-title">Director Academics</div>
                        <div class="org-subtitle">Academic Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Academics" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20Academics'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node management floating">
                        <i class="fas fa-coins org-icon"></i>
                        <div class="org-title">Director Finance</div>
                        <div class="org-subtitle">Financial Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Finance" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20Finance'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

             <!-- School Management Level -->
             <div class="org-level administrative">
                 <div class="org-branch">
                     <div class="org-node administrative">
                         <i class="fas fa-chalkboard-teacher org-icon"></i>
                         <div class="org-title">School Principal</div>
                         <div class="org-subtitle">Chief Academic Officer</div>
                         <a href="staff-login.php?position=School%20Principal" class="org-link">
                             <i class="fas fa-sign-in-alt"></i> Login
                         </a>
                         <div class="org-actions">
                             <button class="btn-3d" onclick="window.location.href='staff-login.php?position=School%20Principal'">
                                 <i class="fas fa-user-shield me-2"></i>Staff Login
                             </button>
                         </div>
                     </div>
                  </div>
                  <div class="org-branch">
                      <div class="org-node administrative">
                          <i class="fas fa-user-graduate org-icon"></i>
                          <div class="org-title">Deputy Principal</div>
                          <div class="org-subtitle">Assistant Academic Officer</div>
                          <a href="staff-login.php?position=Deputy%20Principal" class="org-link">
                              <i class="fas fa-sign-in-alt"></i> Login
                          </a>
                          <div class="org-actions">
                              <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Deputy%20Principal'">
                                  <i class="fas fa-user-shield me-2"></i>Staff Login
                              </button>
                          </div>
                      </div>
                  </div>
                  <div class="org-branch">
                      <div class="org-node administrative">
                          <i class="fas fa-money-check-alt org-icon"></i>
                          <div class="org-title">School Bursar</div>
                          <div class="org-subtitle">Chief Financial Officer</div>
                          <a href="staff-login.php?position=School%20Bursar" class="org-link">
                              <i class="fas fa-sign-in-alt"></i> Login
                          </a>
                          <div class="org-actions">
                              <button class="btn-3d" onclick="window.location.href='staff-login.php?position=School%20Bursar'">
                                  <i class="fas fa-user-shield me-2"></i>Staff Login
                              </button>
                          </div>
                      </div>
                  </div>
                  <div class="org-branch">
                      <div class="org-node administrative">
                          <i class="fas fa-user-check org-icon"></i>
                          <div class="org-title">Director Admissions & Requirements</div>
                          <div class="org-subtitle">Admissions & Requirements Clearance</div>
                          <a href="staff-login.php?position=Director%20Admissions%20%26%20Requirements" class="org-link">
                              <i class="fas fa-sign-in-alt"></i> Login
                          </a>
                          <div class="org-actions">
                              <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20Admissions%20%26%20Requirements'">
                                  <i class="fas fa-user-shield me-2"></i>Staff Login
                              </button>
                          </div>
                      </div>
                  </div>
              </div>

            <!-- Administrative Staff Level -->
            <div class="org-level administrative">
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-file-alt org-icon"></i>
                        <div class="org-title">Academic Registrar</div>
                        <div class="org-subtitle">Mr. Gejje William</div>
                        <div class="org-description">Student Records</div>
                        <a href="staff-login.php?position=Academic%20Registrar" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Academic%20Registrar'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-users org-icon"></i>
                        <div class="org-title">HR Manager</div>
                        <div class="org-subtitle">Human Resources</div>
                        <a href="staff-login.php?position=HR%20Manager" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=HR%20Manager'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-envelope org-icon"></i>
                        <div class="org-title">School Secretary</div>
                        <div class="org-subtitle">Administrative Support</div>
                        <a href="staff-login.php?position=School%20Secretary" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=School%20Secretary'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-book org-icon"></i>
                        <div class="org-title">School Librarian</div>
                        <div class="org-subtitle">Library Management</div>
                        <a href="staff-login.php?position=School%20Librarian" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=School%20Librarian'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Staff Level -->
            <div class="org-level academic">
                <div class="org-branch">
                    <div class="org-node academic">
                        <i class="fas fa-heartbeat org-icon"></i>
                        <div class="org-title">Head of Nursing</div>
                        <div class="org-subtitle">Nursing Department</div>
                        <a href="staff-login.php?position=Head%20of%20Nursing" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Head%20of%20Nursing'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node academic">
                        <i class="fas fa-baby org-icon"></i>
                        <div class="org-title">Head of Midwifery</div>
                        <div class="org-subtitle">Midwifery Department</div>
                        <a href="staff-login.php?position=Head%20of%20Midwifery" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Head%20of%20Midwifery'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node academic">
                        <i class="fas fa-chalkboard org-icon"></i>
                        <div class="org-title">Senior Lecturers</div>
                        <div class="org-subtitle">Advanced Teaching</div>
                        <a href="staff-login.php?position=Senior%20Lecturers" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Senior%20Lecturers'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node academic">
                        <i class="fas fa-book-reader org-icon"></i>
                        <div class="org-title">Lecturers</div>
                        <div class="org-subtitle">Classroom Teaching</div>
                        <a href="staff-login.php?position=Lecturers" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Lecturers'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Staff Level -->
            <div class="org-level support">
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-hands-helping org-icon"></i>
                        <div class="org-title">Matrons</div>
                        <div class="org-subtitle">Student Welfare</div>
                        <a href="staff-login.php?position=Matrons" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Matrons'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-shield-alt org-icon"></i>
                        <div class="org-title">Wardens</div>
                        <div class="org-subtitle">Student Care & Support</div>
                        <a href="staff-login.php?position=Wardens" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Wardens'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-flask org-icon"></i>
                        <div class="org-title">Sickbay</div>
                        <div class="org-subtitle">Student Health Support</div>
                        <a href="staff-login.php?position=Sickbay" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Sickbay'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-bus org-icon"></i>
                        <div class="org-title">Drivers</div>
                        <div class="org-subtitle">Transport Services</div>
                        <a href="staff-login.php?position=Drivers" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Drivers'">
                                <i class="fas fa-user-shield me-2"></i>Staff Login
                            </button>
                        </div>
                    </div>
                </div>
                 <div class="org-branch">
                     <div class="org-node support">
                         <i class="fas fa-user-shield org-icon"></i>
                         <div class="org-title">Security</div>
                         <div class="org-subtitle">Campus Security</div>
                         <a href="staff-login.php?position=Security" class="org-link">
                             <i class="fas fa-sign-in-alt"></i> Login
                         </a>
                         <div class="org-actions">
                             <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Security'">
                                 <i class="fas fa-user-shield me-2"></i>Staff Login
                             </button>
                         </div>
                     </div>
                 </div>
                 <div class="org-branch">
                     <div class="org-node support">
                         <i class="fas fa-warehouse org-icon"></i>
                         <div class="org-title">Store Keeper</div>
                         <div class="org-subtitle">Inventory Management</div>
                         <a href="staff-login.php?position=Store%20Keeper" class="org-link">
                             <i class="fas fa-sign-in-alt"></i> Login
                         </a>
                         <div class="org-actions">
                             <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Store%20Keeper'">
                                 <i class="fas fa-user-shield me-2"></i>Staff Login
                             </button>
                         </div>
                     </div>
                 </div>
             </div>

              <!-- ICT Department - Independent Authority Section -->
              <div class="page-header" style="margin-top: 20px; margin-bottom: 15px;">
                  <h2 style="font-size: 1.5rem; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                      <i class="fas fa-laptop-code"></i> ICT Department
                  </h2>
                  <p style="opacity: 0.85; font-size: 0.95rem;">Computer Lab & Technology Services</p>
              </div>

              <div class="org-level ict-level" style="background: linear-gradient(145deg, rgba(0,119,182,0.3) 0%, rgba(0,95,146,0.25) 100%); border-radius: 22px; padding: 28px 24px; margin: 15px 0; box-shadow: inset 0 1px 0 rgba(255,255,255,0.1), 0 8px 28px rgba(0,0,0,0.18); border: 1.5px solid rgba(144,224,239,0.2);">
                  <div class="org-branch">
                      <div class="org-node ict floating">
                          <div class="card-shine"></div>
                          <i class="fas fa-user-tie org-icon"></i>
                          <div class="org-title">Director ICT</div>
                          <div class="org-subtitle">Head of ICT Department — Oversight & Management</div>
                          <a href="staff-login.php?position=Director%20ICT" class="org-link" onclick="event.preventDefault(); window.location.replace('staff-login.php?position=Director%20ICT');">
                              <i class="fas fa-sign-in-alt"></i> Login
                          </a>
                          <div class="org-actions">
                              <button class="btn-3d btn-ict-primary" onclick="window.location.replace('staff-login.php?position=Director%20ICT')">
                                  <i class="fas fa-user-shield me-2"></i>Staff Login
                              </button>
                          </div>
                      </div>
                  </div>
                  <div class="org-branch">
                      <div class="org-node ict floating">
                          <div class="card-shine"></div>
                          <i class="fas fa-desktop org-icon"></i>
                          <div class="org-title">Computer Lab Manager</div>
                          <div class="org-subtitle">ICT Operations — Lab Management & Support</div>
                          <a href="staff-login.php?position=Computer%20Lab%20Manager" class="org-link">
                              <i class="fas fa-sign-in-alt"></i> Login
                          </a>
                          <div class="org-actions">
                              <button class="btn-3d btn-ict-primary" onclick="window.location.href='staff-login.php?position=Computer%20Lab%20Manager'">
                                  <i class="fas fa-user-shield me-2"></i>Staff Login
                              </button>
                              <button class="btn-3d btn-ict-secondary" onclick="window.location.href='computer_lab.php'">
                                  <i class="fas fa-desktop me-2"></i>Lab Dashboard
                              </button>
                          </div>
                      </div>
                  </div>
              </div>

            <!-- Student Leadership Level -->
            <div class="org-level student">
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-users org-icon"></i>
                        <div class="org-title">Students</div>
                        <div class="org-subtitle">All Student Access</div>
                        <a href="student-login.php?student_role=Students" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='student-login.php?student_role=Students'">
                                <i class="fas fa-graduation-cap me-2"></i>Student Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-crown org-icon"></i>
                        <div class="org-title">Guild President</div>
                        <div class="org-subtitle">Student Leadership</div>
                        <a href="student-login.php?student_role=Guild%20President" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='student-login.php?student_role=Guild%20President'">
                                <i class="fas fa-graduation-cap me-2"></i>Student Login
                            </button>
                        </div>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-user-tie org-icon"></i>
                        <div class="org-title">Class Representatives</div>
                        <div class="org-subtitle">Class Leadership</div>
                        <a href="student-login.php?student_role=Class%20Representatives" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='student-login.php?student_role=Class%20Representatives'">
                                <i class="fas fa-graduation-cap me-2"></i>Student Login
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

            <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.org-node').forEach(function(node) {
                node.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                node.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
        });
    </script>
</main>
<?php include("shared/_footer.php"); ?>
