/**
 * NCA CTF – Login Page
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return BASE_URL + '/' + cleanPath;
    }

    var form = document.getElementById('loginForm');
    var identifierInput = document.getElementById('identifier');
    var passwordInput = document.getElementById('password');
    var identifierError = document.getElementById('identifierError');
    var passwordError = document.getElementById('passwordError');
    var formMessage = document.getElementById('formMessage');
    var loginButton = document.getElementById('loginButton');
    var togglePassword = document.getElementById('togglePassword');

    var csrfToken = null;

    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

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
            var response = await fetch(url('/api/v1/auth/login'), {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ identifier: identifier, password: password })
            });

            var data = await response.json();

            if (response.ok) {
                if (data.success && data.data) {
                    csrfToken = data.data.csrf_token || null;
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(function () {
                        window.location.href = BASE_URL + '/';
                    }, 800);
                } else {
                    showMessage('Unexpected response from server.', 'error');
                    setLoading(false);
                }
            } else if (response.status === 400) {
                showMessage(data.message || 'Identifier and password are required.', 'error');
                setLoading(false);
            } else if (response.status === 401) {
                showMessage(data.message || 'Invalid username/email or password.', 'error');
                identifierInput.classList.add('error');
                passwordInput.classList.add('error');
                setLoading(false);
            } else if (response.status === 429) {
                showMessage(data.message || 'Too many login attempts. Please try again later.', 'error');
                setLoading(false);
            } else {
                showMessage(data.message || 'An unexpected error occurred. Please try again.', 'error');
                setLoading(false);
            }
        } catch (error) {
            showMessage('Network error. Please check your connection and try again.', 'error');
            setLoading(false);
        }
    });

    window.__nca_ctf = window.__nca_ctf || {};
    window.__nca_ctf.getCsrfToken = function () {
        return csrfToken;
    };

})();