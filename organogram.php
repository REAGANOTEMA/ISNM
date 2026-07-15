<?php 
$pageTitle = 'ISNM Organizational Structure';
include('shared/_header.php');
?>
    <style>
        :root {
            --executive: #667eea;
            --management: #c471f5;
            --administrative: #4facfe;
            --academic: #43e97b;
            --support: #f673a8;
            --student: #30cfd0;
            --ict: #00b4d8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #070b15 0%, #0a1226 25%, #0f1a2e 50%, #0a1628 75%, #070b15 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .ambient-orbs { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .ambient-orb {
            position: absolute; border-radius: 50%; filter: blur(100px);
            animation: orbDrift 20s ease-in-out infinite alternate;
        }
        .ambient-orb:nth-child(1) {
            top: -10%; left: -5%; width: 450px; height: 450px;
            background: rgba(102,126,234,0.08);
        }
        .ambient-orb:nth-child(2) {
            bottom: -10%; right: -5%; width: 500px; height: 500px;
            background: rgba(196,113,245,0.07);
            animation-delay: -7s;
        }
        .ambient-orb:nth-child(3) {
            top: 50%; left: 50%; width: 600px; height: 600px;
            background: rgba(79,172,254,0.05);
            transform: translate(-50%, -50%);
            animation-delay: -14s;
        }

        @keyframes orbDrift {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -20px) scale(1.1); }
        }

        .stars-container {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            overflow: hidden;
        }
        .star {
            position: absolute; width: 2px; height: 2px;
            background: #fff; border-radius: 50%;
            animation: starTwinkle var(--duration) ease-in-out infinite alternate;
        }
        .star:nth-child(odd) { width: 1.5px; height: 1.5px; }
        .star:nth-child(3n) { width: 2.5px; height: 2.5px; }

        @keyframes starTwinkle {
            0% { opacity: 0.2; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.2); }
        }

        .organogram-container {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }

        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #a8b8ff 40%, #c471f5 70%, #4facfe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 20px rgba(102,126,234,0.15));
        }

        .page-header p {
            font-size: 1rem;
            opacity: 0.8;
            color: rgba(255,255,255,0.75);
        }

        .organogram-tree {
            position: relative;
            padding: 20px;
            perspective: 1200px;
        }

        /* ── School header (premium 3D) ── */
        .school-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px 28px;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            margin-bottom: 32px;
            border: 1px solid rgba(255,255,255,0.06);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            perspective: 800px;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.25),
                0 2px 8px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.06);
        }

        .school-header::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 22px;
            background: conic-gradient(
                from 0deg at 50% 50%,
                transparent 0deg,
                rgba(102,126,234,0.15) 60deg,
                transparent 120deg,
                rgba(196,113,245,0.12) 180deg,
                transparent 240deg,
                rgba(79,172,254,0.15) 300deg,
                transparent 360deg
            );
            z-index: -2;
            animation: borderSpin 8s linear infinite;
            pointer-events: none;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            -webkit-mask-composite: xor;
            padding: 2px;
        }

        .school-header::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 21px;
            background: linear-gradient(135deg, rgba(102,126,234,0.08), transparent 40%, rgba(196,113,245,0.06));
            z-index: -1;
            pointer-events: none;
        }

        @keyframes borderSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .logo-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container::after {
            content: '';
            position: absolute;
            width: 92px; height: 92px;
            border-radius: 50%;
            border: 1.5px solid rgba(102,126,234,0.12);
            animation: logoRingPulse 3s ease-in-out infinite;
            pointer-events: none;
        }

        .logo-container:first-child::after { animation-delay: 0s; }
        .logo-container:last-child::after { animation-delay: 1.5s; }

        @keyframes logoRingPulse {
            0%, 100% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.08); opacity: 1; }
        }

        .school-logo {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.12);
            box-shadow: 0 4px 24px rgba(0,0,0,0.35), 0 0 40px rgba(102,126,234,0.06);
            position: relative;
            z-index: 1;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .school-header:hover .school-logo {
            transform: scale(1.04);
            box-shadow: 0 6px 30px rgba(0,0,0,0.4), 0 0 60px rgba(102,126,234,0.1);
        }

        .school-info {
            text-align: center;
            margin: 0 22px;
            color: white;
            position: relative;
        }

        .school-info::before,
        .school-info::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06));
        }

        .school-info::before { right: calc(100% + 8px); }
        .school-info::after { left: calc(100% + 8px); background: linear-gradient(90deg, rgba(255,255,255,0.06), transparent); }

        .school-info h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0 0 6px;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #c8d0ff 40%, #a88bff 70%, #c471f5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 20px rgba(102,126,234,0.15));
            animation: headerTitleGlow 4s ease-in-out infinite alternate;
        }

        @keyframes headerTitleGlow {
            0% { filter: drop-shadow(0 0 15px rgba(102,126,234,0.1)); }
            100% { filter: drop-shadow(0 0 30px rgba(102,126,234,0.2)) drop-shadow(0 0 60px rgba(196,113,245,0.08)); }
        }

        .school-info h2 .title-accent {
            display: inline-block;
            animation: titleAccentPulse 3s ease-in-out infinite;
        }

        @keyframes titleAccentPulse {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        .school-info p {
            font-size: 0.95rem;
            font-style: italic;
            opacity: 0.65;
            margin: 0;
            letter-spacing: 0.3px;
            color: rgba(255,255,255,0.75);
        }

        .header-deco {
            position: absolute;
            width: 4px; height: 4px;
            border-radius: 50%;
            background: rgba(102,126,234,0.15);
            pointer-events: none;
            animation: decoFloat 6s ease-in-out infinite;
        }

        .header-deco:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; }
        .header-deco:nth-child(2) { top: 15%; right: 8%; animation-delay: -2s; }
        .header-deco:nth-child(3) { bottom: 20%; left: 10%; animation-delay: -4s; }
        .header-deco:nth-child(4) { bottom: 12%; right: 5%; animation-delay: -1s; }

        @keyframes decoFloat {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.2; }
            50% { transform: translateY(-6px) scale(1.3); opacity: 0.6; }
        }

        /* ── Universal card ── */
        .org-node {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 16px;
            padding: 22px 18px 18px;
            margin: 12px;
            border: 1.5px solid rgba(255,255,255,0.06);
            text-align: center;
            position: relative;
            width: 238px;
            height: 240px;
            min-height: 240px;
            max-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            transform-style: preserve-3d;
            will-change: transform;
            transition: transform 0.45s cubic-bezier(.25,.8,.25,1), box-shadow 0.45s ease, border-color 0.3s ease;
            box-shadow:
                0 4px 16px rgba(0,0,0,0.25),
                0 8px 32px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.06);
        }

        .org-node::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255,255,255,0.06) 0%, transparent 45%, rgba(0,0,0,0.04) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .org-node > * { position: relative; z-index: 2; }

        .org-node:hover {
            transform: translateY(-6px);
            border-color: rgba(255,255,255,0.15);
            box-shadow:
                0 8px 32px rgba(0,0,0,0.35),
                0 16px 48px rgba(0,0,0,0.25),
                0 0 40px var(--card-glow),
                inset 0 1px 0 rgba(255,255,255,0.1);
        }

        /* Category neon borders + glow */
        .org-node.executive { border-color: rgba(102,126,234,0.2); --card-glow: rgba(102,126,234,0.12); }
        .org-node.executive:hover { border-color: rgba(102,126,234,0.4); }
        .org-node.management { border-color: rgba(196,113,245,0.2); --card-glow: rgba(196,113,245,0.12); }
        .org-node.management:hover { border-color: rgba(196,113,245,0.4); }
        .org-node.administrative { border-color: rgba(79,172,254,0.2); --card-glow: rgba(79,172,254,0.12); }
        .org-node.administrative:hover { border-color: rgba(79,172,254,0.4); }
        .org-node.academic { border-color: rgba(67,233,123,0.2); --card-glow: rgba(67,233,123,0.12); }
        .org-node.academic:hover { border-color: rgba(67,233,123,0.4); }
        .org-node.support { border-color: rgba(246,115,168,0.2); --card-glow: rgba(246,115,168,0.12); }
        .org-node.support:hover { border-color: rgba(246,115,168,0.4); }
        .org-node.student { border-color: rgba(48,207,208,0.2); --card-glow: rgba(48,207,208,0.12); }
        .org-node.student:hover { border-color: rgba(48,207,208,0.4); }

        /* Shine sweep on hover */
        .card-shine {
            position: absolute;
            top: 0; left: -110%;
            width: 60%; height: 100%;
            background: linear-gradient(105deg, transparent 25%, rgba(255,255,255,0.08) 50%, transparent 75%);
            transform: skewX(-15deg);
            transition: left 0.65s ease;
            pointer-events: none;
            z-index: 3;
        }
        .org-node:hover .card-shine { left: 130%; }

        /* Icon */
        .org-icon {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            transition: transform 0.45s cubic-bezier(.25,.8,.25,1);
        }

        .org-node:hover .org-icon { transform: scale(1.12) translateY(-3px); }

        .org-node.executive .org-icon { color: #667eea; }
        .org-node.management .org-icon { color: #c471f5; text-shadow: 0 0 15px rgba(196,113,245,0.3); }
        .org-node.administrative .org-icon { color: #4facfe; }
        .org-node.academic .org-icon { color: #43e97b; }
        .org-node.support .org-icon { color: #f673a8; }
        .org-node.student .org-icon { color: #30cfd0; }

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
            color: #fff;
        }

        .org-subtitle {
            font-size: 0.80rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
            flex-grow: 1;
            margin-bottom: 6px;
            overflow: hidden;
        }

        /* Link pill — only login element */
        .org-link {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.80rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 4px;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .org-link:hover {
            background: rgba(255,255,255,0.12);
            transform: scale(1.05);
            color: #fff;
            border-color: rgba(255,255,255,0.25);
        }

        /* Level + connectors (neon) */
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
            background: linear-gradient(180deg, transparent, rgba(255,255,255,0.08));
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
            background: linear-gradient(180deg, transparent, rgba(255,255,255,0.06));
        }

        .org-branch:first-child::before  { left: 50%;  width: 50%; }
        .org-branch:last-child::before   { left: 0;    width: 50%; }
        .org-branch:not(:first-child):not(:last-child)::before { left: 0; width: 100%; }

        .org-horizontal {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
        }

        /* Floating levitation */
        .floating { animation: subtleFloat 6s ease-in-out infinite; }
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
            50%      { transform: translateY(-3px); }
        }

        /* Level label */
        .level-label {
            width: 100%;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.45;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.5);
        }

        /* ── Responsive ── */
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
            .page-header h1 { font-size: 1.6rem; }
            .page-header p { font-size: 0.9rem; }
            .org-node {
                width: 100%;
                height: auto;
                min-height: 200px;
                max-height: none;
                padding: 14px 12px;
                margin: 8px 0;
            }
            .org-icon { font-size: 1.7rem; }
            .org-title { font-size: 0.85rem; max-height: none; }
            .org-subtitle { font-size: 0.75rem; }
            .org-link { padding: 6px 14px; font-size: 0.78rem; }
            .org-level {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                margin: 18px 0;
            }
            .org-branch { width: 100%; max-width: none; margin: 0; }
            .org-branch::before,
            .org-horizontal { display: none; }
            .school-header { flex-direction: column; text-align: center; padding: 24px 18px; }
            .school-info { margin: 14px 0; }
            .school-info::before,
            .school-info::after { display: none; }
            .school-logo { width: 58px; height: 58px; }
            .school-info h2 { font-size: 1.3rem; }
            .header-deco { display: none; }
            .level-label { font-size: 0.65rem; letter-spacing: 1.5px; }
        }

        @media (max-width: 480px) {
            .organogram-container { padding: 12px 4px; }
            .page-header h1 { font-size: 1.2rem; }
            .org-node {
                padding: 12px 8px;
                margin: 6px 0;
                min-height: 180px;
                max-height: none;
            }
            .org-icon { font-size: 1.4rem; }
            .org-title { font-size: 0.8rem; }
            .org-subtitle { font-size: 0.7rem; }
            .org-link { padding: 5px 12px; font-size: 0.72rem; }
            .school-logo { width: 48px; height: 48px; }
            .school-info h2 { font-size: 1.1rem; }
            .school-info p { font-size: 0.85rem; }
        }
    </style>

    <main>
        <div class="ambient-orbs">
            <div class="ambient-orb"></div>
            <div class="ambient-orb"></div>
            <div class="ambient-orb"></div>
        </div>
        <div class="stars-container" id="stars"></div>
        <div class="school-header">
            <div class="header-deco"></div>
            <div class="header-deco"></div>
            <div class="header-deco"></div>
            <div class="header-deco"></div>
            <div class="logo-container">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            </div>
            <div class="school-info">
                <h2>Iganga School of Nursing and Midwifery</h2>
                <p>"Chosen to Serve , Based on a disciplined mind for health action"</p>
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

            <!-- ═══ EXECUTIVE ═══ -->
            <div class="level-label">Executive</div>
            <div class="org-level executive">
                <div class="org-branch">
                    <div class="org-node executive pulse-animation">
                        <i class="fas fa-crown org-icon"></i>
                        <div class="org-title">Director General</div>
                        <div class="org-subtitle">Overall Institution Leadership</div>
                        <a href="staff-login.php?position=Director%20General" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ MANAGEMENT ═══ -->
            <div class="level-label">Management</div>
            <div class="org-level management">
                <div class="org-branch">
                    <div class="org-node management floating">
                        <i class="fas fa-user-tie org-icon"></i>
                        <div class="org-title">Chief Executive Officer</div>
                        <div class="org-subtitle">Executive Leadership</div>
                        <a href="staff-login.php?position=Chief%20Executive%20Officer" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node management floating">
                        <i class="fas fa-laptop-code org-icon"></i>
                        <div class="org-title">Director ICT</div>
                        <div class="org-subtitle">ICT Department Oversight &amp; Management</div>
                        <a href="staff-login.php?position=Director%20ICT" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ SCHOOL ADMINISTRATION ═══ -->
            <div class="level-label">School Administration</div>
            <div class="org-level administrative">
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-chalkboard-teacher org-icon"></i>
                        <div class="org-title">School Principal</div>
                        <div class="org-subtitle">Chief Academic Officer</div>
                        <a href="staff-login.php?position=School%20Principal" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-user-check org-icon"></i>
                        <div class="org-title">Director Admissions &amp; Requirements</div>
                        <div class="org-subtitle">Admissions &amp; Requirements Clearance</div>
                        <a href="staff-login.php?position=Director%20Admissions%20%26%20Requirements" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ ADMINISTRATIVE STAFF ═══ -->
            <div class="level-label">Administrative Staff</div>
            <div class="org-level administrative">
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-file-alt org-icon"></i>
                        <div class="org-title">Academic Registrar</div>
                        <div class="org-subtitle">Student Records</div>
                        <a href="staff-login.php?position=Academic%20Registrar" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-calendar-alt org-icon"></i>
                        <div class="org-title">Events Coordinator</div>
                        <div class="org-subtitle">Event Planning &amp; Management</div>
                        <a href="staff-login.php?position=Events%20Coordinator" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node administrative">
                        <i class="fas fa-user-graduate org-icon"></i>
                        <div class="org-title">Alumni Relations Officer</div>
                        <div class="org-subtitle">Alumni Engagement &amp; Records</div>
                        <a href="staff-login.php?position=Alumni%20Relations%20Officer" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ ACADEMIC STAFF ═══ -->
            <div class="level-label">Academic Staff</div>
            <div class="org-level academic">
                <div class="org-branch">
                    <div class="org-node academic">
                        <i class="fas fa-heartbeat org-icon"></i>
                        <div class="org-title">Head of Nursing</div>
                        <div class="org-subtitle">Nursing Department</div>
                        <a href="staff-login.php?position=Head%20of%20Nursing" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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
                    </div>
                </div>
            </div>

            <!-- ═══ SUPPORT STAFF ═══ -->
            <div class="level-label">Support Staff</div>
            <div class="org-level support">
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-hands-helping org-icon"></i>
                        <div class="org-title">Matrons</div>
                        <div class="org-subtitle">Student Welfare</div>
                        <a href="staff-login.php?position=Matrons" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-shield-alt org-icon"></i>
                        <div class="org-title">Wardens</div>
                        <div class="org-subtitle">Student Care &amp; Support</div>
                        <a href="staff-login.php?position=Wardens" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
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
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-flask org-icon"></i>
                        <div class="org-title">Skills Lab Manager</div>
                        <div class="org-subtitle">Skills Laboratory Management</div>
                        <a href="staff-login.php?position=Skills%20Lab%20Manager" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node support">
                        <i class="fas fa-desktop org-icon"></i>
                        <div class="org-title">Computer Lab Manager</div>
                        <div class="org-subtitle">ICT Operations &amp; Lab Support</div>
                        <a href="staff-login.php?position=Computer%20Lab%20Manager" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- ═══ STUDENT LEVEL ═══ -->
            <div class="level-label">Student Leadership</div>
            <div class="org-level student">
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-users org-icon"></i>
                        <div class="org-title">Students</div>
                        <div class="org-subtitle">All Student Access</div>
                        <a href="student-login.php?student_role=Students" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Student Login
                        </a>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-crown org-icon"></i>
                        <div class="org-title">Guild President</div>
                        <div class="org-subtitle">Student Leadership</div>
                        <a href="student-login.php?student_role=Guild%20President" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Student Login
                        </a>
                    </div>
                </div>
                <div class="org-branch">
                    <div class="org-node student">
                        <i class="fas fa-user-tie org-icon"></i>
                        <div class="org-title">Class Representatives</div>
                        <div class="org-subtitle">Class Leadership</div>
                        <a href="student-login.php?student_role=Class%20Representatives" class="org-link">
                            <i class="fas fa-sign-in-alt"></i> Student Login
                        </a>
                    </div>
                </div>
            </div>

        </div>
        </div>

            <script>
        document.addEventListener('DOMContentLoaded', function() {
            var starsContainer = document.getElementById('stars');
            if (starsContainer) {
                for (var i = 0; i < 120; i++) {
                    var star = document.createElement('div');
                    star.className = 'star';
                    star.style.left = Math.random() * 100 + '%';
                    star.style.top = Math.random() * 100 + '%';
                    star.style.setProperty('--duration', (2 + Math.random() * 4) + 's');
                    star.style.animationDelay = Math.random() * 5 + 's';
                    star.style.opacity = 0.2 + Math.random() * 0.4;
                    starsContainer.appendChild(star);
                }
            }

            document.querySelectorAll('.org-node').forEach(function(node) {
                node.addEventListener('mousemove', function(e) {
                    var rect = this.getBoundingClientRect();
                    var x = (e.clientX - rect.left) / rect.width - 0.5;
                    var y = (e.clientY - rect.top) / rect.height - 0.5;
                    var rotateX = y * -6;
                    var rotateY = x * 6;
                    this.style.transform =
                        'translateY(-6px) perspective(600px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg) scale(1.02)';
                });
                node.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
                node.addEventListener('click', function(e) {
                    if (e.target.closest('a') || e.target.closest('button')) return;
                    var link = this.querySelector('a.org-link');
                    if (link) window.location.href = link.href;
                });
                node.style.cursor = 'pointer';
            });
        });
    </script>
</main>
<?php include("shared/_footer.php"); ?>
