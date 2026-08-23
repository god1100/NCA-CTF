/**
 * NCA CTF – API Helper
 * Uses index.php as the entry point for all API requests
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    // If BASE_URL is empty, try to detect it
    if (!BASE_URL) {
        // Get the current script's URL
        var scripts = document.getElementsByTagName('script');
        var currentScript = scripts[scripts.length - 1];
        var scriptSrc = currentScript.src;
        // Extract the base path (everything up to /assets/js/)
        var match = scriptSrc.match(/^(.*?)\/assets\/js\//);
        if (match) {
            BASE_URL = match[1];
        } else {
            BASE_URL = '/NCA-CTF/public';
        }
        window.NCA_CTF_BASE_URL = BASE_URL;
    }

    console.log('🔍 API.js: BASE_URL =', BASE_URL);

    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        // For API calls, we need to go through index.php
        return BASE_URL + '/index.php/' + cleanPath;
    }

    // CSRF token stored in memory only
    var csrfToken = null;
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
            credentials: 'include',
            headers: headers
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }

        var fullUrl = url(endpoint);
        console.log('🔍 API Request:', fullUrl);

        try {
            var response = await fetch(fullUrl, options);
            var text = await response.text();

            console.log('📦 API Response:', {
                status: response.status,
                body: text.substring(0, 200)
            });

            var responseData;
            try {
                if (text && text.trim() !== '') {
                    responseData = JSON.parse(text);
                } else {
                    responseData = { success: false, message: 'Empty response from server' };
                }
            } catch (parseError) {
                console.error('❌ Failed to parse JSON:', parseError);
                responseData = {
                    success: false,
                    message: 'Invalid response from server',
                    raw: text.substring(0, 500)
                };
            }

            // Handle CSRF token from response
            if (responseData.data && responseData.data.csrf_token) {
                setCsrfToken(responseData.data.csrf_token);
            }

            // Handle user from response
            if (responseData.data && responseData.data.user) {
                setCurrentUser(responseData.data.user);
            }

            // Handle 401 Unauthorized - clear auth state
            if (response.status === 401 || response.status === 419) {
                clearAuthState();
            }

            return {
                status: response.status,
                ok: response.ok,
                data: responseData,
                success: responseData.success || false
            };

        } catch (error) {
            console.error('❌ Network error:', error);
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
        register: function (data) {
            return request('POST', '/api/v1/auth/register', data, false);
        },
        login: function (data) {
            return request('POST', '/api/v1/auth/login', data, false);
        },
        logout: function () {
            return request('POST', '/api/v1/auth/logout', null, true);
        },
        changePassword: function (data) {
            return request('POST', '/api/v1/auth/change-password', data, true);
        },
        me: function () {
            return request('GET', '/api/v1/auth/me', null, true);
        },
        getCsrfToken: getCsrfToken,
        getCurrentUser: getCurrentUser,
        setCsrfToken: setCsrfToken,
        setCurrentUser: setCurrentUser,
        clearAuthState: clearAuthState,
        isAuthenticated: function () {
            return currentUser !== null;
        },
        url: url

    };

    window.__nca_ctf = window.__nca_ctf || {};
    window.__nca_ctf.getCsrfToken = getCsrfToken;
    window.__nca_ctf.getCurrentUser = getCurrentUser;

})();