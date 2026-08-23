/**
 * NCA CTF – Dashboard
 * Loads user data and handles authentication state
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    // ---- DOM refs ----
    var userMenuBtn = document.getElementById('userMenuBtn');
    var userDropdown = document.getElementById('userDropdown');
    var logoutBtn = document.getElementById('logoutBtn');
    var changePasswordBtn = document.getElementById('changePasswordBtn');

    var welcomeUsername = document.getElementById('welcomeUsername');
    var usernameDisplay = document.getElementById('usernameDisplay');
    var dropdownUsername = document.getElementById('dropdownUsername');
    var dropdownRole = document.getElementById('dropdownRole');

    var infoUsername = document.getElementById('infoUsername');
    var infoEmail = document.getElementById('infoEmail');
    var infoFullName = document.getElementById('infoFullName');
    var infoStatus = document.getElementById('infoStatus');
    var infoRole = document.getElementById('infoRole');
    var infoCreated = document.getElementById('infoCreated');
    var infoLastLogin = document.getElementById('infoLastLogin');

    // ---- User dropdown toggle ----
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var expanded = this.getAttribute('aria-expanded') === 'true' ? false : true;
            this.setAttribute('aria-expanded', expanded);
            userDropdown.classList.toggle('open');
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function () {
        userDropdown.classList.remove('open');
        if (userMenuBtn) {
            userMenuBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // ---- Load user data ----
    async function loadDashboard() {
        try {
            var result = await window.NCA_API.me();

            if (result.ok && result.success) {
                var user = window.NCA_API.getCurrentUser();

                if (user) {
                    displayUserData(user);
                    return;
                }

                // If user is null but response was ok, try to get from response
                if (result.data && result.data.data && result.data.data.user) {
                    displayUserData(result.data.data.user);
                    window.NCA_API.setCurrentUser(result.data.data.user);
                    return;
                }
            }

            // Not authenticated - redirect to login
            window.location.href = BASE_URL + '/login.php';

        } catch (error) {
            console.error('Failed to load dashboard:', error);
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
        var lastLoginAt = user.last_login_at || '—';

        // Format dates
        if (createdAt !== '—') {
            try {
                var date = new Date(createdAt);
                createdAt = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            } catch (e) {
                // Keep as is
            }
        }

        if (lastLoginAt !== '—' && lastLoginAt !== null) {
            try {
                var date2 = new Date(lastLoginAt);
                lastLoginAt = date2.toLocaleDateString() + ' ' + date2.toLocaleTimeString();
            } catch (e) {
                // Keep as is
            }
        } else {
            lastLoginAt = 'Never';
        }

        // Update navigation
        if (usernameDisplay) usernameDisplay.textContent = username;
        if (dropdownUsername) dropdownUsername.textContent = username;
        if (dropdownRole) dropdownRole.textContent = role;

        // Update welcome
        if (welcomeUsername) welcomeUsername.textContent = username;

        // Update account info
        if (infoUsername) infoUsername.textContent = username;
        if (infoEmail) infoEmail.textContent = email;
        if (infoFullName) infoFullName.textContent = fullName;
        if (infoStatus) infoStatus.textContent = status;
        if (infoRole) infoRole.textContent = role;
        if (infoCreated) infoCreated.textContent = createdAt;
        if (infoLastLogin) infoLastLogin.textContent = lastLoginAt;

        // Update page title
        document.title = 'Dashboard — ' + username + ' — NCA CTF';
    }

    // ---- Logout ----
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function (e) {
            e.preventDefault();

            try {
                var result = await window.NCA_API.logout();

                if (result.ok) {
                    window.NCA_API.clearAuthState();
                    window.location.href = BASE_URL + '/';
                } else {
                    // Even if logout fails, clear local state and redirect
                    window.NCA_API.clearAuthState();
                    window.location.href = BASE_URL + '/';
                }
            } catch (error) {
                window.NCA_API.clearAuthState();
                window.location.href = BASE_URL + '/';
            }
        });
    }

    // ---- Change Password (Coming Soon) ----
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function (e) {
            e.preventDefault();
            alert('Password changes are currently handled by NCA moderators.\n\nPlease contact a moderator through Discord for password assistance.');
        });
    }

    // ---- Init ----
    loadDashboard();

})();