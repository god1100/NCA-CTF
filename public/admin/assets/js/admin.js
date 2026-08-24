// NCA-CTF Admin Core

(function () {
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

    function getBaseUrl() {
        return window.NCA_CTF_BASE_URL || '/NCA-CTF/public';
    }
    async function checkAuth() {
        try {
            const response = await fetch(
                getBaseUrl() + '/api/v1/auth/me',
                {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (response.status === 401) {
                window.location.href = getBaseUrl() + '/login.php';
                return false;
            }

            if (!response.ok) {
                throw new Error('Authentication request failed.');
            }

            const data = await response.json();

            /*
             * Backend response:
             *
             * {
             *     success: true,
             *     data: {
             *         user: {
             *             id,
             *             username,
             *             email,
             *             role,
             *             status,
             *             ...
             *         },
             *         csrf_token: "..."
             *     },
             *     message: "Operation successful"
             * }
             */

            if (
                !data.success ||
                !data.data ||
                !data.data.user
            ) {
                window.location.href = getBaseUrl() + '/login.php';
                return false;
            }

            currentUser = data.data.user;

            /*
             * Admin roles
             */
            if (
                currentUser.role !== 'challenge_admin' &&
                currentUser.role !== 'super_admin'
            ) {
                showAccessDenied();
                return false;
            }

            isAuthorized = true;

            updateUserUI(currentUser);

            return true;

        } catch (error) {
            console.error('Admin authentication error:', error);
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

                    <div class="error-text">
                        Access Denied
                    </div>

                    <div class="error-hint">
                        You do not have permission to access the NCA-CTF administration area.
                    </div>

                    <div class="error-actions">
                        <a
                            href="${getBaseUrl()}/dashboard.php"
                            class="btn btn-primary"
                        >
                            Return to Dashboard
                        </a>
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

                    <div class="error-text">
                        Authentication Error
                    </div>

                    <div class="error-hint">
                        Unable to verify your administrator session.
                    </div>

                    <div class="error-actions">
                        <button
                            onclick="location.reload()"
                            class="btn btn-primary"
                        >
                            Retry
                        </button>

                        <a
                            href="${getBaseUrl()}/login.php"
                            class="btn btn-outline"
                        >
                            Login
                        </a>
                    </div>
                </div>
            `;
        }
    }

    function updateUserUI(user) {
        if (!user) {
            return;
        }

        if (userAvatar) {
            userAvatar.textContent =
                (user.username || 'U').charAt(0).toUpperCase();
        }

        if (userName) {
            userName.textContent = user.username || 'User';
        }

        if (userRole) {
            userRole.textContent =
                user.role === 'super_admin'
                    ? 'Super Admin'
                    : 'Challenge Admin';
        }
    }

    function setupSidebar() {
        if (toggleBtn && sidebar && overlay) {
            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();

                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }

        if (overlay && sidebar) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        document.addEventListener('keydown', function (e) {
            if (
                e.key === 'Escape' &&
                sidebar &&
                sidebar.classList.contains('open')
            ) {
                sidebar.classList.remove('open');

                if (overlay) {
                    overlay.classList.remove('active');
                }
            }
        });
    }

    function setupLogout() {
        if (!logoutBtn) {
            return;
        }

        logoutBtn.addEventListener('click', async function (e) {
            e.preventDefault();

            try {
                await fetch(
                    getBaseUrl() + '/index.php/api/v1/auth/logout',
                    {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );
            } catch (error) {
                console.warn('Logout request failed:', error);
            }

            window.location.href =
                getBaseUrl() + '/login.php';
        });
    }

    window.Admin = {
        getCurrentUser: function () {
            return currentUser;
        },

        isAuthorized: function () {
            return isAuthorized;
        },

        checkAuth: checkAuth
    };

    async function init() {
        setupSidebar();
        setupLogout();

        const authorized = await checkAuth();

        document.dispatchEvent(
            new CustomEvent('admin:authReady', {
                detail: {
                    authorized: authorized,
                    user: currentUser
                }
            })
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            init
        );
    } else {
        init();
    }

})();