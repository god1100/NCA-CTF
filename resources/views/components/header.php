<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '';
$currentPage = $currentPage ?? '';
?>
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
                <li><a href="<?= $baseUrl ?>/" <?= $currentPage === 'home' ? 'class="active"' : '' ?>>Home</a></li>
                <li><a href="<?= $baseUrl ?>/challenges.php" <?= $currentPage === 'challenges' ? 'class="active"' : '' ?>>Challenges</a></li>
                <li><a href="<?= $baseUrl ?>/categories.php" <?= $currentPage === 'categories' ? 'class="active"' : '' ?>>Categories</a></li>
                <li><a href="<?= $baseUrl ?>/leaderboard.php" <?= $currentPage === 'leaderboard' ? 'class="active"' : '' ?>>Leaderboard</a></li>
                <li><a href="<?= $baseUrl ?>/about.php" <?= $currentPage === 'about' ? 'class="active"' : '' ?>>About</a></li>
            </ul>
            <div class="navbar__actions" id="navActions">
                <!-- Will be populated by JavaScript based on auth state -->
            </div>
        </div>
    </div>
</nav>