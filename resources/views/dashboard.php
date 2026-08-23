<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — NCA CTF</title>
    <meta name="description" content="NCA Batch 4 CTF Dashboard">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
    </script>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
</head>
<body>

    <!-- ============================================================== -->
    <!-- NAVIGATION                                                     -->
    <!-- ============================================================== -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="navbar__container">
            <div class="navbar__brand">
                <a href="<?= $baseUrl ?>/" class="navbar__logo-link">
                    <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" class="navbar__logo" width="40" height="40">
                    <span class="navbar__brand-text">NCA <span class="brand__ctf">CTF</span></span>
                </a>
            </div>

            <button class="navbar__toggle" id="navToggle" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar__toggle-icon"></span>
                <span class="navbar__toggle-icon"></span>
                <span class="navbar__toggle-icon"></span>
            </button>

            <div class="navbar__menu" id="navMenu">
                <ul class="navbar__links">
                    <li><a href="<?= $baseUrl ?>/challenges.php" class="active">Challenges</a></li>
                    <li><a href="<?= $baseUrl ?>/categories.php">Categories</a></li>
                    <li><a href="<?= $baseUrl ?>/leaderboard.php">Leaderboard</a></li>
                </ul>
                <div class="navbar__user" id="navUser">
                    <button class="user-btn" id="userMenuBtn" aria-expanded="false" aria-label="User menu">
                        <i class="fas fa-user-circle"></i>
                        <span id="usernameDisplay">Loading...</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown" role="menu">
                        <div class="dropdown-header">
                            <strong id="dropdownUsername">Loading...</strong>
                            <span id="dropdownRole">participant</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $baseUrl ?>/dashboard" class="dropdown-item" role="menuitem">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="#" class="dropdown-item" id="changePasswordBtn" role="menuitem">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item dropdown-item-danger" id="logoutBtn" role="menuitem">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============================================================== -->
    <!-- DASHBOARD CONTENT                                              -->
    <!-- ============================================================== -->
    <main class="dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <div class="dashboard__welcome">
                <div>
                    <h1 class="dashboard__title" id="dashboardTitle">Welcome back, <span id="welcomeUsername">User</span></h1>
                    <p class="dashboard__subtitle">Ready to continue the challenge?</p>
                </div>
                <a href="<?= $baseUrl ?>/challenges.php" class="btn btn--primary btn--large">
                    <i class="fas fa-flag"></i> Continue Challenges
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="dashboard__stats">
                <div class="stat-card">
                    <div class="stat-card__icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-card__content">
                        <span class="stat-card__label">Score</span>
                        <span class="stat-card__value">—</span>
                        <span class="stat-card__status">Coming Soon</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-card__content">
                        <span class="stat-card__label">Solved</span>
                        <span class="stat-card__value">—</span>
                        <span class="stat-card__status">Coming Soon</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card__icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <div class="stat-card__content">
                        <span class="stat-card__label">Rank</span>
                        <span class="stat-card__value">—</span>
                        <span class="stat-card__status">Coming Soon</span>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="dashboard__grid">
                <!-- Account Information -->
                <div class="dashboard__card">
                    <div class="card-header">
                        <h2><i class="fas fa-user"></i> Account</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value" id="infoUsername">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value" id="infoEmail">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value" id="infoFullName">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value" id="infoStatus">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value" id="infoRole">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since</span>
                            <span class="info-value" id="infoCreated">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Login</span>
                            <span class="info-value" id="infoLastLogin">—</span>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="dashboard__card">
                    <div class="card-header">
                        <h2><i class="fas fa-life-ring"></i> Need Help?</h2>
                    </div>
                    <div class="card-body">
                        <p class="help-text">
                            Forgot your password or having account problems?<br>
                            Contact an NCA moderator through our Discord server.
                        </p>

                        <a href="https://discord.gg/jgrpYzMrc" target="_blank" rel="noopener noreferrer" class="btn btn--primary btn--full discord-btn">
                            <i class="fab fa-discord"></i> Join NCA Discord
                        </a>

                        <div class="help-contacts">
                            <div class="contact-group">
                                <span class="contact-label">Moderators:</span>
                                <span class="contact-value">godxpromise, k2106472</span>
                            </div>
                            <div class="contact-group">
                                <span class="contact-label">CEO:</span>
                                <span class="contact-value">zeroair41</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ============================================================== -->
    <!-- FOOTER                                                         -->
    <!-- ============================================================== -->
    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__brand">
                <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" width="28" height="28">
                <span class="footer__brand-text">NCA <span class="brand__ctf">CTF</span></span>
                <span class="footer__tagline">| Batch 4</span>
            </div>
            <div class="footer__copyright">
                &copy; 2026 <strong>NCA@Nepal</strong>
            </div>
        </div>
    </footer>

    <!-- ============================================================== -->
    <!-- JAVASCRIPT                                                     -->
    <!-- ============================================================== -->
    <script src="<?= $baseUrl ?>/assets/js/api.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/dashboard.js"></script>
</body>
</html>