<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — NCA CTF</title>
    <meta name="description" content="Create your NCA Batch 4 CTF account">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
    </script>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?= $baseUrl ?>/" class="auth-logo">
                    <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" width="45" height="45">
                    <span>NCA <span class="brand__ctf">CTF</span></span>
                </a>
                <h1>Create Account</h1>
                <p class="auth-sub">Join the NCA Batch 4 CTF community</p>
            </div>

            <form id="registerForm" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Choose a username"
                        required
                        autocomplete="username"
                        autofocus
                    >
                    <span class="error-message" id="usernameError"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                        autocomplete="email"
                    >
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="optional">(Optional)</span></label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Your full name"
                        autocomplete="name"
                    >
                    <span class="error-message" id="fullNameError"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Min 10 characters"
                            required
                            autocomplete="new-password"
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Toggle password visibility"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                    <small class="hint-text">Must be at least 10 characters with a letter and number</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            placeholder="Confirm your password"
                            required
                            autocomplete="new-password"
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePasswordConfirm"
                            aria-label="Toggle password visibility"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="error-message" id="passwordConfirmError"></span>
                </div>

                <div id="formMessage" class="form-message" role="alert" aria-live="polite"></div>

                <button type="submit" class="btn btn--primary btn--full" id="registerButton">
                    <span class="btn-text">Create Account</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>

                <p class="auth-footer">
                    Already have an account? <a href="<?= $baseUrl ?>/login.php">Log in</a>
                </p>
            </form>
        </div>
    </div>

    <script src="<?= $baseUrl ?>/assets/js/register.js"></script>
</body>
</html>