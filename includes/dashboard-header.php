<?php
/**
 * ISNM Responsive Dashboard Header Component
 * Perfect for all devices - mobile, tablet, desktop
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['staff_id'])) {
    header('Location: /staff-login.php');
    exit;
}

$fullName = $_SESSION['full_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'staff';
$department = $_SESSION['department'] ?? 'Unknown';
?>

<header class="dashboard-header">
    <!-- Mobile Hamburger Menu -->
    <button class="hamburger-menu" id="hamburgerMenu" aria-label="Toggle menu" aria-expanded="false">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Logo Section -->
    <div class="dashboard-logo">
        <a href="/index.php" class="logo-link">
            <svg class="logo-icon" viewBox="0 0 100 100" fill="currentColor">
                <path d="M50 10 L90 30 L90 70 C90 80, 70 90, 50 90 C30 90, 10 80, 10 70 L10 30 Z" />
                <circle cx="50" cy="45" r="15" fill="white" />
            </svg>
            <span class="logo-text">ISNM</span>
        </a>
    </div>

    <!-- Center Search Bar -->
    <div class="dashboard-search">
        <input 
            type="text" 
            class="search-input" 
            id="globalStudentSearch"
            placeholder="Search students by name, index, email..."
            aria-label="Search students"
        >
        <button class="search-button" aria-label="Search">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
        </button>
        <div class="search-results" id="searchResults" style="display: none;"></div>
    </div>

    <!-- Right Side Actions -->
    <div class="dashboard-actions">
        <!-- Notifications -->
        <div class="notification-dropdown">
            <button class="notification-btn" id="notificationBtn" aria-label="Notifications" aria-haspopup="true">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
            <div class="notification-panel" id="notificationPanel" style="display: none;">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button class="close-btn" id="closeNotifications" aria-label="Close">✕</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <p class="empty-message">No notifications</p>
                </div>
            </div>
        </div>

        <!-- User Profile Menu -->
        <div class="profile-dropdown">
            <button class="profile-btn" id="profileBtn" aria-label="User profile" aria-haspopup="true">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullName); ?>&background=1a237e&color=fff" 
                     alt="Profile" class="profile-avatar">
                <span class="profile-name"><?php echo htmlspecialchars(explode(' ', $fullName)[0]); ?></span>
                <svg class="profile-arrow" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z"/>
                </svg>
            </button>
            <div class="profile-menu" id="profileMenu" style="display: none;">
                <div class="profile-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullName); ?>&background=1a237e&color=fff" 
                         alt="Profile" class="profile-menu-avatar">
                    <div class="profile-text">
                        <p class="profile-full-name"><?php echo htmlspecialchars($fullName); ?></p>
                        <p class="profile-role"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role))); ?></p>
                        <p class="profile-department"><?php echo htmlspecialchars($department); ?></p>
                    </div>
                </div>
                <hr class="profile-divider">
                <a href="/settings.php" class="profile-menu-item">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.62l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.48.1.62l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.62l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.1-.62l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                    </svg>
                    <span>Settings</span>
                </a>
                <a href="/help.php" class="profile-menu-item">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
                    </svg>
                    <span>Help & Support</span>
                </a>
                <hr class="profile-divider">
                <a href="/logout.php" class="profile-menu-item profile-logout">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>

<style>
/* Dashboard Header Responsive Styles */
.dashboard-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-md);
    flex-wrap: wrap;
}

.hamburger-menu {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    gap: 5px;
    margin: 0;
}

.hamburger-line {
    width: 24px;
    height: 2.5px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.hamburger-menu.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(8px, 8px);
}

.hamburger-menu.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.hamburger-menu.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -7px);
}

.dashboard-logo {
    flex-shrink: 0;
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
    font-weight: 700;
    font-size: 18px;
    transition: opacity 0.3s ease;
}

.logo-link:hover {
    opacity: 0.9;
}

