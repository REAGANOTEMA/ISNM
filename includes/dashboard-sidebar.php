<?php
/**
 * ISNM Responsive Dashboard Sidebar Component
 * Perfect responsive navigation for all devices
 */

$currentRole = $_SESSION['role'] ?? 'staff';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define menu structure based on role
$menuStructure = [
    'director_general' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => '../dashboards/director-general.php', 'active' => 'director-general'],
        ['label' => 'Staff Directory', 'icon' => 'people', 'url' => '../dashboards/staff-directory.php', 'active' => 'staff-directory'],
        ['label' => 'Financial Reports', 'icon' => 'assessment', 'url' => '../dashboards/financial-reports.php', 'active' => 'financial-reports'],
        ['label' => 'Messaging', 'icon' => 'mail', 'url' => '../dashboards/messaging.php', 'active' => 'messaging'],
        ['label' => 'System Admin', 'icon' => 'settings', 'url' => '../dashboards/system-admin.php', 'active' => 'system-admin'],
    ],
    'director' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => '../dashboards/director-general.php', 'active' => 'director-general'],
        ['label' => 'News', 'icon' => 'article', 'url' => '../dashboards/news.php', 'active' => 'news'],
        ['label' => 'Departments', 'icon' => 'domain', 'url' => '../dashboards/director-general.php?page=departments', 'active' => 'departments'],
        ['label' => 'Reports', 'icon' => 'assessment', 'url' => '../dashboards/financial-reports.php', 'active' => 'financial-reports'],
        ['label' => 'Messages', 'icon' => 'mail', 'url' => '../dashboards/messaging.php', 'active' => 'messaging'],
    ],
    'bursar' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => '../dashboards/school-bursar.php', 'active' => 'school-bursar'],
        ['label' => 'Student Fees', 'icon' => 'money', 'url' => '../dashboards/student-fees.php', 'active' => 'student-fees'],
        ['label' => 'Payments', 'icon' => 'payment', 'url' => '../dashboards/bursar-payments.php', 'active' => 'bursar-payments'],
        ['label' => 'Reports', 'icon' => 'assessment', 'url' => '../dashboards/bursar-reports.php', 'active' => 'bursar-reports'],
        ['label' => 'Messages', 'icon' => 'mail', 'url' => '../dashboards/messaging.php', 'active' => 'messaging'],
    ],
    'admissions' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => '../dashboards/director-admissions.php', 'active' => 'director-admissions'],
        ['label' => 'Applications', 'icon' => 'description', 'url' => '../dashboards/director-admissions.php?page=applications', 'active' => 'applications'],
        ['label' => 'Applicants', 'icon' => 'people', 'url' => '../dashboards/director-admissions.php?page=applicants', 'active' => 'applicants'],
        ['label' => 'Enrollment', 'icon' => 'school', 'url' => '../dashboards/director-admissions.php?page=registration', 'active' => 'registration'],
        ['label' => 'Reports', 'icon' => 'assessment', 'url' => '../dashboards/director-admissions.php?page=reports', 'active' => 'reports'],
    ],
    'lecturer' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => '../dashboards/lecturers.php', 'active' => 'lecturers'],
        ['label' => 'Classes', 'icon' => 'class', 'url' => '../dashboards/lecturers.php?page=classes', 'active' => 'classes'],
        ['label' => 'Grades', 'icon' => 'assessment', 'url' => '../dashboards/lecturers.php?page=grades', 'active' => 'grades'],
        ['label' => 'Attendance', 'icon' => 'event_note', 'url' => '../dashboards/lecturers.php?page=attendance', 'active' => 'attendance'],
        ['label' => 'Resources', 'icon' => 'folder', 'url' => '../dashboards/lecturers.php?page=resources', 'active' => 'resources'],
    ],
];

$menu = $menuStructure[$currentRole] ?? $menuStructure['lecturer'];
?>

