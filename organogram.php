<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISNM Organizational Structure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/school-logo.png">
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
        }

        .org-node {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-align: center;
            transition: all 0.3s ease;
            border: 3px solid transparent;
            position: relative;
            min-width: 200px;
        }

        .org-node:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
            border-color: var(--secondary-color);
        }

        .org-node.executive {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .org-node.management {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .org-node.administrative {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .org-node.academic {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .org-node.support {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .org-node.student {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            color: white;
        }

        .org-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .org-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .org-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .org-link {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .org-link:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
            color: white;
        }

        .org-actions {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .org-actions .btn-3d {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            padding: 8px 16px;
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
            width: 100%;
            text-align: center;
        }

        .org-actions .btn-3d::before {
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

        .org-actions .btn-3d:hover {
            transform: translateY(2px);
            box-shadow: 
                0 4px 0 var(--isnm-chocolate),
                0 8px 12px rgba(0,0,0,0.25);
        }

        .org-actions .btn-3d:hover::before {
            opacity: 0.3;
        }

        .org-actions .btn-3d:active {
            transform: translateY(4px);
            box-shadow: 
                0 2px 0 var(--isnm-chocolate),
                0 4px 8px rgba(0,0,0,0.25);
        }

        /* Student Login Buttons - Green Theme */
        .org-level.student .org-actions .btn-3d {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 
                0 3px 0 #155724,
                0 4px 8px rgba(0,0,0,0.15);
        }

        .org-level.student .org-actions .btn-3d::before {
            background: linear-gradient(135deg, #20c997, #17a2b8);
        }

        .org-level.student .org-actions .btn-3d:hover {
            transform: translateY(2px);
            box-shadow: 
                0 4px 0 #155724,
                0 8px 12px rgba(0,0,0,0.25);
        }

        .org-level.student .org-actions .btn-3d:active {
            transform: translateY(4px);
            box-shadow: 
                0 2px 0 #155724,
                0 4px 8px rgba(0,0,0,0.25);
        }

        .org-level {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 30px 0;
            position: relative;
        }

        .org-level::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 50%;
            width: 2px;
            height: 30px;
            background: rgba(255,255,255,0.3);
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
            height: 30px;
            background: rgba(255,255,255,0.3);
        }

        .org-branch:first-child::before {
            left: 50%;
            width: 50%;
        }

        .org-branch:last-child::before {
            left: 0;
            width: 50%;
        }

        .org-branch:not(:first-child):not(:last-child)::before {
            left: 0;
            width: 100%;
        }

        .org-horizontal {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255,255,255,0.3);
        }

        .org-level.executive {
            justify-content: center;
        }

        .org-level.management {
            justify-content: space-around;
        }

        .org-level.administrative {
            justify-content: space-around;
        }

        .org-level.academic {
            justify-content: space-around;
        }

        .org-level.support {
            justify-content: space-around;
        }

        .org-level.student {
            justify-content: space-around;
        }

        @media (max-width: 1200px) {
            .org-level.management,
            .org-level.administrative,
            .org-level.academic,
            .org-level.support {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .org-branch {
                flex: 0 0 45%;
                margin: 10px;
            }
        }

        @media (max-width: 768px) {
            .organogram-container {
                padding: 20px 10px;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }

            .org-node {
                min-width: 140px;
                padding: 12px;
                margin: 8px 4px;
                max-width: 160px;
            }

            .org-icon {
                font-size: 1.8rem;
            }

            .org-title {
                font-size: 0.9rem;
                font-weight: 500;
            }

            .org-subtitle {
                font-size: 0.75rem;
                margin-bottom: 10px;
            }
            
            .org-link {
                padding: 6px 15px;
                font-size: 0.8rem;
            }
            
            .org-actions .btn-3d {
                padding: 6px 12px;
                font-size: 0.7rem;
            }

            .org-level {
                flex-direction: column;
                align-items: center;
                margin: 20px 0;
            }

            .org-branch {
                width: 100%;
                max-width: 180px;
                margin: 5px 0;
            }

            .org-branch::before,
            .org-horizontal {
                display: none;
            }
            
            .org-level.management,
            .org-level.administrative,
            .org-level.academic,
            .org-level.support,
            .org-level.student {
                padding: 0;
            }
        }
        
        @media (max-width: 480px) {
            .organogram-container {
                padding: 15px 5px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .org-node {
                min-width: 120px;
                padding: 10px;
                margin: 6px 2px;
                max-width: 140px;
            }
            
            .org-icon {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }
            
            .org-title {
                font-size: 0.85rem;
                line-height: 1.2;
            }
            
            .org-subtitle {
                font-size: 0.7rem;
                margin-bottom: 8px;
            }
            
            .org-link {
                padding: 5px 12px;
                font-size: 0.75rem;
            }
            
            .org-actions .btn-3d {
                padding: 5px 10px;
                font-size: 0.65rem;
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
            50% {
                box-shadow: 0 8px 35px rgba(103, 126, 234, 0.4);
            }
            100% {
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <?php include 'shared/_header.php'; ?>
    
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
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20General'">
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
                        <i class="fas fa-laptop-code org-icon"></i>
                        <div class="org-title">Director ICT</div>
                        <div class="org-subtitle">Technology Director</div>
                        <a href="staff-login.php?position=Director%20ICT" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Director%20ICT'">
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
            </div>

            <!-- Administrative Staff Level -->
            <div class="org-level administrative">
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-file-alt org-icon"></i>
                        <div class="org-title">Academic Registrar</div>
                        <div class="org-subtitle">Student Records</div>
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
                        <div class="org-title">Lab Technicians</div>
                        <div class="org-subtitle">Laboratory Services</div>
                        <a href="staff-login.php?position=Lab%20Technicians" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <div class="org-actions">
                            <button class="btn-3d" onclick="window.location.href='staff-login.php?position=Lab%20Technicians'">
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

    <?php include 'shared/_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling and hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const nodes = document.querySelectorAll('.org-node');
            
            nodes.forEach(node => {
                node.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                node.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add click tracking
            const loginLinks = document.querySelectorAll('.org-link');
            loginLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const role = this.closest('.org-node').querySelector('.org-title').textContent;
                    console.log('User clicked on:', role);
                });
            });
        });
    </script>
</body>
</html>