.logo-icon {
    width: 36px;
    height: 36px;
    color: var(--accent-color);
}

.logo-text {
    display: none;
    white-space: nowrap;
}

.dashboard-search {
    flex: 1;
    min-width: 200px;
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s ease;
}

.dashboard-search:focus-within {
    background: rgba(255, 255, 255, 0.25);
    box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3);
}

.search-input {
    flex: 1;
    background: none;
    border: none;
    color: white;
    font-size: 14px;
    outline: none;
    padding: 0;
    margin: 0;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
}

.search-button svg {
    width: 20px;
    height: 20px;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 8px;
    margin-top: 8px;
    box-shadow: var(--shadow-lg);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
}

.search-result-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s ease;
    color: var(--dark-text);
    text-decoration: none;
    display: block;
}

.search-result-item:hover {
    background: var(--light-bg);
}

.search-result-name {
    font-weight: 600;
    color: var(--primary-color);
}

.search-result-info {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.dashboard-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

/* Notification Dropdown */
.notification-dropdown,
.profile-dropdown {
    position: relative;
}

.notification-btn,
.profile-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
    width: 40px;
    height: 40px;
}

.notification-btn:hover,
.profile-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.notification-btn svg,
.profile-btn svg {
    width: 20px;
    height: 20px;
}

.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #e74c3c;
    color: white;
    font-size: 10px;
    font-weight: 700;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--primary-color);
}

.notification-panel,
.profile-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    margin-top: 8px;
    min-width: 300px;
    max-width: 400px;
    z-index: 1000;
}

.notification-header,
.profile-header {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--dark-text);
}

.close-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 20px;
    color: #999;
    padding: 0;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.empty-message {
    padding: 20px;
    text-align: center;
    color: #999;
    font-size: 14px;
    margin: 0;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s ease;
}

.notification-item:hover {
    background: var(--light-bg);
}

.notification-item.unread {
    background: rgba(58, 73, 171, 0.05);
}

.notification-title {
    font-weight: 600;
    color: var(--primary-color);
    margin: 0 0 4px 0;
    font-size: 14px;
}

.notification-message {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.4;
}

.notification-time {
    font-size: 11px;
    color: #999;
    margin-top: 4px;
}

/* Profile Menu */
.profile-btn {
    display: flex;
    gap: 8px;
    border-radius: 24px;
    padding: 4px 8px 4px 4px;
    width: auto;
    height: auto;
}

.profile-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-name {
    display: none;
    font-size: 14px;
    font-weight: 500;
    color: white;
}

.profile-arrow {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.profile-btn.active .profile-arrow {
    transform: rotate(180deg);
}

.profile-menu {
    min-width: 280px;
    right: auto;
    left: auto;
}

.profile-info {
    padding: 16px;
    display: flex;
    gap: 12px;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.profile-menu-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-text {
    flex: 1;
}

.profile-full-name {
    margin: 0 0 4px 0;
    font-weight: 600;
    font-size: 14px;
    color: var(--dark-text);
}

.profile-role {
    margin: 0 0 2px 0;
    font-size: 12px;
    color: #666;
}

.profile-department {
    margin: 0;
    font-size: 11px;
    color: #999;
}

.profile-divider {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 8px 0;
    padding: 0;
}

.profile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--dark-text);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 14px;
}

.profile-menu-item:hover {
    background: var(--light-bg);
    color: var(--primary-color);
}

.profile-menu-item svg {
    width: 18px;
    height: 18px;
    opacity: 0.7;
}

.profile-menu-item:hover svg {
    opacity: 1;
}

.profile-logout {
    color: #e74c3c;
}

.profile-logout:hover {
    background: rgba(231, 76, 60, 0.1);
}

