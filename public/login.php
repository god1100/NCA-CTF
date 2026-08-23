<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
$from = isset($_GET['from']) ? $_GET['from'] : '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NCA CTF</title>
    <meta name="description" content="Log in to NCA Batch 4 CTF">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
        window.NCA_CTF_REDIRECT = '<?= htmlspecialchars($from) ?>';
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
                <h1>Welcome Back</h1>
                <p class="auth-sub">Log in to continue your CTF journey</p>
            </div>

            <form id="loginForm" novalidate>
                <div class="form-group">
                    <label for="identifier">
                        Username or Email <span class="required-star">*</span>
                    </label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        placeholder="Enter your username or email"
                        required
                        autocomplete="username"
                        autofocus
                    >
                    <span class="error-message" id="identifierError"></span>
                </div>

                <div class="form-group">
                    <label for="password">
                        Password <span class="required-star">*</span>
                    </label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
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
                </div>

                <div id="formMessage" class="form-message" role="alert" aria-live="polite"></div>

                <button type="submit" class="btn btn--primary btn--full" id="loginButton">
                    <span class="btn-text">Log In</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>

                <p class="auth-footer">
                    Don't have an account? <a href="<?= $baseUrl ?>/register.php">Create one</a>
                </p>
            </form>
        </div>
    </div>

    <script src="<?= $baseUrl ?>/assets/js/login.js"></script>
</body>
</html>