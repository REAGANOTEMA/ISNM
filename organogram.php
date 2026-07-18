<?php 
$pageTitle = 'ISNM Organizational Structure';
include('shared/_header.php');
?>
    <style>
        /* ══════════════════════════════════════════════════════════════
           ISNM ORGANOGRAM — Premium Mobile-First Design
           ══════════════════════════════════════════════════════════════ */
        :root {
            --exec: #667eea;
            --mgmt: #c471f5;
            --admin: #4facfe;
            --academic: #43e97b;
            --support: #f673a8;
            --student: #30cfd0;
            --bg-deep: #060a14;
            --bg-card: rgba(255,255,255,0.035);
            --border-subtle: rgba(255,255,255,0.06);
            --text-primary: #f0f2f8;
            --text-secondary: rgba(255,255,255,0.55);
            --glow-spread: 40px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-deep);
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Ambient background ── */
        .org-bg {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(102,126,234,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 85%, rgba(196,113,245,0.05) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(79,172,254,0.04) 0%, transparent 60%),
                linear-gradient(180deg, #060a14 0%, #0a1226 40%, #0f1a2e 70%, #060a14 100%);
        }
        .org-bg::after {
            content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.012) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.012) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── Stars ── */
        .stars { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
        .star {
            position: absolute; width: 2px; height: 2px; background: #fff; border-radius: 50%;
            animation: twinkle var(--d, 3s) ease-in-out infinite alternate;
        }

        @keyframes twinkle {
            0% { opacity: 0.15; transform: scale(0.7); }
            100% { opacity: 0.9; transform: scale(1.1); }
        }

        /* ══════════════════════════════════════════════════
           SCHOOL HEADER — Glassmorphism Banner
           ══════════════════════════════════════════════════ */
        .school-banner {
            position: relative; z-index: 1;
            max-width: 1000px; margin: 0 auto 40px;
            padding: 36px 32px;
            background: linear-gradient(135deg, rgba(102,126,234,0.06) 0%, rgba(196,113,245,0.04) 50%, rgba(79,172,254,0.06) 100%);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.07);
            display: flex; align-items: center; justify-content: center; gap: 28px;
            box-shadow:
                0 8px 40px rgba(0,0,0,0.3),
                0 1px 0 rgba(255,255,255,0.05) inset;
            animation: bannerFadeIn 0.8s ease-out;
        }

        @keyframes bannerFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .school-banner::before {
            content: ''; position: absolute; inset: -1px; border-radius: 25px;
            background: conic-gradient(from 180deg, rgba(102,126,234,0.12), rgba(196,113,245,0.08), rgba(79,172,254,0.12), rgba(67,233,123,0.08), rgba(102,126,234,0.12));
            z-index: -1; mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude; -webkit-mask-composite: xor; padding: 1.5px;
            animation: rotateConic 12s linear infinite;
        }

        @keyframes rotateConic { to { transform: rotate(360deg); } }

        .school-banner .logo-ring {
            position: relative; flex-shrink: 0;
        }
        .school-banner .logo-ring::after {
            content: ''; position: absolute; inset: -6px; border-radius: 50%;
            border: 1.5px solid rgba(102,126,234,0.15);
            animation: ringPulse 3s ease-in-out infinite;
        }
        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.06); opacity: 0.8; }
        }

        .school-banner .school-logo {
            width: 72px; height: 72px; border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,0.1);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            transition: transform 0.4s ease;
        }
        .school-banner:hover .school-logo { transform: scale(1.05); }

        .school-banner .school-text {
            text-align: center; flex: 1; min-width: 0;
        }
        .school-banner .school-text h2 {
            font-size: 1.65rem; font-weight: 800; line-height: 1.2; margin-bottom: 6px;
            background: linear-gradient(135deg, #fff 0%, #c8d0ff 50%, #c471f5 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .school-banner .school-text p {
            font-size: 0.88rem; font-style: italic; color: var(--text-secondary);
            letter-spacing: 0.3px;
        }

        .school-banner .divider-line {
            width: 1px; height: 50px; flex-shrink: 0;
            background: linear-gradient(180deg, transparent, rgba(255,255,255,0.08), transparent);
        }

        /* ══════════════════════════════════════════════════
           PAGE HEADER
           ══════════════════════════════════════════════════ */
        .page-header {
            position: relative; z-index: 1; text-align: center; margin-bottom: 48px;
            padding: 0 16px;
        }
        .page-header h1 {
            font-size: 2.1rem; font-weight: 800; margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #a8b8ff 40%, #c471f5 70%, #4facfe 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .page-header p {
            font-size: 1rem; color: var(--text-secondary); max-width: 500px; margin: 0 auto;
        }
        .page-header .hint-pills {
            display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 14px;
        }
        .page-header .hint-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 14px; border-radius: 20px;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
            font-size: 0.75rem; color: var(--text-secondary);
        }
        .hint-pill i { font-size: 0.65rem; opacity: 0.6; }

        /* ══════════════════════════════════════════════════
           ORG TREE CONTAINER
           ══════════════════════════════════════════════════ */
        .org-tree {
            position: relative; z-index: 1;
            max-width: 1300px; margin: 0 auto; padding: 0 24px 60px;
        }

        /* ── Level divider ── */
        .level-section { margin-bottom: 44px; }

        .level-tag {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 18px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 2px; margin-bottom: 20px;
            border: 1px solid var(--tag-border);
            background: var(--tag-bg);
            color: var(--tag-color);
            animation: tagSlideIn 0.6s ease-out both;
        }
        .level-tag i { font-size: 0.65rem; }

        @keyframes tagSlideIn {
            from { opacity: 0; transform: translateX(-12px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .level-section.executive .level-tag { --tag-color: var(--exec); --tag-border: rgba(102,126,234,0.2); --tag-bg: rgba(102,126,234,0.08); }
        .level-section.management .level-tag { --tag-color: var(--mgmt); --tag-border: rgba(196,113,245,0.2); --tag-bg: rgba(196,113,245,0.08); }
        .level-section.administrative .level-tag { --tag-color: var(--admin); --tag-border: rgba(79,172,254,0.2); --tag-bg: rgba(79,172,254,0.08); }
        .level-section.academic .level-tag { --tag-color: var(--academic); --tag-border: rgba(67,233,123,0.2); --tag-bg: rgba(67,233,123,0.08); }
        .level-section.support .level-tag { --tag-color: var(--support); --tag-border: rgba(246,115,168,0.2); --tag-bg: rgba(246,115,168,0.08); }
        .level-section.student .level-tag { --tag-color: var(--student); --tag-border: rgba(48,207,208,0.2); --tag-bg: rgba(48,207,208,0.08); }

        /* ── Card grid ── */
        .org-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
            justify-items: center;
        }

        /* ══════════════════════════════════════════════════
           ORG CARD — Premium Glassmorphism
           ══════════════════════════════════════════════════ */
        .org-card {
            position: relative;
            width: 100%; max-width: 250px;
            background: var(--bg-card);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 18px;
            border: 1px solid var(--border-subtle);
            padding: 28px 18px 18px;
            text-align: center;
            display: flex; flex-direction: column; align-items: center;
            min-height: 230px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.4s cubic-bezier(.25,.8,.25,1), box-shadow 0.4s ease, border-color 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: cardReveal 0.5s ease-out both;
        }

        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .org-card::before {
            content: ''; position: absolute; inset: 0; border-radius: 18px;
            background: linear-gradient(160deg, rgba(255,255,255,0.06) 0%, transparent 40%, rgba(0,0,0,0.03) 100%);
            pointer-events: none; z-index: 0;
        }

        /* Shine sweep */
        .org-card::after {
            content: ''; position: absolute; top: 0; left: -120%; width: 50%; height: 100%;
            background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.06) 50%, transparent 70%);
            transform: skewX(-12deg); transition: left 0.6s ease; pointer-events: none; z-index: 1;
        }
        .org-card:hover::after { left: 140%; }

        .org-card > * { position: relative; z-index: 2; }

        .org-card:hover {
            transform: translateY(-8px);
            box-shadow:
                0 12px 40px rgba(0,0,0,0.35),
                0 0 var(--glow-spread) var(--card-glow, rgba(255,255,255,0.03));
        }

        /* Category glow borders */
        .org-card.cat-exec { border-color: rgba(102,126,234,0.15); --card-glow: rgba(102,126,234,0.1); }
        .org-card.cat-exec:hover { border-color: rgba(102,126,234,0.4); }
        .org-card.cat-mgmt { border-color: rgba(196,113,245,0.15); --card-glow: rgba(196,113,245,0.1); }
        .org-card.cat-mgmt:hover { border-color: rgba(196,113,245,0.4); }
        .org-card.cat-admin { border-color: rgba(79,172,254,0.15); --card-glow: rgba(79,172,254,0.1); }
        .org-card.cat-admin:hover { border-color: rgba(79,172,254,0.4); }
        .org-card.cat-academic { border-color: rgba(67,233,123,0.15); --card-glow: rgba(67,233,123,0.1); }
        .org-card.cat-academic:hover { border-color: rgba(67,233,123,0.4); }
        .org-card.cat-support { border-color: rgba(246,115,168,0.15); --card-glow: rgba(246,115,168,0.1); }
        .org-card.cat-support:hover { border-color: rgba(246,115,168,0.4); }
        .org-card.cat-student { border-color: rgba(48,207,208,0.15); --card-glow: rgba(48,207,208,0.1); }
        .org-card.cat-student:hover { border-color: rgba(48,207,208,0.4); }

        /* Icon circle */
        .card-icon {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 14px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.06);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .org-card:hover .card-icon {
            transform: scale(1.1) translateY(-3px);
        }
        .org-card.cat-exec .card-icon { color: var(--exec); box-shadow: 0 0 20px rgba(102,126,234,0.08); }
        .org-card.cat-mgmt .card-icon { color: var(--mgmt); box-shadow: 0 0 20px rgba(196,113,245,0.08); }
        .org-card.cat-admin .card-icon { color: var(--admin); box-shadow: 0 0 20px rgba(79,172,254,0.08); }
        .org-card.cat-academic .card-icon { color: var(--academic); box-shadow: 0 0 20px rgba(67,233,123,0.08); }
        .org-card.cat-support .card-icon { color: var(--support); box-shadow: 0 0 20px rgba(246,115,168,0.08); }
        .org-card.cat-student .card-icon { color: var(--student); box-shadow: 0 0 20px rgba(48,207,208,0.08); }

        .card-title {
            font-size: 0.92rem; font-weight: 700; line-height: 1.25;
            color: #fff; margin-bottom: 6px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-sub {
            font-size: 0.78rem; color: var(--text-secondary); line-height: 1.35;
            margin-bottom: 14px; flex-grow: 1;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-login {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 22px; border-radius: 24px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.75);
            text-decoration: none; font-size: 0.78rem; font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        }
        .card-login:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
            color: #fff; transform: scale(1.04);
        }
        .card-login i { font-size: 0.7rem; }

        /* ══════════════════════════════════════════════════
           MOBILE-FIRST RESPONSIVE
           ══════════════════════════════════════════════════ */

        /* Large tablets / small desktop */
        @media (max-width: 1024px) {
            .org-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 14px;
            }
        }

        /* Tablets */
        @media (max-width: 768px) {
            .school-banner {
                padding: 28px 20px; margin-bottom: 28px; gap: 20px;
                border-radius: 20px;
            }
            .school-banner .school-logo { width: 56px; height: 56px; }
            .school-banner .school-text h2 { font-size: 1.25rem; }
            .school-banner .school-text p { font-size: 0.82rem; }
            .school-banner .divider-line { display: none; }
            .school-banner .logo-ring:last-child { display: none; }

            .page-header { margin-bottom: 32px; }
            .page-header h1 { font-size: 1.55rem; }
            .page-header p { font-size: 0.9rem; }

            .org-tree { padding: 0 12px 40px; }
            .level-section { margin-bottom: 32px; }

            .org-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 12px;
            }
            .org-card {
                max-width: none; min-height: 200px;
                padding: 22px 14px 16px; border-radius: 14px;
            }
            .card-icon { width: 48px; height: 48px; font-size: 1.2rem; margin-bottom: 12px; }
            .card-title { font-size: 0.84rem; }
            .card-sub { font-size: 0.73rem; }
            .card-login { padding: 7px 18px; font-size: 0.74rem; }
        }

        /* Mobile */
        @media (max-width: 576px) {
            .school-banner {
                padding: 22px 16px; margin-bottom: 20px; gap: 14px;
            }
            .school-banner .school-logo { width: 48px; height: 48px; }
            .school-banner .school-text h2 { font-size: 1.05rem; }
            .school-banner .school-text p { font-size: 0.76rem; }

            .page-header { margin-bottom: 24px; padding: 0 8px; }
            .page-header h1 { font-size: 1.3rem; }
            .page-header p { font-size: 0.82rem; }
            .page-header .hint-pills { gap: 6px; }
            .hint-pill { font-size: 0.68rem; padding: 4px 10px; }

            .org-tree { padding: 0 8px 32px; }
            .level-section { margin-bottom: 24px; }
            .level-tag { font-size: 0.62rem; padding: 5px 14px; letter-spacing: 1.5px; margin-bottom: 14px; }

            .org-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            .org-card {
                min-height: 0; padding: 18px 10px 14px;
                border-radius: 12px;
            }
            .card-icon { width: 42px; height: 42px; font-size: 1.05rem; margin-bottom: 10px; border-radius: 14px; }
            .card-title { font-size: 0.78rem; margin-bottom: 4px; }
            .card-sub { font-size: 0.68rem; margin-bottom: 10px; -webkit-line-clamp: 2; }
            .card-login { padding: 6px 14px; font-size: 0.7rem; border-radius: 20px; gap: 4px; }
            .card-login i { font-size: 0.6rem; }
        }

        /* Small phones */
        @media (max-width: 380px) {
            .school-banner { padding: 18px 12px; gap: 10px; }
            .school-banner .school-logo { width: 40px; height: 40px; }
            .school-banner .school-text h2 { font-size: 0.95rem; }
            .school-banner .school-text p { font-size: 0.72rem; }

            .page-header h1 { font-size: 1.15rem; }

            .org-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .org-card { padding: 14px 8px 12px; border-radius: 10px; }
            .card-icon { width: 36px; height: 36px; font-size: 0.95rem; margin-bottom: 8px; }
            .card-title { font-size: 0.73rem; }
            .card-sub { font-size: 0.63rem; }
            .card-login { padding: 5px 12px; font-size: 0.66rem; }
        }

        /* ══════════════════════════════════════════════════
           ENTRANCE STAGGER ANIMATION
           ══════════════════════════════════════════════════ */
        .org-card:nth-child(1) { animation-delay: 0.05s; }
        .org-card:nth-child(2) { animation-delay: 0.1s; }
        .org-card:nth-child(3) { animation-delay: 0.15s; }
        .org-card:nth-child(4) { animation-delay: 0.2s; }
        .org-card:nth-child(5) { animation-delay: 0.25s; }
        .org-card:nth-child(6) { animation-delay: 0.3s; }
        .org-card:nth-child(7) { animation-delay: 0.35s; }
        .org-card:nth-child(8) { animation-delay: 0.4s; }

        /* ── Scroll reveal ── */
        .level-section {
            opacity: 0; transform: translateY(24px);
            animation: sectionReveal 0.6s ease-out forwards;
        }
        .level-section:nth-child(1) { animation-delay: 0.1s; }
        .level-section:nth-child(2) { animation-delay: 0.2s; }
        .level-section:nth-child(3) { animation-delay: 0.3s; }
        .level-section:nth-child(4) { animation-delay: 0.4s; }
        .level-section:nth-child(5) { animation-delay: 0.5s; }
        .level-section:nth-child(6) { animation-delay: 0.6s; }
        .level-section:nth-child(7) { animation-delay: 0.7s; }

        @keyframes sectionReveal {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Pulse animation for DG card ── */
        .org-card.pulse-glow {
            animation: cardReveal 0.5s ease-out both, pulseBorder 4s ease-in-out infinite 1s;
        }
        @keyframes pulseBorder {
            0%, 100% { border-color: rgba(102,126,234,0.15); }
            50% { border-color: rgba(102,126,234,0.35); box-shadow: 0 4px 20px rgba(0,0,0,0.2), 0 0 30px rgba(102,126,234,0.06); }
        }

        /* ── Touch-friendly mobile taps ── */
        @media (hover: none) and (pointer: coarse) {
            .org-card { -webkit-tap-highlight-color: transparent; }
            .org-card:active { transform: scale(0.97); transition-duration: 0.1s; }
            .card-login:active { background: rgba(255,255,255,0.12); transform: scale(0.96); }
        }

        /* ── Safe area for notched phones ── */
        @supports (padding: env(safe-area-inset-bottom)) {
            .org-tree { padding-bottom: calc(32px + env(safe-area-inset-bottom)); }
        }
    </style>

    <main>
        <div class="org-bg"></div>
        <div class="stars" id="stars"></div>

        <!-- ═══ SCHOOL BANNER ═══ -->
        <div class="school-banner">
            <div class="logo-ring">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            </div>
            <div class="divider-line"></div>
            <div class="school-text">
                <h2>Iganga School of Nursing and Midwifery</h2>
                <p>"Chosen to Serve, Based on a disciplined mind for health action"</p>
            </div>
            <div class="divider-line"></div>
            <div class="logo-ring">
                <img src="images/school-logo.png" alt="ISNM Logo" class="school-logo">
            </div>
        </div>

        <!-- ═══ PAGE HEADER ═══ -->
        <div class="page-header">
            <h1><i class="fas fa-sitemap"></i> Organizational Structure</h1>
            <p>Click on your position to access your personalized dashboard</p>
            <div class="hint-pills">
                <span class="hint-pill"><i class="fas fa-hand-pointer"></i> Tap a card to login</span>
                <span class="hint-pill"><i class="fas fa-layer-group"></i> 6 tiers</span>
                <span class="hint-pill"><i class="fas fa-users"></i> 30+ positions</span>
            </div>
        </div>

        <!-- ═══ ORG TREE ═══ -->
        <div class="org-tree">

            <!-- ── EXECUTIVE ── -->
            <div class="level-section executive">
                <div class="level-tag"><i class="fas fa-crown"></i> Executive</div>
                <div class="org-grid">
                    <div class="org-card cat-exec pulse-glow">
                        <div class="card-icon"><i class="fas fa-crown"></i></div>
                        <div class="card-title">Director General</div>
                        <div class="card-sub">Overall Institution Leadership</div>
                        <a href="staff-login.php?position=Director%20General" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── MANAGEMENT ── -->
            <div class="level-section management">
                <div class="level-tag"><i class="fas fa-user-tie"></i> Management</div>
                <div class="org-grid">
                    <div class="org-card cat-mgmt">
                        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="card-title">Chief Executive Officer</div>
                        <div class="card-sub">Executive Leadership</div>
                        <a href="staff-login.php?position=Chief%20Executive%20Officer" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="card-title">Director Academics</div>
                        <div class="card-sub">Academic Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Academics" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-icon"><i class="fas fa-coins"></i></div>
                        <div class="card-title">Director Finance</div>
                        <div class="card-sub">Financial Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Finance" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                        <div class="card-title">Director ICT</div>
                        <div class="card-sub">ICT Department Oversight &amp; Management</div>
                        <a href="staff-login.php?position=Director%20ICT" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── SCHOOL ADMINISTRATION ── -->
            <div class="level-section administrative">
                <div class="level-tag"><i class="fas fa-building"></i> School Administration</div>
                <div class="org-grid">
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="card-title">School Principal</div>
                        <div class="card-sub">Chief Academic Officer</div>
                        <a href="staff-login.php?position=School%20Principal" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-title">Deputy Principal</div>
                        <div class="card-sub">Assistant Academic Officer</div>
                        <a href="staff-login.php?position=Deputy%20Principal" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-money-check-alt"></i></div>
                        <div class="card-title">School Bursar</div>
                        <div class="card-sub">Chief Financial Officer</div>
                        <a href="staff-login.php?position=School%20Bursar" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-user-check"></i></div>
                        <div class="card-title">Director Admissions &amp; Requirements</div>
                        <div class="card-sub">Admissions &amp; Requirements Clearance</div>
                        <a href="staff-login.php?position=Director%20Admissions%20%26%20Requirements" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── ADMINISTRATIVE STAFF ── -->
            <div class="level-section administrative">
                <div class="level-tag"><i class="fas fa-folder-open"></i> Administrative Staff</div>
                <div class="org-grid">
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="card-title">Academic Registrar</div>
                        <div class="card-sub">Student Records</div>
                        <a href="staff-login.php?position=Academic%20Registrar" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                        <div class="card-title">HR Manager</div>
                        <div class="card-sub">Human Resources</div>
                        <a href="staff-login.php?position=HR%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-envelope"></i></div>
                        <div class="card-title">School Secretary</div>
                        <div class="card-sub">Administrative Support</div>
                        <a href="staff-login.php?position=School%20Secretary" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-book"></i></div>
                        <div class="card-title">School Librarian</div>
                        <div class="card-sub">Library Management</div>
                        <a href="staff-login.php?position=School%20Librarian" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="card-title">Events Coordinator</div>
                        <div class="card-sub">Event Planning &amp; Management</div>
                        <a href="staff-login.php?position=Events%20Coordinator" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-title">Alumni Relations Officer</div>
                        <div class="card-sub">Alumni Engagement &amp; Records</div>
                        <a href="staff-login.php?position=Alumni%20Relations%20Officer" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── ACADEMIC STAFF ── -->
            <div class="level-section academic">
                <div class="level-tag"><i class="fas fa-chalkboard"></i> Academic Staff</div>
                <div class="org-grid">
                    <div class="org-card cat-academic">
                        <div class="card-icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="card-title">Head of Nursing</div>
                        <div class="card-sub">Nursing Department</div>
                        <a href="staff-login.php?position=Head%20of%20Nursing" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-icon"><i class="fas fa-baby"></i></div>
                        <div class="card-title">Head of Midwifery</div>
                        <div class="card-sub">Midwifery Department</div>
                        <a href="staff-login.php?position=Head%20of%20Midwifery" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-icon"><i class="fas fa-chalkboard"></i></div>
                        <div class="card-title">Senior Lecturers</div>
                        <div class="card-sub">Advanced Teaching</div>
                        <a href="staff-login.php?position=Senior%20Lecturers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-icon"><i class="fas fa-book-reader"></i></div>
                        <div class="card-title">Lecturers</div>
                        <div class="card-sub">Classroom Teaching</div>
                        <a href="staff-login.php?position=Lecturers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── SUPPORT STAFF ── -->
            <div class="level-section support">
                <div class="level-tag"><i class="fas fa-hands-helping"></i> Support Staff</div>
                <div class="org-grid">
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-hands-helping"></i></div>
                        <div class="card-title">Matrons</div>
                        <div class="card-sub">Student Welfare</div>
                        <a href="staff-login.php?position=Matrons" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="card-title">Wardens</div>
                        <div class="card-sub">Student Care &amp; Support</div>
                        <a href="staff-login.php?position=Wardens" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="card-title">Sickbay</div>
                        <div class="card-sub">Student Health Support</div>
                        <a href="staff-login.php?position=Sickbay" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-bus"></i></div>
                        <div class="card-title">Drivers</div>
                        <div class="card-sub">Transport Services</div>
                        <a href="staff-login.php?position=Drivers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="card-title">Security</div>
                        <div class="card-sub">Campus Security</div>
                        <a href="staff-login.php?position=Security" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-warehouse"></i></div>
                        <div class="card-title">Store Keeper</div>
                        <div class="card-sub">Inventory Management</div>
                        <a href="staff-login.php?position=Store%20Keeper" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-flask"></i></div>
                        <div class="card-title">Skills Lab Manager</div>
                        <div class="card-sub">Skills Laboratory Management</div>
                        <a href="staff-login.php?position=Skills%20Lab%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-icon"><i class="fas fa-desktop"></i></div>
                        <div class="card-title">Computer Lab Manager</div>
                        <div class="card-sub">ICT Operations &amp; Lab Support</div>
                        <a href="staff-login.php?position=Computer%20Lab%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <!-- ── STUDENT LEVEL ── -->
            <div class="level-section student">
                <div class="level-tag"><i class="fas fa-user-graduate"></i> Student Leadership</div>
                <div class="org-grid">
                    <div class="org-card cat-student">
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                        <div class="card-title">Students</div>
                        <div class="card-sub">All Student Access</div>
                        <a href="student-login.php?student_role=Students" class="card-login"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                    <div class="org-card cat-student">
                        <div class="card-icon"><i class="fas fa-crown"></i></div>
                        <div class="card-title">Guild President</div>
                        <div class="card-sub">Student Leadership</div>
                        <a href="student-login.php?student_role=Guild%20President" class="card-login"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                    <div class="org-card cat-student">
                        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="card-title">Class Representatives</div>
                        <div class="card-sub">Class Leadership</div>
                        <a href="student-login.php?student_role=Class%20Representatives" class="card-login"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        /* ── Generate stars ── */
        var sc = document.getElementById('stars');
        if (sc) {
            for (var i = 0; i < 80; i++) {
                var s = document.createElement('div');
                s.className = 'star';
                s.style.left = Math.random() * 100 + '%';
                s.style.top = Math.random() * 100 + '%';
                s.style.setProperty('--d', (2 + Math.random() * 4) + 's');
                s.style.animationDelay = Math.random() * 5 + 's';
                s.style.opacity = 0.15 + Math.random() * 0.35;
                sc.appendChild(s);
            }
        }

        /* ── Card click → navigate to login link ── */
        document.querySelectorAll('.org-card').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('a')) return;
                var link = this.querySelector('a.card-login');
                if (link) window.location.href = link.href;
            });
        });
    });
    </script>
<?php include("shared/_footer.php"); ?>
