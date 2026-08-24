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
            var type = passwordInput.getAttribute('type') === 'password'
                ? 'text'
                : 'password';

            passwordInput.setAttribute('type', type);

            this.querySelector('i').className =
                type === 'password'
                    ? 'fas fa-eye'
                    : 'fas fa-eye-slash';
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
        var errorEl = field === 'identifier'
            ? identifierError
            : passwordError;

        var inputEl = field === 'identifier'
            ? identifierInput
            : passwordInput;

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

        if (loginButton.disabled) {
            return;
        }

        setLoading(true);

        var identifier = identifierInput.value.trim();
        var password = passwordInput.value;

        try {
            var result = await window.NCA_API.login({
                identifier: identifier,
                password: password
            });

            if (result.ok && result.success) {
                showMessage(
                    'Login successful! Redirecting...',
                    'success'
                );

                var userRole = null;

                /*
                 * First try to get the role from the login response.
                 */
                if (
                    result.data &&
                    result.data.user &&
                    result.data.user.role
                ) {
                    userRole = result.data.user.role;
                }

                /*
                 * If login response does not contain the role,
                 * fetch the authenticated user from /auth/me.
                 */
                if (!userRole) {
                    try {
                        var meResponse = await window.NCA_API.me();

                        if (
                            meResponse.ok &&
                            meResponse.success &&
                            meResponse.data &&
                            meResponse.data.user
                        ) {
                            userRole = meResponse.data.user.role;
                        }
                    } catch (e) {
                        console.warn('Could not fetch authenticated user:', e);
                    }
                }

                /*
                 * Redirect based on authenticated role.
                 */
                setTimeout(function () {

                    if (
                        userRole === 'challenge_admin' ||
                        userRole === 'super_admin'
                    ) {
                        window.location.href =
                            BASE_URL + '/admin/index.php';

                        return;
                    }

                    window.location.href =
                        BASE_URL + '/dashboard.php';

                }, 800);
                showMessage(
                    'Login successful! Redirecting...',
                    'success'
                );

                /*
                 * IMPORTANT:
                 *
                 * api.js returns:
                 *
                 * result = {
                 *     status: ...,
                 *     ok: ...,
                 *     success: ...,
                 *     data: {
                 *         user: {
                 *             role: ...
                 *         }
                 *     }
                 * }
                 *
                 * Therefore the user role is:
                 *
                 * result.data.user.role
                 */

                var userRole = null;

                if (
                    result.data &&
                    result.data.user &&
                    result.data.user.role
                ) {
                    userRole = result.data.user.role;
                }

                /*
                 * Fallback:
                 * If the login response does not contain the user for
                 * some reason, ask the existing API for /auth/me.
                 *
                 * We use BASE_URL so the application does not depend
                 * on a hard-coded /NCA-CTF/public path.
                 */
                if (!userRole) {
                    try {
                        var meResponse = await window.NCA_API.me();

                        if (
                            meResponse.ok &&
                            meResponse.success &&
                            meResponse.data &&
                            meResponse.data.user
                        ) {
                            userRole = meResponse.data.user.role;
                        }
                    } catch (e) {
                        console.warn(
                            'Could not fetch user role:',
                            e
                        );
                    }
                }

                setTimeout(function () {

                    /*
                     * Admin users
                     */
                    if (
                        userRole === 'challenge_admin' ||
                        userRole === 'super_admin'
                    ) {
                        window.location.href =
                            BASE_URL + '/admin/index.php';

                        return;
                    }

                    /*
                     * Normal participants
                     */
                    window.location.href =
                        BASE_URL + '/dashboard.php';

                }, 800);

            } else if (result.status === 401) {

                showMessage(
                    'Invalid username/email or password.',
                    'error'
                );

                identifierInput.classList.add('error');
                passwordInput.classList.add('error');

                setLoading(false);

            } else if (result.status === 400) {

                var msg =
                    result.data && result.data.message
                        ? result.data.message
                        : 'Identifier and password are required.';

                showMessage(msg, 'error');
                setLoading(false);

            } else if (result.status === 429) {

                var rateMsg =
                    result.data && result.data.message
                        ? result.data.message
                        : 'Too many login attempts. Please try again later.';

                showMessage(rateMsg, 'error');
                setLoading(false);

            } else if (result.status === 419) {

                showMessage(
                    'Session expired. Please refresh the page and try again.',
                    'error'
                );

                setLoading(false);

            } else if (result.status === 500) {

                showMessage(
                    'Something went wrong. Please try again later.',
                    'error'
                );

                setLoading(false);

            } else {

                var errorMsg =
                    result.data && result.data.message
                        ? result.data.message
                        : 'An unexpected error occurred. Please try again.';

                showMessage(errorMsg, 'error');
                setLoading(false);
            }

        } catch (error) {

            console.error('Login error:', error);

            showMessage(
                'Network error. Please check your connection and try again.',
                'error'
            );

            setLoading(false);
        }
    });

})();