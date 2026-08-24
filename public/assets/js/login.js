/**
 * NCA CTF – Login Page
 * Connects to backend API via api.js
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '/NCA-CTF/public';
    var REDIRECT_URL = window.NCA_CTF_REDIRECT || '/dashboard.php';

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

            var type =
                passwordInput.getAttribute('type') === 'password'
                    ? 'text'
                    : 'password';

            passwordInput.setAttribute('type', type);

            var icon = this.querySelector('i');

            if (icon) {
                icon.className =
                    type === 'password'
                        ? 'fas fa-eye'
                        : 'fas fa-eye-slash';
            }

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

        var errorEl =
            field === 'identifier'
                ? identifierError
                : passwordError;

        var inputEl =
            field === 'identifier'
                ? identifierInput
                : passwordInput;

        errorEl.textContent = '';
        inputEl.classList.remove('error');

        hideMessage();
    }


    function hideMessage() {

        formMessage.classList.remove(
            'visible',
            'error',
            'success'
        );

        formMessage.textContent = '';
    }


    function showMessage(message, type) {

        formMessage.textContent = message;

        formMessage.className =
            'form-message visible ' + type;
    }


    function setLoading(loading) {

        loginButton.disabled = loading;

        var textSpan =
            loginButton.querySelector('.btn-text');

        var loaderSpan =
            loginButton.querySelector('.btn-loader');

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

        var identifier =
            identifierInput.value.trim();

        var password =
            passwordInput.value;

        if (!identifier) {

            identifierError.textContent =
                'Username or email is required.';

            identifierInput.classList.add('error');

            isValid = false;
        }

        if (!password) {

            passwordError.textContent =
                'Password is required.';

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

        var identifier =
            identifierInput.value.trim();

        var password =
            passwordInput.value;


        try {

            // --------------------------------------------------
            // LOGIN
            // --------------------------------------------------

            var result =
                await window.NCA_API.login({
                    identifier: identifier,
                    password: password
                });


            console.log('🔐 Login result:', result);


            if (result.ok && result.success) {

                showMessage(
                    'Login successful! Redirecting...',
                    'success'
                );


                // --------------------------------------------------
                // IMPORTANT
                //
                // api.js returns:
                //
                // result
                //   └── data
                //       ├── success
                //       ├── data
                //       │   └── user
                //       │       └── role
                //       └── message
                //
                // Therefore:
                //
                // result.data.data.user.role
                // --------------------------------------------------

                var userRole = null;


                // First try the login response

                if (
                    result.data &&
                    result.data.data &&
                    result.data.data.user &&
                    result.data.data.user.role
                ) {

                    userRole =
                        result.data.data.user.role;

                }


                console.log(
                    '👤 Role from login response:',
                    userRole
                );


                // --------------------------------------------------
                // If role was not included in login response,
                // fetch /auth/me
                // --------------------------------------------------

                if (!userRole) {

                    try {

                        var meResponse =
                            await window.NCA_API.me();

                        console.log(
                            '👤 /auth/me response:',
                            meResponse
                        );


                        if (
                            meResponse.ok &&
                            meResponse.success &&
                            meResponse.data &&
                            meResponse.data.data &&
                            meResponse.data.data.user
                        ) {

                            userRole =
                                meResponse.data.data.user.role;

                        }

                    } catch (error) {

                        console.warn(
                            'Could not fetch authenticated user:',
                            error
                        );

                    }

                }


                console.log(
                    '🎯 FINAL USER ROLE:',
                    userRole
                );


                // --------------------------------------------------
                // REDIRECT
                // --------------------------------------------------

                setTimeout(function () {

                    // Admin users

                    if (
                        userRole === 'challenge_admin' ||
                        userRole === 'super_admin'
                    ) {

                        console.log(
                            '🔐 Admin detected → Admin Dashboard'
                        );

                        window.location.href =
                            BASE_URL + '/admin/index.php';

                        return;
                    }


                    // Normal users

                    console.log(
                        '👤 Normal user detected → User Dashboard'
                    );

                    window.location.href =
                        BASE_URL + '/dashboard.php';

                }, 800);


                return;
            }


            // --------------------------------------------------
            // LOGIN ERRORS
            // --------------------------------------------------

            if (result.status === 401) {

                showMessage(
                    'Invalid username/email or password.',
                    'error'
                );

                identifierInput.classList.add('error');
                passwordInput.classList.add('error');

                setLoading(false);

                return;
            }


            if (result.status === 400) {

                var msg =
                    result.data &&
                    result.data.message
                        ? result.data.message
                        : 'Identifier and password are required.';

                showMessage(msg, 'error');

                setLoading(false);

                return;
            }


            if (result.status === 429) {

                var rateMsg =
                    result.data &&
                    result.data.message
                        ? result.data.message
                        : 'Too many login attempts. Please try again later.';

                showMessage(rateMsg, 'error');

                setLoading(false);

                return;
            }


            if (result.status === 419) {

                showMessage(
                    'Session expired. Please refresh the page and try again.',
                    'error'
                );

                setLoading(false);

                return;
            }


            if (result.status === 500) {

                showMessage(
                    'Something went wrong. Please try again later.',
                    'error'
                );

                setLoading(false);

                return;
            }


            // Unknown error

            var errorMsg =
                result.data &&
                result.data.message
                    ? result.data.message
                    : 'An unexpected error occurred. Please try again.';

            showMessage(
                errorMsg,
                'error'
            );

            setLoading(false);


        } catch (error) {

            console.error(
                'Login error:',
                error
            );

            showMessage(
                'Network error. Please check your connection and try again.',
                'error'
            );

            setLoading(false);

        }

    });

})();