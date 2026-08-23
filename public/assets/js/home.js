/**
 * NCA CTF – Homepage Behaviors
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return BASE_URL + '/' + cleanPath;
    }

    var navToggle = document.getElementById('navToggle');
    var navMenu = document.getElementById('navMenu');
    var navActions = document.getElementById('navActions');

    if (navToggle) {
        navToggle.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true' ? false : true;
            this.setAttribute('aria-expanded', expanded);
            navMenu.classList.toggle('open');
        });
    }

    async function loadAuthState() {
        try {
            var response = await fetch(url('/api/v1/auth/me'), {
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                setUnauthenticatedNav();
                return;
            }

            var data = await response.json();
            if (data.data && data.data.user) {
                setAuthenticatedNav(data.data.user);
            } else {
                setUnauthenticatedNav();
            }
        } catch (_) {
            setUnauthenticatedNav();
        }
    }

    function setAuthenticatedNav(user) {
        navActions.innerHTML = `
            <a href="${BASE_URL}/dashboard.php" class="btn btn--secondary">Dashboard</a>
            <a href="#" class="btn btn--secondary" onclick="event.preventDefault(); logout();">Logout</a>
        `;
        window.logout = async function() {
            var csrfToken = window.__nca_ctf ? window.__nca_ctf.getCsrfToken() : null;
            try {
                await fetch(url('/api/v1/auth/logout'), {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken || ''
                    }
                });
                window.location.href = BASE_URL + '/';
            } catch (_) {
                window.location.href = BASE_URL + '/';
            }
        };
    }

    function setUnauthenticatedNav() {
        navActions.innerHTML = `
            <a href="${BASE_URL}/login.php" class="btn btn--secondary">Login</a>
            <a href="${BASE_URL}/register.php" class="btn btn--primary">Register</a>
        `;
    }

    function init() {
        loadAuthState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();