/* Mobile Styles (< 640px) */
@media (max-width: 639px) {
    .hamburger-menu {
        display: flex;
    }

    .dashboard-header {
        padding: 12px 12px;
        gap: 12px;
    }

    .logo-text {
        display: none !important;
    }

    .logo-icon {
        width: 32px;
        height: 32px;
    }

    .dashboard-search {
        display: none;
        position: absolute;
        top: 60px;
        left: 12px;
        right: 12px;
        width: calc(100% - 24px);
        order: 5;
    }

    .dashboard-search.active {
        display: flex;
    }

    .profile-name {
        display: none;
    }

    .profile-menu {
        min-width: 250px;
    }

    .notification-panel {
        min-width: 280px;
    }
}

/* Tablet Styles (640px - 1024px) */
@media (min-width: 640px) and (max-width: 1023px) {
    .logo-text {
        display: inline;
    }

    .profile-name {
        display: inline;
    }

    .dashboard-search {
        min-width: 250px;
    }
}

/* Desktop Styles (1024px+) */
@media (min-width: 1024px) {
    .hamburger-menu {
        display: none;
    }

    .logo-text {
        display: inline;
    }

    .profile-name {
        display: inline;
    }

    .dashboard-search {
        flex: 1;
        min-width: 300px;
    }

    .profile-btn {
        padding: 6px 12px 6px 6px;
    }

    .profile-name {
        margin: 0 8px;
    }
}

/* Print Styles */
@media print {
    .dashboard-header {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu toggle
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            const search = document.querySelector('.dashboard-search');
            if (search) search.classList.toggle('active');
        });
    }

    // Notification dropdown
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationPanel = document.getElementById('notificationPanel');
    const closeNotifications = document.getElementById('closeNotifications');

    if (notificationBtn && notificationPanel) {
        notificationBtn.addEventListener('click', function() {
            notificationPanel.style.display = notificationPanel.style.display === 'none' ? 'block' : 'none';
            loadNotifications();
        });

        closeNotifications?.addEventListener('click', () => {
            notificationPanel.style.display = 'none';
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!notificationBtn.contains(e.target) && !notificationPanel.contains(e.target)) {
                notificationPanel.style.display = 'none';
            }
        });
    }

    // Profile menu toggle
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'none' ? 'block' : 'none';
            profileBtn.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
                profileBtn.classList.remove('active');
            }
        });
    }

    // Student search
    const searchInput = document.getElementById('globalStudentSearch');
    const searchResults = document.getElementById('searchResults');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            fetch(`/includes/student_search.php?action=search_students&q=${encodeURIComponent(query)}`)
                .then(r => r.text())
                .then(t => JSON.parse(t.replace(/^\uFEFF/, '')))
                .then(data => {
                    if (data.success && data.students.length > 0) {
                        searchResults.innerHTML = data.students.map(s => `
                            <a href="/student-profile.php?id=${s.id}" class="search-result-item">
                                <div class="search-result-name">${s.first_name} ${s.surname}</div>
                                <div class="search-result-info">${s.index_number} • ${s.program || 'Unknown Program'}</div>
                            </a>
                        `).join('');
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<p class="empty-message">No students found</p>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(err => console.error('Search error:', err));
        });
    }
});

function loadNotifications() {
    fetch(`/includes/form_router.php?action=get_notifications`)
        .then(r => r.text())
        .then(t => JSON.parse(t.replace(/^\uFEFF/, '')))
        .then(data => {
            if (data.success) {
                const notificationList = document.getElementById('notificationList');
                const badge = document.getElementById('notificationBadge');

                if (data.count > 0) {
                    notificationList.innerHTML = data.notifications.map(n => `
                        <div class="notification-item ${n.is_read ? '' : 'unread'}">
                            <p class="notification-title">${n.title}</p>
                            <p class="notification-message">${n.message}</p>
                            <p class="notification-time">${new Date(n.created_at).toLocaleDateString()}</p>
                        </div>
                    `).join('');

                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                } else {
                    notificationList.innerHTML = '<p class="empty-message">No notifications</p>';
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Notification error:', err));
}
</script>
