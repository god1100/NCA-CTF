/**
 * NCA CTF – Homepage Behaviors
 * Includes session restoration via /api/v1/auth/me
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    // If BASE_URL is empty, use a fallback
    if (!BASE_URL) {
        BASE_URL = '/NCA-CTF/public';
        window.NCA_CTF_BASE_URL = BASE_URL;
    }

    // Helper to build correct URLs
    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return BASE_URL + '/' + cleanPath;
    }

    var navToggle = document.getElementById('navToggle');
    var navMenu = document.getElementById('navMenu');
    var navActions = document.getElementById('navActions');

    // ---- Mobile menu toggle ----
    if (navToggle) {
        navToggle.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true' ? false : true;
            this.setAttribute('aria-expanded', expanded);
            navMenu.classList.toggle('open');
        });
    }

    // ---- Authentication state ----
    async function loadAuthState() {
        // Check if navActions exists
        if (!navActions) {
            console.warn('navActions element not found');
            return;
        }

        try {
            var result = await window.NCA_API.me();

            if (result.ok && result.success) {
                var user = window.NCA_API.getCurrentUser();
                if (user) {
                    setAuthenticatedNav(user);
                    return;
                }
            }

            setUnauthenticatedNav();

        } catch (_) {
            setUnauthenticatedNav();
        }
    }

    function setAuthenticatedNav(user) {
        navActions.innerHTML = `
            <div class="navbar__user">
                <button class="user-btn" id="userMenuBtn" aria-expanded="false" aria-label="User menu">
                    <i class="fas fa-user-circle"></i>
                    <span>${user.username || 'User'}</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown" role="menu">
                    <div class="dropdown-header">
                        <strong>${user.username || 'User'}</strong>
                        <span>${user.role || 'participant'}</span>
                    </div>
                    <div class="dropdown-divider"></div>
                   <a href="${BASE_URL}/dashboard" class="dropdown-item" role="menuitem">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-item-danger" id="logoutBtn" role="menuitem">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        `;

        // Add dropdown toggle
        var userMenuBtn = document.getElementById('userMenuBtn');
        var userDropdown = document.getElementById('userDropdown');

        if (userMenuBtn) {
            userMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var expanded = this.getAttribute('aria-expanded') === 'true' ? false : true;
                this.setAttribute('aria-expanded', expanded);
                if (userDropdown) userDropdown.classList.toggle('open');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
            if (userDropdown) userDropdown.classList.remove('open');
            if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'false');
        });

        // Logout
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function (e) {
                e.preventDefault();
                try {
                    var result = await window.NCA_API.logout();
                    window.NCA_API.clearAuthState();
                    window.location.href = BASE_URL + '/';
                } catch (_) {
                    window.NCA_API.clearAuthState();
                    window.location.href = BASE_URL + '/';
                }
            });
        }
    }

    function setUnauthenticatedNav() {
        navActions.innerHTML = `
            <a href="${BASE_URL}/login.php" class="btn btn--secondary">Login</a>
            <a href="${BASE_URL}/register.php" class="btn btn--primary">Register</a>
        `;
    }

    // ---- Init ----
    function init() {
        // Wait for API to be loaded
        if (typeof window.NCA_API === 'undefined') {
            console.warn('NCA_API not loaded, retrying...');
            setTimeout(init, 500);
            return;
        }
        loadAuthState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();