/**
 * NCA CTF – Login Page
 * Connects to backend API via api.js
 */

(function () {
    'use strict';

    // API helper is loaded via api.js
    var BASE_URL = window.NCA_CTF_BASE_URL || '';
    var REDIRECT_URL = window.NCA_CTF_REDIRECT || '/';

    // ---- DOM refs ----
    var form = document.getElementById('loginForm');
    var identifierInput = document.getElementById('identifier');
    var passwordInput = document.getElementById('password');
    var identifierError = document.getElementById('identifierError');
    var passwordError = document.getElementById('passwordError');
    var formMessage = document.getElementById('formMessage');
    var loginButton = document.getElementById('loginButton');
    var togglePassword = document.getElementById('togglePassword');

    // ---- Password visibility toggle ----
    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

    // ---- Clear errors on input ----
    identifierInput.addEventListener('input', function () {
        clearFieldError('identifier');
    });
    passwordInput.addEventListener('input', function () {
        clearFieldError('password');
    });

    function clearFieldError(field) {
        var errorEl = field === 'identifier' ? identifierError : passwordError;
        var inputEl = field === 'identifier' ? identifierInput : passwordInput;
        errorEl.textContent = '';
        inputEl.classList.remove('error');
        hideMessage();
    }

    function hideMessage() {
        formMessage.classList.remove('visible', 'error', 'success');
        formMessage.textContent = '';
    }

    function showMessage(message, type) {
        formMessage.textContent = message;
        formMessage.className = 'form-message visible ' + type;
    }

    function setLoading(loading) {
        loginButton.disabled = loading;
        var textSpan = loginButton.querySelector('.btn-text');
        var loaderSpan = loginButton.querySelector('.btn-loader');
        if (loading) {
            textSpan.textContent = 'Logging in...';
            loaderSpan.style.display = 'inline-block';
        } else {
            textSpan.textContent = 'Log In';
            loaderSpan.style.display = 'none';
        }
    }

    // ---- Client-side validation ----
    function validateForm() {
        var isValid = true;
        var identifier = identifierInput.value.trim();
        var password = passwordInput.value;

        if (!identifier) {
            identifierError.textContent = 'Username or email is required.';
            identifierInput.classList.add('error');
            isValid = false;
        }

        if (!password) {
            passwordError.textContent = 'Password is required.';
            passwordInput.classList.add('error');
            isValid = false;
        }

        return isValid;
    }

    // ---- Submit handler ----
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideMessage();
        clearFieldError('identifier');
        clearFieldError('password');

        if (!validateForm()) {
            return;
        }

        if (loginButton.disabled) return;

        setLoading(true);

        var identifier = identifierInput.value.trim();
        var password = passwordInput.value;

        try {
            var result = await window.NCA_API.login({
                identifier: identifier,
                password: password
            });

            if (result.ok && result.success) {
                showMessage('Login successful! Redirecting...', 'success');

                // Get user role from result or fetch separately
                var userRole = null;
                if (result.user && result.user.role) {
                    userRole = result.user.role;
                } else {
                    try {
                        var meResponse = await fetch('/NCA-CTF/public/api/v1/auth/me', {
                            method: 'GET',
                            credentials: 'include',
                            headers: { 'Accept': 'application/json' }
                        });
                        if (meResponse.ok) {
                            var meData = await meResponse.json();
                            if (meData.authenticated && meData.user) {
                                userRole = meData.user.role;
                            }
                        }
                    } catch (e) {
                        console.warn('Could not fetch user role:', e);
                    }
                }

                setTimeout(function () {
                    // Redirect based on role
                    if (userRole === 'challenge_admin' || userRole === 'super_admin') {
                        window.location.href = '/NCA-CTF/public/admin/index.php';
                    } else {
                        window.location.href = '/NCA-CTF/public/dashboard.php';
                    }
                }, 800);

            } else if (result.status === 401) {
                showMessage('Invalid username/email or password.', 'error');
                identifierInput.classList.add('error');
                passwordInput.classList.add('error');
                setLoading(false);

            } else if (result.status === 400) {
                var msg = result.data && result.data.message ? result.data.message : 'Identifier and password are required.';
                showMessage(msg, 'error');
                setLoading(false);

            } else if (result.status === 429) {
                var rateMsg = result.data && result.data.message ? result.data.message : 'Too many login attempts. Please try again later.';
                showMessage(rateMsg, 'error');
                setLoading(false);

            } else if (result.status === 419) {
                showMessage('Session expired. Please refresh the page and try again.', 'error');
                setLoading(false);

            } else if (result.status === 500) {
                showMessage('Something went wrong. Please try again later.', 'error');
                setLoading(false);

            } else {
                var errorMsg = result.data && result.data.message ? result.data.message : 'An unexpected error occurred. Please try again.';
                showMessage(errorMsg, 'error');
                setLoading(false);
            }

        } catch (error) {
            showMessage('Network error. Please check your connection and try again.', 'error');
            setLoading(false);
        }
    });

})();