<aside class="dashboard-sidebar" id="dashboardSidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <h2 class="sidebar-title"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $currentRole))); ?></h2>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menu as $item): ?>
                <?php 
                $isActive = str_replace('-', '', $item['active']) === str_replace('-', '', $currentPage);
                $isActive = $isActive || (basename($item['url'], '.php') === basename($_SERVER['PHP_SELF'], '.php'));
                ?>
                <li class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="nav-link">
                        <span class="nav-icon">
                            <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                                <?php
                                // Icon SVG paths
                                $icons = [
                                    'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
                                    'people' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm1 6h-2v4h-4v-4h-2v-5h8v5zm-11-7.5c1.38 0 2.5-1.12 2.5-2.5S6.38 3.5 5 3.5 2.5 4.62 2.5 6 3.62 8.5 5 8.5zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-4.5c0-2.33-4.67-3.5-7-3.5z"/>',
                                    'assessment' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2V17zm4 0h-2V7h2V17zm4 0h-2v-4h2V17z"/>',
                                    'mail' => '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
                                    'settings' => '<path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.62l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.48.1.62l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.62l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.1-.62l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>',
                                    'article' => '<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>',
                                    'domain' => '<path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2z"/>',
                                    'money' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>',
                                    'payment' => '<path d="M20 8H4V6h16m0 10H4c-1.1 0-2 .9-2 2v2h20v-2c0-1.1-.9-2-2-2zm0-5H4c-1.1 0-2 .9-2 2v2h20v-2c0-1.1-.9-2-2-2z"/>',
                                    'description' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>',
                                    'school' => '<path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>',
                                    'class' => '<path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>',
                                    'event_note' => '<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zm-5-5H7v5h7v-5z"/>',
                                    'folder' => '<path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>',
                                ];
                                echo $icons[$item['icon']] ?? '';
                                ?>
                            </svg>
                        </span>
                        <span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                        <span class="nav-indicator" aria-hidden="true"></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=1a237e&color=fff" 
                 alt="Profile" class="sidebar-avatar">
            <div class="sidebar-user-info">
                <p class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                <p class="sidebar-user-role"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $currentRole))); ?></p>
            </div>
        </div>
    </div>
</aside>

<style>
/* Dashboard Sidebar Responsive Styles */
.dashboard-sidebar {
    background: white;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 60px;
    width: 280px;
    height: calc(100vh - 60px);
    overflow-y: auto;
    z-index: 90;
    box-shadow: var(--shadow-md);
    transform: translateX(0);
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 24px 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

.sidebar-close {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: var(--primary-color);
}

.sidebar-close svg {
    width: 24px;
    height: 24px;
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
}

.nav-item.active {
    border-left-color: var(--accent-color);
    background: rgba(58, 73, 171, 0.05);
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--dark-text);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.nav-item.active .nav-link {
    color: var(--primary-color);
    font-weight: 600;
}

.nav-link:hover {
    background: rgba(58, 73, 171, 0.05);
    padding-left: 20px;
}

.nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.nav-icon svg {
    width: 20px;
    height: 20px;
    opacity: 0.7;
}

.nav-item.active .nav-icon svg {
    opacity: 1;
    color: var(--accent-color);
}

.nav-label {
    flex: 1;
}

.nav-indicator {
    width: 8px;
    height: 8px;
    background: var(--accent-color);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-item.active .nav-indicator {
    opacity: 1;
}

.sidebar-footer {
    padding: 16px;
    border-top: 1px solid var(--border-color);
    background: var(--light-bg);
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 8px;
    border-radius: 8px;
}

.sidebar-user:hover {
    background: white;
}

.sidebar-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.sidebar-user-info {
    flex: 1;
    min-width: 0;
}

.sidebar-user-name {
    margin: 0 0 2px 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--dark-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-user-role {
    margin: 0;
    font-size: 11px;
    color: #999;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Mobile Styles (< 768px) */
@media (max-width: 767px) {
    .dashboard-sidebar {
        width: 100%;
        left: 0;
        top: 60px;
        height: calc(100vh - 60px);
        transform: translateX(-100%);
        box-shadow: none;
    }

    .dashboard-sidebar.active {
        transform: translateX(0);
        box-shadow: var(--shadow-lg);
    }

    .sidebar-close {
        display: block;
    }
}

/* Tablet Styles (768px - 1024px) */
@media (min-width: 768px) and (max-width: 1023px) {
    .dashboard-sidebar {
        width: 240px;
    }

    .sidebar-header {
        padding: 20px 12px;
    }

    .sidebar-title {
        font-size: 16px;
    }

    .nav-link {
        padding: 10px 12px;
        font-size: 13px;
    }
}

/* Desktop Styles (1024px+) */
@media (min-width: 1024px) {
    .dashboard-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        transform: translateX(0) !important;
        box-shadow: none;
        z-index: 50;
    }

    .sidebar-close {
        display: none !important;
    }
}

/* Scrollbar Styling */
.dashboard-sidebar::-webkit-scrollbar {
    width: 6px;
}

.dashboard-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.dashboard-sidebar::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}

.dashboard-sidebar::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Print Styles */
@media print {
    .dashboard-sidebar {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('dashboardSidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const hamburger = document.getElementById('hamburgerMenu');

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', () => {
            sidebar.classList.remove('active');
            hamburger?.classList.remove('active');
        });
    }

    // Close sidebar when clicking on a link (mobile)
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                sidebar.classList.remove('active');
                hamburger?.classList.remove('active');
            }
        });
    });

    // Handle screen resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('active');
            hamburger?.classList.remove('active');
        }
    });
});
</script>
