// NCA-CTF Admin Core

(function() {
    'use strict';

    let currentUser = null;
    let isAuthorized = false;

    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminSidebarOverlay');
    const toggleBtn = document.getElementById('adminSidebarToggle');
    const userAvatar = document.getElementById('adminUserAvatar');
    const userName = document.getElementById('adminUserName');
    const userRole = document.getElementById('adminUserRole');
    const logoutBtn = document.getElementById('adminLogoutBtn');

    async function checkAuth() {
        try {
            const response = await fetch('/NCA-CTF/public/api/v1/auth/me', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = '/NCA-CTF/public/login.php';
                    return false;
                }
                throw new Error('Auth check failed');
            }

            const data = await response.json();

            if (!data.authenticated || !data.user) {
                window.location.href = '/NCA-CTF/public/login.php';
                return false;
            }

            currentUser = data.user;

            if (!['challenge_admin', 'super_admin'].includes(currentUser.role)) {
                showAccessDenied();
                return false;
            }

            isAuthorized = true;
            updateUserUI(currentUser);
            return true;

        } catch (error) {
            console.error('Auth error:', error);
            showAuthError();
            return false;
        }
    }

    function showAccessDenied() {
        const mainContent = document.querySelector('.admin-content');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="admin-error">
                    <div class="error-icon">🔒</div>
                    <div class="error-text">Access Denied</div>
                    <div class="error-hint">You do not have permission to access the admin area.</div>
                    <div class="error-actions">
                        <a href="/NCA-CTF/public/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                    </div>
                </div>
            `;
        }
    }

    function showAuthError() {
        const mainContent = document.querySelector('.admin-content');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="admin-error">
                    <div class="error-icon">⚠️</div>
                    <div class="error-text">Authentication Error</div>
                    <div class="error-hint">Unable to verify your credentials. Please try again.</div>
                    <div class="error-actions">
                        <button onclick="location.reload()" class="btn btn-primary">Retry</button>
                        <a href="/NCA-CTF/public/login.php" class="btn btn-outline">Login</a>
                    </div>
                </div>
            `;
        }
    }

    function updateUserUI(user) {
        if (!user) return;
        if (userAvatar) userAvatar.textContent = (user.username || 'U').charAt(0).toUpperCase();
        if (userName) userName.textContent = user.username || 'User';
        if (userRole) {
            userRole.textContent = user.role === 'super_admin' ? 'Super Admin' : 'Challenge Admin';
        }
    }

    function setupSidebar() {
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
    }

    function setupLogout() {
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                try {
                    await fetch('/NCA-CTF/public/api/v1/auth/logout', {
                        method: 'POST',
                        credentials: 'include'
                    });
                } catch (e) {}
                window.location.href = '/NCA-CTF/public/login.php';
            });
        }
    }

    window.Admin = {
        getCurrentUser: () => currentUser,
        isAuthorized: () => isAuthorized,
        checkAuth
    };

    async function init() {
        setupSidebar();
        setupLogout();
        const authorized = await checkAuth();
        document.dispatchEvent(new CustomEvent('admin:authReady', {
            detail: { authorized, user: currentUser }
        }));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();