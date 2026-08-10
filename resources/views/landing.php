<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCA Batch 4 CTF — Foundation</title>
    <meta name="description" content="NCA Batch 4 Private Cybersecurity CTF Platform">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">
            <span class="brand__nca">NCA</span>
            <span class="brand__batch">BATCH 4</span>
            <span class="brand__ctf">CTF</span>
        </div>
        <span class="badge badge--info">Phase 0 &middot; Foundation</span>
    </header>

    <main class="hero">
        <p class="hero__eyebrow">WEB &bull; PWN &bull; CRYPTO &bull; GENERAL</p>
        <h1 class="hero__title">NCA BATCH 4<br>CYBERSECURITY CTF</h1>
        <p class="hero__tagline">Learn. Hack. Compete.</p>

        <div class="status-card">
            <h2 class="status-card__title">Platform Status</h2>
            <p class="status-card__body">
                The application foundation is running. Authentication, teams, challenges,
                and the leaderboard are not implemented yet &mdash; they arrive in later
                development phases.
            </p>
            <code class="status-card__endpoint" id="health-endpoint">GET /api/v1/health</code>
            <button class="btn" id="check-health">Check API Health</button>
            <pre class="status-card__result" id="health-result" aria-live="polite"></pre>
        </div>
    </main>

    <footer class="footer">
        <p>NCA Batch 4 Private CTF Platform &mdash; Internal training use only.</p>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
