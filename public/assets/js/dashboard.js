/**
 * NCA CTF – Dashboard
 * Loads user data and handles authentication state
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';
    if (!BASE_URL) {
        BASE_URL = '/NCA-CTF/public';
        window.NCA_CTF_BASE_URL = BASE_URL;
    }

    // ---- DOM refs ----
    var navActions = document.getElementById('navActions');
    var welcomeUsername = document.getElementById('welcomeUsername');

    var infoUsername = document.getElementById('infoUsername');
    var infoEmail = document.getElementById('infoEmail');
    var infoFullName = document.getElementById('infoFullName');
    var infoStatus = document.getElementById('infoStatus');
    var infoRole = document.getElementById('infoRole');
    var infoCreated = document.getElementById('infoCreated');

    // ---- Render user dropdown in navigation ----
    function setAuthenticatedNav(user) {
        if (!navActions) return;
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
                    <a href="${BASE_URL}/dashboard.php" class="dropdown-item" role="menuitem">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="#" class="dropdown-item" id="changePasswordBtn" role="menuitem">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-item-danger" id="logoutBtn" role="menuitem">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        `;

        setupUserDropdown();
        setupLogout();
        setupChangePassword();
    }

    function setUnauthenticatedNav() {
        if (!navActions) return;
        navActions.innerHTML = `
            <a href="${BASE_URL}/login.php" class="btn btn--secondary">Login</a>
            <a href="${BASE_URL}/register.php" class="btn btn--primary">Register</a>
        `;
    }

    function setupUserDropdown() {
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

        document.addEventListener('click', function () {
            if (userDropdown) userDropdown.classList.remove('open');
            if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'false');
        });
    }

    function setupLogout() {
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function (e) {
                e.preventDefault();
                try {
                    await window.NCA_API.logout();
                    window.NCA_API.clearAuthState();
                    window.location.href = BASE_URL + '/';
                } catch (_) {
                    window.location.href = BASE_URL + '/';
                }
            });
        }
    }

    // ---- Change Password - Open Modal ----
    function setupChangePassword() {
        var changePasswordBtn = document.getElementById('changePasswordBtn');
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', function (e) {
                e.preventDefault();
                // Use the global modal function from home.js
                if (typeof window.openChangePasswordModal === 'function') {
                    window.openChangePasswordModal();
                } else {
                    alert('Password changes are currently handled by NCA moderators.\n\nPlease contact a moderator through Discord for password assistance.');
                }
            });
        }
    }

    // ---- Load user data ----
    async function loadDashboard() {
        try {
            var result = await window.NCA_API.me();

            if (result.ok && result.success) {
                var user = window.NCA_API.getCurrentUser();

                if (user) {
                    displayUserData(user);
                    setAuthenticatedNav(user);
                    return;
                }

                if (result.data && result.data.data && result.data.data.user) {
                    var userData = result.data.data.user;
                    displayUserData(userData);
                    window.NCA_API.setCurrentUser(userData);
                    setAuthenticatedNav(userData);
                    return;
                }

                if (result.data && result.data.user) {
                    var userData2 = result.data.user;
                    displayUserData(userData2);
                    window.NCA_API.setCurrentUser(userData2);
                    setAuthenticatedNav(userData2);
                    return;
                }
            }

            setUnauthenticatedNav();
            window.location.href = BASE_URL + '/login.php';

        } catch (error) {
            console.error('Failed to load dashboard:', error);
            setUnauthenticatedNav();
            window.location.href = BASE_URL + '/login.php';
        }
    }

    function displayUserData(user) {
        var username = user.username || 'User';
        var email = user.email || '—';
        var fullName = user.full_name || '—';
        var status = user.status || '—';
        var role = user.role || 'participant';
        var createdAt = user.created_at || '—';

        // Format date
        if (createdAt !== '—') {
            try {
                var date = new Date(createdAt);
                createdAt = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            } catch (e) {
                // Keep as is
            }
        }

        if (welcomeUsername) welcomeUsername.textContent = username;
        if (infoUsername) infoUsername.textContent = username;
        if (infoEmail) infoEmail.textContent = email;
        if (infoFullName) infoFullName.textContent = fullName;
        if (infoStatus) infoStatus.textContent = status;
        if (infoRole) infoRole.textContent = role;
        if (infoCreated) infoCreated.textContent = createdAt;

        document.title = 'Dashboard — ' + username + ' — NCA CTF';
    }

    // ---- Modal Events ----
    function setupModalEvents() {
        // Close button
        var closeBtn = document.getElementById('closeModalBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                if (typeof window.closeChangePasswordModal === 'function') {
                    window.closeChangePasswordModal();
                }
            });
        }

        // Click outside modal
        var modalOverlay = document.getElementById('changePasswordModal');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay && typeof window.closeChangePasswordModal === 'function') {
                    window.closeChangePasswordModal();
                }
            });
        }

        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && typeof window.closeChangePasswordModal === 'function') {
                window.closeChangePasswordModal();
            }
        });
    }

    // ---- Init ----
    function init() {
        if (typeof window.NCA_API === 'undefined') {
            setTimeout(init, 200);
            return;
        }
        loadDashboard();
        setupModalEvents();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();