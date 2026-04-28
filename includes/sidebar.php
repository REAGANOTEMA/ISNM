<?php
/**
 * Responsive Dashboard Navigation System
 * Mobile Slider Menu + Desktop Sidebar for ISNM School Management System
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['type'])) {
    header('Location: ../index.php');
    exit();
}

$user_role = $_SESSION['role'];
$user_type = $_SESSION['type'];
$user_name = $_SESSION['first_name'] ?? 'User';
$user_id = $_SESSION['user_id'];

// Determine menu items based on user role and type
$menu_items = [];

// Common menu items for all users
$menu_items[] = [
    'icon' => 'fas fa-tachometer-alt',
    'title' => 'Dashboard',
    'link' => '#dashboard-home',
    'active' => true
];

$menu_items[] = [
    'icon' => 'fas fa-user',
    'title' => 'Profile',
    'link' => '#profile',
    'active' => false
];

// Messages for all users
$menu_items[] = [
    'icon' => 'fas fa-envelope',
    'title' => 'Messages',
    'link' => '#messages',
    'active' => false,
    'badge' => '5' // This should be dynamic based on unread messages
];

// Student-specific menu items
if ($user_type === 'student') {
    $menu_items[] = [
        'icon' => 'fas fa-book',
        'title' => 'Academics',
        'link' => '#academics',
        'active' => false
    ];
    
    $menu_items[] = [
        'icon' => 'fas fa-money-bill',
        'title' => 'Fees',
        'link' => '#fees',
        'active' => false
    ];
    
    $menu_items[] = [
        'icon' => 'fas fa-calendar',
        'title' => 'Schedule',
        'link' => '#schedule',
        'active' => false
    ];
}

// Staff-specific menu items
if ($user_type === 'staff') {
    // Check if user can create students
    $can_create_students = (
        strpos(strtolower($user_role), 'director') !== false ||
        in_array(strtolower($user_role), ['secretary', 'principal', 'accountant', 'school secretary', 'school principal', 'school bursar'])
    );
    
    if ($can_create_students) {
        $menu_items[] = [
            'icon' => 'fas fa-user-plus',
            'title' => 'Students',
            'link' => '../student_accounts_management.php',
            'active' => false
        ];
    }
    
    // Staff tools based on role
    $menu_items[] = [
        'icon' => 'fas fa-tools',
        'title' => 'Staff Tools',
        'link' => '#staff-tools',
        'active' => false
    ];
    
    // Communication system
    $menu_items[] = [
        'icon' => 'fas fa-comments',
        'title' => 'Communication',
        'link' => '../student_communication_system.php',
        'active' => false
    ];
    
    // Reports for admin roles
    if (strpos(strtolower($user_role), 'director') !== false || 
        in_array(strtolower($user_role), ['principal', 'secretary', 'accountant'])) {
        $menu_items[] = [
            'icon' => 'fas fa-chart-bar',
            'title' => 'Reports',
            'link' => '#reports',
            'active' => false
        ];
    }
}

// Settings menu item for all users
$menu_items[] = [
    'icon' => 'fas fa-cog',
    'title' => 'Settings',
    'link' => '#settings',
    'active' => false
];

// Logout menu item
$menu_items[] = [
    'icon' => 'fas fa-sign-out-alt',
    'title' => 'Logout',
    'link' => '../auth-handler.php?action=logout',
    'active' => false
];
?>

<!-- Responsive Dashboard Navigation -->
<div class="dashboard-nav-container">
    <!-- Mobile Hamburger Menu -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Sidebar Navigation -->
    <nav class="dashboard-sidebar" id="dashboardSidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../images/school-logo.png" alt="ISNM Logo" class="sidebar-logo-img">
                <div class="sidebar-title">
                    <h3>ISNM</h3>
                    <span class="sidebar-subtitle">Management System</span>
                </div>
            </div>
            
            <!-- User Profile Section -->
            <div class="sidebar-user">
                <div class="user-avatar">
                    <img src="../images/default-avatar.png" alt="User Avatar" class="user-avatar-img">
                    <span class="user-status online"></span>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($user_role); ?></div>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-nav">
            <ul class="nav-list">
                <?php foreach ($menu_items as $index => $item): ?>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                           class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>"
                           data-section="<?php echo htmlspecialchars($item['link']); ?>">
                            <div class="nav-icon">
                                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </div>
                            <span class="nav-text"><?php echo htmlspecialchars($item['title']); ?></span>
                            <?php if (isset($item['badge']) && $item['badge']): ?>
                                <span class="nav-badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="footer-info">
                <div class="version">v2.0.1</div>
                <div class="copyright">© 2026 ISNM</div>
            </div>
        </div>
    </nav>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<!-- Navigation CSS -->
<style>
/* Dashboard Navigation System */
.dashboard-nav-container {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
}

/* Mobile Hamburger Menu */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.mobile-menu-toggle:active {
    transform: translateY(0);
}

.hamburger-line {
    display: block;
    width: 24px;
    height: 3px;
    background: white;
    margin: 5px 0;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Sidebar Navigation */
.dashboard-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #1a237e, #3949ab);
    color: white;
    overflow-y: auto;
    overflow-x: hidden;
    transition: transform 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
}

/* Sidebar Header */
.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.sidebar-logo-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.sidebar-title h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: white;
}

