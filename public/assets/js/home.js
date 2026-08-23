/**
 * NCA CTF – Homepage Behaviors
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';
    if (!BASE_URL) {
        BASE_URL = '/NCA-CTF/public';
        window.NCA_CTF_BASE_URL = BASE_URL;
    }

    var navToggle = document.getElementById('navToggle');
    var navMenu = document.getElementById('navMenu');
    var navActions = document.getElementById('navActions');

    if (!navActions) {
        console.warn('navActions element not found');
        return;
    }

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
                openChangePasswordModal();
            });
        }
    }

    // ---- Global Modal Functions ----
    window.openChangePasswordModal = function() {
        var modal = document.getElementById('changePasswordModal');
        if (!modal) {
            alert('Password changes are currently handled by NCA moderators.\n\nPlease contact a moderator through Discord for password assistance.');
            return;
        }
        modal.classList.add('active');
        
        // Reset form
        var form = document.getElementById('changePasswordForm');
        if (form) form.reset();
        
        document.querySelectorAll('#changePasswordForm .error-message').forEach(function(el) {
            el.textContent = '';
        });
        document.querySelectorAll('#changePasswordForm input.error').forEach(function(el) {
            el.classList.remove('error');
        });
        
        var message = document.getElementById('passwordFormMessage');
        if (message) {
            message.classList.remove('visible', 'error', 'success');
            message.textContent = '';
        }
        
        var submitBtn = document.getElementById('changePasswordSubmit');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.querySelector('.btn-text').textContent = 'Update Password';
            submitBtn.querySelector('.btn-loader').style.display = 'none';
        }
        
        var currentPassword = document.getElementById('currentPassword');
        if (currentPassword) currentPassword.focus();
    };

    window.closeChangePasswordModal = function() {
        var modal = document.getElementById('changePasswordModal');
        if (modal) modal.classList.remove('active');
    };

    // ---- Modal Close Events ----
    function setupModalEvents() {
        // Close button
        var closeBtn = document.getElementById('closeModalBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                window.closeChangePasswordModal();
            });
        }

        // Click outside modal
        var modalOverlay = document.getElementById('changePasswordModal');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    window.closeChangePasswordModal();
                }
            });
        }

        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
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
        loadAuthState();
        setupModalEvents();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();