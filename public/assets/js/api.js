/**
 * NCA CTF – API Helper
 * Uses index.php as the entry point for all API requests
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return BASE_URL + '/index.php/' + cleanPath;
    }

    // CSRF token stored in memory only (never localStorage/sessionStorage)
    var csrfToken = null;

    // Current authenticated user
    var currentUser = null;

    function getCsrfToken() {
        return csrfToken;
    }

    function setCsrfToken(token) {
        csrfToken = token;
    }

    function getCurrentUser() {
        return currentUser;
    }

    function setCurrentUser(user) {
        currentUser = user;
    }

    function clearAuthState() {
        csrfToken = null;
        currentUser = null;
    }

    async function request(method, endpoint, data, requiresAuth) {
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        // Add CSRF token for state-changing requests
        if (requiresAuth || method !== 'GET') {
            var token = getCsrfToken();
            if (token) {
                headers['X-CSRF-Token'] = token;
            }
        }

        var options = {
            method: method,
            credentials: 'include', // Required for session cookie
            headers: headers
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }

        var fullUrl = url(endpoint);

        try {
            var response = await fetch(fullUrl, options);
            var responseData = await response.json();

            // Handle CSRF token from response
            if (responseData.data && responseData.data.csrf_token) {
                setCsrfToken(responseData.data.csrf_token);
            }

            // Handle user from response
            if (responseData.data && responseData.data.user) {
                setCurrentUser(responseData.data.user);
            }

            // Handle 401 Unauthorized - clear auth state
            if (response.status === 401) {
                clearAuthState();
            }

            // Handle 419 CSRF token invalid
            if (response.status === 419) {
                clearAuthState();
            }

            return {
                status: response.status,
                ok: response.ok,
                data: responseData,
                success: responseData.success || false
            };

        } catch (error) {
            // Network error
            return {
                status: 0,
                ok: false,
                data: null,
                success: false,
                error: 'Network error. Please check your connection.'
            };
        }
    }

    // Public API methods
    window.NCA_API = {
        // Authentication
        register: function (data) {
            return request('POST', '/api/v1/auth/register', data, false);
        },
        login: function (data) {
            return request('POST', '/api/v1/auth/login', data, false);
        },
        logout: function () {
            return request('POST', '/api/v1/auth/logout', null, true);
        },
        me: function () {
            return request('GET', '/api/v1/auth/me', null, true);
        },

        // Getters
        getCsrfToken: getCsrfToken,
        getCurrentUser: getCurrentUser,
        setCsrfToken: setCsrfToken,
        setCurrentUser: setCurrentUser,
        clearAuthState: clearAuthState,
        isAuthenticated: function () {
            return currentUser !== null;
        },

        // Helper
        url: url
    };

    // Expose CSRF token getter for other scripts
    window.__nca_ctf = window.__nca_ctf || {};
    window.__nca_ctf.getCsrfToken = getCsrfToken;
    window.__nca_ctf.getCurrentUser = getCurrentUser;
    window.__nca_ctf.isAuthenticated = function () {
        return currentUser !== null;
    };

})();