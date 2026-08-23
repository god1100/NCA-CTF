<?php

$baseUrl = '/NCA-CTF/public';
$currentPage = 'dashboard';
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
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
</head>

<body>

    <!-- ============================================================== -->
    <!-- HEADER (Universal)                                             -->
    <!-- ============================================================== -->
    <?php include __DIR__ . '/../resources/views/components/header.php'; ?>

    <!-- ============================================================== -->
    <!-- DASHBOARD CONTENT                                              -->
    <!-- ============================================================== -->
    <main class="dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <div class="dashboard__welcome">
                <div>
                    <h1 class="dashboard__title">Welcome back, <span id="welcomeUsername">User</span></h1>
                    <p class="dashboard__subtitle">Ready to continue the challenge?</p>
                </div>
                <a href="<?= $baseUrl ?>/challenges.php" class="btn btn--primary">
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
                    </div>
                </div>

                <!-- Join NCA Committee Section -->
                <div class="dashboard__card">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> Join NCA Committee</h2>
                    </div>
                    <div class="card-body">
                        <p class="help-text">
                            Connect with the NCA community, get involved in cybersecurity events,
                            and collaborate with fellow security enthusiasts.
                        </p>

                        <a href="https://discord.gg/jgrpYzMrc" target="_blank" rel="noopener noreferrer" class="btn btn--primary btn--full discord-btn">
                            <i class="fab fa-discord"></i> Join NCA Discord
                        </a>

                        <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-top: 1rem; text-align: center;">
                            Be part of Nepal's active cybersecurity community.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ============================================================== -->
    <!-- FOOTER (Universal)                                             -->
    <!-- ============================================================== -->
    <?php include __DIR__ . '/../resources/views/components/footer.php'; ?>

    <!-- ============================================================== -->
    <!-- JAVASCRIPT                                                     -->
    <!-- ============================================================== -->
    <script src="<?= $baseUrl ?>/assets/js/api.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/home.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/dashboard.js"></script>
</body>

</html>