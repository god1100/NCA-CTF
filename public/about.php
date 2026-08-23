<?php
// Start session FIRST - before ANY HTML output
if (session_status() === PHP_SESSION_NONE) {
    session_name('nca_ctf_session');
    session_start();
}

$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
$currentPage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — NCA CTF</title>
    <meta name="description" content="NCA Batch 4 CTF - A private cybersecurity challenge platform for NCA students, hackers, and pentesters to enhance their skills.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
    </script>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/home.css">
    <style>
        .about-hero {
            padding: 4rem 0 3rem;
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
            text-align: center;
        }
        .about-hero__title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        .about-hero__sub {
            color: var(--color-text-secondary);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        .about-content {
            padding: 4rem 0;
            background: var(--color-bg);
        }
        .about-content__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .about-content__text p {
            color: var(--color-text-secondary);
            line-height: 1.8;
            margin-bottom: 1.2rem;
            font-size: 1rem;
            text-align: justify;
            text-justify: inter-word;
        }
        .about-content__text p strong {
            color: var(--color-text-primary);
        }
        .about-content__text .highlight {
            color: var(--color-accent-light);
            font-weight: 600;
        }
        .about-content__links {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        .link-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 1.2rem 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }
        .link-card:hover {
            border-color: var(--color-accent-light);
            transform: translateX(6px);
            box-shadow: 0 4px 15px rgba(71, 37, 37, 0.2);
        }
        .link-card h4 {
            color: var(--color-text-primary);
            font-size: 1.05rem;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .link-card h4 i {
            color: var(--color-accent-light);
            font-size: 1rem;
        }
        .link-card p {
            color: var(--color-text-muted);
            font-size: 0.9rem;
            margin: 0;
        }
        .link-card .arrow {
            color: var(--color-accent-light);
            font-size: 0.8rem;
            margin-left: 0.3rem;
            transition: transform 0.2s;
        }
        .link-card:hover .arrow {
            transform: translateX(4px);
        }
        .external-link {
            color: var(--color-accent-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .external-link:hover {
            color: var(--color-text-primary);
            text-decoration: underline;
        }
        .badge-private {
            display: inline-block;
            background: var(--color-accent-glow);
            border: 1px solid var(--color-accent-light);
            color: var(--color-accent-light);
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.3rem;
        }
        @media (max-width: 768px) {
            .about-content__grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .about-hero__title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Include Header -->
    <?php include __DIR__ . '/../resources/views/components/header.php'; ?>

    <!-- ============================================================== -->
    <!-- ABOUT HERO                                                     -->
    <!-- ============================================================== -->
    <section class="about-hero">
        <div class="container">
            <span class="badge-private">🔒 Private • Batch 4</span>
            <h1 class="about-hero__title">About NCA CTF</h1>
            <p class="about-hero__sub">
                A private cybersecurity challenge platform crafted for the <strong>NCA Batch 4</strong> community — 
                where students, hackers, and pentesters come together to sharpen their skills.
            </p>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- ABOUT CONTENT                                                  -->
    <!-- ============================================================== -->
    <section class="about-content">
        <div class="container">
            <div class="about-content__grid">

                <div class="about-content__text">
                    <p>
                        The <strong>NCA CTF</strong> is more than just a competition — it's a 
                        <span class="highlight">private training ground</span> designed exclusively for 
                        the NCA Batch 4 cohort. Our mission is to provide a space where cybersecurity 
                        enthusiasts can test, refine, and expand their skills in a realistic, 
                        challenge-driven environment.
                    </p>
                    <p>
                        Whether you're a student taking your first steps into the world of hacking, 
                        a pentester looking to sharpen your edge, or a curious mind eager to explore 
                        the depths of cybersecurity — this platform is built for you. Every challenge 
                        is crafted to push your boundaries and deepen your understanding of real-world 
                        vulnerabilities and defenses.
                    </p>
                    <p>
                        <strong>NCA@Nepal</strong> believes in the power of community and shared knowledge. 
                        Our motto, <span class="highlight">"Learn and Share: Growing Together in Cybersecurity,"</span> 
                        is at the heart of everything we do. The NCA CTF is our way of giving back — 
                        fostering a culture of growth, collaboration, and excellence.
                    </p>
                    <p style="margin-top: 1.5rem; font-size: 0.95rem; color: var(--color-text-muted);">
                        <i class="fas fa-lock" style="color: var(--color-accent-light); margin-right: 0.5rem;"></i>
                        This is a <strong>private</strong> platform. Access is restricted to NCA Batch 4 participants.
                    </p>
                </div>

                <div class="about-content__links">
                    <a href="https://ncagroup.com.np/" target="_blank" rel="noopener noreferrer" class="link-card">
                        <h4>
                            <i class="fas fa-building"></i>
                            NCA Group
                            <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        </h4>
                        <p>Visit the official NCA Group website — Nepal's trusted name in cybersecurity training and research.</p>
                    </a>

                    <a href="https://ncateam.xyz" target="_blank" rel="noopener noreferrer" class="link-card">
                        <h4>
                            <i class="fas fa-globe"></i>
                            NCA@Nepal
                            <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        </h4>
                        <p>Explore Nepal's active cybersecurity community and CTF team — learn, share, and grow together.</p>
                    </a>

                    <a href="https://handbook.ncateam.xyz/" target="_blank" rel="noopener noreferrer" class="link-card">
                        <h4>
                            <i class="fas fa-book-open"></i>
                            Hacking Handbook
                            <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        </h4>
                        <p>A comprehensive introduction to penetration testing — completely free and accessible to everyone.</p>
                    </a>

                    <a href="https://toolkit.ncateam.xyz/" target="_blank" rel="noopener noreferrer" class="link-card">
                        <h4>
                            <i class="fas fa-tools"></i>
                            NCA Toolkit
                            <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        </h4>
                        <p>A curated collection of cybersecurity tools for encoding, cryptography, and data manipulation.</p>
                    </a>

                    <div style="margin-top: 0.5rem; padding: 1rem; border-top: 1px solid var(--color-border);">
                        <p style="color: var(--color-text-muted); font-size: 0.85rem; margin: 0;">
                            <i class="fas fa-envelope" style="color: var(--color-accent-light); margin-right: 0.5rem;"></i>
                            For inquiries: <a href="mailto:us@ncateam.xyz" class="external-link">us@ncateam.xyz</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include __DIR__ . '/../resources/views/components/footer.php'; ?>

    <script src="<?= $baseUrl ?>/assets/js/api.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/home.js"></script>
</body>
</html>