/**
 * NCA CTF – Register Page
 */

(function () {
    'use strict';

    var BASE_URL = window.NCA_CTF_BASE_URL || '';

    function url(path) {
        var cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return BASE_URL + '/' + cleanPath;
    }

    var form = document.getElementById('registerForm');
    var usernameInput = document.getElementById('username');
    var emailInput = document.getElementById('email');
    var fullNameInput = document.getElementById('full_name');
    var passwordInput = document.getElementById('password');
    var passwordConfirmInput = document.getElementById('password_confirm');
    var formMessage = document.getElementById('formMessage');
    var registerButton = document.getElementById('registerButton');
    var togglePassword = document.getElementById('togglePassword');
    var togglePasswordConfirm = document.getElementById('togglePasswordConfirm');

    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

    if (togglePasswordConfirm) {
        togglePasswordConfirm.addEventListener('click', function () {
            var type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirmInput.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

    ['username', 'email', 'full_name', 'password', 'password_confirm'].forEach(function(id) {
        var input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function() {
                clearFieldError(id);
            });
        }
    });

    function clearFieldError(fieldId) {
        var errorEl = document.getElementById(fieldId + 'Error');
        var inputEl = document.getElementById(fieldId);
        if (errorEl) errorEl.textContent = '';
        if (inputEl) inputEl.classList.remove('error');
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
        registerButton.disabled = loading;
        var textSpan = registerButton.querySelector('.btn-text');
        var loaderSpan = registerButton.querySelector('.btn-loader');
        if (loading) {
            textSpan.textContent = 'Creating Account...';
            loaderSpan.style.display = 'inline-block';
        } else {
            textSpan.textContent = 'Create Account';
            loaderSpan.style.display = 'none';
        }
    }

    function validateForm() {
        var isValid = true;
        var username = usernameInput.value.trim();
        var email = emailInput.value.trim();
        var password = passwordInput.value;
        var passwordConfirm = passwordConfirmInput.value;

        // Username
        if (!username || username.length < 3) {
            document.getElementById('usernameError').textContent = 'Username must be at least 3 characters.';
            usernameInput.classList.add('error');
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            document.getElementById('usernameError').textContent = 'Username may only contain letters, numbers, and underscores.';
            usernameInput.classList.add('error');
            isValid = false;
        }

        // Email
        if (!email || !email.includes('@') || !email.includes('.')) {
            document.getElementById('emailError').textContent = 'A valid email address is required.';
            emailInput.classList.add('error');
            isValid = false;
        }

        // Password: Minimum 6 characters
        if (!password || password.length < 6) {
            document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
            passwordInput.classList.add('error');
            isValid = false;
        }

        // Password Confirm
        if (password !== passwordConfirm) {
            document.getElementById('passwordConfirmError').textContent = 'Passwords do not match.';
            passwordConfirmInput.classList.add('error');
            isValid = false;
        }

        return isValid;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideMessage();

        ['username', 'email', 'full_name', 'password', 'password_confirm'].forEach(function(id) {
            clearFieldError(id);
        });

        if (!validateForm()) {
            return;
        }

        if (registerButton.disabled) return;

        setLoading(true);

        var username = usernameInput.value.trim();
        var email = emailInput.value.trim();
        var fullName = fullNameInput.value.trim() || null;
        var password = passwordInput.value;

        try {
            var response = await fetch(url('/api/v1/auth/register'), {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    full_name: fullName,
                    password: password
                })
            });

            var data = await response.json();

            if (response.ok) {
                showMessage('Registration successful! Redirecting to login...', 'success');
                setTimeout(function () {
                    window.location.href = BASE_URL + '/login.php';
                }, 1500);
            } else if (response.status === 422) {
                var errors = data.message || data.errors || 'Registration failed.';
                if (Array.isArray(errors)) errors = errors.join(' ');
                showMessage(errors, 'error');
                setLoading(false);
            } else if (response.status === 429) {
                showMessage(data.message || 'Too many registration attempts. Please try again later.', 'error');
                setLoading(false);
            } else {
                showMessage(data.message || 'Registration failed. Please try again.', 'error');
                setLoading(false);
            }
        } catch (error) {
            showMessage('Network error. Please check your connection and try again.', 'error');
            setLoading(false);
        }
    });

})();