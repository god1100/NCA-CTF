<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCA CTF – Batch 4 Cybersecurity Challenge</title>
    <meta name="description" content="Private Capture The Flag platform for NCA Batch 4">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
    </script>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/home.css">
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
                    <li><a href="<?= $baseUrl ?>/" class="active">Home</a></li>
                    <li><a href="<?= $baseUrl ?>/challenges.php">Challenges</a></li>
                    <li><a href="<?= $baseUrl ?>/about.php">About Us</a></li>
                </ul>
                <div class="navbar__actions" id="navActions">
                    <a href="<?= $baseUrl ?>/login.php" class="btn btn--secondary">Login</a>
                    <a href="<?= $baseUrl ?>/register.php" class="btn btn--primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ============================================================== -->
    <!-- HERO SECTION                                                   -->
    <!-- ============================================================== -->
    <section class="hero" id="hero">
        <div class="hero__container">
            <div class="hero__content">
                <p class="hero__badge">NCA Batch 4</p>
                <h1 class="hero__title">Capture The Flag</h1>
                <p class="hero__tagline">Think. Exploit. Capture.</p>
                <p class="hero__desc">A private cybersecurity challenge platform built for the NCA community.</p>
                <div class="hero__cta">
                    <a href="<?= $baseUrl ?>/challenges.php" class="btn btn--primary">Explore Challenges</a>
                    <a href="<?= $baseUrl ?>/register.php" class="btn btn--secondary">Join Now</a>
                </div>
            </div>
            <div class="hero__visual">
                <div class="terminal-card">
                    <div class="terminal-card__header">
                        <span class="terminal-card__dot terminal-card__dot--red"></span>
                        <span class="terminal-card__dot terminal-card__dot--yellow"></span>
                        <span class="terminal-card__dot terminal-card__dot--green"></span>
                        <span class="terminal-card__title">nca@ctf:~$</span>
                    </div>
                    <div class="terminal-card__body">
                        <div class="terminal-line">$ whoami</div>
                        <div class="terminal-line terminal-output">NCA@Team</div>
                        <div class="terminal-line">$ ls /challenges/</div>
                        <div class="terminal-line terminal-output">web/  crypto/  pwn/  osint/  misc/</div>
                        <div class="terminal-line cursor-blink">$</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- ABOUT NCA CTF                                                  -->
    <!-- ============================================================== -->
    <section class="about" id="about">
        <div class="container">
            <h2 class="section-title">About NCA CTF</h2>
            
            <div class="private-notice">
                <i class="fas fa-lock"></i>
                <span>This is a <strong>private CTF platform</strong> exclusively for <strong>NCA Batch 4</strong> students. Not publicly available.</span>
            </div>

            <div class="about__grid">
                <div class="about__content">
                    <p class="about__text">
                        The NCA Group is a cybersecurity training and research organization based in Nepal. 
                        We specialize in vulnerability assessment, penetration testing, and security education. 
                        Our mission is to bridge the gap between theoretical knowledge and practical skills, 
                        preparing the next generation of security professionals through hands-on training and 
                        real-world challenge scenarios.
                    </p>
                    <p class="about__text">
                        The NCA CTF platform is a reflection of that mission. It is designed to foster a 
                        competitive yet collaborative learning environment where participants can test their 
                        abilities against challenges that mirror actual industry threats. We believe that 
                        the best way to learn security is by doing, and this CTF is our way of giving back 
                        to the community that continues to inspire us.
                    </p>
                    <blockquote class="hacker-quote">
                        <i class="fas fa-quote-left"></i>
                        I don't fear what I see, but I fear what I don't see.
                        <span class="quote-author">— Anonymous</span>
                    </blockquote>
                </div>

                <div class="about__image">
                    <div class="about__image-wrapper">
                        <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="Cybersecurity" class="about__img">
                        <div class="about__image-overlay">
                            <i class="fas fa-user-secret"></i>
                            <span>NCA CTF</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- FINAL CTA                                                      -->
    <!-- ============================================================== -->
    <section class="final-cta" id="cta">
        <div class="container">
            <h2 class="final-cta__title">Ready to Begin?</h2>
            <p class="final-cta__sub">Join the NCA Batch 4 CTF and put your skills to the test.</p>
            <a href="<?= $baseUrl ?>/register.php" class="btn btn--large btn--primary">Join NCA‑CTF</a>
        </div>
    </section>

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

    <script src="<?= $baseUrl ?>/assets/js/home.js"></script>
</body>
</html>