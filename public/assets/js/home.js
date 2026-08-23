/**
 * NCA CTF – Homepage Behaviors
 */

(function () {
    'use strict';

    // Get the base URL from the PHP-injected global variable
    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    // If BASE_URL is empty, try to detect it from the current page
    if (!BASE_URL) {
        var scripts = document.getElementsByTagName('script');
        var currentScript = scripts[scripts.length - 1];
        var scriptSrc = currentScript.src;
        // Extract the base path (everything up to /assets/js/)
        var match = scriptSrc.match(/^(.*?)\/assets\/js\//);
        if (match) {
            BASE_URL = match[1];
        } else {
            // Final fallback
            BASE_URL = '/NCA-CTF/public';
        }
        // Also set it on the window so other scripts can use it
        window.NCA_CTF_BASE_URL = BASE_URL;
    }

    // Helper function to build correct URLs
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
        // Define logout function globally so the onclick can call it
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

    // ---- Init ----
    function init() {
        loadAuthState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();