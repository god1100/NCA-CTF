<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — NCA CTF</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/home.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <div class="navbar__brand">
                <a href="<?= $baseUrl ?>/" class="navbar__logo-link">
                    <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" class="navbar__logo" width="40" height="40">
                    <span class="navbar__brand-text">NCA <span class="brand__ctf">CTF</span></span>
                </a>
            </div>
            <div class="navbar__menu">
                <ul class="navbar__links">
                    <li><a href="<?= $baseUrl ?>/">Home</a></li>
                    <li><a href="<?= $baseUrl ?>/challenges.php">Challenges</a></li>
                    <li><a href="<?= $baseUrl ?>/about.php" class="active">About Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero" style="min-height: 40vh;">
        <div class="hero__container" style="grid-template-columns: 1fr;">
            <div class="hero__content" style="text-align: center; max-width: 800px; margin: 0 auto;">
                <h1 class="hero__title" style="font-size: 3rem;">About NCA CTF</h1>
                <p class="hero__desc" style="max-width: 100%;">
                    The NCA CTF is a private cybersecurity competition platform built for the NCA Batch 4 community.
                </p>
            </div>
        </div>
    </section>

    <section class="about" style="padding: var(--space-lg) 0;">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto;">
                <h2 style="color: var(--color-accent-light); margin-bottom: var(--space-md);">Our Mission</h2>
                <p style="color: var(--color-text-secondary); margin-bottom: var(--space-md);">
                    To provide a hands-on cybersecurity training environment where participants can develop practical skills in a competitive, collaborative setting.
                </p>
                <h2 style="color: var(--color-accent-light); margin-bottom: var(--space-md);">Who We Are</h2>
                <p style="color: var(--color-text-secondary); margin-bottom: var(--space-md);">
                    <strong>NCA Group</strong> is a cybersecurity training and research organization based in Nepal. We specialize in vulnerability assessment, penetration testing, and security education.
                </p>
                <h2 style="color: var(--color-accent-light); margin-bottom: var(--space-md);">The CTF</h2>
                <p style="color: var(--color-text-secondary);">
                    This Capture The Flag platform is part of the NCA Batch 4 internship program. It features challenges across multiple categories including Web, Crypto, Forensics, Pwn, OSINT, and more.
                </p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container footer__container">
            <div class="footer__brand">
                <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" width="28" height="28">
                <span class="footer__brand-text">NCA <span class="brand__ctf">CTF</span></span>
                <span class="footer__tagline">| Batch 4</span>
            </div>
            <div class="footer__links">
                <a href="<?= $baseUrl ?>/">Home</a>
                <a href="<?= $baseUrl ?>/challenges.php">Challenges</a>
                <a href="<?= $baseUrl ?>/about.php">About</a>
                <a href="<?= $baseUrl ?>/register.php">Join</a>
            </div>
            <div class="footer__copyright">
                &copy; 2026 <strong>NCA@Nepal</strong>
            </div>
        </div>
    </footer>
</body>
</html>