/**
 * ISNM Dashboard Theme Switcher
 * 5 color themes stored in localStorage
 */
(function() {
    'use strict';

    const THEMES = {
        'default-blue': {
            name: 'Default Blue',
            colors: {
                sidebarBg: '#0f172a',
                sidebarHover: '#1e293b',
                sidebarActive: '#2563eb',
                sidebarAccent: '#3b82f6',
                headerBg: '#ffffff',
                primary: '#1a237e',
                accent: '#2563eb'
            }
        },
        'emerald-green': {
            name: 'Emerald Green',
            colors: {
                sidebarBg: '#064e3b',
                sidebarHover: '#065f46',
                sidebarActive: '#059669',
                sidebarAccent: '#10b981',
                headerBg: '#f0fdf4',
                primary: '#065f46',
                accent: '#059669'
            }
        },
        'royal-purple': {
            name: 'Royal Purple',
            colors: {
                sidebarBg: '#1e1b4b',
                sidebarHover: '#2e1065',
                sidebarActive: '#7c3aed',
                sidebarAccent: '#8b5cf6',
                headerBg: '#f5f3ff',
                primary: '#4c1d95',
                accent: '#7c3aed'
            }
        },
        'sunset-orange': {
            name: 'Sunset Orange',
            colors: {
                sidebarBg: '#1c1917',
                sidebarHover: '#292524',
                sidebarActive: '#ea580c',
                sidebarAccent: '#f97316',
                headerBg: '#fff7ed',
                primary: '#9a3412',
                accent: '#ea580c'
            }
        },
        'ocean-teal': {
            name: 'Ocean Teal',
            colors: {
                sidebarBg: '#0f3b3f',
                sidebarHover: '#164e63',
                sidebarActive: '#0891b2',
                sidebarAccent: '#06b6d4',
                headerBg: '#ecfeff',
                primary: '#155e75',
                accent: '#0891b2'
            }
        }
    };

    const STORAGE_KEY = 'isnm_dashboard_theme';

    function getTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || 'default-blue';
        } catch (e) {
            return 'default-blue';
        }
    }

    function applyTheme(themeId) {
        if (!THEMES[themeId]) themeId = 'default-blue';
        const theme = THEMES[themeId];
        const root = document.documentElement;

        root.style.setProperty('--theme-sidebar-bg', theme.colors.sidebarBg);
        root.style.setProperty('--theme-sidebar-hover', theme.colors.sidebarHover);
        root.style.setProperty('--theme-sidebar-active', theme.colors.sidebarActive);
        root.style.setProperty('--theme-sidebar-accent', theme.colors.sidebarAccent);
        root.style.setProperty('--theme-header-bg', theme.colors.headerBg);
        root.style.setProperty('--theme-primary', theme.colors.primary);
        root.style.setProperty('--theme-accent', theme.colors.accent);

        // Also set sidebar-specific vars
        const sidebar = document.querySelector('.isnm-sidebar');
        if (sidebar) {
            sidebar.style.setProperty('--sidebar-bg', theme.colors.sidebarBg);
            sidebar.style.setProperty('--sidebar-hover', theme.colors.sidebarHover);
            sidebar.style.setProperty('--sidebar-active', theme.colors.sidebarActive);
            sidebar.style.setProperty('--sidebar-accent', theme.colors.sidebarAccent);
        }

        // Update meta theme-color
        const metaTheme = document.querySelector('meta[name="theme-color"]');
        if (metaTheme) metaTheme.content = theme.colors.sidebarBg;

        // Highlight active theme in picker
        document.querySelectorAll('.theme-picker-option').forEach(function(el) {
            el.classList.toggle('active', el.dataset.theme === themeId);
        });

        document.querySelectorAll('.theme-picker-name').forEach(function(el) {
            el.textContent = THEMES[getTheme()].name;
        });
        document.querySelectorAll('.theme-current-name').forEach(function(el) {
            el.textContent = THEMES[getTheme()].name;
        });

        try {
            localStorage.setItem(STORAGE_KEY, themeId);
        } catch (e) {}
    }

    function initThemePicker() {
        var pickerHtml = '';
        pickerHtml += '<div class="theme-picker-dropdown">';
        for (var id in THEMES) {
            var t = THEMES[id];
            var isActive = id === getTheme() ? ' active' : '';
            pickerHtml += '<div class="theme-picker-option' + isActive + '" data-theme="' + id + '" title="' + t.name + '">';
            pickerHtml += '<span class="theme-swatch" style="background:' + t.colors.sidebarBg + '"></span>';
            pickerHtml += '<span class="theme-label">' + t.name + '</span>';
            pickerHtml += '<span class="theme-check"><i class="fas fa-check"></i></span>';
            pickerHtml += '</div>';
        }
        pickerHtml += '</div>';
        return pickerHtml;
    }

    function setupTrigger() {
        // Find settings trigger in sidebar
        var settingsTrigger = document.querySelector('.settings-trigger');
        var themeTrigger = document.querySelector('.theme-trigger');
        if (!themeTrigger) return;

        themeTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            var dropdown = document.querySelector('.theme-picker-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            } else {
                // First click: create and show
                var container = document.createElement('div');
                container.innerHTML = initThemePicker();
                this.parentNode.appendChild(container.firstElementChild);
                document.querySelector('.theme-picker-dropdown').classList.add('show');
                attachPickerEvents();
            }
        });

        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.theme-trigger') && !e.target.closest('.theme-picker-dropdown')) {
                var dd = document.querySelector('.theme-picker-dropdown');
                if (dd) dd.classList.remove('show');
            }
        });
    }

    function attachPickerEvents() {
        document.querySelectorAll('.theme-picker-option').forEach(function(el) {
            el.addEventListener('click', function() {
                var themeId = this.dataset.theme;
                applyTheme(themeId);
                var dd = document.querySelector('.theme-picker-dropdown');
                if (dd) dd.classList.remove('show');
            });
        });
    }

    // ── Theme change callback (for persistence) ──
    var themeChangeCallbacks = [];
    function onThemeChange(cb) {
        themeChangeCallbacks.push(cb);
    }
    function notifyThemeChange(themeId) {
        themeChangeCallbacks.forEach(function(cb) { try { cb(themeId); } catch(e) { console.warn('[ISNM] Theme callback error:', e); } });
    }

    // ── Build theme option HTML ──
    function buildThemeOptions(selectedId) {
        var html = '';
        for (var id in THEMES) {
            var t = THEMES[id];
            var isActive = id === selectedId ? ' active' : '';
            html += '<div class="theme-option' + isActive + '" data-theme="' + id + '">';
            html += '<div class="theme-preview">';
            html += '<div class="theme-preview-sidebar" style="background:' + t.colors.sidebarBg + '"></div>';
            html += '<div class="theme-preview-main">';
            html += '<div class="theme-preview-header" style="background:' + t.colors.headerBg + '"></div>';
            html += '<div class="theme-preview-content"><div class="theme-preview-accent" style="background:' + t.colors.accent + '"></div></div>';
            html += '</div>';
            html += '</div>';
            html += '<div class="theme-option-info">';
            html += '<span class="theme-option-name">' + t.name + '</span>';
            html += '<span class="theme-option-check"><i class="fas fa-check-circle"></i></span>';
            html += '</div>';
            html += '</div>';
        }
        return html;
    }

    // ── Open Theme Modal ──
    window.openThemeModal = function() {
        // Remove existing modal if any
        var existing = document.getElementById('themeModal');
        if (existing) existing.remove();

        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'themeModal';
        modal.tabIndex = -1;
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML =
            '<div class="modal-dialog modal-dialog-centered modal-sm">' +
            '<div class="modal-content theme-modal-content">' +
            '<div class="modal-header theme-modal-header">' +
            '<h5 class="mb-0 fw-bold"><i class="fas fa-palette me-2"></i>Choose Theme</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '</div>' +
            '<div class="modal-body theme-modal-body">' +
            '<p class="text-muted small mb-3">Select a color theme for your dashboard</p>' +
            '<div class="theme-options-grid">' + buildThemeOptions(getTheme()) + '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        document.body.appendChild(modal);

        // Attach events
        modal.querySelectorAll('.theme-option').forEach(function(el) {
            el.addEventListener('click', function() {
                var themeId = this.dataset.theme;
                applyTheme(themeId);
                modal.querySelectorAll('.theme-option').forEach(function(o) {
                    o.classList.toggle('active', o.dataset.theme === themeId);
                });
                document.querySelectorAll('.theme-current-name').forEach(function(el) {
                    el.textContent = THEMES[themeId].name;
                });
                notifyThemeChange(themeId);
                bsModal.hide();
            });
        });

        var bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Cleanup on hidden
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    };

    // ── Init ──
    function init() {
        applyTheme(getTheme());
        setupTrigger();
        // Re-apply after DOM is fully loaded
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            applyTheme(getTheme());
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                applyTheme(getTheme());
                attachPickerEvents();
            });
        }
    }

    // Try to run immediately and also after full load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for debugging
    window.dashboardTheme = { applyTheme: applyTheme, getTheme: getTheme, THEMES: THEMES, onThemeChange: onThemeChange };
})();