.sidebar-subtitle {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* User Profile Section */
.sidebar-user {
    display: flex;
    align-items: center;
    position: relative;
}

.user-avatar {
    position: relative;
    margin-right: 15px;
}

.user-avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.user-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #1a237e;
}

.user-status.online {
    background: #4caf50;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.user-role {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
    text-transform: capitalize;
}

.sidebar-close {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.sidebar-close:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Navigation Menu */
.sidebar-nav {
    padding: 20px 0;
}

.nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    margin-bottom: 2px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 15px 25px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    position: relative;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #ffd700;
    transform: translateX(5px);
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: #ffd700;
}

.nav-icon {
    width: 24px;
    margin-right: 15px;
    text-align: center;
    font-size: 16px;
}

.nav-text {
    flex: 1;
    font-weight: 500;
    font-size: 14px;
}

.nav-badge {
    background: #ff5252;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    font-weight: 600;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
}

.footer-info {
    text-align: center;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.version {
    margin-bottom: 5px;
}

/* Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .dashboard-sidebar {
        transform: translateX(-100%);
    }
    
    .dashboard-sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-close {
        display: block;
    }
    
    .sidebar-overlay {
        display: block;
    }
    
    .sidebar-overlay.active {
        opacity: 1;
    }
}

@media (min-width: 769px) {
    .dashboard-sidebar {
        transform: translateX(0);
    }
    
    .dashboard-sidebar.collapsed {
        transform: translateX(-280px);
    }
    
    /* Add sidebar toggle button for desktop */
    .sidebar-toggle {
        display: block;
    }
    
    .sidebar-header {
        padding: 15px;
    }
    
    .sidebar-title {
        display: none;
    }
    
    .sidebar-logo-img {
        margin-right: 0;
    }
    
    .sidebar-user {
        flex-direction: column;
        text-align: center;
    }
    
    .user-avatar {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .user-info {
        display: none;
    }
    
    .sidebar-close {
        display: none;
    }
    
    .nav-text {
        display: none;
    }
    
    .nav-link {
        justify-content: center;
        padding: 15px;
    }
    
    .nav-icon {
        margin-right: 0;
    }
    
    .sidebar-footer {
        display: none;
    }
}

/* Collapsed Sidebar Toggle (Desktop) */
.sidebar-toggle {
    position: absolute;
    top: 20px;
    right: -15px;
    background: #3949ab;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    z-index: 1001;
}

.sidebar-toggle:hover {
    background: #5c6bc0;
    transform: scale(1.1);
}

/* Animation Classes */
.slide-in {
    animation: slideIn 0.3s ease;
}

.slide-out {
    animation: slideOut 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-100%);
    }
}

/* Accessibility */
.nav-link:focus {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

.mobile-menu-toggle:focus,
.sidebar-close:focus,
.sidebar-toggle:focus {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}
</style>

<!-- Navigation JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('dashboardSidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navLinks = document.querySelectorAll('.nav-link');
    const dashboardContainer = document.querySelector('.dashboard-container');
    
    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            this.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        if (mobileMenuToggle) {
            mobileMenuToggle.classList.remove('active');
        }
        document.body.style.overflow = '';
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Handle navigation clicks
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Close mobile sidebar after navigation
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
            
            // Handle section navigation for hash links
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetSection = document.querySelector(href);
                if (targetSection) {
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeSidebar();
                // Adjust dashboard container margin for desktop
                if (dashboardContainer) {
                    if (sidebar.classList.contains('collapsed')) {
                        dashboardContainer.style.marginLeft = '0';
                    } else {
                        dashboardContainer.style.marginLeft = '280px';
                    }
                }
            } else {
                // Reset for mobile
                if (dashboardContainer) {
                    dashboardContainer.style.marginLeft = '0';
                }
            }
        }, 250);
    });
    
    // Add desktop sidebar toggle functionality
    function toggleDesktopSidebar() {
        if (window.innerWidth > 768 && dashboardContainer) {
            sidebar.classList.toggle('collapsed');
            
            if (sidebar.classList.contains('collapsed')) {
                dashboardContainer.style.marginLeft = '0';
                dashboardContainer.classList.add('sidebar-collapsed');
            } else {
                dashboardContainer.style.marginLeft = '280px';
                dashboardContainer.classList.remove('sidebar-collapsed');
            }
        }
    }
    
    // Create and add sidebar toggle button
    if (window.innerWidth > 768) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'sidebar-toggle';
        toggleButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle sidebar');
        toggleButton.addEventListener('click', toggleDesktopSidebar);
        sidebar.appendChild(toggleButton);
        
        // Update toggle button icon when sidebar state changes
        const updateToggleButton = () => {
            const icon = toggleButton.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
            } else {
                icon.className = 'fas fa-chevron-left';
            }
        };
        
        // Update icon on toggle
        toggleButton.addEventListener('click', updateToggleButton);
        
        // Set initial state
        if (dashboardContainer) {
            dashboardContainer.style.marginLeft = '280px';
        }
    }
    
    // Add touch support for mobile swipe
    let touchStartX = 0;
    let touchEndX = 0;
    
    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - close sidebar
                closeSidebar();
            } else {
                // Swipe right - open sidebar (only if closed)
                if (!sidebar.classList.contains('active') && window.innerWidth <= 768) {
                    sidebar.classList.add('active');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
        }
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
});
</script>
