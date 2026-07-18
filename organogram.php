<?php 
$pageTitle = 'ISNM Organizational Structure';
include('shared/_header.php');
?>
    <style>
        /* ══════════════════════════════════════════════════════════════
           ISNM ORGANOGRAM — Hierarchical Tree Layout
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
            --line-color: rgba(255,255,255,0.08);
            --line-glow: rgba(102,126,234,0.12);
            --text-primary: #f0f2f8;
            --text-secondary: rgba(255,255,255,0.55);
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
           SCHOOL BANNER
           ══════════════════════════════════════════════════ */
        .school-banner {
            position: relative; z-index: 1;
            max-width: 960px; margin: 0 auto 36px;
            padding: 32px 28px;
            background: linear-gradient(135deg, rgba(102,126,234,0.06) 0%, rgba(196,113,245,0.04) 50%, rgba(79,172,254,0.06) 100%);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.07);
            display: flex; align-items: center; justify-content: center; gap: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.3), 0 1px 0 rgba(255,255,255,0.05) inset;
            animation: fadeDown 0.7s ease-out;
        }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }

        .school-banner::before {
            content: ''; position: absolute; inset: -1px; border-radius: 25px;
            background: conic-gradient(from 180deg, rgba(102,126,234,0.12), rgba(196,113,245,0.08), rgba(79,172,254,0.12), rgba(67,233,123,0.08), rgba(102,126,234,0.12));
            z-index: -1; mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude; -webkit-mask-composite: xor; padding: 1.5px;
            animation: rotateConic 12s linear infinite;
        }
        @keyframes rotateConic { to { transform: rotate(360deg); } }

        .banner-logo { position: relative; flex-shrink: 0; }
        .banner-logo::after {
            content: ''; position: absolute; inset: -5px; border-radius: 50%;
            border: 1.5px solid rgba(102,126,234,0.15);
            animation: ringPulse 3s ease-in-out infinite;
        }
        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        .banner-logo img { width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .banner-divider { width: 1px; height: 48px; flex-shrink: 0; background: linear-gradient(180deg, transparent, rgba(255,255,255,0.08), transparent); }
        .banner-text { text-align: center; flex: 1; min-width: 0; }
        .banner-text h2 {
            font-size: 1.6rem; font-weight: 800; line-height: 1.2; margin-bottom: 5px;
            background: linear-gradient(135deg, #fff 0%, #c8d0ff 50%, #c471f5 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .banner-text p { font-size: 0.85rem; font-style: italic; color: var(--text-secondary); }

        /* ══════════════════════════════════════════════════
           PAGE HEADER
           ══════════════════════════════════════════════════ */
        .page-header { position: relative; z-index: 1; text-align: center; margin-bottom: 40px; padding: 0 16px; }
        .page-header h1 {
            font-size: 2rem; font-weight: 800; margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #a8b8ff 40%, #c471f5 70%, #4facfe 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .page-header p { font-size: 0.95rem; color: var(--text-secondary); max-width: 480px; margin: 0 auto; }

        /* ══════════════════════════════════════════════════
           ORG CHART TREE
           ══════════════════════════════════════════════════ */
        .org-chart {
            position: relative; z-index: 1;
            max-width: 1400px; margin: 0 auto; padding: 0 20px 80px;
            overflow-x: auto;
        }

        /* ── Tier (one level of the hierarchy) ── */
        .org-tier {
            display: flex; flex-direction: column; align-items: center;
            position: relative;
            margin-bottom: 0;
        }

        /* ── Vertical connector between tiers ── */
        .v-line {
            width: 2px; height: 40px;
            background: linear-gradient(180deg, var(--line-color), var(--line-glow), var(--line-color));
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        .v-line.short { height: 28px; }
        .v-line::before {
            content: ''; position: absolute; bottom: -3px; left: 50%; transform: translateX(-50%);
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(102,126,234,0.25);
            box-shadow: 0 0 8px rgba(102,126,234,0.15);
        }

        /* ── Tier label ── */
        .tier-label {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 5px 16px; border-radius: 20px;
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 2px; margin-bottom: 18px;
            border: 1px solid var(--tl-border);
            background: var(--tl-bg);
            color: var(--tl-color);
            animation: labelIn 0.5s ease-out both;
        }
        .tier-label i { font-size: 0.6rem; }

        @keyframes labelIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

        .org-tier.t-exec .tier-label { --tl-color: var(--exec); --tl-border: rgba(102,126,234,0.2); --tl-bg: rgba(102,126,234,0.08); }
        .org-tier.t-mgmt .tier-label { --tl-color: var(--mgmt); --tl-border: rgba(196,113,245,0.2); --tl-bg: rgba(196,113,245,0.08); }
        .org-tier.t-admin .tier-label { --tl-color: var(--admin); --tl-border: rgba(79,172,254,0.2); --tl-bg: rgba(79,172,254,0.08); }
        .org-tier.t-academic .tier-label { --tl-color: var(--academic); --tl-border: rgba(67,233,123,0.2); --tl-bg: rgba(67,233,123,0.08); }
        .org-tier.t-support .tier-label { --tl-color: var(--support); --tl-border: rgba(246,115,168,0.2); --tl-bg: rgba(246,115,168,0.08); }
        .org-tier.t-student .tier-label { --tl-color: var(--student); --tl-border: rgba(48,207,208,0.2); --tl-bg: rgba(48,207,208,0.08); }

        /* ── Cards row ── */
        .tier-cards {
            display: flex; justify-content: center; align-items: flex-start;
            gap: 18px; flex-wrap: wrap;
            position: relative;
        }

        /* ── Horizontal connector bar ── */
        .h-bar {
            position: absolute; top: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--line-color) 15%, var(--line-color) 85%, transparent);
            z-index: 0;
        }

        /* ══════════════════════════════════════════════════
           ORG CARD
           ══════════════════════════════════════════════════ */
        .org-card {
            position: relative;
            width: 200px;
            background: var(--bg-card);
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.06);
            padding: 24px 14px 16px;
            text-align: center;
            display: flex; flex-direction: column; align-items: center;
            min-height: 195px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.4s cubic-bezier(.25,.8,.25,1), box-shadow 0.4s ease, border-color 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 2;
        }

        .org-card::before {
            content: ''; position: absolute; inset: 0; border-radius: 16px;
            background: linear-gradient(160deg, rgba(255,255,255,0.06) 0%, transparent 40%, rgba(0,0,0,0.03) 100%);
            pointer-events: none; z-index: 0;
        }

        /* Drop line from horizontal bar to card */
        .org-card::after {
            content: ''; position: absolute; top: -20px; left: 50%; transform: translateX(-50%);
            width: 2px; height: 20px;
            background: linear-gradient(180deg, var(--line-color), rgba(255,255,255,0.04));
            z-index: -1;
        }

        .org-card > * { position: relative; z-index: 2; }

        /* Shine sweep */
        .org-card .card-shine {
            position: absolute; top: 0; left: -120%; width: 50%; height: 100%;
            background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.06) 50%, transparent 70%);
            transform: skewX(-12deg); transition: left 0.6s ease; pointer-events: none; z-index: 3;
        }
        .org-card:hover .card-shine { left: 140%; }

        .org-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.35), 0 0 30px var(--card-glow, rgba(255,255,255,0.02));
        }

        /* Category styles */
        .org-card.cat-exec { border-color: rgba(102,126,234,0.15); --card-glow: rgba(102,126,234,0.08); }
        .org-card.cat-exec:hover { border-color: rgba(102,126,234,0.4); }
        .org-card.cat-mgmt { border-color: rgba(196,113,245,0.15); --card-glow: rgba(196,113,245,0.08); }
        .org-card.cat-mgmt:hover { border-color: rgba(196,113,245,0.4); }
        .org-card.cat-admin { border-color: rgba(79,172,254,0.15); --card-glow: rgba(79,172,254,0.08); }
        .org-card.cat-admin:hover { border-color: rgba(79,172,254,0.4); }
        .org-card.cat-academic { border-color: rgba(67,233,123,0.15); --card-glow: rgba(67,233,123,0.08); }
        .org-card.cat-academic:hover { border-color: rgba(67,233,123,0.4); }
        .org-card.cat-support { border-color: rgba(246,115,168,0.15); --card-glow: rgba(246,115,168,0.08); }
        .org-card.cat-support:hover { border-color: rgba(246,115,168,0.4); }
        .org-card.cat-student { border-color: rgba(48,207,208,0.15); --card-glow: rgba(48,207,208,0.08); }
        .org-card.cat-student:hover { border-color: rgba(48,207,208,0.4); }

        /* ── Root card (DG) — special crown treatment ── */
        .org-card.root-node {
            width: 230px; min-height: 210px;
            border-color: rgba(102,126,234,0.25);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2), 0 0 40px rgba(102,126,234,0.06);
        }
        .org-card.root-node::after { display: none; }
        .org-card.root-node .card-icon { width: 62px; height: 62px; font-size: 1.6rem; border-radius: 50%; border-color: rgba(102,126,234,0.2); }
        .org-card.root-node:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.35), 0 0 50px rgba(102,126,234,0.12);
            border-color: rgba(102,126,234,0.5);
        }
        .org-card.root-node { animation: cardReveal 0.5s ease-out both, pulseRoot 4s ease-in-out infinite 1.5s; }
        @keyframes pulseRoot {
            0%, 100% { box-shadow: 0 4px 20px rgba(0,0,0,0.2), 0 0 30px rgba(102,126,234,0.04); }
            50% { box-shadow: 0 4px 20px rgba(0,0,0,0.2), 0 0 50px rgba(102,126,234,0.1); }
        }

        /* ── Icon ── */
        .card-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 12px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.06);
            transition: transform 0.4s ease;
        }
        .org-card:hover .card-icon { transform: scale(1.1) translateY(-2px); }

        .org-card.cat-exec .card-icon { color: var(--exec); }
        .org-card.cat-mgmt .card-icon { color: var(--mgmt); }
        .org-card.cat-admin .card-icon { color: var(--admin); }
        .org-card.cat-academic .card-icon { color: var(--academic); }
        .org-card.cat-support .card-icon { color: var(--support); }
        .org-card.cat-student .card-icon { color: var(--student); }

        .card-title {
            font-size: 0.88rem; font-weight: 700; line-height: 1.25;
            color: #fff; margin-bottom: 4px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .card-sub {
            font-size: 0.73rem; color: var(--text-secondary); line-height: 1.35;
            margin-bottom: 12px; flex-grow: 1;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .card-login {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 20px; border-radius: 22px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.75);
            text-decoration: none; font-size: 0.74rem; font-weight: 500;
            transition: all 0.3s ease;
        }
        .card-login:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff; transform: scale(1.04); }
        .card-login i { font-size: 0.65rem; }

        /* ── Stagger animation ── */
        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(18px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .tier-cards .org-card:nth-child(1) { animation: cardReveal 0.45s ease-out 0.05s both; }
        .tier-cards .org-card:nth-child(2) { animation: cardReveal 0.45s ease-out 0.1s both; }
        .tier-cards .org-card:nth-child(3) { animation: cardReveal 0.45s ease-out 0.15s both; }
        .tier-cards .org-card:nth-child(4) { animation: cardReveal 0.45s ease-out 0.2s both; }
        .tier-cards .org-card:nth-child(5) { animation: cardReveal 0.45s ease-out 0.25s both; }
        .tier-cards .org-card:nth-child(6) { animation: cardReveal 0.45s ease-out 0.3s both; }
        .tier-cards .org-card:nth-child(7) { animation: cardReveal 0.45s ease-out 0.35s both; }
        .tier-cards .org-card:nth-child(8) { animation: cardReveal 0.45s ease-out 0.4s both; }

        /* ══════════════════════════════════════════════════
           RESPONSIVE — Desktop tree
           ══════════════════════════════════════════════════ */
        @media (min-width: 1100px) {
            .org-card { width: 190px; }
            .org-card.root-node { width: 220px; }
            .tier-cards { gap: 16px; }
        }

        /* ── Tablet ── */
        @media (max-width: 1099px) {
            .org-chart { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .org-card { width: 180px; min-height: 180px; }
            .org-card.root-node { width: 200px; }
            .tier-cards { gap: 12px; }
            .v-line { height: 32px; }
        }

        /* ── Mobile: hide tree lines, show card grid ── */
        @media (max-width: 768px) {
            .school-banner { padding: 24px 16px; margin-bottom: 24px; gap: 14px; border-radius: 18px; }
            .school-banner .banner-logo img { width: 50px; height: 50px; }
            .school-banner .banner-text h2 { font-size: 1.15rem; }
            .school-banner .banner-text p { font-size: 0.78rem; }
            .school-banner .banner-divider:last-of-type,
            .school-banner .banner-logo:last-of-type { display: none; }

            .page-header { margin-bottom: 28px; }
            .page-header h1 { font-size: 1.4rem; }
            .page-header p { font-size: 0.85rem; }

            .org-chart { padding: 0 10px 40px; }

            /* Hide all tree connectors on mobile */
            .v-line, .h-bar { display: none; }
            .org-card::after { display: none; }

            .org-tier { margin-bottom: 20px; }

            .tier-cards {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                justify-items: center;
            }

            .org-card, .org-card.root-node {
                width: 100%; min-height: 0;
                padding: 16px 10px 14px; border-radius: 12px;
            }
            .org-card.root-node .card-icon { width: 48px; height: 48px; font-size: 1.2rem; }
            .card-icon { width: 40px; height: 40px; font-size: 1rem; margin-bottom: 8px; border-radius: 12px; }
            .card-title { font-size: 0.78rem; }
            .card-sub { font-size: 0.67rem; margin-bottom: 8px; }
            .card-login { padding: 5px 14px; font-size: 0.68rem; }

            /* Touch feedback */
            .org-card:active { transform: scale(0.97); transition-duration: 0.1s; }
        }

        @media (max-width: 380px) {
            .school-banner { padding: 18px 12px; gap: 10px; }
            .school-banner .banner-logo img { width: 40px; height: 40px; }
            .school-banner .banner-text h2 { font-size: 0.95rem; }
            .school-banner .banner-text p { font-size: 0.7rem; }
            .page-header h1 { font-size: 1.15rem; }
            .org-card { padding: 14px 8px 12px; border-radius: 10px; }
            .card-icon { width: 36px; height: 36px; font-size: 0.9rem; }
            .card-title { font-size: 0.72rem; }
            .card-sub { font-size: 0.62rem; }
            .card-login { padding: 4px 12px; font-size: 0.64rem; }
        }

        /* Safe area */
        @supports (padding: env(safe-area-inset-bottom)) {
            .org-chart { padding-bottom: calc(40px + env(safe-area-inset-bottom)); }
        }

        /* ── Touch devices ── */
        @media (hover: none) and (pointer: coarse) {
            .org-card { -webkit-tap-highlight-color: transparent; }
            .card-login:active { background: rgba(255,255,255,0.12); transform: scale(0.96); }
        }
    </style>

    <main>
        <div class="org-bg"></div>
        <div class="stars" id="stars"></div>

        <!-- ═══ SCHOOL BANNER ═══ -->
        <div class="school-banner">
            <div class="banner-logo"><img src="images/school-logo.png" alt="ISNM Logo"></div>
            <div class="banner-divider"></div>
            <div class="banner-text">
                <h2>Iganga School of Nursing and Midwifery</h2>
                <p>"Chosen to Serve, Based on a disciplined mind for health action"</p>
            </div>
            <div class="banner-divider"></div>
            <div class="banner-logo"><img src="images/school-logo.png" alt="ISNM Logo"></div>
        </div>

        <!-- ═══ PAGE HEADER ═══ -->
        <div class="page-header">
            <h1><i class="fas fa-sitemap"></i> Organizational Structure</h1>
            <p>Click on your position to access your personalized dashboard</p>
        </div>

        <!-- ═══════════════════════════════════════════════════
             ORG CHART TREE
             ═══════════════════════════════════════════════════ -->
        <div class="org-chart">

            <!-- ── TIER 1: EXECUTIVE (Root) ── -->
            <div class="org-tier t-exec">
                <div class="tier-label"><i class="fas fa-crown"></i> Executive</div>
                <div class="tier-cards">
                    <div class="org-card root-node cat-exec">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-crown"></i></div>
                        <div class="card-title">Director General</div>
                        <div class="card-sub">Overall Institution Leadership</div>
                        <a href="staff-login.php?position=Director%20General" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 2: MANAGEMENT ── -->
            <div class="org-tier t-mgmt">
                <div class="tier-label"><i class="fas fa-user-tie"></i> Management</div>
                <div class="tier-cards">
                    <div class="org-card cat-mgmt">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                        <div class="card-title">Chief Executive Officer</div>
                        <div class="card-sub">Executive Leadership</div>
                        <a href="staff-login.php?position=Chief%20Executive%20Officer" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="card-title">Director Academics</div>
                        <div class="card-sub">Academic Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Academics" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-coins"></i></div>
                        <div class="card-title">Director Finance</div>
                        <div class="card-sub">Financial Affairs Director</div>
                        <a href="staff-login.php?position=Director%20Finance" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-mgmt">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                        <div class="card-title">Director ICT</div>
                        <div class="card-sub">ICT Department Oversight</div>
                        <a href="staff-login.php?position=Director%20ICT" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 3: SCHOOL ADMINISTRATION ── -->
            <div class="org-tier t-admin">
                <div class="tier-label"><i class="fas fa-building"></i> School Administration</div>
                <div class="tier-cards">
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="card-title">School Principal</div>
                        <div class="card-sub">Chief Academic Officer</div>
                        <a href="staff-login.php?position=School%20Principal" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-title">Deputy Principal</div>
                        <div class="card-sub">Assistant Academic Officer</div>
                        <a href="staff-login.php?position=Deputy%20Principal" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-money-check-alt"></i></div>
                        <div class="card-title">School Bursar</div>
                        <div class="card-sub">Chief Financial Officer</div>
                        <a href="staff-login.php?position=School%20Bursar" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-user-check"></i></div>
                        <div class="card-title">Director Admissions &amp; Requirements</div>
                        <div class="card-sub">Admissions &amp; Clearance</div>
                        <a href="staff-login.php?position=Director%20Admissions%20%26%20Requirements" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 4: ADMINISTRATIVE STAFF ── -->
            <div class="org-tier t-admin">
                <div class="tier-label"><i class="fas fa-folder-open"></i> Administrative Staff</div>
                <div class="tier-cards">
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="card-title">Academic Registrar</div>
                        <div class="card-sub">Student Records</div>
                        <a href="staff-login.php?position=Academic%20Registrar" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                        <div class="card-title">HR Manager</div>
                        <div class="card-sub">Human Resources</div>
                        <a href="staff-login.php?position=HR%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-envelope"></i></div>
                        <div class="card-title">School Secretary</div>
                        <div class="card-sub">Administrative Support</div>
                        <a href="staff-login.php?position=School%20Secretary" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-book"></i></div>
                        <div class="card-title">School Librarian</div>
                        <div class="card-sub">Library Management</div>
                        <a href="staff-login.php?position=School%20Librarian" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="card-title">Events Coordinator</div>
                        <div class="card-sub">Event Planning &amp; Management</div>
                        <a href="staff-login.php?position=Events%20Coordinator" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-admin">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="card-title">Alumni Relations Officer</div>
                        <div class="card-sub">Alumni Engagement &amp; Records</div>
                        <a href="staff-login.php?position=Alumni%20Relations%20Officer" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 5: ACADEMIC STAFF ── -->
            <div class="org-tier t-academic">
                <div class="tier-label"><i class="fas fa-chalkboard"></i> Academic Staff</div>
                <div class="tier-cards">
                    <div class="org-card cat-academic">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="card-title">Head of Nursing</div>
                        <div class="card-sub">Nursing Department</div>
                        <a href="staff-login.php?position=Head%20of%20Nursing" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-baby"></i></div>
                        <div class="card-title">Head of Midwifery</div>
                        <div class="card-sub">Midwifery Department</div>
                        <a href="staff-login.php?position=Head%20of%20Midwifery" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-chalkboard"></i></div>
                        <div class="card-title">Senior Lecturers</div>
                        <div class="card-sub">Advanced Teaching</div>
                        <a href="staff-login.php?position=Senior%20Lecturers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-academic">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-book-reader"></i></div>
                        <div class="card-title">Lecturers</div>
                        <div class="card-sub">Classroom Teaching</div>
                        <a href="staff-login.php?position=Lecturers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 6: SUPPORT STAFF ── -->
            <div class="org-tier t-support">
                <div class="tier-label"><i class="fas fa-hands-helping"></i> Support Staff</div>
                <div class="tier-cards">
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-hands-helping"></i></div>
                        <div class="card-title">Matrons</div>
                        <div class="card-sub">Student Welfare</div>
                        <a href="staff-login.php?position=Matrons" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="card-title">Wardens</div>
                        <div class="card-sub">Student Care &amp; Support</div>
                        <a href="staff-login.php?position=Wardens" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="card-title">Sickbay</div>
                        <div class="card-sub">Student Health Support</div>
                        <a href="staff-login.php?position=Sickbay" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-bus"></i></div>
                        <div class="card-title">Drivers</div>
                        <div class="card-sub">Transport Services</div>
                        <a href="staff-login.php?position=Drivers" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="card-title">Security</div>
                        <div class="card-sub">Campus Security</div>
                        <a href="staff-login.php?position=Security" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-warehouse"></i></div>
                        <div class="card-title">Store Keeper</div>
                        <div class="card-sub">Inventory Management</div>
                        <a href="staff-login.php?position=Store%20Keeper" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-flask"></i></div>
                        <div class="card-title">Skills Lab Manager</div>
                        <div class="card-sub">Skills Laboratory</div>
                        <a href="staff-login.php?position=Skills%20Lab%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                    <div class="org-card cat-support">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-desktop"></i></div>
                        <div class="card-title">Computer Lab Manager</div>
                        <div class="card-sub">ICT Lab Operations</div>
                        <a href="staff-login.php?position=Computer%20Lab%20Manager" class="card-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </div>
                </div>
            </div>

            <div class="v-line"></div>

            <!-- ── TIER 7: STUDENT LEADERSHIP ── -->
            <div class="org-tier t-student">
                <div class="tier-label"><i class="fas fa-user-graduate"></i> Student Leadership</div>
                <div class="tier-cards">
                    <div class="org-card cat-student">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                        <div class="card-title">Students</div>
                        <div class="card-sub">All Student Access</div>
                        <a href="student-login.php?student_role=Students" class="card-login"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                    <div class="org-card cat-student">
                        <div class="card-shine"></div>
                        <div class="card-icon"><i class="fas fa-crown"></i></div>
                        <div class="card-title">Guild President</div>
                        <div class="card-sub">Student Leadership</div>
                        <a href="student-login.php?student_role=Guild%20President" class="card-login"><i class="fas fa-sign-in-alt"></i> Student Login</a>
                    </div>
                    <div class="org-card cat-student">
                        <div class="card-shine"></div>
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
